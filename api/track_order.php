<?php
require_once '../includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customer_id = $_SESSION['customer_id'];

$stmt = $pdo->prepare("
    SELECT o.*, 
           TIMESTAMPDIFF(MINUTE, o.order_date, NOW()) as minutes_elapsed
    FROM tbl_orders o 
    WHERE o.id = ? AND o.customer_id = ?
");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch();

if (!$order) {
    echo json_encode(['success' => false, 'error' => 'Order not found']);
    exit;
}

// Get timeline data with all preparation timestamps
$timeline = [
    ['status' => 'pending', 'time' => $order['order_date'], 'completed' => true],
    ['status' => 'confirmed', 'time' => $order['confirmed_at'], 'completed' => !is_null($order['confirmed_at'])],
    ['status' => 'preparing', 'time' => $order['preparation_started_at'], 'completed' => !is_null($order['preparation_started_at'])],
    ['status' => 'ready', 'time' => $order['ready_for_pickup_at'], 'completed' => !is_null($order['ready_for_pickup_at'])],
    ['status' => 'out_for_delivery', 'time' => $order['out_for_delivery_at'], 'completed' => !is_null($order['out_for_delivery_at'])],
    ['status' => 'delivered', 'time' => $order['delivered_at'], 'completed' => !is_null($order['delivered_at'])]
];

echo json_encode([
    'success' => true,
    'order' => [
        'id' => $order['id'],
        'status' => $order['status'],
        'minutes_elapsed' => $order['minutes_elapsed'],
        'tracking_number' => $order['tracking_number'],
        'preparation_started_at' => $order['preparation_started_at'],
        'preparation_completed_at' => $order['preparation_completed_at'],
        'confirmed_at' => $order['confirmed_at'],
        'ready_for_pickup_at' => $order['ready_for_pickup_at'],
        'out_for_delivery_at' => $order['out_for_delivery_at'],
        'delivered_at' => $order['delivered_at'],
        'timeline' => $timeline
    ]
]);
?>