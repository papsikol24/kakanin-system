<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

// Start transaction
$pdo->beginTransaction();

try {
    // Get products that have never been ordered
    $stmt = $pdo->query("
        SELECT id, name FROM tbl_products 
        WHERE id NOT IN (SELECT DISTINCT product_id FROM tbl_order_items)
    ");
    $unusedProducts = $stmt->fetchAll();
    
    if (empty($unusedProducts)) {
        $_SESSION['success'] = "No unused products found to delete.";
        header('Location: index.php');
        exit;
    }
    
    $deletedCount = 0;
    $failedCount = 0;
    $deletedNames = [];
    
    foreach ($unusedProducts as $product) {
        $productId = $product['id'];
        
        // Check if product has inventory logs
        $logCheck = $pdo->prepare("SELECT COUNT(*) FROM tbl_inventory_logs WHERE product_id = ?");
        $logCheck->execute([$productId]);
        $logCount = $logCheck->fetchColumn();
        
        // Check if product is in any carts
        $cartCheck = $pdo->prepare("SELECT COUNT(*) FROM tbl_carts WHERE product_id = ?");
        $cartCheck->execute([$productId]);
        $cartCount = $cartCheck->fetchColumn();
        
        // Delete dependencies first
        if ($logCount > 0) {
            $pdo->prepare("DELETE FROM tbl_inventory_logs WHERE product_id = ?")->execute([$productId]);
        }
        
        if ($cartCount > 0) {
            $pdo->prepare("DELETE FROM tbl_carts WHERE product_id = ?")->execute([$productId]);
        }
        
        // Delete product image if exists
        $imgStmt = $pdo->prepare("SELECT image FROM tbl_products WHERE id = ?");
        $imgStmt->execute([$productId]);
        $image = $imgStmt->fetchColumn();
        
        if ($image) {
            $imagePath = '../../assets/images/' . $image;
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        // Finally delete the product
        $deleteStmt = $pdo->prepare("DELETE FROM tbl_products WHERE id = ?");
        if ($deleteStmt->execute([$productId])) {
            $deletedCount++;
            $deletedNames[] = $product['name'];
        } else {
            $failedCount++;
        }
    }
    
    $pdo->commit();
    
    $message = "✅ Deleted {$deletedCount} unused product(s)";
    if (!empty($deletedNames)) {
        $message .= ": " . implode(", ", array_slice($deletedNames, 0, 5));
        if (count($deletedNames) > 5) {
            $message .= " and " . (count($deletedNames) - 5) . " more";
        }
    }
    
    if ($failedCount > 0) {
        $message .= " (Failed to delete {$failedCount} product(s))";
    }
    
    $_SESSION['success'] = $message;
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to delete products: " . $e->getMessage();
}

header('Location: index.php');
exit;
?>