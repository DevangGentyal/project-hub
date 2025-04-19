-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3307
-- Generation Time: Apr 07, 2025 at 08:36 PM
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
-- Database: `teamflow`
--

-- --------------------------------------------------------

--
-- Table structure for table `logins`
--

CREATE TABLE `logins` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `login_time` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `logins`
--

INSERT INTO `logins` (`id`, `email`, `login_time`) VALUES
(1, 'devang.g@gmail.com', '2025-04-05 22:24:47'),
(2, 'devang.g@gmail.com', '2025-04-05 22:33:36'),
(3, 'devang.g@gmail.com', '2025-04-05 22:40:39'),
(4, 'devang.g@gmail.com', '2025-04-05 22:43:07'),
(5, 'tanishka.k@gmail.com', '2025-04-05 22:54:20'),
(6, 'jidnya.stud@gmail.com', '2025-04-06 00:03:21'),
(7, 'jidnya.stud@gmail.com', '2025-04-06 00:03:37'),
(8, 'jidnya.stud@gmail.com', '2025-04-06 00:04:22'),
(16, 'jidnya.stud@gmail.com', '2025-04-06 00:19:41'),
(17, 'jidnya.stud@gmail.com', '2025-04-06 00:27:30'),
(18, 'jidnya.stud@gmail.com', '2025-04-06 00:27:39'),
(19, 'devang.g@gmail.com', '2025-04-06 00:27:57'),
(20, 'jidnya.stud@gmail.com', '2025-04-06 00:29:57'),
(21, 'jidnya.stud@gmail.com', '2025-04-06 00:31:59'),
(22, 'jidnya.stud@gmail.com', '2025-04-06 00:38:32'),
(23, 'jidnya.stud@gmail.com', '2025-04-06 00:38:43'),
(24, 'jidnya.stud@gmail.com', '2025-04-06 00:40:35'),
(25, 'jidnya.stud@gmail.com', '2025-04-06 09:26:05'),
(26, 'jidnya.stud@gmail.com', '2025-04-06 09:26:33'),
(27, 'devang.g@gmail.com', '2025-04-06 10:28:26'),
(28, 'jidnya.stud@gmail.com', '2025-04-06 10:29:27'),
(29, 'jidnya.stud@gmail.com', '2025-04-06 15:47:48'),
(30, 'devang.g@gmail.com', '2025-04-06 15:48:06'),
(31, 'diya.stud@gmail.com', '2025-04-06 16:09:45'),
(32, 'jidnya.stud@gmail.com', '2025-04-06 16:11:50'),
(33, 'devang.g@gmail.com', '2025-04-06 16:12:16'),
(34, 'devang.g@gmail.com', '2025-04-06 17:40:33'),
(35, 'devang.g@gmail.com', '2025-04-06 18:33:48'),
(36, 'devang.g@gmail.com', '2025-04-06 18:48:02'),
(37, 'jidnya.stud@gmail.com', '2025-04-06 18:59:10'),
(38, 'devang.g@gmail.com', '2025-04-06 19:16:13'),
(39, 'jidnya.stud@gmail.com', '2025-04-06 19:18:09'),
(40, 'devang.g@gmail.com', '2025-04-07 05:19:42'),
(41, 'jidnya.stud@gmail.com', '2025-04-07 05:20:18'),
(42, 'purva.stud@gmail.com', '2025-04-07 05:39:23'),
(43, 'devang.g@gmail.com', '2025-04-07 05:45:07'),
(44, 'jidnya.stud@gmail.com', '2025-04-07 05:51:57'),
(45, 'devang.g@gmail.com', '2025-04-07 05:52:25'),
(46, 'jidnya.stud@gmail.com', '2025-04-07 05:56:31'),
(47, 'jidnya.stud@gmail.com', '2025-04-07 05:59:42'),
(48, 'devang.g@gmail.com', '2025-04-07 06:05:59'),
(49, 'jidnya.stud@gmail.com', '2025-04-07 06:08:34'),
(50, 'jidnya.stud@gmail.com', '2025-04-07 06:17:08'),
(51, 'jidnya.stud@gmail.com', '2025-04-07 06:18:44'),
(52, 'devang.g@gmail.com', '2025-04-07 06:22:50'),
(53, 'jidnya.stud@gmail.com', '2025-04-07 15:21:03'),
(54, 'alex.stud@gmail.com', '2025-04-07 15:23:56'),
(55, 'manya.stud@gmail.com', '2025-04-07 15:25:36'),
(56, 'devang.g@gmail.com', '2025-04-07 16:21:37'),
(57, 'devang.g@gmail.com', '2025-04-07 19:09:37'),
(58, 'devang.g@gmail.com', '2025-04-07 19:18:23'),
(59, 'jidnya.stud@gmail.com', '2025-04-07 19:19:09'),
(60, 'devang.g@gmail.com', '2025-04-07 19:36:52'),
(61, 'jidnya.stud@gmail.com', '2025-04-07 19:39:14'),
(62, 'devang.g@gmail.com', '2025-04-07 19:39:43'),
(63, 'devang.g@gmail.com', '2025-04-07 19:42:38'),
(64, 'devang.g@gmail.com', '2025-04-07 20:19:27');

-- --------------------------------------------------------

--
-- Stand-in structure for view `student_view`
-- (See below for the actual view)
--
CREATE TABLE `student_view` (
`name` varchar(100)
,`email` varchar(100)
,`department` varchar(100)
,`year` varchar(5)
,`division` varchar(5)
,`prn_no` varchar(20)
,`guide_id` varchar(20)
);

-- --------------------------------------------------------

--
-- Table structure for table `subject`
--

CREATE TABLE `subject` (
  `subject_code` varchar(10) NOT NULL,
  `subject_name` varchar(100) NOT NULL,
  `guide_id` int(11) NOT NULL,
  `num_teams` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `subject`
--

INSERT INTO `subject` (`subject_code`, `subject_name`, `guide_id`, `num_teams`) VALUES
('CBKPUE', 'DMS', 0, 0),
('TH94IP', 'DSA', 0, 1);

-- --------------------------------------------------------

--
-- Table structure for table `task`
--

CREATE TABLE `task` (
  `task_id` int(11) NOT NULL,
  `team_code` varchar(100) NOT NULL,
  `assigned_by_email` varchar(100) NOT NULL,
  `member_name` varchar(100) NOT NULL,
  `member_email` varchar(100) NOT NULL,
  `task_title` varchar(255) NOT NULL,
  `task_description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `status` enum('Not Started','In Progress','Completed') DEFAULT 'Not Started',
  `file_upload` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `task`
--

INSERT INTO `task` (`task_id`, `team_code`, `assigned_by_email`, `member_name`, `member_email`, `task_title`, `task_description`, `due_date`, `status`, `file_upload`, `created_at`) VALUES
(3, '5698ZZ', 'jidnya.stud@gmail.com', 'Manya B', 'manya.stud@gmail.com', 'SRS document', 'Prepare a Software requirement analysis document as per IEEE format', '2025-04-09', 'Not Started', NULL, '2025-04-07 03:25:00');

-- --------------------------------------------------------

--
-- Table structure for table `team`
--

CREATE TABLE `team` (
  `team_id` int(11) NOT NULL,
  `team_name` varchar(100) NOT NULL,
  `team_leader_name` varchar(100) NOT NULL,
  `team_leader_email` varchar(100) NOT NULL,
  `team_members_names` text NOT NULL,
  `team_members_emails` text NOT NULL,
  `guide_name` varchar(100) NOT NULL,
  `guide_email` varchar(100) NOT NULL,
  `creation_datetime` datetime DEFAULT current_timestamp(),
  `team_size` int(11) DEFAULT NULL,
  `team_code` varchar(100) DEFAULT NULL,
  `subject_code` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team`
--

INSERT INTO `team` (`team_id`, `team_name`, `team_leader_name`, `team_leader_email`, `team_members_names`, `team_members_emails`, `guide_name`, `guide_email`, `creation_datetime`, `team_size`, `team_code`, `subject_code`) VALUES
(1, 'CodeCraft', 'Jidnya J', 'jidnya.stud@gmail.com', '[\"Manya B\",\"Tanya L\",\"Sanya C\",\"\"]', '[\"manya.stud@gmail.com\",\"tanya.stud@gmail.com\",\"sanya.stud@gmail.com\",\"\"]', 'Devang G', 'devang.g@gmail.com', '2025-04-06 10:17:13', 4, '4523PO', ''),
(4, 'CodeBrew', 'Jidnya J', 'jidnya.stud@gmail.com', '[\"Manya B\",\"Tanya L\",\"Sanya C\",\"\"]', '[\"manya.stud@gmail.com\",\"tanya.stud@gmail.com\",\"sanya.stud@gmail.com\",\"\"]', 'Devang G', 'devang.g@gmail.com', '2025-04-06 12:36:20', 4, '5698ZZ', 'CBKPUE'),
(5, 'CodeBrew', 'Jidnya J', 'jidnya.stud@gmail.com', '[\"Manya B\",\"Tanya L\",\"Sanya C\",\"\"]', '[\"manya.stud@gmail.com\",\"tanya.stud@gmail.com\",\"sanya.stud@gmail.com\",\"\"]', 'Devang G', 'devang.g@gmail.com', '2025-04-06 12:37:29', 4, '7343GF', ''),
(6, 'Random()', 'Diya Jain', 'diya.stud@gmail.com', '[\"Kiya K\",\"Riya R\",\"Gina G\",\"\"]', '[\"kiya.stud@gmail.com\",\"riya.stud@gmail.com\",\"gina.stud@gmail.com\",\"\"]', 'Devang G', 'devang.g@gmail.com', '2025-04-06 19:41:20', 4, '5VM85Z', ''),
(7, 'PowerPuffGirls', 'Purva R', 'purva.stud@gmail.com', '[\"Alex C\",\"Glen V\",\"Ben K\",\"\"]', '[\"alex.stud@gmail.com\",\"glen.stud@gmail.com\",\"ben.stud@gmail.com\",\"\"]', 'tanishka K', 'tanishka.k@gmail.com', '2025-04-07 09:11:39', 4, 'MIYG02', ''),
(8, 'xyz1', 'Jidnya J', 'jidnya.stud@gmail.com', '[\"Alex C\",\"Glen V\",\"Ben K\",\"\"]', '[\"alex.stud@gmail.com\",\"glen.stud@gmail.com\",\"ben.stud@gmail.com\",\"\"]', 'Devang G', 'devang.g@gmail.com', '2025-04-07 09:33:05', 4, '9IA3C6', ''),
(10, 'FancyCoders', 'Jidnya J', 'jidnya.stud@gmail.com', '[\"Alex C\",\"Glen V\",\"Ben K\",\"\"]', '[\"alex.stud@gmail.com\",\"glen.stud@gmail.com\",\"ben.stud@gmail.com\",\"\"]', 'Devang G', 'devang.g@gmail.com', '2025-04-07 23:09:27', 4, 'A5GWDV', 'TH94IP');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `role` enum('student','guide') NOT NULL,
  `prn_no` varchar(20) DEFAULT NULL,
  `guide_id` varchar(20) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `department` varchar(100) DEFAULT NULL,
  `division` varchar(5) DEFAULT NULL,
  `year` varchar(5) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`name`, `email`, `role`, `prn_no`, `guide_id`, `password`, `department`, `division`, `year`) VALUES
('Alex C', 'alex.stud@gmail.com', 'student', '12425555', NULL, '$2y$10$gEmAe.REVyEYPFHbKg3Qq.BPw71rtkMhjSGt9zT6E/j3ctNiRuUli', 'IT', 'A', 'TY'),
('Devang G', 'devang.g@gmail.com', 'guide', NULL, 'GUIDE_67f19146605d7', '$2y$10$ttUF/sw8sz2yK5FVinLZxOCJzAUUa76282bvhnGPfwKLKoBqTEkuG', NULL, NULL, NULL),
('Diya Jain', 'diya.stud@gmail.com', 'student', '12420666', NULL, '$2y$10$Q5tqGDjcPqtKcFmpARqsyuMMmA72zZL3M.Pag9yP08bOUFEaDd8Dq', 'IT', 'A', 'SY'),
('jidnya j', 'jidnya.stud@gmail.com', 'student', '12420221', NULL, '$2y$10$riuy3KLtoe64iYYgnsp7OOWN860bT0DfxCx64vYT95enAOTVToFXi', 'AIDS', 'C', 'SY'),
('Manya B', 'manya.stud@gmail.com', 'student', '12425545', NULL, '$2y$10$9I7oDgW.3LCZNzARgLwie.fcaILkj.dUaTTz0wWKZWOJsemH7z0xK', 'AIDS', 'C', 'SY'),
('Purva R', 'purva.stud@gmail.com', 'student', '12420303', NULL, '$2y$10$AuXyK.Pie6.BBBm9Ctb7PO.50Qx0tbUuA3Rs/Q/y.GbTXplrsDQuy', 'CIVIL', 'B', 'TY'),
('Ruby H', 'ruby.stud@gmail.com', 'student', '1242565', NULL, '$2y$10$Rmi7mGq7cyxPpr.gZ0lyC.h7jXhADm1w1zUA3FivDw41fLJ3pAz96', 'CS', 'B', 'FY'),
('tanishka K', 'tanishka.k@gmail.com', 'guide', NULL, 'GUIDE_67f1986106d4c', '$2y$10$Q4NZpqM6qHJWjleekwSBouRyPOLBCt7ChyiyJhdE2tZEN16sne09q', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure for view `student_view`
--
DROP TABLE IF EXISTS `student_view`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `student_view`  AS SELECT `users`.`name` AS `name`, `users`.`email` AS `email`, `users`.`department` AS `department`, `users`.`year` AS `year`, `users`.`division` AS `division`, `users`.`prn_no` AS `prn_no`, `users`.`guide_id` AS `guide_id` FROM `users` WHERE `users`.`role` = 'student' ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `logins`
--
ALTER TABLE `logins`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email` (`email`);

--
-- Indexes for table `subject`
--
ALTER TABLE `subject`
  ADD PRIMARY KEY (`subject_code`);

--
-- Indexes for table `task`
--
ALTER TABLE `task`
  ADD PRIMARY KEY (`task_id`),
  ADD KEY `team_code` (`team_code`);

--
-- Indexes for table `team`
--
ALTER TABLE `team`
  ADD PRIMARY KEY (`team_id`),
  ADD UNIQUE KEY `team_code` (`team_code`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`email`),
  ADD UNIQUE KEY `guide_id` (`guide_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `logins`
--
ALTER TABLE `logins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=65;

--
-- AUTO_INCREMENT for table `task`
--
ALTER TABLE `task`
  MODIFY `task_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `team`
--
ALTER TABLE `team`
  MODIFY `team_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `logins`
--
ALTER TABLE `logins`
  ADD CONSTRAINT `logins_ibfk_1` FOREIGN KEY (`email`) REFERENCES `users` (`email`);

--
-- Constraints for table `task`
--
ALTER TABLE `task`
  ADD CONSTRAINT `task_ibfk_1` FOREIGN KEY (`team_code`) REFERENCES `team` (`team_code`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
