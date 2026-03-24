<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../../includes/config.php';
requireLogin();
requireRole('admin'); // Only admin can delete users

$id = (int)($_GET['id'] ?? 0);

// Don't allow deleting yourself
if ($id == $_SESSION['user_id']) {
    $_SESSION['error'] = "You cannot delete your own account.";
    header('Location: index.php');
    exit;
}

if (!$id) {
    $_SESSION['error'] = "Invalid user ID.";
    header('Location: index.php');
    exit;
}

// Begin transaction
$pdo->beginTransaction();

try {
    // Check if user exists
    $stmt = $pdo->prepare("SELECT * FROM tbl_users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        throw new Exception("User not found.");
    }

    // Check if user has any orders (if created_by references this user)
    $check = $pdo->prepare("SELECT COUNT(*) FROM tbl_orders WHERE created_by = ?");
    $check->execute([$id]);
    $orderCount = $check->fetchColumn();

    if ($orderCount > 0) {
        // Instead of deleting, set orders to NULL or prevent deletion
        // Option 1: Set created_by to NULL for all orders by this user
        $update = $pdo->prepare("UPDATE tbl_orders SET created_by = NULL WHERE created_by = ?");
        $update->execute([$id]);
    }

    // Check if user has any inventory logs
    $logCheck = $pdo->prepare("SELECT COUNT(*) FROM tbl_inventory_logs WHERE user_id = ?");
    $logCheck->execute([$id]);
    $logCount = $logCheck->fetchColumn();

    if ($logCount > 0) {
        // Set user_id to NULL in inventory logs
        $updateLogs = $pdo->prepare("UPDATE tbl_inventory_logs SET user_id = NULL WHERE user_id = ?");
        $updateLogs->execute([$id]);
    }

    // Finally, delete the user
    $stmt = $pdo->prepare("DELETE FROM tbl_users WHERE id = ?");
    $stmt->execute([$id]);

    $pdo->commit();
    
    $_SESSION['success'] = "User '{$user['username']}' deleted successfully.";
    
} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Failed to delete user: " . $e->getMessage();
}

header('Location: index.php');
exit;