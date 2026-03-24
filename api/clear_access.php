<?php
/**
 * Clear staff access verification
 * Used by staff-login.php to clear session when leaving page
 */
session_start();

header('Content-Type: application/json');

if (isset($_POST['clear_access']) || isset($_GET['clear_access'])) {
    unset($_SESSION['code_verified']);
    unset($_SESSION['code_verified_time']);
    echo json_encode(['success' => true, 'message' => 'Access cleared']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>