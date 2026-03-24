<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'includes/config.php';

// ===== REMOVE FROM ONLINE CUSTOMERS =====
if (isset($_SESSION['customer_id'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM tbl_online_customers WHERE customer_id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
    } catch (Exception $e) {
        error_log("Failed to remove online customer: " . $e->getMessage());
    }
}

// ===== REMOVE FROM ACTIVE SESSIONS (for staff) =====
$user_id = $_SESSION['user_id'] ?? null;
$tab_id = $_SESSION['tab_id'] ?? null;

if ($user_id) {
    try {
        $pdo->prepare("DELETE FROM tbl_active_sessions WHERE user_id = ?")->execute([$user_id]);
        
        if ($tab_id && isset($_SESSION['active_tabs'][$tab_id])) {
            unset($_SESSION['active_tabs'][$tab_id]);
        }
        
        error_log("User ID {$user_id} logged out at " . date('Y-m-d H:i:s'));
        
    } catch (Exception $e) {
        error_log("Failed to remove active session: " . $e->getMessage());
    }
}

// ===== CLEAR ALL SESSION DATA =====
$_SESSION = array();

// Destroy session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Destroy the session
session_destroy();

// Clear all remember me cookies
setcookie('remember_customer', '', time() - 3600, '/');
setcookie('remember_staff', '', time() - 3600, '/');
setcookie('tab_id', '', time() - 3600, '/');

// ===== REDIRECT BASED ON USER TYPE =====
$type = $_GET['type'] ?? '';

if ($type == 'staff') {
    header('Location: /staff-login');
} elseif ($type == 'customer') {
    header('Location: /login');
} else {
    header('Location: /landing-page');
}
exit;
?>