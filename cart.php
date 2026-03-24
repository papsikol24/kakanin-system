<?php
require_once 'includes/config.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: /login');
    exit;
}

// ===== CHECK STORE STATUS =====
$store_online = true;
$offline_message = 'Store is currently closed. Please check back later.';

try {
    $stmt = $pdo->query("SELECT is_online, offline_message FROM tbl_store_status WHERE id = 1");
    $store_status = $stmt->fetch();
    if ($store_status) {
        $store_online = (bool)$store_status['is_online'];
        $offline_message = $store_status['offline_message'] ?? 'Store is currently closed. Please check back later.';
    }
} catch (Exception $e) {
    // Table might not exist yet, assume store is open
    error_log("Store status check failed: " . $e->getMessage());
}

// ===== TIER-BASED FUNCTIONS =====

/**
 * Get product tier information
 */
function getProductTier($price) {
    if ($price < 10) {
        return [
            'name' => 'Budget',
            'color' => 'budget',
            'icon' => 'fa-tag',
            'bg' => '#28a745',
            'min' => 20,
            'max' => 300
        ];
    } elseif ($price >= 10 && $price < 250) {
        return [
            'name' => 'Regular',
            'color' => 'regular',
            'icon' => 'fa-box',
            'bg' => '#ffc107',
            'min' => 20,
            'max' => 300
        ];
    } else {
        return [
            'name' => 'Premium',
            'color' => 'premium',
            'icon' => 'fa-crown',
            'bg' => '#dc3545',
            'min' => 1,
            'max' => 10
        ];
    }
}

/**
 * Check if cart contains ANY premium items (₱250+)
 */
function cartHasPremiumItems($cart) {
    foreach ($cart as $item) {
        if ($item['price'] >= 250) {
            return true;
        }
    }
    return false;
}

/**
 * Get the appropriate maximum order based on cart contents
 */
function getCartMaximum($cart) {
    if (cartHasPremiumItems($cart)) {
        return 999999; // NO MAXIMUM LIMIT for premium items
    } else {
        return 300; // Only budget/regular items: maximum 300 pieces
    }
}

/**
 * Calculate total pieces in cart
 */
function getTotalPieces($cart) {
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['quantity'];
    }
    return $total;
}

/**
 * Check if adding an item would exceed maximum limit
 */
function wouldExceedMaximum($currentCart, $productId, $quantityToAdd, $productPrice) {
    $tempCart = $currentCart;
    
    // Create a temporary cart to calculate new total
    if (isset($tempCart[$productId])) {
        $tempCart[$productId]['quantity'] += $quantityToAdd;
    } else {
        $tempCart[$productId] = [
            'quantity' => $quantityToAdd,
            'price' => $productPrice
        ];
    }
    
    $newTotal = getTotalPieces($tempCart);
    $maxAllowed = getCartMaximum($tempCart);
    
    return $newTotal > $maxAllowed;
}

// Function to save cart after any modification
function saveCartAfterChange($pdo) {
    if (isset($_SESSION['customer_id'])) {
        saveCartToDatabase($pdo, $_SESSION['customer_id']);
    }
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ===== ALL POST HANDLING MUST BE BEFORE ANY OUTPUT =====
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if store is open before allowing cart modifications
    if (!$store_online && !isset($_POST['remove']) && !isset($_POST['clear_all'])) {
        $_SESSION['error'] = "Store is currently closed. You cannot modify your cart at this time.";
        header('Location: /cart');
        exit;
    }
    
    if (isset($_POST['update'])) {
        foreach ($_POST['quantity'] as $id => $qty) {
            $id = (int)$id;
            $qty = (int)$qty;
            
            // Get current stock and product details
            $stmt = $pdo->prepare("SELECT stock, price, name FROM tbl_products WHERE id = ?");
            $stmt->execute([$id]);
            $product = $stmt->fetch();
            
            if (!$product) continue;
            
            $current_stock = $product['stock'];
            $old_qty = $_SESSION['cart'][$id]['quantity'] ?? 0;
            
            if ($qty <= 0) {
                // Return stock to inventory
                $stmt = $pdo->prepare("UPDATE tbl_products SET stock = stock + ? WHERE id = ?");
                $stmt->execute([$old_qty, $id]);
                unset($_SESSION['cart'][$id]);
                
                // Log inventory change
                $log = $pdo->prepare("INSERT INTO tbl_inventory_logs (product_id, user_id, change_type, quantity_changed, previous_stock, new_stock) VALUES (?, NULL, 'add', ?, ?, ?)");
                $log->execute([$id, $old_qty, $current_stock, $current_stock + $old_qty]);
                
            } else {
                // Calculate stock difference
                $diff = $qty - $old_qty;
                
                if ($diff > 0) {
                    // Check if adding would exceed maximum
                    if (wouldExceedMaximum($_SESSION['cart'], $id, $diff, $product['price'])) {
                        $_SESSION['error'] = "Cannot increase quantity. This would exceed the maximum order limit.";
                        continue;
                    }
                    
                    // Adding more - check if enough stock
                    if ($diff <= $current_stock) {
                        $stmt = $pdo->prepare("UPDATE tbl_products SET stock = stock - ? WHERE id = ?");
                        $stmt->execute([$diff, $id]);
                        $_SESSION['cart'][$id]['quantity'] = $qty;
                        
                        // Log inventory change
                        $log = $pdo->prepare("INSERT INTO tbl_inventory_logs (product_id, user_id, change_type, quantity_changed, previous_stock, new_stock) VALUES (?, NULL, 'subtract', ?, ?, ?)");
                        $log->execute([$id, $diff, $current_stock, $current_stock - $diff]);
                        
                    } else {
                        $_SESSION['error'] = "Not enough stock available for {$product['name']}.";
                    }
                } else if ($diff < 0) {
                    // Reducing quantity - return stock
                    $return_qty = abs($diff);
                    $stmt = $pdo->prepare("UPDATE tbl_products SET stock = stock + ? WHERE id = ?");
                    $stmt->execute([$return_qty, $id]);
                    $_SESSION['cart'][$id]['quantity'] = $qty;
                    
                    // Log inventory change
                    $log = $pdo->prepare("INSERT INTO tbl_inventory_logs (product_id, user_id, change_type, quantity_changed, previous_stock, new_stock) VALUES (?, NULL, 'add', ?, ?, ?)");
                    $log->execute([$id, $return_qty, $current_stock, $current_stock + $return_qty]);
                }
            }
        }
        saveCartAfterChange($pdo);
        header('Location: /cart');
        exit;
    }
    
    if (isset($_POST['remove'])) {
        $id = (int)$_POST['remove'];
        
        // Get current quantity and stock
        $qty = $_SESSION['cart'][$id]['quantity'] ?? 0;
        
        if ($qty > 0) {
            $stmt = $pdo->prepare("SELECT stock FROM tbl_products WHERE id = ?");
            $stmt->execute([$id]);
            $current_stock = $stmt->fetchColumn();
            
            // Return stock to inventory
            $stmt = $pdo->prepare("UPDATE tbl_products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$qty, $id]);
            
            // Log inventory change
            $log = $pdo->prepare("INSERT INTO tbl_inventory_logs (product_id, user_id, change_type, quantity_changed, previous_stock, new_stock) VALUES (?, NULL, 'add', ?, ?, ?)");
            $log->execute([$id, $qty, $current_stock, $current_stock + $qty]);
        }
        
        unset($_SESSION['cart'][$id]);
        saveCartAfterChange($pdo);
        header('Location: /cart');
        exit;
    }
    
    if (isset($_POST['clear_all'])) {
        // Return all stock to inventory
        foreach ($_SESSION['cart'] as $id => $item) {
            $qty = $item['quantity'];
            $stmt = $pdo->prepare("SELECT stock FROM tbl_products WHERE id = ?");
            $stmt->execute([$id]);
            $current_stock = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("UPDATE tbl_products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$qty, $id]);
            
            $log = $pdo->prepare("INSERT INTO tbl_inventory_logs (product_id, user_id, change_type, quantity_changed, previous_stock, new_stock) VALUES (?, NULL, 'add', ?, ?, ?)");
            $log->execute([$id, $qty, $current_stock, $current_stock + $qty]);
        }
        
        $_SESSION['cart'] = [];
        saveCartAfterChange($pdo);
        $_SESSION['success'] = "Your cart has been cleared.";
        header('Location: /cart');
        exit;
    }
    
    if (isset($_POST['checkout'])) {
        if (!$store_online) {
            $_SESSION['error'] = "Store is currently closed. Please try again later.";
            header('Location: /cart');
            exit;
        }
        header('Location: /checkout');
        exit;
    }
}

// NOW it's safe to include the header
include 'includes/customer_header.php';

// Calculate total
$total = 0;
foreach ($_SESSION['cart'] as $id => $item) {
    $total += $item['price'] * $item['quantity'];
}

// Fetch ALL products with current stock and group by tier
$all_products = $pdo->query("
    SELECT id, name, stock, price, image 
    FROM tbl_products 
    WHERE stock > 0
    ORDER BY price ASC, name ASC
")->fetchAll();

// Group products by tier
$budget_products = [];
$regular_products = [];
$premium_products = [];

foreach ($all_products as $product) {
    $tier = getProductTier($product['price']);
    if ($tier['name'] == 'Budget') {
        $budget_products[] = $product;
    } elseif ($tier['name'] == 'Regular') {
        $regular_products[] = $product;
    } else {
        $premium_products[] = $product;
    }
}

// Get current cart totals for display
$currentTotalPieces = getTotalPieces($_SESSION['cart']);
$cartHasPremium = cartHasPremiumItems($_SESSION['cart']);
$maxAllowed = getCartMaximum($_SESSION['cart']);
$minAllowed = $cartHasPremium ? 1 : 20;

// Calculate cart count for badge
$cartCount = !empty($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>

<style>
    /* ===== GLOBAL STYLES ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }

    .container {
        width: 100%;
        padding-right: 12px;
        padding-left: 12px;
        margin-right: auto;
        margin-left: auto;
        max-width: 1400px;
    }

    /* ===== STORE STATUS BANNER ===== */
    .store-status-banner {
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 12px;
        animation: slideDown 0.5s ease;
    }

    .store-status-banner.online {
        background: #d4edda;
        border-left: 4px solid #28a745;
        color: #155724;
    }

    .store-status-banner.offline {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
        color: #721c24;
    }

    .store-status-banner i {
        font-size: 1.5rem;
    }

    .store-status-banner .status-text {
        flex: 1;
    }

    .store-status-banner .status-text h5 {
        font-weight: 600;
        margin-bottom: 3px;
        font-size: 1rem;
    }

    .store-status-banner .status-text p {
        margin: 0;
        font-size: 0.85rem;
        opacity: 0.9;
    }

    .store-status-banner .realtime-indicator {
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255,255,255,0.3);
        padding: 4px 8px;
        border-radius: 50px;
        margin-left: 10px;
    }

    .store-status-banner .realtime-indicator i {
        font-size: 0.6rem;
        animation: spin 2s linear infinite;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .page-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .page-title i {
        color: #008080;
    }

    /* Cart Limit Info */
    .cart-limit-info {
        background: #e8f4f4;
        border-left: 4px solid #008080;
        padding: 0.8rem 1.2rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 0.95rem;
    }

    .cart-limit-info i {
        font-size: 1.3rem;
        color: #008080;
    }

    .cart-limit-info .info-text {
        flex: 1;
    }

    .cart-limit-info .info-text strong {
        color: #008080;
        font-size: 1rem;
    }

    .cart-limit-warning {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 0.8rem 1.2rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        font-size: 0.95rem;
    }

    .cart-limit-warning i {
        font-size: 1.3rem;
        color: #856404;
    }

    /* ===== CART ITEMS GRID ===== */
    .cart-items-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.8rem;
        margin-bottom: 1.5rem;
    }
    
    @media (min-width: 768px) {
        .cart-items-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 1.2rem;
        }
    }
    
    @media (min-width: 992px) {
        .cart-items-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }
    }
    
    @media (min-width: 1200px) {
        .cart-items-grid {
            grid-template-columns: repeat(5, 1fr);
            gap: 1.8rem;
        }
    }

    .cart-item-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border: 1px solid #f0f0f0;
        display: flex;
        flex-direction: column;
        position: relative;
        transition: transform 0.2s, box-shadow 0.2s;
        height: 100%;
    }

    .cart-item-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }

    .cart-item-card.premium {
        border-top: 4px solid #dc3545;
    }
    
    .cart-item-card.regular {
        border-top: 4px solid #ffc107;
    }
    
    .cart-item-card.budget {
        border-top: 4px solid #28a745;
    }

    .cart-item-image {
        width: 100%;
        height: 120px;
        overflow: hidden;
        border-bottom: 1px solid #eee;
    }
    
    @media (min-width: 768px) {
        .cart-item-image {
            height: 150px;
        }
    }

    .cart-item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.3s;
    }

    .cart-item-card:hover .cart-item-image img {
        transform: scale(1.05);
    }

    .cart-item-image .placeholder {
        width: 100%;
        height: 100%;
        background: #f0f0f0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
        font-size: 2rem;
    }

    .cart-item-details {
        padding: 0.8rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .cart-item-name {
        font-weight: 600;
        color: #333;
        font-size: 0.85rem;
        margin-bottom: 0.3rem;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    @media (min-width: 768px) {
        .cart-item-name {
            font-size: 1rem;
        }
    }

    .cart-item-price {
        font-weight: 500;
        color: #008080;
        font-size: 0.8rem;
        margin-bottom: 0.4rem;
    }
    
    @media (min-width: 768px) {
        .cart-item-price {
            font-size: 0.95rem;
        }
    }

    .cart-item-quantity-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin: 0.4rem 0;
    }

    .cart-item-qty-input {
        width: 55px;
        padding: 0.3rem;
        border: 1px solid #ddd;
        border-radius: 50px;
        text-align: center;
        font-size: 0.75rem;
        font-family: 'Poppins', sans-serif;
    }
    
    @media (min-width: 768px) {
        .cart-item-qty-input {
            width: 70px;
            padding: 0.4rem;
            font-size: 0.85rem;
        }
    }

    .cart-item-subtotal {
        font-weight: 600;
        color: #333;
        font-size: 0.8rem;
    }
    
    @media (min-width: 768px) {
        .cart-item-subtotal {
            font-size: 0.95rem;
        }
    }

    .cart-item-actions {
        display: flex;
        gap: 0.4rem;
        margin-top: 0.5rem;
    }

    .btn-cart-item {
        flex: 1;
        padding: 0.4rem 0;
        border: none;
        border-radius: 50px;
        font-size: 0.7rem;
        cursor: pointer;
        transition: all 0.2s;
        text-align: center;
        font-weight: 500;
        -webkit-tap-highlight-color: transparent;
    }
    
    @media (min-width: 768px) {
        .btn-cart-item {
            padding: 0.5rem 0;
            font-size: 0.8rem;
        }
    }

    .btn-cart-item:active {
        transform: scale(0.95);
    }

    .btn-cart-item:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-update-item {
        background: #008080;
        color: white;
    }

    .btn-update-item:hover:not(:disabled) {
        background: #006666;
    }

    .btn-remove-item {
        background: #dc3545;
        color: white;
    }

    .btn-remove-item:hover:not(:disabled) {
        background: #bb2d3b;
    }

    /* Tier badge on cart items */
    .item-tier-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        color: white;
        font-size: 0.6rem;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        z-index: 2;
        box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    .item-tier-badge.budget {
        background: #28a745;
    }
    
    .item-tier-badge.regular {
        background: #ffc107;
        color: #333;
    }
    
    .item-tier-badge.premium {
        background: #dc3545;
    }
    
    @media (min-width: 768px) {
        .item-tier-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.6rem;
        }
    }

    .tier-minmax-info {
        font-size: 0.6rem;
        color: #666;
        margin: 0.3rem 0;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        background: #f8f9fa;
        padding: 0.2rem 0.4rem;
        border-radius: 50px;
    }
    
    .tier-minmax-info i.budget { color: #28a745; }
    .tier-minmax-info i.regular { color: #ffc107; }
    .tier-minmax-info i.premium { color: #dc3545; }
    
    @media (min-width: 768px) {
        .tier-minmax-info {
            font-size: 0.7rem;
        }
    }

    /* Update All Button */
    .btn-update-all {
        background: #008080;
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.7rem 2rem;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.2s;
        margin: 1rem 0 2rem;
        display: inline-block;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
    }
    
    @media (min-width: 768px) {
        .btn-update-all {
            padding: 0.8rem 2.5rem;
            font-size: 1rem;
        }
    }

    .btn-update-all:hover:not(:disabled) {
        background: #006666;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,128,128,0.3);
    }

    .btn-update-all:active {
        transform: scale(0.98);
    }

    .btn-update-all:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-clear-all {
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.7rem 2rem;
        font-weight: 500;
        font-size: 0.9rem;
        transition: all 0.2s;
        margin: 1rem 0 2rem;
        display: inline-block;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
        margin-left: 1rem;
    }
    
    @media (min-width: 768px) {
        .btn-clear-all {
            padding: 0.8rem 2.5rem;
            font-size: 1rem;
        }
    }

    .btn-clear-all:hover:not(:disabled) {
        background: #bb2d3b;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(220,53,69,0.3);
    }

    .btn-clear-all:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* ===== TIER SECTIONS IN ADD MORE ITEMS ===== */
    .tier-section {
        margin-bottom: 2.5rem;
    }

    .tier-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.2rem;
        padding: 0.7rem 1.2rem;
        border-radius: 10px;
        font-weight: 600;
    }

    .tier-header.budget {
        background: #e8f5e9;
        color: #28a745;
        border-left: 4px solid #28a745;
    }

    .tier-header.regular {
        background: #fff3e0;
        color: #ffc107;
        border-left: 4px solid #ffc107;
    }

    .tier-header.premium {
        background: #ffebee;
        color: #dc3545;
        border-left: 4px solid #dc3545;
    }

    .tier-header i {
        font-size: 1.3rem;
    }

    .tier-header span {
        font-size: 1.1rem;
        font-weight: 600;
    }
    
    @media (min-width: 992px) {
        .tier-header span {
            font-size: 1.2rem;
        }
    }

    .tier-count {
        margin-left: auto;
        background: rgba(0,0,0,0.05);
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 500;
    }

    /* ===== QUICK ADD GRID ===== */
    .quick-add-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.8rem;
        margin-bottom: 0.5rem;
    }
    
    @media (min-width: 768px) {
        .quick-add-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
        }
    }
    
    @media (min-width: 992px) {
        .quick-add-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 1.2rem;
        }
    }

    .product-option-card {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 0.8rem;
        transition: all 0.2s;
        border: 1px solid #eee;
        display: flex;
        flex-direction: column;
        position: relative;
        height: 100%;
    }

    .product-option-card:hover {
        background: #e9ecef;
        transform: translateY(-3px);
        border-color: #ff8c00;
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    /* Tier border colors */
    .product-option-card.budget {
        border-left: 4px solid #28a745;
    }

    .product-option-card.regular {
        border-left: 4px solid #ffc107;
    }

    .product-option-card.premium {
        border-left: 4px solid #dc3545;
    }

    .product-option-card.in-cart {
        background: #e8f4f4;
    }

    .product-option-card.out-of-stock {
        opacity: 0.6;
        background: #f5f5f5;
    }

    /* Tier badges on product cards */
    .product-tier-badge {
        position: absolute;
        top: 5px;
        left: 5px;
        color: white;
        font-size: 0.6rem;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        z-index: 2;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    .product-tier-badge.budget {
        background: #28a745;
    }
    
    .product-tier-badge.regular {
        background: #ffc107;
        color: #333;
    }
    
    .product-tier-badge.premium {
        background: #dc3545;
    }
    
    @media (min-width: 768px) {
        .product-tier-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.6rem;
        }
    }

    .in-cart-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #008080;
        color: white;
        font-size: 0.6rem;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        z-index: 2;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    @media (min-width: 768px) {
        .in-cart-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.6rem;
        }
    }

    .out-of-stock-badge {
        position: absolute;
        top: 5px;
        right: 5px;
        background: #dc3545;
        color: white;
        font-size: 0.6rem;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
    }
    
    @media (min-width: 768px) {
        .out-of-stock-badge {
            font-size: 0.7rem;
            padding: 0.25rem 0.6rem;
        }
    }

    .no-limit-badge {
        position: absolute;
        top: 40px;
        left: 5px;
        background: #28a745;
        color: white;
        font-size: 0.6rem;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    
    @media (min-width: 768px) {
        .no-limit-badge {
            top: 45px;
            font-size: 0.7rem;
            padding: 0.25rem 0.6rem;
        }
    }

    .product-option-image {
        width: 100%;
        height: 90px;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 0.6rem;
        border: 1px solid #008080;
    }
    
    @media (min-width: 768px) {
        .product-option-image {
            height: 120px;
        }
    }

    .product-option-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .product-option-image .placeholder {
        width: 100%;
        height: 100%;
        background: #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #666;
        font-size: 1.5rem;
    }

    .product-option-name {
        font-weight: 600;
        color: #333;
        font-size: 0.75rem;
        margin-bottom: 0.3rem;
        line-height: 1.3;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    
    @media (min-width: 768px) {
        .product-option-name {
            font-size: 0.9rem;
        }
    }

    .product-option-price {
        font-weight: 600;
        color: #008080;
        font-size: 0.8rem;
        margin-bottom: 0.3rem;
    }
    
    @media (min-width: 768px) {
        .product-option-price {
            font-size: 1rem;
        }
    }

    .product-option-stock {
        font-size: 0.65rem;
        color: #28a745;
        margin-bottom: 0.4rem;
    }
    
    @media (min-width: 768px) {
        .product-option-stock {
            font-size: 0.75rem;
        }
    }

    .product-option-stock.low-stock {
        color: #dc3545;
    }

    .product-option-stock.out-of-stock {
        color: #dc3545;
        font-weight: 600;
    }

    .btn-add-mini {
        background: #ff8c00;
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.4rem 0;
        font-size: 0.65rem;
        cursor: pointer;
        transition: all 0.2s;
        width: 100%;
        margin-top: 0.3rem;
        font-weight: 500;
        -webkit-tap-highlight-color: transparent;
        position: relative;
    }
    
    .btn-add-mini.loading {
        position: relative;
        color: transparent !important;
        pointer-events: none;
    }
    
    .btn-add-mini.loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.6s linear infinite;
    }
    
    .btn-add-mini.success {
        background: #28a745;
    }
    
    .btn-add-mini.disabled {
        background: #6c757d;
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .btn-add-mini:active {
        transform: scale(0.95);
    }
    
    @media (min-width: 768px) {
        .btn-add-mini {
            padding: 0.5rem 0;
            font-size: 0.75rem;
        }
    }

    .btn-add-mini:hover:not(:disabled) {
        background: #e07b00;
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(255,140,0,0.3);
    }

    .btn-add-mini:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .continue-shopping {
        display: inline-block;
        color: #008080;
        text-decoration: none;
        font-weight: 500;
        font-size: 0.9rem;
        margin-top: 1rem;
        transition: all 0.2s;
        padding: 0.5rem 0;
        -webkit-tap-highlight-color: transparent;
    }

    .continue-shopping:hover {
        color: #20b2aa;
        transform: translateX(-5px);
    }

    .continue-shopping:active {
        transform: translateX(-2px);
    }

    .continue-shopping i {
        margin-right: 0.5rem;
    }

    /* ===== ORDER SUMMARY ===== */
    .order-summary-container {
        margin: 1.5rem 0 2rem;
    }

    .order-summary {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        max-width: 100%;
        margin: 0 auto;
    }
    
    @media (min-width: 768px) {
        .order-summary {
            max-width: 450px;
            padding: 1.8rem;
        }
    }

    .order-summary h5 {
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
        padding-bottom: 0.6rem;
        border-bottom: 2px solid #008080;
        font-size: 1.1rem;
    }
    
    @media (min-width: 768px) {
        .order-summary h5 {
            font-size: 1.2rem;
        }
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        color: #666;
        font-size: 0.9rem;
    }
    
    @media (min-width: 768px) {
        .summary-row {
            font-size: 1rem;
            margin-bottom: 0.6rem;
        }
    }

    .summary-total {
        font-weight: 700;
        color: #333;
        font-size: 1.1rem;
        margin-top: 0.6rem;
        padding-top: 0.6rem;
        border-top: 1px solid #ddd;
    }
    
    @media (min-width: 768px) {
        .summary-total {
            font-size: 1.3rem;
        }
    }

    .btn-checkout {
        background: linear-gradient(135deg, #008080, #20b2aa);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.7rem;
        font-weight: 600;
        font-size: 0.95rem;
        width: 100%;
        transition: all 0.3s;
        margin-top: 1rem;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
    }
    
    @media (min-width: 768px) {
        .btn-checkout {
            padding: 0.8rem;
            font-size: 1rem;
        }
    }

    .btn-checkout:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,128,128,0.4);
    }

    .btn-checkout:active {
        transform: scale(0.98);
    }

    .btn-checkout:disabled {
        background: #ccc;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    /* ===== CART BADGE ===== */
    .badge-cart {
        background: #dc3545;
        color: white;
        border-radius: 50px;
        padding: 0.2rem 0.5rem;
        font-size: 0.7rem;
        margin-left: 0.3rem;
        display: inline-block;
    }
    
    @keyframes badgePop {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    
    .badge-cart.updated {
        animation: badgePop 0.3s ease;
    }

    /* ===== EMPTY CART ===== */
    .empty-cart {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.03);
    }

    .empty-cart i {
        font-size: 3.5rem;
        color: #ddd;
        margin-bottom: 1rem;
    }
    
    @media (min-width: 768px) {
        .empty-cart i {
            font-size: 4.5rem;
        }
    }

    .empty-cart h3 {
        font-weight: 600;
        color: #333;
        margin-bottom: 0.5rem;
        font-size: 1.3rem;
    }
    
    @media (min-width: 768px) {
        .empty-cart h3 {
            font-size: 1.8rem;
        }
    }

    .empty-cart p {
        color: #999;
        margin-bottom: 1.5rem;
        font-size: 0.95rem;
    }

    .btn-browse {
        background: linear-gradient(135deg, #008080, #20b2aa);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.7rem 2rem;
        font-weight: 600;
        font-size: 0.9rem;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        -webkit-tap-highlight-color: transparent;
    }
    
    @media (min-width: 768px) {
        .btn-browse {
            padding: 0.8rem 2.5rem;
            font-size: 1rem;
        }
    }

    .btn-browse:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,128,128,0.4);
        color: white;
    }

    .btn-browse:active {
        transform: scale(0.98);
    }

    /* ===== ALERTS ===== */
    .alert {
        padding: 1rem;
        border-radius: 10px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
    }
    
    @media (min-width: 768px) {
        .alert {
            font-size: 1rem;
        }
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-warning {
        background: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    /* ===== QUICK ADD SECTION ===== */
    .quick-add-section {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin: 2rem 0 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-top: 4px solid #ff8c00;
    }
    
    @media (min-width: 768px) {
        .quick-add-section {
            padding: 2rem;
        }
    }

    .quick-add-title {
        font-size: 1.3rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    @media (min-width: 768px) {
        .quick-add-title {
            font-size: 1.5rem;
            margin-bottom: 2rem;
        }
    }

    .quick-add-title i {
        color: #ff8c00;
    }

    /* Toast notification */
    .toast-notification {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #008080;
        color: white;
        padding: 1rem 1.5rem;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        z-index: 9999;
        animation: slideInRight 0.3s ease;
        max-width: 300px;
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    .toast-notification.fade-out {
        animation: fadeOut 0.3s ease forwards;
    }

    @keyframes fadeOut {
        to {
            opacity: 0;
            transform: translateY(20px);
        }
    }

    /* ===== TOUCH OPTIMIZATIONS ===== */
    .btn-cart-item,
    .btn-update-all,
    .btn-clear-all,
    .btn-add-mini,
    .btn-checkout,
    .btn-browse,
    .continue-shopping {
        -webkit-tap-highlight-color: transparent;
    }

    .btn-cart-item:active,
    .btn-update-all:active,
    .btn-clear-all:active,
    .btn-add-mini:active,
    .btn-checkout:active,
    .btn-browse:active {
        transform: scale(0.95);
    }
</style>

<!-- Toast notification container -->
<div id="toastContainer" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;"></div>

<div class="container mt-3">
    <!-- ===== STORE STATUS BANNER ===== -->
    <div class="store-status-banner <?php echo $store_online ? 'online' : 'offline'; ?>" id="storeStatusBanner">
        <i class="fas <?php echo $store_online ? 'fa-store' : 'fa-store-slash'; ?>"></i>
        <div class="status-text">
            <h5>
                <?php echo $store_online ? 'Store is OPEN' : 'Store is CLOSED'; ?>
                <span class="realtime-indicator" id="storeRealtimeIndicator">
                    <i class="fas fa-sync-alt fa-spin"></i> live
                </span>
            </h5>
            <p id="storeStatusMessage"><?php echo htmlspecialchars($offline_message); ?></p>
        </div>
    </div>

    <!-- Store Closed Warning (if closed and cart has items) -->
    <?php if (!$store_online && !empty($_SESSION['cart'])): ?>
    <div class="alert alert-warning mb-3" id="storeClosedWarning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Store is currently closed:</strong> <?php echo htmlspecialchars($offline_message); ?>
        <br><small>You can still view your cart, but checkout is disabled until the store opens.</small>
    </div>
    <?php endif; ?>

    <h1 class="page-title">
        <i class="fas fa-shopping-cart"></i>
        Your Cart
    </h1>

    <!-- Cart Limit Information -->
    <?php if (!empty($_SESSION['cart'])): ?>
        <?php if ($cartHasPremium): ?>
            <div class="cart-limit-info">
                <i class="fas fa-crown"></i>
                <div class="info-text">
                    <strong>Premium Items - NO LIMIT!</strong>
                    <p>Current: <?php echo $currentTotalPieces; ?> pieces</p>
                </div>
            </div>
        <?php else: ?>
            <div class="cart-limit-info">
                <i class="fas fa-box"></i>
                <div class="info-text">
                    <strong>Regular Items</strong>
                    <p>Max: <?php echo $maxAllowed; ?> | Min: <?php echo $minAllowed; ?> | Current: <?php echo $currentTotalPieces; ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$cartHasPremium && $currentTotalPieces > $maxAllowed): ?>
            <div class="cart-limit-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="warning-text">
                    <strong>Maximum Exceeded!</strong>
                    <p>Remove <?php echo $currentTotalPieces - $maxAllowed; ?> piece(s)</p>
                </div>
            </div>
        <?php elseif (!$cartHasPremium && $currentTotalPieces < $minAllowed): ?>
            <div class="cart-limit-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="warning-text">
                    <strong>Minimum Not Met</strong>
                    <p>Need <?php echo $minAllowed - $currentTotalPieces; ?> more</p>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($_SESSION['cart'])): ?>
        <!-- EMPTY CART -->
        <div class="empty-cart">
            <i class="fas fa-shopping-cart"></i>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven't added any products yet.</p>
            <a href="/menu" class="btn-browse">
                <i class="fas fa-utensils me-2"></i>Browse Menu
            </a>
        </div>
    <?php else: ?>
        <!-- CART ITEMS GRID -->
        <form method="post" id="cartForm">
            <div class="cart-items-grid">
                <?php foreach ($_SESSION['cart'] as $id => $item): 
                    $tier = getProductTier($item['price']);
                ?>
                <div class="cart-item-card <?php echo $tier['color']; ?>">
                    <span class="item-tier-badge <?php echo $tier['color']; ?>">
                        <i class="fas <?php echo $tier['icon']; ?>"></i>
                        <?php echo $tier['name']; ?>
                    </span>
                    
                    <div class="cart-item-image">
                        <?php if ($item['image'] && file_exists("assets/images/".$item['image'])): ?>
                            <img src="assets/images/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php else: ?>
                            <div class="placeholder">
                                <i class="fas fa-image"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cart-item-details">
                        <div class="cart-item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                        <div class="cart-item-price">₱<?php echo number_format($item['price'], 2); ?> each</div>
                        
                        <div class="cart-item-quantity-row">
                            <input type="number" 
                                   name="quantity[<?php echo $id; ?>]" 
                                   value="<?php echo $item['quantity']; ?>" 
                                   min="0" 
                                   max="99"
                                   class="cart-item-qty-input"
                                   <?php echo !$store_online ? 'disabled' : ''; ?>>
                            <span class="cart-item-subtotal">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                        </div>
                        
                        <div class="tier-minmax-info">
                            <i class="fas <?php echo $tier['icon']; ?> <?php echo $tier['color']; ?>"></i>
                            <span>Min: <?php echo $tier['min']; ?> | Max: <?php echo $tier['max']; ?></span>
                        </div>
                        
                        <div class="cart-item-actions">
                            <button type="submit" name="update" class="btn-cart-item btn-update-item" value="1" <?php echo !$store_online ? 'disabled' : ''; ?>>Update</button>
                            <button type="submit" name="remove" value="<?php echo $id; ?>" class="btn-cart-item btn-remove-item">Remove</button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Action Buttons -->
            <div style="text-align: center;">
                <button type="submit" name="update" class="btn-update-all" <?php echo !$store_online ? 'disabled' : ''; ?>>
                    <i class="fas fa-sync-alt me-2"></i>Update All Quantities
                </button>
                <button type="submit" name="clear_all" class="btn-clear-all" onclick="return confirm('Clear your entire cart? This action cannot be undone.')">
                    <i class="fas fa-trash-alt me-2"></i>Clear Cart
                </button>
            </div>
        </form>
    <?php endif; ?>

    <!-- ORDER SUMMARY -->
    <?php if (!empty($_SESSION['cart'])): ?>
    <div class="order-summary-container">
        <div class="order-summary">
            <h5>Order Summary</h5>
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>₱<?php echo number_format($total, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping:</span>
                <span>₱20.00</span>
            </div>
            <?php if ($cartHasPremium): ?>
            <div class="summary-row text-success">
                <span><i class="fas fa-crown"></i> Premium:</span>
                <span>No Limit</span>
            </div>
            <?php endif; ?>
            <div class="summary-total">
                <span>Total:</span>
                <span>₱<?php echo number_format($total, 2); ?></span>
            </div>
            
            <?php if (!$store_online): ?>
            <div class="alert alert-warning mt-3 mb-0">
                <i class="fas fa-store-slash"></i>
                Checkout is disabled because the store is closed.
            </div>
            <?php endif; ?>
            
            <form method="post">
                <button type="submit" name="checkout" class="btn-checkout" 
                        <?php echo (!$cartHasPremium && ($currentTotalPieces > $maxAllowed || $currentTotalPieces < $minAllowed)) ? 'disabled' : ''; ?>
                        <?php echo !$store_online ? 'disabled' : ''; ?>>
                    <i class="fas fa-credit-card me-2"></i>Checkout
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- QUICK ADD MORE ITEMS SECTION -->
    <?php if (!empty($budget_products) || !empty($regular_products) || !empty($premium_products)): ?>
    <div class="quick-add-section">
        <div class="quick-add-title">
            <i class="fas fa-plus-circle"></i>
            Add More Items
        </div>

        <!-- Budget Items Section -->
        <?php if (!empty($budget_products)): ?>
        <div class="tier-section">
            <div class="tier-header budget">
                <i class="fas fa-tag"></i>
                <span>Budget Items (Below ₱10)</span>
                <span class="tier-count"><?php echo count($budget_products); ?> items</span>
            </div>
            <div class="quick-add-grid">
                <?php foreach ($budget_products as $product): 
                    $in_cart = isset($_SESSION['cart'][$product['id']]);
                    $tier = getProductTier($product['price']);
                    $stock_class = '';
                    if ($product['stock'] <= 0) {
                        $stock_class = 'out-of-stock';
                    } elseif ($product['stock'] <= 5) {
                        $stock_class = 'low-stock';
                    }
                    
                    // Check if adding this product would exceed max
                    $wouldExceed = false;
                    if (!cartHasPremiumItems($_SESSION['cart'])) {
                        $wouldExceed = wouldExceedMaximum($_SESSION['cart'], $product['id'], 1, $product['price']);
                    }
                    
                    $currentQty = $in_cart ? $_SESSION['cart'][$product['id']]['quantity'] : 0;
                    $buttonText = $in_cart ? '+ More' : 'Add';
                    $maxReached = $in_cart && $currentQty >= $tier['max'];
                ?>
                <div class="product-option-card budget <?php echo $in_cart ? 'in-cart' : ''; ?> <?php echo $product['stock'] <= 0 ? 'out-of-stock' : ''; ?>" data-product-id="<?php echo $product['id']; ?>">
                    <span class="product-tier-badge budget"><i class="fas fa-tag"></i> Budget</span>
                    <?php if ($in_cart): ?>
                        <span class="in-cart-badge"><?php echo $currentQty; ?></span>
                    <?php elseif ($product['stock'] <= 0): ?>
                        <span class="out-of-stock-badge">Out</span>
                    <?php endif; ?>
                    
                    <div class="product-option-image">
                        <?php if ($product['image'] && file_exists("assets/images/".$product['image'])): ?>
                            <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <div class="placeholder"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-option-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="product-option-price">₱<?php echo number_format($product['price'], 2); ?></div>
                    
                    <div class="tier-minmax-info">
                        <i class="fas fa-tag budget"></i>
                        <span>Min: 20 | Max: 300</span>
                    </div>
                    
                    <div class="product-option-stock <?php echo $stock_class; ?>">
                        <i class="fas fa-box"></i> 
                        <?php echo $product['stock'] > 0 ? "Stock: {$product['stock']}" : 'Out of Stock'; ?>
                    </div>

                    <?php if ($product['stock'] <= 0): ?>
                        <button class="btn-add-mini" disabled>
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </button>
                    <?php elseif ($maxReached): ?>
                        <button class="btn-add-mini" disabled>
                            <i class="fas fa-ban"></i> Max Reached
                        </button>
                    <?php elseif ($wouldExceed): ?>
                        <button class="btn-add-mini" disabled>
                            <i class="fas fa-ban"></i> Would Exceed Max
                        </button>
                    <?php elseif (!$store_online): ?>
                        <button class="btn-add-mini disabled" disabled>
                            <i class="fas fa-store-slash"></i> Store Closed
                        </button>
                    <?php else: ?>
                        <button class="btn-add-mini add-to-cart-btn" 
                                data-product-id="<?php echo $product['id']; ?>"
                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                data-tier-min="20"
                                data-tier-max="300"
                                data-current-qty="<?php echo $currentQty; ?>">
                            <i class="fas fa-cart-plus"></i> <?php echo $buttonText; ?>
                        </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Regular Items Section -->
        <?php if (!empty($regular_products)): ?>
        <div class="tier-section">
            <div class="tier-header regular">
                <i class="fas fa-box"></i>
                <span>Regular Items (₱10 - ₱249)</span>
                <span class="tier-count"><?php echo count($regular_products); ?> items</span>
            </div>
            <div class="quick-add-grid">
                <?php foreach ($regular_products as $product): 
                    $in_cart = isset($_SESSION['cart'][$product['id']]);
                    $tier = getProductTier($product['price']);
                    $stock_class = '';
                    if ($product['stock'] <= 0) {
                        $stock_class = 'out-of-stock';
                    } elseif ($product['stock'] <= 5) {
                        $stock_class = 'low-stock';
                    }
                    
                    $wouldExceed = false;
                    if (!cartHasPremiumItems($_SESSION['cart'])) {
                        $wouldExceed = wouldExceedMaximum($_SESSION['cart'], $product['id'], 1, $product['price']);
                    }
                    
                    $currentQty = $in_cart ? $_SESSION['cart'][$product['id']]['quantity'] : 0;
                    $buttonText = $in_cart ? '+ More' : 'Add';
                    $maxReached = $in_cart && $currentQty >= $tier['max'];
                ?>
                <div class="product-option-card regular <?php echo $in_cart ? 'in-cart' : ''; ?> <?php echo $product['stock'] <= 0 ? 'out-of-stock' : ''; ?>" data-product-id="<?php echo $product['id']; ?>">
                    <span class="product-tier-badge regular"><i class="fas fa-box"></i> Regular</span>
                    <?php if ($in_cart): ?>
                        <span class="in-cart-badge"><?php echo $currentQty; ?></span>
                    <?php elseif ($product['stock'] <= 0): ?>
                        <span class="out-of-stock-badge">Out</span>
                    <?php endif; ?>
                    
                    <div class="product-option-image">
                        <?php if ($product['image'] && file_exists("assets/images/".$product['image'])): ?>
                            <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <div class="placeholder"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-option-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="product-option-price">₱<?php echo number_format($product['price'], 2); ?></div>
                    
                    <div class="tier-minmax-info">
                        <i class="fas fa-box regular"></i>
                        <span>Min: 20 | Max: 300</span>
                    </div>
                    
                    <div class="product-option-stock <?php echo $stock_class; ?>">
                        <i class="fas fa-box"></i> 
                        <?php echo $product['stock'] > 0 ? "Stock: {$product['stock']}" : 'Out of Stock'; ?>
                    </div>

                    <?php if ($product['stock'] <= 0): ?>
                        <button class="btn-add-mini" disabled>
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </button>
                    <?php elseif ($maxReached): ?>
                        <button class="btn-add-mini" disabled>
                            <i class="fas fa-ban"></i> Max Reached
                        </button>
                    <?php elseif ($wouldExceed): ?>
                        <button class="btn-add-mini" disabled>
                            <i class="fas fa-ban"></i> Would Exceed Max
                        </button>
                    <?php elseif (!$store_online): ?>
                        <button class="btn-add-mini disabled" disabled>
                            <i class="fas fa-store-slash"></i> Store Closed
                        </button>
                    <?php else: ?>
                        <button class="btn-add-mini add-to-cart-btn" 
                                data-product-id="<?php echo $product['id']; ?>"
                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                data-tier-min="20"
                                data-tier-max="300"
                                data-current-qty="<?php echo $currentQty; ?>">
                            <i class="fas fa-cart-plus"></i> <?php echo $buttonText; ?>
                        </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Premium Items Section -->
        <?php if (!empty($premium_products)): ?>
        <div class="tier-section">
            <div class="tier-header premium">
                <i class="fas fa-crown"></i>
                <span>Premium Items (₱250+)</span>
                <span class="tier-count"><?php echo count($premium_products); ?> items</span>
            </div>
            <div class="quick-add-grid">
                <?php foreach ($premium_products as $product): 
                    $in_cart = isset($_SESSION['cart'][$product['id']]);
                    $tier = getProductTier($product['price']);
                    $stock_class = '';
                    if ($product['stock'] <= 0) {
                        $stock_class = 'out-of-stock';
                    } elseif ($product['stock'] <= 5) {
                        $stock_class = 'low-stock';
                    }
                    
                    $wouldExceed = false; // Premium items don't have max limit
                    
                    $currentQty = $in_cart ? $_SESSION['cart'][$product['id']]['quantity'] : 0;
                    $buttonText = $in_cart ? '+ More' : 'Add';
                    $maxReached = $in_cart && $currentQty >= $tier['max'];
                ?>
                <div class="product-option-card premium <?php echo $in_cart ? 'in-cart' : ''; ?> <?php echo $product['stock'] <= 0 ? 'out-of-stock' : ''; ?>" data-product-id="<?php echo $product['id']; ?>">
                    <span class="product-tier-badge premium"><i class="fas fa-crown"></i> Premium</span>
                    <?php if (!$in_cart): ?>
                        <span class="no-limit-badge">No Limit</span>
                    <?php endif; ?>
                    <?php if ($in_cart): ?>
                        <span class="in-cart-badge"><?php echo $currentQty; ?></span>
                    <?php elseif ($product['stock'] <= 0): ?>
                        <span class="out-of-stock-badge">Out</span>
                    <?php endif; ?>
                    
                    <div class="product-option-image">
                        <?php if ($product['image'] && file_exists("assets/images/".$product['image'])): ?>
                            <img src="assets/images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <div class="placeholder"><i class="fas fa-image"></i></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-option-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="product-option-price">₱<?php echo number_format($product['price'], 2); ?></div>
                    
                    <div class="tier-minmax-info">
                        <i class="fas fa-crown premium"></i>
                        <span>Min: 1 | Max: 10</span>
                    </div>
                    
                    <div class="product-option-stock <?php echo $stock_class; ?>">
                        <i class="fas fa-box"></i> 
                        <?php echo $product['stock'] > 0 ? "Stock: {$product['stock']}" : 'Out of Stock'; ?>
                    </div>

                    <?php if ($product['stock'] <= 0): ?>
                        <button class="btn-add-mini" disabled>
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </button>
                    <?php elseif ($maxReached): ?>
                        <button class="btn-add-mini" disabled>
                            <i class="fas fa-ban"></i> Max Reached
                        </button>
                    <?php elseif (!$store_online): ?>
                        <button class="btn-add-mini disabled" disabled>
                            <i class="fas fa-store-slash"></i> Store Closed
                        </button>
                    <?php else: ?>
                        <button class="btn-add-mini add-to-cart-btn" 
                                data-product-id="<?php echo $product['id']; ?>"
                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                data-tier-min="1"
                                data-tier-max="10"
                                data-current-qty="<?php echo $currentQty; ?>">
                            <i class="fas fa-cart-plus"></i> <?php echo $buttonText; ?>
                        </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Continue Shopping Link -->
        <a href="/menu" class="continue-shopping">
            <i class="fas fa-arrow-left me-2"></i>Continue Shopping
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
// ===== FETCH STORE STATUS =====
let storeUpdateInterval;

function fetchStoreStatus() {
    fetch('/api/store_status.php?action=get_status')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateStoreUI(data);
            }
        })
        .catch(error => console.error('Error fetching store status:', error));
}

function updateStoreUI(data) {
    const isOnline = data.is_online;
    const banner = document.getElementById('storeStatusBanner');
    const message = document.getElementById('storeStatusMessage');
    const warning = document.getElementById('storeClosedWarning');
    const checkoutBtn = document.querySelector('.btn-checkout');
    const updateButtons = document.querySelectorAll('.btn-update-item, .btn-update-all');
    const qtyInputs = document.querySelectorAll('.cart-item-qty-input');
    const addButtons = document.querySelectorAll('.add-to-cart-btn');
    
    // Update banner
    if (banner) {
        banner.className = 'store-status-banner ' + (isOnline ? 'online' : 'offline');
        banner.querySelector('i').className = isOnline ? 'fas fa-store' : 'fas fa-store-slash';
        banner.querySelector('h5').childNodes[0].nodeValue = isOnline ? 'Store is OPEN' : 'Store is CLOSED';
    }
    
    // Update message
    if (message) {
        message.textContent = data.offline_message || (isOnline ? 'Store is open for business' : 'Store is currently closed');
    }
    
    // Update warning
    if (warning) {
        if (isOnline) {
            warning.style.display = 'none';
        } else {
            warning.style.display = 'block';
        }
    }
    
    // Update checkout button
    if (checkoutBtn) {
        if (isOnline) {
            checkoutBtn.disabled = false;
        } else {
            checkoutBtn.disabled = true;
        }
    }
    
    // Update quantity inputs and update buttons
    qtyInputs.forEach(input => {
        input.disabled = !isOnline;
    });
    
    updateButtons.forEach(btn => {
        btn.disabled = !isOnline;
    });
    
    // Update add-to-cart buttons in quick add section
    addButtons.forEach(btn => {
        const productCard = btn.closest('.product-option-card');
        if (productCard && !productCard.querySelector('.btn-add-mini[disabled]')) {
            if (!isOnline) {
                btn.disabled = true;
                btn.classList.add('disabled');
                btn.innerHTML = '<i class="fas fa-store-slash"></i> Store Closed';
            } else {
                btn.disabled = false;
                btn.classList.remove('disabled');
                const inCart = productCard.querySelector('.in-cart-badge');
                btn.innerHTML = '<i class="fas fa-cart-plus"></i> ' + (inCart ? '+ More' : 'Add');
            }
        }
    });
}

// ===== SHOW TOAST NOTIFICATION =====
function showToast(message, type = 'success') {
    const toastContainer = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.style.background = type === 'success' ? '#28a745' : '#dc3545';
    toast.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} me-2"></i>
        ${message}
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.add('fade-out');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ===== AJAX ADD TO CART =====
document.addEventListener('DOMContentLoaded', function() {
    const addButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Check if store is open
            const isStoreOnline = !document.querySelector('.btn-checkout')?.disabled;
            if (!isStoreOnline) {
                showToast('Store is currently closed. Please try again later.', 'error');
                return;
            }
            
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const tierMin = parseInt(this.dataset.tierMin);
            const tierMax = parseInt(this.dataset.tierMax);
            const originalText = this.innerHTML;
            
            this.classList.add('loading');
            this.disabled = true;
            
            fetch('/ajax/add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'product_id=' + productId
            })
            .then(response => response.json())
            .then(data => {
                this.classList.remove('loading');
                
                if (data.success) {
                    // Update cart count
                    updateCartCount(data.cartCount);
                    
                    // Update product card
                    updateProductCard(productId, data.newQty, tierMin, tierMax);
                    
                    // Show success state
                    this.classList.add('success');
                    this.innerHTML = '<i class="fas fa-check"></i> Added!';
                    
                    // Show toast notification
                    showToast(`${productName} added to cart!`, 'success');
                    
                    // Reload page after 1.5 seconds to update cart items
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                    
                } else {
                    console.error('Error:', data.error);
                    this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Failed';
                    
                    showToast(data.error || 'Failed to add item', 'error');
                    
                    setTimeout(() => {
                        this.innerHTML = originalText;
                        this.disabled = false;
                    }, 2000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.classList.remove('loading');
                this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                
                showToast('Network error. Please try again.', 'error');
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 2000);
            });
        });
    });
    
    // Start store status updates
    fetchStoreStatus();
    storeUpdateInterval = setInterval(fetchStoreStatus, 5000);
});

// Clean up interval on page unload
window.addEventListener('beforeunload', function() {
    if (storeUpdateInterval) {
        clearInterval(storeUpdateInterval);
    }
});

function updateCartCount(count) {
    const cartBadge = document.querySelector('.badge-cart');
    if (cartBadge) {
        cartBadge.textContent = count;
        cartBadge.classList.add('updated');
        setTimeout(() => {
            cartBadge.classList.remove('updated');
        }, 300);
    }
}

function updateProductCard(id, qty, tierMin, tierMax) {
    const card = document.querySelector(`.product-option-card[data-product-id="${id}"]`);
    if (!card) return;
    
    // Update in-cart badge
    let inCartBadge = card.querySelector('.in-cart-badge');
    if (!inCartBadge) {
        inCartBadge = document.createElement('span');
        inCartBadge.className = 'in-cart-badge';
        card.appendChild(inCartBadge);
    }
    inCartBadge.textContent = qty;
    
    // Update button text
    const button = card.querySelector('.add-to-cart-btn');
    if (button) {
        button.innerHTML = '<i class="fas fa-cart-plus"></i> + More';
    }
}

// Auto-hide alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.transition = 'opacity 0.5s';
        a.style.opacity = '0';
        setTimeout(() => a.style.display = 'none', 500);
    });
}, 5000);
</script>

<?php include 'includes/footer.php'; ?>