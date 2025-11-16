-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 16, 2025 at 07:48 PM
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
-- Database: `sdu`
--

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `offices`
--

CREATE TABLE `offices` (
  `id` int(6) UNSIGNED NOT NULL,
  `name` varchar(150) NOT NULL,
  `code` varchar(20) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offices`
--

INSERT INTO `offices` (`id`, `name`, `code`, `is_active`, `created_at`) VALUES
(1, 'Ateneo Center for Culture & the Arts (ACCA)', 'ACCA', 1, '2025-10-19 17:13:47'),
(2, 'Ateneo Center for Environment & Sustainability (ACES)', 'ACES', 1, '2025-10-19 17:13:47'),
(3, 'Ateneo Center for Leadership & Governance (ACLG)', 'ACLG', 1, '2025-10-19 17:13:47'),
(4, 'Ateneo Peace Institute (API)', 'API', 1, '2025-10-19 17:13:47'),
(5, 'Center for Community Extension Services (CCES)', 'CCES', 1, '2025-10-19 17:13:47'),
(6, 'Ateneo Learning and Teaching Excellence Center (ALTEC)', 'ALTEC', 1, '2025-10-19 17:13:47');

-- --------------------------------------------------------

--
-- Table structure for table `staff_details`
--

CREATE TABLE `staff_details` (
  `id` int(6) UNSIGNED NOT NULL,
  `user_id` int(6) UNSIGNED DEFAULT NULL,
  `position` varchar(100) DEFAULT NULL,
  `program` varchar(100) DEFAULT NULL,
  `job_function` varchar(100) DEFAULT NULL,
  `office` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staff_details`
--

INSERT INTO `staff_details` (`id`, `user_id`, `position`, `program`, `job_function`, `office`) VALUES
(0, 0, NULL, NULL, NULL, 'Ateneo Center for Culture & the Arts (ACCA)'),
(0, 2, 'nigga', NULL, 'asdas', 'Ateneo Center for Culture & the Arts (ACCA)'),
(0, 3, 'mid laner', NULL, 'asd', 'Ateneo Center for Culture & the Arts (ACCA)'),
(0, 4, NULL, NULL, NULL, 'Ateneo Center for Culture & the Arts (ACCA)'),
(0, 5, NULL, NULL, NULL, 'Ateneo Center for Environment & Sustainability (ACES)'),
(0, 6, NULL, NULL, NULL, 'Ateneo Center for Environment & Sustainability (ACES)'),
(0, 7, NULL, NULL, NULL, 'Ateneo Center for Culture & the Arts (ACCA)'),
(0, 8, NULL, NULL, NULL, 'Ateneo Center for Culture & the Arts (ACCA)'),
(0, 9, NULL, NULL, NULL, 'Ateneo Center for Culture & the Arts (ACCA)');

-- --------------------------------------------------------

--
-- Table structure for table `trainings`
--

CREATE TABLE `trainings` (
  `id` int(6) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `training_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `trainings`
--

INSERT INTO `trainings` (`id`, `title`, `description`, `training_date`, `created_at`) VALUES
(1, 'asd', 'asd', '2025-10-31', '2025-10-22 08:59:03');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(6) UNSIGNED NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','head','staff') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'admin1', 'admin@gmail.com', '$2y$10$WIGD32gFA564/kT4IK9Wce1oOlSebQ67/iEyFHtiXRhiURfMD8nCW', 'admin', '2025-10-19 17:07:10'),
(2, 'staff1', 'justine123@gmail.com', '$2y$10$.g0rRms1HaDQdJGKWzZ39.OtKGj7/eaaNi/48ksi7KTygRFAjghmm', 'staff', '2025-10-19 17:07:51'),
(3, 'head1', 'brianjustine123@gmail.com', '$2y$10$KCZpBJGDi6ivYmKFt.yn5eqf9NcoNbUBSIBTJqokCgRuTdhnGhpPS', 'head', '2025-10-19 17:14:39'),
(4, 'staff2', 'rich@gmail.com', '$2y$10$T1eHbtaeCl8TOZF20bgBLONZ5hWfm1287K.W5wGPjhfTwgc5fAs3W', 'staff', '2025-10-19 17:15:11'),
(5, 'head2', 'daniloalcoran@yahoo.com', '$2y$10$/pN26BfYSvEd2OZNcyft6OPzIvA6Q9AWBLJ22cvqfLEiBmPKT6m96', 'head', '2025-10-19 17:15:46'),
(6, 'staff3', 'anne@gmail.com', '$2y$10$VhLr8dj73Jby8tTq1X6NI.Ia4KwDhdN7yHlcIbNPvjF116cf94/AK', 'staff', '2025-10-19 17:15:57'),
(7, 'staff3', 'sabel@gmail.com', '$2y$10$kbxSuR4meXVSwQDoP3saI.ygwK9NxIRfVFFYN89aUKYFyDmBzFmAm', 'staff', '2025-10-22 07:47:21'),
(8, 'anne', 'anne123@gmail.com', '$2y$10$Y/2EL29VjaRyep4tG55LP.WKNS1wSSYzLGwjmV2Jddxtc/snTa2rW', 'staff', '2025-10-22 09:17:16'),
(9, 'cunt', 'meow@gmail.com', '$2y$10$uw0NgALt9PsY39jtwtVn0.LUgfravl778r3j3MdS/zIkOro3F3z5G', 'staff', '2025-10-22 11:49:52');

-- --------------------------------------------------------

--
-- Table structure for table `user_trainings`
--

CREATE TABLE `user_trainings` (
  `id` int(6) UNSIGNED NOT NULL,
  `user_id` int(6) UNSIGNED DEFAULT NULL,
  `training_id` int(6) UNSIGNED DEFAULT NULL,
  `completion_date` date DEFAULT NULL,
  `status` enum('completed','upcoming') DEFAULT 'completed',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `user_trainings`
--

INSERT INTO `user_trainings` (`id`, `user_id`, `training_id`, `completion_date`, `status`, `created_at`) VALUES
(1, 2, 1, '2025-10-31', 'upcoming', '2025-10-22 08:59:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receiver_id` (`receiver_id`),
  ADD KEY `sender_id` (`sender_id`);

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `trainings`
--
ALTER TABLE `trainings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_trainings`
--
ALTER TABLE `user_trainings`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `trainings`
--
ALTER TABLE `trainings`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `user_trainings`
--
ALTER TABLE `user_trainings`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
