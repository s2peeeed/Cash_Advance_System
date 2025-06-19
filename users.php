<?php
// session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';

$success = $error = "";
$edit_employee = null;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employee'])) {
        $user_id = trim($_POST['user_id']);
        $user_name = trim($_POST['user_name']);
        $email = trim($_POST['email']);

        if (!empty($user_id) && !empty($user_name) && !empty($email)) {
            try {
                // Check for duplicate employee ID
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE user_id = ?");
                $stmt->execute([$user_id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Employee ID already exists. Please use a different ID.";
                } else {
                    // Check for duplicate email
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE email = ?");
                    $stmt->execute([$email]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Email address already exists. Please use a different email.";
                    } else {
                        // Validate email format
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $error = "Please enter a valid email address.";
                        } else {
                            // Insert the new employee
                            $stmt = $pdo->prepare("INSERT INTO employees (user_id, user_name, email, status, date_added) VALUES (?, ?, ?, 'active', CURDATE())");
                            if ($stmt->execute([$user_id, $user_name, $email])) {
                                $success = "Employee has been added successfully!";
                            } else {
                                $error = "Failed to add employee.";
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    } elseif (isset($_POST['update_employee'])) {
        $user_id = trim($_POST['user_id']);
        $user_name = trim($_POST['user_name']);
        $email = trim($_POST['email']);
        $original_user_id = $_POST['original_user_id'];

        if (!empty($user_id) && !empty($user_name) && !empty($email)) {
            try {
                // Check for duplicate employee ID (excluding current employee)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE user_id = ? AND user_id != ?");
                $stmt->execute([$user_id, $original_user_id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Employee ID already exists. Please use a different ID.";
                } else {
                    // Check for duplicate email (excluding current employee)
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employees WHERE email = ? AND user_id != ?");
                    $stmt->execute([$email, $original_user_id]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Email address already exists. Please use a different email.";
                    } else {
                        // Validate email format
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $error = "Please enter a valid email address.";
                        } else {
                            // Update the employee
                            $stmt = $pdo->prepare("UPDATE employees SET user_id = ?, user_name = ?, email = ? WHERE user_id = ?");
                            if ($stmt->execute([$user_id, $user_name, $email, $original_user_id])) {
                                $success = "Employee has been updated successfully!";
                                $edit_employee = null; // Clear edit mode
                            } else {
                                $error = "Failed to update employee.";
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Please fill in all required fields.";
        }
    } elseif (isset($_POST['toggle_status'])) {
        $employee_id = $_POST['employee_id'];
        $current_status = $_POST['current_status'];
        $employee_name = $_POST['employee_name'];
        
        $new_status = ($current_status === 'active') ? 'inactive' : 'active';
        
        try {
            $stmt = $pdo->prepare("UPDATE employees SET status = ? WHERE user_id = ?");
            if ($stmt->execute([$new_status, $employee_id])) {
                $success = "Employee " . htmlspecialchars($employee_name) . " status has been updated to " . $new_status . ".";
            } else {
                $error = "Failed to update employee status.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_employee'])) {
        $employee_id = $_POST['employee_id'];
        $employee_name = $_POST['employee_name'];
        
        try {
            // Check if employee has any cash advances
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM granted_cash_advances WHERE name = (SELECT user_name FROM employees WHERE user_id = ?)");
            $stmt->execute([$employee_id]);
            $cash_advance_count = $stmt->fetchColumn();
            
            if ($cash_advance_count > 0) {
                $error = "Cannot delete employee " . htmlspecialchars($employee_name) . ". They have " . $cash_advance_count . " cash advance record(s). Please handle the cash advances first.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM employees WHERE user_id = ?");
                if ($stmt->execute([$employee_id])) {
                    $success = "Employee " . htmlspecialchars($employee_name) . " has been deleted successfully!";
                } else {
                    $error = "Failed to delete employee.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } elseif (isset($_POST['edit_employee'])) {
        $employee_id = $_POST['employee_id'];
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM employees WHERE user_id = ?");
            $stmt->execute([$employee_id]);
            $edit_employee = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all employees
$employees = [];
try {
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY date_added DESC");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Management - LGU Liquidation System</title>
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
            background: #f5f7fa;
            min-height: 100vh;
            color: #1f2937;
        }

        .container-fluid {
            padding: 20px;
        }

        .main-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow);
            border: none;
            overflow: hidden;
        }

        .card-header {
            background: white;
            border-bottom: 1px solid var(--border-color);
            padding: 1.25rem 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Enhanced Form Elements */
        .form-control {
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.65rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        .form-label {
            font-weight: 500;
            color: #4b5563;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }

        /* Enhanced Buttons */
        .btn {
            border-radius: 8px;
            padding: 0.65rem 1.25rem;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
            transform: translateY(-1px);
        }

        .btn-success {
            background-color: var(--success);
        }

        .btn-success:hover {
            background-color: #0d9f6e;
            transform: translateY(-1px);
        }

        .btn-warning {
            background-color: var(--warning);
        }

        .btn-warning:hover {
            background-color: #e59409;
            transform: translateY(-1px);
        }

        .btn-danger {
            background-color: var(--danger);
        }

        .btn-danger:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
        }

        .btn-outline-primary {
            border: 1px solid var(--primary);
            color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            color: white;
        }

        /* Enhanced Alerts */
        .alert {
            border-radius: 8px;
            border: none;
            padding: 0.85rem 1.25rem;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .alert-success {
            background-color: rgba(16, 185, 129, 0.1);
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.1);
            color: #991b1b;
            border-left: 4px solid var(--danger);
        }

        /* Enhanced Table */
        .table {
            border-radius: 8px;
            overflow: hidden;
            font-size: 0.9rem;
        }

        .table thead th {
            background-color: #f8fafc;
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
            color: #374151;
            padding: 0.85rem 1rem;
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        .table tbody td {
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--border-color);
            vertical-align: middle;
        }

        .table tbody tr:hover {
            background-color: rgba(59, 130, 246, 0.05);
        }

        /* Enhanced Badges */
        .badge {
            padding: 0.4rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
        }

        .badge-success {
            background-color: var(--success);
        }

        .badge-warning {
            background-color: var(--warning);
        }

        /* Status Filter */
        .status-filter {
            margin-bottom: 1.5rem;
        }

        .status-filter .btn {
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
            border-radius: 20px;
            padding: 0.4rem 1rem;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .status-filter .btn.active {
            background-color: var(--primary);
            color: white;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .action-buttons .btn {
            padding: 0.4rem 0.75rem;
            font-size: 0.8rem;
            border-radius: 6px;
        }

        /* Employee Row States */
        .employee-row.inactive {
            opacity: 0.8;
        }

        /* Modal Styling */
        .modal {
            z-index: 1055;
        }

        .modal-backdrop {
            display: none !important;
        }

        .modal-dialog {
            z-index: 1056;
        }

        /* Ensure form fields are accessible */
        .modal .form-control {
            position: relative;
            z-index: 1057;
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            cursor: text;
        }

        .modal .form-control:focus {
            background-color: #ffffff;
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        .modal .form-label {
            position: relative;
            z-index: 1057;
            color: #374151;
            cursor: default;
        }

        /* Ensure buttons are clickable */
        .modal .btn {
            position: relative;
            z-index: 1057;
            cursor: pointer !important;
            pointer-events: auto !important;
        }

        .modal .btn:hover {
            cursor: pointer !important;
        }

        /* Modal content styling */
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

        .modal .btn-primary, .modal .btn-success {
            background: linear-gradient(90deg, #10b981 0%, #2563eb 100%);
            border: none;
            color: #fff;
        }

        .modal .btn-primary:hover, .modal .btn-success:hover {
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

        /* Page Header */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .page-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #111827;
            margin: 0;
        }

        /* Animation Classes */
        .fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container-fluid {
                padding: 15px;
            }
            
            .card-body {
                padding: 1.25rem;
            }
            
            .action-buttons {
                flex-direction: column;
                gap: 0.3rem;
            }
            
            .action-buttons .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="main-card animate__animated animate__fadeIn">
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success fade-in mb-4">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger fade-in mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Page Header with Add Button -->
                        <div class="page-header">
                            <h1 class="page-title">
                                <i class="fas fa-users me-2"></i>Employee Management
                            </h1>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                                <i class="fas fa-plus me-2"></i>Add Employee
                            </button>
                        </div>

                        <!-- Status Filter -->
                        <div class="status-filter">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary filter-btn active" data-status="all">
                                    <i class="fas fa-list me-1"></i>All
                                </button>
                                <button type="button" class="btn btn-outline-primary filter-btn" data-status="active">
                                    <i class="fas fa-check-circle me-1"></i>Active
                                </button>
                                <button type="button" class="btn btn-outline-primary filter-btn" data-status="inactive">
                                    <i class="fas fa-pause-circle me-1"></i>Inactive
                                </button>
                            </div>
                        </div>

                        <!-- Employees Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Employee ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Date Added</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($employees as $employee): ?>
                                    <tr class="employee-row <?php echo $employee['status'] === 'inactive' ? 'inactive' : ''; ?>" data-status="<?php echo $employee['status']; ?>">
                                        <td>
                                            <strong><?php echo htmlspecialchars($employee['user_id']); ?></strong>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($employee['user_name']); ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($employee['email']); ?>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($employee['date_added'])); ?>
                                        </td>
                                        <td>
                                            <?php if ($employee['status'] === 'active'): ?>
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check-circle me-1"></i>Active
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-pause-circle me-1"></i>Inactive
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employee['user_id']); ?>">
                                                    <button type="submit" name="edit_employee" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employee['user_id']); ?>">
                                                    <input type="hidden" name="current_status" value="<?php echo htmlspecialchars($employee['status']); ?>">
                                                    <input type="hidden" name="employee_name" value="<?php echo htmlspecialchars($employee['user_name']); ?>">
                                                    <button type="submit" name="toggle_status" class="btn btn-sm btn-warning" title="<?php echo $employee['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="fas fa-toggle-<?php echo $employee['status'] === 'active' ? 'on' : 'off'; ?>"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('<?php echo htmlspecialchars($employee['user_name']); ?>')">
                                                    <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employee['user_id']); ?>">
                                                    <input type="hidden" name="employee_name" value="<?php echo htmlspecialchars($employee['user_name']); ?>">
                                                    <button type="submit" name="delete_employee" class="btn btn-sm btn-danger" title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Back to Dashboard Button -->
                        <div class="mt-4">
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addEmployeeModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Add New Employee
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="user_id" class="form-label">Employee ID</label>
                            <input type="text" class="form-control" id="user_id" name="user_id" required>
                        </div>
                        <div class="mb-3">
                            <label for="user_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="user_name" name="user_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="add_employee" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Employee Modal -->
    <?php if ($edit_employee): ?>
    <div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel">
                        <i class="fas fa-user-edit me-2"></i>Edit Employee
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="original_user_id" value="<?php echo htmlspecialchars($edit_employee['user_id']); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_user_id" class="form-label">Employee ID</label>
                            <input type="text" class="form-control" id="edit_user_id" name="user_id" value="<?php echo htmlspecialchars($edit_employee['user_id']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_user_name" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="edit_user_name" name="user_name" value="<?php echo htmlspecialchars($edit_employee['user_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="edit_email" name="email" value="<?php echo htmlspecialchars($edit_employee['email']); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="update_employee" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Status filter functionality
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Update active button
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                
                const status = this.dataset.status;
                const rows = document.querySelectorAll('.employee-row');
                
                rows.forEach(row => {
                    if (status === 'all' || row.dataset.status === status) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Delete confirmation
        function confirmDelete(employeeName) {
            return confirm(`Are you sure you want to delete employee "${employeeName}"? This action cannot be undone.`);
        }

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease-out';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Form validation for add employee
        const addForm = document.querySelector('#addEmployeeModal form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                const requiredFields = addForm.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });
                
                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                }
            });
        }

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

            // Show edit modal if edit_employee is set
            <?php if ($edit_employee): ?>
            const editModal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
            editModal.show();
            <?php endif; ?>

            // Clear form when add modal is closed
            const addModal = document.getElementById('addEmployeeModal');
            if (addModal) {
                addModal.addEventListener('hidden.bs.modal', function() {
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
                addModal.addEventListener('shown.bs.modal', function() {
                    const firstInput = this.querySelector('input[type="text"]');
                    if (firstInput) {
                        setTimeout(() => {
                            firstInput.focus();
                        }, 100);
                    }
                });
            }

            // Handle edit modal
            const editModalElement = document.getElementById('editEmployeeModal');
            if (editModalElement) {
                editModalElement.addEventListener('shown.bs.modal', function() {
                    const firstInput = this.querySelector('input[type="text"]');
                    if (firstInput) {
                        setTimeout(() => {
                            firstInput.focus();
                        }, 100);
                    }
                });
            }
        });

        // Add success message handling
        <?php if ($success): ?>
        document.addEventListener('DOMContentLoaded', function() {
            // Close any open modals after successful submission
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>