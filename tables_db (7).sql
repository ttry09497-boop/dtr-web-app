-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 12, 2025 at 05:00 PM
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
-- Database: `tables_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `dtr_logs`
--

CREATE TABLE `dtr_logs` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` enum('time_in','time_out') NOT NULL,
  `location_id` int(11) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `outside_geofence` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp(),
  `working_hours` decimal(10,2) DEFAULT NULL,
  `salary` decimal(10,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `dtr_logs`
--

INSERT INTO `dtr_logs` (`id`, `user_id`, `action`, `location_id`, `timestamp`, `latitude`, `longitude`, `outside_geofence`, `created_at`, `working_hours`, `salary`) VALUES
(12, 37, 'time_out', 5, '2025-12-12 06:04:01', NULL, NULL, 0, '2025-12-12 14:04:01', NULL, NULL),
(13, 37, 'time_in', 5, '2025-12-12 06:05:01', NULL, NULL, 0, '2025-12-12 14:05:01', 8.00, 320.00),
(14, 37, 'time_out', 5, '2025-12-12 06:05:50', NULL, NULL, 0, '2025-12-12 14:05:50', NULL, NULL),
(15, 38, 'time_in', 5, '2025-12-12 08:48:29', NULL, NULL, 0, '2025-12-12 16:48:29', 6.00, 300.00),
(16, 38, 'time_out', 5, '2025-12-12 08:49:09', NULL, NULL, 0, '2025-12-12 16:49:09', NULL, NULL),
(17, 38, 'time_in', 5, '2025-12-12 15:47:01', NULL, NULL, 0, '2025-12-12 23:47:01', NULL, NULL),
(18, 38, 'time_out', 5, '2025-12-12 15:56:08', NULL, NULL, 0, '2025-12-12 23:56:08', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `groups`
--

CREATE TABLE `groups` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `groups`
--

INSERT INTO `groups` (`id`, `name`, `created_at`) VALUES
(39, 'baliwasan team', '2025-12-11 07:58:34'),
(40, 'lol', '2025-12-11 07:59:13'),
(41, 'lol', '2025-12-11 07:59:45'),
(43, 'gg', '2025-12-12 08:47:45'),
(44, 'edlyn group', '2025-12-12 10:09:03');

-- --------------------------------------------------------

--
-- Table structure for table `group_locations`
--

CREATE TABLE `group_locations` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_locations`
--

INSERT INTO `group_locations` (`id`, `group_id`, `location_id`) VALUES
(63, 40, 22),
(66, 41, 19),
(67, 41, 22),
(68, 41, 11),
(73, 39, 5),
(75, 44, 11),
(77, 43, 5);

-- --------------------------------------------------------

--
-- Table structure for table `group_users`
--

CREATE TABLE `group_users` (
  `id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `group_users`
--

INSERT INTO `group_users` (`id`, `group_id`, `user_id`) VALUES
(117, 39, 33),
(118, 39, 37),
(119, 39, 35),
(122, 40, 31),
(123, 40, 30),
(128, 41, 36),
(129, 43, 38),
(130, 44, 32);

-- --------------------------------------------------------

--
-- Table structure for table `tagged_locations`
--

CREATE TABLE `tagged_locations` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `radius` int(11) DEFAULT 50,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tagged_locations`
--

INSERT INTO `tagged_locations` (`id`, `name`, `latitude`, `longitude`, `radius`, `created_at`) VALUES
(5, 'san roque center', 6.94227458, 122.04806328, 100, '2025-08-20 03:27:33'),
(11, 'paso', 6.94764224, 122.07312584, 50, '2025-08-22 01:42:26'),
(19, 'luns', 6.95624740, 122.09072113, 50, '2025-09-07 17:59:33'),
(22, 'jbgfg', 6.96084812, 122.14874268, 50, '2025-09-16 15:22:38');

-- --------------------------------------------------------

--
-- Table structure for table `tasks`
--

CREATE TABLE `tasks` (
  `id` int(11) NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `group_id` int(11) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tasks`
--

INSERT INTO `tasks` (`id`, `task_name`, `created_at`, `group_id`, `due_date`, `description`, `status`) VALUES
(5, 'adada', '2025-12-10 07:10:05', 39, '2025-12-13', 'feeSAfse', 0),
(9, 'eres', '2025-12-12 08:48:12', 43, '2025-12-13', 'kagsdkja', 0);

-- --------------------------------------------------------

--
-- Table structure for table `task_assignments`
--

CREATE TABLE `task_assignments` (
  `id` int(11) NOT NULL,
  `task_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `location_id` int(11) NOT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','user') DEFAULT 'user',
  `position` varchar(255) NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `status` enum('available','assigned') DEFAULT 'available',
  `salary_rate` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `phone`, `password`, `role`, `position`, `salary`, `latitude`, `longitude`, `status`, `salary_rate`) VALUES
(1, 'admin', NULL, '$2y$10$WCYFPslqs2mXa/VAbwdLG.Tbr.l.H4gnZhpSIChXgpdDArUL/W.Fy', 'admin', '', 0.00, NULL, NULL, 'available', 0.00),
(30, 'super', '+639658611932', '$2y$10$.r8P8Lfy91DyduJnm79Xh.R1jt3YiDa1ZBM0R.rWbAEYdEZQgZY6G', 'admin', '', 0.00, NULL, NULL, 'available', 0.00),
(31, 'steven ', '+639658611932', '$2y$10$pGtg.qkzT.Q9twzQvJ9w..RmS/aGr1IJbKU..mKQGOofNVYdffq3O', 'user', '', 0.00, NULL, NULL, 'available', 0.00),
(32, 'albert', '+639658611932', '$2y$10$a1NfDiygGN6gAGN5YtfYIeDacC/HkPL0cE2gmNc4XsxjzuQk0pAQS', 'user', '', 0.00, NULL, NULL, 'assigned', 0.00),
(33, 'jeff', '09155426835', '$2y$10$2x4vKpsh6/xCkQpPAuD6UukXCe3n1MxG0KyozpgggBwXEm6cv4sL6', 'user', '', 0.00, NULL, NULL, 'assigned', 0.00),
(35, 'mae', '099748465468', '$2y$10$CP7fa7YQ5Blk1fG7l64WfeZWG24gSZN30eTMwirJ2ioRT2GMdpsDu', 'user', 'Technician', 500.00, NULL, NULL, 'assigned', 0.00),
(36, 'rodel', '099748465468', '$2y$10$/FlDQNey4xD9PqLBHtF/ueuW7qUhdvoknJup96XqWU1ZgYbMdTiOK', 'user', 'OJT', 1.00, NULL, NULL, 'available', 0.00),
(37, 'KIAN ', '099748465468', '$2y$10$wO0KZvJ.QJteW7sJ/LHyKOUZEGJjkeKdst/vizlWz9NHhFoHdu2Yu', 'user', 'Techn', 40.00, NULL, NULL, 'assigned', 40.00),
(38, 'joan', '099748465468', '$2y$10$or0qHmgTAsgK3GnxgZnIZupTpRGa7jiwKm7FXuu1w.kWn3ALF3Rri', 'user', 'driver', 50.00, NULL, NULL, 'assigned', 50.00),
(39, 'josie', '+639658611932', '$2y$10$gjP/4BAq48pBPycJZHsR7uAdm1vCYP9cQOg00fJ8CpJxlhg8jXpTi', 'user', 'OJT', 0.00, NULL, NULL, 'available', 0.00),
(41, 'esang', '+639658611932', '$2y$10$y.PtTj1GyMZ963MZUUogiORt98X9FHoioJhBSOtozZKpLg0JSrwpy', 'user', 'OJT', 0.00, NULL, NULL, 'available', 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `user_verifications`
--

CREATE TABLE `user_verifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `code` varchar(6) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `dtr_logs`
--
ALTER TABLE `dtr_logs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `groups`
--
ALTER TABLE `groups`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `group_locations`
--
ALTER TABLE `group_locations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `group_users`
--
ALTER TABLE `group_users`
  ADD PRIMARY KEY (`id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tagged_locations`
--
ALTER TABLE `tagged_locations`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tasks`
--
ALTER TABLE `tasks`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `task_id` (`task_id`),
  ADD KEY `group_id` (`group_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `location_id` (`location_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `user_verifications`
--
ALTER TABLE `user_verifications`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `dtr_logs`
--
ALTER TABLE `dtr_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `groups`
--
ALTER TABLE `groups`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `group_locations`
--
ALTER TABLE `group_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

--
-- AUTO_INCREMENT for table `group_users`
--
ALTER TABLE `group_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=131;

--
-- AUTO_INCREMENT for table `tagged_locations`
--
ALTER TABLE `tagged_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `tasks`
--
ALTER TABLE `tasks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `task_assignments`
--
ALTER TABLE `task_assignments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=42;

--
-- AUTO_INCREMENT for table `user_verifications`
--
ALTER TABLE `user_verifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `dtr_logs`
--
ALTER TABLE `dtr_logs`
  ADD CONSTRAINT `dtr_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_locations`
--
ALTER TABLE `group_locations`
  ADD CONSTRAINT `group_locations_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_locations_ibfk_2` FOREIGN KEY (`location_id`) REFERENCES `tagged_locations` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `group_users`
--
ALTER TABLE `group_users`
  ADD CONSTRAINT `group_users_ibfk_1` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `group_users_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `task_assignments`
--
ALTER TABLE `task_assignments`
  ADD CONSTRAINT `task_assignments_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `tasks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_2` FOREIGN KEY (`group_id`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `task_assignments_ibfk_4` FOREIGN KEY (`location_id`) REFERENCES `tagged_locations` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
