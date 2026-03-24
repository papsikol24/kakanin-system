<?php
/**
 * Cart Functions - Handles database cart persistence
 */

/**
 * Load cart from database into session
 */
function loadCartFromDatabase($pdo, $customer_id) {
    if (!$customer_id) return;
    
    $stmt = $pdo->prepare("
        SELECT c.*, p.name, p.price, p.image 
        FROM tbl_carts c
        JOIN tbl_products p ON c.product_id = p.id
        WHERE c.customer_id = ?
    ");
    $stmt->execute([$customer_id]);
    $cart_items = $stmt->fetchAll();
    
    $_SESSION['cart'] = [];
    foreach ($cart_items as $item) {
        $_SESSION['cart'][$item['product_id']] = [
            'name' => $item['name'],
            'price' => $item['price'],
            'quantity' => $item['quantity'],
            'image' => $item['image']
        ];
    }
}

/**
 * Save cart to database
 */
function saveCartToDatabase($pdo, $customer_id) {
    if (!$customer_id || !isset($_SESSION['cart'])) return;
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Clear existing cart
        $stmt = $pdo->prepare("DELETE FROM tbl_carts WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        
        // Insert new cart items
        $stmt = $pdo->prepare("INSERT INTO tbl_carts (customer_id, product_id, quantity) VALUES (?, ?, ?)");
        
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $stmt->execute([$customer_id, $product_id, $item['quantity']]);
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Failed to save cart: " . $e->getMessage());
    }
}

/**
 * Sync cart - load on login, save on changes
 */
function syncCart($pdo, $customer_id) {
    if ($customer_id) {
        loadCartFromDatabase($pdo, $customer_id);
    }
}

/**
 * Clear cart from database
 */
function clearCartFromDatabase($pdo, $customer_id) {
    if (!$customer_id) return;
    
    $stmt = $pdo->prepare("DELETE FROM tbl_carts WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
}
?>