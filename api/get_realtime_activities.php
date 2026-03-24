<?php
// Turn off error display
error_reporting(0);
ini_set('display_errors', 0);

require_once '../includes/config.php';

header('Content-Type: application/json');

$response = ['success' => false];

try {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in as staff
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }
    
    $last_id = (int)($_GET['last_id'] ?? 0);
    
    // Get new activities since last_id
    $query = "
        SELECT 
            'order' as type,
            o.id as reference_id,
            o.order_date as date_time,
            u.username as cashier,
            CONCAT('Processed Order #', o.id, ' for ₱', o.total_amount) as description,
            o.total_amount as amount,
            o.payment_method,
            o.status,
            o.id as activity_id
        FROM tbl_orders o
        JOIN tbl_users u ON o.created_by = u.id
        WHERE o.id > ? AND u.role = 'cashier'
        
        UNION ALL
        
        SELECT 
            'inventory' as type,
            l.id as reference_id,
            l.log_time as date_time,
            u.username as cashier,
            CONCAT(l.change_type, ' ', l.quantity_changed, ' ', p.name) as description,
            NULL as amount,
            NULL as payment_method,
            NULL as status,
            (l.id + 1000000) as activity_id
        FROM tbl_inventory_logs l
        JOIN tbl_products p ON l.product_id = p.id
        JOIN tbl_users u ON l.user_id = u.id
        WHERE l.id > ? AND u.role = 'cashier'
        
        ORDER BY date_time DESC
        LIMIT 20
    ";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$last_id, $last_id]);
    $new_activities = $stmt->fetchAll();
    
    // Get max ID from new activities
    $max_id = $last_id;
    if (!empty($new_activities)) {
        $ids = array_column($new_activities, 'activity_id');
        $max_id = max($ids);
    }
    
    $response['success'] = true;
    $response['new_activities'] = $new_activities;
    $response['max_id'] = $max_id;
    $response['timestamp'] = date('h:i:s A');
    $response['count'] = count($new_activities);
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Realtime Activities API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>