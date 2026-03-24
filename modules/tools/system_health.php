<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

// System information
$phpVersion = phpversion();
$mysqlVersion = $pdo->query("SELECT VERSION()")->fetchColumn();
$totalTables = $pdo->query("SHOW TABLES")->rowCount();
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Apache';
$serverProtocol = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
$documentRoot = $_SERVER['DOCUMENT_ROOT'];

// Check cron file
$cronFile = '../../cron/daily_reset.php';
$cronFileExists = file_exists($cronFile) ? '✅ File exists' : '❌ File missing';
$cronFilePath = realpath($cronFile) ?: 'Not found';

// Check daily reset tables
$tables = [
    'tbl_daily_counters' => $pdo->query("SHOW TABLES LIKE 'tbl_daily_counters'")->rowCount() > 0,
    'tbl_daily_orders_archive' => $pdo->query("SHOW TABLES LIKE 'tbl_daily_orders_archive'")->rowCount() > 0,
    'tbl_daily_sales_summary' => $pdo->query("SHOW TABLES LIKE 'tbl_daily_sales_summary'")->rowCount() > 0,
    'tbl_deleted_orders' => $pdo->query("SHOW TABLES LIKE 'tbl_deleted_orders'")->rowCount() > 0,
    'tbl_inventory_logs' => $pdo->query("SHOW TABLES LIKE 'tbl_inventory_logs'")->rowCount() > 0,
    'tbl_notifications' => $pdo->query("SHOW TABLES LIKE 'tbl_notifications'")->rowCount() > 0,
];

// Get table counts
$tableCounts = [];
foreach (array_keys($tables) as $table) {
    if ($tables[$table]) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
        $tableCounts[$table] = $count;
    } else {
        $tableCounts[$table] = 0;
    }
}

// Get today's counter
$todayCounter = $pdo->prepare("SELECT * FROM tbl_daily_counters WHERE counter_date = CURDATE()");
$todayCounter->execute();
$counter = $todayCounter->fetch();

// Check PHP extensions
$requiredExtensions = ['mysqli', 'pdo_mysql', 'gd', 'json', 'session'];
$extensions = [];
foreach ($requiredExtensions as $ext) {
    $extensions[$ext] = extension_loaded($ext);
}

// Disk space (only if function exists)
$diskTotal = function_exists('disk_total_space') ? disk_total_space($documentRoot) : 0;
$diskFree = function_exists('disk_free_space') ? disk_free_space($documentRoot) : 0;
$diskUsed = $diskTotal - $diskFree;
$diskPercent = $diskTotal > 0 ? round(($diskUsed / $diskTotal) * 100) : 0;

// Memory limit
$memoryLimit = ini_get('memory_limit');
$maxExecutionTime = ini_get('max_execution_time');
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');

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
    .health-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        overflow: hidden;
        margin: 15px 0;
        border: 1px solid rgba(0,0,0,0.05);
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .card-header {
        background: linear-gradient(135deg, #17a2b8, #138496);
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

    /* ===== SECTION TITLES ===== */
    .section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 1.5rem 0 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
        padding-bottom: 0.5rem;
        border-bottom: 2px solid #17a2b8;
    }

    .section-title i {
        color: #17a2b8;
    }

    .section-title:first-of-type {
        margin-top: 0;
    }

    /* ===== INFO GRID ===== */
    .info-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }

    @media (min-width: 640px) {
        .info-grid {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    @media (min-width: 1024px) {
        .info-grid {
            grid-template-columns: repeat(3, 1fr);
        }
    }

    .info-item {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 1rem;
        border-left: 4px solid #17a2b8;
        transition: transform 0.2s;
    }

    .info-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 15px rgba(0,0,0,0.1);
    }

    .info-label {
        font-size: 0.75rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 0.3rem;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .info-value {
        font-size: 1.1rem;
        font-weight: 600;
        color: #2c3e50;
        word-break: break-word;
    }

    .info-value small {
        font-size: 0.8rem;
        font-weight: 400;
        color: #6c757d;
        margin-left: 5px;
    }

    @media (min-width: 768px) {
        .info-value {
            font-size: 1.2rem;
        }
    }

    /* ===== TABLE LIST ===== */
    .table-list {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 0.5rem;
        margin-bottom: 1.5rem;
    }

    .table-item {
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

    .table-item:last-child {
        margin-bottom: 0;
    }

    @media (min-width: 768px) {
        .table-item {
            font-size: 0.95rem;
            padding: 1rem;
        }
    }

    .table-name {
        font-weight: 500;
        color: #333;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .table-name i {
        width: 20px;
        color: #17a2b8;
    }

    .table-status {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .status-badge {
        padding: 0.2rem 0.6rem;
        border-radius: 50px;
        font-size: 0.7rem;
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

    .count-badge {
        background: #17a2b8;
        color: white;
        padding: 0.2rem 0.6rem;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 600;
        min-width: 45px;
        text-align: center;
    }

    /* ===== EXTENSION LIST ===== */
    .extension-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin: 1rem 0;
    }

    .extension-item {
        flex: 1 1 calc(50% - 10px);
        min-width: 120px;
        background: #f8f9fa;
        border-radius: 50px;
        padding: 0.6rem 1rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 0.85rem;
    }

    @media (min-width: 768px) {
        .extension-item {
            flex: 0 1 auto;
            min-width: 150px;
        }
    }

    .extension-name {
        font-weight: 500;
        color: #333;
    }

    .extension-status {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .extension-status.loaded {
        background: #28a745;
        box-shadow: 0 0 5px #28a745;
    }

    .extension-status.missing {
        background: #dc3545;
    }

    /* ===== PROGRESS BAR ===== */
    .progress-container {
        margin: 1rem 0;
    }

    .progress-label {
        display: flex;
        justify-content: space-between;
        font-size: 0.85rem;
        margin-bottom: 0.3rem;
    }

    .progress-bar-bg {
        background: #e9ecef;
        height: 8px;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #17a2b8, #138496);
        border-radius: 4px;
        transition: width 0.3s ease;
    }

    /* ===== BUTTON ===== */
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
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        box-shadow: 0 4px 10px rgba(23,162,184,0.2);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(23,162,184,0.3);
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

    /* ===== ALERT ===== */
    .alert {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 1rem;
        border-radius: 10px;
        margin: 1rem 0;
        font-size: 0.85rem;
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }

    .alert i {
        color: #856404;
        font-size: 1rem;
        margin-top: 2px;
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
        color: #17a2b8;
        font-size: 1rem;
        margin-top: 2px;
    }
</style>

<div class="container-fluid">
    <div class="health-card">
        <div class="card-header">
            <i class="fas fa-heartbeat"></i>
            System Health Check
        </div>
        
        <div class="card-body">
            <!-- Server Information -->
            <div class="section-title">
                <i class="fas fa-server"></i>
                Server Information
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-code"></i> PHP Version</div>
                    <div class="info-value"><?php echo $phpVersion; ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-database"></i> MySQL Version</div>
                    <div class="info-value"><?php echo $mysqlVersion; ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-table"></i> Total Tables</div>
                    <div class="info-value"><?php echo $totalTables; ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-globe"></i> Server Software</div>
                    <div class="info-value"><?php echo $serverSoftware; ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-folder-open"></i> Document Root</div>
                    <div class="info-value"><small><?php echo $documentRoot; ?></small></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-clock"></i> Cron File</div>
                    <div class="info-value">
                        <?php echo $cronFileExists; ?>
                        <small><?php echo basename($cronFilePath); ?></small>
                    </div>
                </div>
            </div>

            <!-- PHP Configuration -->
            <div class="section-title">
                <i class="fas fa-cog"></i>
                PHP Configuration
            </div>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-memory"></i> Memory Limit</div>
                    <div class="info-value"><?php echo $memoryLimit; ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-hourglass-half"></i> Max Execution Time</div>
                    <div class="info-value"><?php echo $maxExecutionTime; ?>s</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-upload"></i> Upload Max Size</div>
                    <div class="info-value"><?php echo $uploadMaxFilesize; ?></div>
                </div>
                
                <div class="info-item">
                    <div class="info-label"><i class="fas fa-inbox"></i> Post Max Size</div>
                    <div class="info-value"><?php echo $postMaxSize; ?></div>
                </div>
            </div>

            <!-- Required Extensions -->
            <div class="section-title">
                <i class="fas fa-puzzle-piece"></i>
                PHP Extensions
            </div>
            
            <div class="extension-list">
                <?php foreach ($extensions as $ext => $loaded): ?>
                    <div class="extension-item">
                        <span class="extension-name"><?php echo $ext; ?></span>
                        <span class="extension-status <?php echo $loaded ? 'loaded' : 'missing'; ?>"></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Daily Reset Tables -->
            <div class="section-title">
                <i class="fas fa-clock"></i>
                Daily Reset Tables
            </div>
            
            <div class="table-list">
                <?php foreach ($tables as $table => $exists): ?>
                    <div class="table-item">
                        <span class="table-name">
                            <i class="fas fa-table"></i>
                            <?php echo $table; ?>
                        </span>
                        <div class="table-status">
                            <?php if ($exists): ?>
                                <span class="count-badge"><?php echo number_format($tableCounts[$table]); ?> rows</span>
                                <span class="status-badge exists">✅</span>
                            <?php else: ?>
                                <span class="status-badge missing">❌ Missing</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Today's Counter -->
            <?php if ($counter): ?>
                <div class="alert">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <strong>Today's Order Counter:</strong> 
                        ORD-<?php echo date('Y-m-d'); ?>-<?php echo str_pad($counter['order_counter'], 4, '0', STR_PAD_LEFT); ?><br>
                        <small>Counter: <?php echo $counter['order_counter']; ?> | 
                        Last Reset: <?php echo date('M d, H:i', strtotime($counter['last_reset_at'])); ?></small>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Disk Space (if available) -->
            <?php if ($diskTotal > 0): ?>
                <div class="section-title">
                    <i class="fas fa-hdd"></i>
                    Disk Usage
                </div>
                
                <div class="progress-container">
                    <div class="progress-label">
                        <span>Used: <?php echo round($diskUsed / 1024 / 1024 / 1024, 2); ?> GB</span>
                        <span>Free: <?php echo round($diskFree / 1024 / 1024 / 1024, 2); ?> GB</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-bar-fill" style="width: <?php echo $diskPercent; ?>%;"></div>
                    </div>
                    <small class="text-muted"><?php echo $diskPercent; ?>% used of <?php echo round($diskTotal / 1024 / 1024 / 1024, 2); ?> GB total</small>
                </div>
            <?php endif; ?>

            <!-- Action Buttons -->
            <div class="btn-container">
                <a href="javascript:window.location.reload();" class="btn btn-primary">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                    Back to Tools
                </a>
            </div>

            <!-- Footnote -->
            <div class="footnote">
                <i class="fas fa-shield-alt"></i>
                <div>
                    <strong>System Health:</strong> All critical components are being monitored. 
                    Red indicators show missing items that may need attention. 
                    The daily reset system should have all three tables present for proper functionality.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Touch feedback for buttons
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

// Add loading animation to refresh button
document.querySelector('.btn-primary')?.addEventListener('click', function(e) {
    if (this.textContent.includes('Refresh')) {
        this.classList.add('loading');
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
    }
});
</script>

<?php include '../../includes/footer.php'; ?>