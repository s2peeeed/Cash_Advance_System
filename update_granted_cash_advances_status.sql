-- Update granted_cash_advances table to support 'completed' status
-- This script modifies the status enum to include 'completed'

-- First, let's check if the table needs to be updated
-- The original enum only allows: 'pending','liquidated','overdue'
-- We need to add 'completed' to the enum

-- Update the status enum to include 'completed'
ALTER TABLE `granted_cash_advances` 
MODIFY COLUMN `status` enum('pending','liquidated','overdue','completed') NOT NULL DEFAULT 'pending';

-- Add date_completed column if it doesn't exist
ALTER TABLE `granted_cash_advances` 
ADD COLUMN `date_completed` date DEFAULT NULL AFTER `due_date`;

-- Add index for date_completed for better performance
CREATE INDEX `idx_date_completed` ON `granted_cash_advances` (`date_completed`);

-- Add index for status for better performance
CREATE INDEX `idx_status` ON `granted_cash_advances` (`status`);

-- Verify the changes
DESCRIBE `granted_cash_advances`; 