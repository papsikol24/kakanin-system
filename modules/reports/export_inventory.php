<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

$logs = $pdo->query("SELECT l.log_time, p.name AS product, u.username AS user, l.change_type, l.quantity_changed, l.previous_stock, l.new_stock 
                     FROM tbl_inventory_logs l 
                     JOIN tbl_products p ON l.product_id = p.id 
                     LEFT JOIN tbl_users u ON l.user_id = u.id 
                     ORDER BY l.log_time DESC")->fetchAll();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="inventory_logs_' . date('Ymd_His') . '.csv"');

$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, ['Time', 'Product', 'User', 'Change Type', 'Quantity Changed', 'Previous Stock', 'New Stock']);

foreach ($logs as $row) {
    fputcsv($output, [
        $row['log_time'],
        $row['product'],
        $row['user'] ?? 'System',
        $row['change_type'],
        $row['quantity_changed'],
        $row['previous_stock'],
        $row['new_stock']
    ]);
}
fclose($output);
exit;