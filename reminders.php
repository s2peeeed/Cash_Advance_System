<?php
// session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'config/database.php';
require_once 'includes/EmailSender.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);

    if (!$email) {
        $error_message = "Invalid email address.";
    } elseif (empty($subject) || empty($message)) {
        $error_message = "Subject and message are required.";
    } else {
        try {
            $emailSender = new EmailSender();
            $emailSender->sendReminder($email, $subject, $message);
            $success_message = "Reminder email sent successfully!";
        } catch (Exception $e) {
            $error_message = "Failed to send reminder: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Reminders - LGU Liquidation System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #3b82f6;
            --secondary: #6b7280;
            --success: #22c55e;
            --danger: #ef4444;
            --bg-light: #f9fafb;
            --card-bg: #ffffff;
            --nav-bg: #ffffff;
            --nav-text: #1f2937;
            --border-color: #e5e7eb;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        [data-theme="dark"] {
            --bg-light: #1a1a1a;
            --card-bg: #2c3e50;
            --nav-bg: #2c3e50;
            --nav-text: #ecf0f1;
            --border-color: #4b5563;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: var(--nav-text);
            transition: background-color 0.3s ease, color 0.3s ease;
            min-height: 100vh;
        }

        .navbar {
            background-color: var(--nav-bg);
            box-shadow: var(--shadow);
        }

        .navbar-brand, .nav-link {
            color: var(--nav-text) !important;
            transition: color 0.3s ease;
        }

        .navbar-brand:hover, .nav-link:hover {
            color: var(--primary) !important;
        }

        .card {
            background-color: var(--card-bg);
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
        }

        .card-header {
            border-radius: 12px 12px 0 0;
            background-color: var(--primary);
            color: white;
        }

        .form-control {
            background-color: var(--card-bg);
            color: var(--nav-text);
            border: 1px solid var(--border-color);
            border-radius: 8px;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.25);
        }

        .admin-badge {
            background: var(--primary);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 12px;
            margin-left: 10px;
        }

        .theme-toggle {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .theme-toggle:hover {
            border-color: var(--primary);
            transform: scale(1.1) rotate(360deg);
        }

        .theme-toggle i {
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .preview-box {
            margin-top: 1rem;
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: var(--card-bg);
            display: none;
        }

        @media (max-width: 768px) {
            .card-header {
                padding: 1rem;
            }
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">LGU Liquidation System</a>
            <div class="d-flex align-items-center">
                <button class="theme-toggle me-3" id="themeToggle">
                    <i class="fas fa-moon" id="themeIcon"></i>
                </button>
                <div class="dropdown">
                    <button class="btn btn-link nav-link dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-1"></i>
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        <span class="admin-badge">Administrator</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav> -->

    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0"><i class="fas fa-envelope me-2"></i>Send Reminder Email</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo $success_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($error_message): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo $error_message; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" id="reminderForm">
                            <div class="mb-3">
                                <label for="email" class="form-label">Recipient Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="subject" class="form-label">Subject</label>
                                <input type="text" class="form-control" id="subject" name="subject" required>
                            </div>
                            <div class="mb-3">
                                <label for="message" class="form-label">Message</label>
                                <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Send Reminder
                            </button>
                            <button type="button" class="btn btn-secondary ms-2" id="previewBtn">Preview</button>
                        </form>
                        <div class="preview-box" id="previewBox"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Theme toggle with system preference detection
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = document.getElementById('themeIcon');
        
        // Function to set theme
        function setTheme(theme) {
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
                themeIcon.classList.replace('fa-moon', 'fa-sun');
            } else {
                document.documentElement.removeAttribute('data-theme');
                themeIcon.classList.replace('fa-sun', 'fa-moon');
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

        themeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            setTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });

        // Preview functionality
        const previewBtn = document.getElementById('previewBtn');
        const previewBox = document.getElementById('previewBox');
        const emailInput = document.getElementById('email');
        const subjectInput = document.getElementById('subject');
        const messageInput = document.getElementById('message');

        previewBtn.addEventListener('click', () => {
            const email = emailInput.value;
            const subject = subjectInput.value;
            const message = messageInput.value;
            if (email && subject && message) {
                previewBox.innerHTML = `
                    <h5>Preview</h5>
                    <p><strong>To:</strong> ${email}</p>
                    <p><strong>Subject:</strong> ${subject}</p>
                    <p><strong>Message:</strong> ${message}</p>
                `;
                previewBox.style.display = 'block';
            } else {
                alert('Please fill in all fields to preview.');
            }
        });

        // Close preview on form submit
        document.getElementById('reminderForm').addEventListener('submit', () => {
            previewBox.style.display = 'none';
        });
    </script>
</body>
</html>