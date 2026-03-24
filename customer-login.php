<?php
require_once 'includes/config.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: customer-login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Handle notification actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['mark_all_read'])) {
        $stmt = $pdo->prepare("UPDATE tbl_notifications SET is_read = 1 WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $_SESSION['success'] = "All notifications marked as read.";
        header('Location: customer-dashboard.php');
        exit;
    }
    
    if (isset($_POST['delete_all'])) {
        $stmt = $pdo->prepare("DELETE FROM tbl_notifications WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $_SESSION['success'] = "All notifications deleted.";
        header('Location: customer-dashboard.php');
        exit;
    }
    
    if (isset($_POST['delete_one'])) {
        $notif_id = (int)$_POST['notification_id'];
        $stmt = $pdo->prepare("DELETE FROM tbl_notifications WHERE id = ? AND customer_id = ?");
        $stmt->execute([$notif_id, $customer_id]);
        $_SESSION['success'] = "Notification deleted.";
        header('Location: customer-dashboard.php');
        exit;
    }
    
    // Delete order code
    if (isset($_POST['delete_order'])) {
        $order_id = (int)$_POST['order_id'];
        
        $getName = $pdo->prepare("SELECT customer_name, status, total_amount, order_date FROM tbl_orders WHERE id = ? AND customer_id = ?");
        $getName->execute([$order_id, $customer_id]);
        $orderData = $getName->fetch();
        
        if ($orderData) {
            $customer_name = $orderData['customer_name'];
            $order_status = $orderData['status'];
            
            if (empty($customer_name)) {
                $cust = $pdo->prepare("SELECT name FROM tbl_customers WHERE id = ?");
                $cust->execute([$customer_id]);
                $customer_name = $cust->fetchColumn();
            }
            
            $pdo->beginTransaction();
            
            try {
                $forceSave1 = $pdo->prepare("UPDATE tbl_orders SET customer_name = ? WHERE id = ?");
                $forceSave1->execute([$customer_name, $order_id]);
                
                if ($order_status == 'pending' || $order_status == 'cancelled') {
                    $items = $pdo->prepare("SELECT * FROM tbl_order_items WHERE order_id = ?");
                    $items->execute([$order_id]);
                    $order_items = $items->fetchAll();
                    
                    foreach ($order_items as $item) {
                        $stmt = $pdo->prepare("SELECT stock FROM tbl_products WHERE id = ?");
                        $stmt->execute([$item['product_id']]);
                        $old_stock = $stmt->fetchColumn();
                        $new_stock = $old_stock + $item['quantity'];
                        
                        $stmt = $pdo->prepare("UPDATE tbl_products SET stock = ? WHERE id = ?");
                        $stmt->execute([$new_stock, $item['product_id']]);
                        
                        $log = $pdo->prepare("INSERT INTO tbl_inventory_logs (product_id, user_id, change_type, quantity_changed, previous_stock, new_stock) VALUES (?, NULL, 'add', ?, ?, ?)");
                        $log->execute([$item['product_id'], $item['quantity'], $old_stock, $new_stock]);
                    }
                }
                
                // Mark notifications related to this order as read
                $stmt = $pdo->prepare("UPDATE tbl_notifications SET is_read = 1 WHERE order_id = ? AND customer_id = ?");
                $stmt->execute([$order_id, $customer_id]);
                
                // Track this deletion for real-time staff updates
                $trackStmt = $pdo->prepare("INSERT INTO tbl_deleted_orders (order_id, customer_id, customer_name, notified) VALUES (?, ?, ?, 0)");
                $trackStmt->execute([$order_id, $customer_id, $customer_name]);
                
                $forceSave2 = $pdo->prepare("UPDATE tbl_orders SET customer_name = ? WHERE id = ?");
                $forceSave2->execute([$customer_name, $order_id]);
                
                $stmt = $pdo->prepare("UPDATE tbl_orders SET customer_id = NULL WHERE id = ? AND customer_id = ?");
                $stmt->execute([$order_id, $customer_id]);
                
                $finalCheck = $pdo->prepare("UPDATE tbl_orders SET customer_name = ? WHERE id = ? AND (customer_name IS NULL OR customer_name = '' OR customer_name != ?)");
                $finalCheck->execute([$customer_name, $order_id, $customer_name]);
                
                $pdo->commit();
                
                $verify = $pdo->prepare("SELECT customer_name FROM tbl_orders WHERE id = ?");
                $verify->execute([$order_id]);
                $saved_name = $verify->fetchColumn();
                
                $status_text = ($order_status == 'pending' || $order_status == 'cancelled') 
                    ? "Stock has been restored." 
                    : "Note: Stock was NOT restored because this order was already completed.";
                    
                // Add deleted marker to session for UI highlighting
                $_SESSION['deleted_order_' . $order_id] = $order_status;
                    
                $_SESSION['success'] = "✅ Order #{$order_id} has been removed from your history.\n{$status_text}\nYour name '{$saved_name}' will permanently remain in staff records.";
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['error'] = "❌ Failed to delete order: " . $e->getMessage();
                error_log("Order deletion error: " . $e->getMessage());
            }
        } else {
            $_SESSION['error'] = "❌ Order not found or does not belong to you.";
        }
        
        header('Location: customer-dashboard.php');
        exit;
    }
}

include 'includes/customer_header.php';

$stmt = $pdo->prepare("SELECT * FROM tbl_customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

$orders = $pdo->prepare("SELECT * FROM tbl_orders WHERE customer_id = ? ORDER BY order_date DESC");
$orders->execute([$customer_id]);
$orders = $orders->fetchAll();

$notifications = $pdo->prepare("SELECT * FROM tbl_notifications WHERE customer_id = ? ORDER BY created_at DESC LIMIT 20");
$notifications->execute([$customer_id]);
$notifications = $notifications->fetchAll();

$unread_count = $pdo->prepare("SELECT COUNT(*) FROM tbl_notifications WHERE customer_id = ? AND is_read = 0");
$unread_count->execute([$customer_id]);
$unread = $unread_count->fetchColumn();

// Get best selling products with error handling
$bestSellers = [];
try {
    $result = $pdo->query("
        SELECT p.id, p.name, p.image, p.price, p.description, p.stock, COALESCE(SUM(oi.quantity), 0) as total_ordered
        FROM tbl_products p
        LEFT JOIN tbl_order_items oi ON p.id = oi.product_id
        WHERE p.stock > 0
        GROUP BY p.id
        ORDER BY total_ordered DESC
        LIMIT 8
    ");
    if ($result) {
        $bestSellers = $result->fetchAll();
    }
} catch (Exception $e) {
    $bestSellers = [];
}

// If no best sellers, get any products with stock
if (empty($bestSellers)) {
    try {
        $result = $pdo->query("
            SELECT id, name, image, price, description, stock, 0 as total_ordered
            FROM tbl_products 
            WHERE stock > 0 
            ORDER BY name 
            LIMIT 8
        ");
        if ($result) {
            $bestSellers = $result->fetchAll();
        }
    } catch (Exception $e) {
        $bestSellers = [];
    }
}

// Calculate cart count for badge
$cartCount = !empty($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>

<style>
    /* ===== GLOBAL STYLES ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }

    .container {
        width: 100%;
        padding-right: 12px;
        padding-left: 12px;
        margin-right: auto;
        margin-left: auto;
    }
    
    @media (min-width: 576px) {
        .container {
            max-width: 540px;
        }
    }
    
    @media (min-width: 768px) {
        .container {
            max-width: 720px;
        }
    }
    
    @media (min-width: 992px) {
        .container {
            max-width: 960px;
        }
    }
    
    @media (min-width: 1200px) {
        .container {
            max-width: 1140px;
        }
    }

    /* ===== WELCOME CARD ===== */
    .welcome-card {
        background: white;
        border-radius: 20px;
        padding: 1rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        margin-bottom: 1.5rem;
        border-left: 8px solid #008080;
        animation: slideIn 0.5s ease;
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    .welcome-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
    }

    .welcome-info {
        flex: 1;
        min-width: 200px;
    }

    .welcome-info h2 {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 0.25rem;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .welcome-info h2 i {
        color: #008080;
    }

    .welcome-info p {
        font-size: 0.8rem;
        color: #666;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .welcome-actions {
        display: flex;
        align-items: center;
        gap: 0.8rem;
        flex-wrap: wrap;
    }

    /* ===== NOTIFICATION BELL ===== */
    .notification-wrapper {
        position: relative;
        display: inline-block;
    }

    .notification-bell {
        position: relative;
        cursor: pointer;
        background: #f0f0f0;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .notification-bell:hover {
        background: #e0e0e0;
    }

    .notification-bell i {
        font-size: 1.2rem;
        color: #008080;
    }

    .notification-dot {
        position: absolute;
        top: 2px;
        right: 2px;
        width: 12px;
        height: 12px;
        background: #ff4444;
        border: 2px solid white;
        border-radius: 50%;
        z-index: 2;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    /* ===== ORDER NOW BUTTON ===== */
    .btn-order-now-mobile {
        background: linear-gradient(135deg, #ff8c00, #ff6b00);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.5rem 1rem;
        font-weight: 600;
        font-size: 0.85rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        white-space: nowrap;
    }

    .btn-order-now-mobile:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255, 140, 0, 0.3);
        color: white;
    }

    /* ===== NOTIFICATIONS PANEL ===== */
    .notifications-panel {
        background: white;
        border-radius: 15px;
        margin-bottom: 1.5rem;
        box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        overflow: hidden;
        display: none;
    }

    .notifications-panel.show {
        display: block;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .notifications-header {
        background: #008080;
        color: white;
        padding: 0.8rem 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    .notifications-header h5 {
        margin: 0;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .notification-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }

    .btn-notification {
        background: rgba(255,255,255,0.2);
        border: 1px solid rgba(255,255,255,0.3);
        color: white;
        border-radius: 50px;
        padding: 0.2rem 0.6rem;
        font-size: 0.7rem;
        transition: all 0.2s;
        border: none;
    }

    .btn-notification:hover {
        background: rgba(255,255,255,0.3);
    }

    .notifications-list {
        max-height: 300px;
        overflow-y: auto;
    }

    .notification-item {
        padding: 0.8rem;
        border-bottom: 1px solid #eee;
        position: relative;
        transition: background 0.2s;
        font-size: 0.85rem;
    }

    .notification-item:hover {
        background: #f8f9fa;
    }

    .notification-item.unread {
        background: #fff3cd;
        border-left: 3px solid #ff8c00;
        animation: newNotification 2s ease;
    }

    @keyframes newNotification {
        0% { background-color: #fff3cd; }
        50% { background-color: #ffe69c; }
        100% { background-color: #fff3cd; }
    }

    .notification-content {
        padding-right: 25px;
    }

    .notification-title {
        font-weight: 600;
        color: #333;
        font-size: 0.85rem;
        margin-bottom: 0.2rem;
    }

    .notification-message {
        font-size: 0.75rem;
        color: #666;
        margin-bottom: 0.2rem;
        line-height: 1.4;
    }

    .notification-time {
        font-size: 0.65rem;
        color: #999;
    }

    .delete-notification {
        position: absolute;
        top: 8px;
        right: 8px;
        color: #dc3545;
        background: transparent;
        border: none;
        font-size: 0.9rem;
        cursor: pointer;
        opacity: 0.5;
        transition: opacity 0.2s;
    }

    .delete-notification:hover {
        opacity: 1;
    }

    .empty-state {
        text-align: center;
        padding: 2rem;
        color: #999;
    }

    .empty-state i {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        color: #ddd;
    }

    /* ===== BEST SELLERS SECTION ===== */
    .section-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
        margin: 1.5rem 0 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-title i {
        color: #ff8c00;
    }

    /* ===== BEST SELLERS GRID - 3x3 on Mobile ===== */
    .best-sellers-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.5rem;
        margin-bottom: 2rem;
    }
    
    @media (min-width: 768px) {
        .best-sellers-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
        }
    }
    
    @media (min-width: 992px) {
        .best-sellers-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 1.5rem;
        }
    }

    .best-seller-card {
        background: white;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: all 0.3s;
        height: 100%;
        display: flex;
        flex-direction: column;
        border: 1px solid #f0f0f0;
        position: relative;
    }

    .best-seller-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .best-seller-card img {
        width: 100%;
        height: 100px;
        object-fit: cover;
        border-bottom: 2px solid #ff8c00;
    }
    
    @media (min-width: 768px) {
        .best-seller-card img {
            height: 140px;
        }
    }

    .best-seller-info {
        padding: 0.6rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    @media (min-width: 768px) {
        .best-seller-info {
            padding: 0.75rem;
        }
    }

    .best-seller-name {
        font-weight: 600;
        color: #333;
        font-size: 0.75rem;
        margin-bottom: 0.2rem;
        line-height: 1.2;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 0.3rem;
    }
    
    @media (min-width: 768px) {
        .best-seller-name {
            font-size: 0.9rem;
        }
    }

    .best-seller-price {
        font-weight: 700;
        color: #008080;
        font-size: 0.85rem;
        margin-bottom: 0.2rem;
    }
    
    @media (min-width: 768px) {
        .best-seller-price {
            font-size: 1rem;
        }
    }

    .best-seller-orders {
        font-size: 0.65rem;
        color: #888;
        margin-bottom: 0.3rem;
    }
    
    @media (min-width: 768px) {
        .best-seller-orders {
            font-size: 0.7rem;
        }
    }

    .cart-indicator-small {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #28a745;
        color: white;
        border-radius: 50px;
        padding: 0.1rem 0.4rem;
        font-size: 0.55rem;
        margin-left: 0.3rem;
    }
    
    @media (min-width: 768px) {
        .cart-indicator-small {
            font-size: 0.65rem;
            padding: 0.15rem 0.5rem;
        }
    }

    .btn-add-cart-small {
        background: #ff8c00;
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.25rem 0;
        font-size: 0.65rem;
        text-decoration: none;
        display: block;
        text-align: center;
        transition: all 0.2s;
        margin-top: auto;
        cursor: pointer;
        width: 100%;
        border: none;
    }
    
    .btn-add-cart-small.loading {
        position: relative;
        color: transparent !important;
    }
    
    .btn-add-cart-small.loading::after {
        content: '';
        position: absolute;
        width: 16px;
        height: 16px;
        top: 50%;
        left: 50%;
        margin-left: -8px;
        margin-top: -8px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.6s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    @media (min-width: 768px) {
        .btn-add-cart-small {
            padding: 0.3rem 0;
            font-size: 0.75rem;
        }
    }

    .btn-add-cart-small:hover:not(:disabled) {
        background: #e07b00;
        transform: translateY(-2px);
        box-shadow: 0 5px 10px rgba(255,140,0,0.3);
    }

    .btn-add-cart-small:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    /* ===== ORDER HISTORY WITH SCROLLABLE TABLE ===== */
    .order-history-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
        margin: 1.5rem 0 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .order-history-title i {
        color: #008080;
    }

    .table-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 1.5rem;
        position: relative;
        height: 400px;
        display: flex;
        flex-direction: column;
    }

    .table-header {
        background: linear-gradient(135deg, #008080, #20b2aa);
        color: white;
        padding: 12px 15px;
        font-weight: 600;
        font-size: 0.95rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    .table-header i {
        margin-right: 8px;
    }

    .table-header .badge {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 4px 10px;
        border-radius: 50px;
        font-size: 0.75rem;
    }

    .table-scroll {
        overflow-y: auto;
        flex: 1;
        -webkit-overflow-scrolling: touch;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
    }

    .table thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table thead th {
        background: #f8f9fa;
        color: #333;
        font-weight: 600;
        border-bottom: 2px solid #008080;
        padding: 12px 10px;
        white-space: nowrap;
        font-size: 0.8rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .table tbody td {
        padding: 10px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .table tbody tr {
        transition: all 0.3s;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
    }

    .table tbody tr:active {
        background: #e9ecef;
        transform: scale(0.99);
    }

    .table tbody tr.status-updated {
        animation: statusUpdateFlash 1.5s ease;
    }

    @keyframes statusUpdateFlash {
        0% { background-color: #d4edda; transform: scale(1.01); }
        50% { background-color: #a3cfbb; }
        100% { background-color: transparent; transform: scale(1); }
    }

    .badge.bg-order {
        background: #000 !important;
        color: white;
        padding: 0.2rem 0.4rem;
        border-radius: 50px;
        font-size: 0.7rem;
    }

    .badge.bg-success {
        background: #28a745 !important;
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }

    .badge.bg-warning {
        background: #ffc107 !important;
        color: #333;
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }

    .badge.bg-secondary {
        background: #6c757d !important;
        padding: 0.2rem 0.4rem;
        font-size: 0.7rem;
    }

    .btn-action {
        padding: 0.2rem 0.5rem;
        font-size: 0.7rem;
        border-radius: 50px;
        text-decoration: none;
        display: inline-block;
        transition: all 0.2s;
        margin: 0 2px;
        border: none;
        cursor: pointer;
    }
    
    @media (min-width: 768px) {
        .btn-action {
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
        }
    }

    .btn-view-action {
        background: #008080;
        color: white;
    }

    .btn-view-action:hover {
        background: #20b2aa;
    }

    .btn-delete-action {
        background: #dc3545;
        color: white;
    }

    .btn-delete-action:hover {
        background: #bb2d3b;
    }

    /* ===== CART BADGE ===== */
    .badge-cart {
        background: #dc3545;
        color: white;
        border-radius: 50px;
        padding: 0.2rem 0.5rem;
        font-size: 0.7rem;
        margin-left: 0.3rem;
        display: inline-block;
    }
    
    @keyframes badgePop {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    
    .badge-cart.updated {
        animation: badgePop 0.3s ease;
    }

    /* ===== NOTIFICATION SOUND STYLES ===== */
    #notificationSound {
        display: none;
    }

    .sound-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 0.2rem 0.6rem;
        border-radius: 50px;
        font-size: 0.7rem;
        cursor: pointer;
        margin-left: 0.5rem;
        border: 1px solid rgba(255,255,255,0.3);
    }

    .sound-toggle i {
        font-size: 0.7rem;
    }

    .sound-toggle.muted {
        opacity: 0.5;
    }

    /* ===== SCROLL TO TOP BUTTON ===== */
    .scroll-to-top {
        position: fixed;
        bottom: 15px;
        right: 15px;
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, #008080, #20b2aa);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        box-shadow: 0 5px 15px rgba(0,128,128,0.3);
        transition: all 0.3s;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        border: 2px solid white;
    }

    .scroll-to-top.show {
        opacity: 1;
        visibility: visible;
    }

    .scroll-to-top:hover {
        background: #ff8c00;
        transform: translateY(-3px);
    }

    /* ===== LOADING INDICATOR ===== */
    .realtime-indicator {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.7rem;
        color: #28a745;
        margin-left: 0.5rem;
    }

    .realtime-indicator i {
        font-size: 0.6rem;
        animation: spin 2s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }

    /* ===== DELETE ORDER MODAL ===== */
    .modal-content {
        border-radius: 20px;
        border: none;
    }

    .modal-header {
        border-radius: 20px 20px 0 0;
        padding: 1rem;
    }

    .modal-header.bg-warning {
        background: linear-gradient(135deg, #ff8c00, #ff6b00) !important;
    }

    .modal-header.bg-danger {
        background: linear-gradient(135deg, #dc3545, #bb2d3b) !important;
    }

    .modal-body {
        padding: 1.2rem;
        font-size: 0.9rem;
    }

    .modal-footer {
        border-top: 1px solid #eee;
        padding: 1rem;
    }

    .form-check-input:checked {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    /* ===== MOBILE-SPECIFIC ADJUSTMENTS ===== */
    @media (max-width: 768px) {
        .welcome-row {
            flex-direction: column;
            align-items: flex-start;
        }

        .welcome-actions {
            width: 100%;
            justify-content: space-between;
        }
        
        .best-sellers-grid {
            gap: 0.4rem;
        }

        .best-seller-card img {
            height: 80px;
        }

        .best-seller-name {
            font-size: 0.7rem;
        }

        .best-seller-price {
            font-size: 0.75rem;
        }

        .best-seller-orders {
            font-size: 0.6rem;
        }

        .notification-bell {
            width: 35px;
            height: 35px;
        }

        .notification-bell i {
            font-size: 1rem;
        }

        .btn-order-now-mobile {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
        }
        
        .table-container {
            height: 350px;
        }

        .table thead th {
            padding: 8px 5px;
            font-size: 0.75rem;
        }

        .table tbody td {
            padding: 6px 5px;
            font-size: 0.75rem;
        }
    }

    @media (max-width: 480px) {
        .table-container {
            height: 300px;
        }

        .table {
            min-width: 600px;
        }

        .table thead th {
            padding: 6px 4px;
            font-size: 0.7rem;
        }

        .table tbody td {
            padding: 6px 4px;
            font-size: 0.7rem;
        }

        .btn-action {
            padding: 0.15rem 0.4rem;
            font-size: 0.65rem;
        }
    }

    @media (max-width: 360px) {
        .best-sellers-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        
        .table-container {
            height: 280px;
        }

        .table {
            min-width: 500px;
        }
    }
</style>

<!-- Notification Sound Element -->
<audio id="notificationSound" preload="auto" loop="false">
    <source src="assets/sounds/notification.mp3" type="audio/mpeg">
</audio>

<div class="container mt-2">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert" style="white-space: pre-line; font-size: 0.85rem; padding: 0.75rem;">
            <i class="fas fa-check-circle me-2"></i><?php echo nl2br($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size: 0.85rem; padding: 0.75rem;">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- WELCOME CARD -->
    <div class="welcome-card">
        <div class="welcome-row">
            <div class="welcome-info">
                <h2>
                    <i class="fas fa-hand-peace"></i>
                    <?php echo htmlspecialchars($customer['name']); ?>!
                </h2>
                <p>
                    <i class="fas fa-envelope"></i>
                    <?php echo htmlspecialchars($customer['email']); ?>
                </p>
            </div>
            <div class="welcome-actions">
                <!-- NOTIFICATION BELL -->
                <div class="notification-wrapper">
                    <div class="notification-bell" onclick="toggleNotifications()">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread > 0): ?>
                            <span class="notification-dot" id="notificationDot"></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ORDER NOW BUTTON -->
                <a href="menu.php" class="btn-order-now-mobile">
                    <i class="fas fa-utensils"></i> Order Now
                </a>
            </div>
        </div>
    </div>

    <!-- NOTIFICATIONS PANEL -->
    <div id="notificationsPanel" class="notifications-panel">
        <div class="notifications-header">
            <h5>
                <i class="fas fa-bell"></i>
                Notifications
                <?php if ($unread > 0): ?>
                    <span class="badge bg-danger" id="notificationHeaderBadge"><?php echo $unread; ?> new</span>
                <?php endif; ?>
            </h5>
            <?php if (!empty($notifications)): ?>
            <div class="notification-actions">
                <form method="post" style="display: inline;" onsubmit="return confirm('Mark all as read?');">
                    <button type="submit" name="mark_all_read" class="btn-notification">
                        <i class="fas fa-check-double"></i> Mark all read
                    </button>
                </form>
                <form method="post" style="display: inline;" onsubmit="return confirm('Delete ALL notifications?');">
                    <button type="submit" name="delete_all" class="btn-notification">
                        <i class="fas fa-trash-alt"></i> Delete all
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <div class="notifications-list" id="notificationsList">
            <?php if (empty($notifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <p>No notifications yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notification-item <?php echo $notif['is_read'] ? '' : 'unread'; ?>" data-notification-id="<?php echo $notif['id']; ?>">
                        <form method="post" style="display: inline;" onsubmit="return confirm('Delete this notification?');">
                            <input type="hidden" name="notification_id" value="<?php echo $notif['id']; ?>">
                            <button type="submit" name="delete_one" class="delete-notification" title="Delete">
                                <i class="fas fa-times-circle"></i>
                            </button>
                        </form>
                        <div class="notification-content">
                            <div class="notification-title"><?php echo htmlspecialchars($notif['title']); ?></div>
                            <div class="notification-message"><?php echo nl2br(htmlspecialchars($notif['message'])); ?></div>
                            <div class="notification-time"><?php echo date('M d, h:i A', strtotime($notif['created_at'])); ?></div>
                        </div>
                        <?php if ($notif['order_id']): ?>
                            <a href="order-success.php?id=<?php echo $notif['order_id']; ?>" class="btn-view-action" style="margin-top: 0.5rem; display: inline-block; font-size: 0.7rem; padding: 0.2rem 0.5rem;">View</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- BEST SELLERS SECTION -->
    <?php if (!empty($bestSellers)): ?>
    <div class="section-title">
        <i class="fas fa-crown"></i>
        Best Sellers
    </div>
    <div class="best-sellers-grid" id="bestSellersGrid">
        <?php 
        $count = 0;
        foreach ($bestSellers as $product): 
            if ($count >= 4) break;
            $count++;
            
            $productId = isset($product['id']) ? $product['id'] : 0;
            $productName = isset($product['name']) ? $product['name'] : 'Unknown';
            $productImage = isset($product['image']) ? $product['image'] : '';
            $productPrice = isset($product['price']) ? $product['price'] : 0;
            $totalOrdered = isset($product['total_ordered']) ? $product['total_ordered'] : 0;
            
            // Check current stock
            $current_stock = 0;
            if ($productId > 0) {
                $stock_check = $pdo->prepare("SELECT stock FROM tbl_products WHERE id = ?");
                $stock_check->execute([$productId]);
                $current_stock = $stock_check->fetchColumn();
                if ($current_stock === false) $current_stock = 0;
            }
            
            // Check if already in cart
            $inCart = isset($_SESSION['cart'][$productId]);
            $cartQty = $inCart ? $_SESSION['cart'][$productId]['quantity'] : 0;
        ?>
        <div class="best-seller-card" data-product-id="<?php echo $productId; ?>" data-product-name="<?php echo htmlspecialchars($productName); ?>" data-product-price="<?php echo $productPrice; ?>">
            <?php 
            $imagePath = "assets/images/" . $productImage;
            if (!empty($productImage) && file_exists($imagePath)): 
            ?>
                <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($productName); ?>">
            <?php else: ?>
                <div style="height:100px; background:#ddd; display:flex; align-items:center; justify-content:center;">
                    <i class="fas fa-image fa-2x text-muted"></i>
                </div>
            <?php endif; ?>
            <div class="best-seller-info">
                <div class="best-seller-name">
                    <?php echo htmlspecialchars($productName); ?>
                    <?php if ($inCart): ?>
                        <span class="cart-indicator-small" id="cart-indicator-<?php echo $productId; ?>">
                            <i class="fas fa-check-circle"></i> <?php echo $cartQty; ?>
                        </span>
                    <?php endif; ?>
                </div>
                <div class="best-seller-price">₱<?php echo number_format(floatval($productPrice), 2); ?></div>
                <div class="best-seller-orders">
                    <i class="fas fa-shopping-bag"></i> <?php echo intval($totalOrdered); ?> sold
                </div>
                <?php if ($current_stock > 0): ?>
                    <button class="btn-add-cart-small add-to-cart-btn" 
                            data-product-id="<?php echo $productId; ?>"
                            data-product-name="<?php echo htmlspecialchars($productName); ?>">
                        <i class="fas fa-cart-plus"></i> 
                        <?php echo $inCart ? 'Add More' : 'Add to Cart'; ?>
                    </button>
                <?php else: ?>
                    <button class="btn-add-cart-small" disabled style="background: #ccc;">
                        <i class="fas fa-times-circle"></i> Out of Stock
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- ORDER HISTORY WITH SCROLLABLE TABLE -->
    <div class="order-history-title">
        <i class="fas fa-history"></i>
        Your Orders
        <span class="realtime-indicator" id="realtimeIndicator">
            <i class="fas fa-sync-alt"></i> live
        </span>
        <span class="sound-toggle" id="soundToggle" onclick="toggleSound()">
            <i class="fas fa-volume-up"></i> sound
        </span>
    </div>

    <?php if (empty($orders)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            No orders yet. 
            <a href="menu.php" class="alert-link">Browse menu</a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <div class="table-header">
                <div>
                    <i class="fas fa-history"></i> Order History
                </div>
                <span class="badge" id="ordersCount"><?php echo count($orders); ?> orders</span>
            </div>
            <div class="table-scroll">
                <table class="table" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr data-order-id="<?php echo $order['id']; ?>" data-status="<?php echo $order['status']; ?>">
                            <td><span class="badge bg-order">#<?php echo $order['id']; ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                            <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo ucfirst($order['payment_method']); ?></td>
                            <td>
                                <?php
                                $class = '';
                                $statusText = '';
                                if ($order['status'] == 'completed') {
                                    $class = 'bg-success';
                                    $statusText = 'Completed';
                                } elseif ($order['status'] == 'pending') {
                                    $class = 'bg-warning';
                                    $statusText = 'Pending';
                                } else {
                                    $class = 'bg-secondary';
                                    $statusText = 'Cancelled';
                                }
                                ?>
                                <span class="badge <?php echo $class; ?> order-status"><?php echo $statusText; ?></span>
                            </td>
                            <td>
                                <a href="order-success.php?id=<?php echo $order['id']; ?>" class="btn-action btn-view-action" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <button type="button" class="btn-action btn-delete-action" 
                                        onclick="confirmDeleteOrder(
                                            <?php echo $order['id']; ?>, 
                                            '<?php echo date('M d, Y', strtotime($order['order_date'])); ?>', 
                                            <?php echo $order['total_amount']; ?>,
                                            '<?php echo $order['status']; ?>'
                                        )"
                                        title="Remove from history">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- DELETE ORDER MODAL -->
<div class="modal fade" id="deleteOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" id="deleteModalHeader">
                <h5 class="modal-title text-white">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span id="modalTitle">Remove Order</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Remove order #<span id="deleteOrderId"></span>?</strong></p>
                <p class="text-muted small">Date: <span id="deleteOrderDate"></span> (₱<span id="deleteOrderAmount"></span>)</p>
                
                <div id="stockRestoreMessage" class="alert alert-success mt-2 small">
                    <i class="fas fa-undo-alt me-2"></i>
                    <span id="stockMessage">Stock will be restored</span>
                </div>
                
                <div id="noStockRestoreMessage" class="alert alert-warning mt-2 small" style="display: none;">
                    <i class="fas fa-lock me-2"></i>
                    <span id="noStockMessage">Completed order - stock NOT restored</span>
                </div>
                
                <div class="alert alert-info mt-2 small">
                    <i class="fas fa-bell-slash me-2"></i>
                    <span>Notifications for this order will be marked as read and stop playing sounds.</span>
                </div>
                
                <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" id="confirmDeleteOrder">
                    <label class="form-check-label small" for="confirmDeleteOrder">
                        I understand this will be removed from my history
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <form method="post" id="deleteOrderForm">
                    <input type="hidden" name="order_id" id="deleteOrderInput" value="">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_order" class="btn btn-sm" id="confirmDeleteBtn" disabled>Remove</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// ===== GENERATE UNIQUE DEVICE ID =====
let deviceId = localStorage.getItem('customer_device_id');
if (!deviceId) {
    deviceId = 'customer_' + Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
    localStorage.setItem('customer_device_id', deviceId);
}

// ===== SOUND TOGGLE =====
let soundEnabled = localStorage.getItem('customerSoundEnabled') !== 'false';
let lastNotificationTime = 0;
let lastSeenNotificationId = 0;
let lastNotificationCount = <?php echo $unread; ?>;
const NOTIFICATION_COOLDOWN = 5000;

<?php if (!empty($notifications)): ?>
lastSeenNotificationId = <?php echo $notifications[0]['id']; ?>;
<?php endif; ?>

function toggleSound() {
    soundEnabled = !soundEnabled;
    localStorage.setItem('customerSoundEnabled', soundEnabled);
    updateSoundToggleIcon();
}

function updateSoundToggleIcon() {
    const toggle = document.getElementById('soundToggle');
    if (toggle) {
        if (soundEnabled) {
            toggle.innerHTML = '<i class="fas fa-volume-up"></i> sound';
            toggle.classList.remove('muted');
        } else {
            toggle.innerHTML = '<i class="fas fa-volume-mute"></i> muted';
            toggle.classList.add('muted');
        }
    }
}

updateSoundToggleIcon();

function toggleNotifications() {
    document.getElementById('notificationsPanel').classList.toggle('show');
}

document.addEventListener('click', function(e) {
    const panel = document.getElementById('notificationsPanel');
    const bell = document.querySelector('.notification-bell');
    if (panel && bell && !bell.contains(e.target) && !panel.contains(e.target)) {
        panel.classList.remove('show');
    }
});

// ===== AJAX ADD TO CART =====
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const productId = this.dataset.productId;
            const btn = this;
            
            btn.classList.add('loading');
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Adding...';
            
            fetch('ajax/add-to-cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'product_id=' + productId
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    updateCartCount(data.cartCount);
                    updateBestSellerCard(productId, data.newQty);
                    btn.innerHTML = '<i class="fas fa-check"></i> Added!';
                    setTimeout(() => window.location.href = 'cart.php', 500);
                } else {
                    btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Failed';
                    btn.classList.remove('loading');
                    setTimeout(() => {
                        btn.innerHTML = '<i class="fas fa-cart-plus"></i> ' + (btn.innerHTML.includes('Add More') ? 'Add More' : 'Add to Cart');
                        btn.disabled = false;
                    }, 2000);
                    alert('Error: ' + data.error);
                }
            })
            .catch(() => {
                btn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Error';
                btn.classList.remove('loading');
            });
        });
    });
});

function updateCartCount(count) {
    const badge = document.querySelector('.badge-cart');
    if (badge) {
        badge.textContent = count;
        badge.classList.add('updated');
        setTimeout(() => badge.classList.remove('updated'), 300);
    }
}

function updateBestSellerCard(id, qty) {
    const card = document.querySelector(`[data-product-id="${id}"]`);
    if (!card) return;
    
    let indicator = document.getElementById('cart-indicator-' + id);
    if (!indicator) {
        indicator = document.createElement('span');
        indicator.id = 'cart-indicator-' + id;
        indicator.className = 'cart-indicator-small';
        card.querySelector('.best-seller-name').appendChild(indicator);
    }
    indicator.innerHTML = '<i class="fas fa-check-circle"></i> ' + qty;
}

// ===== DELETE ORDER =====
function confirmDeleteOrder(id, date, amount, status) {
    document.getElementById('deleteOrderId').textContent = id;
    document.getElementById('deleteOrderDate').textContent = date;
    document.getElementById('deleteOrderAmount').textContent = amount.toFixed(2);
    document.getElementById('deleteOrderInput').value = id;
    
    const stockMsg = document.getElementById('stockRestoreMessage');
    const noStockMsg = document.getElementById('noStockRestoreMessage');
    const header = document.getElementById('deleteModalHeader');
    const btn = document.getElementById('confirmDeleteBtn');
    const title = document.getElementById('modalTitle');
    
    if (status === 'pending' || status === 'cancelled') {
        stockMsg.style.display = 'block';
        noStockMsg.style.display = 'none';
        header.className = 'modal-header bg-warning text-white';
        btn.className = 'btn btn-warning btn-sm';
        title.textContent = 'Remove Order - Stock Restored';
    } else {
        stockMsg.style.display = 'none';
        noStockMsg.style.display = 'block';
        header.className = 'modal-header bg-danger text-white';
        btn.className = 'btn btn-danger btn-sm';
        title.textContent = 'Remove Order - Stock NOT Restored';
    }
    
    document.getElementById('confirmDeleteOrder').checked = false;
    document.getElementById('confirmDeleteBtn').disabled = true;
    new bootstrap.Modal(document.getElementById('deleteOrderModal')).show();
}

document.getElementById('confirmDeleteOrder')?.addEventListener('change', function() {
    document.getElementById('confirmDeleteBtn').disabled = !this.checked;
});

// ===== SCROLL TO TOP =====
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

window.addEventListener('scroll', function() {
    const btn = document.getElementById('scrollToTop');
    if (window.scrollY > 300) btn.classList.add('show');
    else btn.classList.remove('show');
});

// ===== NOTIFICATION SOUND =====
function playNotificationSound() {
    if (!soundEnabled) return;
    const now = Date.now();
    if (now - lastNotificationTime < NOTIFICATION_COOLDOWN) return;
    
    const sound = document.getElementById('notificationSound');
    if (sound) {
        sound.currentTime = 0;
        sound.play().catch(e => console.log('Sound play failed'));
        lastNotificationTime = now;
    }
}

function markNotificationsSeen() {
    fetch('api/get_realtime_data.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'device_id=' + encodeURIComponent(deviceId) + '&mark_seen=true'
    }).catch(console.error);
}

document.addEventListener('click', markNotificationsSeen);
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) markNotificationsSeen();
});

function updateNotificationBadge(unreadCount) {
    const dot = document.getElementById('notificationDot');
    const headerBadge = document.getElementById('notificationHeaderBadge');
    
    if (unreadCount > 0) {
        if (!dot) {
            const bell = document.querySelector('.notification-bell');
            if (bell) {
                const newDot = document.createElement('span');
                newDot.id = 'notificationDot';
                newDot.className = 'notification-dot';
                bell.appendChild(newDot);
            }
        }
        if (headerBadge) {
            headerBadge.textContent = unreadCount + ' new';
            headerBadge.style.display = 'inline-block';
        }
    } else {
        if (dot) dot.remove();
        if (headerBadge) headerBadge.style.display = 'none';
    }
}

function updateOrdersTable(orders) {
    const tbody = document.querySelector('#ordersTable tbody');
    if (!tbody || !orders) return;
    
    let html = '';
    orders.forEach(order => {
        const date = new Date(order.order_date).toLocaleDateString('en-US', {
            month: 'short', day: 'numeric', year: 'numeric'
        });
        
        let statusClass = order.status === 'completed' ? 'bg-success' : 
                         order.status === 'pending' ? 'bg-warning' : 'bg-secondary';
        let statusText = order.status.charAt(0).toUpperCase() + order.status.slice(1);
        let paymentMethod = order.payment_method.charAt(0).toUpperCase() + order.payment_method.slice(1);
        
        html += `<tr data-order-id="${order.id}" data-status="${order.status}">
            <td><span class="badge bg-order">#${order.id}</span></td>
            <td>${date}</td>
            <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
            <td>${paymentMethod}</td>
            <td><span class="badge ${statusClass} order-status">${statusText}</span></td>
            <td>
                <a href="order-success.php?id=${order.id}" class="btn-action btn-view-action" title="View">
                    <i class="fas fa-eye"></i>
                </a>
                <button type="button" class="btn-action btn-delete-action" 
                        onclick="confirmDeleteOrder(${order.id}, '${date}', ${order.total_amount}, '${order.status}')"
                        title="Remove">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    });
    
    tbody.innerHTML = html;
    document.getElementById('ordersCount').textContent = orders.length + ' orders';
    
    // Highlight first row if status changed
    const firstRow = tbody.querySelector('tr:first-child');
    if (firstRow) {
        firstRow.classList.add('status-updated');
        setTimeout(() => firstRow.classList.remove('status-updated'), 2000);
    }
}

function updateNotificationsPanel(notifications) {
    const panel = document.getElementById('notificationsList');
    if (!panel) return;
    
    if (!notifications || notifications.length === 0) {
        panel.innerHTML = '<div class="empty-state"><i class="fas fa-bell-slash"></i><p>No notifications</p></div>';
        return;
    }
    
    let html = '';
    notifications.forEach(n => {
        const isUnread = n.is_read == 0;
        const date = new Date(n.created_at).toLocaleString('en-US', {
            month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit'
        });
        
        html += `<div class="notification-item ${isUnread ? 'unread' : ''}">
            <form method="post" style="display:inline;" onsubmit="return confirm('Delete?');">
                <input type="hidden" name="notification_id" value="${n.id}">
                <button type="submit" name="delete_one" class="delete-notification" title="Delete">
                    <i class="fas fa-times-circle"></i>
                </button>
            </form>
            <div class="notification-content">
                <div class="notification-title">${escapeHtml(n.title)}</div>
                <div class="notification-message">${escapeHtml(n.message).replace(/\n/g, '<br>')}</div>
                <div class="notification-time">${date}</div>
            </div>
            ${n.order_id ? `<a href="order-success.php?id=${n.order_id}" class="btn-view-action" style="margin-top:0.5rem; display:inline-block; font-size:0.7rem; padding:0.2rem 0.5rem;">View</a>` : ''}
        </div>`;
    });
    
    panel.innerHTML = html;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===== REAL-TIME FETCH FUNCTION =====
let lastOrderData = {};
let lastOrderTimestamp = Date.now();

// Store initial order data
document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
    const orderId = row.dataset.orderId;
    const status = row.querySelector('.order-status')?.textContent.trim().toLowerCase() || '';
    lastOrderData[orderId] = { status: status };
});

function fetchCustomerUpdates() {
    fetch('api/get_realtime_data.php?device_id=' + encodeURIComponent(deviceId))
        .then(res => res.json())
        .then(data => {
            console.log('Customer real-time data:', data);
            
            if (!data.success || data.userType !== 'customer') return;
            
            // Update notification badge
            if (data.unreadCount !== undefined) {
                updateNotificationBadge(data.unreadCount);
            }
            
            // Check for new notifications and play sound
            if (data.hasNewNotifications && data.notifications) {
                const newNotifs = data.notifications.filter(n => n.id > lastSeenNotificationId);
                if (newNotifs.length > 0) {
                    playNotificationSound();
                    
                    if ("Notification" in window && Notification.permission === "granted") {
                        const latest = newNotifs[0];
                        new Notification(latest.title || 'New Notification', {
                            body: latest.message || 'You have a new notification',
                            icon: 'assets/images/owner.jpg'
                        });
                    }
                    
                    lastSeenNotificationId = Math.max(...newNotifs.map(n => n.id));
                    
                    const bell = document.querySelector('.notification-bell');
                    if (bell) {
                        bell.style.backgroundColor = '#fff3cd';
                        setTimeout(() => bell.style.backgroundColor = '', 1000);
                    }
                    
                    if (document.getElementById('notificationsPanel')?.classList.contains('show')) {
                        updateNotificationsPanel(data.notifications);
                    }
                }
            }
            
            // ===== REAL-TIME ORDER HISTORY UPDATES =====
            if (data.orders && data.orders.length > 0) {
                let ordersChanged = false;
                
                // Check if orders have changed (new order, status change, or deleted)
                data.orders.forEach(order => {
                    const orderId = order.id;
                    const currentStatus = order.status;
                    const previousData = lastOrderData[orderId];
                    
                    // Check if this is a new order (not in lastOrderData)
                    if (!previousData) {
                        ordersChanged = true;
                        console.log(`🆕 New order detected: #${orderId}`);
                    } 
                    // Check if status changed
                    else if (previousData.status !== currentStatus) {
                        ordersChanged = true;
                        console.log(`📝 Order #${orderId} status changed from ${previousData.status} to ${currentStatus}`);
                    }
                });
                
                // Also check for deleted orders (in lastOrderData but not in new data)
                const currentOrderIds = new Set(data.orders.map(o => o.id));
                Object.keys(lastOrderData).forEach(orderId => {
                    if (!currentOrderIds.has(parseInt(orderId))) {
                        ordersChanged = true;
                        console.log(`🗑️ Order #${orderId} was removed`);
                    }
                });
                
                // Update the table if anything changed
                if (ordersChanged) {
                    console.log('🔄 Updating orders table...');
                    updateOrdersTable(data.orders);
                    
                    // Update lastOrderData
                    lastOrderData = {};
                    data.orders.forEach(order => {
                        lastOrderData[order.id] = { status: order.status };
                    });
                    
                    // Flash the table header to indicate update
                    const tableHeader = document.querySelector('.table-header');
                    if (tableHeader) {
                        tableHeader.style.backgroundColor = '#28a745';
                        tableHeader.style.transition = 'background-color 0.5s';
                        setTimeout(() => {
                            tableHeader.style.backgroundColor = '';
                        }, 500);
                    }
                }
            }
            
            // Update cart count
            const cartBadge = document.querySelector('.badge-cart');
            if (cartBadge && data.cartCount !== undefined) {
                cartBadge.textContent = data.cartCount;
            }
        })
        .catch(error => console.error('Error fetching customer updates:', error));
}

// Enhanced updateOrdersTable function
function updateOrdersTable(orders) {
    const tbody = document.querySelector('#ordersTable tbody');
    if (!tbody || !orders) return;
    
    let html = '';
    const now = new Date();
    
    orders.forEach(order => {
        const orderDate = new Date(order.order_date);
        const formattedDate = orderDate.toLocaleDateString('en-US', {
            month: 'short', 
            day: 'numeric', 
            year: 'numeric'
        });
        const formattedTime = orderDate.toLocaleTimeString('en-US', {
            hour: '2-digit', 
            minute: '2-digit'
        });
        
        // Determine status class and text
        let statusClass = '';
        let statusText = '';
        let statusIcon = '';
        
        if (order.status === 'completed') {
            statusClass = 'bg-success';
            statusText = 'Completed';
            statusIcon = 'fa-check-circle';
        } else if (order.status === 'pending') {
            statusClass = 'bg-warning';
            statusText = 'Pending';
            statusIcon = 'fa-clock';
        } else {
            statusClass = 'bg-secondary';
            statusText = 'Cancelled';
            statusIcon = 'fa-times-circle';
        }
        
        // Payment method display
        const paymentMethod = order.payment_method.charAt(0).toUpperCase() + order.payment_method.slice(1);
        
        // Check if this is a new order (within last 5 minutes)
        const timeDiff = (now - orderDate) / (1000 * 60); // minutes
        const isNew = timeDiff <= 5 && order.status === 'pending';
        
        html += `<tr data-order-id="${order.id}" data-status="${order.status}" class="${isNew ? 'new-order' : ''}">
            <td>
                <span class="badge bg-order">#${order.id}</span>
                ${isNew ? '<span class="badge-new">NEW</span>' : ''}
            </td>
            <td>
                ${formattedDate}<br>
                <small class="text-muted">${formattedTime}</small>
            </td>
            <td>₱${parseFloat(order.total_amount).toFixed(2)}</td>
            <td>${paymentMethod}</td>
            <td>
                <span class="badge ${statusClass} order-status">
                    <i class="fas ${statusIcon} me-1"></i>${statusText}
                </span>
            </td>
            <td>
                <a href="order-success.php?id=${order.id}" class="btn-action btn-view-action" title="View Details">
                    <i class="fas fa-eye"></i>
                </a>
                <button type="button" class="btn-action btn-delete-action" 
                        onclick="confirmDeleteOrder(${order.id}, '${formattedDate}', ${order.total_amount}, '${order.status}')"
                        title="Remove from history">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    });
    
    tbody.innerHTML = html;
    
    // Update order count badge
    const ordersCount = document.getElementById('ordersCount');
    if (ordersCount) {
        ordersCount.textContent = orders.length + ' order' + (orders.length !== 1 ? 's' : '');
    }
    
    // Highlight the first row (most recent)
    const firstRow = tbody.querySelector('tr:first-child');
    if (firstRow) {
        firstRow.classList.add('status-updated');
        setTimeout(() => firstRow.classList.remove('status-updated'), 2000);
    }
    
    // Make rows touch-friendly
    makeRowsTouchFriendly();
}

// Make table rows touch-friendly
function makeRowsTouchFriendly() {
    document.querySelectorAll('#ordersTable tbody tr').forEach(row => {
        row.removeEventListener('touchstart', row._touchHandler);
        row.removeEventListener('touchend', row._touchEndHandler);
        row.removeEventListener('touchcancel', row._touchCancelHandler);
        
        row._touchHandler = function() {
            this.style.backgroundColor = '#f0f0f0';
            this.style.transform = 'scale(0.99)';
        };
        
        row._touchEndHandler = function() {
            this.style.backgroundColor = '';
            this.style.transform = '';
        };
        
        row._touchCancelHandler = function() {
            this.style.backgroundColor = '';
            this.style.transform = '';
        };
        
        row.addEventListener('touchstart', row._touchHandler);
        row.addEventListener('touchend', row._touchEndHandler);
        row.addEventListener('touchcancel', row._touchCancelHandler);
    });
}

// Add CSS for new order animation
const style = document.createElement('style');
style.textContent = `
    .new-order {
        animation: newOrderPulse 2s ease;
    }
    
    @keyframes newOrderPulse {
        0% { background-color: #fff3cd; transform: scale(1.01); }
        50% { background-color: #ffe69c; }
        100% { background-color: transparent; transform: scale(1); }
    }
    
    .badge-new {
        background: #dc3545;
        color: white;
        font-size: 0.6rem;
        padding: 0.1rem 0.4rem;
        border-radius: 50px;
        margin-left: 0.3rem;
        display: inline-block;
        animation: pulse 2s infinite;
    }
    
    @media (min-width: 768px) {
        .badge-new {
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
        }
    }
`;
document.head.appendChild(style);

// Request notification permission
if ("Notification" in window && Notification.permission === "default") {
    Notification.requestPermission();
}

// Check every 3 seconds for updates (more responsive)
setInterval(fetchCustomerUpdates, 3000);

// Initial fetch
fetchCustomerUpdates();

// Also fetch when page becomes visible
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        fetchCustomerUpdates();
        markNotificationsSeen();
    }
});

// Auto-hide alerts
setTimeout(() => {
    document.querySelectorAll('.alert').forEach(a => {
        a.style.transition = 'opacity 0.5s';
        a.style.opacity = '0';
        setTimeout(() => a.style.display = 'none', 500);
    });
}, 5000);

// Touch-friendly cards
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.dashboard-card').forEach(c => {
        c.addEventListener('touchstart', function() { this.style.transform = 'scale(0.98)'; });
        c.addEventListener('touchend', function() { this.style.transform = ''; });
        c.addEventListener('touchcancel', function() { this.style.transform = ''; });
    });
    
    makeRowsTouchFriendly();
});

// Pull to refresh
let touchStart = 0;
document.addEventListener('touchstart', e => touchStart = e.changedTouches[0].screenY);
document.addEventListener('touchend', e => {
    const dist = e.changedTouches[0].screenY - touchStart;
    if (window.scrollY === 0 && dist > 100) {
        fetchCustomerUpdates();
        const ind = document.getElementById('realtimeIndicator');
        if (ind) {
            ind.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> refreshing';
            setTimeout(() => ind.innerHTML = '<i class="fas fa-sync-alt"></i> live', 1000);
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>