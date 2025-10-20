-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 20, 2025 at 04:53 PM
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
(1, 'Ateneo Center for Culture & the Arts (ACCA)', 'ACCA', 1, '2025-10-20 14:24:20'),
(2, 'Ateneo Center for Environment & Sustainability (ACES)', 'ACES', 1, '2025-10-20 14:24:20'),
(3, 'Ateneo Center for Leadership & Governance (ACLG)', 'ACLG', 1, '2025-10-20 14:24:20'),
(4, 'Ateneo Peace Institute (API)', 'API', 1, '2025-10-20 14:24:20'),
(5, 'Center for Community Extension Services (CCES)', 'CCES', 1, '2025-10-20 14:24:20'),
(6, 'Ateneo Learning and Teaching Excellence Center (ALTEC)', 'ALTEC', 1, '2025-10-20 14:24:20');

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
(1, 2, 'Manager', 'Information Technology', 'Software Development', 'ACES'),
(2, 3, 'Senior Developer', 'Computer Science', 'Web Development', 'SDU'),
(3, 4, 'Analyst', 'Business Administration', 'Data Analysis', 'SDU'),
(4, 5, 'Department Head', 'Management', 'Leadership', 'ACES'),
(5, 0, NULL, NULL, NULL, 'Ateneo Center for Culture & the Arts (ACCA)');

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
(1, 'Leadership Development', 'Comprehensive leadership skills training program', '2025-09-11', '2025-10-20 14:25:11'),
(2, 'Technical Skills Workshop', 'Advanced technical skills development', '2025-09-19', '2025-10-20 14:25:11'),
(3, 'Communication Excellence', 'Effective communication and presentation skills', '2025-09-30', '2025-10-20 14:25:11'),
(4, 'Project Management', 'Project management methodologies and tools', '2025-09-19', '2025-10-20 14:25:11'),
(5, 'Data Analysis Fundamentals', 'Introduction to data analysis and visualization', '2025-10-15', '2025-10-20 14:25:11'),
(6, 'Team Building Workshop', 'Building effective teams and collaboration', '2025-10-20', '2025-10-20 14:25:11'),
(7, 'Kung fu', 'Kungu fu Panda 3 the revelation', '2025-10-29', '2025-10-20 14:42:46'),
(14, 'Kung fu hustler', 'na debo nana brian', '2025-10-31', '2025-10-20 14:49:43');

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
(1, 'craftyadmin', 'craftthehive@gmail.com', '$2y$10$EelgEdpJgY3/zNcGnHXvQu.4kCyEHdRjH/rc32C9n/AjpneqmDzLC', 'admin', '2025-09-21 03:34:17'),
(2, 'dorthyy', 'dor@gmail.com', '$2y$10$pPkdTQ2lJvUSenbMUrNOPetSILWUxJdWwhDDR0IDMxkadhtnu/Upe', 'staff', '2025-09-21 03:35:11'),
(3, 'tey', 'tey@gmail.com', '$2y$10$EsOo25PivrZXPKrqznMm4.IgRar/s47cd6a6km6BuhSHIcy0JWXuO', 'staff', '2025-09-21 04:29:47'),
(4, 'dor', 'teytey@gmail.com', '$2y$10$.U5OcDVt2C09V/l3P6/uK.IDGW4lwxqefpv64QdehVFlUYZ8gKwXm', 'staff', '2025-09-21 04:45:17'),
(5, 'headuser', 'head@example.com', '$2y$10$example_hash_for_head_user', 'head', '2025-09-21 05:00:00'),
(6, '230332', '230332@gmail.com', '$2y$10$W8clNo6gWA/5Mf7AYxYJnOoHZQRfzYBBgUPWpybAdfyYCK8goZ9ky', 'staff', '2025-10-20 14:27:24');

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
(1, 2, 1, '2025-09-11', 'completed', '2025-10-20 14:26:23'),
(2, 2, 2, '2025-09-19', 'completed', '2025-10-20 14:26:23'),
(3, 2, 4, '2025-10-25', 'upcoming', '2025-10-20 14:26:23'),
(4, 3, 1, '2025-09-12', 'completed', '2025-10-20 14:26:23'),
(5, 3, 3, '2025-10-15', 'upcoming', '2025-10-20 14:26:23'),
(6, 4, 2, '2025-09-20', 'completed', '2025-10-20 14:26:23'),
(7, 4, 5, '2025-10-20', 'upcoming', '2025-10-20 14:26:23'),
(8, 5, 1, '2025-09-15', 'completed', '2025-10-20 14:26:23'),
(9, 5, 6, '2025-10-30', 'upcoming', '2025-10-20 14:26:23'),
(10, 0, 0, '2025-10-29', 'upcoming', '2025-10-20 14:42:46'),
(20, 6, 14, '2025-10-31', 'upcoming', '2025-10-20 14:49:43');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `offices`
--
ALTER TABLE `offices`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `staff_details`
--
ALTER TABLE `staff_details`
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
-- AUTO_INCREMENT for table `offices`
--
ALTER TABLE `offices`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `staff_details`
--
ALTER TABLE `staff_details`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `trainings`
--
ALTER TABLE `trainings`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `user_trainings`
--
ALTER TABLE `user_trainings`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
