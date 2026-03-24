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
    // Check if user is logged in as staff
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }

    // Get parameters
    $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
    $to = $_GET['to'] ?? date('Y-m-d');
    $cashier_filter = $_GET['cashier'] ?? '';

    // ===== GET ALL CASHIERS WITH ACCURATE COUNTS =====
    $all_cashiers = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.status as account_status,
            u.created_at,
            DATE_FORMAT(u.created_at, '%Y-%m-%d') as join_date
        FROM tbl_users u
        WHERE u.role = 'cashier'
        ORDER BY u.username
    ")->fetchAll();

    // ===== GET ONLINE CASHIERS (active in last 5 minutes) =====
    $online_cashiers = $pdo->query("
        SELECT DISTINCT 
            u.id,
            u.username,
            MAX(s.last_activity) as last_activity,
            COUNT(DISTINCT s.id) as active_sessions,
            TIMESTAMPDIFF(SECOND, MAX(s.last_activity), NOW()) as seconds_ago
        FROM tbl_users u
        INNER JOIN tbl_active_sessions s ON u.id = s.user_id 
        WHERE u.role = 'cashier' 
        AND s.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        GROUP BY u.id
        ORDER BY last_activity DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Create lookup array for online status
    $online_lookup = [];
    $online_ids = [];
    foreach ($online_cashiers as $oc) {
        $online_ids[] = $oc['id'];
        $online_lookup[$oc['id']] = [
            'last_activity' => $oc['last_activity'],
            'active_sessions' => $oc['active_sessions'],
            'seconds_ago' => $oc['seconds_ago']
        ];
    }

    // ===== GET ACCURATE ORDER COUNTS FOR EACH CASHIER =====
    $cashier_data = [];
    $total_stats = [
        'total_cashiers' => count($all_cashiers),
        'online_count' => count($online_ids),
        'total_orders_created' => 0,
        'total_orders_completed' => 0,
        'total_orders_cancelled' => 0,
        'total_sales' => 0,
        'total_items_sold' => 0
    ];

    foreach ($all_cashiers as $cashier) {
        $id = $cashier['id'];
        
        // Apply filter if set
        if (!empty($cashier_filter) && $id != $cashier_filter) {
            continue;
        }
        
        // Get orders created by this cashier
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as order_count,
                COALESCE(SUM(total_amount), 0) as total_sales,
                COALESCE(SUM(
                    SELECT SUM(quantity) FROM tbl_order_items WHERE order_id = o.id
                ), 0) as items_sold
            FROM tbl_orders o
            WHERE o.created_by = ? 
            AND DATE(o.order_date) BETWEEN ? AND ?
        ");
        $stmt->execute([$id, $from, $to]);
        $created = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get orders completed by this cashier
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as order_count,
                COALESCE(SUM(total_amount), 0) as total_sales
            FROM tbl_orders o
            WHERE o.completed_by = ? 
            AND o.status = 'completed'
            AND DATE(o.order_date) BETWEEN ? AND ?
        ");
        $stmt->execute([$id, $from, $to]);
        $completed = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get orders cancelled by this cashier
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as order_count,
                COALESCE(SUM(total_amount), 0) as total_sales
            FROM tbl_orders o
            WHERE o.completed_by = ? 
            AND o.status = 'cancelled'
            AND DATE(o.order_date) BETWEEN ? AND ?
        ");
        $stmt->execute([$id, $from, $to]);
        $cancelled = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get payment method breakdown
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN o.payment_method = 'cash' THEN 1 ELSE 0 END) as cash_count,
                SUM(CASE WHEN o.payment_method = 'gcash' THEN 1 ELSE 0 END) as gcash_count,
                SUM(CASE WHEN o.payment_method = 'paymaya' THEN 1 ELSE 0 END) as paymaya_count,
                SUM(CASE WHEN o.payment_method = 'cash' THEN o.total_amount ELSE 0 END) as cash_total,
                SUM(CASE WHEN o.payment_method = 'gcash' THEN o.total_amount ELSE 0 END) as gcash_total,
                SUM(CASE WHEN o.payment_method = 'paymaya' THEN o.total_amount ELSE 0 END) as paymaya_total
            FROM tbl_orders o
            WHERE o.created_by = ? 
            AND DATE(o.order_date) BETWEEN ? AND ?
        ");
        $stmt->execute([$id, $from, $to]);
        $payments = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get last activity date
        $stmt = $pdo->prepare("
            SELECT MAX(o.order_date) as last_activity
            FROM tbl_orders o
            WHERE (o.created_by = ? OR o.completed_by = ?)
            AND DATE(o.order_date) BETWEEN ? AND ?
        ");
        $stmt->execute([$id, $id, $from, $to]);
        $last_activity = $stmt->fetchColumn();
        
        // Get days active (unique days with orders)
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT DATE(o.order_date)) as days_active
            FROM tbl_orders o
            WHERE (o.created_by = ? OR o.completed_by = ?)
            AND DATE(o.order_date) BETWEEN ? AND ?
        ");
        $stmt->execute([$id, $id, $from, $to]);
        $days_active = $stmt->fetchColumn();
        if (!$days_active) $days_active = 0;
        
        // Calculate totals
        $orders_created = (int)($created['order_count'] ?? 0);
        $orders_completed = (int)($completed['order_count'] ?? 0);
        $orders_cancelled = (int)($cancelled['order_count'] ?? 0);
        $sales_created = (float)($created['total_sales'] ?? 0);
        $sales_completed = (float)($completed['total_sales'] ?? 0);
        $items_sold = (int)($created['items_sold'] ?? 0);
        
        $total_orders = $orders_created + $orders_completed;
        $total_sales = $sales_created + $sales_completed;
        $avg_order_value = $total_orders > 0 ? $total_sales / $total_orders : 0;
        
        // Update total stats
        $total_stats['total_orders_created'] += $orders_created;
        $total_stats['total_orders_completed'] += $orders_completed;
        $total_stats['total_orders_cancelled'] += $orders_cancelled;
        $total_stats['total_sales'] += $total_sales;
        $total_stats['total_items_sold'] += $items_sold;
        
        // Determine online status
        $is_online = isset($online_lookup[$id]);
        $online_info = $is_online ? $online_lookup[$id] : null;
        
        $cashier_data[] = [
            'id' => $id,
            'username' => $cashier['username'],
            'account_status' => $cashier['account_status'],
            'join_date' => $cashier['join_date'],
            'orders_created' => $orders_created,
            'orders_completed' => $orders_completed,
            'orders_cancelled' => $orders_cancelled,
            'total_orders' => $total_orders,
            'total_sales' => $total_sales,
            'avg_order_value' => round($avg_order_value, 2),
            'items_sold' => $items_sold,
            'days_active' => $days_active,
            'last_activity' => $last_activity,
            'cash_orders' => (int)($payments['cash_count'] ?? 0),
            'gcash_orders' => (int)($payments['gcash_count'] ?? 0),
            'paymaya_orders' => (int)($payments['paymaya_count'] ?? 0),
            'cash_sales' => (float)($payments['cash_total'] ?? 0),
            'gcash_sales' => (float)($payments['gcash_total'] ?? 0),
            'paymaya_sales' => (float)($payments['paymaya_total'] ?? 0),
            'is_online' => $is_online ? 1 : 0,
            'active_sessions' => $online_info['active_sessions'] ?? 0,
            'last_online' => $online_info['last_activity'] ?? null,
            'seconds_ago' => $online_info['seconds_ago'] ?? null
        ];
    }

    // Calculate averages
    $total_stats['avg_sale'] = $total_stats['total_orders_created'] + $total_stats['total_orders_completed'] > 0 
        ? round($total_stats['total_sales'] / ($total_stats['total_orders_created'] + $total_stats['total_orders_completed']), 2)
        : 0;
    
    $total_stats['avg_items_per_order'] = $total_stats['total_orders_created'] > 0
        ? round($total_stats['total_items_sold'] / $total_stats['total_orders_created'], 1)
        : 0;

    $response['success'] = true;
    $response['stats'] = $total_stats;
    $response['cashiers'] = $cashier_data;
    $response['online_cashiers'] = $online_cashiers;
    $response['online_ids'] = $online_ids;
    $response['timestamp'] = time();
    $response['datetime'] = date('Y-m-d H:i:s');
    
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Realtime Cashier API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>