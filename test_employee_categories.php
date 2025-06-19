<?php
require_once 'config/database.php';

try {
    echo "<h2>Testing Employee Categories</h2>";
    
    // Test regular employees query
    echo "<h3>Regular Employees:</h3>";
    $stmt = $pdo->query("
        SELECT 
            e.user_id, 
            e.user_name, 
            e.email,
            CASE WHEN gca_pending.id IS NOT NULL THEN 'pending' ELSE 'clear' END as cash_advance_status,
            CASE 
                WHEN gca_any.id IS NOT NULL THEN 'regular'
                ELSE 'new_employee'
            END as employee_category,
            COALESCE(gca_count.cash_advance_count, 0) as cash_advance_count,
            COALESCE(gca_recent.date_granted, '1900-01-01') as last_cash_advance_date
        FROM employees e 
        LEFT JOIN granted_cash_advances gca_pending ON e.user_name = gca_pending.name 
            AND gca_pending.status IN ('pending', 'approved')
        LEFT JOIN granted_cash_advances gca_recent ON e.user_name = gca_recent.name 
            AND gca_recent.status = 'completed'
            AND gca_recent.date_completed >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        LEFT JOIN (
            SELECT name, COUNT(*) as cash_advance_count 
            FROM granted_cash_advances 
            WHERE status = 'completed' 
            GROUP BY name
        ) gca_count ON e.user_name = gca_count.name
        LEFT JOIN granted_cash_advances gca_any ON e.user_name = gca_any.name 
        WHERE e.status = 'active' 
        GROUP BY e.user_id, e.user_name, e.email
        ORDER BY 
            CASE 
                WHEN gca_any.id IS NOT NULL THEN 1
                ELSE 2
            END,
            gca_recent.date_completed DESC,
            e.user_name ASC
        LIMIT 10
    ");
    
    $regular_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Name</th><th>Email</th><th>Status</th><th>Category</th><th>Cash Advance Count</th><th>Last Cash Advance</th></tr>";
    foreach ($regular_employees as $emp) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($emp['user_name']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['email']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['cash_advance_status']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['employee_category']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['cash_advance_count']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['last_cash_advance_date']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test bonded employees query
    echo "<h3>Bonded Employees:</h3>";
    $stmt = $pdo->query("
        SELECT 
            be.bonded_id, 
            be.full_name, 
            be.email,
            CASE WHEN gca_pending.id IS NOT NULL THEN 'pending' ELSE 'clear' END as cash_advance_status,
            CASE 
                WHEN gca_any.id IS NOT NULL THEN 'regular'
                ELSE 'new_employee'
            END as employee_category,
            COALESCE(gca_count.cash_advance_count, 0) as cash_advance_count,
            COALESCE(gca_recent.date_granted, '1900-01-01') as last_cash_advance_date
        FROM bonded_employees be 
        LEFT JOIN granted_cash_advances gca_pending ON be.full_name = gca_pending.name 
            AND gca_pending.status IN ('pending', 'approved')
        LEFT JOIN granted_cash_advances gca_recent ON be.full_name = gca_recent.name 
            AND gca_recent.status = 'completed'
            AND gca_recent.date_completed >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        LEFT JOIN (
            SELECT name, COUNT(*) as cash_advance_count 
            FROM granted_cash_advances 
            WHERE status = 'completed' 
            GROUP BY name
        ) gca_count ON be.full_name = gca_count.name
        LEFT JOIN granted_cash_advances gca_any ON be.full_name = gca_any.name 
        GROUP BY be.bonded_id, be.full_name, be.email
        ORDER BY 
            CASE 
                WHEN gca_any.id IS NOT NULL THEN 1
                ELSE 2
            END,
            gca_recent.date_completed DESC,
            be.full_name ASC
        LIMIT 10
    ");
    
    $bonded_employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Name</th><th>Email</th><th>Status</th><th>Category</th><th>Cash Advance Count</th><th>Last Cash Advance</th></tr>";
    foreach ($bonded_employees as $emp) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($emp['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['email']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['cash_advance_status']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['employee_category']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['cash_advance_count']) . "</td>";
        echo "<td>" . htmlspecialchars($emp['last_cash_advance_date']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show category counts
    echo "<h3>Category Summary:</h3>";
    $stmt = $pdo->query("
        SELECT 
            'regular' as type,
            CASE 
                WHEN gca_any.id IS NOT NULL THEN 'regular'
                ELSE 'new_employee'
            END as category,
            COUNT(*) as count
        FROM employees e 
        LEFT JOIN granted_cash_advances gca_any ON e.user_name = gca_any.name 
        WHERE e.status = 'active' 
        GROUP BY category
        
        UNION ALL
        
        SELECT 
            'bonded' as type,
            CASE 
                WHEN gca_any.id IS NOT NULL THEN 'regular'
                ELSE 'new_employee'
            END as category,
            COUNT(*) as count
        FROM bonded_employees be 
        LEFT JOIN granted_cash_advances gca_any ON be.full_name = gca_any.name 
        GROUP BY category
        ORDER BY type, category
    ");
    
    $category_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Type</th><th>Category</th><th>Count</th></tr>";
    foreach ($category_counts as $count) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($count['type']) . "</td>";
        echo "<td>" . htmlspecialchars($count['category']) . "</td>";
        echo "<td>" . htmlspecialchars($count['count']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Show pending liquidations
    echo "<h3>Pending Liquidations:</h3>";
    $stmt = $pdo->query("
        SELECT 
            name,
            type,
            amount,
            date_granted,
            status
        FROM granted_cash_advances 
        WHERE status IN ('pending', 'approved')
        ORDER BY date_granted DESC
    ");
    
    $pending_liquidations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Name</th><th>Type</th><th>Amount</th><th>Date Granted</th><th>Status</th></tr>";
    foreach ($pending_liquidations as $pending) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($pending['name']) . "</td>";
        echo "<td>" . htmlspecialchars($pending['type']) . "</td>";
        echo "<td>₱" . number_format($pending['amount'], 2) . "</td>";
        echo "<td>" . htmlspecialchars($pending['date_granted']) . "</td>";
        echo "<td>" . htmlspecialchars($pending['status']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?> 