-- Create activity_log table
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    user_name VARCHAR(255) NOT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at),
    INDEX idx_table_record (table_name, record_id)
);

-- Insert some sample activity types
INSERT INTO activity_log (user_id, user_name, action, description, table_name, ip_address) VALUES
(1, 'System Admin', 'LOGIN', 'User logged in successfully', 'users', '127.0.0.1'),
(1, 'System Admin', 'LOGOUT', 'User logged out', 'users', '127.0.0.1'),
(1, 'System Admin', 'CREATE', 'Created new cash advance record', 'granted_cash_advances', '127.0.0.1'),
(1, 'System Admin', 'UPDATE', 'Updated cash advance status to completed', 'granted_cash_advances', '127.0.0.1'),
(1, 'System Admin', 'DELETE', 'Deleted cash advance record', 'granted_cash_advances', '127.0.0.1'),
(1, 'System Admin', 'SEND_EMAIL', 'Sent reminder email to employee', 'email_logs', '127.0.0.1'),
(1, 'System Admin', 'ADD_EMPLOYEE', 'Added new employee to system', 'employees', '127.0.0.1'),
(1, 'System Admin', 'ADD_BONDED_EMPLOYEE', 'Added new bonded employee', 'bonded_employees', '127.0.0.1'),
(1, 'System Admin', 'VIEW_REPORTS', 'Viewed system reports', 'reports', '127.0.0.1'),
(1, 'System Admin', 'UPDATE_SETTINGS', 'Updated system settings', 'settings', '127.0.0.1'); 