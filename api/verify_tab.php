<?php
session_start();

header('Content-Type: application/json');

$response = ['valid' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tab_id'])) {
    $tab_id = $_POST['tab_id'];
    
    // Check if user is logged in and tab ID matches
    if (isset($_SESSION['user_id']) && 
        isset($_SESSION['user_tab_id']) && 
        $_SESSION['user_tab_id'] === $tab_id) {
        
        $response['valid'] = true;
        $response['user_id'] = $_SESSION['user_id'];
        $response['role'] = $_SESSION['user_role'] ?? 'staff';
    }
}

echo json_encode($response);
?>