-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Apr 09, 2025 at 02:40 AM
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
-- Table structure for table `cash_reconciliations`
--

CREATE TABLE `cash_reconciliations` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `report_date` date NOT NULL,
  `expected_amount` decimal(10,2) NOT NULL,
  `physical_cash` decimal(10,2) NOT NULL,
  `petty_cash` decimal(10,2) NOT NULL,
  `deficit` decimal(10,2) NOT NULL,
  `total_cash` decimal(10,2) NOT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cash_reconciliations`
--

INSERT INTO `cash_reconciliations` (`id`, `employee_id`, `report_date`, `expected_amount`, `physical_cash`, `petty_cash`, `deficit`, `total_cash`, `submitted_at`) VALUES
(1, 5, '2025-04-08', 800.00, 800.00, 500.00, 0.00, 1300.00, '2025-04-08 02:23:52'),
(3, 5, '2025-04-06', 800.00, 600.00, 0.00, 200.00, 600.00, '2025-04-06 13:27:11'),
(4, 5, '2025-04-06', 800.00, 500.00, 500.00, 300.00, 1000.00, '2025-04-06 13:30:03'),
(5, 5, '2025-04-06', 800.00, 800.00, 500.00, 0.00, 1300.00, '2025-04-06 13:39:43'),
(6, 5, '2025-04-08', 290.00, 290.00, 500.00, 0.00, 790.00, '2025-04-08 12:47:26'),
(7, 5, '2025-04-08', 300.00, 300.00, 500.00, 0.00, 800.00, '2025-04-08 17:07:31'),
(8, 5, '2025-04-08', 300.00, 400.00, 100.00, -100.00, 500.00, '2025-04-08 17:09:15'),
(9, 5, '2025-04-08', 800.00, 800.00, 100.00, 0.00, 900.00, '2025-04-08 23:27:57'),
(10, 5, '2025-04-09', 400.00, 400.00, 500.00, 0.00, 900.00, '2025-04-09 00:08:07');

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
  `fingerlings_quantity` int(11) DEFAULT NULL,
  `fingerlings_unit_price` decimal(15,2) DEFAULT NULL,
  `fingerlings_total_cost` decimal(15,2) DEFAULT NULL,
  `starter_feed_quantity` decimal(10,2) DEFAULT NULL,
  `starter_feed_unit_price` decimal(15,2) DEFAULT NULL,
  `starter_feed_total_cost` decimal(15,2) DEFAULT NULL,
  `grower_feed_quantity` decimal(10,2) DEFAULT NULL,
  `grower_feed_unit_price` decimal(15,2) DEFAULT NULL,
  `grower_feed_total_cost` decimal(15,2) DEFAULT NULL,
  `basin_quantity` int(11) DEFAULT NULL,
  `basin_unit_price` decimal(15,2) DEFAULT NULL,
  `basin_total_cost` decimal(15,2) DEFAULT NULL,
  `fish_nets_quantity` int(11) DEFAULT NULL,
  `fish_nets_unit_price` decimal(15,2) DEFAULT NULL,
  `fish_nets_total_cost` decimal(15,2) DEFAULT NULL,
  `water_quality_meter_quantity` int(11) DEFAULT NULL,
  `water_quality_meter_unit_price` decimal(15,2) DEFAULT NULL,
  `water_quality_meter_total_cost` decimal(15,2) DEFAULT NULL,
  `pond_pumps_quantity` int(11) DEFAULT NULL,
  `pond_pumps_unit_price` decimal(15,2) DEFAULT NULL,
  `pond_pumps_total_cost` decimal(15,2) DEFAULT NULL,
  `pond_aeration_quantity` int(11) DEFAULT NULL,
  `pond_aeration_unit_price` decimal(15,2) DEFAULT NULL,
  `pond_aeration_total_cost` decimal(15,2) DEFAULT NULL,
  `pond_vacuum_quantity` int(11) DEFAULT NULL,
  `pond_vacuum_unit_price` decimal(15,2) DEFAULT NULL,
  `pond_vacuum_total_cost` decimal(15,2) DEFAULT NULL,
  `fencing_quantity` int(11) DEFAULT NULL,
  `fencing_unit_price` decimal(15,2) DEFAULT NULL,
  `fencing_total_cost` decimal(15,2) DEFAULT NULL,
  `transport_senegal_quantity` int(11) DEFAULT NULL,
  `transport_senegal_unit_price` decimal(15,2) DEFAULT NULL,
  `transport_senegal_total_cost` decimal(15,2) DEFAULT NULL,
  `transport_gambia_quantity` int(11) DEFAULT NULL,
  `transport_gambia_unit_price` decimal(15,2) DEFAULT NULL,
  `transport_gambia_total_cost` decimal(15,2) DEFAULT NULL,
  `water_quantity` int(11) DEFAULT NULL,
  `water_unit_price` decimal(15,2) DEFAULT NULL,
  `water_total_cost` decimal(15,2) DEFAULT NULL,
  `electricity_quantity` int(11) DEFAULT NULL,
  `electricity_unit_price` decimal(15,2) DEFAULT NULL,
  `electricity_total_cost` decimal(15,2) DEFAULT NULL,
  `maintenance_cost` decimal(15,2) DEFAULT NULL,
  `rent_cost` decimal(15,2) DEFAULT NULL,
  `refrigeration_cost` decimal(15,2) DEFAULT NULL,
  `marketing_cost` decimal(15,2) DEFAULT NULL,
  `medication_cost` decimal(15,2) DEFAULT NULL,
  `hosting_cost` decimal(15,2) DEFAULT NULL,
  `electrical_installation_cost` decimal(15,2) DEFAULT NULL,
  `business_registration_cost` decimal(15,2) DEFAULT NULL,
  `insurance_cost` decimal(15,2) DEFAULT NULL,
  `tax_cost` decimal(15,2) DEFAULT NULL,
  `total_fingerlings_cost` decimal(15,2) DEFAULT NULL,
  `total_feed_cost` decimal(15,2) DEFAULT NULL,
  `total_material_cost` decimal(15,2) DEFAULT NULL,
  `total_transport_cost` decimal(15,2) DEFAULT NULL,
  `total_services_cost` decimal(15,2) DEFAULT NULL,
  `running_cost` decimal(15,2) DEFAULT NULL,
  `smoker_ovon_quantity` int(11) DEFAULT NULL,
  `smoker_ovon_unit_price` decimal(11,0) DEFAULT NULL,
  `smoker_ovon_total_cost` float DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detailed_costs`
--

INSERT INTO `detailed_costs` (`id`, `fish_type_id`, `date_recorded`, `fingerlings_quantity`, `fingerlings_unit_price`, `fingerlings_total_cost`, `starter_feed_quantity`, `starter_feed_unit_price`, `starter_feed_total_cost`, `grower_feed_quantity`, `grower_feed_unit_price`, `grower_feed_total_cost`, `basin_quantity`, `basin_unit_price`, `basin_total_cost`, `fish_nets_quantity`, `fish_nets_unit_price`, `fish_nets_total_cost`, `water_quality_meter_quantity`, `water_quality_meter_unit_price`, `water_quality_meter_total_cost`, `pond_pumps_quantity`, `pond_pumps_unit_price`, `pond_pumps_total_cost`, `pond_aeration_quantity`, `pond_aeration_unit_price`, `pond_aeration_total_cost`, `pond_vacuum_quantity`, `pond_vacuum_unit_price`, `pond_vacuum_total_cost`, `fencing_quantity`, `fencing_unit_price`, `fencing_total_cost`, `transport_senegal_quantity`, `transport_senegal_unit_price`, `transport_senegal_total_cost`, `transport_gambia_quantity`, `transport_gambia_unit_price`, `transport_gambia_total_cost`, `water_quantity`, `water_unit_price`, `water_total_cost`, `electricity_quantity`, `electricity_unit_price`, `electricity_total_cost`, `maintenance_cost`, `rent_cost`, `refrigeration_cost`, `marketing_cost`, `medication_cost`, `hosting_cost`, `electrical_installation_cost`, `business_registration_cost`, `insurance_cost`, `tax_cost`, `total_fingerlings_cost`, `total_feed_cost`, `total_material_cost`, `total_transport_cost`, `total_services_cost`, `running_cost`, `smoker_ovon_quantity`, `smoker_ovon_unit_price`, `smoker_ovon_total_cost`) VALUES
(7, 1, '2025-04-05', 5, 6.00, 30.00, 6.00, 6.00, 36.00, 4.00, 4.98, 19.92, 0, 0.00, 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 30.00, 55.92, 0.00, 0.00, 0.00, 85.92, NULL, 0, 0),
(8, 2, '2025-04-08', 3, 3.00, 9.00, 3.00, 3.00, 9.00, 3.00, 3.00, 9.00, 3, 3.00, 9.00, 3, 3.00, 9.00, 3, 3.00, 9.00, 3, 3.00, 9.00, 3, 3.00, 9.00, 3, 3.00, 9.00, 3, 3.00, 9.00, 3, 3.00, 9.00, 3, 3.00, 9.00, 3, 3.00, 9.00, 3, 3.00, 9.00, 3.00, 3.00, 3.00, 3.00, 3.00, 3.00, 3.00, 3.00, 3.00, 3.00, 9.00, 18.00, 63.00, 18.00, 48.00, 156.00, NULL, 3, 0);

-- --------------------------------------------------------

--
-- Table structure for table `employee_permissions`
--

CREATE TABLE `employee_permissions` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `can_sell` tinyint(1) DEFAULT 1,
  `can_record_fatality` tinyint(1) DEFAULT 1,
  `can_process_orders` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_permissions`
--

INSERT INTO `employee_permissions` (`id`, `user_id`, `can_sell`, `can_record_fatality`, `can_process_orders`) VALUES
(1, 5, 1, 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `employee_salaries`
--

CREATE TABLE `employee_salaries` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `monthly_salary` decimal(10,2) NOT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employee_salaries`
--

INSERT INTO `employee_salaries` (`id`, `employee_id`, `monthly_salary`, `effective_date`, `created_at`) VALUES
(1, 5, 20.00, '2025-04-08', '2025-04-08 15:27:30');

-- --------------------------------------------------------

--
-- Table structure for table `fish_fatalities`
--

CREATE TABLE `fish_fatalities` (
  `id` int(11) NOT NULL,
  `fish_type_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `date_recorded` date NOT NULL,
  `cause` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `recorded_by` int(11) NOT NULL,
  `recorded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `fish_fatalities`
--

INSERT INTO `fish_fatalities` (`id`, `fish_type_id`, `quantity`, `date_recorded`, `cause`, `notes`, `recorded_by`, `recorded_at`) VALUES
(1, 1, 1, '2025-04-04', 'water_quality', '', 1, '2025-04-04 13:28:29'),
(2, 1, 4, '2025-04-04', '', 'eee', 1, '2025-04-04 13:30:29'),
(3, 2, 4, '2025-04-04', 'water_quality', 'eee', 1, '2025-04-04 13:33:29'),
(4, 2, 4, '2025-04-04', 'water_quality', 'wwwww', 5, '2025-04-04 16:06:27');

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
(1, 'Catfish', 'Freshwater catfish from our pond', 100.00, 'uploads/fish_images/67f4744c3de1d_fish.jpg'),
(2, 'Tilapia', 'High-quality tilapia fish', 200.00, 'uploads/fish_images/67f47455de3e2_fish2.jpg'),
(6, 'smoke Catfish', 'this chimical free smoke fish', 200.00, 'uploads/fish_images/67f517f0a1f43_header_fishes_image.jpg');

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
(1, 1, 25.00, '2025-04-09 00:35:15'),
(2, 2, 9.00, '2025-04-09 00:30:29'),
(6, 6, 1.60, '2025-04-08 12:34:56');

-- --------------------------------------------------------

--
-- Table structure for table `locations`
--

CREATE TABLE `locations` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `address` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `locations`
--

INSERT INTO `locations` (`id`, `name`, `address`, `created_at`) VALUES
(1, 'brikama', 'kuloro', '2025-04-04 20:34:06');

-- --------------------------------------------------------

--
-- Table structure for table `location_inventory`
--

CREATE TABLE `location_inventory` (
  `id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `fish_type_id` int(11) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `last_updated` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `location_inventory`
--

INSERT INTO `location_inventory` (`id`, `location_id`, `employee_id`, `fish_type_id`, `quantity`, `last_updated`) VALUES
(1, 1, 5, 1, 28.00, '2025-04-09 00:35:15'),
(2, 1, 5, 2, 14.00, '2025-04-09 00:30:29');

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
(2, 2, '2025-03-26 16:33:53', 'completed', 200.00),
(6, 1, '2025-04-04 03:17:02', 'completed', 1000.00),
(8, 5, '2025-04-04 15:22:45', 'completed', 100.00),
(9, 1, '2025-04-04 15:38:32', 'cancelled', 200.00),
(10, 5, '2025-04-04 15:43:55', 'completed', 100.00),
(11, 2, '2025-04-04 15:44:23', 'cancelled', 200.00),
(12, 5, '2025-04-04 15:50:06', 'completed', 200.00),
(13, 5, '2025-04-04 15:50:43', 'cancelled', 200.00),
(14, 5, '2025-04-04 15:51:59', 'cancelled', 200.00),
(15, 5, '2025-04-04 15:53:30', 'cancelled', 0.00),
(16, 5, '2025-04-04 16:12:46', 'cancelled', 0.00),
(17, 2, '2025-04-04 16:22:35', 'completed', 200.00),
(19, 2, '2025-04-04 17:17:06', 'completed', 300.00),
(31, 2, '2025-04-05 03:44:14', 'cancelled', 200.00),
(35, 2, '2025-04-05 14:44:42', 'cancelled', 200.00),
(38, 5, '2025-04-06 03:29:25', 'cancelled', 0.00),
(39, 1, '2025-04-06 03:46:11', 'cancelled', 0.00),
(40, 5, '2025-04-06 03:47:09', 'cancelled', 0.00),
(41, 2, '2025-04-06 13:19:16', 'cancelled', 100.00),
(42, 2, '2025-04-06 19:22:37', 'cancelled', 200.00),
(43, 2, '2025-04-06 19:25:56', 'cancelled', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `order_id` int(11) NOT NULL,
  `fish_type_id` int(11) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`order_id`, `fish_type_id`, `quantity_kg`, `unit_price`) VALUES
(24, 1, 2.00, 100.00),
(31, 1, 2.00, 100.00),
(35, 1, 2.00, 100.00),
(38, 1, 1.00, 100.00),
(39, 1, 2.00, 100.00),
(40, 1, 2.00, 100.00),
(41, 1, 1.00, 100.00),
(42, 1, 2.00, 100.00),
(43, 1, 1.00, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `partners`
--

CREATE TABLE `partners` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `logo_url` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `partners`
--

INSERT INTO `partners` (`id`, `name`, `logo_url`, `website`) VALUES
(1, 'Medical Research Councile Unit The Gambia', 'uploads/partners/67f488b3f011d_WhatsApp Image 2024-10-13 at 18.48.13_cb0e406a.jpg', 'https://www.facebook.com');

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `sale_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `payment_method` enum('cash','credit','mobile_money') DEFAULT 'cash'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sales`
--

INSERT INTO `sales` (`id`, `employee_id`, `total_amount`, `sale_date`, `payment_method`) VALUES
(1, 1, 200.00, '2025-04-04 13:23:48', 'cash'),
(2, 1, 200.00, '2025-04-04 15:40:58', 'cash'),
(3, 5, 24000.00, '2025-04-04 16:13:53', 'cash'),
(4, 1, 400.00, '2025-04-04 16:48:31', 'cash'),
(5, 1, 400.00, '2025-04-04 16:48:54', 'cash'),
(6, 5, 100.00, '2025-04-04 21:06:45', 'cash'),
(7, 5, 200.00, '2025-04-05 15:49:27', 'cash'),
(8, 5, 100.00, '2025-04-05 22:17:54', 'cash'),
(9, 5, 200.00, '2025-04-05 22:54:35', 'cash'),
(10, 5, 900.00, '2025-04-05 23:16:43', 'cash'),
(11, 5, 100.00, '2025-04-06 00:29:57', 'cash'),
(12, 5, 400.00, '2025-04-06 00:35:42', 'credit'),
(13, 1, 100.00, '2025-04-06 00:50:02', 'cash'),
(14, 5, 100.00, '2025-04-06 01:17:45', 'cash'),
(16, 5, 200.00, '2025-04-06 02:06:27', 'cash'),
(17, 5, 200.00, '2025-04-06 02:10:56', 'cash'),
(25, 5, 400.00, '2025-04-09 00:30:02', 'cash'),
(27, 5, 100.00, '2025-04-09 00:35:15', 'cash');

-- --------------------------------------------------------

--
-- Table structure for table `sale_items`
--

CREATE TABLE `sale_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `fish_type_id` int(11) NOT NULL,
  `quantity_kg` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `sale_items`
--

INSERT INTO `sale_items` (`id`, `sale_id`, `fish_type_id`, `quantity_kg`, `unit_price`) VALUES
(1, 1, 1, 2.00, 100.00),
(2, 2, 1, 2.00, 100.00),
(3, 3, 2, 120.00, 200.00),
(4, 4, 1, 4.00, 100.00),
(5, 5, 2, 2.00, 200.00),
(6, 6, 1, 1.00, 100.00),
(7, 7, 1, 2.00, 100.00),
(8, 8, 1, 1.00, 100.00),
(9, 9, 2, 1.00, 200.00),
(10, 10, 1, 3.00, 100.00),
(11, 10, 2, 3.00, 200.00),
(12, 11, 1, 1.00, 100.00),
(13, 12, 2, 2.00, 200.00),
(14, 13, 1, 1.00, 100.00),
(15, 14, 1, 1.00, 100.00),
(17, 16, 2, 1.00, 200.00),
(18, 17, 2, 1.00, 200.00),
(27, 25, 1, 4.00, 100.00),
(29, 27, 1, 1.00, 100.00);

-- --------------------------------------------------------

--
-- Table structure for table `shop_locations`
--

CREATE TABLE `shop_locations` (
  `id` int(11) NOT NULL,
  `employee_id` int(11) NOT NULL,
  `location_name` varchar(100) NOT NULL,
  `region` varchar(100) NOT NULL,
  `contact_phone` varchar(20) NOT NULL,
  `opening_time` time NOT NULL,
  `closing_time` time NOT NULL,
  `is_open` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `shop_locations`
--

INSERT INTO `shop_locations` (`id`, `employee_id`, `location_name`, `region`, `contact_phone`, `opening_time`, `closing_time`, `is_open`, `created_at`, `updated_at`, `latitude`, `longitude`) VALUES
(3, 1, 'admin shop', 'Kuloro', '3114881', '22:00:00', '23:00:00', 1, '2025-04-08 21:59:19', '2025-04-08 22:12:02', 13.33164300, -16.00878100),
(4, 5, 'brikama', 'Kombo ', '3114881', '22:00:00', '23:00:00', 1, '2025-04-08 22:05:46', '2025-04-08 22:08:36', 13.33156200, -16.00862100);

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `position` varchar(100) NOT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `facebook` varchar(255) DEFAULT NULL,
  `twitter` varchar(255) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `position_order` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `name`, `position`, `photo_url`, `facebook`, `twitter`, `linkedin`, `position_order`) VALUES
(2, 'abdoulie Bah', 'Data Manager ', 'uploads/team/67f4887be5bcd_Screenshot 2024-09-23 204000.png', 'https://www.facebook.com', 'https://www.facebook.com', 'https://www.facebook.com', 0);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('admin','employee','customer') DEFAULT 'customer',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `email`, `role`, `created_at`) VALUES
(1, 'admin@abliebah', '$2b$12$6AK4Efc8uTcnOnJOM2ueHeanljd6oke9VgT7WHRD8tFrWUGGTnMVC', 'admin@fishfarm.com', 'admin', '2025-03-26 11:13:59'),
(2, 'abliebah', '$2y$10$riEbnkvMN/CKqWY9kCasQOGYlWtp955c8F.U6x2QQHzyc8pX3Wnv6', 'abdouliebah@mrc.gm', 'customer', '2025-03-26 11:42:21'),
(5, 'employees', '$2y$10$pDKmtrSRNyiDxz5tv8NJpeMFlE1WHVxEfZkvdAulloDZZ7Gb8Uk9a', 'employees@sales.com', 'employee', '2025-04-04 15:09:25');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `cash_reconciliations`
--
ALTER TABLE `cash_reconciliations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

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
-- Indexes for table `employee_permissions`
--
ALTER TABLE `employee_permissions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `employee_salaries`
--
ALTER TABLE `employee_salaries`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `fish_fatalities`
--
ALTER TABLE `fish_fatalities`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fish_type_id` (`fish_type_id`),
  ADD KEY `recorded_by` (`recorded_by`);

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
-- Indexes for table `locations`
--
ALTER TABLE `locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `location_inventory`
--
ALTER TABLE `location_inventory`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `location_id` (`location_id`,`employee_id`,`fish_type_id`),
  ADD KEY `employee_id` (`employee_id`),
  ADD KEY `fish_type_id` (`fish_type_id`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `partners`
--
ALTER TABLE `partners`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `fish_type_id` (`fish_type_id`);

--
-- Indexes for table `shop_locations`
--
ALTER TABLE `shop_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `employee_id` (`employee_id`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`);

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
-- AUTO_INCREMENT for table `cash_reconciliations`
--
ALTER TABLE `cash_reconciliations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `employee_permissions`
--
ALTER TABLE `employee_permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `employee_salaries`
--
ALTER TABLE `employee_salaries`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `fish_fatalities`
--
ALTER TABLE `fish_fatalities`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `fish_types`
--
ALTER TABLE `fish_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `locations`
--
ALTER TABLE `locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `location_inventory`
--
ALTER TABLE `location_inventory`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `partners`
--
ALTER TABLE `partners`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `sale_items`
--
ALTER TABLE `sale_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `shop_locations`
--
ALTER TABLE `shop_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `cash_reconciliations`
--
ALTER TABLE `cash_reconciliations`
  ADD CONSTRAINT `cash_reconciliations_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `detailed_costs`
--
ALTER TABLE `detailed_costs`
  ADD CONSTRAINT `detailed_costs_ibfk_1` FOREIGN KEY (`fish_type_id`) REFERENCES `fish_types` (`id`);

--
-- Constraints for table `employee_permissions`
--
ALTER TABLE `employee_permissions`
  ADD CONSTRAINT `employee_permissions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `employee_salaries`
--
ALTER TABLE `employee_salaries`
  ADD CONSTRAINT `employee_salaries_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `fish_fatalities`
--
ALTER TABLE `fish_fatalities`
  ADD CONSTRAINT `fish_fatalities_ibfk_1` FOREIGN KEY (`fish_type_id`) REFERENCES `fish_types` (`id`),
  ADD CONSTRAINT `fish_fatalities_ibfk_2` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`fish_type_id`) REFERENCES `fish_types` (`id`);

--
-- Constraints for table `location_inventory`
--
ALTER TABLE `location_inventory`
  ADD CONSTRAINT `location_inventory_ibfk_1` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`),
  ADD CONSTRAINT `location_inventory_ibfk_2` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`),
  ADD CONSTRAINT `location_inventory_ibfk_3` FOREIGN KEY (`fish_type_id`) REFERENCES `fish_types` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `sale_items`
--
ALTER TABLE `sale_items`
  ADD CONSTRAINT `sale_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`),
  ADD CONSTRAINT `sale_items_ibfk_2` FOREIGN KEY (`fish_type_id`) REFERENCES `fish_types` (`id`);

--
-- Constraints for table `shop_locations`
--
ALTER TABLE `shop_locations`
  ADD CONSTRAINT `shop_locations_ibfk_1` FOREIGN KEY (`employee_id`) REFERENCES `users` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
