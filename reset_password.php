<?php
session_start();
require_once 'config/database.php';

$error = $success = '';
$validToken = false;
$email = '';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    try {
        // Debug: Log the token being checked
        error_log("Attempting to validate token: " . $token);
        
        // Check if token exists and is not expired
        $stmt = $pdo->prepare("SELECT email, reset_token, reset_token_expiry FROM users WHERE reset_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            error_log("Token found in database. Expiry: " . $user['reset_token_expiry']);
            // Check if token is expired
            if (strtotime($user['reset_token_expiry']) > time()) {
                $validToken = true;
                $email = $user['email'];
                error_log("Token is valid for email: " . $email);
            } else {
                error_log("Token has expired");
                $error = "Reset token has expired. Please request a new password reset.";
            }
        } else {
            error_log("No matching token found in database");
            $error = "Invalid reset token. Please request a new password reset.";
        }
    } catch (PDOException $e) {
        error_log("Database error during token validation: " . $e->getMessage());
        $error = "An error occurred. Please try again later.";
    }
} else {
    error_log("No token provided in URL");
    $error = "No reset token provided.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $validToken) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        try {
            // Update password and clear reset token
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE email = ?");
            $stmt->execute([$password, $email]);
            
            $success = "Password has been reset successfully. You can now login with your new password.";
            $validToken = false; // Prevent form from showing after successful reset
        } catch (PDOException $e) {
            $error = "Failed to reset password. Please try again.";
            error_log("Password reset error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - LGU Liquidation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #3b82f6;
            --primary-dark: #2563eb;
            --secondary: #7c3aed;
            --success: #22c55e;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #06b6d4;
            --bg-color: #f8fafc;
            --text-color: #1e293b;
            --text-muted: #64748b;
            --card-bg: #ffffff;
            --nav-bg: #ffffff;
            --nav-text: #1e293b;
            --border-color: #e2e8f0;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] {
            --primary: #60a5fa;
            --primary-dark: #3b82f6;
            --secondary: #a78bfa;
            --success: #34d399;
            --warning: #fbbf24;
            --danger: #f87171;
            --info: #22d3ee;
            --bg-color: #0f172a;
            --text-color: #f1f5f9;
            --text-muted: #94a3b8;
            --card-bg: #1e293b;
            --nav-bg: #1e293b;
            --nav-text: #f1f5f9;
            --border-color: #334155;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3), 0 2px 4px -2px rgba(0, 0, 0, 0.2);
            --hover-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3), 0 4px 6px -2px rgba(0, 0, 0, 0.2);
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .reset-container {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 400px;
            transition: all 0.3s ease;
        }

        .reset-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .reset-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .reset-header p {
            color: var(--text-muted);
            font-size: 0.875rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--text-color);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            background: var(--card-bg);
            color: var(--text-color);
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }

        .password-wrapper {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.5rem;
            transition: color 0.3s ease;
        }

        .password-toggle:hover {
            color: var(--primary);
        }

        .btn-reset {
            width: 100%;
            padding: 0.75rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-reset:hover {
            background: var(--primary-dark);
            transform: translateY(-1px);
        }

        .back-to-login {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-to-login a {
            color: var(--primary);
            text-decoration: none;
            font-size: 0.875rem;
            transition: color 0.3s ease;
        }

        .back-to-login a:hover {
            color: var(--primary-dark);
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .alert-success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        @media (max-width: 480px) {
            .reset-container {
                padding: 1.5rem;
            }

            .reset-header h1 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="reset-header">
            <h1>Reset Password</h1>
            <p>Enter your new password below</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
            <div class="back-to-login">
                <a href="login.php">
                    <i class="fas fa-arrow-left me-1"></i>Back to Login
                </a>
            </div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
            <?php if (!$validToken): ?>
                <div class="back-to-login">
                    <a href="forgot_password.php">
                        <i class="fas fa-arrow-left me-1"></i>Request New Reset Link
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($validToken): ?>
            <form method="POST" action="" id="resetForm">
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control" id="password" name="password" required 
                               minlength="8" placeholder="Enter new password">
                        <button type="button" class="password-toggle" id="passwordToggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-wrapper">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                               required minlength="8" placeholder="Confirm new password">
                        <button type="button" class="password-toggle" id="confirmPasswordToggle">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <button type="submit" class="btn-reset">
                    <i class="fas fa-key me-2"></i>Reset Password
                </button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Theme detection
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }

        // Password toggle functionality
        const passwordToggle = document.getElementById('passwordToggle');
        const confirmPasswordToggle = document.getElementById('confirmPasswordToggle');
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');

        if (passwordToggle && passwordInput) {
            passwordToggle.addEventListener('click', () => {
                const type = passwordInput.type === 'password' ? 'text' : 'password';
                passwordInput.type = type;
                passwordToggle.querySelector('i').classList.toggle('fa-eye');
                passwordToggle.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }

        if (confirmPasswordToggle && confirmPasswordInput) {
            confirmPasswordToggle.addEventListener('click', () => {
                const type = confirmPasswordInput.type === 'password' ? 'text' : 'password';
                confirmPasswordInput.type = type;
                confirmPasswordToggle.querySelector('i').classList.toggle('fa-eye');
                confirmPasswordToggle.querySelector('i').classList.toggle('fa-eye-slash');
            });
        }

        // Form validation
        const resetForm = document.getElementById('resetForm');
        if (resetForm) {
            resetForm.addEventListener('submit', (e) => {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                }
            });
        }
    </script>
</body>
</html> 