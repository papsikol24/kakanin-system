<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

// Handle search
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$stockFilter = isset($_GET['stock']) ? $_GET['stock'] : '';

// Build query with search
$query = "SELECT * FROM tbl_products";
$params = [];

$whereConditions = [];

if (!empty($searchTerm)) {
    $whereConditions[] = "(name LIKE ? OR description LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

if ($stockFilter === 'low') {
    $whereConditions[] = "stock <= low_stock_threshold";
} elseif ($stockFilter === 'out') {
    $whereConditions[] = "stock = 0";
} elseif ($stockFilter === 'in') {
    $whereConditions[] = "stock > 0";
}

if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}

$query .= " ORDER BY name";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Get total count
$totalProducts = count($products);

include '../../includes/header.php';
?>

<!-- Cache prevention meta tags -->
<meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Expires" content="0">

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
        color: #28a745;
        margin-right: 8px;
    }

    .btn-add {
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

    .btn-add:hover {
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
            grid-template-columns: 2fr 1fr 1fr;
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
        border-color: #28a745;
        box-shadow: 0 0 0 3px rgba(40,167,69,0.1);
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
        background: linear-gradient(135deg, #28a745, #218838);
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
        background: #d4edda;
        color: #155724;
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
        background: linear-gradient(135deg, #28a745, #218838);
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
        border-bottom: 2px solid #28a745;
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

    /* Product image */
    .product-image {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        object-fit: cover;
    }

    .image-placeholder {
        width: 40px;
        height: 40px;
        background: #f0f0f0;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #999;
    }

    /* Stock badges */
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 50px;
        font-size: 0.7rem;
        font-weight: 500;
        margin-left: 5px;
    }

    .badge.bg-warning {
        background: #ffc107 !important;
        color: #333;
    }

    .badge.bg-danger {
        background: #dc3545 !important;
        color: white;
    }

    /* Action buttons */
    .btn-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.2s;
        margin: 0 2px;
        border: 1px solid;
        background: transparent;
        cursor: pointer;
        gap: 4px;
    }

    .btn-action i {
        font-size: 0.7rem;
    }

    .btn-action.edit {
        border-color: #ffc107;
        color: #ffc107;
    }

    .btn-action.edit:hover {
        background: #ffc107;
        color: white;
    }

    .btn-action.delete {
        border-color: #dc3545;
        color: #dc3545;
    }

    .btn-action.delete:hover {
        background: #dc3545;
        color: white;
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
        background: linear-gradient(135deg, #28a745, #218838);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 5px 15px rgba(40,167,69,0.3);
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

        .product-image, .image-placeholder {
            width: 30px;
            height: 30px;
        }

        .btn-action {
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
        <h4><i class="fas fa-box"></i> Inventory Management</h4>
        <a href="add.php" class="btn-add">
            <i class="fas fa-plus"></i> Add Product
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
                <label for="search">Search Products</label>
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="search"
                           name="search" 
                           class="search-input" 
                           placeholder="Name or description..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
            </div>
            
            <!-- Stock Filter -->
            <div class="form-group">
                <label for="stock">Stock Status</label>
                <select name="stock" id="stock" class="filter-select">
                    <option value="">All</option>
                    <option value="in" <?php echo $stockFilter === 'in' ? 'selected' : ''; ?>>In Stock</option>
                    <option value="low" <?php echo $stockFilter === 'low' ? 'selected' : ''; ?>>Low Stock</option>
                    <option value="out" <?php echo $stockFilter === 'out' ? 'selected' : ''; ?>>Out of Stock</option>
                </select>
            </div>
            
            <!-- Buttons -->
            <div class="button-group">
                <button type="submit" class="btn-search">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <?php if (!empty($searchTerm) || !empty($stockFilter)): ?>
                    <a href="index.php" class="btn-reset">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
        
        <!-- Search Stats -->
        <?php if (!empty($searchTerm) || !empty($stockFilter)): ?>
        <div class="search-stats">
            <div>
                <i class="fas fa-box me-1"></i>
                <strong><?php echo $totalProducts; ?></strong> product<?php echo $totalProducts != 1 ? 's' : ''; ?> found
            </div>
            
            <?php if (!empty($searchTerm)): ?>
                <div class="search-term">
                    <i class="fas fa-tag"></i> "<?php echo htmlspecialchars($searchTerm); ?>"
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Products Table with Scrollable Body -->
    <?php if (empty($products)): ?>
        <div class="no-results">
            <i class="fas fa-box-open"></i>
            <h3>No Products Found</h3>
            <p>No products match your search criteria.</p>
            <a href="index.php" class="btn-search" style="display: inline-block; width: auto; padding: 10px 25px;">
                <i class="fas fa-times"></i> Clear Filters
            </a>
        </div>
    <?php else: ?>
        <div class="table-container">
            <div class="table-header">
                <div>
                    <i class="fas fa-box"></i> Products Inventory
                </div>
                <span class="badge"><?php echo $totalProducts; ?> items</span>
            </div>
            <div class="table-scroll">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Threshold</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $counter = 1;
                        foreach ($products as $p): 
                            // Determine stock status
                            $stockBadge = '';
                            if ($p['stock'] <= 0) {
                                $stockBadge = '<span class="badge bg-danger">Out</span>';
                            } elseif ($p['stock'] <= $p['low_stock_threshold']) {
                                $stockBadge = '<span class="badge bg-warning">Low</span>';
                            }
                        ?>
                        <tr>
                            <td><?php echo $counter++; ?></td>
                            <td>
                                <?php if ($p['image'] && file_exists("../../assets/images/".$p['image'])): ?>
                                    <img src="../../assets/images/<?php echo $p['image']; ?>" class="product-image">
                                <?php else: ?>
                                    <div class="image-placeholder">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($p['name']); ?></td>
                            <td>₱<?php echo number_format($p['price'], 2); ?></td>
                            <td>
                                <?php echo $p['stock']; ?>
                                <?php echo $stockBadge; ?>
                            </td>
                            <td><?php echo $p['low_stock_threshold']; ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $p['id']; ?>" class="btn-action edit" title="Edit">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button type="button" class="btn-action delete" 
                                        onclick="confirmDelete(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars(addslashes($p['name'])); ?>')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
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
                <p>Are you sure you want to delete <strong id="productName"></strong>?</p>
                <p class="text-danger small">This action cannot be undone.</p>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmDelete">
                    <label class="form-check-label small" for="confirmDelete">I understand this action is permanent.</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteLink" class="btn btn-danger disabled">Delete Permanently</a>
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
    document.getElementById('productName').textContent = name;
    const deleteLink = document.getElementById('deleteLink');
    deleteLink.href = 'delete.php?id=' + id;
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