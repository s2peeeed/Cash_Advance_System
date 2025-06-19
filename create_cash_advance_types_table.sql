-- Create cash_advance_types table
CREATE TABLE `cash_advance_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert some default types
INSERT INTO `cash_advance_types` (`type_name`, `description`) VALUES
('Payroll', 'Cash advance for payroll purposes'),
('Special Purposes', 'Cash advance for special purposes'),
('Travel', 'Cash advance for travel expenses'),
('Confidential Funds', 'Cash advance for confidential funds'); 