<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}
require_once 'config/database.php';

$success = $error = "";

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if ($newPassword === $confirmPassword) {
        try {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($currentPassword, $user['password'])) {
                // Update password
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$hashedPassword, $_SESSION['user_id']]);
                $success = "Password changed successfully!";
            } else {
                $error = "Current password is incorrect.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "New passwords do not match.";
    }
}
?>

<div class="content-header animate__animated animate__fadeInDown">
    <div class="welcome-text">
        <h1>Security <span class="admin-badge">Administrator</span></h1>
        <p>Manage account security and access controls</p>
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
        <div class="col-md-6">
            <div class="card shadow animate__animated animate__fadeInUp">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Change Password</h4>
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
                    
                    <form method="post" action="">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="change_password" class="btn btn-warning">
                            <i class="fas fa-key me-2"></i>Change Password
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow animate__animated animate__fadeInUp">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="fas fa-user-shield me-2"></i>Account Security</h4>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6>Account Information</h6>
                        <div class="row">
                            <div class="col-6">
                                <strong>User ID:</strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($_SESSION['user_id']); ?></span>
                            </div>
                            <div class="col-6">
                                <strong>Role:</strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($_SESSION['role']); ?></span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-6">
                                <strong>Name:</strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                            </div>
                            <div class="col-6">
                                <strong>Department:</strong><br>
                                <span class="text-muted"><?php echo htmlspecialchars($_SESSION['department']); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6>Security Settings</h6>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="twoFactorAuth" checked disabled>
                            <label class="form-check-label" for="twoFactorAuth">
                                Two-Factor Authentication (Required for Admin)
                            </label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="sessionTimeout">
                            <label class="form-check-label" for="sessionTimeout">
                                Auto-logout after 30 minutes of inactivity
                            </label>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="loginNotifications">
                            <label class="form-check-label" for="loginNotifications">
                                Email notifications for new login attempts
                            </label>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Security Tips:</strong>
                        <ul class="mb-0 mt-2">
                            <li>Use a strong password with at least 8 characters</li>
                            <li>Include uppercase, lowercase, numbers, and symbols</li>
                            <li>Never share your password with anyone</li>
                            <li>Log out when using shared computers</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 