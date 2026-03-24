<?php
require_once '../../includes/config.php';
require_once '../../includes/daily_counter.php'; // Include daily counter functions
requireLogin();
requireRole(['admin', 'manager']);

// Get date range from request or set defaults
$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$view = $_GET['view'] ?? 'both'; // table, charts, or both

// Fetch daily sales summaries for the date range
$dailySummaries = $pdo->prepare("
    SELECT * FROM tbl_daily_sales_summary 
    WHERE sale_date BETWEEN ? AND ?
    ORDER BY sale_date DESC
");
$dailySummaries->execute([$from, $to]);
$dailySummaries = $dailySummaries->fetchAll();

// Fetch orders for the selected date range with customer names and daily numbers
$stmt = $pdo->prepare("
    SELECT 
        o.id,
        o.customer_id,
        o.customer_name,
        o.total_amount,
        o.payment_method,
        o.status,
        o.order_date,
        o.service_fee,
        COALESCE(o.customer_name, 'Walk-in') as customer_name_display,
        DATE(o.order_date) as order_date_only
    FROM tbl_orders o 
    WHERE DATE(o.order_date) BETWEEN ? AND ? 
    ORDER BY o.order_date DESC
");
$stmt->execute([$from, $to]);
$orders = $stmt->fetchAll();

// Add daily order numbers to orders
foreach ($orders as &$order) {
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

// Calculate total sales and payment method totals
$totalSales = 0;
$cashTotal = 0;
$gcashTotal = 0;
$paymayaTotal = 0;
$dailySales = [];
$statusCounts = ['pending' => 0, 'completed' => 0, 'cancelled' => 0];

foreach ($orders as $o) {
    $totalSales += $o['total_amount'];
    
    // Group by payment method
    if ($o['payment_method'] == 'cash') $cashTotal += $o['total_amount'];
    elseif ($o['payment_method'] == 'gcash') $gcashTotal += $o['total_amount'];
    elseif ($o['payment_method'] == 'paymaya') $paymayaTotal += $o['total_amount'];
    
    // Count by status
    $statusCounts[$o['status']]++;
    
    // Group by date for daily trend
    $date = date('Y-m-d', strtotime($o['order_date']));
    if (!isset($dailySales[$date])) {
        $dailySales[$date] = 0;
    }
    $dailySales[$date] += $o['total_amount'];
}

// Sort daily sales by date
ksort($dailySales);

// Get top selling products
$topProducts = $pdo->prepare("
    SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as revenue
    FROM tbl_order_items oi
    JOIN tbl_products p ON oi.product_id = p.id
    JOIN tbl_orders o ON oi.order_id = o.id
    WHERE DATE(o.order_date) BETWEEN ? AND ?
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 10
");
$topProducts->execute([$from, $to]);
$topProducts = $topProducts->fetchAll();

include '../../includes/header.php';
?>

<!-- Chart.js Library -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
    /* ===== RESET & BASE STYLES ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
    }

    .container-fluid {
        width: 100%;
        padding-right: 10px;
        padding-left: 10px;
        margin-right: auto;
        margin-left: auto;
    }

    @media (min-width: 768px) {
        .container-fluid {
            padding-right: 15px;
            padding-left: 15px;
        }
    }

    /* ===== CUSTOM SCROLLBAR ===== */
    ::-webkit-scrollbar {
        width: 6px;
        height: 6px;
    }
    ::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #d35400, #e67e22);
        border-radius: 10px;
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #e67e22;
    }

    /* ===== SECTION HEADER ===== */
    .section-header {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 20px;
        padding-bottom: 8px;
        border-bottom: 2px solid #e0e0e0;
    }

    @media (min-width: 768px) {
        .section-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .section-header h4 {
        font-size: 1.2rem;
        font-weight: 700;
        color: #2c3e50;
        margin: 0;
        position: relative;
        display: inline-block;
    }

    @media (min-width: 768px) {
        .section-header h4 {
            font-size: 1.5rem;
        }
    }

    .section-header h4 i {
        color: #d35400;
        margin-right: 6px;
    }

    .section-header h4::after {
        content: '';
        position: absolute;
        bottom: -8px;
        left: 0;
        width: 40px;
        height: 3px;
        background: linear-gradient(90deg, #d35400, #e67e22);
        border-radius: 2px;
    }

    @media (min-width: 768px) {
        .section-header h4::after {
            width: 60px;
        }
    }

    .view-toggle {
        display: flex;
        gap: 3px;
        background: white;
        padding: 4px;
        border-radius: 50px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        width: 100%;
    }

    @media (min-width: 480px) {
        .view-toggle {
            width: auto;
        }
    }

    .view-toggle .btn {
        flex: 1;
        border-radius: 50px;
        padding: 8px 10px;
        font-weight: 500;
        font-size: 0.75rem;
        transition: all 0.3s ease;
        border: none;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        white-space: nowrap;
    }

    @media (min-width: 768px) {
        .view-toggle .btn {
            padding: 8px 20px;
            font-size: 0.9rem;
            gap: 8px;
        }
    }

    .view-toggle .btn.active {
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
        box-shadow: 0 5px 15px rgba(211,84,0,0.3);
    }

    .view-toggle .btn:not(.active) {
        background: transparent;
        color: #666;
    }

    .view-toggle .btn:not(.active):hover {
        background: #f8f9fa;
        color: #d35400;
    }

    /* ===== DATE FILTER SECTION ===== */
    .filter-card {
        background: white;
        border-radius: 15px;
        padding: 15px;
        margin-bottom: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.05);
    }

    @media (min-width: 768px) {
        .filter-card {
            padding: 20px;
        }
    }

    .filter-form {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    @media (min-width: 768px) {
        .filter-form {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            align-items: end;
        }
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .filter-group label {
        font-weight: 500;
        color: #2c3e50;
        margin-bottom: 5px;
        font-size: 0.75rem;
    }

    @media (min-width: 768px) {
        .filter-group label {
            font-size: 0.85rem;
        }
    }

    .filter-group input {
        width: 100%;
        padding: 10px 12px;
        border: 2px solid #e0e0e0;
        border-radius: 50px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.85rem;
        transition: all 0.3s ease;
        background: white;
    }

    @media (min-width: 768px) {
        .filter-group input {
            padding: 12px 15px;
            font-size: 0.95rem;
        }
    }

    .filter-group input:focus {
        border-color: #d35400;
        box-shadow: 0 0 0 3px rgba(211,84,0,0.1);
        outline: none;
    }

    .btn-filter {
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 10px 15px;
        font-weight: 500;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        box-shadow: 0 5px 15px rgba(211,84,0,0.3);
        width: 100%;
    }

    @media (min-width: 768px) {
        .btn-filter {
            padding: 12px 20px;
            font-size: 0.95rem;
        }
    }

    .btn-filter:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(211,84,0,0.4);
    }

    .btn-export {
        background: linear-gradient(135deg, #28a745, #218838);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 10px 15px;
        font-weight: 500;
        font-size: 0.85rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        box-shadow: 0 5px 15px rgba(40,167,69,0.3);
        width: 100%;
    }

    @media (min-width: 768px) {
        .btn-export {
            padding: 12px 20px;
            font-size: 0.95rem;
        }
    }

    .btn-export:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(40,167,69,0.4);
        color: white;
    }

    .button-group {
        display: flex;
        gap: 8px;
        margin-top: 5px;
    }

    @media (min-width: 768px) {
        .button-group {
            margin-top: 0;
            grid-column: span 2;
        }
    }

    /* ===== DAILY ORDER BADGE ===== */
    .daily-order-badge {
        display: inline-block;
        background: #008080;
        color: white;
        font-size: 0.6rem;
        padding: 0.15rem 0.4rem;
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

    /* ===== SUMMARY CARDS - 2x2 Grid on Mobile ===== */
    .summary-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        margin-bottom: 20px;
    }

    @media (min-width: 768px) {
        .summary-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
    }

    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 12px 8px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
        text-align: center;
    }

    @media (min-width: 768px) {
        .summary-card {
            padding: 20px 15px;
            border-radius: 15px;
        }
    }

    .summary-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 3px;
        background: linear-gradient(90deg, #d35400, #e67e22, #f39c12);
        transform: translateX(-100%);
        transition: transform 0.5s ease;
    }

    .summary-card:hover::before {
        transform: translateX(0);
    }

    .summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }

    .card-icon {
        width: 35px;
        height: 35px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        margin: 0 auto 8px;
        background: rgba(211,84,0,0.1);
        color: #d35400;
    }

    @media (min-width: 768px) {
        .card-icon {
            width: 50px;
            height: 50px;
            font-size: 1.5rem;
            margin: 0 auto 12px;
        }
    }

    .summary-card h5 {
        color: #666;
        font-size: 0.65rem;
        font-weight: 500;
        margin-bottom: 4px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    @media (min-width: 768px) {
        .summary-card h5 {
            font-size: 0.8rem;
            margin-bottom: 8px;
        }
    }

    .summary-card h2 {
        font-size: 1rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 2px;
    }

    @media (min-width: 768px) {
        .summary-card h2 {
            font-size: 1.6rem;
            margin-bottom: 5px;
        }
    }

    .summary-card small {
        color: #999;
        font-size: 0.55rem;
        display: block;
    }

    @media (min-width: 768px) {
        .summary-card small {
            font-size: 0.7rem;
        }
    }

    /* ===== DAILY SUMMARY CARDS ===== */
    .daily-summary-section {
        margin-bottom: 30px;
    }

    .daily-summary-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .daily-summary-title i {
        color: #d35400;
    }

    .daily-summary-grid {
        display: grid;
        grid-template-columns: repeat(1, 1fr);
        gap: 10px;
        margin-bottom: 20px;
    }

    @media (min-width: 640px) {
        .daily-summary-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 1024px) {
        .daily-summary-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    .daily-summary-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-left: 4px solid #d35400;
        transition: transform 0.2s;
    }

    .daily-summary-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .daily-summary-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 1px solid #eee;
    }

    .daily-summary-date {
        font-weight: 600;
        color: #d35400;
        font-size: 0.95rem;
    }

    .daily-summary-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }

    .daily-stat {
        text-align: center;
    }

    .daily-stat-label {
        font-size: 0.7rem;
        color: #666;
        margin-bottom: 2px;
    }

    .daily-stat-value {
        font-weight: 600;
        color: #2c3e50;
        font-size: 0.9rem;
    }

    .daily-stat-value.highlight {
        color: #d35400;
        font-size: 1rem;
    }

    /* ===== CHART CARDS ===== */
    .chart-card {
        background: white;
        border-radius: 12px;
        padding: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        margin-bottom: 15px;
        border: 1px solid rgba(0,0,0,0.05);
    }

    @media (min-width: 768px) {
        .chart-card {
            padding: 20px;
            margin-bottom: 20px;
        }
    }

    .chart-header {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-bottom: 12px;
    }

    @media (min-width: 768px) {
        .chart-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .chart-header h5 {
        font-size: 0.9rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
    }

    @media (min-width: 768px) {
        .chart-header h5 {
            font-size: 1.1rem;
        }
    }

    .chart-header h5 i {
        color: #d35400;
        margin-right: 5px;
    }

    .chart-header .badge {
        background: #d35400;
        color: white;
        padding: 4px 8px;
        border-radius: 50px;
        font-size: 0.65rem;
        width: fit-content;
    }

    @media (min-width: 768px) {
        .chart-header .badge {
            padding: 5px 10px;
            font-size: 0.75rem;
        }
    }

    .chart-container {
        position: relative;
        height: 200px;
        width: 100%;
    }

    @media (min-width: 768px) {
        .chart-container {
            height: 300px;
        }
    }

    /* ===== TABLE CARD WITH SCROLLABLE TABLE ===== */
    .table-card {
        background: white;
        border-radius: 12px;
        padding: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        margin-bottom: 15px;
        border: 1px solid rgba(0,0,0,0.05);
        overflow: hidden;
    }

    @media (min-width: 768px) {
        .table-card {
            padding: 20px;
            margin-bottom: 20px;
        }
    }

    .table-container {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 400px;
        -webkit-overflow-scrolling: touch;
        border-radius: 8px;
        margin-top: 10px;
    }

    @media (min-width: 768px) {
        .table-container {
            max-height: 500px;
        }
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
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
        font-weight: 600;
        padding: 8px 6px;
        font-size: 0.7rem;
        white-space: nowrap;
        border: none;
    }

    @media (min-width: 768px) {
        .table thead th {
            padding: 12px 10px;
            font-size: 0.8rem;
        }
    }

    .table thead th:first-child {
        border-top-left-radius: 8px;
    }

    .table thead th:last-child {
        border-top-right-radius: 8px;
    }

    .table tbody tr {
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
    }

    .table tbody td {
        padding: 6px 4px;
        border-bottom: 1px solid #eee;
        color: #2c3e50;
        font-size: 0.7rem;
        white-space: nowrap;
    }

    @media (min-width: 768px) {
        .table tbody td {
            padding: 10px 8px;
            font-size: 0.8rem;
        }
    }

    .badge-order {
        background: #e67e22;
        color: white;
        padding: 2px 5px;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 600;
        display: inline-block;
    }

    .badge-cash {
        background: #28a745;
        color: white;
        padding: 2px 5px;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 600;
        display: inline-block;
    }

    .badge-gcash {
        background: #0057e7;
        color: white;
        padding: 2px 5px;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 600;
        display: inline-block;
    }

    .badge-paymaya {
        background: #ff4d4d;
        color: white;
        padding: 2px 5px;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 600;
        display: inline-block;
    }

    .badge-completed {
        background: #28a745;
        color: white;
        padding: 2px 5px;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 600;
        display: inline-block;
    }

    .badge-pending {
        background: #ffc107;
        color: #333;
        padding: 2px 5px;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 600;
        display: inline-block;
    }

    .badge-cancelled {
        background: #dc3545;
        color: white;
        padding: 2px 5px;
        border-radius: 50px;
        font-size: 0.6rem;
        font-weight: 600;
        display: inline-block;
    }

    @media (min-width: 768px) {
        .badge-order, .badge-cash, .badge-gcash, .badge-paymaya,
        .badge-completed, .badge-pending, .badge-cancelled {
            padding: 4px 8px;
            font-size: 0.7rem;
        }
    }

    .customer-name {
        font-weight: 600;
        color: #d35400;
    }

    .empty-state {
        text-align: center;
        padding: 30px 15px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        animation: fadeInUp 0.8s ease;
    }

    @media (min-width: 768px) {
        .empty-state {
            padding: 50px 20px;
        }
    }

    .empty-state i {
        font-size: 3rem;
        color: #ddd;
        margin-bottom: 12px;
    }

    @media (min-width: 768px) {
        .empty-state i {
            font-size: 4rem;
        }
    }

    .empty-state h3 {
        font-size: 1.1rem;
        color: #2c3e50;
        margin-bottom: 8px;
    }

    .empty-state p {
        color: #666;
        margin-bottom: 15px;
        font-size: 0.8rem;
    }

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
        background: #e67e22;
        transform: translateY(-3px);
    }

    .loading-bar {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 3px;
        background: linear-gradient(90deg, #d35400, #e67e22, #f39c12, #d35400);
        background-size: 300% 100%;
        animation: loading 2s ease-in-out infinite;
        z-index: 9999;
        display: none;
    }

    .loading-bar.show {
        display: block;
    }

    @keyframes loading {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @media print {
        .btn-filter, .btn-export, .view-toggle, .scroll-to-top {
            display: none !important;
        }
        
        .summary-card, .chart-card, .table-card {
            box-shadow: none;
            border: 1px solid #ddd;
        }
    }

    @media (max-width: 360px) {
        .summary-grid {
            gap: 5px;
        }

        .summary-card {
            padding: 8px 4px;
        }

        .card-icon {
            width: 30px;
            height: 30px;
            font-size: 0.9rem;
        }

        .summary-card h2 {
            font-size: 0.9rem;
        }

        .daily-order-badge {
            font-size: 0.5rem;
            padding: 0.1rem 0.3rem;
        }
    }
</style>

<!-- Loading Bar -->
<div class="loading-bar" id="loadingBar"></div>

<div class="container-fluid">
    <!-- Header with View Toggle -->
    <div class="section-header">
        <h4>
            <i class="fas fa-chart-line"></i>
            Sales Report
        </h4>
        <div class="view-toggle">
            <a href="?from=<?php echo $from; ?>&to=<?php echo $to; ?>&view=table" 
               class="btn <?php echo $view == 'table' ? 'active' : ''; ?>">
                <i class="fas fa-table"></i> <span class="d-none d-sm-inline">Table</span>
            </a>
            <a href="?from=<?php echo $from; ?>&to=<?php echo $to; ?>&view=charts" 
               class="btn <?php echo $view == 'charts' ? 'active' : ''; ?>">
                <i class="fas fa-chart-pie"></i> <span class="d-none d-sm-inline">Charts</span>
            </a>
            <a href="?from=<?php echo $from; ?>&to=<?php echo $to; ?>&view=both" 
               class="btn <?php echo $view == 'both' ? 'active' : ''; ?>">
                <i class="fas fa-columns"></i> <span class="d-none d-sm-inline">Both</span>
            </a>
        </div>
    </div>

    <!-- Date Filter Section -->
    <div class="filter-card">
        <form method="get" class="filter-form">
            <input type="hidden" name="view" value="<?php echo $view; ?>">
            
            <div class="filter-group">
                <label><i class="fas fa-calendar-alt me-1"></i>From</label>
                <input type="date" name="from" value="<?php echo $from; ?>">
            </div>
            
            <div class="filter-group">
                <label><i class="fas fa-calendar-alt me-1"></i>To</label>
                <input type="date" name="to" value="<?php echo $to; ?>">
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn-filter">
                    <i class="fas fa-filter"></i> <span class="d-none d-sm-inline">Apply</span>
                </button>
                
                <a href="export_sales.php?from=<?php echo $from; ?>&to=<?php echo $to; ?>" class="btn-export">
                    <i class="fas fa-download"></i> <span class="d-none d-sm-inline">Export</span>
                </a>
            </div>
        </form>
    </div>

    <!-- Summary Cards - 2x2 Grid on Mobile -->
    <div class="summary-grid">
        <div class="summary-card">
            <div class="card-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <h5>Total Sales</h5>
            <h2>₱<?php echo number_format($totalSales, 2); ?></h2>
            <small><?php echo count($orders); ?> transactions</small>
        </div>
        
        <div class="summary-card">
            <div class="card-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <h5>Cash</h5>
            <h2>₱<?php echo number_format($cashTotal, 2); ?></h2>
            <small><?php echo $statusCounts['completed']; ?> completed</small>
        </div>
        
        <div class="summary-card">
            <div class="card-icon">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h5>GCash</h5>
            <h2>₱<?php echo number_format($gcashTotal, 2); ?></h2>
            <small>Digital</small>
        </div>
        
        <div class="summary-card">
            <div class="card-icon">
                <i class="fas fa-mobile-alt"></i>
            </div>
            <h5>PayMaya</h5>
            <h2>₱<?php echo number_format($paymayaTotal, 2); ?></h2>
            <small>Digital</small>
        </div>
    </div>

    <!-- Daily Sales Summary Section -->
    <?php if (!empty($dailySummaries)): ?>
    <div class="daily-summary-section">
        <h5 class="daily-summary-title">
            <i class="fas fa-calendar-alt"></i> Daily Sales Summary
        </h5>
        <div class="daily-summary-grid">
            <?php foreach ($dailySummaries as $summary): ?>
            <div class="daily-summary-card">
                <div class="daily-summary-header">
                    <span class="daily-summary-date">
                        <i class="fas fa-calendar-day me-1"></i>
                        <?php echo date('M d, Y', strtotime($summary['sale_date'])); ?>
                    </span>
                    <span class="badge bg-order">#<?php echo $summary['total_orders']; ?> orders</span>
                </div>
                <div class="daily-summary-stats">
                    <div class="daily-stat">
                        <div class="daily-stat-label">Total Sales</div>
                        <div class="daily-stat-value highlight">₱<?php echo number_format($summary['total_sales'], 2); ?></div>
                    </div>
                    <div class="daily-stat">
                        <div class="daily-stat-label">Items Sold</div>
                        <div class="daily-stat-value"><?php echo $summary['total_items_sold']; ?></div>
                    </div>
                    <div class="daily-stat">
                        <div class="daily-stat-label">Cash</div>
                        <div class="daily-stat-value">₱<?php echo number_format($summary['cash_sales'], 2); ?></div>
                    </div>
                    <div class="daily-stat">
                        <div class="daily-stat-label">GCash</div>
                        <div class="daily-stat-value">₱<?php echo number_format($summary['gcash_sales'], 2); ?></div>
                    </div>
                    <div class="daily-stat">
                        <div class="daily-stat-label">PayMaya</div>
                        <div class="daily-stat-value">₱<?php echo number_format($summary['paymaya_sales'], 2); ?></div>
                    </div>
                    <div class="daily-stat">
                        <div class="daily-stat-label">Status</div>
                        <div class="daily-stat-value">
                            <span class="badge bg-success"><?php echo $summary['completed_orders']; ?> ✓</span>
                            <span class="badge bg-warning"><?php echo $summary['pending_orders']; ?> ⏳</span>
                            <span class="badge bg-secondary"><?php echo $summary['cancelled_orders']; ?> ✗</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($view == 'charts' || $view == 'both'): ?>
        <!-- Charts Section -->
        <div class="row g-2 g-md-3">
            <!-- Daily Sales Trend -->
            <div class="col-md-8">
                <div class="chart-card">
                    <div class="chart-header">
                        <h5>
                            <i class="fas fa-chart-line"></i>
                            Daily Sales Trend
                        </h5>
                        <span class="badge"><?php echo count($dailySales); ?> days</span>
                    </div>
                    <div class="chart-container">
                        <canvas id="dailySalesChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Payment Method Distribution -->
            <div class="col-md-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h5>
                            <i class="fas fa-chart-pie"></i>
                            Payment Methods
                        </h5>
                    </div>
                    <div class="chart-container">
                        <canvas id="paymentChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products Chart -->
        <?php if (!empty($topProducts)): ?>
        <div class="chart-card">
            <div class="chart-header">
                <h5>
                    <i class="fas fa-chart-bar"></i>
                    Top Selling Products
                </h5>
            </div>
            <div class="chart-container" style="height: 250px;">
                <canvas id="topProductsChart"></canvas>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($view == 'table' || $view == 'both'): ?>
        <!-- Data Table with Scroll -->
        <div class="table-card">
            <div class="chart-header">
                <h5>
                    <i class="fas fa-list"></i>
                    Order Details
                </h5>
                <span class="badge"><?php echo count($orders); ?> orders</span>
            </div>

            <?php if (empty($orders)): ?>
                <div class="empty-state">
                    <i class="fas fa-chart-line"></i>
                    <h3>No Orders Found</h3>
                    <p>No orders found for the selected date range.</p>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Daily #</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Total</th>
                                <th>Payment</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $o): ?>
                            <tr>
                                <td>
                                    <span class="badge-order">#<?php echo $o['id']; ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($o['daily_number'])): ?>
                                        <span class="daily-order-badge"><?php echo $o['daily_number']; ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <i class="far fa-calendar-alt me-1 d-none d-md-inline" style="color: #d35400;"></i>
                                    <?php echo date('M d, Y', strtotime($o['order_date'])); ?>
                                </td>
                                <td>
                                    <span class="customer-name">
                                        <i class="fas fa-user me-1 d-none d-md-inline"></i>
                                        <?php echo htmlspecialchars($o['customer_name_display']); ?>
                                    </span>
                                </td>
                                <td>
                                    <strong>₱<?php echo number_format($o['total_amount'], 2); ?></strong>
                                </td>
                                <td>
                                    <?php
                                    $paymentClass = '';
                                    $paymentIcon = '';
                                    $paymentLabel = '';
                                    if ($o['payment_method'] == 'cash') {
                                        $paymentClass = 'badge-cash';
                                        $paymentIcon = 'fa-money-bill-wave';
                                        $paymentLabel = 'Cash';
                                    } elseif ($o['payment_method'] == 'gcash') {
                                        $paymentClass = 'badge-gcash';
                                        $paymentIcon = 'fa-mobile-alt';
                                        $paymentLabel = 'GCash';
                                    } else {
                                        $paymentClass = 'badge-paymaya';
                                        $paymentIcon = 'fa-mobile-alt';
                                        $paymentLabel = 'PayMaya';
                                    }
                                    ?>
                                    <span class="<?php echo $paymentClass; ?>">
                                        <i class="fas <?php echo $paymentIcon; ?> me-1"></i>
                                        <?php echo $paymentLabel; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = '';
                                    $statusIcon = '';
                                    $statusLabel = '';
                                    if ($o['status'] == 'completed') {
                                        $statusClass = 'badge-completed';
                                        $statusIcon = 'fa-check-circle';
                                        $statusLabel = 'Completed';
                                    } elseif ($o['status'] == 'pending') {
                                        $statusClass = 'badge-pending';
                                        $statusIcon = 'fa-clock';
                                        $statusLabel = 'Pending';
                                    } else {
                                        $statusClass = 'badge-cancelled';
                                        $statusIcon = 'fa-times-circle';
                                        $statusLabel = 'Cancelled';
                                    }
                                    ?>
                                    <span class="<?php echo $statusClass; ?>">
                                        <i class="fas <?php echo $statusIcon; ?> me-1"></i>
                                        <?php echo $statusLabel; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// Show loading bar
document.getElementById('loadingBar').classList.add('show');
setTimeout(() => {
    document.getElementById('loadingBar').classList.remove('show');
}, 1000);

// Scroll to top function
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Show/hide scroll button based on scroll position
window.addEventListener('scroll', function() {
    const scrollButton = document.getElementById('scrollToTop');
    if (window.scrollY > 300) {
        scrollButton.classList.add('show');
    } else {
        scrollButton.classList.remove('show');
    }
});

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.style.display = 'none';
        }, 500);
    });
}, 5000);

// Daily Sales Chart
<?php if (!empty($dailySales) && ($view == 'charts' || $view == 'both')): ?>
new Chart(document.getElementById('dailySalesChart'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_keys($dailySales)); ?>,
        datasets: [{
            label: 'Sales (₱)',
            data: <?php echo json_encode(array_values($dailySales)); ?>,
            borderColor: '#d35400',
            backgroundColor: 'rgba(211, 84, 0, 0.1)',
            borderWidth: 2,
            pointBackgroundColor: '#d35400',
            pointBorderColor: '#fff',
            pointBorderWidth: 1,
            pointRadius: 3,
            pointHoverRadius: 5,
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: '#2c3e50',
                titleColor: '#fff',
                bodyColor: '#fff',
                callbacks: {
                    label: function(context) {
                        return 'Sales: ₱' + context.raw.toFixed(2);
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                },
                ticks: {
                    callback: function(value) {
                        return '₱' + value.toFixed(0);
                    },
                    font: {
                        size: 10
                    }
                }
            },
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    maxRotation: 45,
                    minRotation: 45,
                    font: {
                        size: 9
                    }
                }
            }
        }
    }
});

// Payment Method Chart
new Chart(document.getElementById('paymentChart'), {
    type: 'doughnut',
    data: {
        labels: ['Cash', 'GCash', 'PayMaya'],
        datasets: [{
            data: [<?php echo $cashTotal; ?>, <?php echo $gcashTotal; ?>, <?php echo $paymayaTotal; ?>],
            backgroundColor: [
                '#28a745',
                '#0057e7',
                '#ff4d4d'
            ],
            borderColor: '#fff',
            borderWidth: 2,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    padding: 10,
                    font: {
                        size: 10
                    },
                    boxWidth: 10
                }
            },
            tooltip: {
                backgroundColor: '#2c3e50',
                callbacks: {
                    label: function(context) {
                        let label = context.label || '';
                        let value = context.raw || 0;
                        let total = context.dataset.data.reduce((a, b) => a + b, 0);
                        let percentage = ((value / total) * 100).toFixed(1);
                        return `${label}: ₱${value.toFixed(2)} (${percentage}%)`;
                    }
                }
            }
        },
        cutout: '65%'
    }
});

// Top Products Chart
<?php if (!empty($topProducts)): ?>
new Chart(document.getElementById('topProductsChart'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($topProducts, 'name')); ?>,
        datasets: [
            {
                label: 'Quantity Sold',
                data: <?php echo json_encode(array_column($topProducts, 'total_sold')); ?>,
                backgroundColor: '#d35400',
                borderRadius: 4,
                yAxisID: 'y',
                barPercentage: 0.7
            },
            {
                label: 'Revenue (₱)',
                data: <?php echo json_encode(array_column($topProducts, 'revenue')); ?>,
                backgroundColor: '#28a745',
                borderRadius: 4,
                yAxisID: 'y1',
                barPercentage: 0.7
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    boxWidth: 6,
                    font: {
                        size: 10
                    }
                }
            },
            tooltip: {
                backgroundColor: '#2c3e50',
                callbacks: {
                    label: function(context) {
                        let label = context.dataset.label || '';
                        let value = context.raw || 0;
                        if (context.dataset.label.includes('Revenue')) {
                            return label + ': ₱' + value.toFixed(2);
                        }
                        return label + ': ' + value;
                    }
                }
            }
        },
        scales: {
            y: {
                type: 'linear',
                display: true,
                position: 'left',
                title: {
                    display: true,
                    text: 'Quantity',
                    color: '#d35400',
                    font: {
                        size: 9
                    }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                },
                ticks: {
                    font: {
                        size: 8
                    }
                }
            },
            y1: {
                type: 'linear',
                display: true,
                position: 'right',
                title: {
                    display: true,
                    text: 'Revenue (₱)',
                    color: '#28a745',
                    font: {
                        size: 9
                    }
                },
                grid: {
                    drawOnChartArea: false
                },
                ticks: {
                    callback: function(value) {
                        return '₱' + value;
                    },
                    font: {
                        size: 8
                    }
                }
            },
            x: {
                ticks: {
                    maxRotation: 45,
                    minRotation: 45,
                    font: {
                        size: 8
                    }
                }
            }
        }
    }
});
<?php endif; ?>
<?php endif; ?>
</script>

<?php include '../../includes/footer.php'; ?>