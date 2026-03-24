<?php
session_start();

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$response = ['success' => false];

if ($action === 'register_tab') {
    $tab_id = $_POST['tab_id'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;
    
    if ($user_id && $tab_id) {
        // Store tab mapping in session
        if (!isset($_SESSION['active_tabs'])) {
            $_SESSION['active_tabs'] = [];
        }
        
        // Register this tab
        $_SESSION['active_tabs'][$tab_id] = [
            'user_id' => $user_id,
            'last_activity' => time(),
            'tab_id' => $tab_id
        ];
        
        $response['success'] = true;
        $response['message'] = 'Tab registered';
    }
} 
elseif ($action === 'verify_tab') {
    $tab_id = $_POST['tab_id'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;
    
    if ($user_id && $tab_id) {
        // Check if tab exists in session
        if (isset($_SESSION['active_tabs'][$tab_id])) {
            // Update last activity
            $_SESSION['active_tabs'][$tab_id]['last_activity'] = time();
            $response['success'] = true;
            $response['valid'] = true;
        } 
        // If tab not found but user is logged in, this might be a new tab
        // We'll register it now to prevent unnecessary logouts
        else {
            // Register this new tab automatically
            $_SESSION['active_tabs'][$tab_id] = [
                'user_id' => $user_id,
                'last_activity' => time(),
                'tab_id' => $tab_id
            ];
            $response['success'] = true;
            $response['valid'] = true;
            $response['message'] = 'Tab auto-registered';
        }
    } else {
        $response['valid'] = false;
    }
}
elseif ($action === 'remove_tab') {
    $tab_id = $_POST['tab_id'] ?? '';
    if (isset($_SESSION['active_tabs'][$tab_id])) {
        unset($_SESSION['active_tabs'][$tab_id]);
        $response['success'] = true;
    }
}

echo json_encode($response);
?>