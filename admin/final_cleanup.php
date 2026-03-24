<?php
require_once '../includes/config.php';
requireLogin();
requireRole('admin');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['cleanup'])) {
        try {
            // Method 1: Rollback any PHP transaction
            $rolledback = 0;
            while ($pdo->inTransaction()) {
                $pdo->rollBack();
                $rolledback++;
            }
            
            // Method 2: Kill all other connections
            $stmt = $pdo->query("
                SELECT id FROM information_schema.PROCESSLIST 
                WHERE db = 'if0_41233935_kakanin_db' 
                AND id != CONNECTION_ID()
            ");
            
            $connections = $stmt->fetchAll();
            $killed = 0;
            
            foreach ($connections as $conn) {
                try {
                    $pdo->exec("KILL " . $conn['id']);
                    $killed++;
                } catch (Exception $e) {
                    // Connection might already be gone
                }
            }
            
            // Method 3: Reset the daily counter
            require_once '../includes/daily_counter.php';
            if (function_exists('resetDailyCounter')) {
                resetDailyCounter($pdo);
            } else {
                // Manual reset if function doesn't exist
                $today = date('Y-m-d');
                $stmt = $pdo->prepare("
                    INSERT INTO tbl_daily_counters (counter_date, order_counter, inventory_log_counter)
                    VALUES (?, 1, 1)
                    ON DUPLICATE KEY UPDATE 
                        order_counter = 1,
                        inventory_log_counter = 1,
                        last_reset_at = NOW()
                ");
                $stmt->execute([$today]);
            }
            
            // Method 4: Clear session data
            if (isset($_SESSION['daily_order_map'])) {
                unset($_SESSION['daily_order_map']);
            }
            
            $message = "✅ Cleanup complete! Rolled back: $rolledback, Killed connections: $killed";
            
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get current connections
$connections = [];
try {
    $stmt = $pdo->query("
        SELECT id, user, host, command, time, state 
        FROM information_schema.PROCESSLIST 
        WHERE db = 'if0_41233935_kakanin_db' 
        ORDER BY time DESC
    ");
    $connections = $stmt->fetchAll();
} catch (Exception $e) {
    $error = "Could not fetch connections: " . $e->getMessage();
}

include '../includes/header.php';
?>

<style>
    .cleanup-card {
        max-width: 1000px;
        margin: 30px auto;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .table-warning {
        background-color: #fff3cd !important;
    }
    
    .badge-current {
        background: #28a745;
        color: white;
        padding: 3px 8px;
        border-radius: 50px;
        font-size: 0.7rem;
    }
    
    .btn-kill {
        padding: 3px 8px;
        font-size: 0.7rem;
        border-radius: 50px;
    }
    
    .stats-box {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .stat-item {
        flex: 1;
        min-width: 120px;
        text-align: center;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #dc3545;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.8rem;
        text-transform: uppercase;
    }
</style>

<div class="container-fluid">
    <div class="card cleanup-card">
        <div class="card-header bg-danger text-white">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>Final Transaction Cleanup</h4>
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

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle fa-2x me-3 float-start"></i>
                <div>
                    <strong>⚠️ CRITICAL WARNING:</strong> This tool will kill ALL database connections except yours. 
                    Any users currently placing orders will be disconnected. Use only when absolutely necessary.
                </div>
                <div class="clearfix"></div>
            </div>

            <!-- Statistics -->
            <div class="stats-box">
                <div class="stat-item">
                    <div class="stat-value"><?php echo count($connections); ?></div>
                    <div class="stat-label">Total Connections</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php 
                        $sleeping = 0;
                        foreach ($connections as $conn) {
                            if ($conn['command'] == 'Sleep') $sleeping++;
                        }
                        echo $sleeping;
                    ?></div>
                    <div class="stat-label">Sleeping</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?php 
                        $stuck = 0;
                        foreach ($connections as $conn) {
                            if ($conn['time'] > 60) $stuck++;
                        }
                        echo $stuck;
                    ?></div>
                    <div class="stat-label">Stuck (>60s)</div>
                </div>
            </div>

            <!-- Current Connections -->
            <h5 class="mt-4">📊 Current Database Connections</h5>
            <div class="table-responsive">
                <table class="table table-bordered table-hover table-sm">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Host</th>
                            <th>Command</th>
                            <th>Time (s)</th>
                            <th>State</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($connections)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">No active connections</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($connections as $conn): 
                                $is_current = ($conn['id'] == $pdo->query("SELECT CONNECTION_ID()")->fetchColumn());
                                $is_stuck = ($conn['time'] > 60);
                                $row_class = '';
                                if ($is_current) $row_class = 'table-success';
                                elseif ($is_stuck) $row_class = 'table-warning';
                            ?>
                            <tr class="<?php echo $row_class; ?>">
                                <td><?php echo $conn['id']; ?></td>
                                <td><?php echo $conn['user']; ?></td>
                                <td><?php echo $conn['host']; ?></td>
                                <td>
                                    <span class="badge <?php echo $conn['command'] == 'Sleep' ? 'bg-secondary' : 'bg-primary'; ?>">
                                        <?php echo $conn['command']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($is_stuck): ?>
                                        <span class="badge bg-danger"><?php echo $conn['time']; ?>s</span>
                                    <?php else: ?>
                                        <?php echo $conn['time']; ?>s
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $conn['state'] ?: '-'; ?></td>
                                <td>
                                    <?php if ($is_current): ?>
                                        <span class="badge bg-success">Current</span>
                                    <?php else: ?>
                                        <a href="?kill=<?php echo $conn['id']; ?>" 
                                           class="btn btn-sm btn-danger btn-kill"
                                           onclick="return confirm('Kill connection <?php echo $conn['id']; ?>?')">
                                            Kill
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-md-6 mb-2">
                    <form method="post" onsubmit="return confirm('⚠️ This will kill ALL other connections! Continue?')">
                        <button type="submit" name="cleanup" class="btn btn-danger btn-lg w-100">
                            <i class="fas fa-broom me-2"></i>
                            RUN COMPLETE CLEANUP
                        </button>
                    </form>
                </div>
                <div class="col-md-6 mb-2">
                    <a href="/admin/clear_customer_session.php" class="btn btn-warning btn-lg w-100">
                        <i class="fas fa-user-slash me-2"></i>
                        Clear Customer Session
                    </a>
                </div>
            </div>

            <!-- Quick Fix Instructions -->
            <div class="alert alert-info mt-4">
                <h6><i class="fas fa-info-circle"></i> Quick Fix for Customers</h6>
                <p class="mb-0">If customers still can't order, ask them to:</p>
                <ol class="mb-0 mt-2">
                    <li>Log out of their account</li>
                    <li>Clear their browser cache (Ctrl+Shift+Delete)</li>
                    <li>Wait 5 minutes</li>
                    <li>Log back in and try again</li>
                </ol>
            </div>

            <hr class="my-4">

            <div class="d-flex justify-content-between">
                <a href="/tools" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Tools
                </a>
                <a href="?refresh=1" class="btn btn-info">
                    <i class="fas fa-sync-alt"></i> Refresh
                </a>
            </div>
        </div>
    </div>
</div>

<?php
// Handle single kill
if (isset($_GET['kill'])) {
    $kill_id = (int)$_GET['kill'];
    try {
        $pdo->exec("KILL " . $kill_id);
        $_SESSION['success'] = "Killed process $kill_id";
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to kill process: " . $e->getMessage();
    }
    header('Location: final_cleanup.php');
    exit;
}

include '../includes/footer.php';
?>