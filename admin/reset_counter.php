<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/config.php';
requireLogin();
requireRole('admin');

// Handle the reset action
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset_today'])) {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Get current counter value before reset (for logging)
        $check = $pdo->prepare("SELECT order_counter FROM tbl_daily_counters WHERE counter_date = CURDATE()");
        $check->execute();
        $old_value = $check->fetchColumn();
        
        // Reset today's counter to 1
        $stmt = $pdo->prepare("
            INSERT INTO tbl_daily_counters (counter_date, order_counter, inventory_log_counter)
            VALUES (CURDATE(), 1, 1)
            ON DUPLICATE KEY UPDATE 
                order_counter = 1,
                inventory_log_counter = 1,
                last_reset_at = NOW()
        ");
        $stmt->execute();
        
        // Clear session mapping for today
        if (isset($_SESSION['daily_order_map'][date('Y-m-d')])) {
            unset($_SESSION['daily_order_map'][date('Y-m-d')]);
        }
        
        // Log the reset action
        $log_msg = "Admin " . $_SESSION['user_id'] . " reset daily counter from " . ($old_value ?: 'new') . " to 1";
        error_log($log_msg);
        
        $pdo->commit();
        
        $_SESSION['success'] = "✅ Order counter successfully reset to 1 for today!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "❌ Failed to reset counter: " . $e->getMessage();
        error_log("Reset counter error: " . $e->getMessage());
    }
    
    header('Location: /modules/tools/setup_daily_reset.php');
    exit;
}

// If someone tries to access directly without POST
header('Location: /modules/tools/setup_daily_reset.php');
exit;
?>