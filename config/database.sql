-- Add reset token columns to users table
ALTER TABLE users
ADD COLUMN reset_token VARCHAR(64) NULL,
ADD COLUMN reset_token_expiry DATETIME NULL;

-- Add index for reset token
CREATE INDEX idx_reset_token ON users(reset_token); 