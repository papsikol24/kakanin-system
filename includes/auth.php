<?php
/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Redirect to login if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Get current user data
 */
function currentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM tbl_users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

/**
 * Check role permission (pass array of allowed roles)
 */
function hasRole($roles) {
    $user = currentUser();
    if (!$user) return false;
    return in_array($user['role'], (array)$roles);
}

/**
 * Require specific role(s)
 */
function requireRole($roles) {
    if (!hasRole($roles)) {
        $_SESSION['error'] = 'Access denied. Insufficient privileges.';
        header('Location: /index.php');
        exit;
    }
}

/**
 * Log inventory changes
 */
function logInventory($pdo, $product_id, $user_id, $change_type, $quantity_changed, $previous_stock, $new_stock) {
    $stmt = $pdo->prepare("INSERT INTO tbl_inventory_logs (product_id, user_id, change_type, quantity_changed, previous_stock, new_stock) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$product_id, $user_id, $change_type, $quantity_changed, $previous_stock, $new_stock]);
}
?>