-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Sep 16, 2026 at 11:32 AM
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
-- Database: `attendance_portal`
--

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `attendance_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `subject` varchar(100) NOT NULL,
  `date` date NOT NULL,
  `status` enum('Present','Absent','Late') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`attendance_id`, `student_id`, `subject`, `date`, `status`) VALUES
(1, 4, 'Object Oriented Programming', '2026-07-31', 'Present'),
(2, 2, 'Object Oriented Programming', '2026-07-31', 'Present'),
(3, 4, 'Database Management System', '2026-07-31', 'Present'),
(4, 2, 'Database Management System', '2026-07-31', 'Absent'),
(5, 4, 'Object Oriented Programming', '2026-08-02', 'Present'),
(6, 2, 'Object Oriented Programming', '2026-08-02', 'Present'),
(7, 8, 'Object Oriented Programming', '2026-08-02', 'Absent'),
(8, 6, 'Object Oriented Programming', '2026-08-02', 'Present'),
(9, 4, 'Database Management System', '2026-08-02', 'Present'),
(10, 2, 'Database Management System', '2026-08-02', 'Late'),
(11, 8, 'Database Management System', '2026-08-02', 'Absent'),
(12, 6, 'Database Management System', '2026-08-02', 'Present'),
(13, 4, 'Web Technology', '2026-08-02', 'Present'),
(14, 2, 'Web Technology', '2026-08-02', 'Present'),
(15, 8, 'Web Technology', '2026-08-02', 'Absent'),
(16, 6, 'Web Technology', '2026-08-02', 'Present'),
(17, 4, 'Object Oriented Programming', '2026-08-09', 'Present'),
(18, 2, 'Object Oriented Programming', '2026-08-09', 'Absent'),
(19, 8, 'Object Oriented Programming', '2026-08-09', 'Present'),
(20, 6, 'Object Oriented Programming', '2026-08-09', 'Present'),
(21, 4, 'Object Oriented Programming', '2026-09-12', 'Present'),
(22, 10, 'Object Oriented Programming', '2026-09-12', 'Absent'),
(23, 9, 'Object Oriented Programming', '2026-09-12', 'Late'),
(24, 2, 'Object Oriented Programming', '2026-09-12', 'Present'),
(25, 8, 'Object Oriented Programming', '2026-09-12', 'Present'),
(26, 6, 'Object Oriented Programming', '2026-09-12', 'Present'),
(27, 4, 'Web Technology', '2026-09-12', 'Present'),
(28, 10, 'Web Technology', '2026-09-12', 'Present'),
(29, 9, 'Web Technology', '2026-09-12', 'Present'),
(30, 2, 'Web Technology', '2026-09-12', 'Present'),
(31, 8, 'Web Technology', '2026-09-12', 'Present'),
(32, 6, 'Web Technology', '2026-09-12', 'Present');

-- --------------------------------------------------------

--
-- Table structure for table `leave_application`
--

CREATE TABLE `leave_application` (
  `leave_id` int(11) NOT NULL,
  `student_id` int(11) NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `reason` text NOT NULL,
  `status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leave_application`
--

INSERT INTO `leave_application` (`leave_id`, `student_id`, `start_date`, `end_date`, `reason`, `status`, `created_at`) VALUES
(1, 4, '2026-08-03', '2026-08-04', 'family function', 'Approved', '2026-08-02 12:59:12'),
(2, 6, '2026-08-03', '2026-08-03', 'function', 'Pending', '2026-08-02 13:27:27'),
(3, 8, '2026-08-02', '2026-08-02', 'headache', 'Approved', '2026-08-02 13:30:14'),
(4, 2, '2026-07-27', '2026-08-02', 'sick', 'Rejected', '2026-08-03 10:10:05'),
(5, 2, '2030-06-04', '2034-06-30', 'not feeling well', 'Pending', '2026-08-03 10:10:45'),
(6, 2, '2026-08-06', '2026-08-06', 'hospital appointment', 'Pending', '2026-08-04 07:14:18'),
(7, 2, '2026-12-02', '2026-12-05', 'family function', 'Rejected', '2026-09-12 07:07:40');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--
-- Error reading structure for table attendance_portal.subjects: #1932 - Table &#039;attendance_portal.subjects&#039; doesn&#039;t exist in engine
-- Error reading data for table attendance_portal.subjects: #1064 - You have an error in your SQL syntax; check the manual that corresponds to your MariaDB server version for the right syntax to use near &#039;FROM `attendance_portal`.`subjects`&#039; at line 1

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','teacher','student') NOT NULL,
  `subject` varchar(100) DEFAULT NULL,
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `role`, `subject`, `status`, `created_at`) VALUES
(1, 'admin', '$2y$10$zVVZJitFTkB4QyhSwlAjpesxZ6GK8jTnmCMmw1Uku/3oOXz9Y5Rwq', 'admin', NULL, 'approved', '2026-07-31 07:37:00'),
(2, 'Student 1', '$2y$10$P2KQ1e5Qi26P6Xh/TCHxo.T.hmHcYkkfnAyiWLqN.uwoXVnPIO8rS', 'student', NULL, 'approved', '2026-07-31 07:43:26'),
(3, 'Teacher 1', '$2y$10$3Q0YlWLRoEYcFnBdZI3SHuhi3ABRnvuLC0NXDDk3aDS06/xQbXlC.', 'teacher', 'Numerical Methods', 'approved', '2026-07-31 07:44:09'),
(4, 'aashika', '$2y$10$sB2IRz/pwRPTCwr96oVPduXFC9Vg6HCljXhUP/P0Ji45qSFeM1Sqa', 'student', NULL, 'approved', '2026-07-31 07:59:32'),
(5, 'Teacher 2', '$2y$10$FdzsLl/GV1195TRlTfadR.Mq3h0Ap3qpJpHNObDJMLpqEzEGAJEZm', 'teacher', 'Database Management System', 'approved', '2026-07-31 08:00:51'),
(6, 'Student 3', '$2y$10$RfHwoEnrNVxG2NTf7TnGKuVbpptHwgOwkHa8.g3sqcTPcNi9fVNHW', 'student', NULL, 'approved', '2026-08-02 12:57:06'),
(7, 'Teacher 3', '$2y$10$25z4fNwbQnmmV9Aef6iGqeqIeTyCarfON0DSC7AnQm6dRyaAOGVQC', 'teacher', 'Scripting Language', 'approved', '2026-08-02 12:57:32'),
(8, 'Student 2', '$2y$10$jlwae3gPXCJXh.wFqrzUJeAdwWoJglKnS98kmvlxtdgCK3KoX25ee', 'student', NULL, 'approved', '2026-08-02 13:28:44'),
(9, 'Peko', '$2y$10$dt.xWRwXgv75AVFjPbZalOpvi/hQsKegzs5Vb5uL11mFnGCxmVFwq', 'student', NULL, 'approved', '2026-09-12 07:01:04'),
(10, 'Kia', '$2y$10$rrW5.TMaBrvo8lDmC2x.Lu4SYzTRQLoSlhdtegmWgZPVGj4BTawB.', 'student', NULL, 'approved', '2026-09-12 07:03:27'),
(11, 'Mowg', '$2y$10$R21bnTyM6hYbWpqC6Mv.v.JrAHXywx87DE641tonjvBPFwgLENpke', 'teacher', 'Operating System', 'approved', '2026-09-12 07:05:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`attendance_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `leave_application`
--
ALTER TABLE `leave_application`
  ADD PRIMARY KEY (`leave_id`),
  ADD KEY `student_id` (`student_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `attendance_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `leave_application`
--
ALTER TABLE `leave_application`
  MODIFY `leave_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `leave_application`
--
ALTER TABLE `leave_application`
  ADD CONSTRAINT `leave_application_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
