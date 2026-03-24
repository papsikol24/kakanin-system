<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager', 'cashier']);

// Handle customer deletion
if (isset($_GET['delete'])) {
    requireRole(['admin', 'manager']);
    
    $id = (int)$_GET['delete'];
    
    $pdo->beginTransaction();
    
    try {
        $stmt = $pdo->prepare("UPDATE tbl_orders SET customer_id = NULL WHERE customer_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM tbl_notifications WHERE customer_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM tbl_carts WHERE customer_id = ?");
        $stmt->execute([$id]);
        
        $stmt = $pdo->prepare("DELETE FROM tbl_customers WHERE id = ?");
        $stmt->execute([$id]);
        
        $pdo->commit();
        $_SESSION['success'] = "Customer deleted successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Failed to delete customer: " . $e->getMessage();
    }
    
    header('Location: index.php');
    exit;
}

// Handle search
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with search
$query = "SELECT id, name, email, username, phone, status, created_at FROM tbl_customers";
$params = [];

$whereConditions = [];

if (!empty($searchTerm)) {
    $whereConditions[] = "(name LIKE ? OR email LIKE ? OR username LIKE ? OR phone LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

if ($status !== '') {
    $whereConditions[] = "status = ?";
    $params[] = $status;
}

if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}

$query .= " ORDER BY name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Get total count
$totalCustomers = count($customers);

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

    .btn-add {
        background: linear-gradient(135deg, #28a745, #218838);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 10px 20px;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s;
        box-shadow: 0 4px 10px rgba(40,167,69,0.2);
        width: fit-content;
    }

    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(40,167,69,0.3);
        color: white;
    }

    .btn-add i {
        font-size: 0.9rem;
    }

    /* ===== SEARCH SECTION ===== */
    .search-section {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    /* Mobile: Stack vertically */
    .search-form {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    /* Tablet and Desktop: Grid layout */
    @media (min-width: 768px) {
        .search-form {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            align-items: end;
        }
    }

    .form-group {
        display: flex;
        flex-direction: column;
        width: 100%;
    }

    .form-group label {
        font-size: 0.8rem;
        font-weight: 500;
        color: #555;
        margin-bottom: 5px;
    }

    @media (min-width: 768px) {
        .form-group label {
            font-size: 0.85rem;
        }
    }

    /* Search input with icon */
    .search-wrapper {
        position: relative;
        width: 100%;
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
        padding: 12px 15px 12px 45px;
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

    .filter-select {
        width: 100%;
        padding: 12px 15px;
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

    .filter-select:focus {
        border-color: #17a2b8;
        box-shadow: 0 0 0 3px rgba(23,162,184,0.1);
        outline: none;
    }

    /* Button group */
    .button-group {
        display: flex;
        gap: 10px;
        margin-top: 5px;
    }

    @media (min-width: 768px) {
        .button-group {
            margin-top: 0;
            grid-column: span 1;
        }
    }

    .btn-search {
        flex: 1;
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 12px 15px;
        font-size: 0.9rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 10px rgba(23,162,184,0.2);
    }

    .btn-search:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(23,162,184,0.3);
    }

    .btn-reset {
        flex: 1;
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 12px 15px;
        font-size: 0.9rem;
        font-weight: 500;
        text-decoration: none;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        box-shadow: 0 4px 10px rgba(108,117,125,0.2);
    }

    .btn-reset:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(108,117,125,0.3);
        color: white;
    }

    /* Search stats */
    .search-stats {
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #eee;
        color: #666;
        font-size: 0.9rem;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    @media (min-width: 768px) {
        .search-stats {
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
        }
    }

    .search-term {
        background: #d1ecf1;
        color: #0c5460;
        padding: 5px 15px;
        border-radius: 50px;
        font-weight: 500;
        font-size: 0.85rem;
        display: inline-block;
        width: fit-content;
    }

    /* ===== TABLE STYLES ===== */
    .table-responsive {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        margin-bottom: 20px;
        background: white;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .table {
        width: 100%;
        min-width: 900px;
        border-collapse: collapse;
    }

    .table thead {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
    }

    .table thead th {
        padding: 15px;
        font-weight: 500;
        font-size: 0.85rem;
        text-align: left;
        white-space: nowrap;
    }

    .table tbody td {
        padding: 12px 15px;
        font-size: 0.85rem;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
    }

    /* Badges */
    .badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 500;
        text-align: center;
    }

    .badge.bg-success {
        background: #28a745 !important;
        color: white;
    }

    .badge.bg-danger {
        background: #dc3545 !important;
        color: white;
    }

    /* Action buttons */
    .btn-edit {
        border: 1px solid #ffc107;
        color: #ffc107;
        padding: 5px 12px;
        border-radius: 50px;
        text-decoration: none;
        font-size: 0.8rem;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin: 0 2px;
    }

    .btn-edit:hover {
        background: #ffc107;
        color: white;
    }

    .btn-delete {
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
        margin: 0 2px;
        background: none;
        cursor: pointer;
    }

    .btn-delete:hover {
        background: #dc3545;
        color: white;
    }

    .btn-delete:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-edit i, .btn-delete i {
        font-size: 0.8rem;
    }

    /* Highlight search term */
    .highlight {
        background-color: #fff3cd;
        padding: 2px 4px;
        border-radius: 3px;
        font-weight: 600;
    }

    /* ===== NO RESULTS ===== */
    .no-results {
        text-align: center;
        padding: 40px 20px;
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
        font-size: 1.3rem;
        color: #333;
        margin-bottom: 10px;
    }

    .no-results p {
        color: #666;
        margin-bottom: 20px;
    }

    /* ===== SCROLL TO TOP ===== */
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
        box-shadow: 0 4px 10px rgba(23,162,184,0.3);
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

        .section-header h4 {
            font-size: 1.2rem;
        }

        .btn-add {
            width: 100%;
            padding: 12px;
        }

        .search-input, .filter-select {
            padding: 12px 15px;
            font-size: 16px; /* Prevents zoom on iOS */
        }

        .search-wrapper i {
            left: 12px;
        }

        .search-input {
            padding-left: 40px;
        }

        .table thead th {
            padding: 12px 10px;
            font-size: 0.8rem;
        }

        .table tbody td {
            padding: 10px;
            font-size: 0.8rem;
        }

        .badge {
            padding: 4px 8px;
            font-size: 0.7rem;
        }

        .btn-edit, .btn-delete {
            padding: 4px 10px;
            font-size: 0.7rem;
        }

        .btn-edit i, .btn-delete i {
            font-size: 0.7rem;
        }

        .no-results {
            padding: 30px 15px;
        }

        .no-results i {
            font-size: 3rem;
        }

        .no-results h3 {
            font-size: 1.2rem;
        }
    }

    /* Small phones */
    @media (max-width: 480px) {
        .table thead th {
            padding: 10px 8px;
            font-size: 0.75rem;
        }

        .table tbody td {
            padding: 8px;
            font-size: 0.75rem;
        }

        .badge {
            padding: 3px 6px;
            font-size: 0.65rem;
        }

        .btn-edit, .btn-delete {
            padding: 3px 8px;
            font-size: 0.65rem;
        }

        .btn-edit i, .btn-delete i {
            font-size: 0.65rem;
        }

        .search-term {
            font-size: 0.8rem;
        }
    }
</style>

<div class="container-fluid">
    <!-- Section Header -->
    <div class="section-header">
        <h4><i class="fas fa-users"></i> Customer Management</h4>
        <a href="add.php" class="btn-add">
            <i class="fas fa-plus"></i> Add Customer
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
                <label for="search">Search Customers</label>
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="search"
                           name="search" 
                           class="search-input" 
                           placeholder="Name, email, username, phone..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
            </div>
            
            <!-- Status Filter -->
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="filter-select">
                    <option value="">All Status</option>
                    <option value="1" <?php echo $status === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $status === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <!-- Buttons -->
            <div class="button-group">
                <button type="submit" class="btn-search">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <?php if (!empty($searchTerm) || $status !== ''): ?>
                    <a href="index.php" class="btn-reset">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
        
        <!-- Search Stats -->
        <?php if (!empty($searchTerm) || $status !== ''): ?>
        <div class="search-stats">
            <div>
                <i class="fas fa-users me-1"></i>
                <strong><?php echo $totalCustomers; ?></strong> customer<?php echo $totalCustomers != 1 ? 's' : ''; ?> found
            </div>
            
            <?php if (!empty($searchTerm)): ?>
                <div class="search-term">
                    <i class="fas fa-tag"></i> "<?php echo htmlspecialchars($searchTerm); ?>"
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Customers Table -->
    <?php if (empty($customers)): ?>
        <div class="no-results">
            <i class="fas fa-users-slash"></i>
            <h3>No Customers Found</h3>
            <p>No customers match your search criteria.</p>
            <a href="index.php" class="btn-search" style="display: inline-block; width: auto; padding: 10px 25px;">
                <i class="fas fa-times"></i> Clear Filters
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Username</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): 
                        // Highlight search term
                        $name = htmlspecialchars($c['name']);
                        $email = htmlspecialchars($c['email']);
                        $username = htmlspecialchars($c['username']);
                        $phone = htmlspecialchars($c['phone']);
                        
                        if (!empty($searchTerm)) {
                            $name = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="highlight">$1</span>', $name);
                            $email = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="highlight">$1</span>', $email);
                            $username = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="highlight">$1</span>', $username);
                            $phone = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="highlight">$1</span>', $phone);
                        }
                    ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td><?php echo $name; ?></td>
                        <td><?php echo $email; ?></td>
                        <td><?php echo $username; ?></td>
                        <td><?php echo $phone; ?></td>
                        <td>
                            <?php if ($c['status']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                        <td>
                            <a href="edit.php?id=<?php echo $c['id']; ?>" class="btn-edit" title="Edit">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <?php if (hasRole(['admin', 'manager'])): ?>
                            <button type="button" 
                                    class="btn-delete" 
                                    title="Delete"
                                    onclick="confirmDelete(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['name'])); ?>')">
                                <i class="fas fa-trash"></i> Del
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="customerName"></strong>?</p>
                <p class="text-danger small">
                    <i class="fas fa-info-circle me-2"></i>
                    This will:
                </p>
                <ul class="text-danger small">
                    <li>Remove customer account</li>
                    <li>Orders will show as Walk-in</li>
                    <li>Delete their notifications</li>
                    <li>Clear their saved cart</li>
                </ul>
                <p class="text-danger small">This cannot be undone!</p>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmDelete">
                    <label class="form-check-label small" for="confirmDelete">
                        I understand this action is permanent.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteLink" class="btn btn-danger btn-sm disabled">Delete Permanently</a>
            </div>
        </div>
    </div>
</div>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
function confirmDelete(id, name) {
    document.getElementById('customerName').textContent = name;
    const deleteLink = document.getElementById('deleteLink');
    deleteLink.href = '?delete=' + id;
    deleteLink.classList.add('disabled');
    document.getElementById('confirmDelete').checked = false;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

document.getElementById('confirmDelete').addEventListener('change', function() {
    const deleteLink = document.getElementById('deleteLink');
    if (this.checked) {
        deleteLink.classList.remove('disabled');
    } else {
        deleteLink.classList.add('disabled');
    }
});

// Scroll to top function
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

// Show/hide scroll button
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