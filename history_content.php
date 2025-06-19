<?php
// session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';

// Fetch all completed cash advances
$completed = [];
$total_liquidated = 0;
$total_count = 0;

try {
    // Get completed liquidations
    $stmt = $pdo->query("SELECT * FROM granted_cash_advances WHERE status = 'completed' ORDER BY date_completed DESC");
    $completed = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total liquidated amount
    $stmt = $pdo->query("SELECT SUM(amount) as total_amount, COUNT(*) as total_count FROM granted_cash_advances WHERE status = 'completed'");
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_liquidated = $totals['total_amount'] ?? 0;
    $total_count = $totals['total_count'] ?? 0;

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="content-header animate__animated animate__fadeInDown">
    <div class="welcome-text">
        <h1>Liquidation History <span class="admin-badge">Administrator</span></h1>
        <p>View completed cash advance liquidations</p>
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
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-success text-white shadow animate__animated animate__fadeInLeft">
                <div class="card-body text-center">
                    <h3 class="mb-0">₱<?php echo number_format($total_liquidated, 2); ?></h3>
                    <p class="mb-0">Total Liquidated Amount</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-info text-white shadow animate__animated animate__fadeInRight">
                <div class="card-body text-center">
                    <h3 class="mb-0"><?php echo number_format($total_count); ?></h3>
                    <p class="mb-0">Total Completed Liquidations</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card shadow animate__animated animate__fadeInUp">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0"><i class="fas fa-history me-2"></i>Liquidation History</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($completed)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No completed liquidations found. Complete some liquidations from the pending page to see them here.
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
                                        <th>Date Completed</th>
                                        <th>Duration</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($completed as $row): ?>
                                        <?php 
                                        $date_granted = new DateTime($row['date_granted']);
                                        $date_completed = new DateTime($row['date_completed']);
                                        $duration = $date_granted->diff($date_completed)->days;
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                            <td><strong>₱<?php echo number_format($row['amount'], 2); ?></strong></td>
                                            <td>
                                                <span class="badge bg-info"><?php echo ucwords(str_replace('_', ' ', $row['type'])); ?></span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['date_granted'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['due_date'])); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['date_completed'])); ?></td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $duration; ?> days</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">Completed</span>
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