-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Feb 10, 2026 at 05:43 AM
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
-- Database: `mydrupal`
--

-- --------------------------------------------------------

--
-- Table structure for table `event_registration_event`
--

CREATE TABLE `event_registration_event` (
  `id` int(11) NOT NULL,
  `event_name` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `event_date` DATETIME NOT NULL,
  `reg_start_date` DATETIME NOT NULL,
  `reg_end_date` DATETIME NOT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores event configuration details.';

--
-- Dumping data for table `event_registration_event`
--

INSERT INTO `event_registration_event` (`id`, `event_name`, `category`, `event_date`, `reg_start_date`, `reg_end_date`, `created`) VALUES
(6, 'test', 'Online Workshop', 1771957800, 1770229800, 1771093800, 1770636401),
(7, 'test1', 'Hackathon', 1771871400, 1769884200, 1771525800, 1770698356),
(8, 'test2', 'Conference', 1771785000, 1769970600, 1771612200, 1770698381),
(9, 'test3', 'One-day Workshop', 1771698600, 1770057000, 1771007400, 1770698407);

-- --------------------------------------------------------

--
-- Table structure for table `event_registration_submission`
--

CREATE TABLE `event_registration_submission` (
  `id` int(11) NOT NULL,
  `full_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `college` varchar(255) NOT NULL,
  `department` varchar(255) NOT NULL,
  `category` varchar(100) NOT NULL,
  `event_date` DATETIME NOT NULL,
  `event_id` int(11) NOT NULL,
  `created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Stores event registration submissions.';

--
-- Dumping data for table `event_registration_submission`
--

INSERT INTO `event_registration_submission` (`id`, `full_name`, `email`, `college`, `department`, `category`, `event_date`, `event_id`, `created`) VALUES
(15, 'TestGuy', 'Testguy@gmail.com', 'TestCollege', 'TestDepartment', 'Online Workshop', 1771957800, 6, 1770636434),
(16, 'TestGuy', 'Testguy@gmail.com', 'TestCollege', 'TestDepartment', 'Conference', 1771785000, 8, 1770698458),
(17, 'TestGuy', 'Testguy@gmail.com', 'TestCollege', 'TestDepartment', 'One-day Workshop', 1771698600, 9, 1770698477);

--
-- Indexes for dumped tables
--

--
-- Indexes for table `event_registration_event`
--
ALTER TABLE `event_registration_event`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `event_registration_submission`
--
ALTER TABLE `event_registration_submission`
  ADD PRIMARY KEY (`id`),
  ADD KEY `email_event_date` (`email`(191),`event_date`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `event_registration_event`
--
ALTER TABLE `event_registration_event`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `event_registration_submission`
--
ALTER TABLE `event_registration_submission`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

