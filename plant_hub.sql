-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 31, 2026 at 08:44 PM
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
-- Table structure for table `customer`
--

CREATE TABLE `customer` (
  `Customer_ID` int(11) NOT NULL,
  `Customer_name` varchar(100) DEFAULT NULL,
  `Loyalty_points` int(11) DEFAULT 0,
  `Phone` varchar(20) DEFAULT NULL,
  `Address` varchar(200) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Password` varchar(100) DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `wallet_balance` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customer`
--

INSERT INTO `customer` (`Customer_ID`, `Customer_name`, `Loyalty_points`, `Phone`, `Address`, `Email`, `Password`, `points`, `wallet_balance`) VALUES
(1, 'Ahsan', 0, '01701342809', 'Brac University', NULL, NULL, 0, 0.00),
(2, 'Sakib', 0, NULL, NULL, NULL, NULL, 0, 0.00),
(3, 'AFIA SULTANA', 0, NULL, NULL, 'afia.sultana1@g.bracu.ac.bd', '123456', 0, 0.00),
(4, 'AFIA SULTANA', 0, NULL, NULL, 'afia.sultana1@g.bracu.ac.bd', '123456', 0, 0.00),
(5, 'AFIA SULTANA', 0, NULL, NULL, 'afia.sultana1@g.bracu.ac.bd', '12345678', 0, 0.00),
(6, 'AFIA SULTANA', 0, NULL, NULL, 'afia.sultana1@g.bracu.ac.bd', '5563565265656', 0, 0.00),
(7, 'AFIA SULTANA', 0, NULL, NULL, 'afia.sultana1@g.bracu.ac.bd', '5563565265656', 0, 0.00),
(8, 'AFIA SULTANA', 0, NULL, NULL, 'afia.sultana5@g.bracu.ac.bd', '6677', 0, 0.00),
(9, 'Ahona', 0, NULL, NULL, 'ahona123@gmail.com', '667788', 0, 0.00),
(10, 'AFIA SULTANA', 0, NULL, NULL, 'afia.sultana1@g.bracu.ac.bd', '12345678', 0, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `employee`
--

CREATE TABLE `employee` (
  `Employee_ID` int(11) NOT NULL,
  `Employee_name` varchar(100) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Hire_date` date DEFAULT NULL,
  `Address` varchar(200) DEFAULT NULL,
  `Username` varchar(50) DEFAULT NULL,
  `Password` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee`
--

INSERT INTO `employee` (`Employee_ID`, `Employee_name`, `Phone`, `Hire_date`, `Address`, `Username`, `Password`) VALUES
(1, 'Admin', '01711111111', '2026-01-01', 'Dhaka', 'admin', '1234'),
(2, 'Rahim', '01722222222', '2026-02-01', 'Dhaka', 'rahim', '1234'),
(3, 'AFIA SULTANA', '01701342809', '2026-12-04', 'Brac University', 'testuser', 'abc'),
(1234, 'AFIA SULTANA', '015646354165', '0000-00-00', 'fjnfjkslgilk', 'admin@', '1234'),
(23201684, 'Tashrif Hossain', '01701342809', '2015-02-11', 'Brac University', 'tash_rif', '123456'),
(24101125, 'Ahona', '01701342809', '2026-08-18', 'Brac University', 'admin2', '123456'),
(24101140, 'Afia Sultana', '01701342809', '2026-08-18', 'Brac University', 'admin1', '12345'),
(24101141, 'AFIA SULTANA', NULL, NULL, NULL, 'afia.sultana1@g.bracu.ac.bd', '123456'),
(24101142, 'AFIA SULTANA', NULL, NULL, NULL, 'afia.sultana2@g.bracu.ac.bd', '12345678'),
(24101143, 'AFIA SULTANA', NULL, NULL, NULL, 'afia.sultana3@g.bracu.ac.bd', '123456789'),
(24101144, 'Ahona', NULL, NULL, NULL, 'Ahona@gmail.com', '667788'),
(24101146, 'Tashrif Hossain', NULL, NULL, NULL, 'Tashrif11@gmail.com', '12345678'),
(24101147, 'Tashrif Hossain', NULL, NULL, NULL, 'TH123@gmail.com', '2233');

-- --------------------------------------------------------

--
-- Table structure for table `exchange`
--

CREATE TABLE `exchange` (
  `exchange_id` int(11) NOT NULL,
  `Exchange_date` date DEFAULT NULL,
  `Exchange_value` decimal(10,2) DEFAULT NULL,
  `Received_plant_ID` int(11) DEFAULT NULL,
  `Customer_ID` int(11) DEFAULT NULL,
  `Employee_ID` int(11) DEFAULT NULL,
  `Offered_plant_ID` int(11) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Pending',
  `payment_method` varchar(50) DEFAULT 'N/A',
  `payment_status` varchar(50) DEFAULT 'Pending',
  `adjustment_direction` varchar(50) DEFAULT 'No Adjustment',
  `notes` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `exchange`
--

INSERT INTO `exchange` (`exchange_id`, `Exchange_date`, `Exchange_value`, `Received_plant_ID`, `Customer_ID`, `Employee_ID`, `Offered_plant_ID`, `status`, `payment_method`, `payment_status`, `adjustment_direction`, `notes`) VALUES
(1, '2026-08-22', 200.00, 1, 0, NULL, 2, 'Pending', 'N/A', 'Pending', 'No Adjustment', NULL),
(2, '2026-08-22', 200.00, 1, 0, NULL, 2, 'Pending', 'N/A', 'Pending', 'No Adjustment', NULL),
(3, '2026-08-22', 0.00, 2, 0, NULL, 2, 'Pending', 'N/A', 'Pending', 'No Adjustment', NULL),
(4, '2026-08-22', 0.00, 2, 0, NULL, 2, 'Pending', 'N/A', 'Pending', 'No Adjustment', NULL),
(5, '2026-08-22', 200.00, 1, 0, NULL, 2, 'Pending', 'N/A', 'Pending', 'No Adjustment', NULL),
(6, '2026-08-22', -20.00, 12, 0, NULL, 2, 'Pending', 'Store Wallet Credit', 'Refunded to Wallet', 'No Adjustment', NULL),
(7, '2026-08-22', -220.00, 12, 0, NULL, 1, 'Pending', 'Store Wallet Credit', 'Refunded to Wallet', 'No Adjustment', NULL),
(8, '2026-08-22', -320.00, 6, 0, NULL, 1, 'Pending', 'Store Wallet Credit', 'Refunded to Wallet', 'No Adjustment', NULL),
(9, '2026-08-22', -290.00, 14, 0, NULL, 1, 'Pending', 'Store Wallet Credit', 'Refunded to Wallet', 'No Adjustment', NULL),
(10, '2026-08-22', -290.00, 14, 0, NULL, 1, 'Pending', 'Store Wallet Credit', 'Refunded to Wallet', 'No Adjustment', NULL);

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
  `points_redeemed` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`Order_id`, `Customer_id`, `Plant_id`, `Amount`, `Order_date`, `Exchange_status`, `points_redeemed`) VALUES
(1, 0, 0, 0.00, '2026-08-18 23:15:22', 'None', 0),
(2, 0, 0, 0.00, '2026-08-18 23:15:24', 'None', 0),
(3, 0, 0, 0.00, '2026-08-18 23:15:27', 'None', 0),
(4, 0, 0, 0.00, '2026-08-18 23:15:28', 'None', 0),
(5, 0, 1, 0.00, '2026-08-18 23:20:23', 'None', 0),
(6, 0, 1, 500.00, '2026-08-18 23:20:26', 'None', 0),
(7, 0, 1, 500.00, '2026-08-18 23:20:28', 'None', 0),
(8, 0, 1, 0.00, '2026-08-18 23:24:38', 'None', 0),
(9, 0, 1, 500.00, '2026-08-18 23:26:18', 'None', 0),
(10, 0, 2, 300.00, '2026-08-18 23:27:13', 'None', 0),
(11, 0, 3, 250.00, '2026-08-18 23:27:13', 'None', 0),
(12, 0, 1, 500.00, '2026-08-18 23:27:13', 'None', 0),
(13, 0, 1, 500.00, '2026-08-18 23:37:48', 'None', 0),
(14, 0, 2, 300.00, '2026-08-18 23:37:48', 'None', 0),
(15, 0, 3, 250.00, '2026-08-18 23:37:48', 'None', 0),
(16, 0, 1, 500.00, '2026-08-18 23:48:36', 'None', 0),
(17, 0, 901, 220.00, '2026-08-18 23:48:36', 'None', 0),
(18, 0, 3, 250.00, '2026-08-18 23:48:36', 'None', 0),
(19, 1, 1, 500.00, '2026-08-19 00:00:12', 'None', 0),
(20, 1, 2, 300.00, '2026-08-19 00:00:12', 'None', 0),
(21, 1, 3, 250.00, '2026-08-19 00:00:12', 'None', 0),
(22, 0, 1, 500.00, '2026-08-19 00:14:53', 'None', 0),
(23, 0, 2, 300.00, '2026-08-19 00:14:53', 'None', 0),
(24, 0, 1, 1500.00, '2026-08-19 00:15:16', 'None', 0),
(25, 0, 1, 500.00, '2026-08-19 00:19:01', 'None', 200),
(26, 0, 1, 500.00, '2026-08-19 00:19:35', 'None', 0),
(27, 0, 901, 220.00, '2026-08-19 00:19:35', 'None', 0),
(28, 0, 902, 450.00, '2026-08-19 00:19:35', 'None', 0),
(29, 0, 903, 180.00, '2026-08-19 00:19:35', 'None', 0),
(30, 0, 1, 500.00, '2026-08-19 00:23:32', 'None', 200),
(31, 0, 1, 2000.00, '2026-08-19 00:23:48', 'None', 200),
(32, 0, 1, 500.00, '2026-08-19 00:26:39', 'None', 200),
(33, 0, 902, 450.00, '2026-08-19 00:26:39', 'None', 1),
(34, 0, 1, 500.00, '2026-08-19 00:27:00', 'None', 0),
(35, 0, 1, 500.00, '2026-08-22 01:02:47', 'None', 0),
(36, 0, 2, 300.00, '2026-08-22 01:02:47', 'None', 0),
(37, 0, 3, 250.00, '2026-08-22 01:03:10', 'None', 0),
(38, 0, 902, 450.00, '2026-08-22 01:03:10', 'None', 0),
(39, 0, 903, 180.00, '2026-08-22 01:03:32', 'None', 200),
(40, 0, 2, 300.00, '2026-08-22 01:03:32', 'None', 1),
(41, 0, 3, 250.00, '2026-08-22 01:03:33', 'None', 1),
(42, 0, 1, 500.00, '2026-08-22 01:07:02', 'None', 0),
(43, 0, 2, 300.00, '2026-08-22 01:07:02', 'None', 0),
(44, 0, 1, 500.00, '2026-08-22 01:08:19', 'None', 0),
(45, 0, 2, 300.00, '2026-08-22 01:08:19', 'None', 0),
(46, 0, 1, 500.00, '2026-08-22 01:09:57', 'None', 0),
(47, 0, 2, 300.00, '2026-08-22 01:09:57', 'None', 0),
(48, 0, 3, 250.00, '2026-08-22 01:09:57', 'None', 0),
(49, 0, 1, 500.00, '2026-08-22 01:11:28', 'None', 200),
(50, 0, 2, 300.00, '2026-08-22 01:11:28', 'None', 1),
(51, 0, 1, 500.00, '2026-08-22 01:16:36', 'None', 0),
(52, 0, 2, 300.00, '2026-08-22 01:16:36', 'None', 0),
(53, 0, 1, 500.00, '2026-08-22 01:16:52', 'None', 0),
(54, 0, 2, 300.00, '2026-08-22 01:16:52', 'None', 0),
(55, 0, 3, 250.00, '2026-08-22 01:16:52', 'None', 0),
(56, 0, 902, 450.00, '2026-08-22 01:16:52', 'None', 0),
(57, 0, 2, 300.00, '2026-08-22 01:17:06', 'None', 200),
(58, 0, 3, 250.00, '2026-08-22 01:17:06', 'None', 1),
(59, 0, 1, 500.00, '2026-08-22 01:19:56', 'None', 0),
(60, 0, 2, 300.00, '2026-08-22 01:19:56', 'None', 0),
(61, 0, 3, 250.00, '2026-08-22 01:19:56', 'None', 0),
(62, 0, 901, 220.00, '2026-08-22 01:19:56', 'None', 0),
(63, 0, 1, 500.00, '2026-08-22 01:20:11', 'None', 200),
(64, 0, 2, 300.00, '2026-08-22 01:20:11', 'None', 1),
(65, 0, 1, 500.00, '2026-08-22 01:39:37', 'None', 0),
(66, 0, 2, 300.00, '2026-08-22 01:39:37', 'None', 0);

-- --------------------------------------------------------

--
-- Table structure for table `plant`
--

CREATE TABLE `plant` (
  `Plant_ID` int(11) NOT NULL,
  `Plant_name` varchar(100) DEFAULT NULL,
  `Unit_price` decimal(10,2) DEFAULT NULL,
  `Stock_quantity` int(11) DEFAULT 0,
  `Low_stock_level` int(11) DEFAULT NULL,
  `Care_info` varchar(500) DEFAULT NULL,
  `Category_ID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plant`
--

INSERT INTO `plant` (`Plant_ID`, `Plant_name`, `Unit_price`, `Stock_quantity`, `Low_stock_level`, `Care_info`, `Category_ID`) VALUES
(1, 'Snake Plant', 500.00, 20, 5, 'Low sunlight, water once a week', 1),
(2, 'Money Plant', 300.00, 15, 5, 'Indirect sunlight, moderate watering', 1),
(3, 'Rose', 250.00, 10, 3, 'Needs sunlight and regular watering', 3),
(4, 'Star plant', 370.00, 20, 5, '', 3),
(5, 'Sunflower', 250.00, 10, 5, 'Needs bright sunlight and regular watering', 3),
(6, 'Snake Plant', 500.00, 20, 5, 'Low sunlight, water once a week', 1),
(7, 'Money Plant', 300.00, 15, 5, 'Indirect sunlight, moderate watering', 1),
(8, 'Rose', 250.00, 10, 3, 'Needs sunlight and regular watering', 3),
(9, 'Star plant', 370.00, 20, 5, '', 3),
(10, 'Sunflower', 250.00, 10, 5, 'Needs bright sunlight and regular watering', 3);

-- --------------------------------------------------------

--
-- Table structure for table `plants`
--

CREATE TABLE `plants` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) DEFAULT 10,
  `sunlight` varchar(50) DEFAULT 'Indirect Light',
  `watering` varchar(50) DEFAULT 'Weekly',
  `difficulty` varchar(50) DEFAULT 'Easy'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `plants`
--

INSERT INTO `plants` (`id`, `name`, `price`, `stock`, `sunlight`, `watering`, `difficulty`) VALUES
(1, 'Snake Plant', 500.00, 15, 'Low to Bright', 'Every 2 Weeks', 'Easy'),
(2, 'Monstera Deliciosa', 850.00, 8, 'Bright Indirect', 'Weekly', 'Moderate'),
(3, 'Peace Lily', 400.00, 4, 'Medium Light', 'Twice Weekly', 'Easy'),
(4, 'Fiddle Leaf Fig', 1200.00, 2, 'Bright Direct', 'Weekly', 'Hard');

-- --------------------------------------------------------

--
-- Table structure for table `plant_exchange`
--

CREATE TABLE `plant_exchange` (
  `Exchange_ID` int(11) NOT NULL,
  `Exchange_date` date NOT NULL,
  `Reason` varchar(255) NOT NULL,
  `Customer_ID` int(11) DEFAULT NULL,
  `Plant_ID` int(11) DEFAULT NULL,
  `Quantity` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchase_contains_plant`
--

CREATE TABLE `purchase_contains_plant` (
  `Purchase_ID` int(11) NOT NULL,
  `Plant_ID` int(11) NOT NULL,
  `Quantity` int(11) DEFAULT NULL,
  `Purchase_unit_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sale_contains_plant`
--

CREATE TABLE `sale_contains_plant` (
  `Sale_ID` int(11) NOT NULL,
  `Plant_ID` int(11) NOT NULL,
  `Quantity` int(11) DEFAULT NULL,
  `Sale_unit_price` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `service_id` int(11) NOT NULL,
  `service_type` varchar(255) DEFAULT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT 'Assigned',
  `assigned_employee` varchar(100) DEFAULT 'Ahona'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`service_id`, `service_type`, `customer_name`, `status`, `assigned_employee`) VALUES
(1, 'Plant Care Consultation', 'Afia Sultana', 'Assigned', 'Ahona'),
(2, 'Garden Installation & Setup', 'John Doe', 'In Progress', 'Ahona');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customer`
--
ALTER TABLE `customer`
  ADD PRIMARY KEY (`Customer_ID`);

--
-- Indexes for table `employee`
--
ALTER TABLE `employee`
  ADD PRIMARY KEY (`Employee_ID`),
  ADD UNIQUE KEY `Username` (`Username`);

--
-- Indexes for table `exchange`
--
ALTER TABLE `exchange`
  ADD PRIMARY KEY (`exchange_id`),
  ADD KEY `Received_plant_ID` (`Received_plant_ID`),
  ADD KEY `Offered_plant_ID` (`Offered_plant_ID`),
  ADD KEY `Customer_ID` (`Customer_ID`),
  ADD KEY `Employee_ID` (`Employee_ID`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`Order_id`);

--
-- Indexes for table `plant`
--
ALTER TABLE `plant`
  ADD PRIMARY KEY (`Plant_ID`),
  ADD KEY `Category_ID` (`Category_ID`);

--
-- Indexes for table `plants`
--
ALTER TABLE `plants`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `plant_exchange`
--
ALTER TABLE `plant_exchange`
  ADD PRIMARY KEY (`Exchange_ID`);

--
-- Indexes for table `purchase_contains_plant`
--
ALTER TABLE `purchase_contains_plant`
  ADD PRIMARY KEY (`Purchase_ID`,`Plant_ID`),
  ADD KEY `Plant_ID` (`Plant_ID`);

--
-- Indexes for table `sale_contains_plant`
--
ALTER TABLE `sale_contains_plant`
  ADD PRIMARY KEY (`Sale_ID`,`Plant_ID`),
  ADD KEY `Plant_ID` (`Plant_ID`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`service_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customer`
--
ALTER TABLE `customer`
  MODIFY `Customer_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `employee`
--
ALTER TABLE `employee`
  MODIFY `Employee_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24101148;

--
-- AUTO_INCREMENT for table `exchange`
--
ALTER TABLE `exchange`
  MODIFY `exchange_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `plant`
--
ALTER TABLE `plant`
  MODIFY `Plant_ID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
