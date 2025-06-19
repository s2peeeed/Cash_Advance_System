<?php
// session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';
require_once 'includes/EmailSender.php';

// Function to send reminder email
function sendDueDateReminder($name, $email, $purpose, $amount, $due_date, $type) {
    if (empty($email)) {
        error_log("No email provided for $name");
        return false;
    }
    try {
        $emailSender = new EmailSender();
        $subject = "URGENT: Cash Advance Liquidation Due";
        $message = "
            <p>Dear " . htmlspecialchars($name ?? '') . ",</p>
            <p>This is a reminder that your cash advance liquidation is now due:</p>
            <div class='details'>
                <ul>
                    <li><strong>Purpose:</strong> " . htmlspecialchars($purpose ?? '') . "</li>
                    <li><strong>Amount:</strong> ₱" . number_format($amount ?? 0, 2) . "</li>
                    <li><strong>Type:</strong> " . htmlspecialchars($type ?? 'Unknown') . "</li>
                    <li><strong>Due Date:</strong> " . htmlspecialchars($due_date ?? '') . "</li>
                </ul>
            </div>
            <p class='urgent'>Please submit your liquidation documents as soon as possible to avoid any penalties.</p>
            <p>If you have already submitted your liquidation, please disregard this message.</p>
            <p>Thank you for your immediate attention to this matter.</p>
        ";
        $emailSender->sendReminder($email, $subject, $message);
        return true;
    } catch (Exception $e) {
        error_log("Failed to send reminder email to $email: " . $e->getMessage());
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reminder'])) {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $purpose = $_POST['purpose'] ?? '';
    $amount = $_POST['amount'] ?? 0;
    $due_date = $_POST['due_date'] ?? '';
    $type = $_POST['type'] ?? '';
    
    if (sendDueDateReminder($name, $email, $purpose, $amount, $due_date, $type)) {
        $reminder_sent = true;
        $reminder_count = 1;
    } else {
        $error = "Failed to send reminder to $name. No valid email provided.";
    }
}

// Fetch all pending liquidations with search
$pending = [];
$today = date('Y-m-d');
$reminder_sent = false;
$reminder_count = 0;
$error = null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    // Join granted_cash_advances with employees to get type
    $query = "
        SELECT gca.*, e.type as employee_type 
        FROM granted_cash_advances gca 
        LEFT JOIN employees e ON gca.name = e.user_name 
        WHERE gca.status = 'pending' 
    ";
    if ($search) {
        $query .= " AND (gca.name LIKE :search OR gca.purpose LIKE :search OR gca.type LIKE :search OR gca.email LIKE :search OR e.type LIKE :search)";
    }
    $query .= " ORDER BY gca.due_date ASC";
    $stmt = $pdo->prepare($query);
    if ($search) {
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    $stmt->execute();
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log if emails or types are empty
    foreach ($pending as $row) {
        if (empty($row['email'])) {
            error_log("No email found for name: " . ($row['name'] ?? 'Unknown') . ", id: " . ($row['id'] ?? 'Unknown'));
        }
        if (empty($row['employee_type'])) {
            error_log("No type found for name: " . ($row['name'] ?? 'Unknown') . ", id: " . ($row['id'] ?? 'Unknown') . ", using gca.type: " . ($row['type'] ?? 'Unknown'));
        }
    }

    // Check for due dates and send reminders
    foreach ($pending as $row) {
        $type_to_use = !empty($row['employee_type']) ? $row['employee_type'] : ($row['type'] ?? 'Unknown');
        if ($row['due_date'] <= $today && !empty($row['email'])) {
            if (sendDueDateReminder($row['name'], $row['email'], $row['purpose'], $row['amount'], $row['due_date'], $type_to_use)) {
                $reminder_sent = true;
                $reminder_count++;
            }
        }
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
    error_log("Database error in pending.php: " . $e->getMessage());
}
?>

<div class="content-header animate__animated animate__fadeInDown">
    <div class="welcome-text">
        <h1>Pending Liquidations <span class="admin-badge">Administrator</span></h1>
        <p>Review and manage pending cash advance liquidations</p>
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
        <div class="col-12">
            <div class="card shadow animate__animated animate__fadeInUp">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Pending Liquidations</h4>
                </div>
                <div class="card-body">
                    <?php if ($reminder_sent): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>Reminder sent successfully to <?php echo $reminder_count; ?> employee(s).
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Search Form -->
                    <form method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search by name, purpose, type, or email..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                    <?php if ($search): ?>
                                        <a href="?" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>

                    <!-- Pending Liquidations Table -->
                    <?php if (empty($pending)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No pending liquidations found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Email</th>
                                        <th>Purpose</th>
                                        <th>Amount</th>
                                        <th>Type</th>
                                        <th>Date Granted</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pending as $row): ?>
                                        <?php
                                        $due_date = new DateTime($row['due_date']);
                                        $today = new DateTime();
                                        $days_until_due = $today->diff($due_date)->days;
                                        $is_overdue = $due_date < $today;
                                        $is_due_soon = $days_until_due <= 3 && !$is_overdue;
                                        $row_class = $is_overdue ? 'overdue' : ($is_due_soon ? 'due-soon' : '');
                                        $status_badge_class = $is_overdue ? 'status-overdue' : ($is_due_soon ? 'status-due-soon' : '');
                                        $status_text = $is_overdue ? 'Overdue' : ($is_due_soon ? 'Due Soon' : 'Pending');
                                        ?>
                                        <tr class="<?php echo $row_class; ?>">
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                            <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                                            <td><?php echo ucwords(str_replace('_', ' ', $row['type'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['date_granted'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                            <td>
                                                <span class="status-badge <?php echo $status_badge_class; ?>">
                                                    <?php echo $status_text; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if (!empty($row['email'])): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['name']); ?>">
                                                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($row['email']); ?>">
                                                        <input type="hidden" name="purpose" value="<?php echo htmlspecialchars($row['purpose']); ?>">
                                                        <input type="hidden" name="amount" value="<?php echo $row['amount']; ?>">
                                                        <input type="hidden" name="due_date" value="<?php echo $row['due_date']; ?>">
                                                        <input type="hidden" name="type" value="<?php echo htmlspecialchars($row['type']); ?>">
                                                        <button type="submit" name="send_reminder" class="btn btn-sm btn-warning" onclick="return confirm('Send reminder email to <?php echo htmlspecialchars($row['name']); ?>?')">
                                                            <i class="fas fa-envelope"></i> Send Reminder
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">No email</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .due-soon {
        background-color: #fff3cd;
    }

    .overdue {
        background-color: #f8d7da;
    }

    .status-badge {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 12px;
        font-weight: bold;
    }

    .status-due-soon {
        background-color: #ffc107;
        color: #000;
    }

    .status-overdue {
        background-color: #dc3545;
        color: #fff;
    }
</style>

<script>
const searchInput = document.getElementById('searchInput');
const tableRows = document.querySelectorAll('.table tbody tr');

searchInput.addEventListener('input', function() {
    const searchValue = this.value.toLowerCase().trim();
    let anyVisible = false;

    tableRows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.includes(searchValue)) {
            row.style.display = '';
            anyVisible = true;
        } else {
            row.style.display = 'none';
        }
    });

    // Optionally, show a "no results" message if nothing is visible
    const noResults = document.getElementById('noResultsMessage');
    if (noResults) {
        noResults.style.display = anyVisible ? 'none' : '';
    }
});
</script> 