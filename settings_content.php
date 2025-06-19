<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';

$success = $error = "";

// Fetch current settings (assume a 'settings' table exists)
$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $settings = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $systemName = trim($_POST['system_name']);
    $systemEmail = trim($_POST['system_email']);
    $reminderDays = intval($_POST['reminder_days']);

    if ($systemName && $systemEmail) {
        try {
            // Update settings (assume a 'settings' table exists)
            $stmt = $pdo->prepare("UPDATE settings SET system_name = ?, system_email = ?, reminder_days = ?");
            $stmt->execute([$systemName, $systemEmail, $reminderDays]);
            $success = "Settings updated successfully!";
            // Refresh settings
            $stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields.";
    }
}
?>

<div class="content-header animate__animated animate__fadeInDown">
    <div class="welcome-text">
        <h1>System Settings <span class="admin-badge">Administrator</span></h1>
        <p>Manage system configuration and preferences</p>
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
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow animate__animated animate__fadeInUp">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-cog me-2"></i>System Settings</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="system_name" class="form-label">System Name</label>
                            <input type="text" class="form-control" id="system_name" name="system_name" value="<?php echo htmlspecialchars($settings['system_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="system_email" class="form-label">System Email</label>
                            <input type="email" class="form-control" id="system_email" name="system_email" value="<?php echo htmlspecialchars($settings['system_email'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="reminder_days" class="form-label">Reminder Days</label>
                            <input type="number" class="form-control" id="reminder_days" name="reminder_days" value="<?php echo htmlspecialchars($settings['reminder_days'] ?? '15'); ?>" required>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div> 