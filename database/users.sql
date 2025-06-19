-- Drop the existing users table if it exists
DROP TABLE IF EXISTS users;

-- Create the users table with all required columns
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    department VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert some sample users (passwords are 'password123' - you should change these in production)
INSERT INTO users (full_name, email, department, password) VALUES
('John Doe', 'john.doe@lgu.gov.ph', 'Finance', 'password123'),
('Jane Smith', 'jane.smith@lgu.gov.ph', 'HR', 'password123');

-- Insert admin user
INSERT INTO users (full_name, email, department, password, role) VALUES
('Admin User', 'admin@lgu.gov.ph', 'IT', 'admin123', 'admin'); 

SELECT *
FROM granted_cash_advances
WHERE status = 'pending' AND due_date < CURDATE(); 