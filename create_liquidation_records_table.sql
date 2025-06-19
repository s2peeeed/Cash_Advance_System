-- Create liquidation_records table for tracking multiple liquidations per cash advance
CREATE TABLE `liquidation_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cash_advance_id` int(11) NOT NULL,
  `liquidation_number` int(11) NOT NULL DEFAULT 1 COMMENT '1 for first liquidation, 2 for second liquidation, etc.',
  `employee_id` varchar(50) DEFAULT NULL,
  `full_name` varchar(100) NOT NULL,
  `type` varchar(50) NOT NULL,
  `voucher_number` varchar(50) DEFAULT NULL,
  `cheque_number` varchar(50) DEFAULT NULL,
  `cash_advance_amount` decimal(10,2) NOT NULL,
  `amount_liquidated` decimal(10,2) NOT NULL,
  `remaining_balance` decimal(10,2) NOT NULL COMMENT 'Cash advance amount minus amount liquidated',
  `reference_number` varchar(50) DEFAULT NULL,
  `jev_number` varchar(50) DEFAULT NULL,
  `date_submitted` date NOT NULL,
  `submitted_by` varchar(100) DEFAULT NULL COMMENT 'Admin who parocessed the liquidation',
  `remarks` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_cash_advance_id` (`cash_advance_id`),
  KEY `idx_liquidation_number` (`liquidation_number`),
  KEY `idx_full_name` (`full_name`),
  KEY `idx_type` (`type`),
  KEY `idx_voucher_number` (`voucher_number`),
  KEY `idx_cheque_number` (`cheque_number`),
  KEY `idx_status` (`status`),
  KEY `idx_date_submitted` (`date_submitted`),
  CONSTRAINT `fk_liquidation_cash_advance` FOREIGN KEY (`cash_advance_id`) REFERENCES `granted_cash_advances` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Add indexes for better performance
CREATE INDEX `idx_liquidation_employee` ON `liquidation_records` (`employee_id`);
CREATE INDEX `idx_liquidation_composite` ON `liquidation_records` (`cash_advance_id`, `liquidation_number`);

-- Add comments for documentation
ALTER TABLE `liquidation_records` 
COMMENT = 'Stores liquidation records for cash advances, supporting multiple liquidations per cash advance';

-- Sample data (optional - for testing)
-- INSERT INTO `liquidation_records` (`cash_advance_id`, `liquidation_number`, `employee_id`, `full_name`, `type`, `voucher_number`, `cheque_number`, `cash_advance_amount`, `amount_liquidated`, `remaining_balance`, `reference_number`, `jev_number`, `date_submitted`, `submitted_by`, `status`) VALUES
-- (1, 1, '1110', 'John Valentine', 'payroll', 'V001', 'C001', 1000.00, 500.00, 500.00, 'REF001', 'JEV001', '2025-01-15', 'Admin User', 'pending'),
-- (1, 2, '1110', 'John Valentine', 'payroll', 'V002', 'C002', 1000.00, 500.00, 0.00, 'REF002', 'JEV002', '2025-01-20', 'Admin User', 'approved'); 