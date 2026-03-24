<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

$format = $_GET['format'] ?? 'orders';

if ($format == 'summary') {
    // Export sales summary
    $from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
    $to = $_GET['to'] ?? date('Y-m-d');
    
    $stmt = $pdo->prepare("
        SELECT * FROM tbl_daily_sales_summary 
        WHERE sale_date BETWEEN ? AND ?
        ORDER BY sale_date DESC
    ");
    $stmt->execute([$from, $to]);
    $data = $stmt->fetchAll();
    
    $filename = "sales_summary_{$from}_to_{$to}.csv";
    $headers = ['Date', 'Total Orders', 'Total Sales', 'Cash Sales', 'GCash Sales', 'PayMaya Sales', 
                'Completed', 'Pending', 'Cancelled', 'Items Sold'];
    
} else {
    // Export archived orders
    $archive_date = $_GET['archive_date'] ?? '';
    $search = $_GET['archive_search'] ?? '';
    $status = $_GET['status'] ?? '';
    
    $query = "SELECT * FROM tbl_daily_orders_archive WHERE 1=1";
    $params = [];
    
    if (!empty($archive_date)) {
        $query .= " AND archive_date = ?";
        $params[] = $archive_date;
    }
    
    if (!empty($search)) {
        $query .= " AND (customer_name LIKE ? OR original_order_id LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    if (!empty($status)) {
        $query .= " AND status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY archive_date DESC, order_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    $filename = "archived_orders_" . date('Y-m-d_His') . ".csv";
    $headers = ['Archive Date', 'Original Order ID', 'Daily Number', 'Order Date', 'Customer', 
                'Total Amount', 'Payment Method', 'Status', 'Items Count'];
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Add UTF-8 BOM for Excel
echo "\xEF\xBB\xBF";

$output = fopen('php://output', 'w');
fputcsv($output, $headers);

foreach ($data as $row) {
    if ($format == 'summary') {
        fputcsv($output, [
            $row['sale_date'],
            $row['total_orders'],
            $row['total_sales'],
            $row['cash_sales'],
            $row['gcash_sales'],
            $row['paymaya_sales'],
            $row['completed_orders'],
            $row['pending_orders'],
            $row['cancelled_orders'],
            $row['total_items_sold']
        ]);
    } else {
        fputcsv($output, [
            $row['archive_date'],
            $row['original_order_id'],
            str_pad($row['daily_order_number'], 4, '0', STR_PAD_LEFT),
            $row['order_date'],
            $row['customer_name'] ?: 'Walk-in',
            $row['total_amount'],
            $row['payment_method'],
            $row['status'],
            $row['items_count']
        ]);
    }
}

fclose($output);
exit;
?>