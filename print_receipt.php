<?php
require_once 'includes/config.php';
requireLogin(); // staff only

$id = $_GET['id'] ?? 0;

// ===== FIXED QUERY - INCLUDES delivery_address =====
$order = $pdo->prepare("SELECT o.*, c.name AS customer_name, c.phone, u.username AS cashier_name 
                        FROM tbl_orders o 
                        LEFT JOIN tbl_customers c ON o.customer_id = c.id 
                        LEFT JOIN tbl_users u ON o.created_by = u.id 
                        WHERE o.id = ?");
$order->execute([$id]);
$order = $order->fetch();

if (!$order) {
    die("Order not found.");
}

$items = $pdo->prepare("SELECT oi.*, p.name FROM tbl_order_items oi JOIN tbl_products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$items->execute([$id]);
$items = $items->fetchAll();

// Business details
$business_name = "JEN'S KAKANIN";
$business_address = "Brgy 83-B Cogon San Jose, Tacloban City";
$business_contact = "0935 606 2163";
$logo_path = "assets/images/owner.jpg";

// Format order number
$order_number = "CUS-" . str_pad($order['id'], 8, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt <?php echo $order_number; ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <style>
        /* Reset all margins/padding to zero */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        /* Dynamic height based on content */
        @page {
            size: 80mm auto; /* Slightly wider for mobile */
            margin: 5mm;
        }
        
        html, body {
            width: 100%;
            max-width: 80mm;
            margin: 0 auto;
            background: white;
            font-family: 'Courier New', monospace, 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            padding: 5px;
            -webkit-text-size-adjust: 100%; /* Prevents font scaling on mobile */
        }
        
        /* Hide print buttons when printing */
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
                max-width: 100%;
            }
        }
        
        /* Mobile-optimized receipt layout */
        .receipt-container {
            width: 100%;
            max-width: 70mm;
            margin: 0 auto;
            background: white;
            padding: 5px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 8px;
            border-bottom: 2px dashed #333;
            padding-bottom: 8px;
        }
        
        .header img {
            max-width: 50mm;
            max-height: 20mm;
            object-fit: contain;
            margin-bottom: 5px;
            border: 2px solid #008080;
            border-radius: 10px;
            padding: 3px;
            background: white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .header h2 {
            font-size: 16px;
            font-weight: bold;
            margin: 3px 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .header p {
            font-size: 10px;
            margin: 2px 0;
            color: #333;
        }
        
        .official {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            margin: 8px 0;
            letter-spacing: 2px;
            text-transform: uppercase;
            background: #f0f0f0;
            padding: 4px;
            border-radius: 4px;
        }
        
        .divider {
            border-top: 1px dashed #333;
            margin: 8px 0;
        }
        
        .receipt-info {
            margin: 8px 0;
            font-size: 11px;
        }
        
        .receipt-info p {
            margin: 4px 0;
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .receipt-info strong {
            min-width: 80px;
            display: inline-block;
        }
        
        .receipt-number {
            font-weight: bold;
            font-size: 14px;
            text-align: center;
            margin: 10px 0;
            background: #e8f4f4;
            padding: 6px;
            border-radius: 50px;
            border: 1px dashed #008080;
        }
        
        /* Delivery address styling */
        .delivery-address {
            font-size: 11px;
            margin: 8px 0;
            padding: 8px;
            background: #f8f9fa;
            border-left: 4px solid #008080;
            border-radius: 5px;
        }
        
        .delivery-address p {
            margin: 2px 0;
        }
        
        .delivery-address strong {
            color: #008080;
        }
        
        /* ===== FIXED: ITEMS TABLE WITH WRAPPING TEXT ===== */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            margin: 8px 0;
            table-layout: fixed; /* Fixed layout for better control */
        }
        
        .items-table th {
            text-align: left;
            padding: 6px 3px;
            border-bottom: 2px solid #333;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        
        .items-table td {
            padding: 4px 3px;
            border-bottom: 1px dotted #999;
            vertical-align: top;
            word-wrap: break-word; /* Allow text to wrap */
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        /* ===== FIXED: Item name column with wrapping ===== */
        .items-table .item-name {
            width: 45%; /* Give more space for product names */
            max-width: 45%;
            word-wrap: break-word;
            white-space: normal; /* Allow wrapping */
            overflow: visible;
            text-overflow: clip;
            line-height: 1.3;
        }
        
        .items-table .item-qty {
            width: 15%;
            text-align: center;
            white-space: nowrap;
        }
        
        .items-table .item-price {
            width: 20%;
            text-align: right;
            white-space: nowrap;
        }
        
        .items-table .item-total {
            width: 20%;
            text-align: right;
            white-space: nowrap;
        }
        
        /* Product name styling */
        .product-name {
            font-weight: normal;
            display: block;
            width: 100%;
        }
        
        /* Premium item indicator (optional) */
        .premium-item {
            font-weight: bold;
            color: #d35400;
        }
        
        /* Totals section */
        .totals {
            margin: 10px 0;
            padding: 8px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .totals .row {
            display: flex;
            justify-content: space-between;
            margin: 4px 0;
            font-size: 11px;
        }
        
        .totals .grand-total {
            font-weight: bold;
            font-size: 14px;
            color: #008080;
            border-top: 2px solid #333;
            padding-top: 6px;
            margin-top: 4px;
        }
        
        /* Footer */
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 8px;
            border-top: 2px dashed #333;
            font-size: 10px;
        }
        
        .footer p {
            margin: 3px 0;
        }
        
        .footer .thank-you {
            font-size: 14px;
            font-weight: bold;
            color: #008080;
            margin: 8px 0;
        }
        
        /* Button Container - Mobile friendly */
        .no-print {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 10px;
            width: 100%;
            padding: 0 10px;
        }
        
        .button-group {
            display: flex;
            justify-content: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .no-print button {
            padding: 12px 20px;
            font-size: 14px;
            cursor: pointer;
            border: none;
            border-radius: 50px;
            transition: all 0.2s;
            min-width: 120px;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            -webkit-tap-highlight-color: transparent;
        }
        
        .no-print button:active {
            transform: scale(0.95);
        }
        
        .btn-print {
            background: #008080;
            color: white;
        }
        
        .btn-print:hover {
            background: #006666;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,128,128,0.4);
        }
        
        .btn-close {
            background: #dc3545;
            color: white;
        }
        
        .btn-close:hover {
            background: #bb2d3b;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(220,53,69,0.4);
        }
        
        /* Payment method badges */
        .payment-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .payment-cash {
            background: #28a745;
            color: white;
        }
        
        .payment-gcash {
            background: #0057e7;
            color: white;
        }
        
        .payment-paymaya {
            background: #ff4d4d;
            color: white;
        }
        
        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 50px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .status-pending {
            background: #ffc107;
            color: #333;
        }
        
        .status-completed {
            background: #28a745;
            color: white;
        }
        
        .status-cancelled {
            background: #dc3545;
            color: white;
        }
        
        /* Reference number */
        .ref-number {
            background: #e8f4f4;
            padding: 5px 10px;
            border-radius: 50px;
            font-family: monospace;
            font-size: 12px;
            color: #008080;
            border: 1px dashed #008080;
            display: inline-block;
            margin: 5px 0;
            word-break: break-all;
        }
        
        /* Mobile specific */
        @media (max-width: 480px) {
            html, body {
                font-size: 11px;
            }
            
            .no-print button {
                padding: 10px 16px;
                min-width: 100px;
                font-size: 13px;
            }
            
            .button-group {
                gap: 8px;
            }
            
            .items-table {
                font-size: 10px;
            }
            
            .items-table th {
                font-size: 9px;
                padding: 4px 2px;
            }
            
            .items-table td {
                padding: 3px 2px;
            }
            
            .items-table .item-name {
                width: 40%;
            }
        }
        
        /* Touch-friendly improvements */
        .no-print button {
            touch-action: manipulation;
        }
        
        /* Print-specific styles */
        @media print {
            .no-print {
                display: none;
            }
            
            .payment-badge,
            .status-badge {
                background: none !important;
                color: black !important;
                border: 1px solid black;
            }
            
            .ref-number {
                border: 1px solid black;
                background: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <!-- Header with Logo -->
        <div class="header">
            <?php if (file_exists($logo_path)): ?>
                <img src="<?php echo $logo_path; ?>" alt="Jen's Kakanin">
            <?php endif; ?>
            <h2><?php echo $business_name; ?></h2>
            <p><?php echo $business_address; ?></p>
            <p>Tel: <?php echo $business_contact; ?></p>
        </div>

        <!-- Official Receipt Label -->
        <div class="official">OFFICIAL RECEIPT</div>

        <!-- Receipt Number -->
        <div class="receipt-number">
            <?php echo $order_number; ?>
        </div>

        <!-- Order Information -->
        <div class="receipt-info">
            <p><strong>Order #:</strong> <span><?php echo $order_number; ?></span></p>
            <p><strong>Date:</strong> <span><?php echo date('m/d/Y, h:i:s A', strtotime($order['order_date'])); ?></span></p>
            <p><strong>Cashier:</strong> <span><?php echo htmlspecialchars($order['cashier_name'] ?? 'System'); ?></span></p>
            <p><strong>Customer:</strong> <span><?php echo htmlspecialchars($order['customer_name'] ?? 'Walk-in'); ?></span></p>
            <?php if ($order['phone']): ?>
                <p><strong>Phone:</strong> <span><?php echo htmlspecialchars($order['phone']); ?></span></p>
            <?php endif; ?>
            <p><strong>Payment:</strong> 
                <span class="payment-badge payment-<?php echo $order['payment_method']; ?>">
                    <?php echo strtoupper($order['payment_method']); ?>
                </span>
            </p>
            <?php if ($order['gcash_ref']): ?>
                <p><strong>GCash Ref:</strong> <span class="ref-number"><?php echo $order['gcash_ref']; ?></span></p>
            <?php endif; ?>
            <?php if ($order['paymaya_ref']): ?>
                <p><strong>PayMaya Ref:</strong> <span class="ref-number"><?php echo $order['paymaya_ref']; ?></span></p>
            <?php endif; ?>
            <p><strong>Status:</strong> 
                <span class="status-badge status-<?php echo $order['status']; ?>">
                    <?php echo strtoupper($order['status']); ?>
                </span>
            </p>
        </div>

        <!-- Delivery Address -->
        <?php if (!empty($order['delivery_address'])): ?>
        <div class="delivery-address">
            <p><strong>DELIVERY ADDRESS:</strong></p>
            <p><?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?></p>
            <?php if ($order['delivery_phone']): ?>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['delivery_phone']); ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="divider"></div>

        <!-- ===== FIXED: Items Table with Full Product Names ===== -->
        <table class="items-table">
            <thead>
                <tr>
                    <th class="item-name">Item</th>
                    <th class="item-qty">Qty</th>
                    <th class="item-price">Price</th>
                    <th class="item-total">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): 
                    // Check if product is premium (price >= 250)
                    $is_premium = ($item['price'] >= 250);
                    $name_class = $is_premium ? 'premium-item' : '';
                ?>
                <tr>
                    <td class="item-name">
                        <span class="product-name <?php echo $name_class; ?>">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </span>
                    </td>
                    <td class="item-qty"><?php echo $item['quantity']; ?></td>
                    <td class="item-price">₱<?php echo number_format($item['price'], 2); ?></td>
                    <td class="item-total">₱<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="divider"></div>

        <!-- Totals -->
        <div class="totals">
            <?php
            $subtotal = $order['total_amount'] - ($order['service_fee'] ?? 0);
            ?>
            <div class="row">
                <span>Subtotal:</span>
                <span>₱<?php echo number_format($subtotal, 2); ?></span>
            </div>
            
            <?php if (($order['service_fee'] ?? 0) > 0): ?>
            <div class="row">
                <span>Service Fee:</span>
                <span>₱<?php echo number_format($order['service_fee'], 2); ?></span>
            </div>
            <?php endif; ?>
            
            <div class="row grand-total">
                <span>TOTAL AMOUNT:</span>
                <span>₱<?php echo number_format($order['total_amount'], 2); ?></span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p class="thank-you">THANK YOU, COME AGAIN!</p>
            <p>Served by: Jen's Kakanin Team</p>
            <p><?php echo date('Y-m-d H:i:s'); ?></p>
            <p style="font-size: 8px; margin-top: 5px;">This is a computer-generated receipt.</p>
            <p style="font-size: 8px;">No signature required.</p>
        </div>
    </div>

    <!-- Action Buttons - Mobile friendly -->
    <div class="no-print">
        <div class="button-group">
            <button class="btn-print" onclick="window.print()">
                <i class="fas fa-print"></i> Print
            </button>
            <button class="btn-close" onclick="window.close()">
                <i class="fas fa-times"></i> Close
            </button>
        </div>
        <p style="font-size: 10px; color: #666; margin-top: 10px; text-align: center;">
            Tap outside to go back
        </p>
    </div>

    <!-- Font Awesome for icons (optional) -->
    <script src="https://kit.fontawesome.com/your-kit-id.js" crossorigin="anonymous"></script>
    <script>
        // Auto-close on outside tap for mobile
        document.addEventListener('touchstart', function(e) {
            if (!e.target.closest('.receipt-container') && !e.target.closest('.no-print')) {
                window.close();
            }
        });

        // Prevent zoom on double tap
        document.addEventListener('touchstart', function(e) {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, { passive: false });

        // Handle print button
        document.querySelector('.btn-print')?.addEventListener('click', function() {
            setTimeout(function() {
                window.print();
            }, 100);
        });
    </script>
</body>
</html>