<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

// Delete logs older than 30 days
try {
    $stmt = $pdo->prepare("DELETE FROM tbl_inventory_logs WHERE log_time < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    
    $deletedCount = $stmt->rowCount();
    $_SESSION['success'] = "Successfully deleted " . $deletedCount . " inventory log(s) older than 30 days.";
    
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to clear logs: " . $e->getMessage();
}

header('Location: inventory.php');
exit;
?>