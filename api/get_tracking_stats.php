<?php
require_once '../includes/config.php';
requireLogin();

header('Content-Type: application/json');

try {
    $today = date('Y-m-d');
    
    $stats = [
        'preparing' => $pdo->query("SELECT COUNT(*) FROM tbl_orders WHERE status = 'preparing'")->fetchColumn(),
        'ready' => $pdo->query("SELECT COUNT(*) FROM tbl_orders WHERE status = 'ready'")->fetchColumn(),
        'delivery' => $pdo->query("SELECT COUNT(*) FROM tbl_orders WHERE status = 'out_for_delivery'")->fetchColumn(),
        'completed_today' => $pdo->query("SELECT COUNT(*) FROM tbl_orders WHERE status = 'delivered' AND DATE(order_date) = CURDATE()")->fetchColumn()
    ];
    
    echo json_encode(['success' => true, ...$stats]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>