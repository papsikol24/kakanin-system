<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

// Get filter parameters
$filter_cashier = $_GET['cashier'] ?? '';
$filter_date = $_GET['date'] ?? date('Y-m-d');
$filter_action = $_GET['action'] ?? '';
$view = $_GET['view'] ?? 'realtime'; // 'realtime' or 'archives'

// Get all cashiers for filter dropdown
$cashiers = $pdo->query("
    SELECT id, username 
    FROM tbl_users 
    WHERE role = 'cashier' 
    ORDER BY username
")->fetchAll();

// Get cashier activity summary (for realtime view)
$summary = $pdo->query("
    SELECT 
        u.username,
        COUNT(DISTINCT o.id) as orders_today,
        COALESCE(SUM(o.total_amount), 0) as sales_today,
        COUNT(l.id) as stock_changes,
        MAX(o.order_date) as last_activity
    FROM tbl_users u
    LEFT JOIN tbl_orders o ON u.id = o.created_by AND DATE(o.order_date) = CURDATE()
    LEFT JOIN tbl_inventory_logs l ON u.id = l.user_id AND DATE(l.log_time) = CURDATE()
    WHERE u.role = 'cashier'
    GROUP BY u.id
    ORDER BY orders_today DESC
")->fetchAll();

// ===== GET ARCHIVE DATA IF VIEWING ARCHIVES =====
$archive_orders = [];
$sales_summary = [];
$archive_dates = [];

if ($view == 'archives') {
    // Get archive dates for filter
    $archive_dates = $pdo->query("
        SELECT DISTINCT archive_date 
        FROM tbl_daily_orders_archive 
        ORDER BY archive_date DESC
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    // Build archive query
    $archive_query = "SELECT * FROM tbl_daily_orders_archive WHERE 1=1";
    $archive_params = [];
    
    $archive_date_filter = $_GET['archive_date'] ?? '';
    $archive_search = $_GET['archive_search'] ?? '';
    $archive_status = $_GET['archive_status'] ?? '';
    
    if (!empty($archive_date_filter)) {
        $archive_query .= " AND archive_date = ?";
        $archive_params[] = $archive_date_filter;
    }
    
    if (!empty($archive_search)) {
        $archive_query .= " AND (customer_name LIKE ? OR original_order_id LIKE ?)";
        $archive_params[] = "%$archive_search%";
        $archive_params[] = "%$archive_search%";
    }
    
    if (!empty($archive_status)) {
        $archive_query .= " AND status = ?";
        $archive_params[] = $archive_status;
    }
    
    $archive_query .= " ORDER BY archive_date DESC, order_date DESC LIMIT 500";
    
    $stmt = $pdo->prepare($archive_query);
    $stmt->execute($archive_params);
    $archive_orders = $stmt->fetchAll();
    
    // Get sales summary
    $sales_summary = $pdo->query("
        SELECT * FROM tbl_daily_sales_summary 
        ORDER BY sale_date DESC 
        LIMIT 30
    ")->fetchAll();
}

// Get initial logs for realtime view (first load only)
$initial_logs = [];
if ($view == 'realtime') {
    $query = "
        SELECT 
            'order' as type,
            o.id as reference_id,
            o.order_date as date_time,
            u.username as cashier,
            CONCAT('Processed Order #', o.id, ' for ₱', o.total_amount) as description,
            o.total_amount as amount,
            o.payment_method,
            o.status,
            o.id as activity_id
        FROM tbl_orders o
        JOIN tbl_users u ON o.created_by = u.id
        WHERE u.role = 'cashier'
    ";

    $params = [];

    if ($filter_cashier) {
        $query .= " AND u.id = ?";
        $params[] = $filter_cashier;
    }

    if ($filter_date) {
        $query .= " AND DATE(o.order_date) = ?";
        $params[] = $filter_date;
    }

    $query .= " UNION ALL ";

    $query .= "
        SELECT 
            'inventory' as type,
            l.id as reference_id,
            l.log_time as date_time,
            u.username as cashier,
            CONCAT(l.change_type, ' ', l.quantity_changed, ' ', p.name) as description,
            NULL as amount,
            NULL as payment_method,
            NULL as status,
            l.id + 1000000 as activity_id
        FROM tbl_inventory_logs l
        JOIN tbl_products p ON l.product_id = p.id
        JOIN tbl_users u ON l.user_id = u.id
        WHERE u.role = 'cashier'
    ";

    if ($filter_cashier) {
        $query .= " AND u.id = ?";
        $params[] = $filter_cashier;
    }

    if ($filter_date) {
        $query .= " AND DATE(l.log_time) = ?";
        $params[] = $filter_date;
    }

    $query .= " ORDER BY date_time DESC LIMIT 500";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $initial_logs = $stmt->fetchAll();
    
    // Get max activity ID for real-time tracking
    $max_id = 0;
    if (!empty($initial_logs)) {
        $max_id = max(array_column($initial_logs, 'activity_id'));
    }
}

include '../../includes/header.php';
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

    .container-fluid {
        width: 100%;
        padding-right: 15px;
        padding-left: 15px;
        margin-right: auto;
        margin-left: auto;
        max-width: 1400px;
    }

    /* ===== SECTION HEADER ===== */
    .section-header {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .section-header h4 {
        font-size: 1.5rem;
        font-weight: 600;
        color: #333;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-header h4 i {
        color: #17a2b8;
    }

    .realtime-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #28a745;
        color: white;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 500;
        animation: pulse 2s infinite;
    }

    .realtime-badge i {
        font-size: 0.7rem;
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.8; }
        100% { opacity: 1; }
    }

    /* ===== TAB NAVIGATION ===== */
    .view-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 25px;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 10px;
        flex-wrap: wrap;
    }
    
    .view-tab {
        padding: 10px 25px;
        border-radius: 50px;
        background: white;
        color: #6c757d;
        text-decoration: none;
        font-weight: 500;
        transition: all 0.2s;
        border: 1px solid #dee2e6;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .view-tab:hover {
        background: #e9ecef;
        color: #495057;
        transform: translateY(-2px);
    }
    
    .view-tab.active {
        background: #17a2b8;
        color: white;
        border-color: #17a2b8;
    }
    
    .view-tab i {
        font-size: 1rem;
    }

    /* ===== SUMMARY CARDS ===== */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }

    @media (max-width: 768px) {
        .summary-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
    }

    .summary-card {
        background: white;
        border-radius: 15px;
        padding: 1.2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-left: 4px solid #17a2b8;
        transition: transform 0.2s;
    }
    
    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }
    
    .summary-card .title {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 0.3rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    
    .summary-card .value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #17a2b8;
        margin-bottom: 0.3rem;
    }
    
    .summary-card .sub {
        font-size: 0.75rem;
        color: #28a745;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* ===== FILTER SECTION ===== */
    .filter-section {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .filter-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        align-items: end;
    }

    @media (max-width: 768px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }
    }

    .filter-group {
        display: flex;
        flex-direction: column;
    }

    .filter-group label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 5px;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .filter-group label i {
        color: #17a2b8;
    }

    .filter-group input,
    .filter-group select {
        padding: 10px 12px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-family: 'Poppins', sans-serif;
        transition: all 0.2s;
    }

    .filter-group input:focus,
    .filter-group select:focus {
        border-color: #17a2b8;
        outline: none;
        box-shadow: 0 0 0 3px rgba(23,162,184,0.1);
    }

    .filter-actions {
        display: flex;
        gap: 10px;
    }

    .btn-filter {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        flex: 1;
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(23,162,184,0.3);
    }

    .btn-reset {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s;
        flex: 1;
    }

    .btn-reset:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(108,117,125,0.3);
        color: white;
    }

    /* ===== TABLE STYLES ===== */
    .table-container {
        background: white;
        border-radius: 15px;
        padding: 1.5rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        overflow-x: auto;
        margin-bottom: 20px;
    }

    .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
        flex-wrap: wrap;
        gap: 10px;
    }

    .table-header h5 {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .table-header h5 i {
        color: #17a2b8;
    }

    .badge-count {
        background: #17a2b8;
        color: white;
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 0.75rem;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .table thead {
        background: #f8f9fa;
    }

    .table thead th {
        padding: 12px 15px;
        font-weight: 600;
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        white-space: nowrap;
    }

    .table tbody td {
        padding: 10px 15px;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }

    .table tbody tr {
        transition: all 0.2s;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
    }

    .table tbody tr.new-activity {
        animation: newActivityFlash 2s ease;
        background-color: #fff3cd;
    }

    @keyframes newActivityFlash {
        0% { background-color: #fff3cd; transform: scale(1.01); }
        50% { background-color: #ffe69c; }
        100% { background-color: transparent; transform: scale(1); }
    }

    /* ===== BADGES ===== */
    .badge-order {
        background: #17a2b8;
        color: white;
        padding: 0.3rem 0.6rem;
        border-radius: 50px;
        font-size: 0.7rem;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    
    .badge-inventory {
        background: #ff8c00;
        color: white;
        padding: 0.3rem 0.6rem;
        border-radius: 50px;
        font-size: 0.7rem;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }
    
    .badge-completed {
        background: #28a745;
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        font-size: 0.7rem;
    }
    
    .badge-pending {
        background: #ffc107;
        color: #333;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        font-size: 0.7rem;
    }
    
    .badge-cancelled {
        background: #dc3545;
        color: white;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        font-size: 0.7rem;
    }

    .badge-archive {
        background: #6f42c1;
        color: white;
    }

    .cashier-badge {
        background: #17a2b8;
        color: white;
        padding: 0.2rem 0.6rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 3px;
    }

    /* ===== ARCHIVE STATS ===== */
    .archive-stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 20px;
        margin-bottom: 25px;
    }

    @media (max-width: 768px) {
        .archive-stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
    }

    .archive-stat-card {
        background: white;
        border-radius: 15px;
        padding: 1.2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-left: 4px solid #6f42c1;
    }
    
    .archive-stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: #6f42c1;
    }
    
    .archive-stat-label {
        font-size: 0.8rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* ===== EMPTY STATE ===== */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 3rem;
        color: #dee2e6;
        margin-bottom: 1rem;
    }

    .empty-state p {
        font-size: 1rem;
        margin: 0;
    }

    /* ===== SOUND TOGGLE ===== */
    .sound-toggle {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: rgba(255,255,255,0.2);
        color: #17a2b8;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.8rem;
        cursor: pointer;
        border: 1px solid #17a2b8;
        transition: all 0.2s;
    }

    .sound-toggle:hover {
        background: #17a2b8;
        color: white;
    }

    .sound-toggle.muted {
        opacity: 0.5;
        border-color: #6c757d;
        color: #6c757d;
    }

    /* ===== EXPORT BUTTON ===== */
    .btn-export {
        background: linear-gradient(135deg, #28a745, #218838);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 8px 15px;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.2s;
    }

    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        color: white;
    }

    /* ===== LOADING INDICATOR ===== */
    .loading-overlay {
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #17a2b8;
        color: white;
        padding: 10px 20px;
        border-radius: 50px;
        font-size: 0.9rem;
        display: none;
        align-items: center;
        gap: 10px;
        z-index: 9999;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        animation: slideInRight 0.3s ease;
    }

    .loading-overlay.show {
        display: flex;
    }

    .loading-overlay i {
        animation: spin 1s linear infinite;
    }

    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }

    /* ===== SCROLL TO TOP ===== */
    .scroll-to-top {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 5px 15px rgba(23,162,184,0.3);
        transition: all 0.3s;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
    }

    .scroll-to-top.show {
        opacity: 1;
        visibility: visible;
    }

    .scroll-to-top:hover {
        background: #ff8c00;
        transform: translateY(-3px);
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
        .section-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .filter-actions {
            flex-direction: column;
        }
        
        .table thead th {
            font-size: 0.8rem;
            padding: 8px 10px;
        }
        
        .table tbody td {
            font-size: 0.8rem;
            padding: 6px 10px;
        }
    }
</style>

<!-- Notification Sound Element -->
<audio id="notificationSound" preload="auto">
    <source src="/assets/sounds/notification.mp3" type="audio/mpeg">
</audio>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <i class="fas fa-sync-alt fa-spin"></i>
    <span>Updating...</span>
</div>

<div class="container-fluid">
    <!-- Section Header -->
    <div class="section-header">
        <h4>
            <i class="fas fa-history"></i>
            Activity Monitor
            <?php if ($view == 'realtime'): ?>
                <span class="realtime-badge">
                    <i class="fas fa-sync-alt fa-spin"></i> LIVE
                </span>
            <?php endif; ?>
        </h4>
        <span class="sound-toggle" id="soundToggle" onclick="toggleSound()">
            <i class="fas fa-volume-up"></i> <span id="soundText">sound on</span>
        </span>
    </div>

    <!-- View Tabs -->
    <div class="view-tabs">
        <a href="?view=realtime<?php echo $filter_cashier ? '&cashier='.$filter_cashier : ''; ?><?php echo $filter_date ? '&date='.$filter_date : ''; ?>" 
           class="view-tab <?php echo $view == 'realtime' ? 'active' : ''; ?>">
            <i class="fas fa-sync-alt"></i> Real-time Activity
        </a>
        <a href="?view=archives" class="view-tab <?php echo $view == 'archives' ? 'active' : ''; ?>">
            <i class="fas fa-archive"></i> Archives
        </a>
    </div>

    <?php if ($view == 'realtime'): ?>
        <!-- ===== REALTIME VIEW ===== -->
        
        <!-- Summary Cards -->
        <?php if (empty($summary)): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                No cashier activity found for today.
            </div>
        <?php else: ?>
            <div class="summary-grid">
                <?php foreach ($summary as $cashier): ?>
                <div class="summary-card">
                    <div class="title">
                        <i class="fas fa-user-circle"></i>
                        <?php echo htmlspecialchars($cashier['username']); ?>
                    </div>
                    <div class="value">
                        ₱<?php echo number_format($cashier['sales_today'], 2); ?>
                    </div>
                    <div class="sub">
                        <span><i class="fas fa-shopping-cart"></i> <?php echo $cashier['orders_today']; ?> orders</span>
                        <span><i class="fas fa-box"></i> <?php echo $cashier['stock_changes']; ?> changes</span>
                    </div>
                    <small class="text-muted">
                        Last: <?php echo $cashier['last_activity'] ? date('h:i A', strtotime($cashier['last_activity'])) : 'No activity'; ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Filter Section -->
        <div class="filter-section">
            <form method="get" class="filter-grid" id="filterForm">
                <input type="hidden" name="view" value="realtime">
                
                <div class="filter-group">
                    <label><i class="fas fa-user"></i> Cashier</label>
                    <select name="cashier" class="form-select">
                        <option value="">All Cashiers</option>
                        <?php foreach ($cashiers as $c): ?>
                        <option value="<?php echo $c['id']; ?>" 
                            <?php echo $filter_cashier == $c['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['username']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><i class="fas fa-calendar-alt"></i> Date</label>
                    <input type="date" name="date" class="form-control" 
                           value="<?php echo $filter_date; ?>" max="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="filter-actions" style="grid-column: span 2;">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="?view=realtime" class="btn-reset">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>

        <!-- Real-time Activities Table -->
        <div class="table-container">
            <div class="table-header">
                <h5>
                    <i class="fas fa-list"></i>
                    Live Activity Feed
                </h5>
                <span class="badge-count" id="activityCount"><?php echo count($initial_logs); ?> activities</span>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="activitiesTable">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Cashier</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($initial_logs)): ?>
                        <tr>
                            <td colspan="7" class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <p>No activities found</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($initial_logs as $log): ?>
                            <tr data-activity-id="<?php echo $log['activity_id']; ?>">
                                <td>
                                    <i class="far fa-clock me-1 text-muted"></i>
                                    <?php echo date('h:i:s A', strtotime($log['date_time'])); ?>
                                    <br>
                                    <small class="text-muted"><?php echo date('M d', strtotime($log['date_time'])); ?></small>
                                </td>
                                <td>
                                    <span class="cashier-badge">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($log['cashier']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['type'] == 'order'): ?>
                                        <span class="badge-order">
                                            <i class="fas fa-shopping-cart"></i> Order
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-inventory">
                                            <i class="fas fa-box"></i> Inventory
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($log['description']); ?>
                                    <?php if ($log['type'] == 'order'): ?>
                                        <br>
                                        <small class="text-muted">#<?php echo $log['reference_id']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['amount']): ?>
                                        <strong>₱<?php echo number_format($log['amount'], 2); ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['payment_method']): ?>
                                        <span class="badge bg-info text-white">
                                            <?php echo ucfirst($log['payment_method']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($log['status']): ?>
                                        <span class="badge <?php 
                                            echo $log['status'] == 'completed' ? 'bg-success' : 
                                                ($log['status'] == 'pending' ? 'bg-warning' : 'bg-danger'); 
                                        ?>">
                                            <?php echo ucfirst($log['status']); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    <?php else: ?>
        <!-- ===== ARCHIVES VIEW ===== -->
        
        <?php 
        $total_archived = count($archive_orders);
        $total_sales = array_sum(array_column($archive_orders, 'total_amount'));
        $total_items = array_sum(array_column($archive_orders, 'items_count'));
        $total_dates = count($archive_dates);
        ?>
        
        <!-- Archive Stats -->
        <div class="archive-stats-grid">
            <div class="archive-stat-card">
                <div class="archive-stat-value"><?php echo number_format($total_archived); ?></div>
                <div class="archive-stat-label">Archived Orders</div>
            </div>
            <div class="archive-stat-card">
                <div class="archive-stat-value">₱<?php echo number_format($total_sales, 2); ?></div>
                <div class="archive-stat-label">Archived Sales</div>
            </div>
            <div class="archive-stat-card">
                <div class="archive-stat-value"><?php echo number_format($total_items); ?></div>
                <div class="archive-stat-label">Items Sold</div>
            </div>
            <div class="archive-stat-card">
                <div class="archive-stat-value"><?php echo $total_dates; ?></div>
                <div class="archive-stat-label">Archive Dates</div>
            </div>
        </div>
        
        <!-- Archive Filter -->
        <div class="filter-section">
            <form method="get" class="filter-grid">
                <input type="hidden" name="view" value="archives">
                
                <div class="filter-group">
                    <label><i class="fas fa-calendar-day"></i> Archive Date</label>
                    <select name="archive_date" class="form-select">
                        <option value="">All Dates</option>
                        <?php foreach ($archive_dates as $date): ?>
                        <option value="<?php echo $date; ?>" <?php echo ($_GET['archive_date'] ?? '') == $date ? 'selected' : ''; ?>>
                            <?php echo date('F d, Y', strtotime($date)); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label><i class="fas fa-search"></i> Search</label>
                    <input type="text" name="archive_search" class="form-control" 
                           placeholder="Order ID or Customer..." 
                           value="<?php echo htmlspecialchars($_GET['archive_search'] ?? ''); ?>">
                </div>
                
                <div class="filter-group">
                    <label><i class="fas fa-tag"></i> Status</label>
                    <select name="archive_status" class="form-select">
                        <option value="">All Status</option>
                        <option value="completed" <?php echo ($_GET['archive_status'] ?? '') == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="pending" <?php echo ($_GET['archive_status'] ?? '') == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="cancelled" <?php echo ($_GET['archive_status'] ?? '') == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <div class="filter-actions">
                    <button type="submit" class="btn-filter">
                        <i class="fas fa-filter"></i> Apply
                    </button>
                    <a href="?view=archives" class="btn-reset">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Daily Sales Summary -->
        <div class="table-container">
            <div class="table-header">
                <h5><i class="fas fa-chart-line"></i> Daily Sales Summary (Last 30 Days)</h5>
                <?php if (!empty($sales_summary)): ?>
                <a href="export_archives.php?format=summary" class="btn-export btn-sm">
                    <i class="fas fa-file-csv"></i> Export
                </a>
                <?php endif; ?>
            </div>
            
            <?php if (empty($sales_summary)): ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <p>No sales summary data available</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Orders</th>
                                <th>Total Sales</th>
                                <th>Cash</th>
                                <th>GCash</th>
                                <th>PayMaya</th>
                                <th>Completed</th>
                                <th>Pending</th>
                                <th>Cancelled</th>
                                <th>Items</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales_summary as $s): ?>
                            <tr>
                                <td><strong><?php echo date('M d, Y', strtotime($s['sale_date'])); ?></strong></td>
                                <td class="text-center"><?php echo $s['total_orders']; ?></td>
                                <td class="text-success fw-bold">₱<?php echo number_format($s['total_sales'], 2); ?></td>
                                <td>₱<?php echo number_format($s['cash_sales'], 2); ?></td>
                                <td>₱<?php echo number_format($s['gcash_sales'], 2); ?></td>
                                <td>₱<?php echo number_format($s['paymaya_sales'], 2); ?></td>
                                <td><span class="badge bg-success"><?php echo $s['completed_orders']; ?></span></td>
                                <td><span class="badge bg-warning"><?php echo $s['pending_orders']; ?></span></td>
                                <td><span class="badge bg-secondary"><?php echo $s['cancelled_orders']; ?></span></td>
                                <td class="text-center"><?php echo $s['total_items_sold']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Archived Orders -->
        <div class="table-container">
            <div class="table-header">
                <h5><i class="fas fa-archive"></i> Archived Orders</h5>
                <div class="d-flex gap-2">
                    <span class="badge-count"><?php echo count($archive_orders); ?> records</span>
                    <?php if (!empty($archive_orders)): ?>
                    <a href="export_archives.php?<?php echo http_build_query($_GET); ?>" class="btn-export btn-sm">
                        <i class="fas fa-file-csv"></i> Export
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (empty($archive_orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-archive"></i>
                    <p>No archived orders found</p>
                    <?php if (!empty($_GET['archive_date']) || !empty($_GET['archive_search']) || !empty($_GET['archive_status'])): ?>
                        <a href="?view=archives" class="btn btn-sm btn-outline-secondary mt-3">Clear Filters</a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Archive Date</th>
                                <th>Order ID</th>
                                <th>Daily #</th>
                                <th>Order Date</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Items</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($archive_orders as $a): ?>
                            <tr>
                                <td><span class="badge bg-info text-white"><?php echo date('M d, Y', strtotime($a['archive_date'])); ?></span></td>
                                <td><strong>#<?php echo $a['original_order_id']; ?></strong></td>
                                <td><code>ORD-<?php echo str_pad($a['daily_order_number'], 4, '0', STR_PAD_LEFT); ?></code></td>
                                <td><?php echo date('M d, Y H:i', strtotime($a['order_date'])); ?></td>
                                <td><?php echo htmlspecialchars($a['customer_name'] ?: 'Walk-in'); ?></td>
                                <td class="fw-bold">₱<?php echo number_format($a['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $a['payment_method'] == 'cash' ? 'success' : 
                                            ($a['payment_method'] == 'gcash' ? 'primary' : 'danger'); 
                                    ?>">
                                        <?php echo ucfirst($a['payment_method']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $a['status'] == 'completed' ? 'success' : 
                                            ($a['status'] == 'pending' ? 'warning' : 'secondary'); 
                                    ?>">
                                        <?php echo ucfirst($a['status']); ?>
                                    </span>
                                </td>
                                <td class="text-center"><?php echo $a['items_count']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
    <?php endif; ?>
    
    <!-- Back to Tools Link -->
    <div class="mt-4 text-center">
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> Back to Tools
        </a>
    </div>
</div>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// ===== REAL-TIME SYSTEM FOR ACTIVITY LOGS =====
let lastActivityId = <?php echo isset($max_id) ? $max_id : 0; ?>;
let soundEnabled = localStorage.getItem('staffSoundEnabled') !== 'false';
let updateInterval;
let isUpdating = false;
let deviceId = localStorage.getItem('staff_device_id');

if (!deviceId) {
    deviceId = 'staff_' + Math.random().toString(36).substring(2, 15);
    localStorage.setItem('staff_device_id', deviceId);
}

// Sound toggle
function toggleSound() {
    soundEnabled = !soundEnabled;
    localStorage.setItem('staffSoundEnabled', soundEnabled);
    updateSoundUI();
}

function updateSoundUI() {
    const toggle = document.getElementById('soundToggle');
    if (toggle) {
        if (soundEnabled) {
            toggle.innerHTML = '<i class="fas fa-volume-up"></i> <span id="soundText">sound on</span>';
            toggle.classList.remove('muted');
        } else {
            toggle.innerHTML = '<i class="fas fa-volume-mute"></i> <span id="soundText">sound off</span>';
            toggle.classList.add('muted');
        }
    }
}

// Play notification sound
function playNotificationSound() {
    if (!soundEnabled) return;
    
    const sound = document.getElementById('notificationSound');
    if (sound) {
        sound.currentTime = 0;
        sound.play().catch(e => console.log('Sound play failed:', e));
    }
}

// Show loading overlay
function showLoading() {
    document.getElementById('loadingOverlay').classList.add('show');
}

function hideLoading() {
    setTimeout(() => {
        document.getElementById('loadingOverlay').classList.remove('show');
    }, 500);
}

// Create activity row HTML
function createActivityRow(activity) {
    let statusBadge = '';
    if (activity.status) {
        let statusClass = activity.status === 'completed' ? 'bg-success' : 
                         (activity.status === 'pending' ? 'bg-warning' : 'bg-danger');
        statusBadge = `<span class="badge ${statusClass}">${activity.status.charAt(0).toUpperCase() + activity.status.slice(1)}</span>`;
    } else {
        statusBadge = '<span class="text-muted">—</span>';
    }
    
    let paymentBadge = activity.payment_method ? 
        `<span class="badge bg-info text-white">${activity.payment_method.charAt(0).toUpperCase() + activity.payment_method.slice(1)}</span>` : 
        '<span class="text-muted">—</span>';
    
    let amountDisplay = activity.amount ? 
        `<strong>₱${parseFloat(activity.amount).toFixed(2)}</strong>` : 
        '<span class="text-muted">—</span>';
    
    let typeBadge = activity.type === 'order' ?
        '<span class="badge-order"><i class="fas fa-shopping-cart"></i> Order</span>' :
        '<span class="badge-inventory"><i class="fas fa-box"></i> Inventory</span>';
    
    let date = new Date(activity.date_time);
    let timeStr = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    let dateStr = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    
    return `<tr class="new-activity" data-activity-id="${activity.activity_id}">
        <td>
            <i class="far fa-clock me-1 text-muted"></i> ${timeStr}<br>
            <small class="text-muted">${dateStr}</small>
        </td>
        <td>
            <span class="cashier-badge">
                <i class="fas fa-user"></i> ${escapeHtml(activity.cashier)}
            </span>
        </td>
        <td>${typeBadge}</td>
        <td>${escapeHtml(activity.description)}${activity.type === 'order' ? `<br><small class="text-muted">#${activity.reference_id}</small>` : ''}</td>
        <td>${amountDisplay}</td>
        <td>${paymentBadge}</td>
        <td>${statusBadge}</td>
    </tr>`;
}

// Escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Fetch new activities
function fetchNewActivities() {
    if (isUpdating) return;
    
    isUpdating = true;
    showLoading();
    
    fetch('/api/get_realtime_activities.php?last_id=' + lastActivityId + '&t=' + Date.now())
        .then(res => res.json())
        .then(data => {
            if (data.success && data.new_activities && data.new_activities.length > 0) {
                console.log('New activities:', data.new_activities.length);
                
                const tbody = document.querySelector('#activitiesTable tbody');
                if (!tbody) return;
                
                // Remove empty state if present
                if (tbody.children.length === 1 && tbody.children[0].classList.contains('empty-state')) {
                    tbody.innerHTML = '';
                }
                
                // Add new activities at the top
                data.new_activities.forEach(activity => {
                    const rowHtml = createActivityRow(activity);
                    tbody.insertAdjacentHTML('afterbegin', rowHtml);
                });
                
                // Update last ID
                lastActivityId = data.max_id;
                
                // Update count
                const countSpan = document.getElementById('activityCount');
                if (countSpan) {
                    const currentCount = parseInt(countSpan.textContent) || 0;
                    countSpan.textContent = currentCount + data.new_activities.length;
                }
                
                // Play sound
                playNotificationSound();
                
                // Highlight table header
                const tableHeader = document.querySelector('.table-header');
                if (tableHeader) {
                    tableHeader.style.backgroundColor = '#d4edda';
                    setTimeout(() => {
                        tableHeader.style.backgroundColor = '';
                    }, 500);
                }
            }
            
            isUpdating = false;
            hideLoading();
        })
        .catch(error => {
            console.error('Error fetching activities:', error);
            isUpdating = false;
            hideLoading();
        });
}

// Initialize
updateSoundUI();

// Start real-time updates (only in realtime view)
<?php if ($view == 'realtime'): ?>
    updateInterval = setInterval(fetchNewActivities, 5000); // Check every 5 seconds
<?php endif; ?>

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    if (updateInterval) {
        clearInterval(updateInterval);
    }
});

// Scroll to top
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.addEventListener('scroll', function() {
    const btn = document.getElementById('scrollToTop');
    if (window.scrollY > 300) {
        btn.classList.add('show');
    } else {
        btn.classList.remove('show');
    }
});

// Touch-friendly
document.querySelectorAll('.btn-filter, .btn-reset, .btn-export').forEach(btn => {
    btn.addEventListener('touchstart', function() {
        this.style.transform = 'scale(0.98)';
    });
    btn.addEventListener('touchend', function() {
        this.style.transform = '';
    });
});

// Pull to refresh (mobile)
let touchStart = 0;
document.addEventListener('touchstart', e => {
    touchStart = e.changedTouches[0].screenY;
});

document.addEventListener('touchend', e => {
    const dist = e.changedTouches[0].screenY - touchStart;
    if (window.scrollY === 0 && dist > 100 && <?php echo $view == 'realtime' ? 'true' : 'false'; ?>) {
        fetchNewActivities();
    }
});
</script>

<?php include '../../includes/footer.php'; ?>