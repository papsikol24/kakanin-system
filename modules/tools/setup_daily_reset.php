<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin']);

$message = '';
$error = '';

// Handle Create Tables
if (isset($_POST['create_tables'])) {
    try {
        $sql = "
        CREATE TABLE IF NOT EXISTS `tbl_daily_orders_archive` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `original_order_id` int(11) NOT NULL,
          `daily_order_number` int(11) NOT NULL,
          `order_date` datetime NOT NULL,
          `customer_name` varchar(100) DEFAULT NULL,
          `total_amount` decimal(10,2) NOT NULL,
          `payment_method` varchar(50) NOT NULL,
          `status` varchar(50) NOT NULL,
          `items_count` int(11) DEFAULT 0,
          `archive_date` date NOT NULL,
          PRIMARY KEY (`id`),
          KEY `archive_date` (`archive_date`),
          KEY `daily_order_number` (`daily_order_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

        CREATE TABLE IF NOT EXISTS `tbl_daily_sales_summary` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `sale_date` date NOT NULL,
          `total_orders` int(11) DEFAULT 0,
          `total_sales` decimal(10,2) DEFAULT 0.00,
          `cash_sales` decimal(10,2) DEFAULT 0.00,
          `gcash_sales` decimal(10,2) DEFAULT 0.00,
          `paymaya_sales` decimal(10,2) DEFAULT 0.00,
          `completed_orders` int(11) DEFAULT 0,
          `pending_orders` int(11) DEFAULT 0,
          `cancelled_orders` int(11) DEFAULT 0,
          `total_items_sold` int(11) DEFAULT 0,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_sale_date` (`sale_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

        CREATE TABLE IF NOT EXISTS `tbl_daily_counters` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `counter_date` date NOT NULL,
          `order_counter` int(11) DEFAULT 1,
          `inventory_log_counter` int(11) DEFAULT 1,
          `last_reset_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_counter_date` (`counter_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        
        -- Insert initial counter for today
        INSERT INTO `tbl_daily_counters` (`counter_date`, `order_counter`, `inventory_log_counter`) 
        VALUES (CURDATE(), 1, 1)
        ON DUPLICATE KEY UPDATE `order_counter` = `order_counter`;
        ";
        
        $pdo->exec($sql);
        $message = "✅ Daily reset tables created successfully!";
        
    } catch (Exception $e) {
        $error = "❌ Error: " . $e->getMessage();
    }
}

// Handle Reset Counter
if (isset($_POST['reset_counter'])) {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Get current counter value before reset (for logging)
        $check = $pdo->prepare("SELECT order_counter FROM tbl_daily_counters WHERE counter_date = CURDATE()");
        $check->execute();
        $old_value = $check->fetchColumn();
        
        // Reset today's counter to 1
        $stmt = $pdo->prepare("
            INSERT INTO tbl_daily_counters (counter_date, order_counter, inventory_log_counter)
            VALUES (CURDATE(), 1, 1)
            ON DUPLICATE KEY UPDATE 
                order_counter = 1,
                inventory_log_counter = 1,
                last_reset_at = NOW()
        ");
        $stmt->execute();
        
        // Clear session mapping for today
        if (isset($_SESSION['daily_order_map'][date('Y-m-d')])) {
            unset($_SESSION['daily_order_map'][date('Y-m-d')]);
        }
        
        // Log the reset action
        $log_msg = "Admin " . $_SESSION['user_id'] . " reset daily counter from " . ($old_value ?: 'new') . " to 1";
        error_log($log_msg);
        
        $pdo->commit();
        
        $message = "✅ Daily order counter successfully reset to 1 for today!";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "❌ Failed to reset counter: " . $e->getMessage();
    }
}

// Check which tables already exist
$tablesExist = [
    'tbl_daily_orders_archive' => $pdo->query("SHOW TABLES LIKE 'tbl_daily_orders_archive'")->rowCount() > 0,
    'tbl_daily_sales_summary' => $pdo->query("SHOW TABLES LIKE 'tbl_daily_sales_summary'")->rowCount() > 0,
    'tbl_daily_counters' => $pdo->query("SHOW TABLES LIKE 'tbl_daily_counters'")->rowCount() > 0,
];

include '../../includes/header.php';
?>

<style>
    /* ===== MOBILE-FRIENDLY STYLES ===== */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Poppins', sans-serif;
        background-color: #f8f9fa;
    }

    .container-fluid {
        width: 100%;
        padding-right: 10px;
        padding-left: 10px;
        margin-right: auto;
        margin-left: auto;
    }

    @media (min-width: 768px) {
        .container-fluid {
            padding-right: 15px;
            padding-left: 15px;
            max-width: 1200px;
        }
    }

    /* ===== CARD STYLES ===== */
    .setup-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        overflow: hidden;
        margin: 15px 0;
        border: 1px solid rgba(0,0,0,0.05);
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .card-header {
        background: linear-gradient(135deg, #007bff, #0069d9);
        color: white;
        padding: 1.2rem;
        font-size: 1.2rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .card-header i {
        font-size: 1.4rem;
    }

    .card-body {
        padding: 1.2rem;
    }

    @media (min-width: 768px) {
        .card-body {
            padding: 1.8rem;
        }
    }

    /* ===== ALERTS ===== */
    .alert {
        padding: 1rem;
        border-radius: 12px;
        margin-bottom: 1.2rem;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert i {
        font-size: 1.1rem;
    }

    /* ===== TABLE STATUS ===== */
    .table-status {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 1rem;
        margin-bottom: 1.5rem;
    }

    .table-status h5 {
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .table-status h5 i {
        color: #007bff;
    }

    .status-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .status-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.8rem;
        background: white;
        border-radius: 10px;
        margin-bottom: 0.5rem;
        border: 1px solid #eee;
        font-size: 0.85rem;
    }

    @media (min-width: 768px) {
        .status-item {
            font-size: 0.95rem;
            padding: 1rem;
        }
    }

    .status-name {
        font-weight: 500;
        color: #333;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .status-name i {
        width: 20px;
        color: #6c757d;
    }

    .status-badge {
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .status-badge.exists {
        background: #28a745;
        color: white;
    }

    .status-badge.missing {
        background: #dc3545;
        color: white;
    }

    /* ===== INFO CARD ===== */
    .info-card {
        background: #e8f4f4;
        border-radius: 15px;
        padding: 1rem;
        margin: 1.5rem 0;
        border-left: 4px solid #17a2b8;
    }

    .info-title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #17a2b8;
        margin-bottom: 0.8rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .info-list li {
        padding: 0.5rem 0;
        border-bottom: 1px dashed rgba(23,162,184,0.2);
        font-size: 0.85rem;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-list li:last-child {
        border-bottom: none;
    }

    .info-list li i {
        color: #17a2b8;
        font-size: 0.9rem;
        width: 20px;
    }

    /* ===== BUTTON STYLES ===== */
    .btn-container {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin: 1.5rem 0;
    }

    @media (min-width: 576px) {
        .btn-container {
            flex-direction: row;
            gap: 15px;
        }
    }

    .btn {
        padding: 0.8rem 1.2rem;
        border: none;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        cursor: pointer;
        transition: all 0.2s;
        -webkit-tap-highlight-color: transparent;
        flex: 1;
    }

    @media (min-width: 768px) {
        .btn {
            padding: 0.8rem 1.5rem;
            font-size: 1rem;
        }
    }

    .btn:active {
        transform: scale(0.98);
    }

    .btn-primary {
        background: linear-gradient(135deg, #007bff, #0069d9);
        color: white;
        box-shadow: 0 4px 10px rgba(0,123,255,0.2);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,123,255,0.3);
    }

    .btn-warning {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #333;
        box-shadow: 0 4px 10px rgba(255,193,7,0.2);
    }

    .btn-warning:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(255,193,7,0.3);
    }

    .btn-secondary {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        box-shadow: 0 4px 10px rgba(108,117,125,0.2);
    }

    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(108,117,125,0.3);
    }

    .btn i {
        font-size: 0.9rem;
    }

    /* ===== CURRENT COUNTER ===== */
    .counter-box {
        background: linear-gradient(135deg, #fff3cd, #ffe69c);
        border-radius: 15px;
        padding: 1rem;
        margin-top: 1.5rem;
        border: 1px solid #ffc107;
        text-align: center;
    }

    .counter-label {
        font-size: 0.8rem;
        color: #856404;
        margin-bottom: 0.3rem;
    }

    .counter-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #856404;
    }

    .counter-date {
        font-size: 0.75rem;
        color: #856404;
        opacity: 0.8;
    }

    /* ===== FOOTNOTE ===== */
    .footnote {
        margin-top: 1.5rem;
        padding: 1rem;
        background: #f8f9fa;
        border-radius: 12px;
        font-size: 0.8rem;
        color: #666;
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }

    .footnote i {
        color: #007bff;
        font-size: 1rem;
        margin-top: 2px;
    }

    /* ===== LOADING SPINNER ===== */
    .btn.loading {
        position: relative;
        color: transparent !important;
        pointer-events: none;
    }

    .btn.loading::after {
        content: '';
        position: absolute;
        width: 18px;
        height: 18px;
        top: 50%;
        left: 50%;
        margin-left: -9px;
        margin-top: -9px;
        border: 2px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        border-top-color: white;
        animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .btn-warning.loading::after {
        border-top-color: #333;
    }

    /* ===== CUSTOM CONFIRM DIALOG ===== */
    .modal-confirm {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        animation: fadeIn 0.3s ease;
    }

    .modal-content {
        background-color: white;
        margin: 15% auto;
        padding: 20px;
        border-radius: 20px;
        max-width: 450px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        animation: slideDown 0.3s ease;
    }

    .modal-header {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #333;
        padding: 15px 20px;
        margin: -20px -20px 20px -20px;
        border-radius: 20px 20px 0 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .modal-header i {
        font-size: 1.2rem;
    }

    .modal-body {
        padding: 10px 0;
        font-size: 1rem;
        color: #333;
    }

    .modal-footer {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
        margin-top: 20px;
    }

    .modal-btn {
        padding: 10px 20px;
        border: none;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.2s;
    }

    .modal-btn-cancel {
        background: #6c757d;
        color: white;
    }

    .modal-btn-confirm {
        background: #ffc107;
        color: #333;
    }

    .modal-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }

    /* ===== INFO BOX ===== */
    .info-box {
        background: #e7f3ff;
        border-left: 4px solid #17a2b8;
        padding: 1rem;
        border-radius: 10px;
        margin: 1rem 0;
        font-size: 0.9rem;
    }

    .info-box i {
        color: #17a2b8;
        margin-right: 8px;
    }

    .badge-db {
        background: #dc3545;
        color: white;
        padding: 0.2rem 0.6rem;
        border-radius: 50px;
        font-size: 0.8rem;
        display: inline-block;
    }

    .badge-daily {
        background: #28a745;
        color: white;
        padding: 0.2rem 0.6rem;
        border-radius: 50px;
        font-size: 0.8rem;
        display: inline-block;
    }
</style>

<div class="container-fluid">
    <div class="setup-card">
        <div class="card-header">
            <i class="fas fa-clock"></i>
            Daily Reset Setup
        </div>
        
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- IMPORTANT: Clarify what gets reset -->
            <div class="info-box">
                <i class="fas fa-info-circle"></i>
                <strong>Understanding Order Numbers:</strong>
                <div class="mt-2">
                    <p><span class="badge-db me-2">DB ID: #7</span> This is the database ID - <strong>NEVER RESETS</strong> (keeps increasing forever)</p>
                    <p><span class="badge-daily me-2">Daily: ORD-2026-02-28-0001</span> This is the daily number - <strong>RESETS to 1 each day</strong></p>
                </div>
                <p class="mt-2 mb-0 text-muted small">The reset button below only affects the <strong>Daily Order Number</strong>, not the database ID.</p>
            </div>

            <!-- Table Status -->
            <div class="table-status">
                <h5>
                    <i class="fas fa-database"></i>
                    Current Table Status
                </h5>
                <ul class="status-list">
                    <li class="status-item">
                        <span class="status-name">
                            <i class="fas fa-archive"></i>
                            Daily Orders Archive
                        </span>
                        <span class="status-badge <?php echo $tablesExist['tbl_daily_orders_archive'] ? 'exists' : 'missing'; ?>">
                            <?php echo $tablesExist['tbl_daily_orders_archive'] ? '✅ Created' : '❌ Missing'; ?>
                        </span>
                    </li>
                    <li class="status-item">
                        <span class="status-name">
                            <i class="fas fa-chart-line"></i>
                            Daily Sales Summary
                        </span>
                        <span class="status-badge <?php echo $tablesExist['tbl_daily_sales_summary'] ? 'exists' : 'missing'; ?>">
                            <?php echo $tablesExist['tbl_daily_sales_summary'] ? '✅ Created' : '❌ Missing'; ?>
                        </span>
                    </li>
                    <li class="status-item">
                        <span class="status-name">
                            <i class="fas fa-counter"></i>
                            Daily Counters
                        </span>
                        <span class="status-badge <?php echo $tablesExist['tbl_daily_counters'] ? 'exists' : 'missing'; ?>">
                            <?php echo $tablesExist['tbl_daily_counters'] ? '✅ Created' : '❌ Missing'; ?>
                        </span>
                    </li>
                </ul>
            </div>

            <!-- What Gets Reset Info -->
            <div class="info-card">
                <div class="info-title">
                    <i class="fas fa-info-circle"></i>
                    What Gets Reset Daily at Midnight
                </div>
                <ul class="info-list">
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <strong>Daily order numbers</strong> restart from 1 (ORD-0001 format)
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        <strong>Database IDs (#7, #8, #9...)</strong> - <span class="text-danger">NEVER RESET</span>
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        Inventory log numbers restart daily
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        Daily sales summaries are automatically created
                    </li>
                    <li>
                        <i class="fas fa-check-circle"></i>
                        All data is preserved in archive tables
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="btn-container">
                <form method="post" style="flex: 1;" id="createTablesForm">
                    <button type="submit" name="create_tables" class="btn btn-primary" id="createBtn">
                        <i class="fas fa-database"></i>
                        Create Tables
                    </button>
                </form>
                
                <?php if ($tablesExist['tbl_daily_counters']): ?>
                <!-- Reset button with custom dialog -->
                <button type="button" class="btn btn-warning" id="showResetDialog" style="flex: 1;">
                    <i class="fas fa-sync-alt"></i> Reset Daily Counter
                </button>
                <?php endif; ?>
                
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <!-- Current Counter Display -->
            <?php if ($tablesExist['tbl_daily_counters']): 
                $counter = $pdo->prepare("SELECT * FROM tbl_daily_counters WHERE counter_date = CURDATE()");
                $counter->execute();
                $today_counter = $counter->fetch();
                
                // Get the latest database ID
                $lastDbId = $pdo->query("SELECT MAX(id) FROM tbl_orders")->fetchColumn();
            ?>
                <div class="counter-box">
                    <div class="counter-label">Today's Daily Order Number</div>
                    <div class="counter-value">
                        ORD-<?php echo date('Y-m-d'); ?>-<?php echo str_pad(($today_counter ? $today_counter['order_counter'] : 1), 4, '0', STR_PAD_LEFT); ?>
                    </div>
                    <div class="counter-date">
                        Daily Counter: <?php echo $today_counter ? $today_counter['order_counter'] : 1; ?> | 
                        Last DB ID: #<?php echo $lastDbId ?: '0'; ?> | 
                        Last Reset: <?php echo $today_counter ? date('M d, H:i', strtotime($today_counter['last_reset_at'])) : 'Not yet'; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Footnote -->
            <div class="footnote">
                <i class="fas fa-lightbulb"></i>
                <div>
                    <strong>Note:</strong> The reset button only affects the <strong>Daily Order Number</strong> (e.g., ORD-2026-02-28-0001). 
                    The database ID (e.g., #7) will continue to increase and never resets.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Confirm Dialog -->
<div id="confirmDialog" class="modal-confirm">
    <div class="modal-content">
        <div class="modal-header">
            <i class="fas fa-exclamation-triangle"></i>
            <span>Confirm Daily Counter Reset</span>
        </div>
        <div class="modal-body">
            <p><strong>Are you sure you want to reset the DAILY ORDER COUNTER to 1 for today?</strong></p>
            <p>This will affect: <strong id="todayDateDisplay"></strong></p>
            <div class="alert alert-warning p-2 small">
                <i class="fas fa-info-circle"></i>
                <strong>Database IDs (#7, #8, #9...) will NOT be affected.</strong> They will continue to increase normally.
            </div>
            <p class="text-warning mt-2"><i class="fas fa-clock"></i> All future orders today will start from daily number 0001 again.</p>
        </div>
        <div class="modal-footer">
            <button class="modal-btn modal-btn-cancel" id="cancelReset">Cancel</button>
            <form method="post" id="resetForm" style="display: inline;">
                <button type="submit" name="reset_counter" class="modal-btn modal-btn-confirm" id="confirmReset">Yes, Reset Daily Counter</button>
            </form>
        </div>
    </div>
</div>

<script>
// Get today's date for display
const today = new Date().toISOString().slice(0,10);
document.getElementById('todayDateDisplay').textContent = 'ORD-' + today + '-xxxx';

// Show custom dialog
document.getElementById('showResetDialog')?.addEventListener('click', function() {
    document.getElementById('confirmDialog').style.display = 'block';
    document.body.style.overflow = 'hidden'; // Prevent background scrolling
});

// Hide dialog when clicking cancel
document.getElementById('cancelReset')?.addEventListener('click', function() {
    document.getElementById('confirmDialog').style.display = 'none';
    document.body.style.overflow = 'auto';
});

// Hide dialog when clicking outside
document.getElementById('confirmDialog')?.addEventListener('click', function(e) {
    if (e.target === this) {
        this.style.display = 'none';
        document.body.style.overflow = 'auto';
    }
});

// Loading animation for create tables button
document.getElementById('createBtn')?.addEventListener('click', function() {
    this.classList.add('loading');
    this.disabled = true;
});

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

// Touch feedback
document.querySelectorAll('.btn').forEach(btn => {
    btn.addEventListener('touchstart', function() {
        this.style.opacity = '0.8';
    });
    btn.addEventListener('touchend', function() {
        this.style.opacity = '1';
    });
    btn.addEventListener('touchcancel', function() {
        this.style.opacity = '1';
    });
});
</script>

<?php include '../../includes/footer.php'; ?>