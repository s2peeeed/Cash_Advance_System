<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once 'config/database.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_cash_advance_details':
            $cash_advance_id = $_POST['cash_advance_id'] ?? '';
            
            if (empty($cash_advance_id)) {
                echo json_encode(['error' => 'Cash advance ID is required']);
                exit();
            }
            
            try {
                $stmt = $pdo->prepare("
                    SELECT 
                        gca.id,
                        gca.name as full_name,
                        gca.email,
                        gca.purpose,
                        gca.amount as cash_advance_amount,
                        gca.type,
                        gca.voucher_number,
                        gca.cheque_number,
                        gca.date_granted,
                        gca.due_date,
                        gca.status,
                        COALESCE(e.user_id, be.bonded_id) as employee_id
                    FROM granted_cash_advances gca
                    LEFT JOIN employees e ON gca.name = e.user_name
                    LEFT JOIN bonded_employees be ON gca.name = be.full_name
                    WHERE gca.id = ?
                ");
                $stmt->execute([$cash_advance_id]);
                $cash_advance = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$cash_advance) {
                    echo json_encode(['error' => 'Cash advance not found']);
                    exit();
                }
                
                // Get existing liquidations for this cash advance
                $stmt = $pdo->prepare("
                    SELECT 
                        lr.liquidation_number,
                        lr.amount_liquidated,
                        lr.remaining_balance,
                        lr.date_submitted,
                        lr.reference_number,
                        lr.jev_number,
                        CASE 
                            WHEN gca.status = 'completed' THEN 'approved'
                            ELSE lr.status
                        END as status
                    FROM liquidation_records lr
                    JOIN granted_cash_advances gca ON lr.cash_advance_id = gca.id
                    WHERE lr.cash_advance_id = ? 
                    ORDER BY lr.liquidation_number ASC
                ");
                $stmt->execute([$cash_advance_id]);
                $existing_liquidations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate next liquidation number
                $next_liquidation_number = 1;
                if (!empty($existing_liquidations)) {
                    $max_liquidation_number = 0;
                    foreach ($existing_liquidations as $liquidation) {
                        if ($liquidation['liquidation_number'] > $max_liquidation_number) {
                            $max_liquidation_number = $liquidation['liquidation_number'];
                        }
                    }
                    $next_liquidation_number = $max_liquidation_number + 1;
                }
                
                // Calculate remaining balance
                $total_liquidated = 0;
                foreach ($existing_liquidations as $liquidation) {
                    $total_liquidated += $liquidation['amount_liquidated'];
                }
                $remaining_balance = $cash_advance['cash_advance_amount'] - $total_liquidated;
                
                $response = [
                    'success' => true,
                    'cash_advance' => $cash_advance,
                    'existing_liquidations' => $existing_liquidations,
                    'next_liquidation_number' => $next_liquidation_number,
                    'total_liquidated' => $total_liquidated,
                    'remaining_balance' => $remaining_balance
                ];
                
                echo json_encode($response);
                
            } catch (PDOException $e) {
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            break;
            
        case 'process_liquidation':
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
                echo json_encode(['error' => 'Please fill in all required fields']);
                exit();
            }
            
            if ($amount_liquidated <= 0) {
                echo json_encode(['error' => 'Amount liquidated must be greater than 0']);
                exit();
            }
            
            try {
                // Start transaction
                $pdo->beginTransaction();
                
                // Get existing liquidations to calculate remaining balance
                $stmt = $pdo->prepare("
                    SELECT COALESCE(SUM(amount_liquidated), 0) as total_liquidated
                    FROM liquidation_records 
                    WHERE cash_advance_id = ?
                ");
                $stmt->execute([$cash_advance_id]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $total_liquidated = $result['total_liquidated'];
                
                $remaining_balance = $cash_advance_amount - $total_liquidated;
                
                if ($amount_liquidated > $remaining_balance) {
                    echo json_encode(['error' => 'Amount liquidated cannot exceed remaining balance of ₱' . number_format($remaining_balance, 2)]);
                    exit();
                }
                
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
                    $voucher_number, $cheque_number, $cash_advance_amount, $amount_liquidated,
                    $new_remaining_balance, $reference_number, $jev_number, $date_submitted,
                    $submitted_by, $remarks
                ]);
                
                $liquidation_id = $pdo->lastInsertId();
                
                // Update cash advance status if fully liquidated
                if ($new_remaining_balance <= 0) {
                    $stmt = $pdo->prepare("
                        UPDATE granted_cash_advances 
                        SET status = 'completed', date_completed = ? 
                        WHERE id = ?
                    ");
                    $stmt->execute([$date_submitted, $cash_advance_id]);
                }
                
                $pdo->commit();
                
                $response = [
                    'success' => true,
                    'message' => 'Liquidation processed successfully!',
                    'liquidation_id' => $liquidation_id,
                    'remaining_balance' => $new_remaining_balance,
                    'is_fully_liquidated' => $new_remaining_balance <= 0
                ];
                
                echo json_encode($response);
                
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
            }
            break;
            
        default:
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_liquidation_details') {
        $cash_advance_id = $_GET['cash_advance_id'] ?? '';
        
        if (empty($cash_advance_id)) {
            echo '<div class="alert alert-danger">Cash advance ID is required</div>';
            exit();
        }
        
        try {
            // Get cash advance details
            $stmt = $pdo->prepare("
                SELECT 
                    gca.id,
                    gca.name as full_name,
                    gca.email,
                    gca.purpose,
                    gca.amount as original_amount,
                    gca.type,
                    gca.voucher_number,
                    gca.cheque_number,
                    gca.date_granted,
                    gca.due_date,
                    gca.status
                FROM granted_cash_advances gca
                WHERE gca.id = ?
            ");
            $stmt->execute([$cash_advance_id]);
            $cash_advance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cash_advance) {
                echo '<div class="alert alert-danger">Cash advance not found</div>';
                exit();
            }
            
            // Get liquidation records
            $stmt = $pdo->prepare("
                SELECT 
                    lr.liquidation_number,
                    lr.amount_liquidated,
                    lr.remaining_balance,
                    lr.date_submitted,
                    lr.reference_number,
                    lr.jev_number,
                    CASE 
                        WHEN gca.status = 'completed' THEN 'approved'
                        ELSE lr.status
                    END as status,
                    lr.remarks,
                    lr.submitted_by
                FROM liquidation_records lr
                JOIN granted_cash_advances gca ON lr.cash_advance_id = gca.id
                WHERE lr.cash_advance_id = ? 
                ORDER BY lr.liquidation_number ASC
            ");
            $stmt->execute([$cash_advance_id]);
            $liquidations = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Calculate totals
            $total_liquidated = 0;
            foreach ($liquidations as $liquidation) {
                $total_liquidated += $liquidation['amount_liquidated'];
            }
            $remaining_balance = $cash_advance['original_amount'] - $total_liquidated;
            
            // Display the liquidation details
            ?>
            <div class="liquidation-details-container">
                <div class="row mb-3">
                    <div class="col-12">
                        <h6 class="text-primary mb-2">
                            <i class="fas fa-user"></i> Employee Information
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Name:</strong> <?php echo htmlspecialchars($cash_advance['full_name']); ?><br>
                                <strong>Email:</strong> <?php echo htmlspecialchars($cash_advance['email']); ?><br>
                                <strong>Type:</strong> <?php echo htmlspecialchars($cash_advance['type']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Purpose:</strong> <?php echo htmlspecialchars($cash_advance['purpose']); ?><br>
                                <strong>Voucher #:</strong> <?php echo htmlspecialchars($cash_advance['voucher_number'] ?? '-'); ?><br>
                                <strong>Cheque #:</strong> <?php echo htmlspecialchars($cash_advance['cheque_number'] ?? '-'); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-12">
                        <h6 class="text-success mb-2">
                            <i class="fas fa-money-bill-wave"></i> Amount Summary
                        </h6>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-muted">Original Amount</h6>
                                        <h4 class="text-primary mb-0">₱<?php echo number_format($cash_advance['original_amount'], 2); ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-muted">Total Liquidated</h6>
                                        <h4 class="text-success mb-0">₱<?php echo number_format($total_liquidated, 2); ?></h4>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body text-center">
                                        <h6 class="card-title text-muted">Remaining Balance</h6>
                                        <h4 class="text-warning mb-0">₱<?php echo number_format($remaining_balance, 2); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($liquidations)): ?>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-info mb-2">
                                <i class="fas fa-list-alt"></i> Liquidation Records
                            </h6>
                            <?php foreach ($liquidations as $liquidation): ?>
                                <div class="card mb-3 <?php echo $liquidation['liquidation_number'] == 1 ? 'border-success' : 'border-warning'; ?> liquidation-card" 
                                     onclick="showLiquidationDetails(<?php echo htmlspecialchars(json_encode($liquidation)); ?>, <?php echo $liquidation['liquidation_number']; ?>)"
                                     style="cursor: pointer; transition: all 0.3s ease;">
                                    <div class="card-header <?php echo $liquidation['liquidation_number'] == 1 ? 'bg-success' : 'bg-warning'; ?> text-white">
                                        <h6 class="mb-0">
                                            <i class="fas <?php echo $liquidation['liquidation_number'] == 1 ? 'fa-1' : 'fa-2'; ?>"></i>
                                            <?php echo $liquidation['liquidation_number'] == 1 ? 'First' : 'Second'; ?> Liquidation
                                            <small class="float-end"><i class="fas fa-chevron-right"></i></small>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <strong>Amount Liquidated:</strong> ₱<?php echo number_format($liquidation['amount_liquidated'], 2); ?><br>
                                                <strong>Remaining Balance:</strong> ₱<?php echo number_format($liquidation['remaining_balance'], 2); ?><br>
                                                <strong>Date Submitted:</strong> <?php echo date('M d, Y', strtotime($liquidation['date_submitted'])); ?><br>
                                                <strong>Status:</strong> 
                                                <span class="badge <?php echo $liquidation['status'] === 'pending' ? 'bg-warning' : ($liquidation['status'] === 'approved' ? 'bg-success' : 'bg-danger'); ?>">
                                                    <?php echo ucfirst($liquidation['status']); ?>
                                                </span>
                                            </div>
                                            <div class="col-md-6">
                                                <strong>Reference Number:</strong> <?php echo htmlspecialchars($liquidation['reference_number'] ?? '-'); ?><br>
                                                <strong>JEV Number:</strong> <?php echo htmlspecialchars($liquidation['jev_number'] ?? '-'); ?><br>
                                                <strong>Submitted By:</strong> <?php echo htmlspecialchars($liquidation['submitted_by'] ?? 'Admin'); ?><br>
                                                <?php if (!empty($liquidation['remarks'])): ?>
                                                    <strong>Remarks:</strong> <?php echo htmlspecialchars($liquidation['remarks']); ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No liquidation records found for this cash advance.
                    </div>
                <?php endif; ?>
            </div>
            <?php
            
        } catch (PDOException $e) {
            echo '<div class="alert alert-danger">Database error: ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
    }
}
?> 