<?php
require_once 'config/database.php';

echo "<h2>Setting up Liquidation System</h2>";

try {
    // Create liquidation_records table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `liquidation_records` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `cash_advance_id` int(11) NOT NULL,
            `liquidation_number` int(11) NOT NULL DEFAULT 1 COMMENT '1 for first liquidation, 2 for second liquidation, etc.',
            `employee_id` varchar(50) DEFAULT NULL,
            `full_name` varchar(100) NOT NULL,
            `type` varchar(50) NOT NULL,
            `voucher_number` varchar(50) DEFAULT NULL,
            `cheque_number` varchar(50) DEFAULT NULL,
            `cash_advance_amount` decimal(10,2) NOT NULL,
            `amount_liquidated` decimal(10,2) NOT NULL,
            `remaining_balance` decimal(10,2) NOT NULL COMMENT 'Cash advance amount minus amount liquidated',
            `reference_number` varchar(100) DEFAULT NULL,
            `jev_number` varchar(100) DEFAULT NULL,
            `date_submitted` date NOT NULL,
            `submitted_by` varchar(100) DEFAULT NULL COMMENT 'Admin who processed the liquidation',
            `remarks` text DEFAULT NULL,
            `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `idx_cash_advance_id` (`cash_advance_id`),
            KEY `idx_liquidation_number` (`liquidation_number`),
            KEY `idx_date_submitted` (`date_submitted`),
            KEY `idx_status` (`status`),
            CONSTRAINT `fk_liquidation_cash_advance` FOREIGN KEY (`cash_advance_id`) REFERENCES `granted_cash_advances` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    echo "<p style='color: green;'>✓ Created liquidation_records table successfully.</p>";

    // Add indexes for better performance
    $indexes = [
        'idx_full_name' => 'CREATE INDEX `idx_full_name` ON `liquidation_records` (`full_name`)',
        'idx_type' => 'CREATE INDEX `idx_type` ON `liquidation_records` (`type`)',
        'idx_voucher_number' => 'CREATE INDEX `idx_voucher_number` ON `liquidation_records` (`voucher_number`)',
        'idx_cheque_number' => 'CREATE INDEX `idx_cheque_number` ON `liquidation_records` (`cheque_number`)'
    ];

    foreach ($indexes as $index_name => $sql) {
        try {
            $pdo->exec($sql);
            echo "<p style='color: green;'>✓ Created index {$index_name} successfully.</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p style='color: blue;'>ℹ Index {$index_name} already exists.</p>";
            } else {
                echo "<p style='color: red;'>✗ Error creating index {$index_name}: " . $e->getMessage() . "</p>";
            }
        }
    }

    // Update granted_cash_advances table to ensure it has all necessary columns
    $columns_to_add = [
        'cheque_number' => 'ALTER TABLE `granted_cash_advances` ADD COLUMN `cheque_number` VARCHAR(50) NULL AFTER `amount`',
        'voucher_number' => 'ALTER TABLE `granted_cash_advances` ADD COLUMN `voucher_number` VARCHAR(50) NULL AFTER `cheque_number`',
        'departure_date' => 'ALTER TABLE `granted_cash_advances` ADD COLUMN `departure_date` date DEFAULT NULL AFTER `due_date`',
        'arrival_date' => 'ALTER TABLE `granted_cash_advances` ADD COLUMN `arrival_date` date DEFAULT NULL AFTER `departure_date`',
        'date_completed' => 'ALTER TABLE `granted_cash_advances` ADD COLUMN `date_completed` date DEFAULT NULL AFTER `arrival_date`'
    ];

    // Get existing columns
    $stmt = $pdo->query("DESCRIBE granted_cash_advances");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($columns_to_add as $column_name => $sql) {
        if (!in_array($column_name, $existing_columns)) {
            try {
                $pdo->exec($sql);
                echo "<p style='color: green;'>✓ Added column {$column_name} to granted_cash_advances table.</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>✗ Error adding column {$column_name}: " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ Column {$column_name} already exists in granted_cash_advances table.</p>";
        }
    }

    // Update status enum to include 'completed' if not already present
    try {
        $pdo->exec("ALTER TABLE `granted_cash_advances` MODIFY COLUMN `status` enum('pending','liquidated','overdue','completed') NOT NULL DEFAULT 'pending'");
        echo "<p style='color: green;'>✓ Updated status enum to include 'completed'.</p>";
    } catch (PDOException $e) {
        echo "<p style='color: blue;'>ℹ Status enum already includes 'completed'.</p>";
    }

    // Add indexes to granted_cash_advances table
    $gca_indexes = [
        'idx_cheque_number' => 'CREATE INDEX `idx_cheque_number` ON `granted_cash_advances`(`cheque_number`)',
        'idx_voucher_number' => 'CREATE INDEX `idx_voucher_number` ON `granted_cash_advances`(`voucher_number`)',
        'idx_departure_date' => 'CREATE INDEX `idx_departure_date` ON `granted_cash_advances`(`departure_date`)',
        'idx_arrival_date' => 'CREATE INDEX `idx_arrival_date` ON `granted_cash_advances`(`arrival_date`)',
        'idx_date_completed' => 'CREATE INDEX `idx_date_completed` ON `granted_cash_advances`(`date_completed`)'
    ];

    foreach ($gca_indexes as $index_name => $sql) {
        try {
            $pdo->exec($sql);
            echo "<p style='color: green;'>✓ Created index {$index_name} on granted_cash_advances table.</p>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p style='color: blue;'>ℹ Index {$index_name} already exists on granted_cash_advances table.</p>";
            } else {
                echo "<p style='color: red;'>✗ Error creating index {$index_name}: " . $e->getMessage() . "</p>";
            }
        }
    }

    echo "<br><div style='background: #d1fae5; padding: 1rem; border-radius: 8px; border: 1px solid #10b981;'>";
    echo "<h3 style='color: #065f46; margin-top: 0;'>✅ Liquidation System Setup Complete!</h3>";
    echo "<p style='color: #065f46; margin-bottom: 0;'>The liquidation system has been successfully set up. You can now:</p>";
    echo "<ul style='color: #065f46;'>";
    echo "<li>Use the 'Liquidation Details' button in pending.php to process liquidations</li>";
    echo "<li>View liquidation history at liquidation_history.php</li>";
    echo "<li>Track first and second liquidations for each cash advance</li>";
    echo "<li>Monitor remaining balances and liquidation status</li>";
    echo "</ul>";
    echo "</div>";

} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}
?> 