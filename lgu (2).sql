-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 10, 2025 at 10:53 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lgu`
--

-- --------------------------------------------------------

--
-- Table structure for table `cash_advances`
--

CREATE TABLE `cash_advances` (
  `ca_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `purpose` text DEFAULT NULL,
  `released_date` date NOT NULL,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `cash_advance_requests`
--

CREATE TABLE `cash_advance_requests` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `purpose` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` enum('payroll','special_purposes','travel','confidential_funds') NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `date_requested` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `id` int(11) NOT NULL,
  `user_id` varchar(50) NOT NULL,
  `user_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `type` enum('payroll','special_purposes','travel','confidential_funds') NOT NULL,
  `status` enum('active','inactive') NOT NULL DEFAULT 'active',
  `date_added` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`id`, `user_id`, `user_name`, `email`, `type`, `status`, `date_added`, `created_at`) VALUES
(1, '1110', 'John Valentine', 's2peed3@gmail.com', 'payroll', 'active', '2025-06-10', '2025-06-10 08:42:06'),
(2, '1111', 'Ronneth Porrras', 'ronneth.porras@gmail.com', 'travel', 'active', '2025-06-10', '2025-06-10 08:45:46');

-- --------------------------------------------------------

--
-- Table structure for table `granted_cash_advances`
--

CREATE TABLE `granted_cash_advances` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `purpose` text NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `type` varchar(50) NOT NULL,
  `status` enum('pending','liquidated','overdue') NOT NULL DEFAULT 'pending',
  `date_granted` date NOT NULL,
  `due_date` date NOT NULL,
  `date_liquidated` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `granted_cash_advances`
--

INSERT INTO `granted_cash_advances` (`id`, `name`, `email`, `purpose`, `amount`, `type`, `status`, `date_granted`, `due_date`, `date_liquidated`, `created_at`) VALUES
(1, 'Ronneth Porrras', 'ronneth.porras@gmail.com', 'tagay', 100.00, 'travel', 'pending', '2025-06-10', '2025-07-10', NULL, '2025-06-10 08:47:50'),
(2, 'John Valentine', 's2peed3@gmail.com', 'ahsgjdfjas', 1000.00, 'payroll', 'pending', '2025-06-10', '2025-06-15', NULL, '2025-06-10 08:50:09');

-- --------------------------------------------------------

--
-- Table structure for table `liquidations`
--

CREATE TABLE `liquidations` (
  `liquidation_id` int(11) NOT NULL,
  `ca_id` int(11) NOT NULL,
  `due_date` date NOT NULL,
  `submitted_date` date DEFAULT NULL,
  `status` enum('not_submitted','submitted','reviewed','overdue') DEFAULT 'not_submitted',
  `remarks` text DEFAULT NULL,
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_date` date DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `role` enum('admin','user') DEFAULT 'user'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `full_name`, `email`, `department`, `password`, `created_at`, `role`) VALUES
(1, 'ronneth', 'ronneth@gmail.com', 'ojt', 'ronneth', '2025-06-10 10:37:56', 'user'),
(2, 'JOHN VALENTINE A. ESTRADA', 'johnvalentineestrada@gmail.com', 'MARKETING SALES', 'JOHNJOHN', '2025-06-10 10:48:20', 'user'),
(3, 'Admin User', 'admin@lgu.com', 'Administration', 'admin123', '2025-06-10 11:08:32', 'admin'),
(4, 'Rj Calderon Canamocan', 's2peed3@gmail.com', 'Not Specified', 'default123', '2025-06-10 16:01:18', 'user');

-- --------------------------------------------------------

--
-- Stand-in structure for view `view_liquidation_reminders`
-- (See below for the actual view)
--
CREATE TABLE `view_liquidation_reminders` (
`liquidation_id` int(11)
,`full_name` varchar(100)
,`email` varchar(100)
,`due_date` date
,`status` enum('not_submitted','submitted','reviewed','overdue')
,`ca_id` int(11)
);

-- --------------------------------------------------------

--
-- Structure for view `view_liquidation_reminders`
--
DROP TABLE IF EXISTS `view_liquidation_reminders`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `view_liquidation_reminders`  AS SELECT `l`.`liquidation_id` AS `liquidation_id`, `u`.`full_name` AS `full_name`, `u`.`email` AS `email`, `l`.`due_date` AS `due_date`, `l`.`status` AS `status`, `ca`.`ca_id` AS `ca_id` FROM ((`liquidations` `l` join `cash_advances` `ca` on(`l`.`ca_id` = `ca`.`ca_id`)) join `users` `u` on(`ca`.`user_id` = `u`.`user_id`)) WHERE `l`.`status` = 'not_submitted' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cash_advances`
--
ALTER TABLE `cash_advances`
  ADD PRIMARY KEY (`ca_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cash_advance_requests`
--
ALTER TABLE `cash_advance_requests`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `granted_cash_advances`
--
ALTER TABLE `granted_cash_advances`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `liquidations`
--
ALTER TABLE `liquidations`
  ADD PRIMARY KEY (`liquidation_id`),
  ADD KEY `ca_id` (`ca_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `cash_advances`
--
ALTER TABLE `cash_advances`
  MODIFY `ca_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `cash_advance_requests`
--
ALTER TABLE `cash_advance_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `granted_cash_advances`
--
ALTER TABLE `granted_cash_advances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `liquidations`
--
ALTER TABLE `liquidations`
  MODIFY `liquidation_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cash_advances`
--
ALTER TABLE `cash_advances`
  ADD CONSTRAINT `cash_advances_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Constraints for table `liquidations`
--
ALTER TABLE `liquidations`
  ADD CONSTRAINT `liquidations_ibfk_1` FOREIGN KEY (`ca_id`) REFERENCES `cash_advances` (`ca_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
