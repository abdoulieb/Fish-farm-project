-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 26, 2025 at 02:03 PM
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
(1, 1, 99.00, '2025-03-26 11:43:18'),
(2, 2, 150.00, '2025-03-26 11:13:59');

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
(1, 2, '2025-03-26 11:43:18', 'completed', 500.00);

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
(1, 1, 1, 1.00, 500.00);

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
(1, 'admin', '$2b$12$6AK4Efc8uTcnOnJOM2ueHeanljd6oke9VgT7WHRD8tFrWUGGTnMVC', 'admin@fishfarm.com', 'admin', '2025-03-26 11:13:59'),
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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
