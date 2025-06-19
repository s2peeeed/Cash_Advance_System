-- Create bonded_employees table
CREATE TABLE `bonded_employees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bonded_id` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `ris_number` varchar(50) DEFAULT NULL,
  `date_of_bond` date NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('active','inactive','completed') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `bonded_id` (`bonded_id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci; 