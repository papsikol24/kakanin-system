<?php
// Set Philippines timezone FIRST before anything else
date_default_timezone_set('Asia/Manila');

require_once 'includes/config.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: /login');
    exit;
}

$order_id = $_GET['id'] ?? 0;
$customer_id = $_SESSION['customer_id'];

// Verify order belongs to this customer
$stmt = $pdo->prepare("SELECT * FROM tbl_orders WHERE id = ? AND customer_id = ?");
$stmt->execute([$order_id, $customer_id]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['error'] = "Order not found.";
    header('Location: /dashboard');
    exit;
}

// Fetch order items
$items = $pdo->prepare("
    SELECT oi.*, p.name, p.image 
    FROM tbl_order_items oi 
    JOIN tbl_products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$items->execute([$order_id]);
$items = $items->fetchAll();

// Calculate totals
$subtotal = 0;
foreach ($items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$service_fee = $order['service_fee'] ?? 20.00;
$total = $subtotal + $service_fee;

// Get current Philippines time for display
$current_time = date('F d, Y h:i A');
$order_time = date('F d, Y h:i A', strtotime($order['order_date']));

// Check if this was a retry
$wasRetry = isset($_SESSION['order_retry']) ? $_SESSION['order_retry'] : false;
unset($_SESSION['order_retry']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Order Success · Kakanin System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 70px;
        }
        .navbar {
            background: linear-gradient(135deg, #008080, #20b2aa) !important;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
        }
        .success-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 800px;
            margin: 2rem auto;
        }
        .success-icon {
            font-size: 5rem;
            color: #28a745;
            margin-bottom: 1rem;
            animation: scaleIn 0.5s ease;
        }
        @keyframes scaleIn {
            0% { transform: scale(0); }
            80% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .order-number {
            background: #e8f4f4;
            padding: 1rem;
            border-radius: 50px;
            font-size: 1.2rem;
            color: #008080;
            border: 2px dashed #008080;
            margin: 1rem 0;
            animation: slideUp 0.5s ease 0.2s both;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .retry-badge {
            background: #ffc107;
            color: #333;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 1rem;
            font-weight: 600;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .btn-dashboard {
            background: #008080;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
            transition: all 0.3s;
        }
        .btn-dashboard:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,128,128,0.3);
            color: white;
        }
        .btn-menu {
            background: #ff8c00;
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.8rem 2rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin: 0.5rem;
            transition: all 0.3s;
        }
        .btn-menu:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,140,0,0.3);
            color: white;
        }
        
        /* ===== SCROLLABLE TABLE STYLES ===== */
        .table-container {
            margin: 2rem 0;
            background: #f8f9fa;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            scrollbar-color: #008080 #f0f0f0;
        }
        
        .table-scroll::-webkit-scrollbar {
            height: 8px;
        }
        
        .table-scroll::-webkit-scrollbar-track {
            background: #f0f0f0;
            border-radius: 10px;
        }
        
        .table-scroll::-webkit-scrollbar-thumb {
            background: #008080;
            border-radius: 10px;
        }
        
        .scroll-hint {
            display: none;
            text-align: center;
            color: #008080;
            font-size: 0.8rem;
            padding: 0.5rem;
            animation: fadeInOut 2s infinite;
        }
        
        @keyframes fadeInOut {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 1; }
        }
        
        .scroll-hint i {
            margin-right: 0.3rem;
            animation: slideLeft 1.5s infinite;
        }
        
        @keyframes slideLeft {
            0%, 100% { transform: translateX(0); }
            50% { transform: translateX(-5px); }
        }
        
        @media (max-width: 768px) {
            .scroll-hint {
                display: block;
            }
        }
        
        .items-table {
            min-width: 600px;
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th {
            background: #008080;
            color: white;
            font-weight: 500;
            padding: 1rem;
            white-space: nowrap;
        }
        
        .items-table td {
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid #dee2e6;
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #008080;
        }
        
        .product-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .product-name {
            font-weight: 500;
            color: #333;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .summary-box {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px dashed #dee2e6;
        }
        
        .summary-row:last-child {
            border-bottom: none;
            font-weight: 700;
            color: #008080;
            font-size: 1.2rem;
        }
        
        .delivery-info {
            background: #e8f4f4;
            border-left: 4px solid #008080;
            padding: 1rem;
            border-radius: 10px;
            margin: 1.5rem 0;
        }
        
        .reference-number {
            font-family: monospace;
            background: #f0f0f0;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.9rem;
        }
        
        .time-badge {
            background: #17a2b8;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            display: inline-block;
            margin-top: 0.5rem;
        }
        
        .time-badge i {
            margin-right: 0.3rem;
        }
        
        @media (max-width: 768px) {
            .success-card {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .product-image {
                width: 40px;
                height: 40px;
            }
            
            .btn-dashboard, .btn-menu {
                display: block;
                width: 100%;
                margin: 0.5rem 0;
            }
        }
        
        @media (max-width: 480px) {
            .success-card {
                padding: 1rem;
            }
            
            .order-number {
                font-size: 1rem;
                padding: 0.8rem;
            }
            
            .product-info {
                gap: 0.5rem;
            }
            
            .product-name {
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/dashboard">
                <i class="fas fa-store me-2"></i>Kakanin Customer
            </a>
            <div class="ms-auto">
                <a href="/logout?type=customer" class="btn btn-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="success-card">
            <?php if ($wasRetry): ?>
                <div class="retry-badge">
                    <i class="fas fa-check-circle me-2"></i>
                    Order Completed After Retry!
                </div>
            <?php endif; ?>

            <div class="text-center">
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <h2>Order Placed Successfully!</h2>
                <p class="text-muted">Thank you for your order. We'll start preparing it right away.</p>
                
                <div class="order-number">
                    <strong>Order #<?php echo $order['id']; ?></strong>
                </div>
                
                <!-- Display current Philippines time -->
                <div class="time-badge">
                    <i class="fas fa-clock"></i> <?php echo $current_time; ?> (Philippines Time)
                </div>
            </div>

            <?php 
            $daily_number = isset($_GET['daily']) ? htmlspecialchars($_GET['daily']) : '';
            $payment_ref = isset($_GET['ref']) ? htmlspecialchars($_GET['ref']) : '';
            $payment_method = isset($_GET['method']) ? htmlspecialchars($_GET['method']) : $order['payment_method'];
            ?>

            <!-- Order Items Table -->
            <div class="table-container">
                <div class="scroll-hint">
                    <i class="fas fa-arrow-left"></i>
                    <span>Swipe to see more</span>
                    <i class="fas fa-arrow-right"></i>
                </div>
                <div class="table-scroll">
                    <table class="items-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-center">Price</th>
                                <th class="text-center">Qty</th>
                                <th class="text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <div class="product-info">
                                        <?php if ($item['image']): ?>
                                            <img src="/assets/images/<?php echo $item['image']; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="product-image">
                                        <?php endif; ?>
                                        <span class="product-name"><?php echo htmlspecialchars($item['name']); ?></span>
                                    </div>
                                </td>
                                <td class="text-center">₱<?php echo number_format($item['price'], 2); ?></td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-right">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="summary-box">
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>₱<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Service Fee:</span>
                    <span>₱<?php echo number_format($service_fee, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Total Amount:</span>
                    <span>₱<?php echo number_format($total, 2); ?></span>
                </div>
            </div>

            <!-- Delivery Information -->
            <?php if ($order['delivery_address']): ?>
            <div class="delivery-info">
                <h6 class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>Delivery Address:</h6>
                <p class="mb-1"><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
                <?php if ($order['delivery_phone']): ?>
                    <p class="mb-0"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($order['delivery_phone']); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Order Details -->
            <div class="row mt-4">
                <div class="col-md-6">
                    <p><strong>Order Date:</strong> <?php echo $order_time; ?></p>
                    <p><strong>Payment Method:</strong> <?php echo ucfirst($payment_method); ?></p>
                </div>
                <div class="col-md-6">
                    <?php if ($daily_number): ?>
                        <p><strong>Daily Order:</strong> <?php echo $daily_number; ?></p>
                    <?php endif; ?>
                    <?php if ($payment_ref): ?>
                        <p><strong>Reference Number:</strong> <span class="reference-number"><?php echo $payment_ref; ?></span></p>
                    <?php endif; ?>
                    <p><strong>Status:</strong> 
                        <span class="badge <?php 
                            echo $order['status'] == 'completed' ? 'bg-success' : 
                                ($order['status'] == 'pending' ? 'bg-warning' : 'bg-secondary'); 
                        ?>"><?php echo ucfirst($order['status']); ?></span>
                    </p>
                </div>
            </div>

            <!-- Cash on Delivery Reminder -->
            <?php if ($payment_method == 'cash' && $order['status'] == 'pending'): ?>
                <div class="alert alert-warning mt-3">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    Please prepare the <strong>exact amount</strong> of <strong>₱<?php echo number_format($total, 2); ?></strong> for delivery.
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <hr class="my-4">
            
            <div class="text-center">
                <a href="/dashboard" class="btn-dashboard">
                    <i class="fas fa-home me-2"></i>Go to Dashboard
                </a>
                <a href="/menu" class="btn-menu">
                    <i class="fas fa-utensils me-2"></i>Order Again
                </a>
            </div>

            <?php if ($wasRetry): ?>
                <p class="text-muted small text-center mt-3">
                    <i class="fas fa-info-circle"></i>
                    Your previous attempt failed, but this order was successfully placed.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Add touch feedback for scroll hint
        document.addEventListener('DOMContentLoaded', function() {
            const scrollContainer = document.querySelector('.table-scroll');
            const scrollHint = document.querySelector('.scroll-hint');
            
            if (scrollContainer && scrollHint) {
                scrollContainer.addEventListener('scroll', function() {
                    scrollHint.style.opacity = '0.3';
                });
                
                let timeout;
                scrollContainer.addEventListener('scroll', function() {
                    clearTimeout(timeout);
                    timeout = setTimeout(function() {
                        scrollHint.style.opacity = '1';
                    }, 3000);
                });
            }
        });
    </script>
</body>
</html>