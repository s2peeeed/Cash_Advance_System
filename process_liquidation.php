<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php?error=admin_only");
    exit();
}

require_once 'config/database.php';
require_once 'includes/EmailSender.php';

$success = $error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cash_advance_id = $_POST['cash_advance_id'] ?? '';
    $liquidation_number = $_POST['liquidation_number'] ?? '';
    $employee_id = $_POST['employee_id'] ?? '';
    $full_name = $_POST['full_name'] ?? '';
    $type = $_POST['type'] ?? '';
    $voucher_number = $_POST['voucher_number'] ?? '';
    $cheque_number = $_POST['cheque_number'] ?? '';
    $cash_advance_amount = $_POST['cash_advance_amount'] ?? '';
    $amount_liquidated = $_POST['amount_liquidated'] ?? '';
    $reference_number = $_POST['reference_number'] ?? '';
    $jev_number = $_POST['jev_number'] ?? '';
    $date_submitted = $_POST['date_submitted'] ?? '';
    $remarks = $_POST['remarks'] ?? '';
    
    // Validation
    if (empty($cash_advance_id) || empty($full_name) || empty($type) || 
        empty($cash_advance_amount) || empty($amount_liquidated) || empty($date_submitted)) {
        $error = "Please fill in all required fields.";
    } elseif ($amount_liquidated <= 0) {
        $error = "Amount liquidated must be greater than 0.";
    } else {
        // Convert string amounts to numbers
        $cash_advance_amount = (float) preg_replace('/[^0-9.]/', '', $cash_advance_amount);
        $amount_liquidated = (float) $amount_liquidated;
        
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Get the original cash advance amount from database
            $stmt = $pdo->prepare("
                SELECT amount as original_amount
                FROM granted_cash_advances 
                WHERE id = ?
            ");
            $stmt->execute([$cash_advance_id]);
            $cash_advance_result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cash_advance_result) {
                throw new Exception("Cash advance not found");
            }
            
            $original_cash_advance_amount = (float) $cash_advance_result['original_amount'];
            
            // Get existing liquidations to calculate remaining balance
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(amount_liquidated), 0) as total_liquidated
                FROM liquidation_records 
                WHERE cash_advance_id = ?
            ");
            $stmt->execute([$cash_advance_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $total_liquidated = (float) $result['total_liquidated'];
            
            $remaining_balance = $original_cash_advance_amount - $total_liquidated;
            
            if ($amount_liquidated > $remaining_balance) {
                $error = "Amount liquidated cannot exceed remaining balance of ₱" . number_format($remaining_balance, 2);
            } else {
                // Insert liquidation record
                $stmt = $pdo->prepare("
                    INSERT INTO liquidation_records (
                        cash_advance_id, liquidation_number, employee_id, full_name, type,
                        voucher_number, cheque_number, cash_advance_amount, amount_liquidated,
                        remaining_balance, reference_number, jev_number, date_submitted,
                        submitted_by, remarks, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                
                $new_remaining_balance = $remaining_balance - $amount_liquidated;
                $submitted_by = $_SESSION['full_name'] ?? 'Admin';
                
                $stmt->execute([
                    $cash_advance_id, $liquidation_number, $employee_id, $full_name, $type,
                    $voucher_number, $cheque_number, $original_cash_advance_amount, $amount_liquidated,
                    $new_remaining_balance, $reference_number, $jev_number, $date_submitted,
                    $submitted_by, $remarks
                ]);
                
                $liquidation_id = $pdo->lastInsertId();
                
                // Update cash advance status if fully liquidated
                // Use small threshold (0.01) for floating point comparison to handle precision issues
                if ($new_remaining_balance <= 0.01) { 
                    $stmt = $pdo->prepare("
                        UPDATE granted_cash_advances 
                        SET status = 'completed', date_completed = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$date_submitted, $cash_advance_id]);
                    
                    // Also update the liquidation record status to approved when fully liquidated
                    $stmt = $pdo->prepare("
                        UPDATE liquidation_records 
                        SET status = 'approved' 
                        WHERE id = ?
                    ");
                    $stmt->execute([$liquidation_id]);
                }
                
                $pdo->commit();
                $success = "Liquidation processed successfully!";
            }
            
            // Fetch all needed info for the latest liquidation
            $stmt = $pdo->prepare("SELECT gca.amount as original_amount, gca.email, gca.name, lr.amount_liquidated, lr.remaining_balance, lr.reference_number, lr.jev_number
                FROM liquidation_records lr
                JOIN granted_cash_advances gca ON lr.cash_advance_id = gca.id
                WHERE lr.id = ?");
            $stmt->execute([$liquidation_id]);
            $info = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($info && !empty($info['email'])) {
                $subject = "Cash Advance Liquidation Update";
                $status = ($info['remaining_balance'] <= 0.01) ? 'Completed' : 'Pending';
                // Calculate total liquidated before this liquidation
                $stmt2 = $pdo->prepare("SELECT SUM(amount_liquidated) FROM liquidation_records WHERE cash_advance_id = ? AND id < ?");
                $stmt2->execute([$cash_advance_id, $liquidation_id]);
                $total_liquidated_before = floatval($stmt2->fetchColumn());
                $remaining_cash_advance_before = $info['original_amount'] - $total_liquidated_before;
                $total_liquidated_now = $total_liquidated_before + floatval($info['amount_liquidated']);
                $message = "<p>Dear {$info['name']},</p>"
                    . "<ul>"
                    . "<li><strong>Original Cash Advance:</strong> ₱" . number_format($info['original_amount'], 2) . "</li>"
                    . "<li><strong>Remaining Cash Advance Before This Liquidation:</strong> ₱" . number_format($remaining_cash_advance_before, 2) . "</li>"
                    . "<li><strong>Amount Liquidated This Submission:</strong> ₱" . number_format($info['amount_liquidated'], 2) . "</li>"
                    . "<li><strong>Total Amount Liquidated:</strong> ₱" . number_format($total_liquidated_now, 2) . "</li>"
                    . "<li><strong>Remaining Balance:</strong> ₱" . number_format($info['remaining_balance'], 2) . "</li>"
                    . "<li><strong>Status:</strong> $status</li>"
                    . "<li><strong>Reference Number:</strong> " . htmlspecialchars($info['reference_number']) . "</li>"
                    . "<li><strong>JEV Number:</strong> " . htmlspecialchars($info['jev_number']) . "</li>"
                    . "</ul>";
                if ($status === 'Completed') {
                    $message .= "<p>You have <b>successfully liquidated</b> your cash advance. Thank you for your prompt liquidation.</p>";
                } else {
                    $message .= "<p>Your liquidation has been recorded. Please liquidate the remaining balance as soon as possible.</p>";
                }
                $emailSender = new EmailSender();
                $emailSender->sendReminder($info['email'], $subject, $message);
            }
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Redirect back to dashboard.php?page=pending.php with success/error message
$redirect_url = "dashboard.php?page=pending.php";
if ($success) {
    $redirect_url .= "&success=" . urlencode($success);
} elseif ($error) {
    $redirect_url .= "&error=" . urlencode($error);
}

header("Location: " . $redirect_url);
exit();
?> 