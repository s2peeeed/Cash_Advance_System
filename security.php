<?php
// session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';

$success = $error = "";

// Fetch current security settings
$securitySettings = [];
try {
    $stmt = $pdo->query("SELECT * FROM security_settings LIMIT 1");
    $securitySettings = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $securitySettings = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionTimeout = intval($_POST['session_timeout']);
    $maxLoginAttempts = intval($_POST['max_login_attempts']);
    $passwordExpiryDays = intval($_POST['password_expiry_days']);
    $minPasswordLength = intval($_POST['min_password_length']);
    $requireSpecialChars = isset($_POST['require_special_chars']) ? 1 : 0;
    $requireNumbers = isset($_POST['require_numbers']) ? 1 : 0;
    $requireUppercase = isset($_POST['require_uppercase']) ? 1 : 0;
    $enable2FA = isset($_POST['enable_2fa']) ? 1 : 0;
    $ipBlockDuration = intval($_POST['ip_block_duration']);
    $maxFailedAttempts = intval($_POST['max_failed_attempts']);

    if ($sessionTimeout > 0 && $maxLoginAttempts > 0 && $passwordExpiryDays > 0 && $minPasswordLength >= 8) {
        try {
            $stmt = $pdo->prepare("UPDATE security_settings SET 
                session_timeout = ?, 
                max_login_attempts = ?, 
                password_expiry_days = ?,
                min_password_length = ?,
                require_special_chars = ?,
                require_numbers = ?,
                require_uppercase = ?,
                enable_2fa = ?,
                ip_block_duration = ?,
                max_failed_attempts = ?
            ");
            $stmt->execute([
                $sessionTimeout, 
                $maxLoginAttempts, 
                $passwordExpiryDays,
                $minPasswordLength,
                $requireSpecialChars,
                $requireNumbers,
                $requireUppercase,
                $enable2FA,
                $ipBlockDuration,
                $maxFailedAttempts
            ]);
            $success = "Security settings updated successfully!";
            // Refresh settings
            $stmt = $pdo->query("SELECT * FROM security_settings LIMIT 1");
            $securitySettings = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please fill in all required fields with valid values.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Settings</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .settings-section {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .settings-section:last-child {
            border-bottom: none;
        }
        .form-check {
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card shadow">
                    <div class="card-header bg-danger text-white">
                        <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Security Settings</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="post" action="">
                            <!-- Session Settings -->
                            <div class="settings-section">
                                <h5 class="mb-3"><i class="fas fa-clock me-2"></i>Session Settings</h5>
                                <div class="mb-3">
                                    <label for="session_timeout" class="form-label">Session Timeout (minutes)</label>
                                    <input type="number" class="form-control" id="session_timeout" name="session_timeout" 
                                           value="<?php echo htmlspecialchars($securitySettings['session_timeout'] ?? '30'); ?>" required>
                                </div>
                            </div>

                            <!-- Password Policy -->
                            <div class="settings-section">
                                <h5 class="mb-3"><i class="fas fa-key me-2"></i>Password Policy</h5>
                                <div class="mb-3">
                                    <label for="min_password_length" class="form-label">Minimum Password Length</label>
                                    <input type="number" class="form-control" id="min_password_length" name="min_password_length" 
                                           value="<?php echo htmlspecialchars($securitySettings['min_password_length'] ?? '8'); ?>" required min="8">
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="require_special_chars" name="require_special_chars" 
                                           <?php echo ($securitySettings['require_special_chars'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="require_special_chars">
                                        Require Special Characters
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="require_numbers" name="require_numbers" 
                                           <?php echo ($securitySettings['require_numbers'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="require_numbers">
                                        Require Numbers
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="require_uppercase" name="require_uppercase" 
                                           <?php echo ($securitySettings['require_uppercase'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="require_uppercase">
                                        Require Uppercase Letters
                                    </label>
                                </div>
                                <div class="mb-3 mt-3">
                                    <label for="password_expiry_days" class="form-label">Password Expiry (days)</label>
                                    <input type="number" class="form-control" id="password_expiry_days" name="password_expiry_days" 
                                           value="<?php echo htmlspecialchars($securitySettings['password_expiry_days'] ?? '90'); ?>" required>
                                </div>
                            </div>

                            <!-- Login Security -->
                            <div class="settings-section">
                                <h5 class="mb-3"><i class="fas fa-lock me-2"></i>Login Security</h5>
                                <div class="mb-3">
                                    <label for="max_login_attempts" class="form-label">Max Login Attempts</label>
                                    <input type="number" class="form-control" id="max_login_attempts" name="max_login_attempts" 
                                           value="<?php echo htmlspecialchars($securitySettings['max_login_attempts'] ?? '3'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="ip_block_duration" class="form-label">IP Block Duration (minutes)</label>
                                    <input type="number" class="form-control" id="ip_block_duration" name="ip_block_duration" 
                                           value="<?php echo htmlspecialchars($securitySettings['ip_block_duration'] ?? '30'); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="max_failed_attempts" class="form-label">Max Failed Attempts Before IP Block</label>
                                    <input type="number" class="form-control" id="max_failed_attempts" name="max_failed_attempts" 
                                           value="<?php echo htmlspecialchars($securitySettings['max_failed_attempts'] ?? '5'); ?>" required>
                                </div>
                            </div>

                            <!-- Two-Factor Authentication -->
                            <div class="settings-section">
                                <h5 class="mb-3"><i class="fas fa-mobile-alt me-2"></i>Two-Factor Authentication</h5>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="enable_2fa" name="enable_2fa" 
                                           <?php echo ($securitySettings['enable_2fa'] ?? 0) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="enable_2fa">
                                        Enable Two-Factor Authentication
                                    </label>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Save Security Settings</button>
                                <a href="dashboard.php" class="btn btn-secondary ms-2">Back to Dashboard</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 