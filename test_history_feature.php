<?php
// Test script for History Feature
// This script helps verify that the database schema and functionality are working correctly

require_once 'config/database.php';

echo "<h1>History Feature Test</h1>";

try {
    // Test 1: Check if the table exists and has the correct structure
    echo "<h2>Test 1: Database Schema Check</h2>";
    
    $stmt = $pdo->query("DESCRIBE granted_cash_advances");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if date_completed column exists
    $has_date_completed = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'date_completed') {
            $has_date_completed = true;
            break;
        }
    }
    
    if (!$has_date_completed) {
        echo "<p style='color: red;'>❌ date_completed column is missing! Please run the migration script.</p>";
    } else {
        echo "<p style='color: green;'>✅ date_completed column exists!</p>";
    }
    
    // Test 2: Check current data
    echo "<h2>Test 2: Current Data Check</h2>";
    
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM granted_cash_advances GROUP BY status");
    $status_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Status</th><th>Count</th></tr>";
    foreach ($status_counts as $status) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($status['status']) . "</td>";
        echo "<td>" . htmlspecialchars($status['count']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 3: Check completed liquidations
    echo "<h2>Test 3: Completed Liquidations</h2>";
    
    $stmt = $pdo->query("SELECT * FROM granted_cash_advances WHERE status = 'completed' ORDER BY date_completed DESC LIMIT 5");
    $completed = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($completed)) {
        echo "<p style='color: orange;'>No completed liquidations found. This is normal if you haven't clicked 'Done' on any pending liquidations yet.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Name</th><th>Purpose</th><th>Amount</th><th>Type</th><th>Date Granted</th><th>Date Completed (Liquidation Date)</th><th>Duration (days)</th></tr>";
        foreach ($completed as $row) {
            $date_granted = new DateTime($row['date_granted']);
            $date_completed = new DateTime($row['date_completed']);
            $duration = $date_granted->diff($date_completed)->days;
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['purpose']) . "</td>";
            echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['date_granted']) . "</td>";
            echo "<td>" . htmlspecialchars($row['date_completed']) . "</td>";
            echo "<td>" . $duration . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 4: Check pending liquidations
    echo "<h2>Test 4: Pending Liquidations</h2>";
    
    $stmt = $pdo->query("SELECT * FROM granted_cash_advances WHERE status = 'pending' ORDER BY due_date ASC LIMIT 5");
    $pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($pending)) {
        echo "<p style='color: orange;'>No pending liquidations found.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>Name</th><th>Purpose</th><th>Amount</th><th>Type</th><th>Date Granted</th><th>Due Date</th><th>ID (for testing)</th></tr>";
        foreach ($pending as $row) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['purpose']) . "</td>";
            echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['date_granted']) . "</td>";
            echo "<td>" . htmlspecialchars($row['due_date']) . "</td>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 5: Statistics
    echo "<h2>Test 5: Statistics</h2>";
    
    $stmt = $pdo->query("SELECT 
        COUNT(*) as total_records,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_count,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_completed_amount
        FROM granted_cash_advances");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Metric</th><th>Value</th></tr>";
    echo "<tr><td>Total Records</td><td>" . $stats['total_records'] . "</td></tr>";
    echo "<tr><td>Pending Count</td><td>" . $stats['pending_count'] . "</td></tr>";
    echo "<tr><td>Completed Count</td><td>" . $stats['completed_count'] . "</td></tr>";
    echo "<tr><td>Overdue Count</td><td>" . $stats['overdue_count'] . "</td></tr>";
    echo "<tr><td>Total Completed Amount</td><td>₱" . number_format($stats['total_completed_amount'], 2) . "</td></tr>";
    echo "</table>";
    
    echo "<h2>Test Results</h2>";
    if ($has_date_completed) {
        echo "<p style='color: green;'>✅ All tests completed successfully!</p>";
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ol>";
        echo "<li>Go to <a href='pending.php'>pending.php</a> to see pending liquidations</li>";
        echo "<li>Click 'Done' on any pending liquidation to test the completion feature</li>";
        echo "<li>Go to <a href='history.php'>history.php</a> to see the completed liquidation in history</li>";
        echo "</ol>";
    } else {
        echo "<p style='color: red;'>❌ Database schema needs to be updated. Please run the migration script first.</p>";
    }
    
} catch (PDOException $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database connection and run the migration script first.</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #333; }
h2 { color: #666; margin-top: 30px; }
table { font-size: 14px; }
th { background-color: #f5f5f5; padding: 8px; }
td { padding: 6px 8px; }
</style> 