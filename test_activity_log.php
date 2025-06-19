<?php
session_start();

// Strict admin-only access check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    session_unset();
    session_destroy();
    header("Location: login.php?error=admin_only");
    exit();
}

require_once 'config/database.php';
require_once 'includes/ActivityLogger.php';

$activityLogger = new ActivityLogger($pdo);

echo "<h2>Activity Log Test</h2>";

// Test 1: Check if table exists
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'activity_log'");
    $tableExists = $stmt->rowCount() > 0;
    echo "<p><strong>Table exists:</strong> " . ($tableExists ? "YES" : "NO") . "</p>";
} catch (PDOException $e) {
    echo "<p><strong>Error checking table:</strong> " . $e->getMessage() . "</p>";
}

// Test 2: Check table structure
if ($tableExists) {
    try {
        $stmt = $pdo->query("DESCRIBE activity_log");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p><strong>Table structure:</strong></p><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
    } catch (PDOException $e) {
        echo "<p><strong>Error describing table:</strong> " . $e->getMessage() . "</p>";
    }
}

// Test 3: Check if there's any data
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM activity_log");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p><strong>Total records:</strong> " . $count . "</p>";
} catch (PDOException $e) {
    echo "<p><strong>Error counting records:</strong> " . $e->getMessage() . "</p>";
}

// Test 4: Show recent activities
if ($count > 0) {
    try {
        $stmt = $pdo->query("SELECT * FROM activity_log ORDER BY created_at DESC LIMIT 5");
        $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p><strong>Recent activities:</strong></p><ul>";
        foreach ($activities as $activity) {
            echo "<li>{$activity['created_at']} - {$activity['action']} - {$activity['description']}</li>";
        }
        echo "</ul>";
    } catch (PDOException $e) {
        echo "<p><strong>Error fetching activities:</strong> " . $e->getMessage() . "</p>";
    }
}

// Test 5: Test users table
try {
    $stmt = $pdo->query("SELECT user_id, full_name FROM users LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Users table sample:</strong></p><ul>";
    foreach ($users as $user) {
        echo "<li>ID: {$user['user_id']} - Name: {$user['full_name']}</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p><strong>Error fetching users:</strong> " . $e->getMessage() . "</p>";
}

// Test 6: Test the join query
try {
    $sql = "SELECT al.*, u.full_name as user_full_name 
            FROM activity_log al 
            LEFT JOIN users u ON al.user_id = u.user_id 
            ORDER BY al.created_at DESC LIMIT 5";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $joinedActivities = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p><strong>Joined query result:</strong></p><ul>";
    foreach ($joinedActivities as $activity) {
        echo "<li>{$activity['created_at']} - {$activity['action']} - {$activity['user_name']} - {$activity['description']}</li>";
    }
    echo "</ul>";
} catch (PDOException $e) {
    echo "<p><strong>Error with joined query:</strong> " . $e->getMessage() . "</p>";
}

echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";
?> 