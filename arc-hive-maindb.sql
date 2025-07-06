-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 04, 2025 at 05:30 AM
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
-- Database: `arc-hive-maindb`
--

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `Department_id` int(11) NOT NULL,
  `Department_name` varchar(255) DEFAULT NULL,
  `Department_type` varchar(255) DEFAULT NULL,
  `Name_type` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `departments`
--

INSERT INTO `departments` (`Department_id`, `Department_name`, `Department_type`, `Name_type`) VALUES
(1, 'College of Agriculture and Forestry', 'college', NULL),
(2, 'College of Arts and Sciences', 'college', NULL),
(3, 'College of Business and Management', 'college', NULL),
(4, 'College of Education', 'college', NULL),
(5, 'College of Engineering and Technology', 'college', NULL),
(6, 'College of Veterinary Medicine', 'college', NULL),
(7, 'Admission and Registration Services', 'office', NULL),
(8, 'Audit Offices', 'office', NULL),
(9, 'External Linkages and International Affairs', 'office', NULL),
(10, 'Management Information Systems', 'office', NULL),
(11, 'Office of the President', 'office', NULL),
(12, 'College of Agriculture and Forestry - BS Agriculture', 'sub_department', NULL),
(13, 'College of Agriculture and Forestry - BS Forestry', 'sub_department', NULL),
(14, 'College of Agriculture and Forestry - BS Animal Science', 'sub_department', NULL),
(15, 'College of Agriculture and Forestry - BS Food Technology', 'sub_department', NULL),
(16, 'College of Arts and Sciences - BS Development Communication', 'sub_department', NULL),
(17, 'College of Arts and Sciences - BS Psychology', 'sub_department', NULL),
(18, 'College of Arts and Sciences - AB Economics', 'sub_department', NULL),
(19, 'College of Business and Management - BS Business Administration', 'sub_department', NULL),
(20, 'College of Business and Management - BS Entrepreneurship', 'sub_department', NULL),
(21, 'College of Business and Management - BS Agribusiness', 'sub_department', NULL),
(22, 'College of Business and Management - BS Tourism Management', 'sub_department', NULL),
(23, 'College of Education - Bachelor of Elementary Education', 'sub_department', NULL),
(24, 'College of Education - Early Childhood Education', 'sub_department', NULL),
(25, 'College of Education - Secondary Education', 'sub_department', NULL),
(26, 'College of Education - Technology and Livelihood Education', 'sub_department', NULL),
(27, 'College of Engineering and Technology - BS Information Technology', 'sub_department', NULL),
(28, 'College of Engineering and Technology - BS Geodetic Engineering', 'sub_department', NULL),
(29, 'College of Engineering and Technology - BS Agricultural and Biosystems Engineering', 'sub_department', NULL),
(30, 'College of Veterinary Medicine - Doctor of Veterinary Medicine', 'sub_department', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `documents_type_fields`
--

CREATE TABLE `documents_type_fields` (
  `Document_type_id` int(11) NOT NULL,
  `Document_type_field` int(11) DEFAULT NULL,
  `Field_name` varchar(255) DEFAULT NULL,
  `Field_label` varchar(255) DEFAULT NULL,
  `Field_type` int(11) DEFAULT NULL,
  `Is_required` tinyint(1) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `documents_type_fields`
--

INSERT INTO `documents_type_fields` (`Document_type_id`, `Document_type_field`, `Field_name`, `Field_label`, `Field_type`, `Is_required`) VALUES
(1, 1, 'memo', 'Memorandum', 1, 1),
(2, 2, 'letter', 'Letter', 1, 1),
(3, 3, 'notice', 'Notice', 1, 1),
(4, 4, 'announcement', 'Announcement', 1, 1),
(5, 5, 'invitation', 'Invitation', 1, 1),
(6, 6, 'SAMPLE TYPE', 'Sample Type', 1, 1);

-- --------------------------------------------------------

--
-- Table structure for table `files`
--

CREATE TABLE `files` (
  `File_id` int(11) NOT NULL,
  `Parent_file_id` int(11) DEFAULT NULL,
  `File_name` varchar(255) DEFAULT NULL,
  `Meta_data` varchar(255) DEFAULT NULL,
  `User_id` int(11) DEFAULT NULL,
  `Upload_date` datetime DEFAULT NULL,
  `File_size` int(11) DEFAULT NULL,
  `File_type` varchar(50) DEFAULT NULL,
  `Document_type_id` int(11) DEFAULT NULL,
  `File_status` varchar(50) DEFAULT NULL,
  `Copy_type` varchar(50) DEFAULT NULL,
  `File_path` varchar(255) DEFAULT NULL,
  `Type_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `transaction`
--

CREATE TABLE `transaction` (
  `Transaction_id` int(11) NOT NULL,
  `User_id` int(11) DEFAULT NULL,
  `Users_Department_id` int(11) DEFAULT NULL,
  `File_id` int(11) DEFAULT NULL,
  `Transaction_status` varchar(50) DEFAULT NULL,
  `Transaction_type` int(11) DEFAULT NULL,
  `Time` datetime DEFAULT NULL,
  `Massage` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `transaction`
--

INSERT INTO `transaction` (`Transaction_id`, `User_id`, `Users_Department_id`, `File_id`, `Transaction_status`, `Transaction_type`, `Time`, `Massage`) VALUES
(1, NULL, NULL, NULL, 'Failure', 2, '2025-07-04 11:10:15', 'Invalid login attempt for username: IT_Admin'),
(2, NULL, NULL, NULL, 'Failure', 2, '2025-07-04 11:10:21', 'Invalid login attempt for username: IT_Admin'),
(3, NULL, NULL, NULL, 'Failure', 2, '2025-07-04 11:10:24', 'Invalid login attempt for username: IT_Admin'),
(4, 13, NULL, NULL, 'Success', 3, '2025-07-04 11:11:55', 'User \'newuser\' created successfully'),
(5, 13, NULL, NULL, 'Success', 1, '2025-07-04 11:12:11', 'User logged in successfully'),
(6, 13, NULL, NULL, 'failed', 22, '2025-07-04 11:17:04', 'Unauthorized access attempt to user_management.php'),
(7, 14, NULL, NULL, 'Success', 3, '2025-07-04 11:26:39', 'Added user: ADMIN'),
(8, NULL, NULL, NULL, 'Failure', 3, '2025-07-04 11:26:43', 'Error: Username \'ADMIN\' already exists.'),
(11, 14, NULL, NULL, 'Success', 1, '2025-07-04 11:27:13', 'User logged in successfully'),
(12, 14, NULL, NULL, 'completed', 7, '2025-07-04 11:28:09', 'Fetched document type fields');

-- --------------------------------------------------------

--
-- Table structure for table `type`
--

CREATE TABLE `type` (
  `Type_id` int(11) NOT NULL,
  `Type_name` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `type`
--

INSERT INTO `type` (`Type_id`, `Type_name`) VALUES
(1, 'pdf'),
(2, 'docx');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `User_id` int(11) NOT NULL,
  `Username` varchar(255) DEFAULT NULL,
  `Password` varchar(255) DEFAULT NULL,
  `Role` varchar(50) DEFAULT NULL,
  `Profile_pic` blob DEFAULT NULL,
  `Position` int(11) DEFAULT NULL,
  `Created_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`User_id`, `Username`, `Password`, `Role`, `Profile_pic`, `Position`, `Created_at`) VALUES
(1, 'AdminUser', '$2y$10$J3z1b3zK7G6kXz1Y6z3X9uJ7X8z1Y9z2K3z4L5z6M7z8N9z0P1z2', 'admin', NULL, 0, '2025-07-04 10:48:00'),
(10, 'Trevor Mundo', '$2y$10$uv2Q/VDISAkVggfX92u1GeB9SVZRWryEAN0Mq8Cba1ugPtPMNFU8W', 'client', NULL, 0, '2025-03-19 18:52:12'),
(11, 'John Doe', '$2y$10$uv2Q/VDISAkVggfX92u1GeB9SVZRWryEAN0Mq8Cba1ugPtPMNFU8W', 'admin', NULL, 0, '2025-04-06 05:22:30'),
(12, 'IT_Admin', '$2y$10$J3z1b3zK7G6kXz1Y6z3X9uJ7X8z1Y9z2K3z4L5z6M7z8N9z0P1z2', 'admin', NULL, 0, '2025-07-04 10:59:00'),
(13, 'newuser', '$2y$10$hW3hp.Ruo.ian6EEUKoADOxGZUX8enOuwdMhjhO.y85jfUkXswS6i', 'user', NULL, 1, '2025-07-04 11:11:55'),
(14, 'ADMIN', '$2y$10$P11kil32erfZMdJoCKBNoe1NE44aUm0QLXSkxKYhSDvZ50i..hCJW', 'admin', NULL, 0, '2025-07-04 11:26:39');

-- --------------------------------------------------------

--
-- Table structure for table `users_department`
--

CREATE TABLE `users_department` (
  `Users_Department_id` int(11) NOT NULL,
  `User_id` int(11) DEFAULT NULL,
  `Department_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users_department`
--

INSERT INTO `users_department` (`Users_Department_id`, `User_id`, `Department_id`) VALUES
(1, 10, 5),
(2, 10, 27),
(3, 11, 5),
(4, 11, 27),
(5, 14, 11);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`Department_id`);

--
-- Indexes for table `documents_type_fields`
--
ALTER TABLE `documents_type_fields`
  ADD PRIMARY KEY (`Document_type_id`);

--
-- Indexes for table `files`
--
ALTER TABLE `files`
  ADD PRIMARY KEY (`File_id`),
  ADD KEY `Parent_file_id` (`Parent_file_id`),
  ADD KEY `User_id` (`User_id`),
  ADD KEY `Document_type_id` (`Document_type_id`),
  ADD KEY `Type_id` (`Type_id`);

--
-- Indexes for table `transaction`
--
ALTER TABLE `transaction`
  ADD PRIMARY KEY (`Transaction_id`),
  ADD KEY `User_id` (`User_id`),
  ADD KEY `Users_Department_id` (`Users_Department_id`),
  ADD KEY `File_id` (`File_id`);

--
-- Indexes for table `type`
--
ALTER TABLE `type`
  ADD PRIMARY KEY (`Type_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`User_id`);

--
-- Indexes for table `users_department`
--
ALTER TABLE `users_department`
  ADD PRIMARY KEY (`Users_Department_id`),
  ADD KEY `User_id` (`User_id`),
  ADD KEY `Department_id` (`Department_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `Department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `documents_type_fields`
--
ALTER TABLE `documents_type_fields`
  MODIFY `Document_type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `files`
--
ALTER TABLE `files`
  MODIFY `File_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `transaction`
--
ALTER TABLE `transaction`
  MODIFY `Transaction_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `type`
--
ALTER TABLE `type`
  MODIFY `Type_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `User_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `users_department`
--
ALTER TABLE `users_department`
  MODIFY `Users_Department_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `files`
--
ALTER TABLE `files`
  ADD CONSTRAINT `files_ibfk_1` FOREIGN KEY (`Parent_file_id`) REFERENCES `files` (`File_id`),
  ADD CONSTRAINT `files_ibfk_2` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_id`),
  ADD CONSTRAINT `files_ibfk_3` FOREIGN KEY (`Document_type_id`) REFERENCES `documents_type_fields` (`Document_type_id`),
  ADD CONSTRAINT `files_ibfk_4` FOREIGN KEY (`Type_id`) REFERENCES `type` (`Type_id`);

--
-- Constraints for table `transaction`
--
ALTER TABLE `transaction`
  ADD CONSTRAINT `transaction_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_id`),
  ADD CONSTRAINT `transaction_ibfk_2` FOREIGN KEY (`Users_Department_id`) REFERENCES `users_department` (`Users_Department_id`),
  ADD CONSTRAINT `transaction_ibfk_3` FOREIGN KEY (`File_id`) REFERENCES `files` (`File_id`);

--
-- Constraints for table `users_department`
--
ALTER TABLE `users_department`
  ADD CONSTRAINT `users_department_ibfk_1` FOREIGN KEY (`User_id`) REFERENCES `users` (`User_id`),
  ADD CONSTRAINT `users_department_ibfk_2` FOREIGN KEY (`Department_id`) REFERENCES `departments` (`Department_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
