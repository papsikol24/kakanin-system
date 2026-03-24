<?php
require_once 'includes/config.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: /login');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$customer_id = $_SESSION['customer_id'];

// Function to get estimated preparation time based on order items
function getEstimatedPreparationTime($pdo, $order_id) {
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
    
    return $max_time ?: 30;
}

// Verify order belongs to this customer
$stmt = $pdo->prepare("SELECT * FROM tbl_orders WHERE id = ? AND customer_id = ?");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    header('Location: /dashboard');
    exit;
}

// Define final states
$final_states = ['delivered', 'completed', 'cancelled'];
$is_final = in_array($order['status'], $final_states);

// Get order items
$items = $pdo->prepare("
    SELECT oi.*, p.name, p.image 
    FROM tbl_order_items oi 
    JOIN tbl_products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$items->execute([$order_id]);
$items = $items->fetchAll();

// Calculate estimated time
$estimated_time = getEstimatedPreparationTime($pdo, $order['id']);

// Calculate elapsed time and remaining time
$current_time = time();
$order_time = strtotime($order['order_date']);

// Check if preparation has started
$preparation_started = !is_null($order['preparation_started_at']);
$preparation_completed = !is_null($order['preparation_completed_at']);

if ($preparation_completed) {
    $start_time = strtotime($order['preparation_started_at']);
    $end_time = strtotime($order['preparation_completed_at']);
    $actual_time = round(($end_time - $start_time) / 60);
    $elapsed_seconds = ($end_time - $start_time);
    $remaining_seconds = 0;
    $progress = 100;
    $timer_display = "00:00";
} elseif ($preparation_started) {
    $start_time = strtotime($order['preparation_started_at']);
    $elapsed_seconds = $current_time - $start_time;
    $elapsed_minutes = round($elapsed_seconds / 60);
    $remaining_seconds = max(0, ($estimated_time * 60) - $elapsed_seconds);
    $remaining_minutes = floor($remaining_seconds / 60);
    $remaining_secs = $remaining_seconds % 60;
    $progress = min(100, round(($elapsed_seconds / ($estimated_time * 60)) * 100));
    $timer_display = sprintf("%02d:%02d", $remaining_minutes, $remaining_secs);
} else {
    $elapsed_seconds = 0;
    $remaining_seconds = $estimated_time * 60;
    $progress = 0;
    $timer_display = sprintf("%02d:00", $estimated_time);
}

include 'includes/customer_header.php';
?>

<style>
    /* ===== MOBILE-FIRST STYLES ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }

    .tracking-container {
        max-width: 100%;
        padding: 10px;
        animation: fadeIn 0.5s ease;
    }

    @media (min-width: 768px) {
        .tracking-container {
            max-width: 900px;
            margin: 20px auto;
            padding: 15px;
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* ===== TRACKING HEADER ===== */
    .tracking-header {
        background: linear-gradient(135deg, #008080, #20b2aa);
        color: white;
        padding: 20px 15px;
        border-radius: 15px;
        margin-bottom: 15px;
        text-align: center;
        position: relative;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,128,128,0.2);
    }

    .tracking-header::before {
        content: '🚚';
        position: absolute;
        top: -10px;
        right: -10px;
        font-size: 5rem;
        opacity: 0.1;
        transform: rotate(15deg);
    }

    .tracking-header h1 {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    @media (min-width: 768px) {
        .tracking-header h1 {
            font-size: 2rem;
        }
    }

    .tracking-header .order-number {
        font-size: 1rem;
        background: rgba(255,255,255,0.2);
        padding: 6px 15px;
        border-radius: 50px;
        display: inline-block;
        margin-bottom: 8px;
    }

    .live-indicator {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(255,255,255,0.2);
        padding: 4px 12px;
        border-radius: 50px;
        font-size: 0.8rem;
    }

    .live-indicator .pulse {
        width: 8px;
        height: 8px;
        background: #28a745;
        border-radius: 50%;
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% { transform: scale(1); opacity: 1; }
        50% { transform: scale(1.2); opacity: 0.7; }
        100% { transform: scale(1); opacity: 1; }
    }

    /* ===== PROGRESS CARD ===== */
    .progress-card {
        background: white;
        border-radius: 15px;
        padding: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        margin-bottom: 15px;
        border-left: 4px solid #ff8c00;
    }

    @media (min-width: 768px) {
        .progress-card {
            padding: 20px;
        }
    }

    .progress-stats {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 15px;
    }

    @media (min-width: 480px) {
        .progress-stats {
            flex-direction: row;
            justify-content: space-around;
        }
    }

    .stat-item {
        text-align: center;
        flex: 1;
        padding: 5px;
        border-bottom: 1px solid #f0f0f0;
    }

    @media (min-width: 480px) {
        .stat-item {
            border-bottom: none;
        }
    }

    .stat-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 3px;
    }

    .stat-value {
        font-size: 1.3rem;
        font-weight: 700;
        color: #008080;
    }

    .timer-value {
        font-size: 1.5rem;
        font-weight: 800;
        color: #ff8c00;
        font-family: monospace;
        letter-spacing: 1px;
        animation: timerPulse 1s infinite;
    }

    @keyframes timerPulse {
        0% { opacity: 1; }
        50% { opacity: 0.8; }
        100% { opacity: 1; }
    }

    .progress-bar-container {
        width: 100%;
        height: 12px;
        background: #e9ecef;
        border-radius: 50px;
        overflow: hidden;
        margin: 15px 0;
        position: relative;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #008080, #20b2aa);
        border-radius: 50px;
        transition: width 0.5s ease;
        position: relative;
        overflow: hidden;
    }

    .progress-bar-fill::after {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(90deg, 
            rgba(255,255,255,0.1) 0%,
            rgba(255,255,255,0.3) 50%,
            rgba(255,255,255,0.1) 100%);
        animation: shimmer 2s infinite;
    }

    @keyframes shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }

    .progress-status {
        text-align: center;
        font-size: 0.9rem;
        font-weight: 600;
        color: #008080;
        margin-top: 10px;
        line-height: 1.4;
        word-break: break-word;
    }

    .progress-status small {
        font-size: 0.7rem;
        color: #6c757d;
        display: block;
        margin-top: 4px;
    }

    /* ===== TIMELINE - VERTICAL FOR MOBILE ===== */
    .timeline-card {
        background: white;
        border-radius: 15px;
        padding: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        margin-bottom: 15px;
    }

    .timeline-title {
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .timeline-title i {
        color: #008080;
        font-size: 1rem;
    }

    .timeline {
        position: relative;
        padding: 0;
    }

    .timeline::before {
        display: none;
    }

    .timeline-item {
        position: relative;
        padding: 10px 0 10px 60px;
        margin-bottom: 8px;
        min-height: 50px;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-dot {
        position: absolute;
        left: 15px;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: white;
        border: 3px solid #dee2e6;
        top: 50%;
        transform: translateY(-50%);
        z-index: 3;
        transition: all 0.3s ease;
    }

    .timeline-item.completed .timeline-dot {
        background: #28a745;
        border-color: #28a745;
        animation: bounce 0.5s ease;
    }

    .timeline-item.active .timeline-dot {
        background: #008080;
        border-color: #008080;
        box-shadow: 0 0 0 5px rgba(0,128,128,0.2);
        animation: pulse 1.5s infinite;
    }

    .timeline-item.cancelled .timeline-dot {
        background: #dc3545;
        border-color: #dc3545;
    }

    @keyframes bounce {
        0%, 100% { transform: translateY(-50%) scale(1); }
        50% { transform: translateY(-50%) scale(1.2); }
    }

    .timeline-content {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 12px;
        transition: all 0.3s ease;
    }

    .timeline-item.completed .timeline-content {
        background: #d4edda;
    }

    .timeline-item.active .timeline-content {
        background: #cce5ff;
        border-left: 4px solid #008080;
    }

    .timeline-item.cancelled .timeline-content {
        background: #f8d7da;
    }

    .timeline-time {
        font-size: 0.7rem;
        color: #6c757d;
        margin-bottom: 3px;
        transition: all 0.3s ease;
    }

    .timeline-title-text {
        font-weight: 600;
        color: #333;
        font-size: 0.85rem;
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .timeline-title-text i {
        font-size: 0.9rem;
    }

    .timeline-status {
        font-size: 0.65rem;
        color: #28a745;
        display: inline-block;
        padding: 2px 6px;
        background: rgba(40, 167, 69, 0.1);
        border-radius: 50px;
    }

    /* ===== ORDER DETAILS CARD ===== */
    .details-card {
        background: white;
        border-radius: 15px;
        padding: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        margin-bottom: 15px;
    }

    .details-grid {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .detail-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.85rem;
    }

    .detail-row:last-child {
        border-bottom: none;
    }

    .detail-label {
        color: #6c757d;
        font-weight: 500;
        flex: 0 0 100px;
    }

    .detail-value {
        color: #333;
        font-weight: 500;
        flex: 1;
        text-align: right;
        word-break: break-word;
    }

    .detail-value strong {
        color: #008080;
    }

    .tracking-number {
        font-family: monospace;
        background: #e8f4f4;
        padding: 4px 8px;
        border-radius: 50px;
        display: inline-block;
        color: #008080;
        font-size: 0.75rem;
    }

    /* ===== ITEMS CARD ===== */
    .items-card {
        background: white;
        border-radius: 15px;
        padding: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        margin-bottom: 15px;
    }

    .items-title {
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .items-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .item-row {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .item-row:last-child {
        border-bottom: none;
    }

    .item-image {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
        flex-shrink: 0;
        border: 1px solid #008080;
    }

    .item-details {
        flex: 1;
        min-width: 0;
    }

    .item-name {
        font-weight: 600;
        color: #333;
        font-size: 0.85rem;
        margin-bottom: 2px;
        white-space: normal;
        word-wrap: break-word;
    }

    .item-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        font-size: 0.75rem;
        color: #6c757d;
    }

    .item-meta span {
        background: #f0f0f0;
        padding: 2px 8px;
        border-radius: 50px;
    }

    .item-price {
        font-weight: 600;
        color: #008080;
        font-size: 0.85rem;
        white-space: nowrap;
        text-align: right;
        min-width: 70px;
    }

    /* ===== ACTION BUTTONS ===== */
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin: 20px 0 10px;
    }

    @media (min-width: 480px) {
        .action-buttons {
            flex-direction: row;
            gap: 10px;
        }
    }

    .btn-track, .btn-back {
        flex: 1;
        padding: 12px 16px;
        border: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
        text-align: center;
        -webkit-tap-highlight-color: transparent;
    }

    .btn-track {
        background: linear-gradient(135deg, #008080, #20b2aa);
        color: white;
    }

    .btn-track:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0,128,128,0.3);
    }

    .btn-back {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
    }

    .btn-back:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(108,117,125,0.3);
    }

    .btn-track:active, .btn-back:active {
        transform: scale(0.98);
    }

    .btn-track:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    /* ===== UPDATE TOAST ===== */
    .update-toast {
        position: fixed;
        bottom: 15px;
        right: 15px;
        left: 15px;
        background: #28a745;
        color: white;
        padding: 12px 16px;
        border-radius: 50px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        display: none;
        align-items: center;
        justify-content: center;
        gap: 8px;
        z-index: 9999;
        animation: slideInUp 0.3s ease;
        font-size: 0.85rem;
        max-width: 90%;
        margin: 0 auto;
    }

    @media (min-width: 768px) {
        .update-toast {
            left: auto;
            right: 20px;
            bottom: 20px;
            max-width: 300px;
            animation: slideInRight 0.3s ease;
        }
    }

    .update-toast.show {
        display: flex;
    }

    @keyframes slideInUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    /* ===== FINAL STATE MESSAGE ===== */
    .final-state-message {
        background: #e7f3ff;
        border-left: 4px solid #008080;
        border-radius: 10px;
        padding: 12px 15px;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .final-state-message i {
        font-size: 1.3rem;
        color: #008080;
        flex-shrink: 0;
    }

    .final-state-message .message {
        flex: 1;
    }

    .final-state-message .message strong {
        display: block;
        font-size: 0.9rem;
        margin-bottom: 2px;
    }

    .final-state-message .message p {
        margin: 0;
        font-size: 0.8rem;
        color: #666;
    }

    .cancelled-badge {
        display: inline-block;
        background: #dc3545;
        color: white;
        padding: 5px 12px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.8rem;
        margin-bottom: 15px;
    }

    /* ===== LOADING SPINNER ===== */
    .loading-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* ===== STATUS UPDATE ANIMATION ===== */
    .status-updated {
        animation: statusUpdate 1s ease;
    }

    @keyframes statusUpdate {
        0% { background-color: #fff3cd; transform: scale(1.01); }
        50% { background-color: #ffe69c; }
        100% { background-color: transparent; transform: scale(1); }
    }

    /* ===== TOUCH OPTIMIZATIONS ===== */
    .btn-track, .btn-back, .timeline-item, .detail-row {
        -webkit-tap-highlight-color: transparent;
    }

    .btn-track:active, .btn-back:active {
        transform: scale(0.98);
    }

    .timeline-item:active {
        opacity: 0.9;
    }
</style>

<div class="tracking-container">
    <!-- Update Toast -->
    <div class="update-toast" id="updateToast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMessage">Order status updated!</span>
    </div>

    <!-- Header -->
    <div class="tracking-header">
        <h1><i class="fas fa-map-marker-alt"></i> Track Order</h1>
        <div class="order-number">#<?php echo $order['id']; ?></div>
        <div class="live-indicator">
            <span class="pulse"></span>
            <span>Live Tracking</span>
        </div>
    </div>

    <!-- Final State Message -->
    <?php if ($is_final): ?>
    <div class="final-state-message">
        <i class="fas fa-info-circle"></i>
        <div class="message">
            <strong>Order <?php echo $order['status']; ?></strong>
            <p>You can view the complete order details below</p>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($order['status'] == 'cancelled'): ?>
        <div class="cancelled-badge">
            <i class="fas fa-times-circle me-2"></i> This order has been cancelled
        </div>
    <?php endif; ?>

    <!-- Progress Bar (only for active orders) -->
    <?php if (!$is_final && $order['status'] != 'cancelled'): ?>
    <div class="progress-card">
        <div class="progress-stats">
            <div class="stat-item">
                <div class="stat-label">Est. Time</div>
                <div class="stat-value" id="estimatedTime"><?php echo $estimated_time; ?> min</div>
            </div>
            <div class="stat-item">
                <div class="stat-label">
                    <?php 
                    if ($preparation_completed) {
                        echo 'Actual';
                    } elseif ($preparation_started && $order['status'] == 'preparing') {
                        echo 'Remaining';
                    } elseif ($order['status'] == 'confirmed') {
                        echo 'Waiting';
                    } elseif ($order['status'] == 'ready') {
                        echo 'Ready';
                    } elseif ($order['status'] == 'out_for_delivery') {
                        echo 'On the way';
                    } else {
                        echo 'Status';
                    }
                    ?>
                </div>
                <div class="stat-value timer-value" id="timerDisplay">
                    <?php 
                    if ($preparation_completed) {
                        echo sprintf("%02d:00", $actual_time);
                    } elseif ($preparation_started && $order['status'] == 'preparing') {
                        echo $timer_display;
                    } elseif ($order['status'] == 'ready') {
                        echo 'Ready';
                    } elseif ($order['status'] == 'out_for_delivery') {
                        echo 'On way';
                    } elseif ($order['status'] == 'confirmed') {
                        echo '—';
                    } else {
                        echo '—';
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="progress-bar-container">
            <div class="progress-bar-fill" id="progressBar" style="width: <?php echo $progress; ?>%"></div>
        </div>

        <div class="progress-status" id="progressStatus">
            <?php
            $status_message = '';
            
            if ($preparation_started && !$preparation_completed && $order['status'] == 'preparing') {
                $status_message = '👨‍🍳 Your order is being prepared';
            } elseif ($preparation_completed && $order['status'] == 'ready') {
                $status_message = '📦 Ready for pickup';
            } elseif ($order['status'] == 'pending') {
                $status_message = '⏳ Waiting for confirmation';
            } elseif ($order['status'] == 'confirmed') {
                $status_message = '✅ Order confirmed';
            } elseif ($order['status'] == 'preparing') {
                $status_message = '👨‍🍳 Being prepared';
            } elseif ($order['status'] == 'ready') {
                $status_message = '📦 Ready for pickup';
            } elseif ($order['status'] == 'out_for_delivery') {
                $status_message = '🚚 Out for delivery';
            } elseif ($order['status'] == 'delivered') {
                $status_message = '🏠 Delivered. Thank you!';
            } elseif ($order['status'] == 'completed') {
                $status_message = '✅ Complete. Thank you!';
            } elseif ($order['status'] == 'cancelled') {
                $status_message = '❌ Cancelled';
            }
            
            echo $status_message;
            ?>
            <?php if ($preparation_started && !$preparation_completed && $order['status'] == 'preparing'): ?>
                <br><small>Started at <?php echo date('h:i A', strtotime($order['preparation_started_at'])); ?></small>
            <?php endif; ?>
            <?php if ($preparation_completed && $order['status'] == 'ready'): ?>
                <br><small>Completed in <?php echo $actual_time; ?> min</small>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Timeline - Vertical for Mobile -->
    <div class="timeline-card">
        <div class="timeline-title">
            <i class="fas fa-history"></i> Order Timeline
        </div>
        <div class="timeline" id="timeline">
            <?php
            $status_flow = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered'];
            $status_labels = [
                'pending' => 'Order Placed',
                'confirmed' => 'Confirmed',
                'preparing' => 'Preparing',
                'ready' => 'Ready for Pickup',
                'out_for_delivery' => 'Out for Delivery',
                'delivered' => 'Delivered'
            ];
            $status_icons = [
                'pending' => 'fa-clock',
                'confirmed' => 'fa-check-circle',
                'preparing' => 'fa-utensils',
                'ready' => 'fa-box',
                'out_for_delivery' => 'fa-truck',
                'delivered' => 'fa-home'
            ];
            $status_colors = [
                'pending' => '#ffc107',
                'confirmed' => '#007bff',
                'preparing' => '#fd7e14',
                'ready' => '#28a745',
                'out_for_delivery' => '#17a2b8',
                'delivered' => '#28a745'
            ];
            
            $current_status_index = array_search($order['status'], $status_flow);
            if ($current_status_index === false) $current_status_index = -1;
            
            foreach ($status_flow as $index => $status):
                $time_field = $status . '_at';
                if ($status == 'pending') $time_field = 'order_date';
                if ($status == 'delivered') $time_field = 'delivered_at';
                
                $time_value = $order[$time_field] ?? null;
                
                $is_completed = false;
                
                if ($status == 'pending') {
                    $is_completed = true;
                } elseif (!is_null($time_value)) {
                    $is_completed = true;
                } elseif ($current_status_index > $index) {
                    $is_completed = true;
                } elseif (($order['status'] == 'delivered' || $order['status'] == 'completed') && $index < count($status_flow) - 1) {
                    $is_completed = true;
                } elseif ($order['status'] == 'ready' && $status == 'preparing') {
                    $is_completed = true;
                } elseif ($order['status'] == 'out_for_delivery' && ($status == 'preparing' || $status == 'ready')) {
                    $is_completed = true;
                }
                
                $is_active = ($status == $order['status']) && !$is_final && $order['status'] != 'cancelled';
                $is_cancelled = ($order['status'] == 'cancelled');
                
                $item_class = '';
                if ($is_cancelled) {
                    $item_class = 'cancelled';
                } elseif ($is_completed) {
                    $item_class = 'completed';
                } elseif ($is_active) {
                    $item_class = 'active';
                }
                
                $time_display = '';
                
                if ($is_completed) {
                    if ($status == 'ready' || $status == 'preparing') {
                        $time_display = '—';
                    } else {
                        $time_display = $time_value ? date('h:i A', strtotime($time_value)) : '—';
                    }
                } else {
                    if ($status == 'preparing' && $order['status'] == 'preparing') {
                        $time_display = 'In Progress';
                    } else {
                        $time_display = 'Pending';
                    }
                }
            ?>
            <div class="timeline-item <?php echo $item_class; ?>" data-status="<?php echo $status; ?>" data-index="<?php echo $index; ?>">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <div class="timeline-time time-<?php echo $status; ?>"><?php echo $time_display; ?></div>
                    <div class="timeline-title-text">
                        <i class="fas <?php echo $status_icons[$status]; ?>" style="color: <?php echo $status_colors[$status]; ?>"></i>
                        <?php echo $status_labels[$status]; ?>
                    </div>
                    <?php if ($is_active): ?>
                        <div class="timeline-status">Current</div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Order Details -->
    <div class="details-card">
        <div class="timeline-title">
            <i class="fas fa-info-circle"></i> Order Details
        </div>
        <div class="details-grid">
            <div class="detail-row">
                <span class="detail-label">Order ID</span>
                <span class="detail-value"><strong>#<?php echo $order['id']; ?></strong></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Customer</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['customer_name'] ?: 'Walk-in'); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date</span>
                <span class="detail-value"><?php echo date('M d, h:i A', strtotime($order['order_date'])); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Payment</span>
                <span class="detail-value"><?php echo ucfirst($order['payment_method']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total</span>
                <span class="detail-value"><strong>₱<?php echo number_format($order['total_amount'], 2); ?></strong></span>
            </div>
            
            <?php if ($preparation_started): ?>
            <div class="detail-row">
                <span class="detail-label">Started</span>
                <span class="detail-value"><?php echo date('h:i A', strtotime($order['preparation_started_at'])); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($preparation_completed): ?>
            <div class="detail-row">
                <span class="detail-label">Completed</span>
                <span class="detail-value"><?php echo date('h:i A', strtotime($order['preparation_completed_at'])); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($order['tracking_number']): ?>
            <div class="detail-row">
                <span class="detail-label">Tracking</span>
                <span class="detail-value tracking-number" id="trackingNumber"><?php echo $order['tracking_number']; ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($order['delivery_address']): ?>
            <div class="detail-row">
                <span class="detail-label">Address</span>
                <span class="detail-value"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($order['delivery_phone']): ?>
            <div class="detail-row">
                <span class="detail-label">Contact</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['delivery_phone']); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Order Items - Mobile Optimized List -->
    <div class="items-card">
        <div class="items-title">
            <i class="fas fa-boxes"></i> Items
        </div>
        <div class="items-list">
            <?php foreach ($items as $item): ?>
            <div class="item-row">
                <?php if ($item['image']): ?>
                    <img src="/assets/images/<?php echo $item['image']; ?>" class="item-image">
                <?php else: ?>
                    <div class="item-image" style="background:#f0f0f0; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-image" style="color:#999;"></i>
                    </div>
                <?php endif; ?>
                <div class="item-details">
                    <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                    <div class="item-meta">
                        <span>Qty: <?php echo $item['quantity']; ?></span>
                        <span>@ ₱<?php echo number_format($item['price'], 2); ?></span>
                    </div>
                </div>
                <div class="item-price">
                    ₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="action-buttons">
        <?php if (!$is_final && $order['status'] != 'cancelled'): ?>
        <button class="btn-track" onclick="manualRefresh()" id="refreshBtn">
            <i class="fas fa-sync-alt"></i> Refresh Status
        </button>
        <?php endif; ?>
        <a href="/dashboard" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<script>
let orderId = <?php echo $order['id']; ?>;
let updateInterval;
let timerInterval;
let lastStatus = '<?php echo $order['status']; ?>';
let isFinal = <?php echo $is_final ? 'true' : 'false'; ?>;
let preparationStarted = <?php echo $preparation_started ? 'true' : 'false'; ?>;
let preparationCompleted = <?php echo $preparation_completed ? 'true' : 'false'; ?>;
let startTime = <?php echo $preparation_started ? strtotime($order['preparation_started_at']) : 'null'; ?>;
let estimatedTime = <?php echo $estimated_time; ?>;
let actualTime = <?php echo $actual_time ?? 0; ?>;

// ===== GET PROGRESS MESSAGE =====
function getProgressMessage(status, prepStarted, prepCompleted) {
    if (prepStarted && !prepCompleted && status === 'preparing') {
        return '👨‍🍳 Being prepared';
    } else if (prepCompleted && status === 'ready') {
        return '📦 Ready for pickup';
    } else {
        switch(status) {
            case 'pending': return '⏳ Waiting for confirmation';
            case 'confirmed': return '✅ Order confirmed';
            case 'preparing': return '👨‍🍳 Being prepared';
            case 'ready': return '📦 Ready for pickup';
            case 'out_for_delivery': return '🚚 Out for delivery';
            case 'delivered': return '🏠 Delivered. Thank you!';
            case 'completed': return '✅ Complete. Thank you!';
            case 'cancelled': return '❌ Cancelled';
            default: return 'Processing...';
        }
    }
}

// ===== UPDATE PROGRESS MESSAGE =====
function updateProgressMessage(status, prepStarted, prepCompleted, startTime) {
    const progressStatus = document.getElementById('progressStatus');
    if (!progressStatus) return;
    
    let message = getProgressMessage(status, prepStarted, prepCompleted);
    
    if (prepStarted && !prepCompleted && status === 'preparing' && startTime) {
        const startDate = new Date(startTime * 1000);
        const timeStr = startDate.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        message += `<br><small>Started at ${timeStr}</small>`;
    } else if (prepCompleted && status === 'ready' && actualTime > 0) {
        message += `<br><small>Completed in ${actualTime} min</small>`;
    }
    
    progressStatus.innerHTML = message;
    progressStatus.classList.add('status-updated');
    setTimeout(() => progressStatus.classList.remove('status-updated'), 1000);
}

// ===== REAL-TIME COUNTDOWN TIMER =====
function updateTimer() {
    if (preparationStarted && !preparationCompleted && !isFinal && lastStatus === 'preparing') {
        const now = Math.floor(Date.now() / 1000);
        const elapsed = now - startTime;
        const remaining = Math.max(0, (estimatedTime * 60) - elapsed);
        
        const minutes = Math.floor(remaining / 60);
        const seconds = remaining % 60;
        
        const timerDisplay = document.getElementById('timerDisplay');
        if (timerDisplay) {
            timerDisplay.textContent = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        }
        
        const progress = Math.min(100, Math.round((elapsed / (estimatedTime * 60)) * 100));
        const progressBar = document.getElementById('progressBar');
        if (progressBar) {
            progressBar.style.width = progress + '%';
        }
        
        if (remaining <= 0 && timerInterval) {
            clearInterval(timerInterval);
            timerInterval = null;
            if (timerDisplay) timerDisplay.textContent = '00:00';
            if (progressBar) progressBar.style.width = '100%';
        }
    }
}

// ===== UPDATE TIMELINE BASED ON STATUS =====
function updateTimeline(status, timestamps) {
    const timelineItems = document.querySelectorAll('.timeline-item');
    const statusFlow = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'delivered'];
    
    const currentIndex = statusFlow.indexOf(status);
    
    timelineItems.forEach((item, index) => {
        const itemIndex = parseInt(item.dataset.index);
        const itemStatus = statusFlow[itemIndex];
        
        item.classList.remove('completed', 'active', 'cancelled');
        
        let isCompleted = false;
        
        if (itemStatus === 'pending') {
            isCompleted = true;
        } else if (timestamps && timestamps[itemStatus]) {
            isCompleted = true;
        } else if (currentIndex > itemIndex) {
            isCompleted = true;
        } else if (status === 'ready' && itemStatus === 'preparing') {
            isCompleted = true;
        } else if (status === 'out_for_delivery' && (itemStatus === 'preparing' || itemStatus === 'ready')) {
            isCompleted = true;
        } else if (status === 'delivered' || status === 'completed') {
            if (itemStatus !== 'delivered') {
                isCompleted = true;
            }
        }
        
        if (isCompleted) {
            item.classList.add('completed');
        }
        
        if (itemStatus === status && !isFinal && status !== 'cancelled') {
            item.classList.add('active');
        }
        
        if (timestamps && timestamps[itemStatus]) {
            const timeEl = item.querySelector('.timeline-time');
            if (timeEl) {
                if (itemStatus === 'ready' || (itemStatus === 'preparing' && isCompleted)) {
                    timeEl.textContent = '—';
                } else if (itemStatus === 'preparing' && status === 'preparing' && !preparationCompleted) {
                    timeEl.textContent = 'In Progress';
                } else {
                    const date = new Date(timestamps[itemStatus]);
                    timeEl.textContent = date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                }
            }
        }
        
        item.classList.add('status-updated');
        setTimeout(() => item.classList.remove('status-updated'), 1000);
    });
}

// ===== FETCH LATEST ORDER STATUS =====
function fetchOrderStatus() {
    if (isFinal) return;
    
    const refreshBtn = document.getElementById('refreshBtn');
    if (refreshBtn) {
        refreshBtn.innerHTML = '<span class="loading-spinner"></span> Updating...';
        refreshBtn.disabled = true;
    }
    
    fetch('/api/track_order.php?id=' + orderId + '&t=' + Date.now())
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                
                if (order.status !== lastStatus) {
                    console.log('Status changed to:', order.status);
                    
                    lastStatus = order.status;
                    
                    if (order.preparation_started_at && !preparationStarted) {
                        preparationStarted = true;
                        startTime = new Date(order.preparation_started_at).getTime() / 1000;
                    }
                    
                    if (order.preparation_completed_at && !preparationCompleted) {
                        preparationCompleted = true;
                    }
                    
                    const timestamps = {
                        pending: order.order_date,
                        confirmed: order.confirmed_at,
                        preparing: order.preparation_started_at,
                        ready: order.ready_for_pickup_at,
                        out_for_delivery: order.out_for_delivery_at,
                        delivered: order.delivered_at
                    };
                    
                    updateTimeline(order.status, timestamps);
                    updateProgressMessage(order.status, preparationStarted, preparationCompleted, startTime);
                    showToast('Status updated to: ' + order.status, 'success');
                    
                    if (order.status === 'preparing' && preparationStarted && !preparationCompleted) {
                        if (timerInterval) clearInterval(timerInterval);
                        timerInterval = setInterval(updateTimer, 1000);
                        updateTimer();
                    } else if (order.status !== 'preparing' && timerInterval) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                    }
                    
                    if (order.status === 'delivered' || order.status === 'completed') {
                        setTimeout(() => location.reload(), 2000);
                    }
                }
            } else {
                showToast('Failed to update', 'error');
            }
            
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Status';
                refreshBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (refreshBtn) {
                refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> Refresh Status';
                refreshBtn.disabled = false;
            }
            showToast('Connection error', 'error');
        });
}

// ===== MANUAL REFRESH =====
function manualRefresh() {
    fetchOrderStatus();
}

// ===== SHOW TOAST =====
function showToast(message, type = 'success') {
    const toast = document.getElementById('updateToast');
    const toastMessage = document.getElementById('toastMessage');
    
    toast.style.background = type === 'success' ? '#28a745' : '#dc3545';
    toastMessage.textContent = message;
    toast.classList.add('show');
    
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}

// ===== START TIMER =====
if (preparationStarted && !preparationCompleted && !isFinal && lastStatus === 'preparing') {
    timerInterval = setInterval(updateTimer, 1000);
    updateTimer();
}

// ===== AUTO-REFRESH =====
if (!isFinal) {
    updateInterval = setInterval(() => {
        if (!document.hidden) {
            fetchOrderStatus();
        }
    }, 3000);
}

// ===== INITIAL FETCH =====
if (!isFinal) {
    setTimeout(() => fetchOrderStatus(), 1000);
}

// ===== CLEAN UP =====
window.addEventListener('beforeunload', () => {
    if (updateInterval) clearInterval(updateInterval);
    if (timerInterval) clearInterval(timerInterval);
});

// Update timer immediately
if (preparationStarted && !preparationCompleted && lastStatus === 'preparing') {
    updateTimer();
}
</script>

<?php include 'includes/footer.php'; ?>