<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';

$success = $error = "";

// Handle form submission (no form data to process yet, keeping the structure)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Placeholder for future form handling if needed
}

// Fetch all employees (for potential use in tabs)
$employees = [];
try {
    $stmt = $pdo->query("SELECT * FROM employees ORDER BY date_added DESC");
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<div class="content-header animate__animated animate__fadeInDown">
    <div class="welcome-text">
        <h1>User Management <span class="admin-badge">Administrator</span></h1>
        <p>Manage employees and user accounts</p>
    </div>
    <div class="header-actions">
        <button class="theme-switch" id="themeSwitch"><i class="fas fa-moon"></i></button>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i>
            Logout
        </a>
    </div>
</div>

<div class="container-fluid p-0">
    <div class="row">
        <div class="col-12">
            <div class="card shadow animate__animated animate__fadeInUp">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-users me-2"></i>Employee Management</h4>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Tab Navigation -->
                    <ul class="nav nav-tabs" id="employeeTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="employees-tab" data-bs-toggle="tab" data-bs-target="#employees" type="button" role="tab" aria-controls="employees" aria-selected="true">Employees</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="employees-bunded-tab" data-bs-toggle="tab" data-bs-target="#employees-bunded" type="button" role="tab" aria-controls="employees-bunded" aria-selected="false">Employees Bunded</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="type-of-cash-advance-tab" data-bs-toggle="tab" data-bs-target="#type-of-cash-advance" type="button" role="tab" aria-controls="type-of-cash-advance" aria-selected="false">Type of Cash Advance</button>
                        </li>
                    </ul>

                    <!-- Tab Content -->
                    <div class="tab-content" id="employeeTabContent">
                        <!-- Employees Tab -->
                        <div class="tab-pane fade show active" id="employees" role="tabpanel" aria-labelledby="employees-tab">
                            <div class="mt-3">
                                <h5>Employees List</h5>
                                <p>This tab will display the list of all employees.</p>
                                <!-- Placeholder for employee list content -->
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Department</th>
                                                <th>Email</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($employees as $employee): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($employee['id']); ?></td>
                                                <td><?php echo htmlspecialchars($employee['name']); ?></td>
                                                <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                                <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary">Edit</button>
                                                    <button class="btn btn-sm btn-danger">Delete</button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Employees Bunded Tab -->
                        <div class="tab-pane fade" id="employees-bunded" role="tabpanel" aria-labelledby="employees-bunded-tab">
                            <div class="mt-3">
                                <h5>Employees Bunded</h5>
                                <p>This tab will show bundled employee information.</p>
                                <!-- Placeholder for bundled employee content -->
                            </div>
                        </div>

                        <!-- Type of Cash Advance Tab -->
                        <div class="tab-pane fade" id="type-of-cash-advance" role="tabpanel" aria-labelledby="type-of-cash-advance-tab">
                            <div class="mt-3">
                                <h5>Type of Cash Advance</h5>
                                <p>This tab will display types of cash advances available.</p>
                                <!-- Placeholder for cash advance type content -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Re-initialize Bootstrap components after content load
    if (typeof bootstrap !== 'undefined') {
        // Re-initialize tabs
        const tabElements = document.querySelectorAll('[data-bs-toggle="tab"]');
        tabElements.forEach(tab => {
            new bootstrap.Tab(tab);
        });
    }
</script> 