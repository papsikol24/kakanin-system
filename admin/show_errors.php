<?php
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';
requireLogin();
requireRole('admin');

echo "<h2>PHP Error Log</h2>";
echo "<pre>";

// Check PHP error log
$log_file = ini_get('error_log');
if (file_exists($log_file)) {
    echo "Error log file: $log_file\n\n";
    $lines = file($log_file);
    $last_lines = array_slice($lines, -20);
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line) . "\n";
    }
} else {
    echo "No error log file found.\n";
}

echo "\n\n---\n\n";

// Test database connection
try {
    $pdo->query("SELECT 1");
    echo "✅ Database connection OK\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>