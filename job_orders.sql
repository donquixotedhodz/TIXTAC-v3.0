-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2025 at 01:17 PM
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
-- Database: `job_order_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `job_orders`
--

CREATE TABLE `job_orders` (
  `id` int(11) NOT NULL,
  `job_order_number` varchar(20) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_address` text NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `service_type` enum('installation','repair') NOT NULL,
  `aircon_model_id` int(11) DEFAULT NULL,
  `assigned_technician_id` int(11) DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled') DEFAULT 'pending',
  `price` decimal(10,2) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_orders`
--

INSERT INTO `job_orders` (`id`, `job_order_number`, `customer_name`, `customer_address`, `customer_phone`, `service_type`, `aircon_model_id`, `assigned_technician_id`, `status`, `price`, `created_by`, `due_date`, `completed_at`, `created_at`, `updated_at`) VALUES
(1, '202500001', 'Josh McDowell Trapal', 'Ogbot, Bongabong, Oriental Mindoro', '09958714112', 'installation', 1, 1, 'completed', 15000.00, 1, '2025-06-03', '2025-06-02 10:16:40', '2025-06-02 11:06:16', '2025-06-02 11:06:16');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `job_orders`
--
ALTER TABLE `job_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `job_order_number` (`job_order_number`),
  ADD KEY `aircon_model_id` (`aircon_model_id`),
  ADD KEY `assigned_technician_id` (`assigned_technician_id`),
  ADD KEY `created_by` (`created_by`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `job_orders`
--
ALTER TABLE `job_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `job_orders`
--
ALTER TABLE `job_orders`
  ADD CONSTRAINT `job_orders_ibfk_1` FOREIGN KEY (`aircon_model_id`) REFERENCES `aircon_models` (`id`),
  ADD CONSTRAINT `job_orders_ibfk_2` FOREIGN KEY (`assigned_technician_id`) REFERENCES `technicians` (`id`),
  ADD CONSTRAINT `job_orders_ibfk_3` FOREIGN KEY (`created_by`) REFERENCES `admins` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
