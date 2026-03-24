<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

$from = $_GET['from'] ?? date('Y-m-d', strtotime('-30 days'));
$to = $_GET['to'] ?? date('Y-m-d');

$stmt = $pdo->prepare("SELECT o.id, o.order_date, c.name AS customer, o.total_amount, o.payment_method, o.status 
                        FROM tbl_orders o 
                        LEFT JOIN tbl_customers c ON o.customer_id = c.id 
                        WHERE DATE(o.order_date) BETWEEN ? AND ? 
                        ORDER BY o.order_date DESC");
$stmt->execute([$from, $to]);
$orders = $stmt->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sales_report_' . $from . '_to_' . $to . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
fputcsv($output, ['Order ID', 'Date', 'Customer', 'Total', 'Payment Method', 'Status']);

foreach ($orders as $row) {
    fputcsv($output, [
        $row['id'],
        $row['order_date'],
        $row['customer'] ?? 'Walk-in',
        $row['total_amount'],
        $row['payment_method'],
        $row['status']
    ]);
}
fclose($output);
exit;