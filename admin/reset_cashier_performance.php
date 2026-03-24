<?php
require_once '../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

// Get list of cashiers
$cashiers = $pdo->query("
    SELECT id, username, 
           (SELECT COUNT(*) FROM tbl_orders WHERE created_by = id) as orders_created,
           (SELECT COUNT(*) FROM tbl_orders WHERE completed_by = id) as orders_completed,
           (SELECT COALESCE(SUM(total_amount), 0) FROM tbl_orders WHERE created_by = id) as total_sales
    FROM tbl_users 
    WHERE role = 'cashier' 
    ORDER BY username
")->fetchAll();

include '../includes/header.php';
?>

<style>
    .reset-card {
        max-width: 800px;
        margin: 30px auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .card-header {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 20px 25px;
    }
    
    .card-header h4 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .card-header h4 i {
        font-size: 1.8rem;
    }
    
    .card-body {
        padding: 25px;
    }
    
    .warning-box {
        background: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 25px;
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }
    
    .warning-box i {
        font-size: 2rem;
        color: #856404;
    }
    
    .warning-box .warning-text {
        flex: 1;
    }
    
    .warning-box .warning-text h5 {
        color: #856404;
        margin-bottom: 5px;
        font-weight: 600;
    }
    
    .warning-box .warning-text p {
        color: #856404;
        margin: 0;
        font-size: 0.9rem;
    }
    
    .reset-section {
        background: #f8f9fa;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        border: 1px solid #e9ecef;
    }
    
    .reset-section h5 {
        color: #dc3545;
        font-weight: 600;
        margin-bottom: 15px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .reset-section h5 i {
        font-size: 1.2rem;
    }
    
    .cashier-list {
        max-height: 300px;
        overflow-y: auto;
        border: 1px solid #dee2e6;
        border-radius: 10px;
        margin-bottom: 15px;
    }
    
    .cashier-item {
        display: flex;
        align-items: center;
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        transition: background 0.2s;
    }
    
    .cashier-item:last-child {
        border-bottom: none;
    }
    
    .cashier-item:hover {
        background: #f8f9fa;
    }
    
    .cashier-item input[type="radio"] {
        margin-right: 15px;
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .cashier-item label {
        flex: 1;
        cursor: pointer;
        margin: 0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .cashier-name {
        font-weight: 600;
        color: #333;
        font-size: 1rem;
    }
    
    .cashier-stats {
        display: flex;
        gap: 20px;
        font-size: 0.85rem;
    }
    
    .cashier-stats span {
        background: #e9ecef;
        padding: 3px 10px;
        border-radius: 50px;
        color: #495057;
    }
    
    .cashier-stats .orders {
        background: #cce5ff;
        color: #004085;
    }
    
    .cashier-stats .sales {
        background: #d4edda;
        color: #155724;
    }
    
    .date-range {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    
    .date-range .form-group {
        flex: 1;
        min-width: 200px;
    }
    
    .form-group label {
        font-weight: 500;
        color: #495057;
        margin-bottom: 5px;
        display: block;
    }
    
    .form-group input {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e9ecef;
        border-radius: 10px;
        font-family: 'Poppins', sans-serif;
        transition: all 0.2s;
    }
    
    .form-group input:focus {
        border-color: #dc3545;
        outline: none;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1);
    }
    
    .btn-reset {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 12px 20px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
    }
    
    .btn-reset:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
    }
    
    .btn-reset:active {
        transform: translateY(0);
    }
    
    .btn-reset:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }
    
    .btn-secondary {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 10px;
        padding: 12px 20px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        width: 100%;
        text-decoration: none;
    }
    
    .btn-secondary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(108, 117, 125, 0.3);
        color: white;
    }
    
    .reset-options {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .reset-option {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px 15px;
        text-align: center;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .reset-option:hover {
        border-color: #dc3545;
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(220, 53, 69, 0.1);
    }
    
    .reset-option.selected {
        border-color: #dc3545;
        background: #fff5f5;
    }
    
    .reset-option i {
        font-size: 2rem;
        color: #dc3545;
        margin-bottom: 10px;
    }
    
    .reset-option h6 {
        font-weight: 600;
        color: #333;
        margin-bottom: 5px;
    }
    
    .reset-option p {
        font-size: 0.8rem;
        color: #666;
        margin: 0;
    }
    
    .alert {
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideDown 0.3s ease;
    }
    
    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
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
    
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.8);
        display: none;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }
    
    .loading-overlay.show {
        display: flex;
    }
    
    .loading-spinner {
        width: 50px;
        height: 50px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid #dc3545;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    
    .result-details {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-top: 15px;
        font-size: 0.9rem;
    }
    
    .result-details p {
        margin: 5px 0;
        display: flex;
        justify-content: space-between;
    }
    
    .result-details .badge {
        background: #dc3545;
        color: white;
        padding: 3px 8px;
        border-radius: 50px;
        font-size: 0.8rem;
    }
    
    @media (max-width: 768px) {
        .reset-options {
            grid-template-columns: 1fr;
        }
        
        .date-range {
            flex-direction: column;
            gap: 10px;
        }
        
        .cashier-stats {
            flex-direction: column;
            gap: 5px;
        }
        
        .cashier-item label {
            flex-direction: column;
            align-items: flex-start;
            gap: 5px;
        }
    }
</style>

<!-- Loading Overlay -->
<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<div class="container-fluid">
    <div class="reset-card">
        <div class="card-header">
            <h4>
                <i class="fas fa-broom"></i>
                Reset Cashier Performance
            </h4>
        </div>
        
        <div class="card-body">
            <!-- Warning Box -->
            <div class="warning-box">
                <i class="fas fa-exclamation-triangle"></i>
                <div class="warning-text">
                    <h5>⚠️ WARNING: This action cannot be undone!</h5>
                    <p>Resetting cashier performance will permanently delete order records and reset statistics. All data will be archived before deletion.</p>
                </div>
            </div>
            
            <!-- Success/Error Messages -->
            <div id="messageContainer"></div>
            
            <!-- Reset Options -->
            <div class="reset-options">
                <div class="reset-option selected" id="optionAll" onclick="selectOption('all')">
                    <i class="fas fa-users"></i>
                    <h6>Reset All Cashiers</h6>
                    <p>Reset performance for all cashiers</p>
                </div>
                <div class="reset-option" id="optionSingle" onclick="selectOption('single')">
                    <i class="fas fa-user"></i>
                    <h6>Reset Single Cashier</h6>
                    <p>Reset specific cashier only</p>
                </div>
                <div class="reset-option" id="optionDate" onclick="selectOption('date')">
                    <i class="fas fa-calendar-alt"></i>
                    <h6>Reset by Date Range</h6>
                    <p>Reset orders within date range</p>
                </div>
            </div>
            
            <!-- Reset All Cashiers Form -->
            <div id="resetAllForm" class="reset-section" style="display: block;">
                <h5><i class="fas fa-users"></i> Reset ALL Cashiers</h5>
                <div class="date-range">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" id="allFromDate" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" id="allToDate" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <button class="btn-reset" onclick="resetAllCashiers()" id="resetAllBtn">
                    <i class="fas fa-broom"></i> Reset ALL Cashiers Performance
                </button>
            </div>
            
            <!-- Reset Single Cashier Form -->
            <div id="resetSingleForm" class="reset-section" style="display: none;">
                <h5><i class="fas fa-user"></i> Reset Single Cashier</h5>
                <div class="cashier-list">
                    <?php foreach ($cashiers as $cashier): ?>
                    <div class="cashier-item">
                        <input type="radio" name="cashier" id="cashier_<?php echo $cashier['id']; ?>" value="<?php echo $cashier['id']; ?>">
                        <label for="cashier_<?php echo $cashier['id']; ?>">
                            <span class="cashier-name"><?php echo htmlspecialchars($cashier['username']); ?></span>
                            <span class="cashier-stats">
                                <span class="orders"><i class="fas fa-shopping-cart"></i> <?php echo $cashier['orders_created'] + $cashier['orders_completed']; ?> orders</span>
                                <span class="sales"><i class="fas fa-money-bill"></i> ₱<?php echo number_format($cashier['total_sales'], 0); ?></span>
                            </span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="date-range">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" id="singleFromDate" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" id="singleToDate" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <button class="btn-reset" onclick="resetSingleCashier()" id="resetSingleBtn" disabled>
                    <i class="fas fa-broom"></i> Reset Selected Cashier
                </button>
            </div>
            
            <!-- Reset by Date Range Form -->
            <div id="resetDateForm" class="reset-section" style="display: none;">
                <h5><i class="fas fa-calendar-alt"></i> Reset by Date Range</h5>
                <div class="date-range">
                    <div class="form-group">
                        <label>From Date</label>
                        <input type="date" id="rangeFromDate" value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>To Date</label>
                        <input type="date" id="rangeToDate" value="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <button class="btn-reset" onclick="resetByDateRange()" id="resetDateBtn">
                    <i class="fas fa-broom"></i> Reset Orders in Date Range
                </button>
            </div>
            
            <!-- Action Buttons -->
            <div class="row mt-4">
                <div class="col-md-6 mb-2">
                    <a href="/modules/reports/cashier_performance" class="btn-secondary">
                        <i class="fas fa-chart-bar me-2"></i> Back to Performance Report
                    </a>
                </div>
                <div class="col-md-6 mb-2">
                    <a href="/tools" class="btn-secondary">
                        <i class="fas fa-tools me-2"></i> Back to Tools
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let selectedOption = 'all';

function selectOption(option) {
    selectedOption = option;
    
    // Update UI
    document.querySelectorAll('.reset-option').forEach(el => {
        el.classList.remove('selected');
    });
    document.getElementById('option' + option.charAt(0).toUpperCase() + option.slice(1)).classList.add('selected');
    
    // Show/hide forms
    document.getElementById('resetAllForm').style.display = option === 'all' ? 'block' : 'none';
    document.getElementById('resetSingleForm').style.display = option === 'single' ? 'block' : 'none';
    document.getElementById('resetDateForm').style.display = option === 'date' ? 'block' : 'none';
}

// Enable/disable single reset button based on radio selection
document.querySelectorAll('input[name="cashier"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.getElementById('resetSingleBtn').disabled = false;
    });
});

function showMessage(message, isSuccess = true) {
    const container = document.getElementById('messageContainer');
    const className = isSuccess ? 'alert-success' : 'alert-danger';
    const icon = isSuccess ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    container.innerHTML = `
        <div class="alert ${className}">
            <i class="fas ${icon}"></i>
            ${message}
        </div>
    `;
    
    setTimeout(() => {
        container.innerHTML = '';
    }, 5000);
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.add('show');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('show');
}

function resetAllCashiers() {
    const fromDate = document.getElementById('allFromDate').value;
    const toDate = document.getElementById('allToDate').value;
    
    if (!confirm(`⚠️ RESET ALL CASHIERS PERFORMANCE?\n\nThis will permanently delete ALL orders from ${fromDate} to ${toDate} for ALL cashiers.\n\nThis action CANNOT be undone!`)) {
        return;
    }
    
    showLoading();
    
    const formData = new FormData();
    formData.append('action', 'reset_all');
    formData.append('from_date', fromDate);
    formData.append('to_date', toDate);
    
    fetch('/api/reset_cashier_performance.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            let details = '';
            if (data.details) {
                details = `<div class="result-details">
                    <p><span>📦 Orders deleted:</span> <span class="badge">${data.details.deleted_orders}</span></p>
                    <p><span>📝 Items deleted:</span> <span class="badge">${data.details.deleted_items}</span></p>
                    <p><span>📚 Archived:</span> <span class="badge">${data.details.archived}</span></p>
                </div>`;
            }
            showMessage(data.message + details, true);
            setTimeout(() => {
                window.location.href = '/modules/reports/cashier_performance';
            }, 2000);
        } else {
            showMessage('❌ Error: ' + (data.error || 'Unknown error'), false);
        }
    })
    .catch(error => {
        hideLoading();
        showMessage('❌ Network error: ' + error, false);
    });
}

function resetSingleCashier() {
    const cashierId = document.querySelector('input[name="cashier"]:checked')?.value;
    if (!cashierId) {
        showMessage('❌ Please select a cashier', false);
        return;
    }
    
    const fromDate = document.getElementById('singleFromDate').value;
    const toDate = document.getElementById('singleToDate').value;
    const cashierName = document.querySelector('input[name="cashier"]:checked')?.closest('.cashier-item')?.querySelector('.cashier-name')?.textContent || 'Selected Cashier';
    
    if (!confirm(`⚠️ RESET PERFORMANCE FOR ${cashierName}?\n\nThis will permanently delete ALL orders from ${fromDate} to ${toDate} for this cashier.\n\nThis action CANNOT be undone!`)) {
        return;
    }
    
    showLoading();
    
    const formData = new FormData();
    formData.append('action', 'reset_single');
    formData.append('cashier_id', cashierId);
    formData.append('from_date', fromDate);
    formData.append('to_date', toDate);
    
    fetch('/api/reset_cashier_performance.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            let details = '';
            if (data.details) {
                details = `<div class="result-details">
                    <p><span>📦 Orders deleted:</span> <span class="badge">${data.details.deleted_orders}</span></p>
                    <p><span>📝 Items deleted:</span> <span class="badge">${data.details.deleted_items}</span></p>
                    <p><span>📚 Archived:</span> <span class="badge">${data.details.archived}</span></p>
                </div>`;
            }
            showMessage(data.message + details, true);
            setTimeout(() => {
                window.location.href = '/modules/reports/cashier_performance';
            }, 2000);
        } else {
            showMessage('❌ Error: ' + (data.error || 'Unknown error'), false);
        }
    })
    .catch(error => {
        hideLoading();
        showMessage('❌ Network error: ' + error, false);
    });
}

function resetByDateRange() {
    const fromDate = document.getElementById('rangeFromDate').value;
    const toDate = document.getElementById('rangeToDate').value;
    
    if (!confirm(`⚠️ RESET ORDERS BY DATE RANGE?\n\nThis will permanently delete ALL orders from ${fromDate} to ${toDate} for ALL cashiers.\n\nThis action CANNOT be undone!`)) {
        return;
    }
    
    showLoading();
    
    const formData = new FormData();
    formData.append('action', 'reset_by_date_range');
    formData.append('from_date', fromDate);
    formData.append('to_date', toDate);
    
    fetch('/api/reset_cashier_performance.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            let details = '';
            if (data.details) {
                details = `<div class="result-details">
                    <p><span>📦 Orders deleted:</span> <span class="badge">${data.details.deleted_orders}</span></p>
                    <p><span>📝 Items deleted:</span> <span class="badge">${data.details.deleted_items}</span></p>
                    <p><span>📚 Archived:</span> <span class="badge">${data.details.archived}</span></p>
                </div>`;
            }
            showMessage(data.message + details, true);
            setTimeout(() => {
                window.location.href = '/modules/reports/cashier_performance';
            }, 2000);
        } else {
            showMessage('❌ Error: ' + (data.error || 'Unknown error'), false);
        }
    })
    .catch(error => {
        hideLoading();
        showMessage('❌ Network error: ' + error, false);
    });
}

// Set max dates to today
document.querySelectorAll('input[type="date"]').forEach(input => {
    input.max = '<?php echo date('Y-m-d'); ?>';
});
</script>

<?php include '../includes/footer.php'; ?>