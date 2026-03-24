<?php
require_once '../includes/config.php';
requireLogin();
requireRole('admin');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['force_cleanup'])) {
        try {
            // Method 1: Rollback any PHP-level transactions
            try {
                while ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } catch (Exception $e) {
                // Ignore
            }
            
            // Method 2: Kill all connections to the database except current one
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
                    // Ignore errors if connection already closed
                }
            }
            
            // Method 3: Reset the database connection
            try {
                $pdo->exec("UNLOCK TABLES");
            } catch (Exception $e) {
                // Ignore
            }
            
            $message = "✅ Cleanup complete! Killed $killed connections.";
            
        } catch (Exception $e) {
            $error = "Error during cleanup: " . $e->getMessage();
        }
    }
    
    if (isset($_POST['reset_counter'])) {
        try {
            // Reset daily counter
            require_once '../includes/daily_counter.php';
            resetDailyCounter($pdo);
            $message = "✅ Daily counter reset to 1!";
        } catch (Exception $e) {
            $error = "Error resetting counter: " . $e->getMessage();
        }
    }
}

// Get current processes
$processes = $pdo->query("
    SELECT id, user, host, db, command, time, state 
    FROM information_schema.PROCESSLIST 
    WHERE db = 'if0_41233935_kakanin_db' 
    ORDER BY time DESC
")->fetchAll();

include '../includes/header.php';
?>

<style>
    .cleanup-card {
        max-width: 800px;
        margin: 30px auto;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .process-table {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin: 20px 0;
        max-height: 400px;
        overflow-y: auto;
    }
    
    .process-row {
        padding: 8px;
        border-bottom: 1px solid #dee2e6;
        font-size: 0.9rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .process-row:last-child {
        border-bottom: none;
    }
    
    .process-row.stuck {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
    }
    
    .badge-sleep {
        background: #6c757d;
        color: white;
    }
    
    .badge-query {
        background: #007bff;
        color: white;
    }
    
    .badge-stuck {
        background: #dc3545;
        color: white;
    }
    
    .btn-danger-custom {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 12px 20px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-danger-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220,53,69,0.4);
        color: white;
    }
    
    .btn-warning-custom {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #333;
        border: none;
        border-radius: 50px;
        padding: 12px 20px;
        font-weight: 600;
        transition: all 0.3s;
    }
    
    .btn-warning-custom:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255,193,7,0.4);
    }
    
    .sql-box {
        background: #2d2d2d;
        color: #f8f9fa;
        padding: 15px;
        border-radius: 10px;
        font-family: monospace;
        font-size: 0.9rem;
        overflow-x: auto;
    }
</style>

<div class="container-fluid">
    <div class="card cleanup-card">
        <div class="card-header bg-danger text-white">
            <h4><i class="fas fa-exclamation-triangle me-2"></i>Force Transaction Cleanup</h4>
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
                <i class="fas fa-exclamation-triangle fa-2x me-3"></i>
                <div>
                    <strong>⚠️ CRITICAL WARNING:</strong> This tool will kill ALL database connections except yours. 
                    Any users currently placing orders will be disconnected. Use only when absolutely necessary.
                </div>
            </div>

            <!-- Current Connections -->
            <h5 class="mt-4">📊 Current Database Connections</h5>
            <div class="process-table">
                <?php if (empty($processes)): ?>
                    <p class="text-muted">No active connections</p>
                <?php else: ?>
                    <?php foreach ($processes as $proc): 
                        $is_stuck = ($proc['command'] == 'Sleep' && $proc['time'] > 60) || $proc['time'] > 300;
                        $row_class = $is_stuck ? 'process-row stuck' : 'process-row';
                    ?>
                    <div class="<?php echo $row_class; ?>">
                        <div>
                            <span class="badge <?php echo $proc['command'] == 'Sleep' ? 'badge-sleep' : 'badge-query'; ?> me-2">
                                <?php echo $proc['command']; ?>
                            </span>
                            <strong>ID:</strong> <?php echo $proc['id']; ?> | 
                            <strong>Time:</strong> <?php echo $proc['time']; ?>s | 
                            <strong>Host:</strong> <?php echo $proc['host']; ?>
                            <?php if ($is_stuck): ?>
                                <span class="badge badge-stuck ms-2">STUCK</span>
                            <?php endif; ?>
                        </div>
                        <?php if ($proc['id'] != $pdo->query("SELECT CONNECTION_ID()")->fetchColumn()): ?>
                            <a href="?kill=<?php echo $proc['id']; ?>" class="btn btn-sm btn-danger" 
                               onclick="return confirm('Kill connection <?php echo $proc['id']; ?>?')">
                                Kill
                            </a>
                        <?php else: ?>
                            <span class="badge bg-success">Current</span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-md-6 mb-2">
                    <form method="post" onsubmit="return confirm('⚠️ This will kill ALL other connections! Continue?')">
                        <button type="submit" name="force_cleanup" class="btn btn-danger-custom w-100">
                            <i class="fas fa-skull-crossbones me-2"></i>
                            FORCE CLEANUP - Kill All Connections
                        </button>
                    </form>
                </div>
                <div class="col-md-6 mb-2">
                    <form method="post" onsubmit="return confirm('Reset daily counter to 1?')">
                        <button type="submit" name="reset_counter" class="btn btn-warning-custom w-100">
                            <i class="fas fa-sync-alt me-2"></i>
                            Reset Daily Counter
                        </button>
                    </form>
                </div>
            </div>

            <!-- Manual SQL Commands -->
            <div class="mt-4">
                <h5>📝 Manual SQL Commands (Run in phpMyAdmin)</h5>
                <div class="sql-box">
                    -- Check for open transactions<br>
                    SELECT * FROM information_schema.INNODB_TRX\G<br><br>
                    
                    -- Kill all connections to your database<br>
                    SELECT CONCAT('KILL ', id, ';') <br>
                    FROM information_schema.PROCESSLIST <br>
                    WHERE db = 'if0_41233935_kakanin_db' <br>
                    AND id != CONNECTION_ID();<br><br>
                    
                    -- Reset auto-increment if needed<br>
                    ALTER TABLE tbl_orders AUTO_INCREMENT = 1;<br><br>
                    
                    -- Clear any table locks<br>
                    UNLOCK TABLES;
                </div>
            </div>

            <!-- Quick Test -->
            <div class="mt-4">
                <h5>🔍 Quick Database Test</h5>
                <button class="btn btn-info" onclick="testConnection()">
                    <i class="fas fa-plug"></i> Test Database Connection
                </button>
                <div id="testResult" class="mt-2"></div>
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

<script>
function testConnection() {
    const resultDiv = document.getElementById('testResult');
    resultDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
    
    fetch('/api/test_connection.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                resultDiv.innerHTML = '<div class="alert alert-success">✅ Database connection is working!</div>';
            } else {
                resultDiv.innerHTML = '<div class="alert alert-danger">❌ Connection error: ' + data.error + '</div>';
            }
        })
        .catch(error => {
            resultDiv.innerHTML = '<div class="alert alert-danger">❌ Error: ' + error + '</div>';
        });
}

// Auto-refresh every 10 seconds
setInterval(function() {
    location.reload();
}, 10000);
</script>

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
    header('Location: force_cleanup.php');
    exit;
}

include '../includes/footer.php';
?>