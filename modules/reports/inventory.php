<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

// Handle single log deletion
if (isset($_GET['delete'])) {
    $log_id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM tbl_inventory_logs WHERE id = ?");
        $stmt->execute([$log_id]);
        $_SESSION['success'] = "Inventory log deleted successfully.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to delete log: " . $e->getMessage();
    }
    header('Location: inventory.php');
    exit;
}

// Handle bulk delete (older than 30 days)
if (isset($_POST['delete_old'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM tbl_inventory_logs WHERE log_time < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $stmt->execute();
        $deletedCount = $stmt->rowCount();
        $_SESSION['success'] = "Successfully deleted " . $deletedCount . " log(s) older than 30 days.";
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to clear logs: " . $e->getMessage();
    }
    header('Location: inventory.php');
    exit;
}

// Handle search and filters
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$changeType = isset($_GET['change_type']) ? $_GET['change_type'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query with filters
$query = "
    SELECT l.*, p.name AS product_name, u.username 
    FROM tbl_inventory_logs l 
    JOIN tbl_products p ON l.product_id = p.id 
    LEFT JOIN tbl_users u ON l.user_id = u.id 
";
$params = [];

$whereConditions = [];

if (!empty($searchTerm)) {
    $whereConditions[] = "(p.name LIKE ? OR u.username LIKE ? OR l.change_type LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

if (!empty($changeType)) {
    $whereConditions[] = "l.change_type = ?";
    $params[] = $changeType;
}

if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(l.log_time) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "DATE(l.log_time) <= ?";
    $params[] = $dateTo;
}

if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}

$query .= " ORDER BY l.log_time DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get total count
$totalLogs = count($logs);

// Get statistics for summary cards
$stats = [
    'total_additions' => $pdo->query("SELECT SUM(quantity_changed) FROM tbl_inventory_logs WHERE change_type = 'add'")->fetchColumn() ?: 0,
    'total_subtractions' => $pdo->query("SELECT SUM(quantity_changed) FROM tbl_inventory_logs WHERE change_type = 'subtract'")->fetchColumn() ?: 0,
    'total_sets' => $pdo->query("SELECT COUNT(*) FROM tbl_inventory_logs WHERE change_type = 'set'")->fetchColumn() ?: 0,
    'unique_products' => $pdo->query("SELECT COUNT(DISTINCT product_id) FROM tbl_inventory_logs")->fetchColumn() ?: 0,
];

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
        max-width: 1400px;
    }

    /* ===== SECTION HEADER ===== */
    .section-header {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e0e0e0;
    }

    @media (min-width: 768px) {
        .section-header {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .section-header h4 {
        font-size: 1.3rem;
        font-weight: 600;
        color: #333;
        margin: 0;
    }

    .section-header h4 i {
        color: #17a2b8;
        margin-right: 8px;
    }

    .header-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn-action {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 8px 15px;
        font-size: 0.85rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: all 0.3s;
        box-shadow: 0 4px 10px rgba(23,162,184,0.2);
        white-space: nowrap;
    }

    .btn-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(23,162,184,0.3);
        color: white;
    }

    .btn-action.warning {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #333;
    }

    .btn-action.warning:hover {
        background: #e0a800;
    }

    .btn-action.success {
        background: linear-gradient(135deg, #28a745, #218838);
    }

    /* ===== STATS CARDS - KEPT AS REQUESTED ===== */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 25px;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
    }

    .stat-card {
        background: white;
        border-radius: 15px;
        padding: 20px 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        transition: all 0.3s;
        border-left: 4px solid #17a2b8;
        text-align: center;
    }

    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    }

    .stat-icon {
        font-size: 2rem;
        color: #17a2b8;
        margin-bottom: 10px;
    }

    .stat-value {
        font-size: 1.8rem;
        font-weight: 700;
        color: #333;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #666;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    @media (max-width: 768px) {
        .stat-card {
            padding: 15px 10px;
        }
        .stat-icon {
            font-size: 1.5rem;
        }
        .stat-value {
            font-size: 1.3rem;
        }
        .stat-label {
            font-size: 0.75rem;
        }
    }

    /* ===== SEARCH SECTION ===== */
    .search-section {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .search-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    @media (min-width: 768px) {
        .search-form {
            display: grid;
            grid-template-columns: repeat(4, 1fr) auto;
            gap: 15px;
            align-items: end;
        }
    }

    .form-group {
        display: flex;
        flex-direction: column;
    }

    .form-group label {
        font-weight: 500;
        color: #555;
        margin-bottom: 5px;
        font-size: 0.85rem;
    }

    .search-wrapper {
        position: relative;
    }

    .search-wrapper i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
        font-size: 0.9rem;
        z-index: 2;
    }

    .search-input {
        width: 100%;
        padding: 10px 15px 10px 45px;
        border: 2px solid #e0e0e0;
        border-radius: 50px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.9rem;
        transition: all 0.3s;
        background: white;
    }

    .search-input:focus {
        border-color: #17a2b8;
        box-shadow: 0 0 0 3px rgba(23,162,184,0.1);
        outline: none;
    }

    .filter-select, .date-input {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 50px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.9rem;
        background: white;
        transition: all 0.3s;
    }

    .filter-select {
        padding-right: 40px;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
    }

    .button-group {
        display: flex;
        gap: 10px;
    }

    @media (min-width: 768px) {
        .button-group {
            grid-column: span 1;
            align-self: end;
        }
    }

    .btn-search, .btn-reset {
        padding: 10px 20px;
        font-size: 0.9rem;
        border-radius: 50px;
        border: none;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        white-space: nowrap;
    }

    .btn-search {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        flex: 1;
    }

    .btn-reset {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        text-decoration: none;
        flex: 1;
    }

    .search-stats {
        margin-top: 15px;
        padding-top: 10px;
        border-top: 1px solid #eee;
        font-size: 0.9rem;
        color: #666;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .search-term {
        background: #d1ecf1;
        color: #0c5460;
        padding: 5px 15px;
        border-radius: 50px;
        font-size: 0.85rem;
    }

    /* ===== TABLE WITH SCROLLABLE BODY ===== */
    .table-container {
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        overflow: hidden;
        margin-bottom: 20px;
        position: relative;
        height: 500px; /* Fixed height for the entire table container */
        display: flex;
        flex-direction: column;
    }

    .table-header {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        padding: 15px 20px;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    .table-header i {
        margin-right: 8px;
    }

    .table-header .badge {
        background: rgba(255,255,255,0.2);
        color: white;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.85rem;
    }

    /* Scrollable table wrapper */
    .table-scroll {
        overflow-y: auto;
        flex: 1;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    /* Fixed header that stays on top when scrolling */
    .table thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table thead th {
        background: #f8f9fa;
        color: #333;
        font-weight: 600;
        border-bottom: 2px solid #17a2b8;
        padding: 12px 15px;
        white-space: nowrap;
        font-size: 0.85rem;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .table tbody td {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
    }

    /* Badges for change types */
    .badge-change {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        white-space: nowrap;
    }

    .badge-add {
        background: #28a745;
        color: white;
    }

    .badge-subtract {
        background: #ffc107;
        color: #333;
    }

    .badge-set {
        background: #17a2b8;
        color: white;
    }

    /* Delete button */
    .btn-delete-log {
        border: 1px solid #dc3545;
        color: #dc3545;
        padding: 5px 12px;
        border-radius: 50px;
        text-decoration: none;
        font-size: 0.8rem;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: none;
        cursor: pointer;
    }

    .btn-delete-log:hover {
        background: #dc3545;
        color: white;
    }

    .btn-delete-log i {
        font-size: 0.8rem;
    }

    /* Highlight search term */
    .highlight {
        background-color: #fff3cd;
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: 600;
    }

    /* No results */
    .no-results {
        text-align: center;
        padding: 50px 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .no-results i {
        font-size: 3.5rem;
        color: #ddd;
        margin-bottom: 15px;
    }

    .no-results h3 {
        font-size: 1.2rem;
        color: #333;
        margin-bottom: 10px;
    }

    .no-results p {
        color: #666;
        font-size: 0.9rem;
    }

    /* Scroll to top button */
    .scroll-to-top {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 45px;
        height: 45px;
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 5px 15px rgba(23,162,184,0.3);
        transition: all 0.3s;
        z-index: 1000;
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
    }

    /* Mobile specific */
    @media (max-width: 768px) {
        .table-container {
            height: 450px;
        }

        .table thead th {
            padding: 8px 10px;
            font-size: 0.8rem;
        }

        .table tbody td {
            padding: 6px 10px;
            font-size: 0.8rem;
        }

        .badge-change {
            padding: 4px 8px;
            font-size: 0.7rem;
        }

        .btn-delete-log {
            padding: 4px 8px;
            font-size: 0.7rem;
        }
    }

    @media (max-width: 480px) {
        .table-container {
            height: 400px;
        }

        .table {
            min-width: 800px; /* Allow horizontal scroll on very small screens */
        }

        .table thead th {
            padding: 6px 8px;
            font-size: 0.75rem;
        }

        .table tbody td {
            padding: 4px 8px;
            font-size: 0.75rem;
        }

        .stats-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
</style>

<div class="container-fluid">
    <!-- Section Header -->
    <div class="section-header">
        <h4><i class="fas fa-clipboard-list"></i> Inventory Change Logs</h4>
        <div class="header-actions">
            <form method="post" action="clear_logs.php" style="display: inline;" onsubmit="return confirm('Delete all logs older than 30 days? This cannot be undone.');">
                <button type="submit" name="delete_old" class="btn-action warning">
                    <i class="fas fa-trash-alt"></i> Delete Old Logs
                </button>
            </form>
            <a href="export_inventory.php" class="btn-action success">
                <i class="fas fa-download"></i> Export CSV
            </a>
        </div>
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

    <!-- STATISTICS CARDS - KEPT AS REQUESTED -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-plus-circle"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total_additions']); ?></div>
            <div class="stat-label">Additions</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-minus-circle"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total_subtractions']); ?></div>
            <div class="stat-label">Subtractions</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-equals"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['total_sets']); ?></div>
            <div class="stat-label">Stock Sets</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-boxes"></i>
            </div>
            <div class="stat-value"><?php echo number_format($stats['unique_products']); ?></div>
            <div class="stat-label">Products</div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="search-section">
        <form method="get" action="" class="search-form">
            <!-- Search Input -->
            <div class="form-group">
                <label for="search">Search Logs</label>
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="search"
                           name="search" 
                           class="search-input" 
                           placeholder="Product, user, change type..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
            </div>
            
            <!-- Change Type Filter -->
            <div class="form-group">
                <label for="change_type">Change Type</label>
                <select name="change_type" id="change_type" class="filter-select">
                    <option value="">All Types</option>
                    <option value="add" <?php echo $changeType === 'add' ? 'selected' : ''; ?>>Additions</option>
                    <option value="subtract" <?php echo $changeType === 'subtract' ? 'selected' : ''; ?>>Subtractions</option>
                    <option value="set" <?php echo $changeType === 'set' ? 'selected' : ''; ?>>Sets</option>
                </select>
            </div>
            
            <!-- Date From -->
            <div class="form-group">
                <label for="date_from">From Date</label>
                <input type="date" name="date_from" id="date_from" class="date-input" value="<?php echo $dateFrom; ?>">
            </div>
            
            <!-- Date To -->
            <div class="form-group">
                <label for="date_to">To Date</label>
                <input type="date" name="date_to" id="date_to" class="date-input" value="<?php echo $dateTo; ?>">
            </div>
            
            <!-- Buttons -->
            <div class="button-group">
                <button type="submit" class="btn-search">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <?php if (!empty($searchTerm) || !empty($changeType) || !empty($dateFrom) || !empty($dateTo)): ?>
                    <a href="inventory.php" class="btn-reset">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
        
        <!-- Search Stats -->
        <?php if (!empty($searchTerm) || !empty($changeType) || !empty($dateFrom) || !empty($dateTo)): ?>
        <div class="search-stats">
            <div>
                <i class="fas fa-clipboard-list me-1"></i>
                <strong><?php echo $totalLogs; ?></strong> log<?php echo $totalLogs != 1 ? 's' : ''; ?> found
            </div>
            
            <?php if (!empty($searchTerm)): ?>
                <div class="search-term">
                    <i class="fas fa-tag"></i> "<?php echo htmlspecialchars($searchTerm); ?>"
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Logs Table with Scrollable Body -->
    <?php if (empty($logs)): ?>
        <div class="no-results">
            <i class="fas fa-clipboard-list"></i>
            <h3>No Logs Found</h3>
            <p>No inventory logs match your search criteria.</p>
            <a href="inventory.php" class="btn-search" style="display: inline-block; width: auto; padding: 10px 25px;">
                <i class="fas fa-times"></i> Clear Filters
            </a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <div class="table-header">
                <div>
                    <i class="fas fa-list"></i> Inventory Logs
                </div>
                <span class="badge"><?php echo $totalLogs; ?> records</span>
            </div>
            <div class="table-scroll">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Date & Time</th>
                            <th>Product</th>
                            <th>User</th>
                            <th>Change Type</th>
                            <th>Qty Changed</th>
                            <th>Previous</th>
                            <th>New</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $index => $log): 
                            // Highlight search term
                            $productName = htmlspecialchars($log['product_name']);
                            $username = htmlspecialchars($log['username'] ?? 'System');
                            
                            if (!empty($searchTerm)) {
                                $productName = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="highlight">$1</span>', $productName);
                                $username = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="highlight">$1</span>', $username);
                            }
                            
                            // Set badge class based on change type
                            $badgeClass = '';
                            $icon = '';
                            if ($log['change_type'] == 'add') {
                                $badgeClass = 'badge-add';
                                $icon = 'fa-plus-circle';
                            } elseif ($log['change_type'] == 'subtract') {
                                $badgeClass = 'badge-subtract';
                                $icon = 'fa-minus-circle';
                            } else {
                                $badgeClass = 'badge-set';
                                $icon = 'fa-equals';
                            }
                        ?>
                        <tr>
                            <td><?php echo $log['id']; ?></td>
                            <td><?php echo date('M d, Y H:i', strtotime($log['log_time'])); ?></td>
                            <td><?php echo $productName; ?></td>
                            <td><?php echo $username; ?></td>
                            <td>
                                <span class="badge-change <?php echo $badgeClass; ?>">
                                    <i class="fas <?php echo $icon; ?> me-1"></i>
                                    <?php echo ucfirst($log['change_type']); ?>
                                </span>
                            </td>
                            <td><?php echo $log['quantity_changed']; ?></td>
                            <td><?php echo $log['previous_stock']; ?></td>
                            <td><?php echo $log['new_stock']; ?></td>
                            <td>
                                <a href="?delete=<?php echo $log['id']; ?>" 
                                   class="btn-delete-log" 
                                   title="Delete Log"
                                   onclick="return confirm('Delete this log entry? This cannot be undone.');">
                                    <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// Scroll to top function
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Show/hide scroll button based on page scroll position
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