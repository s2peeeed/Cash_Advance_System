<?php
// session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';

// Fetch comprehensive stats for reports
$totalGranted = 0;
$totalCompleted = 0;
$totalLiquidated = 0; // Legacy support
$pendingCount = 0;
$overdueCount = 0;
$totalAmount = 0;
$totalCompletedAmount = 0;
$totalPendingAmount = 0;
$monthlyStats = [];

// Handle report filters from GET
$filter_start = $_GET['start_date'] ?? '';
$filter_end = $_GET['end_date'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? '';

$where = [];
$params = [];
if ($filter_start) {
    $where[] = 'date_granted >= ?';
    $params[] = $filter_start;
}
if ($filter_end) {
    $where[] = 'date_granted <= ?';
    $params[] = $filter_end;
}
if ($filter_type) {
    $where[] = 'type = ?';
    $params[] = $filter_type;
}
if ($filter_status) {
    $where[] = 'status = ?';
    $params[] = $filter_status;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

try {
    // Basic counts
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM granted_cash_advances");
    $totalGranted = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM granted_cash_advances WHERE status = 'completed'");
    $totalCompleted = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM granted_cash_advances WHERE status = 'liquidated'");
    $totalLiquidated = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM granted_cash_advances WHERE status = 'pending'");
    $pendingCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM granted_cash_advances WHERE status = 'overdue'");
    $overdueCount = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Amount calculations
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM granted_cash_advances");
    $totalAmount = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM granted_cash_advances WHERE status = 'completed'");
    $totalCompletedAmount = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    $stmt = $pdo->query("SELECT SUM(amount) as total FROM granted_cash_advances WHERE status = 'pending'");
    $totalPendingAmount = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    
    // Monthly statistics for completed liquidations
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(date_completed, '%Y-%m') as month,
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM granted_cash_advances 
        WHERE status = 'completed' AND date_completed IS NOT NULL
        GROUP BY DATE_FORMAT(date_completed, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $monthlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Type breakdown
    $stmt = $pdo->query("
        SELECT 
            type,
            COUNT(*) as count,
            SUM(amount) as total_amount
        FROM granted_cash_advances 
        GROUP BY type
        ORDER BY total_amount DESC
    ");
    $typeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Filtered stats for table
    $stmt = $pdo->prepare("SELECT * FROM granted_cash_advances $where_sql ORDER BY date_granted DESC");
    if (!empty($params)) {
        $stmt->execute($params);
    } else {
        $stmt->execute();
    }
    $filteredStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Calculate completion rate
$completionRate = $totalGranted > 0 ? round(($totalCompleted / $totalGranted) * 100, 1) : 0;
?>

<!-- <div class="content-header animate__animated animate__fadeInDown">
    <div class="welcome-text">
        <h1>Reports <span class="admin-badge">Administrator</span></h1>
        <p>Generate and view system reports</p>
    </div>
    <div class="header-actions">
        <button class="theme-switch" id="themeSwitch"><i class="fas fa-moon"></i></button>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>
</div> -->

<div class="container-fluid p-0">
    <div class="row">
        <div class="col-12">
            <div class="card shadow animate__animated animate__fadeInUp">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-chart-bar me-2"></i>System Reports & Analytics</h4>
                </div>
                <div class="card-body">
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h6>Total Granted</h6>
                                    <h4><?php echo number_format($totalGranted); ?></h4>
                                    <small>Cash Advances</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h6>Completed</h6>
                                    <h4><?php echo number_format($totalCompleted); ?></h4>
                                    <small>Liquidations</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-warning text-dark">
                                <div class="card-body text-center">
                                    <h6>Pending</h6>
                                    <h4><?php echo number_format($pendingCount); ?></h4>
                                    <small>Liquidations</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h6>Overdue</h6>
                                    <h4><?php echo number_format($overdueCount); ?></h4>
                                    <small>Liquidations</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-info text-white">
                                <div class="card-body text-center">
                                    <h6>Total Amount</h6>
                                    <h4>₱<?php echo number_format($totalAmount, 0); ?></h4>
                                    <small>Granted</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card bg-secondary text-white">
                                <div class="card-body text-center">
                                    <h6>Completion Rate</h6>
                                    <h4><?php echo $completionRate; ?>%</h4>
                                    <small>Success Rate</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Detailed Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-check-circle me-2"></i>Completed Liquidations Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h3 class="text-success">₱<?php echo number_format($totalCompletedAmount, 2); ?></h3>
                                            <p class="text-muted">Total Completed Amount</p>
                                        </div>
                                        <div class="col-6">
                                            <h3 class="text-info"><?php echo number_format($totalCompleted); ?></h3>
                                            <p class="text-muted">Total Completed Count</p>
                                        </div>
                                    </div>
                                    <div class="progress mb-3">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $completionRate; ?>%" 
                                             aria-valuenow="<?php echo $completionRate; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $completionRate; ?>%
                                        </div>
                                    </div>
                                    <small class="text-muted">Completion Rate: <?php echo $totalCompleted; ?> of <?php echo $totalGranted; ?> liquidations</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-warning text-dark">
                                    <h5 class="mb-0"><i class="fas fa-clock me-2"></i>Pending Liquidations Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h3 class="text-warning">₱<?php echo number_format($totalPendingAmount, 2); ?></h3>
                                            <p class="text-muted">Total Pending Amount</p>
                                        </div>
                                        <div class="col-6">
                                            <h3 class="text-warning"><?php echo number_format($pendingCount); ?></h3>
                                            <p class="text-muted">Total Pending Count</p>
                                        </div>
                                    </div>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Action Required:</strong> <?php echo $pendingCount; ?> liquidations need attention
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monthly Statistics -->
                    <?php if (!empty($monthlyStats)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Monthly Completion Statistics</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Month</th>
                                                    <th>Completed Liquidations</th>
                                                    <th>Total Amount</th>
                                                    <th>Average Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($monthlyStats as $stat): ?>
                                                <tr>
                                                    <td><strong><?php echo date('F Y', strtotime($stat['month'] . '-01')); ?></strong></td>
                                                    <td>
                                                        <span class="badge bg-success"><?php echo $stat['count']; ?></span>
                                                    </td>
                                                    <td><strong>₱<?php echo number_format($stat['total_amount'], 2); ?></strong></td>
                                                    <td>₱<?php echo number_format($stat['total_amount'] / $stat['count'], 2); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Type Breakdown -->
                    <?php if (!empty($typeStats)): ?>
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Cash Advance Type Breakdown</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Type</th>
                                                    <th>Count</th>
                                                    <th>Total Amount</th>
                                                    <th>Percentage</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($typeStats as $stat): ?>
                                                <?php $percentage = $totalAmount > 0 ? round(($stat['total_amount'] / $totalAmount) * 100, 1) : 0; ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo ucwords(str_replace('_', ' ', $stat['type'])); ?></span>
                                                    </td>
                                                    <td><?php echo $stat['count']; ?></td>
                                                    <td><strong>₱<?php echo number_format($stat['total_amount'], 2); ?></strong></td>
                                                    <td>
                                                        <div class="progress" style="height: 20px;">
                                                            <div class="progress-bar bg-info" role="progressbar" 
                                                                 style="width: <?php echo $percentage; ?>%" 
                                                                 aria-valuenow="<?php echo $percentage; ?>" 
                                                                 aria-valuemin="0" aria-valuemax="100">
                                                                <?php echo $percentage; ?>%
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Quick Reports and Filters -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Quick Reports</h5>
                                </div>
                                <div class="card-body">
                                    <div class="list-group">
                                        <a href="history.php" class="list-group-item list-group-item-action">
                                            <i class="fas fa-history me-2"></i>Completed Liquidations History
                                        </a>
                                        <a href="pending.php" class="list-group-item list-group-item-action">
                                            <i class="fas fa-clock me-2"></i>Pending Liquidations Report
                                        </a>
                                        <a href="export_monthly_report.php?<?php echo http_build_query($_GET); ?>" class="list-group-item list-group-item-action" target="_blank">
                                            <i class="fas fa-file-pdf me-2"></i>Monthly Cash Advance Report
                                        </a>
                                        <a href="export_excel.php?<?php echo http_build_query($_GET); ?>" class="list-group-item list-group-item-action" target="_blank">
                                            <i class="fas fa-file-excel me-2"></i>Export to Excel
                                        </a>
                                        <a href="#departmentAnalysisModal" class="list-group-item list-group-item-action" data-bs-toggle="modal">
                                            <i class="fas fa-chart-pie me-2"></i>Department Analysis
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Report Filters</h5>
                                </div>
                                <div class="card-body">
                                    <form method="get">
                                        <div class="mb-3">
                                            <label class="form-label">Date Range</label>
                                            <div class="row">
                                                <div class="col-6">
                                                    <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($filter_start); ?>">
                                                </div>
                                                <div class="col-6">
                                                    <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($filter_end); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Cash Advance Type</label>
                                            <select class="form-select" name="type">
                                                <option value="">All Types</option>
                                                <option value="Payroll" <?php if($filter_type=='Payroll') echo 'selected'; ?>>Payroll</option>
                                                <option value="Travel" <?php if($filter_type=='Travel') echo 'selected'; ?>>Travel</option>
                                                <option value="Special Purposes" <?php if($filter_type=='Special Purposes') echo 'selected'; ?>>Special Purposes</option>
                                                <option value="Confidential Funds" <?php if($filter_type=='Confidential Funds') echo 'selected'; ?>>Confidential Funds</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Status</label>
                                            <select class="form-select" name="status">
                                                <option value="">All Status</option>
                                                <option value="pending" <?php if($filter_status=='pending') echo 'selected'; ?>>Pending</option>
                                                <option value="completed" <?php if($filter_status=='completed') echo 'selected'; ?>>Completed</option>
                                                <option value="overdue" <?php if($filter_status=='overdue') echo 'selected'; ?>>Overdue</option>
                                            </select>
                                        </div>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-filter me-2"></i>Generate Report
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Department Analysis Modal -->
<div class="modal fade" id="departmentAnalysisModal" tabindex="-1" aria-labelledby="departmentAnalysisModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="departmentAnalysisModalLabel"><i class="fas fa-chart-pie me-2"></i>Department Analysis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Department analysis feature coming soon!</p>
            </div>
        </div>
    </div>
</div>

<!-- Filtered Results Table -->
<?php if (!empty($filteredStats)): ?>
<div class="d-flex justify-content-end mb-2">
    <button class="btn btn-outline-secondary" onclick="printFilteredReport()">
        <i class="fas fa-print me-2"></i>Print Report
    </button>
</div>
<div id="filteredReportCard" class="card mt-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0"><i class="fas fa-table me-2"></i>Filtered Cash Advance Records</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Date Granted</th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Amount</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($filteredStats as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['date_granted']); ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['type']); ?></td>
                        <td><?php echo htmlspecialchars($row['status']); ?></td>
                        <td>₱<?php echo number_format($row['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function printFilteredReport() {
    var printContents = document.getElementById('filteredReportCard').outerHTML;
    var originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    location.reload();
}
</script>
<?php endif; ?> 