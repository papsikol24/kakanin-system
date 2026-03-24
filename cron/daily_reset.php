<?php
/**
 * Daily Reset Cron Job
 * Run this script every day at 12:00 AM
 * Can be set up as a cron job: 0 0 * * * php /path/to/cron/daily_reset.php
 */

require_once __DIR__ . '/../includes/config.php';

// Log start of reset process
error_log("Daily reset started at " . date('Y-m-d H:i:s'));

try {
    $pdo->beginTransaction();
    
    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    
    // ===== 1. Archive yesterday's orders =====
    $archiveOrders = $pdo->prepare("
        INSERT INTO tbl_daily_orders_archive 
        (original_order_id, daily_order_number, order_date, customer_name, total_amount, payment_method, status, items_count, archive_date)
        SELECT 
            o.id,
            ROW_NUMBER() OVER (ORDER BY o.order_date) as daily_order_number,
            o.order_date,
            COALESCE(o.customer_name, 'Walk-in') as customer_name,
            o.total_amount,
            o.payment_method,
            o.status,
            (SELECT COUNT(*) FROM tbl_order_items WHERE order_id = o.id) as items_count,
            DATE(o.order_date) as archive_date
        FROM tbl_orders o
        WHERE DATE(o.order_date) = ?
    ");
    $archiveOrders->execute([$yesterday]);
    $archivedOrdersCount = $archiveOrders->rowCount();
    
    // ===== 2. Archive yesterday's inventory logs =====
    $archiveLogs = $pdo->prepare("
        INSERT INTO tbl_daily_inventory_archive
        (original_log_id, product_id, product_name, change_type, quantity_changed, previous_stock, new_stock, user_name, log_time, archive_date)
        SELECT 
            l.id,
            l.product_id,
            p.name,
            l.change_type,
            l.quantity_changed,
            l.previous_stock,
            l.new_stock,
            u.username,
            l.log_time,
            DATE(l.log_time) as archive_date
        FROM tbl_inventory_logs l
        JOIN tbl_products p ON l.product_id = p.id
        LEFT JOIN tbl_users u ON l.user_id = u.id
        WHERE DATE(l.log_time) = ?
    ");
    $archiveLogs->execute([$yesterday]);
    $archivedLogsCount = $archiveLogs->rowCount();
    
    // ===== 3. Create daily sales summary =====
    $salesSummary = $pdo->prepare("
        INSERT INTO tbl_daily_sales_summary
        (sale_date, total_orders, total_sales, cash_sales, gcash_sales, paymaya_sales, 
         completed_orders, pending_orders, cancelled_orders, total_items_sold)
        SELECT 
            DATE(o.order_date) as sale_date,
            COUNT(DISTINCT o.id) as total_orders,
            COALESCE(SUM(o.total_amount), 0) as total_sales,
            COALESCE(SUM(CASE WHEN o.payment_method = 'cash' THEN o.total_amount ELSE 0 END), 0) as cash_sales,
            COALESCE(SUM(CASE WHEN o.payment_method = 'gcash' THEN o.total_amount ELSE 0 END), 0) as gcash_sales,
            COALESCE(SUM(CASE WHEN o.payment_method = 'paymaya' THEN o.total_amount ELSE 0 END), 0) as paymaya_sales,
            COUNT(CASE WHEN o.status = 'completed' THEN 1 END) as completed_orders,
            COUNT(CASE WHEN o.status = 'pending' THEN 1 END) as pending_orders,
            COUNT(CASE WHEN o.status = 'cancelled' THEN 1 END) as cancelled_orders,
            COALESCE(SUM(oi.quantity), 0) as total_items_sold
        FROM tbl_orders o
        LEFT JOIN tbl_order_items oi ON o.id = oi.order_id
        WHERE DATE(o.order_date) = ?
        GROUP BY DATE(o.order_date)
    ");
    $salesSummary->execute([$yesterday]);
    
    // ===== 4. Reset daily counters for today =====
    $resetCounter = $pdo->prepare("
        INSERT INTO tbl_daily_counters (counter_date, order_counter, inventory_log_counter)
        VALUES (CURDATE(), 1, 1)
        ON DUPLICATE KEY UPDATE 
            order_counter = 1,
            inventory_log_counter = 1,
            last_reset_at = NOW()
    ");
    $resetCounter->execute();
    
    // ===== 5. Clean up old data (optional - keep last 30 days in main tables) =====
    // Delete orders older than 30 days from main tables (they're already archived)
    $cleanOrders = $pdo->prepare("DELETE FROM tbl_orders WHERE DATE(order_date) < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $cleanOrders->execute();
    
    $cleanLogs = $pdo->prepare("DELETE FROM tbl_inventory_logs WHERE DATE(log_time) < DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
    $cleanLogs->execute();
    
    $pdo->commit();
    
    // Log success
    error_log("Daily reset completed successfully at " . date('Y-m-d H:i:s'));
    error_log("Archived {$archivedOrdersCount} orders and {$archivedLogsCount} inventory logs from {$yesterday}");
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Daily reset failed: " . $e->getMessage());
}
?>