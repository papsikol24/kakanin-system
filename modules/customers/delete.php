<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager', 'cashier']); // Adjust role as needed

$id = $_GET['id'] ?? 0;

// Begin transaction
$pdo->beginTransaction();

try {
    // Set customer_id to NULL in all orders for this customer
    $stmt = $pdo->prepare("UPDATE tbl_orders SET customer_id = NULL WHERE customer_id = ?");
    $stmt->execute([$id]);

    // Now delete the customer
    $stmt = $pdo->prepare("DELETE FROM tbl_customers WHERE id = ?");
    $stmt->execute([$id]);

    $pdo->commit();
    $_SESSION['success'] = "Customer deleted successfully. Orders are now associated with Walk-in.";
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to delete customer: " . $e->getMessage();
}

header('Location: index.php');
exit;