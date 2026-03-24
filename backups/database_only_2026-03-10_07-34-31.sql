-- ===================================================
-- KAKANIN SYSTEM - COMPLETE DATABASE BACKUP
-- Generated: 2026-03-10 07:34:31
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
  KEY `tab_id` (`tab_id`),
  KEY `idx_session` (`session_id`),
  KEY `idx_cleanup` (`last_activity`),
  CONSTRAINT `tbl_active_sessions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=9403 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_active_sessions` - 264 records
INSERT INTO `tbl_active_sessions` (`id`, `user_id`, `session_id`, `tab_id`, `last_activity`, `ip_address`, `user_agent`) VALUES 
('9139', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:29', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9140', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:31', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9141', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:32', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9142', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:33', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9143', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:33', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9144', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:34', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9145', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:34', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9146', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:34', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9147', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:36', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9148', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:37', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9149', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9150', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9151', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9152', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9153', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9154', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9155', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9156', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9157', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9158', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9159', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9160', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9161', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9162', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9163', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9164', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9165', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9166', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:39', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9167', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9168', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9169', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9170', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9171', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9172', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9173', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9174', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9175', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9176', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9177', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9178', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9179', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9180', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9181', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9182', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9183', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9184', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9185', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9186', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:40', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9187', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9188', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9189', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9190', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9191', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9192', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9193', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9194', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9195', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9196', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9197', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9198', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9199', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9200', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9201', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9202', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9203', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9204', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9205', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:41', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9206', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9207', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9208', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9209', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9210', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9211', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9212', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9213', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9214', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9215', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9216', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9217', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9218', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9219', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9220', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9221', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9222', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9223', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:42', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9224', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9225', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9226', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9227', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9228', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9229', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9230', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9231', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9232', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9233', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9234', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9235', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9236', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9237', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9238', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36');
INSERT INTO `tbl_active_sessions` (`id`, `user_id`, `session_id`, `tab_id`, `last_activity`, `ip_address`, `user_agent`) VALUES 
('9239', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9240', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:43', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9241', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:44', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9242', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:44', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9243', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:44', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9244', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:45', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9245', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9246', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9247', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9248', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9249', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9250', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9251', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9252', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9253', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9254', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9255', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9256', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9257', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9258', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:47', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9259', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9260', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9261', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9262', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9263', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9264', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9265', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9266', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9267', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9268', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9269', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9270', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9271', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9272', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9273', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9274', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9275', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9276', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9277', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9278', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:48', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9279', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9280', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9281', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9282', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9283', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9284', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9285', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9286', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9287', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9288', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9289', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9290', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9291', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9292', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9293', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9294', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9295', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9296', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9297', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:49', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9298', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9299', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9300', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9301', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9302', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9303', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9304', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9305', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9306', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9307', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9308', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9309', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9310', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9311', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9312', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9313', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9314', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9315', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9316', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:50', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9317', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9318', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9319', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9320', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9321', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9322', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9323', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9324', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9325', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9326', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9327', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9328', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9329', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9330', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9331', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9332', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9333', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9334', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:51', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9335', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9336', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9337', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9338', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36');
INSERT INTO `tbl_active_sessions` (`id`, `user_id`, `session_id`, `tab_id`, `last_activity`, `ip_address`, `user_agent`) VALUES 
('9339', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9340', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9341', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9342', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9343', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9344', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9345', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9346', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9347', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9348', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9349', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9350', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9351', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9352', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9353', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9354', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:52', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9355', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9356', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9357', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9358', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9359', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9360', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9361', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9362', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9363', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9364', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9365', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9366', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9367', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9368', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9369', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9370', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9371', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9372', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9373', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:53', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9374', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9375', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9376', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9377', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9378', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9379', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9380', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9381', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9382', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9383', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9384', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9385', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9386', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9387', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9388', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9389', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9390', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:54', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9391', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:55', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9392', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:55', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9393', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:55', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9394', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:33:57', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9395', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:34:00', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9396', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:34:03', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9397', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:34:08', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9398', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:34:13', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9399', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:34:18', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9400', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:34:23', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9401', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:34:25', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36'),
('9402', '1', 'b47f4c4231b2b1b4cd69b784d5062973', 'tab_69af58bb454773.81296912', '2026-03-10 07:34:31', '124.217.26.47', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36');


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
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_customers` - 1 records
INSERT INTO `tbl_customers` (`id`, `name`, `email`, `username`, `password`, `status`, `phone`, `security_question`, `security_answer`, `reset_token`, `reset_expires`, `created_at`) VALUES 
('1', 'Piolo Niño Salaño', 'salanopiolonino@gmail.com', 'Paps', '$2y$10$clXoOi9CEujIOhNuzN5x7eK0x.LgPO19Go8eFsTHKMNJg4XxP42j6', '1', '+639356062163', 'What was the name of your first pet?', '$2y$10$aKIUjte74XUspdkDKpAT1.bgIMMpmYfMVubsfbYg.fEutrMl/CMKm', NULL, NULL, '2026-03-08 23:38:47');


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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_daily_counters` - 2 records
INSERT INTO `tbl_daily_counters` (`id`, `counter_date`, `order_counter`, `inventory_log_counter`, `last_reset_at`) VALUES 
('1', '2026-03-08', '1', '1', '2026-03-08 23:35:09'),
('2', '2026-03-09', '30', '1', '2026-03-09 19:39:31');


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
  KEY `archive_date` (`archive_date`),
  KEY `product_id` (`product_id`)
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
  KEY `daily_order_number` (`daily_order_number`)
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
  KEY `order_id` (`order_id`),
  KEY `deleted_at` (`deleted_at`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_deleted_orders` - 1 records
INSERT INTO `tbl_deleted_orders` (`id`, `order_id`, `customer_id`, `customer_name`, `deleted_at`, `notified`) VALUES 
('1', '1', '1', 'Piolo Niño Salaño', '2026-03-09 08:50:34', '0');


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
  KEY `idx_log_time` (`log_time`),
  KEY `idx_product_time` (`product_id`,`log_time`),
  CONSTRAINT `tbl_inventory_logs_ibfk_1` FOREIGN KEY (`product_id`) REFERENCES `tbl_products` (`id`),
  CONSTRAINT `tbl_inventory_logs_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=81 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_inventory_logs` - 80 records
INSERT INTO `tbl_inventory_logs` (`id`, `product_id`, `user_id`, `change_type`, `quantity_changed`, `previous_stock`, `new_stock`, `log_time`) VALUES 
('1', '3', '3', 'add', '300', '0', '300', '2026-03-09 08:44:40'),
('2', '5', '3', 'add', '300', '0', '300', '2026-03-09 08:44:49'),
('3', '4', '3', 'add', '300', '0', '300', '2026-03-09 08:44:55'),
('4', '2', '3', 'add', '300', '0', '300', '2026-03-09 08:45:02'),
('5', '7', '3', 'add', '300', '0', '300', '2026-03-09 08:45:11'),
('6', '9', '3', 'add', '300', '0', '300', '2026-03-09 08:45:18'),
('7', '1', '3', 'add', '300', '0', '300', '2026-03-09 08:45:26'),
('8', '8', '3', 'add', '300', '0', '300', '2026-03-09 08:45:35'),
('9', '12', '3', 'add', '300', '0', '300', '2026-03-09 08:45:44'),
('10', '14', '3', 'add', '300', '0', '300', '2026-03-09 08:45:52'),
('11', '13', '3', 'add', '300', '0', '300', '2026-03-09 08:46:00'),
('12', '10', '3', 'add', '300', '0', '300', '2026-03-09 08:46:08'),
('13', '15', '3', 'add', '300', '0', '300', '2026-03-09 08:46:16'),
('14', '11', '3', 'add', '300', '0', '300', '2026-03-09 08:46:24'),
('15', '6', '3', 'add', '300', '0', '300', '2026-03-09 08:46:33'),
('16', '3', NULL, 'subtract', '29', '299', '270', '2026-03-09 08:47:49'),
('17', '3', NULL, 'subtract', '30', '270', '240', '2026-03-09 08:48:37'),
('18', '10', NULL, 'subtract', '1', '299', '298', '2026-03-09 08:48:37'),
('19', '12', NULL, 'subtract', '1', '299', '298', '2026-03-09 08:48:37'),
('20', '13', NULL, 'subtract', '1', '299', '298', '2026-03-09 08:48:37'),
('21', '14', NULL, 'subtract', '1', '299', '298', '2026-03-09 08:48:37'),
('22', '15', NULL, 'subtract', '1', '299', '298', '2026-03-09 08:48:37'),
('23', '6', NULL, 'subtract', '1', '299', '298', '2026-03-09 09:25:47'),
('24', '11', NULL, 'subtract', '1', '299', '298', '2026-03-09 09:25:47'),
('25', '13', NULL, 'subtract', '1', '297', '296', '2026-03-09 09:25:47'),
('26', '14', NULL, 'subtract', '1', '297', '296', '2026-03-09 09:25:47'),
('27', '6', NULL, 'subtract', '1', '297', '296', '2026-03-09 10:07:19'),
('28', '13', NULL, 'subtract', '1', '295', '294', '2026-03-09 10:07:19'),
('29', '14', NULL, 'subtract', '1', '295', '294', '2026-03-09 10:10:27'),
('30', '14', NULL, 'subtract', '1', '293', '292', '2026-03-09 10:17:56'),
('31', '14', NULL, 'subtract', '1', '291', '290', '2026-03-09 10:26:12'),
('32', '13', NULL, 'subtract', '1', '293', '292', '2026-03-09 10:36:19'),
('33', '13', NULL, 'subtract', '1', '291', '290', '2026-03-09 10:59:40'),
('34', '14', NULL, 'subtract', '1', '289', '288', '2026-03-09 11:03:44'),
('35', '3', NULL, 'subtract', '19', '239', '220', '2026-03-09 11:34:27'),
('36', '3', NULL, 'subtract', '20', '220', '200', '2026-03-09 11:34:39'),
('37', '1', NULL, 'subtract', '19', '299', '280', '2026-03-09 11:37:49'),
('38', '1', NULL, 'subtract', '20', '280', '260', '2026-03-09 11:38:02'),
('39', '1', NULL, 'subtract', '19', '259', '240', '2026-03-09 12:09:15'),
('40', '1', NULL, 'subtract', '20', '240', '220', '2026-03-09 12:09:24'),
('41', '1', NULL, 'subtract', '19', '219', '200', '2026-03-09 12:13:19'),
('42', '1', NULL, 'subtract', '20', '200', '180', '2026-03-09 12:14:21'),
('43', '1', NULL, 'subtract', '19', '179', '160', '2026-03-09 12:36:37'),
('44', '1', NULL, 'subtract', '20', '160', '140', '2026-03-09 12:36:47'),
('45', '1', NULL, 'subtract', '19', '139', '120', '2026-03-09 13:00:10'),
('46', '1', NULL, 'subtract', '20', '120', '100', '2026-03-09 13:00:31'),
('47', '1', NULL, 'subtract', '19', '99', '80', '2026-03-09 13:18:07'),
('48', '1', NULL, 'subtract', '20', '80', '60', '2026-03-09 13:18:16'),
('49', '1', NULL, 'subtract', '19', '59', '40', '2026-03-09 17:20:43'),
('50', '1', NULL, 'subtract', '20', '40', '20', '2026-03-09 17:20:59'),
('51', '14', NULL, 'add', '1', '287', '288', '2026-03-09 17:28:25'),
('52', '1', NULL, 'subtract', '19', '19', '0', '2026-03-09 17:28:37'),
('53', '1', NULL, 'add', '20', '0', '20', '2026-03-09 17:29:24'),
('54', '2', NULL, 'subtract', '19', '299', '280', '2026-03-09 17:29:29'),
('55', '2', NULL, 'subtract', '20', '280', '260', '2026-03-09 17:29:37'),
('56', '2', NULL, 'subtract', '19', '259', '240', '2026-03-09 17:55:11'),
('57', '2', NULL, 'subtract', '20', '240', '220', '2026-03-09 17:55:19'),
('58', '2', NULL, 'subtract', '19', '219', '200', '2026-03-09 17:58:04'),
('59', '2', NULL, 'subtract', '20', '200', '180', '2026-03-09 17:58:19'),
('60', '2', NULL, 'subtract', '19', '179', '160', '2026-03-09 18:10:45'),
('61', '2', NULL, 'subtract', '20', '160', '140', '2026-03-09 18:11:07'),
('62', '2', NULL, 'subtract', '19', '139', '120', '2026-03-09 18:16:52'),
('63', '2', NULL, 'subtract', '20', '120', '100', '2026-03-09 18:17:02'),
('64', '2', NULL, 'subtract', '19', '99', '80', '2026-03-09 18:27:06'),
('65', '2', NULL, 'subtract', '20', '80', '60', '2026-03-09 18:28:14'),
('66', '2', NULL, 'subtract', '19', '59', '40', '2026-03-09 18:36:38'),
('67', '2', NULL, 'subtract', '20', '40', '20', '2026-03-09 18:36:44'),
('68', '2', NULL, 'subtract', '19', '19', '0', '2026-03-09 18:45:11'),
('69', '2', '1', 'add', '1000', '0', '1000', '2026-03-09 18:45:58'),
('70', '2', NULL, 'subtract', '21', '999', '978', '2026-03-09 18:46:31'),
('71', '2', NULL, 'subtract', '19', '977', '958', '2026-03-09 18:49:35'),
('72', '2', NULL, 'subtract', '20', '958', '938', '2026-03-09 18:49:43'),
('73', '2', NULL, 'subtract', '19', '937', '918', '2026-03-09 19:06:01'),
('74', '2', NULL, 'subtract', '20', '918', '898', '2026-03-09 19:06:09'),
('75', '2', NULL, 'subtract', '18', '896', '878', '2026-03-09 19:19:37'),
('76', '2', NULL, 'subtract', '20', '878', '858', '2026-03-09 19:19:44'),
('77', '2', NULL, 'subtract', '19', '857', '838', '2026-03-09 19:29:24'),
('78', '2', NULL, 'subtract', '20', '838', '818', '2026-03-09 19:30:22'),
('79', '2', NULL, 'subtract', '19', '817', '798', '2026-03-09 19:39:14'),
('80', '2', NULL, 'subtract', '20', '798', '778', '2026-03-09 19:39:31');


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
  KEY `user_id` (`user_id`),
  KEY `idx_last_seen` (`last_seen_at`)
) ENGINE=InnoDB AUTO_INCREMENT=456 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_notification_seen` - 24 records
INSERT INTO `tbl_notification_seen` (`id`, `user_id`, `notification_type`, `last_seen_id`, `device_id`, `last_seen_at`) VALUES 
('1', '3', 'staff_order', '1', 'staff_dinz3ycob69xysjrdw8uxg', '2026-03-09 08:51:00'),
('2', '1', 'staff_order', '16', 'staff_yj3k6feohxhpwqkpt0uone', '2026-03-09 13:44:32'),
('3', '1', 'staff_order', '0', '32edbc0fb0828616b269c3d904e9edc9', '2026-03-09 08:48:38'),
('4', '2', 'staff_order', '1', 'staff_oqwowvlbqbmk0qz9flmeul', '2026-03-09 08:51:13'),
('5', '3', 'staff_order', '0', '32edbc0fb0828616b269c3d904e9edc9', '2026-03-09 08:48:38'),
('6', '2', 'staff_order', '0', '32edbc0fb0828616b269c3d904e9edc9', '2026-03-09 08:48:39'),
('7', '1', 'customer_notification', '2', 'customer_0bn26lig0qiixj60hnj63a', '2026-03-09 08:51:20'),
('8', '1', 'staff_order', '11', 'staff_q3j0d22n52cuw1cisa5twk', '2026-03-09 11:42:29'),
('9', '1', 'customer_notification', '62', 'customer_k8w86iksga91lzlytibx73', '2026-03-09 11:42:16'),
('21', '3', 'staff_order', '0', 'staff_ntnhtiryfnagzd156xwk8b', '2026-03-09 09:30:25'),
('22', '3', 'staff_order', '0', '2946be9e959b6cd85fd10256b31f1ec4', '2026-03-09 09:30:26'),
('24', '2', 'staff_order', '9', 'staff_q3j0d22n52cuw1cisa5twk', '2026-03-09 11:03:55'),
('42', '1', 'customer_notification', '62', 'customer_2fhmql3sixnyuzoaljj9i', '2026-03-09 12:08:19'),
('43', '1', 'staff_order', '12', 'staff_c7uak252l2v1ai08u6tgk5h', '2026-03-09 12:10:45'),
('164', '1', 'customer_notification', '63', 'customer_5p67ipvbt9t341d9diyagm', '2026-03-09 12:10:44'),
('341', '1', 'staff_order', '15', 'staff_xzyxnsa8dj8z3l686rhrnl', '2026-03-09 13:00:36'),
('342', '1', 'customer_notification', '94', 'customer_vd7lowojywq234mkektq', '2026-03-09 13:44:10'),
('373', '2', 'staff_order', '15', 'staff_x1j1hyvmfhdmm9y2iicdre', '2026-03-09 13:17:32'),
('378', '1', 'staff_order', '16', 'staff_x1j1hyvmfhdmm9y2iicdre', '2026-03-09 13:18:28'),
('385', '1', 'staff_order', '22', 'staff_mvwkw96laq8mhy8x28mb', '2026-03-09 18:17:14'),
('386', '1', 'customer_notification', '185', 'customer_swo5kbyu2prq2w5cy2zyb', '2026-03-09 19:39:38'),
('421', '1', 'staff_order', '28', 'staff_dinz3ycob69xysjrdw8uxg', '2026-03-09 19:19:57'),
('451', '1', 'staff_order', '30', 'staff_zda5n5u22dg2a5woxd75jd', '2026-03-09 19:39:41'),
('455', '1', 'staff_order', '30', 'staff_gvsr1knrlg7p0bk06ek1ri', '2026-03-10 07:33:55');


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
  KEY `idx_created_at` (`created_at`),
  KEY `idx_customer_read` (`customer_id`,`is_read`),
  CONSTRAINT `tbl_notifications_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=189 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_notifications` - 188 records
INSERT INTO `tbl_notifications` (`id`, `customer_id`, `order_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES 
('1', '1', '1', 'Order Placed', 'Your order #1 (Daily #ORD-2026-03-09-0001) has been placed successfully. Total pieces: 35. Service fee: ₱20.00', 'order_update', '1', '2026-03-09 08:48:37'),
('2', '1', '1', 'Order #1 Ready for Delivery', 'Your order #1 is now ready for delivery. Please prepare the exact amount: ₱1,720.00', 'order_update', '1', '2026-03-09 08:49:17'),
('3', '1', '2', 'Order Placed', 'Your order #2 (Daily #ORD-2026-03-09-0002) has been placed successfully. Total pieces: 4. Service fee: ₱20.00', 'order_update', '1', '2026-03-09 09:25:47'),
('4', '1', '2', 'Order #2 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '1', '2026-03-09 09:26:17'),
('5', '1', '2', 'Order #2 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '1', '2026-03-09 09:55:44'),
('6', '1', '2', 'Order #2 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '1', '2026-03-09 09:56:02'),
('7', '1', '2', 'Order #2 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '1', '2026-03-09 09:56:20'),
('8', '1', '2', 'Order #2 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '1', '2026-03-09 09:56:33'),
('9', '1', '2', 'Order #2 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000002', 'order_update', '1', '2026-03-09 09:56:42'),
('10', '1', '3', 'Order Placed', 'Your order #3 (Daily #ORD-2026-03-09-0003) has been placed successfully. Total pieces: 2. Service fee: ₱20.00', 'order_update', '1', '2026-03-09 10:07:19'),
('11', '1', '3', 'Order #3 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '1', '2026-03-09 10:07:46'),
('12', '1', '3', 'Order #3 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '1', '2026-03-09 10:08:01'),
('13', '1', '3', 'Order #3 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '1', '2026-03-09 10:08:12'),
('14', '1', '3', 'Order #3 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '1', '2026-03-09 10:08:42'),
('15', '1', '3', 'Order #3 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000003', 'order_update', '1', '2026-03-09 10:08:51'),
('16', '1', '3', 'Order #3 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '1', '2026-03-09 10:09:04'),
('17', '1', '2', 'Order #2 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 10:09:46'),
('18', '1', '4', 'Order Placed', 'Your order #4 (Daily #ORD-2026-03-09-0004) has been placed successfully. Total pieces: 1. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 10:10:27'),
('19', '1', '4', 'Order #4 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 10:15:48'),
('20', '1', '4', 'Order #4 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 10:16:15'),
('21', '1', '4', 'Order #4 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 10:16:34'),
('22', '1', '4', 'Order #4 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000004', 'order_update', '0', '2026-03-09 10:16:46'),
('23', '1', '4', 'Order #4 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 10:17:07'),
('24', '1', '5', 'Order Placed', 'Your order #5 (Daily #ORD-2026-03-09-0005) has been placed successfully. Total pieces: 1. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 10:17:56'),
('25', '1', '5', 'Order #5 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 10:22:38'),
('26', '1', '5', 'Order #5 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 10:22:56'),
('27', '1', '5', 'Order #5 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 10:23:44'),
('28', '1', '5', 'Order #5 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000005', 'order_update', '0', '2026-03-09 10:23:59'),
('29', '1', '5', 'Order #5 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 10:25:01'),
('30', '1', '5', 'Order #5 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 10:25:30'),
('31', '1', '6', 'Order Placed', 'Your order #6 (Daily #ORD-2026-03-09-0006) has been placed successfully. Total pieces: 1. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 10:26:12'),
('32', '1', '6', 'Order #6 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 10:26:52'),
('33', '1', '6', 'Order #6 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 10:27:04'),
('34', '1', '6', 'Order #6 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 10:27:24'),
('35', '1', '6', 'Order #6 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000006', 'order_update', '0', '2026-03-09 10:27:38'),
('36', '1', '6', 'Order #6 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 10:27:52'),
('37', '1', '7', 'Order Placed', 'Your order #7 (Daily #ORD-2026-03-09-0007) has been placed successfully. Total pieces: 1. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 10:36:19'),
('38', '1', '7', 'Order #7 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 10:36:52'),
('39', '1', '7', 'Order #7 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 10:37:48'),
('40', '1', '8', 'Order Placed', 'Your order #8 (Daily #ORD-2026-03-09-0008) has been placed successfully. Total pieces: 1. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 10:59:40'),
('41', '1', '8', 'Order #8 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 11:00:16'),
('42', '1', '8', 'Order #8 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 11:00:32'),
('43', '1', '8', 'Order #8 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 11:01:17'),
('44', '1', '8', 'Order #8 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 11:01:28'),
('45', '1', '9', 'Order Placed', 'Your order #9 (Daily #ORD-2026-03-09-0009) has been placed successfully. Total pieces: 1. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 11:03:44'),
('46', '1', '9', 'Order #9 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 11:04:18'),
('47', '1', '9', 'Order #9 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 11:04:43'),
('48', '1', '9', 'Order #9 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 11:32:13'),
('49', '1', '9', 'Order #9 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000009', 'order_update', '0', '2026-03-09 11:32:26'),
('50', '1', '9', 'Order #9 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 11:32:36'),
('51', '1', '9', 'Order #9 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 11:33:33'),
('52', '1', '9', 'Order #9 Cancelled', 'Your order #9 has been cancelled by staff.', 'order_update', '0', '2026-03-09 11:33:58'),
('53', '1', '10', 'Order Placed', 'Your order #10 (Daily #ORD-2026-03-09-0010) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 11:34:39'),
('54', '1', '10', 'Order #10 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 11:35:20'),
('55', '1', '10', 'Order #10 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 11:35:38'),
('56', '1', '10', 'Order #10 Cancelled', 'Your order #10 has been cancelled by staff.', 'order_update', '0', '2026-03-09 11:37:14'),
('57', '1', '11', 'Order Placed', 'Your order #11 (Daily #ORD-2026-03-09-0011) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 11:38:02'),
('58', '1', '11', 'Order #11 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 11:38:38'),
('59', '1', '11', 'Order #11 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 11:38:54'),
('60', '1', '11', 'Order #11 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 11:39:54'),
('61', '1', '11', 'Order #11 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000011', 'order_update', '0', '2026-03-09 11:40:17'),
('62', '1', '11', 'Order #11 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 11:40:35'),
('63', '1', '12', 'Order Placed', 'Your order #12 (Daily #ORD-2026-03-09-0012) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 12:09:24'),
('64', '1', '13', 'Order Placed', 'Your order #13 (Daily #ORD-2026-03-09-0013) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 12:14:21'),
('65', '1', '12', 'Order #12 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 12:14:59'),
('66', '1', '12', 'Order #12 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 12:15:17'),
('67', '1', '12', 'Order #12 Cancelled', 'Your order #12 has been cancelled by staff.', 'order_update', '0', '2026-03-09 12:36:04'),
('68', '1', '14', 'Order Placed', 'Your order #14 (Daily #ORD-2026-03-09-0014) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 12:36:47'),
('69', '1', '14', 'Order #14 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 12:37:11'),
('70', '1', '14', 'Order #14 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 12:37:27'),
('71', '1', '14', 'Order #14 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 12:43:36'),
('72', '1', '14', 'Order #14 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 12:43:52'),
('73', '1', '14', 'Order #14 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000014', 'order_update', '0', '2026-03-09 12:44:01'),
('74', '1', '14', 'Order #14 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 12:44:19'),
('75', '1', '13', 'Order #13 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 12:49:29'),
('76', '1', '13', 'Order #13 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 12:49:39'),
('77', '1', '13', 'Order #13 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 12:52:07'),
('78', '1', '13', 'Order #13 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000013', 'order_update', '0', '2026-03-09 12:52:25'),
('79', '1', '13', 'Order #13 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 12:52:41'),
('80', '1', '15', 'Order Placed', 'Your order #15 (Daily #ORD-2026-03-09-0015) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 13:00:31'),
('81', '1', '15', 'Order #15 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 13:00:57'),
('82', '1', '15', 'Order #15 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 13:01:16'),
('83', '1', '15', 'Order #15 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 13:04:01'),
('84', '1', '15', 'Order #15 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000015', 'order_update', '0', '2026-03-09 13:04:39'),
('85', '1', '15', 'Order #15 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 13:04:54'),
('86', '1', '15', 'Order #15 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000015', 'order_update', '0', '2026-03-09 13:06:59'),
('87', '1', '15', 'Order #15 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 13:07:11'),
('88', '1', '15', 'Order #15 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 13:07:25'),
('89', '1', '16', 'Order Placed', 'Your order #16 (Daily #ORD-2026-03-09-0016) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 13:18:16'),
('90', '1', '16', 'Order #16 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 13:18:36'),
('91', '1', '16', 'Order #16 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 13:18:50'),
('92', '1', '16', 'Order #16 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 13:21:26'),
('93', '1', '16', 'Order #16 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000016', 'order_update', '0', '2026-03-09 13:21:35'),
('94', '1', '16', 'Order #16 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 13:21:44'),
('95', '1', '17', 'Order Placed', 'Your order #17 (Daily #ORD-2026-03-09-0017) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 17:20:59'),
('96', '1', '17', 'Order #17 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 17:21:27'),
('97', '1', '17', 'Order #17 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 17:21:42'),
('98', '1', '17', 'Order #17 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000017', 'order_update', '0', '2026-03-09 17:22:33'),
('99', '1', '17', 'Order #17 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 17:22:46'),
('100', '1', '18', 'Order Placed', 'Your order #18 (Daily #ORD-2026-03-09-0018) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 17:29:37');
INSERT INTO `tbl_notifications` (`id`, `customer_id`, `order_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES 
('101', '1', '18', 'Order #18 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 17:29:57'),
('102', '1', '18', 'Order #18 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 17:30:10'),
('103', '1', '18', 'Order #18 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 17:31:17'),
('104', '1', '18', 'Order #18 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000018', 'order_update', '0', '2026-03-09 17:31:38'),
('105', '1', '18', 'Order #18 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 17:32:22'),
('106', '1', '18', 'Order #18 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 17:39:06'),
('107', '1', '18', 'Order #18 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000018', 'order_update', '0', '2026-03-09 17:40:28'),
('108', '1', '19', 'Order Placed', 'Your order #19 (Daily #ORD-2026-03-09-0019) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 17:55:19'),
('109', '1', '19', 'Order #19 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 17:55:41'),
('110', '1', '19', 'Order #19 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 17:56:15'),
('111', '1', '19', 'Order #19 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 17:57:09'),
('112', '1', '19', 'Order #19 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 17:57:23'),
('113', '1', '19', 'Order #19 Cancelled', 'Your order #19 has been cancelled by staff.', 'order_update', '0', '2026-03-09 17:57:33'),
('114', '1', '20', 'Order Placed', 'Your order #20 (Daily #ORD-2026-03-09-0020) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 17:58:19'),
('115', '1', '20', 'Order #20 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 17:58:52'),
('116', '1', '20', 'Order #20 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 17:59:16'),
('117', '1', '20', 'Order #20 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 17:59:44'),
('118', '1', '20', 'Order #20 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 18:00:19'),
('119', '1', '20', 'Order #20 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 18:00:37'),
('120', '1', '20', 'Order #20 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 18:00:46'),
('121', '1', '20', 'Order #20 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 18:01:08'),
('122', '1', '20', 'Order #20 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 18:02:11'),
('123', '1', '20', 'Order #20 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 18:02:53'),
('124', '1', '20', 'Order #20 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000020', 'order_update', '0', '2026-03-09 18:03:31'),
('125', '1', '20', 'Order #20 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000020', 'order_update', '0', '2026-03-09 18:03:55'),
('126', '1', '20', 'Order #20 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 18:04:20'),
('127', '1', '21', 'Order Placed', 'Your order #21 (Daily #ORD-2026-03-09-0021) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 18:11:07'),
('128', '1', '21', 'Order #21 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 18:11:26'),
('129', '1', '21', 'Order #21 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 18:11:38'),
('130', '1', '21', 'Order #21 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 18:11:49'),
('131', '1', '21', 'Order #21 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 18:12:15'),
('132', '1', '21', 'Order #21 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000021', 'order_update', '0', '2026-03-09 18:12:30'),
('133', '1', '21', 'Order #21 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 18:12:42'),
('134', '1', '22', 'Order Placed', 'Your order #22 (Daily #ORD-2026-03-09-0022) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 18:17:02'),
('135', '1', '22', 'Order #22 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 18:17:18'),
('136', '1', '22', 'Order #22 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 18:17:26'),
('137', '1', '22', 'Order #22 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 18:18:34'),
('138', '1', '22', 'Order #22 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000022', 'order_update', '0', '2026-03-09 18:18:47'),
('139', '1', '22', 'Order #22 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 18:19:01'),
('140', '1', '22', 'Order #22 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 18:19:21'),
('141', '1', '22', 'Order #22 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 18:19:40'),
('142', '1', '22', 'Order #22 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 18:26:53'),
('143', '1', '23', 'Order Placed', 'Your order #23 (Daily #ORD-2026-03-09-0023) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 18:28:14'),
('144', '1', '23', 'Order #23 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 18:28:30'),
('145', '1', '23', 'Order #23 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 18:28:40'),
('146', '1', '23', 'Order #23 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 18:29:50'),
('147', '1', '23', 'Order #23 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000023', 'order_update', '0', '2026-03-09 18:30:04'),
('148', '1', '23', 'Order #23 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 18:36:09'),
('149', '1', '24', 'Order Placed', 'Your order #24 (Daily #ORD-2026-03-09-0024) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 18:36:44'),
('150', '1', '24', 'Order #24 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 18:37:01'),
('151', '1', '24', 'Order #24 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 18:37:09'),
('152', '1', '24', 'Order #24 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 18:37:36'),
('153', '1', '24', 'Order #24 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000024', 'order_update', '0', '2026-03-09 18:37:48'),
('154', '1', '24', 'Order #24 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000024', 'order_update', '0', '2026-03-09 18:37:49'),
('155', '1', '24', 'Order #24 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 18:37:59'),
('156', '1', '25', 'Order Placed', 'Your order #25 (Daily #ORD-2026-03-09-0025) has been placed successfully. Total pieces: 21. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 18:46:31'),
('157', '1', '25', 'Order #25 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 18:46:48'),
('158', '1', '25', 'Order #25 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 18:46:59'),
('159', '1', '25', 'Order #25 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 18:47:34'),
('160', '1', '25', 'Order #25 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000025', 'order_update', '0', '2026-03-09 18:47:45'),
('161', '1', '25', 'Order #25 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 18:47:54'),
('162', '1', '26', 'Order Placed', 'Your order #26 (Daily #ORD-2026-03-09-0026) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 18:49:43'),
('163', '1', '26', 'Order #26 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 18:52:33'),
('164', '1', '26', 'Order #26 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 18:52:39'),
('165', '1', '26', 'Order #26 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 18:57:45'),
('166', '1', '26', 'Order #26 Cancelled', 'Your order #26 has been cancelled by staff.', 'order_update', '0', '2026-03-09 19:05:45'),
('167', '1', '27', 'Order Placed', 'Your order #27 (Daily #ORD-2026-03-09-0027) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 19:06:09'),
('168', '1', '27', 'Order #27 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 19:09:01'),
('169', '1', '27', 'Order #27 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 19:09:13'),
('170', '1', '27', 'Order #27 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 19:10:20'),
('171', '1', '27', 'Order #27 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000027', 'order_update', '0', '2026-03-09 19:10:52'),
('172', '1', '27', 'Order #27 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 19:11:07'),
('173', '1', '28', 'Order Placed', 'Your order #28 (Daily #ORD-2026-03-09-0028) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 19:19:44'),
('174', '1', '28', 'Order #28 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 19:20:01'),
('175', '1', '28', 'Order #28 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 19:20:16'),
('176', '1', '28', 'Order #28 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 19:21:28'),
('177', '1', '28', 'Order #28 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000028', 'order_update', '0', '2026-03-09 19:21:45'),
('178', '1', '28', 'Order #28 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 19:23:12'),
('179', '1', '29', 'Order Placed', 'Your order #29 (Daily #ORD-2026-03-09-0029) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 19:30:22'),
('180', '1', '29', 'Order #29 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 19:30:41'),
('181', '1', '29', 'Order #29 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 19:31:04'),
('182', '1', '29', 'Order #29 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 19:32:22'),
('183', '1', '29', 'Order #29 out for delivery', 'Your order is now out for delivery! Tracking #: TRK-20260309-000029', 'order_update', '0', '2026-03-09 19:38:46'),
('184', '1', '29', 'Order #29 Delivered', 'Your order has been delivered. Thank you for choosing Jen\'s Kakanin!', 'order_update', '0', '2026-03-09 19:38:54'),
('185', '1', '30', 'Order Placed', 'Your order #30 (Daily #ORD-2026-03-09-0030) has been placed successfully. Total pieces: 20. Service fee: ₱20.00', 'order_update', '0', '2026-03-09 19:39:31'),
('186', '1', '30', 'Order #30 Confirmed', 'Your order has been confirmed and will be prepared soon.', 'order_update', '0', '2026-03-09 19:39:47'),
('187', '1', '30', 'Order #30 is being prepared', 'Your order is now being prepared by our kitchen staff.', 'order_update', '0', '2026-03-09 19:39:59'),
('188', '1', '30', 'Order #30 ready for pickup', 'Your order is now ready for pickup. Please come to the store.', 'order_update', '0', '2026-03-09 19:41:50');


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
  CONSTRAINT `tbl_online_customers_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=368 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;


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
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_order_items` - 39 records
INSERT INTO `tbl_order_items` (`id`, `order_id`, `product_id`, `quantity`, `price`) VALUES 
('1', '1', '3', '30', '15.00'),
('2', '1', '10', '1', '250.00'),
('3', '1', '12', '1', '250.00'),
('4', '1', '13', '1', '250.00'),
('5', '1', '14', '1', '250.00'),
('6', '1', '15', '1', '250.00'),
('7', '2', '6', '1', '30.00'),
('8', '2', '11', '1', '12.00'),
('9', '2', '13', '1', '250.00'),
('10', '2', '14', '1', '250.00'),
('11', '3', '6', '1', '30.00'),
('12', '3', '13', '1', '250.00'),
('13', '4', '14', '1', '250.00'),
('14', '5', '14', '1', '250.00'),
('15', '6', '14', '1', '250.00'),
('16', '7', '13', '1', '250.00'),
('17', '8', '13', '1', '250.00'),
('18', '9', '14', '1', '250.00'),
('19', '10', '3', '20', '15.00'),
('20', '11', '1', '20', '5.00'),
('21', '12', '1', '20', '5.00'),
('22', '13', '1', '20', '5.00'),
('23', '14', '1', '20', '5.00'),
('24', '15', '1', '20', '5.00'),
('25', '16', '1', '20', '5.00'),
('26', '17', '1', '20', '5.00'),
('27', '18', '2', '20', '8.00'),
('28', '19', '2', '20', '8.00'),
('29', '20', '2', '20', '8.00'),
('30', '21', '2', '20', '8.00'),
('31', '22', '2', '20', '8.00'),
('32', '23', '2', '20', '8.00'),
('33', '24', '2', '20', '8.00'),
('34', '25', '2', '21', '8.00'),
('35', '26', '2', '20', '8.00'),
('36', '27', '2', '20', '8.00'),
('37', '28', '2', '20', '8.00'),
('38', '29', '2', '20', '8.00'),
('39', '30', '2', '20', '8.00');


-- -------------------------------------------------
-- Table structure for table `tbl_orders`
-- -------------------------------------------------
CREATE TABLE `tbl_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) DEFAULT NULL,
  `customer_name` varchar(100) DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  `total_amount` decimal(10,2) NOT NULL,
  `service_fee` decimal(10,2) NOT NULL DEFAULT 0.00,
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
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `last_status_update` timestamp NULL DEFAULT NULL,
  `estimated_preparation_time` int(11) DEFAULT NULL,
  `actual_preparation_time` int(11) DEFAULT NULL,
  `preparation_started_at` timestamp NULL DEFAULT NULL,
  `preparation_completed_at` timestamp NULL DEFAULT NULL,
  `ready_for_pickup_at` timestamp NULL DEFAULT NULL,
  `out_for_delivery_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `tracking_number` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_id` (`customer_id`),
  KEY `created_by` (`created_by`),
  KEY `completed_by` (`completed_by`),
  KEY `idx_order_date` (`order_date`),
  KEY `idx_status` (`status`),
  KEY `idx_order_date_status` (`order_date`,`status`),
  KEY `idx_created_completed` (`created_by`,`completed_by`),
  CONSTRAINT `tbl_orders_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `tbl_customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_orders_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tbl_orders_ibfk_3` FOREIGN KEY (`completed_by`) REFERENCES `tbl_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_orders` - 30 records
INSERT INTO `tbl_orders` (`id`, `customer_id`, `customer_name`, `order_date`, `total_amount`, `service_fee`, `payment_method`, `gcash_ref`, `gcash_screenshot`, `paymaya_ref`, `paymaya_screenshot`, `delivery_address`, `delivery_phone`, `status`, `cancellation_reason`, `created_by`, `completed_by`, `confirmed_at`, `last_status_update`, `estimated_preparation_time`, `actual_preparation_time`, `preparation_started_at`, `preparation_completed_at`, `ready_for_pickup_at`, `out_for_delivery_at`, `delivered_at`, `tracking_number`) VALUES 
('1', NULL, 'Piolo Niño Salaño', '2026-03-09 08:48:37', '1720.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 83-B, near montanyo, Tacloban City', '9356062163', 'completed', NULL, NULL, '2', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
('2', '1', 'Piolo Niño Salaño', '2026-03-09 09:25:47', '562.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 16, Tacloban City', '9356062163', 'completed', NULL, NULL, '2', '2026-03-09 09:55:44', '2026-03-09 10:09:46', NULL, '14', '2026-03-09 09:56:02', '2026-03-09 09:56:20', '2026-03-09 09:56:20', '2026-03-09 09:56:42', '2026-03-09 09:56:33', 'TRK-20260309-000002'),
('3', '1', 'Piolo Niño Salaño', '2026-03-09 10:07:19', '300.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 20, Tacloban City', '9356062163', 'completed', NULL, NULL, '2', '2026-03-09 10:07:46', '2026-03-09 10:09:04', NULL, '1', '2026-03-09 10:08:01', '2026-03-09 10:08:12', '2026-03-09 10:08:12', '2026-03-09 10:08:12', '2026-03-09 10:08:12', NULL),
('4', '1', 'Piolo Niño Salaño', '2026-03-09 10:10:27', '270.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 19, Tacloban City', '9356062163', 'completed', NULL, NULL, '2', '2026-03-09 10:15:48', '2026-03-09 10:17:07', NULL, '0', '2026-03-09 10:16:15', '2026-03-09 10:16:34', '2026-03-09 10:16:34', '2026-03-09 10:16:46', '2026-03-09 10:17:07', 'TRK-20260309-000004'),
('5', '1', 'Piolo Niño Salaño', '2026-03-09 10:17:56', '270.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 20, Tacloban City', '9356062163', 'completed', NULL, NULL, '2', '2026-03-09 10:22:38', '2026-03-09 10:25:30', NULL, '1', '2026-03-09 10:22:56', '2026-03-09 10:23:44', '2026-03-09 10:23:44', '2026-03-09 10:23:59', '2026-03-09 10:25:30', 'TRK-20260309-000005'),
('6', '1', 'Piolo Niño Salaño', '2026-03-09 10:26:12', '270.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 20, Tacloban City', '9356062163', 'completed', NULL, NULL, '2', '2026-03-09 10:26:52', '2026-03-09 10:27:52', NULL, '0', '2026-03-09 10:27:04', '2026-03-09 10:27:24', '2026-03-09 10:27:24', '2026-03-09 10:27:38', '2026-03-09 10:27:52', 'TRK-20260309-000006'),
('7', '1', 'Piolo Niño Salaño', '2026-03-09 10:36:19', '270.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 18, Tacloban City', '9356062163', 'completed', NULL, NULL, '2', '2026-03-09 10:36:52', '2026-03-09 10:37:48', NULL, NULL, '2026-03-09 10:37:48', '2026-03-09 10:37:48', '2026-03-09 10:37:48', '2026-03-09 10:37:48', '2026-03-09 10:37:48', NULL),
('8', '1', 'Piolo Niño Salaño', '2026-03-09 10:59:40', '270.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 18, Tacloban City', '9356062163', 'completed', NULL, NULL, '2', '2026-03-09 11:00:16', '2026-03-09 11:01:28', NULL, '1', '2026-03-09 11:00:32', '2026-03-09 11:01:17', '2026-03-09 11:01:17', '2026-03-09 11:01:28', '2026-03-09 11:01:28', NULL),
('9', '1', 'Piolo Niño Salaño', '2026-03-09 11:03:44', '270.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 19, Tacloban City', '9356062163', 'cancelled', NULL, NULL, '1', '2026-03-09 11:04:18', '2026-03-09 11:33:58', NULL, '28', '2026-03-09 11:04:43', '2026-03-09 11:32:13', '2026-03-09 11:32:13', '2026-03-09 11:32:26', '2026-03-09 11:32:36', 'TRK-20260309-000009'),
('10', '1', 'Piolo Niño Salaño', '2026-03-09 11:34:39', '320.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 21, Tacloban City', '9356062163', 'cancelled', NULL, NULL, '1', '2026-03-09 11:35:20', '2026-03-09 11:37:14', NULL, NULL, '2026-03-09 11:35:38', NULL, NULL, NULL, NULL, NULL),
('11', '1', 'Piolo Niño Salaño', '2026-03-09 11:38:02', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 20, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-09 11:38:38', '2026-03-09 11:40:35', NULL, '1', '2026-03-09 11:38:54', '2026-03-09 11:39:54', '2026-03-09 11:39:54', '2026-03-09 11:40:17', '2026-03-09 11:40:35', 'TRK-20260309-000011'),
('12', '1', 'Piolo Niño Salaño', '2026-03-09 12:09:24', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 17, Tacloban City', '9356062163', 'cancelled', NULL, NULL, '1', '2026-03-09 12:14:59', '2026-03-09 12:36:04', NULL, NULL, '2026-03-09 12:15:17', NULL, NULL, NULL, NULL, NULL),
('13', '1', 'Piolo Niño Salaño', '2026-03-09 12:14:21', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 20, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-09 12:49:29', '2026-03-09 12:52:41', NULL, '2', '2026-03-09 12:49:39', '2026-03-09 12:52:07', '2026-03-09 12:52:07', '2026-03-09 12:52:25', '2026-03-09 12:52:41', 'TRK-20260309-000013'),
('14', '1', 'Piolo Niño Salaño', '2026-03-09 12:36:47', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 20, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-09 12:37:11', '2026-03-09 12:44:19', NULL, '6', '2026-03-09 12:37:27', '2026-03-09 12:43:36', '2026-03-09 12:43:36', '2026-03-09 12:44:01', '2026-03-09 12:43:52', 'TRK-20260309-000014'),
('15', '1', 'Piolo Niño Salaño', '2026-03-09 13:00:31', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 19, Tacloban City', '9356062163', '', NULL, NULL, '1', '2026-03-09 13:00:57', '2026-03-09 13:07:25', NULL, '3', '2026-03-09 13:01:16', '2026-03-09 13:04:01', '2026-03-09 13:04:01', '2026-03-09 13:04:39', '2026-03-09 13:04:54', 'TRK-20260309-000015'),
('16', '1', 'Piolo Niño Salaño', '2026-03-09 13:18:16', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 20, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-09 13:18:36', '2026-03-09 13:21:44', NULL, '3', '2026-03-09 13:18:50', '2026-03-09 13:21:26', '2026-03-09 13:21:26', '2026-03-09 13:21:35', '2026-03-09 13:21:44', 'TRK-20260309-000016'),
('17', '1', 'Piolo Niño Salaño', '2026-03-09 17:20:59', '120.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 19, Tacloban City', '9356062163', '', NULL, NULL, NULL, '2026-03-09 17:21:27', '2026-03-09 17:22:46', NULL, '1', '2026-03-09 17:21:42', '2026-03-09 17:22:46', '2026-03-09 17:22:46', '2026-03-09 17:22:33', NULL, 'TRK-20260309-000017'),
('18', '1', 'Piolo Niño Salaño', '2026-03-09 17:29:37', '180.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 18, Tacloban City', '9356062163', '', NULL, NULL, NULL, '2026-03-09 17:29:57', '2026-03-09 17:40:28', NULL, '1', '2026-03-09 17:30:10', '2026-03-09 17:31:17', '2026-03-09 17:32:22', '2026-03-09 17:31:38', NULL, 'TRK-20260309-000018'),
('19', '1', 'Piolo Niño Salaño', '2026-03-09 17:55:19', '180.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 19, Tacloban City', '9356062163', 'cancelled', NULL, NULL, '1', '2026-03-09 17:55:41', '2026-03-09 17:57:33', NULL, '1', '2026-03-09 17:56:15', '2026-03-09 17:57:09', NULL, NULL, NULL, NULL),
('20', '1', 'Piolo Niño Salaño', '2026-03-09 17:58:19', '180.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 18, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-09 17:58:52', '2026-03-09 18:04:20', NULL, '1', '2026-03-09 18:00:37', '2026-03-09 18:01:08', NULL, '2026-03-09 18:03:31', '2026-03-09 18:04:20', 'TRK-20260309-000020'),
('21', '1', 'Piolo Niño Salaño', '2026-03-09 18:11:07', '180.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 12 (Palanog Resettlement), Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-09 18:11:26', '2026-03-09 18:12:42', NULL, '0', '2026-03-09 18:11:38', '2026-03-09 18:11:49', NULL, '2026-03-09 18:12:30', '2026-03-09 18:12:42', 'TRK-20260309-000021'),
('22', '1', 'Piolo Niño Salaño', '2026-03-09 18:17:02', '180.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 14, Tacloban City', '9356062163', '', NULL, NULL, '1', '2026-03-09 18:17:18', '2026-03-09 18:26:53', NULL, '1', '2026-03-09 18:17:26', '2026-03-09 18:18:34', NULL, '2026-03-09 18:18:47', '2026-03-09 18:19:01', 'TRK-20260309-000022'),
('23', '1', 'Piolo Niño Salaño', '2026-03-09 18:28:14', '180.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 17, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-09 18:28:30', '2026-03-09 18:36:09', NULL, '1', '2026-03-09 18:28:40', '2026-03-09 18:29:50', NULL, '2026-03-09 18:30:04', '2026-03-09 18:36:09', 'TRK-20260309-000023'),
('24', '1', 'Piolo Niño Salaño', '2026-03-09 18:36:44', '180.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 15, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-09 18:37:01', '2026-03-09 18:37:59', NULL, '1', '2026-03-09 18:37:09', '2026-03-09 18:37:36', NULL, '2026-03-09 18:37:48', '2026-03-09 18:37:59', 'TRK-20260309-000024'),
('25', '1', 'Piolo Niño Salaño', '2026-03-09 18:46:31', '188.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 17, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-09 18:46:48', '2026-03-09 18:47:54', NULL, '1', '2026-03-09 18:46:59', '2026-03-09 18:47:34', NULL, '2026-03-09 18:47:45', '2026-03-09 18:47:54', 'TRK-20260309-000025'),
('26', '1', 'Piolo Niño Salaño', '2026-03-09 18:49:43', '180.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 17, Tacloban City', '9356062163', 'cancelled', NULL, NULL, '1', '2026-03-09 18:52:33', '2026-03-09 19:05:45', NULL, NULL, '2026-03-09 18:52:39', NULL, NULL, NULL, NULL, NULL),
('27', '1', 'Piolo Niño Salaño', '2026-03-09 19:06:09', '180.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 21, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-09 19:09:01', '2026-03-09 19:11:07', NULL, '1', '2026-03-09 19:09:13', '2026-03-09 19:10:20', NULL, '2026-03-09 19:10:52', '2026-03-09 19:11:07', 'TRK-20260309-000027'),
('28', '1', 'Piolo Niño Salaño', '2026-03-09 19:19:44', '180.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 19, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-09 19:20:01', '2026-03-09 19:23:12', NULL, '1', '2026-03-09 19:20:16', '2026-03-09 19:21:28', NULL, '2026-03-09 19:21:45', '2026-03-09 19:23:12', 'TRK-20260309-000028'),
('29', '1', 'Piolo Niño Salaño', '2026-03-09 19:30:22', '180.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 18, Tacloban City', '9356062163', 'completed', NULL, NULL, '1', '2026-03-09 19:30:41', '2026-03-09 19:38:54', NULL, '1', '2026-03-09 19:31:04', '2026-03-09 19:32:22', NULL, '2026-03-09 19:38:46', '2026-03-09 19:38:54', 'TRK-20260309-000029'),
('30', '1', 'Piolo Niño Salaño', '2026-03-09 19:39:31', '180.00', '20.00', 'cash', NULL, NULL, NULL, NULL, 'Barangay 19, Tacloban City', '9356062163', 'ready', NULL, NULL, NULL, '2026-03-09 19:39:47', '2026-03-09 19:41:50', NULL, '2', '2026-03-09 19:39:59', '2026-03-09 19:41:50', NULL, NULL, NULL, NULL);


-- -------------------------------------------------
-- Table structure for table `tbl_preparation_settings`
-- -------------------------------------------------
CREATE TABLE `tbl_preparation_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(50) NOT NULL,
  `item_name` varchar(100) DEFAULT NULL,
  `preparation_time` int(11) NOT NULL COMMENT 'in minutes',
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `category` (`category`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_preparation_settings` - 4 records
INSERT INTO `tbl_preparation_settings` (`id`, `category`, `item_name`, `preparation_time`, `description`, `is_default`, `created_at`, `updated_at`) VALUES 
('1', 'budget', 'Budget Items (< ?10)', '1', 'Small items like puto, kutsinta', '1', '2026-03-09 11:05:55', '2026-03-09 13:17:52'),
('2', 'regular', 'Regular Items (?10-?249)', '20', 'Standard items like bibingka, biko', '1', '2026-03-09 11:05:55', '2026-03-09 11:05:55'),
('3', 'premium', 'Premium Items (?250+)', '30', 'Large bilao sets, special orders', '1', '2026-03-09 11:05:55', '2026-03-09 11:05:55'),
('4', 'custom', 'Custom Orders', '45', 'Special requests and bulk orders', '0', '2026-03-09 11:05:55', '2026-03-09 11:05:55');


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
('1', 'Puto', 'Soft and fluffy rice cake, perfect for breakfast or merienda', '5.00', '20', '10', 'puto.jpg', '2026-03-08 23:35:09', '2026-03-09 17:29:24'),
('2', 'Kutsinta', 'Brown rice cake topped with grated coconut, chewy and sweet', '8.00', '778', '10', 'kutsinta.jpg', '2026-03-08 23:35:09', '2026-03-09 19:39:31'),
('3', 'Bibingka', 'Baked rice cake with salted egg and cheese, topped with butter', '15.00', '200', '10', 'bibingka.jpg', '2026-03-08 23:35:09', '2026-03-09 11:34:39'),
('4', 'Biko', 'Sweet sticky rice cake with coconut milk and brown sugar latik', '20.00', '300', '10', 'biko.jpg', '2026-03-08 23:35:09', '2026-03-09 08:44:55'),
('5', 'Cassava Cake', 'Dense and moist cassava based cake with custard topping', '25.00', '300', '10', 'cassava-cake.jpg', '2026-03-08 23:35:09', '2026-03-09 08:44:49'),
('6', 'Ube Halaya', 'Rich and creamy purple yam dessert, perfect for any occasion', '30.00', '296', '10', 'ube-halaya.jpg', '2026-03-08 23:35:09', '2026-03-09 10:07:19'),
('7', 'Maja Blanca', 'Coconut pudding with corn, smooth and creamy', '12.00', '300', '10', 'maja.jpg', '2026-03-08 23:35:09', '2026-03-09 08:45:11'),
('8', 'Sapin-sapin', 'Layered glutinous rice dessert with ube and coconut', '22.00', '300', '10', 'sapin-sapin.jpg', '2026-03-08 23:35:09', '2026-03-09 08:45:35'),
('9', 'Palitaw', 'Sweet rice cakes coated with coconut and sesame seeds', '15.00', '300', '10', 'palitaw.jpg', '2026-03-08 23:35:09', '2026-03-09 08:45:18'),
('10', 'Special Puto Kutsinta', 'Assorted kakanin in bilao, perfect for parties', '250.00', '298', '5', 'bilao.jpg', '2026-03-08 23:35:09', '2026-03-09 08:48:37'),
('11', 'Suman', 'Sticky rice wrapped in banana leaves, served with sugar', '12.00', '298', '10', 'suman.jpg', '2026-03-08 23:35:09', '2026-03-09 09:25:47'),
('12', 'Special Biko', 'Premium biko bilao with extra latik and toppings', '250.00', '298', '10', 'biko-bilao.jpg', '2026-03-08 23:35:09', '2026-03-09 08:48:37'),
('13', 'Special Palitaw', 'Premium palitaw bilao with extra coconut and sesame', '250.00', '290', '10', '699d19773c8b5_1771903351.jpg', '2026-03-08 23:35:09', '2026-03-09 10:59:40'),
('14', 'Special Black Kutsinta', 'Premium black kutsinta bilao with premium ingredients', '250.00', '288', '10', 'black-kutsinta.jpg', '2026-03-08 23:35:09', '2026-03-09 17:28:25'),
('15', 'Special Suman Latik', 'Premium suman latik bilao with caramelized coconut', '250.00', '298', '10', 'suman-latik.jpg', '2026-03-08 23:35:09', '2026-03-09 08:48:37');


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
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `tbl_staff_notifications_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `tbl_orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table `tbl_staff_notifications` - 30 records
INSERT INTO `tbl_staff_notifications` (`id`, `order_id`, `is_read`, `created_at`) VALUES 
('1', '1', '0', '2026-03-09 08:48:37'),
('2', '2', '0', '2026-03-09 09:25:47'),
('3', '3', '0', '2026-03-09 10:07:19'),
('4', '4', '0', '2026-03-09 10:10:27'),
('5', '5', '0', '2026-03-09 10:17:56'),
('6', '6', '0', '2026-03-09 10:26:12'),
('7', '7', '0', '2026-03-09 10:36:19'),
('8', '8', '0', '2026-03-09 10:59:40'),
('9', '9', '0', '2026-03-09 11:03:44'),
('10', '10', '0', '2026-03-09 11:34:39'),
('11', '11', '0', '2026-03-09 11:38:02'),
('12', '12', '0', '2026-03-09 12:09:24'),
('13', '13', '0', '2026-03-09 12:14:21'),
('14', '14', '0', '2026-03-09 12:36:47'),
('15', '15', '0', '2026-03-09 13:00:31'),
('16', '16', '0', '2026-03-09 13:18:16'),
('17', '17', '0', '2026-03-09 17:20:59'),
('18', '18', '0', '2026-03-09 17:29:37'),
('19', '19', '0', '2026-03-09 17:55:19'),
('20', '20', '0', '2026-03-09 17:58:19'),
('21', '21', '0', '2026-03-09 18:11:07'),
('22', '22', '0', '2026-03-09 18:17:02'),
('23', '23', '0', '2026-03-09 18:28:14'),
('24', '24', '0', '2026-03-09 18:36:44'),
('25', '25', '0', '2026-03-09 18:46:31'),
('26', '26', '0', '2026-03-09 18:49:43'),
('27', '27', '0', '2026-03-09 19:06:09'),
('28', '28', '0', '2026-03-09 19:19:44'),
('29', '29', '0', '2026-03-09 19:30:22'),
('30', '30', '0', '2026-03-09 19:39:31');


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
('1', '0', '1', '2026-03-10 07:33:34', '');


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
('1', 'admin', '$2y$10$ER3DiBS/hJD2xhTLWmTE1uLtG6hHJt1Kx0McT1PG5s5xloxvEvItW', 'admin', '1', NULL, NULL, '2026-03-10 07:33:34', 'close', '2026-03-08 23:35:09'),
('2', 'Cashier', '$2y$10$Sq652hsdQYFMqB2n0V1WWO30g69uNFgSyIwWTCwMjvwvC7KM0jgle', 'cashier', '1', NULL, NULL, NULL, NULL, '2026-03-08 23:35:09'),
('3', 'manager', '$2y$10$rSeMsTd8.hPEfth3hgptJeLvD/QRmtwiEvJF7B31aHJbjBOtmDhs.', 'manager', '1', NULL, NULL, '2026-03-09 08:47:22', 'open', '2026-03-08 23:35:09');

SET FOREIGN_KEY_CHECKS = 1;

-- ===================================================
-- BACKUP COMPLETED
-- Total Tables: 20
-- Total Records: 682
-- ===================================================
