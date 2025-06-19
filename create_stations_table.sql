-- Create stations table
CREATE TABLE IF NOT EXISTS stations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    station_name VARCHAR(255) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert some sample stations
INSERT INTO stations (station_name) VALUES 
('Main Office'),
('Branch Office 1'),
('Branch Office 2'),
('Field Station 1'),
('Field Station 2'),
('Satellite Office'),
('Regional Office'),
('District Office'),
('Municipal Office'),
('Barangay Office')
ON DUPLICATE KEY UPDATE station_name = station_name; 