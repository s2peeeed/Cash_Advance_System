<?php
session_start();

// Strict admin-only access check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: login.php?error=admin_only");
    exit();
}

require_once 'config/database.php';

// Fetch total granted count
$totalGranted = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM granted_cash_advances");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['total'] !== null) {
        $totalGranted = $row['total'];
    }
} catch (PDOException $e) {
    $totalGranted = 0;
}

// Fetch pending liquidation count
$pendingCount = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM granted_cash_advances WHERE status = 'pending'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['total'] !== null) {
        $pendingCount = $row['total'];
    }
} catch (PDOException $e) {
    $pendingCount = 0;
}

// Fetch total completed liquidations count and amount
$totalCompleted = 0;
$totalLiquidatedAmount = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total_count, SUM(amount) as total_amount FROM granted_cash_advances WHERE status = 'completed'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        $totalCompleted = $row['total_count'] ?? 0;
        $totalLiquidatedAmount = $row['total_amount'] ?? 0;
    }
} catch (PDOException $e) {
    $totalCompleted = 0;
    $totalLiquidatedAmount = 0;
}

// Determine which page to display
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'users.php';
$validPages = [
    'dashboard',
    'add_granted.php',
    'pending.php',
    'history.php',
    'liquidation_history.php',
    'user_management',
    'reports_content.php',
    'settings.php',
    'security.php',
    'reminders.php',
    'activity_log.php'
];
$validTabs = [
    'users.php',
    'add_bonded_employee.php',
    'type_of_cash_advance.php',
    'add_position.php',
    'add_station.php'
];

// Sanitize page and tab
$page = in_array($page, $validPages) ? $page : 'dashboard';
$tab = ($page === 'user_management' && in_array($tab, $validTabs)) ? $tab : 'users.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LGU Liquidation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --secondary: #7c3aed;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --bg-color: #f8fafc;
            --text-color: #1e293b;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --nav-bg: #ffffff;
            --nav-text: #1e293b;
            --border-color: #e2e8f0;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --sidebar-width: 280px;
            --gradient-primary: linear-gradient(135deg, #3b82f6 0%, #7c3aed 100%);
        }

        [data-theme="dark"] {
            --primary: #60a5fa;
            --primary-dark: #3b82f6;
            --secondary: #a78bfa;
            --success: #34d399;
            --warning: #fbbf24;
            --danger: #f87171;
            --info: #22d3ee;
            --bg-color: #0f172a;
            --text-color: #f1f5f9;
            --text-muted: #94a3b8;
            --card-bg: #1e293b;
            --nav-bg: #1e293b;
            --nav-text: #f1f5f9;
            --border-color: #334155;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.2);
            --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.6;
        }

        .wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: var(--sidebar-width);
            background: var(--nav-bg);
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: var(--primary) var(--nav-bg);
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 3px;
        }

        .sidebar.collapsed {
            width: 72px;
        }

        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid var(--border-color);
            position: relative;
        }

        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
        }

        .logo-container img {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            border: 2px solid var(--primary);
            background: white;
            padding: 6px;
            box-shadow: var(--card-shadow);
        }

        .logo-text {
            font-weight: 700;
            font-size: 1rem;
            letter-spacing: 0.05em;
            color: var(--text-color);
            text-transform: uppercase;
        }

        .collapsed .logo-text {
            display: none;
        }

        .toggle-sidebar {
            position: absolute;
            top: 1.5rem;
            right: 1rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.5rem;
            cursor: pointer;
            color: var(--text-color);
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
        }

        .toggle-sidebar:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: scale(1.05);
        }

        .sidebar-nav {
            padding: 1.5rem 0;
        }

        .nav-section-title {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--text-muted);
            padding: 0 1.5rem;
            margin: 1.5rem 0 0.75rem;
        }

        .collapsed .nav-section-title {
            display: none;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            margin: 0.25rem 0.75rem;
        }

        .sidebar-menu a {
            color: var(--nav-text);
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 500;
            font-size: 0.875rem;
        }

        .sidebar-menu a:hover {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
        }

        .sidebar-menu a.active {
            background: var(--primary);
            color: white;
            box-shadow: var(--card-shadow);
        }

        .sidebar-menu i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .collapsed .sidebar-menu a {
            justify-content: center;
            padding: 0.75rem;
        }

        .collapsed .sidebar-menu span {
            display: none;
        }

        .collapsed .sidebar-menu i {
            margin-right: 0;
        }

        .menu-badge {
            background: var(--danger);
            color: white;
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 10px;
            margin-left: auto;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        .collapsed .menu-badge {
            position: absolute;
            top: 6px;
            right: 6px;
            margin: 0;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            transition: all 0.3s ease;
            padding: 1.5rem;
        }

        .main-content.expanded {
            margin-left: 72px;
            width: calc(100% - 72px);
        }

        .content-header {
            background: var(--card-bg);
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .welcome-text h1 {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .welcome-text p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .admin-badge {
            background: var(--gradient-primary);
            color: white;
            padding: 0.3rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .theme-switch {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            width: 2.25rem;
            height: 2.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-color);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .theme-switch:hover {
            border-color: var(--primary);
            transform: scale(1.05);
        }

        .theme-switch i {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .theme-switch:hover i {
            transform: rotate(360deg);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            color: #dc3545;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.2s ease;
        }

        .logout-btn:hover {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        .logout-btn i {
            font-size: 1.1rem;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stats-card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            border: 1px solid var(--border-color);
        }

        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--hover-shadow);
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: var(--gradient-primary);
        }

        .stats-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }

        .stats-icon {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
            border: 1px solid rgba(59, 130, 246, 0.2);
        }

        .stats-card.success .stats-icon {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .stats-card.warning .stats-icon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border: 1px solid rgba(245, 158, 11, 0.2);
        }

        .stats-card.info .stats-icon {
            background: rgba(6, 182, 212, 0.1);
            color: var(--info);
            border: 1px solid rgba(6, 182, 212, 0.2);
        }

        .stats-value {
            font-size: 2.25rem;
            font-weight: 800;
            color: var(--text-color);
            margin-bottom: 0.25rem;
        }

        .stats-label {
            color: var(--text-muted);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .clickable-stats {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .clickable-stats:hover {
            transform: translateY(-4px);
            box-shadow: var(--hover-shadow);
            border-color: var(--primary);
        }

        .clickable-stats:active {
            transform: translateY(-2px);
        }

        /* Employee Records Table Styles */
        .records-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .records-table th,
        .records-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .records-table th {
            background: var(--bg-color);
            font-weight: 600;
            color: var(--text-color);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .records-table tbody tr:hover {
            background: rgba(59, 130, 246, 0.05);
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-badge.overdue {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
        }

        .status-badge.due-soon {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .status-badge.on-track {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
        }

        .status-badge.completed {
            background: rgba(34, 197, 94, 0.1);
            color: var(--success);
        }

        .status-badge.pending {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
        }

        .employee-type-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .employee-type-badge.bonded {
            background: rgba(139, 92, 246, 0.1);
            color: #8b5cf6;
        }

        .employee-type-badge.regular {
            background: rgba(59, 130, 246, 0.1);
            color: var(--primary);
        }

        .table-responsive {
            max-height: 500px;
            overflow-y: auto;
        }

        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem;
        }

        .spinner-border {
            width: 2rem;
            height: 2rem;
        }

        /* Tab Bar for User Management */
        .tab-bar {
            background: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
            margin-bottom: 1.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: none;
        }

        .tab-bar.show {
            display: flex;
        }

        .tab-item {
            padding: 0.75rem 1.5rem;
            color: var(--text-muted);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.3s ease;
            border-bottom: 2px solid transparent;
            margin-right: 1rem;
        }

        .tab-item:hover {
            color: var(--primary);
        }

        .tab-item.active {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
        }

        /* Dynamic Content Area */
        .dynamic-content {
            min-height: 400px;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .sidebar {
                width: 72px;
            }

            .main-content {
                margin-left: 72px;
                width: calc(100% - 72px);
            }

            .sidebar-menu span,
            .nav-section-title,
            .user-details,
            .logo-text {
                display: none;
            }

            .sidebar-menu a {
                justify-content: center;
                padding: 0.75rem;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .tab-bar {
                flex-wrap: wrap;
            }

            .tab-item {
                margin-bottom: 0.5rem;
            }

            .welcome-text h1 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .content-header {
                padding: 1rem;
            }

            .tab-item {
                padding: 0.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="logo-container">
                    <img src="logo.png" alt="LGU Logo" class="animate__animated animate__fadeIn">
                    <div class="logo-text">LGU System</div>
                </div>
                <button class="toggle-sidebar" id="toggleSidebar">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
            <div class="sidebar-nav">
                <div class="nav-section">
                    <div class="nav-section-title">Main</div>
                    <ul class="sidebar-menu">
                        <li><a href="?page=dashboard" class="<?php echo $page === 'dashboard' ? 'active' : ''; ?>"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a></li>
                        <li><a href="?page=user_management&tab=users.php" class="<?php echo $page === 'user_management' ? 'active' : ''; ?>"><i class="fas fa-users"></i><span>User Management</span></a></li>
                        <li><a href="?page=add_granted.php" class="<?php echo $page === 'add_granted.php' ? 'active' : ''; ?>"><i class="fas fa-plus-circle"></i><span>Cash Advance</span></a></li>
  
                        <li><a href="?page=pending.php" class="<?php echo $page === 'pending.php' ? 'active' : ''; ?>"><i class="fas fa-file-invoice"></i><span>Pending Liquidations</span>
                            <?php if ($pendingCount > 0): ?>
                                <span class="menu-badge"><?php echo $pendingCount; ?></span>
                            <?php endif; ?>
                        </a></li>
                        <li><a href="?page=history.php" class="<?php echo $page === 'history.php' ? 'active' : ''; ?>"><i class="fas fa-history"></i><span>Liquidation History</span></a></li>
                        <!-- <li><a href="?page=liquidation_history.php" class="<?php echo $page === 'liquidation_history.php' ? 'active' : ''; ?>"><i class="fas fa-file-invoice-dollar"></i><span>Liquidation Records</span></a></li> -->
                    </ul>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Management</div>
                    <ul class="sidebar-menu">
                       
                        <li><a href="?page=reports_content.php" class="<?php echo $page === 'reports_content.php' ? 'active' : ''; ?>"><i class="fas fa-chart-bar"></i><span>Reports</span></a></li>
                        <li><a href="?page=activity_log.php" class="<?php echo $page === 'activity_log.php' ? 'active' : ''; ?>"><i class="fas fa-history"></i><span>Activity Log</span></a></li>
                        <li><a href="?page=settings.php" class="<?php echo $page === 'settings.php' ? 'active' : ''; ?>"><i class="fas fa-cog"></i><span>System Settings</span></a></li>
                    </ul>
                </div>
                <div class="nav-section">
                    <div class="nav-section-title">Security</div>
                    <ul class="sidebar-menu">
                        <li><a href="?page=security.php" class="<?php echo $page === 'security.php' ? 'active' : ''; ?>"><i class="fas fa-shield-alt"></i><span>Security</span></a></li>
                        <li><a href="?page=reminders.php" class="<?php echo $page === 'reminders.php' ? 'active' : ''; ?>"><i class="fas fa-envelope"></i><span>Email Reminders</span></a></li>
                    </ul>
                </div>
            </div>
            <div class="user-info">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['full_name']); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($_SESSION['department']); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content" id="mainContent">
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

            <!-- Tab Bar for User Management -->
            <div class="tab-bar <?php echo $page === 'user_management' ? 'show' : ''; ?>" id="userManagementTabs">
                <a href="?page=user_management&tab=users.php" class="tab-item <?php echo $tab === 'users.php' ? 'active' : ''; ?>">Add Employee</a>
                <a href="?page=user_management&tab=add_bonded_employee.php" class="tab-item <?php echo $tab === 'add_bonded_employee.php' ? 'active' : ''; ?>">Add Bonded Employee</a>
                <a href="?page=user_management&tab=type_of_cash_advance.php" class="tab-item <?php echo $tab === 'type_of_cash_advance.php' ? 'active' : ''; ?>">Add Type of Cash Advance</a>
                <a href="?page=user_management&tab=add_position.php" class="tab-item <?php echo $tab === 'add_position.php' ? 'active' : ''; ?>">Add Position</a>
                <a href="?page=user_management&tab=add_station.php" class="tab-item <?php echo $tab === 'add_station' ? 'active' : ''; ?>">Add Station</a>
            </div>

            <!-- Dynamic Content Area -->
            <div class="dynamic-content animate__animated animate__fadeIn">
                <?php
                if ($page === 'dashboard') {
                    ?>
                    <div class="stats-grid">
                        <div class="stats-card clickable-stats" data-type="total_granted">
                            <div class="stats-header">
                                <div>
                                    <div class="stats-value"><?php echo number_format($totalGranted); ?></div>
                                    <div class="stats-label">Total Granted</div>
                                </div>
                                <div class="stats-icon"><i class="fas fa-check-circle"></i></div>
                            </div>
                        </div>
                        <div class="stats-card success clickable-stats" data-type="total_completed">
                            <div class="stats-header">
                                <div>
                                    <div class="stats-value"><?php echo number_format($totalCompleted); ?></div>
                                    <div class="stats-label">Total Completed</div>
                                </div>
                                <div class="stats-icon"><i class="fas fa-coins"></i></div>
                            </div>
                        </div>
                        <div class="stats-card warning clickable-stats" data-type="pending_liquidations">
                            <div class="stats-header">
                                <div>
                                    <div class="stats-value"><?php echo number_format($pendingCount); ?></div>
                                    <div class="stats-label">Pending Liquidations</div>
                                </div>
                                <div class="stats-icon"><i class="fas fa-hourglass-half"></i></div>
                            </div>
                        </div>
                        <div class="stats-card info clickable-stats" data-type="total_liquidated_amount">
                            <div class="stats-header">
                                <div>
                                    <div class="stats-value">₱<?php echo number_format($totalLiquidatedAmount, 2); ?></div>
                                    <div class="stats-label">Total Liquidated Amount</div>
                                </div>
                                <div class="stats-icon"><i class="fas fa-money-bill-wave"></i></div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Employee Records Table Container -->
                    <div id="employeeRecordsContainer" style="display: none;">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 id="recordsTitle" class="mb-0"></h5>
                                <button type="button" class="btn btn-secondary btn-sm" id="closeRecords">
                                    <i class="fas fa-times"></i> Close
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="recordsTableContainer">
                                    <!-- Table will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                } elseif ($page === 'user_management') {
                    if (file_exists($tab)) {
                        include $tab;
                    } else {
                        echo '<div class="alert alert-danger">Error: Page not found.</div>';
                    }
                } else {
                    if (file_exists($page)) {
                        include $page;
                    } else {
                        echo '<div class="alert alert-danger">Error: Page not found.</div>';
                    }
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle with system preference detection
        const themeSwitch = document.getElementById('themeSwitch');
        const themeIcon = themeSwitch.querySelector('i');
        
        function setTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                themeIcon.classList.replace('fa-moon', 'fa-sun');
            } else {
                document.documentElement.removeAttribute('data-theme');
                themeIcon.classList.replace('fa-sun', 'fa-moon');
            }
            localStorage.setItem('theme', theme);
        }

        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            setTheme(savedTheme);
        } else {
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            setTheme(prefersDark ? 'dark' : 'light');
        }

        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem('theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });

        themeSwitch.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });

        // Sidebar toggle
        const toggleSidebar = document.getElementById('toggleSidebar');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        toggleSidebar.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
        });

        // Stats Cards Click Functionality
        document.addEventListener('DOMContentLoaded', function() {
            const statsCards = document.querySelectorAll('.clickable-stats');
            const recordsContainer = document.getElementById('employeeRecordsContainer');
            const recordsTitle = document.getElementById('recordsTitle');
            const recordsTableContainer = document.getElementById('recordsTableContainer');
            const closeRecordsBtn = document.getElementById('closeRecords');

            statsCards.forEach(card => {
                card.addEventListener('click', function() {
                    const type = this.dataset.type;
                    loadEmployeeRecords(type);
                });
            });

            closeRecordsBtn.addEventListener('click', function() {
                recordsContainer.style.display = 'none';
            });

            function loadEmployeeRecords(type) {
                // Show loading spinner
                recordsTableContainer.innerHTML = `
                    <div class="loading-spinner">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `;
                recordsContainer.style.display = 'block';

                // Fetch data from server
                fetch(`get_employee_records.php?type=${type}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayEmployeeRecords(data.data, data.title);
                        } else {
                            recordsTableContainer.innerHTML = `
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    Error: ${data.message}
                                </div>
                            `;
                        }
                    })
                    .catch(error => {
                        recordsTableContainer.innerHTML = `
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                Error loading data: ${error.message}
                            </div>
                        `;
                    });
            }

            function displayEmployeeRecords(records, title) {
                recordsTitle.textContent = title;
                
                if (records.length === 0) {
                    recordsTableContainer.innerHTML = `
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No records found for this category.
                        </div>
                    `;
                    return;
                }

                let tableHTML = `
                    <div class="table-responsive">
                        <table class="records-table">
                            <thead>
                                <tr>
                                    <th>Employee Name</th>
                                    <th>Email</th>
                                    <th>Employee Type</th>
                                    <th>Purpose</th>
                                    <th>Amount</th>
                                    <th>Type</th>
                                    <th>Cheque Number</th>
                                    <th>Voucher Number</th>
                                    <th>Date Granted</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    ${title.includes('Completed') || title.includes('Liquidated') ? '<th>Date Completed</th>' : ''}
                                    ${title.includes('Pending') ? '<th>Status Indicator</th>' : ''}
                                </tr>
                            </thead>
                            <tbody>
                `;

                records.forEach(record => {
                    const statusClass = getStatusClass(record.status, record.status_indicator);
                    const employeeTypeClass = record.employee_type.toLowerCase();
                    
                    tableHTML += `
                        <tr>
                            <td><strong>${escapeHtml(record.name)}</strong></td>
                            <td>${escapeHtml(record.email || 'N/A')}</td>
                            <td><span class="employee-type-badge ${employeeTypeClass}">${record.employee_type}</span></td>
                            <td>${escapeHtml(record.purpose)}</td>
                            <td><strong>₱${parseFloat(record.amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</strong></td>
                            <td>${escapeHtml(record.type)}</td>
                            <td>${escapeHtml(record.cheque_number || 'N/A')}</td>
                            <td>${escapeHtml(record.voucher_number || 'N/A')}</td>
                            <td>${formatDate(record.date_granted)}</td>
                            <td>${formatDate(record.due_date)}</td>
                            <td><span class="status-badge ${statusClass}">${record.status}</span></td>
                            ${title.includes('Completed') || title.includes('Liquidated') ? `<td>${record.date_completed ? formatDate(record.date_completed) : 'N/A'}</td>` : ''}
                            ${title.includes('Pending') ? `<td><span class="status-badge ${getStatusIndicatorClass(record.status_indicator)}">${record.status_indicator || 'N/A'}</span></td>` : ''}
                        </tr>
                    `;
                });

                tableHTML += `
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Showing ${records.length} record(s)
                        </small>
                    </div>
                `;

                recordsTableContainer.innerHTML = tableHTML;
            }

            function getStatusClass(status, statusIndicator) {
                if (status === 'completed') return 'completed';
                if (status === 'pending') return 'pending';
                if (statusIndicator === 'Overdue') return 'overdue';
                if (statusIndicator === 'Due Soon') return 'due-soon';
                if (statusIndicator === 'On Track') return 'on-track';
                return 'pending';
            }

            function getStatusIndicatorClass(statusIndicator) {
                if (statusIndicator === 'Overdue') return 'overdue';
                if (statusIndicator === 'Due Soon') return 'due-soon';
                if (statusIndicator === 'On Track') return 'on-track';
                return '';
            }

            function formatDate(dateString) {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric'
                });
            }

            function escapeHtml(text) {
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }
        });
    </script>
</body>
</html>