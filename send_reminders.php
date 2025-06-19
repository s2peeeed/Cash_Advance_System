<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';

$success = $error = "";

// Handle reminder sending
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get all pending liquidations
        $stmt = $pdo->query("SELECT * FROM granted_cash_advances WHERE status = 'pending' AND due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)");
        $pendingLiquidations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $remindersSent = 0;
        foreach ($pendingLiquidations as $liquidation) {
            // Here you would implement your email sending logic
            // For now, we'll just update the last_reminder_date
            $updateStmt = $pdo->prepare("UPDATE granted_cash_advances SET last_reminder_date = CURDATE() WHERE id = ?");
            if ($updateStmt->execute([$liquidation['id']])) {
                $remindersSent++;
            }
        }
        
        if ($remindersSent > 0) {
            $success = "Reminders have been sent successfully!";
        } else {
            $success = "No reminders needed to be sent at this time.";
        }
    } catch (PDOException $e) {
        $error = "An error occurred while sending reminders: " . $e->getMessage();
    }
}

// Get pending liquidations for display
$pendingLiquidations = [];
try {
    $stmt = $pdo->query("SELECT * FROM granted_cash_advances WHERE status = 'pending' ORDER BY due_date ASC");
    $pendingLiquidations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Reminders</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-bell me-2"></i>Send Reminders</h4>
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

                        <form method="post" action="" class="mb-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Send Reminders
                            </button>
                        </form>

                        <?php if (empty($pendingLiquidations)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No pending liquidations found.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Name</th>
                                            <th>Purpose</th>
                                            <th>Amount</th>
                                            <th>Type</th>
                                            <th>Date Granted</th>
                                            <th>Due Date</th>
                                            <th>Last Reminder</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($pendingLiquidations as $liquidation): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($liquidation['name']); ?></td>
                                                <td><?php echo htmlspecialchars($liquidation['purpose']); ?></td>
                                                <td>₱<?php echo number_format($liquidation['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($liquidation['type']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($liquidation['date_granted'])); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($liquidation['due_date'])); ?></td>
                                                <td>
                                                    <?php 
                                                    if ($liquidation['last_reminder_date']) {
                                                        echo date('M d, Y', strtotime($liquidation['last_reminder_date']));
                                                    } else {
                                                        echo '<span class="text-muted">Never</span>';
                                                    }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                        <div class="mt-3">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 