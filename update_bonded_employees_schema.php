<?php
// Script to update bonded_employees table with amount columns
require_once 'config/database.php';

try {
    // Add approximate_amount column
    $pdo->exec("ALTER TABLE bonded_employees ADD COLUMN IF NOT EXISTS approximate_amount DECIMAL(15,2) DEFAULT 0.00 AFTER ris_number");
    echo "✓ Added approximate_amount column<br>";
    
    // Add total_amount column
    $pdo->exec("ALTER TABLE bonded_employees ADD COLUMN IF NOT EXISTS total_amount DECIMAL(15,2) DEFAULT 0.00 AFTER approximate_amount");
    echo "✓ Added total_amount column<br>";
    
    // Add comments to document the columns
    $pdo->exec("ALTER TABLE bonded_employees MODIFY COLUMN approximate_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Approximate bond amount'");
    $pdo->exec("ALTER TABLE bonded_employees MODIFY COLUMN total_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total bond amount'");
    echo "✓ Added column comments<br>";
    
    echo "<br><strong>Database schema updated successfully!</strong><br>";
    echo "The bonded_employees table now includes approximate_amount and total_amount columns.<br>";
    echo "<a href='add_bonded_employee.php'>← Back to Bonded Employee Management</a>";
    
} catch (PDOException $e) {
    echo "Error updating database schema: " . $e->getMessage();
}
?> 