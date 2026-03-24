<?php
require_once '../../includes/config.php';
require_once '../../includes/daily_counter.php';
requireLogin();

$id = $_GET['id'] ?? 0;

// Function to get estimated preparation time based on order items
function getEstimatedPreparationTime($pdo, $order_id) {
    // Get all items in the order
    $stmt = $pdo->prepare("
        SELECT oi.*, p.price, 
               CASE 
                   WHEN p.price < 10 THEN 'budget'
                   WHEN p.price >= 10 AND p.price < 250 THEN 'regular'
                   WHEN p.price >= 250 THEN 'premium'
                   ELSE 'custom'
               END as category
        FROM tbl_order_items oi
        JOIN tbl_products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order_id]);
    $items = $stmt->fetchAll();
    
    $max_time = 0;
    
    foreach ($items as $item) {
        // Get preparation time for this category
        $stmt = $pdo->prepare("
            SELECT preparation_time 
            FROM tbl_preparation_settings 
            WHERE category = ? 
            ORDER BY is_default DESC 
            LIMIT 1
        ");
        $stmt->execute([$item['category']]);
        $time = $stmt->fetchColumn();
        
        if ($time && $time > $max_time) {
            $max_time = $time;
        }
    }
    
    // Default to 30 minutes if no settings found
    return $max_time ?: 30;
}

// Handle status update with tracking timestamps
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    requireRole(['admin', 'manager', 'cashier']);
    
    $new_status = $_POST['status'];
    $order_id = $_POST['order_id'];
    $current_user_id = $_SESSION['user_id'];
    
    // Validate status
    $valid_statuses = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'completed', 'cancelled'];
    
    if (in_array($new_status, $valid_statuses)) {
        
        // Get current order details
        $stmt = $pdo->prepare("SELECT o.*, c.name AS customer_name, c.id AS customer_id 
                               FROM tbl_orders o 
                               LEFT JOIN tbl_customers c ON o.customer_id = c.id 
                               WHERE o.id = ?");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
        // Begin transaction
        $pdo->beginTransaction();
        
        try {
            // Set tracking timestamps based on status
            $update_fields = [];
            $params = [];
            
            // Always update status and last update
            $update_fields[] = "status = ?";
            $params[] = $new_status;
            $update_fields[] = "last_status_update = NOW()";
            
            // Track who completed/cancelled
            if ($new_status == 'completed' || $new_status == 'cancelled' || $new_status == 'delivered') {
                $update_fields[] = "completed_by = ?";
                $params[] = $current_user_id;
            }
            
            // Status-specific timestamps
            switch($new_status) {
                case 'confirmed':
                    if (!$order['confirmed_at']) {
                        $update_fields[] = "confirmed_at = NOW()";
                    }
                    break;
                    
                case 'preparing':
                    if (!$order['preparation_started_at']) {
                        $update_fields[] = "preparation_started_at = NOW()";
                    }
                    break;
                    
                case 'ready':
                    if (!$order['preparation_completed_at']) {
                        $update_fields[] = "preparation_completed_at = NOW()";
                        
                        // Calculate actual preparation time
                        if ($order['preparation_started_at']) {
                            $start = strtotime($order['preparation_started_at']);
                            $end = time();
                            $minutes = round(($end - $start) / 60);
                            $update_fields[] = "actual_preparation_time = ?";
                            $params[] = $minutes;
                        }
                    }
                    // DO NOT set ready_for_pickup_at - keep it NULL
                    break;
                    
                case 'out_for_delivery':
                    if (!$order['out_for_delivery_at']) {
                        $update_fields[] = "out_for_delivery_at = NOW()";
                        
                        // Generate tracking number if not exists
                        if (!$order['tracking_number']) {
                            $tracking = 'TRK-' . date('Ymd') . '-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
                            $update_fields[] = "tracking_number = ?";
                            $params[] = $tracking;
                        }
                    }
                    break;
                    
                case 'delivered':
                    if (!$order['delivered_at']) {
                        $update_fields[] = "delivered_at = NOW()";
                    }
                    // Also mark as completed
                    $update_fields[] = "status = 'completed'";
                    break;
                    
                case 'completed':
                    // Mark all tracking steps as completed if not already
                    if (!$order['confirmed_at']) {
                        $update_fields[] = "confirmed_at = NOW()";
                    }
                    if (!$order['preparation_started_at']) {
                        $update_fields[] = "preparation_started_at = NOW()";
                    }
                    if (!$order['preparation_completed_at']) {
                        $update_fields[] = "preparation_completed_at = NOW()";
                    }
                    if (!$order['out_for_delivery_at']) {
                        $update_fields[] = "out_for_delivery_at = NOW()";
                    }
                    if (!$order['delivered_at']) {
                        $update_fields[] = "delivered_at = NOW()";
                    }
                    
                    // Calculate actual preparation time if not set
                    if (!$order['actual_preparation_time'] && $order['preparation_started_at']) {
                        $start = strtotime($order['preparation_started_at']);
                        $end = time();
                        $minutes = round(($end - $start) / 60);
                        $update_fields[] = "actual_preparation_time = ?";
                        $params[] = $minutes;
                    }
                    break;
            }
            
            // Build and execute update query
            $sql = "UPDATE tbl_orders SET " . implode(", ", $update_fields) . " WHERE id = ?";
            $params[] = $order_id;
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            
            // Create notification for customer
            if ($order['customer_id']) {
                $title = "";
                $message = "";
                
                switch($new_status) {
                    case 'confirmed':
                        $title = "Order #{$order_id} Confirmed";
                        $message = "Your order has been confirmed and will be prepared soon.";
                        break;
                        
                    case 'preparing':
                        $title = "Order #{$order_id} is being prepared";
                        $message = "Your order is now being prepared by our kitchen staff.";
                        break;
                        
                    case 'ready':
                        $title = "Order #{$order_id} ready for pickup";
                        $message = "Your order is now ready for pickup. Please come to the store.";
                        break;
                        
                    case 'out_for_delivery':
                        $tracking = $order['tracking_number'] ?? ('TRK-' . date('Ymd') . '-' . str_pad($order_id, 6, '0', STR_PAD_LEFT));
                        $title = "Order #{$order_id} out for delivery";
                        $message = "Your order is now out for delivery! Tracking #: {$tracking}";
                        break;
                        
                    case 'delivered':
                    case 'completed':
                        $title = "Order #{$order_id} Delivered";
                        $message = "Your order has been delivered. Thank you for choosing Jen's Kakanin!";
                        break;
                        
                    case 'cancelled':
                        $title = "Order #{$order_id} Cancelled";
                        $message = "Your order #{$order_id} has been cancelled by staff.";
                        break;
                }
                
                if (!empty($title) && !empty($message)) {
                    $notif_stmt = $pdo->prepare("INSERT INTO tbl_notifications (customer_id, order_id, title, message, type) VALUES (?, ?, ?, ?, 'order_update')");
                    $notif_stmt->execute([
                        $order['customer_id'],
                        $order_id,
                        $title,
                        $message
                    ]);
                }
            }
            
            $pdo->commit();
            
            $_SESSION['success'] = "Order status updated to: " . ucfirst(str_replace('_', ' ', $new_status));
            header("Location: /modules/orders/view.php?id=$order_id");
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Failed to update order: " . $e->getMessage();
            header("Location: /modules/orders/view.php?id=$order_id");
            exit;
        }
    } else {
        $_SESSION['error'] = "Invalid status.";
        header("Location: /modules/orders/view.php?id=$order_id");
        exit;
    }
}

// Fetch order details with tracking info
$order = $pdo->prepare("
    SELECT o.*, 
           u.username AS cashier,
           cu.username AS completed_by_username,
           TIMESTAMPDIFF(MINUTE, o.order_date, NOW()) as minutes_since_order,
           CASE 
               WHEN o.confirmed_at IS NOT NULL THEN 1 ELSE 0 END as is_confirmed,
           CASE 
               WHEN o.preparation_started_at IS NOT NULL THEN 1 ELSE 0 END as is_preparing,
           CASE 
               WHEN o.preparation_completed_at IS NOT NULL THEN 1 ELSE 0 END as is_prepared,
           CASE 
               WHEN o.ready_for_pickup_at IS NOT NULL THEN 1 ELSE 0 END as is_ready,
           CASE 
               WHEN o.out_for_delivery_at IS NOT NULL THEN 1 ELSE 0 END as is_out_for_delivery,
           CASE 
               WHEN o.delivered_at IS NOT NULL THEN 1 ELSE 0 END as is_delivered
    FROM tbl_orders o 
    LEFT JOIN tbl_users u ON o.created_by = u.id 
    LEFT JOIN tbl_users cu ON o.completed_by = cu.id
    WHERE o.id = ?
");
$order->execute([$id]);
$order = $order->fetch();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    header('Location: index.php');
    exit;
}

// Get daily order number
$order_date = date('Y-m-d', strtotime($order['order_date']));
$formatted_daily = '';

if ($order_date == date('Y-m-d') && isset($_SESSION['daily_order_map'][$order_date])) {
    $daily_number = array_search($order['id'], $_SESSION['daily_order_map'][$order_date]);
    if ($daily_number) {
        $formatted_daily = "ORD-{$order_date}-" . str_pad($daily_number, 4, '0', STR_PAD_LEFT);
    }
}

if (empty($formatted_daily)) {
    $stmt = $pdo->prepare("SELECT daily_order_number FROM tbl_daily_orders_archive WHERE original_order_id = ?");
    $stmt->execute([$order['id']]);
    $archive_number = $stmt->fetchColumn();
    
    if ($archive_number) {
        $formatted_daily = "ORD-{$order_date}-" . str_pad($archive_number, 4, '0', STR_PAD_LEFT);
    }
}

// Fetch order items
$items = $pdo->prepare("SELECT oi.*, p.name, p.image 
                        FROM tbl_order_items oi 
                        JOIN tbl_products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

// Get estimated preparation time
$estimated_time = getEstimatedPreparationTime($pdo, $id);

include '../../includes/header.php';
?>

<style>
    /* ===== RESET & BASE STYLES ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }

    .container-fluid {
        width: 100%;
        padding-right: 15px;
        padding-left: 15px;
        margin-right: auto;
        margin-left: auto;
    }

    /* ===== SECTION HEADER ===== */
    .section-header {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e0e0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .section-header h4 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #333;
        margin: 0;
    }

    .section-header h4 i {
        color: #d35400;
        margin-right: 8px;
    }

    @media (min-width: 768px) {
        .section-header h4 {
            font-size: 1.5rem;
        }
    }

    .daily-order-badge {
        background: #008080;
        color: white;
        padding: 5px 15px;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 500;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .daily-order-badge i {
        font-size: 1rem;
    }

    /* ===== TRACKING TIMELINE ===== */
    .tracking-timeline {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border-left: 4px solid #17a2b8;
        animation: slideIn 0.5s ease;
    }

    .timeline-steps {
        display: flex;
        justify-content: space-between;
        margin: 20px 0;
        position: relative;
        flex-wrap: wrap;
        gap: 10px;
    }

    .timeline-steps::before {
        content: '';
        position: absolute;
        top: 25px;
        left: 0;
        right: 0;
        height: 3px;
        background: #e9ecef;
        z-index: 1;
    }

    .step {
        position: relative;
        z-index: 2;
        flex: 1;
        text-align: center;
        min-width: 100px;
    }

    .step-icon {
        width: 50px;
        height: 50px;
        background: white;
        border: 3px solid #dee2e6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-size: 1.3rem;
        color: #6c757d;
        transition: all 0.3s ease;
    }

    .step.completed .step-icon {
        background: #28a745;
        border-color: #28a745;
        color: white;
        animation: bounce 0.5s ease;
    }

    .step.active .step-icon {
        background: #17a2b8;
        border-color: #17a2b8;
        color: white;
        box-shadow: 0 0 0 5px rgba(23,162,184,0.2);
        animation: pulse 1.5s infinite;
    }

    .step.cancelled .step-icon {
        background: #dc3545;
        border-color: #dc3545;
        color: white;
    }

    .step-label {
        font-size: 0.8rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }

    .step-time {
        font-size: 0.7rem;
        color: #6c757d;
    }

    @keyframes bounce {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    /* ===== ORDER DETAILS CARDS ===== */
    .details-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
        margin-bottom: 25px;
    }

    @media (min-width: 768px) {
        .details-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    .card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        overflow: hidden;
        border: none;
    }

    .card-header {
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
        font-size: 1rem;
    }

    .card-header i {
        margin-right: 8px;
    }

    .card-body {
        padding: 20px;
    }

    @media (max-width: 767px) {
        .card-body {
            padding: 15px;
        }
    }

    /* Order details list */
    .details-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
        padding-bottom: 10px;
        border-bottom: 1px dashed #eee;
    }

    .detail-item:last-child {
        border-bottom: none;
        padding-bottom: 0;
    }

    @media (min-width: 576px) {
        .detail-item {
            flex-direction: row;
            align-items: center;
            gap: 10px;
        }
    }

    .detail-label {
        font-weight: 600;
        color: #555;
        min-width: 130px;
        font-size: 0.85rem;
    }

    @media (max-width: 767px) {
        .detail-label {
            min-width: 110px;
            font-size: 0.8rem;
        }
    }

    .detail-value {
        color: #333;
        font-size: 0.9rem;
        word-break: break-word;
    }

    @media (max-width: 767px) {
        .detail-value {
            font-size: 0.85rem;
        }
    }

    .detail-value strong {
        color: #d35400;
    }

    /* Badges */
    .badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 500;
        text-align: center;
    }

    .badge.bg-payment {
        background: #3498db;
        color: white;
    }

    .badge.bg-info {
        background: #17a2b8;
        color: white;
    }

    .badge.bg-completed {
        background: #27ae60;
        color: white;
    }

    .badge.bg-pending {
        background: #f39c12;
        color: white;
    }

    .badge.bg-cancelled {
        background: #e74c3c;
        color: white;
    }

    .badge.bg-confirmed {
        background: #007bff;
        color: white;
    }

    .badge.bg-preparing {
        background: #ffc107;
        color: #333;
    }

    .badge.bg-ready {
        background: #28a745;
        color: white;
    }

    .badge.bg-delivery {
        background: #fd7e14;
        color: white;
    }

    .badge.bg-delivered {
        background: #20c997;
        color: white;
    }

    /* Screenshot link */
    .screenshot-link {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #17a2b8;
        color: white;
        padding: 5px 12px;
        border-radius: 50px;
        text-decoration: none;
        font-size: 0.8rem;
        transition: all 0.2s;
    }

    .screenshot-link:hover {
        background: #138496;
        color: white;
    }

    .screenshot-link i {
        font-size: 0.8rem;
    }

    /* Alert messages */
    .alert {
        padding: 12px 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    /* Status Update Form */
    .status-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .form-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .form-group label {
        font-weight: 500;
        color: #555;
        margin-bottom: 5px;
        font-size: 0.85rem;
    }

    .form-select {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 50px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.9rem;
        background: white;
        transition: all 0.3s;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
    }

    .form-select:focus {
        border-color: #d35400;
        box-shadow: 0 0 0 3px rgba(211,84,0,0.1);
        outline: none;
    }

    .btn-update {
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 12px 20px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 10px rgba(211,84,0,0.2);
        width: 100%;
    }

    .btn-update:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(211,84,0,0.3);
    }

    .btn-update i {
        font-size: 0.9rem;
    }

    /* ===== ITEMS TABLE ===== */
    .items-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: #333;
        margin: 20px 0 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .items-title i {
        color: #d35400;
    }

    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .table {
        width: 100%;
        min-width: 500px;
        border-collapse: collapse;
    }

    .table thead {
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
    }

    .table thead th {
        padding: 12px 15px;
        font-weight: 500;
        font-size: 0.85rem;
        text-align: left;
        white-space: nowrap;
    }

    .table tbody td {
        padding: 12px 15px;
        font-size: 0.85rem;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
    }

    .product-image {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
        margin-right: 10px;
        vertical-align: middle;
    }

    @media (max-width: 767px) {
        .table thead th {
            padding: 10px;
            font-size: 0.8rem;
        }

        .table tbody td {
            padding: 8px 10px;
            font-size: 0.8rem;
        }

        .product-image {
            width: 30px;
            height: 30px;
        }
    }

    /* ===== ACTION BUTTONS ===== */
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin: 20px 0;
    }

    @media (min-width: 576px) {
        .action-buttons {
            flex-direction: row;
            gap: 15px;
        }
    }

    .btn-print {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 12px 20px;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 10px rgba(23,162,184,0.2);
        flex: 1;
    }

    .btn-print:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(23,162,184,0.3);
        color: white;
    }

    .btn-back {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 12px 20px;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 10px rgba(108,117,125,0.2);
        flex: 1;
    }

    .btn-back:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(108,117,125,0.3);
        color: white;
    }

    /* ===== CANCELLATION REASON ===== */
    .cancelled-reason {
        background: #f8d7da;
        border-left: 4px solid #dc3545;
        padding: 15px;
        border-radius: 10px;
        margin: 15px 0;
    }

    .cancelled-reason strong {
        color: #721c24;
        display: block;
        margin-bottom: 5px;
    }

    .cancelled-reason p {
        color: #721c24;
        margin: 0;
        font-size: 0.9rem;
    }

    /* ===== AMOUNT HIGHLIGHT ===== */
    .amount-highlight {
        font-size: 1.2rem;
        font-weight: 700;
        color: #d35400;
    }

    @media (min-width: 768px) {
        .amount-highlight {
            font-size: 1.3rem;
        }
    }

    /* ===== SCROLL TO TOP ===== */
    .scroll-to-top {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 4px 10px rgba(211,84,0,0.3);
        transition: all 0.3s;
        z-index: 999;
        opacity: 0;
        visibility: hidden;
    }

    .scroll-to-top.show {
        opacity: 1;
        visibility: visible;
    }

    .scroll-to-top:hover {
        background: #ff8c00;
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(255,140,0,0.4);
    }

    /* ===== MOBILE SPECIFIC STYLES ===== */
    @media (max-width: 767px) {
        .container-fluid {
            padding-right: 10px;
            padding-left: 10px;
        }

        .card-header {
            padding: 12px 15px;
            font-size: 0.95rem;
        }

        .card-body {
            padding: 12px;
        }

        .detail-label {
            font-size: 0.8rem;
        }

        .detail-value {
            font-size: 0.85rem;
        }

        .badge {
            padding: 4px 8px;
            font-size: 0.7rem;
        }

        .screenshot-link {
            padding: 4px 10px;
            font-size: 0.75rem;
        }

        .btn-update, .btn-print, .btn-back {
            padding: 10px 15px;
            font-size: 0.85rem;
        }

        .items-title {
            font-size: 1rem;
            margin: 15px 0 10px;
        }

        .amount-highlight {
            font-size: 1.1rem;
        }

        .timeline-steps {
            flex-direction: column;
            align-items: flex-start;
        }

        .timeline-steps::before {
            display: none;
        }

        .step {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
            text-align: left;
        }

        .step-icon {
            margin: 0;
        }
    }

    /* Small phones */
    @media (max-width: 480px) {
        .detail-item {
            flex-direction: column;
            align-items: flex-start;
            gap: 3px;
        }

        .detail-label {
            min-width: auto;
        }

        .table thead th {
            padding: 8px;
            font-size: 0.75rem;
        }

        .table tbody td {
            padding: 6px 8px;
            font-size: 0.75rem;
        }

        .product-image {
            width: 25px;
            height: 25px;
        }

        .action-buttons {
            flex-direction: column;
        }
    }
</style>

<div class="container-fluid">
    <!-- Section Header with Daily Order Number -->
    <div class="section-header">
        <h4><i class="fas fa-file-invoice"></i> Order #<?php echo $order['id']; ?></h4>
        <?php if (!empty($formatted_daily)): ?>
            <div class="daily-order-badge">
                <i class="fas fa-calendar-day"></i>
                Daily: <?php echo $formatted_daily; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tracking Timeline -->
    <div class="tracking-timeline">
        <h5 class="mb-3"><i class="fas fa-clock me-2" style="color: #17a2b8;"></i>Order Timeline</h5>
        <div class="timeline-steps">
            <?php
            // Define all status steps with their properties
            $steps = [
                'pending' => [
                    'icon' => 'fa-clock', 
                    'label' => 'Pending', 
                    'time' => $order['order_date'],
                    'completed' => true // Always completed
                ],
                'confirmed' => [
                    'icon' => 'fa-check-circle', 
                    'label' => 'Confirmed', 
                    'time' => $order['confirmed_at'],
                    'completed' => !is_null($order['confirmed_at']) || in_array($order['status'], ['confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered', 'completed'])
                ],
                'preparing' => [
                    'icon' => 'fa-utensils', 
                    'label' => 'Preparing', 
                    'time' => $order['preparation_started_at'],
                    'completed' => !is_null($order['preparation_started_at']) || in_array($order['status'], ['preparing', 'ready', 'out_for_delivery', 'delivered', 'completed'])
                ],
                'ready' => [
                    'icon' => 'fa-box', 
                    'label' => 'Ready for Pickup', 
                    'time' => null, // No time for Ready
                    'completed' => !is_null($order['preparation_completed_at']) || in_array($order['status'], ['ready', 'out_for_delivery', 'delivered', 'completed'])
                ],
                'out_for_delivery' => [
                    'icon' => 'fa-truck', 
                    'label' => 'Out for Delivery', 
                    'time' => $order['out_for_delivery_at'],
                    'completed' => !is_null($order['out_for_delivery_at']) || in_array($order['status'], ['out_for_delivery', 'delivered', 'completed'])
                ],
                'delivered' => [
                    'icon' => 'fa-home', 
                    'label' => 'Delivered', 
                    'time' => $order['delivered_at'],
                    'completed' => !is_null($order['delivered_at']) || in_array($order['status'], ['delivered', 'completed'])
                ]
            ];

            $current_status = $order['status'];

            foreach ($steps as $status => $step):
                // Skip if status is not applicable based on current status
                if ($status == 'confirmed' && $current_status == 'pending') continue;
                if ($status == 'delivered' && $current_status != 'delivered' && $current_status != 'completed') continue;
                
                $is_completed = $step['completed'];
                $is_active = ($status == $current_status) && $current_status != 'cancelled';
                
                $step_class = '';
                if ($order['status'] == 'cancelled') {
                    $step_class = 'cancelled';
                } elseif ($is_completed) {
                    $step_class = 'completed';
                } elseif ($is_active) {
                    $step_class = 'active';
                }
            ?>
            <div class="step <?php echo $step_class; ?>">
                <div class="step-icon">
                    <i class="fas <?php echo $step['icon']; ?>"></i>
                </div>
                <div>
                    <div class="step-label"><?php echo $step['label']; ?></div>
                    <div class="step-time">
                        <?php 
                        if ($status == 'ready') {
                            echo '—';
                        } else {
                            echo $step['time'] ? date('h:i A', strtotime($step['time'])) : 'Pending';
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Tracking Number Display -->
        <?php if ($order['tracking_number']): ?>
        <div class="alert alert-info mt-3 mb-0">
            <i class="fas fa-barcode me-2"></i>
            <strong>Tracking Number:</strong> <?php echo $order['tracking_number']; ?>
        </div>
        <?php endif; ?>
        
        <!-- Actual Preparation Time - Show only once when preparation is completed -->
        <?php if ($order['actual_preparation_time']): ?>
        <div class="alert alert-success mt-3 mb-0">
            <i class="fas fa-clock me-2"></i>
            <strong>Actual Preparation Time:</strong> <?php echo $order['actual_preparation_time']; ?> minute<?php echo $order['actual_preparation_time'] != 1 ? 's' : ''; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Order Details Grid -->
    <div class="details-grid">
        <!-- Order Details Card -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i> Order Details
            </div>
            <div class="card-body">
                <div class="details-list">
                    <div class="detail-item">
                        <span class="detail-label">Order ID:</span>
                        <span class="detail-value">
                            <strong>#<?php echo $order['id']; ?></strong>
                            <?php if (!empty($formatted_daily)): ?>
                                <br><small class="text-muted">(Daily: <?php echo $formatted_daily; ?>)</small>
                            <?php endif; ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Customer:</span>
                        <span class="detail-value">
                            <strong>
                                <?php 
                                if (!empty($order['customer_name'])) {
                                    echo htmlspecialchars($order['customer_name']);
                                } else {
                                    echo 'Walk-in';
                                }
                                ?>
                            </strong>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Order Date:</span>
                        <span class="detail-value"><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Payment Method:</span>
                        <span class="detail-value">
                            <span class="badge bg-payment"><?php echo ucfirst($order['payment_method']); ?></span>
                        </span>
                    </div>
                    
                    <!-- GCash Details -->
                    <?php if ($order['payment_method'] == 'gcash'): ?>
                        <?php if ($order['gcash_ref']): ?>
                        <div class="detail-item">
                            <span class="detail-label">GCash Reference:</span>
                            <span class="detail-value">
                                <span class="badge bg-info"><?php echo $order['gcash_ref']; ?></span>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($order['gcash_screenshot']): ?>
                        <div class="detail-item">
                            <span class="detail-label">GCash Screenshot:</span>
                            <span class="detail-value">
                                <a href="../../assets/uploads/screenshots/<?php echo $order['gcash_screenshot']; ?>" target="_blank" class="screenshot-link">
                                    <i class="fas fa-image"></i> View Screenshot
                                </a>
                            </span>
                        </div>
                        <?php else: ?>
                        <div class="detail-item">
                            <span class="detail-label">GCash Screenshot:</span>
                            <span class="detail-value text-warning">
                                <i class="fas fa-exclamation-triangle"></i> No screenshot
                            </span>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- PayMaya Details -->
                    <?php if ($order['payment_method'] == 'paymaya'): ?>
                        <?php if ($order['paymaya_ref']): ?>
                        <div class="detail-item">
                            <span class="detail-label">PayMaya Reference:</span>
                            <span class="detail-value">
                                <span class="badge bg-info"><?php echo $order['paymaya_ref']; ?></span>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($order['paymaya_screenshot']): ?>
                        <div class="detail-item">
                            <span class="detail-label">PayMaya Screenshot:</span>
                            <span class="detail-value">
                                <a href="../../assets/uploads/screenshots/<?php echo $order['paymaya_screenshot']; ?>" target="_blank" class="screenshot-link">
                                    <i class="fas fa-image"></i> View Screenshot
                                </a>
                            </span>
                        </div>
                        <?php else: ?>
                        <div class="detail-item">
                            <span class="detail-label">PayMaya Screenshot:</span>
                            <span class="detail-value text-warning">
                                <i class="fas fa-exclamation-triangle"></i> No screenshot
                            </span>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Delivery Address -->
                    <?php if ($order['delivery_address']): ?>
                    <div class="detail-item">
                        <span class="detail-label">Delivery Address:</span>
                        <span class="detail-value"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Contact Number -->
                    <?php if ($order['delivery_phone']): ?>
                    <div class="detail-item">
                        <span class="detail-label">Contact Number:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($order['delivery_phone']); ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Order Status with Enhanced Badge -->
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <?php
                            $statusClass = '';
                            $statusIcon = '';
                            switch($order['status']) {
                                case 'pending':
                                    $statusClass = 'bg-pending';
                                    $statusIcon = 'fa-clock';
                                    break;
                                case 'confirmed':
                                    $statusClass = 'bg-confirmed';
                                    $statusIcon = 'fa-check-circle';
                                    break;
                                case 'preparing':
                                    $statusClass = 'bg-preparing';
                                    $statusIcon = 'fa-utensils';
                                    break;
                                case 'ready':
                                    $statusClass = 'bg-ready';
                                    $statusIcon = 'fa-box';
                                    break;
                                case 'out_for_delivery':
                                    $statusClass = 'bg-delivery';
                                    $statusIcon = 'fa-truck';
                                    break;
                                case 'delivered':
                                case 'completed':
                                    $statusClass = 'bg-completed';
                                    $statusIcon = 'fa-check-circle';
                                    break;
                                case 'cancelled':
                                    $statusClass = 'bg-cancelled';
                                    $statusIcon = 'fa-times-circle';
                                    break;
                                default:
                                    $statusClass = 'bg-secondary';
                                    $statusIcon = 'fa-circle';
                            }
                            ?>
                            <span class="badge <?php echo $statusClass; ?>">
                                <i class="fas <?php echo $statusIcon; ?> me-1"></i>
                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                            </span>
                        </span>
                    </div>

                    <!-- Cashier who created the order -->
                    <div class="detail-item">
                        <span class="detail-label">Created By:</span>
                        <span class="detail-value">
                            <i class="fas fa-user-circle me-1"></i>
                            <?php echo htmlspecialchars($order['cashier'] ?? 'Online Order'); ?>
                        </span>
                    </div>

                    <!-- Cashier who completed/cancelled the order -->
                    <?php if ($order['status'] == 'completed' || $order['status'] == 'cancelled' || $order['status'] == 'delivered'): ?>
                    <div class="detail-item">
                        <span class="detail-label">
                            <?php if ($order['status'] == 'completed' || $order['status'] == 'delivered'): ?>
                                <i class="fas fa-check-circle text-success"></i> Completed By:
                            <?php else: ?>
                                <i class="fas fa-times-circle text-danger"></i> Cancelled By:
                            <?php endif; ?>
                        </span>
                        <span class="detail-value">
                            <strong>
                                <i class="fas fa-user-check me-1"></i>
                                <?php echo htmlspecialchars($order['completed_by_username'] ?? 'Unknown'); ?>
                            </strong>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Status Update Card (visible to admin, manager, cashier) -->
        <?php if (hasRole(['admin', 'manager', 'cashier'])): ?>
        <div class="card">
            <div class="card-header">
                <i class="fas fa-edit"></i> Update Order Status
            </div>
            <div class="card-body">
                <form method="post" class="status-form" onsubmit="return confirmStatusChange()">
                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                    
                    <div class="form-group">
                        <label for="status">Change Order Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>🕐 Pending</option>
                            <option value="confirmed" <?php echo $order['status'] == 'confirmed' ? 'selected' : ''; ?>>✅ Confirm Order</option>
                            <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>👨‍🍳 Preparing Food</option>
                            <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>📦 Ready for Pickup</option>
                            <option value="out_for_delivery" <?php echo $order['status'] == 'out_for_delivery' ? 'selected' : ''; ?>>🚚 Out for Delivery</option>
                            <option value="delivered" <?php echo $order['status'] == 'delivered' ? 'selected' : ''; ?>>🏠 Delivered</option>
                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>✅ Completed</option>
                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                        </select>
                        
                        <!-- Preparation Time Info -->
                        <?php if ($order['status'] == 'preparing'): ?>
                        <div class="alert alert-info mt-3 small">
                            <i class="fas fa-clock me-2"></i>
                            <strong>Estimated Time:</strong> <?php echo $estimated_time; ?> minutes
                            <br><small>Based on order items. Can be adjusted in Settings.</small>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Live Preview of Customer Notification -->
                        <div class="alert alert-info mt-3 small" id="notificationPreview">
                            <i class="fas fa-bell me-2"></i>
                            <strong>Customer will be notified:</strong>
                            <p class="mb-0 mt-1" id="previewMessage">
                                <?php
                                switch($order['status']) {
                                    case 'pending':
                                        echo "⏳ Your order is pending confirmation";
                                        break;
                                    case 'confirmed':
                                        echo "✅ Your order has been confirmed";
                                        break;
                                    case 'preparing':
                                        echo "👨‍🍳 Your order is being prepared (Est. {$estimated_time} min)";
                                        break;
                                    case 'ready':
                                        echo "📦 Your order is ready for pickup";
                                        break;
                                    case 'out_for_delivery':
                                        echo "🚚 Your order is out for delivery";
                                        break;
                                    case 'delivered':
                                        echo "🏠 Your order has been delivered";
                                        break;
                                    case 'completed':
                                        echo "✅ Your order is complete";
                                        break;
                                    case 'cancelled':
                                        echo "❌ Your order has been cancelled";
                                        break;
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_status" class="btn-update">
                        <i class="fas fa-sync-alt"></i> Update Status
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Cancellation Reason (if cancelled) -->
    <?php if ($order['status'] == 'cancelled' && $order['cancellation_reason']): ?>
        <div class="cancelled-reason">
            <strong><i class="fas fa-info-circle me-2"></i>Cancellation Reason:</strong>
            <p><?php echo nl2br(htmlspecialchars($order['cancellation_reason'])); ?></p>
        </div>
    <?php endif; ?>

    <!-- Financial Summary -->
    <div class="card" style="margin-bottom: 20px;">
        <div class="card-header">
            <i class="fas fa-calculator"></i> Financial Summary
        </div>
        <div class="card-body">
            <div class="details-list">
                <?php
                $subtotal = $order['total_amount'] - $order['service_fee'];
                ?>
                <div class="detail-item">
                    <span class="detail-label">Subtotal:</span>
                    <span class="detail-value">₱<?php echo number_format($subtotal, 2); ?></span>
                </div>
                
                <?php if ($order['service_fee'] > 0): ?>
                <div class="detail-item">
                    <span class="detail-label">Service Fee:</span>
                    <span class="detail-value">₱<?php echo number_format($order['service_fee'], 2); ?></span>
                </div>
                <?php endif; ?>
                
                <div class="detail-item">
                    <span class="detail-label">Total Amount:</span>
                    <span class="detail-value amount-highlight">₱<?php echo number_format($order['total_amount'], 2); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <h5 class="items-title">
        <i class="fas fa-boxes"></i> Order Items
    </h5>
    
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td>
                        <?php if ($item['image']): ?>
                            <img src="../../assets/images/<?php echo $item['image']; ?>" class="product-image" alt="<?php echo htmlspecialchars($item['name']); ?>">
                        <?php endif; ?>
                        <?php echo htmlspecialchars($item['name']); ?>
                    </td>
                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td>₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <a href="../../print_receipt.php?id=<?php echo $order['id']; ?>&daily=<?php echo urlencode($formatted_daily); ?>" class="btn-print" target="_blank">
            <i class="fas fa-print"></i> Print Receipt
        </a>
        <a href="/staff-dashboard" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// Scroll to top function
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Status change preview
const statusSelect = document.getElementById('status');
const previewMessage = document.getElementById('previewMessage');

if (statusSelect) {
    statusSelect.addEventListener('change', function() {
        const status = this.value;
        let message = '';
        const estimatedTime = <?php echo $estimated_time; ?>;
        
        switch(status) {
            case 'pending':
                message = "⏳ Your order is pending confirmation";
                break;
            case 'confirmed':
                message = "✅ Your order has been confirmed and will be prepared soon";
                break;
            case 'preparing':
                message = `👨‍🍳 Your order is now being prepared by our kitchen staff (Est. ${estimatedTime} min)`;
                break;
            case 'ready':
                message = "📦 Your order is now ready for pickup. Please come to the store";
                break;
            case 'out_for_delivery':
                message = "🚚 Your order is now out for delivery!";
                break;
            case 'delivered':
                message = "🏠 Your order has been delivered. Thank you!";
                break;
            case 'completed':
                message = "✅ Your order is complete. Thank you!";
                break;
            case 'cancelled':
                message = "❌ Your order has been cancelled";
                break;
        }
        
        previewMessage.textContent = message;
    });
}

// Confirm status change
function confirmStatusChange() {
    const status = document.getElementById('status').value;
    const currentStatus = '<?php echo $order['status']; ?>';
    const username = '<?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?>';
    
    if (status === currentStatus) {
        alert('Please select a different status to update.');
        return false;
    }
    
    let message = '';
    switch(status) {
        case 'confirmed':
            message = `Confirm this order?\n\nThis will notify the customer that their order is confirmed.`;
            break;
        case 'preparing':
            message = `Mark order as PREPARING?\n\nThis will start the preparation timer.\n\nThis will notify the customer that their food is being prepared.`;
            break;
        case 'ready':
            message = `Mark order as READY FOR PICKUP?\n\nThis will stop the preparation timer and record actual preparation time.\n\nThis will notify the customer to pickup their order.`;
            break;
        case 'out_for_delivery':
            message = `Mark order as OUT FOR DELIVERY?\n\nA tracking number will be generated.`;
            break;
        case 'delivered':
            message = `Mark order as DELIVERED?\n\nThis will complete the order.`;
            break;
        case 'completed':
            message = `Mark this order as COMPLETED?\n\nThis will record "${username}" as the processor.`;
            break;
        case 'cancelled':
            message = `⚠️ Cancel this order?\n\nThis will record "${username}" as the one who cancelled it.`;
            break;
    }
    
    return confirm(message);
}

// Show/hide scroll button
window.addEventListener('scroll', function() {
    const scrollButton = document.getElementById('scrollToTop');
    if (window.scrollY > 300) {
        scrollButton.classList.add('show');
    } else {
        scrollButton.classList.remove('show');
    }
});

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(function(alert) {
        alert.style.transition = 'opacity 0.5s ease';
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.style.display = 'none';
        }, 500);
    });
}, 5000);
</script>

<?php include '../../includes/footer.php'; ?>