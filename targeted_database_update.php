<?php
require_once 'config/database.php';

try {
    // Get the current table structure
    $stmt = $pdo->query("DESCRIBE granted_cash_advances");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $existing_columns = array_column($columns, 'Field');
    
    echo "<h2>Database Update Results:</h2>";
    
    // Check and add arrival_date column
    if (!in_array('arrival_date', $existing_columns)) {
        try {
            $pdo->exec("ALTER TABLE `granted_cash_advances` ADD COLUMN `arrival_date` date DEFAULT NULL AFTER `departure_date`");
            echo "<p style='color: green;'>✓ Added arrival_date column successfully.</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Error adding arrival_date column: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ arrival_date column already exists.</p>";
    }
    
    // Check and add date_completed column
    if (!in_array('date_completed', $existing_columns)) {
        try {
            $pdo->exec("ALTER TABLE `granted_cash_advances` ADD COLUMN `date_completed` date DEFAULT NULL AFTER `arrival_date`");
            echo "<p style='color: green;'>✓ Added date_completed column successfully.</p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Error adding date_completed column: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ date_completed column already exists.</p>";
    }
    
    // Check and update status enum
    $status_column = array_filter($columns, function($col) { return $col['Field'] === 'status'; });
    if (!empty($status_column)) {
        $status_col = reset($status_column);
        if (strpos($status_col['Type'], 'completed') === false) {
            try {
                $pdo->exec("ALTER TABLE `granted_cash_advances` MODIFY COLUMN `status` enum('pending','liquidated','overdue','completed') NOT NULL DEFAULT 'pending'");
                echo "<p style='color: green;'>✓ Updated status enum to include 'completed' successfully.</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>✗ Error updating status enum: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ Status enum already includes 'completed'.</p>";
        }
    }
    
    // Add indexes if they don't exist
    try {
        $pdo->exec("ALTER TABLE `granted_cash_advances` ADD INDEX `idx_arrival_date` (`arrival_date`)");
        echo "<p style='color: green;'>✓ Added arrival_date index successfully.</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p style='color: blue;'>ℹ arrival_date index already exists.</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding arrival_date index: " . $e->getMessage() . "</p>";
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE `granted_cash_advances` ADD INDEX `idx_date_completed` (`date_completed`)");
        echo "<p style='color: green;'>✓ Added date_completed index successfully.</p>";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "<p style='color: blue;'>ℹ date_completed index already exists.</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding date_completed index: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<br><h3 style='color: green;'>Database update completed! You can now use the add_granted.php page.</h3>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?> 