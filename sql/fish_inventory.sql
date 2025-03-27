-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2025 at 07:47 PM
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
-- Database: `fish_inventory`
--

-- --------------------------------------------------------

--
-- Table structure for table `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `subject` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','responded') DEFAULT 'new',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `admin_notes` text DEFAULT NULL,
  `responded_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `contact_submissions`
--

CREATE TABLE `contact_submissions` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `subject` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `submitted_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `detailed_costs`
--

CREATE TABLE `detailed_costs` (
  `id` int(11) NOT NULL,
  `fish_type_id` int(11) NOT NULL,
  `date_recorded` date NOT NULL,
  `feed_cost` decimal(10,2) NOT NULL COMMENT 'Cost of feed per kg',
  `labor_cost` decimal(10,2) NOT NULL COMMENT 'Labor cost per kg',
  `transport_cost` decimal(10,2) NOT NULL COMMENT 'Transportation cost per kg',
  `medication_cost` decimal(10,2) NOT NULL COMMENT 'Medication cost per kg',
  `equipment_cost` decimal(10,2) NOT NULL COMMENT 'Equipment maintenance per kg',
  `aeration_cost` decimal(10,2) NOT NULL COMMENT 'Aeration/oxygen costs per kg',
  `other_cost` decimal(10,2) NOT NULL COMMENT 'Other miscellaneous costs per kg',
  `total_cost` decimal(10,2) NOT NULL COMMENT 'Sum of all cost components'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detailed_costs`
--

INSERT INTO `detailed_costs` (`id`, `fish_type_id`, `date_recorded`, `feed_cost`, `labor_cost`, `transport_cost`, `medication_cost`, `equipment_cost`, `aeration_cost`, `other_cost`, `total_cost`) VALUES
(1, 1, '2025-03-26', 100.00, 200.00, 300.00, 100.00, 100.00, 200.00, 200.00, 1200.00),
(2, 2, '2025-03-26', 100.00, 200.00, 200.00, 200.00, 400.00, 300.00, 100.00, 1500.00);

-- --------------------------------------------------------

--
-- Table structure for table `fish_types`
--

CREATE TABLE `fish_types` (
  `id` int(11) NOT NULL,
  `name` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `price_per_kg` decimal(10,2) NOT NULL,
  `image_path` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fish_types`
--

INSERT INTO `fish_types` (`id`, `name`, `description`, `price_per_kg`, `image_path`) VALUES
(1, 'Catfish', 'Freshwater catfish from our pond', 100.00, NULL),
(2, 'Tilapia', 'High-quality tilapia fish', 200.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `id` int(11) NOT NULL,
  `fish_type_id` int(11) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `inventory`
--

INSERT INTO `inventory` (`id`, `fish_type_id`, `quantity_kg`, `last_updated`) VALUES
(1, 1, 97.00, '2025-03-26 16:33:53'),
(2, 2, 148.00, '2025-03-26 16:34:07');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','processing','completed','cancelled') DEFAULT 'pending',
  `total_amount` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_date`, `status`, `total_amount`) VALUES
(1, 2, '2025-03-26 11:43:18', 'completed', 500.00),
(2, 2, '2025-03-26 16:33:53', 'processing', 200.00),
(3, 2, '2025-03-26 16:34:07', 'cancelled', 400.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `fish_type_id` int(11) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `fish_type_id`, `quantity_kg`, `unit_price`) VALUES
(1, 1, 1, 1.00, 500.00),
(2, 2, 1, 2.00, 100.00),
(3, 3, 2, 2.00, 200.00);

-- --------------------------------------------------------

--
-- Table structure for table `production_costs`
--

CREATE TABLE `production_costs` (
  `id` int(11) NOT NULL,
  `fish_type_id` int(11) NOT NULL,
  `cost_per_kg` decimal(10,2) NOT NULL,
  `date_recorded` date NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `production_costs`
--

INSERT INTO `production_costs` (`id`, `fish_type_id`, `cost_per_kg`, `date_recorded`, `notes`) VALUES
(1, 1, 100000.00, '2025-03-26', NULL),
(2, 2, 99999.99, '2025-03-26', NULL),
(3, 1, 100000.00, '2025-03-26', NULL),
(4, 2, 99999.99, '2025-03-26', NULL);

-- --------------------------------------------------------

--
-- Stand-in structure for view `profit_analysis`
-- (See below for the actual view)
--
CREATE TABLE `profit_analysis` (
`order_id` int(11)
,`order_date` timestamp
,`revenue` decimal(10,2)
,`total_cost` decimal(42,4)
,`profit` decimal(43,4)
,`profit_margin` decimal(52,8)
);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'admin@abliebah', '$2b$12$6AK4Efc8uTcnOnJOM2ueHeanljd6oke9VgT7WHRD8tFrWUGGTnMVC', 'admin@fishfarm.com', 'admin', '2025-03-26 11:13:59'),
(2, 'abliebah', '$2y$10$riEbnkvMN/CKqWY9kCasQOGYlWtp955c8F.U6x2QQHzyc8pX3Wnv6', 'abdouliebah@mrc.gm', 'customer', '2025-03-26 11:42:21');

-- --------------------------------------------------------

--
-- Structure for view `profit_analysis`
--
DROP TABLE IF EXISTS `profit_analysis`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `profit_analysis`  AS SELECT `o`.`id` AS `order_id`, `o`.`order_date` AS `order_date`, `o`.`total_amount` AS `revenue`, sum(`oi`.`quantity_kg` * `pc`.`cost_per_kg`) AS `total_cost`, `o`.`total_amount`- sum(`oi`.`quantity_kg` * `pc`.`cost_per_kg`) AS `profit`, (`o`.`total_amount` - sum(`oi`.`quantity_kg` * `pc`.`cost_per_kg`)) / `o`.`total_amount` * 100 AS `profit_margin` FROM ((`orders` `o` join `order_items` `oi` on(`o`.`id` = `oi`.`order_id`)) join (select `production_costs`.`fish_type_id` AS `fish_type_id`,`production_costs`.`cost_per_kg` AS `cost_per_kg` from `production_costs` where `production_costs`.`date_recorded` = (select max(`production_costs`.`date_recorded`) from `production_costs`)) `pc` on(`oi`.`fish_type_id` = `pc`.`fish_type_id`)) GROUP BY `o`.`id` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `detailed_costs`
--
ALTER TABLE `detailed_costs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fish_type_id` (`fish_type_id`);

--
-- Indexes for table `fish_types`
--
ALTER TABLE `fish_types`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fish_type_id` (`fish_type_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `fish_type_id` (`fish_type_id`);

--
-- Indexes for table `production_costs`
--
ALTER TABLE `production_costs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fish_type_id` (`fish_type_id`);

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
-- AUTO_INCREMENT for table `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `contact_submissions`
--
ALTER TABLE `contact_submissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `detailed_costs`
--
ALTER TABLE `detailed_costs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `fish_types`
--
ALTER TABLE `fish_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `production_costs`
--
ALTER TABLE `production_costs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detailed_costs`
--
ALTER TABLE `detailed_costs`
  ADD CONSTRAINT `detailed_costs_ibfk_1` FOREIGN KEY (`fish_type_id`) REFERENCES `fish_types` (`id`);

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`fish_type_id`) REFERENCES `fish_types` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`fish_type_id`) REFERENCES `fish_types` (`id`);

--
-- Constraints for table `production_costs`
--
ALTER TABLE `production_costs`
  ADD CONSTRAINT `production_costs_ibfk_1` FOREIGN KEY (`fish_type_id`) REFERENCES `fish_types` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
