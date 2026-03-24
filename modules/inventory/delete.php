<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    $_SESSION['error'] = "Invalid product ID.";
    header('Location: index.php');
    exit;
}

// Begin transaction for safe deletion
$pdo->beginTransaction();

try {
    // Check if product exists
    $stmt = $pdo->prepare("SELECT * FROM tbl_products WHERE id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();

    if (!$product) {
        throw new Exception("Product not found.");
    }

    // Check for dependencies (orders)
    $check = $pdo->prepare("SELECT COUNT(*) FROM tbl_order_items WHERE product_id = ?");
    $check->execute([$id]);
    $orderCount = $check->fetchColumn();
    
    if ($orderCount > 0) {
        throw new Exception("Cannot delete product '{$product['name']}' because it has {$orderCount} order(s).");
    }

    // Check inventory logs (optional - you may want to keep logs)
    $logCheck = $pdo->prepare("SELECT COUNT(*) FROM tbl_inventory_logs WHERE product_id = ?");
    $logCheck->execute([$id]);
    $logCount = $logCheck->fetchColumn();

    // Delete product image if exists
    if ($product['image']) {
        $imagePath = '../../assets/images/' . $product['image'];
        if (file_exists($imagePath)) {
            if (!unlink($imagePath)) {
                // Log but don't stop deletion - image file may be missing
                error_log("Failed to delete image file: " . $imagePath);
            }
        }
    }

    // Delete inventory logs if any (optional - comment out if you want to keep logs)
    if ($logCount > 0) {
        $delLogs = $pdo->prepare("DELETE FROM tbl_inventory_logs WHERE product_id = ?");
        $delLogs->execute([$id]);
    }

    // Delete the product
    $stmt = $pdo->prepare("DELETE FROM tbl_products WHERE id = ?");
    $stmt->execute([$id]);

    $pdo->commit();
    
    $_SESSION['success'] = "Product '{$product['name']}' deleted successfully.";
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = $e->getMessage();
}

header('Location: index.php');
exit;