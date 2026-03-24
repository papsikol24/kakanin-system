<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

// Delete all order items first (foreign key)
$pdo->exec("DELETE FROM tbl_order_items");
// Then delete all orders
$pdo->exec("DELETE FROM tbl_orders");

$_SESSION['success'] = "All orders have been permanently deleted.";
header('Location: index.php');
exit;