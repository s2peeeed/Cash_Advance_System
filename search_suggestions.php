<?php
require_once 'config/database.php';

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$suggestions = [];

if ($search !== '') {
    $query = "SELECT name, email, type FROM granted_cash_advances WHERE status = 'pending' AND (name LIKE :search OR email LIKE :search OR type LIKE :search) LIMIT 10";
    $stmt = $pdo->prepare($query);
    $searchParam = "%$search%";
    $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    $stmt->execute();
    $suggestions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

header('Content-Type: application/json');
echo json_encode($suggestions); 