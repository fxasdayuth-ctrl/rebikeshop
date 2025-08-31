-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Aug 28, 2025 at 04:38 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `rebikeshop`
--

-- --------------------------------------------------------

--
-- Table structure for table `bikes`
--

CREATE TABLE `bikes` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `bike_type_id` int(11) DEFAULT NULL,
  `brand` varchar(100) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `serial_number` varchar(100) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bikes`
--

INSERT INTO `bikes` (`id`, `customer_id`, `bike_type_id`, `brand`, `model`, `color`, `serial_number`, `purchase_date`, `created_at`) VALUES
(1, 3, 1, 'Trek', 'Marlin 5', 'Black', 'TRK123456', '2024-03-15', '2025-08-26 14:59:24'),
(2, 4, 2, 'Giant', 'Defy Advanced', 'Blue', 'GNT789012', '2024-06-20', '2025-08-26 14:59:24'),
(3, 5, 3, 'Specialized', 'Turbo Vado', 'Red', 'SPZ345678', '2024-09-10', '2025-08-26 14:59:24'),
(4, 6, 4, 'Schwinn', 'Kids Balance', 'Green', 'SCH901234', '2023-12-05', '2025-08-26 14:59:24'),
(5, 7, 1, 'Cannondale', 'Trail 6', 'Silver', 'CND567890', '2024-01-25', '2025-08-26 14:59:24'),
(6, 8, 2, 'Bianchi', 'Aria', 'White', 'BNC234567', '2024-04-12', '2025-08-26 14:59:24'),
(7, 9, 3, 'Rad Power', 'RadRover 6', 'Black', 'RAD890123', '2024-07-30', '2025-08-26 14:59:24'),
(8, 10, 4, 'Strider', '12 Sport', 'Yellow', 'STR456789', '2023-11-18', '2025-08-26 14:59:24'),
(9, 11, 1, 'Scott', 'Scale 970', 'Orange', 'SCT012345', '2024-02-28', '2025-08-26 14:59:24'),
(10, 12, 2, 'Merida', 'Scultura 400', 'Grey', 'MRD678901', '2024-05-15', '2025-08-26 14:59:24');

-- --------------------------------------------------------

--
-- Table structure for table `bike_types`
--

CREATE TABLE `bike_types` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bike_types`
--

INSERT INTO `bike_types` (`id`, `name`, `description`) VALUES
(1, 'จักรยานภูเขา', 'จักรยานสำหรับขี่บนทางวิบากและทางธรรมชาติ'),
(2, 'จักรยานทางเรียบ', 'จักรยานสำหรับขี่บนทางเรียบในเมือง'),
(3, 'จักรยานไฟฟ้า', 'จักรยานที่มีระบบขับเคลื่อนด้วยไฟฟ้า'),
(4, 'จักรยานเด็ก', 'จักรยานขนาดเล็กสำหรับเด็ก');

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `district` varchar(100) DEFAULT NULL,
  `province` varchar(100) DEFAULT NULL,
  `zip_code` varchar(10) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`id`, `first_name`, `last_name`, `phone`, `email`, `address`, `district`, `province`, `zip_code`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'สมชาย', 'ใจดี', '081-234-5678', 'somchai@example.com', '123 ถนนตัวอย่าง', 'เขตทดสอบ', 'กรุงเทพฯ', '10100', NULL, '2025-08-25 06:30:42', '2025-08-25 06:30:42'),
(2, 'สมหญิง', 'รักงาน', '082-345-6789', 'somying@example.com', '456 ซอยตัวอย่าง', 'อำเภอทดลอง', 'นนทบุรี', '10200', NULL, '2025-08-25 06:30:42', '2025-08-25 06:30:42'),
(3, 'กิตติ', 'สุขใจ', '081-111-2222', 'kitti@example.com', '789 ถนนสุขสันต์', 'เขตสวนหลวง', 'กรุงเทพฯ', '10250', NULL, '2025-08-26 14:56:49', '2025-08-26 14:56:49'),
(4, 'มานะ', 'มุ่งมั่น', '082-222-3333', 'mana@example.com', '101 ถนนมุ่งมั่น', 'อำเภอบางใหญ่', 'นนทบุรี', '11140', NULL, '2025-08-26 14:56:49', '2025-08-26 14:56:49'),
(5, 'สุนทร', 'ยิ้มแย้ม', '083-333-4444', 'sunthorn@example.com', '202 ถนนยิ้มแย้ม', 'เขตบางเขน', 'กรุงเทพฯ', '10220', NULL, '2025-08-26 14:56:49', '2025-08-26 14:56:49'),
(6, 'วรรณี', 'สว่าง', '084-444-5555', 'wannee@example.com', '303 ถนนสว่าง', 'อำเภอเมือง', 'สมุทรปราการ', '10270', NULL, '2025-08-26 14:56:49', '2025-08-26 14:56:49'),
(7, 'ชัยวัฒน์', 'มั่นคง', '085-555-6666', 'chaiwat@example.com', '404 ถนนมั่นคง', 'เขตดอนเมือง', 'กรุงเทพฯ', '10210', NULL, '2025-08-26 14:56:49', '2025-08-26 14:56:49'),
(8, 'นฤมล', 'ใจเย็น', '086-666-7777', 'narumon@example.com', '505 ถนนสงบ', 'อำเภอเมือง', 'ปทุมธานี', '12000', NULL, '2025-08-26 14:56:49', '2025-08-26 14:56:49'),
(9, 'ประวิทย์', 'ตั้งใจ', '087-777-8888', 'prawit@example.com', '606 ถนนตั้งใจ', 'เขตสายไหม', 'กรุงเทพฯ', '10220', NULL, '2025-08-26 14:56:49', '2025-08-26 14:56:49'),
(10, 'ศิริพร', 'รุ่งเรือง', '088-888-9999', 'siriporn@example.com', '707 ถนนรุ่งเรือง', 'อำเภอบางพลี', 'สมุทรปราการ', '10540', NULL, '2025-08-26 14:56:49', '2025-08-26 14:56:49'),
(11, 'ธนากร', 'มุ่งหวัง', '089-999-0000', 'thanagon@example.com', '808 ถนนมุ่งหวัง', 'เขตจตุจักร', 'กรุงเทพฯ', '10900', NULL, '2025-08-26 14:56:49', '2025-08-26 14:56:49'),
(12, 'จันทร์จิรา', 'แสงดาว', '090-000-1111', 'janchira@example.com', '909 ถนนแสงดาว', 'อำเภอลำลูกกา', 'ปทุมธานี', '12150', NULL, '2025-08-26 14:56:49', '2025-08-26 14:56:49'),
(13, 'มาร์ค', 'หล่อว่ะ', '0123456789', '68319100021@phuketvc.ac.th', '123 กรุงเทพ', 'สักที่', 'สักที่', '80110', 'เดี่ยวมาเอานะครับ', '2025-08-26 15:19:54', '2025-08-26 15:19:54');

-- --------------------------------------------------------

--
-- Table structure for table `repair_items`
--

CREATE TABLE `repair_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `description` text NOT NULL,
  `cost` decimal(10,2) NOT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repair_items`
--

INSERT INTO `repair_items` (`id`, `order_id`, `description`, `cost`, `notes`) VALUES
(1, 1, 'เปลี่ยนแฮนด์', 593.00, 'หมายเหตุสำหรับไอเท็ม 1'),
(2, 1, 'เปลี่ยนแฮนด์', 915.00, 'หมายเหตุสำหรับไอเท็ม 2'),
(3, 1, 'เปลี่ยนยาง', 748.00, NULL),
(4, 1, 'เปลี่ยน saddle', 595.00, NULL),
(5, 2, 'ทำความสะอาด', 208.00, 'หมายเหตุสำหรับไอเท็ม 1'),
(6, 2, 'ซ่อมเฟรม', 923.00, 'หมายเหตุสำหรับไอเท็ม 2'),
(7, 3, 'เปลี่ยน saddle', 537.00, 'หมายเหตุสำหรับไอเท็ม 1'),
(8, 3, 'ทำความสะอาด', 304.00, NULL),
(9, 3, 'เปลี่ยนโซ่', 100.00, NULL),
(10, 4, 'ติดตั้งไฟ', 270.00, NULL),
(11, 4, 'เปลี่ยน saddle', 242.00, 'หมายเหตุสำหรับไอเท็ม 2'),
(12, 4, 'เปลี่ยนแฮนด์', 112.00, NULL),
(13, 4, 'เปลี่ยนเฟือง', 861.00, 'หมายเหตุสำหรับไอเท็ม 4'),
(14, 5, 'เปลี่ยน saddle', 413.00, 'หมายเหตุสำหรับไอเท็ม 1'),
(15, 5, 'เปลี่ยนโซ่', 474.00, NULL),
(16, 5, 'ปรับเกียร์', 947.00, NULL),
(17, 5, 'เปลี่ยน saddle', 227.00, NULL),
(18, 5, 'เปลี่ยนโซ่', 241.00, NULL),
(19, 6, 'เปลี่ยนโซ่', 978.00, NULL),
(20, 6, 'ปรับเกียร์', 777.00, 'หมายเหตุสำหรับไอเท็ม 2'),
(21, 6, 'เปลี่ยนยาง', 124.00, 'หมายเหตุสำหรับไอเท็ม 3'),
(22, 6, 'เปลี่ยนยาง', 241.00, 'หมายเหตุสำหรับไอเท็ม 4'),
(23, 7, 'ปรับเบรก', 992.00, 'หมายเหตุสำหรับไอเท็ม 1'),
(24, 7, 'เปลี่ยนเฟือง', 244.00, 'หมายเหตุสำหรับไอเท็ม 2'),
(25, 8, 'ทำความสะอาด', 812.00, 'หมายเหตุสำหรับไอเท็ม 1'),
(26, 9, 'ปรับเกียร์', 939.00, 'หมายเหตุสำหรับไอเท็ม 1'),
(27, 9, 'ซ่อมเฟรม', 943.00, 'หมายเหตุสำหรับไอเท็ม 2'),
(28, 9, 'ทำความสะอาด', 901.00, NULL),
(29, 10, 'เปลี่ยนเฟือง', 616.00, NULL),
(30, 10, 'ติดตั้งไฟ', 167.00, NULL),
(31, 10, 'เปลี่ยนยาง', 143.00, NULL),
(32, 10, 'เปลี่ยนยาง', 802.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `repair_orders`
--

CREATE TABLE `repair_orders` (
  `id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `bike_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `received_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `estimated_completion` date DEFAULT NULL,
  `status` enum('received','diagnosing','repairing','waiting_parts','completed','delivered') DEFAULT 'received',
  `total_cost` decimal(10,2) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `brand` varchar(100) NOT NULL,
  `model` varchar(100) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `name` varchar(255) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `repair_orders`
--

INSERT INTO `repair_orders` (`id`, `customer_id`, `bike_id`, `order_number`, `received_date`, `estimated_completion`, `status`, `total_cost`, `notes`, `created_at`, `updated_at`, `brand`, `model`, `color`, `name`) VALUES
(1, 3, 1, 'RO-20250826-001', '2025-08-26 03:00:00', '2025-08-30', 'received', 2851.00, 'รอยางรั่ว ต้องเปลี่ยนยางใหม่', '2025-08-26 15:02:21', '2025-08-27 13:16:43', '', NULL, NULL, ''),
(2, 4, 2, 'RO-20250826-002', '2025-08-26 04:00:00', '2025-08-29', 'diagnosing', NULL, 'ระบบเกียร์มีปัญหา', '2025-08-26 15:02:21', '2025-08-26 15:02:21', '', NULL, NULL, ''),
(3, 5, 3, 'RO-20250826-003', '2025-08-26 05:00:00', '2025-09-01', 'waiting_parts', NULL, 'รออะไหล่แบตเตอรี่สำหรับจักรยานไฟฟ้า', '2025-08-26 15:02:21', '2025-08-26 15:02:21', '', NULL, NULL, ''),
(4, 6, 4, 'RO-20250826-004', '2025-08-26 06:00:00', '2025-08-28', 'repairing', NULL, 'ปรับแต่งเบรกและล้อสำหรับจักรยานเด็ก', '2025-08-26 15:02:21', '2025-08-26 15:02:21', '', NULL, NULL, ''),
(5, 7, 5, 'RO-20250826-005', '2025-08-26 07:00:00', '2025-08-31', 'received', NULL, 'ตรวจสอบโช๊คหน้า', '2025-08-26 15:02:21', '2025-08-26 15:02:21', '', NULL, NULL, ''),
(6, 8, 6, 'RO-20250826-006', '2025-08-26 08:00:00', '2025-09-02', 'diagnosing', NULL, 'ตรวจสอบระบบเบรก', '2025-08-26 15:02:21', '2025-08-26 15:02:21', '', NULL, NULL, ''),
(7, 9, 7, 'RO-20250826-007', '2025-08-26 09:00:00', '2025-09-03', 'waiting_parts', NULL, 'รออะไหล่สำหรับมอเตอร์ไฟฟ้า', '2025-08-26 15:02:21', '2025-08-26 15:02:21', '', NULL, NULL, ''),
(8, 10, 8, 'RO-20250826-008', '2025-08-26 10:00:00', '2025-08-30', 'repairing', NULL, 'เปลี่ยนล้อหลัง', '2025-08-26 15:02:21', '2025-08-26 15:02:21', '', NULL, NULL, ''),
(9, 11, 9, 'RO-20250826-009', '2025-08-26 11:00:00', '2025-09-01', 'received', NULL, 'ตรวจสอบโซ่และเกียร์', '2025-08-26 15:02:21', '2025-08-26 15:02:21', '', NULL, NULL, ''),
(10, 12, 10, 'RO-20250826-010', '2025-08-26 12:00:00', '2025-08-31', 'completed', 1500.00, 'ซ่อมยางและปรับแต่งเกียร์เสร็จสมบูรณ์', '2025-08-26 15:02:21', '2025-08-26 15:02:21', '', NULL, NULL, '');

-- --------------------------------------------------------

--
-- Table structure for table `status_updates`
--

CREATE TABLE `status_updates` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `status` enum('received','diagnosing','repairing','waiting_parts','completed','delivered') NOT NULL,
  `update_time` timestamp NOT NULL DEFAULT current_timestamp(),
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `status_updates`
--

INSERT INTO `status_updates` (`id`, `order_id`, `status`, `update_time`, `notes`) VALUES
(1, 1, 'delivered', '2025-08-11 17:00:00', NULL),
(2, 1, 'received', '2025-08-13 17:00:00', 'อัพเดท 2 สำหรับออเดอร์ RO-20250827-01'),
(3, 1, 'waiting_parts', '2025-08-13 17:00:00', NULL),
(4, 1, 'received', '2025-08-14 17:00:00', NULL),
(5, 1, 'diagnosing', '2025-08-16 17:00:00', 'อัพเดท 5 สำหรับออเดอร์ RO-20250827-01'),
(6, 1, 'received', '2025-08-16 17:00:00', NULL),
(7, 2, 'received', '2025-08-07 17:00:00', NULL),
(8, 2, 'diagnosing', '2025-08-07 17:00:00', 'อัพเดท 2 สำหรับออเดอร์ RO-20250827-02'),
(9, 2, 'completed', '2025-08-11 17:00:00', 'อัพเดท 3 สำหรับออเดอร์ RO-20250827-02'),
(10, 2, 'diagnosing', '2025-08-12 17:00:00', 'อัพเดท 4 สำหรับออเดอร์ RO-20250827-02'),
(11, 2, 'diagnosing', '2025-08-16 17:00:00', 'อัพเดท 5 สำหรับออเดอร์ RO-20250827-02'),
(12, 3, 'delivered', '2025-08-09 17:00:00', 'อัพเดท 1 สำหรับออเดอร์ RO-20250827-03'),
(13, 3, 'diagnosing', '2025-08-12 17:00:00', 'อัพเดท 2 สำหรับออเดอร์ RO-20250827-03'),
(14, 3, 'received', '2025-08-14 17:00:00', 'อัพเดท 3 สำหรับออเดอร์ RO-20250827-03'),
(15, 3, 'received', '2025-08-14 17:00:00', 'อัพเดท 4 สำหรับออเดอร์ RO-20250827-03'),
(16, 3, 'waiting_parts', '2025-08-14 17:00:00', 'อัพเดท 5 สำหรับออเดอร์ RO-20250827-03'),
(17, 4, 'delivered', '2025-08-07 17:00:00', NULL),
(18, 4, 'waiting_parts', '2025-08-08 17:00:00', 'อัพเดท 2 สำหรับออเดอร์ RO-20250827-04'),
(19, 4, 'received', '2025-08-08 17:00:00', 'อัพเดท 3 สำหรับออเดอร์ RO-20250827-04'),
(20, 4, 'completed', '2025-08-10 17:00:00', NULL),
(21, 4, 'waiting_parts', '2025-08-13 17:00:00', NULL),
(22, 5, 'repairing', '2025-08-10 17:00:00', NULL),
(23, 5, 'completed', '2025-08-15 17:00:00', 'อัพเดท 2 สำหรับออเดอร์ RO-20250827-05'),
(24, 5, 'received', '2025-08-16 17:00:00', 'อัพเดท 3 สำหรับออเดอร์ RO-20250827-05'),
(25, 5, 'repairing', '2025-08-16 17:00:00', NULL),
(26, 6, 'completed', '2025-08-09 17:00:00', 'อัพเดท 1 สำหรับออเดอร์ RO-20250827-06'),
(27, 6, 'diagnosing', '2025-08-12 17:00:00', NULL),
(28, 6, 'completed', '2025-08-15 17:00:00', 'อัพเดท 3 สำหรับออเดอร์ RO-20250827-06'),
(29, 7, 'delivered', '2025-08-10 17:00:00', 'อัพเดท 1 สำหรับออเดอร์ RO-20250827-07'),
(30, 7, 'completed', '2025-08-13 17:00:00', NULL),
(31, 7, 'delivered', '2025-08-14 17:00:00', 'อัพเดท 3 สำหรับออเดอร์ RO-20250827-07'),
(32, 8, 'received', '2025-08-07 17:00:00', 'อัพเดท 1 สำหรับออเดอร์ RO-20250827-08'),
(33, 8, 'waiting_parts', '2025-08-10 17:00:00', NULL),
(34, 8, 'waiting_parts', '2025-08-11 17:00:00', NULL),
(35, 9, 'delivered', '2025-08-07 17:00:00', NULL),
(36, 9, 'completed', '2025-08-08 17:00:00', NULL),
(37, 9, 'repairing', '2025-08-11 17:00:00', NULL),
(38, 9, 'diagnosing', '2025-08-12 17:00:00', 'อัพเดท 4 สำหรับออเดอร์ RO-20250827-09'),
(39, 9, 'diagnosing', '2025-08-13 17:00:00', NULL),
(40, 9, 'waiting_parts', '2025-08-16 17:00:00', NULL),
(41, 10, 'waiting_parts', '2025-08-09 17:00:00', 'อัพเดท 1 สำหรับออเดอร์ RO-20250827-10'),
(42, 10, 'delivered', '2025-08-13 17:00:00', 'อัพเดท 2 สำหรับออเดอร์ RO-20250827-10'),
(43, 10, 'delivered', '2025-08-13 17:00:00', 'อัพเดท 3 สำหรับออเดอร์ RO-20250827-10'),
(44, 10, 'delivered', '2025-08-14 17:00:00', NULL),
(45, 10, 'repairing', '2025-08-16 17:00:00', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `role` enum('admin','staff') DEFAULT 'staff',
  `is_active` tinyint(1) DEFAULT 1,
  `last_login` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `first_name`, `last_name`, `role`, `is_active`, `last_login`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'M1234', 'admin', 'System', 'admin', 1, '2025-08-27 22:41:03', '2025-08-27 13:14:51', '2025-08-27 15:41:03');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `bikes`
--
ALTER TABLE `bikes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `bike_type_id` (`bike_type_id`);

--
-- Indexes for table `bike_types`
--
ALTER TABLE `bike_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `repair_items`
--
ALTER TABLE `repair_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

--
-- Indexes for table `repair_orders`
--
ALTER TABLE `repair_orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `customer_id` (`customer_id`),
  ADD KEY `bike_id` (`bike_id`);

--
-- Indexes for table `status_updates`
--
ALTER TABLE `status_updates`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`);

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
-- AUTO_INCREMENT for table `bikes`
--
ALTER TABLE `bikes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `bike_types`
--
ALTER TABLE `bike_types`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `repair_items`
--
ALTER TABLE `repair_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `repair_orders`
--
ALTER TABLE `repair_orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `status_updates`
--
ALTER TABLE `status_updates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bikes`
--
ALTER TABLE `bikes`
  ADD CONSTRAINT `bikes_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `bikes_ibfk_2` FOREIGN KEY (`bike_type_id`) REFERENCES `bike_types` (`id`);

--
-- Constraints for table `repair_items`
--
ALTER TABLE `repair_items`
  ADD CONSTRAINT `repair_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `repair_orders` (`id`);

--
-- Constraints for table `repair_orders`
--
ALTER TABLE `repair_orders`
  ADD CONSTRAINT `repair_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`),
  ADD CONSTRAINT `repair_orders_ibfk_2` FOREIGN KEY (`bike_id`) REFERENCES `bikes` (`id`);

--
-- Constraints for table `status_updates`
--
ALTER TABLE `status_updates`
  ADD CONSTRAINT `status_updates_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `repair_orders` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
