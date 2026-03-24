<?php
// Turn off error display
error_reporting(0);
ini_set('display_errors', 0);

require_once '../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$response = ['success' => false];

try {
    // Check if user is logged in as admin/manager
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    
    // Check if user is admin or manager
    $stmt = $pdo->prepare("SELECT role FROM tbl_users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user || !in_array($user['role'], ['admin', 'manager'])) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    $cashier_id = isset($_POST['cashier_id']) ? (int)$_POST['cashier_id'] : 0;
    $from_date = $_POST['from_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $to_date = $_POST['to_date'] ?? date('Y-m-d');
    
    if ($action === 'reset_all') {
        // Reset ALL cashier performance
        $pdo->beginTransaction();
        
        try {
            // Get all cashier IDs
            $cashiers = $pdo->query("SELECT id FROM tbl_users WHERE role = 'cashier'")->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($cashiers)) {
                throw new Exception("No cashiers found");
            }
            
            $placeholders = implode(',', array_fill(0, count($cashiers), '?'));
            
            // First, get the orders to be deleted (for archiving)
            $orders_to_delete = $pdo->prepare("
                SELECT id FROM tbl_orders 
                WHERE (created_by IN ($placeholders) OR completed_by IN ($placeholders))
                AND DATE(order_date) BETWEEN ? AND ?
            ");
            $params = array_merge($cashiers, $cashiers, [$from_date, $to_date]);
            $orders_to_delete->execute($params);
            $order_ids = $orders_to_delete->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($order_ids)) {
                $pdo->commit();
                $response['success'] = true;
                $response['message'] = "No orders found in the selected date range.";
                echo json_encode($response);
                exit;
            }
            
            $order_placeholders = implode(',', array_fill(0, count($order_ids), '?'));
            
            // Archive orders
            $archive_sql = "
                INSERT INTO tbl_daily_orders_archive 
                (original_order_id, daily_order_number, order_date, customer_name, total_amount, payment_method, status, items_count, archive_date)
                SELECT o.id, COALESCE(doa.daily_order_number, 0), o.order_date, o.customer_name, o.total_amount, o.payment_method, o.status, 
                       (SELECT COUNT(*) FROM tbl_order_items WHERE order_id = o.id), CURDATE()
                FROM tbl_orders o
                LEFT JOIN tbl_daily_orders_archive doa ON o.id = doa.original_order_id
                WHERE o.id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($archive_sql);
            $stmt->execute($order_ids);
            $archived = $stmt->rowCount();
            
            // Delete order items
            $delete_items_sql = "
                DELETE FROM tbl_order_items 
                WHERE order_id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($delete_items_sql);
            $stmt->execute($order_ids);
            $deleted_items = $stmt->rowCount();
            
            // Delete notifications
            $delete_notifications_sql = "
                DELETE FROM tbl_notifications 
                WHERE order_id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($delete_notifications_sql);
            $stmt->execute($order_ids);
            
            // Delete staff notifications
            $delete_staff_notifications_sql = "
                DELETE FROM tbl_staff_notifications 
                WHERE order_id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($delete_staff_notifications_sql);
            $stmt->execute($order_ids);
            
            // Delete orders
            $delete_orders_sql = "
                DELETE FROM tbl_orders 
                WHERE id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($delete_orders_sql);
            $stmt->execute($order_ids);
            $deleted_orders = $stmt->rowCount();
            
            // Log the action in a special log table or use a default product
            // FIXED: Use a valid product_id (get the first product or create a system log entry)
            $first_product = $pdo->query("SELECT id FROM tbl_products LIMIT 1")->fetchColumn();
            if ($first_product) {
                $log_sql = "INSERT INTO tbl_inventory_logs (product_id, user_id, change_type, quantity_changed, previous_stock, new_stock) VALUES (?, ?, 'set', 0, 0, 0)";
                $log_stmt = $pdo->prepare($log_sql);
                $log_stmt->execute([$first_product, $_SESSION['user_id']]);
            }
            
            $pdo->commit();
            
            $response['success'] = true;
            $response['message'] = "✅ Reset complete! Deleted {$deleted_orders} orders and {$deleted_items} items. Archived {$archived} records.";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } elseif ($action === 'reset_single' && $cashier_id > 0) {
        // Reset SINGLE cashier's performance
        $pdo->beginTransaction();
        
        try {
            // Check if cashier exists
            $check = $pdo->prepare("SELECT username FROM tbl_users WHERE id = ? AND role = 'cashier'");
            $check->execute([$cashier_id]);
            $cashier = $check->fetch();
            
            if (!$cashier) {
                throw new Exception("Cashier not found");
            }
            
            // Get orders to delete
            $orders_to_delete = $pdo->prepare("
                SELECT id FROM tbl_orders 
                WHERE (created_by = ? OR completed_by = ?)
                AND DATE(order_date) BETWEEN ? AND ?
            ");
            $orders_to_delete->execute([$cashier_id, $cashier_id, $from_date, $to_date]);
            $order_ids = $orders_to_delete->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($order_ids)) {
                $pdo->commit();
                $response['success'] = true;
                $response['message'] = "No orders found for this cashier in the selected date range.";
                echo json_encode($response);
                exit;
            }
            
            $order_placeholders = implode(',', array_fill(0, count($order_ids), '?'));
            
            // Archive orders
            $archive_sql = "
                INSERT INTO tbl_daily_orders_archive 
                (original_order_id, daily_order_number, order_date, customer_name, total_amount, payment_method, status, items_count, archive_date)
                SELECT o.id, COALESCE(doa.daily_order_number, 0), o.order_date, o.customer_name, o.total_amount, o.payment_method, o.status, 
                       (SELECT COUNT(*) FROM tbl_order_items WHERE order_id = o.id), CURDATE()
                FROM tbl_orders o
                LEFT JOIN tbl_daily_orders_archive doa ON o.id = doa.original_order_id
                WHERE o.id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($archive_sql);
            $stmt->execute($order_ids);
            $archived = $stmt->rowCount();
            
            // Delete order items
            $delete_items_sql = "
                DELETE FROM tbl_order_items 
                WHERE order_id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($delete_items_sql);
            $stmt->execute($order_ids);
            $deleted_items = $stmt->rowCount();
            
            // Delete notifications
            $delete_notifications_sql = "
                DELETE FROM tbl_notifications 
                WHERE order_id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($delete_notifications_sql);
            $stmt->execute($order_ids);
            
            // Delete staff notifications
            $delete_staff_notifications_sql = "
                DELETE FROM tbl_staff_notifications 
                WHERE order_id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($delete_staff_notifications_sql);
            $stmt->execute($order_ids);
            
            // Delete orders
            $delete_orders_sql = "
                DELETE FROM tbl_orders 
                WHERE id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($delete_orders_sql);
            $stmt->execute($order_ids);
            $deleted_orders = $stmt->rowCount();
            
            // Log the action - FIXED: Use a valid product_id
            $first_product = $pdo->query("SELECT id FROM tbl_products LIMIT 1")->fetchColumn();
            if ($first_product) {
                $log_sql = "INSERT INTO tbl_inventory_logs (product_id, user_id, change_type, quantity_changed, previous_stock, new_stock) VALUES (?, ?, 'set', 0, 0, 0)";
                $log_stmt = $pdo->prepare($log_sql);
                $log_stmt->execute([$first_product, $_SESSION['user_id']]);
            }
            
            $pdo->commit();
            
            $response['success'] = true;
            $response['message'] = "✅ Performance reset for cashier: " . $cashier['username'] . " - Deleted {$deleted_orders} orders and {$deleted_items} items.";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } elseif ($action === 'reset_by_date_range') {
        // Reset performance for ALL cashiers within date range
        $pdo->beginTransaction();
        
        try {
            // Get orders to delete
            $orders_to_delete = $pdo->prepare("
                SELECT id FROM tbl_orders 
                WHERE DATE(order_date) BETWEEN ? AND ?
            ");
            $orders_to_delete->execute([$from_date, $to_date]);
            $order_ids = $orders_to_delete->fetchAll(PDO::FETCH_COLUMN);
            
            if (empty($order_ids)) {
                $pdo->commit();
                $response['success'] = true;
                $response['message'] = "No orders found in the selected date range.";
                echo json_encode($response);
                exit;
            }
            
            $order_placeholders = implode(',', array_fill(0, count($order_ids), '?'));
            
            // Archive orders
            $archive_sql = "
                INSERT INTO tbl_daily_orders_archive 
                (original_order_id, daily_order_number, order_date, customer_name, total_amount, payment_method, status, items_count, archive_date)
                SELECT o.id, COALESCE(doa.daily_order_number, 0), o.order_date, o.customer_name, o.total_amount, o.payment_method, o.status, 
                       (SELECT COUNT(*) FROM tbl_order_items WHERE order_id = o.id), CURDATE()
                FROM tbl_orders o
                LEFT JOIN tbl_daily_orders_archive doa ON o.id = doa.original_order_id
                WHERE o.id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($archive_sql);
            $stmt->execute($order_ids);
            $archived = $stmt->rowCount();
            
            // Delete order items
            $delete_items_sql = "
                DELETE FROM tbl_order_items 
                WHERE order_id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($delete_items_sql);
            $stmt->execute($order_ids);
            $deleted_items = $stmt->rowCount();
            
            // Delete notifications
            $delete_notifications_sql = "
                DELETE FROM tbl_notifications 
                WHERE order_id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($delete_notifications_sql);
            $stmt->execute($order_ids);
            
            // Delete staff notifications
            $delete_staff_notifications_sql = "
                DELETE FROM tbl_staff_notifications 
                WHERE order_id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($delete_staff_notifications_sql);
            $stmt->execute($order_ids);
            
            // Delete orders
            $delete_orders_sql = "
                DELETE FROM tbl_orders 
                WHERE id IN ($order_placeholders)
            ";
            $stmt = $pdo->prepare($delete_orders_sql);
            $stmt->execute($order_ids);
            $deleted_orders = $stmt->rowCount();
            
            // Log the action - FIXED: Use a valid product_id
            $first_product = $pdo->query("SELECT id FROM tbl_products LIMIT 1")->fetchColumn();
            if ($first_product) {
                $log_sql = "INSERT INTO tbl_inventory_logs (product_id, user_id, change_type, quantity_changed, previous_stock, new_stock) VALUES (?, ?, 'set', 0, 0, 0)";
                $log_stmt = $pdo->prepare($log_sql);
                $log_stmt->execute([$first_product, $_SESSION['user_id']]);
            }
            
            $pdo->commit();
            
            $response['success'] = true;
            $response['message'] = "✅ Reset complete for date range {$from_date} to {$to_date}! Deleted {$deleted_orders} orders and {$deleted_items} items.";
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } elseif ($action === 'get_cashiers') {
        // Get list of cashiers for dropdown
        $cashiers = $pdo->query("
            SELECT id, username, 
                   (SELECT COUNT(*) FROM tbl_orders WHERE created_by = id) as orders_created,
                   (SELECT COUNT(*) FROM tbl_orders WHERE completed_by = id) as orders_completed
            FROM tbl_users 
            WHERE role = 'cashier' 
            ORDER BY username
        ")->fetchAll();
        
        $response['success'] = true;
        $response['cashiers'] = $cashiers;
        
    } else {
        $response['error'] = 'Invalid action';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Reset Cashier Performance API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>