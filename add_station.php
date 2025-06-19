<?php
// session_start();
// Optional: Only allow admin access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}

require_once 'config/database.php';

$success = $error = "";
$edit_station = null;

// Handle form submission for adding new station
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_station'])) {
        $station_name = trim($_POST['station_name']);
        if ($station_name) {
            try {
                // Check for duplicate station name
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM stations WHERE station_name = ?");
                $stmt->execute([$station_name]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Station name already exists. Please use a different name.";
                } else {
                    $stmt = $pdo->prepare("INSERT INTO stations (station_name) VALUES (?)");
                    $stmt->execute([$station_name]);
                    $success = "Station added successfully.";
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Please enter a station name.";
        }
    } elseif (isset($_POST['update_station'])) {
        $station_name = trim($_POST['station_name']);
        $original_station_id = $_POST['original_station_id'];
        
        if ($station_name) {
            try {
                // Check for duplicate station name (excluding current station)
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM stations WHERE station_name = ? AND id != ?");
                $stmt->execute([$station_name, $original_station_id]);
                if ($stmt->fetchColumn() > 0) {
                    $error = "Station name already exists. Please use a different name.";
                } else {
                    $stmt = $pdo->prepare("UPDATE stations SET station_name = ? WHERE id = ?");
                    $stmt->execute([$station_name, $original_station_id]);
                    $success = "Station updated successfully.";
                    $edit_station = null; // Clear edit mode
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        } else {
            $error = "Please enter a station name.";
        }
    } elseif (isset($_POST['delete_station'])) {
        $station_id = $_POST['station_id'];
        $station_name = $_POST['station_name'];
        
        try {
            // Check if station is being used in any bonded_employees
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bonded_employees WHERE station = (SELECT station_name FROM stations WHERE id = ?)");
            $stmt->execute([$station_id]);
            $usage_count = $stmt->fetchColumn();
            
            if ($usage_count > 0) {
                $error = "Cannot delete station '" . htmlspecialchars($station_name) . "'. It is being used by " . $usage_count . " bonded employee(s). Please update those records first.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM stations WHERE id = ?");
                if ($stmt->execute([$station_id])) {
                    $success = "Station '" . htmlspecialchars($station_name) . "' has been deleted successfully!";
                } else {
                    $error = "Failed to delete station.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } elseif (isset($_POST['edit_station'])) {
        $station_id = $_POST['station_id'];
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM stations WHERE id = ?");
            $stmt->execute([$station_id]);
            $edit_station = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Fetch all stations with usage count
$stations = [];
try {
    $stmt = $pdo->query("
        SELECT s.*, 
               COALESCE(usage_count, 0) as usage_count
        FROM stations s
        LEFT JOIN (
            SELECT station, COUNT(*) as usage_count 
            FROM bonded_employees 
            GROUP BY station
        ) usage_stats ON s.station_name = usage_stats.station
        ORDER BY s.station_name ASC
    ");
    $stations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Station Management - LGU Liquidation System</title>
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

        .badge-danger {
            background-color: var(--danger);
        }

        .badge-info {
            background-color: var(--info);
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

        /* Modal Styling */
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
                                <i class="fas fa-building me-2"></i>Station Management
                            </h1>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addStationModal">
                                <i class="fas fa-plus me-2"></i>Add New Station
                            </button>
                        </div>

                        <!-- Stations Table -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Station Name</th>
                                        <th>Usage Count</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stations as $station): ?>
                                    <tr class="fade-in">
                                        <td>
                                            <strong>#<?php echo htmlspecialchars($station['id']); ?></strong>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-building text-primary me-2"></i>
                                                <strong><?php echo htmlspecialchars($station['station_name']); ?></strong>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($station['usage_count'] > 0): ?>
                                                <span class="badge badge-info">
                                                    <i class="fas fa-users me-1"></i><?php echo $station['usage_count']; ?> employee(s)
                                                </span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-times-circle me-1"></i>No usage
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="station_id" value="<?php echo htmlspecialchars($station['id']); ?>">
                                                    <button type="submit" name="edit_station" class="btn btn-sm btn-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;" onsubmit="return confirmDelete('<?php echo htmlspecialchars($station['station_name']); ?>', <?php echo $station['usage_count']; ?>)">
                                                    <input type="hidden" name="station_id" value="<?php echo htmlspecialchars($station['id']); ?>">
                                                    <input type="hidden" name="station_name" value="<?php echo htmlspecialchars($station['station_name']); ?>">
                                                    <button type="submit" name="delete_station" class="btn btn-sm btn-danger" title="Delete" <?php echo $station['usage_count'] > 0 ? 'disabled' : ''; ?>>
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

    <!-- Add Station Modal -->
    <div class="modal fade" id="addStationModal" tabindex="-1" aria-labelledby="addStationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addStationModalLabel">
                        <i class="fas fa-plus me-2"></i>Add New Station
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="station_name" class="form-label">Station Name</label>
                            <input type="text" class="form-control" id="station_name" name="station_name" required placeholder="e.g., Main Office, Branch Office, Field Station">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="add_station" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Save Station
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Station Modal -->
    <?php if ($edit_station): ?>
    <div class="modal fade" id="editStationModal" tabindex="-1" aria-labelledby="editStationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editStationModalLabel">
                        <i class="fas fa-edit me-2"></i>Edit Station
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="original_station_id" value="<?php echo htmlspecialchars($edit_station['id']); ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_station_name" class="form-label">Station Name</label>
                            <input type="text" class="form-control" id="edit_station_name" name="station_name" value="<?php echo htmlspecialchars($edit_station['station_name']); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Cancel
                        </button>
                        <button type="submit" name="update_station" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Station
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

            // Show edit modal if edit_station is set
            <?php if ($edit_station): ?>
            const editModal = new bootstrap.Modal(document.getElementById('editStationModal'));
            editModal.show();
            <?php endif; ?>

            // Clear form when add modal is closed
            const addModal = document.getElementById('addStationModal');
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
            const editModalElement = document.getElementById('editStationModal');
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

        // Delete confirmation
        function confirmDelete(stationName, usageCount) {
            if (usageCount > 0) {
                alert(`Cannot delete station "${stationName}" because it is being used by ${usageCount} employee(s). Please update those records first.`);
                return false;
            }
            return confirm(`Are you sure you want to delete station "${stationName}"? This action cannot be undone.`);
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

        // Form validation for add station
        const addForm = document.querySelector('#addStationModal form');
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

        // Real-time form validation
        document.getElementById('station_name')?.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            } else {
                this.classList.add('is-invalid');
            }
        });

        document.getElementById('edit_station_name')?.addEventListener('input', function() {
            if (this.value.trim()) {
                this.classList.remove('is-invalid');
            } else {
                this.classList.add('is-invalid');
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