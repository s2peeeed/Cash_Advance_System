-- Add missing columns to granted_cash_advances table
-- These columns are needed for the enhanced cash advance functionality

-- Add departure_date column
ALTER TABLE `granted_cash_advances` 
ADD COLUMN `departure_date` date DEFAULT NULL AFTER `due_date`;

-- Add arrival_date column
ALTER TABLE `granted_cash_advances` 
ADD COLUMN `arrival_date` date DEFAULT NULL AFTER `departure_date`;

-- Add indexes for better performance on date queries
ALTER TABLE `granted_cash_advances` 
ADD INDEX `idx_departure_date` (`departure_date`),
ADD INDEX `idx_arrival_date` (`arrival_date`);

-- Update the status enum to include 'completed' if not already present
ALTER TABLE `granted_cash_advances` 
MODIFY COLUMN `status` enum('pending','liquidated','overdue','completed') NOT NULL DEFAULT 'pending';

-- Add date_completed column if it doesn't exist
ALTER TABLE `granted_cash_advances` 
ADD COLUMN `date_completed` date DEFAULT NULL AFTER `arrival_date`;

-- Add index for date_completed
ALTER TABLE `granted_cash_advances` 
ADD INDEX `idx_date_completed` (`date_completed`); 