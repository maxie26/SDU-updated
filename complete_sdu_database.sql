-- Complete SDU System Database
-- This file contains all necessary tables and data for the entire SDU system
-- Includes: users, staff_details, trainings, user_trainings tables
-- Supports: login, registration, admin dashboard, staff dashboard, training management

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- Disable foreign key checks during import
SET FOREIGN_KEY_CHECKS = 0;

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sdu_system`
--

-- --------------------------------------------------------

--
-- Table structure for table `users`
-- Stores all user accounts (admin, head, staff)
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
-- Sample users for testing
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'craftyadmin', 'craftthehive@gmail.com', '$2y$10$EelgEdpJgY3/zNcGnHXvQu.4kCyEHdRjH/rc32C9n/AjpneqmDzLC', 'admin', '2025-09-21 11:34:17'),
(2, 'dorthyy', 'dor@gmail.com', '$2y$10$pPkdTQ2lJvUSenbMUrNOPetSILWUxJdWwhDDR0IDMxkadhtnu/Upe', 'staff', '2025-09-21 11:35:11'),
(3, 'tey', 'tey@gmail.com', '$2y$10$EsOo25PivrZXPKrqznMm4.IgRar/s47cd6a6km6BuhSHIcy0JWXuO', 'staff', '2025-09-21 12:29:47'),
(4, 'dor', 'teytey@gmail.com', '$2y$10$.U5OcDVt2C09V/l3P6/uK.IDGW4lwxqefpv64QdehVFlUYZ8gKwXm', 'staff', '2025-09-21 12:45:17'),
(5, 'headuser', 'head@example.com', '$2y$10$example_hash_for_head_user', 'head', '2025-09-21 13:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `staff_details`
-- Stores additional information for staff members
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
-- Sample staff details
--

INSERT INTO `staff_details` (`id`, `user_id`, `position`, `program`, `job_function`, `office`) VALUES
(1, 2, 'Manager', 'Information Technology', 'Software Development', 'ACES'),
(2, 3, 'Senior Developer', 'Computer Science', 'Web Development', 'SDU'),
(3, 4, 'Analyst', 'Business Administration', 'Data Analysis', 'SDU'),
(4, 5, 'Department Head', 'Management', 'Leadership', 'ACES');

-- --------------------------------------------------------

--
-- Table structure for table `trainings`
-- Stores all available training programs
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
-- Sample training programs
--

INSERT INTO `trainings` (`id`, `title`, `description`, `training_date`) VALUES
(1, 'Leadership Development', 'Comprehensive leadership skills training program', '2025-09-11'),
(2, 'Technical Skills Workshop', 'Advanced technical skills development', '2025-09-19'),
(3, 'Communication Excellence', 'Effective communication and presentation skills', '2025-09-30'),
(4, 'Project Management', 'Project management methodologies and tools', '2025-09-19'),
(5, 'Data Analysis Fundamentals', 'Introduction to data analysis and visualization', '2025-10-15'),
(6, 'Team Building Workshop', 'Building effective teams and collaboration', '2025-10-20');

-- --------------------------------------------------------

--
-- Table structure for table `user_trainings`
-- Links users to their training records with status tracking
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
-- Sample training records with mixed statuses
--

INSERT INTO `user_trainings` (`id`, `user_id`, `training_id`, `completion_date`, `status`) VALUES
(1, 2, 1, '2025-09-11', 'completed'),
(2, 2, 2, '2025-09-19', 'completed'),
(3, 2, 4, '2025-10-25', 'upcoming'),
(4, 3, 1, '2025-09-12', 'completed'),
(5, 3, 3, '2025-10-15', 'upcoming'),
(6, 4, 2, '2025-09-20', 'completed'),
(7, 4, 5, '2025-10-20', 'upcoming'),
(8, 5, 1, '2025-09-15', 'completed'),
(9, 5, 6, '2025-10-30', 'upcoming');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `staff_details`
--
ALTER TABLE `staff_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`);

--
-- Indexes for table `trainings`
--
ALTER TABLE `trainings`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_trainings`
--
ALTER TABLE `user_trainings`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `training_id` (`training_id`),
  ADD KEY `status` (`status`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `staff_details`
--
ALTER TABLE `staff_details`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `trainings`
--
ALTER TABLE `trainings`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `user_trainings`
--
ALTER TABLE `user_trainings`
  MODIFY `id` int(6) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `staff_details`
--
ALTER TABLE `staff_details`
  ADD CONSTRAINT `staff_details_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `user_trainings`
--
ALTER TABLE `user_trainings`
  ADD CONSTRAINT `user_trainings_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_trainings_ibfk_2` FOREIGN KEY (`training_id`) REFERENCES `trainings` (`id`) ON DELETE CASCADE;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

-- Additional useful queries for the system:

-- Query to get all staff with their training counts (for admin dashboard)
-- SELECT 
--     u.username,
--     COUNT(ut.id) as total_trainings,
--     COUNT(CASE WHEN ut.status = 'completed' THEN 1 END) as completed_trainings,
--     COUNT(CASE WHEN ut.status = 'upcoming' THEN 1 END) as upcoming_trainings
-- FROM users u 
-- LEFT JOIN user_trainings ut ON u.id = ut.user_id 
-- WHERE u.role IN ('staff', 'head')
-- GROUP BY u.id;

-- Query to get training statistics (for admin dashboard)
-- SELECT 
--     COUNT(DISTINCT u.id) as total_staff,
--     COUNT(ut.id) as total_trainings,
--     COUNT(CASE WHEN ut.status = 'completed' THEN 1 END) as completed_trainings,
--     COUNT(CASE WHEN ut.status = 'upcoming' THEN 1 END) as upcoming_trainings
-- FROM users u 
-- LEFT JOIN user_trainings ut ON u.id = ut.user_id 
-- WHERE u.role IN ('staff', 'head');

-- Query to get staff directory with training info (for staff_report.php)
-- SELECT
--     u.username,
--     s.position,
--     s.program,
--     s.job_function,
--     s.office,
--     GROUP_CONCAT(CONCAT(t.title, ' (', ut.completion_date, ' - ', ut.status, ')') SEPARATOR ';<br>') AS trainings
-- FROM users u
-- LEFT JOIN staff_details s ON u.id = s.user_id
-- LEFT JOIN user_trainings ut ON u.id = ut.user_id
-- LEFT JOIN trainings t ON ut.training_id = t.id
-- WHERE u.role IN ('staff', 'head')
-- GROUP BY u.id;
