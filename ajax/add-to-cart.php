<?php
require_once '../includes/config.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set JSON header
header('Content-Type: application/json');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'error' => 'Please login first']);
    exit;
}

// ===== CHECK STORE STATUS =====
try {
    $stmt = $pdo->query("SELECT is_online FROM tbl_store_status WHERE id = 1");
    $store_status = $stmt->fetch();
    if ($store_status && $store_status['is_online'] == 0) {
        echo json_encode(['success' => false, 'error' => 'Store is currently closed. Please try again later.']);
        exit;
    }
} catch (Exception $e) {
    // If table doesn't exist, allow adding to cart (store is considered open)
    error_log("Store status check failed: " . $e->getMessage());
}

// Check if product ID is provided
if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'error' => 'No product specified']);
    exit;
}

$product_id = (int)$_POST['product_id'];

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Get product details
$stmt = $pdo->prepare("SELECT * FROM tbl_products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'error' => 'Product not found']);
    exit;
}

if ($product['stock'] <= 0) {
    echo json_encode(['success' => false, 'error' => 'Product is out of stock']);
    exit;
}

// Define tier rules
function getProductTier($price) {
    if ($price < 10) {
        return ['min' => 20, 'max' => 300];
    } elseif ($price >= 10 && $price < 250) {
        return ['min' => 20, 'max' => 300];
    } elseif ($price >= 250) {
        return ['min' => 1, 'max' => 10];
    } else {
        return ['min' => 20, 'max' => 300];
    }
}

$tier = getProductTier($product['price']);
$currentQty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
$newQty = $currentQty + 1;

// Check if over maximum
if ($newQty > $tier['max']) {
    echo json_encode([
        'success' => false, 
        'error' => "{$product['name']} maximum is {$tier['max']} pieces."
    ]);
    exit;
}

// ===== FIXED: Check if a transaction is already active =====
try {
    // First, check if we can start a transaction
    // Rollback any existing transaction to be safe
    while ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Now start a new transaction
    $pdo->beginTransaction();
    
    // Deduct stock
    $stmt = $pdo->prepare("UPDATE tbl_products SET stock = stock - 1 WHERE id = ? AND stock > 0");
    $stmt->execute([$product_id]);
    
    if ($stmt->rowCount() === 0) {
        throw new Exception("Stock update failed");
    }
    
    // Add to cart session
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id]['quantity']++;
    } else {
        $_SESSION['cart'][$product_id] = [
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => 1,
            'image' => $product['image']
        ];
    }
    
    // Save to database if function exists
    if (function_exists('saveCartToDatabase')) {
        saveCartToDatabase($pdo, $_SESSION['customer_id']);
    }
    
    $pdo->commit();
    
    // Calculate total cart count
    $cartCount = array_sum(array_column($_SESSION['cart'], 'quantity'));
    
    // Prepare response
    $response = [
        'success' => true,
        'cartCount' => $cartCount,
        'newQty' => $newQty,
        'productName' => $product['name']
    ];
    
    // Add warning if below minimum
    if ($newQty < $tier['min']) {
        $response['warning'] = "Minimum {$tier['min']} pieces needed. You need " . ($tier['min'] - $newQty) . " more.";
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback if there's an active transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'error' => 'Failed to add item: ' . $e->getMessage()]);
}
?>