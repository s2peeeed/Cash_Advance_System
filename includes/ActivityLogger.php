<?php

class ActivityLogger {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Log an activity
     */
    public function log($userId, $userName, $action, $description, $tableName = null, $recordId = null, $oldValues = null, $newValues = null) {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO activity_log 
                (user_id, user_name, action, description, table_name, record_id, old_values, new_values, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $ipAddress = $this->getClientIP();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
            $newValuesJson = $newValues ? json_encode($newValues) : null;
            
            return $stmt->execute([
                $userId,
                $userName,
                $action,
                $description,
                $tableName,
                $recordId,
                $oldValuesJson,
                $newValuesJson,
                $ipAddress,
                $userAgent
            ]);
        } catch (PDOException $e) {
            error_log("Activity logging failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log user login
     */
    public function logLogin($userId, $userName) {
        return $this->log($userId, $userName, 'LOGIN', 'User logged in successfully', 'users');
    }
    
    /**
     * Log user logout
     */
    public function logLogout($userId, $userName) {
        return $this->log($userId, $userName, 'LOGOUT', 'User logged out', 'users');
    }
    
    /**
     * Log cash advance creation
     */
    public function logCashAdvanceCreated($userId, $userName, $recordId, $data) {
        return $this->log(
            $userId, 
            $userName, 
            'CREATE', 
            "Created cash advance for {$data['name']} - ₱" . number_format($data['amount'], 2),
            'granted_cash_advances',
            $recordId,
            null,
            $data
        );
    }
    
    /**
     * Log cash advance update
     */
    public function logCashAdvanceUpdated($userId, $userName, $recordId, $oldData, $newData) {
        $changes = [];
        foreach ($newData as $key => $value) {
            if (isset($oldData[$key]) && $oldData[$key] !== $value) {
                $changes[$key] = ['old' => $oldData[$key], 'new' => $value];
            }
        }
        
        $description = "Updated cash advance for {$newData['name']}";
        if (isset($changes['status'])) {
            $description .= " - Status changed from {$changes['status']['old']} to {$changes['status']['new']}";
        }
        
        return $this->log(
            $userId,
            $userName,
            'UPDATE',
            $description,
            'granted_cash_advances',
            $recordId,
            $oldData,
            $newData
        );
    }
    
    /**
     * Log liquidation completion
     */
    public function logLiquidationCompleted($userId, $userName, $recordId, $employeeName) {
        return $this->log(
            $userId,
            $userName,
            'COMPLETE_LIQUIDATION',
            "Marked liquidation as completed for {$employeeName}",
            'granted_cash_advances',
            $recordId
        );
    }
    
    /**
     * Log email sent
     */
    public function logEmailSent($userId, $userName, $recipient, $type, $purpose) {
        return $this->log(
            $userId,
            $userName,
            'SEND_EMAIL',
            "Sent {$type} reminder email to {$recipient} for {$purpose}",
            'email_logs'
        );
    }
    
    /**
     * Log employee addition
     */
    public function logEmployeeAdded($userId, $userName, $employeeName, $type = 'regular') {
        return $this->log(
            $userId,
            $userName,
            'ADD_EMPLOYEE',
            "Added new {$type} employee: {$employeeName}",
            $type === 'bonded' ? 'bonded_employees' : 'employees'
        );
    }
    
    /**
     * Log report viewing
     */
    public function logReportViewed($userId, $userName, $reportType) {
        return $this->log(
            $userId,
            $userName,
            'VIEW_REPORT',
            "Viewed {$reportType} report",
            'reports'
        );
    }
    
    /**
     * Log settings update
     */
    public function logSettingsUpdated($userId, $userName, $setting, $oldValue, $newValue) {
        return $this->log(
            $userId,
            $userName,
            'UPDATE_SETTINGS',
            "Updated setting '{$setting}' from '{$oldValue}' to '{$newValue}'",
            'settings'
        );
    }
    
    /**
     * Get recent activities
     */
    public function getRecentActivities($limit = 50, $userId = null, $action = null) {
        try {
            $sql = "SELECT * FROM activity_log WHERE 1=1";
            $params = [];
            
            if ($userId) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            }
            
            if ($action) {
                $sql .= " AND action = ?";
                $params[] = $action;
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ?";
            $params[] = $limit;
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get recent activities: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get activities by date range
     */
    public function getActivitiesByDateRange($startDate, $endDate, $userId = null) {
        try {
            $sql = "SELECT * FROM activity_log WHERE created_at BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
            
            if ($userId) {
                $sql .= " AND user_id = ?";
                $params[] = $userId;
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get activities by date range: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get activity statistics
     */
    public function getActivityStats($days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT 
                    action,
                    COUNT(*) as count,
                    DATE(created_at) as date
                FROM activity_log 
                WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
                GROUP BY action, DATE(created_at)
                ORDER BY date DESC, count DESC
            ");
            
            $stmt->execute([$days]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Failed to get activity stats: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
?> 