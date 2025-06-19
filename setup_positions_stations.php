<?php
require_once 'config/database.php';

try {
    echo "<h2>Setting up Positions and Stations Tables</h2>";
    
    // Create positions table
    echo "<h3>Creating positions table...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS positions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            position_name VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Positions table created successfully!<br>";
    
    // Insert sample positions
    echo "<h3>Inserting sample positions...</h3>";
    $positions = [
        'Department Head',
        'Staff',
        'Manager',
        'Supervisor',
        'Assistant',
        'Coordinator',
        'Director',
        'Officer',
        'Clerk',
        'Secretary'
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO positions (position_name) VALUES (?)");
    foreach ($positions as $position) {
        $stmt->execute([$position]);
    }
    echo "✅ Sample positions inserted successfully!<br>";
    
    // Create stations table
    echo "<h3>Creating stations table...</h3>";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS stations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            station_name VARCHAR(255) NOT NULL UNIQUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    echo "✅ Stations table created successfully!<br>";
    
    // Insert sample stations
    echo "<h3>Inserting sample stations...</h3>";
    $stations = [
        'Main Office',
        'Branch Office 1',
        'Branch Office 2',
        'Field Station 1',
        'Field Station 2',
        'Satellite Office',
        'Regional Office',
        'District Office',
        'Municipal Office',
        'Barangay Office'
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO stations (station_name) VALUES (?)");
    foreach ($stations as $station) {
        $stmt->execute([$station]);
    }
    echo "✅ Sample stations inserted successfully!<br>";
    
    // Show current data
    echo "<h3>Current Positions:</h3>";
    $stmt = $pdo->query("SELECT * FROM positions ORDER BY position_name");
    $positions_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Position Name</th><th>Created At</th></tr>";
    foreach ($positions_data as $pos) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($pos['id']) . "</td>";
        echo "<td>" . htmlspecialchars($pos['position_name']) . "</td>";
        echo "<td>" . htmlspecialchars($pos['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>Current Stations:</h3>";
    $stmt = $pdo->query("SELECT * FROM stations ORDER BY station_name");
    $stations_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Station Name</th><th>Created At</th></tr>";
    foreach ($stations_data as $station) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($station['id']) . "</td>";
        echo "<td>" . htmlspecialchars($station['station_name']) . "</td>";
        echo "<td>" . htmlspecialchars($station['created_at']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>✅ Setup completed successfully!</h3>";
    echo "<p><a href='dashboard.php'>Back to Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
}
?> 