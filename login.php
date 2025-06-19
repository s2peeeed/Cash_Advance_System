<?php
session_start();
require_once 'config/database.php';
require_once __DIR__ . '/includes/ActivityLogger.php';

$activityLogger = new ActivityLogger($pdo);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    try {
        // Only check for admin users
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['department'] = $user['department'];
            $_SESSION['role'] = $user['role'];
            
            // Log successful login
            $activityLogger->logLogin($user['user_id'], $user['full_name']);
            
            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Access denied. Admin privileges required.";
        }
    } catch(PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - LGU Liquidation System</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            /* Color System */
            --primary-50: #eff6ff;
            --primary-100: #dbeafe;
            --primary-200: #bfdbfe;
            --primary-300: #93c5fd;
            --primary-400: #60a5fa;
            --primary-500: #3b82f6;
            --primary-600: #2563eb;
            --primary-700: #1d4ed8;
            --primary-800: #1e40af;
            --primary-900: #1e3a8a;
            
            --purple-500: #8b5cf6;
            --purple-600: #7c3aed;
            --purple-700: #6d28d9;
            
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            --emerald-500: #10b981;
            --emerald-600: #059669;
            --red-500: #ef4444;
            --red-600: #dc2626;
            
            /* Theme Variables */
            --bg-primary: #ffffff;
            --bg-secondary: var(--gray-50);
            --text-primary: var(--gray-900);
            --text-secondary: var(--gray-600);
            --text-muted: var(--gray-400);
            --border-color: var(--gray-200);
            --input-bg: #ffffff;
            --input-border: var(--gray-300);
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            --shadow-2xl: 0 25px 50px -12px rgb(0 0 0 / 0.25);
            
            /* Gradients */
            --gradient-primary: linear-gradient(135deg, var(--primary-600) 0%, var(--purple-600) 100%);
            --gradient-bg: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-mesh: 
                radial-gradient(at 40% 20%, hsla(228,100%,74%,1) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(189,100%,56%,1) 0px, transparent 50%),
                radial-gradient(at 0% 50%, hsla(355,100%,93%,1) 0px, transparent 50%),
                radial-gradient(at 80% 50%, hsla(340,100%,76%,1) 0px, transparent 50%),
                radial-gradient(at 0% 100%, hsla(22,100%,77%,1) 0px, transparent 50%),
                radial-gradient(at 80% 100%, hsla(242,100%,70%,1) 0px, transparent 50%),
                radial-gradient(at 0% 0%, hsla(343,100%,76%,1) 0px, transparent 50%);
        }

        [data-theme="dark"] {
            --bg-primary: var(--gray-800);
            --bg-secondary: var(--gray-900);
            --text-primary: var(--gray-100);
            --text-secondary: var(--gray-300);
            --text-muted: var(--gray-500);
            --border-color: var(--gray-600);
            --input-bg: var(--gray-700);
            --input-border: var(--gray-600);
            --gradient-bg: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            --gradient-mesh:
                radial-gradient(at 40% 20%, hsla(228,100%,74%,0.3) 0px, transparent 50%),
                radial-gradient(at 80% 0%, hsla(189,100%,56%,0.3) 0px, transparent 50%),
                radial-gradient(at 0% 50%, hsla(355,100%,93%,0.2) 0px, transparent 50%),
                radial-gradient(at 80% 50%, hsla(340,100%,76%,0.3) 0px, transparent 50%),
                radial-gradient(at 0% 100%, hsla(22,100%,77%,0.2) 0px, transparent 50%),
                radial-gradient(at 80% 100%, hsla(242,100%,70%,0.3) 0px, transparent 50%),
                radial-gradient(at 0% 0%, hsla(343,100%,76%,0.2) 0px, transparent 50%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            background: var(--gradient-bg);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            position: relative;
            overflow-x: hidden;
            transition: background 0.3s ease;
        }

        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--gradient-mesh);
            opacity: 0.8;
            z-index: 0;
            animation: meshAnimation 20s ease-in-out infinite;
        }

        @keyframes meshAnimation {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }

        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        .particle:nth-child(1) { top: 20%; left: 20%; animation-delay: 0s; }
        .particle:nth-child(2) { top: 80%; left: 80%; animation-delay: 2s; }
        .particle:nth-child(3) { top: 40%; left: 60%; animation-delay: 4s; }
        .particle:nth-child(4) { top: 60%; left: 10%; animation-delay: 1s; }
        .particle:nth-child(5) { top: 10%; left: 90%; animation-delay: 3s; }

        @keyframes float {
            0%, 100% { transform: translateY(0px) scale(1); opacity: 0.7; }
            50% { transform: translateY(-30px) scale(1.2); opacity: 1; }
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 3rem 2.5rem;
            border-radius: 2rem;
            box-shadow: var(--shadow-2xl);
            width: 100%;
            max-width: 440px;
            position: relative;
            z-index: 10;
            animation: slideUp 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        [data-theme="dark"] .login-container {
            background: rgba(31, 41, 55, 0.95);
            border: 1px solid rgba(75, 85, 99, 0.3);
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(60px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .login-header {
            text-align: center;
            margin-bottom: 2.5rem;
            position: relative;
        }

        .logo-section {
            margin-bottom: 1.5rem;
        }

        .logo-container {
            width: 88px;
            height: 88px;
            background: var(--gradient-primary);
            border-radius: 24px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
            position: relative;
            animation: logoPulse 3s ease-in-out infinite;
        }

        @keyframes logoPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); box-shadow: var(--shadow-xl); }
        }

        .logo-container::before {
            content: '';
            position: absolute;
            top: -4px;
            left: -4px;
            right: -4px;
            bottom: -4px;
            background: var(--gradient-primary);
            border-radius: 28px;
            z-index: -1;
            opacity: 0.3;
            animation: rotate 8s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .logo-container i {
            font-size: 2.25rem;
            color: white;
            position: relative;
            z-index: 2;
        }

        .login-header h1 {
            color: var(--text-primary);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            letter-spacing: -0.025em;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--gradient-primary);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin: 0.75rem 0;
            box-shadow: var(--shadow-md);
            animation: badgeGlow 2s ease-in-out infinite alternate;
        }

        @keyframes badgeGlow {
            from { box-shadow: var(--shadow-md); }
            to { box-shadow: 0 0 20px rgba(59, 130, 246, 0.4); }
        }

        .login-header p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
        }

        .form-container {
            margin-bottom: 2rem;
        }

        .form-group {
            position: relative;
            margin-bottom: 1.75rem;
        }

        .form-group label {
            display: block;
            color: var(--text-secondary);
            font-size: 0.875rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            transition: color 0.3s ease;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            font-size: 1.125rem;
            z-index: 2;
            transition: all 0.3s ease;
        }

        .form-control {
            width: 100%;
            background: var(--input-bg);
            border: 2px solid var(--input-border);
            color: var(--text-primary);
            border-radius: 1rem;
            padding: 1rem 1rem 1rem 3rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 1;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-500);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1);
            transform: translateY(-2px);
        }

        .form-control:focus + .input-icon {
            color: var(--primary-500);
            transform: translateY(-50%) scale(1.1);
        }

        .form-control.error {
            border-color: var(--red-500);
            background: rgba(239, 68, 68, 0.05);
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
            border-radius: 0.5rem;
            transition: all 0.3s ease;
            z-index: 3;
        }

        .password-toggle:hover {
            color: var(--primary-500);
            background: rgba(59, 130, 246, 0.1);
        }

        .btn-login {
            width: 100%;
            background: var(--gradient-primary);
            border: none;
            padding: 1rem 1.5rem;
            border-radius: 1rem;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: var(--shadow-lg);
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-xl);
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login:active {
            transform: translateY(-1px);
        }

        .btn-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        .btn-login .spinner {
            display: none;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin-right: 0.5rem;
        }

        .btn-login.loading .spinner {
            display: inline-block;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 1rem 1.25rem;
            border-radius: 1rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: alertSlide 0.5s ease;
            position: relative;
            overflow: hidden;
        }

        @keyframes alertSlide {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: currentColor;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--red-600);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--emerald-600);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .theme-switch {
            position: fixed;
            top: 20px;
            right: 20px;
            background: var(--container-bg);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--text-color);
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 100;
        }

        .theme-switch:hover {
            transform: scale(1.1) rotate(360deg);
        }

        .theme-switch i {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .footer-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .footer-links a {
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        .footer-links a:hover {
            color: var(--primary-600);
        }

        /* Responsive Design */
        @media (max-width: 640px) {
            body {
                padding: 0.75rem;
            }

            .login-container {
                padding: 2rem 1.5rem;
                border-radius: 1.5rem;
                max-width: 100%;
            }

            .login-header h1 {
                font-size: 1.75rem;
            }

            .logo-container {
                width: 72px;
                height: 72px;
                border-radius: 20px;
            }

            .logo-container i {
                font-size: 1.75rem;
            }

            .theme-switch {
                top: 1rem;
                right: 1rem;
                width: 3rem;
                height: 3rem;
            }

            .theme-switch i {
                font-size: 1.125rem;
            }

            .form-control {
                padding: 0.875rem 0.875rem 0.875rem 2.75rem;
            }

            .btn-login {
                padding: 0.875rem 1.25rem;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem 1rem;
            }

            .admin-badge {
                padding: 0.375rem 1rem;
                font-size: 0.675rem;
            }

            .particles {
                display: none;
            }
        }

        /* Landscape mobile optimization */
        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 0.5rem;
            }

            .login-container {
                padding: 1.5rem;
                max-height: 90vh;
                overflow-y: auto;
            }

            .login-header {
                margin-bottom: 1.5rem;
            }

            .logo-container {
                width: 56px;
                height: 56px;
                margin-bottom: 1rem;
            }

            .form-group {
                margin-bottom: 1.25rem;
            }
        }

        /* Reduced motion */
        @media (prefers-reduced-motion: reduce) {
            *,
            *::before,
            *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        /* High contrast mode */
        @media (prefers-contrast: high) {
            :root {
                --input-border: var(--gray-800);
                --border-color: var(--gray-800);
            }

            [data-theme="dark"] {
                --input-border: var(--gray-200);
                --border-color: var(--gray-200);
            }
        }
    </style>
</head>
<body>
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <button class="theme-switch" id="themeSwitch" aria-label="Toggle dark mode">
        <i class="fas fa-moon"></i>
    </button>

    <div class="login-container">
        <div class="login-header">
            <div class="logo-section">
                <div class="logo-container">
                    <i class="fas fa-user-shield"></i>
                </div>
            </div>
            <h1>Admin Portal</h1>
            <div class="admin-badge">
                <i class="fas fa-crown"></i>
                <span>Administrator Access</span>
            </div>
            <p>LGU Liquidation System</p>
        </div>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm" class="form-container" novalidate>
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your admin email" required autocomplete="email">
                    <i class="fas fa-envelope input-icon"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper password-wrapper">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    <i class="fas fa-lock input-icon"></i>
                    <button type="button" class="password-toggle" id="passwordToggle" aria-label="Toggle password visibility">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            
            <button type="submit" class="btn-login" id="loginBtn">
                <span class="spinner"></span>
                <i class="fas fa-sign-in-alt" style="margin-right: 0.5rem;"></i>
                <span>Sign In to Dashboard</span>
            </button>
        </form>

        <div class="footer-links">
            <a href="forgot_password.php" class="forgot-link">
                <i class="fas fa-key me-1"></i>Forgot Password?
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle with system preference detection
        const themeSwitch = document.getElementById('themeSwitch');
        const icon = themeSwitch.querySelector('i');
        
        // Function to set theme
        function setTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                icon.classList.replace('fa-moon', 'fa-sun');
            } else {
                document.documentElement.removeAttribute('data-theme');
                icon.classList.replace('fa-sun', 'fa-moon');
            }
            localStorage.setItem('theme', theme);
        }

        // Check for saved theme preference or system preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            setTheme(savedTheme);
        } else {
            // Check system preference
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            setTheme(prefersDark ? 'dark' : 'light');
        }

        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (!localStorage.getItem('theme')) {
                setTheme(e.matches ? 'dark' : 'light');
            }
        });

        themeSwitch.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });

        // Password toggle functionality
        const passwordToggle = document.getElementById('passwordToggle');
        const passwordInput = document.getElementById('password');
        const passwordIcon = passwordToggle.querySelector('i');

        passwordToggle.addEventListener('click', () => {
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            passwordIcon.classList.toggle('fa-eye');
            passwordIcon.classList.toggle('fa-eye-slash');
        });

        // Form handling
        const form = document.getElementById('loginForm');
        const loginBtn = document.getElementById('loginBtn');
        const formInputs = form.querySelectorAll('.form-control');

        // Real-time validation
        formInputs.forEach(input => {
            input.addEventListener('input', () => {
                validateField(input);
            });

            input.addEventListener('blur', () => {
                validateField(input);
            });

            input.addEventListener('focus', () => {
                input.classList.remove('error');
            });
        });

        function validateField(field) {
            if (field.checkValidity()) {
                field.classList.remove('error');
                return true;
            } else {
                field.classList.add('error');
                return false;
            }
        }

        // Form submission
        form.addEventListener('submit', (e) => {
            let isValid = true;
            
            formInputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });

            if (!isValid) {
                e.preventDefault();
                return;
            }

            // Show loading state
            loginBtn.classList.add('loading');
            loginBtn.disabled = true;
        });

        // Auto-focus first input
        document.getElementById('email').focus();
    </script>
</body>
</html>