<?php
// session_start();
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';
require_once __DIR__ . '/includes/EmailSender.php';

$pending = [];
$error = "";

// Ensure variables are defined to avoid warnings
$reminder_sent = $reminder_sent ?? false;
$liquidation_completed = $liquidation_completed ?? false;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$reminder_count = $reminder_count ?? 0;
$completed_name = $completed_name ?? '';
$today = $today ?? date('Y-m-d');

try {
    $query = "
        SELECT 
            gca.id,
            gca.name,
            gca.email,
            gca.purpose,
            gca.amount,
            gca.type,
            gca.voucher_number,
            gca.cheque_number,
            gca.date_granted,
            gca.due_date,
            gca.status,
            gca.event_name,
            COALESCE(lr.total_liquidated, 0) as total_liquidated,
            (gca.amount - COALESCE(lr.total_liquidated, 0)) as remaining_balance,
            CASE 
                WHEN e.user_id IS NOT NULL THEN e.user_id
                WHEN be.bonded_id IS NOT NULL THEN be.bonded_id
                ELSE NULL
            END as employee_id
        FROM granted_cash_advances gca
        LEFT JOIN employees e ON gca.name = e.user_name
        LEFT JOIN bonded_employees be ON gca.name = be.full_name
        LEFT JOIN (
            SELECT 
                cash_advance_id,
                SUM(amount_liquidated) as total_liquidated
            FROM liquidation_records 
            GROUP BY cash_advance_id
        ) lr ON gca.id = lr.cash_advance_id
        WHERE gca.status = 'pending'
    ";
    if (!empty($search)) {
        $query .= " AND (gca.name LIKE :search OR gca.purpose LIKE :search OR gca.type LIKE :search OR gca.email LIKE :search)";
    }
    $query .= " ORDER BY gca.due_date ASC";
    $stmt = $pdo->prepare($query);
    if (!empty($search)) {
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    $stmt->execute();
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// For the cash advance type dropdown, get all unique types from the database, not just $pending
$all_types = [];
try {
    $type_stmt = $pdo->query("SELECT DISTINCT type FROM granted_cash_advances");
    $all_types = $type_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $all_types = [];
}
// For employee type, get all bonded employee names
$bonded_names = [];
try {
    $bonded_stmt = $pdo->query("SELECT full_name FROM bonded_employees");
    $bonded_names = $bonded_stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $bonded_names = [];
}

// Helper function for reminder schedule
function getReminderSchedule($type) {
    $schedules = [
        'Payroll' => ['first_reminder' => 3, 'final_reminder' => 5],
        'Travel' => ['first_reminder' => 15, 'final_reminder' => 30],
        'Special Purposes' => ['first_reminder' => 10, 'final_reminder' => 25],
    ];
    return $schedules[$type] ?? ['first_reminder' => 15, 'final_reminder' => 30];
}

// Add backend logic for reminder and mark as complete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_reminder'])) {
        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $purpose = $_POST['purpose'] ?? '';
        $amount = $_POST['amount'] ?? 0;
        $due_date = $_POST['due_date'] ?? '';
        $type = $_POST['type'] ?? '';
        $reminder_type = $_POST['reminder_type'] ?? '';
        try {
            $emailSender = new EmailSender();
            $subject = ($reminder_type === 'final' ? 'FINAL ' : '') . "Reminder: Cash Advance Liquidation Due";
            $message = "<p>Dear " . htmlspecialchars($name) . ",</p>"
                . "<p>This is a " . ($reminder_type === 'final' ? '<b>FINAL</b> ' : '') . "reminder that your cash advance liquidation is now due:</p>"
                . "<ul>"
                . "<li><strong>Purpose:</strong> " . htmlspecialchars($purpose) . "</li>"
                . "<li><strong>Amount:</strong> ₱" . number_format($amount, 2) . "</li>"
                . "<li><strong>Type:</strong> " . htmlspecialchars($type) . "</li>"
                . "<li><strong>Due Date:</strong> " . htmlspecialchars($due_date) . "</li>"
                . "</ul>"
                . "<p>Please submit your liquidation documents as soon as possible to avoid any penalties.</p>"
                . "<p>If you have already submitted your liquidation, please disregard this message.</p>"
                . "<p>Thank you for your immediate attention to this matter.</p>";
            $emailSender->sendReminder($email, $subject, $message);
            $reminder_sent = true;
            $reminder_count = 1;
        } catch (Exception $e) {
            $error = "Failed to send reminder to $name. " . $e->getMessage();
        }
    } elseif (isset($_POST['complete_liquidation'])) {
        $liquidation_id = $_POST['liquidation_id'] ?? '';
        $name = $_POST['name'] ?? '';
        try {
            $stmt = $pdo->prepare("UPDATE granted_cash_advances SET status = 'completed', date_completed = CURDATE() WHERE id = ?");
            if ($stmt->execute([$liquidation_id])) {
                $liquidation_completed = true;
                $completed_name = $name;
            } else {
                $error = "Failed to mark liquidation as completed.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Liquidations</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Bootstrap & FontAwesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --warning: #f59e0b;
            --warning-dark: #d97706;
            --danger: #ef4444;
            --danger-dark: #dc2626;
            --success: #10b981;
            --success-dark: #059669;
            --info: #06b6d4;
            --bg-light: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --overdue-bg: #fef2f2;
            --due-soon-bg: #fffbeb;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: #1f2937;
            min-height: 100vh;
            margin: 0;
        }

        /* Sidebar Styles
        .sidebar {
            width: 250px;
            background: #f8fafc;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            padding: 20px;
            border-right: 1px solid var(--border-color);
            box-shadow: var(--shadow);
        }

        .sidebar .nav-link {
            color: #1f2937;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .sidebar .nav-link:hover {
            background: #e2e8f0;
            color: var(--primary);
        }

        .sidebar .nav-link.active {
            background: var(--primary);
            color: white;
        } */

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
            background: linear-gradient(135deg, var(--warning) 0%, var(--warning-dark) 100%);
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .card-header h4 {
            margin: center;
            
            font-weight: 700;
            font-size: 1.5rem;
        }

        .card-body {
            padding: 2rem;
        }

        /* Enhanced Search Bar */
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

        /* Enhanced Employee Cards */
        .employee-card {
            background: white;
            border-radius: 16px;
            box-shadow: var(--shadow);
            border: 2px solid transparent;
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 1.5rem;
            position: relative;
        }

        .employee-card::before {
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

        .employee-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .employee-card:hover::before {
            opacity: 1;
        }

        .employee-card.overdue {
            border-color: var(--danger);
            background: var(--overdue-bg);
        }

        .employee-card.overdue::before {
            background: linear-gradient(90deg, var(--danger) 0%, var(--danger-dark) 100%);
            opacity: 1;
        }

        .employee-card.due-soon {
            border-color: var(--warning);
            background: var(--due-soon-bg);
        }

        .employee-card.due-soon::before {
            background: linear-gradient(90deg, var(--warning) 0%, var(--warning-dark) 100%);
            opacity: 1;
        }

        .employee-card-body {
            padding: 1.5rem;
        }

        .employee-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .employee-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .employee-avatar {
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

        .employee-details h5 {
            margin: 0 0 0.5rem 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: #1f2937;
        }

        .employee-details .employee-email {
            color: #6b7280;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .employee-details .employee-id {
            color: #059669;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
        }

        .status-indicator {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.5rem;
        }

        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-badge.overdue {
            background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .status-badge.due-soon {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .status-badge.normal {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .employee-content {
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

        .info-value.text-success {
            color: var(--success);
            font-weight: 600;
        }

        .info-value.text-warning {
            color: var(--warning);
            font-weight: 600;
        }

        .liquidation-info {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border: 1px solid #bbf7d0;
            border-radius: 8px;
            padding: 0.75rem;
            margin-top: 0.5rem;
        }

        .liquidation-info .info-label {
            font-size: 0.75rem;
            color: #166534;
            font-weight: 600;
        }

        .liquidation-info .info-value {
            font-size: 0.9rem;
            color: #166534;
            font-weight: 700;
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

        .schedule-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #e2e8f0;
        }

        .schedule-info h6 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            font-weight: 600;
            color: #374151;
        }

        .schedule-details {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            font-size: 0.8rem;
            color: #6b7280;
        }

        .schedule-details .current-day {
            color: var(--primary);
            font-weight: 600;
        }

        /* Enhanced Action Buttons */
        .action-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .action-section h6 {
            margin: 0 0 1rem 0;
            font-size: 1rem;
            font-weight: 600;
            color: #374151;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: center;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .action-buttons .btn {
            flex: 1;
            min-width: 140px;
        }

        .reminder-section {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .reminder-section h6 {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
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

        .btn-reminder-first {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
            border: 1px solid #fde68a;
        }

        .btn-reminder-first:hover {
            background: linear-gradient(135deg, #fde68a 0%, #fcd34d 100%);
            color: #b45309;
        }

        .btn-reminder-final {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
            border: 1px solid #fecaca;
        }

        .btn-reminder-final:hover {
            background: linear-gradient(135deg, #fecaca 0%, #fca5a5 100%);
            color: #b91c1c;
        }

        .btn-complete {
            background: linear-gradient(135deg, var(--success) 0%, var(--success-dark) 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-complete:hover {
            background: linear-gradient(135deg, var(--success-dark) 0%, #047857 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-complete-section {
            grid-column: 1 / -1;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .content {
                margin-left: 0;
                padding: 10px 20px;
            }
            .search-container {
                flex-direction: column;
                align-items: stretch;
            }
            .search-bar {
                max-width: 100%;
            }
            .search-container button {
                width: 100%;
                margin-top: 10px;
            }
            .employee-content {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            .action-buttons {
                grid-template-columns: 1fr;
            }
            .employee-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            .status-indicator {
                align-items: flex-start;
            }
        }

        /* Enhanced Alerts */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        .alert-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
            color: #92400e;
            border-left: 4px solid var(--warning);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        mark {
            background: #fff59d;
            color: #222;
            padding: 0 2px;
            border-radius: 3px;
        }

        /* Modal Enhancements */
        .modal-content {
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(16,185,129,0.18);
            border: none;
            overflow: hidden;
        }
        .modal-header {
            background: linear-gradient(90deg, #10b981 0%, #2563eb 100%);
            color: #fff;
            border-bottom: none;
            border-radius: 20px 20px 0 0;
            padding: 1.5rem 2rem 1.2rem 2rem;
            box-shadow: 0 2px 8px rgba(16,185,129,0.08);
        }
        .modal-title {
            font-size: 1.35rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 0.7rem;
        }
        .modal-header .btn-close {
            background-color: #fff;
            opacity: 0.8;
            border-radius: 50%;
            margin-left: 1rem;
            transition: opacity 0.2s;
        }
        .modal-header .btn-close:hover {
            opacity: 1;
        }
        .modal-body {
            background: #f8fafc;
            padding: 2rem 2rem 1.5rem 2rem;
        }
        .modal-footer {
            background: linear-gradient(90deg, #e0f2fe 0%, #f8fafc 100%);
            border-top: none;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 -2px 8px rgba(16,185,129,0.04);
            padding: 1.2rem 2rem;
        }
        .modal .form-control {
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 0.9rem 1.1rem;
            font-size: 1.05rem;
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .modal .form-control:focus {
            border-color: #10b981;
            box-shadow: 0 0 0 0.15rem rgba(16,185,129,0.13);
        }
        .modal .btn {
            border-radius: 18px;
            font-weight: 700;
            font-size: 1.05rem;
            padding: 0.6rem 1.6rem;
            transition: background 0.2s, color 0.2s, box-shadow 0.2s;
            box-shadow: 0 2px 8px rgba(16,185,129,0.08);
        }
        .modal .btn-primary {
            background: linear-gradient(90deg, #10b981 0%, #2563eb 100%);
            border: none;
            color: #fff;
        }
        .modal .btn-primary:hover {
            background: linear-gradient(90deg, #2563eb 0%, #10b981 100%);
            color: #fff;
        }
        .modal .btn-secondary {
            background: #e0e7ef;
            color: #2563eb;
            border: none;
        }
        .modal .btn-secondary:hover {
            background: #2563eb;
            color: #fff;
        }
        .modal .alert {
            border-radius: 12px;
            font-size: 1.01rem;
            margin-bottom: 1.2rem;
        }
        @media (max-width: 600px) {
            .modal-content, .modal-header, .modal-footer, .modal-body {
                padding-left: 0.7rem !important;
                padding-right: 0.7rem !important;
            }
            .modal-header, .modal-footer {
                padding-top: 1rem !important;
                padding-bottom: 1rem !important;
            }
        }

        /* Fix for modal pointer-events and background issues */
        .modal, .modal-dialog, .modal-content, .modal-body, .modal-footer, .modal-backdrop {
            pointer-events: auto !important;
        }
        .modal, .modal-backdrop { background: none !important; }
        .modal-backdrop { display: none !important; }
        body.modal-open { overflow: hidden; }
    </style>
</head>
<body>
    <!-- Sidebar
    <div class="sidebar">
        <h5 class="text-center mb-4">Menu</h5>
        <nav class="nav flex-column">
            <a class="nav-link active" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a class="nav-link" href="pending_liquidations.php"><i class="fas fa-file-invoice"></i> Pending Liquidations</a>
            <a class="nav-link" href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
            <a class="nav-link" href="settings.php"><i class="fas fa-cog"></i> Settings</a>
            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </div> -->

    <!-- Main Content -->
    <div class="content">
       
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Pending Liquidations</h4>
    </div>
                        <div class="card-body">
                            <form method="GET" class="mb-4">
        <div class="search-container">
                                    <div class="search-bar">
                                        <input type="text" id="searchInput" name="search" class="form-control" placeholder="Search by name, purpose, or type..." value="<?php echo htmlspecialchars($search); ?>" autocomplete="off">
                                    </div>
                                    <select id="employeeTypeFilter" class="form-select" style="max-width: 150px;">
                                        <option value="all" selected>All Employees</option>
                                        <option value="regular">Regular</option>
                                        <option value="bonded">Bonded</option>
                                    </select>
                                    <select id="cashAdvanceTypeFilter" class="form-select" style="max-width: 180px;">
                                        <option value="all" selected>All Cash Advance Types</option>
                                        <?php
                                        foreach ($all_types as $type) {
                                            echo '<option value="' . htmlspecialchars($type) . '">' . htmlspecialchars($type) . '</option>';
                                        }
                                        ?>
                                    </select>
                                    <button type="button" id="clearSearch" class="btn btn-secondary">Clear</button>
                                </div>
                            </form>
                            
                            <?php if ($reminder_sent): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>Successfully sent <?php echo $reminder_count; ?> automated reminder email(s) based on cash advance types and schedules.
            </div>
                            <?php endif; ?>
                            
                            <?php if ($liquidation_completed): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>Successfully marked liquidation for <strong><?php echo htmlspecialchars($completed_name); ?></strong> as completed.
        </div>
                            <?php endif; ?>
                            
        <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                </div>
        <?php endif; ?>

                            <?php if (count($pending) === 0): ?>
                                <div class="alert alert-success">No pending liquidations!</div>
                            <?php elseif (empty(array_filter($pending, fn($row) => stripos($row['name'], $search) !== false || stripos($row['purpose'], $search) !== false || stripos($row['type'], $search) !== false)) && $search): ?>
                                <div class="alert alert-warning">No results found for "<?php echo htmlspecialchars($search); ?>".</div>
                            <?php else: ?>
                                <div class="employee-cards-container">
            <?php foreach ($pending as $row): ?>
                                        <?php 
                                        $schedule = getReminderSchedule($row['type']);
                                        $date_granted = new DateTime($row['date_granted']);
                                        $today_date = new DateTime($today);
                                        $days_since_granted = $today_date->diff($date_granted)->days;
                                        $is_overdue = $row['due_date'] <= $today;
                                        $is_due_soon = !$is_overdue && ($row['due_date'] <= date('Y-m-d', strtotime('+7 days')));
                                        $status_class = $is_overdue ? 'overdue' : ($is_due_soon ? 'due-soon' : 'normal');
                                        $employee_type = in_array($row['name'], $bonded_names) ? 'bonded' : 'regular';
                                        ?>
                                        <div class="employee-card <?php echo $status_class; ?>" data-employee-type="<?php echo $employee_type; ?>" data-cash-type="<?php echo htmlspecialchars(strtolower($row['type'])); ?>">
                                            <div class="employee-card-body">
                                                <div class="employee-header">
                                                    <div class="employee-info">
                                                        <div class="employee-avatar">
                                                            <i class="fas fa-user-tie"></i>
                                                        </div>
                                                        <div class="employee-details">
                            <h5><?php echo htmlspecialchars($row['name']); ?></h5>
                                                            <?php if (!empty($row['employee_id'])): ?>
                                                                <div class="employee-id">
                                                                    <i class="fas fa-id-card"></i>
                                                                    ID: <?php echo htmlspecialchars($row['employee_id']); ?>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="employee-email">
                                                                <i class="fas fa-envelope"></i>
                                                                <?php echo !empty($row['email']) ? htmlspecialchars($row['email']) : 'No email provided'; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="status-indicator">
                                                        <span class="status-badge <?php echo $status_class; ?>">
                                                            <i class="fas <?php echo $is_overdue ? 'fa-exclamation-triangle' : ($is_due_soon ? 'fa-clock' : 'fa-check-circle'); ?>"></i>
                                                            <?php echo $is_overdue ? 'Overdue' : ($is_due_soon ? 'Due Soon' : 'On Track'); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                
                                                <div class="employee-content">
                                                    <div class="info-section">
                                                        <?php if (strtolower($row['type']) === 'special purposes'): ?>
                                                            <div class="info-label">Event Name</div>
                                                            <div class="info-value"><?php echo htmlspecialchars($row['event_name'] ?? '-'); ?></div>
                                                        <?php else: ?>
                                                            <div class="info-label">Purpose</div>
                                                            <div class="info-value"><?php echo htmlspecialchars($row['purpose']); ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="info-section">
                                                        <div class="info-label">Original Amount</div>
                                                        <div class="info-value amount">₱<?php echo number_format($row['amount'], 2); ?></div>
                                                        <?php if ($row['total_liquidated'] > 0): ?>
                                                            <div class="liquidation-info">
                                                                <div class="info-label">Total Liquidated</div>
                                                                <div class="info-value text-success">₱<?php echo number_format($row['total_liquidated'], 2); ?></div>
                                                                <div class="info-label mt-2">Remaining Balance</div>
                                                                <div class="info-value text-warning fw-bold">₱<?php echo number_format($row['remaining_balance'], 2); ?></div>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="info-section">
                                                        <div class="info-label">Type</div>
                                                        <div class="type-badge">
                                                            <i class="fas fa-credit-card"></i>
                                                            <?php echo htmlspecialchars($row['type']); ?>
                    </div>
                        </div>
                        </div>
                                                <div class="employee-content">
                                                    <div class="info-section">
                                                        <div class="info-label">Date Granted</div>
                                                        <div class="info-value"><?php echo htmlspecialchars($row['date_granted']); ?></div>
                                                        <div class="info-label">Voucher Number</div>
                                                        <div class="info-value"><?php echo htmlspecialchars($row['voucher_number'] ?? '-'); ?></div>
                                                    </div>
                                                    <div class="info-section">
                                                        <div class="info-label">Due Date</div>
                                                        <div class="info-value"><?php echo htmlspecialchars($row['due_date']); ?></div>
                                                        <div class="info-label">Cheque Number</div>
                                                        <div class="info-value"><?php echo htmlspecialchars($row['cheque_number'] ?? '-'); ?></div>
                                                    </div>
                                                    <div class="info-section">
                                                        <div class="schedule-info">
                                                            <h6><i class="fas fa-calendar-alt me-1"></i>Reminder Schedule</h6>
                                                            <div class="schedule-details">
                                                                <?php if ($schedule['first_reminder']): ?>
                                                                    <div>1st Reminder: Day <?php echo $schedule['first_reminder']; ?></div>
                                                                <?php endif; ?>
                                                                <?php if ($schedule['final_reminder']): ?>
                                                                    <div>Final Reminder: Day <?php echo $schedule['final_reminder']; ?></div>
                                                                <?php endif; ?>
                                                                <div class="current-day">Current: Day <?php echo $days_since_granted; ?></div>
                        </div>
                            </div>
                        </div>
                    </div>
                                                
                                                <div class="action-section">
                                                    <h6><i class="fas fa-tools me-2"></i>Actions</h6>
                                                    <div class="action-buttons">
                                                        <?php if (!empty($row['email'])): ?>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['name']); ?>">
                                                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($row['email']); ?>">
                                                                <input type="hidden" name="purpose" value="<?php echo htmlspecialchars($row['purpose']); ?>">
                                                                <input type="hidden" name="amount" value="<?php echo htmlspecialchars($row['amount']); ?>">
                                                                <input type="hidden" name="due_date" value="<?php echo htmlspecialchars($row['due_date']); ?>">
                                                                <input type="hidden" name="type" value="<?php echo htmlspecialchars($row['type']); ?>">
                                                                <input type="hidden" name="reminder_type" value="first">
                                                                <button type="submit" name="send_reminder" class="btn btn-reminder-first" onclick="return confirm('Send first reminder email to <?php echo htmlspecialchars($row['name']); ?>?')">
                                                                    <i class="fas fa-bell"></i>First Reminder
                                                                </button>
                                                            </form>
                                                            <?php if ($schedule['final_reminder']): ?>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['name']); ?>">
                                                                <input type="hidden" name="email" value="<?php echo htmlspecialchars($row['email']); ?>">
                                                                <input type="hidden" name="purpose" value="<?php echo htmlspecialchars($row['purpose']); ?>">
                                                                <input type="hidden" name="amount" value="<?php echo htmlspecialchars($row['amount']); ?>">
                                                                <input type="hidden" name="due_date" value="<?php echo htmlspecialchars($row['due_date']); ?>">
                                                                <input type="hidden" name="type" value="<?php echo htmlspecialchars($row['type']); ?>">
                                                                <input type="hidden" name="reminder_type" value="final">
                                                                <button type="submit" name="send_reminder" class="btn btn-reminder-final" onclick="return confirm('Send final reminder email to <?php echo htmlspecialchars($row['name']); ?>?')">
                                                                    <i class="fas fa-exclamation-triangle"></i>Final Reminder
                                                                </button>
                                                            </form>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="liquidation_id" value="<?php echo htmlspecialchars($row['id']); ?>">
                                                            <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['name']); ?>">
                                                            <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($row['employee_id'] ?? ''); ?>">
                                                            <input type="hidden" name="full_name" value="<?php echo htmlspecialchars($row['name']); ?>">
                                                            <input type="hidden" name="type" value="<?php echo htmlspecialchars($row['type']); ?>">
                                                            <input type="hidden" name="voucher_number" value="<?php echo htmlspecialchars($row['voucher_number'] ?? ''); ?>">
                                                            <input type="hidden" name="cheque_number" value="<?php echo htmlspecialchars($row['cheque_number'] ?? ''); ?>">
                                                            <input type="hidden" name="cash_advance_amount" value="<?php echo htmlspecialchars($row['amount']); ?>">
                                                            <button type="button" class="btn btn-primary" onclick="fetchAndOpenLiquidationModal(<?php echo htmlspecialchars($row['id']); ?>, '<?php echo htmlspecialchars($row['employee_id'] ?? ''); ?>', '<?php echo htmlspecialchars($row['name']); ?>', '<?php echo htmlspecialchars($row['type']); ?>', '<?php echo htmlspecialchars($row['voucher_number'] ?? ''); ?>', '<?php echo htmlspecialchars($row['cheque_number'] ?? ''); ?>', <?php echo htmlspecialchars($row['amount']); ?>, <?php echo htmlspecialchars($row['remaining_balance']); ?>, <?php echo htmlspecialchars($row['total_liquidated']); ?>)">
                                                                <i class="fas fa-plus-circle"></i>Submit Liquidation
                                                            </button>
                                                        </form>
                                                        
                                                        <?php if ($row['total_liquidated'] > 0): ?>
                                                            <button type="button" class="btn btn-info" onclick="viewLiquidationDetails(<?php echo htmlspecialchars($row['id']); ?>)">
                                                                <i class="fas fa-file-invoice"></i>Liquidation Details
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
                            <?php endif; ?>
                            <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
                        </div>
                    </div>
                </div>
            </div>
       
    </div>

    <!-- Liquidation Modal -->
    <div class="modal fade" id="liquidationModal" tabindex="-1" aria-labelledby="liquidationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="liquidationModalLabel">
                        <i class="fas fa-plus-circle me-2"></i>Submit New Liquidation
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="process_liquidation.php" id="liquidationForm">
                    <div class="modal-body">
                        <!-- New Liquidation Form -->
                        <h6 class="mb-3"><i class="fas fa-plus-circle me-2"></i>New Liquidation Entry</h6>
                        
                        <input type="hidden" id="cashAdvanceId" name="cash_advance_id">
                        <input type="hidden" id="liquidationNumber" name="liquidation_number" value="1">
                        
                        <div class="row">
                            <!-- Auto-filled fields -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Employee ID</label>
                                <input type="text" class="form-control" id="employeeId" name="employee_id" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="fullName" name="full_name" readonly>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Type</label>
                                <input type="text" class="form-control" id="type" name="type" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Voucher Number</label>
                                <input type="text" class="form-control" id="voucherNumber" name="voucher_number" readonly>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Cheque Number</label>
                                <input type="text" class="form-control" id="chequeNumber" name="cheque_number" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" id="amountLabel">Cash Advance Amount</label>
                                <input type="text" class="form-control" id="cashAdvanceAmount" name="cash_advance_amount" readonly>
                            </div>
                        </div>
                        
                        <!-- Admin input fields -->
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Amount Liquidated <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="amountLiquidated" name="amount_liquidated" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Reference Number</label>
                                <input type="text" class="form-control" id="referenceNumber" name="reference_number">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">JEV Number</label>
                                <input type="text" class="form-control" id="jevNumber" name="jev_number">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date Submitted <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="dateSubmitted" name="date_submitted" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Remarks</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Close
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitLiquidationBtn">
                            <i class="fas fa-save me-2"></i>Submit Liquidation
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Liquidation Details Modal -->
    <div class="modal fade" id="viewLiquidationModal" tabindex="-1" aria-labelledby="viewLiquidationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewLiquidationModalLabel">
                        <i class="fas fa-file-invoice me-2"></i>Liquidation Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="viewLiquidationLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Loading liquidation details...</p>
                    </div>
                    
                    <div id="viewLiquidationContent" style="display: none;">
                        <!-- Cash Advance Summary -->
                        <div class="alert alert-info mb-3">
                            <h6><i class="fas fa-info-circle me-2"></i>Cash Advance Summary</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <strong>Employee:</strong><br>
                                    <span id="viewEmployeeName" class="text-primary fw-bold"></span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Original Amount:</strong><br>
                                    <span id="viewOriginalAmount" class="text-primary fw-bold"></span>
                                </div>
                                <div class="col-md-4">
                                    <strong>Remaining Balance:</strong><br>
                                    <span id="viewRemainingBalance" class="text-warning fw-bold"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Existing Liquidations Table -->
                        <h6 class="mb-3"><i class="fas fa-history me-2"></i>Liquidation History</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Liquidation #</th>
                                        <th>Amount Liquidated</th>
                                        <th>Date Submitted</th>
                                        <th>Reference Number</th>
                                        <th>JEV Number</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody id="viewLiquidationsTable">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Initialize Bootstrap modals
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize all modals with proper configuration
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                const bsModal = new bootstrap.Modal(modal, {
                    backdrop: false,
                    keyboard: true,
                    focus: true
                });
            });

            // Clear form when liquidation modal is closed
            const liquidationModal = document.getElementById('liquidationModal');
            if (liquidationModal) {
                liquidationModal.addEventListener('hidden.bs.modal', function() {
                    const form = this.querySelector('form');
                    if (form) {
                        form.reset();
                        // Remove any validation classes
                        form.querySelectorAll('.is-invalid').forEach(field => {
                            field.classList.remove('is-invalid');
                        });
                    }
                });

                // Ensure form fields are focusable when modal opens
                liquidationModal.addEventListener('shown.bs.modal', function() {
                    const firstInput = this.querySelector('input:not([readonly]):not([type="hidden"])');
                    if (firstInput) {
                        firstInput.focus();
                    }
                });
            }

            // Handle view liquidation modal
            const viewLiquidationModal = document.getElementById('viewLiquidationModal');
            if (viewLiquidationModal) {
                viewLiquidationModal.addEventListener('shown.bs.modal', function() {
                    // Modal is shown, content will be loaded via AJAX
                });
            }
        });

        // Client-side search for employee cards
        const searchInput = document.getElementById('searchInput');
        const cards = document.querySelectorAll('.employee-card');
        const clearBtn = document.getElementById('clearSearch');
        const employeeTypeFilter = document.getElementById('employeeTypeFilter');
        const cashAdvanceTypeFilter = document.getElementById('cashAdvanceTypeFilter');

        function filterAndHighlight() {
            const val = searchInput.value.toLowerCase();
            const empType = employeeTypeFilter.value;
            const cashType = cashAdvanceTypeFilter.value;
            cards.forEach(card => {
                // Remove previous highlights
                card.innerHTML = card.innerHTML.replace(/<mark>(.*?)<\/mark>/gi, '$1');
                const text = card.textContent.toLowerCase();
                let matches = true;
                // Search filter
                if (val && !text.includes(val)) matches = false;
                // Employee type filter
                if (empType !== 'all') {
                    if (card.dataset.employeeType !== empType) matches = false;
                }
                // Cash advance type filter
                if (cashType !== 'all') {
                    if (card.dataset.cashType !== cashType.toLowerCase()) matches = false;
                }
                card.style.display = matches ? '' : 'none';
                if (matches && val) highlightMatches(card, val);
            });
        }

        searchInput.addEventListener('input', filterAndHighlight);
        employeeTypeFilter.addEventListener('change', filterAndHighlight);
        cashAdvanceTypeFilter.addEventListener('change', filterAndHighlight);

        clearBtn.addEventListener('click', function() {
            searchInput.value = '';
            employeeTypeFilter.value = 'all';
            cashAdvanceTypeFilter.value = 'all';
            cards.forEach(card => {
                card.style.display = '';
                // Remove highlights
                card.innerHTML = card.innerHTML.replace(/<mark>(.*?)<\/mark>/gi, '$1');
            });
            searchInput.focus();
        });

        // Highlight function
        function highlightMatches(card, searchTerm) {
            // Only highlight in .employee-details, .employee-content, and .employee-card-body
            const highlightIn = card.querySelectorAll('.employee-details, .employee-content, .employee-card-body');
            highlightIn.forEach(section => {
                highlightText(section, searchTerm);
            });
        }
        function highlightText(node, searchTerm) {
            if (node.nodeType === 3) { // text node
                const idx = node.data.toLowerCase().indexOf(searchTerm);
                if (idx >= 0 && searchTerm.length > 0) {
                    const span = document.createElement('span');
                    span.innerHTML = node.data.substring(0, idx) + '<mark>' + node.data.substring(idx, idx + searchTerm.length) + '</mark>' + node.data.substring(idx + searchTerm.length);
                    node.parentNode.replaceChild(span, node);
                }
            } else if (node.nodeType === 1 && node.childNodes && !['SCRIPT', 'STYLE', 'MARK'].includes(node.tagName)) {
                // Don't highlight inside <mark> tags
                for (let i = 0; i < node.childNodes.length; i++) {
                    highlightText(node.childNodes[i], searchTerm);
                }
            }
        }

        // Liquidation Modal Functions
        function fetchAndOpenLiquidationModal(cashAdvanceId, employeeId, fullName, type, voucherNumber, chequeNumber, cashAdvanceAmount, remainingBalance, totalLiquidated) {
            // Fetch existing liquidations for this cash advance
            fetch('liquidation_modal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_cash_advance_details&cash_advance_id=' + cashAdvanceId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const existingLiquidations = data.existing_liquidations || [];
                    openLiquidationModal(cashAdvanceId, employeeId, fullName, type, voucherNumber, chequeNumber, cashAdvanceAmount, remainingBalance, totalLiquidated, existingLiquidations);
                } else {
                    alert('Error: ' + data.error);
                }
            })
            .catch(error => {
                alert('Error fetching liquidation data: ' + error);
            });
        }

        function openLiquidationModal(cashAdvanceId, employeeId, fullName, type, voucherNumber, chequeNumber, cashAdvanceAmount, remainingBalance, totalLiquidated, existingLiquidations = []) {
            document.getElementById('cashAdvanceId').value = cashAdvanceId;
            // Determine liquidation number
            let liquidationNumber = 1;
            if (existingLiquidations.length === 1) {
                liquidationNumber = 2;
            } else if (existingLiquidations.length === 2) {
                liquidationNumber = 3;
            }
            document.getElementById('liquidationNumber').value = liquidationNumber;
            
            // Populate the form fields
            document.getElementById('employeeId').value = employeeId;
            document.getElementById('fullName').value = fullName;
            document.getElementById('type').value = type;
            document.getElementById('voucherNumber').value = voucherNumber;
            document.getElementById('chequeNumber').value = chequeNumber;
            
            // Show the appropriate amount based on liquidation number
            if (liquidationNumber > 1) {
                // Second or subsequent liquidation - show remaining balance
                document.getElementById('amountLabel').textContent = 'Remaining Balance';
                document.getElementById('cashAdvanceAmount').value = '₱' + parseFloat(remainingBalance).toLocaleString('en-PH', {minimumFractionDigits: 2});
                // Set max amount for liquidation to remaining balance
                document.getElementById('amountLiquidated').max = remainingBalance;
            } else {
                // First liquidation - show original amount
                document.getElementById('amountLabel').textContent = 'Cash Advance Amount';
                document.getElementById('cashAdvanceAmount').value = '₱' + parseFloat(cashAdvanceAmount).toLocaleString('en-PH', {minimumFractionDigits: 2});
                // Set max amount for liquidation to original amount
                document.getElementById('amountLiquidated').max = cashAdvanceAmount;
            }
            
            // Set default date to today
            document.getElementById('dateSubmitted').value = new Date().toISOString().split('T')[0];
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('liquidationModal'));
            modal.show();
        }

        function populateLiquidationForm(data) {
            // This function is no longer needed with form submission
            console.log('Form will be populated via PHP');
        }

        function viewLiquidationDetails(cashAdvanceId) {
            // Show loading
            document.getElementById('viewLiquidationLoading').style.display = 'block';
            document.getElementById('viewLiquidationContent').style.display = 'none';
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('viewLiquidationModal'));
            modal.show();
            
            // Fetch liquidation details using AJAX
            fetch('liquidation_modal.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_cash_advance_details&cash_advance_id=' + cashAdvanceId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateViewLiquidationModal(data);
                } else {
                    alert('Error: ' + data.error);
                    modal.hide();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while loading the liquidation details.');
                modal.hide();
            });
        }

        function populateViewLiquidationModal(data) {
            const cashAdvance = data.cash_advance;
            
            // Hide loading, show content
            document.getElementById('viewLiquidationLoading').style.display = 'none';
            document.getElementById('viewLiquidationContent').style.display = 'block';
            
            // Populate summary information
            document.getElementById('viewEmployeeName').textContent = cashAdvance.full_name;
            document.getElementById('viewOriginalAmount').textContent = '₱' + parseFloat(cashAdvance.cash_advance_amount).toLocaleString('en-PH', {minimumFractionDigits: 2});
            document.getElementById('viewRemainingBalance').textContent = '₱' + parseFloat(data.remaining_balance).toLocaleString('en-PH', {minimumFractionDigits: 2});
            
            // Populate liquidations table
            const tableBody = document.getElementById('viewLiquidationsTable');
            tableBody.innerHTML = '';
            
            if (data.existing_liquidations.length > 0) {
                data.existing_liquidations.forEach(liquidation => {
                    const row = tableBody.insertRow();
                    row.innerHTML = `
                        <td><span class="badge bg-${liquidation.liquidation_number == 1 ? 'success' : (liquidation.liquidation_number == 2 ? 'warning' : 'danger')}">${liquidation.liquidation_number}</span></td>
                        <td>₱${parseFloat(liquidation.amount_liquidated).toLocaleString('en-PH', {minimumFractionDigits: 2})}</td>
                        <td>${liquidation.date_submitted}</td>
                        <td>${liquidation.reference_number || '-'}</td>
                        <td>${liquidation.jev_number || '-'}</td>
                        <td><span class="badge bg-${liquidation.status === 'pending' ? 'warning' : (liquidation.status === 'approved' ? 'success' : 'danger')}">${liquidation.status}</span></td>
                    `;
                });
            } else {
                tableBody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No liquidations found</td></tr>';
            }
        }
    </script>
</body>
</html>