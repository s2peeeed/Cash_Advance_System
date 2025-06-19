<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $department = trim($_POST['department']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $errors = [];

    // Validate full name
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Email already exists";
        }
    }

    // Validate department
    if (empty($department)) {
        $errors[] = "Department is required";
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

    // Validate confirm password
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, department, password) VALUES (?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $department, $password]);
            
            // Set success message
            $_SESSION['success'] = "Registration successful! Please login.";
            header("Location: login.php");
            exit();
        } catch(PDOException $e) {
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - LGU Liquidation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --bg-gradient: linear-gradient(120deg, #2980b9, #8e44ad);
            --container-bg: white;
            --text-color: #2c3e50;
            --input-bg: white;
            --input-border: #ddd;
            --btn-bg: #2980b9;
            --btn-hover: #3498db;
            --error-color: #e74c3c;
            --success-color: #27ae60;
        }

        [data-theme="dark"] {
            --bg-gradient: linear-gradient(120deg, #1a1a1a, #2c3e50);
            --container-bg: #2c3e50;
            --text-color: #ecf0f1;
            --input-bg: #34495e;
            --input-border: #2c3e50;
            --btn-bg: #3498db;
            --btn-hover: #2980b9;
            --error-color: #e74c3c;
            --success-color: #2ecc71;
        }

        body {
            background: var(--bg-gradient);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.3s ease;
        }

        .register-container {
            background: var(--container-bg);
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 500px;
            transition: all 0.3s ease;
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h1 {
            color: var(--text-color);
            font-size: 24px;
            margin-bottom: 10px;
            transition: color 0.3s ease;
        }

        .form-control {
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            color: var(--text-color);
            border-radius: 5px;
            padding: 12px;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: var(--input-bg);
            color: var(--text-color);
        }

        .btn-register {
            background: var(--btn-bg);
            border: none;
            padding: 12px;
            border-radius: 5px;
            width: 100%;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-register:hover {
            background: var(--btn-hover);
        }

        .error-message {
            color: var(--error-color);
            text-align: center;
            margin-bottom: 20px;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: var(--text-color);
        }

        .login-link a {
            color: var(--btn-bg);
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
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
            transition: all 0.3s ease;
        }

        .theme-switch:hover {
            transform: scale(1.1);
        }
    </style>
</head>
<body>
    <button class="theme-switch" id="themeSwitch">
        <i class="fas fa-moon"></i>
    </button>

    <div class="register-container">
        <div class="register-header">
            <h1>Create Account</h1>
            <p>Register for LGU Liquidation System</p>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <?php foreach($errors as $error): ?>
                    <div><?php echo $error; ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <input type="text" class="form-control" name="full_name" placeholder="Full Name" 
                       value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <input type="email" class="form-control" name="email" placeholder="Email Address"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <input type="text" class="form-control" name="department" placeholder="Department"
                       value="<?php echo isset($_POST['department']) ? htmlspecialchars($_POST['department']) : ''; ?>" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="password" placeholder="Password" required>
            </div>
            <div class="mb-3">
                <input type="password" class="form-control" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            <button type="submit" class="btn btn-register">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark mode functionality
        const themeSwitch = document.getElementById('themeSwitch');
        const icon = themeSwitch.querySelector('i');
        
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            icon.classList.replace('fa-moon', 'fa-sun');
        }

        themeSwitch.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            
            if (currentTheme === 'dark') {
                document.documentElement.removeAttribute('data-theme');
                localStorage.setItem('theme', 'light');
                icon.classList.replace('fa-sun', 'fa-moon');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark');
                icon.classList.replace('fa-moon', 'fa-sun');
            }
        });
    </script>
</body>
</html> 