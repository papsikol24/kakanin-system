<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set time limit to unlimited for large backups
set_time_limit(0);
ini_set('memory_limit', '512M');

$action = $_GET['action'] ?? '';
$backup_dir = '../../backups/';

// Create backups directory if it doesn't exist with proper permissions
if (!file_exists($backup_dir)) {
    mkdir($backup_dir, 0777, true);
    chmod($backup_dir, 0777); // Ensure writable
}

// Verify backups directory is writable
if (!is_writable($backup_dir)) {
    $_SESSION['error'] = "❌ Backups directory is not writable. Please check permissions.";
    header('Location: backup.php');
    exit;
}

/**
 * Get all table names from database
 */
function getAllTables($pdo) {
    $stmt = $pdo->query("SHOW TABLES");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Get table record counts for summary
 */
function getTableCounts($pdo) {
    $tables = getAllTables($pdo);
    $counts = [];
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $counts[$table] = $count;
    }
    return $counts;
}

/**
 * Generate database-only backup - FULL DATABASE BACKUP
 * FIXED: Preserves original product names and all text data
 */
function createDatabaseOnlyBackup($pdo, $backup_dir) {
    $filename = 'database_only_' . date('Y-m-d_H-i-s') . '.sql';
    $filepath = $backup_dir . $filename;
    
    // Get all tables
    $tables = getAllTables($pdo);
    
    if (empty($tables)) {
        throw new Exception("No tables found in database!");
    }
    
    // Open file for writing with UTF-8 encoding
    $handle = fopen($filepath, 'w');
    if (!$handle) {
        throw new Exception("Cannot create backup file. Check permissions.");
    }
    
    // Add UTF-8 BOM for proper character encoding
    fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Start building backup content
    $header = "-- ===================================================\n";
    $header .= "-- KAKANIN SYSTEM - COMPLETE DATABASE BACKUP\n";
    $header .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
    $header .= "-- Database: " . DB_NAME . "\n";
    $header .= "-- Tables: " . count($tables) . "\n";
    $header .= "-- ===================================================\n\n";
    $header .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
    $header .= "SET NAMES utf8mb4;\n\n";
    
    fwrite($handle, $header);
    
    $totalRecords = 0;
    $tableData = [];
    
    foreach ($tables as $table) {
        // Get create table syntax
        $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        fwrite($handle, "\n-- -------------------------------------------------\n");
        fwrite($handle, "-- Table structure for table `$table`\n");
        fwrite($handle, "-- -------------------------------------------------\n");
        
        // Fix: Ensure the CREATE TABLE statement preserves character set
        $createTableSQL = $create['Create Table'];
        // Make sure it uses utf8mb4
        if (strpos($createTableSQL, 'utf8mb4') === false) {
            $createTableSQL = str_replace('CREATE TABLE', 'CREATE TABLE IF NOT EXISTS', $createTableSQL);
        }
        fwrite($handle, $createTableSQL . ";\n\n");
        
        // Get table data
        $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        $recordCount = count($rows);
        $totalRecords += $recordCount;
        $tableData[$table] = $recordCount;
        
        if ($recordCount > 0) {
            fwrite($handle, "-- Dumping data for table `$table` - " . $recordCount . " records\n");
            
            // Get column names
            $columns = array_keys($rows[0]);
            $columnList = "`" . implode("`, `", $columns) . "`";
            
            // Process rows in batches
            $values = [];
            foreach ($rows as $row) {
                $rowValues = array_map(function($val) use ($pdo) {
                    if ($val === null) return 'NULL';
                    // FIX: Properly quote and escape string values to preserve original text
                    return $pdo->quote($val);
                }, $row);
                $values[] = "(" . implode(', ', $rowValues) . ")";
                
                // Write in batches of 100 to avoid memory issues
                if (count($values) >= 100) {
                    fwrite($handle, "INSERT INTO `$table` ($columnList) VALUES \n" . implode(",\n", $values) . ";\n");
                    $values = [];
                }
            }
            
            // Write remaining records
            if (!empty($values)) {
                fwrite($handle, "INSERT INTO `$table` ($columnList) VALUES \n" . implode(",\n", $values) . ";\n");
            }
            fwrite($handle, "\n");
        }
    }
    
    // Re-enable foreign key checks
    fwrite($handle, "SET FOREIGN_KEY_CHECKS = 1;\n");
    
    // Add footer with statistics
    $footer = "\n-- ===================================================\n";
    $footer .= "-- BACKUP COMPLETED\n";
    $footer .= "-- Total Tables: " . count($tables) . "\n";
    $footer .= "-- Total Records: " . $totalRecords . "\n";
    $footer .= "-- ===================================================\n";
    fwrite($handle, $footer);
    
    fclose($handle);
    
    // Verify file was created
    if (!file_exists($filepath)) {
        throw new Exception("Backup file was not created.");
    }
    
    $filesize = filesize($filepath);
    if ($filesize === 0) {
        throw new Exception("Backup file is empty.");
    }
    
    return [
        'file' => $filename,
        'path' => $filepath,
        'size' => $filesize,
        'tables' => $tables,
        'counts' => $tableData,
        'total_records' => $totalRecords
    ];
}

/**
 * Generate complete system backup (database + CSV reports + images)
 */
function createFullSystemBackup($pdo, $backup_dir) {
    $start_time = microtime(true);
    
    // Create database backup
    $db_info = createDatabaseOnlyBackup($pdo, $backup_dir);
    
    // Export all reports as CSV
    $inventory_csv = exportInventoryCSV($pdo, $backup_dir);
    $sales_csv = exportSalesCSV($pdo, $backup_dir);
    $customers_csv = exportCustomersCSV($pdo, $backup_dir);
    $logs_csv = exportInventoryLogsCSV($pdo, $backup_dir);
    
    $csv_files = [
        'Inventory' => $inventory_csv,
        'Sales' => $sales_csv,
        'Customers' => $customers_csv,
        'Inventory Logs' => $logs_csv
    ];
    
    // Backup images
    $images_info = backupImages($backup_dir);
    
    // Backup uploads
    $uploads_info = backupUploads($backup_dir);
    
    // Create manifest
    $manifest = createManifest($backup_dir, $db_info, $csv_files, $images_info, $uploads_info);
    
    // Create ZIP of all backup files
    $all_files = array_merge(
        [$db_info['file']],
        array_values($csv_files),
        [basename($manifest)]
    );
    
    $zip_name = 'full_system_backup_' . date('Y-m-d_H-i-s') . '.zip';
    $zip_path = createBackupZip($backup_dir, $all_files, $zip_name);
    
    if (!$zip_path) {
        throw new Exception("Failed to create ZIP archive.");
    }
    
    $end_time = microtime(true);
    $duration = round($end_time - $start_time, 2);
    
    return [
        'zip' => $zip_name,
        'db_file' => $db_info['file'],
        'csv_files' => $csv_files,
        'images' => $images_info,
        'uploads' => $uploads_info,
        'duration' => $duration,
        'db_info' => $db_info
    ];
}

/**
 * Export inventory data as CSV
 * FIXED: Preserves original product names
 */
function exportInventoryCSV($pdo, $backup_dir) {
    $filename = 'inventory_export_' . date('Y-m-d_H-i-s') . '.csv';
    $filepath = $backup_dir . $filename;
    
    $products = $pdo->query("
        SELECT id, name, description, price, stock, low_stock_threshold, 
               DATE(created_at) as date_added, 
               (SELECT COUNT(*) FROM tbl_order_items WHERE product_id = tbl_products.id) as times_ordered
        FROM tbl_products 
        ORDER BY id
    ")->fetchAll();
    
    $fp = fopen($filepath, 'w');
    
    // Add UTF-8 BOM for Excel
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($fp, ['ID', 'Product Name', 'Description', 'Price', 'Current Stock', 
                   'Low Stock Threshold', 'Date Added', 'Times Ordered', 'Total Revenue']);
    
    foreach ($products as $p) {
        $revenue = $pdo->prepare("SELECT SUM(quantity * price) FROM tbl_order_items WHERE product_id = ?");
        $revenue->execute([$p['id']]);
        $total_revenue = $revenue->fetchColumn() ?: 0;
        
        // FIX: Ensure UTF-8 encoding for special characters
        $name = mb_convert_encoding($p['name'], 'UTF-8', 'auto');
        $description = mb_convert_encoding($p['description'] ?? '', 'UTF-8', 'auto');
        
        fputcsv($fp, [
            $p['id'],
            $name,
            $description,
            $p['price'],
            $p['stock'],
            $p['low_stock_threshold'],
            $p['date_added'],
            $p['times_ordered'],
            $total_revenue
        ]);
    }
    
    fclose($fp);
    
    return $filename;
}

/**
 * Export sales report as CSV
 */
function exportSalesCSV($pdo, $backup_dir) {
    $filename = 'sales_report_' . date('Y-m-d_H-i-s') . '.csv';
    $filepath = $backup_dir . $filename;
    
    $sales = $pdo->query("
        SELECT o.id, o.order_date, 
               COALESCE(o.customer_name, 'Walk-in') as customer, 
               o.total_amount, o.payment_method, o.status, 
               o.delivery_address,
               COUNT(oi.id) as total_items
        FROM tbl_orders o
        LEFT JOIN tbl_order_items oi ON o.id = oi.order_id
        GROUP BY o.id
        ORDER BY o.order_date DESC
    ")->fetchAll();
    
    $fp = fopen($filepath, 'w');
    
    // Add UTF-8 BOM for Excel
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($fp, ['Order ID', 'Date', 'Customer', 'Total Amount', 'Payment Method', 
                   'Status', 'Delivery Address', 'Number of Items']);
    
    foreach ($sales as $s) {
        // FIX: Ensure UTF-8 encoding
        $customer = mb_convert_encoding($s['customer'], 'UTF-8', 'auto');
        $address = mb_convert_encoding($s['delivery_address'] ?? '', 'UTF-8', 'auto');
        
        fputcsv($fp, [
            $s['id'],
            $s['order_date'],
            $customer,
            $s['total_amount'],
            $s['payment_method'],
            $s['status'],
            $address,
            $s['total_items']
        ]);
    }
    
    fclose($fp);
    
    return $filename;
}

/**
 * Export customers data as CSV
 */
function exportCustomersCSV($pdo, $backup_dir) {
    $filename = 'customers_' . date('Y-m-d_H-i-s') . '.csv';
    $filepath = $backup_dir . $filename;
    
    $customers = $pdo->query("
        SELECT c.id, c.name, c.email, c.username, c.phone, c.status,
               DATE(c.created_at) as registered_date,
               COUNT(o.id) as total_orders,
               COALESCE(SUM(o.total_amount), 0) as total_spent
        FROM tbl_customers c
        LEFT JOIN tbl_orders o ON c.id = o.customer_id
        GROUP BY c.id
        ORDER BY c.name
    ")->fetchAll();
    
    $fp = fopen($filepath, 'w');
    
    // Add UTF-8 BOM for Excel
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($fp, ['ID', 'Name', 'Email', 'Username', 'Phone', 'Status', 
                   'Registered Date', 'Total Orders', 'Total Spent']);
    
    foreach ($customers as $c) {
        // FIX: Ensure UTF-8 encoding
        $name = mb_convert_encoding($c['name'], 'UTF-8', 'auto');
        $email = mb_convert_encoding($c['email'] ?? '', 'UTF-8', 'auto');
        $username = mb_convert_encoding($c['username'] ?? '', 'UTF-8', 'auto');
        
        fputcsv($fp, [
            $c['id'],
            $name,
            $email,
            $username,
            $c['phone'],
            $c['status'] ? 'Active' : 'Inactive',
            $c['registered_date'],
            $c['total_orders'],
            $c['total_spent']
        ]);
    }
    
    fclose($fp);
    
    return $filename;
}

/**
 * Export inventory logs as CSV
 */
function exportInventoryLogsCSV($pdo, $backup_dir) {
    $filename = 'inventory_logs_' . date('Y-m-d_H-i-s') . '.csv';
    $filepath = $backup_dir . $filename;
    
    $logs = $pdo->query("
        SELECT l.log_time, p.name as product, COALESCE(u.username, 'System') as user,
               l.change_type, l.quantity_changed, l.previous_stock, l.new_stock
        FROM tbl_inventory_logs l
        JOIN tbl_products p ON l.product_id = p.id
        LEFT JOIN tbl_users u ON l.user_id = u.id
        ORDER BY l.log_time DESC
    ")->fetchAll();
    
    $fp = fopen($filepath, 'w');
    
    // Add UTF-8 BOM for Excel
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($fp, ['Date/Time', 'Product', 'User', 'Change Type', 'Quantity Changed', 
                   'Previous Stock', 'New Stock']);
    
    foreach ($logs as $log) {
        // FIX: Ensure UTF-8 encoding for product names
        $product = mb_convert_encoding($log['product'], 'UTF-8', 'auto');
        
        fputcsv($fp, [
            $log['log_time'],
            $product,
            $log['user'],
            $log['change_type'],
            $log['quantity_changed'],
            $log['previous_stock'],
            $log['new_stock']
        ]);
    }
    
    fclose($fp);
    
    return $filename;
}

/**
 * Backup product images
 */
function backupImages($backup_dir) {
    $images_dir = '../../assets/images/';
    $backup_images_dir = $backup_dir . 'images_' . date('Y-m-d_H-i-s') . '/';
    
    if (!file_exists($images_dir)) {
        return null;
    }
    
    if (!is_dir($images_dir)) {
        return null;
    }
    
    mkdir($backup_images_dir, 0777, true);
    $files = scandir($images_dir);
    $count = 0;
    $total_size = 0;
    
    foreach ($files as $file) {
        if ($file != '.' && $file != '..' && is_file($images_dir . $file)) {
            $src = $images_dir . $file;
            $dst = $backup_images_dir . $file;
            if (copy($src, $dst)) {
                $count++;
                $total_size += filesize($src);
            }
        }
    }
    
    return [
        'path' => $backup_images_dir,
        'count' => $count,
        'size' => $total_size
    ];
}

/**
 * Backup uploads (screenshots)
 */
function backupUploads($backup_dir) {
    $uploads_dir = '../../assets/uploads/';
    $backup_uploads_dir = $backup_dir . 'uploads_' . date('Y-m-d_H-i-s') . '/';
    
    if (!file_exists($uploads_dir)) {
        return null;
    }
    
    // Recursive copy function
    function copyDir($src, $dst) {
        $dir = opendir($src);
        if (!file_exists($dst)) {
            mkdir($dst, 0777, true);
        }
        
        $count = 0;
        $size = 0;
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $src_file = $src . '/' . $file;
                $dst_file = $dst . '/' . $file;
                
                if (is_dir($src_file)) {
                    $result = copyDir($src_file, $dst_file);
                    $count += $result['count'];
                    $size += $result['size'];
                } else {
                    if (copy($src_file, $dst_file)) {
                        $count++;
                        $size += filesize($src_file);
                    }
                }
            }
        }
        closedir($dir);
        return ['count' => $count, 'size' => $size];
    }
    
    if (is_dir($uploads_dir)) {
        $result = copyDir($uploads_dir, $backup_uploads_dir);
        
        return [
            'path' => $backup_uploads_dir,
            'count' => $result['count'],
            'size' => $result['size']
        ];
    }
    
    return null;
}

/**
 * Create a manifest file describing the complete backup
 */
function createManifest($backup_dir, $db_info, $csv_files, $images_info, $uploads_info) {
    $manifest_file = $backup_dir . 'manifest_' . date('Y-m-d_H-i-s') . '.txt';
    
    $content = "========================================================\n";
    $content .= "      KAKANIN SYSTEM - COMPLETE BACKUP MANIFEST\n";
    $content .= "========================================================\n\n";
    $content .= "Backup Date: " . date('Y-m-d H:i:s') . "\n";
    $content .= "Generated by: " . ($_SESSION['username'] ?? 'Admin') . "\n\n";
    
    $content .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $content .= "DATABASE BACKUP\n";
    $content .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $content .= "File: " . $db_info['file'] . "\n";
    $content .= "Size: " . round($db_info['size'] / 1024 / 1024, 2) . " MB\n";
    $content .= "Tables: " . count($db_info['tables']) . "\n";
    $content .= "Total Records: " . number_format($db_info['total_records']) . "\n\n";
    
    $content .= "Table Records:\n";
    foreach ($db_info['counts'] as $table => $count) {
        $content .= "  • $table: " . number_format($count) . " records\n";
    }
    
    $content .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $content .= "EXPORTED REPORTS (CSV)\n";
    $content .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    foreach ($csv_files as $name => $file) {
        $filepath = $backup_dir . $file;
        if (file_exists($filepath)) {
            $size = filesize($filepath);
            $content .= "• " . str_pad($name, 20) . ": " . $file . " (" . round($size / 1024, 2) . " KB)\n";
        }
    }
    
    if ($images_info && $images_info['count'] > 0) {
        $content .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $content .= "PRODUCT IMAGES\n";
        $content .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $content .= "Folder: " . basename($images_info['path']) . "\n";
        $content .= "Images: " . $images_info['count'] . " files\n";
        $content .= "Size: " . round($images_info['size'] / 1024 / 1024, 2) . " MB\n";
    }
    
    if ($uploads_info && $uploads_info['count'] > 0) {
        $content .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $content .= "UPLOADS (Screenshots)\n";
        $content .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $content .= "Folder: " . basename($uploads_info['path']) . "\n";
        $content .= "Files: " . $uploads_info['count'] . " files\n";
        $content .= "Size: " . round($uploads_info['size'] / 1024 / 1024, 2) . " MB\n";
    }
    
    $content .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $content .= "BACKUP SUMMARY\n";
    $content .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    $total_files = 1 + count($csv_files);
    if ($images_info && $images_info['count'] > 0) $total_files++;
    if ($uploads_info && $uploads_info['count'] > 0) $total_files++;
    
    $content .= "Total backup files: " . $total_files . "\n";
    $content .= "Backup location: " . realpath($backup_dir) . "\n";
    $content .= "========================================================\n";
    
    file_put_contents($manifest_file, $content);
    return $manifest_file;
}

/**
 * Create a ZIP archive of all backup files
 */
function createBackupZip($backup_dir, $files, $zip_name) {
    $zip = new ZipArchive();
    $zip_path = $backup_dir . $zip_name;
    
    if ($zip->open($zip_path, ZipArchive::CREATE) !== TRUE) {
        return false;
    }
    
    $added = 0;
    foreach ($files as $file) {
        $file_path = $backup_dir . $file;
        if (file_exists($file_path)) {
            $zip->addFile($file_path, $file);
            $added++;
        }
    }
    
    $zip->close();
    
    if ($added === 0 || !file_exists($zip_path)) {
        return false;
    }
    
    return $zip_path;
}

// ===== HANDLE BACKUP ACTIONS =====

// Handle database-only backup
if ($action == 'create_database') {
    try {
        // Verify database connection
        $test = $pdo->query("SELECT 1");
        if (!$test) {
            throw new Exception("Database connection failed.");
        }
        
        // Get table count before backup
        $tables_before = getAllTables($pdo);
        $table_count = count($tables_before);
        
        if ($table_count === 0) {
            throw new Exception("No tables found in database!");
        }
        
        // Create backup
        $db_info = createDatabaseOnlyBackup($pdo, $backup_dir);
        
        // Verify backup file
        if (!file_exists($backup_dir . $db_info['file'])) {
            throw new Exception("Backup file was not created.");
        }
        
        $filesize = filesize($backup_dir . $db_info['file']);
        if ($filesize === 0) {
            throw new Exception("Backup file is empty.");
        }
        
        // Build success message
        $message = "✅ **FULL DATABASE BACKUP CREATED SUCCESSFULLY!**\n\n";
        $message .= "📁 **Backup File:**\n";
        $message .= "   • " . $db_info['file'] . "\n";
        $message .= "   • Size: " . round($filesize / 1024 / 1024, 2) . " MB\n\n";
        
        $message .= "📊 **Database Statistics:**\n";
        $message .= "   • Total Tables: " . count($db_info['tables']) . "\n";
        $message .= "   • Total Records: " . number_format($db_info['total_records']) . "\n\n";
        
        $message .= "📋 **Tables Backed Up:**\n";
        foreach ($db_info['counts'] as $table => $count) {
            $message .= "   • $table: " . number_format($count) . " records\n";
        }
        
        $_SESSION['success'] = $message;
        
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ Backup failed: " . $e->getMessage();
    }
    
    header('Location: backup.php');
    exit;
}

// Handle full system backup
if ($action == 'create_full') {
    try {
        $result = createFullSystemBackup($pdo, $backup_dir);
        
        $message = "✅ **FULL SYSTEM BACKUP CREATED SUCCESSFULLY!**\n\n";
        $message .= "📁 **Database Backup:**\n";
        $message .= "   • File: " . $result['db_file'] . "\n";
        $message .= "   • Size: " . round($result['db_info']['size'] / 1024 / 1024, 2) . " MB\n";
        $message .= "   • Tables: " . count($result['db_info']['tables']) . "\n";
        $message .= "   • Records: " . number_format($result['db_info']['total_records']) . "\n\n";
        
        $message .= "📊 **Exported Reports:**\n";
        foreach ($result['csv_files'] as $name => $file) {
            $filepath = $backup_dir . $file;
            if (file_exists($filepath)) {
                $size = filesize($filepath);
                $message .= "   • " . $name . ": " . $file . " (" . round($size / 1024, 2) . " KB)\n";
            }
        }
        $message .= "\n";
        
        if ($result['images'] && $result['images']['count'] > 0) {
            $message .= "🖼️ **Product Images:**\n";
            $message .= "   • Folder: " . basename($result['images']['path']) . "\n";
            $message .= "   • Files: " . $result['images']['count'] . " images\n";
            $message .= "   • Size: " . round($result['images']['size'] / 1024 / 1024, 2) . " MB\n\n";
        }
        
        if ($result['uploads'] && $result['uploads']['count'] > 0) {
            $message .= "📎 **Uploads (Screenshots):**\n";
            $message .= "   • Folder: " . basename($result['uploads']['path']) . "\n";
            $message .= "   • Files: " . $result['uploads']['count'] . " files\n";
            $message .= "   • Size: " . round($result['uploads']['size'] / 1024 / 1024, 2) . " MB\n\n";
        }
        
        $message .= "📦 **ZIP Archive:** " . $result['zip'] . "\n";
        $message .= "⏱️ **Backup completed in:** " . $result['duration'] . " seconds\n";
        
        $_SESSION['success'] = $message;
        
    } catch (Exception $e) {
        $_SESSION['error'] = "❌ Backup failed: " . $e->getMessage();
    }
    
    header('Location: backup.php');
    exit;
}

// Handle backup download
if ($action == 'download' && isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $filepath = $backup_dir . $file;
    
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        
        // Clear output buffer
        ob_clean();
        flush();
        
        readfile($filepath);
        exit;
    } else {
        $_SESSION['error'] = "❌ File not found: " . $file;
        header('Location: backup.php');
        exit;
    }
}

// Handle backup restore
if ($action == 'restore' && isset($_POST['restore_file'])) {
    $file = basename($_POST['restore_file']);
    $filepath = $backup_dir . $file;
    
    if (file_exists($filepath) && pathinfo($filepath, PATHINFO_EXTENSION) == 'sql') {
        // Read SQL file
        $sql = file_get_contents($filepath);
        
        if (empty($sql)) {
            $_SESSION['error'] = "❌ Backup file is empty or corrupt.";
            header('Location: backup.php');
            exit;
        }
        
        $pdo->beginTransaction();
        try {
            // Disable foreign key checks
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
            $pdo->exec("SET NAMES utf8mb4");
            
            // Get all current tables
            $tables = getAllTables($pdo);
            
            // Drop all existing tables
            foreach ($tables as $table) {
                $pdo->exec("DROP TABLE IF EXISTS `$table`");
            }
            
            // Execute the SQL
            $pdo->exec($sql);
            
            // Re-enable foreign key checks
            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
            $pdo->commit();
            
            // Verify restore
            $tables_after = getAllTables($pdo);
            $message = "✅ **Database restored successfully from:** " . $file . "\n\n";
            $message .= "📊 **Tables restored:** " . count($tables_after);
            
            $_SESSION['success'] = $message;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "❌ Restore failed: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "❌ Invalid backup file or file not found.";
    }
    
    header('Location: backup.php');
    exit;
}

// Handle backup deletion
if ($action == 'delete' && isset($_GET['file'])) {
    $file = basename($_GET['file']);
    $filepath = $backup_dir . $file;
    
    if (file_exists($filepath)) {
        if (unlink($filepath)) {
            $_SESSION['success'] = "✅ Backup deleted successfully: " . $file;
        } else {
            $_SESSION['error'] = "❌ Failed to delete file. Check permissions.";
        }
    } else {
        $_SESSION['error'] = "❌ File not found: " . $file;
    }
    
    header('Location: backup.php');
    exit;
}

// Get list of backups
$backups = [];
if (file_exists($backup_dir)) {
    $files = scandir($backup_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filepath = $backup_dir . $file;
            if (is_file($filepath)) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if ($ext == 'sql' || $ext == 'zip' || $ext == 'csv' || $ext == 'txt') {
                    $type = 'Unknown';
                    if ($ext == 'sql') {
                        if (strpos($file, 'database_only_') === 0) {
                            $type = 'Database Only';
                        } elseif (strpos($file, 'full_backup_') === 0) {
                            $type = 'Full Database';
                        } else {
                            $type = 'SQL Backup';
                        }
                    } elseif ($ext == 'zip') {
                        $type = 'Full System';
                    } elseif ($ext == 'csv') {
                        if (strpos($file, 'inventory_') === 0) $type = 'Inventory CSV';
                        elseif (strpos($file, 'sales_') === 0) $type = 'Sales CSV';
                        elseif (strpos($file, 'customers_') === 0) $type = 'Customers CSV';
                        elseif (strpos($file, 'inventory_logs_') === 0) $type = 'Logs CSV';
                        else $type = 'CSV Report';
                    } elseif ($ext == 'txt') {
                        $type = 'Manifest';
                    }
                    
                    $backups[] = [
                        'name' => $file,
                        'size' => filesize($filepath),
                        'date' => date('Y-m-d H:i:s', filemtime($filepath)),
                        'type' => $type,
                        'ext' => $ext
                    ];
                }
            }
        }
    }
    // Sort by date (newest first)
    usort($backups, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

include '../../includes/header.php';
?>

<style>
    /* ===== MOBILE-FRIENDLY STYLES ===== */
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
        padding-right: 10px;
        padding-left: 10px;
        margin-right: auto;
        margin-left: auto;
    }

    @media (min-width: 768px) {
        .container-fluid {
            padding-right: 15px;
            padding-left: 15px;
            max-width: 1200px;
        }
    }

    /* ===== SECTION HEADER ===== */
    .section-header {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e0e0;
    }

    .section-header h4 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #333;
        margin: 0;
    }

    .section-header h4 i {
        color: #007bff;
        margin-right: 8px;
    }

    @media (min-width: 768px) {
        .section-header h4 {
            font-size: 1.5rem;
        }
    }

    /* ===== BACKUP OPTIONS CARDS ===== */
    .backup-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }

    @media (min-width: 768px) {
        .backup-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .backup-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        overflow: hidden;
        transition: all 0.3s;
        border: 1px solid rgba(0,0,0,0.05);
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .backup-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
    }

    .backup-card.primary {
        border-top: 4px solid #007bff;
    }

    .backup-card.success {
        border-top: 4px solid #28a745;
    }

    .backup-card-header {
        padding: 1.5rem;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
    }

    .backup-card-header.primary {
        background: linear-gradient(135deg, #007bff, #0069d9);
    }

    .backup-card-header.success {
        background: linear-gradient(135deg, #28a745, #218838);
    }

    .backup-card-body {
        padding: 1.5rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    @media (max-width: 767px) {
        .backup-card-header {
            padding: 1.2rem;
            font-size: 1rem;
        }

        .backup-card-body {
            padding: 1.2rem;
        }
    }

    .backup-icon {
        font-size: 2.5rem;
        text-align: center;
        margin-bottom: 1rem;
    }

    .backup-icon.primary {
        color: #007bff;
    }

    .backup-icon.success {
        color: #28a745;
    }

    .backup-features {
        list-style: none;
        padding: 0;
        margin: 0 0 1.5rem 0;
        flex: 1;
    }

    .backup-features li {
        padding: 0.6rem 0;
        border-bottom: 1px solid #eee;
        font-size: 0.85rem;
        color: #555;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .backup-features li:last-child {
        border-bottom: none;
    }

    .backup-features li i {
        color: #28a745;
        font-size: 0.9rem;
    }

    @media (max-width: 767px) {
        .backup-features li {
            font-size: 0.8rem;
            padding: 0.5rem 0;
        }
    }

    .backup-btn {
        display: block;
        width: 100%;
        padding: 0.8rem;
        border: none;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.9rem;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
        margin-top: auto;
    }

    .backup-btn.primary {
        background: linear-gradient(135deg, #007bff, #0069d9);
        color: white;
    }

    .backup-btn.success {
        background: linear-gradient(135deg, #28a745, #218838);
        color: white;
    }

    .backup-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    }

    .backup-btn:active {
        transform: scale(0.98);
    }

    .backup-footer {
        padding: 0.8rem 1rem;
        background: #f8f9fa;
        font-size: 0.8rem;
        color: #666;
        border-top: 1px solid #eee;
    }

    /* ===== BACK TO TOOLS BUTTON ===== */
    .back-to-tools {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.7rem 1.2rem;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s;
        margin-bottom: 20px;
    }

    .back-to-tools:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(108,117,125,0.3);
        color: white;
    }

    .back-to-tools:active {
        transform: scale(0.98);
    }

    .back-to-tools i {
        transition: transform 0.2s;
    }

    .back-to-tools:active i {
        transform: translateX(-3px);
    }

    /* ===== SCROLLABLE BACKUPS TABLE ===== */
    .backups-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        margin-top: 20px;
        overflow: hidden;
    }

    .backups-header {
        background: linear-gradient(135deg, #007bff, #0069d9);
        color: white;
        padding: 1rem 1.2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .backups-header h5 {
        margin: 0;
        font-size: 1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .backups-header .badge {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-size: 0.8rem;
    }

    .backups-body {
        padding: 1.2rem;
    }

    /* ===== SCROLLABLE TABLE CONTAINER ===== */
    .table-container {
        background: white;
        border-radius: 15px;
        border: 1px solid #e0e0e0;
        overflow: hidden;
        position: relative;
    }

    /* Table Header */
    .table-header {
        background: linear-gradient(135deg, #007bff, #0069d9);
        color: white;
        padding: 12px 15px;
        font-weight: 600;
        font-size: 0.95rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .table-header i {
        margin-right: 8px;
    }

    .table-header .badge {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 0.75rem;
    }

    /* Scrollable table wrapper */
    .table-scroll {
        overflow-x: auto;
        overflow-y: auto;
        max-height: 400px;
        -webkit-overflow-scrolling: touch;
    }

    /* Custom scrollbar styling */
    .table-scroll::-webkit-scrollbar {
        width: 10px;
        height: 10px;
    }

    .table-scroll::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 10px;
    }

    .table-scroll::-webkit-scrollbar-thumb {
        background: #007bff;
        border-radius: 10px;
        border: 2px solid #f1f1f1;
    }

    .table-scroll::-webkit-scrollbar-thumb:hover {
        background: #0056b3;
    }

    .table-scroll::-webkit-scrollbar-corner {
        background: #f1f1f1;
    }

    /* Table styling */
    .table {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
        font-size: 0.8rem;
    }

    @media (min-width: 768px) {
        .table {
            font-size: 0.9rem;
            min-width: 1000px;
        }
    }

    /* Fixed header that stays on top when scrolling */
    .table thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table thead th {
        background: #f8f9fa;
        color: #333;
        font-weight: 600;
        border-bottom: 2px solid #007bff;
        padding: 12px 10px;
        white-space: nowrap;
        font-size: 0.8rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .table tbody td {
        padding: 10px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
    }

    .table tbody tr:active {
        background: #e9ecef;
    }

    /* Badge types */
    .badge-type {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-type.success {
        background: #28a745;
        color: white;
    }

    .badge-type.info {
        background: #17a2b8;
        color: white;
    }

    .badge-type.warning {
        background: #ffc107;
        color: #333;
    }

    .badge-type.primary {
        background: #007bff;
        color: white;
    }

    .badge-type.secondary {
        background: #6c757d;
        color: white;
    }

    /* Action buttons */
    .btn-action-group {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        color: white;
        text-decoration: none;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
        -webkit-tap-highlight-color: transparent;
    }

    .btn-action.info {
        background: #17a2b8;
    }

    .btn-action.success {
        background: #28a745;
    }

    .btn-action.danger {
        background: #dc3545;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .btn-action:active {
        transform: scale(0.95);
    }

    .btn-action i {
        font-size: 1rem;
    }

    @media (max-width: 767px) {
        .btn-action {
            width: 40px;
            height: 40px;
        }
    }

    /* Table info bar */
    .table-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        background: #f8f9fa;
        border-top: 1px solid #e0e0e0;
        font-size: 0.8rem;
        color: #666;
    }

    .table-info i {
        margin-right: 5px;
        color: #007bff;
    }

    /* Empty state */
    .empty-state {
        text-align: center;
        padding: 3rem 1rem;
        color: #999;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 1rem;
        color: #ddd;
    }

    .empty-state p {
        margin-bottom: 0.3rem;
    }

    .empty-state small {
        color: #ccc;
    }

    /* ===== SCROLL TO TOP BUTTON ===== */
    .scroll-to-top {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #007bff, #0069d9);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 5px 15px rgba(0,123,255,0.3);
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

    .scroll-to-top:active {
        transform: scale(0.95);
    }

    /* ===== INFO CARD ===== */
    .info-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: 1px solid #ffc107;
        overflow: hidden;
        margin-top: 30px;
    }

    .info-header {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #333;
        padding: 1rem 1.2rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-body {
        padding: 1.2rem;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 768px) {
        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .info-column h6 {
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.8rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .info-column ol {
        padding-left: 1.2rem;
        margin-bottom: 0;
    }

    .info-column li {
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        color: #555;
    }

    @media (max-width: 767px) {
        .info-column li {
            font-size: 0.8rem;
        }
    }

    /* ===== RESTORE MODAL ===== */
    .modal-content {
        border-radius: 20px;
        border: none;
        overflow: hidden;
    }

    .modal-header {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #333;
        padding: 1rem 1.2rem;
        border: none;
    }

    .modal-header .btn-close {
        filter: invert(1);
    }

    .modal-title {
        font-size: 1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modal-body {
        padding: 1.2rem;
        font-size: 0.9rem;
    }

    .modal-footer {
        padding: 1rem 1.2rem;
        border-top: 1px solid #eee;
    }

    .form-check-input:checked {
        background-color: #ffc107;
        border-color: #ffc107;
    }

    .btn-modal {
        border-radius: 50px;
        padding: 0.5rem 1.2rem;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-modal:active {
        transform: scale(0.95);
    }

    /* ===== ALERTS ===== */
    .alert {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
        white-space: pre-line;
        display: flex;
        align-items: flex-start;
        gap: 10px;
        animation: slideDown 0.3s ease;
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

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert i {
        font-size: 1.2rem;
    }

    /* ===== LOADING STATES ===== */
    .backup-btn.loading {
        position: relative;
        color: transparent !important;
        pointer-events: none;
    }

    .backup-btn.loading::after {
        content: '';
        position: absolute;
        width: 18px;
        height: 18px;
        top: 50%;
        left: 50%;
        margin-left: -9px;
        margin-top: -9px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* ===== TOUCH OPTIMIZATIONS ===== */
    .backup-btn, .btn-action, .back-to-tools, .btn-modal, .scroll-to-top {
        -webkit-tap-highlight-color: transparent;
    }

    /* ===== STATS ROW ===== */
    .stats-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
        font-size: 0.8rem;
        color: #666;
    }

    /* ===== BACKUP NOTE ===== */
    .backup-note {
        background: #e7f3ff;
        border-left: 4px solid #007bff;
        padding: 1rem;
        border-radius: 10px;
        margin: 1rem 0;
        font-size: 0.9rem;
    }

    .backup-note i {
        color: #007bff;
        margin-right: 8px;
    }
</style>

<div class="container-fluid">
    <!-- Section Header -->
    <div class="section-header">
        <h4><i class="fas fa-database me-2"></i>Complete System Backup & Restore</h4>
    </div>

    <!-- Backup Note - Important Information -->
    <div class="backup-note">
        <i class="fas fa-info-circle"></i>
        <strong>Important:</strong> Database backups (.sql files) preserve ALL product names, descriptions, and text data exactly as entered. 
        CSV files are for reporting only and should not be used for restoration.
    </div>

    <!-- Back to Tools Button -->
    <a href="index.php" class="back-to-tools">
        <i class="fas fa-arrow-left me-2"></i>Back to Tools
    </a>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="white-space: pre-line;">
            <i class="fas fa-check-circle me-2"></i><?php echo nl2br($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Backup Options Cards -->
    <div class="backup-grid">
        <!-- Database Only Backup -->
        <div class="backup-card primary">
            <div class="backup-card-header primary">
                <i class="fas fa-database me-2"></i> Full Database Backup
            </div>
            <div class="backup-card-body">
                <div class="backup-icon primary">
                    <i class="fas fa-database"></i>
                </div>
                <h6 class="text-center mb-3">✅ Creates COMPLETE database backup</h6>
                <ul class="backup-features">
                    <li><i class="fas fa-check-circle"></i> All database tables structure</li>
                    <li><i class="fas fa-check-circle"></i> All records from every table</li>
                    <li><i class="fas fa-check-circle"></i> Preserves ALL product names exactly as entered</li>
                    <li><i class="fas fa-check-circle"></i> Customers, orders, products</li>
                    <li><i class="fas fa-check-circle"></i> Inventory logs, notifications</li>
                    <li><i class="fas fa-check-circle"></i> Staff accounts, carts</li>
                </ul>
                <a href="?action=create_database" class="backup-btn primary" 
                   onclick="return confirm('Create FULL DATABASE backup?\n\nThis will backup ALL tables and ALL records in the database.\n\n✅ Product names will be preserved exactly as entered.')">
                    <i class="fas fa-database me-2"></i>Create Database Backup
                </a>
            </div>
            <div class="backup-footer">
                <i class="fas fa-clock me-1"></i> Complete .sql file with all tables and data
            </div>
        </div>

        <!-- Full System Backup -->
        <div class="backup-card success">
            <div class="backup-card-header success">
                <i class="fas fa-boxes me-2"></i> Full System Backup
            </div>
            <div class="backup-card-body">
                <div class="backup-icon success">
                    <i class="fas fa-boxes"></i>
                </div>
                <h6 class="text-center mb-3">✅ Creates COMPLETE system backup</h6>
                <ul class="backup-features">
                    <li><i class="fas fa-check-circle"></i> Complete database (all tables)</li>
                    <li><i class="fas fa-check-circle"></i> Inventory Report (CSV)</li>
                    <li><i class="fas fa-check-circle"></i> Sales Report (CSV)</li>
                    <li><i class="fas fa-check-circle"></i> Customer List (CSV)</li>
                    <li><i class="fas fa-check-circle"></i> All product images</li>
                </ul>
                <a href="?action=create_full" class="backup-btn success" 
                   onclick="return confirm('Create FULL SYSTEM BACKUP?\n\nThis will backup:\n✅ Complete database\n✅ All CSV reports\n✅ All images & uploads\n\nThis may take a few minutes.')">
                    <i class="fas fa-boxes me-2"></i>Create Full System Backup
                </a>
            </div>
            <div class="backup-footer">
                <i class="fas fa-clock me-1"></i> ZIP file with database, CSVs, images
            </div>
        </div>
    </div>

    <!-- Backups List with Scrollable Table -->
    <div class="backups-card">
        <div class="backups-header">
            <h5>
                <i class="fas fa-history me-2"></i>Available Backups
            </h5>
            <span class="badge">📁 Location: /backups/</span>
        </div>
        <div class="backups-body">
            <?php if (empty($backups)): ?>
                <div class="empty-state">
                    <i class="fas fa-database"></i>
                    <p>No backups found in the backups folder.</p>
                    <small>Click one of the buttons above to create your first backup.</small>
                </div>
            <?php else: ?>
                <div class="table-container">
                    <div class="table-header">
                        <div><i class="fas fa-history"></i> Backup Files</div>
                        <span class="badge"><?php echo count($backups); ?> files</span>
                    </div>
                    <div class="table-scroll">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Backup File</th>
                                    <th>Type</th>
                                    <th>Date Created</th>
                                    <th>Size</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($backups as $index => $backup): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><code><?php echo $backup['name']; ?></code></td>
                                    <td>
                                        <?php
                                        $badge_color = 'secondary';
                                        if ($backup['type'] == 'Database Only' || $backup['type'] == 'Full Database') {
                                            $badge_color = 'info';
                                        } elseif ($backup['type'] == 'Full System') {
                                            $badge_color = 'success';
                                        } elseif ($backup['type'] == 'Manifest') {
                                            $badge_color = 'warning';
                                        } elseif (strpos($backup['type'], 'CSV') !== false) {
                                            $badge_color = 'primary';
                                        }
                                        ?>
                                        <span class="badge-type <?php echo $badge_color; ?>"><?php echo $backup['type']; ?></span>
                                    </td>
                                    <td><?php echo $backup['date']; ?></td>
                                    <td><?php echo round($backup['size'] / 1024 / 1024, 2); ?> MB</td>
                                    <td>
                                        <div class="btn-action-group">
                                            <a href="?action=download&file=<?php echo urlencode($backup['name']); ?>" class="btn-action info" title="Download">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <?php if ($backup['ext'] == 'sql'): ?>
                                            <button type="button" class="btn-action success" title="Restore" 
                                                    onclick="confirmRestore('<?php echo $backup['name']; ?>')">
                                                <i class="fas fa-undo-alt"></i>
                                            </button>
                                            <?php endif; ?>
                                            <a href="?action=delete&file=<?php echo urlencode($backup['name']); ?>" 
                                               class="btn-action danger" 
                                               title="Delete Permanently"
                                               onclick="return confirm('⚠️ PERMANENTLY DELETE this backup?\n\nFile: <?php echo $backup['name']; ?>\nSize: <?php echo round($backup['size'] / 1024 / 1024, 2); ?> MB\n\nThis will be removed from the server FOREVER.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-info">
                        <span><i class="fas fa-clock"></i> Last updated: <?php echo date('M d, Y H:i:s'); ?></span>
                        <span><i class="fas fa-database"></i> Total: <?php echo count($backups); ?> files</span>
                    </div>
                </div>
                <div class="stats-row">
                    <span><i class="fas fa-info-circle me-1"></i> Total backups: <?php echo count($backups); ?></span>
                    <span><i class="fas fa-hdd me-1"></i> Total size: <?php 
                        $total_size = array_sum(array_column($backups, 'size'));
                        echo round($total_size / 1024 / 1024, 2) . ' MB';
                    ?></span>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- How to Move to Another Computer -->
    <div class="info-card">
        <div class="info-header">
            <i class="fas fa-exchange-alt"></i> How to Move to Another Computer
        </div>
        <div class="info-body">
            <div class="info-grid">
                <div class="info-column">
                    <h6><i class="fas fa-database me-2" style="color: #007bff;"></i> Database Only Backup:</h6>
                    <ol>
                        <li>Create "Full Database Backup"</li>
                        <li>Download the .sql file using 📥 button</li>
                        <li>Copy to new computer</li>
                        <li>Import to new computer via phpMyAdmin</li>
                        <li>All tables and data will be restored</li>
                        <li><strong>✅ Product names remain exactly as entered</strong></li>
                    </ol>
                </div>
                <div class="info-column">
                    <h6><i class="fas fa-boxes me-2" style="color: #28a745;"></i> Full System Backup:</h6>
                    <ol>
                        <li>Create "Full System Backup"</li>
                        <li>Download the .zip file using 📥 button</li>
                        <li>Copy to new computer</li>
                        <li>Extract the zip file</li>
                        <li>Import .sql file to database</li>
                        <li>Copy images folders to assets/</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>⚠️ Confirm Database Restore
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to restore this database backup?</strong></p>
                <p class="text-danger">
                    <i class="fas fa-info-circle me-2"></i>
                    This will REPLACE all current database data with the backup data.
                </p>
                <p><strong>This includes:</strong></p>
                <ul>
                    <li>✅ All customers</li>
                    <li>✅ All orders and transactions</li>
                    <li>✅ All products and inventory</li>
                    <li>✅ All staff accounts</li>
                    <li>✅ All notifications and carts</li>
                </ul>
                <p class="text-danger"><small>This action cannot be undone!</small></p>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmRestore">
                    <label class="form-check-label" for="confirmRestore">
                        I understand that current data will be replaced.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <form method="post" id="restoreForm">
                    <input type="hidden" name="restore_file" id="restoreFile" value="">
                    <button type="button" class="btn btn-secondary btn-modal" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="restore" class="btn btn-warning btn-modal" id="restoreBtn" disabled>Yes, Restore Database</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmRestore(filename) {
    document.getElementById('restoreFile').value = filename;
    document.getElementById('confirmRestore').checked = false;
    document.getElementById('restoreBtn').disabled = true;
    var restoreModal = new bootstrap.Modal(document.getElementById('restoreModal'));
    restoreModal.show();
}

document.getElementById('confirmRestore').addEventListener('change', function() {
    document.getElementById('restoreBtn').disabled = !this.checked;
});

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

// Add loading states to backup buttons
document.querySelectorAll('.backup-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
        if (!confirm('Are you sure?')) {
            e.preventDefault();
            return;
        }
        this.classList.add('loading');
        this.disabled = true;
    });
});

// Touch feedback
document.querySelectorAll('.backup-btn, .btn-action, .back-to-tools, .btn-modal, .scroll-to-top').forEach(el => {
    el.addEventListener('touchstart', function() {
        this.style.opacity = '0.8';
    });
    el.addEventListener('touchend', function() {
        this.style.opacity = '1';
    });
    el.addEventListener('touchcancel', function() {
        this.style.opacity = '1';
    });
});

// Auto-hide alerts after 15 seconds
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 15000);
</script>

<?php include '../../includes/footer.php'; ?>