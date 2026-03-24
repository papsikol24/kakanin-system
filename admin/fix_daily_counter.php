<?php
require_once '../includes/config.php';
require_once '../includes/daily_counter.php';
requireLogin();
requireRole('admin');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['fix_counter'])) {
        // Reset to 1
        resetDailyCounter($pdo);
        $message = "✅ Counter reset to 1 successfully!";
    }
    
    if (isset($_POST['set_to'])) {
        $new_value = (int)$_POST['set_to'];
        if ($new_value > 0) {
            $today = date('Y-m-d');
            $stmt = $pdo->prepare("
                INSERT INTO tbl_daily_counters (counter_date, order_counter, inventory_log_counter)
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE 
                    order_counter = ?,
                    last_reset_at = NOW()
            ");
            $stmt->execute([$today, $new_value, $new_value]);
            $message = "✅ Counter set to $new_value successfully!";
        }
    }
}

// Get current counter
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT * FROM tbl_daily_counters WHERE counter_date = ?");
$stmt->execute([$today]);
$counter = $stmt->fetch();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="card" style="max-width: 600px; margin: 50px auto;">
        <div class="card-header bg-warning">
            <h4><i class="fas fa-tools me-2"></i>Fix Daily Counter</h4>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="text-center mb-4">
                <h5>Current Counter Value</h5>
                <div class="display-1 text-primary"><?php echo $counter ? $counter['order_counter'] : '1'; ?></div>
                <p class="text-muted">Next order will be: 
                    <strong>ORD-<?php echo $today; ?>-<?php echo str_pad(($counter ? $counter['order_counter'] : 1), 4, '0', STR_PAD_LEFT); ?></strong>
                </p>
            </div>
            
            <form method="post" class="mb-4">
                <button type="submit" name="fix_counter" class="btn btn-warning btn-lg w-100" onclick="return confirm('Reset counter to 1?')">
                    <i class="fas fa-sync-alt"></i> Reset Counter to 1
                </button>
            </form>
            
            <form method="post" class="mb-4">
                <div class="input-group">
                    <input type="number" name="set_to" class="form-control" placeholder="Set to specific value" min="1" value="1">
                    <button type="submit" class="btn btn-primary">Set Value</button>
                </div>
            </form>
            
            <div class="mt-3">
                <a href="/modules/tools/setup_daily_reset.php" class="btn btn-secondary">Back to Setup</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>