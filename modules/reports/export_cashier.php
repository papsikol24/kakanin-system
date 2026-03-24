<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');
$cashier_filter = $_GET['cashier'] ?? '';
$format = $_GET['format'] ?? 'csv';

// Get cashier data
$query = "
    SELECT 
        u.id,
        u.username,
        u.status as account_status,
        u.created_at as join_date,
        (SELECT COUNT(*) FROM tbl_orders WHERE created_by = u.id AND DATE(order_date) BETWEEN ? AND ?) as orders_created,
        (SELECT COUNT(*) FROM tbl_orders WHERE completed_by = u.id AND status = 'completed' AND DATE(order_date) BETWEEN ? AND ?) as orders_completed,
        (SELECT COUNT(*) FROM tbl_orders WHERE completed_by = u.id AND status = 'cancelled' AND DATE(order_date) BETWEEN ? AND ?) as orders_cancelled,
        (SELECT COALESCE(SUM(total_amount), 0) FROM tbl_orders WHERE created_by = u.id AND DATE(order_date) BETWEEN ? AND ?) as sales_created,
        (SELECT COALESCE(SUM(total_amount), 0) FROM tbl_orders WHERE completed_by = u.id AND status = 'completed' AND DATE(order_date) BETWEEN ? AND ?) as sales_completed,
        (SELECT COUNT(*) FROM tbl_order_items oi JOIN tbl_orders o ON oi.order_id = o.id WHERE o.created_by = u.id AND DATE(o.order_date) BETWEEN ? AND ?) as items_sold,
        (SELECT COUNT(DISTINCT DATE(order_date)) FROM tbl_orders WHERE (created_by = u.id OR completed_by = u.id) AND DATE(order_date) BETWEEN ? AND ?) as days_active
    FROM tbl_users u
    WHERE u.role = 'cashier'
";

$params = [
    $from, $to,
    $from, $to,
    $from, $to,
    $from, $to,
    $from, $to,
    $from, $to,
    $from, $to
];

if (!empty($cashier_filter)) {
    $query .= " AND u.id = ?";
    $params[] = $cashier_filter;
}

$query .= " ORDER BY u.username";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$cashiers = $stmt->fetchAll();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="cashier_performance_' . $from . '_to_' . $to . '.csv"');

// Add UTF-8 BOM for Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');
fputcsv($output, [
    'Cashier ID',
    'Username',
    'Account Status',
    'Join Date',
    'Orders Created',
    'Orders Completed',
    'Orders Cancelled',
    'Total Orders',
    'Sales Created',
    'Sales Completed',
    'Total Sales',
    'Items Sold',
    'Days Active',
    'Avg Order Value'
]);

foreach ($cashiers as $c) {
    $total_orders = $c['orders_created'] + $c['orders_completed'];
    $total_sales = $c['sales_created'] + $c['sales_completed'];
    $avg_order = $total_orders > 0 ? $total_sales / $total_orders : 0;
    
    fputcsv($output, [
        $c['id'],
        $c['username'],
        $c['account_status'] ? 'Active' : 'Inactive',
        $c['join_date'],
        $c['orders_created'],
        $c['orders_completed'],
        $c['orders_cancelled'],
        $total_orders,
        number_format($c['sales_created'], 2),
        number_format($c['sales_completed'], 2),
        number_format($total_sales, 2),
        $c['items_sold'],
        $c['days_active'],
        number_format($avg_order, 2)
    ]);
}

fclose($output);
exit;
?>