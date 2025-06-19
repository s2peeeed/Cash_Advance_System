<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Strict admin-only access check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: login.php?error=admin_only");
    exit();
}

require_once 'config/database.php';
require_once 'includes/ActivityLogger.php';

$activityLogger = new ActivityLogger($pdo);

// Get filter parameters
$action = $_GET['action'] ?? '';
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$userId = $_GET['user_id'] ?? '';
$search = $_GET['search'] ?? '';

// Get activities
$activities = [];
$error = null;
try {
    if ($action || $userId || $search) {
        // Get filtered activities
        $sql = "SELECT al.*, u.full_name as user_full_name 
                FROM activity_log al 
                LEFT JOIN users u ON al.user_id = u.user_id 
                WHERE al.created_at BETWEEN ? AND ?";
        $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
        
        if ($action) {
            $sql .= " AND al.action = ?";
            $params[] = $action;
        }
        
        if ($userId) {
            $sql .= " AND al.user_id = ?";
            $params[] = $userId;
        }
        
        if ($search) {
            $sql .= " AND (al.description LIKE ? OR al.user_name LIKE ? OR al.action LIKE ?)";
            $searchParam = "%$search%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $sql .= " ORDER BY al.created_at DESC LIMIT 1000";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Get recent activities - use direct query instead of ActivityLogger method
        $sql = "SELECT al.*, u.full_name as user_full_name 
                FROM activity_log al 
                LEFT JOIN users u ON al.user_id = u.user_id 
                ORDER BY al.created_at DESC LIMIT 100";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Get unique actions for filter dropdown
$actions = [];
try {
    $stmt = $pdo->query("SELECT DISTINCT action FROM activity_log ORDER BY action");
    $actions = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $actions = [];
}

// Get users for filter dropdown
$users = [];
try {
    $stmt = $pdo->query("SELECT user_id, full_name FROM users ORDER BY full_name");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - LGU Liquidation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border-radius: 12px 12px 0 0;
            border: none;
        }

        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow);
        }

        .activity-item {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
            border-left: 4px solid var(--primary);
            transition: all 0.3s ease;
        }

        .activity-item:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .activity-item.create { border-left-color: var(--success); }
        .activity-item.update { border-left-color: var(--warning); }
        .activity-item.delete { border-left-color: var(--danger); }
        .activity-item.login { border-left-color: var(--info); }
        .activity-item.logout { border-left-color: var(--info); }

        .action-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .action-badge.create { background: rgba(16, 185, 129, 0.1); color: var(--success); }
        .action-badge.update { background: rgba(245, 158, 11, 0.1); color: var(--warning); }
        .action-badge.delete { background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        .action-badge.login { background: rgba(6, 182, 212, 0.1); color: var(--info); }
        .action-badge.logout { background: rgba(6, 182, 212, 0.1); color: var(--info); }
        .action-badge.send_email { background: rgba(139, 92, 246, 0.1); color: #8b5cf6; }
        .action-badge.complete_liquidation { background: rgba(16, 185, 129, 0.1); color: var(--success); }

        .activity-time {
            color: #6b7280;
            font-size: 0.875rem;
        }

        .activity-user {
            font-weight: 600;
            color: var(--primary);
        }

        .activity-description {
            margin: 0.5rem 0;
            line-height: 1.5;
        }

        .activity-details {
            font-size: 0.875rem;
            color: #6b7280;
        }

        .btn-filter {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-filter:hover {
            background: var(--primary-dark);
            color: white;
            transform: translateY(-1px);
        }

        .btn-clear {
            background: #6b7280;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .btn-clear:hover {
            background: #4b5563;
            color: white;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            box-shadow: var(--shadow);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary);
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-history me-2"></i>Activity Log
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Filter Section -->
                        <div class="filter-section">
                            <form method="GET" class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label">Action Type</label>
                                    <select name="action" class="form-select">
                                        <option value="">All Actions</option>
                                        <?php foreach ($actions as $act): ?>
                                            <option value="<?php echo htmlspecialchars($act); ?>" 
                                                    <?php echo $action === $act ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($act); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">All Users</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?php echo $user['user_id']; ?>" 
                                                    <?php echo $userId == $user['user_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($user['full_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Start Date</label>
                                    <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">End Date</label>
                                    <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">Search</label>
                                    <input type="text" name="search" class="form-control" placeholder="Search activities..." value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                                <div class="col-md-2 d-flex align-items-end">
                                    <button type="submit" class="btn btn-filter me-2">
                                        <i class="fas fa-filter"></i> Filter
                                    </button>
                                    <a href="activity_log.php" class="btn btn-clear">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </form>
                        </div>

                        <!-- Activity List -->
                        <div class="activity-list">
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i>
                                    <?php echo htmlspecialchars($error); ?>
                                </div>
                            <?php elseif (empty($activities)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    No activities found for the selected criteria.
                                    <?php if (!$action && !$userId && !$search): ?>
                                        <br><small>This might mean no activities have been logged yet, or there's an issue with the database connection.</small>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0">Showing <?php echo count($activities); ?> activities</h6>
                                    <small class="text-muted">
                                        <?php echo $startDate; ?> to <?php echo $endDate; ?>
                                    </small>
                                </div>
                                
                                <?php foreach ($activities as $activity): ?>
                                    <div class="activity-item <?php echo strtolower($activity['action']); ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center gap-2 mb-1">
                                                    <span class="action-badge <?php echo strtolower($activity['action']); ?>">
                                                        <?php echo htmlspecialchars($activity['action']); ?>
                                                    </span>
                                                    <span class="activity-user">
                                                        <?php echo htmlspecialchars($activity['user_name']); ?>
                                                    </span>
                                                </div>
                                                <div class="activity-description">
                                                    <?php echo htmlspecialchars($activity['description']); ?>
                                                </div>
                                                <div class="activity-details">
                                                    <?php if ($activity['table_name']): ?>
                                                        <span class="me-3">
                                                            <i class="fas fa-database me-1"></i>
                                                            <?php echo htmlspecialchars($activity['table_name']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <?php if ($activity['ip_address']): ?>
                                                        <span class="me-3">
                                                            <i class="fas fa-network-wired me-1"></i>
                                                            <?php echo htmlspecialchars($activity['ip_address']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="activity-time text-end">
                                                <div><?php echo date('M j, Y', strtotime($activity['created_at'])); ?></div>
                                                <div><?php echo date('g:i A', strtotime($activity['created_at'])); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="mt-3">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 