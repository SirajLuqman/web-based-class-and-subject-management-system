-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 05, 2025 at 10:51 AM
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
-- Database: `class_scheduler_db`
--

-- --------------------------------------------------------

--
-- Table structure for table `faculties`
--

CREATE TABLE `faculties` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `faculties`
--

INSERT INTO `faculties` (`id`, `name`, `code`) VALUES
(1, 'Faculty of Engineering', 'ENG'),
(2, 'Faculty of Business & Economics', 'BUS'),
(3, 'Faculty of Science', 'SCI'),
(4, 'Faculty of Humanities & Social Sciences', 'HUM'),
(5, 'Faculty of Computing & Information Technology', 'CIT');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `title`, `message`, `user_id`, `created_at`) VALUES
(2, 'Admin', 'Admin', 0, '2025-11-29 02:23:32'),
(3, 'Time Table Sep-2025', 'The time table for the 202509 Semester have been added.', 0, '2025-11-29 15:36:00'),
(10, 'Testing', 'Testing Notification', 0, '2025-12-05 17:47:22');

-- --------------------------------------------------------

--
-- Table structure for table `notifications_read`
--

CREATE TABLE `notifications_read` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `read_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications_read`
--

INSERT INTO `notifications_read` (`id`, `user_id`, `notification_id`, `read_at`) VALUES
(1, 4, 2, '2025-11-29 02:49:11'),
(3, 4, 3, '2025-12-01 00:46:03'),
(7, 11, 3, '2025-12-03 00:06:12'),
(8, 11, 2, '2025-12-03 00:06:13'),
(9, 11, 10, '2025-12-05 17:51:22');

-- --------------------------------------------------------

--
-- Table structure for table `offered_subjects`
--

CREATE TABLE `offered_subjects` (
  `id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `day` varchar(10) NOT NULL,
  `time_start` time NOT NULL,
  `time_end` time NOT NULL,
  `location` varchar(100) NOT NULL,
  `semester` varchar(50) NOT NULL,
  `study_level` varchar(50) NOT NULL,
  `capacity` int(11) NOT NULL,
  `added_date` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `offered_subjects`
--

INSERT INTO `offered_subjects` (`id`, `subject_id`, `day`, `time_start`, `time_end`, `location`, `semester`, `study_level`, `capacity`, `added_date`) VALUES
(25, 137, 'Monday', '08:00:00', '11:00:00', '', '', 'Bachelor', 50, '2025-12-03 18:04:48'),
(27, 135, 'Monday', '11:00:00', '14:00:00', '', '', 'Bachelor', 49, '2025-12-04 00:07:38'),
(28, 122, 'Wednesday', '08:00:00', '11:00:00', '', '', 'Bachelor', 49, '2025-12-04 00:07:58'),
(29, 131, 'Monday', '14:00:00', '17:00:00', '', '', 'Bachelor', 49, '2025-12-04 00:08:24'),
(31, 138, 'Tuesday', '08:00:00', '11:00:00', '', '', 'Bachelor', 49, '2025-12-04 00:09:15'),
(34, 136, 'Thursday', '08:00:00', '11:00:00', '', '', 'Bachelor', 50, '2025-12-04 00:10:54'),
(35, 134, 'Tuesday', '08:00:00', '11:00:00', '', '', 'Bachelor', 50, '2025-12-04 00:11:31'),
(36, 140, 'Thursday', '08:00:00', '11:00:00', '', '', 'Bachelor', 49, '2025-12-04 00:25:38'),
(38, 136, 'Thursday', '11:00:00', '14:00:00', '', '', 'Bachelor', 50, '2025-12-05 17:29:36'),
(39, 137, 'Monday', '08:00:00', '11:00:00', '', '', 'Bachelor', 50, '2025-12-05 17:31:26');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `registrations`
--

CREATE TABLE `registrations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `subject_id` int(11) NOT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` varchar(20) NOT NULL DEFAULT 'registered'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `registrations`
--

INSERT INTO `registrations` (`id`, `user_id`, `subject_id`, `registration_date`, `status`) VALUES
(4, 11, 25, '2025-12-05 17:50:35', 'registered'),
(5, 11, 27, '2025-12-05 17:50:42', 'registered'),
(6, 11, 34, '2025-12-05 17:50:44', 'registered'),
(7, 11, 38, '2025-12-05 17:50:46', 'registered'),
(8, 11, 35, '2025-12-05 17:50:48', 'registered'),
(9, 11, 28, '2025-12-05 17:50:49', 'registered');

-- --------------------------------------------------------

--
-- Table structure for table `study_levels`
--

CREATE TABLE `study_levels` (
  `id` int(11) NOT NULL,
  `level_name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `study_levels`
--

INSERT INTO `study_levels` (`id`, `level_name`, `description`) VALUES
(1, 'Diploma', 'Diploma level courses'),
(2, 'Bachelor', 'Bachelor degree courses'),
(3, 'Master', 'Master degree courses');

-- --------------------------------------------------------

--
-- Table structure for table `subjects_master`
--

CREATE TABLE `subjects_master` (
  `id` int(11) NOT NULL,
  `subject_code` varchar(20) NOT NULL,
  `subject_name` varchar(255) NOT NULL,
  `faculty_id` int(11) NOT NULL,
  `study_level` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subjects_master`
--

INSERT INTO `subjects_master` (`id`, `subject_code`, `subject_name`, `faculty_id`, `study_level`) VALUES
(1, 'ENG101', 'Introduction to Engineering', 1, 'Diploma'),
(2, 'ENG102', 'Engineering Mathematics I', 1, 'Diploma'),
(3, 'ENG103', 'Physics for Engineers', 1, 'Diploma'),
(4, 'ENG104', 'Computer Aided Design', 1, 'Diploma'),
(5, 'ENG105', 'Engineering Drawing', 1, 'Diploma'),
(6, 'ENG106', 'Electrical Basics', 1, 'Diploma'),
(7, 'ENG107', 'Mechanics I', 1, 'Diploma'),
(8, 'ENG108', 'Materials Science', 1, 'Diploma'),
(9, 'ENG109', 'Workshop Practices', 1, 'Diploma'),
(10, 'ENG110', 'Communication Skills', 1, 'Diploma'),
(11, 'ENG201', 'Thermodynamics', 1, 'Bachelor'),
(12, 'ENG202', 'Fluid Mechanics', 1, 'Bachelor'),
(13, 'ENG203', 'Circuit Analysis', 1, 'Bachelor'),
(14, 'ENG204', 'Engineering Mathematics II', 1, 'Bachelor'),
(15, 'ENG205', 'Electronics I', 1, 'Bachelor'),
(16, 'ENG206', 'Statics and Dynamics', 1, 'Bachelor'),
(17, 'ENG207', 'Computer Programming for Engineers', 1, 'Bachelor'),
(18, 'ENG208', 'Engineering Design', 1, 'Bachelor'),
(19, 'ENG209', 'Manufacturing Processes', 1, 'Bachelor'),
(20, 'ENG210', 'Control Systems', 1, 'Bachelor'),
(21, 'ENG301', 'Advanced Robotics', 1, 'Master'),
(22, 'ENG302', 'Renewable Energy Systems', 1, 'Master'),
(23, 'ENG303', 'Engineering Project Management', 1, 'Master'),
(24, 'ENG304', 'Advanced Thermodynamics', 1, 'Master'),
(25, 'ENG305', 'Nanoengineering', 1, 'Master'),
(26, 'ENG306', 'Automation & Control', 1, 'Master'),
(27, 'ENG307', 'Structural Analysis', 1, 'Master'),
(28, 'ENG308', 'Power Electronics', 1, 'Master'),
(29, 'ENG309', 'Computational Fluid Dynamics', 1, 'Master'),
(30, 'ENG310', 'Advanced Materials', 1, 'Master'),
(31, 'BUS101', 'Principles of Accounting', 2, 'Diploma'),
(32, 'BUS102', 'Business Communication', 2, 'Diploma'),
(33, 'BUS103', 'Introduction to Economics', 2, 'Diploma'),
(34, 'BUS104', 'Principles of Management', 2, 'Diploma'),
(35, 'BUS105', 'Business Mathematics', 2, 'Diploma'),
(36, 'BUS106', 'Marketing Basics', 2, 'Diploma'),
(37, 'BUS107', 'Organizational Behaviour I', 2, 'Diploma'),
(38, 'BUS108', 'Business Law', 2, 'Diploma'),
(39, 'BUS109', 'Finance Basics', 2, 'Diploma'),
(40, 'BUS110', 'Entrepreneurship Essentials', 2, 'Diploma'),
(41, 'BUS201', 'Marketing Management', 2, 'Bachelor'),
(42, 'BUS202', 'Financial Management', 2, 'Bachelor'),
(43, 'BUS203', 'Organizational Behaviour II', 2, 'Bachelor'),
(44, 'BUS204', 'Human Resource Management', 2, 'Bachelor'),
(45, 'BUS205', 'Business Statistics', 2, 'Bachelor'),
(46, 'BUS206', 'Operations Management', 2, 'Bachelor'),
(47, 'BUS207', 'Business Ethics', 2, 'Bachelor'),
(48, 'BUS208', 'International Business', 2, 'Bachelor'),
(49, 'BUS209', 'Management Information Systems', 2, 'Bachelor'),
(50, 'BUS210', 'Corporate Finance', 2, 'Bachelor'),
(51, 'BUS301', 'Strategic Management', 2, 'Master'),
(52, 'BUS302', 'International Marketing', 2, 'Master'),
(54, 'BUS304', 'Leadership & Management', 2, 'Master'),
(55, 'BUS305', 'Project Management', 2, 'Master'),
(57, 'BUS307', 'Organizational Development', 2, 'Master'),
(58, 'BUS308', 'Global Business Environment', 2, 'Master'),
(59, 'BUS309', 'Entrepreneurship & Innovation', 2, 'Master'),
(60, 'BUS310', 'Corporate Governance', 2, 'Master'),
(61, 'SCI101', 'Biology I', 3, 'Diploma'),
(62, 'SCI102', 'Chemistry I', 3, 'Diploma'),
(63, 'SCI103', 'Physics I', 3, 'Diploma'),
(64, 'SCI104', 'Mathematics I', 3, 'Diploma'),
(65, 'SCI105', 'Environmental Science I', 3, 'Diploma'),
(66, 'SCI106', 'Computer Basics', 3, 'Diploma'),
(67, 'SCI107', 'Statistics I', 3, 'Diploma'),
(68, 'SCI108', 'Earth Science', 3, 'Diploma'),
(69, 'SCI109', 'Laboratory Techniques', 3, 'Diploma'),
(70, 'SCI110', 'Scientific Communication', 3, 'Diploma'),
(71, 'SCI201', 'Genetics', 3, 'Bachelor'),
(72, 'SCI202', 'Organic Chemistry', 3, 'Bachelor'),
(73, 'SCI203', 'Microbiology', 3, 'Bachelor'),
(74, 'SCI204', 'Physics II', 3, 'Bachelor'),
(75, 'SCI205', 'Analytical Chemistry', 3, 'Bachelor'),
(76, 'SCI206', 'Ecology', 3, 'Bachelor'),
(77, 'SCI207', 'Data Analysis for Science', 3, 'Bachelor'),
(78, 'SCI208', 'Biostatistics', 3, 'Bachelor'),
(79, 'SCI209', 'Molecular Biology', 3, 'Bachelor'),
(80, 'SCI210', 'Biochemistry', 3, 'Bachelor'),
(81, 'SCI301', 'Quantum Physics', 3, 'Master'),
(82, 'SCI302', 'Advanced Biochemistry', 3, 'Master'),
(83, 'SCI303', 'Environmental Science', 3, 'Master'),
(84, 'SCI304', 'Genomics & Proteomics', 3, 'Master'),
(85, 'SCI305', 'Advanced Microbiology', 3, 'Master'),
(86, 'SCI306', 'Applied Physics', 3, 'Master'),
(87, 'SCI307', 'Advanced Analytical Chemistry', 3, 'Master'),
(88, 'SCI308', 'Computational Biology', 3, 'Master'),
(89, 'SCI309', 'Advanced Ecology', 3, 'Master'),
(90, 'SCI310', 'Scientific Research Methods', 3, 'Master'),
(91, 'HUM101', 'Introduction to Sociology', 4, 'Diploma'),
(92, 'HUM102', 'Psychology Basics', 4, 'Diploma'),
(93, 'HUM103', 'Philosophy Fundamentals', 4, 'Diploma'),
(94, 'HUM104', 'Communication Skills', 4, 'Diploma'),
(95, 'HUM105', 'Ethics & Values', 4, 'Diploma'),
(96, 'HUM106', 'Cultural Studies I', 4, 'Diploma'),
(97, 'HUM107', 'Political Awareness', 4, 'Diploma'),
(98, 'HUM108', 'Social Work Basics', 4, 'Diploma'),
(99, 'HUM109', 'History I', 4, 'Diploma'),
(100, 'HUM110', 'Introduction to Media', 4, 'Diploma'),
(101, 'HUM201', 'Cultural Studies II', 4, 'Bachelor'),
(102, 'HUM202', 'Political Science', 4, 'Bachelor'),
(103, 'HUM203', 'Social Research Methods', 4, 'Bachelor'),
(104, 'HUM204', 'Philosophy II', 4, 'Bachelor'),
(105, 'HUM205', 'Advanced Psychology', 4, 'Bachelor'),
(106, 'HUM206', 'Sociology II', 4, 'Bachelor'),
(107, 'HUM207', 'Media Studies', 4, 'Bachelor'),
(108, 'HUM208', 'Ethics & Governance', 4, 'Bachelor'),
(109, 'HUM209', 'Human Development', 4, 'Bachelor'),
(110, 'HUM210', 'Global Studies', 4, 'Bachelor'),
(111, 'HUM301', 'Advanced Sociology', 4, 'Master'),
(112, 'HUM302', 'Leadership & Management', 4, 'Master'),
(113, 'HUM303', 'Ethics & Philosophy', 4, 'Master'),
(114, 'HUM304', 'Cultural Research', 4, 'Master'),
(115, 'HUM305', 'Advanced Political Science', 4, 'Master'),
(116, 'HUM306', 'Advanced Psychology', 4, 'Master'),
(117, 'HUM307', 'Global Ethics', 4, 'Master'),
(118, 'HUM308', 'Social Policy & Planning', 4, 'Master'),
(119, 'HUM309', 'Research Methods in Humanities', 4, 'Master'),
(120, 'HUM310', 'Contemporary Studies', 4, 'Master'),
(121, 'CIT101', 'Introduction to Programming', 5, 'Diploma'),
(122, 'CIT102', 'Computer Fundamentals', 5, 'Diploma'),
(123, 'CIT103', 'Web Development Basics', 5, 'Diploma'),
(124, 'CIT104', 'Database Basics', 5, 'Diploma'),
(125, 'CIT105', 'Networking Fundamentals', 5, 'Diploma'),
(126, 'CIT106', 'Software Applications', 5, 'Diploma'),
(127, 'CIT107', 'Digital Literacy', 5, 'Diploma'),
(128, 'CIT108', 'Computer Security Basics', 5, 'Diploma'),
(129, 'CIT109', 'Information Systems Basics', 5, 'Diploma'),
(130, 'CIT110', 'Mathematics for Computing', 5, 'Diploma'),
(131, 'CIT201', 'Data Structures & Algorithms', 5, 'Bachelor'),
(132, 'CIT202', 'Database Systems', 5, 'Bachelor'),
(133, 'CIT203', 'Software Engineering', 5, 'Bachelor'),
(134, 'CIT204', 'Operating Systems', 5, 'Bachelor'),
(135, 'CIT205', 'Computer Networks', 5, 'Bachelor'),
(136, 'CIT206', 'Web Development', 5, 'Bachelor'),
(137, 'CIT207', 'Artificial Intelligence Basics', 5, 'Bachelor'),
(138, 'CIT208', 'Human Computer Interaction', 5, 'Bachelor'),
(139, 'CIT209', 'Information Security', 5, 'Bachelor'),
(140, 'CIT210', 'Programming in Python', 5, 'Bachelor'),
(141, 'CIT301', 'Artificial Intelligence', 5, 'Master'),
(142, 'CIT302', 'Machine Learning', 5, 'Master'),
(143, 'CIT303', 'Cybersecurity & Risk Management', 5, 'Master'),
(144, 'CIT304', 'Advanced Database Systems', 5, 'Master'),
(145, 'CIT305', 'Cloud Computing', 5, 'Master'),
(146, 'CIT306', 'Data Analytics', 5, 'Master'),
(147, 'CIT307', 'Computer Vision', 5, 'Master'),
(148, 'CIT308', 'Big Data Systems', 5, 'Master'),
(149, 'CIT309', 'Software Architecture', 5, 'Master'),
(150, 'CIT310', 'Advanced Programming Techniques', 5, 'Master');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_login` tinyint(1) NOT NULL DEFAULT 1,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(20) DEFAULT NULL,
  `student_id` varchar(50) DEFAULT NULL,
  `official_id` varchar(50) DEFAULT NULL,
  `nic_number` varchar(20) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `address` text DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `role` enum('student','faculty','admin') NOT NULL DEFAULT 'student',
  `faculty_id` int(11) NOT NULL,
  `study_level_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `first_login`, `full_name`, `email`, `mobile`, `student_id`, `official_id`, `nic_number`, `date_of_birth`, `address`, `profile_image`, `role`, `faculty_id`, `study_level_id`) VALUES
(1, 'admin', '$2y$10$7T9H.IJuFFsGUw4PzaKiEOUCjz6uvR8iVvvMa7LkOvUzsnClf7YNu', 0, 'Admin', 'admin@gmail.com', '034966', NULL, '01', 'AH8350272', '0000-00-00', '', 'user_1_1764926876.jpg', 'admin', 0, 0),
(2, 'admin2', '$2y$10$5cVUwXbiF6JC9C8ymbAVbuRbT8SZsMaJkJQwMNcBg5ITpS9/Zmg0y', 1, 'Admin2', 'admin2@gmail.com', NULL, NULL, NULL, 'AH8350273', NULL, NULL, NULL, 'admin', 0, 0),
(11, 'luqman', '$2y$10$8zJAAeNuamCXY/BkbugSV.WME02w9ON1ZV.d4N6fvHmpYfECyuYiC', 0, 'Luqman Siraj', 'luqman@gmail.com', '', NULL, NULL, '', '0000-00-00', '', 'user_11_1764928180.jpg', 'student', 5, 2),
(12, 'student2', '$2y$10$wTZDu3eRnOVmGa7z80fy0Otx45HiptkhWyzTRD1tUjUQ/Mypf1pMK', 1, 'Student 2', 'student2@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'student', 5, 2),
(14, 'admin3', '$2y$10$ZN2JwjTox.7LxzfP.wXTs.OhDYXpBmEmBpr5wLgvof44K7.RJdG6K', 1, 'Admin 3 on the way', 'admin3@gmail.com', NULL, NULL, NULL, 'AH8350274', NULL, NULL, NULL, 'admin', 0, 0),
(20, 'student3', '$2y$10$1Sz8inziBpsJMxZLxIaDuu7fvwmIOmpoyqoRNwVis/LZUsTs4xEMa', 1, 'Student 3', 'student3@gmail.com', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 'student', 5, 2);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `faculties`
--
ALTER TABLE `faculties`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `notifications_read`
--
ALTER TABLE `notifications_read`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_notification_unique` (`user_id`,`notification_id`);

--
-- Indexes for table `offered_subjects`
--
ALTER TABLE `offered_subjects`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `registrations`
--
ALTER TABLE `registrations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_subject_unique` (`user_id`,`subject_id`);

--
-- Indexes for table `study_levels`
--
ALTER TABLE `study_levels`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `level_name` (`level_name`);

--
-- Indexes for table `subjects_master`
--
ALTER TABLE `subjects_master`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `subject_code` (`subject_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `faculties`
--
ALTER TABLE `faculties`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notifications_read`
--
ALTER TABLE `notifications_read`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `offered_subjects`
--
ALTER TABLE `offered_subjects`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `registrations`
--
ALTER TABLE `registrations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `study_levels`
--
ALTER TABLE `study_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `subjects_master`
--
ALTER TABLE `subjects_master`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=167;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
