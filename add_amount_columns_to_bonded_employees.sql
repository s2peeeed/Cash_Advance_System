-- Add approximate_amount and total_amount columns to bonded_employees table
-- This script adds the missing columns for bond amounts

USE lgu;

-- Add approximate_amount column
ALTER TABLE bonded_employees 
ADD COLUMN approximate_amount DECIMAL(15,2) DEFAULT 0.00 AFTER ris_number;

-- Add total_amount column  
ALTER TABLE bonded_employees 
ADD COLUMN total_amount DECIMAL(15,2) DEFAULT 0.00 AFTER approximate_amount;

-- Add comments to document the columns
ALTER TABLE bonded_employees 
MODIFY COLUMN approximate_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Approximate bond amount';

ALTER TABLE bonded_employees 
MODIFY COLUMN total_amount DECIMAL(15,2) DEFAULT 0.00 COMMENT 'Total bond amount';

-- Verify the changes
DESCRIBE bonded_employees; 