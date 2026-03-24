<?php
// Turn off error display
error_reporting(0);
ini_set('display_errors', 0);

// Add cache control headers to prevent browser caching
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
header('Content-Type: application/json');

require_once '../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false];

try {
    // Check if user is logged in as staff
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }

    // Clean up old sessions first (older than 5 minutes)
    $pdo->exec("DELETE FROM tbl_online_customers WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");

    // Get online customers (active in last 5 minutes)
    $stmt = $pdo->query("
        SELECT 
            oc.customer_id,
            oc.customer_name,
            oc.last_activity,
            oc.current_page,
            oc.cart_count,
            TIMESTAMPDIFF(MINUTE, oc.last_activity, NOW()) as minutes_ago,
            CASE 
                WHEN oc.cart_count > 0 THEN '🛒 Shopping'
                ELSE '👀 Browsing'
            END as status,
            DATE_FORMAT(oc.last_activity, '%h:%i %p') as last_seen
        FROM tbl_online_customers oc
        WHERE oc.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY oc.last_activity DESC
    ");
    
    $online_customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $total_online = count($online_customers);
    
    // Get customers with items in cart
    $shopping_count = 0;
    foreach ($online_customers as $c) {
        if ($c['cart_count'] > 0) $shopping_count++;
    }
    
    // Add a unique request ID to prevent caching
    $response['success'] = true;
    $response['online_customers'] = $online_customers;
    $response['total_online'] = $total_online;
    $response['shopping_count'] = $shopping_count;
    $response['timestamp'] = date('h:i:s A');
    $response['request_id'] = uniqid();
    
    echo json_encode($response);

} catch (Exception $e) {
    error_log("Online Customers API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>