-- Innovative Features Database Schema
-- This file adds tables for verification documents and messaging system

-- Table for verification documents (multi-factor verification)
DROP TABLE IF EXISTS `tblverification_documents`;

CREATE TABLE IF NOT EXISTS `tblverification_documents` (
  `document_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `document_type` enum('id_document','proof_of_address','bank_statement','business_registration','other') NOT NULL,
  `document_path` varchar(255) NOT NULL,
  `document_name` varchar(255) NOT NULL,
  `upload_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `status` enum('pending','approved','rejected') DEFAULT 'pending',
  `reviewed_by` int(11) DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `rejection_reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`document_id`),
  KEY `user_id` (`user_id`),
  KEY `reviewed_by` (`reviewed_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for messaging system
DROP TABLE IF EXISTS `tblmessages`;

CREATE TABLE IF NOT EXISTS `tblmessages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `receiver_id` int(11) NOT NULL,
  `item_id` int(11) DEFAULT NULL,
  `message_text` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`message_id`),
  KEY `sender_id` (`sender_id`),
  KEY `receiver_id` (`receiver_id`),
  KEY `item_id` (`item_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for order items (to link orders with clothes)
DROP TABLE IF EXISTS `tblorder_items`;

CREATE TABLE IF NOT EXISTS `tblorder_items` (
  `order_item_id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `clothes_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `price_at_purchase` decimal(10,2) NOT NULL,
  PRIMARY KEY (`order_item_id`),
  KEY `order_id` (`order_id`),
  KEY `clothes_id` (`clothes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table for shopping cart
DROP TABLE IF EXISTS `tblcart`;

CREATE TABLE IF NOT EXISTS `tblcart` (
  `cart_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `clothes_id` int(11) NOT NULL,
  `quantity` int(11) DEFAULT 1,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`cart_id`),
  UNIQUE KEY `unique_cart_item` (`user_id`, `clothes_id`),
  KEY `user_id` (`user_id`),
  KEY `clothes_id` (`clothes_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign key constraints
ALTER TABLE `tblverification_documents`
ADD CONSTRAINT `fk_verification_user`
FOREIGN KEY (`user_id`)
REFERENCES `tbluser` (`user_id`)
ON DELETE CASCADE;

ALTER TABLE `tblverification_documents`
ADD CONSTRAINT `fk_verification_admin`
FOREIGN KEY (`reviewed_by`)
REFERENCES `tbladmin` (`admin_id`)
ON DELETE SET NULL;

ALTER TABLE `tblmessages`
ADD CONSTRAINT `fk_message_sender`
FOREIGN KEY (`sender_id`)
REFERENCES `tbluser` (`user_id`)
ON DELETE CASCADE;

ALTER TABLE `tblmessages`
ADD CONSTRAINT `fk_message_receiver`
FOREIGN KEY (`receiver_id`)
REFERENCES `tbluser` (`user_id`)
ON DELETE CASCADE;

ALTER TABLE `tblmessages`
ADD CONSTRAINT `fk_message_item`
FOREIGN KEY (`item_id`)
REFERENCES `tblclothes` (`clothes_id`)
ON DELETE SET NULL;

ALTER TABLE `tblorder_items`
ADD CONSTRAINT `fk_order_item_order`
FOREIGN KEY (`order_id`)
REFERENCES `tblorder` (`order_id`)
ON DELETE CASCADE;

ALTER TABLE `tblorder_items`
ADD CONSTRAINT `fk_order_item_clothes`
FOREIGN KEY (`clothes_id`)
REFERENCES `tblclothes` (`clothes_id`)
ON DELETE CASCADE;

ALTER TABLE `tblcart`
ADD CONSTRAINT `fk_cart_user`
FOREIGN KEY (`user_id`)
REFERENCES `tbluser` (`user_id`)
ON DELETE CASCADE;

ALTER TABLE `tblcart`
ADD CONSTRAINT `fk_cart_clothes`
FOREIGN KEY (`clothes_id`)
REFERENCES `tblclothes` (`clothes_id`)
ON DELETE CASCADE;
