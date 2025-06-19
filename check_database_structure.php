<?php
require_once 'config/database.php';

try {
    // Get the table structure
    $stmt = $pdo->query("DESCRIBE granted_cash_advances");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Current granted_cash_advances table structure:</h2>";
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
    
    // Check which columns we need
    $existing_columns = array_column($columns, 'Field');
    echo "<h3>Missing columns analysis:</h3>";
    
    $required_columns = ['departure_date', 'arrival_date', 'date_completed'];
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "<p style='color: green;'>✓ Column '$col' already exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Column '$col' is missing</p>";
        }
    }
    
    // Check status enum
    $status_column = array_filter($columns, function($col) { return $col['Field'] === 'status'; });
    if (!empty($status_column)) {
        $status_col = reset($status_column);
        echo "<p>Status column type: " . htmlspecialchars($status_col['Type']) . "</p>";
        if (strpos($status_col['Type'], 'completed') !== false) {
            echo "<p style='color: green;'>✓ Status enum includes 'completed'</p>";
        } else {
            echo "<p style='color: red;'>✗ Status enum needs to be updated to include 'completed'</p>";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?> 