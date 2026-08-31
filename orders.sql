-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 11:46 PM
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
-- Database: `plant_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `Order_id` int(11) NOT NULL,
  `Customer_id` int(11) NOT NULL,
  `Plant_id` int(11) NOT NULL,
  `Amount` decimal(10,2) DEFAULT 0.00,
  `Order_date` datetime DEFAULT current_timestamp(),
  `Exchange_status` varchar(50) DEFAULT 'None',
  `points_redeemed` int(11) DEFAULT 0,
  `Employee_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`Order_id`, `Customer_id`, `Plant_id`, `Amount`, `Order_date`, `Exchange_status`, `points_redeemed`, `Employee_id`) VALUES
(1, 3, 0, 0.00, '2026-08-18 23:15:22', 'None', 0, NULL),
(2, 3, 0, 0.00, '2026-08-18 23:15:24', 'None', 0, NULL),
(3, 3, 0, 0.00, '2026-08-18 23:15:27', 'None', 0, NULL),
(4, 3, 0, 0.00, '2026-08-18 23:15:28', 'None', 0, NULL),
(5, 3, 1, 0.00, '2026-08-18 23:20:23', 'None', 0, NULL),
(6, 3, 1, 500.00, '2026-08-18 23:20:26', 'None', 0, NULL),
(7, 3, 1, 500.00, '2026-08-18 23:20:28', 'None', 0, NULL),
(8, 3, 1, 0.00, '2026-08-18 23:24:38', 'None', 0, NULL),
(9, 3, 1, 500.00, '2026-08-18 23:26:18', 'None', 0, NULL),
(10, 3, 2, 300.00, '2026-08-18 23:27:13', 'None', 0, NULL),
(11, 3, 3, 250.00, '2026-08-18 23:27:13', 'None', 0, NULL),
(12, 3, 1, 500.00, '2026-08-18 23:27:13', 'None', 0, NULL),
(13, 3, 1, 500.00, '2026-08-18 23:37:48', 'None', 0, NULL),
(14, 3, 2, 300.00, '2026-08-18 23:37:48', 'None', 0, NULL),
(15, 3, 3, 250.00, '2026-08-18 23:37:48', 'None', 0, NULL),
(16, 3, 1, 500.00, '2026-08-18 23:48:36', 'None', 0, NULL),
(17, 3, 901, 220.00, '2026-08-18 23:48:36', 'None', 0, NULL),
(18, 3, 3, 250.00, '2026-08-18 23:48:36', 'None', 0, NULL),
(19, 1, 1, 500.00, '2026-08-19 00:00:12', 'None', 0, NULL),
(20, 1, 2, 300.00, '2026-08-19 00:00:12', 'None', 0, NULL),
(21, 1, 3, 250.00, '2026-08-19 00:00:12', 'None', 0, NULL),
(22, 3, 1, 500.00, '2026-08-19 00:14:53', 'None', 0, NULL),
(23, 3, 2, 300.00, '2026-08-19 00:14:53', 'None', 0, NULL),
(24, 3, 1, 1500.00, '2026-08-19 00:15:16', 'None', 0, NULL),
(25, 3, 1, 500.00, '2026-08-19 00:19:01', 'None', 200, NULL),
(26, 3, 1, 500.00, '2026-08-19 00:19:35', 'None', 0, NULL),
(27, 3, 901, 220.00, '2026-08-19 00:19:35', 'None', 0, NULL),
(28, 3, 902, 450.00, '2026-08-19 00:19:35', 'None', 0, NULL),
(29, 3, 903, 180.00, '2026-08-19 00:19:35', 'None', 0, NULL),
(30, 3, 1, 500.00, '2026-08-19 00:23:32', 'None', 200, NULL),
(31, 3, 1, 2000.00, '2026-08-19 00:23:48', 'None', 200, NULL),
(32, 3, 1, 500.00, '2026-08-19 00:26:39', 'None', 200, NULL),
(33, 3, 902, 450.00, '2026-08-19 00:26:39', 'None', 1, NULL),
(34, 3, 1, 500.00, '2026-08-19 00:27:00', 'None', 0, NULL),
(35, 3, 1, 500.00, '2026-08-22 01:02:47', 'None', 0, NULL),
(36, 3, 2, 300.00, '2026-08-22 01:02:47', 'None', 0, NULL),
(37, 3, 3, 250.00, '2026-08-22 01:03:10', 'None', 0, NULL),
(38, 3, 902, 450.00, '2026-08-22 01:03:10', 'None', 0, NULL),
(39, 3, 903, 180.00, '2026-08-22 01:03:32', 'None', 200, NULL),
(40, 3, 2, 300.00, '2026-08-22 01:03:32', 'None', 1, NULL),
(41, 3, 3, 250.00, '2026-08-22 01:03:33', 'None', 1, NULL),
(42, 3, 1, 500.00, '2026-08-22 01:07:02', 'None', 0, NULL),
(43, 3, 2, 300.00, '2026-08-22 01:07:02', 'None', 0, NULL),
(44, 3, 1, 500.00, '2026-08-22 01:08:19', 'None', 0, NULL),
(45, 3, 2, 300.00, '2026-08-22 01:08:19', 'None', 0, NULL),
(46, 3, 1, 500.00, '2026-08-22 01:09:57', 'None', 0, NULL),
(47, 3, 2, 300.00, '2026-08-22 01:09:57', 'None', 0, NULL),
(48, 3, 3, 250.00, '2026-08-22 01:09:57', 'None', 0, NULL),
(49, 3, 1, 500.00, '2026-08-22 01:11:28', 'None', 200, NULL),
(50, 3, 2, 300.00, '2026-08-22 01:11:28', 'None', 1, NULL),
(51, 3, 1, 500.00, '2026-08-22 01:16:36', 'None', 0, NULL),
(52, 3, 2, 300.00, '2026-08-22 01:16:36', 'None', 0, NULL),
(53, 3, 1, 500.00, '2026-08-22 01:16:52', 'None', 0, NULL),
(54, 3, 2, 300.00, '2026-08-22 01:16:52', 'None', 0, NULL),
(55, 3, 3, 250.00, '2026-08-22 01:16:52', 'None', 0, NULL),
(56, 3, 902, 450.00, '2026-08-22 01:16:52', 'None', 0, NULL),
(57, 3, 2, 300.00, '2026-08-22 01:17:06', 'None', 200, NULL),
(58, 3, 3, 250.00, '2026-08-22 01:17:06', 'None', 1, NULL),
(59, 3, 1, 500.00, '2026-08-22 01:19:56', 'None', 0, NULL),
(60, 3, 2, 300.00, '2026-08-22 01:19:56', 'None', 0, NULL),
(61, 3, 3, 250.00, '2026-08-22 01:19:56', 'None', 0, NULL),
(62, 3, 901, 220.00, '2026-08-22 01:19:56', 'None', 0, NULL),
(63, 3, 1, 500.00, '2026-08-22 01:20:11', 'None', 200, NULL),
(64, 3, 2, 300.00, '2026-08-22 01:20:11', 'None', 1, NULL),
(65, 3, 1, 500.00, '2026-08-22 01:39:37', 'None', 0, NULL),
(66, 3, 2, 300.00, '2026-08-22 01:39:37', 'None', 0, NULL),
(67, 11, 1, 500.00, '2026-09-01 03:02:53', 'None', 0, NULL),
(68, 11, 1, 1000.00, '2026-09-01 03:45:11', 'None', 0, NULL),
(69, 11, 2, 600.00, '2026-09-01 03:45:11', 'None', 0, NULL),
(70, 11, 3, 500.00, '2026-09-01 03:45:11', 'None', 0, NULL),
(71, 11, 4, 740.00, '2026-09-01 03:45:11', 'None', 0, NULL),
(72, 11, 5, 250.00, '2026-09-01 03:45:11', 'None', 0, NULL),
(73, 11, 6, 500.00, '2026-09-01 03:45:11', 'Pending', 0, NULL);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`Order_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `Order_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=74;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
