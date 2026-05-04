-- phpMyAdmin SQL Dump
-- version 4.9.2
-- https://www.phpmyadmin.net/
-- Host: 127.0.0.1:3306
-- Generation Time: May 03, 2026 at 11:12 AM
-- Server version: 10.4.10-MariaDB
-- PHP Version: 7.3.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

--
-- Database: clothingstor
--

-- --------------------------------------------------------

--
-- Table structure for table `tbladmin`
--

DROP TABLE IF EXISTS `tbladmin`;

CREATE TABLE IF NOT EXISTS `tbladmin` (
  `admin_id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('super_admin','moderator') COLLATE utf8mb4_unicode_ci DEFAULT 'moderator',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`admin_id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbladmin`
--

INSERT INTO `tbladmin`
(`admin_id`, `username`, `password`, `email`, `full_name`, `role`, `created_at`)
VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@pastimes.com', 'System Administrator', 'super_admin', '2026-05-02 22:38:46'),

(4, 'moderator1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mod1@pastimes.com', 'Jane Moderator', 'moderator', '2026-05-03 10:11:09'),

(5, 'moderator2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'mod2@pastimes.com', 'Mike Reviewer', 'moderator', '2026-05-03 10:12:51'),

(6, 'support_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'support@pastimes.com', 'Support Team Lead', 'moderator', '2026-05-03 10:14:05'),

(7, 'head_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'head@pastimes.com', 'Head Administrator', 'super_admin', '2026-05-03 10:15:08');

-- --------------------------------------------------------

--
-- Table structure for table `tbluser`
--

DROP TABLE IF EXISTS `tbluser`;

CREATE TABLE IF NOT EXISTS `tbluser` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `cellphone` varchar(15) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('buyer','seller','both') COLLATE utf8mb4_unicode_ci DEFAULT 'buyer',
  `is_verified` tinyint(1) DEFAULT 0,
  `verification_status` enum('pending','verified','rejected') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `verified_by` int(11) DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`),
  KEY `verified_by` (`verified_by`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tbluser`
--

INSERT INTO `tbluser`
(`user_id`, `first_name`, `last_name`, `email`, `username`, `password`, `cellphone`, `role`, `is_verified`, `verification_status`, `verified_by`, `verified_at`, `rejection_reason`, `created_at`)
VALUES

(1, 'John', 'Doe', 'john.doe@example.com', 'johndoe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0821234567', 'both', 1, 'verified', NULL, NULL, NULL, '2026-05-03 09:56:17'),

(2, 'Jane', 'Smith', 'jane.smith@email.com', 'janesmith', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0832345678', 'buyer', 1, 'verified', NULL, NULL, NULL, '2026-05-03 09:58:34'),

(3, 'Michael', 'Brown', 'michael.b@webmail.co.za', 'mikeb', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0843456789', 'seller', 0, 'pending', NULL, NULL, NULL, '2026-05-03 10:00:39'),

(4, 'Sarah', 'Johnson', 'sarah.j@fashionmail.com', 'sarahj', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0824567890', 'both', 1, 'verified', NULL, NULL, NULL, '2026-05-03 10:02:09'),

(5, 'David', 'Williams', 'david.w@clothingstore.co.za', 'davidw', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '0835678901', 'buyer', 1, 'verified', NULL, NULL, NULL, '2026-05-03 10:03:37');

-- --------------------------------------------------------

--
-- Table structure for table `tblclothes`
--

DROP TABLE IF EXISTS `tblclothes`;

CREATE TABLE IF NOT EXISTS `tblclothes` (
  `clothes_id` int(11) NOT NULL AUTO_INCREMENT,
  `seller_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `category` varchar(50) DEFAULT NULL,
  `size` varchar(10) DEFAULT NULL,
  `clothesCondition` enum('new','like new','good','fair') DEFAULT 'good',
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('active','sold','flagged') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`clothes_id`),
  KEY `seller_id` (`seller_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tblclothes`
--

INSERT INTO `tblclothes`
(`clothes_id`, `seller_id`, `title`, `description`, `price`, `category`, `size`, `clothesCondition`, `image_path`, `status`, `created_at`)
VALUES

(1, 2, 'Vintage Levis 501 Jeans', 'Classic 90s fit denim jeans, excellent condition', '350.00', 'Pants', '32', 'good', 'uploads/levis501.jpg', 'active', '2026-05-03 10:18:25'),

(2, 2, 'Zara Cream Blazer', 'Elegant blazer for formal occasions', '250.00', 'Jackets', 'M', 'like new', 'uploads/zara_blazer.jpg', 'active', '2026-05-03 10:19:39'),

(3, 5, 'Nike Air Max Sneakers', 'Original Nike sneakers, worn twice', '450.00', 'Shoes', '42', 'like new', 'uploads/nike_airmax.jpg', 'active', '2026-05-03 10:21:10'),

(4, 4, 'H&M Summer Dress', 'Floral print summer dress, size small', '120.00', 'Dresses', 'S', 'good', 'uploads/hm_dress.jpg', 'active', '2026-05-03 10:22:10'),

(5, 6, 'Cotton On Hoodie', 'Comfortable everyday hoodie', '180.00', 'Tops', 'L', 'good', 'uploads/cottonon_hoodie.jpg', 'active', '2026-05-03 10:23:01');

-- --------------------------------------------------------

--
-- Table structure for table `tblorder`
--

DROP TABLE IF EXISTS `tblorder`;

CREATE TABLE IF NOT EXISTS `tblorder` (
  `order_id` int(11) NOT NULL AUTO_INCREMENT,
  `buyer_id` int(11) NOT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_cost` decimal(10,2) DEFAULT 0.00,
  `shipping_address` text NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `payment_status` enum('pending','paid','failed') DEFAULT 'pending',
  `order_status` enum('pending','confirmed','shipped','delivered','cancelled') DEFAULT 'pending',
  `tracking_number` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`order_id`),
  KEY `buyer_id` (`buyer_id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4;

--
-- Dumping data for table `tblorder`
--

INSERT INTO `tblorder`
(`order_id`, `buyer_id`, `order_date`, `total_amount`, `shipping_cost`, `shipping_address`, `payment_method`, `payment_status`, `order_status`, `tracking_number`)
VALUES

(1, 1, '2026-05-03 10:45:23', '470.00', '60.00', '12 Main Street, Cape Town 8001', 'Credit Card', 'paid', 'delivered', 'ZA123456789'),

(2, 1, '2026-05-03 10:47:43', '470.00', '60.00', '12 Main Street, Cape Town 8001', 'EFT', 'paid', 'shipped', 'ZA987654321'),

(3, 4, '2026-05-03 10:48:35', '180.00', '60.00', '45 Oak Avenue, Johannesburg 2001', 'Credit Card', 'paid', 'confirmed', 'ZA456789123'),

(4, 4, '2026-05-03 10:49:43', '650.00', '60.00', '45 Oak Avenue, Johannesburg 2001', 'Credit Card', 'pending', 'pending', NULL),

(5, 2, '2026-05-03 10:51:07', '300.00', '60.00', '78 Beach Road, Durban 4001', 'EFT', 'paid', 'delivered', 'ZA789123456');

-- --------------------------------------------------------

--
-- Foreign Key Constraints
--

ALTER TABLE `tbluser`
ADD CONSTRAINT `tbluser_ibfk_1`
FOREIGN KEY (`verified_by`)
REFERENCES `tbladmin` (`admin_id`)
ON DELETE SET NULL;

COMMIT;
