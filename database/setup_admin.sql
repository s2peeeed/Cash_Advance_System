-- First, ensure the role column exists
ALTER TABLE users ADD COLUMN IF NOT EXISTS role ENUM('admin', 'user') DEFAULT 'user';

-- Delete existing admin user if exists (to avoid duplicates)
DELETE FROM users WHERE email = 'admin@lgu.com';

-- Insert the admin user with admin role
INSERT INTO users (full_name, email, department, password, role) 
VALUES ('Admin User', 'admin@lgu.com', 'Administration', 'admin123', 'admin');

-- Verify the admin user was created
SELECT * FROM users WHERE role = 'admin'; 