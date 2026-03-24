<?php
// Turn off error display - we want clean JSON only
error_reporting(0);
ini_set('display_errors', 0);

require_once '../includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$response = ['success' => false];

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    if ($action === 'get_status') {
        // Get current store status - PUBLIC (no login required)
        $stmt = $pdo->query("SELECT is_online, offline_message, updated_at FROM tbl_store_status WHERE id = 1");
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($status) {
            $response['success'] = true;
            $response['is_online'] = (bool)$status['is_online'];
            $response['offline_message'] = $status['offline_message'];
            $response['updated_at'] = $status['updated_at'];
        } else {
            $response['is_online'] = false;
            $response['offline_message'] = 'Store is currently closed';
        }
    }
    
    elseif ($action === 'toggle_store') {
        // Only admin/manager can toggle store status
        if (!isset($_SESSION['user_id'])) {
            $response['error'] = 'Not logged in';
            echo json_encode($response);
            exit;
        }
        
        // Check if user is admin or manager
        $stmt = $pdo->prepare("SELECT role FROM tbl_users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || !in_array($user['role'], ['admin', 'manager'])) {
            $response['error'] = 'Unauthorized';
            echo json_encode($response);
            exit;
        }
        
        $new_status = $_POST['status'] ?? null;
        $message = $_POST['message'] ?? '';
        
        if ($new_status === null) {
            $response['error'] = 'Status not specified';
            echo json_encode($response);
            exit;
        }
        
        // Update store status
        $stmt = $pdo->prepare("
            UPDATE tbl_store_status 
            SET is_online = ?, offline_message = ?, updated_by = ?, updated_at = NOW() 
            WHERE id = 1
        ");
        $stmt->execute([$new_status, $message, $_SESSION['user_id']]);
        
        // Log the action in users table
        $action_type = $new_status ? 'open' : 'close';
        $stmt = $pdo->prepare("UPDATE tbl_users SET last_store_action = NOW(), last_store_action_type = ? WHERE id = ?");
        $stmt->execute([$action_type, $_SESSION['user_id']]);
        
        $response['success'] = true;
        $response['is_online'] = (bool)$new_status;
        $response['message'] = $new_status ? 'Store is now OPEN' : 'Store is now CLOSED';
    }
    
    elseif ($action === 'get_history') {
        // Get store status change history - admin only
        if (!isset($_SESSION['user_id'])) {
            $response['error'] = 'Not logged in';
            echo json_encode($response);
            exit;
        }
        
        $stmt = $pdo->prepare("SELECT role FROM tbl_users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || $user['role'] !== 'admin') {
            $response['error'] = 'Unauthorized';
            echo json_encode($response);
            exit;
        }
        
        // Get last 50 status changes
        $stmt = $pdo->query("
            SELECT s.is_online, s.offline_message, s.updated_at, u.username 
            FROM tbl_store_status s
            LEFT JOIN tbl_users u ON s.updated_by = u.id
            ORDER BY s.updated_at DESC
            LIMIT 50
        ");
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['success'] = true;
        $response['history'] = $history;
    }
    
    else {
        $response['error'] = 'Invalid action';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Store Status API Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Server error']);
}
?>