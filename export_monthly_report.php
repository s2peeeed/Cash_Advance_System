<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php?error=admin_only');
    exit();
}
require_once 'config/database.php';

// Get filters from GET
$filter_start = $_GET['start_date'] ?? '';
$filter_end = $_GET['end_date'] ?? '';
$filter_type = $_GET['type'] ?? '';
$filter_status = $_GET['status'] ?? 'completed'; // Default to completed for monthly report

$where = ["status = 'completed'"];
$params = [];
if ($filter_start) {
    $where[] = 'date_completed >= ?';
    $params[] = $filter_start;
}
if ($filter_end) {
    $where[] = 'date_completed <= ?';
    $params[] = $filter_end;
}
if ($filter_type) {
    $where[] = 'type = ?';
    $params[] = $filter_type;
}
if ($filter_status && $filter_status !== 'completed') {
    $where[] = 'status = ?';
    $params[] = $filter_status;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Fetch monthly summary
$stmt = $pdo->prepare("SELECT DATE_FORMAT(date_completed, '%Y-%m') as month, COUNT(*) as count, SUM(amount) as total_amount, AVG(amount) as avg_amount FROM granted_cash_advances $where_sql AND date_completed IS NOT NULL GROUP BY DATE_FORMAT(date_completed, '%Y-%m') ORDER BY month DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output headers for Excel (CSV)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=monthly_cash_advance_report_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');
fputcsv($output, array('Month', 'Completed Count', 'Total Amount', 'Average Amount'));
foreach ($rows as $row) {
    fputcsv($output, [
        date('F Y', strtotime($row['month'] . '-01')),
        $row['count'],
        number_format($row['total_amount'], 2),
        number_format($row['avg_amount'], 2)
    ]);
}
fclose($output);
exit; 