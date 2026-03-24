<?php
// Set default timezone to Philippines/Asia
date_default_timezone_set('Asia/Manila');

// Start session only once
session_start();

// Database configuration for InfinityFree
define('DB_HOST', 'sql301.infinityfree.com');
define('DB_NAME', 'if0_41233935_kakanin_db');
define('DB_USER', 'if0_41233935');
define('DB_PASS', 'FZMBUesWXVr9');

// Site URL configuration
define('SITE_URL', 'https://jenskakanin.infinityfree.me');
define('SITE_PATH', ''); // Empty if in root, or '/kakanin_system' if in subfolder

try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_AUTOCOMMIT, 1);
    
    // Set MySQL timezone to Philippines as well
    $pdo->exec("SET time_zone = '+08:00'");
    
} catch(PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Include auth functions
require_once __DIR__ . '/auth.php';

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
    
    try {
        $stmt = $pdo->prepare("DELETE FROM tbl_carts WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        
        $stmt = $pdo->prepare("INSERT INTO tbl_carts (customer_id, product_id, quantity) VALUES (?, ?, ?)");
        
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $stmt->execute([$customer_id, $product_id, $item['quantity']]);
        }
    } catch (Exception $e) {
        error_log("Failed to save cart: " . $e->getMessage());
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

// Load cart from database if customer is logged in
if (isset($_SESSION['customer_id'])) {
    loadCartFromDatabase($pdo, $_SESSION['customer_id']);
}
?>