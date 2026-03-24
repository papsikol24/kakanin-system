<?php
// File: C:\xampp\htdocs\kakanin_system\cron\test_reset_now.php
require_once '../includes/config.php';

echo "====================================\n";
echo "     DAILY RESET TEST SCRIPT        \n";
echo "====================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "====================================\n\n";

try {
    $pdo->beginTransaction();
    
    // Get yesterday's date
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $today = date('Y-m-d');
    
    echo "📅 Testing with yesterday: $yesterday\n";
    echo "📅 Today is: $today\n\n";
    
    // ===== STEP 1: Check if there are orders from yesterday =====
    $checkOrders = $pdo->prepare("SELECT COUNT(*) FROM tbl_orders WHERE DATE(order_date) = ?");
    $checkOrders->execute([$yesterday]);
    $orderCount = $checkOrders->fetchColumn();
    
    echo "📊 Found $orderCount orders from $yesterday\n";
    
    if ($orderCount == 0) {
        echo "⚠️ No orders from yesterday. Creating sample orders...\n";
        
        // Create sample orders for testing
        for ($i = 1; $i <= 3; $i++) {
            $stmt = $pdo->prepare("
                INSERT INTO tbl_orders 
                (customer_name, total_amount, payment_method, status, order_date, delivery_address, delivery_phone) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $customer = "Test Customer $i";
            $amount = rand(100, 500) + ($i * 10);
            $methods = ['cash', 'gcash', 'paymaya'];
            $method = $methods[array_rand($methods)];
            $statuses = ['completed', 'pending', 'completed'];
            $status = $statuses[$i-1];
            $orderDate = $yesterday . ' 10:' . str_pad($i*15, 2, '0', STR_PAD_LEFT) . ':00';
            $address = "Barangay " . (83 + $i) . ", Tacloban City";
            $phone = "0917" . rand(1000000, 9999999);
            
            $stmt->execute([$customer, $amount, $method, $status, $orderDate, $address, $phone]);
            $orderId = $pdo->lastInsertId();
            
            // Add order items (2-3 items per order)
            $numItems = rand(2, 3);
            for ($j = 1; $j <= $numItems; $j++) {
                $productId = rand(1, 5);
                $quantity = rand(1, 3);
                $price = rand(50, 200);
                
                $itemStmt = $pdo->prepare("
                    INSERT INTO tbl_order_items (order_id, product_id, quantity, price) 
                    VALUES (?, ?, ?, ?)
                ");
                $itemStmt->execute([$orderId, $productId, $quantity, $price]);
                
                // Update product stock
                $updateStock = $pdo->prepare("UPDATE tbl_products SET stock = stock - ? WHERE id = ?");
                $updateStock->execute([$quantity, $productId]);
            }
            
            echo "   ✅ Created order #$orderId for $customer (₱" . number_format($amount, 2) . ")\n";
        }
        echo "\n";
    }
    
    // ===== STEP 2: Archive yesterday's orders =====
    echo "🔄 Archiving orders from $yesterday...\n";
    
    // First, delete any existing archives for this date (to avoid duplicates)
    $deleteArchive = $pdo->prepare("DELETE FROM tbl_daily_orders_archive WHERE archive_date = ?");
    $deleteArchive->execute([$yesterday]);
    
    $archiveOrders = $pdo->prepare("
        INSERT INTO tbl_daily_orders_archive 
        (original_order_id, daily_order_number, order_date, customer_name, total_amount, 
         payment_method, status, items_count, archive_date)
        SELECT 
            o.id,
            @rownum := @rownum + 1 as daily_order_number,
            o.order_date,
            COALESCE(o.customer_name, 'Walk-in'),
            o.total_amount,
            o.payment_method,
            o.status,
            (SELECT COUNT(*) FROM tbl_order_items WHERE order_id = o.id),
            DATE(o.order_date)
        FROM tbl_orders o
        CROSS JOIN (SELECT @rownum := 0) r
        WHERE DATE(o.order_date) = ?
        ORDER BY o.order_date
    ");
    $archiveOrders->execute([$yesterday]);
    $archivedCount = $archiveOrders->rowCount();
    
    echo "   ✅ Archived $archivedCount orders\n";
    
    // ===== STEP 3: Create daily sales summary =====
    echo "📈 Creating daily sales summary for $yesterday...\n";
    
    // Delete existing summary if any
    $deleteSummary = $pdo->prepare("DELETE FROM tbl_daily_sales_summary WHERE sale_date = ?");
    $deleteSummary->execute([$yesterday]);
    
    $summaryStmt = $pdo->prepare("
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
    $summaryStmt->execute([$yesterday]);
    $summaryCount = $summaryStmt->rowCount();
    
    echo "   ✅ Daily summary created\n\n";
    
    // ===== STEP 4: Reset counter for today =====
    echo "🔄 Resetting daily counter for $today...\n";
    
    $resetStmt = $pdo->prepare("
        INSERT INTO tbl_daily_counters (counter_date, order_counter, inventory_log_counter)
        VALUES (?, 1, 1)
        ON DUPLICATE KEY UPDATE 
            order_counter = 1,
            inventory_log_counter = 1,
            last_reset_at = NOW()
    ");
    $resetStmt->execute([$today]);
    
    echo "   ✅ Counter reset to 1\n\n";
    
    // ===== STEP 5: Clear session mapping for today (optional) =====
    if (isset($_SESSION['daily_order_map'][$today])) {
        unset($_SESSION['daily_order_map'][$today]);
        echo "🧹 Cleared session mapping for today\n\n";
    }
    
    $pdo->commit();
    
    // ===== STEP 6: Show results =====
    echo "====================================\n";
    echo "✅ TEST RESET COMPLETED SUCCESSFULLY!\n";
    echo "====================================\n\n";
    
    // Show archived orders
    $showArchive = $pdo->prepare("
        SELECT * FROM tbl_daily_orders_archive 
        WHERE archive_date = ? 
        ORDER BY daily_order_number
    ");
    $showArchive->execute([$yesterday]);
    $archived = $showArchive->fetchAll();
    
    echo "📋 Archived Orders from $yesterday:\n";
    echo str_repeat("-", 70) . "\n";
    printf("%-10s %-8s %-20s %-12s %-10s %-10s\n", "Daily #", "OrderID", "Customer", "Amount", "Payment", "Status");
    echo str_repeat("-", 70) . "\n";
    
    foreach ($archived as $a) {
        printf("%-10s #%-7s %-20s ₱%-11s %-10s %-10s\n", 
            "ORD-" . str_pad($a['daily_order_number'], 4, '0', STR_PAD_LEFT),
            $a['original_order_id'],
            substr($a['customer_name'], 0, 18),
            number_format($a['total_amount'], 2),
            ucfirst($a['payment_method']),
            ucfirst($a['status'])
        );
    }
    
    // Show daily summary
    $showSummary = $pdo->prepare("SELECT * FROM tbl_daily_sales_summary WHERE sale_date = ?");
    $showSummary->execute([$yesterday]);
    $summary = $showSummary->fetch();
    
    if ($summary) {
        echo "\n📊 Daily Summary for $yesterday:\n";
        echo str_repeat("-", 50) . "\n";
        echo "Total Orders:     {$summary['total_orders']}\n";
        echo "Total Sales:      ₱" . number_format($summary['total_sales'], 2) . "\n";
        echo "Cash Sales:       ₱" . number_format($summary['cash_sales'], 2) . "\n";
        echo "GCash Sales:      ₱" . number_format($summary['gcash_sales'], 2) . "\n";
        echo "PayMaya Sales:    ₱" . number_format($summary['paymaya_sales'], 2) . "\n";
        echo "Completed Orders: {$summary['completed_orders']}\n";
        echo "Pending Orders:   {$summary['pending_orders']}\n";
        echo "Cancelled Orders: {$summary['cancelled_orders']}\n";
        echo "Items Sold:       {$summary['total_items_sold']}\n";
    }
    
    // Show current counter
    $showCounter = $pdo->prepare("SELECT * FROM tbl_daily_counters WHERE counter_date = ?");
    $showCounter->execute([$today]);
    $counter = $showCounter->fetch();
    
    if ($counter) {
        echo "\n🔢 Daily Counter for $today:\n";
        echo str_repeat("-", 50) . "\n";
        echo "Next Order #:      " . $counter['order_counter'] . "\n";
        echo "Next Inventory #:  " . $counter['inventory_log_counter'] . "\n";
        echo "Last Reset:        " . $counter['last_reset_at'] . "\n";
    }
    
    // Show next expected daily number
    echo "\n📌 Next order will be: ORD-" . date('Y-m-d') . "-" . 
         str_pad(($counter ? $counter['order_counter'] : 1), 4, '0', STR_PAD_LEFT) . "\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " line " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n====================================\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";
echo "====================================\n";
?>