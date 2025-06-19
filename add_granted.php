<?php
// session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';

// Cash Advance Eligibility Logic:
// - Employees can receive new cash advances only when they have NO pending or approved cash advances
// - Employees with completed liquidations (status = 'completed') are eligible for new cash advances
// - This prevents multiple active cash advances while allowing new ones after completion

$success = $error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] === 'submit') {
        $employee_type = $_POST['employee_type'];
        $employee_id = $_POST['employee_id'];
        $cash_advance_type = $_POST['cash_advance_type'];

        $purpose = '';
        $departure_date = '';
        $arrival_date = '';
        $event_name = '';
        $event_date = null;
        $amount = 0;
        $due_date = null;

        if ($cash_advance_type === 'Special Purposes') {
            $event_name = trim($_POST['event_name'] ?? '');
            $purpose = 'Special Purpose: ' . $event_name;
            $amount = floatval($_POST['special_amount'] ?? 0);
            $event_date = $_POST['event_date'] ?? null;
            $due_date = date('Y-m-d', strtotime('+30 days'));
        } elseif ($cash_advance_type === 'Payroll') {
            $purpose = trim($_POST['payroll_purpose'] ?? '');
            $amount = floatval($_POST['payroll_amount'] ?? 0);
            $departure_date = $_POST['payroll_date_granted'] ?? '';
            $arrival_date = $_POST['payroll_date_granted'] ?? '';
            $due_date = date('Y-m-d', strtotime('+5 days'));
        } else {
            $purpose = trim($_POST['purpose'] ?? '');
            $amount = floatval($_POST['amount'] ?? 0);
            $departure_date = $_POST['departure_date'] ?? '';
            $arrival_date = $_POST['arrival_date'] ?? '';
            $due_date = date('Y-m-d', strtotime('+30 days'));
        }

                try {
                    // Get employee details based on type
                    if ($employee_type === 'regular') {
                        $stmt = $pdo->prepare("SELECT user_name, email FROM employees WHERE user_id = ? AND status = 'active'");
                    } else {
                        $stmt = $pdo->prepare("SELECT full_name as user_name, email FROM bonded_employees WHERE bonded_id = ?");
                    }
                    $stmt->execute([$employee_id]);
                    $employee = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$employee) {
                $error = "Employee not found.";
            } else {
                        // Check if employee has pending cash advances (exclude completed ones)
                        $stmt = $pdo->prepare("SELECT COUNT(*) as pending_count FROM granted_cash_advances 
                                             WHERE name = ? AND status IN ('pending', 'approved')");
                        $stmt->execute([$employee['user_name']]);
                        $pendingCheck = $stmt->fetch(PDO::FETCH_ASSOC);

                        if ($pendingCheck['pending_count'] > 0) {
                            $error = "Cannot grant new cash advance. This employee has a pending cash advance that needs to be liquidated first.";
                        } else {
                    // Set the rest of the variables
                    $email = trim($_POST['email']);
                    $cheque_number = trim($_POST['cheque_number'] ?? '');
                    $voucher_number = trim($_POST['voucher_number'] ?? '');

                    // Insert logic for each type
                    if ($cash_advance_type === 'Special Purposes') {
                        $stmt = $pdo->prepare("INSERT INTO granted_cash_advances (name, email, purpose, amount, cheque_number, voucher_number, type, status, date_granted, due_date, departure_date, arrival_date, event_name, event_date) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        if ($stmt->execute([
                            $employee['user_name'], $email, $purpose, $amount, $cheque_number, $voucher_number, $cash_advance_type, 'pending', date('Y-m-d'), $due_date, null, null, $event_name, $event_date
                        ])) {
                            $success = "Cash advance has been granted successfully!";
                        } else {
                            $error = "Failed to grant cash advance.";
                        }
                    } elseif ($cash_advance_type === 'Payroll') {
                            $stmt = $pdo->prepare("INSERT INTO granted_cash_advances (name, email, purpose, amount, cheque_number, voucher_number, type, status, date_granted, due_date, departure_date, arrival_date) 
                                                 VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', CURDATE(), ?, ?, ?)");
                        if ($stmt->execute([
                            $employee['user_name'], $email, $purpose, $amount, $cheque_number, $voucher_number, $cash_advance_type, $due_date, $departure_date, $arrival_date
                        ])) {
                                $success = "Cash advance has been granted successfully!";
                            } else {
                                $error = "Failed to grant cash advance.";
                        }
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO granted_cash_advances (name, email, purpose, amount, cheque_number, voucher_number, type, status, date_granted, due_date, departure_date, arrival_date) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', CURDATE(), ?, ?, ?)");
                        if ($stmt->execute([
                            $employee['user_name'], $email, $purpose, $amount, $cheque_number, $voucher_number, $cash_advance_type, $due_date, $departure_date, $arrival_date
                        ])) {
                            $success = "Cash advance has been granted successfully!";
        } else {
                            $error = "Failed to grant cash advance.";
                        }
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch employees and cash advance types
$regular_employees = [];
$bonded_employees = [];
$cash_advance_types = [];

try {
    // Get regular employees - simplified query to ensure all employees are fetched
    $stmt = $pdo->query("
        SELECT 
            e.user_id, 
            e.user_name, 
            e.email,
            'regular' as type,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM granted_cash_advances gca 
                    WHERE gca.name = e.user_name 
                    AND gca.status IN ('pending', 'approved')
                ) THEN 'pending'
                ELSE 'clear'
            END as cash_advance_status,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM granted_cash_advances gca 
                    WHERE gca.name = e.user_name
                ) THEN 'regular'
                ELSE 'new_employee'
            END as employee_category,
            COALESCE((
                SELECT COUNT(*) 
                FROM granted_cash_advances gca 
                WHERE gca.name = e.user_name 
                AND gca.status = 'completed'
            ), 0) as cash_advance_count,
            COALESCE((
                SELECT MAX(gca.date_granted)
                FROM granted_cash_advances gca 
                WHERE gca.name = e.user_name 
                AND gca.status = 'completed'
                AND gca.date_completed >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            ), '1900-01-01') as last_cash_advance_date
        FROM employees e 
        WHERE e.status = 'active' 
        ORDER BY 
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM granted_cash_advances gca 
                    WHERE gca.name = e.user_name
                ) THEN 1
                ELSE 2
            END,
            e.user_name ASC
    ");
    $regular_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get bonded employees - simplified query to ensure all employees are fetched
    $stmt = $pdo->query("
        SELECT 
            be.bonded_id, 
            be.full_name, 
            be.email,
            'bonded' as type,
            CASE 
                WHEN EXISTS (
                    SELECT 1 FROM granted_cash_advances gca 
                    WHERE gca.name = be.full_name 
                    AND gca.status IN ('pending', 'approved')
                ) THEN 'pending'
                ELSE 'clear'
            END as cash_advance_status,
            'bonded' as employee_category,
            COALESCE((
                SELECT COUNT(*) 
                FROM granted_cash_advances gca 
                WHERE gca.name = be.full_name 
                AND gca.status = 'completed'
            ), 0) as cash_advance_count,
            COALESCE((
                SELECT MAX(gca.date_granted)
                FROM granted_cash_advances gca 
                WHERE gca.name = be.full_name 
                AND gca.status = 'completed'
                AND gca.date_completed >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            ), '1900-01-01') as last_cash_advance_date
        FROM bonded_employees be 
        ORDER BY be.full_name ASC
    ");
    $bonded_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->query("SELECT id, type_name FROM cash_advance_types ORDER BY type_name ASC");
    $cash_advance_types = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grant Cash Advance - LGU Liquidation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #ffffff;
            min-height: 100vh;
            color: #1f2937;
            position: relative;
        }

        .container-fluid {
            padding: 20px 40px;
        }

        .main-card {
            background: var(--card-bg);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            border: none;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            padding: 1.5rem;
        }

        .card-body {
            padding: 2rem;
        }

        /* Enhanced Progress Bar */
        .progress {
            height: 12px;
            border-radius: 10px;
            background-color: #e2e8f0;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--primary) 0%, var(--success) 100%);
            border-radius: 10px;
            transition: width 0.6s ease;
        }

        /* Enhanced Step Indicators */
        .step-indicator {
            position: relative;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .step-number {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.4s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .step-indicator.active .step-number {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            transform: scale(1.1);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);
        }

        .step-indicator.completed .step-number {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .step-label {
            font-size: 0.9rem;
            color: #64748b;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .step-indicator.active .step-label {
            color: var(--primary);
            font-weight: 600;
            transform: scale(1.05);
        }

        .step-indicator.completed .step-label {
            color: var(--success);
            font-weight: 600;
        }

        /* Enhanced Cards */
        .employee-type-card, .cash-advance-type-card, .employee-card {
            cursor: pointer;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            border-radius: 15px;
            background: var(--card-bg);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        .employee-type-card:hover, .cash-advance-type-card:hover, .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary);
        }

        .employee-type-card.selected, .cash-advance-type-card.selected, .employee-card.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(59, 130, 246, 0.1) 100%);
            box-shadow: 0 8px 25px rgba(59, 130, 246, 0.2);
        }

        .employee-type-card .card-body, .cash-advance-type-card .card-body {
            padding: 2rem;
            text-align: center;
        }

        .employee-type-card i, .cash-advance-type-card i {
            transition: all 0.3s ease;
        }

        .employee-type-card:hover i, .cash-advance-type-card:hover i {
            transform: scale(1.1);
        }

        /* Enhanced Form Elements */
        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            pointer-events: auto;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
            z-index: 10;
        }

        .form-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }

        /* Enhanced Buttons */
        .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            pointer-events: auto;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
        }

        .btn-success {
            background: linear-gradient(90deg, var(--success) 0%, #059669 100%);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6b7280 0%, #135deg, #4b5563 100%);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(107, 114, 128, 0.4);
        }

        /* Enhanced Alerts */
        .alert {
            border-radius: 12px;
            border: none;
            padding: 1rem 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            color: #065f46;
            border-radius: 4px solid var(--success); */
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) rgba100%);
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        /* Enhanced Badges */
        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .badge-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .type-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .type-badge.regular {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .type-badge.bonded {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .status-badge.available {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .status-badge.pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #fde68a;
        }

        /* Disabled States */
        .employee-card.disabled, .cash-advance-type-card.restricted {
            opacity: 0.6;
            cursor: none-allowed;
            background: #ffffff;
            border: 2px solid #dee2e6;
        }

        .employee-card.disabled:hover,.cash-advance-type-card.restricted:hover {
            transform: none;
            box-shadow: var(--shadow);
        }

        /* Review Section */
        .review-card {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 15px;
            border: 2px solid var(--border-color);
        }

        .review-section {
            padding: 1.5rem;
            border-radius: 10px;
            background: white;
            margin-bottom: 1rem;
        }

        .review-section h6 {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1rem;
            border-bottom: 2px solid var(--border-color);
            padding-bottom: 0.5rem;
        }

        /* Modal Styles */
        .modal {
            z-index: 1050 !important;
        }

        .modal-dialog {
            z-index: 1055 !important;
        }

        .modal-content {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            background: #ffffff;
            border: none;
        }

        .modal-backdrop {
            z-index: 1040 !important;
            background-color: rgba(0, 0, 0, 0.6) !important;
        }

        .modal-header {
            border-bottom: 2px solid #e2e8f0;
            padding: 1.5rem;
            background: #ffffff;
            color: #1f2937;
        }

        .modal-header .modal-title {
            color: #1f2937;
            font-weight: 600;
        }

        .modal-header .btn-close {
            background-color: #6b7280;
            opacity: 0.7;
            transition: opacity 0.2s ease;
        }

        .modal-header .btn-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 1.5rem;
            background: #ffffff;
        }

        .modal-footer {
            border-top: 2px solid #e2e8f0;
            padding: 1rem 1.5rem;
            background: #ffffff;
        }

        .modal .form-control {
            pointer-events: auto !important;
            position: relative;
            z-index: 1;
        }

        .modal .btn {
            pointer-events: auto !important;
            z-index: 1;
        }

        .modal input, .modal button, .modal textarea {
            pointer-events: auto !important;
            z-index: 1;
        }

        .modal .btn-close {
            pointer-events: auto !important;
            z-index: 1;
        }

        /* Ensure modal is fully interactive */
        .modal.show {
            display: block !important;
            pointer-events: auto !important;
        }

        .modal.show .modal-dialog {
            pointer-events: auto !important;
        }

        .modal.show .modal-content {
            pointer-events: auto !important;
        }

        /* Remove any conflicting styles */
        .modal * {
            user-select: auto !important;
            -webkit-user-select: auto !important;
            -moz-user-select: auto !important;
        }

        /* Fix for Bootstrap 5 modal backdrop */
        body.modal-open {
            overflow: hidden;
        }

        .modal-backdrop.show {
            opacity: 0.6 !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 10px 20px;
            }
            
            .card-body {
                padding: 1.5rem;
            }
            
            .step-number {
                width: 40px;
                height: 40px;
                font-size: 1rem;
            }
            
            .step-label {
                font-size: 0.8rem;
            }
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.6s ease-in-out;
        }

        .slide-up {
            animation: slideUp 0.5s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px);
            }
            to { 
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Enhanced Employee Cards */
        .employee-card {
            cursor: pointer;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            border-radius: 16px;
            background: var(--card-bg);
            box-shadow: var(--shadow);
            overflow: hidden;
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
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border-color: var(--primary);
        }

        .employee-card:hover::before {
            opacity: 1;
        }

        .employee-card.selected {
            border-color: var(--primary);
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.03) 0%, rgba(59, 130, 246, 0.08) 100%);
            box-shadow: 0 12px 30px rgba(59, 130, 246, 0.15);
        }

        .employee-card.selected::before {
            opacity: 1;
        }

        .employee-card .card-body {
            padding: 1.5rem;
        }

        .employee-card .employee-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .employee-card .employee-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            margin-right: 1rem;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            flex-shrink: 0;
        }

        .employee-card .employee-info {
            flex-grow: 1;
            min-width: 0;
        }

        .employee-card .employee-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }

        .employee-card .employee-id {
            font-size: 0.85rem;
            color: #6b7280;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .employee-card .badge-container {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }

        .employee-card .status-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .employee-card .status-badge.available {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .employee-card .status-badge.pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .employee-card .type-badge {
            padding: 0.35rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .employee-card .type-badge.regular {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }

        .employee-card .type-badge.bonded {
            background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%);
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .employee-card .employee-details {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .employee-card .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: #6b7280;
        }

        .employee-card .detail-item i {
            color: var(--primary);
            width: 16px;
            text-align: center;
        }

        .employee-card .status-message {
            padding: 0.75rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .employee-card .status-message.success {
            background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .employee-card .status-message.warning {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            color: #92400e;
            border: 1px solid #fde68a;
        }

        .employee-card.disabled {
            opacity: 0.7;
            cursor: not-allowed;
            background: #f8f9fa;
            border: 2px solid #e5e7eb;
        }

        .employee-card.disabled:hover {
            transform: none;
            box-shadow: var(--shadow);
        }

        .employee-card.disabled::before {
            background: linear-gradient(90deg, #f59e0b 0%, #d97706 100%);
        }

        .employee-card.disabled .employee-avatar {
            background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
        }

        /* Enhanced Employee List Container */
        .employee-list-container {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1.5rem;
            margin-top: 1rem;
        }

        .employee-list-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .employee-list-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .employee-count-badge {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }

        /* Ensure modal is always above the backdrop and interactive */
        .modal,
        .modal.show,
        .modal.fade.show {
            z-index: 2000 !important;
            pointer-events: auto !important;
        }
        .modal-backdrop {
            z-index: 1050 !important;
        }
        .modal-backdrop.show {
            opacity: 0.6 !important;
        }
        .modal-dialog,
        .modal-content,
        .modal-header,
        .modal-body,
        .modal-footer {
            pointer-events: auto !important;
        }

        #chequeVoucherModal,
        #chequeVoucherModal * {
            pointer-events: auto !important;
        }
        .modal-backdrop {
            z-index: 1050 !important;
        }
        #chequeVoucherModal {
            z-index: 2000 !important;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="main-card animate__animated animate__fadeInUp">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="fas fa-money-bill-wave me-2"></i>
                            Grant Cash Advance
                        </h4>
                        <p class="mb-0 mt-2 opacity-75">Complete the steps below to grant a new cash advance</p>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success fade-in">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger fade-in">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Enhanced Progress Bar -->
                        <div class="progress">
                            <div class="progress-bar" id="progressBar" role="progressbar" style="width: 25%;" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>

                        <!-- Enhanced Step Indicators -->
                        <div class="row mb-4">
                            <div class="col-md-3 text-center">
                                <div class="step-indicator active" data-step="1">
                                    <div class="step-number">1</div>
                                    <div class="step-label">Select Employee</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="step-indicator" data-step="2">
                                    <div class="step-number">2</div>
                                    <div class="step-label">Cash Advance Type</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="step-indicator" data-step="3">
                                    <div class="step-number">3</div>
                                    <div class="step-label">Details</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="step-indicator" data-step="4">
                                    <div class="step-number">4</div>
                                    <div class="step-label">Review</div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 1: Employee Selection -->
                        <div class="step-content slide-up" id="step1">
                            <h5 class="mb-4 text-primary fw-bold">
                                <i class="fas fa-user-check me-2"></i>Step 1: Select Employee
                            </h5>
                            
                            <!-- Employee Type Filter -->
                            <div class="row mb-4">
                                <div class="col-md-12">
                                    <div class="card">
                                        <div class="card-body">
                                            <h6 class="mb-3"><i class="fas fa-filter me-2"></i>Filter by Employee Category</h6>
                                            <div class="btn-group" role="group">
                                                <input type="radio" class="btn-check" name="employeeFilter" id="allEmployees" value="all" checked>
                                                <label class="btn btn-outline-primary" for="allEmployees">
                                                    <i class="fas fa-users me-1"></i>All Employee
                                                </label>
                                                
                                                <input type="radio" class="btn-check" name="employeeFilter" id="regularEmployees" value="regular">
                                                <label class="btn btn-outline-primary" for="regularEmployees">
                                                    <i class="fas fa-user-tie me-1"></i>Regular Employee
                                                </label>
                                                
                                                <input type="radio" class="btn-check" name="employeeFilter" id="bondedEmployees" value="bonded">
                                                <label class="btn btn-outline-primary" for="bondedEmployees">
                                                    <i class="fas fa-user-graduate me-1"></i>Bonded
                                                </label>
                                                
                                                <input type="radio" class="btn-check" name="employeeFilter" id="newEmployees" value="new_employee">
                                                <label class="btn btn-outline-info" for="newEmployees">
                                                    <i class="fas fa-user-plus me-1"></i>New Employee
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Search Bar -->
                            <div class="row mb-4">
                                <div class="col-md-8">
                                    <div class="input-group">
                                        <span class="input-group-text bg-primary text-white">
                                            <i class="fas fa-search"></i>
                                        </span>
                                        <input type="text" class="form-control" id="employeeSearch" placeholder="Search employees by name or email...">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="d-flex justify-content-end">
                                        <div class="btn-group" role="group">
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="clearSearch">
                                                <i class="fas fa-times me-1"></i>Clear
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="employeeList">
                                <!-- Employee list will be loaded here -->
                            </div>
                        </div>

                        <!-- Step 2: Cash Advance Type -->
                        <div class="step-content" id="step2" style="display: none;">
                            <h5 class="mb-4 text-primary fw-bold">
                                <i class="fas fa-credit-card me-2"></i>Step 2: Choose Cash Advance Type & Email
                            </h5>
                            <div class="row mb-4">
                                <?php foreach ($cash_advance_types as $type): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card cash-advance-type-card" 
                                         data-type-id="<?php echo $type['id']; ?>" 
                                         data-type-name="<?php echo htmlspecialchars($type['type_name']); ?>"
                                         data-restricted="<?php echo strtolower($type['type_name']) === 'payroll' ? 'regular' : 'none'; ?>">
                                        <div class="card-body">
                                            <i class="fas fa-credit-card fa-3x text-info mb-3"></i>
                                            <h6 class="fw-bold"><?php echo htmlspecialchars($type['type_name']); ?></h6>
                                            <?php if (strtolower($type['type_name']) === 'payroll'): ?>
                                                <small class="text-muted">Bonded employees only</small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Email field -->
                            <div class="row">
                                <div class="col-md-6">
                                    <label for="email" class="form-label">
                                        <i class="fas fa-envelope me-2"></i>Email for Reminders
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        This email will be used for sending reminders about the cash advance.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Step 3: Details (Default/Travel/Other Types) -->
                        <div class="step-content" id="step3" style="display: none;">
                            <h5 class="mb-4 text-primary fw-bold">
                                <i class="fas fa-edit me-2"></i>Step 3: Cash Advance Details
                            </h5>
                            <form id="cashAdvanceForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="amount" class="form-label">
                                            <i class="fas fa-peso-sign me-2"></i>Amount (₱)
                                        </label>
                                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" required min="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="purpose" class="form-label">
                                            <i class="fas fa-file-alt me-2"></i>Purpose
                                        </label>
                                        <textarea class="form-control" id="purpose" name="purpose" rows="3" required placeholder="Describe the purpose of this cash advance..."></textarea>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="departure_date" class="form-label">
                                            <i class="fas fa-plane-departure me-2"></i>Departure Date
                                        </label>
                                        <input type="date" class="form-control" id="departure_date" name="departure_date" required>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="arrival_date" class="form-label">
                                            <i class="fas fa-plane-arrival me-2"></i>Arrival Date
                                        </label>
                                        <input type="date" class="form-control" id="arrival_date" name="arrival_date" required>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Step 3: Details (Payroll) -->
                        <div class="step-content" id="step3-payroll" style="display: none;">
                            <h5 class="mb-4 text-primary fw-bold">
                                <i class="fas fa-edit me-2"></i>Step 3: Payroll Cash Advance Details
                            </h5>
                            <form id="payrollForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="payroll_amount" class="form-label">
                                            <i class="fas fa-peso-sign me-2"></i>Amount (₱)
                                        </label>
                                        <input type="number" class="form-control" id="payroll_amount" name="amount" step="0.01" required min="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="payroll_purpose" class="form-label">
                                            <i class="fas fa-file-alt me-2"></i>Purpose
                                        </label>
                                        <textarea class="form-control" id="payroll_purpose" name="purpose" rows="3" required placeholder="Describe the purpose..."></textarea>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="payroll_date_granted" class="form-label">
                                            <i class="fas fa-calendar-day me-2"></i>Date Granted
                                        </label>
                                        <input type="date" class="form-control" id="payroll_date_granted" name="date_granted" required>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- Step 3: Details (Special Purpose) -->
                        <div class="step-content" id="step3-special" style="display: none;">
                            <h5 class="mb-4 text-primary fw-bold">
                                <i class="fas fa-edit me-2"></i>Step 3: Special Purpose Cash Advance Details
                            </h5>
                            <form id="specialForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="special_amount" class="form-label">
                                            <i class="fas fa-peso-sign me-2"></i>Amount (₱)
                                        </label>
                                        <input type="number" class="form-control" id="special_amount" name="amount" step="0.01" required min="0">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="special_event_name" class="form-label">
                                            <i class="fas fa-star me-2"></i>Name of Event
                                        </label>
                                        <input type="text" class="form-control" id="special_event_name" name="event_name" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="special_event_date" class="form-label">
                                            <i class="fas fa-calendar-day me-2"></i>Event Date
                                        </label>
                                        <input type="date" class="form-control" id="special_event_date" name="event_date" required>
                                    </div>
                                </div>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Liquidation counting starts the day after the cash advance is granted.
                                </div>
                            </form>
                        </div>

                        <!-- Step 4: Review -->
                        <div class="step-content" id="step4" style="display: none;">
                            <h5 class="mb-4 text-primary fw-bold">
                                <i class="fas fa-eye me-2"></i>Step 4: Review and Submit
                            </h5>
                            <div class="review-card">
                                <div class="card-body">
                                    <div id="reviewContent">
                                        <!-- Review content will be loaded here -->
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enhanced Navigation Buttons -->
                        <div class="mt-5 d-flex justify-content-between align-items-center">
                            <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">
                                <i class="fas fa-arrow-left me-2"></i>Previous
                            </button>
                            <button type="button" class="btn btn-primary" id="nextBtn">
                                Next<i class="fas fa-arrow-right ms-2"></i>
                            </button>
                            <button type="button" class="btn btn-success" id="submitBtn" style="display: none;">
                                <i class="fas fa-check me-2"></i>Grant Cash Advance
                            </button>
                        </div>

                        <div class="mt-4 text-center">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cheque and Voucher Number Modal (direct child of body, not AJAX) -->
    <div class="modal fade" id="chequeVoucherModal" tabindex="-1" aria-labelledby="chequeVoucherModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="chequeVoucherModalLabel">
                        <i class="fas fa-receipt me-2"></i>Enter Payment Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Please enter the cheque number and voucher number for this cash advance.
                    </div>
                    <form id="chequeVoucherForm" novalidate>
                        <div class="mb-3">
                            <label for="cheque_number" class="form-label">
                                <i class="fas fa-money-check me-2"></i>Cheque Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="cheque_number" name="cheque_number" 
                                   placeholder="Enter cheque number" required>
                            <div class="form-text">Enter the cheque number issued for this cash advance.</div>
                        </div>
                        <div class="mb-3">
                            <label for="voucher_number" class="form-label">
                                <i class="fas fa-file-invoice me-2"></i>Voucher Number <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="voucher_number" name="voucher_number" 
                                   placeholder="Enter voucher number" required>
                            <div class="form-text">Enter the voucher number associated with this cash advance.</div>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Important:</strong> Please verify these numbers carefully before submitting. 
                            They will be recorded in the system and cannot be changed later.
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="confirmGrantBtn">
                        <i class="fas fa-check me-2"></i>Grant Cash Advance
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    let currentStep = 1;
    let selectedEmployee = null;
    let selectedCashAdvanceType = null;
    let formData = {};
    let allEmployees = [];
    let filteredEmployees = [];

    // Initialize employees data
    document.addEventListener('DOMContentLoaded', function() {
        // Combine regular and bonded employees, but if an employee is in both, only show as bonded
        const regularEmployees = <?php echo json_encode($regular_employees); ?>;
        const bondedEmployees = <?php echo json_encode($bonded_employees); ?>;
        const bondedEmails = new Set(bondedEmployees.map(emp => emp.email));
        const regularUnique = regularEmployees
            .filter(emp => !bondedEmails.has(emp.email))
            .map(emp => ({...emp, type: 'regular', displayName: emp.user_name}));
        const bondedUnique = bondedEmployees.map(emp => ({...emp, type: 'bonded', displayName: emp.full_name}));
        allEmployees = [...regularUnique, ...bondedUnique];
        
        filteredEmployees = [...allEmployees];
        loadEmployees();
        updateEmployeeCount();
    });

    // Employee filter functionality
    document.querySelectorAll('input[name="employeeFilter"]').forEach(radio => {
        radio.addEventListener('change', function() {
            filterEmployees();
        });
    });

    // Search functionality
    document.getElementById('employeeSearch').addEventListener('input', function() {
        filterEmployees();
    });

    function filterEmployees() {
        const filterValue = document.querySelector('input[name="employeeFilter"]:checked').value;
        const searchTerm = document.getElementById('employeeSearch').value.toLowerCase();
        
        filteredEmployees = allEmployees.filter(employee => {
            let matchesFilter = false;
            
            switch(filterValue) {
                case 'all':
                    matchesFilter = true;
                    break;
                case 'regular':
                    matchesFilter = employee.type === 'regular' && employee.employee_category === 'regular';
                    break;
                case 'bonded':
                    matchesFilter = employee.type === 'bonded';
                    break;
                case 'new_employee':
                    matchesFilter = employee.employee_category === 'new_employee';
                    break;
                default:
                    matchesFilter = true;
            }
            
            const matchesSearch = employee.displayName.toLowerCase().includes(searchTerm) || 
                                employee.email.toLowerCase().includes(searchTerm);
            
            return matchesFilter && matchesSearch;
        });
        
        loadEmployees();
        updateEmployeeCount();
    }

    function updateEmployeeCount() {
        const count = filteredEmployees.length;
        const countElement = document.getElementById('employeeCount');
        if (countElement) {
            countElement.textContent = count;
        }
    }

    // Add clear search functionality
    document.getElementById('clearSearch').addEventListener('click', function() {
        document.getElementById('employeeSearch').value = '';
        document.getElementById('allEmployees').checked = true;
        filteredEmployees = [...allEmployees];
        loadEmployees();
        updateEmployeeCount();
    });

    // Cash advance type selection
    document.querySelectorAll('.cash-advance-type-card').forEach(card => {
        card.addEventListener('click', function() {
            const restriction = this.dataset.restricted;
            
            if (restriction === 'regular' && selectedEmployee && selectedEmployee.type === 'regular') {
                alert('Payroll cash advances are only available for bonded employees. Regular employees cannot select this option.');
                return;
            }
            
            document.querySelectorAll('.cash-advance-type-card').forEach(c => c.classList.remove('selected'));
            this.classList.add('selected');
            selectedCashAdvanceType = {
                id: this.dataset.typeId,
                name: this.dataset.typeName
            };
            
            if (selectedEmployee) {
                document.getElementById('email').value = selectedEmployee.email;
            }
        });
    });

    // Navigation
    document.getElementById('nextBtn').addEventListener('click', function() {
        if (validateCurrentStep()) {
            if (currentStep < 4) {
                currentStep++;
                showStep(currentStep);
            }
        }
    });

    document.getElementById('prevBtn').addEventListener('click', function() {
        if (currentStep > 1) {
            currentStep--;
            showStep(currentStep);
        }
    });

    document.getElementById('submitBtn').addEventListener('click', function() {
        if (validateCurrentStep()) {
            showChequeVoucherModal();
        }
    });

    function validateCurrentStep() {
        switch(currentStep) {
            case 1:
                if (!selectedEmployee) {
                    alert('Please select an employee.');
                    return false;
                }
                break;
            case 2:
                if (!selectedCashAdvanceType) {
                    alert('Please select a cash advance type.');
                    return false;
                }
                const email = document.getElementById('email').value;
                if (!email || !email.includes('@')) {
                    alert('Please enter a valid email address.');
                    return false;
                }
                break;
            case 3:
                if (selectedCashAdvanceType && selectedCashAdvanceType.name.toLowerCase() === 'payroll') {
                    const form = document.getElementById('payrollForm');
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return false;
                    }
                    formData = {
                        amount: document.getElementById('payroll_amount').value,
                        purpose: document.getElementById('payroll_purpose').value,
                        date_granted: document.getElementById('payroll_date_granted').value,
                        type: 'payroll',
                        email: document.getElementById('email').value
                    };
                } else if (selectedCashAdvanceType && selectedCashAdvanceType.name.toLowerCase() === 'special purposes') {
                    const form = document.getElementById('specialForm');
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return false;
                    }
                    formData = {
                        amount: document.getElementById('special_amount').value,
                        event_name: document.getElementById('special_event_name').value,
                        event_date: document.getElementById('special_event_date').value,
                        type: 'special',
                        email: document.getElementById('email').value
                    };
                } else {
                const form = document.getElementById('cashAdvanceForm');
                if (!form.checkValidity()) {
                    form.reportValidity();
                    return false;
                }
                if (!validateDates()) {
                    alert('Please check the date fields. Departure must be before arrival.');
                    return false;
                }
                formData = {
                    amount: document.getElementById('amount').value,
                    email: document.getElementById('email').value,
                    purpose: document.getElementById('purpose').value,
                    departure_date: document.getElementById('departure_date').value,
                        arrival_date: document.getElementById('arrival_date').value,
                        type: 'default'
                };
                }
                break;
        }
        return true;
    }

    function showStep(step) {
        document.querySelectorAll('.step-content').forEach(content => {
            content.style.display = 'none';
        });
        if (step === 3) {
            if (selectedCashAdvanceType && selectedCashAdvanceType.name.toLowerCase() === 'payroll') {
                document.getElementById('step3-payroll').style.display = 'block';
            } else if (selectedCashAdvanceType && selectedCashAdvanceType.name.toLowerCase() === 'special purposes') {
                document.getElementById('step3-special').style.display = 'block';
            } else {
                document.getElementById('step3').style.display = 'block';
            }
        } else {
        const currentStepElement = document.getElementById(`step${step}`);
            if (currentStepElement) {
        currentStepElement.style.display = 'block';
        currentStepElement.classList.add('slide-up');
            }
        }
        const progress = (step / 4) * 100;
        document.getElementById('progressBar').style.width = progress + '%';
        document.querySelectorAll('.step-indicator').forEach((indicator, index) => {
            indicator.classList.remove('active', 'completed');
            if (index + 1 < step) {
                indicator.classList.add('completed');
            } else if (index + 1 === step) {
                indicator.classList.add('active');
            }
        });
        document.getElementById('prevBtn').style.display = step > 1 ? 'inline-block' : 'none';
        document.getElementById('nextBtn').style.display = step < 4 ? 'inline-block' : 'none';
        document.getElementById('submitBtn').style.display = step === 4 ? 'inline-block' : 'none';
        if (step === 4) {
            loadReview();
        }
    }

    function loadEmployees() {
        const employeeList = document.getElementById('employeeList');
        
        if (filteredEmployees.length === 0) {
            employeeList.innerHTML = `
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No employees found</h5>
                    <p class="text-muted">Try adjusting your search or filter criteria.</p>
                </div>
            `;
            return;
        }
        
        let html = `
            <div class="employee-list-container">
                <div class="employee-list-header">
                    <div class="employee-list-title">
                        <i class="fas fa-users"></i>
                        Available Employees
                    </div>
                    <div class="employee-count-badge">
                        <i class="fas fa-user-check"></i>
                        <span id="employeeCount">${filteredEmployees.length}</span> employees
                    </div>
                </div>
                <div class="row">
        `;
        
        filteredEmployees.forEach(employee => {
            const id = employee.type === 'regular' ? employee.user_id : employee.bonded_id;
            const isPending = employee.cash_advance_status === 'pending';
            const cardClass = isPending ? 'employee-card disabled' : 'employee-card';
            const statusClass = isPending ? 'pending' : 'available';
            const typeClass = employee.type === 'regular' ? 'regular' : 'bonded';
            const statusIcon = isPending ? 'fa-clock' : 'fa-check';
            const typeIcon = employee.type === 'regular' ? 'fa-user-tie' : 'fa-user-graduate';
            const avatarIcon = employee.type === 'regular' ? 'fa-user-tie' : 'fa-user-graduate';
            
            let categoryBadge = '';
            let categoryColor = '';
            let categoryIcon = '';
            
            switch(employee.employee_category) {
                case 'regular':
                    categoryBadge = `Regular Employee (${employee.cash_advance_count} cash advances)`;
                    categoryColor = 'primary';
                    categoryIcon = 'fa-user-tie';
                    break;
                case 'bonded':
                    categoryBadge = `Bonded Employee (${employee.cash_advance_count} cash advances)`;
                    categoryColor = 'info';
                    categoryIcon = 'fa-user-graduate';
                    break;
                case 'new_employee':
                    categoryBadge = 'New Employee';
                    categoryColor = 'warning';
                    categoryIcon = 'fa-user-plus';
                    break;
                default:
                    categoryBadge = 'Unknown';
                    categoryColor = 'secondary';
                    categoryIcon = 'fa-question';
            }
            
            html += `
                <div class="col-lg-6 col-md-12 mb-3">
                    <div class="card ${cardClass}" data-employee='${JSON.stringify(employee)}' ${isPending ? 'data-disabled="true"' : ''}>
                        <div class="card-body">
                            <div class="employee-header">
                                <div class="employee-avatar">
                                    <i class="fas ${avatarIcon}"></i>
                                </div>
                                <div class="employee-info">
                                    <div class="employee-name">${employee.displayName}</div>
                                    <div class="employee-id">ID: ${id}</div>
                                    <div class="badge-container">
                                        <span class="type-badge ${typeClass}">
                                            <i class="fas ${typeIcon}"></i>
                                            ${employee.type === 'regular' ? 'Regular' : 'Bonded'}
                                        </span>
                                        <span class="status-badge ${statusClass}">
                                            <i class="fas ${statusIcon}"></i>
                                            ${isPending ? 'Pending Liquidation' : 'Available'}
                                        </span>
                                        <span class="badge bg-${categoryColor}">
                                            <i class="fas ${categoryIcon}"></i>
                                            ${categoryBadge}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="employee-details">
                                <div class="detail-item">
                                    <i class="fas fa-envelope"></i>
                                    <span>${employee.email}</span>
                                </div>
                                ${employee.employee_category === 'regular' ? `
                                <div class="detail-item">
                                    <i class="fas fa-chart-bar"></i>
                                    <span>Total cash advances: ${employee.cash_advance_count}</span>
                                </div>
                                ` : ''}
                                ${employee.employee_category === 'new_employee' ? `
                                <div class="detail-item">
                                    <i class="fas fa-info-circle"></i>
                                    <span>No previous cash advance history</span>
                                </div>
                                ` : ''}
                            </div>
                            
                            <div class="status-message ${isPending ? 'warning' : 'success'}">
                                <i class="fas ${isPending ? 'fa-exclamation-triangle' : 'fa-check-circle'}"></i>
                                <span>${isPending ? 
                                    'Cannot grant new cash advance until liquidation is complete' : 
                                    'Ready for new cash advance'
                                }</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += `
                </div>
            </div>
        `;
        
        employeeList.innerHTML = html;
        
        document.querySelectorAll('.employee-card').forEach(card => {
            card.addEventListener('click', function() {
                if (this.dataset.disabled === 'true') {
                    alert('This employee has a pending cash advance that needs to be liquidated first. Please complete the liquidation before granting a new cash advance.');
                    return;
                }
                
                document.querySelectorAll('.employee-card').forEach(c => c.classList.remove('selected'));
                this.classList.add('selected');
                selectedEmployee = JSON.parse(this.dataset.employee);
                
                if (currentStep >= 2) {
                    document.getElementById('email').value = selectedEmployee.email;
                }
            });
        });
    }

    function loadReview() {
        const reviewContent = document.getElementById('reviewContent');
        const employeeType = selectedEmployee.type === 'regular' ? 'Regular Employee' : 'Bonded Employee';
        let detailsHtml = '';
        if (formData.type === 'payroll') {
            detailsHtml = `
                <p><strong>Type:</strong> Payroll</p>
                <p><strong>Amount:</strong> ₱${parseFloat(formData.amount).toLocaleString()}</p>
                <p><strong>Purpose:</strong> ${formData.purpose}</p>
                <p><strong>Date Granted:</strong> ${formData.date_granted}</p>
            `;
        } else if (formData.type === 'special') {
            detailsHtml = `
                <p><strong>Type:</strong> Special Purposes</p>
                <p><strong>Amount:</strong> ₱${parseFloat(formData.amount).toLocaleString()}</p>
                <p><strong>Name of Event:</strong> ${formData.event_name}</p>
                <p><strong>Event Date:</strong> ${formData.event_date}</p>
                <div class="alert alert-info mt-2">
                    <i class="fas fa-info-circle me-2"></i>
                    Liquidation counting starts the day after the cash advance is granted.
                </div>
            `;
        } else {
            detailsHtml = `
                <p><strong>Type:</strong> ${selectedCashAdvanceType.name}</p>
                <p><strong>Amount:</strong> ₱${parseFloat(formData.amount).toLocaleString()}</p>
                ${formData.purpose ? `<p><strong>Purpose:</strong> ${formData.purpose}</p>` : ''}
                ${formData.departure_date ? `<p><strong>Departure:</strong> ${formData.departure_date}</p>` : ''}
                ${formData.arrival_date ? `<p><strong>Arrival:</strong> ${formData.arrival_date}</p>` : ''}
            `;
        }
        reviewContent.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <div class="review-section">
                        <h6><i class="fas fa-user me-2"></i>Employee Information</h6>
                        <p><strong>Type:</strong> ${employeeType}</p>
                        <p><strong>Name:</strong> ${selectedEmployee.displayName}</p>
                        <p><strong>Email:</strong> ${selectedEmployee.email}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="review-section">
                        <h6><i class="fas fa-credit-card me-2"></i>Cash Advance Details</h6>
                        ${detailsHtml}
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Next Step:</strong> After clicking "Grant Cash Advance", you will be prompted to enter the cheque number and voucher number for this cash advance.
                    </div>
                </div>
            </div>
        `;
    }

    function showChequeVoucherModal() {
        // Clear form fields
        document.getElementById('cheque_number').value = '';
        document.getElementById('voucher_number').value = '';
        const modalElement = document.getElementById('chequeVoucherModal');
        const modal = new bootstrap.Modal(modalElement, {
            backdrop: false,
            keyboard: true,
            focus: true
        });
        modal.show();
        // Focus the first input
        modalElement.addEventListener('shown.bs.modal', function() {
            document.getElementById('cheque_number').focus();
        }, { once: true });
        document.getElementById('confirmGrantBtn').onclick = function(e) {
            e.preventDefault();
            e.stopPropagation();
            const chequeNumber = document.getElementById('cheque_number').value.trim();
            const voucherNumber = document.getElementById('voucher_number').value.trim();
            if (!chequeNumber || !voucherNumber) {
                alert('Please enter both cheque number and voucher number.');
                return;
            }
            submitForm(chequeNumber, voucherNumber);
            modal.hide();
        };
    }

    function submitForm(chequeNumber, voucherNumber) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';
        const fields = {
            'step': 'submit',
            'employee_type': selectedEmployee.type,
            'employee_id': selectedEmployee.type === 'regular' ? selectedEmployee.user_id : selectedEmployee.bonded_id,
            'cash_advance_type': selectedCashAdvanceType.name,
            'cheque_number': chequeNumber,
            'voucher_number': voucherNumber,
            'email': formData.email
        };
        if (formData.type === 'payroll') {
            fields['payroll_amount'] = formData.amount;
            fields['payroll_purpose'] = formData.purpose;
            fields['date_granted'] = formData.date_granted;
        } else if (formData.type === 'special') {
            fields['special_amount'] = formData.amount;
            fields['event_name'] = formData.event_name;
            fields['event_date'] = formData.event_date;
        } else {
            fields['amount'] = formData.amount;
            fields['purpose'] = formData.purpose;
            fields['departure_date'] = formData.departure_date;
            fields['arrival_date'] = formData.arrival_date;
        }
        Object.keys(fields).forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
    }

    showStep(1);

    document.getElementById('departure_date').addEventListener('change', function() {
        validateDates();
    });

    document.getElementById('arrival_date').addEventListener('change', function() {
        validateDates();
    });

    function validateDates() {
        const departureDate = document.getElementById('departure_date').value;
        const arrivalDate = document.getElementById('arrival_date').value;

        document.getElementById('departure_date').classList.remove('is-invalid');
        document.getElementById('arrival_date').classList.remove('is-invalid');

        let hasError = false;

        if (departureDate && arrivalDate && departureDate >= arrivalDate) {
            document.getElementById('departure_date').classList.add('is-invalid');
            document.getElementById('arrival_date').classList.add('is-invalid');
            hasError = true;
        }

        return !hasError;
    }
    </script>
</body>
</html>