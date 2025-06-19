<?php
session_start();

// Log logout activity if user was logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['full_name'])) {
    require_once 'config/database.php';
    require_once __DIR__ . '/includes/ActivityLogger.php';
    
    $activityLogger = new ActivityLogger($pdo);
    $activityLogger->logLogout($_SESSION['user_id'], $_SESSION['full_name']);
}

session_destroy();
header("Location: login.php");
exit();
?> 