<?php
require_once '../includes/config.php';
requireLogin();
requireRole('admin');

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['customer_id'])) {
        $customer_id = (int)$_POST['customer_id'];
        $action = $_POST['action'] ?? 'clear';
        
        try {
            $pdo->beginTransaction();
            
            if ($action == 'clear') {
                // 1. Clear their cart from database
                $stmt = $pdo->prepare("DELETE FROM tbl_carts WHERE customer_id = ?");
                $stmt->execute([$customer_id]);
                $carts_cleared = $stmt->rowCount();
                
                // 2. Mark their notifications as read
                $stmt = $pdo->prepare("UPDATE tbl_notifications SET is_read = 1 WHERE customer_id = ?");
                $stmt->execute([$customer_id]);
                $notifs_updated = $stmt->rowCount();
                
                // 3. Get customer name for message
                $stmt = $pdo->prepare("SELECT name FROM tbl_customers WHERE id = ?");
                $stmt->execute([$customer_id]);
                $customer_name = $stmt->fetchColumn();
                
                $message = "✅ Cleared data for $customer_name:<br>";
                $message .= "- Cart items cleared: $carts_cleared<br>";
                $message .= "- Notifications marked read: $notifs_updated";
            }
            
            if ($action == 'reset_password') {
                $new_password = $_POST['new_password'] ?? '';
                if (strlen($new_password) >= 6) {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE tbl_customers SET password = ? WHERE id = ?");
                    $stmt->execute([$hashed, $customer_id]);
                    $message = "✅ Password reset successfully for customer ID: $customer_id";
                } else {
                    $error = "Password must be at least 6 characters";
                }
            }
            
            $pdo->commit();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get list of customers with their stats
$customers = $pdo->query("
    SELECT 
        c.id, 
        c.name, 
        c.email, 
        c.phone,
        c.status,
        (SELECT COUNT(*) FROM tbl_carts WHERE customer_id = c.id) as cart_count,
        (SELECT COUNT(*) FROM tbl_notifications WHERE customer_id = c.id AND is_read = 0) as unread_count,
        (SELECT COUNT(*) FROM tbl_orders WHERE customer_id = c.id) as order_count
    FROM tbl_customers c
    ORDER BY c.name
")->fetchAll();

include '../includes/header.php';
?>

<style>
    .customer-card {
        max-width: 800px;
        margin: 30px auto;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .customer-table {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .badge-count {
        background: #17a2b8;
        color: white;
        border-radius: 50px;
        padding: 2px 8px;
        font-size: 0.7rem;
    }
    
    .badge-unread {
        background: #dc3545;
        color: white;
        border-radius: 50px;
        padding: 2px 8px;
        font-size: 0.7rem;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
</style>

<div class="container-fluid">
    <div class="card customer-card">
        <div class="card-header bg-warning">
            <h4><i class="fas fa-user-slash me-2"></i>Clear Customer Session</h4>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>How to use:</strong> Select a customer below to clear their cart and notifications. 
                This will force them to log out and log back in to reset their session.
            </div>

            <!-- Customer List -->
            <div class="customer-table">
                <table class="table table-hover table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Cart</th>
                            <th>Unread</th>
                            <th>Orders</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $c): ?>
                        <tr>
                            <td><?php echo $c['id']; ?></td>
                            <td><?php echo htmlspecialchars($c['name']); ?></td>
                            <td><?php echo htmlspecialchars($c['email']); ?></td>
                            <td>
                                <?php if ($c['cart_count'] > 0): ?>
                                    <span class="badge-count"><?php echo $c['cart_count']; ?> items</span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['unread_count'] > 0): ?>
                                    <span class="badge-unread"><?php echo $c['unread_count']; ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $c['order_count']; ?></td>
                            <td>
                                <button class="btn btn-sm btn-warning" 
                                        onclick="showClearModal(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['name'])); ?>')">
                                    <i class="fas fa-broom"></i> Clear
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Quick Actions -->
            <hr class="my-4">
            
            <h5>Bulk Actions</h5>
            <div class="row mt-3">
                <div class="col-md-6">
                    <button class="btn btn-danger w-100" onclick="clearAllCarts()">
                        <i class="fas fa-trash-alt"></i> Clear All Carts
                    </button>
                </div>
                <div class="col-md-6">
                    <button class="btn btn-warning w-100" onclick="markAllRead()">
                        <i class="fas fa-check-double"></i> Mark All Notifications Read
                    </button>
                </div>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between">
                <a href="/tools" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Tools
                </a>
                <a href="/admin/final_cleanup.php" class="btn btn-danger">
                    <i class="fas fa-exclamation-triangle"></i> Force Cleanup
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Clear Modal -->
<div class="modal fade" id="clearModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Clear Customer Data
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Clear data for <strong id="customerName"></strong>?</p>
                <p class="text-muted small">This will:</p>
                <ul class="text-muted small">
                    <li>Delete their shopping cart</li>
                    <li>Mark all notifications as read</li>
                    <li>Force them to log out on next action</li>
                </ul>
                <form method="post" id="clearForm">
                    <input type="hidden" name="customer_id" id="customerId" value="">
                    <input type="hidden" name="action" value="clear">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="clearForm" class="btn btn-warning">Yes, Clear Data</button>
            </div>
        </div>
    </div>
</div>

<script>
function showClearModal(id, name) {
    document.getElementById('customerId').value = id;
    document.getElementById('customerName').textContent = name;
    new bootstrap.Modal(document.getElementById('clearModal')).show();
}

function clearAllCarts() {
    if (confirm('⚠️ Clear ALL customer carts? This cannot be undone.')) {
        window.location.href = '?clear_all_carts=1';
    }
}

function markAllRead() {
    if (confirm('Mark ALL notifications as read for all customers?')) {
        window.location.href = '?mark_all_read=1';
    }
}

// Auto-refresh every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php
// Handle bulk actions
if (isset($_GET['clear_all_carts'])) {
    try {
        $pdo->exec("DELETE FROM tbl_carts");
        $_SESSION['success'] = "All carts cleared!";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: clear_customer_session.php');
    exit;
}

if (isset($_GET['mark_all_read'])) {
    try {
        $pdo->exec("UPDATE tbl_notifications SET is_read = 1");
        $_SESSION['success'] = "All notifications marked as read!";
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    header('Location: clear_customer_session.php');
    exit;
}

include '../includes/footer.php';
?>