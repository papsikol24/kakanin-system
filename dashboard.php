<?php
require_once 'includes/config.php';
require_once 'includes/daily_counter.php'; // Include daily counter functions
requireLogin();

$user = currentUser();
$role = $user['role'];

// Get counts for dashboard cards
$productCount = $pdo->query("SELECT COUNT(*) FROM tbl_products")->fetchColumn();
$orderCount = $pdo->query("SELECT COUNT(*) FROM tbl_orders")->fetchColumn();
$customerCount = $pdo->query("SELECT COUNT(*) FROM tbl_customers")->fetchColumn();
$lowStockCount = $pdo->query("SELECT COUNT(*) FROM tbl_products WHERE stock <= low_stock_threshold")->fetchColumn();

// ===== TODAY'S DATE =====
$today = date('Y-m-d');

// ===== GET TODAY'S ORDERS COUNT =====
$todayOrders = $pdo->prepare("SELECT COUNT(*) FROM tbl_orders WHERE DATE(order_date) = ?");
$todayOrders->execute([$today]);
$todayCount = $todayOrders->fetchColumn();

// ===== GET PENDING ORDERS COUNT =====
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM tbl_orders WHERE status = 'pending'")->fetchColumn();

// ===== GET COMPLETED ORDERS TODAY =====
$completedToday = $pdo->prepare("SELECT COUNT(*) FROM tbl_orders WHERE status = 'completed' AND DATE(order_date) = ?");
$completedToday->execute([$today]);
$completedCount = $completedToday->fetchColumn();

// ===== GET CANCELLED ORDERS TODAY =====
$cancelledToday = $pdo->prepare("SELECT COUNT(*) FROM tbl_orders WHERE status = 'cancelled' AND DATE(order_date) = ?");
$cancelledToday->execute([$today]);
$cancelledCount = $cancelledToday->fetchColumn();

// ===== RECENT ORDERS WITH DELETED CUSTOMER INDICATOR AND DAILY NUMBERS =====
$recentOrders = $pdo->query("
    SELECT 
        o.id,
        o.customer_id,
        o.customer_name,
        o.total_amount,
        o.payment_method,
        o.status,
        o.order_date,
        CASE 
            WHEN o.customer_id IS NULL AND o.customer_name IS NOT NULL AND o.customer_name != '' THEN CONCAT(o.customer_name, ' (Deleted Account)')
            WHEN o.customer_name IS NOT NULL AND o.customer_name != '' THEN o.customer_name 
            ELSE 'Walk-in' 
        END as display_customer,
        CASE 
            WHEN o.customer_id IS NULL AND o.customer_name IS NOT NULL THEN 1 
            ELSE 0 
        END as is_deleted_customer,
        DATE(o.order_date) as order_date_only
    FROM tbl_orders o 
    ORDER BY o.order_date DESC 
    LIMIT 50
")->fetchAll();

// Add daily order numbers to recent orders
foreach ($recentOrders as &$order) {
    $order_date = $order['order_date_only'];
    $order_id = $order['id'];
    $formatted_daily = '';
    
    // Check if this is today's order (in session)
    if ($order_date == date('Y-m-d') && isset($_SESSION['daily_order_map'][$order_date])) {
        $daily_number = array_search($order_id, $_SESSION['daily_order_map'][$order_date]);
        if ($daily_number) {
            $formatted_daily = "ORD-" . str_pad($daily_number, 4, '0', STR_PAD_LEFT);
        }
    }
    
    // If not found in session, check archive for past orders
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

include 'includes/header';
?>

<style>
    /* ===== RESET & BASE STYLES ===== */
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

    /* ===== WELCOME CARD ===== */
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
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
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

    /* ===== DASHBOARD CARDS - 2x2 GRID ON MOBILE ===== */
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
        .card-content h3 {
            font-size: 2rem;
        }
    }

    .card-content p {
        color: #7f8c8d;
        margin: 0;
        font-weight: 500;
        font-size: 0.7rem;
    }

    @media (min-width: 768px) {
        .card-content p {
            font-size: 0.85rem;
        }
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

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    /* ===== SECTION HEADER ===== */
    .section-header {
        position: relative;
        margin-bottom: 1rem;
        padding-bottom: 0.4rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .section-header h4 {
        font-weight: 600;
        color: #2c3e50;
        display: inline-block;
        font-size: 1rem;
    }

    @media (min-width: 768px) {
        .section-header h4 {
            font-size: 1.3rem;
        }
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

    @media (min-width: 768px) {
        .view-all-link {
            padding: 0.3rem 1rem;
            font-size: 0.8rem;
        }
    }

    .view-all-link:hover {
        background: #d35400;
        color: white;
    }

    /* ===== DAILY ORDER BADGE ===== */
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

    /* ===== SCROLLABLE TABLE STYLES ===== */
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
        .table-container {
            height: 500px;
        }
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
        min-width: 700px;
        border-collapse: collapse;
        font-size: 0.75rem;
    }

    @media (min-width: 768px) {
        .table {
            min-width: 800px;
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

    .table tbody tr:hover {
        background: #f8f9fa;
    }

    .table tbody tr:active {
        background: #e9ecef;
        transform: scale(0.99);
    }

    .table tbody tr.new-order {
        animation: newOrderFlash 2s ease;
    }

    @keyframes newOrderFlash {
        0% { background-color: #fff3cd; transform: scale(1.01); }
        50% { background-color: #ffe69c; }
        100% { background-color: transparent; transform: scale(1); }
    }

    .table tbody tr.status-updated {
        animation: statusUpdateFlash 1.5s ease;
    }

    @keyframes statusUpdateFlash {
        0% { background-color: #d4edda; transform: scale(1.01); }
        50% { background-color: #a3cfbb; }
        100% { background-color: transparent; transform: scale(1); }
    }

    .table tbody tr.deleted-order {
        background-color: #f8d7da !important;
        opacity: 0.9;
        position: relative;
    }

    .table tbody tr.deleted-order:hover {
        background-color: #f5c6cb !important;
    }

    .table tbody tr.deleted-order td {
        color: #721c24;
    }

    .table tbody tr.deleted-order .badge {
        opacity: 0.8;
    }

    .deleted-customer-badge {
        display: inline-block;
        background: #6c757d;
        color: white;
        font-size: 0.6rem;
        padding: 2px 6px;
        border-radius: 50px;
        margin-left: 5px;
        font-weight: normal;
    }

    .deleted-order-badge {
        display: inline-block;
        background: #dc3545;
        color: white;
        font-size: 0.6rem;
        padding: 2px 6px;
        border-radius: 50px;
        margin-left: 5px;
        font-weight: bold;
        animation: pulse 2s infinite;
    }

    .pending-row {
        background-color: #fff3cd !important;
    }

    .cancelled-row {
        background-color: #f8d7da !important;
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

    @media (min-width: 768px) {
        .badge-new {
            padding: 0.2rem 0.5rem;
            font-size: 0.7rem;
            margin-left: 0.3rem;
        }
    }

    .badge.bg-order {
        background: #e67e22;
        color: white;
        padding: 0.2rem 0.4rem;
        border-radius: 50px;
        font-size: 0.65rem;
        white-space: nowrap;
    }

    @media (min-width: 768px) {
        .badge.bg-order {
            padding: 0.3rem 0.6rem;
            font-size: 0.75rem;
        }
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

    @media (min-width: 768px) {
        .btn-outline-view {
            padding: 0.25rem 1rem;
            font-size: 0.8rem;
            gap: 0.3rem;
        }
    }

    .btn-outline-view:hover {
        background: #d35400;
        color: white;
    }

    .btn-outline-view i {
        font-size: 0.6rem;
    }

    .btn-outline-view.disabled {
        opacity: 0.5;
        pointer-events: none;
        border-color: #6c757d;
        color: #6c757d;
    }

    /* ===== NOTIFICATION SOUND STYLES ===== */
    #notificationSound {
        display: none;
    }

    .sound-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 0.2rem 0.6rem;
        border-radius: 50px;
        font-size: 0.7rem;
        cursor: pointer;
        margin-left: 0.5rem;
        border: 1px solid rgba(255,255,255,0.3);
    }

    .sound-toggle i {
        font-size: 0.7rem;
    }

    .sound-toggle.muted {
        opacity: 0.5;
    }

    /* ===== SCROLL TO TOP BUTTON ===== */
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

    /* ===== LOADING INDICATOR ===== */
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

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* ===== MOBILE-SPECIFIC ADJUSTMENTS ===== */
    @media (max-width: 480px) {
        .table-container {
            height: 400px;
            margin-left: -5px;
            margin-right: -5px;
            width: calc(100% + 10px);
            border-radius: 10px;
        }

        .table {
            min-width: 600px;
        }

        .table thead th {
            padding: 6px 4px;
            font-size: 0.65rem;
        }

        .table tbody td {
            padding: 6px 4px;
            font-size: 0.65rem;
        }

        .badge.bg-order,
        .badge.bg-payment,
        .badge.bg-completed,
        .badge.bg-pending,
        .badge.bg-cancelled {
            padding: 0.15rem 0.3rem;
            font-size: 0.6rem;
        }

        .btn-outline-view {
            padding: 0.15rem 0.4rem;
            font-size: 0.6rem;
        }

        .btn-outline-view i {
            font-size: 0.55rem;
        }
        
        .daily-order-badge {
            font-size: 0.5rem;
            padding: 0.1rem 0.3rem;
        }
    }

    @media (max-width: 360px) {
        .table-container {
            height: 350px;
        }

        .table {
            min-width: 550px;
        }
    }
</style>

<!-- Notification Sound Element -->
<audio id="notificationSound" preload="auto" loop="false">
    <source src="assets/sounds/notification.mp3" type="audio/mpeg">
</audio>

<div class="container-fluid">
    <!-- Welcome Card -->
    <div class="welcome-card">
        <div class="welcome-content">
            <h2>Welcome, <span class="text-gradient"><?php echo htmlspecialchars($user['username']); ?></span>!</h2>
            <p class="mb-0"><i class="fas fa-user-tag me-1"></i><span class="badge bg-role"><?php echo ucfirst($role); ?></span></p>
        </div>
        <div class="welcome-icon">
            <i class="fas fa-utensils"></i>
        </div>
    </div>

    <!-- Dashboard Cards - 2x2 Grid -->
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
                <h3 id="orderCount"><?php echo $orderCount; ?></h3>
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

    <!-- Recent Orders Section with Scrollable Table -->
    <div class="section-header">
        <h4>
            <i class="fas fa-history"></i>Recent Orders
            <span class="realtime-indicator" id="realtimeIndicator">
                <i class="fas fa-sync-alt"></i> live
            </span>
            <span class="sound-toggle" id="soundToggle" onclick="toggleSound()">
                <i class="fas fa-volume-up"></i> sound
            </span>
        </h4>
        <a href="modules/orders/index" class="view-all-link">View All</a>
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
                    $orderIds = [];
                    foreach ($recentOrders as $order): 
                        $isPending = ($order['status'] == 'pending');
                        $isCancelled = ($order['status'] == 'cancelled');
                        $isDeletedCustomer = $order['is_deleted_customer'] == 1;
                        $rowClass = $isPending ? 'pending-row' : ($isCancelled ? 'cancelled-row' : '');
                        if ($isDeletedCustomer) {
                            $rowClass .= ' deleted-order';
                        }
                        
                        // Check if order is NEW (Pending and within 30 minutes)
                        $orderTime = strtotime($order['order_date']);
                        $minutesAgo = ($currentTime - $orderTime) / 60;
                        $isNew = ($isPending && $minutesAgo <= 30 && !$isDeletedCustomer);
                        
                        $orderIds[] = $order['id'];
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
                                <a href="/view?id=<?php echo $order['id']; ?>" class="btn-outline-view">
                                    <i class="fas fa-eye"></i>
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
// ===== GENERATE UNIQUE DEVICE ID =====
let deviceId = localStorage.getItem('staff_device_id');
if (!deviceId) {
    deviceId = 'staff_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
    localStorage.setItem('staff_device_id', deviceId);
}

// ===== SOUND TOGGLE =====
let soundEnabled = localStorage.getItem('staffSoundEnabled') !== 'false';
let lastNotificationTime = 0;
let lastSeenOrderId = 0;
const NOTIFICATION_COOLDOWN = 5000;

<?php if (!empty($orderIds)): ?>
lastSeenOrderId = <?php echo max($orderIds); ?>;
<?php endif; ?>

function toggleSound() {
    soundEnabled = !soundEnabled;
    localStorage.setItem('staffSoundEnabled', soundEnabled);
    updateSoundToggleIcon();
}

function updateSoundToggleIcon() {
    const toggle = document.getElementById('soundToggle');
    if (toggle) {
        if (soundEnabled) {
            toggle.innerHTML = '<i class="fas fa-volume-up"></i> sound';
            toggle.classList.remove('muted');
        } else {
            toggle.innerHTML = '<i class="fas fa-volume-mute"></i> muted';
            toggle.classList.add('muted');
        }
    }
}

updateSoundToggleIcon();

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.addEventListener('scroll', function() {
    const btn = document.getElementById('scrollToTop');
    if (window.scrollY > 300) btn.classList.add('show');
    else btn.classList.remove('show');
});

function playNotificationSound() {
    if (!soundEnabled) return;
    const now = Date.now();
    if (now - lastNotificationTime < NOTIFICATION_COOLDOWN) return;
    
    const sound = document.getElementById('notificationSound');
    if (sound) {
        sound.currentTime = 0;
        sound.play().catch(e => console.log('Sound play failed'));
        lastNotificationTime = now;
    }
}

function markAsSeen() {
    fetch('api/get_realtime_data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'device_id=' + encodeURIComponent(deviceId) + '&mark_seen=true'
    }).catch(error => console.error('Error marking seen'));
}

// Auto-mark as seen when interacting
document.addEventListener('click', markAsSeen);
document.addEventListener('scroll', markAsSeen);
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) markAsSeen();
});

// ===== REAL-TIME UPDATES =====
let lastOrderData = {};
let lastDeletedIds = new Set();

document.querySelectorAll('#recentOrdersTable tbody tr').forEach(row => {
    const id = row.dataset.orderId;
    const status = row.dataset.status;
    const deleted = row.dataset.deleted === '1';
    lastOrderData[id] = { status, deleted };
    if (deleted) lastDeletedIds.add(parseInt(id));
});

function fetchStaffUpdates() {
    fetch('api/get_realtime_data.php?device_id=' + encodeURIComponent(deviceId))
        .then(res => res.json())
        .then(data => {
            if (!data.success || data.userType !== 'staff') return;
            
            // Update stats
            ['todayCount', 'pendingCount', 'completedCount', 'cancelledCount'].forEach(id => {
                const el = document.getElementById(id);
                if (el && data[id] !== undefined) {
                    const old = parseInt(el.textContent);
                    if (old !== data[id]) {
                        el.textContent = data[id];
                        el.classList.add('count-updated');
                        setTimeout(() => el.classList.remove('count-updated'), 300);
                        
                        const card = el.closest('.dashboard-card');
                        if (card) {
                            card.classList.add('highlight');
                            setTimeout(() => card.classList.remove('highlight'), 500);
                        }
                    }
                }
            });
            
            // Update badges
            ['todayBadge', 'pendingBadge'].forEach(id => {
                const badge = document.getElementById(id);
                if (badge) {
                    if (id === 'todayBadge' && data.todayCount > 0) badge.style.display = 'inline-block';
                    else if (id === 'pendingBadge' && data.pendingCount > 0) {
                        badge.textContent = data.pendingCount;
                        badge.style.display = 'inline-block';
                    } else badge.style.display = 'none';
                }
            });
            
            // Check for new orders
            if (data.hasNewOrders) {
                playNotificationSound();
                
                if ("Notification" in window && Notification.permission === "granted") {
                    const latest = data.recentOrders[0];
                    new Notification('🆕 New Order!', {
                        body: `Order #${latest.id} from ${latest.display_customer} - ₱${parseFloat(latest.total_amount).toFixed(2)}`,
                        icon: 'assets/images/owner.jpg'
                    });
                }
                
                const pendingCard = document.querySelector('[data-card="pending"]');
                if (pendingCard) {
                    pendingCard.classList.add('highlight');
                    setTimeout(() => pendingCard.classList.remove('highlight'), 1000);
                }
            }
            
            // Update table
            if (data.recentOrders) {
                let html = '';
                const now = Math.floor(Date.now() / 1000);
                
                data.recentOrders.forEach(order => {
                    const isPending = order.status === 'pending';
                    const isCancelled = order.status === 'cancelled';
                    const isDeleted = order.is_deleted_customer == 1;
                    const rowClass = isPending ? 'pending-row' : (isCancelled ? 'cancelled-row' : '');
                    const finalClass = isDeleted ? rowClass + ' deleted-order' : rowClass;
                    
                    const orderTime = new Date(order.order_date).getTime() / 1000;
                    const minsAgo = (now - orderTime) / 60;
                    const isNew = isPending && minsAgo <= 30 && !isDeleted;
                    
                    const date = new Date(order.order_date).toLocaleString('en-US', {
                        month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
                    });
                    
                    let statusClass = order.status === 'completed' ? 'bg-completed' : 
                                     order.status === 'pending' ? 'bg-pending' : 'bg-cancelled';
                    let statusText = order.status.charAt(0).toUpperCase() + order.status.slice(1);
                    let paymentMethod = order.payment_method.charAt(0).toUpperCase() + order.payment_method.slice(1);
                    
                    // Get daily order number (you'll need to add this to your API response)
                    let dailyNumber = '';
                    if (order.daily_number) {
                        dailyNumber = `<span class="daily-order-badge">${order.daily_number}</span>`;
                    }
                    
                    html += `<tr class="${finalClass}" data-order-id="${order.id}" data-status="${order.status}" data-deleted="${isDeleted ? '1' : '0'}">
                        <td>
                            <span class="badge bg-order">#${order.id}</span>
                            ${isNew ? '<span class="badge-new">NEW</span>' : ''}
                            ${isDeleted ? '<span class="deleted-order-badge">DELETED</span>' : ''}
                        </td>
                        <td>${dailyNumber ? dailyNumber : '<span class="text-muted">—</span>'}</td>
                        <td><strong>${escapeHtml(order.display_customer)}</strong>${isDeleted ? ' <span class="deleted-customer-badge">Account Deleted</span>' : ''}</td>
                        <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
                        <td><span class="badge bg-payment">${paymentMethod}</span></td>
                        <td><span class="badge ${statusClass} order-status">${statusText}</span></td>
                        <td>${date}</td>
                        <td>${isDeleted ? 
                            '<span class="btn-outline-view disabled"><i class="fas fa-eye-slash"></i> Unavailable</span>' : 
                            `<a href="/view?id=${order.id}" class="btn-outline-view"><i class="fas fa-eye"></i></a>`
                        }</td>
                    </tr>`;
                });
                
                document.querySelector('#recentOrdersTable tbody').innerHTML = html;
                document.getElementById('ordersCount').textContent = data.recentOrders.length;
            }
        })
        .catch(console.error);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

if ("Notification" in window && Notification.permission === "default") {
    Notification.requestPermission();
}

setInterval(fetchStaffUpdates, 3000);
fetchStaffUpdates();

setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.transition = 'opacity 0.5s';
        a.style.opacity = '0';
        setTimeout(() => a.style.display = 'none', 500);
    });
}, 5000);

// Touch-friendly
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.dashboard-card').forEach(c => {
        c.addEventListener('touchstart', function() { this.style.transform = 'scale(0.98)'; });
        c.addEventListener('touchend', function() { this.style.transform = ''; });
        c.addEventListener('touchcancel', function() { this.style.transform = ''; });
    });
});

// Pull to refresh
let touchStart = 0;
document.addEventListener('touchstart', e => touchStart = e.changedTouches[0].screenY);
document.addEventListener('touchend', e => {
    const dist = e.changedTouches[0].screenY - touchStart;
    if (window.scrollY === 0 && dist > 100) {
        fetchStaffUpdates();
        const ind = document.getElementById('realtimeIndicator');
        ind.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> refreshing';
        setTimeout(() => ind.innerHTML = '<i class="fas fa-sync-alt"></i> live', 1000);
    }
});
</script>

<?php include 'includes/footer.php'; ?>