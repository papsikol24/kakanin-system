-- ===================================================
-- KAKANIN SYSTEM - COMPLETE DATABASE BACKUP
-- Generated: 2026-03-12 15:31:59
-- Database: if0_41233935_kakanin_db
-- Tables: 20
-- ===================================================

SET FOREIGN_KEY_CHECKS = 0;

SET NAMES utf8mb4;


-- -------------------------------------------------
-- Table structure for table `tbl_active_sessions`
-- -------------------------------------------------
CREATE TABLE `tbl_active_sessions` (
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
  KEY `idx_cleanup` (`last_activity`),
  KEY `idx_user_tab` (`user_id`,`tab_id`),
  CONSTRAINT `tbl_active_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2944 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_active_sessions` - 16 records
INSERT INTO `tbl_active_sessions` (`id`, `user_id`, `session_id`, `tab_id`, `last_activity`, `ip_address`, `user_agent`) VALUES 
('2928', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:18', '122.3.133.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2929', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:20', '103.226.24.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2930', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:21', '122.3.133.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2931', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:22', '122.3.133.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2932', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:25', '122.3.133.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2933', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:26', '122.3.133.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2934', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:28', '122.3.133.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2935', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:29', '103.226.24.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2936', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:31', '103.226.24.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2937', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:34', '103.226.24.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2938', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:39', '103.226.24.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2939', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:43', '103.226.24.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2940', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:45', '103.226.24.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2941', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:50', '103.226.24.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2942', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:53', '122.3.133.254', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36'),
('2943', '1', '0f7009b5c4429553dc989ab8f1613c9a', 'tab_69b26ba6655fc9.00731242', '2026-03-12 15:31:56', '103.226.24.60', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/146.0.0.0 Safari/537.36');


-- -------------------------------------------------
-- Table structure for table `tbl_carts`
-- -------------------------------------------------
CREATE TABLE `tbl_carts` (
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- -------------------------------------------------
-- Table structure for table `tbl_customers`
-- -------------------------------------------------
CREATE TABLE `tbl_customers` (
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_customers` - 2 records
INSERT INTO `tbl_customers` (`id`, `name`, `email`, `username`, `password`, `status`, `phone`, `security_question`, `security_answer`, `reset_token`, `reset_expires`, `created_at`) VALUES 
('1', 'Piolo Niño Salaño', 'salanopiolonino@gmail.com', 'Piolo', '$2y$10$.yYsrDGavdL.nl5IRhisqu40UqdDhCTYyg7X0ri.elqNj0RBpx1W2', '1', '+639356062163', 'What was the name of your first pet?', '$2y$10$1Gu5Sd.tNtRQlvJ6RbdEV.jpTvq7R0MJBi2x3WvEpZswCNioMmma6', NULL, NULL, '2026-03-10 08:27:19'),
('2', 'Asislorentz', 'asislorentz@gmail.com', 'Asislorentz', '$2y$10$qhkzYVN22gRKXTB0TtxwXOuSCKM1qbioFDb.6qUKn0vzmrrcH5Yee', '1', '+639923312816', 'What is your favorite book?', '$2y$10$8/s56E7tC6gybsI4x.3fgei.1x9g7gckvyZ.fhH6HBcnFE7kvhpr.', NULL, NULL, '2026-03-10 10:32:38');


-- -------------------------------------------------
-- Table structure for table `tbl_daily_counters`
-- -------------------------------------------------
CREATE TABLE `tbl_daily_counters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `counter_date` date NOT NULL,
  `order_counter` int(11) DEFAULT 1,
  `inventory_log_counter` int(11) DEFAULT 1,
  `last_reset_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_counter_date` (`counter_date`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_daily_counters` - 3 records
INSERT INTO `tbl_daily_counters` (`id`, `counter_date`, `order_counter`, `inventory_log_counter`, `last_reset_at`) VALUES 
('1', '2026-03-09', '1', '1', '2026-03-10 08:26:50'),
('2', '2026-03-10', '10', '1', '2026-03-10 11:59:19'),
('3', '2026-03-12', '1', '1', '2026-03-12 10:17:02');


-- -------------------------------------------------
-- Table structure for table `tbl_daily_inventory_archive`
-- -------------------------------------------------
CREATE TABLE `tbl_daily_inventory_archive` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- -------------------------------------------------
-- Table structure for table `tbl_daily_orders_archive`
-- -------------------------------------------------
CREATE TABLE `tbl_daily_orders_archive` (
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
  KEY `archive_date` (`archive_date`),
  KEY `idx_original_order` (`original_order_id`),
  KEY `idx_archive_search` (`archive_date`,`daily_order_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- -------------------------------------------------
-- Table structure for table `tbl_daily_sales_summary`
-- -------------------------------------------------
CREATE TABLE `tbl_daily_sales_summary` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- -------------------------------------------------
-- Table structure for table `tbl_deleted_orders`
-- -------------------------------------------------
CREATE TABLE `tbl_deleted_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `deleted_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `notified` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- -------------------------------------------------
-- Table structure for table `tbl_inventory_logs`
-- -------------------------------------------------
CREATE TABLE `tbl_inventory_logs` (
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
  KEY `idx_product_time` (`product_id`,`log_time`),
  CONSTRAINT `tbl_inventory_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`id`),
  CONSTRAINT `tbl_inventory_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_inventory_logs` - 38 records
INSERT INTO `tbl_inventory_logs` (`id`, `product_id`, `user_id`, `change_type`, `quantity_changed`, `previous_stock`, `new_stock`, `log_time`) VALUES 
('1', '1', '1', 'add', '300', '0', '300', '2026-03-10 08:28:01'),
('2', '1', NULL, 'subtract', '19', '299', '280', '2026-03-10 08:28:19'),
('3', '1', NULL, 'subtract', '20', '280', '260', '2026-03-10 08:28:27'),
('4', '1', NULL, 'subtract', '19', '259', '240', '2026-03-10 08:37:57'),
('5', '1', NULL, 'subtract', '20', '240', '220', '2026-03-10 08:38:09'),
('6', '1', NULL, 'subtract', '19', '219', '200', '2026-03-10 08:45:12'),
('7', '1', NULL, 'subtract', '20', '200', '180', '2026-03-10 08:45:21'),
('8', '1', NULL, 'subtract', '19', '179', '160', '2026-03-10 08:55:24'),
('9', '1', NULL, 'subtract', '20', '160', '140', '2026-03-10 08:55:40'),
('10', '1', NULL, 'subtract', '19', '139', '120', '2026-03-10 08:57:46'),
('11', '1', NULL, 'subtract', '20', '120', '100', '2026-03-10 08:57:53'),
('12', '1', NULL, 'subtract', '19', '99', '80', '2026-03-10 09:03:31'),
('13', '1', NULL, 'subtract', '20', '80', '60', '2026-03-10 09:03:39'),
('14', '1', NULL, 'subtract', '19', '59', '40', '2026-03-10 09:07:34'),
('15', '1', NULL, 'subtract', '20', '40', '20', '2026-03-10 09:07:47'),
('16', '1', NULL, 'subtract', '19', '19', '0', '2026-03-10 10:27:41'),
('17', '1', '1', 'add', '500', '0', '500', '2026-03-10 10:28:26'),
('18', '1', NULL, 'subtract', '20', '500', '480', '2026-03-10 10:28:33'),
('19', '1', NULL, 'subtract', '17', '477', '460', '2026-03-10 10:39:10'),
('20', '1', NULL, 'subtract', '20', '460', '440', '2026-03-10 10:39:20'),
('21', '3', '1', 'add', '300', '0', '300', '2026-03-10 10:52:55'),
('22', '4', '1', 'add', '300', '0', '300', '2026-03-10 10:53:01'),
('23', '5', '1', 'add', '300', '0', '300', '2026-03-10 10:53:05'),
('24', '2', '1', 'add', '300', '0', '300', '2026-03-10 10:53:10'),
('25', '7', '1', 'add', '300', '0', '300', '2026-03-10 10:53:14'),
('26', '9', '1', 'add', '300', '0', '300', '2026-03-10 10:53:18'),
('27', '8', '1', 'add', '300', '0', '300', '2026-03-10 10:53:26'),
('28', '12', '1', 'add', '300', '0', '300', '2026-03-10 10:53:31'),
('29', '14', '1', 'add', '300', '0', '300', '2026-03-10 10:53:36'),
('30', '13', '1', 'add', '300', '0', '300', '2026-03-10 10:53:42'),
('31', '10', '1', 'add', '300', '0', '300', '2026-03-10 10:53:47'),
('32', '15', '1', 'add', '300', '0', '300', '2026-03-10 10:53:52'),
('33', '11', '1', 'add', '300', '0', '300', '2026-03-10 10:53:57'),
('34', '6', '1', 'add', '300', '0', '300', '2026-03-10 10:54:02'),
('35', '1', NULL, 'subtract', '19', '439', '420', '2026-03-10 11:59:12'),
('36', '1', NULL, 'subtract', '20', '420', '400', '2026-03-10 11:59:19'),
('37', '1', NULL, 'subtract', '19', '399', '380', '2026-03-12 10:16:46'),
('38', '1', NULL, 'subtract', '20', '380', '360', '2026-03-12 10:17:02');


-- -------------------------------------------------
-- Table structure for table `tbl_login_attempts`
-- -------------------------------------------------
CREATE TABLE `tbl_login_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `last_attempt` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip_address` (`ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- -------------------------------------------------
-- Table structure for table `tbl_notification_seen`
-- -------------------------------------------------
CREATE TABLE `tbl_notification_seen` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `notification_type` varchar(50) NOT NULL,
  `last_seen_id` int(11) NOT NULL DEFAULT 0,
  `device_id` varchar(255) DEFAULT NULL,
  `last_seen_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_device` (`user_id`,`device_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=77 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_notification_seen` - 29 records
INSERT INTO `tbl_notification_seen` (`id`, `user_id`, `notification_type`, `last_seen_id`, `device_id`, `last_seen_at`) VALUES 
('1', '1', 'staff_order', '7', 'staff_gvsr1knrlg7p0bk06ek1ri', '2026-03-10 09:12:49'),
('2', '1', 'staff_order', '0', '32edbc0fb0828616b269c3d904e9edc9', '2026-03-10 08:28:28'),
('3', '1', 'customer_notification', '32', 'customer_blyoa5xf3icimpx8vxjmxh', '2026-03-10 09:08:00'),
('26', '2', 'staff_order', '7', 'staff_gvsr1knrlg7p0bk06ek1ri', '2026-03-10 09:07:55'),
('27', '2', 'staff_order', '0', '32edbc0fb0828616b269c3d904e9edc9', '2026-03-10 09:03:12'),
('46', '1', 'customer_notification', '36', 'customer_qsjn5vmk858tur5e5z58u', '2026-03-10 10:00:25'),
('47', '1', 'customer_notification', '58', 'customer_lc3crc8kf68xjl0ttblzu', '2026-03-10 12:02:50'),
('48', '1', 'staff_order', '7', 'staff_ntnhtiryfnagzd156xwk8b', '2026-03-10 10:12:07'),
('49', '1', 'staff_order', '0', '2946be9e959b6cd85fd10256b31f1ec4', '2026-03-10 10:06:32'),
('50', '2', 'staff_order', '9', 'staff_bj1dhcukmvr1f72eirm2b', '2026-03-10 11:39:48'),
('51', '1', 'staff_order', '9', 'staff_bj1dhcukmvr1f72eirm2b', '2026-03-10 10:54:10'),
('55', '1', 'staff_order', '7', 'staff_zda5n5u22dg2a5woxd75jd', '2026-03-10 10:28:15'),
('56', '2', 'customer_notification', '48', 'customer_tjmwlcn7apeahcs89t2se4', '2026-03-10 20:12:31'),
('57', '2', 'customer_notification', '0', '05b63a1300f51d2b844f19cf79f58324', '2026-03-10 10:42:42'),
('59', '2', 'customer_notification', '48', 'customer_p1cgis3z0z9w9m2yohp9q', '2026-03-10 10:50:33'),
('60', '1', 'staff_order', '10', 'staff_6gmb9hypct8vdbj09at1gk', '2026-03-10 12:03:24'),
('61', '2', 'staff_order', '9', 'staff_6gmb9hypct8vdbj09at1gk', '2026-03-10 11:56:13'),
('62', '2', 'staff_order', '10', 'staff_itttwxjuob8i459axznrbr', '2026-03-10 12:03:06'),
('66', '2', 'staff_order', '10', 'staff_ntnhtiryfnagzd156xwk8b', '2026-03-10 12:05:55'),
('67', '2', 'staff_order', '0', '2946be9e959b6cd85fd10256b31f1ec4', '2026-03-10 12:05:51'),
('68', '1', 'staff_order', '11', 'staff_nf1jyhpky29qkmdpnigwz', '2026-03-12 12:24:34'),
('69', '1', 'staff_order', '0', '2a9bada585816ff8924e4985784c04ec', '2026-03-12 12:24:12'),
('70', '2', 'customer_notification', '59', 'customer_33xhm4sdnt2ydo847ndgbg', '2026-03-12 13:58:54'),
('71', '2', 'customer_notification', '0', '0e6d9234a1b6d52a32d97ffc7e8c5779', '2026-03-12 13:22:47'),
('72', '2', 'customer_notification', '0', '9176f212d4a15ea22f9694f5128dbde3', '2026-03-12 13:22:57'),
('73', '1', 'staff_order', '11', 'staff_mq7rfdbg478oegnoin9lm', '2026-03-12 14:04:43'),
('74', '1', 'staff_order', '11', 'staff_r1kq6n6f8q8xch147r8r2', '2026-03-12 15:31:29'),
('75', '1', 'staff_order', '0', '91a9a287ca363f94096173af55b6554a', '2026-03-12 15:31:21'),
('76', '1', 'staff_order', '0', '8271eb39972df23bcd2c081476b43cea', '2026-03-12 15:31:31');


-- -------------------------------------------------
-- Table structure for table `tbl_notifications`
-- -------------------------------------------------
CREATE TABLE `tbl_notifications` (
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
  KEY `idx_customer_read` (`customer_id`,`is_read`),
  KEY `idx_order` (`order_id`),
  CONSTRAINT `tbl_notifications_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=60 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_notifications` - 53 records
INSERT INTO `tbl_notifications` (`id`, `customer_id`, `order_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES 
('1', '1', '1', 'Order Placed', 'Your order #1 (Daily #ORD-2026-03-10-0001) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-10 08:28:27'),
('2', '1', '1', 'Order #1 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-10 08:29:15'),
('3', '1', '1', 'Order #1 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-10 08:29:27'),
('4', '1', '1', 'Order #1 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-10 08:30:32'),
('5', '1', '1', 'Order #1 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260310-000001', 'order_update', '0', '2026-03-10 08:37:22'),
('6', '1', '1', 'Order #1 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-10 08:37:38'),
('7', '1', '2', 'Order Placed', 'Your order #2 (Daily #ORD-2026-03-10-0002) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-10 08:38:09'),
('8', '1', '2', 'Order #2 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-10 08:38:24'),
('9', '1', '2', 'Order #2 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-10 08:38:44'),
('10', '1', '2', 'Order #2 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-10 08:40:08'),
('11', '1', '2', 'Order #2 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260310-000002', 'order_update', '0', '2026-03-10 08:40:22'),
('12', '1', '2', 'Order #2 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-10 08:40:32'),
('13', '1', '3', 'Order Placed', 'Your order #3 (Daily #ORD-2026-03-10-0003) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-10 08:45:21'),
('14', '1', '3', 'Order #3 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-10 08:45:42'),
('15', '1', '3', 'Order #3 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-10 08:46:02'),
('16', '1', '3', 'Order #3 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-10 08:47:12'),
('17', '1', '3', 'Order #3 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260310-000003', 'order_update', '0', '2026-03-10 08:47:24'),
('18', '1', '3', 'Order #3 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-10 08:55:05'),
('19', '1', '4', 'Order Placed', 'Your order #4 (Daily #ORD-2026-03-10-0004) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-10 08:55:40'),
('20', '1', '4', 'Order #4 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-10 08:55:57'),
('21', '1', '4', 'Order #4 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-10 08:56:05'),
('22', '1', '4', 'Order #4 Cancelled', 'Your order #4 has been cancelled by staff.', 'order_update', '0', '2026-03-10 08:57:02'),
('23', '1', '5', 'Order Placed', 'Your order #5 (Daily #ORD-2026-03-10-0005) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-10 08:57:53'),
('24', '1', '5', 'Order #5 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-10 08:58:12'),
('25', '1', '5', 'Order #5 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-10 08:58:22'),
('26', '1', '5', 'Order #5 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-10 08:58:38'),
('27', '1', '5', 'Order #5 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260310-000005', 'order_update', '0', '2026-03-10 08:58:45'),
('28', '1', '5', 'Order #5 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-10 08:58:54'),
('29', '1', '6', 'Order Placed', 'Your order #6 (Daily #ORD-2026-03-10-0006) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-10 09:03:39'),
('30', '1', '6', 'Order #6 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-10 09:03:58'),
('31', '1', '6', 'Order #6 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-10 09:04:05'),
('32', '1', '7', 'Order Placed', 'Your order #7 (Daily #ORD-2026-03-10-0007) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-10 09:07:47'),
('33', '1', '7', 'Order #7 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-10 09:08:04'),
('34', '1', '7', 'Order #7 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-10 09:08:11'),
('35', '1', '7', 'Order #7 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-10 09:09:22'),
('36', '1', '7', 'Order #7 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260310-000007', 'order_update', '0', '2026-03-10 09:09:32'),
('37', '1', '8', 'Order Placed', 'Your order #8 (Daily #ORD-2026-03-10-0008) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-10 10:28:33'),
('38', '1', '8', 'Order #8 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-10 10:28:52'),
('39', '1', '8', 'Order #8 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-10 10:29:02'),
('40', '1', '8', 'Order #8 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-10 10:30:11'),
('41', '1', '8', 'Order #8 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260310-000008', 'order_update', '0', '2026-03-10 10:30:23'),
('42', '1', '8', 'Order #8 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-10 10:30:34'),
('49', '1', '6', 'Order #6 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-10 10:47:25'),
('50', '1', '6', 'Order #6 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260310-000006', 'order_update', '0', '2026-03-10 10:47:34'),
('51', '1', '6', 'Order #6 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-10 10:47:44'),
('52', '1', '7', 'Order #7 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-10 11:39:52'),
('53', '1', '10', 'Order Placed', 'Your order #10 (Daily #ORD-2026-03-10-0010) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-10 11:59:19'),
('54', '1', '10', 'Order #10 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-10 11:59:39'),
('55', '1', '10', 'Order #10 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-10 11:59:48'),
('56', '1', '10', 'Order #10 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-10 12:00:56'),
('57', '1', '10', 'Order #10 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260310-000010', 'order_update', '0', '2026-03-10 12:01:06'),
('58', '1', '10', 'Order #10 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-10 12:01:17'),
('59', '2', '11', 'Order Placed', 'Your order #11 (Daily #ORD-2026-03-12-0001) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-12 10:17:02');


-- -------------------------------------------------
-- Table structure for table `tbl_online_customers`
-- -------------------------------------------------
CREATE TABLE `tbl_online_customers` (
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
  KEY `idx_cleanup` (`last_activity`),
  KEY `idx_customer_activity` (`customer_id`,`last_activity`),
  CONSTRAINT `tbl_online_customers_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=295 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


-- -------------------------------------------------
-- Table structure for table `tbl_order_items`
-- -------------------------------------------------
CREATE TABLE `tbl_order_items` (
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_order_items` - 11 records
INSERT INTO `tbl_order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES 
('1', '1', '1', '20', '5.00'),
('2', '2', '1', '20', '5.00'),
('3', '3', '1', '20', '5.00'),
('4', '4', '1', '20', '5.00'),
('5', '5', '1', '20', '5.00'),
('6', '6', '1', '20', '5.00'),
('7', '7', '1', '20', '5.00'),
('8', '8', '1', '20', '5.00'),
('9', '9', '1', '20', '5.00'),
('10', '10', '1', '20', '5.00'),
('11', '11', '1', '20', '5.00');


-- -------------------------------------------------
-- Table structure for table `tbl_orders`
-- -------------------------------------------------
CREATE TABLE `tbl_orders` (
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
  KEY `idx_order_date_status` (`order_date`,`status`),
  KEY `idx_created_completed` (`created_by`,`completed_by`),
  KEY `idx_tracking` (`tracking_number`),
  KEY `idx_preparation` (`preparation_started_at`,`preparation_completed_at`),
  CONSTRAINT `tbl_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_orders_ibfk_3` FOREIGN KEY (`completed_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_orders` - 11 records
INSERT INTO `tbl_orders` (`id`, `customer_id`, `customer_name`, `order_date`, `total_amount`, `service_fee`, `payment_method`, `gcash_ref`, `gcash_screenshot`, `paymaya_ref`, `paymaya_screenshot`, `delivery_address`, `delivery_phone`, `status`, `cancellation_reason`, `created_by`, `completed_by`, `confirmed_at`, `preparation_started_at`, `preparation_completed_at`, `ready_for_pickup_at`, `out_for_delivery_at`, `delivered_at`, `tracking_number`, `actual_preparation_time`, `last_status_update`) VALUES 
('1', '1', 'Piolo Niño Salaño', '2026-03-10 08:28:27', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 18, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-10 08:29:15', '2026-03-10 08:29:27', '2026-03-10 08:30:32', NULL, '2026-03-10 08:37:22', '2026-03-10 08:37:38', 'TRK-20260310-000001', '1', '2026-03-10 08:37:38'),
('2', '1', 'Piolo Niño Salaño', '2026-03-10 08:38:09', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 19, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-10 08:38:24', '2026-03-10 08:38:44', '2026-03-10 08:40:08', NULL, '2026-03-10 08:40:22', '2026-03-10 08:40:32', 'TRK-20260310-000002', '1', '2026-03-10 08:40:32'),
('3', '1', 'Piolo Niño Salaño', '2026-03-10 08:45:21', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 20, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-10 08:45:42', '2026-03-10 08:46:02', '2026-03-10 08:47:12', NULL, '2026-03-10 08:47:24', '2026-03-10 08:55:05', 'TRK-20260310-000003', '1', '2026-03-10 08:55:05'),
('4', '1', 'Piolo Niño Salaño', '2026-03-10 08:55:40', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 20, Tacloban City', '9356062163', 'cancelled', NULL, NULL, '1', '2026-03-10 08:55:57', '2026-03-10 08:56:05', NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-10 08:57:02'),
('5', '1', 'Piolo Niño Salaño', '2026-03-10 08:57:53', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 19, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-10 08:58:12', '2026-03-10 08:58:22', '2026-03-10 08:58:38', NULL, '2026-03-10 08:58:45', '2026-03-10 08:58:54', 'TRK-20260310-000005', '0', '2026-03-10 08:58:54'),
('6', '1', 'Piolo Niño Salaño', '2026-03-10 09:03:39', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 20, Tacloban City', '9356062163', 'completed', NULL, NULL, '2', '2026-03-10 09:03:58', '2026-03-10 09:04:05', '2026-03-10 10:47:25', NULL, '2026-03-10 10:47:34', '2026-03-10 10:47:44', 'TRK-20260310-000006', '103', '2026-03-10 10:47:44'),
('7', '1', 'Piolo Niño Salaño', '2026-03-10 09:07:47', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 19, Tacloban City', '9356062163', 'completed', NULL, NULL, '2', '2026-03-10 09:08:04', '2026-03-10 09:08:11', '2026-03-10 09:09:22', NULL, '2026-03-10 09:09:32', '2026-03-10 11:39:52', 'TRK-20260310-000007', '1', '2026-03-10 11:39:52'),
('8', '1', 'Piolo Niño Salaño', '2026-03-10 10:28:33', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 6-A, Tacloban City', '9356062163', 'completed', NULL, NULL, '2', '2026-03-10 10:28:52', '2026-03-10 10:29:02', '2026-03-10 10:30:11', NULL, '2026-03-10 10:30:23', '2026-03-10 10:30:34', 'TRK-20260310-000008', '1', '2026-03-10 10:30:34'),
('9', '2', 'Asislorentz', '2026-03-10 10:39:20', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 8-A, near Hh, Tacloban City', '9923312816', 'completed', NULL, NULL, '2', '2026-03-10 10:41:01', '2026-03-10 10:41:20', '2026-03-10 10:43:04', NULL, '2026-03-10 10:43:18', '2026-03-10 10:43:27', 'TRK-20260310-000009', '2', '2026-03-10 10:43:27'),
('10', '1', 'Piolo Niño Salaño', '2026-03-10 11:59:19', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 6-A, Tacloban City', '9356062163', 'completed', NULL, NULL, '2', '2026-03-10 11:59:39', '2026-03-10 11:59:48', '2026-03-10 12:00:56', NULL, '2026-03-10 12:01:06', '2026-03-10 12:01:17', 'TRK-20260310-000010', '1', '2026-03-10 12:01:17'),
('11', '2', 'Asislorentz', '2026-03-12 10:17:02', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 102 (Old Kawayan), near BhHajaj, Tacloban City', '9923312816', 'pending', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2026-03-12 10:17:02');


-- -------------------------------------------------
-- Table structure for table `tbl_preparation_settings`
-- -------------------------------------------------
CREATE TABLE `tbl_preparation_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` enum('budget','regular','premium','custom') NOT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `preparation_time` int(11) NOT NULL DEFAULT 30,
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_preparation_settings` - 3 records
INSERT INTO `tbl_preparation_settings` (`id`, `category`, `item_name`, `preparation_time`, `description`, `is_default`, `created_at`) VALUES 
('7', 'budget', 'Budget Items (< ?10)', '1', 'Small items like puto, kutsinta', '1', '2026-03-10 08:26:50'),
('8', 'regular', 'Regular Items (?10 - ?249)', '20', 'Standard items like bibingka, biko', '1', '2026-03-10 08:26:50'),
('9', 'premium', 'Premium Items (?250+)', '30', 'Large bilao sets, special orders', '1', '2026-03-10 08:26:50');


-- -------------------------------------------------
-- Table structure for table `tbl_products`
-- -------------------------------------------------
CREATE TABLE `tbl_products` (
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

-- Dumping data for table `tbl_products` - 15 records
INSERT INTO `tbl_products` (`id`, `name`, `description`, `price`, `stock`, `low_stock_threshold`, `image`, `created_at`, `updated_at`) VALUES 
('1', 'Puto', 'Soft and fluffy rice cake, perfect for breakfast or merienda', '5.00', '360', '10', 'puto.jpg', '2026-03-10 08:26:50', '2026-03-12 10:17:02'),
('2', 'Kutsinta', 'Brown rice cake topped with grated coconut, chewy and sweet', '8.00', '300', '10', 'kutsinta.jpg', '2026-03-10 08:26:50', '2026-03-10 10:53:10'),
('3', 'Bibingka', 'Baked rice cake with salted egg and cheese, topped with butter', '15.00', '300', '10', 'bibingka.jpg', '2026-03-10 08:26:50', '2026-03-10 10:52:55'),
('4', 'Biko', 'Sweet sticky rice cake with coconut milk and brown sugar latik', '20.00', '300', '10', 'biko.jpg', '2026-03-10 08:26:50', '2026-03-10 10:53:01'),
('5', 'Cassava Cake', 'Dense and moist cassava based cake with custard topping', '25.00', '300', '10', 'cassava-cake.jpg', '2026-03-10 08:26:50', '2026-03-10 10:53:05'),
('6', 'Ube Halaya', 'Rich and creamy purple yam dessert, perfect for any occasion', '30.00', '300', '10', 'ube-halaya.jpg', '2026-03-10 08:26:50', '2026-03-10 10:54:02'),
('7', 'Maja Blanca', 'Coconut pudding with corn, smooth and creamy', '12.00', '300', '10', 'maja.jpg', '2026-03-10 08:26:50', '2026-03-10 10:53:14'),
('8', 'Sapin-sapin', 'Layered glutinous rice dessert with ube and coconut', '22.00', '300', '10', 'sapin-sapin.jpg', '2026-03-10 08:26:50', '2026-03-10 10:53:26'),
('9', 'Palitaw', 'Sweet rice cakes coated with coconut and sesame seeds', '15.00', '300', '10', 'palitaw.jpg', '2026-03-10 08:26:50', '2026-03-10 10:53:18'),
('10', 'Special Puto Kutsinta', 'Assorted kakanin in bilao, perfect for parties', '250.00', '300', '5', 'bilao.jpg', '2026-03-10 08:26:50', '2026-03-10 10:53:47'),
('11', 'Suman', 'Sticky rice wrapped in banana leaves, served with sugar', '12.00', '300', '10', 'suman.jpg', '2026-03-10 08:26:50', '2026-03-10 10:53:57'),
('12', 'Special Biko', 'Premium biko bilao with extra latik and toppings', '250.00', '300', '10', 'biko-bilao.jpg', '2026-03-10 08:26:50', '2026-03-10 10:53:31'),
('13', 'Special Palitaw', 'Premium palitaw bilao with extra coconut and sesame', '250.00', '300', '10', '699d19773c8b5_1771903351.jpg', '2026-03-10 08:26:50', '2026-03-10 10:53:42'),
('14', 'Special Black Kutsinta', 'Premium black kutsinta bilao with premium ingredients', '250.00', '300', '10', 'black-kutsinta.jpg', '2026-03-10 08:26:50', '2026-03-10 10:53:36'),
('15', 'Special Suman Latik', 'Premium suman latik bilao with caramelized coconut', '250.00', '300', '10', 'suman-latik.jpg', '2026-03-10 08:26:50', '2026-03-10 10:53:52');


-- -------------------------------------------------
-- Table structure for table `tbl_staff_notifications`
-- -------------------------------------------------
CREATE TABLE `tbl_staff_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `order_id` int(11) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `order_id` (`order_id`),
  CONSTRAINT `tbl_staff_notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `tbl_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_staff_notifications` - 11 records
INSERT INTO `tbl_staff_notifications` (`id`, `order_id`, `is_read`, `created_at`) VALUES 
('1', '1', '0', '2026-03-10 08:28:27'),
('2', '2', '0', '2026-03-10 08:38:09'),
('3', '3', '0', '2026-03-10 08:45:21'),
('4', '4', '0', '2026-03-10 08:55:40'),
('5', '5', '0', '2026-03-10 08:57:53'),
('6', '6', '0', '2026-03-10 09:03:39'),
('7', '7', '0', '2026-03-10 09:07:47'),
('8', '8', '0', '2026-03-10 10:28:33'),
('9', '9', '0', '2026-03-10 10:39:20'),
('10', '10', '0', '2026-03-10 11:59:19'),
('11', '11', '0', '2026-03-12 10:17:02');


-- -------------------------------------------------
-- Table structure for table `tbl_store_status`
-- -------------------------------------------------
CREATE TABLE `tbl_store_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `is_online` tinyint(1) DEFAULT 0,
  `updated_by` int(11) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `offline_message` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_store_status` - 1 records
INSERT INTO `tbl_store_status` (`id`, `is_online`, `updated_by`, `updated_at`, `offline_message`) VALUES 
('1', '0', '1', '2026-03-12 12:24:21', '');


-- -------------------------------------------------
-- Table structure for table `tbl_users`
-- -------------------------------------------------
CREATE TABLE `tbl_users` (
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

-- Dumping data for table `tbl_users` - 3 records
INSERT INTO `tbl_users` (`id`, `username`, `password`, `role`, `status`, `reset_token`, `reset_expires`, `last_store_action`, `last_store_action_type`, `created_at`) VALUES 
('1', 'admin', '$2y$10$ER3DiBS/hJD2xhTLWmTE1uLtG6hHJt1Kx0McT1PG5s5xloxvEvItW', 'admin', '1', NULL, NULL, '2026-03-12 12:24:21', 'close', '2026-03-10 08:26:50'),
('2', 'Cashier', '$2y$10$Sq652hsdQYFMqB2n0V1WWO30g69uNFgSyIwWTCwMjvwvC7KM0jgle', 'cashier', '1', NULL, NULL, NULL, NULL, '2026-03-10 08:26:50'),
('3', 'manager', '$2y$10$rSeMsTd8.hPEfth3hgptJeLvD/QRmtwiEvJF7B31aHJbjBOtmDhs.', 'manager', '1', NULL, NULL, NULL, NULL, '2026-03-10 08:26:50');

SET FOREIGN_KEY_CHECKS = 1;

-- ===================================================
-- BACKUP COMPLETED
-- Total Tables: 20
-- Total Records: 196
-- ===================================================
