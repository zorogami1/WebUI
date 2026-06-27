-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 27, 2026 at 09:31 AM
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
-- Database: `createprojectdb`
--

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
                             `cid` int(11) NOT NULL,
                             `cname` varchar(255) NOT NULL,
                             `cpassword` varchar(255) NOT NULL,
                             `ctel` varchar(20) NOT NULL,
                             `caddr` varchar(255) NOT NULL,
                             `ccompany` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`cid`, `cname`, `cpassword`, `ctel`, `caddr`, `ccompany`) VALUES
                                                                                       (1, 'John Doe', '123456', '+852 1234 5678', '123 Main Street, Hong Kong', 'ABC Company Ltd.'),
                                                                                       (2, 'Jane Smith', 'qwerty', '+852 9876 5432', '456 Nathan Road, Kowloon', NULL),
                                                                                       (3, 'John Cena', 'johncena', '11223344', 'hong kong', NULL),
                                                                                       (5, 'Cheung Yat Long', 'verygood', '+852 1919 2828', 'regal estate new territory', 'Cheung Furniture Ltd.'),
                                                                                       (6, '19192828', 'verygood', '94463393', 'Please update address', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `furniturematerials`
--

CREATE TABLE `furniturematerials` (
                                      `fid` int(11) NOT NULL,
                                      `mid` int(11) NOT NULL,
                                      `pmqty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `furniturematerials`
--

INSERT INTO `furniturematerials` (`fid`, `mid`, `pmqty`) VALUES
                                                             (1, 1, 2),
                                                             (2, 1, 10),
                                                             (3, 1, 5),
                                                             (3, 3, 10),
                                                             (3, 4, 3),
                                                             (4, 1, 15),
                                                             (5, 1, 4),
                                                             (5, 2, 6),
                                                             (6, 1, 12),
                                                             (7, 1, 6),
                                                             (8, 1, 14),
                                                             (8, 2, 3),
                                                             (8, 3, 3),
                                                             (8, 4, 4);

-- --------------------------------------------------------

--
-- Table structure for table `furnitures`
--

CREATE TABLE `furnitures` (
                              `fid` int(11) NOT NULL,
                              `fname` varchar(255) NOT NULL,
                              `fdesc` varchar(255) NOT NULL,
                              `fprice` decimal(10,2) NOT NULL,
                              `fimage` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `furnitures`
--

INSERT INTO `furnitures` (`fid`, `fname`, `fdesc`, `fprice`, `fimage`) VALUES
                                                                           (1, 'Oak Dining Chair', 'Classic style dining chair made of solid oak.', 450.00, NULL),
                                                                           (2, 'Large Dining Table', '6-seater dining table, perfect for families.', 2500.00, NULL),
                                                                           (3, '3-Seater Fabric Sofa', 'Comfortable grey fabric sofa with foam filling.', 3800.00, NULL),
                                                                           (4, 'Wooden Wardrobe', 'Double door wardrobe with hanging space.', 1800.00, NULL),
                                                                           (5, 'Industrial Bookshelf', 'Modern style bookshelf with steel frame.', 1200.00, NULL),
                                                                           (6, 'Queen Size Bed Frame', 'Sturdy bed frame for queen size mattress.', 2200.00, NULL),
                                                                           (7, 'Wave Coffee Table', 'Where you put coffee aside and think about the php!', 12000.00, 'uploads/1782536992_Wave Coffee Table.webp'),
                                                                           (8, 'Single Bed Wooden Drawer Wooden Frame and Comforting Mattress', 'Cosy Mattress which comforts you at night lets you fall asleep faster the the wooden lasts for longer', 5000.00, 'uploads/1782544123_single_Bed_Wooden_Drawer_Wooden_Frames_webp');

-- --------------------------------------------------------

--
-- Table structure for table `materials`
--

CREATE TABLE `materials` (
                             `mid` int(11) NOT NULL,
                             `mname` varchar(255) NOT NULL,
                             `mtype` varchar(50) DEFAULT NULL,
                             `mqty` int(11) NOT NULL DEFAULT 0,
                             `munit` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `materials`
--

INSERT INTO `materials` (`mid`, `mname`, `mtype`, `mqty`, `munit`) VALUES
                                                                       (1, 'Oak Wood Plank', NULL, 398, 'pcs'),
                                                                       (2, 'Steel Tube', NULL, 200, 'meter'),
                                                                       (3, 'Fabric Cloth', NULL, 100, 'meter'),
                                                                       (4, 'High Density Foam', NULL, 50, 'block'),
                                                                       (5, 'oak wood plank', 'Wood', 2, 'meter');

-- --------------------------------------------------------

--
-- Table structure for table `orderfurnitures`
--

CREATE TABLE `orderfurnitures` (
                                   `oid` int(11) NOT NULL,
                                   `fid` int(11) NOT NULL,
                                   `oqty` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orderfurnitures`
--

INSERT INTO `orderfurnitures` (`oid`, `fid`, `oqty`) VALUES
                                                         (1, 1, 1),
                                                         (3, 1, 6),
                                                         (1, 2, 10),
                                                         (4, 2, 1),
                                                         (5, 3, 1),
                                                         (6, 4, 2),
                                                         (7, 5, 3),
                                                         (8, 6, 2);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
                          `oid` int(11) NOT NULL,
                          `odate` datetime NOT NULL DEFAULT current_timestamp(),
                          `ototalamount` decimal(10,2) NOT NULL,
                          `cid` int(11) NOT NULL,
                          `odeliverydate` datetime NOT NULL,
                          `odeliveraddress` text NOT NULL,
                          `ostatus` int(11) NOT NULL DEFAULT 1,
                          `status` varchar(50) DEFAULT 'Open'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`oid`, `odate`, `ototalamount`, `cid`, `odeliverydate`, `odeliveraddress`, `ostatus`, `status`) VALUES
                                                                                                                          (1, '2026-06-26 23:39:33', 25450.00, 1, '2026-06-28 00:00:00', 'hong kong tsing yi tsing mui house floor 12 rm 18', 3, 'Completed'),
                                                                                                                          (3, '2026-06-27 14:54:02', 2700.00, 5, '2026-07-04 00:00:00', 'regal estate new territory', 1, 'Processing'),
                                                                                                                          (4, '2026-06-27 14:54:05', 2500.00, 5, '2026-07-04 00:00:00', 'regal estate new territory', 1, 'Processing'),
                                                                                                                          (5, '2026-06-27 14:54:15', 3800.00, 5, '2026-07-04 00:00:00', 'regal estate new territory', 1, 'Cancelled'),
                                                                                                                          (6, '2026-06-27 14:54:22', 3600.00, 5, '2026-07-04 00:00:00', 'regal estate new territory', 1, 'Completed'),
                                                                                                                          (7, '2026-06-27 14:54:26', 3600.00, 5, '2026-07-04 00:00:00', 'regal estate new territory', 1, 'Delivered'),
                                                                                                                          (8, '2026-06-27 14:54:52', 4400.00, 5, '2026-07-04 00:00:00', 'regal estate new territory', 1, 'Processing');

-- --------------------------------------------------------

--
-- Table structure for table `staffs`
--

CREATE TABLE `staffs` (
                          `sid` int(11) NOT NULL,
                          `sname` varchar(100) NOT NULL,
                          `semail` varchar(100) NOT NULL,
                          `spassword` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `staffs`
--

INSERT INTO `staffs` (`sid`, `sname`, `semail`, `spassword`) VALUES
    (1, 'Admin Staff', 'staff@premiumliving.com', 'staff123');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
    ADD PRIMARY KEY (`cid`);

--
-- Indexes for table `furniturematerials`
--
ALTER TABLE `furniturematerials`
    ADD PRIMARY KEY (`fid`,`mid`),
  ADD KEY `mid` (`mid`);

--
-- Indexes for table `furnitures`
--
ALTER TABLE `furnitures`
    ADD PRIMARY KEY (`fid`);

--
-- Indexes for table `materials`
--
ALTER TABLE `materials`
    ADD PRIMARY KEY (`mid`);

--
-- Indexes for table `orderfurnitures`
--
ALTER TABLE `orderfurnitures`
    ADD PRIMARY KEY (`fid`,`oid`),
  ADD KEY `oid` (`oid`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
    ADD PRIMARY KEY (`oid`),
  ADD KEY `cid` (`cid`);

--
-- Indexes for table `staffs`
--
ALTER TABLE `staffs`
    ADD PRIMARY KEY (`sid`),
  ADD UNIQUE KEY `semail` (`semail`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
    MODIFY `cid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `furnitures`
--
ALTER TABLE `furnitures`
    MODIFY `fid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `materials`
--
ALTER TABLE `materials`
    MODIFY `mid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
    MODIFY `oid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `staffs`
--
ALTER TABLE `staffs`
    MODIFY `sid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `furniturematerials`
--
ALTER TABLE `furniturematerials`
    ADD CONSTRAINT `furniturematerials_ibfk_1` FOREIGN KEY (`fid`) REFERENCES `furnitures` (`fid`),
  ADD CONSTRAINT `furniturematerials_ibfk_2` FOREIGN KEY (`mid`) REFERENCES `materials` (`mid`);

--
-- Constraints for table `orderfurnitures`
--
ALTER TABLE `orderfurnitures`
    ADD CONSTRAINT `orderfurnitures_ibfk_1` FOREIGN KEY (`fid`) REFERENCES `furnitures` (`fid`),
  ADD CONSTRAINT `orderfurnitures_ibfk_2` FOREIGN KEY (`oid`) REFERENCES `orders` (`oid`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
    ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`cid`) REFERENCES `customers` (`cid`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
