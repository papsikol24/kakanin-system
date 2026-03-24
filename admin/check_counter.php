<?php
require_once '../includes/config.php';
require_once '../includes/daily_counter.php';
requireLogin();
requireRole('admin');

$today = date('Y-m-d');

// Check database counter
$stmt = $pdo->prepare("SELECT * FROM tbl_daily_counters WHERE counter_date = ?");
$stmt->execute([$today]);
$counter = $stmt->fetch();

include '../includes/header.php';
?>

<style>
    .counter-display {
        font-size: 2.5rem;
        font-weight: 700;
        color: #28a745;
        text-align: center;
        padding: 2rem;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 15px;
        margin: 1rem 0;
    }
    .badge-reset {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .badge-success { background: #28a745; color: white; }
    .badge-warning { background: #ffc107; color: #333; }
</style>

<div class="container-fluid">
    <div class="card" style="max-width: 700px; margin: 30px auto;">
        <div class="card-header bg-info text-white">
            <h4><i class="fas fa-counter me-2"></i>Daily Counter Status</h4>
        </div>
        <div class="card-body">
            <?php if ($counter): ?>
                <!-- Current Counter Display -->
                <div class="counter-display">
                    <?php echo $counter['order_counter']; ?>
                </div>
                
                <div class="text-center mb-4">
                    <span class="badge-reset <?php echo $counter['order_counter'] == 1 ? 'badge-success' : 'badge-warning'; ?>">
                        <?php echo $counter['order_counter'] == 1 ? '✅ Fresh Start' : '⚡ In Progress'; ?>
                    </span>
                </div>
                
                <table class="table table-bordered">
                    <tr>
                        <th style="width: 200px;">Today's Date</th>
                        <td><strong><?php echo $today; ?></strong></td>
                    </tr>
                    <tr>
                        <th>Current Counter Value</th>
                        <td>
                            <span class="badge bg-primary fs-6 p-2"><?php echo $counter['order_counter']; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th>Next Order Number</th>
                        <td>
                            <div class="p-2 bg-light rounded">
                                <code class="h5">
                                    ORD-<?php echo $today; ?>-<?php echo str_pad($counter['order_counter'], 4, '0', STR_PAD_LEFT); ?>
                                </code>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>Inventory Log Counter</th>
                        <td><?php echo $counter['inventory_log_counter']; ?></td>
                    </tr>
                    <tr>
                        <th>Last Reset</th>
                        <td><?php echo date('F d, Y h:i:s A', strtotime($counter['last_reset_at'])); ?></td>
                    </tr>
                    <tr>
                        <th>Session Mapping</th>
                        <td>
                            <?php if (isset($_SESSION['daily_order_map'][$today])): ?>
                                <span class="badge bg-success">Active</span>
                                <?php echo count($_SESSION['daily_order_map'][$today]); ?> orders mapped today
                            <?php else: ?>
                                <span class="badge bg-secondary">None</span>
                                No orders in session
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
                
                <div class="mt-4 d-flex gap-2">
                    <a href="/modules/tools/setup_daily_reset.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left"></i> Back to Setup
                    </a>
                    <a href="/admin/check_counter.php" class="btn btn-info">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </a>
                    <a href="/admin/reset_counter.php?test=1" class="btn btn-warning" onclick="return confirm('Test mode: This would reset the counter. Use the button in Setup instead.')">
                        <i class="fas fa-test"></i> Test (No action)
                    </a>
                </div>
                
                <!-- Quick Stats -->
                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3><?php 
                                    $total = $pdo->query("SELECT COUNT(*) FROM tbl_orders WHERE DATE(order_date) = CURDATE()")->fetchColumn();
                                    echo $total;
                                ?></h3>
                                <p class="mb-0">Orders Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3><?php 
                                    $pending = $pdo->query("SELECT COUNT(*) FROM tbl_orders WHERE DATE(order_date) = CURDATE() AND status = 'pending'")->fetchColumn();
                                    echo $pending;
                                ?></h3>
                                <p class="mb-0">Pending Today</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h3><?php 
                                    $max = $pdo->query("SELECT MAX(id) FROM tbl_orders")->fetchColumn();
                                    echo $max ?: 0;
                                ?></h3>
                                <p class="mb-0">Last Order ID</p>
                            </div>
                        </div>
                    </div>
                </div>
                
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    No counter found for today. Please create tables first.
                </div>
                <a href="/modules/tools/setup_daily_reset.php" class="btn btn-primary">
                    Go to Setup
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>