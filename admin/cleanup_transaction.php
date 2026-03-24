<?php
require_once '../includes/config.php';
requireLogin();
requireRole('admin');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['cleanup'])) {
        try {
            $count = 0;
            // Force rollback any open transaction
            while ($pdo->inTransaction()) {
                $pdo->rollBack();
                $count++;
            }
            
            if ($count > 0) {
                $message = "✅ Successfully rolled back $count stuck transaction(s)!";
            } else {
                $message = "✅ No stuck transactions found. Database is clean!";
            }
            
            // Also kill any sleeping connections if needed
            if (isset($_POST['kill_sleeping'])) {
                $stmt = $pdo->query("
                    SELECT id FROM information_schema.PROCESSLIST 
                    WHERE command = 'Sleep' 
                    AND time > 60
                    AND db = 'if0_41233935_kakanin_db'
                    AND id != CONNECTION_ID()
                ");
                $sleeping = $stmt->fetchAll();
                $killed = 0;
                
                foreach ($sleeping as $conn) {
                    $pdo->exec("KILL " . $conn['id']);
                    $killed++;
                }
                
                $message .= " Killed $killed sleeping connections.";
            }
            
        } catch (Exception $e) {
            $error = "Error during cleanup: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<style>
    .cleanup-card {
        max-width: 600px;
        margin: 40px auto;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .process-list {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin: 20px 0;
        max-height: 300px;
        overflow-y: auto;
    }
    
    .process-item {
        padding: 8px;
        border-bottom: 1px solid #dee2e6;
        font-size: 0.9rem;
    }
    
    .process-item:last-child {
        border-bottom: none;
    }
    
    .badge-sleep {
        background: #ffc107;
        color: #333;
    }
    
    .badge-query {
        background: #007bff;
        color: white;
    }
</style>

<div class="container-fluid">
    <div class="card cleanup-card">
        <div class="card-header bg-danger text-white">
            <h4><i class="fas fa-broom me-2"></i>Transaction Cleanup Tool</h4>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Warning:</strong> This tool should only be used when you get "active transaction" errors during checkout.
            </div>

            <!-- Show current database status -->
            <h5 class="mt-4">Current Database Status</h5>
            <div class="process-list">
                <?php
                // Get current processes
                $processes = $pdo->query("
                    SELECT id, command, time, state, db 
                    FROM information_schema.PROCESSLIST 
                    WHERE db = 'if0_41233935_kakanin_db' 
                    OR db IS NULL
                    ORDER BY time DESC
                    LIMIT 10
                ")->fetchAll();
                
                if (empty($processes)) {
                    echo '<p class="text-muted">No active processes</p>';
                } else {
                    foreach ($processes as $p) {
                        $badge_class = $p['command'] == 'Sleep' ? 'badge-sleep' : 'badge-query';
                        echo '<div class="process-item d-flex justify-content-between align-items-center">';
                        echo '<div>';
                        echo '<span class="badge ' . $badge_class . ' me-2">' . $p['command'] . '</span>';
                        echo 'ID: ' . $p['id'] . ' | Time: ' . $p['time'] . 's';
                        if ($p['db']) {
                            echo ' | DB: ' . $p['db'];
                        }
                        echo '</div>';
                        if ($p['command'] == 'Sleep' && $p['time'] > 10) {
                            echo '<span class="badge bg-warning">Stuck</span>';
                        }
                        echo '</div>';
                    }
                }
                ?>
            </div>

            <!-- Cleanup Form -->
            <form method="post" class="mt-4">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="kill_sleeping" id="killSleeping" checked>
                    <label class="form-check-label" for="killSleeping">
                        Also kill sleeping connections older than 60 seconds
                    </label>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" name="cleanup" class="btn btn-danger btn-lg" 
                            onclick="return confirm('Run transaction cleanup? This will rollback any open transactions.')">
                        <i class="fas fa-broom me-2"></i>Run Cleanup Now
                    </button>
                </div>
            </form>

            <hr class="my-4">

            <h5>Quick Fix Commands</h5>
            <p>If the button doesn't work, run these commands in phpMyAdmin:</p>
            <pre class="bg-light p-3 rounded">
-- Check for open transactions
SELECT * FROM information_schema.INNODB_TRX\G

-- Kill specific process (replace 123 with ID)
KILL 123;

-- Kill all sleeping connections
SELECT CONCAT('KILL ', id, ';') 
FROM information_schema.PROCESSLIST 
WHERE command = 'Sleep' 
AND time > 60;
            </pre>

            <div class="mt-3">
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

<?php include '../includes/footer.php'; ?>