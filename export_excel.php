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
$filter_status = $_GET['status'] ?? '';

$where = [];
$params = [];
if ($filter_start) {
    $where[] = 'date_granted >= ?';
    $params[] = $filter_start;
}
if ($filter_end) {
    $where[] = 'date_granted <= ?';
    $params[] = $filter_end;
}
if ($filter_type) {
    $where[] = 'type = ?';
    $params[] = $filter_type;
}
if ($filter_status) {
    $where[] = 'status = ?';
    $params[] = $filter_status;
}
$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Fetch data
$stmt = $pdo->prepare("SELECT id, name, email, purpose, amount, cheque_number, voucher_number, type, status, date_granted, due_date, departure_date, arrival_date, event_name, event_date, date_completed, created_at FROM granted_cash_advances $where_sql ORDER BY date_granted DESC");
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Output headers for Excel (CSV)
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=granted_cash_advances_export_' . date('Ymd_His') . '.csv');

$output = fopen('php://output', 'w');
// Output column headers
fputcsv($output, array('ID', 'Name', 'Email', 'Purpose', 'Amount', 'Cheque Number', 'Voucher Number', 'Type', 'Status', 'Date Granted', 'Due Date', 'Departure Date', 'Arrival Date', 'Event Name', 'Event Date', 'Date Completed', 'Created At'));
// Output data rows
foreach ($rows as $row) {
    fputcsv($output, $row);
}
fclose($output);
exit; 