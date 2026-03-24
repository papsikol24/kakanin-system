<?php
require_once 'includes/config.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: /login');
    exit;
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// ===== CHECK STORE STATUS =====
$store_online = true;
$offline_message = 'Store is currently closed. Please check back later.';
$store_status_checked = false;

try {
    $stmt = $pdo->query("SELECT is_online, offline_message FROM tbl_store_status WHERE id = 1");
    $store_status = $stmt->fetch();
    if ($store_status) {
        $store_online = (bool)$store_status['is_online'];
        $offline_message = $store_status['offline_message'] ?? 'Store is currently closed. Please check back later.';
        $store_status_checked = true;
    }
} catch (Exception $e) {
    // Table might not exist yet, assume store is open
    error_log("Store status check failed: " . $e->getMessage());
}

// ===== TIERED PRICING RULES =====
function getProductTier($price) {
    if ($price < 10) {
        return [
            'id' => 'budget',
            'name' => 'Budget',
            'fullname' => 'Budget Items',
            'range' => 'Below ₱10',
            'min' => 20,
            'max' => 300,
            'color' => 'budget',
            'icon' => 'fa-tag',
            'border' => '#28a745',
            'bg' => '#e8f5e9'
        ];
    } elseif ($price >= 10 && $price < 250) {
        return [
            'id' => 'regular',
            'name' => 'Regular',
            'fullname' => 'Regular Items',
            'range' => '₱10 - ₱249',
            'min' => 20,
            'max' => 300,
            'color' => 'regular',
            'icon' => 'fa-box',
            'border' => '#ffc107',
            'bg' => '#fff3e0'
        ];
    } else {
        return [
            'id' => 'premium',
            'name' => 'Premium',
            'fullname' => 'Premium Items',
            'range' => '₱250 and above',
            'min' => 1,
            'max' => 10,
            'color' => 'premium',
            'icon' => 'fa-crown',
            'border' => '#dc3545',
            'bg' => '#ffebee'
        ];
    }
}

// Handle search and filters
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$selectedTier = isset($_GET['tier']) ? $_GET['tier'] : 'all';

// Build query with search
$query = "SELECT *, DATEDIFF(NOW(), created_at) <= 7 as is_new FROM tbl_products";
$params = [];

$whereConditions = [];

if (!empty($searchTerm)) {
    $whereConditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

// Add tier filter
if ($selectedTier !== 'all') {
    if ($selectedTier === 'budget') {
        $whereConditions[] = "price < 10";
    } elseif ($selectedTier === 'regular') {
        $whereConditions[] = "price >= 10 AND price < 250";
    } elseif ($selectedTier === 'premium') {
        $whereConditions[] = "price >= 250";
    }
}

if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}

// Add sorting
switch ($sort) {
    case 'price_low':
        $query .= " ORDER BY price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY price DESC";
        break;
    case 'newest':
        $query .= " ORDER BY created_at DESC";
        break;
    case 'name':
    default:
        $query .= " ORDER BY name ASC";
        break;
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get total count
$totalProducts = count($products);

// Get counts for each tier
$tierCounts = [
    'budget' => $pdo->query("SELECT COUNT(*) FROM tbl_products WHERE price < 10")->fetchColumn(),
    'regular' => $pdo->query("SELECT COUNT(*) FROM tbl_products WHERE price >= 10 AND price < 250")->fetchColumn(),
    'premium' => $pdo->query("SELECT COUNT(*) FROM tbl_products WHERE price >= 250")->fetchColumn(),
];

// Get best selling products for badge display
$bestSellers = $pdo->query("
    SELECT p.id, SUM(oi.quantity) as total_ordered
    FROM tbl_order_items oi
    JOIN tbl_products p ON oi.product_id = p.id
    GROUP BY p.id
    ORDER BY total_ordered DESC
    LIMIT 8
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Group products by tier for category display
$groupedProducts = [
    'budget' => ['items' => [], 'info' => null],
    'regular' => ['items' => [], 'info' => null],
    'premium' => ['items' => [], 'info' => null]
];

foreach ($products as $product) {
    $tier = getProductTier($product['price']);
    $groupedProducts[$tier['id']]['items'][] = $product;
    $groupedProducts[$tier['id']]['info'] = $tier;
}

include 'includes/customer_header.php';

// Calculate current cart total for display
$cartTotal = !empty($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
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
        padding-right: 15px;
        padding-left: 15px;
        margin-right: auto;
        margin-left: auto;
        max-width: 1200px;
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
        display: flex;
        align-items: center;
        gap: 5px;
        background: rgba(255,255,255,0.3);
        padding: 4px 8px;
        border-radius: 50px;
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

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* ===== PAGE HEADER ===== */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1.5rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-header h2 {
        font-size: 2rem;
        font-weight: 700;
        color: #333;
        margin: 0;
    }

    .page-header h2 i {
        color: #008080;
    }

    .btn-cart {
        background: white;
        color: #008080;
        border: 2px solid #008080;
        border-radius: 50px;
        padding: 0.5rem 1.5rem;
        font-size: 1rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
        position: relative;
    }

    .btn-cart:hover {
        background: #008080;
        color: white;
    }

    .btn-cart.disabled {
        opacity: 0.5;
        pointer-events: none;
        border-color: #6c757d;
        color: #6c757d;
    }

    .badge-cart {
        background: #dc3545;
        color: white;
        border-radius: 50px;
        padding: 0.2rem 0.6rem;
        font-size: 0.8rem;
        margin-left: 0.3rem;
        display: inline-block;
        animation: badgePop 0.3s ease;
    }

    @keyframes badgePop {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    /* ===== TIER FILTER BUTTONS ===== */
    .tier-filters {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 2rem;
        justify-content: center;
    }

    .tier-filter-btn {
        flex: 1;
        min-width: 80px;
        padding: 0.8rem 0.3rem;
        border: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.2rem;
        background: white;
        color: #333;
        box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        text-decoration: none;
        line-height: 1.2;
    }

    .tier-filter-btn i {
        font-size: 1.2rem;
    }

    .tier-filter-btn span {
        font-size: 0.7rem;
        opacity: 0.8;
    }

    .tier-filter-btn.active {
        color: white;
    }

    .tier-filter-btn.active.all {
        background: #008080;
    }

    .tier-filter-btn.active.budget {
        background: #28a745;
    }

    .tier-filter-btn.active.regular {
        background: #ffc107;
        color: #333;
    }

    .tier-filter-btn.active.premium {
        background: #dc3545;
    }

    .tier-count {
        background: rgba(0,0,0,0.1);
        padding: 0.1rem 0.4rem;
        border-radius: 50px;
        font-size: 0.65rem;
    }

    .tier-filter-btn.active .tier-count {
        background: rgba(255,255,255,0.2);
    }

    /* ===== SEARCH SECTION ===== */
    .search-section {
        background: white;
        border-radius: 20px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }

    .search-form {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
        align-items: center;
    }

    .search-wrapper {
        flex: 2;
        min-width: 200px;
        position: relative;
    }

    .search-wrapper i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 1rem;
        z-index: 2;
    }

    .search-input {
        width: 100%;
        padding: 0.8rem 1rem 0.8rem 40px;
        border: 2px solid #e0e0e0;
        border-radius: 50px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.95rem;
        transition: all 0.3s ease;
        background: white;
    }

    .search-input:focus {
        border-color: #008080;
        box-shadow: 0 0 0 0.25rem rgba(0, 128, 128, 0.25);
        outline: none;
    }

    .search-filters {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        flex: 1;
    }

    .filter-select {
        padding: 0.8rem 2rem 0.8rem 1.2rem;
        border: 2px solid #e0e0e0;
        border-radius: 50px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.9rem;
        cursor: pointer;
        background: white;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.8rem center;
        min-width: 100px;
    }

    .btn-search {
        background: linear-gradient(135deg, #008080, #20b2aa);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.8rem 1.5rem;
        font-weight: 500;
        font-size: 0.9rem;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        white-space: nowrap;
    }

    .btn-reset {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.8rem 1.5rem;
        font-weight: 500;
        font-size: 0.9rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        white-space: nowrap;
    }

    .search-stats {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
        color: #666;
        font-size: 0.95rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 1rem;
    }

    /* ===== TIER CATEGORY HEADERS ===== */
    .tier-category {
        margin: 2.5rem 0 1.5rem;
    }

    .tier-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.5rem;
        border-radius: 15px;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-left: 6px solid;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .tier-header.budget {
        background: #e8f5e9;
        border-left-color: #28a745;
    }

    .tier-header.regular {
        background: #fff3e0;
        border-left-color: #ffc107;
    }

    .tier-header.premium {
        background: #ffebee;
        border-left-color: #dc3545;
    }

    .tier-title {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .tier-title i {
        font-size: 1.5rem;
    }

    .tier-title i.budget { color: #28a745; }
    .tier-title i.regular { color: #ffc107; }
    .tier-title i.premium { color: #dc3545; }

    .tier-title h3 {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 0;
        color: #333;
    }

    .tier-rules {
        display: flex;
        gap: 1rem;
        flex-wrap: wrap;
    }

    .tier-rule-badge {
        padding: 0.3rem 1rem;
        border-radius: 50px;
        font-size: 0.85rem;
        font-weight: 600;
        background: white;
    }

    .tier-rule-badge.budget {
        color: #28a745;
        border: 1px solid #28a745;
    }

    .tier-rule-badge.regular {
        color: #ffc107;
        border: 1px solid #ffc107;
    }

    .tier-rule-badge.premium {
        color: #dc3545;
        border: 1px solid #dc3545;
    }

    /* ===== PRODUCT GRID ===== */
    .product-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1.5rem;
    }

    @media (max-width: 768px) {
        .product-grid {
            grid-template-columns: repeat(3, 1fr);
            gap: 0.8rem;
        }
    }

    @media (max-width: 480px) {
        .product-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.5rem;
        }
    }

    .product-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: transform 0.3s, box-shadow 0.3s;
        height: 100%;
        display: flex;
        flex-direction: column;
        position: relative;
        border: 1px solid;
    }

    .product-card.budget { 
        border-color: #28a745;
        border-top: 4px solid #28a745;
    }
    .product-card.regular { 
        border-color: #ffc107;
        border-top: 4px solid #ffc107;
    }
    .product-card.premium { 
        border-color: #dc3545;
        border-top: 4px solid #dc3545;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }

    .product-card img {
        width: 100%;
        height: 180px;
        object-fit: cover;
        border-bottom: 2px solid;
    }

    .product-card.budget img { border-bottom-color: #28a745; }
    .product-card.regular img { border-bottom-color: #ffc107; }
    .product-card.premium img { border-bottom-color: #dc3545; }

    @media (max-width: 768px) {
        .product-card img {
            height: 120px;
        }
    }

    .product-info {
        padding: 1rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    @media (max-width: 768px) {
        .product-info {
            padding: 0.6rem;
        }
    }

    .product-name {
        font-weight: 600;
        color: #333;
        font-size: 1rem;
        margin-bottom: 0.3rem;
        line-height: 1.3;
    }

    @media (max-width: 768px) {
        .product-name {
            font-size: 0.8rem;
        }
    }

    .product-price {
        font-weight: 700;
        color: #008080;
        font-size: 1.1rem;
        margin-bottom: 0.5rem;
    }

    @media (max-width: 768px) {
        .product-price {
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
    }

    /* ===== PRODUCT BADGES ===== */
    .product-tier-badge {
        position: absolute;
        top: 8px;
        right: 8px;
        padding: 0.25rem 0.5rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
        z-index: 2;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        display: flex;
        align-items: center;
        gap: 0.2rem;
    }

    @media (max-width: 768px) {
        .product-tier-badge {
            font-size: 0.6rem;
            padding: 0.2rem 0.4rem;
        }
    }

    .product-tier-badge.budget {
        background: #28a745;
        color: white;
    }

    .product-tier-badge.regular {
        background: #ffc107;
        color: #333;
    }

    .product-tier-badge.premium {
        background: #dc3545;
        color: white;
    }

    .best-seller-badge {
        position: absolute;
        top: 40px;
        right: 8px;
        background: #ff8c00;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
        z-index: 2;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    @media (max-width: 768px) {
        .best-seller-badge {
            top: 35px;
            font-size: 0.6rem;
            padding: 0.2rem 0.4rem;
        }
    }

    .new-badge {
        position: absolute;
        top: 8px;
        left: 8px;
        background: #ff4444;
        color: white;
        padding: 0.25rem 0.5rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
        z-index: 2;
        box-shadow: 0 2px 5px rgba(255,68,68,0.2);
    }

    @media (max-width: 768px) {
        .new-badge {
            font-size: 0.6rem;
            padding: 0.2rem 0.4rem;
        }
    }

    /* ===== ADD TO CART BUTTON ===== */
    .btn-add {
        background: linear-gradient(135deg, #008080, #20b2aa);
        border: none;
        border-radius: 50px;
        padding: 0.6rem 0;
        color: white;
        font-weight: 500;
        font-size: 0.85rem;
        transition: all 0.2s;
        cursor: pointer;
        width: 100%;
        margin-top: 0.5rem;
        position: relative;
    }

    @media (max-width: 768px) {
        .btn-add {
            padding: 0.4rem 0;
            font-size: 0.7rem;
            margin-top: 0.3rem;
        }
    }

    .btn-add:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,128,128,0.3);
    }

    .btn-add:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .btn-add i {
        margin-right: 0.3rem;
    }

    /* Loading state */
    .btn-add.loading {
        color: transparent !important;
        pointer-events: none;
    }

    .btn-add.loading::after {
        content: '';
        position: absolute;
        width: 20px;
        height: 20px;
        top: 50%;
        left: 50%;
        margin-left: -10px;
        margin-top: -10px;
        border: 3px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Success state */
    .btn-add.success {
        background: #28a745;
    }

    /* ===== CART INDICATOR ===== */
    .cart-indicator {
        font-size: 0.75rem;
        color: #28a745;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        background: #e8f5e9;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
    }

    @media (max-width: 768px) {
        .cart-indicator {
            font-size: 0.6rem;
            padding: 0.15rem 0.4rem;
        }
    }

    .cart-indicator i {
        font-size: 0.7rem;
    }

    .tier-info-row {
        font-size: 0.8rem;
        color: #666;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px dashed #ddd;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    @media (max-width: 768px) {
        .tier-info-row {
            font-size: 0.65rem;
            margin-top: 0.3rem;
            padding-top: 0.3rem;
        }
    }

    .tier-minmax {
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .tier-minmax i {
        font-size: 0.8rem;
    }

    @media (max-width: 768px) {
        .tier-minmax i {
            font-size: 0.6rem;
        }
    }

    .warning-text {
        font-size: 0.7rem;
        color: #856404;
        background: #fff3cd;
        padding: 0.2rem;
        border-radius: 5px;
        text-align: center;
        margin-top: 0.3rem;
    }

    @media (max-width: 768px) {
        .warning-text {
            font-size: 0.6rem;
            padding: 0.1rem;
        }
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

    @media (max-width: 768px) {
        .search-form {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-filters {
            width: 100%;
            justify-content: space-between;
        }
        
        .filter-select {
            flex: 2;
            min-width: 0;
            padding: 0.6rem 1.5rem 0.6rem 1rem;
            font-size: 0.8rem;
        }
        
        .btn-search, .btn-reset {
            flex: 1;
            padding: 0.6rem 0.3rem;
            font-size: 0.8rem;
        }
        
        .tier-filter-btn {
            min-width: 60px;
            padding: 0.5rem 0.2rem;
            font-size: 0.75rem;
        }
        
        .tier-filter-btn i {
            font-size: 0.9rem;
        }
        
        .page-header h2 {
            font-size: 1.5rem;
        }
        
        .btn-cart {
            padding: 0.4rem 1rem;
            font-size: 0.9rem;
        }
    }

    @media (max-width: 480px) {
        .tier-filter-btn {
            min-width: 50px;
            padding: 0.4rem 0.1rem;
            font-size: 0.7rem;
        }
        
        .tier-filter-btn i {
            font-size: 0.8rem;
        }
        
        .tier-filter-btn span {
            font-size: 0.6rem;
        }
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

    <!-- Page Header -->
    <div class="page-header">
        <h2><i class="fas fa-utensils me-2"></i>Menu</h2>
        <a href="/cart" class="btn-cart <?php echo !$store_online ? 'disabled' : ''; ?>" id="cartButton">
            <i class="fas fa-shopping-cart"></i> Cart
            <span class="badge-cart" id="cartCountBadge"><?php echo $cartTotal; ?></span>
        </a>
    </div>

    <!-- Store Closed Warning (if closed) -->
    <?php if (!$store_online): ?>
    <div class="alert alert-warning mb-3" id="storeClosedWarning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Store is currently closed:</strong> <?php echo htmlspecialchars($offline_message); ?>
        <br><small>You can still browse products, but checkout is disabled until the store opens.</small>
    </div>
    <?php endif; ?>

    <!-- Tier Filter Buttons -->
    <div class="tier-filters">
        <a href="/menu" class="tier-filter-btn all <?php echo $selectedTier === 'all' ? 'active' : ''; ?>">
            <i class="fas fa-utensils"></i>
            All
            <span class="tier-count"><?php echo array_sum($tierCounts); ?></span>
        </a>
        <a href="?tier=budget<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $sort != 'name' ? '&sort=' . $sort : ''; ?>" 
           class="tier-filter-btn budget <?php echo $selectedTier === 'budget' ? 'active' : ''; ?>">
            <i class="fas fa-tag"></i>
            Budget
            <span class="tier-count"><?php echo $tierCounts['budget']; ?></span>
        </a>
        <a href="?tier=regular<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $sort != 'name' ? '&sort=' . $sort : ''; ?>" 
           class="tier-filter-btn regular <?php echo $selectedTier === 'regular' ? 'active' : ''; ?>">
            <i class="fas fa-box"></i>
            Regular
            <span class="tier-count"><?php echo $tierCounts['regular']; ?></span>
        </a>
        <a href="?tier=premium<?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $sort != 'name' ? '&sort=' . $sort : ''; ?>" 
           class="tier-filter-btn premium <?php echo $selectedTier === 'premium' ? 'active' : ''; ?>">
            <i class="fas fa-crown"></i>
            Premium
            <span class="tier-count"><?php echo $tierCounts['premium']; ?></span>
        </a>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <form method="get" action="" class="search-form" id="searchForm">
            <?php if ($selectedTier !== 'all'): ?>
                <input type="hidden" name="tier" value="<?php echo $selectedTier; ?>">
            <?php endif; ?>
            
            <div class="search-wrapper">
                <i class="fas fa-search"></i>
                <input type="text" 
                       name="search" 
                       class="search-input" 
                       placeholder="Search products..." 
                       value="<?php echo htmlspecialchars($searchTerm); ?>"
                       autocomplete="off">
            </div>
            
            <div class="search-filters">
                <select name="sort" class="filter-select">
                    <option value="name" <?php echo $sort == 'name' ? 'selected' : ''; ?>>Name</option>
                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price Low</option>
                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price High</option>
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                </select>
                
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i>
                </button>
                
                <?php if (!empty($searchTerm) || $sort != 'name' || $selectedTier !== 'all'): ?>
                    <a href="/menu" class="btn-reset">
                        <i class="fas fa-times"></i>
                    </a>
                <?php endif; ?>
            </div>
        </form>
        
        <!-- Search Stats -->
        <?php if (!empty($searchTerm) || $sort != 'name' || $selectedTier !== 'all'): ?>
        <div class="search-stats">
            <div>
                <i class="fas fa-box me-1"></i>
                <strong><?php echo $totalProducts; ?></strong> found
            </div>
            
            <?php if (!empty($searchTerm)): ?>
                <div class="search-term">
                    "<?php echo htmlspecialchars($searchTerm); ?>"
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Product Grid by Category -->
    <?php 
    $hasProducts = false;
    foreach (['budget', 'regular', 'premium'] as $tierId):
        if (!empty($groupedProducts[$tierId]['items'])):
            $hasProducts = true;
            $tierInfo = $groupedProducts[$tierId]['info'];
    ?>
    
    <!-- Tier Category Header -->
    <div class="tier-category">
        <div class="tier-header <?php echo $tierId; ?>">
            <div class="tier-title">
                <i class="fas <?php echo $tierInfo['icon']; ?> <?php echo $tierId; ?>"></i>
                <h3><?php echo $tierInfo['fullname']; ?></h3>
            </div>
            <div class="tier-rules">
                <span class="tier-rule-badge <?php echo $tierId; ?>">
                    <i class="fas fa-arrow-down"></i> Min: <?php echo $tierInfo['min']; ?>
                </span>
                <span class="tier-rule-badge <?php echo $tierId; ?>">
                    <i class="fas fa-arrow-up"></i> Max: <?php echo $tierInfo['max']; ?>
                </span>
                <span class="tier-rule-badge <?php echo $tierId; ?>">
                    <i class="fas fa-tag"></i> <?php echo $tierInfo['range']; ?>
                </span>
            </div>
        </div>
        
        <!-- Products Grid for this Tier -->
        <div class="product-grid">
            <?php foreach ($groupedProducts[$tierId]['items'] as $product): 
                // Check if image exists
                $imagePath = '/assets/images/' . $product['image'];
                $imageFile = $_SERVER['DOCUMENT_ROOT'] . $imagePath;
                
                // Check if in cart
                $inCart = isset($_SESSION['cart'][$product['id']]);
                $cartQty = $inCart ? $_SESSION['cart'][$product['id']]['quantity'] : 0;
                
                // Check if max reached
                $maxReached = $inCart && $cartQty >= $tierInfo['max'];
                
                // Highlight search term
                $productName = htmlspecialchars($product['name']);
                if (!empty($searchTerm)) {
                    $productName = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="highlight">$1</span>', $productName);
                }
            ?>
            <div class="product-card <?php echo $tierId; ?>" data-product-id="<?php echo $product['id']; ?>">
                <!-- Tier Badge -->
                <div class="product-tier-badge <?php echo $tierId; ?>">
                    <i class="fas <?php echo $tierInfo['icon']; ?>"></i>
                    <?php echo $tierInfo['name']; ?>
                </div>
                
                <!-- Best Seller Badge -->
                <?php if (isset($bestSellers[$product['id']])): ?>
                    <div class="best-seller-badge">
                        <i class="fas fa-crown"></i> Best Seller
                    </div>
                <?php endif; ?>
                
                <!-- NEW Badge -->
                <?php if ($product['is_new']): ?>
                    <div class="new-badge">
                        <i class="fas fa-star"></i> NEW
                    </div>
                <?php endif; ?>
                
                <!-- Product Image -->
                <?php if ($product['image'] && file_exists($imageFile)): ?>
                    <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                    <div style="height:180px; background:#f0f0f0; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-image fa-3x text-muted"></i>
                    </div>
                <?php endif; ?>
                
                <!-- Product Info -->
                <div class="product-info">
                    <div class="product-name"><?php echo $productName; ?></div>
                    <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                    
                    <!-- Tier Info Row -->
                    <div class="tier-info-row">
                        <div class="tier-minmax">
                            <i class="fas <?php echo $tierInfo['icon']; ?> <?php echo $tierId; ?>"></i>
                            <span><?php echo $tierInfo['min']; ?>/<?php echo $tierInfo['max']; ?></span>
                        </div>
                        <?php if ($inCart): ?>
                            <span class="cart-indicator" id="cart-indicator-<?php echo $product['id']; ?>">
                                <i class="fas fa-check-circle"></i> <?php echo $cartQty; ?> in cart
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Add to Cart Button -->
                    <?php if ($product['stock'] <= 0): ?>
                        <button class="btn-add" disabled>
                            <i class="fas fa-times-circle"></i> Out of Stock
                        </button>
                    <?php elseif ($maxReached): ?>
                        <button class="btn-add" disabled>
                            <i class="fas fa-ban"></i> Max Reached (<?php echo $tierInfo['max']; ?>)
                        </button>
                    <?php elseif (!$store_online): ?>
                        <button class="btn-add" disabled>
                            <i class="fas fa-store-slash"></i> Store Closed
                        </button>
                    <?php else: ?>
                        <button class="btn-add add-to-cart-btn" 
                                data-product-id="<?php echo $product['id']; ?>"
                                data-product-name="<?php echo htmlspecialchars($product['name']); ?>"
                                data-tier-min="<?php echo $tierInfo['min']; ?>"
                                data-tier-max="<?php echo $tierInfo['max']; ?>">
                            <i class="fas fa-cart-plus"></i> Add to Cart
                        </button>
                    <?php endif; ?>
                    
                    <!-- Warning if below minimum -->
                    <?php if ($inCart && $cartQty < $tierInfo['min']): ?>
                        <div class="warning-text" id="warning-<?php echo $product['id']; ?>">
                            <i class="fas fa-exclamation-triangle"></i> Need <?php echo $tierInfo['min'] - $cartQty; ?> more
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <?php 
        endif;
    endforeach; 
    
    if (!$hasProducts): 
    ?>
    <!-- No Products Found -->
    <div class="no-results" style="text-align: center; padding: 3rem; background: white; border-radius: 15px;">
        <i class="fas fa-search fa-3x text-muted mb-3"></i>
        <h3>No Products Found</h3>
        <p>Try adjusting your search or filter</p>
        <a href="/menu" class="btn-search" style="display: inline-block; padding: 0.8rem 2rem;">
            <i class="fas fa-times"></i> Clear
        </a>
    </div>
    <?php endif; ?>
</div>

<script>
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

// ===== UPDATE CART COUNT BADGE =====
function updateCartCount(count) {
    const cartBadge = document.getElementById('cartCountBadge');
    if (cartBadge) {
        cartBadge.textContent = count;
        cartBadge.classList.add('badgePop');
        setTimeout(() => {
            cartBadge.classList.remove('badgePop');
        }, 300);
    }
}

// ===== UPDATE PRODUCT CARD AFTER ADD =====
function updateProductCard(productId, newQty, tierMin, tierMax) {
    const productCard = document.querySelector(`.product-card[data-product-id="${productId}"]`);
    if (!productCard) return;
    
    const tierInfoRow = productCard.querySelector('.tier-info-row');
    const addButton = productCard.querySelector('.add-to-cart-btn');
    const warningDiv = document.getElementById(`warning-${productId}`);
    
    // Update or create cart indicator
    let cartIndicator = document.getElementById(`cart-indicator-${productId}`);
    if (!cartIndicator) {
        cartIndicator = document.createElement('span');
        cartIndicator.id = `cart-indicator-${productId}`;
        cartIndicator.className = 'cart-indicator';
        tierInfoRow.appendChild(cartIndicator);
    }
    cartIndicator.innerHTML = `<i class="fas fa-check-circle"></i> ${newQty} in cart`;
    
    // Check if max reached
    if (newQty >= tierMax) {
        if (addButton) {
            addButton.disabled = true;
            addButton.innerHTML = '<i class="fas fa-ban"></i> Max Reached';
        }
    }
    
    // Update or create warning if below minimum
    if (newQty < tierMin) {
        if (!warningDiv) {
            const productInfo = productCard.querySelector('.product-info');
            const newWarning = document.createElement('div');
            newWarning.id = `warning-${productId}`;
            newWarning.className = 'warning-text';
            newWarning.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Need ${tierMin - newQty} more`;
            productInfo.appendChild(newWarning);
        } else {
            warningDiv.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Need ${tierMin - newQty} more`;
        }
    } else if (warningDiv) {
        warningDiv.remove();
    }
}

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
    const cartButton = document.getElementById('cartButton');
    const warning = document.getElementById('storeClosedWarning');
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
    
    // Update cart button
    if (cartButton) {
        if (isOnline) {
            cartButton.classList.remove('disabled');
        } else {
            cartButton.classList.add('disabled');
        }
    }
    
    // Update warning
    if (warning) {
        if (isOnline) {
            warning.style.display = 'none';
        } else {
            warning.style.display = 'block';
        }
    }
    
    // Update all add-to-cart buttons
    addButtons.forEach(btn => {
        if (!isOnline) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-store-slash"></i> Store Closed';
        } else {
            // Re-enable if product is available
            const productCard = btn.closest('.product-card');
            if (productCard && !productCard.querySelector('.btn-add[disabled]')) {
                const productId = btn.dataset.productId;
                const productName = btn.dataset.productName;
                const tierMin = btn.dataset.tierMin;
                const tierMax = btn.dataset.tierMax;
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-cart-plus"></i> Add to Cart';
            }
        }
    });
}

// ===== AJAX ADD TO CART =====
document.addEventListener('DOMContentLoaded', function() {
    const addButtons = document.querySelectorAll('.add-to-cart-btn');
    
    addButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId;
            const productName = this.dataset.productName;
            const tierMin = parseInt(this.dataset.tierMin);
            const tierMax = parseInt(this.dataset.tierMax);
            const originalText = this.innerHTML;
            
            // Show loading state
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
                    
                    // Reset button after 1.5 seconds
                    setTimeout(() => {
                        this.classList.remove('success');
                        if (data.newQty < tierMax) {
                            this.innerHTML = originalText;
                            this.disabled = false;
                        }
                    }, 1500);
                    
                } else {
                    console.error('Error:', data.error);
                    this.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Failed';
                    
                    // Show error toast
                    showToast(data.error || 'Failed to add item', 'error');
                    
                    // Reset button after 2 seconds
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
                
                // Show error toast
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