<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

// Get date range from request or set defaults
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$view = $_GET['view'] ?? 'cards'; // Default to cards view
$cashier_filter = $_GET['cashier'] ?? '';

// ===== Get all cashiers directly from tbl_users =====
$all_cashiers = $pdo->query("
    SELECT id, username, created_at, status 
    FROM tbl_users 
    WHERE role = 'cashier' 
    ORDER BY username
")->fetchAll();

// ===== Get online cashiers (active in last 5 minutes) =====
$online_cashiers = $pdo->query("
    SELECT DISTINCT u.id, u.username, MAX(s.last_activity) as last_active
    FROM tbl_users u
    INNER JOIN tbl_active_sessions s ON u.id = s.user_id
    WHERE u.role = 'cashier' 
    AND s.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    GROUP BY u.id
    ORDER BY last_active DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Create array of online cashier IDs for quick lookup
$online_ids = [];
$online_lookup = [];
foreach ($online_cashiers as $oc) {
    $online_ids[] = $oc['id'];
    $online_lookup[$oc['id']] = $oc['last_active'];
}

// ===== Get initial cashier performance data =====
$cashier_data = [];

foreach ($all_cashiers as $cashier) {
    $id = $cashier['id'];
    $username = $cashier['username'];
    
    // Apply cashier filter if set
    if (!empty($cashier_filter) && $id != $cashier_filter) {
        continue;
    }
    
    // Get orders created by this cashier
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as total_sales
        FROM tbl_orders 
        WHERE created_by = ? 
        AND DATE(order_date) BETWEEN ? AND ?
    ");
    $stmt->execute([$id, $from, $to]);
    $created = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get orders completed by this cashier
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as total_sales
        FROM tbl_orders 
        WHERE completed_by = ? 
        AND status = 'completed'
        AND DATE(order_date) BETWEEN ? AND ?
    ");
    $stmt->execute([$id, $from, $to]);
    $completed = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get orders cancelled by this cashier
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as order_count,
            COALESCE(SUM(total_amount), 0) as total_sales
        FROM tbl_orders 
        WHERE completed_by = ? 
        AND status = 'cancelled'
        AND DATE(order_date) BETWEEN ? AND ?
    ");
    $stmt->execute([$id, $from, $to]);
    $cancelled = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get payment method breakdown for created orders
    $stmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN payment_method = 'cash' THEN 1 ELSE 0 END) as cash_count,
            SUM(CASE WHEN payment_method = 'gcash' THEN 1 ELSE 0 END) as gcash_count,
            SUM(CASE WHEN payment_method = 'paymaya' THEN 1 ELSE 0 END) as paymaya_count
        FROM tbl_orders 
        WHERE created_by = ? 
        AND DATE(order_date) BETWEEN ? AND ?
    ");
    $stmt->execute([$id, $from, $to]);
    $payments = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get last activity date
    $stmt = $pdo->prepare("
        SELECT MAX(order_date) as last_activity
        FROM tbl_orders 
        WHERE (created_by = ? OR completed_by = ?)
        AND DATE(order_date) BETWEEN ? AND ?
    ");
    $stmt->execute([$id, $id, $from, $to]);
    $last_activity = $stmt->fetchColumn();
    
    // Get days active
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT DATE(order_date)) as days_active
        FROM tbl_orders 
        WHERE (created_by = ? OR completed_by = ?)
        AND DATE(order_date) BETWEEN ? AND ?
    ");
    $stmt->execute([$id, $id, $from, $to]);
    $days_active = $stmt->fetchColumn();
    if (!$days_active) $days_active = 0;
    
    // Calculate totals
    $orders_created = (int)$created['order_count'];
    $orders_completed = (int)$completed['order_count'];
    $orders_cancelled = (int)$cancelled['order_count'];
    $sales_created = (float)$created['total_sales'];
    $sales_completed = (float)$completed['total_sales'];
    $sales_cancelled = (float)$cancelled['total_sales'];
    
    $total_orders = $orders_created + $orders_completed;
    $total_sales = $sales_created + $sales_completed;
    $avg_order_value = $total_orders > 0 ? $total_sales / $total_orders : 0;
    
    $cashier_data[] = [
        'id' => $id,
        'username' => $username,
        'account_status' => $cashier['status'],
        'account_created' => $cashier['created_at'],
        'orders_created' => $orders_created,
        'sales_created' => $sales_created,
        'orders_completed' => $orders_completed,
        'sales_completed' => $sales_completed,
        'orders_cancelled' => $orders_cancelled,
        'sales_cancelled' => $sales_cancelled,
        'total_orders' => $total_orders,
        'total_sales' => $total_sales,
        'avg_order_value' => $avg_order_value,
        'days_active' => $days_active,
        'last_activity' => $last_activity,
        'cash_orders_created' => (int)($payments['cash_count'] ?? 0),
        'gcash_orders_created' => (int)($payments['gcash_count'] ?? 0),
        'paymaya_orders_created' => (int)($payments['paymaya_count'] ?? 0),
        'is_online' => in_array($id, $online_ids) ? 1 : 0,
        'last_online' => $online_lookup[$id] ?? null,
        'active_sessions' => in_array($id, $online_ids) ? 1 : 0
    ];
}

// Calculate totals
$total_cashiers = count($cashier_data);
$online_count = count($online_ids);
$total_orders_created = array_sum(array_column($cashier_data, 'orders_created'));
$total_orders_completed = array_sum(array_column($cashier_data, 'orders_completed'));
$total_orders_cancelled = array_sum(array_column($cashier_data, 'orders_cancelled'));
$total_sales = array_sum(array_column($cashier_data, 'total_sales'));
$avg_sale = $total_orders_created + $total_orders_completed > 0 ? $total_sales / ($total_orders_created + $total_orders_completed) : 0;

// Create JSON data for JavaScript
$cashier_json = json_encode($cashier_data);

include '../../includes/header.php';
?>

<style>
    /* ===== REPORT STYLES ===== */
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

    .report-container {
        padding: 10px;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ===== HEADER SECTION ===== */
    .report-header {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 20px 15px;
        border-radius: 15px;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        position: relative;
        overflow: hidden;
    }

    .report-header h2 {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 5px;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .report-header h2 i {
        font-size: 1.3rem;
    }

    .report-header p {
        font-size: 0.8rem;
        opacity: 0.9;
        margin-bottom: 10px;
    }

    .online-indicator {
        background: rgba(255,255,255,0.2);
        padding: 8px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        backdrop-filter: blur(5px);
        flex-wrap: wrap;
    }

    .online-indicator i {
        color: #28a745;
        animation: pulse 2s infinite;
    }

    .online-indicator .count-online {
        font-weight: 700;
        margin: 0 3px;
    }

    .realtime-indicator {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        font-size: 0.7rem;
        color: #28a745;
        margin-left: 5px;
        background: rgba(40, 167, 69, 0.1);
        padding: 3px 8px;
        border-radius: 50px;
        white-space: nowrap;
    }

    .realtime-indicator i {
        font-size: 0.6rem;
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.8; transform: scale(1.05); }
        100% { opacity: 1; transform: scale(1); }
    }

    /* ===== FILTER CARD ===== */
    .filter-card {
        background: white;
        border-radius: 15px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .filter-card details {
        width: 100%;
    }

    .filter-card summary {
        font-weight: 600;
        color: #667eea;
        cursor: pointer;
        padding: 5px 0;
        font-size: 0.9rem;
    }

    .filter-card summary i {
        margin-right: 8px;
    }

    .filter-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
        margin-top: 15px;
    }

    @media (min-width: 768px) {
        .filter-grid {
            grid-template-columns: repeat(4, 1fr);
            align-items: end;
        }
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .filter-group label {
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
        font-size: 0.75rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .filter-group label i {
        color: #667eea;
        font-size: 0.8rem;
    }

    .filter-group input,
    .filter-group select {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.85rem;
        transition: all 0.3s;
    }

    .filter-group input:focus,
    .filter-group select:focus {
        border-color: #667eea;
        outline: none;
        box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
    }

    .button-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 5px;
    }

    @media (min-width: 768px) {
        .button-group {
            flex-direction: row;
        }
    }

    .btn-apply {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 12px 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        font-size: 0.85rem;
    }

    .btn-apply:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(102,126,234,0.3);
    }

    .btn-apply:active {
        transform: translateY(0);
    }

    .btn-reset {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 12px 15px;
        font-weight: 600;
        text-decoration: none;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        font-size: 0.85rem;
    }

    .btn-reset:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(108,117,125,0.3);
        color: white;
    }

    /* ===== STATS CARDS ===== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-bottom: 20px;
    }

    @media (min-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(6, 1fr);
            gap: 15px;
        }
    }

    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-left: 4px solid;
        transition: transform 0.3s;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .stat-card.primary { border-left-color: #667eea; }
    .stat-card.success { border-left-color: #28a745; }
    .stat-card.info { border-left-color: #17a2b8; }
    .stat-card.warning { border-left-color: #ffc107; }
    .stat-card.danger { border-left-color: #dc3545; }
    .stat-card.purple { border-left-color: #9b59b6; }

    .stat-label {
        font-size: 0.7rem;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }

    .stat-value {
        font-size: 1.2rem;
        font-weight: 700;
        color: #333;
        line-height: 1.2;
    }

    .stat-value.count-updated {
        animation: countPop 0.3s ease;
    }

    @keyframes countPop {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); color: #667eea; }
        100% { transform: scale(1); }
    }

    .stat-sub {
        font-size: 0.65rem;
        color: #28a745;
        margin-top: 5px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .stat-sub i {
        font-size: 0.6rem;
    }

    /* ===== ONLINE/OFFLINE BADGES ===== */
    .online-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #28a745;
        color: white;
        padding: 3px 8px;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 600;
        animation: pulse 2s infinite;
        white-space: nowrap;
    }

    .offline-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #6c757d;
        color: white;
        padding: 3px 8px;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 600;
        opacity: 0.8;
        white-space: nowrap;
    }

    /* ===== CASHIER CARDS GRID ===== */
    .cashier-cards-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
        margin-top: 20px;
    }

    @media (min-width: 640px) {
        .cashier-cards-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 1024px) {
        .cashier-cards-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    .cashier-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.05);
        transition: all 0.3s;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .cashier-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(102,126,234,0.15);
    }

    .cashier-card.online {
        border-top: 4px solid #28a745;
    }

    .cashier-card.offline {
        border-top: 4px solid #6c757d;
        opacity: 0.9;
    }

    .cashier-card.status-changed {
        animation: statusFlash 1s ease;
    }

    @keyframes statusFlash {
        0% { background-color: #fff3cd; transform: scale(1.02); }
        50% { background-color: #ffe69c; }
        100% { background-color: white; transform: scale(1); }
    }

    .cashier-header {
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        padding: 15px;
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
    }

    .cashier-avatar {
        width: 45px;
        height: 45px;
        background: rgba(255,255,255,0.2);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        position: relative;
        flex-shrink: 0;
    }

    .online-status {
        position: absolute;
        bottom: 0;
        right: 0;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid white;
    }

    .online-status.online {
        background: #28a745;
        animation: pulse 2s infinite;
    }

    .online-status.offline {
        background: #6c757d;
    }

    .cashier-info {
        flex: 1;
        min-width: 0;
    }

    .cashier-info h4 {
        font-size: 1rem;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }

    .session-count {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 2px 6px;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 3px;
        white-space: nowrap;
    }

    .rank-badge {
        position: absolute;
        top: 10px;
        right: 10px;
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 3px 8px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
    }

    .cashier-body {
        padding: 15px;
        flex: 1;
    }

    .stat-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.85rem;
    }

    .stat-row:last-child {
        border-bottom: none;
    }

    .stat-row .label {
        color: #666;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .stat-row .label i {
        color: #667eea;
        font-size: 0.8rem;
        width: 16px;
    }

    .stat-row .value {
        font-weight: 600;
        color: #333;
        font-size: 0.85rem;
    }

    .stat-row .value.highlight {
        color: #667eea;
        font-weight: 700;
    }

    .stat-row .value.text-success {
        color: #28a745;
    }

    .stat-row .value.text-danger {
        color: #dc3545;
    }

    .stat-row .value.count-updated {
        animation: countPop 0.3s ease;
    }

    .payment-badges {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
        justify-content: flex-end;
    }

    .payment-badge {
        padding: 3px 8px;
        border-radius: 50px;
        font-size: 0.65rem;
        font-weight: 600;
    }

    .payment-badge.cash {
        background: #28a745;
        color: white;
    }

    .payment-badge.gcash {
        background: #0057e7;
        color: white;
    }

    .payment-badge.paymaya {
        background: #ff4d4d;
        color: white;
    }

    .progress-bar {
        width: 100%;
        height: 6px;
        background: #f0f0f0;
        border-radius: 3px;
        overflow: hidden;
        margin: 10px 0;
    }

    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #667eea, #764ba2);
        border-radius: 3px;
        transition: width 0.3s;
    }

    .last-activity {
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed #eee;
        font-size: 0.7rem;
        color: #999;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .last-activity i {
        font-size: 0.6rem;
        color: #667eea;
    }

    /* ===== EXPORT BUTTONS ===== */
    .export-buttons {
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .btn-export {
        padding: 10px 20px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }

    .btn-export.csv {
        background: #28a745;
        color: white;
    }

    .btn-export.pdf {
        background: #dc3545;
        color: white;
    }

    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        color: white;
    }

    /* ===== EMPTY STATE ===== */
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        background: white;
        border-radius: 12px;
    }

    .empty-state i {
        font-size: 3rem;
        color: #ddd;
        margin-bottom: 15px;
    }

    .empty-state h3 {
        font-size: 1.2rem;
        color: #333;
        margin-bottom: 10px;
    }

    .empty-state p {
        color: #666;
        margin-bottom: 20px;
    }

    /* ===== LAST UPDATE TIMESTAMP ===== */
    .last-update {
        text-align: right;
        font-size: 0.65rem;
        color: #999;
        margin-top: 10px;
        padding-top: 10px;
        border-top: 1px dashed #dee2e6;
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 5px;
    }

    .last-update i {
        font-size: 0.6rem;
        color: #28a745;
        animation: spin 2s linear infinite;
    }

    /* ===== LOADING OVERLAY ===== */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    .loading-overlay.show {
        display: flex;
    }

    .loading-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid #f3f3f3;
        border-top: 3px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    /* ===== MOBILE RESPONSIVE ===== */
    @media (max-width: 768px) {
        .report-header h2 {
            font-size: 1.1rem;
        }

        .stat-value {
            font-size: 1rem;
        }

        .filter-grid {
            gap: 8px;
        }

        .button-group {
            flex-direction: column;
        }
    }
</style>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<div class="container-fluid report-container">
    <!-- Header -->
    <div class="report-header">
        <h2>
            <i class="fas fa-chart-bar"></i> Cashier Performance Report
            <span class="realtime-indicator" id="headerRealtimeIndicator">
                <i class="fas fa-sync-alt fa-spin"></i> live
            </span>
        </h2>
        <p>Track and analyze cashier performance, sales, and real-time activity</p>
        <div class="online-indicator">
            <i class="fas fa-circle"></i>
            <span><span class="count-online" id="onlineCount"><?php echo $online_count; ?></span> of <span id="totalCashiers"><?php echo $total_cashiers; ?></span> Cashier(s) Online Now</span>
        </div>
    </div>

 <!-- Filter Section -->
<div class="filter-card">
    <details>
        <summary>
            <i class="fas fa-filter"></i> Filter Options
        </summary>
        <form method="get" class="filter-grid" id="filterForm">
            <div class="filter-group">
                <label><i class="fas fa-calendar-alt"></i> From</label>
                <input type="date" name="from" value="<?php echo $from; ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-calendar-alt"></i> To</label>
                <input type="date" name="to" value="<?php echo $to; ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="filter-group">
                <label><i class="fas fa-user"></i> Cashier</label>
                <select name="cashier">
                    <option value="">All Cashiers</option>
                    <?php foreach ($all_cashiers as $c): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo $cashier_filter == $c['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($c['username']); ?>
                        <?php if (in_array($c['id'], $online_ids)): ?> (Online)<?php endif; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="button-group">
                <button type="submit" class="btn-apply">
                    <i class="fas fa-filter"></i> Apply
                </button>
                <a href="cashier_performance.php" class="btn-reset">
                    <i class="fas fa-times"></i> Reset
                </a>
            </div>
        </form>
    </details>
</div>

<!-- ADD THIS RIGHT AFTER THE FILTER CARD -->
<div class="reset-performance-section" style="margin-bottom: 20px; text-align: right;">
    <a href="/admin/reset_cashier_performance" 
       class="btn btn-danger" 
       style="padding: 10px 20px; border-radius: 50px; font-weight: 600; display: inline-flex; align-items: center; gap: 8px; background: linear-gradient(135deg, #dc3545, #c82333); color: white; text-decoration: none; border: none; box-shadow: 0 4px 10px rgba(220,53,69,0.2);"
       onclick="return confirm('⚠️ WARNING: This will take you to the Cashier Performance Reset Tool.\n\nThis tool allows you to permanently delete order records and reset cashier statistics.\n\nAll data will be archived before deletion.\n\nContinue?')">
        <i class="fas fa-broom"></i>
        <span>Reset Cashier Performance</span>
        <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
    </a>
</div>

<style>
    /* Add this to your existing styles */
    .reset-performance-section .btn-danger:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }
    
    .reset-performance-section .btn-danger:active {
        transform: translateY(0);
    }
    
    @media (max-width: 768px) {
        .reset-performance-section {
            text-align: center;
        }
        
        .reset-performance-section .btn-danger {
            width: 100%;
            justify-content: center;
        }
    }
</style>

    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-label">Total Cashiers</div>
            <div class="stat-value" id="statTotalCashiers"><?php echo $total_cashiers; ?></div>
            <div class="stat-sub">
                <i class="fas fa-circle" style="color: #28a745;"></i> <span id="onlineCountStat"><?php echo $online_count; ?></span> online
            </div>
        </div>
        <div class="stat-card success">
            <div class="stat-label">Orders Created</div>
            <div class="stat-value" id="statOrdersCreated"><?php echo number_format($total_orders_created); ?></div>
        </div>
        <div class="stat-card info">
            <div class="stat-label">Orders Completed</div>
            <div class="stat-value" id="statOrdersCompleted"><?php echo number_format($total_orders_completed); ?></div>
        </div>
        <div class="stat-card warning">
            <div class="stat-label">Orders Cancelled</div>
            <div class="stat-value" id="statOrdersCancelled"><?php echo number_format($total_orders_cancelled); ?></div>
        </div>
        <div class="stat-card purple">
            <div class="stat-label">Total Sales</div>
            <div class="stat-value" id="statTotalSales">₱<?php echo number_format($total_sales, 0); ?></div>
        </div>
        <div class="stat-card danger">
            <div class="stat-label">Average Order</div>
            <div class="stat-value" id="statAverageOrder">₱<?php echo number_format($avg_sale, 0); ?></div>
        </div>
    </div>

    <?php if (empty($cashier_data)): ?>
        <!-- No Data -->
        <div class="empty-state">
            <i class="fas fa-chart-bar"></i>
            <h3>No Data Available</h3>
            <p>No cashier activity found for the selected date range.</p>
            <a href="cashier_performance.php" class="btn-apply" style="display: inline-block; padding: 10px 30px; width: auto;">
                <i class="fas fa-redo"></i> Reset Filters
            </a>
        </div>
    <?php else: ?>

        <!-- Cashier Cards - Clean Display -->
        <div class="cashier-cards-grid" id="cashierCards">
            <?php 
            $rank = 1;
            $max_sales = max(array_column($cashier_data, 'total_sales'));
            foreach ($cashier_data as $cashier): 
                $percentage = $max_sales > 0 ? round(($cashier['total_sales'] / $max_sales) * 100) : 0;
                $isOnline = $cashier['is_online'] == 1;
                $lastOnline = $cashier['last_online'] ? date('h:i A', strtotime($cashier['last_online'])) : 'Never';
                $lastActivity = ($cashier['last_activity'] && $cashier['last_activity'] != '1900-01-01') 
                    ? date('M d, h:i A', strtotime($cashier['last_activity'])) 
                    : 'No activity';
            ?>
            <div class="cashier-card <?php echo $isOnline ? 'online' : 'offline'; ?>" 
                 data-cashier-id="<?php echo $cashier['id']; ?>" 
                 data-online="<?php echo $isOnline ? '1' : '0'; ?>"
                 data-username="<?php echo htmlspecialchars($cashier['username']); ?>">
                
                <div class="cashier-header">
                    <div class="cashier-avatar">
                        <i class="fas fa-user"></i>
                        <span class="online-status <?php echo $isOnline ? 'online' : 'offline'; ?>"></span>
                    </div>
                    <div class="cashier-info">
                        <h4>
                            <?php echo htmlspecialchars($cashier['username']); ?>
                            <?php if ($cashier['active_sessions'] > 1): ?>
                                <span class="session-count" title="Multiple active sessions">
                                    <i class="fas fa-window-maximize"></i> <?php echo $cashier['active_sessions']; ?>
                                </span>
                            <?php endif; ?>
                        </h4>
                    </div>
                    <span class="rank-badge">#<?php echo $rank; ?></span>
                </div>
                
                <div class="cashier-body">
                    <div class="stat-row">
                        <span class="label"><i class="fas fa-plus-circle"></i> Created:</span>
                        <span class="value" id="created-<?php echo $cashier['id']; ?>"><?php echo $cashier['orders_created']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="label"><i class="fas fa-check-circle text-success"></i> Completed:</span>
                        <span class="value text-success" id="completed-<?php echo $cashier['id']; ?>"><?php echo $cashier['orders_completed']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="label"><i class="fas fa-times-circle text-danger"></i> Cancelled:</span>
                        <span class="value text-danger" id="cancelled-<?php echo $cashier['id']; ?>"><?php echo $cashier['orders_cancelled']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="label"><i class="fas fa-shopping-cart"></i> Total Orders:</span>
                        <span class="value highlight" id="total-orders-<?php echo $cashier['id']; ?>"><?php echo $cashier['total_orders']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="label"><i class="fas fa-money-bill-wave"></i> Total Sales:</span>
                        <span class="value highlight" id="sales-<?php echo $cashier['id']; ?>">₱<?php echo number_format($cashier['total_sales'], 0); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="label"><i class="fas fa-calculator"></i> Average:</span>
                        <span class="value" id="average-<?php echo $cashier['id']; ?>">₱<?php echo number_format($cashier['avg_order_value'], 0); ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="label"><i class="fas fa-calendar-alt"></i> Days Active:</span>
                        <span class="value" id="days-<?php echo $cashier['id']; ?>"><?php echo $cashier['days_active']; ?></span>
                    </div>
                    
                    <?php if ($cashier['cash_orders_created'] > 0 || $cashier['gcash_orders_created'] > 0 || $cashier['paymaya_orders_created'] > 0): ?>
                    <div class="stat-row">
                        <span class="label"><i class="fas fa-credit-card"></i> Payments:</span>
                        <span class="value">
                            <div class="payment-badges">
                                <?php if ($cashier['cash_orders_created'] > 0): ?>
                                    <span class="payment-badge cash" id="cash-<?php echo $cashier['id']; ?>">C:<?php echo $cashier['cash_orders_created']; ?></span>
                                <?php endif; ?>
                                <?php if ($cashier['gcash_orders_created'] > 0): ?>
                                    <span class="payment-badge gcash" id="gcash-<?php echo $cashier['id']; ?>">G:<?php echo $cashier['gcash_orders_created']; ?></span>
                                <?php endif; ?>
                                <?php if ($cashier['paymaya_orders_created'] > 0): ?>
                                    <span class="payment-badge paymaya" id="paymaya-<?php echo $cashier['id']; ?>">P:<?php echo $cashier['paymaya_orders_created']; ?></span>
                                <?php endif; ?>
                            </div>
                        </span>
                    </div>
                    <?php endif; ?>

                    <!-- Progress Bar -->
                    <?php if ($max_sales > 0): ?>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $percentage; ?>%;" title="<?php echo $percentage; ?>% of top performer"></div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="last-activity">
                        <i class="far fa-clock"></i>
                        <span id="last-<?php echo $cashier['id']; ?>">Last: <?php echo $lastActivity; ?></span>
                    </div>
                </div>
            </div>
            <?php 
            $rank++;
            endforeach; 
            ?>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons">
            <a href="export_cashier.php?from=<?php echo $from; ?>&to=<?php echo $to; ?>&cashier=<?php echo $cashier_filter; ?>&format=csv" class="btn-export csv">
                <i class="fas fa-file-csv"></i> Export CSV
            </a>
        </div>

        <!-- Last Update -->
        <div class="last-update" id="lastUpdateTime">
            <i class="fas fa-sync-alt fa-spin"></i>
            Last updated: <span id="updateTimestamp"><?php echo date('h:i:s A'); ?></span>
        </div>

    <?php endif; ?>
</div>

<script>
// ===== STORE INITIAL CASHIER DATA =====
let cashierData = <?php echo $cashier_json; ?>;
let deviceId = localStorage.getItem('staff_device_id');
if (!deviceId) {
    deviceId = 'staff_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
    localStorage.setItem('staff_device_id', deviceId);
}

let updateInterval;
let isUpdating = false;
let lastUpdateTime = Date.now();

// ===== UPDATE TIMESTAMP =====
function updateTimestamp() {
    const now = new Date();
    const hours = now.getHours().toString().padStart(2, '0');
    const minutes = now.getMinutes().toString().padStart(2, '0');
    const seconds = now.getSeconds().toString().padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    const displayHours = hours % 12 || 12;
    return `${displayHours.toString().padStart(2, '0')}:${minutes}:${seconds} ${ampm}`;
}

// ===== UPDATE STATS CARDS =====
function updateStatsCards(stats) {
    const elements = {
        'statTotalCashiers': stats.total_cashiers,
        'onlineCount': stats.online_count,
        'onlineCountStat': stats.online_count,
        'statOrdersCreated': stats.orders_created.toLocaleString(),
        'statOrdersCompleted': stats.orders_completed.toLocaleString(),
        'statOrdersCancelled': stats.orders_cancelled.toLocaleString(),
        'statTotalSales': '₱' + stats.total_sales.toLocaleString(),
        'statAverageOrder': '₱' + stats.avg_sale.toLocaleString()
    };
    
    for (let [id, value] of Object.entries(elements)) {
        const el = document.getElementById(id);
        if (el && el.textContent != value) {
            el.textContent = value;
            el.classList.add('count-updated');
            setTimeout(() => el.classList.remove('count-updated'), 300);
        }
    }
}

// ===== UPDATE CASHIER CARDS =====
function updateCashierCards(cashiers) {
    cashiers.forEach(cashier => {
        const id = cashier.id;
        
        // Update each field
        const updates = {
            'created': cashier.orders_created,
            'completed': cashier.orders_completed,
            'cancelled': cashier.orders_cancelled,
            'total-orders': cashier.total_orders,
            'sales': '₱' + cashier.total_sales.toLocaleString(),
            'average': '₱' + cashier.avg_order_value.toLocaleString(),
            'days': cashier.days_active
        };
        
        for (let [field, value] of Object.entries(updates)) {
            const el = document.getElementById(`${field}-${id}`);
            if (el && el.textContent != value) {
                el.textContent = value;
                el.classList.add('count-updated');
                setTimeout(() => el.classList.remove('count-updated'), 300);
            }
        }
        
        // Update last activity
        const lastEl = document.getElementById(`last-${id}`);
        if (lastEl && cashier.last_activity) {
            const date = new Date(cashier.last_activity);
            const formatted = date.toLocaleString('en-US', {
                month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
            });
            if (lastEl.textContent != 'Last: ' + formatted) {
                lastEl.textContent = 'Last: ' + formatted;
            }
        }
        
        // Update payment badges
        if (cashier.cash_orders_created !== undefined) {
            const cashEl = document.getElementById(`cash-${id}`);
            if (cashEl) cashEl.textContent = 'C:' + cashier.cash_orders_created;
        }
        if (cashier.gcash_orders_created !== undefined) {
            const gcashEl = document.getElementById(`gcash-${id}`);
            if (gcashEl) gcashEl.textContent = 'G:' + cashier.gcash_orders_created;
        }
        if (cashier.paymaya_orders_created !== undefined) {
            const paymayaEl = document.getElementById(`paymaya-${id}`);
            if (paymayaEl) paymayaEl.textContent = 'P:' + cashier.paymaya_orders_created;
        }
    });
}

// ===== UPDATE ONLINE STATUS =====
function updateOnlineStatus(onlineCashiers) {
    const onlineIds = onlineCashiers.map(c => parseInt(c.id));
    const onlineCount = onlineIds.length;
    
    // Update online count
    document.querySelectorAll('.count-online, #onlineCountStat').forEach(el => {
        if (el) el.textContent = onlineCount;
    });
    
    // Update each cashier card
    document.querySelectorAll('.cashier-card').forEach(card => {
        const cashierId = parseInt(card.dataset.cashierId);
        const wasOnline = card.classList.contains('online');
        const isOnline = onlineIds.includes(cashierId);
        
        if (wasOnline !== isOnline) {
            // Update card class
            card.classList.remove('online', 'offline');
            card.classList.add(isOnline ? 'online' : 'offline');
            card.dataset.online = isOnline ? '1' : '0';
            
            // Flash effect
            card.classList.add('status-changed');
            setTimeout(() => card.classList.remove('status-changed'), 1000);
            
            // Update status dot
            const statusDot = card.querySelector('.online-status');
            if (statusDot) {
                statusDot.className = 'online-status ' + (isOnline ? 'online' : 'offline');
            }
        }
    });
}

// ===== FETCH REAL-TIME DATA =====
function fetchCashierStatus() {
    if (isUpdating) return;
    
    isUpdating = true;
    
    // Get current filter values
    const urlParams = new URLSearchParams(window.location.search);
    const from = urlParams.get('from') || '<?php echo $from; ?>';
    const to = urlParams.get('to') || '<?php echo $to; ?>';
    const cashierFilter = urlParams.get('cashier') || '';
    
    fetch(`/api/get_realtime_cashier_data.php?from=${from}&to=${to}&cashier=${cashierFilter}&device_id=${deviceId}`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                // Update stats
                updateStatsCards(data.stats);
                
                // Update cashier data
                if (data.cashiers) {
                    updateCashierCards(data.cashiers);
                    cashierData = data.cashiers;
                }
                
                // Update online status
                if (data.online_cashiers) {
                    updateOnlineStatus(data.online_cashiers);
                }
                
                // Update timestamp
                document.getElementById('updateTimestamp').textContent = updateTimestamp();
            }
            isUpdating = false;
        })
        .catch(error => {
            console.error('Error fetching cashier data:', error);
            isUpdating = false;
        });
}

// ===== START REAL-TIME UPDATES =====
updateInterval = setInterval(fetchCashierStatus, 5000);
fetchCashierStatus();

// ===== CLEAN UP =====
window.addEventListener('beforeunload', function() {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
});

// ===== HANDLE FILTER FORM SUBMISSION =====
document.getElementById('filterForm')?.addEventListener('submit', function() {
    document.getElementById('loadingOverlay')?.classList.add('show');
});

// Hide loading on page load
window.addEventListener('load', function() {
    document.getElementById('loadingOverlay')?.classList.remove('show');
});

// ===== PULL TO REFRESH (mobile) =====
let touchStart = 0;
document.addEventListener('touchstart', e => {
    touchStart = e.changedTouches[0].screenY;
});

document.addEventListener('touchend', e => {
    const dist = e.changedTouches[0].screenY - touchStart;
    if (window.scrollY === 0 && dist > 100) {
        fetchCashierStatus();
        const indicator = document.getElementById('headerRealtimeIndicator');
        if (indicator) {
            indicator.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> refreshing';
            setTimeout(() => {
                indicator.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> live';
            }, 1000);
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>