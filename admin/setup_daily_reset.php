<?php
require_once '../includes/config.php';
requireLogin();
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_tables'])) {
    try {
        $sql = "
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
          KEY `archive_date` (`archive_date`),
          KEY `daily_order_number` (`daily_order_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

        CREATE TABLE IF NOT EXISTS `tbl_daily_counters` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `counter_date` date NOT NULL,
          `order_counter` int(11) DEFAULT 1,
          `inventory_log_counter` int(11) DEFAULT 1,
          `last_reset_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_counter_date` (`counter_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        INSERT INTO `tbl_daily_counters` (`counter_date`, `order_counter`, `inventory_log_counter`) 
        VALUES (CURDATE(), 1, 1)
        ON DUPLICATE KEY UPDATE `order_counter` = `order_counter`;
        ";
        
        $pdo->exec($sql);
        $_SESSION['success'] = "✅ Daily reset tables created successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ Error: " . $e->getMessage();
    }
    header('Location: /modules/tools/setup_daily_reset.php');
    exit;
}

header('Location: /modules/tools/setup_daily_reset.php');
exit;
?>