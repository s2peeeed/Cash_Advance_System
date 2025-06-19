-- Database Migration Script for History Feature
-- This script updates the granted_cash_advances table to support completed status

-- Update the status enum to include 'completed'
ALTER TABLE `granted_cash_advances` 
MODIFY COLUMN `status` enum('pending','liquidated','overdue','completed') NOT NULL DEFAULT 'pending';

-- Add date_completed column if it doesn't exist
ALTER TABLE `granted_cash_advances` 
ADD COLUMN `date_completed` date DEFAULT NULL AFTER `due_date`;

-- Update existing 'liquidated' status to 'completed' for consistency
UPDATE `granted_cash_advances` 
SET `status` = 'completed' 
WHERE `status` = 'liquidated';

-- Add index for better performance on status queries
ALTER TABLE `granted_cash_advances` 
ADD INDEX `idx_status` (`status`),
ADD INDEX `idx_date_completed` (`date_completed`);

-- Add index for better performance on date_granted queries
ALTER TABLE `granted_cash_advances` 
ADD INDEX `idx_date_granted` (`date_granted`); 