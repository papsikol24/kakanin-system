-- ===================================================
-- KAKANIN SYSTEM - COMPLETE UPDATED DATABASE
-- Generated: 2026-03-10
-- Database: kakanin_db
-- Tables: 23
-- INCLUDES: All latest features + Online Customers + 15 Products + Admin + Cashier + Manager
-- UPDATED: tbl_orders now supports all 8 statuses (pending, confirmed, preparing, ready, out_for_delivery, delivered, completed, cancelled)
-- ===================================================

SET FOREIGN_KEY_CHECKS = 0;
SET NAMES utf8mb4;

-- ===================================================
-- TABLE: tbl_users (Staff Accounts)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','manager','cashier') NOT NULL,
  `status` tinyint(1) DEFAULT 1,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `last_store_action` datetime DEFAULT NULL,
  `last_store_action_type` enum('open','close') DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_customers (Customer Accounts)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `status` tinyint(1) DEFAULT 1,
  `phone` varchar(20) DEFAULT NULL,
  `security_question` varchar(255) DEFAULT NULL,
  `security_answer` varchar(255) DEFAULT NULL,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_products (Product Inventory) - 15 PRODUCTS
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `stock` int(11) NOT NULL DEFAULT 0,
  `low_stock_threshold` int(11) DEFAULT 10,
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_product_name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_orders (Customer Orders) - UPDATED with all 8 statuses
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `service_fee` decimal(10,2) NOT NULL DEFAULT 20.00,
  `payment_method` enum('cash','gcash','paymaya') NOT NULL,
  `gcash_ref` varchar(20) DEFAULT NULL,
  `gcash_screenshot` varchar(255) DEFAULT NULL,
  `paymaya_ref` varchar(20) DEFAULT NULL,
  `paymaya_screenshot` varchar(255) DEFAULT NULL,
  `delivery_address` text DEFAULT NULL,
  `delivery_phone` varchar(20) DEFAULT NULL,
  `status` enum('pending','confirmed','preparing','ready','out_for_delivery','delivered','completed','cancelled') NOT NULL DEFAULT 'pending',
  `cancellation_reason` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `confirmed_at` datetime DEFAULT NULL,
  `preparation_started_at` datetime DEFAULT NULL,
  `preparation_completed_at` datetime DEFAULT NULL,
  `ready_for_pickup_at` datetime DEFAULT NULL,
  `out_for_delivery_at` datetime DEFAULT NULL,
  `delivered_at` datetime DEFAULT NULL,
  `tracking_number` varchar(50) DEFAULT NULL,
  `actual_preparation_time` int(11) DEFAULT NULL,
  `last_status_update` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `created_by` (`created_by`),
  KEY `completed_by` (`completed_by`),
  CONSTRAINT `tbl_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_orders_ibfk_3` FOREIGN KEY (`completed_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_order_items (Order Line Items)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_order_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `tbl_order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `tbl_orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_inventory_logs (Stock Audit Trail)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_inventory_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `change_type` enum('add','subtract','set') NOT NULL,
  `quantity_changed` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `log_time` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `product_id` (`product_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `tbl_inventory_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`id`),
  CONSTRAINT `tbl_inventory_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_notifications (Customer Notifications)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `order_id` int(11) DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` enum('order_update','general') DEFAULT 'order_update',
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `tbl_notifications_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_notification_seen (Real-time tracking)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_notification_seen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `last_seen_id` int(11) NOT NULL DEFAULT 0,
  `device_id` varchar(255) DEFAULT NULL,
  `last_seen_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_device` (`user_id`,`device_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_staff_notifications (Staff Notifications)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_staff_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `tbl_staff_notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `tbl_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_deleted_orders (Track Customer Deletions)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_deleted_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notified` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_carts (Shopping Cart Persistence)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_carts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_cart` (`customer_id`,`product_id`),
  KEY `customer_id` (`customer_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `tbl_carts_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tbl_carts_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_login_attempts (Security - Rate Limiting)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_active_sessions (Real-time Cashier Tracking)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_active_sessions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `tab_id` varchar(255) DEFAULT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `last_activity` (`last_activity`),
  CONSTRAINT `tbl_active_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_store_status (Store Online/Offline Control)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_store_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_online` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `offline_message` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_online_customers (Real-time Customer Tracking)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_online_customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `session_id` varchar(255) NOT NULL,
  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `current_page` varchar(255) DEFAULT NULL,
  `cart_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_customer_session` (`customer_id`,`session_id`),
  KEY `customer_id` (`customer_id`),
  KEY `last_activity` (`last_activity`),
  CONSTRAINT `tbl_online_customers_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- TABLE: tbl_preparation_settings (Food Preparation Times)
-- ===================================================
CREATE TABLE IF NOT EXISTS `tbl_preparation_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` enum('budget','regular','premium','custom') NOT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `preparation_time` int(11) NOT NULL DEFAULT 30,
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- DAILY RESET TABLES
-- ===================================================

-- Table for daily order archives
CREATE TABLE IF NOT EXISTS `tbl_daily_orders_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_order_id` int(11) NOT NULL,
  `daily_order_number` int(11) NOT NULL,
  `order_date` datetime NOT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `payment_method` varchar(50) NOT NULL,
  `status` varchar(50) NOT NULL,
  `items_count` int(11) DEFAULT 0,
  `archive_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `archive_date` (`archive_date`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for daily inventory logs archive
CREATE TABLE IF NOT EXISTS `tbl_daily_inventory_archive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `original_log_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `change_type` varchar(50) NOT NULL,
  `quantity_changed` int(11) NOT NULL,
  `previous_stock` int(11) NOT NULL,
  `new_stock` int(11) NOT NULL,
  `user_name` varchar(100) DEFAULT NULL,
  `log_time` datetime NOT NULL,
  `archive_date` date NOT NULL,
  PRIMARY KEY (`id`),
  KEY `archive_date` (`archive_date`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table for daily sales summary
CREATE TABLE IF NOT EXISTS `tbl_daily_sales_summary` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_date` date NOT NULL,
  `total_orders` int(11) DEFAULT 0,
  `total_sales` decimal(10,2) DEFAULT 0.00,
  `cash_sales` decimal(10,2) DEFAULT 0.00,
  `gcash_sales` decimal(10,2) DEFAULT 0.00,
  `paymaya_sales` decimal(10,2) DEFAULT 0.00,
  `completed_orders` int(11) DEFAULT 0,
  `pending_orders` int(11) DEFAULT 0,
  `cancelled_orders` int(11) DEFAULT 0,
  `total_items_sold` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_sale_date` (`sale_date`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Table to track daily counters
CREATE TABLE IF NOT EXISTS `tbl_daily_counters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `counter_date` date NOT NULL,
  `order_counter` int(11) DEFAULT 1,
  `inventory_log_counter` int(11) DEFAULT 1,
  `last_reset_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_counter_date` (`counter_date`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- ===================================================
-- INSERT USERS WITH HASHED PASSWORDS
-- Password: staff-2026 for admin
-- Password: cashier-2026 for cashier
-- Password: manager-2026 for manager
-- ===================================================

-- Admin user with password: staff-2026
INSERT IGNORE INTO `tbl_users` (`id`, `username`, `password`, `role`, `status`, `created_at`) VALUES 
(1, 'admin', '$2y$10$ER3DiBS/hJD2xhTLWmTE1uLtG6hHJt1Kx0McT1PG5s5xloxvEvItW', 'admin', 1, NOW());

-- Cashier user with password: cashier-2026
INSERT IGNORE INTO `tbl_users` (`id`, `username`, `password`, `role`, `status`, `created_at`) VALUES 
(2, 'Cashier', '$2y$10$Sq652hsdQYFMqB2n0V1WWO30g69uNFgSyIwWTCwMjvwvC7KM0jgle', 'cashier', 1, NOW());

-- Manager user with password: manager-2026
INSERT IGNORE INTO `tbl_users` (`id`, `username`, `password`, `role`, `status`, `created_at`) VALUES 
(3, 'manager', '$2y$10$rSeMsTd8.hPEfth3hgptJeLvD/QRmtwiEvJF7B31aHJbjBOtmDhs.', 'manager', 1, NOW());

-- ===================================================
-- INSERT 15 PRODUCTS (with 0 stock initially)
-- ===================================================
INSERT IGNORE INTO `tbl_products` (`id`, `name`, `description`, `price`, `stock`, `low_stock_threshold`, `image`, `created_at`, `updated_at`) VALUES 
(1, 'Puto', 'Soft and fluffy rice cake, perfect for breakfast or merienda', 5.00, 0, 10, 'puto.jpg', NOW(), NOW()),
(2, 'Kutsinta', 'Brown rice cake topped with grated coconut, chewy and sweet', 8.00, 0, 10, 'kutsinta.jpg', NOW(), NOW()),
(3, 'Bibingka', 'Baked rice cake with salted egg and cheese, topped with butter', 15.00, 0, 10, 'bibingka.jpg', NOW(), NOW()),
(4, 'Biko', 'Sweet sticky rice cake with coconut milk and brown sugar latik', 20.00, 0, 10, 'biko.jpg', NOW(), NOW()),
(5, 'Cassava Cake', 'Dense and moist cassava based cake with custard topping', 25.00, 0, 10, 'cassava-cake.jpg', NOW(), NOW()),
(6, 'Ube Halaya', 'Rich and creamy purple yam dessert, perfect for any occasion', 30.00, 0, 10, 'ube-halaya.jpg', NOW(), NOW()),
(7, 'Maja Blanca', 'Coconut pudding with corn, smooth and creamy', 12.00, 0, 10, 'maja.jpg', NOW(), NOW()),
(8, 'Sapin-sapin', 'Layered glutinous rice dessert with ube and coconut', 22.00, 0, 10, 'sapin-sapin.jpg', NOW(), NOW()),
(9, 'Palitaw', 'Sweet rice cakes coated with coconut and sesame seeds', 15.00, 0, 10, 'palitaw.jpg', NOW(), NOW()),
(10, 'Special Puto Kutsinta', 'Assorted kakanin in bilao, perfect for parties', 250.00, 0, 5, 'bilao.jpg', NOW(), NOW()),
(11, 'Suman', 'Sticky rice wrapped in banana leaves, served with sugar', 12.00, 0, 10, 'suman.jpg', NOW(), NOW()),
(12, 'Special Biko', 'Premium biko bilao with extra latik and toppings', 250.00, 0, 10, 'biko-bilao.jpg', NOW(), NOW()),
(13, 'Special Palitaw', 'Premium palitaw bilao with extra coconut and sesame', 250.00, 0, 10, '699d19773c8b5_1771903351.jpg', NOW(), NOW()),
(14, 'Special Black Kutsinta', 'Premium black kutsinta bilao with premium ingredients', 250.00, 0, 10, 'black-kutsinta.jpg', NOW(), NOW()),
(15, 'Special Suman Latik', 'Premium suman latik bilao with caramelized coconut', 250.00, 0, 10, 'suman-latik.jpg', NOW(), NOW());

-- ===================================================
-- INSERT PREPARATION SETTINGS (Food Preparation Times)
-- ===================================================
INSERT IGNORE INTO `tbl_preparation_settings` (`category`, `item_name`, `preparation_time`, `description`, `is_default`) VALUES
('budget', 'Budget Items (< ₱10)', 2, 'Small items like puto, kutsinta', 1),
('regular', 'Regular Items (₱10 - ₱249)', 20, 'Standard items like bibingka, biko', 1),
('premium', 'Premium Items (₱250+)', 30, 'Large bilao sets, special orders', 1);

-- ===================================================
-- INSERT STORE STATUS (default closed)
-- ===================================================
INSERT IGNORE INTO `tbl_store_status` (`id`, `is_online`, `offline_message`, `updated_at`) VALUES 
(1, 0, 'Store is currently closed. Please check back during business hours.', NOW());

-- ===================================================
-- INSERT TODAY'S COUNTER
-- ===================================================
INSERT IGNORE INTO `tbl_daily_counters` (`counter_date`, `order_counter`, `inventory_log_counter`, `last_reset_at`) 
VALUES (CURDATE(), 1, 1, NOW());

-- ===================================================
-- RESET AUTO_INCREMENT VALUES
-- ===================================================
ALTER TABLE `tbl_users` AUTO_INCREMENT = 4;
ALTER TABLE `tbl_products` AUTO_INCREMENT = 16;
ALTER TABLE `tbl_store_status` AUTO_INCREMENT = 2;
ALTER TABLE `tbl_preparation_settings` AUTO_INCREMENT = 4;

-- ===================================================
-- ADD OPTIMAL INDEXES FOR PERFORMANCE (with duplicate checks)
-- ===================================================

-- First, drop any existing indexes that might cause duplicates
DROP INDEX IF EXISTS `idx_order_date_status` ON `tbl_orders`;
DROP INDEX IF EXISTS `idx_created_completed` ON `tbl_orders`;
DROP INDEX IF EXISTS `idx_tracking` ON `tbl_orders`;
DROP INDEX IF EXISTS `idx_preparation` ON `tbl_orders`;
DROP INDEX IF EXISTS `idx_product_time` ON `tbl_inventory_logs`;
DROP INDEX IF EXISTS `idx_customer_read` ON `tbl_notifications`;
DROP INDEX IF EXISTS `idx_order` ON `tbl_notifications`;
DROP INDEX IF EXISTS `idx_cleanup` ON `tbl_active_sessions`;
DROP INDEX IF EXISTS `idx_user_tab` ON `tbl_active_sessions`;
DROP INDEX IF EXISTS `idx_cleanup` ON `tbl_online_customers`;
DROP INDEX IF EXISTS `idx_customer_activity` ON `tbl_online_customers`;
DROP INDEX IF EXISTS `idx_original_order` ON `tbl_daily_orders_archive`;
DROP INDEX IF EXISTS `idx_archive_search` ON `tbl_daily_orders_archive`;

-- Now add all indexes fresh
ALTER TABLE `tbl_orders` ADD INDEX `idx_order_date_status` (`order_date`, `status`);
ALTER TABLE `tbl_orders` ADD INDEX `idx_created_completed` (`created_by`, `completed_by`);
ALTER TABLE `tbl_orders` ADD INDEX `idx_tracking` (`tracking_number`);
ALTER TABLE `tbl_orders` ADD INDEX `idx_preparation` (`preparation_started_at`, `preparation_completed_at`);

ALTER TABLE `tbl_inventory_logs` ADD INDEX `idx_product_time` (`product_id`, `log_time`);

ALTER TABLE `tbl_notifications` ADD INDEX `idx_customer_read` (`customer_id`, `is_read`);
ALTER TABLE `tbl_notifications` ADD INDEX `idx_order` (`order_id`);

ALTER TABLE `tbl_active_sessions` ADD INDEX `idx_cleanup` (`last_activity`);
ALTER TABLE `tbl_active_sessions` ADD INDEX `idx_user_tab` (`user_id`, `tab_id`);

ALTER TABLE `tbl_online_customers` ADD INDEX `idx_cleanup` (`last_activity`);
ALTER TABLE `tbl_online_customers` ADD INDEX `idx_customer_activity` (`customer_id`, `last_activity`);

ALTER TABLE `tbl_daily_orders_archive` ADD INDEX `idx_original_order` (`original_order_id`);
ALTER TABLE `tbl_daily_orders_archive` ADD INDEX `idx_archive_search` (`archive_date`, `daily_order_number`);

-- ===================================================
-- ENABLE FOREIGN KEY CHECKS
-- ===================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ===================================================
-- DATABASE IS READY
-- Total Tables: 23
-- Products: 15
-- Users: 3 (Admin, Cashier, Manager)
-- Default Passwords:
--   admin: staff-2026
--   Cashier: cashier-2026  
--   manager: manager-2026
-- Order Statuses: pending, confirmed, preparing, ready, out_for_delivery, delivered, completed, cancelled
-- Preparation Times:
--   Budget: 2 minutes
--   Regular: 20 minutes  
--   Premium: 30 minutes
-- ===================================================