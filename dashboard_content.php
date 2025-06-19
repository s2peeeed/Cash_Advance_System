<?php
// This file expects session and DB to be already set up by dashboard.php
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
?>
<div class="content-header animate__animated animate__fadeInDown">
    <div class="welcome-text">
        <h1>Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?> <span class="admin-badge">Administrator</span></h1>
        <p>Manage cash advances and liquidations efficiently</p>
    </div>
    <div class="header-actions">
        <button class="theme-switch" id="themeSwitch"><i class="fas fa-moon"></i></button>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>
</div>

<div class="stats-grid animate__animated animate__fadeInUp">
    <div class="stats-card">
        <div class="stats-header">
            <div>
                <div class="stats-value"><?php echo number_format($totalGranted); ?></div>
                <div class="stats-label">Total Granted</div>
            </div>
            <div class="stats-icon"><i class="fas fa-check-circle"></i></div>
        </div>
    </div>
    <div class="stats-card success">
        <div class="stats-header">
            <div>
                <div class="stats-value"><?php echo number_format($totalLiquidated); ?></div>
                <div class="stats-label">Total Liquidated</div>
            </div>
            <div class="stats-icon"><i class="fas fa-coins"></i></div>
        </div>
    </div>
    <div class="stats-card warning">
        <div class="stats-header">
            <div>
                <div class="stats-value"><?php echo number_format($pendingCount); ?></div>
                <div class="stats-label">Pending Liquidations</div>
            </div>
            <div class="stats-icon"><i class="fas fa-hourglass-half"></i></div>
        </div>
    </div>
</div>

<div class="action-grid animate__animated animate__fadeInUp">
    <a href="#" class="action-card" onclick="loadContent('add_granted')">
        <i class="fas fa-plus-circle"></i>
        <h6>Add Cash Advance</h6>
        <p>Create new cash advance requests</p>
    </a>
    <a href="#" class="action-card" onclick="loadContent('pending')">
        <i class="fas fa-file-invoice"></i>
        <h6>Review Pending</h6>
        <p>Review pending liquidations</p>
    </a>
    <a href="#" class="action-card" onclick="loadContent('reports')">
        <i class="fas fa-chart-bar"></i>
        <h6>View Reports</h6>
        <p>Generate and view reports</p>
    </a>
    <a href="#" class="action-card" onclick="loadContent('users')">
        <i class="fas fa-users"></i>
        <h6>Manage Users</h6>
        <p>Administer user accounts</p>
    </a>
</div> 