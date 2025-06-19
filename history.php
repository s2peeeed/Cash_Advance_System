<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';

$show_detailed = false;
$detailed_liquidation = null;
$cash_advance = null;
$first_liquidation_date = null;
$last_liquidation_date = null;

if (isset($_GET['cash_advance_id']) && isset($_GET['liquidation_number'])) {
    $show_detailed = true;
    $cash_advance_id = $_GET['cash_advance_id'];
    $liquidation_number = $_GET['liquidation_number'];
    // Fetch the liquidation record and its parent cash advance
    $stmt = $pdo->prepare("
        SELECT lr.*, gca.name as full_name, gca.email, gca.purpose, gca.amount as cash_advance_amount, gca.type, gca.voucher_number, gca.cheque_number, gca.date_granted, gca.due_date, gca.status, gca.date_completed
        FROM liquidation_records lr
        JOIN granted_cash_advances gca ON lr.cash_advance_id = gca.id
        WHERE lr.cash_advance_id = ? AND lr.liquidation_number = ?
    ");
    $stmt->execute([$cash_advance_id, $liquidation_number]);
    $detailed_liquidation = $stmt->fetch(PDO::FETCH_ASSOC);
    // Fetch first and last liquidation dates for this cash advance
    $stmt = $pdo->prepare("SELECT MIN(date_submitted) as first_liquidation_date, MAX(date_submitted) as last_liquidation_date FROM liquidation_records WHERE cash_advance_id = ?");
    $stmt->execute([$cash_advance_id]);
    $dates = $stmt->fetch(PDO::FETCH_ASSOC);
    $first_liquidation_date = $dates['first_liquidation_date'];
    $last_liquidation_date = $dates['last_liquidation_date'];
}

$completed = [];
$total_liquidated = 0;
$total_count = 0;

try {
    // Fetch completed liquidations
    $stmt = $pdo->query("SELECT * FROM granted_cash_advances WHERE status = 'completed' ORDER BY date_completed DESC");
    $completed = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate totals
    $stmt = $pdo->query("SELECT SUM(amount) as total_amount, COUNT(*) as total_count FROM granted_cash_advances WHERE status = 'completed'");
    $totals = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_liquidated = $totals['total_amount'] ?? 0;
    $total_count = $totals['total_count'] ?? 0;
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Liquidation History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: #f8fafc;
            font-family: 'Inter', sans-serif;
        }
        .stats-row {
            display: flex;
            gap: 2rem;
            margin: 2rem 0 2.5rem 0;
            justify-content: center;
        }
        .stats-card {
            flex: 1;
            background: linear-gradient(90deg, #10b981 0%, #16c784 100%);
            color: #fff;
            border-radius: 18px;
            padding: 3rem 2rem 2rem 2rem;
            text-align: center;
            font-weight: 700;
            font-size: 2.5rem;
            box-shadow: 0 4px 24px rgba(16,185,129,0.08);
            min-width: 320px;
        }
        .stats-label {
            font-size: 1.1rem;
            font-weight: 500;
            color: #e0e0e0;
            margin-top: 0.5rem;
        }
        .section-header {
            background: linear-gradient(90deg, #10b981 0%, #16c784 100%);
            color: #fff;
            border-radius: 12px 12px 0 0;
            padding: 1.2rem 2rem;
            font-size: 1.3rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            margin-bottom: 0;
        }
        .search-container {
            background: #fff;
            border-radius: 12px;
            padding: 1.2rem 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            width: 100%;
        }
        .search-bar {
            position: relative;
            width: 100%;
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }
        .search-bar input {
            border-radius: 12px;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid #e2e8f0;
            flex: 1 1 200px;
            min-width: 0;
            font-size: 1rem;
            background: #f8fafc;
        }
        .search-bar button {
            height: 42px;
            min-width: 90px;
        }
        .history-card {
            background: linear-gradient(135deg, #f8fafc 60%, #e0f2fe 100%);
            border-radius: 18px;
            box-shadow: 0 6px 24px rgba(16,185,129,0.10);
            margin-bottom: 2.2rem;
            padding: 2.2rem 2.2rem 1.5rem 2.2rem;
            border: none;
            position: relative;
            transition: box-shadow 0.2s, transform 0.2s;
            border-left: 8px solid #10b981;
            overflow: hidden;
        }
        .history-card[data-type="Payroll"] { border-left-color: #2563eb; }
        .history-card[data-type="Special Purposes"] { border-left-color: #f59e0b; }
        .history-card[data-type="Travel"] { border-left-color: #06b6d4; }
        .history-card[data-type="Confidential Funds"] { border-left-color: #a21caf; }
        .history-card:hover {
            box-shadow: 0 12px 32px rgba(16,185,129,0.18);
            transform: translateY(-4px) scale(1.01);
            background: linear-gradient(135deg, #f0fdfa 60%, #bae6fd 100%);
        }
        .history-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.2rem;
            gap: 1.2rem;
        }
        .history-card .avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #10b981 0%, #16c784 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.1rem;
            box-shadow: 0 2px 8px rgba(16,185,129,0.10);
        }
        .history-info h5 {
            margin: 0;
            font-weight: 800;
            font-size: 1.25rem;
            color: #0f172a;
            letter-spacing: 0.01em;
        }
        .type-badge {
            background: #e0edff;
            color: #2563eb;
            border-radius: 20px;
            font-size: 0.98rem;
            font-weight: 700;
            padding: 0.4rem 1.2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            margin-right: 0.5rem;
            box-shadow: 0 1px 4px rgba(37,99,235,0.07);
        }
        .completed-badge {
            background: #dcfce7;
            color: #166534;
            border-radius: 20px;
            font-size: 0.98rem;
            font-weight: 700;
            padding: 0.4rem 1.2rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            box-shadow: 0 1px 4px rgba(16,185,129,0.07);
        }
        .history-details {
            display: flex;
            flex-wrap: wrap;
            gap: 2.2rem 2rem;
            margin-bottom: 1.2rem;
        }
        .history-detail {
            min-width: 170px;
            display: flex;
            align-items: flex-start;
            gap: 0.7rem;
            margin-bottom: 0.5rem;
        }
        .history-detail .label {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            margin-top: 0.2rem;
        }
        .history-detail .value {
            font-size: 1.08rem;
            font-weight: 700;
            color: #1e293b;
            margin-top: 0.1rem;
        }
        .history-detail .amount {
            color: #10b981;
            font-size: 1.18rem;
            font-weight: 800;
        }
        .history-detail .duration-badge {
            background: #10b981;
            color: #fff;
            border-radius: 20px;
            font-size: 0.98rem;
            font-weight: 700;
            padding: 0.4rem 1.2rem;
            display: inline-block;
            box-shadow: 0 1px 4px rgba(16,185,129,0.07);
        }
        .history-detail i {
            color: #a3a3a3;
            font-size: 1.1rem;
            margin-top: 0.15rem;
        }
        @media (max-width: 900px) {
            .history-details {
                flex-direction: column;
                gap: 1.2rem;
            }
            .history-card {
                padding: 1.2rem 1rem 1rem 1rem;
            }
        }
        mark {
            background: rgb(226, 207, 35);
            color: #222;
            padding: 0 2px;
            border-radius: 3px;
        }
        .btn-outline-primary {
            border: 2px solid #10b981;
            color: #10b981;
            background: transparent;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            padding: 0.4rem 1rem;
            transition: all 0.3s ease;
        }
        .btn-outline-primary:hover {
            background: #10b981;
            color: #fff;
            border-color: #10b981;
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(16, 185, 129, 0.2);
        }
        .btn-sm {
            padding: 0.3rem 0.8rem;
            font-size: 0.85rem;
        }
        .modal-content {
            background-color: #ffffff;
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            position: relative;
            z-index: 1058;
        }
        .modal-header {
            background-color: #ffffff;
            border-bottom: 1px solid #e5e7eb;
            position: relative;
            z-index: 1058;
        }
        .modal-body {
            background-color: #ffffff;
            position: relative;
            z-index: 1058;
        }
        .modal-footer {
            background-color: #ffffff;
            border-top: 1px solid #e5e7eb;
            position: relative;
            z-index: 1058;
        }
        .modal {
            z-index: 1055;
        }
        .modal-backdrop {
            display: none !important;
        }
        .modal-dialog {
            z-index: 1056;
        }
        .modal .form-control {
            position: relative;
            z-index: 1057;
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            cursor: text;
        }
        .modal .form-control:focus {
            background-color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
        }
        .modal .form-label {
            position: relative;
            z-index: 1057;
            color: #374151;
            cursor: default;
        }
        .modal .btn {
            position: relative;
            z-index: 1057;
            cursor: pointer !important;
            pointer-events: auto !important;
        }
        .modal .btn:hover {
            cursor: pointer !important;
        }
        .modal .btn-close {
            background-color: #ffffff;
            opacity: 0.7;
            cursor: pointer !important;
            pointer-events: auto !important;
        }
        .modal .btn-close:hover {
            opacity: 1;
            cursor: pointer !important;
        }
        .modal * {
            pointer-events: auto !important;
        }
        .modal input, .modal button, .modal select, .modal textarea {
            cursor: pointer !important;
        }
        .modal input[type="text"], .modal input[type="email"], .modal textarea {
            cursor: text !important;
        }
        .liquidation-card {
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .liquidation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
            border-color: #10b981;
        }
        .liquidation-card:active {
            transform: translateY(0);
        }
        .liquidation-card .card-header {
            transition: all 0.3s ease;
        }
        .liquidation-card:hover .card-header {
            background: linear-gradient(45deg, #10b981, #16c784) !important;
        }
        .liquidation-card .fa-chevron-right {
            transition: transform 0.3s ease;
        }
        .liquidation-card:hover .fa-chevron-right {
            transform: translateX(3px);
        }
        .liquidation-details-container .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        .liquidation-details-container .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        .liquidation-detailed-card {
            background: linear-gradient(135deg, #f8fafc 60%, #e0f2fe 100%);
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(16,185,129,0.13);
            padding: 2.5rem 2rem 2rem 2rem;
            max-width: 900px;
            margin: 0 auto 2.5rem auto;
            border-left: 10px solid #10b981;
            position: relative;
            overflow: hidden;
            transition: box-shadow 0.2s, transform 0.2s;
        }
        .liquidation-detailed-card.first { border-left-color: #10b981; }
        .liquidation-detailed-card.second { border-left-color: #f59e0b; }
        .liquidation-detailed-card.third { border-left-color: #2563eb; }
        .liquidation-detailed-card .header-row {
            display: flex;
            align-items: center;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .liquidation-detailed-card .avatar {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            background: linear-gradient(135deg,#10b981 0%,#16c784 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            box-shadow: 0 2px 8px rgba(16,185,129,0.12);
        }
        .liquidation-detailed-card .main-info {
            flex: 1;
        }
        .liquidation-detailed-card .main-info .name {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f172a;
        }
        .liquidation-detailed-card .main-info .email {
            color: #6b7280;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.2rem;
        }
        .liquidation-detailed-card .liquidation-badge {
            background: #fef3c7;
            color: #d97706;
            padding: 0.5rem 1.2rem;
            border-radius: 20px;
            font-weight: 700;
            font-size: 1rem;
            display: inline-block;
            margin-left: auto;
        }
        .liquidation-detailed-card .liquidation-badge.first { background: #dcfce7; color: #10b981; }
        .liquidation-detailed-card .liquidation-badge.second { background: #fef3c7; color: #d97706; }
        .liquidation-detailed-card .liquidation-badge.third { background: #dbeafe; color: #2563eb; }
        .liquidation-detailed-card .info-row {
            display: flex;
            flex-wrap: wrap;
            gap: 2.5rem 2rem;
            align-items: flex-start;
        }
        .liquidation-detailed-card .info-block {
            flex: 1;
            min-width: 220px;
            margin-bottom: 1.2rem;
        }
        .liquidation-detailed-card .info-block .label {
            color: #6b7280;
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 0.2rem;
        }
        .liquidation-detailed-card .info-block .value {
            font-size: 1.13rem;
            font-weight: 700;
            color: #1e293b;
        }
        .liquidation-detailed-card .info-block .type-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 0.3rem 1rem;
            border-radius: 12px;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
            margin-top: 0.2rem;
        }
        .liquidation-detailed-card .calc-block {
            margin-top: 1.1rem;
            background: linear-gradient(90deg, #f3f4f6 60%, #e0e7ef 100%);
            padding: 1.1rem 1.2rem;
            border-radius: 14px;
            box-shadow: 0 2px 8px rgba(16,185,129,0.06);
            margin-bottom: 1.2rem;
        }
        .liquidation-detailed-card .calc-block .calc-label {
            font-size: 0.97rem;
            color: #6b7280;
            font-weight: 700;
            margin-top: 0.5rem;
        }
        .liquidation-detailed-card .calc-block .calc-value {
            font-size: 1.13rem;
            font-weight: 800;
            color: #2563eb;
        }
        .liquidation-detailed-card .calc-block .calc-value.orange { color: #f59e0b; }
        .liquidation-detailed-card .calc-block .calc-value.green { color: #10b981; }
        .liquidation-detailed-card .calc-block .calc-value.red { color: #d97706; }
        .liquidation-detailed-card .status-block {
            margin-top: 0.5rem;
        }
        .liquidation-detailed-card .status-badge {
            background: #ecfdf5;
            color: #16a34a;
            padding: 0.6rem 1.5rem;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            gap: 0.7rem;
        }
        .liquidation-detailed-card .status-badge.pending {
            color: #d97706;
            background: #fef3c7;
        }
        @media (max-width: 900px) {
            .liquidation-detailed-card {
                padding: 1.2rem 0.7rem 1rem 0.7rem;
            }
            .liquidation-detailed-card .info-row {
                flex-direction: column;
                gap: 1.2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="section-header">
            <i class="fas fa-undo-alt"></i> Liquidation History
        </div>
        <div class="search-container">
            <div class="search-bar">
                <input type="text" id="searchInput" class="form-control" placeholder="Search by name, purpose, or type..." autocomplete="off">
                <button id="clearSearch" type="button" class="btn btn-secondary">Clear</button>
            </div>
        </div>
        <div id="historyCards">
            <?php if ($show_detailed && $detailed_liquidation): ?>
                <?php
                $liqClass = 'first';
                if ($detailed_liquidation['liquidation_number'] == 2) $liqClass = 'second';
                if ($detailed_liquidation['liquidation_number'] == 3) $liqClass = 'third';
                ?>
                <div class="liquidation-detailed-card <?php echo $liqClass; ?>">
                    <div class="header-row">
                        <div class="avatar"><i class="fas fa-user-tie"></i></div>
                        <div class="main-info">
                            <div class="name"><?php echo htmlspecialchars($detailed_liquidation['full_name']); ?></div>
                            <div class="email"><i class='fas fa-envelope'></i> <?php echo htmlspecialchars($detailed_liquidation['email']); ?></div>
                        </div>
                        <span class="liquidation-badge <?php echo $liqClass; ?>">
                            <span style="font-weight:700;font-size:1.1rem;"> <?php echo htmlspecialchars($detailed_liquidation['liquidation_number']); ?></span> <?php echo $detailed_liquidation['liquidation_number'] == 1 ? 'First' : ($detailed_liquidation['liquidation_number'] == 2 ? 'Second' : 'Third'); ?> Liquidation
                            </span>
                    </div>
                    <hr style="margin:1.5rem 0;">
                    <div class="info-row">
                        <div class="info-block">
                            <div class="label">PURPOSE</div>
                            <div class="value"><?php echo htmlspecialchars($detailed_liquidation['purpose']); ?></div>
                            <div class="label" style="margin-top:1rem;">TYPE</div>
                            <span class="type-badge"><i class='fas fa-credit-card'></i> <?php echo htmlspecialchars($detailed_liquidation['type']); ?></span>
                        </div>
                        <div class="info-block">
                            <div class="label">ORIGINAL AMOUNT</div>
                            <div class="value" style="color:#2563eb;">₱<?php echo number_format($detailed_liquidation['cash_advance_amount'],2); ?></div>
                            <?php
                            $is_first_liquidation = ($detailed_liquidation['liquidation_number'] == 1);
                            $is_second_liquidation = ($detailed_liquidation['liquidation_number'] == 2);
                            $is_third_liquidation = ($detailed_liquidation['liquidation_number'] == 3);
                            $has_remaining_balance = (isset($detailed_liquidation['remaining_balance']) && floatval($detailed_liquidation['remaining_balance']) > 0.01);
                            $remaining_cash_advance_before = null;
                            $total_liquidated_now = null;
                            if ($is_second_liquidation) {
                                $stmt2 = $pdo->prepare("SELECT SUM(amount_liquidated) FROM liquidation_records WHERE cash_advance_id = ? AND liquidation_number < 2");
                                $stmt2->execute([$detailed_liquidation['cash_advance_id']]);
                                $total_liquidated_before = floatval($stmt2->fetchColumn());
                                $remaining_cash_advance_before = floatval($detailed_liquidation['cash_advance_amount']) - $total_liquidated_before;
                                $total_liquidated_now = $total_liquidated_before + floatval($detailed_liquidation['amount_liquidated']);
                            } elseif ($is_third_liquidation) {
                                $stmt2 = $pdo->prepare("SELECT SUM(amount_liquidated) FROM liquidation_records WHERE cash_advance_id = ? AND liquidation_number < 3");
                                $stmt2->execute([$detailed_liquidation['cash_advance_id']]);
                                $total_liquidated_before = floatval($stmt2->fetchColumn());
                                $remaining_cash_advance_before = floatval($detailed_liquidation['cash_advance_amount']) - $total_liquidated_before;
                                $total_liquidated_now = $total_liquidated_before + floatval($detailed_liquidation['amount_liquidated']);
                            }
                            ?>
                            <?php if ($is_second_liquidation || $is_third_liquidation): ?>
                            <div class="calc-block">
                                <div class="calc-label">Remaining Cash Advance (Before This Liquidation)</div>
                                <div class="calc-value orange">₱<?php echo number_format($remaining_cash_advance_before,2); ?></div>
                                <div class="calc-label">Amount Liquidated This Submission</div>
                                <div class="calc-value">₱<?php echo number_format($detailed_liquidation['amount_liquidated'],2); ?></div>
                                <div class="calc-label">Total Amount Liquidated</div>
                                <div class="calc-value green">₱<?php echo number_format($total_liquidated_now,2); ?></div>
                                <div class="calc-label">Remaining Balance</div>
                                <div class="calc-value red">₱<?php echo number_format($detailed_liquidation['remaining_balance'],2); ?></div>
                            </div>
                            <?php endif; ?>
                            <?php if (!($is_second_liquidation || $is_third_liquidation)): ?>
                            <div class="calc-block" style="display:flex;align-items:center;gap:2.5rem;">
                                <div>
                                    <div class="calc-label" style="color:#10b981;">TOTAL LIQUIDATED</div>
                                    <div class="calc-value green">₱<?php echo number_format($detailed_liquidation['amount_liquidated'],2); ?></div>
                                </div>
                                <div>
                                    <div class="calc-label" style="color:#f59e0b;">REMAINING BALANCE</div>
                                    <div class="calc-value orange">₱<?php echo number_format($detailed_liquidation['remaining_balance'],2); ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="info-block">
                            <div class="label">STATUS</div>
                            <div class="status-block">
                                <?php
                                $stmt_count = $pdo->prepare("SELECT COUNT(*) FROM liquidation_records WHERE cash_advance_id = ?");
                                $stmt_count->execute([$detailed_liquidation['cash_advance_id']]);
                                $liquidation_count = (int)$stmt_count->fetchColumn();
                                if ($is_first_liquidation && $has_remaining_balance) {
                                    $custom_status = 'PENDING';
                                } elseif ($is_second_liquidation && $liquidation_count >= 3 && $has_remaining_balance) {
                                    $custom_status = 'PENDING';
                                } else {
                                    $custom_status = strtoupper($detailed_liquidation['status']);
                                }
                                $custom_last_liquidation_date = $is_first_liquidation && $has_remaining_balance ? '' : htmlspecialchars($last_liquidation_date);
                                ?>
                                <span class="status-badge <?php echo ($custom_status === 'PENDING') ? 'pending' : ''; ?>">
                                    <i class='fas fa-times-circle'></i> <?php echo $custom_status; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-block">
                            <div class="label">VOUCHER NUMBER</div>
                            <div class="value"><?php echo htmlspecialchars($detailed_liquidation['voucher_number']); ?></div>
                            <div class="label" style="margin-top:1rem;">CHEQUE NUMBER</div>
                            <div class="value"><?php echo htmlspecialchars($detailed_liquidation['cheque_number']); ?></div>
                            <div class="label" style="margin-top:1rem;">REFERENCE NUMBER</div>
                            <div class="value"><?php echo htmlspecialchars($detailed_liquidation['reference_number']); ?></div>
                            <div class="label" style="margin-top:1rem;">JEV NUMBER</div>
                            <div class="value"><?php echo htmlspecialchars($detailed_liquidation['jev_number']); ?></div>
                        </div>
                        <div class="info-block">
                            <div class="label">DATE GRANTED</div>
                            <div class="value"><?php echo htmlspecialchars($detailed_liquidation['date_granted']); ?></div>
                            <div class="label" style="margin-top:1rem;">DUE DATE</div>
                            <div class="value"><?php echo htmlspecialchars($detailed_liquidation['due_date']); ?></div>
                        </div>
                        <div class="info-block">
                            <div class="label">LAST LIQUIDATION DATE</div>
                            <div class="value"><?php echo $custom_last_liquidation_date; ?></div>
                            <div class="label" style="margin-top:1rem;">FIRST LIQUIDATION DATE</div>
                            <div class="value"><?php echo htmlspecialchars($first_liquidation_date); ?></div>
                        </div>
                    </div>
                    <div style="max-width:900px;margin:2rem auto 1.5rem auto;text-align:left;">
                    <a href="dashboard.php?page=history.php" class="btn btn-secondary mb-3">
                        <i class="fas fa-arrow-left"></i> Back to All Liquidations
                    </a>
            </div>
                </div>
            <?php else: ?>
                <?php foreach ($completed as $row): ?>
                    <?php
                    $stmt = $pdo->prepare("SELECT * FROM liquidation_records WHERE cash_advance_id = ? ORDER BY liquidation_number ASC");
                    $stmt->execute([$row['id']]);
                    $liquidations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    $date_granted = new DateTime($row['date_granted']);
                    $date_completed = new DateTime($row['date_completed']);
                    $duration = $date_granted->diff($date_completed)->days;
                    $typeColor = '';
                    switch ($row['type']) {
                        case 'Payroll': $typeColor = '#2563eb'; break;
                        case 'Special Purposes': $typeColor = '#f59e0b'; break;
                        case 'Travel': $typeColor = '#06b6d4'; break;
                        case 'Confidential Funds': $typeColor = '#a21caf'; break;
                        default: $typeColor = '#10b981'; break;
                    }
                    ?>
                    <div class="history-card fade-in" data-type="<?php echo htmlspecialchars($row['type']); ?>" style="border-left-color: <?php echo $typeColor; ?>;">
                        <div class="history-header">
                            <div class="avatar"><i class="fas fa-user"></i></div>
                            <div class="history-info">
                                <h5><?php echo htmlspecialchars($row['name']); ?></h5>
                                <span class="type-badge"><i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['type']); ?></span>
                                <span class="completed-badge"><i class="fas fa-check-circle"></i> Completed</span>
                            </div>
                            <div class="ms-auto d-none d-md-flex flex-column align-items-end justify-content-center" style="min-width: 180px;">
                                <div style="color:#10b981;font-size:1.1rem;font-weight:700;">Total Amount</div>
                                <div style="color:#10b981;font-size:1.6rem;font-weight:800;line-height:1.1;">₱<?php echo number_format($row['amount'], 2); ?></div>
                            </div>
                        </div>
                        <div class="history-details">
                            <div class="history-detail"><i class="fas fa-file-alt"></i><div><div class="label">Purpose</div><div class="value"><?php echo htmlspecialchars($row['purpose']); ?></div></div></div>
                            <div class="history-detail"><i class="fas fa-money-check-alt"></i><div><div class="label">Cheque Number</div><div class="value"><?php echo htmlspecialchars($row['cheque_number'] ?? '-'); ?></div></div></div>
                            <div class="history-detail"><i class="fas fa-receipt"></i><div><div class="label">Voucher Number</div><div class="value"><?php echo htmlspecialchars($row['voucher_number'] ?? '-'); ?></div></div></div>
                            <div class="history-detail"><i class="fas fa-calendar-plus"></i><div><div class="label">Date Granted</div><div class="value"><?php echo date('M d, Y', strtotime($row['date_granted'])); ?></div></div></div>
                            <div class="history-detail"><i class="fas fa-calendar-check"></i><div><div class="label">Date Completed</div><div class="value"><?php echo date('M d, Y', strtotime($row['date_completed'])); ?></div></div></div>
                            <div class="history-detail"><i class="fas fa-hourglass-half"></i><div><div class="label">Duration</div><div class="duration-badge"><?php echo $duration; ?> days</div></div></div>
                            <div class="history-detail"><i class="fas fa-tasks"></i><div><div class="label">Actions</div><div>
                                <?php foreach ($liquidations as $liquidation): ?>
                                    <form method="get" action="dashboard.php" style="display:inline-block;margin-right:1rem;">
                                        <input type="hidden" name="page" value="history.php">
                                        <input type="hidden" name="cash_advance_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                        <input type="hidden" name="liquidation_number" value="<?php echo htmlspecialchars($liquidation['liquidation_number']); ?>">
                                        <button type="submit" class="btn btn-outline-primary btn-sm" style="min-width:160px;margin-bottom:1rem;">
                                            <i class="fas fa-list-alt"></i> <?php echo $liquidation['liquidation_number'] == 1 ? 'First' : ($liquidation['liquidation_number'] == 2 ? 'Second' : 'Third'); ?> Liquidation
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div></div></div>
                            <div class="history-detail d-md-none"><i class="fas fa-coins"></i><div><div class="label">Total Amount</div><div class="amount">₱<?php echo number_format($row['amount'], 2); ?></div></div></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Liquidation Details Modal -->
    <div class="modal fade" id="liquidationDetailsModal" tabindex="-1" aria-labelledby="liquidationDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="liquidationDetailsModalLabel">
                        <i class="fas fa-list-alt"></i> Liquidation Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="liquidationDetailsContent">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading liquidation details...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Bootstrap modals
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                const bsModal = new bootstrap.Modal(modal, {
                    backdrop: false,
                    keyboard: true,
                    focus: true
                });
            });

            // Search bar functionality
            const searchInput = document.getElementById('searchInput');
            const clearBtn = document.getElementById('clearSearch');

            function getHistoryCards() {
                return document.querySelectorAll('.history-card');
            }

            function filterHistoryCards() {
                const val = searchInput.value.toLowerCase().trim();
                const cards = getHistoryCards();

                cards.forEach(card => {
                    // Remove previous highlights
                    card.querySelectorAll('mark').forEach(mark => {
                        const parent = mark.parentNode;
                        const textNode = document.createTextNode(mark.textContent);
                        parent.replaceChild(textNode, mark);
                        parent.normalize();
                    });

                    // Get text content for searching
                    const text = card.textContent.toLowerCase();

                    // Show/hide cards based on search
                    if (!val || text.includes(val)) {
                        card.style.display = '';
                        if (val) {
                            highlightMatches(card, val);
                        }
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            function highlightMatches(card, searchTerm) {
                const highlightIn = card.querySelectorAll('.history-info .value, .history-details .value, .history-info h5, .type-badge, .completed-badge');
                highlightIn.forEach(element => {
                    const walker = document.createTreeWalker(element, NodeFilter.SHOW_TEXT, null);
                    const textNodes = [];
                    let node;
                    while (node = walker.nextNode()) {
                        textNodes.push(node);
                    }

                    textNodes.forEach(textNode => {
                        const parent = textNode.parentNode;
                        const text = textNode.data;
                        if (!text.toLowerCase().includes(searchTerm) || parent.tagName === 'MARK') {
                            return;
                        }

                        const fragment = document.createDocumentFragment();
                        let lastIndex = 0;
                        const regex = new RegExp(searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
                        let match;

                        while ((match = regex.exec(text)) !== null) {
                            if (match.index > lastIndex) {
                                fragment.appendChild(document.createTextNode(text.slice(lastIndex, match.index)));
                            }
                            const mark = document.createElement('mark');
                            mark.textContent = text.slice(match.index, match.index + searchTerm.length);
                            fragment.appendChild(mark);
                            lastIndex = match.index + searchTerm.length;
                        }

                        if (lastIndex < text.length) {
                            fragment.appendChild(document.createTextNode(text.slice(lastIndex)));
                        }

                        parent.replaceChild(fragment, textNode);
                        parent.normalize();
                    });
                });
            }

            if (document.querySelector('.history-card')) {
                searchInput.addEventListener('input', filterHistoryCards);
                clearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    filterHistoryCards();
                    searchInput.focus();
                });
            } else {
                searchInput.disabled = true;
                clearBtn.disabled = true;
            }

            // View liquidation details
        function viewLiquidationDetails(cashAdvanceId) {
            const modal = new bootstrap.Modal(document.getElementById('liquidationDetailsModal'));
            const contentDiv = document.getElementById('liquidationDetailsContent');
            
            contentDiv.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading liquidation details...</p>
                </div>
            `;
            
            modal.show();
            
            fetch(`liquidation_modal.php?action=get_liquidation_details&cash_advance_id=${cashAdvanceId}`)
                .then(response => response.text())
                .then(data => {
                    contentDiv.innerHTML = data;
                    contentDiv.setAttribute('data-original-content', data);
                    contentDiv.setAttribute('data-cash-advance-id', cashAdvanceId);
                })
                .catch(error => {
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i> Error loading liquidation details: ${error.message}
                        </div>
                    `;
                });
        }

            // Show detailed liquidation information
        function showLiquidationDetails(liquidationData, liquidationNumber) {
            const modal = new bootstrap.Modal(document.getElementById('liquidationDetailsModal'));
            const contentDiv = document.getElementById('liquidationDetailsContent');

            function formatDate(dateStr) {
                if (!dateStr) return '-';
                const d = new Date(dateStr);
                if (isNaN(d)) return dateStr;
                return d.toISOString().split('T')[0];
            }

            const detailedView = `
                <div class="modern-liquidation-card" style="background:#fff;border-radius:18px;box-shadow:0 4px 24px rgba(16,185,129,0.08);padding:2.5rem 2rem 2rem 2rem;max-width:900px;margin:0 auto;">
                    <div style="display:flex;align-items:center;gap:1.5rem;margin-bottom:1.5rem;">
                        <div style="background:linear-gradient(135deg,#10b981 0%,#16c784 100%);width:64px;height:64px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-size:2.2rem;box-shadow:0 2px 8px rgba(16,185,129,0.12);">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <div style="flex:1;">
                            <div style="font-size:1.35rem;font-weight:700;">${liquidationData.full_name || '-'}</div>
                            <div style="color:#6b7280;font-size:1rem;display:flex;align-items:center;gap:0.5rem;"><i class='fas fa-envelope'></i> ${liquidationData.email || '-'}</div>
                        </div>
                        <div style="margin-left:auto;">
                            <span style="background:#fef3c7;color:#d97706;padding:0.5rem 1.2rem;border-radius:20px;font-weight:600;font-size:1rem;display:inline-block;">
                                    <span style="font-weight:700;font-size:1.1rem;">${liquidationNumber}</span> ${liquidationNumber == 1 ? 'First' : (liquidationNumber == 2 ? 'Second' : 'Third')} Liquidation
                            </span>
                        </div>
                    </div>
                    <hr style="margin:1.5rem 0;">
                    <div style="display:flex;flex-wrap:wrap;gap:2.5rem 2rem;align-items:flex-start;">
                        <div style="flex:1;min-width:220px;">
                            <div style="color:#6b7280;font-size:0.9rem;font-weight:600;">PURPOSE</div>
                            <div style="font-size:1.1rem;font-weight:600;">${liquidationData.purpose || '-'}</div>
                            <div style="color:#6b7280;font-size:0.9rem;font-weight:600;margin-top:1rem;">TYPE</div>
                            <span style="background:#dbeafe;color:#1e40af;padding:0.3rem 1rem;border-radius:12px;font-weight:600;display:inline-flex;align-items:center;gap:0.5rem;font-size:1rem;"><i class='fas fa-credit-card'></i> ${liquidationData.type || '-'}</span>
                        </div>
                        <div style="flex:1;min-width:220px;">
                            <div style="color:#6b7280;font-size:0.9rem;font-weight:600;">ORIGINAL AMOUNT</div>
                            <div style="font-size:1.2rem;font-weight:700;color:#2563eb;">₱${parseFloat(liquidationData.cash_advance_amount || liquidationData.original_amount || 0).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                            <div style="margin-top:1rem;background:#ecfdf5;padding:1rem 1.2rem;border-radius:14px;display:flex;align-items:center;gap:2.5rem;">
                                <div>
                                    <div style="font-size:0.9rem;color:#10b981;font-weight:600;">TOTAL LIQUIDATED</div>
                                    <div style="font-size:1.1rem;font-weight:800;color:#10b981;">₱${parseFloat(liquidationData.total_liquidated || liquidationData.amount_liquidated || 0).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                                </div>
                                <div>
                                    <div style="font-size:0.9rem;color:#f59e0b;font-weight:600;">REMAINING BALANCE</div>
                                    <div style="font-size:1.1rem;font-weight:800;color:#f59e0b;">₱${parseFloat(liquidationData.remaining_balance || 0).toLocaleString('en-PH', {minimumFractionDigits:2})}</div>
                                </div>
                            </div>
                        </div>
                        <div style="flex:1;min-width:220px;">
                            <div style="color:#6b7280;font-size:0.9rem;font-weight:600;">STATUS</div>
                            <div style="margin-top:0.5rem;">
                                    <span style="background:#ecfdf5;color:${liquidationData.status.toUpperCase() === 'PENDING' ? '#d97706' : '#16a34a'};padding:0.6rem 1.5rem;border-radius:16px;font-weight:700;font-size:1.1rem;display:inline-flex;align-items:center;gap:0.7rem;">
                                        <i class='fas fa-times-circle'></i> ${liquidationData.status.toUpperCase() || '-'}
                                    </span>
                            </div>
                        </div>
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:2.5rem 2rem;margin-top:2.5rem;align-items:flex-start;">
                        <div style="flex:1;min-width:180px;">
                            <div style="color:#6b7280;font-size:0.9rem;font-weight:600;">VOUCHER NUMBER</div>
                            <div style="font-size:1.1rem;font-weight:600;">${liquidationData.voucher_number || '-'}</div>
                            <div style="color:#6b7280;font-size:0.9rem;font-weight:600;margin-top:1rem;">CHEQUE NUMBER</div>
                            <div style="font-size:1.1rem;font-weight:600;">${liquidationData.cheque_number || '-'}</div>
                            <div style="color:#6b7280;font-size:0.9rem;font-weight:600;margin-top:1rem;">REFERENCE NUMBER</div>
                            <div style="font-size:1.1rem;font-weight:600;">${liquidationData.reference_number || '-'}</div>
                            <div style="color:#6b7280;font-size:0.9rem;font-weight:600;margin-top:1rem;">JEV NUMBER</div>
                            <div style="font-size:1.1rem;font-weight:600;">${liquidationData.jev_number || '-'}</div>
                        </div>
                        <div style="flex:1;min-width:180px;">
                            <div style="color:#6b7280;font-size:0.9rem;font-weight:600;">DATE GRANTED</div>
                            <div style="font-size:1.1rem;font-weight:600;">${formatDate(liquidationData.date_granted)}</div>
                            <div style="color:#6b7280;font-size:0.9rem;font-weight:600;margin-top:1rem;">DUE DATE</div>
                            <div style="font-size:1.1rem;font-weight:600;">${formatDate(liquidationData.due_date)}</div>
                        </div>
                        <div style="flex:1;min-width:180px;">
                            <div style="color:#6b7280;font-size:0.9rem;font-weight:600;">LAST LIQUIDATION DATE</div>
                            <div style="font-size:1.1rem;font-weight:600;">${formatDate(liquidationData.date_submitted)}</div>
                            <div style="color:#6b7280;font-size:0.9rem;font-weight:600;margin-top:1rem;">FIRST LIQUIDATION DATE</div>
                            <div style="font-size:1.1rem;font-weight:600;">${formatDate(liquidationData.first_liquidation_date)}</div>
                        </div>
                    </div>
                </div>
            `;

            contentDiv.innerHTML = detailedView;
            modal.show();
        }

            // Go back to liquidation list
        function goBackToLiquidationList() {
            const contentDiv = document.getElementById('liquidationDetailsContent');
            const originalContent = contentDiv.getAttribute('data-original-content');
            
            if (originalContent) {
                contentDiv.innerHTML = originalContent;
            } else {
                const modal = bootstrap.Modal.getInstance(document.getElementById('liquidationDetailsModal'));
                modal.hide();
                const cashAdvanceId = contentDiv.getAttribute('data-cash-advance-id');
                if (cashAdvanceId) {
                    setTimeout(() => {
                        viewLiquidationDetails(cashAdvanceId);
                    }, 300);
                }
            }
            }
        });
    </script>
</body>
</html>