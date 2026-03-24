<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

// Delete logs older than 30 days
$pdo->exec("DELETE FROM tbl_inventory_logs WHERE log_time < DATE_SUB(NOW(), INTERVAL 30 DAY)");

$_SESSION['success'] = "Inventory logs older than 30 days cleared.";
header('Location: index.php');
exit;