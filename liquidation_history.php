<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}

require_once 'config/database.php';

$liquidations = [];
$error = "";
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

try {
    $query = "
        SELECT 
            gca.id as cash_advance_id,
            gca.name as full_name,
            gca.email,
            gca.purpose,
            gca.amount as original_amount,
            gca.type,
            gca.voucher_number,
            gca.cheque_number,
            gca.date_granted,
            gca.due_date,
            gca.status as cash_advance_status,
            COALESCE(lr.total_liquidated, 0) as total_liquidated,
            (gca.amount - COALESCE(lr.total_liquidated, 0)) as remaining_balance,
            COUNT(lr_records.id) as liquidation_count,
            MAX(lr_records.date_submitted) as last_liquidation_date,
            MIN(lr_records.date_submitted) as first_liquidation_date
        FROM granted_cash_advances gca
        LEFT JOIN (
            SELECT 
                cash_advance_id,
                SUM(amount_liquidated) as total_liquidated
            FROM liquidation_records 
            GROUP BY cash_advance_id
        ) lr ON gca.id = lr.cash_advance_id
        LEFT JOIN liquidation_records lr_records ON gca.id = lr_records.cash_advance_id
        WHERE gca.status IN ('completed', 'liquidated') OR lr.total_liquidated > 0
    ";
    
    if (!empty($search)) {
        $query .= " AND (gca.name LIKE :search OR gca.type LIKE :search OR gca.purpose LIKE :search)";
    }
    
    $query .= " GROUP BY gca.id ORDER BY last_liquidation_date DESC, gca.name ASC";
    
    $stmt = $pdo->prepare($query);
    if (!empty($search)) {
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    $stmt->execute();
    $liquidations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Calculate statistics
$total_liquidations = count($liquidations);
$total_amount_liquidated = 0;
$first_liquidations = 0;
$second_liquidations = 0;

foreach ($liquidations as $liquidation) {
    $total_amount_liquidated += $liquidation['total_liquidated'];
    if ($liquidation['liquidation_count'] == 1) {
        $first_liquidations++;
    } elseif ($liquidation['liquidation_count'] >= 2) {
        $second_liquidations++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Liquidation History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --success: #10b981;
            --success-dark: #059669;
            --warning: #f59e0b;
            --warning-dark: #d97706;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --info: #06b6d4;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: #1f2937;
            min-height: 100vh;
            margin: 0;
        }

        .content {
            margin-left: 0;
            padding: 24px 0 24px 0;
            display: block;
        }

        .container-fluid {
            padding: 0;
            width: 100%;
            max-width: none;
            margin: 0;
        }

        .card {
            width: 100%;
            max-width: none;
            margin: 0;
            border: none;
            border-radius: 16px;
            box-shadow: var(--shadow-lg);
            background: var(--card-bg);
        }

        .card-header {
            border-radius: 16px 16px 0 0;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .card-header h4 {
            margin: 0;
            font-weight: 700;
            font-size: 1.5rem;
        }

        .card-body {
            padding: 2rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            text-align: center;
        }

        .stat-card .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.5rem;
            color: white;
        }

        .stat-card.primary .stat-icon {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
        }

        .stat-card.success .stat-icon {
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
        }

        .stat-card.warning .stat-icon {
            background: linear-gradient(135deg, var(--warning) 0%, var(--warning-dark) 100%);
        }

        .stat-card.info .stat-icon {
            background: linear-gradient(135deg, var(--info) 0%, #0891b2 100%);
        }

        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .stat-card .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .search-container {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .search-bar {
            position: relative;
            flex-grow: 1;
            max-width: 400px;
        }

        .search-bar input {
            border-radius: 12px;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid var(--border-color);
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f8fafc;
        }

        .search-bar input:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
            background: white;
            outline: none;
        }

        .search-bar::before {
            content: '\f002';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
            z-index: 10;
        }

        .liquidation-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 2px solid transparent;
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .liquidation-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary) 0%, var(--success) 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .liquidation-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .liquidation-card:hover::before {
            opacity: 1;
        }

        .liquidation-card.first-liquidation::before {
            background: linear-gradient(90deg, var(--success) 0%, var(--success-dark) 100%);
            opacity: 1;
        }

        .liquidation-card.second-liquidation::before {
            background: linear-gradient(90deg, var(--warning) 0%, var(--warning-dark) 100%);
            opacity: 1;
        }

        .liquidation-card-body {
            padding: 1.5rem;
        }

        .liquidation-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .liquidation-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .liquidation-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            flex-shrink: 0;
        }

        .liquidation-details h5 {
            margin: 0 0 0.5rem 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
        }

        .liquidation-details .liquidation-email {
            color: #6b7280;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .liquidation-number-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .liquidation-number-badge.first {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .liquidation-number-badge.second {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .liquidation-content {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .info-section {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .info-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
        }

        .info-value.amount {
            color: var(--primary);
            font-size: 1.1rem;
        }

        .liquidation-info {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-radius: 8px;
            border: 1px solid #bbf7d0;
        }

        .text-success {
            color: var(--success) !important;
        }

        .text-warning {
            color: var(--warning) !important;
        }

        .fw-bold {
            font-weight: 700 !important;
        }

        .type-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge.pending {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .status-badge.approved {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-badge.rejected {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn {
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            color: white;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #4b5563 0%, #374151 100%);
            color: white;
        }

        @media (max-width: 768px) {
            .content {
                padding: 10px 20px;
            }
            .search-container {
                flex-direction: column;
                align-items: stretch;
            }
            .search-bar {
                max-width: 100%;
            }
            .liquidation-content {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .liquidation-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-history me-2"></i>Liquidation History</h4>
                        </div>
                        <div class="card-body">
                            <!-- Statistics -->
                            <div class="stats-container">
                                <div class="stat-card primary">
                                    <div class="stat-icon">
                                        <i class="fas fa-file-invoice"></i>
                                    </div>
                                    <div class="stat-value"><?php echo number_format($total_liquidations); ?></div>
                                    <div class="stat-label">Total Liquidations</div>
                                </div>
                                <div class="stat-card success">
                                    <div class="stat-icon">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="stat-value">₱<?php echo number_format($total_amount_liquidated, 2); ?></div>
                                    <div class="stat-label">Total Amount Liquidated</div>
                                </div>
                                <div class="stat-card warning">
                                    <div class="stat-icon">
                                        <i class="fas fa-1"></i>
                                    </div>
                                    <div class="stat-value"><?php echo number_format($first_liquidations); ?></div>
                                    <div class="stat-label">First Liquidations</div>
                                </div>
                                <div class="stat-card info">
                                    <div class="stat-icon">
                                        <i class="fas fa-2"></i>
                                    </div>
                                    <div class="stat-value"><?php echo number_format($second_liquidations); ?></div>
                                    <div class="stat-label">Second Liquidations</div>
                                </div>
                            </div>

                            <!-- Search -->
                            <form method="GET" class="mb-4">
                                <div class="search-container">
                                    <div class="search-bar">
                                        <input type="text" name="search" class="form-control" placeholder="Search by name, type, purpose, or email..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-search"></i>Search
                                    </button>
                                    <?php if (!empty($search)): ?>
                                        <a href="liquidation_history.php" class="btn btn-secondary">
                                            <i class="fas fa-times"></i>Clear
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </form>

                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                </div>
                            <?php endif; ?>

                            <?php if (count($liquidations) === 0): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>No liquidation records found.
                                </div>
                            <?php else: ?>
                                <div class="liquidation-cards-container">
                                    <?php foreach ($liquidations as $liquidation): ?>
                                        <div class="liquidation-card <?php echo $liquidation['liquidation_count'] == 1 ? 'first-liquidation' : 'second-liquidation'; ?>">
                                            <div class="liquidation-card-body">
                                                <div class="liquidation-header">
                                                    <div class="liquidation-info">
                                                        <div class="liquidation-avatar">
                                                            <i class="fas fa-user-tie"></i>
                                                        </div>
                                                        <div class="liquidation-details">
                                                            <h5><?php echo htmlspecialchars($liquidation['full_name']); ?></h5>
                                                            <div class="liquidation-email">
                                                                <i class="fas fa-envelope"></i>
                                                                <?php echo htmlspecialchars($liquidation['email']); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="liquidation-number-badge <?php echo $liquidation['liquidation_count'] == 1 ? 'first' : 'second'; ?>">
                                                        <i class="fas <?php echo $liquidation['liquidation_count'] == 1 ? 'fa-1' : 'fa-2'; ?>"></i>
                                                        <?php echo $liquidation['liquidation_count'] == 1 ? 'First' : 'Second'; ?> Liquidation
                                                    </div>
                                                </div>
                                                
                                                <div class="liquidation-content">
                                                    <div class="info-section">
                                                        <div class="info-label">Purpose</div>
                                                        <div class="info-value"><?php echo htmlspecialchars($liquidation['purpose']); ?></div>
                                                        <div class="info-label">Type</div>
                                                        <div class="type-badge">
                                                            <i class="fas fa-credit-card"></i>
                                                            <?php echo htmlspecialchars($liquidation['type']); ?>
                                                        </div>
                                                    </div>
                                                    <div class="info-section">
                                                        <div class="info-label">Original Amount</div>
                                                        <div class="info-value amount">₱<?php echo number_format($liquidation['original_amount'], 2); ?></div>
                                                        <?php if ($liquidation['total_liquidated'] > 0): ?>
                                                            <div class="liquidation-info">
                                                                <div class="info-label">Total Liquidated</div>
                                                                <div class="info-value text-success">₱<?php echo number_format($liquidation['total_liquidated'], 2); ?></div>
                                                                <div class="info-label mt-2">Remaining Balance</div>
                                                                <div class="info-value text-warning fw-bold">₱<?php echo number_format($liquidation['remaining_balance'], 2); ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="info-section">
                                                        <div class="info-label">Status</div>
                                                        <div class="status-badge <?php echo $liquidation['cash_advance_status']; ?>">
                                                            <i class="fas <?php echo $liquidation['cash_advance_status'] === 'pending' ? 'fa-clock' : ($liquidation['cash_advance_status'] === 'approved' ? 'fa-check' : 'fa-times'); ?>"></i>
                                                            <?php echo ucfirst($liquidation['cash_advance_status']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <div class="liquidation-content">
                                                    <div class="info-section">
                                                        <div class="info-label">Voucher Number</div>
                                                        <div class="info-value"><?php echo htmlspecialchars($liquidation['voucher_number'] ?? '-'); ?></div>
                                                        <div class="info-label">Cheque Number</div>
                                                        <div class="info-value"><?php echo htmlspecialchars($liquidation['cheque_number'] ?? '-'); ?></div>
                                                    </div>
                                                    <div class="info-section">
                                                        <div class="info-label">Date Granted</div>
                                                        <div class="info-value"><?php echo htmlspecialchars($liquidation['date_granted']); ?></div>
                                                        <div class="info-label">Due Date</div>
                                                        <div class="info-value"><?php echo htmlspecialchars($liquidation['due_date']); ?></div>
                                                    </div>
                                                    <div class="info-section">
                                                        <div class="info-label">Last Liquidation Date</div>
                                                        <div class="info-value"><?php echo htmlspecialchars($liquidation['last_liquidation_date']); ?></div>
                                                        <div class="info-label">First Liquidation Date</div>
                                                        <div class="info-value"><?php echo htmlspecialchars($liquidation['first_liquidation_date']); ?></div>
                                                    </div>
                                                </div>
                                                
                                                <?php if (!empty($liquidation['remarks'])): ?>
                                                    <div class="mt-3 p-3 bg-light rounded">
                                                        <div class="info-label">Remarks</div>
                                                        <div class="info-value"><?php echo nl2br(htmlspecialchars($liquidation['remarks'])); ?></div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            
                            <a href="dashboard.php" class="btn btn-secondary mt-3">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 