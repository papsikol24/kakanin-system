<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

include '../../includes/header.php';
?>

<style>
    /* ===== RESET & BASE STYLES ===== */
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
        padding-right: 15px;
        padding-left: 15px;
        margin-right: auto;
        margin-left: auto;
    }

    /* ===== SECTION HEADER ===== */
    .section-header {
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e0e0;
    }

    .section-header h4 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #333;
        margin: 0;
    }

    .section-header h4 i {
        color: #6c757d;
        margin-right: 8px;
    }

    @media (min-width: 768px) {
        .section-header h4 {
            font-size: 1.5rem;
        }
    }

    /* ===== TOOLS GRID - 4x4 on Mobile ===== */
    .tools-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 30px;
    }

    @media (max-width: 480px) {
        .tools-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
    }

    .tool-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: all 0.3s ease;
        border: 1px solid rgba(0,0,0,0.05);
        height: 100%;
        display: flex;
        flex-direction: column;
        position: relative;
        overflow: hidden;
    }

    .tool-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.1);
    }

    .tool-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, #6c757d, #adb5bd);
        transform: translateX(-100%);
        transition: transform 0.5s ease;
    }

    .tool-card:hover::before {
        transform: translateX(0);
    }

    .tool-card.danger::before {
        background: linear-gradient(90deg, #dc3545, #c82333);
    }

    .tool-card.warning::before {
        background: linear-gradient(90deg, #ffc107, #e0a800);
    }

    .tool-card.info::before {
        background: linear-gradient(90deg, #17a2b8, #138496);
    }

    .tool-card.primary::before {
        background: linear-gradient(90deg, #007bff, #0069d9);
    }

    .tool-card.success::before {
        background: linear-gradient(90deg, #28a745, #218838);
    }

    .tool-card-body {
        padding: 20px 15px;
        text-align: center;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    @media (max-width: 767px) {
        .tool-card-body {
            padding: 15px 10px;
        }
    }

    .tool-icon {
        font-size: 2rem;
        margin-bottom: 15px;
    }

    @media (min-width: 768px) {
        .tool-icon {
            font-size: 2.5rem;
        }
    }

    .tool-icon.danger {
        color: #dc3545;
    }

    .tool-icon.warning {
        color: #ffc107;
    }

    .tool-icon.info {
        color: #17a2b8;
    }

    .tool-icon.primary {
        color: #007bff;
    }

    .tool-icon.success {
        color: #28a745;
    }

    .tool-title {
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
    }

    @media (min-width: 768px) {
        .tool-title {
            font-size: 1.1rem;
        }
    }

    .tool-description {
        font-size: 0.8rem;
        color: #666;
        line-height: 1.4;
        margin-bottom: 15px;
        flex: 1;
    }

    @media (min-width: 768px) {
        .tool-description {
            font-size: 0.85rem;
        }
    }

    .tool-btn {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 8px 12px;
        font-size: 0.8rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        box-shadow: 0 4px 10px rgba(108,117,125,0.2);
        width: 100%;
        cursor: pointer;
    }

    .tool-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(108,117,125,0.3);
        color: white;
    }

    .tool-btn.danger {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }

    .tool-btn.warning {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #333;
    }

    .tool-btn.info {
        background: linear-gradient(135deg, #17a2b8, #138496);
    }

    .tool-btn.primary {
        background: linear-gradient(135deg, #007bff, #0069d9);
    }

    .tool-btn.success {
        background: linear-gradient(135deg, #28a745, #218838);
    }

    .tool-btn i {
        font-size: 0.8rem;
    }

    @media (max-width: 480px) {
        .tool-btn {
            padding: 6px 10px;
            font-size: 0.7rem;
        }
    }

    /* ===== DAILY RESET CARD SPECIAL STYLING ===== */
    .tool-card.daily-reset {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border: 2px solid #008080;
    }

    .tool-card.daily-reset::before {
        background: linear-gradient(90deg, #008080, #20b2aa);
    }

    .tool-icon.daily-reset {
        color: #008080;
    }

    .daily-reset-badge {
        display: inline-block;
        background: #008080;
        color: white;
        font-size: 0.7rem;
        padding: 0.2rem 0.5rem;
        border-radius: 50px;
        margin-bottom: 10px;
    }

    /* ===== BACKUP SECTION ===== */
    .backup-section {
        margin-top: 30px;
    }

    .backup-title {
        font-size: 1.2rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .backup-title i {
        color: #007bff;
    }

    .backup-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    @media (max-width: 480px) {
        .backup-grid {
            grid-template-columns: 1fr;
            gap: 10px;
        }
    }

    .backup-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        border: 1px solid rgba(0,0,0,0.05);
        overflow: hidden;
        transition: all 0.3s;
    }

    .backup-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .backup-card-header {
        padding: 15px;
        color: white;
        font-weight: 600;
        font-size: 1rem;
    }

    .backup-card-header.primary {
        background: linear-gradient(135deg, #007bff, #0069d9);
    }

    .backup-card-header.success {
        background: linear-gradient(135deg, #28a745, #218838);
    }

    .backup-card-body {
        padding: 20px;
    }

    @media (max-width: 767px) {
        .backup-card-body {
            padding: 15px;
        }
    }

    .backup-features {
        list-style: none;
        padding: 0;
        margin: 0 0 20px 0;
    }

    .backup-features li {
        padding: 8px 0;
        border-bottom: 1px solid #eee;
        font-size: 0.85rem;
        color: #555;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .backup-features li:last-child {
        border-bottom: none;
    }

    .backup-features li i {
        color: #28a745;
        font-size: 0.9rem;
    }

    @media (max-width: 767px) {
        .backup-features li {
            font-size: 0.8rem;
            padding: 6px 0;
        }
    }

    .backup-btn {
        display: block;
        width: 100%;
        padding: 12px;
        border: none;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.9rem;
        text-decoration: none;
        text-align: center;
        transition: all 0.3s;
        cursor: pointer;
    }

    .backup-btn.primary {
        background: linear-gradient(135deg, #007bff, #0069d9);
        color: white;
    }

    .backup-btn.success {
        background: linear-gradient(135deg, #28a745, #218838);
        color: white;
    }

    .backup-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    }

    .backup-footer {
        padding: 12px 15px;
        background: #f8f9fa;
        font-size: 0.8rem;
        color: #666;
        border-top: 1px solid #eee;
    }

    /* ===== INFO CARD ===== */
    .info-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border: 1px solid #ffc107;
        overflow: hidden;
        margin-top: 30px;
    }

    .info-header {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #333;
        padding: 15px 20px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .info-body {
        padding: 20px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    @media (max-width: 480px) {
        .info-grid {
            grid-template-columns: 1fr;
            gap: 15px;
        }
    }

    .info-column h6 {
        font-size: 1rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .info-column ol {
        padding-left: 20px;
        margin-bottom: 0;
    }

    .info-column li {
        margin-bottom: 5px;
        font-size: 0.85rem;
        color: #555;
    }

    @media (max-width: 767px) {
        .info-column li {
            font-size: 0.8rem;
        }
    }

    /* ===== MODAL STYLES ===== */
    .modal-content {
        border-radius: 15px;
        border: none;
        overflow: hidden;
    }

    .modal-header {
        padding: 15px 20px;
        border: none;
    }

    .modal-header.bg-danger {
        background: linear-gradient(135deg, #dc3545, #c82333) !important;
    }

    .modal-header.bg-warning {
        background: linear-gradient(135deg, #ffc107, #e0a800) !important;
        color: #333;
    }

    .modal-header.bg-info {
        background: linear-gradient(135deg, #17a2b8, #138496) !important;
    }

    .modal-header.bg-primary {
        background: linear-gradient(135deg, #007bff, #0069d9) !important;
    }

    .modal-title {
        font-size: 1.1rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .modal-body {
        padding: 20px;
        font-size: 0.9rem;
    }

    .modal-footer {
        padding: 15px 20px;
        border-top: 1px solid #eee;
    }

    @media (max-width: 767px) {
        .modal-body {
            padding: 15px;
            font-size: 0.85rem;
        }
    }

    .modal-body ul {
        margin: 10px 0;
        padding-left: 20px;
    }

    .modal-body li {
        margin-bottom: 5px;
    }

    .form-check-input:checked {
        background-color: #dc3545;
        border-color: #dc3545;
    }

    .btn-modal {
        border-radius: 50px;
        padding: 8px 20px;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.2s;
    }

    .btn-modal:hover {
        transform: translateY(-2px);
    }

    /* ===== SCROLL TO TOP ===== */
    .scroll-to-top {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 4px 10px rgba(108,117,125,0.3);
        transition: all 0.3s;
        z-index: 999;
        opacity: 0;
        visibility: hidden;
    }

    .scroll-to-top.show {
        opacity: 1;
        visibility: visible;
    }

    .scroll-to-top:hover {
        background: #ff8c00;
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(255,140,0,0.4);
    }

    /* ===== MOBILE SPECIFIC STYLES ===== */
    @media (max-width: 767px) {
        .container-fluid {
            padding-right: 10px;
            padding-left: 10px;
        }

        .backup-card-header {
            padding: 12px;
            font-size: 0.95rem;
        }

        .backup-btn {
            padding: 10px;
            font-size: 0.85rem;
        }

        .backup-footer {
            padding: 10px;
            font-size: 0.75rem;
        }
    }

    /* Small phones */
    @media (max-width: 480px) {
        .tools-grid {
            gap: 8px;
        }

        .tool-card-body {
            padding: 12px 8px;
        }

        .tool-icon {
            font-size: 1.5rem;
            margin-bottom: 8px;
        }

        .tool-title {
            font-size: 0.9rem;
            margin-bottom: 5px;
        }

        .tool-description {
            font-size: 0.7rem;
            margin-bottom: 10px;
        }

        .tool-btn {
            padding: 5px 8px;
            font-size: 0.65rem;
        }

        .backup-features li {
            font-size: 0.75rem;
        }

        .modal-title {
            font-size: 1rem;
        }

        .btn-modal {
            padding: 6px 15px;
            font-size: 0.8rem;
        }
    }
</style>

<div class="container-fluid">
    <!-- Section Header -->
    <div class="section-header">
        <h4><i class="fas fa-tools"></i> System Tools</h4>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tools Grid - 4x4 on Mobile -->
    <div class="tools-grid">
        <!-- Delete All Customers -->
        <div class="tool-card danger">
            <div class="tool-card-body">
                <div class="tool-icon danger">
                    <i class="fas fa-users"></i>
                </div>
                <h5 class="tool-title">Delete All Customers</h5>
                <p class="tool-description">Permanently remove all customer records. Orders become anonymous.</p>
                <button class="tool-btn danger" data-bs-toggle="modal" data-bs-target="#deleteCustomersModal">
                    <i class="fas fa-trash"></i> Delete All
                </button>
            </div>
        </div>

        <!-- Delete Unused Products -->
        <div class="tool-card warning">
            <div class="tool-card-body">
                <div class="tool-icon warning">
                    <i class="fas fa-box"></i>
                </div>
                <h5 class="tool-title">Delete Unused Products</h5>
                <p class="tool-description">Remove products that have never been ordered. Keeps products with order history.</p>
                <button class="tool-btn warning" data-bs-toggle="modal" data-bs-target="#deleteProductsModal">
                    <i class="fas fa-trash"></i> Delete Unused
                </button>
            </div>
        </div>

        <!-- Clear Inventory Logs -->
        <div class="tool-card info">
            <div class="tool-card-body">
                <div class="tool-icon info">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <h5 class="tool-title">Clear Inventory Logs</h5>
                <p class="tool-description">Delete all inventory change logs older than 30 days. Keeps recent logs.</p>
                <button class="tool-btn info" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                    <i class="fas fa-eraser"></i> Clear Old Logs
                </button>
            </div>
        </div>

        <!-- Delete All Orders (Danger) -->
        <div class="tool-card danger">
            <div class="tool-card-body">
                <div class="tool-icon danger">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h5 class="tool-title">Delete All Orders</h5>
                <p class="tool-description">⚠️ Permanently delete ALL orders and order items. This cannot be undone.</p>
                <button class="tool-btn danger" data-bs-toggle="modal" data-bs-target="#deleteOrdersModal">
                    <i class="fas fa-trash"></i> Delete All Orders
                </button>
            </div>
        </div>

        <!-- Daily Reset Setup - NEW TOOL -->
        <div class="tool-card daily-reset">
            <div class="tool-card-body">
                <div class="tool-icon daily-reset">
                    <i class="fas fa-clock"></i>
                </div>
                <span class="daily-reset-badge">New Feature</span>
                <h5 class="tool-title">Daily Reset Setup</h5>
                <p class="tool-description">Configure automatic daily reset for order IDs and reports. Runs every midnight.</p>
                <a href="setup_daily_reset" class="tool-btn success">
                    <i class="fas fa-cog"></i> Configure
                </a>
            </div>
        </div>

        <!-- Backup & Restore -->
        <div class="tool-card primary">
            <div class="tool-card-body">
                <div class="tool-icon primary">
                    <i class="fas fa-database"></i>
                </div>
                <h5 class="tool-title">Backup & Restore</h5>
                <p class="tool-description">Create, download, and restore database backups. Perfect for moving to another computer.</p>
                <a href="backup" class="tool-btn primary">
                    <i class="fas fa-database"></i> Manage Backups
                </a>
            </div>
        </div>

        <!-- System Health Check -->
        <div class="tool-card success">
            <div class="tool-card-body">
                <div class="tool-icon success">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <h5 class="tool-title">System Health</h5>
                <p class="tool-description">Check database status, cron jobs, and system configuration.</p>
                <a href="system_health" class="tool-btn success">
                    <i class="fas fa-stethoscope"></i> Check Health
                </a>
            </div>
        </div>

        <!-- Activity Logs -->
        <div class="tool-card info">
            <div class="tool-card-body">
                <div class="tool-icon info">
                    <i class="fas fa-history"></i>
                </div>
                <h5 class="tool-title">Activity Logs</h5>
                <p class="tool-description">View system activity, user actions, and audit trails.</p>
                <a href="activity_logs" class="tool-btn info">
                    <i class="fas fa-list"></i> View Logs
                </a>
            </div>
        </div>
    </div>

    <!-- Daily Reset Information Card -->
    <div class="info-card">
        <div class="info-header">
            <i class="fas fa-clock"></i> Daily Reset System - How It Works
        </div>
        <div class="info-body">
            <div class="info-grid">
                <div class="info-column">
                    <h6><i class="fas fa-database me-2" style="color: #008080;"></i> What Gets Reset:</h6>
                    <ul>
                        <li>✅ Order numbers restart from 1 each day (ORD-0001 format)</li>
                        <li>✅ Inventory log numbers restart daily</li>
                        <li>✅ Daily sales summaries are automatically created</li>
                        <li>✅ All data is preserved in archive tables</li>
                        <li>✅ Reports still show historical data</li>
                    </ul>
                </div>
                <div class="info-column">
                    <h6><i class="fas fa-clock me-2" style="color: #008080;"></i> Scheduled Tasks:</h6>
                    <ul>
                        <li>⏰ Runs automatically at 12:00 AM daily</li>
                        <li>⚙️ Can be set up via Windows Task Scheduler</li>
                        <li>📊 Creates daily sales summaries</li>
                        <li>📁 Archives orders older than 30 days</li>
                        <li>🔄 Order IDs reset to 1 each day</li>
                    </ul>
                </div>
            </div>
            <div class="mt-3 p-3 bg-light rounded">
                <small class="text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    After setup, order IDs will reset daily at midnight. All data is preserved in archive tables for historical reporting.
                    Click "Configure" above to set up the daily reset tables.
                </small>
            </div>
        </div>
    </div>

    <!-- Backup Section (Additional Info) -->
    <div class="backup-section">
        <h5 class="backup-title">
            <i class="fas fa-database"></i> Backup Options
        </h5>
        <div class="backup-grid">
            <!-- Database Only Backup -->
            <div class="backup-card">
                <div class="backup-card-header primary">
                    <i class="fas fa-database me-2"></i> Database Only
                </div>
                <div class="backup-card-body">
                    <ul class="backup-features">
                        <li><i class="fas fa-check-circle"></i> All database tables</li>
                        <li><i class="fas fa-check-circle"></i> All customers</li>
                        <li><i class="fas fa-check-circle"></i> All orders & transactions</li>
                        <li><i class="fas fa-check-circle"></i> All products & inventory</li>
                        <li><i class="fas fa-check-circle"></i> All staff accounts</li>
                    </ul>
                    <a href="backup.php?action=create_database" class="backup-btn primary" 
                       onclick="return confirm('Create database-only backup? This will backup ALL database tables and records.')">
                        <i class="fas fa-database me-2"></i> Create Database Backup
                    </a>
                </div>
                <div class="backup-footer">
                    <i class="fas fa-clock me-1"></i> Quick backup - under 30 seconds
                </div>
            </div>

            <!-- Full System Backup -->
            <div class="backup-card">
                <div class="backup-card-header success">
                    <i class="fas fa-boxes me-2"></i> Full System Backup
                </div>
                <div class="backup-card-body">
                    <ul class="backup-features">
                        <li><i class="fas fa-check-circle"></i> Complete database</li>
                        <li><i class="fas fa-check-circle"></i> Inventory Report (CSV)</li>
                        <li><i class="fas fa-check-circle"></i> Sales Report (CSV)</li>
                        <li><i class="fas fa-check-circle"></i> Customer List (CSV)</li>
                        <li><i class="fas fa-check-circle"></i> All product images</li>
                    </ul>
                    <a href="backup.php?action=create_full" class="backup-btn success" 
                       onclick="return confirm('Create FULL SYSTEM BACKUP? This may take a few minutes.')">
                        <i class="fas fa-boxes me-2"></i> Create Full Backup
                    </a>
                </div>
                <div class="backup-footer">
                    <i class="fas fa-clock me-1"></i> Complete backup - 1-3 minutes
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Customers Modal -->
<div class="modal fade" id="deleteCustomersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete All Customers
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Are you absolutely sure?</strong> This action <span class="text-danger">cannot be undone</span>.</p>
                <p>All customer records will be permanently deleted. Orders will remain but will show "Walk-in" as customer.</p>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmCustomers">
                    <label class="form-check-label" for="confirmCustomers">
                        I understand the consequences.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-modal" data-bs-dismiss="modal">Cancel</button>
                <a href="delete_customers" class="btn btn-danger btn-modal" id="deleteCustomersBtn" onclick="return document.getElementById('confirmCustomers').checked;">Delete Permanently</a>
            </div>
        </div>
    </div>
</div>

<!-- Delete Products Modal -->
<div class="modal fade" id="deleteProductsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete Unused Products
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>This will delete all products that have never been ordered. Products with existing orders will be kept.</p>
                <p>Are you sure?</p>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmProducts">
                    <label class="form-check-label" for="confirmProducts">
                        Yes, delete unused products.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-modal" data-bs-dismiss="modal">Cancel</button>
                <a href="delete_products" class="btn btn-warning btn-modal" id="deleteProductsBtn" onclick="return document.getElementById('confirmProducts').checked;">Delete Unused</a>
            </div>
        </div>
    </div>
</div>

<!-- Clear Logs Modal -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="fas fa-info-circle me-2"></i>Confirm Clear Old Logs
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Delete all inventory change logs older than 30 days. Recent logs (last 30 days) will be kept.</p>
                <p>Proceed?</p>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmLogs">
                    <label class="form-check-label" for="confirmLogs">
                        Yes, clear old logs.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-modal" data-bs-dismiss="modal">Cancel</button>
                <a href="clear_logs" class="btn btn-info btn-modal" id="clearLogsBtn" onclick="return document.getElementById('confirmLogs').checked;">Clear Old Logs</a>
            </div>
        </div>
    </div>
</div>

<!-- Delete All Orders Modal (Extra Dangerous) -->
<div class="modal fade" id="deleteOrdersModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-skull-crossbones me-2"></i>DANGER: Delete ALL Orders
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger"><strong>This will permanently delete EVERY order and order item. Sales reports will be empty.</strong></p>
                <p>Type <strong>DELETE ALL ORDERS</strong> in the box below to confirm.</p>
                <input type="text" class="form-control" id="confirmOrdersText" placeholder="DELETE ALL ORDERS">
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmOrders" disabled>
                    <label class="form-check-label" for="confirmOrders">
                        I understand this irreversible action.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-modal" data-bs-dismiss="modal">Cancel</button>
                <a href="delete_all_orders" class="btn btn-danger btn-modal" id="deleteOrdersBtn" disabled>Delete Everything</a>
            </div>
        </div>
    </div>
</div>

<!-- Transaction Cleanup Tool -->
<div class="tool-card danger">
    <div class="tool-card-body">
        <div class="tool-icon danger">
            <i class="fas fa-broom"></i>
        </div>
        <h5 class="tool-title">Cleanup Transactions</h5>
        <p class="tool-description">Fix "active transaction" errors during checkout. Cleans stuck database transactions.</p>
        <a href="/admin/cleanup_transaction" class="tool-btn danger">
            <i class="fas fa-broom"></i> Run Cleanup
        </a>
    </div>
</div>

<!-- Force Cleanup Tool -->
<div class="tool-card danger">
    <div class="tool-card-body">
        <div class="tool-icon danger">
            <i class="fas fa-skull-crossbones"></i>
        </div>
        <h5 class="tool-title">Force Cleanup</h5>
        <p class="tool-description">Nuclear option: Kill all connections to fix stuck transactions.</p>
        <a href="/admin/force_cleanup" class="tool-btn danger">
            <i class="fas fa-bomb"></i> Force Cleanup
        </a>
    </div>
</div>

<!-- Final Cleanup Tool -->
<div class="tool-card danger">
    <div class="tool-card-body">
        <div class="tool-icon danger">
            <i class="fas fa-broom"></i>
        </div>
        <h5 class="tool-title">Final Cleanup</h5>
        <p class="tool-description">Fix "active transaction" errors by killing all connections.</p>
        <a href="/admin/final_cleanup" class="tool-btn danger">
            <i class="fas fa-exclamation-triangle"></i> Run Cleanup
        </a>
    </div>
</div>

<!-- Clear Customer Session -->
<div class="tool-card warning">
    <div class="tool-card-body">
        <div class="tool-icon warning">
            <i class="fas fa-user-slash"></i>
        </div>
        <h5 class="tool-title">Clear Customer</h5>
        <p class="tool-description">Clear specific customer's cart and notifications.</p>
        <a href="/admin/clear_customer_session" class="tool-btn warning">
            <i class="fas fa-broom"></i> Clear Session
        </a>
    </div>
</div>

<!-- Add this inside your tools grid -->
<div class="tool-card danger">
    <div class="tool-card-body">
        <div class="tool-icon danger">
            <i class="fas fa-broom"></i>
        </div>
        <h5 class="tool-title">Reset Cashier Performance</h5>
        <p class="tool-description">⚠️ Reset cashier statistics by deleting order records. Data is archived first.</p>
        <a href="/admin/reset_cashier_performance" class="tool-btn danger">
            <i class="fas fa-undo-alt"></i> Reset Performance
        </a>
    </div>
</div>

<!-- Food Preparation Settings -->
<div class="tool-card info">
    <div class="tool-card-body">
        <div class="tool-icon info">
            <i class="fas fa-clock"></i>
        </div>
        <span class="daily-reset-badge">New Feature</span>
        <h5 class="tool-title">Food Preparation</h5>
        <p class="tool-description">Set estimated preparation times for different food categories. Customize how long each item takes to prepare.</p>
        <a href="/admin/preparation_settings" class="tool-btn info">
            <i class="fas fa-cog"></i> Configure Times
        </a>
    </div>
</div>

<!-- Fix Duplicate Cashiers -->
<div class="tool-card danger">
    <div class="tool-card-body">
        <div class="tool-icon danger">
            <i class="fas fa-users"></i>
        </div>
        <h5 class="tool-title">Fix Duplicate Cashiers</h5>
        <p class="tool-description">Find and merge duplicate cashier accounts. Preserves order history.</p>
        <a href="/admin/fix_duplicate_cashiers" class="tool-btn danger">
            <i class="fas fa-broom"></i> Fix Duplicates
        </a>
    </div>
</div>

<!-- Checkout Debug Tool -->
<div class="tool-card warning">
    <div class="tool-card-body">
        <div class="tool-icon warning">
            <i class="fas fa-bug"></i>
        </div>
        <h5 class="tool-title">Checkout Debugger</h5>
        <p class="tool-description">Diagnose and fix checkout issues, stuck transactions, and order problems.</p>
        <a href="/admin/debug_checkout_fixed" class="tool-btn warning">
            <i class="fas fa-tools"></i> Run Debugger
        </a>
    </div>
</div>


<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// Enable delete customers button only when checkbox checked
document.getElementById('confirmCustomers')?.addEventListener('change', function() {
    const deleteBtn = document.getElementById('deleteCustomersBtn');
    if (deleteBtn) {
        deleteBtn.style.pointerEvents = this.checked ? 'auto' : 'none';
        deleteBtn.style.opacity = this.checked ? '1' : '0.5';
    }
});

// Enable delete products button only when checkbox checked
document.getElementById('confirmProducts')?.addEventListener('change', function() {
    const deleteBtn = document.getElementById('deleteProductsBtn');
    if (deleteBtn) {
        deleteBtn.style.pointerEvents = this.checked ? 'auto' : 'none';
        deleteBtn.style.opacity = this.checked ? '1' : '0.5';
    }
});

// Enable clear logs button only when checkbox checked
document.getElementById('confirmLogs')?.addEventListener('change', function() {
    const clearBtn = document.getElementById('clearLogsBtn');
    if (clearBtn) {
        clearBtn.style.pointerEvents = this.checked ? 'auto' : 'none';
        clearBtn.style.opacity = this.checked ? '1' : '0.5';
    }
});

// Enable delete orders button only when text matches
document.getElementById('confirmOrdersText')?.addEventListener('input', function() {
    const confirmCheck = document.getElementById('confirmOrders');
    const deleteBtn = document.getElementById('deleteOrdersBtn');
    if (this.value === 'DELETE ALL ORDERS') {
        confirmCheck.disabled = false;
    } else {
        confirmCheck.disabled = true;
        confirmCheck.checked = false;
        deleteBtn.disabled = true;
        deleteBtn.style.opacity = '0.5';
    }
});

document.getElementById('confirmOrders')?.addEventListener('change', function() {
    const deleteBtn = document.getElementById('deleteOrdersBtn');
    deleteBtn.disabled = !this.checked;
    deleteBtn.style.opacity = this.checked ? '1' : '0.5';
});

// Scroll to top function
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Show/hide scroll button based on scroll position
window.addEventListener('scroll', function() {
    const scrollButton = document.getElementById('scrollToTop');
    if (window.scrollY > 300) {
        scrollButton.classList.add('show');
    } else {
        scrollButton.classList.remove('show');
    }
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
</script>

<?php include '../../includes/footer.php'; ?>