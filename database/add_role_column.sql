-- Add role column to users table
ALTER TABLE users ADD COLUMN role ENUM('admin', 'user') DEFAULT 'user';

-- Update existing admin user
UPDATE users SET role = 'admin' WHERE email = 'admin@lgu.com';

-- Update other users to have 'user' role
UPDATE users SET role = 'user' WHERE email != 'admin@lgu.com'; 