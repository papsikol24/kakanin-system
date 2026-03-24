<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if (!$order_id) {
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit;
}

function getEstimatedPreparationTime($pdo, $order_id) {
    $stmt = $pdo->prepare("
        SELECT oi.*, p.price, 
               CASE 
                   WHEN p.price < 10 THEN 'budget'
                   WHEN p.price >= 10 AND p.price < 250 THEN 'regular'
                   WHEN p.price >= 250 THEN 'premium'
                   ELSE 'custom'
               END as category
        FROM tbl_order_items oi
        JOIN tbl_products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
    
    $max_time = 0;
    
    foreach ($items as $item) {
        $stmt = $pdo->prepare("
            SELECT preparation_time 
            FROM tbl_preparation_settings 
            WHERE category = ? 
            ORDER BY is_default DESC 
            LIMIT 1
        ");
        $stmt->execute([$item['category']]);
        $time = $stmt->fetchColumn();
        
        if ($time && $time > $max_time) {
            $max_time = $time;
        }
    }
    
    return $max_time ?: 30;
}

echo json_encode([
    'success' => true,
    'preparation_time' => getEstimatedPreparationTime($pdo, $order_id)
]);