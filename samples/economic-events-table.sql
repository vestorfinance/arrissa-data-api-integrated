-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 18, 2025 at 10:39 AM
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
-- Database: `market_data`
--

-- --------------------------------------------------------

--
-- Table structure for table `economic_events`
--

CREATE TABLE `economic_events` (
  `event_id` varchar(40) NOT NULL,
  `event_name` varchar(255) DEFAULT NULL,
  `event_date` date DEFAULT NULL,
  `event_time` time DEFAULT NULL,
  `currency` varchar(10) DEFAULT NULL,
  `forecast_value` double DEFAULT NULL,
  `actual_value` double DEFAULT NULL,
  `previous_value` double DEFAULT NULL,
  `impact_level` varchar(50) DEFAULT NULL,
  `consistent_event_id` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `economic_events`
--

INSERT INTO `economic_events` (`event_id`, `event_name`, `event_date`, `event_time`, `currency`, `forecast_value`, `actual_value`, `previous_value`, `impact_level`, `consistent_event_id`) VALUES
('000432722ba88c2f25180aa9d7f02bdc77566829', 'Retail Sales (YoY) (Dec)', '2025-01-17', '07:00:00', 'GBP', 4.2, 3.6, 0, 'Moderate', 'KIOBN'),
('0016005f8f4e629c763c7b032d7aebe8a5031e9d', 'Export Price Index (MoM) (Apr)', '2025-05-16', '12:30:00', 'USD', -0.5, 0.1, 0.1, 'Moderate', 'JXDNC'),
('001b36854b5d2c008e18a49b60b41ad63eebc755', 'German ZEW Economic Sentiment (Aug)', '2025-08-12', '09:00:00', 'EUR', 39.5, 34.7, 52.7, 'Moderate', 'DDXDT'),
('001eed7aaa0c29c8398fbc24b767ce8d62f017a3', 'Fed Vice Chair for Supervision Barr Speaks', '2025-11-19', '02:30:00', 'USD', NULL, NULL, NULL, 'Moderate', 'JRUEB'),
('0027e4873d75dd08416318a6b4bbd01c644da953', 'Continuing Jobless Claims', '2025-05-08', '12:30:00', 'USD', 1890000, 1879000, 1908000, 'Moderate', 'PIIRP');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `economic_events`
--
ALTER TABLE `economic_events`
  ADD PRIMARY KEY (`event_id`),
  ADD KEY `idx_event_date` (`event_date`),
  ADD KEY `idx_currency` (`currency`),
  ADD KEY `idx_impact_level` (`impact_level`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
