<?php
// session_start();
// Optional: Only allow admin access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}

require_once 'config/database.php';

$success = $error = "";
$edit_employee = null;

// --- Fetch positions and stations ---
$positions = [];
try {
    $stmt = $pdo->query("SELECT id, position_name FROM positions ORDER BY position_name ASC");
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error fetching positions: " . $e->getMessage();
}

$stations = [];
try {
    $stmt = $pdo->query("SELECT id, station_name FROM stations ORDER BY station_name ASC");
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error fetching stations: " . $e->getMessage();
}

// --- Fetch all employees for search (regular and bonded) ---
$all_employees = [];
try {
    // Get regular employees (not bonded)
    $stmt = $pdo->query("
        SELECT e.user_id as id, e.user_name as full_name, e.email, 'regular' as type, NULL as bonded_ris_number, NULL as bonded_date_of_bond, NULL as bonded_due_date, 'no_bond' as bonded_status
        FROM employees e
        LEFT JOIN bonded_employees be ON e.email = be.email
        WHERE be.bonded_id IS NULL AND e.status = 'active'
        ORDER BY e.user_name ASC
    ");
    $regular_employees_not_bonded = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get existing bonded employees
    $stmt = $pdo->query("
        SELECT bonded_id as id, full_name, email, 'bonded' as type, ris_number as bonded_ris_number, date_of_bond as bonded_date_of_bond, due_date as bonded_due_date, 'bonded' as bonded_status
        FROM bonded_employees
        ORDER BY full_name ASC
    ");
    $existing_bonded_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $all_employees = array_merge($regular_employees_not_bonded, $existing_bonded_employees);

} catch (PDOException $e) {
    $error = "Database error fetching all employees: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employee'])) {
        $bonded_id = trim($_POST['bonded_id']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $ris_number = trim($_POST['ris_number']);
        $approximate_amount = floatval($_POST['approximate_amount']);
        $total_amount = floatval($_POST['total_amount']);
        $position_id = $_POST['position_id'];
        $station_id = $_POST['station_id'];
        $date_of_bond = $_POST['date_of_bond'];
        $due_date = $_POST['due_date'];

        if ($bonded_id && $full_name && $email && $approximate_amount > 0 && $total_amount > 0 && $position_id && $station_id && $date_of_bond && $due_date) {
            try {
                // Get position name and station name
                $stmt = $pdo->prepare("SELECT position_name FROM positions WHERE id = ?");
                $stmt->execute([$position_id]);
                $position_name = $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT station_name FROM stations WHERE id = ?");
                $stmt->execute([$station_id]);
                $station_name = $stmt->fetchColumn();

                if (!$position_name || !$station_name) {
                    $error = "Selected position or station is invalid.";
                } else {
                    // Check for duplicate bonded ID
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bonded_employees WHERE bonded_id = ?");
                    $stmt->execute([$bonded_id]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Bonded Employee ID already exists. Please use a different ID.";
                    } else {
                        // Check for duplicate email
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bonded_employees WHERE email = ?");
                        $stmt->execute([$email]);
                        if ($stmt->fetchColumn() > 0) {
                            $error = "Email address already exists for a bonded employee.";
                        } else {
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $error = "Please enter a valid email address.";
                            } else {
                                $stmt = $pdo->prepare("INSERT INTO bonded_employees (bonded_id, full_name, email, ris_number, approximate_amount, total_amount, position, station, date_of_bond, due_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                                $stmt->execute([$bonded_id, $full_name, $email, $ris_number, $approximate_amount, $total_amount, $position_name, $station_name, $date_of_bond, $due_date]);
                                $success = "Bonded employee added successfully.";
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
        $bonded_id = trim($_POST['bonded_id']);
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $ris_number = trim($_POST['ris_number']);
        $approximate_amount = floatval($_POST['approximate_amount']);
        $total_amount = floatval($_POST['total_amount']);
        $position_id = $_POST['position_id'];
        $station_id = $_POST['station_id'];
        $date_of_bond = $_POST['date_of_bond'];
        $due_date = $_POST['due_date'];
        $original_bonded_id = $_POST['original_bonded_id'];

        if ($bonded_id && $full_name && $email && $approximate_amount > 0 && $total_amount > 0 && $position_id && $station_id && $date_of_bond && $due_date) {
            try {
                $stmt = $pdo->prepare("SELECT position_name FROM positions WHERE id = ?");
                $stmt->execute([$position_id]);
                $position_name = $stmt->fetchColumn();

                $stmt = $pdo->prepare("SELECT station_name FROM stations WHERE id = ?");
                $stmt->execute([$station_id]);
                $station_name = $stmt->fetchColumn();

                if (!$position_name || !$station_name) {
                    $error = "Selected position or station is invalid.";
                } else {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bonded_employees WHERE bonded_id = ? AND bonded_id != ?");
                    $stmt->execute([$bonded_id, $original_bonded_id]);
                    if ($stmt->fetchColumn() > 0) {
                        $error = "Bonded Employee ID already exists.";
                    } else {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bonded_employees WHERE email = ? AND bonded_id != ?");
                        $stmt->execute([$email, $original_bonded_id]);
                        if ($stmt->fetchColumn() > 0) {
                            $error = "Email address already exists.";
                        } else {
                            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $error = "Please enter a valid email address.";
                            } else {
                                $stmt = $pdo->prepare("UPDATE bonded_employees SET bonded_id = ?, full_name = ?, email = ?, ris_number = ?, approximate_amount = ?, total_amount = ?, position = ?, station = ?, date_of_bond = ?, due_date = ? WHERE bonded_id = ?");
                                $stmt->execute([$bonded_id, $full_name, $email, $ris_number, $approximate_amount, $total_amount, $position_name, $station_name, $date_of_bond, $due_date, $original_bonded_id]);
                                $success = "Bonded employee updated successfully.";
                                $edit_employee = null;
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
    } elseif (isset($_POST['delete_employee'])) {
        $bonded_id = $_POST['bonded_id'];
        $employee_name = $_POST['employee_name'];
        
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM granted_cash_advances WHERE name = (SELECT full_name FROM bonded_employees WHERE bonded_id = ?)");
            $stmt->execute([$bonded_id]);
            $cash_advance_count = $stmt->fetchColumn();
            
            if ($cash_advance_count > 0) {
                $error = "Cannot delete bonded employee " . htmlspecialchars($employee_name) . ". They have " . $cash_advance_count . " cash advance record(s).";
            } else {
                $stmt = $pdo->prepare("DELETE FROM bonded_employees WHERE bonded_id = ?");
                if ($stmt->execute([$bonded_id])) {
                    $success = "Bonded employee " . htmlspecialchars($employee_name) . " has been deleted successfully!";
                } else {
                    $error = "Failed to delete bonded employee.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } elseif (isset($_POST['edit_employee'])) {
        $bonded_id = $_POST['bonded_id'];
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM bonded_employees WHERE bonded_id = ?");
            $stmt->execute([$bonded_id]);
            $edit_employee = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all bonded employees
$bonded_employees = [];
try {
    $stmt = $pdo->query("
        SELECT be.*, p.position_name, s.station_name 
        FROM bonded_employees be
        LEFT JOIN positions p ON be.position = p.position_name
        LEFT JOIN stations s ON be.station = s.station_name
        ORDER BY be.date_of_bond DESC
    ");
    $bonded_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bonded Employee Management - LGU Liquidation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background: #f5f7fa;
            font-family: 'Inter', sans-serif;
        }
        .main-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: none;
        }
        .search-results {
            position: absolute;
            z-index: 9999;
            width: 100%;
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            background: white;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .search-container {
            position: relative;
        }
        .bond-status {
            padding: 0.4rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.75rem;
            text-align: center;
        }
        .bond-active {
            background-color: #10b981;
            color: white;
        }
        .bond-overdue {
            background-color: #ef4444;
            color: white;
        }
    /* Existing styles remain unchanged */
    body {
        background: #f5f7fa;
        font-family: 'Inter', sans-serif;
    }
    .main-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        border: none;
    }
    .search-results {
        position: absolute;
        z-index: 10000; /* Increased to ensure it appears above modal */
        width: 100%;
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        background: white;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    .search-container {
        position: relative;
    }
    .bond-status {
        padding: 0.4rem 0.75rem;
        border-radius: 20px;
        font-weight: 500;
        font-size: 0.75rem;
        text-align: center;
    }
    .bond-active {
        background-color: #10b981;
        color: white;
    }
    .bond-overdue {
        background-color: #ef4444;
        color: white;
    }

    /* Add this to prevent dimmed background */
    .modal-backdrop {
        display: none !important; /* Completely remove the backdrop */
    }
    .modal {
        background: transparent !important; /* Ensure modal doesn't add background overlay */
        z-index: 1055; /* Ensure modal is above other content */
    }
    .modal-content {
        z-index: 1060; /* Ensure modal content is clickable */
    }
    .modal-open .modal {
        pointer-events: auto !important; /* Ensure modal is interactive */
    }
    .modal-open .modal-dialog {
        pointer-events: auto !important; /* Ensure modal dialog is interactive */
    }
    input, select, button {
        pointer-events: auto !important; /* Ensure form elements are clickable */
    }
</style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="main-card">
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success mb-4">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Page Header -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h1 class="h3">
                                <i class="fas fa-user-shield me-2"></i>Bonded Employee Management
                            </h1>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBondedEmployeeModal">
                                <i class="fas fa-plus me-2"></i>Add Bonded Employee
                            </button>
                        </div>

                        <!-- Bonded Employees Table -->
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered text-center">
                                <thead>
                                    <tr>
                                        <th class="text-center">Name and Position of Bonded Public Officers</th>
                                        <th class="text-center">Email</th>
                                        <th class="text-center">Station</th>
                                        <th class="text-center">Risk No.</th>
                                        <th class="text-center">Approved Amount of Accountability</th>
                                        <th class="text-center">Approved Amount of Bond</th>
                                        <th class="text-center" colspan="2">Effective Date</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th></th>
                                        <th>FROM</th>
                                        <th>TO</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($bonded_employees as $employee): ?>
                                    <tr>
                                        <td class="text-center"><strong><?php echo htmlspecialchars($employee['full_name']); ?></strong><br>
                                            <span style="font-size: 0.95em; color: #555;">
                                                (<?php echo htmlspecialchars($employee['position_name'] ?? 'Position not set'); ?>)<br>
                                                BOND COVERAGE: 1 YR(S)
                                            </span>
                                        </td>
                                        <td class="text-center"><?php echo htmlspecialchars($employee['email']); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($employee['station_name'] ?? 'N/A'); ?></td>
                                        <td class="text-center"><?php echo htmlspecialchars($employee['ris_number']); ?></td>
                                        <td class="text-center">₱<?php echo number_format($employee['approximate_amount'] ?? 0, 2); ?></td>
                                        <td class="text-center">₱<?php echo number_format($employee['total_amount'] ?? 0, 2); ?></td>
                                        <td class="text-center"><?php echo date('m/d/Y', strtotime($employee['date_of_bond'])); ?></td>
                                        <td class="text-center"><?php echo date('m/d/Y', strtotime($employee['due_date'])); ?></td>
                                        <td class="text-center">
                                            <div class="d-flex gap-1 justify-content-center">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="bonded_id" value="<?php echo htmlspecialchars($employee['bonded_id']); ?>">
                                                    <button type="submit" name="edit_employee" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('<?php echo htmlspecialchars($employee['full_name']); ?>')">
                                                    <input type="hidden" name="bonded_id" value="<?php echo htmlspecialchars($employee['bonded_id']); ?>">
                                                    <input type="hidden" name="employee_name" value="<?php echo htmlspecialchars($employee['full_name']); ?>">
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

    <!-- Add Bonded Employee Modal -->
    <div class="modal fade" id="addBondedEmployeeModal" tabindex="-1" aria-labelledby="addBondedEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addBondedEmployeeModalLabel">
                        <i class="fas fa-user-plus me-2"></i>Add New Bonded Employee
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <!-- Search for Employee -->
                        <div class="mb-3 search-container">
                            <label for="employeeSearchInput" class="form-label">Search Employee to Bond</label>
                            <input type="text" class="form-control" id="employeeSearchInput" placeholder="Search by ID, name, or email...">
                            <div id="employeeSearchResults" class="list-group search-results" style="display:none;"></div>
                        </div>

                        <!-- Employee Details Form -->
                        <div id="employeeDetailsForm">
                            <input type="hidden" name="original_employee_type" id="original_employee_type">
                            <input type="hidden" name="original_employee_id_for_search" id="original_employee_id_for_search">

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="bonded_id" class="form-label">Employee ID</label>
                                    <input type="text" class="form-control" id="bonded_id" name="bonded_id" required readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" required readonly>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label for="email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" required readonly>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="position_id" class="form-label">Position</label>
                                    <select class="form-select" id="position_id" name="position_id" required>
                                        <option value="">Select Position</option>
                                        <?php foreach ($positions as $pos): ?>
                                            <option value="<?php echo htmlspecialchars($pos['id']); ?>"><?php echo htmlspecialchars($pos['position_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="station_id" class="form-label">Station</label>
                                    <select class="form-select" id="station_id" name="station_id" required>
                                        <option value="">Select Station</option>
                                        <?php foreach ($stations as $stat): ?>
                                            <option value="<?php echo htmlspecialchars($stat['id']); ?>"><?php echo htmlspecialchars($stat['station_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ris_number" class="form-label">RISK Number</label>
                                    <input type="text" class="form-control" id="ris_number" name="ris_number">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="approximate_amount" class="form-label">Approved Amount of Accountability</label>
                                    <input type="number" class="form-control" id="approximate_amount" name="approximate_amount" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="total_amount" class="form-label">Approved Amount of Bond</label>
                                    <input type="number" class="form-control" id="total_amount" name="total_amount" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="date_of_bond" class="form-label">Expected Date of Bond (From)</label>
                                    <input type="date" class="form-control" id="date_of_bond" name="date_of_bond" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="due_date" class="form-label">Expected Date of Bond (To)</label>
                                    <input type="date" class="form-control" id="due_date" name="due_date" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="add_employee" class="btn btn-primary" id="saveBondedEmployeeBtn">
                            <i class="fas fa-save me-2"></i>Save Bonded Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Bonded Employee Modal -->
    <?php if ($edit_employee): ?>
    <div class="modal fade" id="editBondedEmployeeModal" tabindex="-1" aria-labelledby="editBondedEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editBondedEmployeeModalLabel">
                        <i class="fas fa-user-edit me-2"></i>Edit Bonded Employee
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="original_bonded_id" value="<?php echo htmlspecialchars($edit_employee['bonded_id']); ?>">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_bonded_id" class="form-label">Bonded Employee ID</label>
                                <input type="text" class="form-control" id="edit_bonded_id" name="bonded_id" value="<?php echo htmlspecialchars($edit_employee['bonded_id']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_full_name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="edit_full_name" name="full_name" value="<?php echo htmlspecialchars($edit_employee['full_name']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="edit_email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="edit_email" name="email" value="<?php echo htmlspecialchars($edit_employee['email']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_position_id" class="form-label">Position</label>
                                <select class="form-select" id="edit_position_id" name="position_id" required>
                                    <option value="">Select Position</option>
                                    <?php foreach ($positions as $pos): ?>
                                        <option value="<?php echo htmlspecialchars($pos['id']); ?>" <?php echo (isset($edit_employee['position']) && $edit_employee['position'] === $pos['position_name']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($pos['position_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_station_id" class="form-label">Station</label>
                                <select class="form-select" id="edit_station_id" name="station_id" required>
                                    <option value="">Select Station</option>
                                    <?php foreach ($stations as $stat): ?>
                                        <option value="<?php echo htmlspecialchars($stat['id']); ?>" <?php echo (isset($edit_employee['station']) && $edit_employee['station'] === $stat['station_name']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($stat['station_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_ris_number" class="form-label">RISK Number</label>
                                <input type="text" class="form-control" id="edit_ris_number" name="ris_number" value="<?php echo htmlspecialchars($edit_employee['ris_number']); ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_approximate_amount" class="form-label">Approved Amount of Accountability</label>
                                <input type="number" class="form-control" id="edit_approximate_amount" name="approximate_amount" step="0.01" min="0" value="<?php echo htmlspecialchars($edit_employee['approximate_amount']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_total_amount" class="form-label">Approved Amount of Bond</label>
                                <input type="number" class="form-control" id="edit_total_amount" name="total_amount" step="0.01" min="0" value="<?php echo htmlspecialchars($edit_employee['total_amount']); ?>" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="edit_date_of_bond" class="form-label">Expected Date of Bond (From)</label>
                                <input type="date" class="form-control" id="edit_date_of_bond" name="date_of_bond" value="<?php echo htmlspecialchars($edit_employee['date_of_bond']); ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="edit_due_date" class="form-label">Expected Date of Bond (To)</label>
                                <input type="date" class="form-control" id="edit_due_date" name="due_date" value="<?php echo htmlspecialchars($edit_employee['due_date']); ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="update_employee" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Bonded Employee
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // PHP data for JavaScript
    const allEmployees = <?php echo json_encode($all_employees); ?>;

    // Initialize Bootstrap modals
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize add modal
        const addModalElement = document.getElementById('addBondedEmployeeModal');
        if (addModalElement) {
            const addModal = new bootstrap.Modal(addModalElement, {
                backdrop: false, // No dimmed backdrop
                keyboard: true,
                focus: true
            });

            // Clear form when add modal is closed
            addModalElement.addEventListener('hidden.bs.modal', function() {
                const form = this.querySelector('form');
                if (form) {
                    form.reset();
                    form.querySelectorAll('.is-invalid').forEach(field => {
                        field.classList.remove('is-invalid');
                    });
                }
                document.getElementById('employeeSearchInput').value = '';
                document.getElementById('employeeSearchResults').style.display = 'none';
                document.getElementById('bonded_id').value = '';
                document.getElementById('full_name').value = '';
                document.getElementById('email').value = '';
                document.getElementById('saveBondedEmployeeBtn').setAttribute('disabled', 'true');
            });

            // Focus on search input when modal opens
            addModalElement.addEventListener('shown.bs.modal', function() {
                const searchInput = document.getElementById('employeeSearchInput');
                if (searchInput) {
                    setTimeout(() => {
                        searchInput.focus();
                    }, 100);
                }
                document.getElementById('saveBondedEmployeeBtn').setAttribute('disabled', 'true');
            });
        }

        // Initialize edit modal
        const editModalElement = document.getElementById('editBondedEmployeeModal');
        if (editModalElement) {
            const editModal = new bootstrap.Modal(editModalElement, {
                backdrop: false, // No dimmed backdrop
                keyboard: true,
                focus: true
            });

            // Show edit modal if edit_employee is set
            <?php if ($edit_employee): ?>
                editModal.show();
            <?php endif; ?>

            // Focus on first input when edit modal opens
            editModalElement.addEventListener('shown.bs.modal', function() {
                const firstInput = this.querySelector('input[type="text"]');
                if (firstInput) {
                    setTimeout(() => {
                        firstInput.focus();
                    }, 100);
                }
            });
        }

        // Close modals after success
        <?php if ($success): ?>
            const modals = document.querySelectorAll('.modal.show');
            modals.forEach(modal => {
                const bsModal = bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            });
        <?php endif; ?>
    });

    // Employee Search and Auto-fill Logic
    const employeeSearchInput = document.getElementById('employeeSearchInput');
    const employeeSearchResults = document.getElementById('employeeSearchResults');
    const bondedIdField = document.getElementById('bonded_id');
    const fullNameField = document.getElementById('full_name');
    const emailField = document.getElementById('email');
    const saveBondedEmployeeBtn = document.getElementById('saveBondedEmployeeBtn');

    if (employeeSearchInput) {
        employeeSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            employeeSearchResults.innerHTML = '';

            if (searchTerm.length < 2) {
                employeeSearchResults.style.display = 'none';
                return;
            }

            const filteredEmployees = allEmployees.filter(emp => {
                const idMatch = emp.id && emp.id.toString().toLowerCase().includes(searchTerm);
                const nameMatch = emp.full_name && emp.full_name.toLowerCase().includes(searchTerm);
                const emailMatch = emp.email && emp.email.toLowerCase().includes(searchTerm);
                return idMatch || nameMatch || emailMatch;
            });

            if (filteredEmployees.length > 0) {
                filteredEmployees.forEach(emp => {
                    const listItem = document.createElement('a');
                    listItem.href = '#';
                    listItem.classList.add('list-group-item', 'list-group-item-action');
                    listItem.innerHTML = `<strong>${emp.full_name}</strong> (${emp.id}) - ${emp.email}`;
                    
                    if (emp.bonded_status === 'bonded') {
                        listItem.classList.add('disabled');
                        listItem.setAttribute('aria-disabled', 'true');
                        listItem.innerHTML += ` <span class="badge bg-danger ms-2">Already Bonded</span>`;
                    } else {
                        listItem.addEventListener('click', (e) => {
                            e.preventDefault();
                            selectEmployee(emp);
                            employeeSearchResults.style.display = 'none';
                            employeeSearchInput.value = emp.full_name;
                        });
                    }
                    employeeSearchResults.appendChild(listItem);
                });
                employeeSearchResults.style.display = 'block';
            } else {
                employeeSearchResults.style.display = 'none';
            }
        });
    }

    function selectEmployee(employee) {
        bondedIdField.value = employee.id;
        fullNameField.value = employee.full_name;
        emailField.value = employee.email;
        
        document.getElementById('original_employee_type').value = employee.type;
        document.getElementById('original_employee_id_for_search').value = employee.id;

        saveBondedEmployeeBtn.removeAttribute('disabled');
        employeeSearchResults.style.display = 'none';
    }

    // Hide search results if clicking outside
    document.addEventListener('click', function(event) {
        if (!employeeSearchInput.contains(event.target) && !employeeSearchResults.contains(event.target)) {
            employeeSearchResults.style.display = 'none';
        }
    });

    // Delete confirmation
    function confirmDelete(employeeName) {
        return confirm(`Are you sure you want to delete bonded employee "${employeeName}"? This action cannot be undone.`);
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

    // Form validation
    const addForm = document.querySelector('#addBondedEmployeeModal form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            if (!bondedIdField.value || !fullNameField.value || !emailField.value) {
                e.preventDefault();
                alert('Please select an employee using the search bar.');
                return;
            }

            const requiredFields = addForm.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.readOnly && !field.value.trim()) {
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

    // Date validation
    const dateOfBondField = document.getElementById('date_of_bond');
    const dueDateField = document.getElementById('due_date');

    if (dateOfBondField) {
        dateOfBondField.addEventListener('change', function() {
            if (this.value && dueDateField.value) {
                if (new Date(this.value) >= new Date(dueDateField.value)) {
                    alert('Due date must be after the start date of bond.');
                    dueDateField.value = '';
                }
            }
        });
    }

    if (dueDateField) {
        dueDateField.addEventListener('change', function() {
            if (this.value && dateOfBondField.value) {
                if (new Date(dateOfBondField.value) >= new Date(this.value)) {
                    alert('Due date must be after the start date of bond.');
                    this.value = '';
                }
            }
        });
    }
</script>
</body>
</html> 