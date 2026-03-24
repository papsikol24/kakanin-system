<?php
require_once '../../includes/config.php';
requireLogin();

// Handle search
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$dateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$dateTo = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Build query with search
$query = "SELECT o.*, 
          CASE 
              WHEN o.customer_name IS NOT NULL AND o.customer_name != '' THEN o.customer_name 
              ELSE 'Walk-in' 
          END as display_customer
          FROM tbl_orders o";
$params = [];

$whereConditions = [];

if (!empty($searchTerm)) {
    $whereConditions[] = "(o.id LIKE ? OR o.customer_name LIKE ? OR o.payment_method LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

if (!empty($status)) {
    $whereConditions[] = "o.status = ?";
    $params[] = $status;
}

if (!empty($dateFrom)) {
    $whereConditions[] = "DATE(o.order_date) >= ?";
    $params[] = $dateFrom;
}

if (!empty($dateTo)) {
    $whereConditions[] = "DATE(o.order_date) <= ?";
    $params[] = $dateTo;
}

if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}

$query .= " ORDER BY o.order_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get total count
$totalOrders = count($orders);

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
        color: #d35400;
        margin-right: 8px;
    }

    .btn-new {
        background: linear-gradient(135deg, #28a745, #218838);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 8px 20px;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s;
        box-shadow: 0 4px 10px rgba(40,167,69,0.2);
        width: fit-content;
    }

    .btn-new:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(40,167,69,0.3);
        color: white;
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
        border-color: #d35400;
        box-shadow: 0 0 0 3px rgba(211,84,0,0.1);
        outline: none;
    }

    .filter-select {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 50px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.9rem;
        background: white;
        transition: all 0.3s;
        padding-right: 40px;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23333' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 15px center;
    }

    .date-input {
        width: 100%;
        padding: 10px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 50px;
        font-family: 'Poppins', sans-serif;
        font-size: 0.9rem;
        background: white;
        transition: all 0.3s;
    }

    .date-input:focus {
        border-color: #d35400;
        box-shadow: 0 0 0 3px rgba(211,84,0,0.1);
        outline: none;
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
        flex: 1;
    }

    .btn-search {
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
    }

    .btn-reset {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        text-decoration: none;
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
        background: #fff3e0;
        color: #d35400;
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
        height: 550px;
        display: flex;
        flex-direction: column;
    }

    .table-header {
        background: linear-gradient(135deg, #d35400, #e67e22);
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

    .table-scroll {
        overflow-y: auto;
        flex: 1;
    }

    .table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9rem;
    }

    .table thead {
        position: sticky;
        top: 0;
        z-index: 10;
    }

    .table thead th {
        background: #f8f9fa;
        color: #333;
        font-weight: 600;
        border-bottom: 2px solid #d35400;
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

    /* Table Badges */
    .badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 500;
        text-align: center;
        white-space: nowrap;
    }

    .badge.bg-order {
        background: #e67e22;
        color: white;
    }

    .badge.bg-payment {
        background: #3498db;
        color: white;
    }

    .badge.bg-completed {
        background: #27ae60;
        color: white;
    }

    .badge.bg-pending {
        background: #f39c12;
        color: white;
    }

    .badge.bg-cancelled {
        background: #e74c3c;
        color: white;
    }

    /* Action button */
    .btn-outline-view {
        border: 1px solid #d35400;
        color: #d35400;
        padding: 5px 12px;
        border-radius: 50px;
        text-decoration: none;
        font-size: 0.8rem;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .btn-outline-view:hover {
        background: #d35400;
        color: white;
    }

    .btn-outline-view i {
        font-size: 0.8rem;
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
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 5px 15px rgba(211,84,0,0.3);
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
            height: 500px;
        }

        .table thead th {
            padding: 8px 10px;
            font-size: 0.8rem;
        }

        .table tbody td {
            padding: 6px 10px;
            font-size: 0.8rem;
        }

        .badge {
            padding: 4px 8px;
            font-size: 0.7rem;
        }

        .btn-outline-view {
            padding: 4px 8px;
            font-size: 0.7rem;
        }
    }

    @media (max-width: 480px) {
        .table-container {
            height: 450px;
        }

        .table {
            min-width: 800px;
        }

        .table thead th {
            padding: 6px 8px;
            font-size: 0.75rem;
        }

        .table tbody td {
            padding: 4px 8px;
            font-size: 0.75rem;
        }
    }
</style>

<div class="container-fluid">
    <!-- Section Header -->
    <div class="section-header">
        <h4><i class="fas fa-shopping-cart"></i> Orders</h4>
        <a href="create.php" class="btn-new">
            <i class="fas fa-plus"></i> New Order
        </a>
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

    <!-- Search Section -->
    <div class="search-section">
        <form method="get" action="" class="search-form">
            <!-- Search Input -->
            <div class="form-group">
                <label for="search">Search Orders</label>
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="search"
                           name="search" 
                           class="search-input" 
                           placeholder="Order ID, Customer, Payment..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
            </div>
            
            <!-- Status Filter -->
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="filter-select">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="completed" <?php echo $status == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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
                
                <?php if (!empty($searchTerm) || !empty($status) || !empty($dateFrom) || !empty($dateTo)): ?>
                    <a href="index.php" class="btn-reset">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
        
        <!-- Search Stats -->
        <?php if (!empty($searchTerm) || !empty($status) || !empty($dateFrom) || !empty($dateTo)): ?>
        <div class="search-stats">
            <div>
                <i class="fas fa-shopping-cart me-1"></i>
                <strong><?php echo $totalOrders; ?></strong> order<?php echo $totalOrders != 1 ? 's' : ''; ?> found
            </div>
            
            <?php if (!empty($searchTerm)): ?>
                <div class="search-term">
                    <i class="fas fa-tag"></i> "<?php echo htmlspecialchars($searchTerm); ?>"
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Orders Table with Scrollable Body -->
    <?php if (empty($orders)): ?>
        <div class="no-results">
            <i class="fas fa-shopping-cart"></i>
            <h3>No Orders Found</h3>
            <p>No orders match your search criteria.</p>
            <a href="index.php" class="btn-search" style="display: inline-block; width: auto; padding: 10px 25px;">
                <i class="fas fa-times"></i> Clear Filters
            </a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <div class="table-header">
                <div>
                    <i class="fas fa-shopping-cart"></i> Orders List
                </div>
                <span class="badge"><?php echo $totalOrders; ?> records</span>
            </div>
            <div class="table-scroll">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($orders as $o): 
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td><span class="badge bg-order">#<?php echo $o['id']; ?></span></td>
                            <td><?php echo htmlspecialchars($o['display_customer']); ?></td>
                            <td>₱<?php echo number_format($o['total_amount'], 2); ?></td>
                            <td><span class="badge bg-payment"><?php echo ucfirst($o['payment_method']); ?></span></td>
                            <td>
                                <?php
                                $statusClass = '';
                                if ($o['status'] == 'completed') $statusClass = 'bg-completed';
                                elseif ($o['status'] == 'pending') $statusClass = 'bg-pending';
                                else $statusClass = 'bg-cancelled';
                                ?>
                                <span class="badge <?php echo $statusClass; ?>"><?php echo ucfirst($o['status']); ?></span>
                            </td>
                            <td><?php echo date('M d, H:i', strtotime($o['order_date'])); ?></td>
                            <td>
                                <a href="/modules/orders/view.php?id=<?php echo $o['id']; ?>" class="btn-outline-view">
                                    <i class="fas fa-eye"></i> View
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