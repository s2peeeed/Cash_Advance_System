<?php
session_start();
require_once 'config/database.php';
require_once 'includes/EmailSender.php';

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    
    if (!$email) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Check if email exists in the database
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                try {
                    // Clear any existing reset tokens first
                    $stmt = $pdo->prepare("UPDATE users SET reset_token = NULL, reset_token_expiry = NULL WHERE email = ?");
                    $stmt->execute([$email]);
                    error_log("Cleared existing reset tokens for: " . $email);
                    
                    // Generate a unique reset token
                    $token = bin2hex(random_bytes(32));
                    $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    error_log("Generated new token: " . $token . " with expiry: " . $expiry);
                    
                    // Store the token in the database
                    $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE email = ?");
                    $stmt->execute([$token, $expiry, $email]);
                    error_log("Stored new token in database for: " . $email);

                    // Verify token was stored
                    $stmt = $pdo->prepare("SELECT reset_token, reset_token_expiry FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $verify = $stmt->fetch();
                    error_log("Verified stored token: " . $verify['reset_token'] . " with expiry: " . $verify['reset_token_expiry']);

                    // Send reset email
                    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/lgu/reset_password.php?token=" . $token;
                    error_log("Generated reset link: " . $resetLink);
                    $subject = "Password Reset Request - LGU Liquidation System";
                    $message = "
                        <p>Dear " . htmlspecialchars($user['full_name']) . ",</p>
                        <p>We received a request to reset your password. Click the link below to reset your password:</p>
                        <p><a href='" . $resetLink . "' style='display: inline-block; padding: 10px 20px; background-color: #3b82f6; color: white; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                        <p>This link will expire in 1 hour.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                        <p>Best regards,<br>LGU Liquidation System Team</p>
                    ";

                    $emailSender = new EmailSender();
                    $emailSender->sendReminder($email, $subject, $message);
                    
                    $success = "Password reset instructions have been sent to your email.";
                } catch (Exception $e) {
                    $error = "An error occurred. Please try again later.";
                    error_log("Password reset error: " . $e->getMessage());
                }
            } else {
                $error = "No admin account found with this email address.";
            }
        } catch (Exception $e) {
            $error = "An error occurred. Please try again later.";
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
    <title>Forgot Password - LGU Liquidation System</title>
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

        .forgot-container {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 1rem;
            box-shadow: var(--card-shadow);
            width: 100%;
            max-width: 400px;
            transition: all 0.3s ease;
        }

        .forgot-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .forgot-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-color);
            margin-bottom: 0.5rem;
        }

        .forgot-header p {
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
            .forgot-container {
                padding: 1.5rem;
            }

            .forgot-header h1 {
                font-size: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="forgot-container">
        <div class="forgot-header">
            <h1>Forgot Password</h1>
            <p>Enter your email address to reset your password</p>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" required 
                       placeholder="Enter your admin email address">
            </div>
            <button type="submit" class="btn-reset">
                <i class="fas fa-paper-plane me-2"></i>Send Reset Link
            </button>
        </form>

        <div class="back-to-login">
            <a href="login.php">
                <i class="fas fa-arrow-left me-1"></i>Back to Login
            </a>
        </div>
    </div>

    <script>
        // Theme detection
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</body>
</html> 