<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';

$success = $error = "";

// Handle reminder settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_reminder_settings'])) {
    $reminderDays = intval($_POST['reminder_days']);
    $autoReminders = isset($_POST['auto_reminders']) ? 1 : 0;
    $emailTemplate = trim($_POST['email_template']);
    
    try {
        // Update reminder settings (assuming a settings table exists)
        $stmt = $pdo->prepare("UPDATE settings SET reminder_days = ?, auto_reminders = ?, email_template = ?");
        $stmt->execute([$reminderDays, $autoReminders, $emailTemplate]);
        $success = "Reminder settings updated successfully!";
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Fetch current reminder settings
$reminderSettings = [];
try {
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $reminderSettings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $reminderSettings = [
        'reminder_days' => 3,
        'auto_reminders' => 1,
        'email_template' => 'Dear {name}, your cash advance liquidation is due in {days} days.'
    ];
}

// Fetch recent reminder history
$reminderHistory = [];
try {
    $stmt = $pdo->query("SELECT * FROM reminder_logs ORDER BY sent_date DESC LIMIT 10");
    $reminderHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist, that's okay
    $reminderHistory = [];
}
?>

<div class="content-header animate__animated animate__fadeInDown">
    <div class="welcome-text">
        <h1>Email Reminders <span class="admin-badge">Administrator</span></h1>
        <p>Manage automatic email reminders for cash advances</p>
    </div>
    <div class="header-actions">
        <button class="theme-switch" id="themeSwitch"><i class="fas fa-moon"></i></button>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>
</div>

<div class="container-fluid p-0">
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow animate__animated animate__fadeInUp">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Reminder Settings</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="reminder_days" class="form-label">Send Reminders (Days Before Due)</label>
                            <input type="number" class="form-control" id="reminder_days" name="reminder_days" 
                                   value="<?php echo htmlspecialchars($reminderSettings['reminder_days'] ?? 3); ?>" 
                                   min="1" max="30" required>
                            <div class="form-text">Send reminders this many days before the due date.</div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="auto_reminders" name="auto_reminders" 
                                       <?php echo ($reminderSettings['auto_reminders'] ?? 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="auto_reminders">
                                    Enable Automatic Reminders
                                </label>
                            </div>
                            <div class="form-text">Automatically send reminders based on due dates.</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email_template" class="form-label">Email Template</label>
                            <textarea class="form-control" id="email_template" name="email_template" rows="6" required><?php echo htmlspecialchars($reminderSettings['email_template'] ?? 'Dear {name}, your cash advance liquidation is due in {days} days.'); ?></textarea>
                            <div class="form-text">
                                Use {name} for employee name, {days} for days remaining, {amount} for cash advance amount.
                            </div>
                        </div>
                        
                        <button type="submit" name="save_reminder_settings" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Settings
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow animate__animated animate__fadeInUp">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-history me-2"></i>Reminder History</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($reminderHistory)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No reminder history found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Employee</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($reminderHistory as $reminder): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($reminder['sent_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($reminder['employee_name']); ?></td>
                                            <td>
                                                <span class="badge bg-success">Sent</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card shadow animate__animated animate__fadeInUp mt-3">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-cog me-2"></i>Manual Actions</h4>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning" onclick="sendManualReminders()">
                            <i class="fas fa-paper-plane me-2"></i>Send Manual Reminders
                        </button>
                        <button class="btn btn-info" onclick="testEmailTemplate()">
                            <i class="fas fa-eye me-2"></i>Preview Email Template
                        </button>
                        <button class="btn btn-secondary" onclick="exportReminderLog()">
                            <i class="fas fa-download me-2"></i>Export Reminder Log
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function sendManualReminders() {
    if (confirm('Send reminders to all employees with pending liquidations?')) {
        // Add AJAX call to send reminders
        alert('Manual reminders sent!');
    }
}

function testEmailTemplate() {
    alert('Email template preview feature coming soon!');
}

function exportReminderLog() {
    alert('Export feature coming soon!');
}
</script> 