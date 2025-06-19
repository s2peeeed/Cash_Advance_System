<?php
require_once 'config/database.php';

try {
    // Add departure_date column
    $pdo->exec("ALTER TABLE `granted_cash_advances` ADD COLUMN `departure_date` date DEFAULT NULL AFTER `due_date`");
    echo "Added departure_date column successfully.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "departure_date column already exists.<br>";
    } else {
        echo "Error adding departure_date column: " . $e->getMessage() . "<br>";
    }
}

try {
    // Add arrival_date column
    $pdo->exec("ALTER TABLE `granted_cash_advances` ADD COLUMN `arrival_date` date DEFAULT NULL AFTER `departure_date`");
    echo "Added arrival_date column successfully.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "arrival_date column already exists.<br>";
    } else {
        echo "Error adding arrival_date column: " . $e->getMessage() . "<br>";
    }
}

try {
    // Add indexes for better performance
    $pdo->exec("ALTER TABLE `granted_cash_advances` ADD INDEX `idx_departure_date` (`departure_date`)");
    echo "Added departure_date index successfully.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "departure_date index already exists.<br>";
    } else {
        echo "Error adding departure_date index: " . $e->getMessage() . "<br>";
    }
}

try {
    $pdo->exec("ALTER TABLE `granted_cash_advances` ADD INDEX `idx_arrival_date` (`arrival_date`)");
    echo "Added arrival_date index successfully.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "arrival_date index already exists.<br>";
    } else {
        echo "Error adding arrival_date index: " . $e->getMessage() . "<br>";
    }
}

try {
    // Update status enum to include 'completed'
    $pdo->exec("ALTER TABLE `granted_cash_advances` MODIFY COLUMN `status` enum('pending','liquidated','overdue','completed') NOT NULL DEFAULT 'pending'");
    echo "Updated status enum successfully.<br>";
} catch (PDOException $e) {
    echo "Error updating status enum: " . $e->getMessage() . "<br>";
}

try {
    // Add date_completed column
    $pdo->exec("ALTER TABLE `granted_cash_advances` ADD COLUMN `date_completed` date DEFAULT NULL AFTER `arrival_date`");
    echo "Added date_completed column successfully.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "date_completed column already exists.<br>";
    } else {
        echo "Error adding date_completed column: " . $e->getMessage() . "<br>";
    }
}

try {
    $pdo->exec("ALTER TABLE `granted_cash_advances` ADD INDEX `idx_date_completed` (`date_completed`)");
    echo "Added date_completed index successfully.<br>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
        echo "date_completed index already exists.<br>";
    } else {
        echo "Error adding date_completed index: " . $e->getMessage() . "<br>";
    }
}

echo "<br>Database update completed! You can now use the add_granted.php page.";
?> 