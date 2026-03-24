<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

// Set all orders' customer_id to NULL
$pdo->exec("UPDATE tbl_orders SET customer_id = NULL WHERE customer_id IS NOT NULL");

// Delete all customers
$pdo->exec("DELETE FROM tbl_customers");

// Redirect with success message
$_SESSION['success'] = "All customers have been deleted. Orders are now associated with Walk-in.";
header('Location: index.php');
exit;