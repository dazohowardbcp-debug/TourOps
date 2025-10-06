-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 29, 2025 at 07:51 AM
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
-- Database: `tourops`
--

-- --------------------------------------------------------

--
-- Table structure for table `bookings`
--

CREATE TABLE `bookings` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `guest_name` varchar(150) NOT NULL,
  `guest_email` varchar(150) NOT NULL,
  `package_id` int(11) NOT NULL,
  `pax` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `travel_date` date NOT NULL,
  `special_requests` text DEFAULT NULL,
  `payment_status` enum('Pending','Paid','Partial','Cancelled') DEFAULT 'Pending',
  `status` varchar(30) DEFAULT 'Pending',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bookings`
--

INSERT INTO `bookings` (`id`, `user_id`, `guest_name`, `guest_email`, `package_id`, `pax`, `total`, `travel_date`, `special_requests`, `payment_status`, `status`, `notes`, `created_at`) VALUES
(1, 2, 'Howard', 'qeqweqe@gmail.com', 1, 5, 149500.00, '2026-04-02', 'no', 'Paid', 'Cancelled', 'wala\n\nAdmin Update (2025-08-27 15:44:32): ', '2025-08-27 07:21:31'),
(2, 4, 'howard', 'queckqueck@gmail.com', 2, 5, 229500.00, '2026-02-24', 'wala', 'Paid', 'Confirmed', '\n\nAdmin Update (2025-08-27 15:47:36): \n\nAdmin Update (2025-08-27 17:36:49): ', '2025-08-27 07:47:06'),
(3, NULL, 'Juan Dela Cruz', 'seed.completed@tourops.local', 1, 2, 59800.00, '2025-10-11', 'Near window seat', 'Paid', 'Completed', NULL, '2025-08-27 09:40:33'),
(4, NULL, 'Maria Santos', 'seed.cancelled@tourops.local', 2, 3, 137700.00, '2025-10-26', 'Vegetarian meal', 'Cancelled', 'Cancelled', NULL, '2025-08-27 09:40:33'),
(5, NULL, 'Pedro Reyes', 'seed.confirmed@tourops.local', 3, 1, 15900.00, '2025-09-26', 'Aisle seat', 'Partial', 'Confirmed', NULL, '2025-08-27 09:40:33'),
(6, 4, 'Howard', 'qeqweqdsadse@gmail.com', 15, 1, 5990.00, '2026-04-02', '', 'Pending', 'Cancelled', '', '2025-08-27 13:52:42');

-- --------------------------------------------------------

--
-- Table structure for table `packages`
--

CREATE TABLE `packages` (
  `id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `days` int(11) NOT NULL,
  `duration` varchar(50) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `highlights` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `location` varchar(200) DEFAULT NULL,
  `group_size` int(11) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `packages`
--

INSERT INTO `packages` (`id`, `title`, `description`, `days`, `duration`, `price`, `highlights`, `image`, `image_url`, `location`, `group_size`, `created_at`) VALUES
(1, 'Batanes Escape — 4 Days', NULL, 4, NULL, 29900.00, 'Scenic cliffs,Local homestay,Lighthouse tour', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', NULL, NULL, 1, '2025-08-27 07:17:05'),
(2, 'Palawan Paradise — 6 Days', NULL, 6, NULL, 45900.00, 'Island-hopping,Kayaking,Underground river', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e', NULL, NULL, 1, '2025-08-27 07:17:05'),
(3, 'Cebu Adventure — 3 Days', NULL, 3, NULL, 15900.00, 'Waterfalls,Whale shark swim,City tour', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee', NULL, NULL, 1, '2025-08-27 07:17:05'),
(4, 'Siargao Surf & Sand — 5 Days', NULL, 5, NULL, 32900.00, 'Cloud 9 surf,Island hopping,Mangrove tour', 'https://images.unsplash.com/photo-1500375592092-40eb2168fd21', NULL, NULL, 1, '2025-08-27 09:18:21'),
(5, 'Bohol Countryside — 4 Days', NULL, 4, NULL, 24900.00, 'Chocolate Hills,Tarsier sanctuary,Loboc river cruise', 'https://images.unsplash.com/photo-1526779259212-939e64788e3c', NULL, NULL, 1, '2025-08-27 09:18:21'),
(6, 'Boracay Bliss — 3 Days', NULL, 3, NULL, 21900.00, 'White beach,Sunset sailing,Parasailing', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e', NULL, NULL, 1, '2025-08-27 09:18:21'),
(7, 'Ilocos Heritage — 3 Days', NULL, 3, NULL, 17900.00, 'Vigan calesa,Paoay sand dunes,Kapurpuran rock', 'https://images.unsplash.com/photo-1500534314209-a25ddb2bd429', NULL, NULL, 1, '2025-08-27 09:18:21'),
(8, 'Davao Discovery — 4 Days', NULL, 4, NULL, 23900.00, 'Eden park,Philippine eagle,Samal island', 'https://images.unsplash.com/photo-1496417263034-38ec4f0b665a', NULL, NULL, 1, '2025-08-27 09:18:21'),
(9, 'Baguio Getaway — 3 Days', NULL, 3, NULL, 16900.00, 'Mines View,Burnham Park,Strawberry farm', 'https://images.unsplash.com/photo-1501785888041-af3ef285b470', NULL, NULL, 1, '2025-08-27 09:18:21'),
(10, 'Camiguin Island Loop — 4 Days', NULL, 4, NULL, 25900.00, 'White island,Sunken cemetery,Waterfalls', 'https://images.unsplash.com/photo-1469474968028-56623f02e42e', NULL, NULL, 1, '2025-08-27 09:18:21'),
(11, 'Coron Wrecks & Lagoons — 4 Days', NULL, 4, NULL, 34900.00, 'Kayangan lake,Twin lagoon,Wreck diving', 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e', NULL, NULL, 1, '2025-08-27 09:18:21'),
(12, 'Sagada Escape — 3 Days', NULL, 3, NULL, 18900.00, 'Hanging coffins,Sumaguing cave,Sunrise at Kiltepan', 'https://images.unsplash.com/photo-1491553895911-0055eca6402d', NULL, NULL, 1, '2025-08-27 09:18:21'),
(13, 'Zambales Cove Camping — 2 Days', NULL, 2, NULL, 9990.00, 'Anawangin cove,Bonfire night,Island hopping', 'https://images.unsplash.com/photo-1493558103817-58b2924bce98', NULL, NULL, 1, '2025-08-27 09:18:21'),
(14, 'Subic Adventure — 2 Days', NULL, 2, NULL, 11990.00, 'Zoobic safari,Ocean adventure,Duty-free shopping', 'https://images.unsplash.com/photo-1482192505345-5655af888cc4', NULL, NULL, 1, '2025-08-27 09:18:21'),
(15, 'Manila City Highlights — 1 Day', NULL, 1, NULL, 5990.00, 'Intramuros,Binondo food trip,Sunset at Baywalk', 'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee', NULL, NULL, 1, '2025-08-27 09:18:21');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `fullname` varchar(150) DEFAULT NULL,
  `dob` date DEFAULT NULL,
  `gender` varchar(20) DEFAULT NULL,
  `nationality` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `mobile` varchar(30) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `two_factor_enabled` tinyint(1) DEFAULT 0,
  `notify_email` tinyint(1) DEFAULT 1,
  `notify_sms` tinyint(1) DEFAULT 0,
  `newsletter` tinyint(1) DEFAULT 0,
  `emergency_name` varchar(150) DEFAULT NULL,
  `emergency_relation` varchar(100) DEFAULT NULL,
  `emergency_phone` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `is_admin` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `name`, `fullname`, `dob`, `gender`, `nationality`, `email`, `mobile`, `address`, `profile_image`, `username`, `two_factor_enabled`, `notify_email`, `notify_sms`, `newsletter`, `emergency_name`, `emergency_relation`, `emergency_phone`, `password`, `is_admin`, `created_at`) VALUES
(2, 'Howard Freed', 'Howard Freed', '2002-07-09', 'Male', 'Philippine', 'examplehowarddazo@gmail.com', '0948795135', '12 dasdasd sdasdas dasdasd asdasd', NULL, 'howard', 0, 1, 0, 0, 'qweqweqw', 'parent', '09211212564', '$2y$10$VAs5EAXpuvjyRaEjf369q.zyG22sv8et/QLgTDYBRFWzXgj6zJdyS', 1, '2025-08-27 07:18:31'),
(4, 'Crazy Macaroro', 'Crazy Macaroro', '1999-04-11', 'Female', 'Bangladeshi', 'dazohoward@gmail.com', '09274812906', 'asd asdasd', 'assets/uploads/avatar_4_1756360536_68afef586ed05.png', 'asd', 0, 1, 0, 0, 'linda creo', 'parent', '09274812905', '$2y$10$EnQMMbbht/Ausbj4UMrZW.KakTYH3tpXZ6x8CcE2ogGaMzUyinPQu', 0, '2025-08-27 07:46:00'),
(5, 'Howard Fre', 'Howard Fre', '2002-07-09', 'Male', 'Bahamian', 'examplehowarddazos@gmail.com', '0948795135', '34 asdas asdfsdaf fdsadfasdfasdf', NULL, 'howards', 0, 1, 0, 0, 'qweqweqw', 'parent', '09211212564', '$2y$10$h9FFtMElO/u07KZPH4Jjz.7nqdJ/ldRwO2CNHTxSK72/75w8wTfUy', 0, '2025-08-28 03:54:07'),
(6, 'Admin', NULL, NULL, NULL, NULL, 'admin@tourops.local', NULL, NULL, NULL, NULL, 0, 1, 0, 0, NULL, NULL, NULL, '$2y$10$A1y3K6sGz1m/0wQJq1q3yO1zNn0o8i9Zk7cE3tF2H2aPqW7vYkD7e', 1, '2025-08-28 04:02:22');

--
-- Triggers `users`
--
DELIMITER $$
CREATE TRIGGER `users_bi_name_from_fullname` BEFORE INSERT ON `users` FOR EACH ROW SET NEW.name = IFNULL(NULLIF(NEW.name, ''), NEW.fullname)
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `users_bu_name_from_fullname` BEFORE UPDATE ON `users` FOR EACH ROW SET NEW.name = IFNULL(NULLIF(NEW.name, ''), NEW.fullname)
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `user_logins`
--

CREATE TABLE `user_logins` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_logins`
--

INSERT INTO `user_logins` (`id`, `user_id`, `ip`, `user_agent`, `created_at`) VALUES
(1, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 04:35:09'),
(2, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 04:35:29'),
(3, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 04:35:44'),
(4, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 04:39:42'),
(5, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 04:46:12'),
(6, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:44'),
(7, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:46'),
(8, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:47'),
(9, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:47'),
(10, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:47'),
(11, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:47'),
(12, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:47'),
(13, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:48'),
(14, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:48'),
(15, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:49'),
(16, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:49'),
(17, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:50'),
(18, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:54'),
(19, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:11:55'),
(20, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:12:06'),
(21, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:12:06'),
(22, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:12:07'),
(23, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:12:07'),
(24, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:12:07'),
(25, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:12:07'),
(26, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:13:55'),
(27, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:13:57'),
(28, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:13:57'),
(29, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:13:57'),
(30, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:13:58'),
(31, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:13:59'),
(32, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:13:59'),
(33, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:14:00'),
(34, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:17:04'),
(35, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:17:31'),
(36, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:21:00'),
(37, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:21:18'),
(38, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 05:21:42'),
(39, 5, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:32:02'),
(40, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:33:35'),
(41, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 05:42:47'),
(42, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-08-28 05:48:34'),
(43, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-28 11:47:41'),
(44, 4, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-08-29 05:40:23');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bookings`
--
ALTER TABLE `bookings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `package_id` (`package_id`),
  ADD KEY `idx_travel_date` (`travel_date`),
  ADD KEY `idx_payment_status` (`payment_status`);

--
-- Indexes for table `packages`
--
ALTER TABLE `packages`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `uniq_username` (`username`);

--
-- Indexes for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `bookings`
--
ALTER TABLE `bookings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `packages`
--
ALTER TABLE `packages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_logins`
--
ALTER TABLE `user_logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bookings`
--
ALTER TABLE `bookings`
  ADD CONSTRAINT `bookings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `bookings_ibfk_2` FOREIGN KEY (`package_id`) REFERENCES `packages` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_logins`
--
ALTER TABLE `user_logins`
  ADD CONSTRAINT `user_logins_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
