<?php
// Set session cookie parameters
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// Set cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';
require_once 'includes/daily_counter.php';

// Get tab ID from cookie
$tab_id = $_COOKIE['tab_id'] ?? '';
if (empty($tab_id)) {
    // No tab ID, redirect to login
    header('Location: /staff-login?expired=1');
    exit;
}

// Generate a simple tab number for display (1, 2, 3, etc.)
if (!isset($_SESSION['tab_display_number'])) {
    // This is a new session, assign a tab number
    $_SESSION['tab_display_number'] = rand(100, 999);
}
$tab_number = $_SESSION['tab_display_number'];

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /staff-login');
    exit;
}

// Check if tab is registered OR if this is the same tab that just logged in
if (!isset($_SESSION['active_tabs'][$tab_id])) {
    // If user is logged in but tab not registered, register it now
    if (isset($_SESSION['user_id'])) {
        $_SESSION['active_tabs'][$tab_id] = [
            'user_id' => $_SESSION['user_id'],
            'last_activity' => time(),
            'tab_id' => $tab_id
        ];
    } else {
        // Not logged in and no tab - redirect
        header('Location: /staff-login?newtab=1');
        exit;
    }
}

// Verify this tab belongs to current user (if already registered)
if (isset($_SESSION['active_tabs'][$tab_id]) && 
    $_SESSION['active_tabs'][$tab_id]['user_id'] != $_SESSION['user_id']) {
    // Tab hijacking attempt
    unset($_SESSION['active_tabs'][$tab_id]);
    header('Location: /staff-login?expired=1');
    exit;
}

// Update last activity
$_SESSION['active_tabs'][$tab_id]['last_activity'] = time();

requireLogin();

$user = currentUser();
$role = $user['role'];

// Get counts for dashboard cards
$productCount = $pdo->query("SELECT COUNT(*) FROM tbl_products")->fetchColumn();
$orderCount = $pdo->query("SELECT COUNT(*) FROM tbl_orders")->fetchColumn();
$customerCount = $pdo->query("SELECT COUNT(*) FROM tbl_customers")->fetchColumn();
$lowStockCount = $pdo->query("SELECT COUNT(*) FROM tbl_products WHERE stock <= low_stock_threshold")->fetchColumn();

// Today's date
$today = date('Y-m-d');

// Get today's orders count
$todayOrders = $pdo->prepare("SELECT COUNT(*) FROM tbl_orders WHERE DATE(order_date) = ?");
$todayOrders->execute([$today]);
$todayCount = $todayOrders->fetchColumn();

// Get pending orders count
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM tbl_orders WHERE status = 'pending'")->fetchColumn();

// Get completed orders today
$completedToday = $pdo->prepare("SELECT COUNT(*) FROM tbl_orders WHERE status = 'completed' AND DATE(order_date) = ?");
$completedToday->execute([$today]);
$completedCount = $completedToday->fetchColumn();

// Get cancelled orders today
$cancelledToday = $pdo->prepare("SELECT COUNT(*) FROM tbl_orders WHERE status = 'cancelled' AND DATE(order_date) = ?");
$cancelledToday->execute([$today]);
$cancelledCount = $cancelledToday->fetchColumn();

// ===== GET STORE STATUS (for initial display) =====
$store_status = $pdo->query("SELECT is_online, offline_message, updated_at FROM tbl_store_status WHERE id = 1")->fetch();
$initial_is_online = $store_status ? (bool)$store_status['is_online'] : false;
$initial_offline_message = $store_status ? $store_status['offline_message'] : 'Store is currently closed. Please check back later.';
$initial_updated_at = $store_status ? $store_status['updated_at'] : date('Y-m-d H:i:s');

// ===== ONLINE CASHIERS MONITOR (for Admin/Manager only) =====
$onlineCashiers = [];
$totalCashiers = 0;
$allCashiers = [];

if ($role == 'admin' || $role == 'manager') {
    // Get all cashiers (for showing offline ones as well)
    $allCashiers = $pdo->query("
        SELECT id, username, created_at, status 
        FROM tbl_users 
        WHERE role = 'cashier' 
        ORDER BY username
    ")->fetchAll();
    
    // Get total cashiers count
    $totalCashiers = count($allCashiers);
    
    // Get online cashiers with their latest activity
    $onlineCashiers = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.status as account_status,
            MAX(s.last_activity) as last_activity,
            COUNT(DISTINCT s.id) as active_sessions,
            TIMESTAMPDIFF(SECOND, MAX(s.last_activity), NOW()) as seconds_ago
        FROM tbl_users u
        LEFT JOIN tbl_active_sessions s ON u.id = s.user_id 
            AND s.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        WHERE u.role = 'cashier'
        GROUP BY u.id
        ORDER BY 
            CASE WHEN MAX(s.last_activity) IS NOT NULL THEN 0 ELSE 1 END,
            MAX(s.last_activity) DESC,
            u.username ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate online count
    $onlineCount = 0;
    foreach ($onlineCashiers as $cashier) {
        if (!is_null($cashier['last_activity'])) {
            $onlineCount++;
        }
    }
}

// ===== GET ONLINE CUSTOMERS (for all staff) =====
$onlineCustomers = [];
$totalOnline = 0;
$shoppingCount = 0;

try {
    // Clean up old sessions first (older than 5 minutes)
    $pdo->exec("DELETE FROM tbl_online_customers WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");

    // Get online customers (active in last 5 minutes)
    $stmt = $pdo->query("
        SELECT 
            oc.customer_id,
            oc.customer_name,
            oc.last_activity,
            oc.current_page,
            oc.cart_count,
            TIMESTAMPDIFF(MINUTE, oc.last_activity, NOW()) as minutes_ago,
            CASE 
                WHEN oc.cart_count > 0 THEN '🛒 Shopping'
                ELSE '👀 Browsing'
            END as status,
            DATE_FORMAT(oc.last_activity, '%h:%i %p') as last_seen
        FROM tbl_online_customers oc
        WHERE oc.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY oc.last_activity DESC
    ");
    
    $onlineCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $totalOnline = count($onlineCustomers);
    
    // Get customers with items in cart
    $shoppingCount = 0;
    foreach ($onlineCustomers as $c) {
        if ($c['cart_count'] > 0) $shoppingCount++;
    }
} catch (Exception $e) {
    error_log("Failed to fetch online customers: " . $e->getMessage());
}

// Recent orders with deleted customer indicator and daily numbers
$recentOrders = $pdo->query("
    SELECT 
        o.id,
        o.customer_id,
        o.customer_name,
        o.total_amount,
        o.payment_method,
        o.status,
        o.order_date,
        u.username as cashier_name,
        CASE 
            WHEN o.customer_id IS NULL AND o.customer_name IS NOT NULL AND o.customer_name != '' THEN CONCAT(o.customer_name, ' (Deleted Account)')
            WHEN o.customer_name IS NOT NULL AND o.customer_name != '' THEN o.customer_name 
            ELSE 'Walk-in' 
        END as display_customer,
        CASE 
            WHEN o.customer_id IS NULL AND o.customer_name IS NOT NULL THEN 1 
            ELSE 0 
        END as is_deleted_customer,
        DATE(o.order_date) as order_date_only,
        UNIX_TIMESTAMP(o.order_date) as order_timestamp
    FROM tbl_orders o 
    LEFT JOIN tbl_users u ON o.created_by = u.id
    ORDER BY o.order_date DESC 
    LIMIT 50
")->fetchAll();

// Add daily order numbers to recent orders
$orderIds = [];
$latestOrderTime = 0;
foreach ($recentOrders as &$order) {
    $order_date = $order['order_date_only'];
    $order_id = $order['id'];
    $orderIds[] = $order_id;
    
    if ($order['order_timestamp'] > $latestOrderTime) {
        $latestOrderTime = $order['order_timestamp'];
    }
    
    $formatted_daily = '';
    
    if ($order_date == date('Y-m-d') && isset($_SESSION['daily_order_map'][$order_date])) {
        $daily_number = array_search($order_id, $_SESSION['daily_order_map'][$order_date]);
        if ($daily_number) {
            $formatted_daily = "ORD-" . str_pad($daily_number, 4, '0', STR_PAD_LEFT);
        }
    }
    
    if (empty($formatted_daily)) {
        $stmt = $pdo->prepare("SELECT daily_order_number FROM tbl_daily_orders_archive WHERE original_order_id = ?");
        $stmt->execute([$order_id]);
        $archive_number = $stmt->fetchColumn();
        
        if ($archive_number) {
            $formatted_daily = "ORD-" . str_pad($archive_number, 4, '0', STR_PAD_LEFT);
        }
    }
    
    $order['daily_number'] = $formatted_daily;
}

include 'includes/header.php';
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
        overflow-x: hidden;
    }

    .container-fluid {
        width: 100%;
        padding-right: 10px;
        padding-left: 10px;
        margin-right: auto;
        margin-left: auto;
    }

    .welcome-card {
        background: linear-gradient(135deg, #fff6e9, #fff);
        border-radius: 15px;
        padding: 1rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
        border-left: 6px solid #d35400;
        animation: slideIn 0.5s ease;
    }

    @keyframes slideIn {
        from { opacity: 0; transform: translateX(-20px); }
        to { opacity: 1; transform: translateX(0); }
    }

    .welcome-content h2 {
        font-weight: 600;
        margin-bottom: 0.1rem;
        font-size: 1.2rem;
    }

    .text-gradient {
        background: linear-gradient(135deg, #d35400, #e67e22);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .welcome-icon i {
        font-size: 2rem;
        color: #d35400;
        opacity: 0.6;
    }

    .bg-role {
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
        padding: 0.2rem 0.8rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 500;
    }

    .tab-number-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #17a2b8;
        color: white;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.2rem 0.6rem;
        border-radius: 50px;
        margin-left: 0.5rem;
        border: 1px solid rgba(255,255,255,0.3);
    }
    
    .tab-number-badge i {
        margin-right: 0.3rem;
        font-size: 0.6rem;
    }

    /* ===== STORE CONTROL CARD ===== */
    .store-control-card {
        background: white;
        border-radius: 15px;
        padding: 1.2rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-left: 4px solid #ff8c00;
        animation: slideIn 0.5s ease;
    }

    .store-control-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .store-control-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #ff8c00;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .store-control-header h5 i {
        font-size: 1.2rem;
    }

    .store-status-badge .badge {
        padding: 0.4rem 1rem;
        font-size: 0.8rem;
    }

    .current-status-display {
        display: flex;
        align-items: center;
        gap: 1.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 12px;
        margin-bottom: 1rem;
    }

    .status-icon i {
        font-size: 2.5rem;
    }

    .status-icon .fa-circle.text-success {
        color: #28a745;
        filter: drop-shadow(0 0 8px rgba(40, 167, 69, 0.5));
        animation: pulse 2s infinite;
    }

    .status-icon .fa-circle.text-danger {
        color: #dc3545;
        filter: drop-shadow(0 0 8px rgba(220, 53, 69, 0.5));
    }

    .status-info h4 {
        margin: 0 0 0.3rem 0;
        font-size: 1.2rem;
        font-weight: 600;
    }

    .status-info p {
        margin: 0 0 0.2rem 0;
        font-size: 0.9rem;
    }

    .store-actions {
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }

    .btn-open-store, .btn-close-store {
        padding: 0.8rem 1rem;
        border: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
    }

    .btn-open-store {
        background: linear-gradient(135deg, #28a745, #20c997);
        color: white;
    }

    .btn-open-store:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
    }

    .btn-close-store {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
    }

    .btn-close-store:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }

    .btn-open-store:disabled, .btn-close-store:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .store-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 1rem;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }

    .store-stats .stat-item {
        text-align: center;
    }

    .store-stats .stat-item .label {
        font-size: 0.7rem;
        color: #666;
        display: block;
        margin-bottom: 0.2rem;
    }

    .store-stats .stat-item .value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
    }

    @media (max-width: 768px) {
        .current-status-display {
            flex-direction: column;
            text-align: center;
            gap: 0.8rem;
        }
        
        .store-stats {
            grid-template-columns: repeat(2, 1fr);
            gap: 0.8rem;
        }
        
        .store-actions {
            margin-top: 1rem;
        }
    }

    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.8; transform: scale(1.1); }
        100% { opacity: 1; transform: scale(1); }
    }

    /* ===== ONLINE CASHIERS CARD ===== */
    .online-cashiers-card {
        background: white;
        border-radius: 15px;
        padding: 1rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
        border-left: 4px solid #17a2b8;
        animation: fadeIn 0.5s ease;
    }

    .online-cashiers-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.5rem;
        position: relative;
    }

    .online-cashiers-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #17a2b8;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .online-cashiers-header h5 i {
        margin-right: 0.5rem;
        font-size: 1.1rem;
    }

    .online-cashiers-header .badge {
        background: #17a2b8;
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-size: 0.7rem;
    }

    /* ===== SCROLLABLE CASHIER LIST ===== */
    .cashier-list-container {
        max-height: 250px;
        overflow-y: auto;
        overflow-x: hidden;
        border-radius: 10px;
        background: #f8f9fa;
        padding: 0.5rem;
        scrollbar-width: thin;
        scrollbar-color: #17a2b8 #f0f0f0;
        margin-bottom: 0.5rem;
    }

    .cashier-list-container::-webkit-scrollbar {
        width: 6px;
    }

    .cashier-list-container::-webkit-scrollbar-track {
        background: #f0f0f0;
        border-radius: 10px;
    }

    .cashier-list-container::-webkit-scrollbar-thumb {
        background: #17a2b8;
        border-radius: 10px;
    }

    .cashier-list-container::-webkit-scrollbar-thumb:hover {
        background: #138496;
    }

    .cashier-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .cashier-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.8rem;
        margin-bottom: 0.3rem;
        background: white;
        border-radius: 8px;
        border-left: 3px solid transparent;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }

    .cashier-item:last-child {
        margin-bottom: 0;
    }

    .cashier-item.online {
        border-left-color: #28a745;
        background: linear-gradient(to right, rgba(40, 167, 69, 0.05), white);
    }

    .cashier-item.offline {
        border-left-color: #dc3545;
        background: linear-gradient(to right, rgba(220, 53, 69, 0.02), white);
        opacity: 0.8;
    }

    .cashier-item:hover {
        transform: translateX(3px);
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    }

    .cashier-info {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        flex: 1;
    }

    .status-indicator {
        position: relative;
        display: inline-block;
    }

    .online-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        background: #28a745;
        border-radius: 50%;
        box-shadow: 0 0 0 rgba(40, 167, 69, 0.4);
        animation: pulse 2s infinite;
    }

    .offline-dot {
        display: inline-block;
        width: 12px;
        height: 12px;
        background: #dc3545;
        border-radius: 50%;
        opacity: 0.5;
    }

    .cashier-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: #333;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .session-count {
        background: #17a2b8;
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
    }

    .session-count i {
        font-size: 0.6rem;
    }

    .cashier-time {
        font-size: 0.75rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        min-width: 120px;
        justify-content: flex-end;
        flex-wrap: wrap;
    }

    .time-display {
        font-family: monospace;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .online-badge {
        background: #28a745;
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .offline-badge {
        background: #6c757d;
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 600;
        white-space: nowrap;
        opacity: 0.8;
    }

    /* ===== ONLINE CUSTOMERS CARD ===== */
    .online-customers-card {
        background: white;
        border-radius: 15px;
        padding: 1rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
        border-left: 4px solid #ff8c00;
        animation: fadeIn 0.5s ease;
    }

    .online-customers-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .online-customers-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        color: #ff8c00;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .online-customers-header h5 i {
        font-size: 1.1rem;
    }

    /* ===== SCROLLABLE CUSTOMER LIST ===== */
    .customer-list-container {
        max-height: 300px;
        overflow-y: auto;
        overflow-x: hidden;
        border-radius: 10px;
        background: #f8f9fa;
        padding: 0.5rem;
        scrollbar-width: thin;
        scrollbar-color: #ff8c00 #f0f0f0;
        margin-bottom: 0.5rem;
    }

    .customer-list-container::-webkit-scrollbar {
        width: 6px;
    }

    .customer-list-container::-webkit-scrollbar-track {
        background: #f0f0f0;
        border-radius: 10px;
    }

    .customer-list-container::-webkit-scrollbar-thumb {
        background: #ff8c00;
        border-radius: 10px;
    }

    .customer-list-container::-webkit-scrollbar-thumb:hover {
        background: #e07b00;
    }

    .customer-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .customer-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.8rem;
        margin-bottom: 0.3rem;
        background: white;
        border-radius: 8px;
        border-left: 3px solid #28a745;
        transition: all 0.2s ease;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        animation: slideIn 0.3s ease;
    }

    .customer-item:last-child {
        margin-bottom: 0;
    }

    .customer-item.shopping {
        border-left-color: #ff8c00;
        background: linear-gradient(to right, rgba(255, 140, 0, 0.05), white);
    }

    .customer-item:hover {
        transform: translateX(3px);
        box-shadow: 0 3px 8px rgba(0,0,0,0.1);
    }

    .customer-info {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        flex: 1;
    }

    .customer-avatar {
        width: 35px;
        height: 35px;
        background: #ff8c00;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .customer-details {
        flex: 1;
        min-width: 0;
    }

    .customer-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: #333;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .customer-badge {
        background: #28a745;
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .customer-badge.shopping {
        background: #ff8c00;
    }

    .customer-status {
        font-size: 0.7rem;
        color: #28a745;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .customer-status.shopping {
        color: #ff8c00;
    }

    .customer-status i {
        font-size: 0.5rem;
    }

    .customer-page {
        font-size: 0.65rem;
        color: #6c757d;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .customer-page i {
        margin-right: 0.2rem;
        color: #ff8c00;
    }

    .customer-time {
        font-size: 0.7rem;
        color: #6c757d;
        display: flex;
        align-items: center;
        gap: 0.3rem;
        white-space: nowrap;
    }

    .no-customers {
        text-align: center;
        padding: 2rem 1rem;
        color: #6c757d;
        font-size: 0.85rem;
        background: white;
        border-radius: 8px;
    }

    .no-customers i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: #ddd;
    }

    .new-customer {
        animation: newCustomerFlash 2s ease;
    }

    @keyframes newCustomerFlash {
        0% { background-color: #fff3cd; }
        50% { background-color: #ffe69c; }
        100% { background-color: white; }
    }

    /* ===== SCROLL HINTS ===== */
    .scroll-hint {
        display: none;
        text-align: center;
        color: #17a2b8;
        font-size: 0.7rem;
        padding: 0.3rem;
        animation: fadeInOut 2s infinite;
    }

    @keyframes fadeInOut {
        0%, 100% { opacity: 0.5; }
        50% { opacity: 1; }
    }

    .scroll-hint i {
        margin: 0 0.2rem;
        animation: slideLeft 1.5s infinite;
    }

    @keyframes slideLeft {
        0%, 100% { transform: translateX(0); }
        50% { transform: translateX(-3px); }
    }

    .realtime-timestamp {
        font-size: 0.7rem;
        color: #28a745;
        text-align: right;
        margin-top: 0.5rem;
        padding-top: 0.5rem;
        border-top: 1px dashed #dee2e6;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.3rem;
    }

    .realtime-timestamp i {
        font-size: 0.6rem;
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    .no-cashiers {
        text-align: center;
        padding: 2rem 1rem;
        color: #6c757d;
        font-size: 0.85rem;
        background: white;
        border-radius: 8px;
    }

    .no-cashiers i {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        color: #ddd;
    }

    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 0.6rem;
        margin-bottom: 1.5rem;
    }

    @media (min-width: 768px) {
        .dashboard-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
    }

    .dashboard-card {
        background: white;
        border-radius: 15px;
        padding: 0.8rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 0.8rem;
        transition: all 0.3s;
        animation: fadeInUp 0.5s ease backwards;
        animation-delay: calc(var(--i) * 0.1s);
        border: 1px solid rgba(0,0,0,0.03);
    }

    .dashboard-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .dashboard-card.highlight {
        animation: cardHighlight 1s ease;
    }

    @keyframes cardHighlight {
        0% { background-color: #fff3cd; transform: scale(1.02); }
        100% { background-color: white; transform: scale(1); }
    }

    .card-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    @media (min-width: 768px) {
        .card-icon {
            width: 60px;
            height: 60px;
            font-size: 1.8rem;
        }
    }

    .bg-primary-soft { background: rgba(211, 84, 0, 0.1); }
    .bg-success-soft { background: rgba(39, 174, 96, 0.1); }
    .bg-info-soft { background: rgba(52, 152, 219, 0.1); }
    .bg-warning-soft { background: rgba(243, 156, 18, 0.1); }

    .card-content h3 {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 0.1rem;
        color: #2c3e50;
        line-height: 1.2;
        transition: all 0.3s;
    }

    .card-content h3.count-updated {
        animation: countPop 0.3s ease;
    }

    @keyframes countPop {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); color: #d35400; }
        100% { transform: scale(1); }
    }

    @media (min-width: 768px) {
        .card-content h3 { font-size: 2rem; }
    }

    .card-content p {
        color: #7f8c8d;
        margin: 0;
        font-weight: 500;
        font-size: 0.7rem;
    }

    @media (min-width: 768px) {
        .card-content p { font-size: 0.85rem; }
    }

    .stat-badge {
        display: inline-block;
        background: #dc3545;
        color: white;
        font-size: 0.6rem;
        padding: 0.1rem 0.4rem;
        border-radius: 50px;
        margin-top: 0.1rem;
        animation: pulse 2s infinite;
    }

    .section-header {
        position: relative;
        margin-bottom: 1rem;
        padding-bottom: 0.4rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .section-header h4 {
        font-weight: 600;
        color: #2c3e50;
        display: inline-block;
        font-size: 1rem;
    }

    @media (min-width: 768px) {
        .section-header h4 { font-size: 1.3rem; }
    }

    .section-header h4 i {
        margin-right: 0.3rem;
        color: #d35400;
    }

    .section-header::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 40px;
        height: 2px;
        background: linear-gradient(135deg, #d35400, #e67e22);
        border-radius: 2px;
    }

    .view-all-link {
        background: #f8f9fa;
        color: #d35400;
        padding: 0.2rem 0.8rem;
        border-radius: 50px;
        text-decoration: none;
        font-size: 0.7rem;
        font-weight: 500;
        border: 1px solid #d35400;
        white-space: nowrap;
        transition: all 0.2s;
    }

    .view-all-link:hover {
        background: #d35400;
        color: white;
    }

    .daily-order-badge {
        display: inline-block;
        background: #008080;
        color: white;
        font-size: 0.6rem;
        padding: 0.1rem 0.4rem;
        border-radius: 50px;
        margin-left: 0.3rem;
        font-weight: 500;
        white-space: nowrap;
    }

    @media (min-width: 768px) {
        .daily-order-badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.6rem;
            margin-left: 0.5rem;
        }
    }

    .table-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 1.5rem;
        position: relative;
        height: 450px;
        display: flex;
        flex-direction: column;
        border: 1px solid rgba(0,0,0,0.05);
    }

    @media (min-width: 768px) {
        .table-container { height: 500px; }
    }

    .table-header {
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
        padding: 10px 12px;
        font-weight: 600;
        font-size: 0.85rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    @media (min-width: 768px) {
        .table-header {
            padding: 15px 20px;
            font-size: 1rem;
        }
    }

    .table-header i {
        margin-right: 5px;
        font-size: 0.8rem;
    }

    .table-header .badge {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 3px 8px;
        border-radius: 50px;
        font-size: 0.7rem;
    }

    .table-scroll {
        overflow-y: auto;
        flex: 1;
        -webkit-overflow-scrolling: touch;
    }

    .table {
        width: 100%;
        min-width: 800px;
        border-collapse: collapse;
        font-size: 0.75rem;
    }

    @media (min-width: 768px) {
        .table {
            min-width: 900px;
            font-size: 0.85rem;
        }
    }

    .table thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table thead th {
        background: #f8f9fa;
        color: #333;
        font-weight: 600;
        border-bottom: 2px solid #d35400;
        padding: 8px 6px;
        white-space: nowrap;
        font-size: 0.7rem;
        box-shadow: 0 2px 3px rgba(0,0,0,0.05);
    }

    @media (min-width: 768px) {
        .table thead th {
            padding: 12px 15px;
            font-size: 0.85rem;
        }
    }

    .table tbody td {
        padding: 8px 6px;
        font-size: 0.7rem;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
        white-space: nowrap;
        transition: background-color 0.3s;
    }

    @media (min-width: 768px) {
        .table tbody td {
            padding: 12px 15px;
            font-size: 0.85rem;
        }
    }

    .table tbody tr {
        transition: all 0.3s;
        cursor: pointer;
    }

    .table tbody tr:hover { background: #f8f9fa; }
    .table tbody tr:active { background: #e9ecef; transform: scale(0.99); }
    .table tbody tr.new-order { animation: newOrderFlash 2s ease; }

    @keyframes newOrderFlash {
        0% { background-color: #fff3cd; transform: scale(1.01); }
        50% { background-color: #ffe69c; }
        100% { background-color: transparent; transform: scale(1); }
    }

    .badge-new {
        background: #dc3545;
        color: white;
        padding: 0.1rem 0.3rem;
        border-radius: 50px;
        font-size: 0.6rem;
        margin-left: 0.2rem;
        display: inline-block;
        animation: pulse 2s infinite;
    }

    .badge.bg-order {
        background: #e67e22;
        color: white;
        padding: 0.2rem 0.4rem;
        border-radius: 50px;
        font-size: 0.65rem;
        white-space: nowrap;
    }

    .badge.bg-payment {
        background: #3498db;
        color: white;
        padding: 0.2rem 0.4rem;
        font-size: 0.65rem;
    }

    .badge.bg-completed {
        background: #27ae60;
        color: white;
        padding: 0.2rem 0.4rem;
        font-size: 0.65rem;
    }

    .badge.bg-pending {
        background: #f39c12;
        color: white;
        padding: 0.2rem 0.4rem;
        font-size: 0.65rem;
    }

    .badge.bg-cancelled {
        background: #e74c3c;
        color: white;
        padding: 0.2rem 0.4rem;
        font-size: 0.65rem;
    }

    .badge-cashier {
        background: #17a2b8;
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        font-size: 0.6rem;
        white-space: nowrap;
    }

    .btn-outline-view {
        border: 1px solid #d35400;
        color: #d35400;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        text-decoration: none;
        font-size: 0.65rem;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 0.2rem;
        white-space: nowrap;
        background: transparent;
        cursor: pointer;
    }

    .btn-outline-view:hover {
        background: #d35400;
        color: white;
    }

    .btn-outline-view i { font-size: 0.6rem; }
    .btn-outline-view.disabled {
        opacity: 0.5;
        pointer-events: none;
        border-color: #6c757d;
        color: #6c757d;
    }

    #notificationSound { display: none; }

    .sound-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-size: 0.75rem;
        cursor: pointer;
        margin-left: 0.5rem;
        border: 1px solid rgba(255,255,255,0.3);
        transition: all 0.2s ease;
    }

    .sound-toggle:hover { background: rgba(255,255,255,0.3); }
    .sound-toggle.muted { opacity: 0.7; background: rgba(108, 117, 125, 0.3); }
    .sound-toggle.muted i { color: #dc3545; }

    .scroll-to-top {
        position: fixed;
        bottom: 15px;
        right: 15px;
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        box-shadow: 0 5px 15px rgba(211,84,0,0.3);
        transition: all 0.3s;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        border: 2px solid white;
    }

    .scroll-to-top.show {
        opacity: 1;
        visibility: visible;
    }

    .scroll-to-top:hover {
        background: #ff8c00;
        transform: translateY(-3px);
    }

    .realtime-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.7rem;
        color: #28a745;
        margin-left: 0.5rem;
    }

    .realtime-indicator i {
        font-size: 0.6rem;
        animation: spin 2s linear infinite;
    }

    /* ===== MOBILE RESPONSIVE ===== */
    @media (max-width: 768px) {
        .scroll-hint {
            display: block;
        }
        
        .cashier-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .cashier-time {
            width: 100%;
            justify-content: flex-start;
            padding-left: 2rem;
        }
        
        .online-cashiers-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .cashier-list-container {
            max-height: 300px;
        }
        
        .customer-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }
        
        .customer-time {
            width: 100%;
            justify-content: flex-start;
            padding-left: 2.5rem;
        }
        
        .customer-page {
            max-width: 100%;
        }
    }

    @media (max-width: 480px) {
        .table-container {
            height: 400px;
            margin-left: -5px;
            margin-right: -5px;
            width: calc(100% + 10px);
            border-radius: 10px;
        }
        .table { min-width: 700px; }
        .badge-cashier { font-size: 0.55rem; padding: 0.15rem 0.3rem; }
        .tab-number-badge { font-size: 0.6rem; padding: 0.15rem 0.4rem; }
    }
</style>

<!-- Notification Sound Element -->
<audio id="notificationSound" preload="auto">
    <source src="/assets/sounds/notification.mp3" type="audio/mpeg">
</audio>

<div class="container-fluid">
    <!-- Welcome Card with Tab Number Badge -->
    <div class="welcome-card">
        <div class="welcome-content">
            <h2>
                Welcome, <span class="text-gradient"><?php echo htmlspecialchars($user['username']); ?></span>!
                <span class="tab-number-badge" title="Tab-specific session">
                    <i class="fas fa-window-maximize"></i> Tab #<?php echo $tab_number; ?>
                </span>
            </h2>
            <p class="mb-0">
                <i class="fas fa-user-tag me-1"></i>
                <span class="badge bg-role"><?php echo ucfirst($role); ?></span>
            </p>
        </div>
        <div class="welcome-icon">
            <i class="fas fa-utensils"></i>
        </div>
    </div>

    <?php if ($role == 'admin' || $role == 'manager'): ?>
    <!-- ===== STORE CONTROL CARD (Admin/Manager only) ===== -->
    <div class="store-control-card" id="storeControlCard">
        <div class="store-control-header">
            <h5>
                <i class="fas fa-store"></i>
                Store Status Control
                <span class="realtime-indicator" id="storeRealtimeIndicator">
                    <i class="fas fa-sync-alt fa-spin"></i> live
                </span>
            </h5>
            <div class="store-status-badge" id="storeStatusBadge">
                <?php if ($initial_is_online): ?>
                    <span class="badge bg-success">🟢 OPEN</span>
                <?php else: ?>
                    <span class="badge bg-danger">🔴 CLOSED</span>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="store-control-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="current-status-display" id="currentStatusDisplay">
                        <div class="status-icon">
                            <?php if ($initial_is_online): ?>
                                <i class="fas fa-circle text-success" id="statusIcon"></i>
                            <?php else: ?>
                                <i class="fas fa-circle text-danger" id="statusIcon"></i>
                            <?php endif; ?>
                        </div>
                        <div class="status-info">
                            <h4 id="statusText"><?php echo $initial_is_online ? 'Store is OPEN' : 'Store is CLOSED'; ?></h4>
                            <p id="statusMessage" class="text-muted"><?php echo htmlspecialchars($initial_offline_message); ?></p>
                            <p id="lastUpdated" class="small text-muted">Last updated: <?php echo date('M d, Y h:i A', strtotime($initial_updated_at)); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="store-actions">
                        <button class="btn-open-store" id="openStoreBtn" onclick="toggleStore(1)" <?php echo $initial_is_online ? 'disabled' : ''; ?>>
                            <i class="fas fa-door-open"></i> Open Store
                        </button>
                        <button class="btn-close-store" id="closeStoreBtn" onclick="showCloseModal()" <?php echo !$initial_is_online ? 'disabled' : ''; ?>>
                            <i class="fas fa-door-closed"></i> Close Store
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Quick Stats -->
            <div class="store-stats">
                <div class="stat-item">
                    <span class="label">Orders Today</span>
                    <span class="value" id="ordersToday"><?php echo $todayCount; ?></span>
                </div>
                <div class="stat-item">
                    <span class="label">Pending</span>
                    <span class="value" id="pendingToday"><?php echo $pendingOrders; ?></span>
                </div>
                <div class="stat-item">
                    <span class="label">Last Opened</span>
                    <span class="value" id="lastOpened">--:--</span>
                </div>
                <div class="stat-item">
                    <span class="label">Last Closed</span>
                    <span class="value" id="lastClosed">--:--</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Close Store Modal -->
    <div class="modal fade" id="closeStoreModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-door-closed me-2"></i>Close Store
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to close the store?</p>
                    <p class="text-muted small">Customers will not be able to place new orders while the store is closed.</p>
                    
                    <div class="mb-3">
                        <label for="closeMessage" class="form-label">Offline Message (optional)</label>
                        <textarea class="form-control" id="closeMessage" rows="2" placeholder="e.g., Store is closed for the day. We'll be back at 9:00 AM tomorrow."></textarea>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="confirmClose">
                        <label class="form-check-label" for="confirmClose">
                            I understand that customers cannot order while store is closed
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="confirmCloseBtn" onclick="closeStore()" disabled>Close Store</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Store History Modal -->
    <div class="modal fade" id="storeHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-history me-2"></i>Store Status History
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm" id="storeHistoryTable">
                            <thead>
                                <tr>
                                    <th>Date/Time</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                    <th>Updated By</th>
                                </tr>
                            </thead>
                            <tbody id="storeHistoryBody">
                                <tr>
                                    <td colspan="4" class="text-center">Loading history...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-info" onclick="viewStoreHistory()">Refresh</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ONLINE CASHIERS MONITOR - LIVE REAL-TIME UPDATES -->
    <div class="online-cashiers-card">
        <div class="online-cashiers-header">
            <h5>
                <i class="fas fa-users"></i>
                Cashiers Status
                <span class="realtime-indicator" id="cashierRealtimeIndicator">
                    <i class="fas fa-sync-alt fa-spin"></i> live
                </span>
            </h5>
            <div>
                <span class="badge" id="onlineCashiersCount">
                    <?php echo $onlineCount ?? 0 . '/' . $totalCashiers; ?> online
                </span>
            </div>
        </div>
        
        <!-- Scroll hint for mobile -->
        <div class="scroll-hint">
            <i class="fas fa-arrow-up"></i>
            <span>Scroll to see all cashiers</span>
            <i class="fas fa-arrow-down"></i>
        </div>
        
        <!-- Scrollable cashier list container -->
        <div class="cashier-list-container" id="cashierListContainer">
            <ul class="cashier-list" id="onlineCashiersList">
                <?php if (empty($onlineCashiers)): ?>
                    <li class="no-cashiers" id="noCashiersMessage">
                        <i class="fas fa-user-clock fa-3x mb-2"></i>
                        <p>No cashiers found</p>
                    </li>
                <?php else: ?>
                    <?php 
                    foreach ($onlineCashiers as $cashier): 
                        $isOnline = !is_null($cashier['last_activity']);
                    ?>
                    <li class="cashier-item <?php echo $isOnline ? 'online' : 'offline'; ?>" 
                        data-cashier-id="<?php echo $cashier['id']; ?>" 
                        data-online="<?php echo $isOnline ? '1' : '0'; ?>">
                        <div class="cashier-info">
                            <span class="status-indicator">
                                <span class="<?php echo $isOnline ? 'online-dot' : 'offline-dot'; ?>"></span>
                            </span>
                            <span class="cashier-name">
                                <?php echo htmlspecialchars($cashier['username']); ?>
                                <?php if (($cashier['active_sessions'] ?? 0) > 1): ?>
                                    <span class="session-count" title="Multiple active sessions">
                                        <i class="fas fa-window-maximize"></i> <?php echo $cashier['active_sessions']; ?>
                                    </span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="cashier-time">
                            <?php if ($isOnline): ?>
                                <i class="far fa-clock text-success"></i>
                                <span class="time-display text-success" data-timestamp="<?php echo strtotime($cashier['last_activity']); ?>">
                                    <?php echo date('h:i:s A', strtotime($cashier['last_activity'])); ?>
                                </span>
                                <span class="online-badge">ONLINE</span>
                            <?php else: ?>
                                <i class="far fa-clock text-muted"></i>
                                <span class="time-display text-muted">Offline</span>
                                <span class="offline-badge">OFFLINE</span>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="realtime-timestamp" id="lastUpdateTime">
            <i class="fas fa-sync-alt fa-spin"></i>
            Updating: <span id="updateTimestamp"><?php echo date('h:i:s A'); ?></span>
        </div>
    </div>
    <?php endif; ?>

    <!-- ===== ONLINE CUSTOMERS MONITOR (Visible to ALL staff) - OPTIMIZED ===== -->
    <div class="online-customers-card">
        <div class="online-customers-header">
            <h5>
                <i class="fas fa-users"></i>
                Online Customers
                <span class="realtime-indicator" id="customerRealtimeIndicator">
                    <i class="fas fa-sync-alt fa-spin"></i> live
                </span>
            </h5>
            <div>
                <span class="badge" id="onlineCustomersCount"><?php echo $totalOnline; ?> online</span>
                <span class="badge bg-warning ms-1" id="shoppingCustomersCount"><?php echo $shoppingCount; ?> shopping</span>
            </div>
        </div>
        
        <!-- Scroll hint for mobile -->
        <div class="scroll-hint">
            <i class="fas fa-arrow-up"></i>
            <span>Scroll to see all customers</span>
            <i class="fas fa-arrow-down"></i>
        </div>
        
        <!-- Scrollable customer list container -->
        <div class="customer-list-container" id="customerListContainer">
            <ul class="customer-list" id="onlineCustomersList">
                <?php if (empty($onlineCustomers)): ?>
                    <li class="no-customers">
                        <i class="fas fa-user-clock fa-3x mb-2"></i>
                        <p>No customers online</p>
                    </li>
                <?php else: ?>
                    <?php foreach ($onlineCustomers as $customer): 
                        $isShopping = $customer['cart_count'] > 0;
                        $statusIcon = $isShopping ? 'fa-shopping-cart' : 'fa-eye';
                        $pageName = $customer['current_page'];
                        $pageName = str_replace(['/customer-', '.php', '/'], '', $pageName);
                        if ($pageName == '' || $pageName == 'dashboard') $pageName = 'Dashboard';
                        if ($pageName == 'menu') $pageName = 'Menu';
                        if ($pageName == 'cart') $pageName = 'Cart';
                        if ($pageName == 'checkout') $pageName = 'Checkout';
                        
                        // Truncate long names
                        $displayName = strlen($customer['customer_name']) > 18 ? 
                            substr($customer['customer_name'], 0, 16) . '...' : 
                            $customer['customer_name'];
                    ?>
                    <li class="customer-item <?php echo $isShopping ? 'shopping' : ''; ?>" data-customer-id="<?php echo $customer['customer_id']; ?>">
                        <div class="customer-info">
                            <div class="customer-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="customer-details">
                                <div class="customer-name">
                                    <?php echo htmlspecialchars($displayName); ?>
                                    <?php if ($isShopping): ?>
                                        <span class="customer-badge shopping">🛒 <?php echo $customer['cart_count']; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="customer-status <?php echo $isShopping ? 'shopping' : ''; ?>">
                                    <i class="fas <?php echo $statusIcon; ?>"></i> <?php echo $customer['status']; ?>
                                </div>
                                <div class="customer-page">
                                    <i class="fas fa-globe"></i> <?php echo $pageName; ?>
                                </div>
                            </div>
                        </div>
                        <div class="customer-time">
                            <i class="far fa-clock"></i> <?php echo $customer['last_seen']; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>
        
        <div class="realtime-timestamp">
            <i class="fas fa-sync-alt fa-spin"></i>
            Last updated: <span id="customerUpdateTime"><?php echo date('h:i:s A'); ?></span>
        </div>
    </div>

    <!-- Dashboard Cards -->
    <div class="dashboard-grid">
        <?php if ($role == 'admin' || $role == 'manager'): ?>
        <div class="dashboard-card" data-card="products" style="--i:1">
            <div class="card-icon bg-primary-soft">
                <i class="fas fa-box text-primary"></i>
            </div>
            <div class="card-content">
                <h3 id="productCount"><?php echo $productCount; ?></h3>
                <p>Products</p>
            </div>
        </div>
        <?php endif; ?>

        <div class="dashboard-card" data-card="orders" style="--i:2">
            <div class="card-icon bg-success-soft">
                <i class="fas fa-shopping-cart text-success"></i>
            </div>
            <div class="card-content">
                <h3 id="totalOrders"><?php echo $orderCount; ?></h3>
                <p>Total Orders</p>
            </div>
        </div>

        <div class="dashboard-card" data-card="today" style="--i:3">
            <div class="card-icon bg-info-soft">
                <i class="fas fa-calendar-day text-info"></i>
            </div>
            <div class="card-content">
                <h3 id="todayCount"><?php echo $todayCount; ?></h3>
                <p>Today's</p>
                <?php if ($todayCount > 0): ?>
                    <span class="stat-badge" id="todayBadge">New</span>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-card" data-card="pending" style="--i:4">
            <div class="card-icon bg-warning-soft">
                <i class="fas fa-clock text-warning"></i>
            </div>
            <div class="card-content">
                <h3 id="pendingCount"><?php echo $pendingOrders; ?></h3>
                <p>Pending</p>
                <?php if ($pendingOrders > 0): ?>
                    <span class="stat-badge" id="pendingBadge"><?php echo $pendingOrders; ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="dashboard-card" data-card="customers" style="--i:5">
            <div class="card-icon bg-info-soft">
                <i class="fas fa-users text-info"></i>
            </div>
            <div class="card-content">
                <h3 id="customerCount"><?php echo $customerCount; ?></h3>
                <p>Customers</p>
            </div>
        </div>

        <div class="dashboard-card" data-card="completed" style="--i:6">
            <div class="card-icon bg-success-soft">
                <i class="fas fa-check-circle text-success"></i>
            </div>
            <div class="card-content">
                <h3 id="completedCount"><?php echo $completedCount; ?></h3>
                <p>Completed</p>
            </div>
        </div>

        <div class="dashboard-card" data-card="cancelled" style="--i:7">
            <div class="card-icon bg-danger-soft">
                <i class="fas fa-times-circle text-danger"></i>
            </div>
            <div class="card-content">
                <h3 id="cancelledCount"><?php echo $cancelledCount; ?></h3>
                <p>Cancelled</p>
            </div>
        </div>

        <?php if ($role == 'admin' || $role == 'manager'): ?>
        <div class="dashboard-card" data-card="lowstock" style="--i:8">
            <div class="card-icon bg-warning-soft">
                <i class="fas fa-exclamation-triangle text-warning"></i>
            </div>
            <div class="card-content">
                <h3 id="lowStockCount"><?php echo $lowStockCount; ?></h3>
                <p>Low Stock</p>
                <?php if ($lowStockCount > 0): ?>
                    <span class="stat-badge" id="lowStockBadge">Alert</span>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Recent Orders Section -->
    <div class="section-header">
        <h4>
            <i class="fas fa-history"></i>Recent Orders
            <span class="realtime-indicator" id="ordersRealtimeIndicator">
                <i class="fas fa-sync-alt"></i> live
            </span>
            <span class="sound-toggle" id="soundToggle" onclick="toggleSound()">
                <i class="fas fa-volume-up"></i> <span id="soundText">sound</span>
            </span>
        </h4>
        <a href="/orders" class="view-all-link">View All</a>
    </div>
    
    <div class="table-container">
        <div class="table-header">
            <div>
                <i class="fas fa-history"></i> Orders
            </div>
            <span class="badge" id="ordersCount"><?php echo count($recentOrders); ?></span>
        </div>
        <div class="table-scroll">
            <table class="table" id="recentOrdersTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Daily #</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $currentTime = time();
                    foreach ($recentOrders as $order): 
                        $isPending = ($order['status'] == 'pending');
                        $isCancelled = ($order['status'] == 'cancelled');
                        $isDeletedCustomer = $order['is_deleted_customer'] == 1;
                        $rowClass = $isPending ? 'pending-row' : ($isCancelled ? 'cancelled-row' : '');
                        if ($isDeletedCustomer) {
                            $rowClass .= ' deleted-order';
                        }
                        
                        $orderTime = strtotime($order['order_date']);
                        $minutesAgo = ($currentTime - $orderTime) / 60;
                        $isNew = ($isPending && $minutesAgo <= 30 && !$isDeletedCustomer);
                    ?>
                    <tr class="<?php echo $rowClass; ?>" data-order-id="<?php echo $order['id']; ?>" data-status="<?php echo $order['status']; ?>" data-deleted="<?php echo $isDeletedCustomer ? '1' : '0'; ?>">
                        <td>
                            <span class="badge bg-order">#<?php echo $order['id']; ?></span>
                            <?php if ($isNew): ?>
                                <span class="badge-new">NEW</span>
                            <?php endif; ?>
                            <?php if ($isDeletedCustomer): ?>
                                <span class="deleted-order-badge">DELETED</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($order['daily_number'])): ?>
                                <span class="daily-order-badge"><?php echo $order['daily_number']; ?></span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong>
                                <?php echo htmlspecialchars($order['display_customer']); ?>
                                <?php if ($isDeletedCustomer): ?>
                                    <span class="deleted-customer-badge">Account Deleted</span>
                                <?php endif; ?>
                            </strong>
                        </td>
                        <td>
                            <?php if ($order['cashier_name']): ?>
                                <span class="badge-cashier">
                                    <i class="fas fa-user"></i>
                                    <?php echo $order['cashier_name']; ?>
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary">
                                    <i class="fas fa-globe"></i> Online
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td><span class="badge bg-payment"><?php echo ucfirst($order['payment_method']); ?></span></td>
                        <td>
                            <?php
                            $statusClass = '';
                            $statusText = '';
                            if ($order['status'] == 'completed') {
                                $statusClass = 'bg-completed';
                                $statusText = 'Completed';
                            } elseif ($order['status'] == 'pending') {
                                $statusClass = 'bg-pending';
                                $statusText = 'Pending';
                            } else {
                                $statusClass = 'bg-cancelled';
                                $statusText = 'Cancelled';
                            }
                            ?>
                            <span class="badge <?php echo $statusClass; ?> order-status"><?php echo $statusText; ?></span>
                        </td>
                        <td><?php echo date('M d, H:i', strtotime($order['order_date'])); ?></td>
                        <td>
                            <?php if ($isDeletedCustomer): ?>
                                <span class="btn-outline-view disabled">
                                    <i class="fas fa-eye-slash"></i> Unavailable
                                </span>
                            <?php else: ?>
                                <a href="/orders/view/<?php echo $order['id']; ?>" class="btn-outline-view">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// ===== OPTIMIZED REAL-TIME SYSTEM =====
let lastOrderData = {};
let lastCashierData = {};
let lastCustomerData = {};
let lastStoreStatus = {};
let lastDataHash = '';
let updateInterval;
let isUpdating = false;
let consecutiveErrors = 0;
const MAX_CONSECUTIVE_ERRORS = 3;
let lastActivityTime = Date.now();

// Store initial order data with full state
document.querySelectorAll('#recentOrdersTable tbody tr').forEach(row => {
    const id = row.dataset.orderId;
    const status = row.dataset.status;
    const deleted = row.dataset.deleted === '1';
    const cashier = row.querySelector('.badge-cashier')?.textContent.trim() || 'Online';
    const amount = row.querySelector('td:nth-child(5)')?.textContent.replace('₱', '') || '0';
    
    lastOrderData[id] = { 
        status, 
        deleted,
        cashier,
        amount,
        row: row.outerHTML,
        timestamp: Date.now()
    };
});

// ===== DEVICE ID =====
let deviceId = localStorage.getItem('staff_device_id');
if (!deviceId) {
    deviceId = 'staff_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
    localStorage.setItem('staff_device_id', deviceId);
}

// ===== TAB-SPECIFIC SESSION MANAGEMENT =====
let tabId = '<?php echo $tab_id; ?>';
let tabNumber = <?php echo $tab_number; ?>;
let userRole = '<?php echo $role; ?>';

console.log('🚀 Staff Dashboard initialized - Tab #' + tabNumber);

// Send heartbeat to keep tab session alive
function sendHeartbeat() {
    fetch('/api/tab_session.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=verify_tab&tab_id=' + encodeURIComponent(tabId)
    })
    .then(response => response.json())
    .then(data => {
        if (!data.valid) {
            console.log('⚠️ Tab session expired, redirecting...');
            window.location.href = '/staff-login?expired=1';
        } else {
            lastActivityTime = Date.now();
        }
    })
    .catch(error => {
        console.error('Heartbeat error:', error);
    });
}

// Send heartbeat every 15 seconds
setInterval(sendHeartbeat, 15000);

// Register tab on load
fetch('/api/tab_session.php', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
    },
    body: 'action=register_tab&tab_id=' + encodeURIComponent(tabId)
});

// Remove tab on unload
window.addEventListener('beforeunload', function() {
    navigator.sendBeacon('/api/tab_session.php', 
        'action=remove_tab&tab_id=' + encodeURIComponent(tabId));
});

// ===== SOUND TOGGLE =====
let soundEnabled = localStorage.getItem('staffSoundEnabled') !== 'false';
let lastNotificationTime = 0;
let lastSeenOrderId = 0;
const NOTIFICATION_COOLDOWN = 3000;

<?php if (!empty($orderIds)): ?>
lastSeenOrderId = <?php echo max($orderIds); ?>;
<?php endif; ?>

let audioInitialized = false;
function initAudio() {
    if (audioInitialized) return;
    
    const sound = document.getElementById('notificationSound');
    if (sound) {
        sound.volume = 0.01;
        sound.play().then(() => {
            sound.pause();
            sound.currentTime = 0;
            sound.volume = 0.7;
            audioInitialized = true;
            console.log('✅ Audio initialized');
        }).catch(e => console.log('Audio init failed:', e));
    }
}

function toggleSound() {
    soundEnabled = !soundEnabled;
    localStorage.setItem('staffSoundEnabled', soundEnabled);
    updateSoundToggleUI();
    
    const toggle = document.getElementById('soundToggle');
    toggle.style.transform = 'scale(0.95)';
    toggle.style.backgroundColor = soundEnabled ? '#28a745' : '#dc3545';
    setTimeout(() => {
        toggle.style.transform = '';
        toggle.style.backgroundColor = '';
    }, 300);
}

function updateSoundToggleUI() {
    const toggle = document.getElementById('soundToggle');
    const soundText = document.getElementById('soundText');
    
    if (toggle) {
        if (soundEnabled) {
            toggle.innerHTML = '<i class="fas fa-volume-up"></i> <span id="soundText">sound</span>';
            toggle.classList.remove('muted');
        } else {
            toggle.innerHTML = '<i class="fas fa-volume-mute"></i> <span id="soundText">muted</span>';
            toggle.classList.add('muted');
        }
    }
}

function playNotificationSound() {
    if (!soundEnabled) return false;
    
    const now = Date.now();
    if (now - lastNotificationTime < NOTIFICATION_COOLDOWN) return false;
    
    const sound = document.getElementById('notificationSound');
    if (sound) {
        sound.currentTime = 0;
        sound.play()
            .then(() => {
                lastNotificationTime = now;
                return true;
            })
            .catch(() => false);
    }
    return false;
}

document.addEventListener('DOMContentLoaded', function() {
    updateSoundToggleUI();
    
    const initEvents = ['click', 'touchstart', 'keydown'];
    function handleFirstInteraction() {
        initAudio();
        initEvents.forEach(event => {
            document.removeEventListener(event, handleFirstInteraction);
        });
    }
    initEvents.forEach(event => {
        document.addEventListener(event, handleFirstInteraction, { once: true });
    });
});

// ===== SCROLL TO TOP =====
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.addEventListener('scroll', function() {
    const btn = document.getElementById('scrollToTop');
    if (window.scrollY > 300) btn.classList.add('show');
    else btn.classList.remove('show');
});

// ===== MARK AS SEEN =====
function markAsSeen() {
    fetch('/api/get_realtime_data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'device_id=' + encodeURIComponent(deviceId) + '&mark_seen=true'
    }).catch(error => console.error('Error marking seen'));
}

document.addEventListener('click', markAsSeen);
document.addEventListener('scroll', markAsSeen);
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) markAsSeen();
});

// ===== INTELLIGENT DATA FETCHING =====
function fetchStaffUpdates() {
    if (isUpdating) return;
    
    isUpdating = true;
    
    // Add timestamp to prevent caching
    const url = '/api/get_realtime_data.php?device_id=' + encodeURIComponent(deviceId) + '&t=' + Date.now();
    
    fetch(url)
        .then(res => {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
        })
        .then(data => {
            // Reset error counter on success
            consecutiveErrors = 0;
            
            if (!data.success || data.userType !== 'staff') {
                isUpdating = false;
                return;
            }
            
            // Log update for debugging (remove in production)
            console.log('🔄 Staff update at', new Date().toLocaleTimeString(), 
                        '| New orders:', data.hasNewOrders ? 'YES' : 'NO',
                        '| Pending:', data.pendingCount);
            
            // Create a hash of critical data to detect real changes
            const criticalHash = JSON.stringify({
                today: data.todayCount,
                pending: data.pendingCount,
                completed: data.completedCount,
                cancelled: data.cancelledCount,
                total: data.totalOrders,
                lowStock: data.lowStockCount,
                orderIds: data.recentOrders?.map(o => o.id + o.status).join(''),
                cashierIds: data.onlineCashiers?.map(c => c.id + (c.last_activity || '')).join('')
            });
            
            // Only update UI if data actually changed
            if (criticalHash !== lastDataHash) {
                console.log('📊 Data changed, updating UI...');
                updateAllUI(data);
                lastDataHash = criticalHash;
            }
            
            isUpdating = false;
        })
        .catch(error => {
            console.error('❌ Error fetching staff updates:', error);
            consecutiveErrors++;
            
            // If too many errors, slow down polling
            if (consecutiveErrors >= MAX_CONSECUTIVE_ERRORS) {
                console.warn('⚠️ Too many errors, slowing down updates');
                if (updateInterval) {
                    clearInterval(updateInterval);
                    updateInterval = setInterval(fetchStaffUpdates, 10000); // 10 seconds
                }
            }
            
            isUpdating = false;
        });
}

// ===== UPDATE ALL UI COMPONENTS =====
function updateAllUI(data) {
    // Update dashboard counts with animation
    updateCounts(data);
    
    // Update badges
    updateBadges(data);
    
    // Update online cashiers
    if (data.onlineCashiers) {
        updateOnlineCashiers(data.onlineCashiers);
    }
    
    // Check for new orders and play sound
    if (data.hasNewOrders) {
        handleNewOrders(data);
    }
    
    // Update orders table if changed
    if (data.recentOrders && hasOrdersChanged(data.recentOrders)) {
        updateOrdersTable(data.recentOrders);
        
        // Flash table header
        const tableHeader = document.querySelector('.table-header');
        if (tableHeader) {
            tableHeader.style.backgroundColor = '#28a745';
            tableHeader.style.transition = 'background-color 0.5s';
            setTimeout(() => {
                tableHeader.style.backgroundColor = '';
            }, 500);
        }
    }
}

// ===== UPDATE COUNTS WITH ANIMATION =====
function updateCounts(data) {
    const countElements = [
        { id: 'todayCount', value: data.todayCount },
        { id: 'pendingCount', value: data.pendingCount },
        { id: 'completedCount', value: data.completedCount },
        { id: 'cancelledCount', value: data.cancelledCount },
        { id: 'totalOrders', value: data.totalOrders },
        { id: 'lowStockCount', value: data.lowStockCount },
        { id: 'ordersToday', value: data.todayCount } // For store stats
    ];
    
    countElements.forEach(item => {
        const el = document.getElementById(item.id);
        if (el && item.value !== undefined) {
            const oldValue = parseInt(el.textContent) || 0;
            const newValue = parseInt(item.value) || 0;
            
            if (oldValue !== newValue) {
                el.textContent = newValue;
                el.classList.add('count-updated');
                setTimeout(() => el.classList.remove('count-updated'), 300);
                
                // Highlight parent card
                const card = el.closest('.dashboard-card, .stat-item');
                if (card) {
                    card.classList.add('highlight');
                    setTimeout(() => card.classList.remove('highlight'), 500);
                }
            }
        }
    });
}

// ===== UPDATE BADGES =====
function updateBadges(data) {
    // Today badge
    const todayBadge = document.getElementById('todayBadge');
    if (todayBadge) {
        todayBadge.style.display = data.todayCount > 0 ? 'inline-block' : 'none';
    }
    
    // Pending badge
    const pendingBadge = document.getElementById('pendingBadge');
    if (pendingBadge) {
        if (data.pendingCount > 0) {
            pendingBadge.textContent = data.pendingCount;
            pendingBadge.style.display = 'inline-block';
        } else {
            pendingBadge.style.display = 'none';
        }
    }
    
    // Low stock badge
    const lowStockBadge = document.getElementById('lowStockBadge');
    if (lowStockBadge) {
        lowStockBadge.style.display = data.lowStockCount > 0 ? 'inline-block' : 'none';
    }
}

// ===== HANDLE NEW ORDERS =====
function handleNewOrders(data) {
    // Play notification sound
    playNotificationSound();
    
    // Flash pending card
    const pendingCard = document.querySelector('[data-card="pending"]');
    if (pendingCard) {
        pendingCard.classList.add('highlight');
        setTimeout(() => pendingCard.classList.remove('highlight'), 1000);
    }
    
    // Show browser notification if permitted
    if ("Notification" in window && Notification.permission === "granted") {
        const newCount = data.newOrdersCount || 1;
        new Notification('🆕 New Order' + (newCount > 1 ? 's' : ''), {
            body: `${newCount} new order${newCount > 1 ? 's have' : ' has'} arrived!`,
            icon: '/assets/images/owner.jpg',
            badge: '/assets/images/owner.jpg',
            silent: true
        });
    }
}

// ===== CHECK IF ORDERS HAVE CHANGED =====
function hasOrdersChanged(newOrders) {
    if (!newOrders || newOrders.length === 0) return false;
    
    // Check if count changed
    if (newOrders.length !== Object.keys(lastOrderData).length) {
        console.log('📊 Order count changed from', Object.keys(lastOrderData).length, 'to', newOrders.length);
        return true;
    }
    
    // Check each order for changes
    for (let i = 0; i < newOrders.length; i++) {
        const order = newOrders[i];
        const prevData = lastOrderData[order.id];
        
        if (!prevData) {
            console.log('🆕 New order detected:', order.id);
            return true;
        }
        
        if (prevData.status !== order.status) {
            console.log('📝 Order status changed:', order.id, prevData.status, '→', order.status);
            return true;
        }
        
        if (prevData.deleted !== (order.is_deleted_customer == 1)) {
            console.log('🗑️ Order deleted status changed:', order.id);
            return true;
        }
    }
    
    return false;
}

// ===== UPDATE ONLINE CASHIERS =====
function updateOnlineCashiers(cashiers) {
    const cashierList = document.getElementById('onlineCashiersList');
    const countBadge = document.getElementById('onlineCashiersCount');
    const updateTime = document.getElementById('updateTimestamp');
    
    if (!cashierList) return;
    
    if (!cashiers || cashiers.length === 0) {
        const emptyHtml = '<li class="no-cashiers"><i class="fas fa-user-clock fa-3x mb-2"></i><p>No cashiers found</p></li>';
        if (cashierList.innerHTML !== emptyHtml) {
            cashierList.innerHTML = emptyHtml;
        }
        if (countBadge) countBadge.textContent = '0/0 online';
        return;
    }
    
    let onlineCount = 0;
    let html = '';
    
    cashiers.forEach(cashier => {
        const isOnline = cashier.last_activity !== null;
        if (isOnline) onlineCount++;
        
        let timeDisplay = 'Offline';
        let timeClass = 'text-muted';
        let badgeClass = 'offline-badge';
        let badgeText = 'OFFLINE';
        
        if (isOnline) {
            const loginTime = new Date(cashier.last_activity);
            const hours = loginTime.getHours().toString().padStart(2, '0');
            const minutes = loginTime.getMinutes().toString().padStart(2, '0');
            const seconds = loginTime.getSeconds().toString().padStart(2, '0');
            const ampm = hours >= 12 ? 'PM' : 'AM';
            const displayHours = hours % 12 || 12;
            timeDisplay = `${displayHours.toString().padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;
            timeClass = 'text-success';
            badgeClass = 'online-badge';
            badgeText = 'ONLINE';
        }
        
        html += `<li class="cashier-item ${isOnline ? 'online' : 'offline'}" data-cashier-id="${cashier.id}" data-online="${isOnline ? '1' : '0'}">
            <div class="cashier-info">
                <span class="status-indicator">
                    <span class="${isOnline ? 'online-dot' : 'offline-dot'}"></span>
                </span>
                <span class="cashier-name">
                    ${escapeHtml(cashier.username)}
                    ${(cashier.active_sessions || 0) > 1 ? 
                        `<span class="session-count" title="Multiple active sessions">
                            <i class="fas fa-window-maximize"></i> ${cashier.active_sessions}
                        </span>` : ''}
                </span>
            </div>
            <div class="cashier-time">
                ${isOnline ? 
                    `<i class="far fa-clock text-success"></i>` : 
                    `<i class="far fa-clock text-muted"></i>`}
                <span class="time-display ${timeClass}" data-timestamp="${cashier.last_activity ? new Date(cashier.last_activity).getTime()/1000 : ''}">
                    ${timeDisplay}
                </span>
                <span class="${badgeClass}">${badgeText}</span>
            </div>
        </li>`;
    });
    
    if (cashierList.innerHTML !== html) {
        cashierList.innerHTML = html;
    }
    
    if (countBadge && countBadge.textContent !== `${onlineCount}/${cashiers.length} online`) {
        countBadge.textContent = `${onlineCount}/${cashiers.length} online`;
    }
    
    if (updateTime) {
        const now = new Date();
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const seconds = now.getSeconds().toString().padStart(2, '0');
        const ampm = hours >= 12 ? 'PM' : 'AM';
        const displayHours = hours % 12 || 12;
        const timeStr = `${displayHours.toString().padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;
        if (updateTime.textContent !== timeStr) {
            updateTime.textContent = timeStr;
        }
    }
}

// ===== UPDATE ORDERS TABLE =====
function updateOrdersTable(orders) {
    const tbody = document.querySelector('#recentOrdersTable tbody');
    if (!tbody) return;
    
    let html = '';
    const now = Math.floor(Date.now() / 1000);
    
    orders.forEach(order => {
        const isPending = order.status === 'pending';
        const isCancelled = order.status === 'cancelled';
        const isDeleted = order.is_deleted_customer == 1;
        const rowClass = isPending ? 'pending-row' : (isCancelled ? 'cancelled-row' : '');
        const finalClass = isDeleted ? rowClass + ' deleted-order' : rowClass;
        
        const orderTime = order.order_timestamp || (new Date(order.order_date).getTime() / 1000);
        const minsAgo = (now - orderTime) / 60;
        const isNew = isPending && minsAgo <= 30 && !isDeleted;
        
        const date = new Date(order.order_date);
        const formattedDate = date.toLocaleDateString('en-US', {
            month: 'short', day: 'numeric'
        });
        const formattedTime = date.toLocaleTimeString('en-US', {
            hour: '2-digit', minute: '2-digit'
        });
        
        let statusClass = order.status === 'completed' ? 'bg-completed' : 
                         order.status === 'pending' ? 'bg-pending' : 'bg-cancelled';
        let statusText = order.status.charAt(0).toUpperCase() + order.status.slice(1);
        let paymentMethod = order.payment_method.charAt(0).toUpperCase() + order.payment_method.slice(1);
        let cashierBadge = order.cashier_name ? 
            `<span class="badge-cashier"><i class="fas fa-user"></i> ${escapeHtml(order.cashier_name)}</span>` :
            '<span class="badge bg-secondary"><i class="fas fa-globe"></i> Online</span>';
        
        let dailyNumber = order.daily_number ? 
            `<span class="daily-order-badge">${order.daily_number}</span>` : 
            '<span class="text-muted">—</span>';
        
        html += `<tr class="${finalClass}" data-order-id="${order.id}" data-status="${order.status}" data-deleted="${isDeleted ? '1' : '0'}">
            <td>
                <span class="badge bg-order">#${order.id}</span>
                ${isNew ? '<span class="badge-new">NEW</span>' : ''}
                ${isDeleted ? '<span class="deleted-order-badge">DELETED</span>' : ''}
            </td>
            <td>${dailyNumber}</td>
            <td>
                <strong>${escapeHtml(order.display_customer)}</strong>
                ${isDeleted ? '<br><small class="text-muted">Account Deleted</small>' : ''}
            </td>
            <td>${cashierBadge}</td>
            <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
            <td><span class="badge bg-payment">${paymentMethod}</span></td>
            <td><span class="badge ${statusClass} order-status">${statusText}</span></td>
            <td>${formattedDate}<br><small class="text-muted">${formattedTime}</small></td>
            <td>
                ${isDeleted ? 
                    '<span class="btn-outline-view disabled"><i class="fas fa-eye-slash"></i> Unavailable</span>' : 
                    `<a href="/orders/view/${order.id}" class="btn-outline-view"><i class="fas fa-eye"></i> View</a>`
                }
            </td>
        </tr>`;
    });
    
    if (tbody.innerHTML !== html) {
        tbody.innerHTML = html;
        const countSpan = document.getElementById('ordersCount');
        if (countSpan) {
            countSpan.textContent = orders.length;
        }
        
        // Update lastOrderData
        lastOrderData = {};
        orders.forEach(order => {
            lastOrderData[order.id] = { 
                status: order.status, 
                deleted: order.is_deleted_customer == 1,
                cashier: order.cashier_name || 'Online',
                amount: order.total_amount
            };
        });
    }
}

// ===== ESCAPE HTML =====
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===== STORE STATUS FUNCTIONS =====
let storeUpdateInterval;

function initializeStoreUI() {
    const badge = document.getElementById('storeStatusBadge');
    const icon = document.getElementById('statusIcon');
    const statusText = document.getElementById('statusText');
    const message = document.getElementById('statusMessage');
    const lastUpdated = document.getElementById('lastUpdated');
    const openBtn = document.getElementById('openStoreBtn');
    const closeBtn = document.getElementById('closeStoreBtn');
    
    if (badge) {
        badge.innerHTML = <?php echo $initial_is_online ? "'<span class=\"badge bg-success\">🟢 OPEN</span>'" : "'<span class=\"badge bg-danger\">🔴 CLOSED</span>'"; ?>;
    }
    
    if (icon) {
        icon.className = <?php echo $initial_is_online ? "'fas fa-circle text-success'" : "'fas fa-circle text-danger'"; ?>;
    }
    
    if (statusText) {
        statusText.textContent = <?php echo $initial_is_online ? "'Store is OPEN'" : "'Store is CLOSED'"; ?>;
    }
    
    if (message) {
        message.textContent = '<?php echo addslashes($initial_offline_message); ?>';
    }
    
    if (lastUpdated) {
        lastUpdated.textContent = 'Last updated: <?php echo date('M d, Y h:i A', strtotime($initial_updated_at)); ?>';
    }
    
    if (openBtn) openBtn.disabled = <?php echo $initial_is_online ? 'true' : 'false'; ?>;
    if (closeBtn) closeBtn.disabled = <?php echo $initial_is_online ? 'false' : 'true'; ?>;
}

function fetchStoreStatus() {
    fetch('/api/store_status.php?action=get_status&t=' + Date.now())
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
    const badge = document.getElementById('storeStatusBadge');
    const icon = document.getElementById('statusIcon');
    const statusText = document.getElementById('statusText');
    const message = document.getElementById('statusMessage');
    const lastUpdated = document.getElementById('lastUpdated');
    const openBtn = document.getElementById('openStoreBtn');
    const closeBtn = document.getElementById('closeStoreBtn');
    
    if (badge) {
        badge.innerHTML = isOnline ? 
            '<span class="badge bg-success">🟢 OPEN</span>' : 
            '<span class="badge bg-danger">🔴 CLOSED</span>';
    }
    
    if (icon) {
        icon.className = isOnline ? 'fas fa-circle text-success' : 'fas fa-circle text-danger';
    }
    
    if (statusText) {
        statusText.textContent = isOnline ? 'Store is OPEN' : 'Store is CLOSED';
    }
    
    if (message) {
        message.textContent = data.offline_message || (isOnline ? 'Customers can place orders' : 'Customers cannot place orders');
    }
    
    if (lastUpdated && data.updated_at) {
        const date = new Date(data.updated_at.replace(' ', 'T'));
        lastUpdated.textContent = `Last updated: ${date.toLocaleString()}`;
        
        if (isOnline) {
            document.getElementById('lastOpened').textContent = date.toLocaleTimeString();
        } else {
            document.getElementById('lastClosed').textContent = date.toLocaleTimeString();
        }
    }
    
    if (openBtn) openBtn.disabled = isOnline;
    if (closeBtn) closeBtn.disabled = !isOnline;
}

function toggleStore(status) {
    const message = status ? '' : document.getElementById('closeMessage')?.value || '';
    
    fetch('/api/store_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=toggle_store&status=' + status + '&message=' + encodeURIComponent(message)
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('closeStoreModal'));
            if (modal) modal.hide();
            alert(data.message);
            fetchStoreStatus();
        } else {
            alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error toggling store:', error);
        alert('Failed to toggle store status');
    });
}

function showCloseModal() {
    document.getElementById('confirmClose').checked = false;
    document.getElementById('confirmCloseBtn').disabled = true;
    document.getElementById('closeMessage').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('closeStoreModal'));
    modal.show();
}

function closeStore() {
    toggleStore(0);
}

// Enable close button only when checkbox is checked
document.getElementById('confirmClose')?.addEventListener('change', function() {
    document.getElementById('confirmCloseBtn').disabled = !this.checked;
});

// View store history
function viewStoreHistory() {
    fetch('/api/store_status.php?action=get_history')
        .then(res => res.json())
        .then(data => {
            if (data.success && data.history) {
                const tbody = document.getElementById('storeHistoryBody');
                let html = '';
                
                data.history.forEach(item => {
                    const date = new Date(item.updated_at.replace(' ', 'T'));
                    const status = item.is_online ? 
                        '<span class="badge bg-success">OPEN</span>' : 
                        '<span class="badge bg-danger">CLOSED</span>';
                    
                    html += `<tr>
                        <td>${date.toLocaleString()}</td>
                        <td>${status}</td>
                        <td>${item.offline_message || '-'}</td>
                        <td>${item.username || 'System'}</td>
                    </tr>`;
                });
                
                tbody.innerHTML = html;
                
                const modal = new bootstrap.Modal(document.getElementById('storeHistoryModal'));
                modal.show();
            }
        })
        .catch(error => console.error('Error fetching history:', error));
}

// ===== ONLINE CUSTOMERS TRACKING - OPTIMIZED =====
let lastCustomerIds = [];
let lastCustomerDataString = '';
let customerUpdateInterval;

function fetchOnlineCustomers() {
    fetch('/api/get_online_customers.php?t=' + Date.now())
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Only update if data actually changed
                const newDataString = JSON.stringify(data.online_customers);
                if (newDataString !== lastCustomerDataString) {
                    console.log('🔄 Online customers updated');
                    updateOnlineCustomers(data);
                    lastCustomerDataString = newDataString;
                }
                // Always update timestamp
                const timeElement = document.getElementById('customerUpdateTime');
                if (timeElement) timeElement.textContent = data.timestamp;
            }
        })
        .catch(error => console.error('Error fetching online customers:', error));
}

function updateOnlineCustomers(data) {
    const customerList = document.getElementById('onlineCustomersList');
    const totalSpan = document.getElementById('onlineCustomersCount');
    const shoppingSpan = document.getElementById('shoppingCustomersCount');
    
    if (!customerList) return;
    
    // Check for new customers (for sound notification)
    const currentCustomerIds = data.online_customers.map(c => c.customer_id);
    if (lastCustomerIds.length > 0) {
        const newCustomers = currentCustomerIds.filter(id => !lastCustomerIds.includes(id));
        if (newCustomers.length > 0) {
            playNotificationSound();
        }
    }
    lastCustomerIds = currentCustomerIds;
    
    if (data.total_online === 0) {
        const emptyHtml = '<li class="no-customers"><i class="fas fa-user-clock fa-3x mb-2"></i><p>No customers online</p></li>';
        if (customerList.innerHTML !== emptyHtml) {
            customerList.innerHTML = emptyHtml;
        }
    } else {
        let html = '';
        data.online_customers.forEach(customer => {
            const isShopping = customer.cart_count > 0;
            const statusIcon = isShopping ? 'fa-shopping-cart' : 'fa-eye';
            let pageName = customer.current_page || '';
            pageName = pageName.replace(/^\/|\.[^.]*$/g, '') || 'Home';
            if (pageName.includes('?')) pageName = pageName.split('?')[0];
            if (pageName === '' || pageName === 'dashboard') pageName = 'Dashboard';
            if (pageName === 'menu') pageName = 'Menu';
            if (pageName === 'cart') pageName = 'Cart';
            if (pageName === 'checkout') pageName = 'Checkout';
            
            // Truncate long names
            const displayName = customer.customer_name.length > 18 ? 
                customer.customer_name.substring(0, 16) + '...' : 
                customer.customer_name;
            
            html += `<li class="customer-item ${isShopping ? 'shopping' : ''}" data-customer-id="${customer.customer_id}">
                <div class="customer-info">
                    <div class="customer-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="customer-details">
                        <div class="customer-name">
                            ${escapeHtml(displayName)}
                            ${isShopping ? `<span class="customer-badge shopping">🛒 ${customer.cart_count}</span>` : ''}
                        </div>
                        <div class="customer-status ${isShopping ? 'shopping' : ''}">
                            <i class="fas ${statusIcon}"></i> ${customer.status}
                        </div>
                        <div class="customer-page">
                            <i class="fas fa-globe"></i> ${pageName}
                        </div>
                    </div>
                </div>
                <div class="customer-time">
                    <i class="far fa-clock"></i> ${customer.last_seen}
                </div>
            </li>`;
        });
        
        // Only update if content changed
        if (customerList.innerHTML !== html) {
            customerList.innerHTML = html;
        }
    }
    
    if (totalSpan && totalSpan.textContent !== data.total_online + ' online') {
        totalSpan.textContent = data.total_online + ' online';
    }
    if (shoppingSpan && shoppingSpan.textContent !== data.shopping_count + ' shopping') {
        shoppingSpan.textContent = data.shopping_count + ' shopping';
    }
}

// ===== REQUEST NOTIFICATION PERMISSION =====
if ("Notification" in window && Notification.permission === "default") {
    Notification.requestPermission();
}

// ===== START ALL INTERVALS =====
// Clear any existing interval before starting a new one
if (updateInterval) {
    clearInterval(updateInterval);
}

// Start real-time updates (check every 3 seconds for accuracy)
updateInterval = setInterval(fetchStaffUpdates, 3000);

// Initialize store UI with initial data
initializeStoreUI();

// Start store status updates
storeUpdateInterval = setInterval(fetchStoreStatus, 5000);

// Start online customers updates - OPTIMIZED with 8-second interval
fetchOnlineCustomers();
customerUpdateInterval = setInterval(fetchOnlineCustomers, 8000);

// Initial fetch
setTimeout(() => {
    fetchStaffUpdates();
}, 500);

// Auto-hide alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.transition = 'opacity 0.5s';
        a.style.opacity = '0';
        setTimeout(() => a.style.display = 'none', 500);
    });
}, 5000);

// Touch-friendly cards
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.dashboard-card').forEach(c => {
        c.addEventListener('touchstart', function() { this.style.transform = 'scale(0.98)'; });
        c.addEventListener('touchend', function() { this.style.transform = ''; });
        c.addEventListener('touchcancel', function() { this.style.transform = ''; });
    });
});

// Pull to refresh (mobile)
let touchStart = 0;
document.addEventListener('touchstart', e => touchStart = e.changedTouches[0].screenY);
document.addEventListener('touchend', e => {
    const dist = e.changedTouches[0].screenY - touchStart;
    if (window.scrollY === 0 && dist > 100) {
        fetchStaffUpdates();
        fetchStoreStatus();
        fetchOnlineCustomers();
        const ind = document.getElementById('ordersRealtimeIndicator');
        ind.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> refreshing';
        setTimeout(() => ind.innerHTML = '<i class="fas fa-sync-alt"></i> live', 1000);
    }
});

// Clean up intervals on page unload
window.addEventListener('beforeunload', function() {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
    if (storeUpdateInterval) {
        clearInterval(storeUpdateInterval);
    }
    if (customerUpdateInterval) {
        clearInterval(customerUpdateInterval);
    }
});

// Focus handling - update when tab becomes active
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        console.log('📱 Tab became active, fetching updates...');
        fetchStaffUpdates();
        fetchStoreStatus();
        fetchOnlineCustomers();
    }
});
</script>

<?php include 'includes/footer.php'; ?>