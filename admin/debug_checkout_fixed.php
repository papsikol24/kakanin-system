<?php
// Maximum error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../includes/config.php';
requireLogin();
requireRole('admin');

// Handle fix actions
$fix_message = '';
$fix_error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Fix 1: Reset stuck transactions
    if (isset($_POST['reset_transactions'])) {
        try {
            $rolled_back = 0;
            while ($pdo->inTransaction()) {
                $pdo->rollBack();
                $rolled_back++;
            }
            $fix_message = "✅ Rolled back $rolled_back stuck transaction(s)";
        } catch (Exception $e) {
            $fix_error = "❌ Error: " . $e->getMessage();
        }
    }
    
    // Fix 2: Reset daily counter
    if (isset($_POST['reset_counter'])) {
        require_once '../includes/daily_counter.php';
        if (function_exists('resetDailyCounter')) {
            resetDailyCounter($pdo);
            $fix_message = "✅ Daily counter reset to 1";
        } else {
            // Manual reset
            $today = date('Y-m-d');
            $pdo->prepare("
                INSERT INTO tbl_daily_counters (counter_date, order_counter, inventory_log_counter)
                VALUES (?, 1, 1)
                ON DUPLICATE KEY UPDATE order_counter = 1, inventory_log_counter = 1
            ")->execute([$today]);
            $fix_message = "✅ Daily counter manually reset to 1";
        }
    }
    
    // Fix 3: Clear cart for specific customer
    if (isset($_POST['clear_customer_cart']) && !empty($_POST['customer_id'])) {
        $customer_id = (int)$_POST['customer_id'];
        try {
            $pdo->prepare("DELETE FROM tbl_carts WHERE customer_id = ?")->execute([$customer_id]);
            $fix_message = "✅ Cart cleared for customer ID: $customer_id";
        } catch (Exception $e) {
            $fix_error = "❌ Error: " . $e->getMessage();
        }
    }
    
    // Fix 4: Fix order status
    if (isset($_POST['fix_order_status']) && !empty($_POST['order_id'])) {
        $order_id = (int)$_POST['order_id'];
        $new_status = $_POST['new_status'] ?? 'pending';
        
        try {
            $pdo->prepare("UPDATE tbl_orders SET status = ? WHERE id = ?")->execute([$new_status, $order_id]);
            $fix_message = "✅ Order #$order_id status updated to: $new_status";
        } catch (Exception $e) {
            $fix_error = "❌ Error: " . $e->getMessage();
        }
    }
    
    // Fix 5: Restore stock for cancelled orders
    if (isset($_POST['restore_stock']) && !empty($_POST['order_id'])) {
        $order_id = (int)$_POST['order_id'];
        $pdo->beginTransaction();
        try {
            // Get order items
            $items = $pdo->prepare("SELECT * FROM tbl_order_items WHERE order_id = ?");
            $items->execute([$order_id]);
            $order_items = $items->fetchAll();
            
            $restored_count = 0;
            foreach ($order_items as $item) {
                $pdo->prepare("
                    UPDATE tbl_products SET stock = stock + ? WHERE id = ?
                ")->execute([$item['quantity'], $item['product_id']]);
                $restored_count++;
            }
            
            // Update order status to cancelled
            $pdo->prepare("UPDATE tbl_orders SET status = 'cancelled' WHERE id = ?")->execute([$order_id]);
            
            $pdo->commit();
            $fix_message = "✅ Restored stock for $restored_count items in Order #$order_id";
        } catch (Exception $e) {
            $pdo->rollBack();
            $fix_error = "❌ Error: " . $e->getMessage();
        }
    }
}

// Get system status
$system_status = [];

// Check database connection
try {
    $pdo->query("SELECT 1");
    $system_status['db'] = ['status' => 'ok', 'message' => 'Database connected'];
} catch (Exception $e) {
    $system_status['db'] = ['status' => 'error', 'message' => $e->getMessage()];
}

// Check for stuck transactions
$system_status['transactions'] = ['status' => 'ok', 'message' => 'No stuck transactions'];
try {
    if ($pdo->inTransaction()) {
        $system_status['transactions'] = ['status' => 'warning', 'message' => 'Active transaction detected'];
    }
} catch (Exception $e) {
    // Ignore
}

// Get today's counter
$today = date('Y-m-d');
$counter = $pdo->prepare("SELECT * FROM tbl_daily_counters WHERE counter_date = ?");
$counter->execute([$today]);
$counter_data = $counter->fetch();

// Get stuck orders (pending for > 1 hour)
$stuck_orders = $pdo->query("
    SELECT id, customer_name, order_date, status 
    FROM tbl_orders 
    WHERE status = 'pending' 
    AND order_date < DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ORDER BY order_date DESC
")->fetchAll();

// Get customers with large carts
$large_carts = $pdo->query("
    SELECT c.id, c.name, COUNT(*) as cart_items, SUM(quantity) as total_qty
    FROM tbl_carts ca
    JOIN tbl_customers c ON ca.customer_id = c.id
    GROUP BY ca.customer_id
    HAVING total_qty > 50
    ORDER BY total_qty DESC
")->fetchAll();

// Get all customers for dropdown
$customers = $pdo->query("SELECT id, name FROM tbl_customers ORDER BY name")->fetchAll();

include '../includes/header.php';
?>

<style>
    .debug-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        overflow: hidden;
        border: 1px solid #e0e0e0;
    }
    
    .debug-header {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .debug-header.warning {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #333;
    }
    
    .debug-header.danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }
    
    .debug-body {
        padding: 20px;
    }
    
    .status-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .status-item {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        border-left: 4px solid;
    }
    
    .status-item.ok { border-left-color: #28a745; }
    .status-item.warning { border-left-color: #ffc107; }
    .status-item.error { border-left-color: #dc3545; }
    
    .status-label {
        font-size: 0.8rem;
        color: #6c757d;
        text-transform: uppercase;
        margin-bottom: 5px;
    }
    
    .status-value {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
    }
    
    .counter-box {
        background: linear-gradient(135deg, #fff3cd, #ffe69c);
        border-radius: 10px;
        padding: 15px;
        text-align: center;
        border: 1px solid #ffc107;
    }
    
    .counter-number {
        font-size: 3rem;
        font-weight: 700;
        color: #856404;
        line-height: 1;
    }
    
    .quick-fix-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }
    
    .fix-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        border: 1px solid #dee2e6;
        transition: all 0.3s;
    }
    
    .fix-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        border-color: #17a2b8;
    }
    
    .fix-title {
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .fix-title i {
        color: #17a2b8;
    }
    
    .btn-fix {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 8px 15px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.3s;
        width: 100%;
        margin-top: 10px;
    }
    
    .btn-fix:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(23,162,184,0.3);
    }
    
    .btn-fix.danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }
    
    .btn-fix.danger:hover {
        box-shadow: 0 5px 15px rgba(220,53,69,0.3);
    }
    
    .btn-fix.warning {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #333;
    }
    
    .log-list {
        max-height: 300px;
        overflow-y: auto;
        background: #2d2d2d;
        color: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        font-family: monospace;
        font-size: 0.8rem;
    }
    
    .log-entry {
        padding: 3px 0;
        border-bottom: 1px solid #444;
    }
    
    .log-time {
        color: #28a745;
        margin-right: 10px;
    }
    
    @media (max-width: 768px) {
        .debug-body {
            padding: 15px;
        }
        
        .quick-fix-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="container-fluid">
    <!-- Main Debug Tool -->
    <div class="debug-card">
        <div class="debug-header">
            <i class="fas fa-bug"></i>
            Checkout Debug & Fix Tool
        </div>
        
        <div class="debug-body">
            <?php if ($fix_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $fix_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($fix_error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $fix_error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- System Status -->
            <h5 class="mb-3"><i class="fas fa-heartbeat me-2"></i>System Status</h5>
            <div class="status-grid">
                <div class="status-item <?php echo $system_status['db']['status']; ?>">
                    <div class="status-label">Database</div>
                    <div class="status-value">
                        <i class="fas fa-database me-2"></i>
                        <?php echo $system_status['db']['message']; ?>
                    </div>
                </div>
                
                <div class="status-item <?php echo $system_status['transactions']['status']; ?>">
                    <div class="status-label">Transactions</div>
                    <div class="status-value">
                        <i class="fas fa-exchange-alt me-2"></i>
                        <?php echo $system_status['transactions']['message']; ?>
                    </div>
                </div>
                
                <div class="status-item ok">
                    <div class="status-label">Daily Counter</div>
                    <div class="status-value">
                        <i class="fas fa-counter me-2"></i>
                        <?php echo $counter_data ? $counter_data['order_counter'] : 'Not set'; ?>
                    </div>
                </div>
                
                <div class="status-item <?php echo empty($stuck_orders) ? 'ok' : 'warning'; ?>">
                    <div class="status-label">Stuck Orders</div>
                    <div class="status-value">
                        <i class="fas fa-clock me-2"></i>
                        <?php echo count($stuck_orders); ?> pending > 1 hour
                    </div>
                </div>
            </div>

            <!-- Daily Counter Display -->
            <div class="counter-box mb-4">
                <div class="small text-muted">Today's Order Counter</div>
                <div class="counter-number">
                    <?php echo $counter_data ? $counter_data['order_counter'] : '1'; ?>
                </div>
                <div class="small">
                    Next order: ORD-<?php echo $today; ?>-<?php echo str_pad(($counter_data ? $counter_data['order_counter'] : 1), 4, '0', STR_PAD_LEFT); ?>
                </div>
                <div class="small text-muted mt-2">
                    Last reset: <?php echo $counter_data ? date('M d, H:i', strtotime($counter_data['last_reset_at'])) : 'Never'; ?>
                </div>
            </div>

            <!-- Quick Fixes Grid -->
            <h5 class="mb-3 mt-4"><i class="fas fa-tools me-2"></i>Quick Fixes</h5>
            <div class="quick-fix-grid">
                <!-- Fix 1: Reset Transactions -->
                <div class="fix-card">
                    <div class="fix-title">
                        <i class="fas fa-broom"></i>
                        Reset Stuck Transactions
                    </div>
                    <p class="small text-muted">Rollback any open database transactions that might be blocking checkout.</p>
                    <form method="post">
                        <button type="submit" name="reset_transactions" class="btn-fix" onclick="return confirm('Reset stuck transactions?')">
                            <i class="fas fa-undo-alt"></i> Reset Transactions
                        </button>
                    </form>
                </div>

                <!-- Fix 2: Reset Daily Counter -->
                <div class="fix-card">
                    <div class="fix-title">
                        <i class="fas fa-sync-alt"></i>
                        Reset Daily Counter
                    </div>
                    <p class="small text-muted">Reset today's order counter back to 1. Useful if numbers are skipping.</p>
                    <form method="post">
                        <button type="submit" name="reset_counter" class="btn-fix warning" onclick="return confirm('Reset daily counter to 1?')">
                            <i class="fas fa-counter"></i> Reset to 1
                        </button>
                    </form>
                </div>

                <!-- Fix 3: Clear Customer Cart -->
                <div class="fix-card">
                    <div class="fix-title">
                        <i class="fas fa-trash-alt"></i>
                        Clear Customer Cart
                    </div>
                    <p class="small text-muted">Remove all items from a specific customer's cart.</p>
                    <form method="post">
                        <select name="customer_id" class="form-select form-select-sm mb-2" required>
                            <option value="">Select Customer...</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" name="clear_customer_cart" class="btn-fix warning" onclick="return confirm('Clear this customer\'s cart?')">
                            <i class="fas fa-broom"></i> Clear Cart
                        </button>
                    </form>
                </div>

                <!-- Fix 4: Fix Order Status -->
                <div class="fix-card">
                    <div class="fix-title">
                        <i class="fas fa-edit"></i>
                        Fix Order Status
                    </div>
                    <p class="small text-muted">Manually update an order's status if it's stuck.</p>
                    <form method="post">
                        <input type="number" name="order_id" class="form-control form-control-sm mb-2" placeholder="Order ID" required>
                        <select name="new_status" class="form-select form-select-sm mb-2" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="preparing">Preparing</option>
                            <option value="ready">Ready</option>
                            <option value="out_for_delivery">Out for Delivery</option>
                            <option value="delivered">Delivered</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                        <button type="submit" name="fix_order_status" class="btn-fix" onclick="return confirm('Update order status?')">
                            <i class="fas fa-check"></i> Update Status
                        </button>
                    </form>
                </div>

                <!-- Fix 5: Restore Stock for Cancelled Order -->
                <div class="fix-card">
                    <div class="fix-title">
                        <i class="fas fa-undo-alt"></i>
                        Restore Order Stock
                    </div>
                    <p class="small text-muted">Restore stock for a cancelled order that didn't return items.</p>
                    <form method="post">
                        <input type="number" name="order_id" class="form-control form-control-sm mb-2" placeholder="Order ID" required>
                        <button type="submit" name="restore_stock" class="btn-fix danger" onclick="return confirm('Restore stock for this order and mark as cancelled?')">
                            <i class="fas fa-box"></i> Restore Stock
                        </button>
                    </form>
                </div>

                <!-- Fix 6: Kill All Connections -->
                <div class="fix-card">
                    <div class="fix-title">
                        <i class="fas fa-skull-crossbones"></i>
                        Force Cleanup
                    </div>
                    <p class="small text-muted">Kill all database connections except yours (nuclear option).</p>
                    <a href="/admin/final_cleanup.php" class="btn-fix danger" style="display: block; text-align: center; text-decoration: none;">
                        <i class="fas fa-bomb"></i> Force Cleanup
                    </a>
                </div>
            </div>

            <!-- Stuck Orders Table -->
            <?php if (!empty($stuck_orders)): ?>
            <div class="mt-4">
                <h5 class="mb-3"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Stuck Orders (Pending > 1 Hour)</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Ordered At</th>
                                <th>Hours Stuck</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stuck_orders as $order): 
                                $hours = round((time() - strtotime($order['order_date'])) / 3600, 1);
                            ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name'] ?: 'Walk-in'); ?></td>
                                <td><?php echo date('M d, H:i', strtotime($order['order_date'])); ?></td>
                                <td><span class="badge bg-warning"><?php echo $hours; ?> hours</span></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="new_status" value="cancelled">
                                        <button type="submit" name="fix_order_status" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this stuck order?')">
                                            Cancel
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Large Carts Warning -->
            <?php if (!empty($large_carts)): ?>
            <div class="mt-4">
                <h5 class="mb-3"><i class="fas fa-shopping-cart text-warning me-2"></i>Unusually Large Carts (>50 items)</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Cart Items</th>
                                <th>Total Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($large_carts as $cart): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($cart['name']); ?></td>
                                <td><?php echo $cart['cart_items']; ?> products</td>
                                <td><span class="badge bg-warning"><?php echo $cart['total_qty']; ?> pcs</span></td>
                                <td>
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="customer_id" value="<?php echo $cart['id']; ?>">
                                        <button type="submit" name="clear_customer_cart" class="btn btn-sm btn-outline-danger" onclick="return confirm('Clear this large cart?')">
                                            Clear Cart
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Recent Error Log -->
            <div class="mt-4">
                <h5 class="mb-3"><i class="fas fa-history me-2"></i>Recent PHP Error Log</h5>
                <div class="log-list" id="errorLog">
                    <?php
                    $log_file = ini_get('error_log');
                    if (file_exists($log_file)) {
                        $lines = file($log_file);
                        $last_lines = array_slice($lines, -20);
                        foreach ($last_lines as $line) {
                            if (preg_match('/(checkout|order|cart|transaction)/i', $line)) {
                                echo '<div class="log-entry"><span class="log-time">[' . date('H:i:s') . ']</span> ' . htmlspecialchars($line) . '</div>';
                            }
                        }
                    } else {
                        echo '<div class="text-muted">No error log found</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Diagnostic Information -->
            <div class="mt-4 p-3 bg-light rounded">
                <h6><i class="fas fa-info-circle me-2"></i>Diagnostic Information</h6>
                <div class="row">
                    <div class="col-md-6">
                        <small><strong>PHP Version:</strong> <?php echo phpversion(); ?></small><br>
                        <small><strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?></small><br>
                        <small><strong>Session Status:</strong> <?php echo session_status() === PHP_SESSION_ACTIVE ? 'Active' : 'Inactive'; ?></small>
                    </div>
                    <div class="col-md-6">
                        <small><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></small><br>
                        <small><strong>Max Execution:</strong> <?php echo ini_get('max_execution_time'); ?>s</small><br>
                        <small><strong>Upload Max:</strong> <?php echo ini_get('upload_max_filesize'); ?></small>
                    </div>
                </div>
            </div>

            <!-- Back to Tools -->
            <div class="mt-4">
                <a href="/modules/tools/index" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Tools
                </a>
                <a href="/admin/check_counter.php" class="btn btn-info ms-2">
                    <i class="fas fa-counter"></i> Check Counter
                </a>
                <a href="/admin/fix_duplicate_cashiers.php" class="btn btn-warning ms-2">
                    <i class="fas fa-users"></i> Fix Duplicate Cashiers
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-refresh error log every 10 seconds (optional)
setInterval(function() {
    location.reload();
}, 30000);

// Auto-hide alerts
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

<?php include '../includes/footer.php'; ?>