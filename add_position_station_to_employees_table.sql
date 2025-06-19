-- Add position column to employees table
ALTER TABLE employees
ADD COLUMN position VARCHAR(255) NULL AFTER email;

-- Add station column to employees table
ALTER TABLE employees
ADD COLUMN station VARCHAR(255) NULL AFTER position;

-- Add position column to bonded_employees table
ALTER TABLE bonded_employees
ADD COLUMN position VARCHAR(255) NULL AFTER email;

-- Add station column to bonded_employees table
ALTER TABLE bonded_employees
ADD COLUMN station VARCHAR(255) NULL AFTER position; 