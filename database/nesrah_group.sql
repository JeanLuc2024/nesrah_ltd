-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 06, 2025 at 03:56 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `nesrah_group`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `check_in` timestamp NULL DEFAULT NULL,
  `check_out` timestamp NULL DEFAULT NULL,
  `work_date` date NOT NULL,
  `total_hours` decimal(4,2) DEFAULT 0.00,
  `status` enum('present','absent','late') DEFAULT 'present',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`id`, `user_id`, `check_in`, `check_out`, `work_date`, `total_hours`, `status`, `notes`, `created_at`) VALUES
(1, 2, '2025-09-21 18:23:09', NULL, '2025-09-21', 0.00, 'present', NULL, '2025-09-21 18:23:09'),
(2, 2, '2025-09-22 09:52:08', '2025-09-22 09:52:12', '2025-09-22', 0.00, 'present', NULL, '2025-09-22 09:52:08'),
(3, 3, '2025-09-23 09:46:08', '2025-09-23 19:04:58', '2025-09-23', 9.31, 'present', NULL, '2025-09-23 09:46:08'),
(4, 3, '2025-09-26 07:31:42', NULL, '2025-09-26', 0.00, 'present', NULL, '2025-09-26 07:31:42'),
(5, 5, '2025-09-26 10:24:57', NULL, '2025-09-26', 0.00, 'present', NULL, '2025-09-26 10:24:57'),
(6, 5, '2025-10-06 07:26:22', '2025-10-06 07:26:26', '2025-10-06', 0.00, 'present', NULL, '2025-10-06 07:26:22');

-- --------------------------------------------------------

--
-- Table structure for table `employee_communications`
--

CREATE TABLE `employee_communications` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `item_code` varchar(50) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(50) DEFAULT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `current_stock` int(11) DEFAULT 0,
  `reorder_level` int(11) DEFAULT 10,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `item_code`, `item_name`, `description`, `category`, `unit_price`, `current_stock`, `reorder_level`, `created_at`, `updated_at`) VALUES
(1, 'ITM001', 'Office Chair', 'Ergonomic office chair with lumbar support', 'Furniture', 299.99, 50, 10, '2025-09-21 13:20:52', '2025-09-21 13:20:52'),
(2, 'ITM002', 'Laptop Computer', 'Business laptop with 8GB RAM, 256GB SSD', 'Electronics', 899.99, 25, 5, '2025-09-21 13:20:52', '2025-09-21 13:20:52'),
(3, 'ITM003', 'Desk Lamp', 'LED desk lamp with adjustable brightness', 'Office Supplies', 49.99, 100, 20, '2025-09-21 13:20:52', '2025-09-21 13:20:52'),
(4, 'ITM004', 'Notebook', 'A4 size spiral bound notebook', 'Stationery', 4.99, 200, 50, '2025-09-21 13:20:52', '2025-09-21 13:20:52'),
(5, 'ITM005', 'Pen Set', 'Set of 5 ballpoint pens', 'Stationery', 12.99, 150, 30, '2025-09-21 13:20:52', '2025-09-21 13:20:52'),
(6, 'ITM8409', 'Rice', 'Pakistan rice', 'Food', 1000.00, 500, 0, '2025-09-21 18:00:45', '2025-09-21 18:00:45'),
(7, 'ITM3821', 'ibirayi', 'fhsdhfsdakfsdajghjfas', 'Ibikoreshoooo', 5000.00, 1820, 10, '2025-09-22 09:55:47', '2025-09-23 09:55:03'),
(8, 'ITM1488', 'dfsdfsdfs', 'jrnertnerjterter', 'hhhhhhh', 1000.00, 10, 2, '2025-09-23 08:35:58', '2025-09-23 09:57:38'),
(9, 'ITM4048', 'Ibiraha', 'Ibiraha birimo urusenda', 'Food', 100.00, 360, 30, '2025-09-26 07:14:37', '2025-09-26 07:17:14'),
(10, 'ITM2772', 'milk', 'Amata y&#039;inyange', 'Food', 500.00, 88, 10, '2025-10-06 07:04:52', '2025-10-06 10:01:29');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `customer_name` varchar(255) NOT NULL,
  `customer_phone` varchar(50) DEFAULT NULL,
  `customer_email` varchar(255) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL DEFAULT 'cash',
  `payment_status` varchar(50) NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `customer_name`, `customer_phone`, `customer_email`, `total_amount`, `payment_method`, `payment_status`, `notes`, `created_at`, `updated_at`) VALUES
(1, 5, 'quan', '', '', 500.00, 'cash', 'pending', '', '2025-10-06 11:59:51', NULL),
(2, 5, 'quan', '', '', 500.00, 'cash', 'pending', '', '2025-10-06 12:01:29', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `created_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `item_id`, `quantity`, `unit_price`, `total_price`, `created_at`) VALUES
(1, 1, 10, 1, 500.00, 500.00, '2025-10-06 11:59:51'),
(2, 2, 10, 1, 500.00, 500.00, '2025-10-06 12:01:29');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) DEFAULT NULL,
  `customer_email` varchar(100) DEFAULT NULL,
  `quantity` int(11) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','card','bank_transfer','credit') DEFAULT 'cash',
  `payment_status` enum('pending','paid','partial') DEFAULT 'paid',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `user_id`, `item_id`, `customer_name`, `customer_phone`, `customer_email`, `quantity`, `unit_price`, `total_amount`, `payment_method`, `payment_status`, `notes`, `created_at`) VALUES
(1, 2, 7, 'king david', '0788907645', 'kingdavid@gmail.com', 20, 5000.00, 100000.00, 'cash', 'paid', 'thank you', '2025-09-22 10:52:17'),
(2, 3, 8, 'Hozanna', '0788487100', 'hozanna@gmail.com', 2, 700.00, 1400.00, 'bank_transfer', 'paid', 'delivered to shalom school', '2025-09-23 10:02:54'),
(3, 3, 9, 'WISDOM SCHOOL LTD', '0788605734', '', 139, 150.00, 20850.00, 'bank_transfer', 'paid', '', '2025-09-26 07:34:15'),
(4, 1, 10, 'malik', '0788605734', 'malik@gmail.com', 2, 500.00, 1000.00, 'credit', 'pending', 'atwaye amata y&#039;inyange', '2025-10-06 07:08:14'),
(6, 5, 10, 'luc', '', '', 6, 500.00, 3000.00, 'cash', 'paid', '', '2025-10-06 09:28:55');

-- --------------------------------------------------------

--
-- Table structure for table `stock_allocations`
--

CREATE TABLE `stock_allocations` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `allocated_quantity` int(11) NOT NULL,
  `remaining_quantity` int(11) NOT NULL,
  `status` enum('active','completed','cancelled') DEFAULT 'active',
  `allocated_by` int(11) NOT NULL,
  `allocated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_allocations`
--

INSERT INTO `stock_allocations` (`id`, `item_id`, `user_id`, `allocated_quantity`, `remaining_quantity`, `status`, `allocated_by`, `allocated_at`, `completed_at`) VALUES
(1, 7, 2, 50, 30, 'completed', 1, '2025-09-22 09:59:12', '2025-09-23 08:39:06'),
(2, 8, 3, 10, 8, 'active', 1, '2025-09-23 09:57:38', NULL),
(3, 9, 3, 140, 1, 'active', 1, '2025-09-26 07:17:14', NULL),
(6, 10, 5, 8, 0, 'completed', 1, '2025-10-06 07:46:18', '2025-10-06 10:01:29');

-- --------------------------------------------------------

--
-- Table structure for table `stock_history`
--

CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `movement_type` enum('in','out','allocation','sale') NOT NULL,
  `quantity` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `reference_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_history`
--

INSERT INTO `stock_history` (`id`, `item_id`, `movement_type`, `quantity`, `previous_stock`, `new_stock`, `reference_id`, `notes`, `created_by`, `created_at`) VALUES
(1, 6, 'in', 500, 0, 500, NULL, 'Initial stock', 1, '2025-09-21 18:00:45'),
(2, 7, 'in', 100, 0, 100, NULL, 'Initial stock', 1, '2025-09-22 09:55:47'),
(3, 7, 'allocation', 50, 100, 50, NULL, 'Stock allocated to employee', 1, '2025-09-22 09:59:12'),
(4, 8, 'in', 20, 0, 20, NULL, 'Initial stock', 1, '2025-09-23 08:35:58'),
(5, 7, 'in', 100, 50, 150, NULL, '', 1, '2025-09-23 09:53:58'),
(6, 7, 'in', 800, 500, 1300, NULL, '', 1, '2025-09-23 09:54:28'),
(7, 7, 'in', 920, 900, 1820, NULL, '', 1, '2025-09-23 09:55:03'),
(8, 8, 'allocation', 10, 20, 10, NULL, 'Stock allocated from approved request', 1, '2025-09-23 09:57:38'),
(9, 9, 'in', 500, 0, 500, NULL, 'Initial stock', 1, '2025-09-26 07:14:37'),
(10, 9, 'allocation', 140, 500, 360, NULL, 'Stock allocated to employee', 1, '2025-09-26 07:17:14'),
(11, 10, 'in', 100, 0, 100, NULL, 'Initial stock', 1, '2025-10-06 07:04:52'),
(12, 10, 'sale', 2, 100, 98, NULL, 'atwaye amata y&#039;inyange', 1, '2025-10-06 07:08:14'),
(13, 10, 'allocation', 8, 98, 90, NULL, 'Stock allocated to employee', 1, '2025-10-06 07:46:18'),
(14, 10, 'sale', 1, 89, 88, 1, 'Sold to: quan', 5, '2025-10-06 09:59:51'),
(15, 10, 'sale', 1, 88, 87, 2, 'Sold to: quan', 5, '2025-10-06 10:01:29');

-- --------------------------------------------------------

--
-- Table structure for table `stock_requests`
--

CREATE TABLE `stock_requests` (
  `id` int(11) NOT NULL,
  `item_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `requested_quantity` int(11) NOT NULL,
  `reason` text DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `admin_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stock_requests`
--

INSERT INTO `stock_requests` (`id`, `item_id`, `user_id`, `requested_quantity`, `reason`, `status`, `reviewed_by`, `reviewed_at`, `admin_notes`, `created_at`) VALUES
(1, 2, 2, 500, 'zashize', 'rejected', 1, '2025-09-23 09:27:22', 'the stock is tooo many', '2025-09-21 18:55:08'),
(2, 8, 3, 10, 'I want to supply to Lanari', 'approved', 1, '2025-09-23 09:57:38', 'okay ntaribi uyikoreshe neza', '2025-09-23 09:46:46'),
(3, 9, 3, 20, '20 more are needed kugira bigere kubanyeshuri bose', 'pending', NULL, NULL, NULL, '2025-09-26 07:33:17'),
(4, 10, 5, 8, 'kujyana kuri pipinierre', 'pending', NULL, NULL, NULL, '2025-10-06 07:26:58'),
(5, 3, 5, 50, 'nshaka izo kujyana kubigo byamashuri', 'pending', NULL, NULL, NULL, '2025-10-06 08:19:04'),
(6, 10, 5, 8, 'the avaible are done', 'pending', NULL, NULL, NULL, '2025-10-06 10:05:26');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int(11) NOT NULL,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `updated_at`) VALUES
(1, 'company_name', 'NESRAH GROUP', 'Company name', '2025-09-21 13:20:52'),
(2, 'company_address', 'Musanze, Rwanda', 'Company address', '2025-09-22 10:10:29'),
(3, 'company_phone', '0790635888', 'Company phone number', '2025-09-22 10:10:14'),
(4, 'company_email', 'info@nesrahgroup.com', 'Company email address', '2025-09-21 13:20:52'),
(5, 'mission', 'To provide excellent business management solutionssss', 'Company mission', '2025-09-23 10:08:44'),
(6, 'vision', 'To be the leading provider of comprehensive business management systems', 'Company vision', '2025-09-21 13:20:52');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `assigned_to` int(11) NOT NULL,
  `assigned_by` int(11) NOT NULL,
  `priority` enum('low','medium','high','urgent') DEFAULT 'medium',
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `title`, `description`, `assigned_to`, `assigned_by`, `priority`, `status`, `due_date`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, 'uuuuuauaaaaaaaaahhhhhhhhhh', 'come tomorrow in the office', 2, 1, 'urgent', 'completed', '2025-09-23', '2025-09-23 09:27:39', '2025-09-22 10:01:38', '2025-09-23 09:27:39'),
(2, 'kugurisha kugihe', 'uyu munsi dushaka ko ugurisha byinshi cyane koko', 3, 1, 'urgent', 'completed', '2025-09-23', '2025-09-23 19:04:09', '2025-09-23 09:58:18', '2025-09-23 19:04:09'),
(3, 'Kugemura ibiraha kuri WISDOM', 'Nugera kuri gate y&#039;ikigo ubwire umu security amvugishe', 3, 1, 'high', 'in_progress', '2025-09-26', NULL, '2025-09-26 07:18:29', '2025-09-26 07:32:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `plain_password` varchar(255) DEFAULT NULL,
  `first_name` varchar(50) NOT NULL,
  `last_name` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `role` enum('admin','employee') DEFAULT 'employee',
  `status` enum('pending','active','inactive') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `plain_password`, `first_name`, `last_name`, `phone`, `address`, `role`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin', '', '$2y$10$fXYWqDVSIs2eklPVClDFk.w/0w008EiAUE9L/WwJTsTTsgdxo6BMy', 'admin123', '', '', '', '', 'admin', 'active', '2025-09-21 13:20:52', '2025-10-06 07:18:51'),
(2, 'prince', 'prince@gmail.com', '$2y$10$8G2R/0NjgijKo2inIase8OtHdbWUmCxgwBAIGs7omYyjQBVXcQMvi', 'prince123', 'ishimwe', 'prince', '0790635888', '', 'employee', 'active', '2025-09-21 18:20:05', '2025-09-26 07:13:47'),
(3, 'haranira', 'haranira@gmail.com', '$2y$10$0JlnL3uTb6Q3eX7ET1iQKOlxGHPedGL9tGptUAlkYLD0R/uocD82O', NULL, 'izabayoooo', 'haranira', '0790635888', '', 'employee', 'active', '2025-09-23 09:41:39', '2025-09-26 10:11:51'),
(4, 'masonga', 'masonga@gmail.com', '$2y$10$YPL.gAvgsM1QYz09rDRMZe7GnmJ2uKXGaTs7ebqkvVUNQ8t1BGHuG', NULL, 'masonga', 'prince', '0788487100', 'musanze-kalisimbi', 'employee', 'active', '2025-09-26 10:13:32', '2025-09-26 10:13:32'),
(5, 'yoram', 'irutabyose@gmail.com', '$2y$10$b.ruk84VzTLr2wnANcKNg.ZfubAvBlXQf44iL0GvdO/k/opEOdx2a', NULL, 'irutabyosee', 'yoramm', '0790635888', '', 'employee', 'active', '2025-09-26 10:24:09', '2025-10-06 07:04:05');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_attendance` (`user_id`,`work_date`);

--
-- Indexes for table `employee_communications`
--
ALTER TABLE `employee_communications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `item_code` (`item_code`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `item_id` (`item_id`);

--
-- Indexes for table `stock_allocations`
--
ALTER TABLE `stock_allocations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `allocated_by` (`allocated_by`);

--
-- Indexes for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `stock_requests`
--
ALTER TABLE `stock_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `item_id` (`item_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `reviewed_by` (`reviewed_by`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `assigned_to` (`assigned_to`),
  ADD KEY `assigned_by` (`assigned_by`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `employee_communications`
--
ALTER TABLE `employee_communications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `stock_allocations`
--
ALTER TABLE `stock_allocations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `stock_history`
--
ALTER TABLE `stock_history`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `stock_requests`
--
ALTER TABLE `stock_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `employee_communications`
--
ALTER TABLE `employee_communications`
  ADD CONSTRAINT `employee_communications_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `employee_communications_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_allocations`
--
ALTER TABLE `stock_allocations`
  ADD CONSTRAINT `stock_allocations_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_allocations_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_allocations_ibfk_3` FOREIGN KEY (`allocated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_history`
--
ALTER TABLE `stock_history`
  ADD CONSTRAINT `stock_history_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_history_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `stock_requests`
--
ALTER TABLE `stock_requests`
  ADD CONSTRAINT `stock_requests_ibfk_1` FOREIGN KEY (`item_id`) REFERENCES `inventory` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_requests_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `stock_requests_ibfk_3` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `tasks`
--
ALTER TABLE `tasks`
  ADD CONSTRAINT `tasks_ibfk_1` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `tasks_ibfk_2` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
