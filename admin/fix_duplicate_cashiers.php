<?php
require_once '../includes/config.php';
requireLogin();
requireRole('admin');

$message = '';
$error = '';

// Handle fixing duplicates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['fix_duplicates'])) {
        try {
            $pdo->beginTransaction();
            
            // Find duplicate usernames among cashiers
            $duplicates = $pdo->query("
                SELECT username, COUNT(*) as count, GROUP_CONCAT(id) as ids, 
                       GROUP_CONCAT(created_at) as created_dates
                FROM tbl_users 
                WHERE role = 'cashier' 
                GROUP BY username 
                HAVING COUNT(*) > 1
            ")->fetchAll();
            
            if (empty($duplicates)) {
                $message = "✅ No duplicate cashiers found!";
            } else {
                $fixed_count = 0;
                $merged_count = 0;
                $results = [];
                
                foreach ($duplicates as $dup) {
                    $ids = explode(',', $dup['ids']);
                    $dates = explode(',', $dup['created_dates']);
                    
                    // Keep the most recent account (highest ID)
                    $keep_id = max($ids);
                    $keep_date = '';
                    foreach ($ids as $index => $id) {
                        if ($id == $keep_id) {
                            $keep_date = $dates[$index];
                            break;
                        }
                    }
                    
                    $results[] = [
                        'username' => $dup['username'],
                        'keep_id' => $keep_id,
                        'keep_date' => $keep_date,
                        'delete_ids' => array_diff($ids, [$keep_id])
                    ];
                    
                    foreach ($ids as $id) {
                        if ($id != $keep_id) {
                            // Check if this cashier has any orders or activity
                            $check_orders = $pdo->prepare("
                                SELECT COUNT(*) FROM tbl_orders 
                                WHERE created_by = ? OR completed_by = ?
                            ");
                            $check_orders->execute([$id, $id]);
                            $order_count = $check_orders->fetchColumn();
                            
                            $check_sessions = $pdo->prepare("
                                SELECT COUNT(*) FROM tbl_active_sessions WHERE user_id = ?
                            ");
                            $check_sessions->execute([$id]);
                            $session_count = $check_orders->fetchColumn();
                            
                            if ($order_count > 0 || $session_count > 0) {
                                // Has activity, update records to point to kept ID
                                $pdo->prepare("
                                    UPDATE tbl_orders SET created_by = ? WHERE created_by = ?
                                ")->execute([$keep_id, $id]);
                                
                                $pdo->prepare("
                                    UPDATE tbl_orders SET completed_by = ? WHERE completed_by = ?
                                ")->execute([$keep_id, $id]);
                                
                                $pdo->prepare("
                                    UPDATE tbl_active_sessions SET user_id = ? WHERE user_id = ?
                                ")->execute([$keep_id, $id]);
                                
                                $pdo->prepare("
                                    UPDATE tbl_inventory_logs SET user_id = ? WHERE user_id = ?
                                ")->execute([$keep_id, $id]);
                                
                                $merged_count++;
                            }
                            
                            // Delete the duplicate cashier
                            $pdo->prepare("DELETE FROM tbl_users WHERE id = ?")->execute([$id]);
                            $fixed_count++;
                        }
                    }
                }
                
                // Add unique constraint to prevent future duplicates
                $pdo->exec("ALTER TABLE tbl_users ADD UNIQUE INDEX IF NOT EXISTS unique_username (username)");
                
                $pdo->commit();
                
                $message = "✅ Fixed " . count($duplicates) . " duplicate username(s)!<br>";
                $message .= "• Deleted: $fixed_count duplicate accounts<br>";
                $message .= "• Merged: $merged_count accounts with existing orders<br>";
                
                // Show details of what was fixed
                if (!empty($results)) {
                    $message .= "<br><strong>Details:</strong><ul>";
                    foreach ($results as $r) {
                        $message .= "<li><strong>{$r['username']}</strong>: Kept ID #{$r['keep_id']} (created {$r['keep_date']}), removed " . count($r['delete_ids']) . " duplicate(s)</li>";
                    }
                    $message .= "</ul>";
                }
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "❌ Error: " . $e->getMessage();
        }
    }
    
    // Handle preview duplicates (no action, just show)
    if (isset($_POST['preview'])) {
        // Just preview mode - no action
    }
}

// Get all cashiers for display
$cashiers = $pdo->query("
    SELECT id, username, role, created_at, status,
           (SELECT COUNT(*) FROM tbl_orders WHERE created_by = id) as orders_created,
           (SELECT COUNT(*) FROM tbl_orders WHERE completed_by = id) as orders_completed,
           (SELECT COUNT(*) FROM tbl_active_sessions WHERE user_id = id) as active_sessions
    FROM tbl_users 
    WHERE role = 'cashier' 
    ORDER BY username, id
")->fetchAll();

// Find duplicates for preview
$duplicate_check = $pdo->query("
    SELECT username, COUNT(*) as count, GROUP_CONCAT(id) as ids
    FROM tbl_users 
    WHERE role = 'cashier' 
    GROUP BY username 
    HAVING COUNT(*) > 1
")->fetchAll();

$has_duplicates = !empty($duplicate_check);

include '../includes/header.php';
?>

<style>
    .admin-tool-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        margin-bottom: 25px;
        overflow: hidden;
        border: 1px solid #e0e0e0;
    }
    
    .tool-header {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .tool-header i {
        font-size: 1.3rem;
    }
    
    .tool-body {
        padding: 20px;
    }
    
    .warning-box {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .stat-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        text-align: center;
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: #dc3545;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 0.85rem;
        text-transform: uppercase;
    }
    
    .table-container {
        max-height: 400px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        margin: 20px 0;
    }
    
    .duplicate-row {
        background-color: #fff3cd !important;
        border-left: 4px solid #dc3545;
    }
    
    .badge-count {
        background: #dc3545;
        color: white;
        padding: 3px 8px;
        border-radius: 50px;
        font-size: 0.7rem;
    }
    
    .btn-fix {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 12px 25px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-fix:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220,53,69,0.3);
    }
    
    .btn-preview {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 12px 25px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-preview:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(23,162,184,0.3);
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 12px 25px;
        font-weight: 600;
        font-size: 1rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
    }
    
    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(108,117,125,0.3);
        color: white;
    }
    
    .duplicate-alert {
        background: #dc3545;
        color: white;
        padding: 10px 15px;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
        animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }
    
    .modal-content {
        border-radius: 15px;
    }
    
    .modal-header.bg-danger {
        background: linear-gradient(135deg, #dc3545, #c82333) !important;
    }
    
    @media (max-width: 768px) {
        .tool-body {
            padding: 15px;
        }
        
        .btn-fix, .btn-preview, .btn-secondary {
            width: 100%;
            margin-bottom: 10px;
        }
    }
</style>

<div class="container-fluid">
    <div class="admin-tool-card">
        <div class="tool-header">
            <i class="fas fa-exclamation-triangle"></i>
            Fix Duplicate Cashiers
        </div>
        
        <div class="tool-body">
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="warning-box">
                <i class="fas fa-info-circle me-2"></i>
                <strong>How this tool works:</strong>
                <ul class="mt-2 mb-0">
                    <li>Finds cashier accounts with duplicate usernames</li>
                    <li>Keeps the most recent account (highest ID)</li>
                    <li>Merges orders and activity from duplicates to the kept account</li>
                    <li>Deletes duplicate accounts safely</li>
                    <li>Adds unique constraint to prevent future duplicates</li>
                </ul>
            </div>

            <!-- Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($cashiers); ?></div>
                    <div class="stat-label">Total Cashiers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo count($duplicate_check); ?></div>
                    <div class="stat-label">Duplicate Usernames</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php 
                        $total_duplicates = 0;
                        foreach ($duplicate_check as $d) {
                            $total_duplicates += $d['count'] - 1;
                        }
                        echo $total_duplicates;
                    ?></div>
                    <div class="stat-label">Duplicate Accounts</div>
                </div>
            </div>

            <?php if ($has_duplicates): ?>
                <div class="duplicate-alert mb-3">
                    <i class="fas fa-exclamation-circle"></i>
                    Warning: Found <?php echo $total_duplicates; ?> duplicate cashier account(s) that need fixing!
                </div>
            <?php endif; ?>

            <!-- Cashiers Table with Duplicates Highlighted -->
            <h5 class="mt-4">Current Cashiers List</h5>
            <div class="table-container">
                <table class="table table-hover">
                    <thead class="sticky-top bg-light">
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Created</th>
                            <th>Orders Created</th>
                            <th>Orders Completed</th>
                            <th>Active Sessions</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $duplicate_usernames = [];
                        foreach ($duplicate_check as $dup) {
                            $duplicate_usernames[$dup['username']] = explode(',', $dup['ids']);
                        }
                        
                        foreach ($cashiers as $c): 
                            $is_duplicate = isset($duplicate_usernames[$c['username']]) && 
                                           in_array($c['id'], $duplicate_usernames[$c['username']]);
                            
                            // Check if this is the one we would keep (highest ID)
                            $would_keep = false;
                            if ($is_duplicate) {
                                $max_id = max($duplicate_usernames[$c['username']]);
                                $would_keep = ($c['id'] == $max_id);
                            }
                        ?>
                        <tr class="<?php echo $is_duplicate ? 'duplicate-row' : ''; ?>">
                            <td>
                                #<?php echo $c['id']; ?>
                                <?php if ($would_keep): ?>
                                    <span class="badge bg-success ms-1">KEEP</span>
                                <?php elseif ($is_duplicate): ?>
                                    <span class="badge bg-danger ms-1">DUPLICATE</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($c['username']); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                            <td><?php echo $c['orders_created']; ?></td>
                            <td><?php echo $c['orders_completed']; ?></td>
                            <td><?php echo $c['active_sessions']; ?></td>
                            <td>
                                <?php if ($c['status']): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactive</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Action Buttons -->
            <div class="mt-4 d-flex gap-2 flex-wrap">
                <form method="post" class="d-inline" onsubmit="return confirmFix()">
                    <button type="submit" name="fix_duplicates" class="btn-fix" <?php echo !$has_duplicates ? 'disabled' : ''; ?>>
                        <i class="fas fa-broom"></i> Fix Duplicate Cashiers Now
                    </button>
                </form>
                
                <form method="post" class="d-inline">
                    <button type="submit" name="preview" class="btn-preview">
                        <i class="fas fa-search"></i> Preview Only
                    </button>
                </form>
                
                <a href="/modules/tools/index" class="btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Tools
                </a>
            </div>

            <!-- Preview Results -->
            <?php if (isset($_POST['preview']) && $has_duplicates): ?>
                <div class="mt-4 p-3 bg-light rounded">
                    <h6><i class="fas fa-eye me-2"></i>Preview - What will be fixed:</h6>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Keep ID</th>
                                <th>Will Delete</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($duplicate_check as $dup): 
                                $ids = explode(',', $dup['ids']);
                                $keep_id = max($ids);
                                $delete_ids = array_diff($ids, [$keep_id]);
                            ?>
                            <tr>
                                <td><strong><?php echo $dup['username']; ?></strong></td>
                                <td><span class="badge bg-success">#<?php echo $keep_id; ?></span></td>
                                <td>
                                    <?php foreach ($delete_ids as $did): ?>
                                        <span class="badge bg-danger">#<?php echo $did; ?></span>
                                    <?php endforeach; ?>
                                </td>
                                <td>
                                    <?php 
                                    // Check if any of the deleted ones have orders
                                    $has_orders = false;
                                    foreach ($delete_ids as $did) {
                                        $check = $pdo->prepare("SELECT COUNT(*) FROM tbl_orders WHERE created_by = ? OR completed_by = ?");
                                        $check->execute([$did, $did]);
                                        if ($check->fetchColumn() > 0) {
                                            $has_orders = true;
                                            break;
                                        }
                                    }
                                    echo $has_orders ? 
                                        '<span class="badge bg-warning">Will merge orders</span>' : 
                                        '<span class="badge bg-info">No orders to merge</span>';
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <!-- Prevention Tips -->
            <div class="alert alert-info mt-4">
                <i class="fas fa-lightbulb me-2"></i>
                <strong>Prevention Tips:</strong>
                <ul class="mt-2 mb-0">
                    <li>Always use unique usernames when creating cashiers</li>
                    <li>The system now has a unique constraint to prevent duplicates</li>
                    <li>Train staff to use their assigned accounts only</li>
                    <li>Regularly review cashier accounts for duplicates</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function confirmFix() {
    return confirm('⚠️ WARNING: This will merge and delete duplicate cashier accounts.\n\n' +
                  '• Duplicate accounts will be permanently deleted\n' +
                  '• Orders will be reassigned to the kept account\n' +
                  '• This action cannot be undone!\n\n' +
                  'Are you sure you want to proceed?');
}

// Auto-hide alerts after 5 seconds
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