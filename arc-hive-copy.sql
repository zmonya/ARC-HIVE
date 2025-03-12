-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 10, 2025 at 03:21 AM
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
-- Database: `arc-hive`
--

-- --------------------------------------------------------

--
-- Table structure for table `access_requests`
--

CREATE TABLE `access_requests` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `status` enum('success','failure') DEFAULT 'success',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `activity_log`
--

INSERT INTO `activity_log` (`id`, `user_id`, `action`, `status`, `timestamp`) VALUES
(1, 2, 'Uploaded file: IMAGE STEGANOGRAPHY USING LSB ALGORITHM .pdf', 'success', '2025-03-08 01:25:59'),
(2, 2, 'Sent file: IMAGE STEGANOGRAPHY USING LSB ALGORITHM .pdf', 'success', '2025-03-08 01:26:09'),
(3, 2, 'Sent file: IMAGE STEGANOGRAPHY USING LSB ALGORITHM .pdf', 'success', '2025-03-08 01:26:09'),
(4, 1, 'Uploaded file: Implementation of Steganography Modified Least.pdf', 'success', '2025-03-08 01:49:43'),
(5, 1, 'Sent file: Implementation of Steganography Modified Least.pdf', 'success', '2025-03-08 01:49:49'),
(6, 1, 'Sent file: Implementation of Steganography Modified Least.pdf', 'success', '2025-03-08 01:52:38'),
(7, 1, 'Sent file: Implementation of Steganography Modified Least.pdf', 'success', '2025-03-08 01:53:00'),
(8, 1, 'Sent file: Implementation of Steganography Modified Least.pdf', 'success', '2025-03-08 01:53:27'),
(9, 2, 'Sent file: IMAGE STEGANOGRAPHY USING LSB ALGORITHM .pdf', 'success', '2025-03-08 01:54:11'),
(10, 3, 'Uploaded file: Laboratory Activity No 2 SWOT Analysis Template.xlsx', 'success', '2025-03-08 06:18:36'),
(11, 1, 'Sent file: Laboratory Activity No 2 SWOT Analysis Template.xlsx', 'success', '2025-03-08 06:18:51'),
(12, 1, 'Sent file: Implementation of Steganography Modified Least.pdf', 'success', '2025-03-08 06:20:27'),
(13, 3, 'Sent file: Laboratory Activity No 2 SWOT Analysis Template.xlsx', 'success', '2025-03-08 06:43:25'),
(14, 3, 'Uploaded file: Products_Report.pdf', 'success', '2025-03-08 09:11:48'),
(15, 3, 'Uploaded file: ARC-HIVE_SYSTEM_DESIGN.docx', 'success', '2025-03-08 10:19:54'),
(16, 3, 'Sent file: ARC-HIVE_SYSTEM_DESIGN.docx', 'success', '2025-03-08 10:56:02'),
(17, 1, 'Uploaded file: RIVERA-IVAN-HARVEY-FINAL-PROJECT-DOCUMENT.pdf', 'success', '2025-03-08 10:56:30');

-- --------------------------------------------------------

--
-- Table structure for table `cabinets`
--

CREATE TABLE `cabinets` (
  `id` int(11) NOT NULL,
  `cabinet_name` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `department_id` int(11) NOT NULL,
  `location` varchar(255) NOT NULL,
  `layers` int(11) NOT NULL,
  `boxes` int(11) NOT NULL,
  `folders` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `cabinets`
--

INSERT INTO `cabinets` (`id`, `cabinet_name`, `username`, `department_id`, `location`, `layers`, `boxes`, `folders`) VALUES
(1, 'Dean Cabinet', 'Dean Cabinet CVM', 6, '1', 7, 5, 7),
(2, 'Dean Cabinet', 'Trevor_Mundo', 1, '1', 5, 4, 6);

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(7, 'Admission and Registration Services'),
(8, 'Audit Offices'),
(1, 'College of Agriculture and Forestry'),
(2, 'College of Arts and Sciences'),
(3, 'College of Business and Management'),
(4, 'College of Education'),
(5, 'College of Engineering and Technology'),
(6, 'College of Veterinary Medicine'),
(9, 'External Linkages and International Affairs'),
(10, 'Management Information Systems'),
(11, 'Office of the President');

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `file_size` bigint(20) DEFAULT NULL,
  `file_type` enum('pdf','doc','docx','xls','xlsx','jpg','png','txt','zip','other') NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `purpose` varchar(255) DEFAULT NULL,
  `hard_copy_available` tinyint(1) DEFAULT 0,
  `is_deleted` tinyint(1) DEFAULT 0,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `cabinet_id` int(11) DEFAULT NULL,
  `layer` int(11) DEFAULT NULL,
  `box` int(11) DEFAULT NULL,
  `folder` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `files`
--

INSERT INTO `files` (`id`, `file_name`, `file_path`, `upload_date`, `user_id`, `file_size`, `file_type`, `subject`, `purpose`, `hard_copy_available`, `is_deleted`, `deleted_at`, `department_id`, `cabinet_id`, `layer`, `box`, `folder`) VALUES
(1, 'IMAGE STEGANOGRAPHY USING LSB ALGORITHM .pdf', 'uploads/c0efbc57a9da2bcb_IMAGESTEGANOGRAPHYUSINGLSBALGORITHM.pdf', '2025-03-08 01:25:59', 2, 988557, '', 'Test', 'Request Letter', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(2, 'Implementation of Steganography Modified Least.pdf', 'uploads/234b34c8c15e3d73_ImplementationofSteganographyModifiedLeast.pdf', '2025-03-08 01:49:43', 1, 1204912, '', 'Test', 'Meeting Announcement', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(3, 'Laboratory Activity No 2 SWOT Analysis Template.xlsx', 'uploads/fa4fe06813cadd97_LaboratoryActivityNo2SWOTAnalysisTemplate.xlsx', '2025-03-08 06:18:36', 3, 18196, '', 'letter', 'Meeting Announcement', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(4, 'Products_Report.pdf', 'uploads/9e10d0ef23450678_Products_Report.pdf', '2025-03-08 09:11:48', 3, 7761, '', 'letter', 'Title Approval', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(5, 'ARC-HIVE_SYSTEM_DESIGN.docx', 'uploads/904a063137ff8d59_ARC-HIVE_SYSTEM_DESIGN.docx', '2025-03-08 10:19:54', 3, 163440, '', 'letter', 'Meeting Announcement', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL),
(6, 'RIVERA-IVAN-HARVEY-FINAL-PROJECT-DOCUMENT.pdf', 'uploads/fe8cf4e9f1f2edec_RIVERA-IVAN-HARVEY-FINAL-PROJECT-DOCUMENT.pdf', '2025-03-08 10:56:30', 1, 1004013, '', 'letter', 'Meeting Announcement', 1, 0, NULL, NULL, NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `file_departments`
--

CREATE TABLE `file_departments` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `department_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `file_departments`
--

INSERT INTO `file_departments` (`id`, `file_id`, `department_id`) VALUES
(1, 2, 9),
(2, 2, 9),
(3, 1, 11),
(4, 3, 11);

-- --------------------------------------------------------

--
-- Table structure for table `file_recipients`
--

CREATE TABLE `file_recipients` (
  `id` int(11) NOT NULL,
  `file_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `file_recipients`
--

INSERT INTO `file_recipients` (`id`, `file_id`, `recipient_id`) VALUES
(1, 1, 1),
(2, 1, 1),
(3, 2, 1),
(4, 2, 1),
(5, 2, 3),
(6, 3, 2),
(7, 5, 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `type` varchar(50) NOT NULL,
  `message` varchar(255) NOT NULL,
  `status` enum('unread','read') DEFAULT 'unread',
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `type`, `message`, `status`, `timestamp`) VALUES
(1, 2, 'info', 'File uploaded successfully: IMAGE STEGANOGRAPHY USING LSB ALGORITHM .pdf', 'unread', '2025-03-08 01:25:59'),
(2, 1, 'info', 'You have received a new file: IMAGE STEGANOGRAPHY USING LSB ALGORITHM .pdf', 'unread', '2025-03-08 01:26:09'),
(3, 1, 'info', 'You have received a new file: IMAGE STEGANOGRAPHY USING LSB ALGORITHM .pdf', 'unread', '2025-03-08 01:26:09'),
(4, 1, 'info', 'File uploaded successfully: Implementation of Steganography Modified Least.pdf', 'unread', '2025-03-08 01:49:43'),
(5, 1, 'info', 'You have received a new file: Implementation of Steganography Modified Least.pdf', 'unread', '2025-03-08 01:49:49'),
(6, 1, 'info', 'You have received a new file: Implementation of Steganography Modified Least.pdf', 'unread', '2025-03-08 01:53:27'),
(7, 1, 'info', 'Your department has received a new file: IMAGE STEGANOGRAPHY USING LSB ALGORITHM .pdf', 'unread', '2025-03-08 01:54:11'),
(8, 2, 'info', 'Your department has received a new file: IMAGE STEGANOGRAPHY USING LSB ALGORITHM .pdf', 'unread', '2025-03-08 01:54:11'),
(9, 3, 'info', 'File uploaded successfully: Laboratory Activity No 2 SWOT Analysis Template.xlsx', 'unread', '2025-03-08 06:18:36'),
(10, 1, 'info', 'Your department has received a new file: Laboratory Activity No 2 SWOT Analysis Template.xlsx', 'unread', '2025-03-08 06:18:51'),
(11, 3, 'info', 'You have received a new file: Implementation of Steganography Modified Least.pdf', 'unread', '2025-03-08 06:20:27'),
(12, 2, 'info', 'You have received a new file: Laboratory Activity No 2 SWOT Analysis Template.xlsx', 'unread', '2025-03-08 06:43:25'),
(13, 3, 'info', 'File uploaded successfully: Products_Report.pdf', 'unread', '2025-03-08 09:11:48'),
(14, 3, 'info', 'File uploaded successfully: ARC-HIVE_SYSTEM_DESIGN.docx', 'unread', '2025-03-08 10:19:54'),
(15, 1, 'info', 'You have received a new file: ARC-HIVE_SYSTEM_DESIGN.docx', 'unread', '2025-03-08 10:56:02'),
(16, 1, 'info', 'File uploaded successfully: RIVERA-IVAN-HARVEY-FINAL-PROJECT-DOCUMENT.pdf', 'unread', '2025-03-08 10:56:30');

-- --------------------------------------------------------

--
-- Table structure for table `recently_deleted`
--

CREATE TABLE `recently_deleted` (
  `id` int(11) NOT NULL,
  `file_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `storage_locations`
--

CREATE TABLE `storage_locations` (
  `id` int(11) NOT NULL,
  `cabinet` int(11) NOT NULL,
  `layer` int(11) NOT NULL,
  `box` int(11) NOT NULL,
  `folder` int(11) NOT NULL,
  `department_id` int(11) NOT NULL,
  `is_occupied` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `role` enum('admin','client') NOT NULL DEFAULT 'client',
  `profile_pic` varchar(255) DEFAULT NULL,
  `position` varchar(255) DEFAULT NULL,
  `department_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `role`, `profile_pic`, `position`, `department_id`, `created_at`) VALUES
(1, 'admin', '$2y$10$uzCPpYzYb4pfubmI8a0F0OFwTVP6OIGcc5gmodm0SFypR.hzRdmTm', 'Ivan Harvey Rivera', 'admin', 'uploads/67cb9b8a0f125.png', 'admin', 11, '2025-03-08 01:21:14'),
(2, 'trevor_mundo', '$2y$10$wepJ5cW2AaRyl2xmHzseL.sHt.6EvM3M93g2atjAIGNMzSbdW5hEO', 'TREVOR MUNDO', 'client', 'uploads/67cbddfd767ad.png', 'Tester', 9, '2025-03-08 01:25:24'),
(3, 'Banjans', '$2y$10$Xn/Kv6PWbBU4yJFiRJyume0hX2DgBskIU8BDfNY//xotgaLXhnY46', 'William Banjans', 'client', 'uploads/67cbe12176733.png', 'Golden Dawn 1st Captain', 10, '2025-03-08 06:18:09');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `access_requests`
--
ALTER TABLE `access_requests`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `file_id` (`file_id`);

--
-- Indexes for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `cabinets`
--
ALTER TABLE `cabinets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `file_path` (`file_path`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `department_id` (`department_id`),
  ADD KEY `fk_files_cabinets` (`cabinet_id`);

--
-- Indexes for table `file_departments`
--
ALTER TABLE `file_departments`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `department_id` (`department_id`);

--
-- Indexes for table `file_recipients`
--
ALTER TABLE `file_recipients`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `recipient_id` (`recipient_id`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `recently_deleted`
--
ALTER TABLE `recently_deleted`
  ADD PRIMARY KEY (`id`),
  ADD KEY `file_id` (`file_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `storage_locations`
--
ALTER TABLE `storage_locations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_location` (`cabinet`,`layer`,`box`,`folder`),
  ADD KEY `fk_storage_locations_department` (`department_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `department_id` (`department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `access_requests`
--
ALTER TABLE `access_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `activity_log`
--
ALTER TABLE `activity_log`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `cabinets`
--
ALTER TABLE `cabinets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `file_departments`
--
ALTER TABLE `file_departments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `file_recipients`
--
ALTER TABLE `file_recipients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `recently_deleted`
--
ALTER TABLE `recently_deleted`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `storage_locations`
--
ALTER TABLE `storage_locations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `access_requests`
--
ALTER TABLE `access_requests`
  ADD CONSTRAINT `access_requests_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `access_requests_ibfk_2` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `activity_log`
--
ALTER TABLE `activity_log`
  ADD CONSTRAINT `activity_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `cabinets`
--
ALTER TABLE `cabinets`
  ADD CONSTRAINT `cabinets_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `files_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_files_cabinets` FOREIGN KEY (`cabinet_id`) REFERENCES `cabinets` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `file_departments`
--
ALTER TABLE `file_departments`
  ADD CONSTRAINT `file_departments_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_departments_ibfk_2` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `file_recipients`
--
ALTER TABLE `file_recipients`
  ADD CONSTRAINT `file_recipients_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `file_recipients_ibfk_2` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recently_deleted`
--
ALTER TABLE `recently_deleted`
  ADD CONSTRAINT `recently_deleted_ibfk_1` FOREIGN KEY (`file_id`) REFERENCES `files` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `recently_deleted_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `storage_locations`
--
ALTER TABLE `storage_locations`
  ADD CONSTRAINT `fk_storage_locations_department` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
