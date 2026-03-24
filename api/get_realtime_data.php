<?php
// Turn off error display - we want clean JSON only
error_reporting(0);
ini_set('display_errors', 0);

require_once '../includes/config.php';
require_once '../includes/daily_counter.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON header
header('Content-Type: application/json');

$response = ['success' => false];

try {
    // Get device ID from request
    $device_id = isset($_GET['device_id']) ? $_GET['device_id'] : (isset($_POST['device_id']) ? $_POST['device_id'] : '');
    
    // Generate a device ID if not provided
    if (empty($device_id)) {
        $device_id = $_SERVER['HTTP_USER_AGENT'] . '_' . $_SERVER['REMOTE_ADDR'];
        $device_id = md5($device_id);
    }

    // Check if user is logged in (staff or customer)
    $isStaff = isset($_SESSION['user_id']);
    $isCustomer = isset($_SESSION['customer_id']);
    
    if (!$isStaff && !$isCustomer) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }

    // Add timestamp to every response for debugging
    $response['server_time'] = time();
    $response['server_time_formatted'] = date('Y-m-d H:i:s');

    // ===== STAFF DATA =====
    if ($isStaff) {
        $user_id = $_SESSION['user_id'];
        $today = date('Y-m-d');
        
        // Get all counts in a single query where possible
        $stmt = $pdo->prepare("
            SELECT 
                SUM(CASE WHEN DATE(order_date) = ? THEN 1 ELSE 0 END) as today_count,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN status = 'completed' AND DATE(order_date) = ? THEN 1 ELSE 0 END) as completed_today,
                SUM(CASE WHEN status = 'cancelled' AND DATE(order_date) = ? THEN 1 ELSE 0 END) as cancelled_today,
                COUNT(*) as total_orders
            FROM tbl_orders
        ");
        $stmt->execute([$today, $today, $today]);
        $counts = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response['todayCount'] = (int)($counts['today_count'] ?? 0);
        $response['pendingCount'] = (int)($counts['pending_count'] ?? 0);
        $response['completedCount'] = (int)($counts['completed_today'] ?? 0);
        $response['cancelledCount'] = (int)($counts['cancelled_today'] ?? 0);
        $response['totalOrders'] = (int)($counts['total_orders'] ?? 0);
        
        // Get low stock count
        $stmt = $pdo->query("SELECT COUNT(*) FROM tbl_products WHERE stock <= low_stock_threshold");
        $response['lowStockCount'] = (int)$stmt->fetchColumn();

        // ===== ONLINE CASHIERS DATA =====
        // Update current user's session activity
        try {
            $session_id = session_id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $tab_id = $_COOKIE['tab_id'] ?? $_SESSION['tab_id'] ?? '';
            
            // Use REPLACE INTO or INSERT with DUPLICATE KEY
            $stmt = $pdo->prepare("
                INSERT INTO tbl_active_sessions (user_id, session_id, tab_id, ip_address, user_agent, last_activity)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                last_activity = NOW(),
                tab_id = VALUES(tab_id),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent)
            ");
            $stmt->execute([$user_id, $session_id, $tab_id, $ip, $user_agent]);
        } catch (Exception $e) {
            error_log("Failed to update active session: " . $e->getMessage());
        }

        // Clean up old sessions (older than 5 minutes for accuracy)
        try {
            $pdo->exec("DELETE FROM tbl_active_sessions WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        } catch (Exception $e) {
            // Ignore cleanup errors
        }

        // Get online cashiers with active sessions (INNER JOIN for accuracy)
        $onlineCashiers = $pdo->query("
            SELECT 
                u.id,
                u.username,
                u.status as account_status,
                MAX(s.last_activity) as last_activity,
                COUNT(DISTINCT s.id) as active_sessions,
                TIMESTAMPDIFF(SECOND, MAX(s.last_activity), NOW()) as seconds_ago
            FROM tbl_users u
            INNER JOIN tbl_active_sessions s ON u.id = s.user_id 
                AND s.last_activity > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            WHERE u.role = 'cashier'
            GROUP BY u.id
            ORDER BY MAX(s.last_activity) DESC
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        $response['onlineCashiers'] = $onlineCashiers;
        $response['onlineCount'] = count($onlineCashiers);
        
        // Get recent orders with a timestamp for change detection
        $stmt = $pdo->query("
            SELECT 
                o.id,
                o.customer_id,
                o.customer_name,
                o.total_amount,
                o.payment_method,
                o.status,
                UNIX_TIMESTAMP(o.order_date) as order_timestamp,
                DATE_FORMAT(o.order_date, '%Y-%m-%d %H:%i:%s') as order_date,
                u.username as cashier_name,
                CASE 
                    WHEN o.customer_id IS NULL AND o.customer_name IS NOT NULL AND o.customer_name != '' THEN CONCAT(o.customer_name, ' (Deleted)')
                    WHEN o.customer_name IS NOT NULL AND o.customer_name != '' THEN o.customer_name 
                    ELSE 'Walk-in' 
                END as display_customer,
                CASE 
                    WHEN o.customer_id IS NULL AND o.customer_name IS NOT NULL THEN 1 
                    ELSE 0 
                END as is_deleted_customer,
                DATE(o.order_date) as order_date_only
            FROM tbl_orders o 
            LEFT JOIN tbl_users u ON o.created_by = u.id
            ORDER BY o.order_date DESC 
            LIMIT 50
        ");
        $recentOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get the latest order timestamp for change detection
        $latest_order_time = 0;
        $order_ids = [];
        
        // Add daily order numbers to recent orders
        foreach ($recentOrders as &$order) {
            $order_ids[] = $order['id'];
            if ($order['order_timestamp'] > $latest_order_time) {
                $latest_order_time = $order['order_timestamp'];
            }
            
            $order_date = $order['order_date_only'];
            $order_id = $order['id'];
            $formatted_daily = '';
            
            // Check session mapping for today's orders
            if ($order_date == date('Y-m-d') && isset($_SESSION['daily_order_map'][$order_date])) {
                $daily_number = array_search($order_id, $_SESSION['daily_order_map'][$order_date]);
                if ($daily_number) {
                    $formatted_daily = "ORD-" . str_pad($daily_number, 4, '0', STR_PAD_LEFT);
                }
            }
            
            // Check archive for older orders
            if (empty($formatted_daily)) {
                static $archive_cache = [];
                if (!isset($archive_cache[$order_id])) {
                    $stmt2 = $pdo->prepare("SELECT daily_order_number FROM tbl_daily_orders_archive WHERE original_order_id = ?");
                    $stmt2->execute([$order_id]);
                    $archive_cache[$order_id] = $stmt2->fetchColumn();
                }
                $archive_number = $archive_cache[$order_id];
                
                if ($archive_number) {
                    $formatted_daily = "ORD-" . str_pad($archive_number, 4, '0', STR_PAD_LEFT);
                }
            }
            
            $order['daily_number'] = $formatted_daily;
        }
        
        $response['recentOrders'] = $recentOrders;
        $response['latest_order_time'] = $latest_order_time;
        
        // Get notification tracking
        if (!empty($recentOrders)) {
            $max_order_id = max($order_ids);
            
            // Get the last seen order ID for this device
            $stmt = $pdo->prepare("SELECT last_seen_id FROM tbl_notification_seen WHERE user_id = ? AND device_id = ? AND notification_type = 'staff_order'");
            $stmt->execute([$user_id, $device_id]);
            $last_seen = $stmt->fetchColumn();
            
            if ($last_seen === false) {
                $last_seen = 0;
                // Insert new record for this device
                $stmt = $pdo->prepare("INSERT INTO tbl_notification_seen (user_id, device_id, notification_type, last_seen_id) VALUES (?, ?, 'staff_order', 0)");
                $stmt->execute([$user_id, $device_id]);
            }
            
            $response['lastSeenId'] = (int)$last_seen;
            $response['maxOrderId'] = $max_order_id;
            $response['hasNewOrders'] = ($max_order_id > $last_seen);
            
            // Count new orders since last seen
            if ($max_order_id > $last_seen) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_orders WHERE id > ?");
                $stmt->execute([$last_seen]);
                $response['newOrdersCount'] = (int)$stmt->fetchColumn();
            } else {
                $response['newOrdersCount'] = 0;
            }
            
            // Update last seen ID for this device if requested
            if (isset($_POST['mark_seen']) && $_POST['mark_seen'] == 'true') {
                $stmt = $pdo->prepare("UPDATE tbl_notification_seen SET last_seen_id = ?, last_seen_at = NOW() WHERE user_id = ? AND device_id = ? AND notification_type = 'staff_order'");
                $stmt->execute([$max_order_id, $user_id, $device_id]);
                $response['markedSeen'] = true;
            }
        }
        
        $response['success'] = true;
        $response['userType'] = 'staff';
        $response['device_id'] = $device_id;
    }
    
    // ===== CUSTOMER DATA =====
    if ($isCustomer) {
        $customer_id = $_SESSION['customer_id'];
        
        // Get unread notifications count
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM tbl_notifications 
            WHERE customer_id = ? AND is_read = 0
        ");
        $stmt->execute([$customer_id]);
        $response['unreadCount'] = (int)$stmt->fetchColumn();
        
        // Get all notifications with timestamp
        $stmt = $pdo->prepare("
            SELECT 
                n.*,
                o.status as order_status,
                UNIX_TIMESTAMP(n.created_at) as notification_timestamp,
                CASE 
                    WHEN o.customer_id IS NULL AND o.id IS NOT NULL THEN 1 
                    ELSE 0 
                END as is_order_deleted
            FROM tbl_notifications n
            LEFT JOIN tbl_orders o ON n.order_id = o.id
            WHERE n.customer_id = ? 
            ORDER BY n.created_at DESC 
            LIMIT 20
        ");
        $stmt->execute([$customer_id]);
        $response['notifications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get latest notification time
        if (!empty($response['notifications'])) {
            $max_notification_id = max(array_column($response['notifications'], 'id'));
            $latest_notification_time = max(array_column($response['notifications'], 'notification_timestamp'));
            
            // Get the last seen notification ID for this device
            $stmt = $pdo->prepare("SELECT last_seen_id FROM tbl_notification_seen WHERE user_id = ? AND device_id = ? AND notification_type = 'customer_notification'");
            $stmt->execute([$customer_id, $device_id]);
            $last_seen = $stmt->fetchColumn();
            
            if ($last_seen === false) {
                $last_seen = 0;
                $stmt = $pdo->prepare("INSERT INTO tbl_notification_seen (user_id, device_id, notification_type, last_seen_id) VALUES (?, ?, 'customer_notification', 0)");
                $stmt->execute([$customer_id, $device_id]);
            }
            
            $response['lastSeenId'] = (int)$last_seen;
            $response['maxNotificationId'] = $max_notification_id;
            $response['latestNotificationTime'] = $latest_notification_time;
            $response['hasNewNotifications'] = ($max_notification_id > $last_seen);
            
            // Count new notifications
            if ($max_notification_id > $last_seen) {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM tbl_notifications WHERE customer_id = ? AND id > ?");
                $stmt->execute([$customer_id, $last_seen]);
                $response['newNotificationsCount'] = (int)$stmt->fetchColumn();
            } else {
                $response['newNotificationsCount'] = 0;
            }
            
            if (isset($_POST['mark_seen']) && $_POST['mark_seen'] == 'true') {
                $stmt = $pdo->prepare("UPDATE tbl_notification_seen SET last_seen_id = ?, last_seen_at = NOW() WHERE user_id = ? AND device_id = ? AND notification_type = 'customer_notification'");
                $stmt->execute([$max_notification_id, $customer_id, $device_id]);
                $response['markedSeen'] = true;
            }
        }
        
        // Get customer orders with timestamps
        $stmt = $pdo->prepare("
            SELECT 
                o.*,
                UNIX_TIMESTAMP(o.order_date) as order_timestamp,
                CASE 
                    WHEN o.customer_id IS NULL THEN 1 
                    ELSE 0 
                END as is_deleted
            FROM tbl_orders o 
            WHERE o.customer_id = ? 
            ORDER BY o.order_date DESC 
            LIMIT 20
        ");
        $stmt->execute([$customer_id]);
        $response['orders'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get latest order time for change detection
        if (!empty($response['orders'])) {
            $response['latestOrderTime'] = max(array_column($response['orders'], 'order_timestamp'));
        }
        
        // Get cart count
        $response['cartCount'] = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
        
        $response['success'] = true;
        $response['userType'] = 'customer';
        $response['device_id'] = $device_id;
    }
    
    // Add request ID to prevent caching
    $response['request_id'] = uniqid();
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Realtime API Error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'error' => 'Server error: ' . $e->getMessage(),
        'time' => time()
    ]);
}
?>