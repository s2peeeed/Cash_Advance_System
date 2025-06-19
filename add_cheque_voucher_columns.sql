-- Add cheque number and voucher number columns to granted_cash_advances table
ALTER TABLE granted_cash_advances 
ADD COLUMN cheque_number VARCHAR(50) NULL AFTER amount,
ADD COLUMN voucher_number VARCHAR(50) NULL AFTER cheque_number;

-- Add indexes for better performance
CREATE INDEX idx_cheque_number ON granted_cash_advances(cheque_number);
CREATE INDEX idx_voucher_number ON granted_cash_advances(voucher_number); 