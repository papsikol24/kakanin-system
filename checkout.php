<?php
require_once 'includes/config.php';
require_once 'includes/daily_counter.php';

if (!isset($_SESSION['customer_id'])) {
    header('Location: /login');
    exit;
}

if (empty($_SESSION['cart'])) {
    $_SESSION['error'] = "Your cart is empty.";
    header('Location: /menu');
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Get customer details
$stmt = $pdo->prepare("SELECT * FROM tbl_customers WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

$error = '';
$gcash_number = '09356062163';
$paymaya_number = '09356062163'; // Updated PayMaya number to match GCash
$qr_gcash_path = 'assets/images/qrcode.png';
$qr_paymaya_path = 'assets/images/qrmaya.jpg';

// ===== TIER-BASED ORDER RULES =====
define('SERVICE_FEE', 20.00);

function cartHasPremiumItems($cart) {
    foreach ($cart as $item) {
        if ($item['price'] >= 250) {
            return true;
        }
    }
    return false;
}

function getMinimumOrder($cart) {
    return cartHasPremiumItems($cart) ? 1 : 20;
}

function getMaximumOrder($cart) {
    return cartHasPremiumItems($cart) ? 999999 : 300;
}

function getOrderTypeDescription($cart) {
    return cartHasPremiumItems($cart) ? "Contains Premium Items (No Maximum Limit)" : "Regular/Budget Items Only";
}

function getTotalPieces($cart) {
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['quantity'];
    }
    return $total;
}

function generatePaymentRef($method) {
    $date = date('Ymd');
    $random = strtoupper(substr(uniqid(), -5));
    return $method == 'gcash' ? "GCASH-{$date}-{$random}" : "PAYMAYA-{$date}-{$random}";
}

// Complete Tacloban barangays
$barangays = [
    'Barangay 2', 'Barangay 5', 'Barangay 5-A', 'Barangay 6', 'Barangay 6-A',
    'Barangay 7', 'Barangay 8', 'Barangay 8-A', 'Barangay 12 (Palanog Resettlement)',
    'Barangay 13', 'Barangay 14', 'Barangay 15', 'Barangay 16', 'Barangay 17',
    'Barangay 18', 'Barangay 19', 'Barangay 20', 'Barangay 21', 'Barangay 21-A',
    'Barangay 22', 'Barangay 23', 'Barangay 23-A', 'Barangay 24', 'Barangay 25',
    'Barangay 26', 'Barangay 27', 'Barangay 28', 'Barangay 29', 'Barangay 30',
    'Barangay 31', 'Barangay 32', 'Barangay 33', 'Barangay 34', 'Barangay 35',
    'Barangay 35-A', 'Barangay 36', 'Barangay 36-A', 'Barangay 37', 'Barangay 37-A',
    'Barangay 38', 'Barangay 39', 'Barangay 40', 'Barangay 41', 'Barangay 42',
    'Barangay 42-A', 'Barangay 43', 'Barangay 43-A', 'Barangay 43-B', 'Barangay 44',
    'Barangay 44-A', 'Barangay 45', 'Barangay 46', 'Barangay 47', 'Barangay 48',
    'Barangay 48-A', 'Barangay 48-B', 'Barangay 49', 'Barangay 50', 'Barangay 50-A',
    'Barangay 50-B', 'Barangay 51', 'Barangay 51-A', 'Barangay 52', 'Barangay 53',
    'Barangay 54', 'Barangay 54-A', 'Barangay 56', 'Barangay 56-A', 'Barangay 57',
    'Barangay 58', 'Barangay 59', 'Barangay 59-A', 'Barangay 59-B', 'Barangay 60',
    'Barangay 60-A', 'Barangay 61', 'Barangay 62', 'Barangay 62-A', 'Barangay 62-B',
    'Barangay 63', 'Barangay 64', 'Barangay 65', 'Barangay 66', 'Barangay 66-A',
    'Barangay 67', 'Barangay 68', 'Barangay 69', 'Barangay 70', 'Barangay 71',
    'Barangay 72', 'Barangay 73', 'Barangay 74', 'Barangay 75', 'Barangay 76',
    'Barangay 77', 'Barangay 78 (Marasbaras)', 'Barangay 79 (Marasbaras)',
    'Barangay 80 (Marasbaras)', 'Barangay 81 (Marasbaras)', 'Barangay 82 (Marasbaras)',
    'Barangay 83 (San Jose)', 'Barangay 83-A (San Jose)', 'Barangay 83-B',
    'Barangay 83-C (San Jose)', 'Barangay 84 (San Jose)', 'Barangay 85 (San Jose)',
    'Barangay 86', 'Barangay 87', 'Barangay 88', 'Barangay 89', 'Barangay 90 (San Jose)',
    'Barangay 91 (Abucay)', 'Barangay 92 (Apitong)', 'Barangay 93 (Bagacay)',
    'Barangay 94 (Tigbao)', 'Barangay 94-A', 'Barangay 95 (Caibaan)', 'Barangay 95-A (Caibaan)',
    'Barangay 96 (Calanipawan)', 'Barangay 97 (Cabalawan)', 'Barangay 98 (Camansinay)',
    'Barangay 99 (Diit)', 'Barangay 100 (San Roque)', 'Barangay 101 (New Kawayan)',
    'Barangay 102 (Old Kawayan)', 'Barangay 103 (Palanog)', 'Barangay 103-A (San Paglaum)',
    'Barangay 104 (Salvacion)', 'Barangay 105 (Suhi)', 'Barangay 106 (Santo Niño)',
    'Barangay 107 (Santa Elena)', 'Barangay 108 (Tagapuro)', 'Barangay 109 (V & G Subd.)',
    'Barangay 109-A', 'Barangay 110 (Utap)', 'El Reposo (Barangays 55 & 55A)',
    'Libertad (Barangays 1 & 4)', 'Nula-Tula (Bgys. 3 & 3A)'
];

// Get current cart data
$totalPieces = getTotalPieces($_SESSION['cart']);
$minOrder = getMinimumOrder($_SESSION['cart']);
$maxOrder = getMaximumOrder($_SESSION['cart']);
$orderType = getOrderTypeDescription($_SESSION['cart']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $payment_method = $_POST['payment_method'] ?? 'cash';
    $barangay = trim($_POST['barangay'] ?? '');
    $landmark = trim($_POST['landmark'] ?? '');
    $delivery_phone = trim($_POST['delivery_phone'] ?? '');
    $payment_ref = ($payment_method != 'cash') ? generatePaymentRef($payment_method) : null;
    $screenshot = $_FILES['screenshot'] ?? null;
    $screenshot_name = null;

    // Calculate total pieces and subtotal
    $totalPieces = getTotalPieces($_SESSION['cart']);
    $subtotal = 0;
    foreach ($_SESSION['cart'] as $id => $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }

    // ===== TIER-BASED ORDER VALIDATION =====
    $minOrder = getMinimumOrder($_SESSION['cart']);
    $maxOrder = getMaximumOrder($_SESSION['cart']);
    
    if ($totalPieces < $minOrder) {
        $error = "Minimum order is " . $minOrder . " pieces for " . $orderType . ". You only have $totalPieces pieces in your cart.";
    } elseif ($totalPieces > $maxOrder) {
        $error = "Maximum order is " . $maxOrder . " pieces for " . $orderType . ". You have $totalPieces pieces in your cart. Please reduce your order.";
    }

    // Validate delivery information
    if (empty($error)) {
        if (empty($barangay)) {
            $error = "Please select your barangay.";
        } elseif (empty($delivery_phone)) {
            $error = "Contact number is required.";
        } elseif (!preg_match('/^[0-9]{10}$/', $delivery_phone)) {
            $error = "Please enter a valid 10-digit phone number.";
        } elseif ($delivery_phone[0] !== '9') {
            $error = "Phone number must start with 9.";
        }
    }

    // Validate payment screenshot for GCash/PayMaya
    if (empty($error) && $payment_method != 'cash') {
        if (!$screenshot || $screenshot['error'] != UPLOAD_ERR_OK) {
            $error = "Please upload a screenshot of your payment.";
        } else {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
            $maxSize = 5 * 1024 * 1024;

            if (!in_array($screenshot['type'], $allowedTypes)) {
                $error = "Screenshot must be JPG, PNG, or GIF.";
            } elseif ($screenshot['size'] > $maxSize) {
                $error = "Screenshot size must be less than 5MB.";
            } else {
                $extension = pathinfo($screenshot['name'], PATHINFO_EXTENSION);
                $screenshot_name = uniqid() . '_' . time() . '.' . $extension;
                $targetDir = 'assets/uploads/screenshots/';

                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }

                $targetPath = $targetDir . $screenshot_name;
                if (!move_uploaded_file($screenshot['tmp_name'], $targetPath)) {
                    $error = "Failed to upload screenshot.";
                }
            }
        }
    }

    if (empty($error)) {
        // Force close any existing transaction
        try {
            while ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Exception $e) {
            // Ignore
        }
        
        $pdo->beginTransaction();
        try {
            // Check stock availability first
            foreach ($_SESSION['cart'] as $id => $item) {
                $stock_check = $pdo->prepare("SELECT stock FROM tbl_products WHERE id = ? FOR UPDATE");
                $stock_check->execute([$id]);
                $current_stock = $stock_check->fetchColumn();
                
                if ($current_stock < $item['quantity']) {
                    throw new Exception("Insufficient stock for product ID: $id. Available: $current_stock");
                }
            }
            
            $delivery_address = $barangay;
            if (!empty($landmark)) {
                $delivery_address .= ', near ' . $landmark;
            }
            $delivery_address .= ', Tacloban City';

            $total_amount = $subtotal + SERVICE_FEE;

            // Insert order
            $sql = "INSERT INTO tbl_orders 
                    (customer_id, customer_name, total_amount, service_fee, payment_method, delivery_address, delivery_phone, status, created_by";
            
            $params = [$customer_id, $customer['name'], $total_amount, SERVICE_FEE, $payment_method, $delivery_address, $delivery_phone, 'pending', null];
            $placeholders = "?, ?, ?, ?, ?, ?, ?, ?, ?";

            if ($payment_method == 'gcash') {
                $sql .= ", gcash_ref, gcash_screenshot";
                $placeholders .= ", ?, ?";
                $params[] = $payment_ref;
                $params[] = $screenshot_name;
            } elseif ($payment_method == 'paymaya') {
                $sql .= ", paymaya_ref, paymaya_screenshot";
                $placeholders .= ", ?, ?";
                $params[] = $payment_ref;
                $params[] = $screenshot_name;
            }

            $sql .= ") VALUES (" . $placeholders . ")";

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $order_id = $pdo->lastInsertId();
            
            // ===== GENERATE DAILY ORDER NUMBER (ONLY ONCE!) =====
            $daily_order_number = getNextDailyOrderNumber($pdo);
            $formatted_daily_number = "ORD-" . date('Y-m-d') . "-" . str_pad($daily_order_number, 4, '0', STR_PAD_LEFT);
            
            // Store daily order number in session for display
            if (!isset($_SESSION['daily_order_map'])) {
                $_SESSION['daily_order_map'] = [];
            }
            if (!isset($_SESSION['daily_order_map'][date('Y-m-d')])) {
                $_SESSION['daily_order_map'][date('Y-m-d')] = [];
            }
            $_SESSION['daily_order_map'][date('Y-m-d')][$daily_order_number] = $order_id;

            // Insert order items and update stock
            foreach ($_SESSION['cart'] as $id => $item) {
                // Insert order item
                $stmt = $pdo->prepare("INSERT INTO tbl_order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$order_id, $id, $item['quantity'], $item['price']]);

                // Get current stock
                $stock_check = $pdo->prepare("SELECT stock FROM tbl_products WHERE id = ? FOR UPDATE");
                $stock_check->execute([$id]);
                $current_stock = $stock_check->fetchColumn();
                $new_stock = $current_stock - $item['quantity'];
                
                // Update stock
                $update = $pdo->prepare("UPDATE tbl_products SET stock = ? WHERE id = ?");
                $update->execute([$new_stock, $id]);

                // Log inventory change
                $log = $pdo->prepare("INSERT INTO tbl_inventory_logs (product_id, user_id, change_type, quantity_changed, previous_stock, new_stock) VALUES (?, NULL, 'subtract', ?, ?, ?)");
                $log->execute([$id, $item['quantity'], $current_stock, $new_stock]);
            }

            // Create customer notification
            $notif_stmt = $pdo->prepare("INSERT INTO tbl_notifications (customer_id, order_id, title, message, type) VALUES (?, ?, 'Order Placed', ?, 'order_update')");
            $notif_message = "Your order #{$order_id} (Daily #{$formatted_daily_number}) has been placed successfully. Total pieces: {$totalPieces}. Service fee: ₱" . number_format(SERVICE_FEE, 2);
            $notif_stmt->execute([$customer_id, $order_id, $notif_message]);

            // Create staff notification
            try {
                $notif_sql = "INSERT INTO tbl_staff_notifications (order_id, is_read) VALUES (?, 0)";
                $notif_stmt = $pdo->prepare($notif_sql);
                $notif_stmt->execute([$order_id]);
            } catch (Exception $e) {
                error_log("Failed to create staff notification: " . $e->getMessage());
            }

            // Clear cart from database
            if (function_exists('clearCartFromDatabase')) {
                clearCartFromDatabase($pdo, $customer_id);
            }
            
            $pdo->commit();
            
            // Clear the cart session
            unset($_SESSION['cart']);
            
            // Set success message
            $_SESSION['success'] = "Order placed successfully! Order #{$order_id}\nDaily Order: {$formatted_daily_number}\nTotal Pieces: {$totalPieces}\nSubtotal: ₱" . number_format($subtotal, 2) . "\nService Fee: ₱" . number_format(SERVICE_FEE, 2) . "\nTotal: ₱" . number_format($total_amount, 2);
            
            // Redirect to success page
            header('Location: /order-success.php?id=' . $order_id . '&daily=' . urlencode($formatted_daily_number) . ($payment_ref ? '&ref=' . $payment_ref . '&method=' . $payment_method : ''));
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Checkout failed: " . $e->getMessage();
            error_log("Checkout error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Checkout · Kakanin System</title>
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
        .checkout-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 20px auto;
        }
        @media (min-width: 768px) {
            .checkout-card {
                padding: 2rem;
            }
        }
        .order-type-badge {
            display: inline-block;
            background: #008080;
            color: white;
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            margin-bottom: 15px;
        }
        .order-rules {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 15px;
            border-radius: 10px;
            margin: 15px 0;
            font-size: 0.85rem;
        }
        .pieces-counter {
            font-size: 1rem;
            font-weight: 600;
            color: #008080;
            background: #e8f4f4;
            padding: 6px 12px;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 10px;
        }
        .order-summary {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #008080;
        }
        .table {
            width: 100%;
            margin-bottom: 10px;
            font-size: 0.8rem;
        }
        .table td {
            padding: 6px 0;
            border: none;
        }
        .total-row {
            font-weight: 700;
            color: #008080;
            font-size: 1rem;
        }
        .service-fee-info {
            background: #e8f4f4;
            border-left: 4px solid #008080;
            padding: 10px 12px;
            border-radius: 10px;
            margin: 15px 0;
            font-size: 0.8rem;
        }
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 5px;
            font-size: 0.85rem;
        }
        .form-control, .form-select {
            border-radius: 50px;
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9rem;
            transition: all 0.3s;
            width: 100%;
        }
        .form-control:focus, .form-select:focus {
            border-color: #008080;
            box-shadow: 0 0 0 3px rgba(0,128,128,0.1);
            outline: none;
        }
        .phone-input-group {
            position: relative;
        }
        .phone-prefix {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: white;
            font-weight: 600;
            z-index: 10;
            background: #008080;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.8rem;
        }
        .phone-input-group .form-control {
            padding-left: 90px;
        }
        .payment-info, .upload-section {
            background: #e8f4f4;
            border-left: 4px solid #008080;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            display: none;
        }
        .payment-number {
            font-size: 1rem;
            font-weight: 600;
            color: #008080;
            text-align: center;
            margin: 10px 0;
        }
        .qr-code {
            max-width: 150px;
            margin: 10px auto;
            display: block;
            border: 2px solid white;
            border-radius: 10px;
        }
        .btn-place-order {
            background: linear-gradient(135deg, #008080, #20b2aa);
            color: white;
            border-radius: 50px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            width: 100%;
            transition: all 0.3s;
            margin-top: 15px;
        }
        .btn-place-order:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,128,128,0.3);
        }
        .btn-place-order:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .back-link {
            margin-top: 15px;
            text-align: center;
        }
        .back-link a {
            color: #008080;
            text-decoration: none;
            font-size: 0.85rem;
        }
        .alert {
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        @media (max-width: 767px) {
            .checkout-card {
                padding: 1rem;
            }
            .phone-prefix {
                font-size: 0.75rem;
                padding: 3px 8px;
            }
            .phone-input-group .form-control {
                padding-left: 85px;
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
                <a href="/cart" class="btn btn-light btn-sm me-2">
                    <i class="fas fa-shopping-cart"></i> Back to Cart
                </a>
                <a href="/logout?type=customer" class="btn btn-light btn-sm">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="checkout-card">
            <h2 class="text-center mb-4">
                <i class="fas fa-credit-card me-2"></i>Checkout
            </h2>
            
            <?php 
            $totalPieces = getTotalPieces($_SESSION['cart']);
            $minOrder = getMinimumOrder($_SESSION['cart']);
            $maxOrder = getMaximumOrder($_SESSION['cart']);
            $orderType = getOrderTypeDescription($_SESSION['cart']);
            $subtotal = 0;
            foreach ($_SESSION['cart'] as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            $totalWithFee = $subtotal + SERVICE_FEE;
            ?>

            <!-- Order Type Badge -->
            <div class="order-type-badge">
                <i class="fas fa-tag me-1"></i> <?php echo $orderType; ?>
            </div>

            <!-- Order Rules Notice -->
            <div class="order-rules">
                <i class="fas fa-info-circle me-2"></i>
                <strong>Order Requirements:</strong><br>
                Minimum: <strong><?php echo $minOrder; ?> pieces</strong><br>
                <?php if (cartHasPremiumItems($_SESSION['cart'])): ?>
                    <strong class="text-success">Maximum: <span class="text-success">NO LIMIT!</span></strong>
                <?php else: ?>
                    <strong>Maximum: <?php echo $maxOrder; ?> pieces</strong>
                <?php endif; ?>
                <br>
                <small>Current cart: <strong><?php echo $totalPieces; ?> pieces</strong></small>
            </div>

            <!-- Pieces Counter -->
            <div class="text-end mb-3">
                <span class="pieces-counter">
                    <i class="fas fa-boxes"></i> <?php echo $totalPieces; ?> pcs
                </span>
                <?php if ($totalPieces < $minOrder): ?>
                    <div class="text-danger small">Need <?php echo $minOrder - $totalPieces; ?> more</div>
                <?php endif; ?>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="post" id="checkoutForm" enctype="multipart/form-data">
                <!-- Order Summary -->
                <div class="order-summary">
                    <h5 class="mb-3"><i class="fas fa-shopping-cart me-2"></i>Order Summary</h5>
                    <table class="table">
                        <?php foreach ($_SESSION['cart'] as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td class="text-center">x<?php echo $item['quantity']; ?></td>
                            <td class="text-end">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Subtotal:</span>
                        <span>₱<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Service Fee:</span>
                        <span>₱<?php echo number_format(SERVICE_FEE, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between total-row mt-2">
                        <span>Total:</span>
                        <span>₱<?php echo number_format($totalWithFee, 2); ?></span>
                    </div>
                </div>

                <!-- Service Fee Notice -->
                <div class="service-fee-info">
                    <i class="fas fa-truck me-2"></i>
                    <strong>Delivery:</strong> ₱<?php echo SERVICE_FEE; ?>.00 fee applies
                </div>

                <!-- Barangay -->
                <div class="mb-3">
                    <label class="form-label">Barangay *</label>
                    <select class="form-select" name="barangay" required>
                        <option value="">Select your barangay...</option>
                        <?php foreach ($barangays as $b): ?>
                            <option value="<?php echo htmlspecialchars($b); ?>"><?php echo $b; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Landmark -->
                <div class="mb-3">
                    <label class="form-label">Landmark (optional)</label>
                    <input type="text" class="form-control" name="landmark" placeholder="e.g., near church, school, market">
                </div>

                <!-- Phone Number -->
                <div class="mb-3">
                    <label class="form-label">Contact Number *</label>
                    <div class="phone-input-group">
                        <span class="phone-prefix">+63</span>
                        <input type="tel" class="form-control" name="delivery_phone" 
                               value="<?php 
                                   $display_phone = '';
                                   if (!empty($customer['phone'])) {
                                       $digits = preg_replace('/[^0-9]/', '', $customer['phone']);
                                       if (strlen($digits) >= 10) {
                                           $display_phone = substr($digits, -10);
                                       }
                                   }
                                   echo htmlspecialchars($display_phone); 
                               ?>" 
                               required placeholder="9356062163" maxlength="10">
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" id="payment_method" name="payment_method" required>
                        <option value="cash">Cash on Delivery</option>
                        <option value="gcash">GCash</option>
                        <option value="paymaya">PayMaya</option>
                    </select>
                </div>

                <!-- GCash Info -->
                <div id="gcashInfo" class="payment-info">
                    <p class="mb-2"><i class="fas fa-info-circle me-2"></i>Pay via GCash:</p>
                    <img src="/assets/images/qrcode.png" alt="GCash QR" class="qr-code img-fluid">
                    <p class="payment-number"><i class="fas fa-mobile-alt me-2"></i><?php echo $gcash_number; ?></p>
                </div>

                <!-- PayMaya Info (Updated with same number) -->
                <div id="paymayaInfo" class="payment-info">
                    <p class="mb-2"><i class="fas fa-info-circle me-2"></i>Pay via PayMaya:</p>
                    <img src="/assets/images/qrmaya.jpg" alt="PayMaya QR" class="qr-code img-fluid">
                    <p class="payment-number"><i class="fas fa-mobile-alt me-2"></i><?php echo $paymaya_number; ?></p>
                </div>

                <!-- Screenshot Upload -->
                <div id="uploadSection" class="upload-section">
                    <label class="form-label">Payment Screenshot *</label>
                    <input type="file" class="form-control" name="screenshot" accept="image/jpeg,image/png,image/gif">
                    <small class="text-muted">Max 5MB. JPG, PNG, GIF only.</small>
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn-place-order" 
                        id="submitBtn"
                        <?php echo ($totalPieces < $minOrder) ? 'disabled' : ''; ?>>
                    <i class="fas fa-check-circle me-2"></i>
                    Place Order (<?php echo $totalPieces; ?> pcs)
                </button>
            </form>
            
            <div class="back-link">
                <a href="/cart">
                    <i class="fas fa-arrow-left me-1"></i>Back to Cart
                </a>
            </div>
        </div>
    </div>

    <script>
        // Payment method toggle
        document.getElementById('payment_method').addEventListener('change', function() {
            var gcashInfo = document.getElementById('gcashInfo');
            var paymayaInfo = document.getElementById('paymayaInfo');
            var uploadSection = document.getElementById('uploadSection');
            var screenshotInput = document.querySelector('input[name="screenshot"]');
            
            gcashInfo.style.display = 'none';
            paymayaInfo.style.display = 'none';
            uploadSection.style.display = 'none';
            if (screenshotInput) screenshotInput.required = false;
            
            if (this.value === 'gcash') {
                gcashInfo.style.display = 'block';
                uploadSection.style.display = 'block';
                if (screenshotInput) screenshotInput.required = true;
            } else if (this.value === 'paymaya') {
                paymayaInfo.style.display = 'block';
                uploadSection.style.display = 'block';
                if (screenshotInput) screenshotInput.required = true;
            }
        });

        // Phone number validation
        document.querySelector('input[name="delivery_phone"]').addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });

        // Form validation
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            
            const totalPieces = <?php echo $totalPieces; ?>;
            const minPieces = <?php echo $minOrder; ?>;
            
            if (totalPieces < minPieces) {
                e.preventDefault();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Place Order';
                alert('Minimum order is ' + minPieces + ' pieces. You have ' + totalPieces + ' pieces.');
                return false;
            }
            
            const phone = document.querySelector('input[name="delivery_phone"]').value;
            if (phone.length !== 10) {
                e.preventDefault();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Place Order';
                alert('Please enter a valid 10-digit phone number');
                return false;
            }
            
            if (phone[0] !== '9') {
                e.preventDefault();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-check-circle"></i> Place Order';
                alert('Phone number must start with 9');
                return false;
            }
        });

        // Trigger change on page load to show correct payment info if pre-selected
        window.addEventListener('DOMContentLoaded', function() {
            const paymentMethod = document.getElementById('payment_method');
            if (paymentMethod.value !== 'cash') {
                paymentMethod.dispatchEvent(new Event('change'));
            }
        });
    </script>
</body>
</html>