<?php
session_start();

// Strict admin-only access check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once 'config/database.php';

$type = $_GET['type'] ?? '';
$response = ['success' => false, 'data' => [], 'message' => ''];

try {
    switch ($type) {
        case 'total_granted':
            // Get all granted cash advances
            $stmt = $pdo->query("
                SELECT 
                    gca.id,
                    gca.name,
                    gca.email,
                    gca.purpose,
                    gca.amount,
                    gca.cheque_number,
                    gca.voucher_number,
                    gca.type,
                    gca.date_granted,
                    gca.due_date,
                    gca.status,
                    CASE 
                        WHEN be.full_name IS NOT NULL THEN 'Bonded'
                        ELSE 'Regular'
                    END as employee_type
                FROM granted_cash_advances gca
                LEFT JOIN bonded_employees be ON gca.name = be.full_name
                ORDER BY gca.date_granted DESC
            ");
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['title'] = 'All Granted Cash Advances';
            $response['success'] = true;
            break;

        case 'total_completed':
            // Get completed liquidations
            $stmt = $pdo->query("
                SELECT 
                    gca.id,
                    gca.name,
                    gca.email,
                    gca.purpose,
                    gca.amount,
                    gca.cheque_number,
                    gca.voucher_number,
                    gca.type,
                    gca.date_granted,
                    gca.due_date,
                    gca.date_completed,
                    gca.status,
                    CASE 
                        WHEN be.full_name IS NOT NULL THEN 'Bonded'
                        ELSE 'Regular'
                    END as employee_type
                FROM granted_cash_advances gca
                LEFT JOIN bonded_employees be ON gca.name = be.full_name
                WHERE gca.status = 'completed'
                ORDER BY gca.date_completed DESC
            ");
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['title'] = 'Completed Liquidations';
            $response['success'] = true;
            break;

        case 'pending_liquidations':
            // Get pending liquidations
            $stmt = $pdo->query("
                SELECT 
                    gca.id,
                    gca.name,
                    gca.email,
                    gca.purpose,
                    gca.amount,
                    gca.cheque_number,
                    gca.voucher_number,
                    gca.type,
                    gca.date_granted,
                    gca.due_date,
                    gca.status,
                    CASE 
                        WHEN be.full_name IS NOT NULL THEN 'Bonded'
                        ELSE 'Regular'
                    END as employee_type,
                    CASE 
                        WHEN gca.due_date < CURDATE() THEN 'Overdue'
                        WHEN gca.due_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'Due Soon'
                        ELSE 'On Track'
                    END as status_indicator
                FROM granted_cash_advances gca
                LEFT JOIN bonded_employees be ON gca.name = be.full_name
                WHERE gca.status = 'pending'
                ORDER BY gca.due_date ASC
            ");
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['title'] = 'Pending Liquidations';
            $response['success'] = true;
            break;

        case 'total_liquidated_amount':
            // Get completed liquidations with amounts
            $stmt = $pdo->query("
                SELECT 
                    gca.id,
                    gca.name,
                    gca.email,
                    gca.purpose,
                    gca.amount,
                    gca.cheque_number,
                    gca.voucher_number,
                    gca.type,
                    gca.date_granted,
                    gca.due_date,
                    gca.date_completed,
                    gca.status,
                    CASE 
                        WHEN be.full_name IS NOT NULL THEN 'Bonded'
                        ELSE 'Regular'
                    END as employee_type
                FROM granted_cash_advances gca
                LEFT JOIN bonded_employees be ON gca.name = be.full_name
                WHERE gca.status = 'completed'
                ORDER BY gca.amount DESC
            ");
            $response['data'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response['title'] = 'Liquidated Amount Records';
            $response['success'] = true;
            break;

        default:
            $response['message'] = 'Invalid type specified';
            break;
    }
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
}

header('Content-Type: application/json');
echo json_encode($response);
?> 