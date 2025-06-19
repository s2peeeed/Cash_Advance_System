-- Create positions table
CREATE TABLE IF NOT EXISTS positions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position_name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some sample positions
INSERT INTO positions (position_name) VALUES 
('Department Head'),
('Staff'),
('Manager'),
('Supervisor'),
('Assistant'),
('Coordinator'),
('Director'),
('Officer'),
('Clerk'),
('Secretary')
ON DUPLICATE KEY UPDATE position_name = position_name; 