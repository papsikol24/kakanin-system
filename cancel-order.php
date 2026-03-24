<?php
require_once 'includes/config.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: customer-login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: customer-dashboard.php');
    exit;
}

$order_id = (int)($_POST['order_id'] ?? 0);
$customer_id = $_SESSION['customer_id'];
$cancellation_reason = trim($_POST['cancellation_reason'] ?? '');
$other_reason = trim($_POST['other_reason'] ?? '');

// Combine reason if "Other" was selected
if ($cancellation_reason === 'Other' && !empty($other_reason)) {
    $cancellation_reason = 'Other: ' . $other_reason;
}

// Validate
if (empty($cancellation_reason)) {
    $_SESSION['error'] = "Please provide a reason for cancellation.";
    header('Location: order-success.php?id=' . $order_id);
    exit;
}

// Verify order belongs to this customer and is pending
$stmt = $pdo->prepare("SELECT * FROM tbl_orders WHERE id = ? AND customer_id = ? AND status = 'pending'");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = "Order not found or cannot be cancelled.";
    header('Location: customer-dashboard.php');
    exit;
}

// Begin transaction
$pdo->beginTransaction();

try {
    // Restore stock for each item
    $items = $pdo->prepare("SELECT * FROM tbl_order_items WHERE order_id = ?");
    $items->execute([$order_id]);
    $order_items = $items->fetchAll();

    foreach ($order_items as $item) {
        // Get current stock
        $stmt = $pdo->prepare("SELECT stock FROM tbl_products WHERE id = ?");
        $stmt->execute([$item['product_id']]);
        $old_stock = $stmt->fetchColumn();
        $new_stock = $old_stock + $item['quantity'];

        // Update stock
        $stmt = $pdo->prepare("UPDATE tbl_products SET stock = ? WHERE id = ?");
        $stmt->execute([$new_stock, $item['product_id']]);

        // Log inventory change
        $stmt = $pdo->prepare("INSERT INTO tbl_inventory_logs (product_id, user_id, change_type, quantity_changed, previous_stock, new_stock) VALUES (?, NULL, 'add', ?, ?, ?)");
        $stmt->execute([$item['product_id'], $item['quantity'], $old_stock, $new_stock]);
    }

    // Update order status to cancelled with reason
    $stmt = $pdo->prepare("UPDATE tbl_orders SET status = 'cancelled', cancellation_reason = ? WHERE id = ?");
    $stmt->execute([$cancellation_reason, $order_id]);

    // Create notification for customer
    $notif_stmt = $pdo->prepare("INSERT INTO tbl_notifications (customer_id, order_id, title, message, type) VALUES (?, ?, ?, ?, 'order_update')");
    $notif_stmt->execute([
        $customer_id,
        $order_id,
        "Order #{$order_id} Cancelled",
        "Your order #{$order_id} has been cancelled. Reason: " . $cancellation_reason
    ]);

    $pdo->commit();
    $_SESSION['success'] = "Order #{$order_id} has been cancelled.";

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to cancel order: " . $e->getMessage();
}

header('Location: order-success.php?id=' . $order_id);
exit;