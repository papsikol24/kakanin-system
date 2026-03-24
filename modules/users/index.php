<?php
require_once '../../includes/config.php';
requireLogin();
requireRole('admin');

// Handle search
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? $_GET['role'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query with search
$query = "SELECT * FROM tbl_users";
$params = [];

$whereConditions = [];

if (!empty($searchTerm)) {
    $whereConditions[] = "(username LIKE ? OR role LIKE ?)";
    $params[] = "%$searchTerm%";
    $params[] = "%$searchTerm%";
}

if (!empty($roleFilter)) {
    $whereConditions[] = "role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter !== '') {
    $whereConditions[] = "status = ?";
    $params[] = $statusFilter;
}

if (!empty($whereConditions)) {
    $query .= " WHERE " . implode(" AND ", $whereConditions);
}

$query .= " ORDER BY role, username";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get total count
$totalUsers = count($users);

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
        background: linear-gradient(135deg, #f5f7fa 0%, #e9ecef 100%);
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
        gap: 15px;
        margin-bottom: 25px;
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
        color: #007bff;
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

    /* ===== SEARCH SECTION - 4x4 Grid on Mobile ===== */
    .search-section {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        animation: fadeInUp 0.5s ease;
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
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
        font-weight: 500;
        color: #555;
        margin-bottom: 5px;
        font-size: 0.8rem;
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
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
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
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
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
        background: linear-gradient(135deg, #007bff, #0069d9);
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
        box-shadow: 0 4px 10px rgba(0,123,255,0.2);
    }

    .btn-search:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,123,255,0.3);
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
        background: #cce5ff;
        color: #004085;
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
        padding: 5px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        animation: fadeInUp 0.5s ease;
    }

    .table {
        width: 100%;
        min-width: 700px;
        border-collapse: collapse;
    }

    .table thead th {
        background: linear-gradient(135deg, #007bff, #0069d9);
        color: white;
        font-weight: 600;
        padding: 15px;
        font-size: 0.85rem;
        white-space: nowrap;
    }

    .table thead th:first-child {
        border-top-left-radius: 10px;
    }

    .table thead th:last-child {
        border-top-right-radius: 10px;
    }

    .table tbody tr {
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        background: #f8f9fa;
    }

    .table tbody td {
        padding: 12px 15px;
        font-size: 0.85rem;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }

    @media (max-width: 767px) {
        .table thead th {
            padding: 10px;
            font-size: 0.8rem;
        }

        .table tbody td {
            padding: 8px 10px;
            font-size: 0.8rem;
        }
    }

    /* Role badges */
    .badge-role {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
        text-align: center;
        white-space: nowrap;
    }

    .badge-role i {
        margin-right: 4px;
        font-size: 0.7rem;
    }

    .badge-role.admin {
        background: #dc3545;
    }

    .badge-role.manager {
        background: #ffc107;
        color: #333;
    }

    .badge-role.cashier {
        background: #17a2b8;
    }

    /* Status badges */
    .badge-status {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        text-align: center;
        white-space: nowrap;
    }

    .badge-status.active {
        background: #28a745;
        color: white;
    }

    .badge-status.inactive {
        background: #6c757d;
        color: white;
    }

    .badge-status i {
        margin-right: 4px;
        font-size: 0.7rem;
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
        border: none;
        cursor: pointer;
        gap: 4px;
    }

    .btn-action i {
        font-size: 0.7rem;
    }

    .btn-action.edit {
        border: 1px solid #ffc107;
        color: #ffc107;
        background: transparent;
    }

    .btn-action.edit:hover {
        background: #ffc107;
        color: white;
    }

    .btn-action.delete {
        border: 1px solid #dc3545;
        color: #dc3545;
        background: transparent;
    }

    .btn-action.delete:hover {
        background: #dc3545;
        color: white;
    }

    .btn-action.disabled {
        border: 1px solid #6c757d;
        color: #6c757d;
        opacity: 0.6;
        cursor: not-allowed;
        pointer-events: none;
    }

    @media (max-width: 767px) {
        .btn-action {
            padding: 4px 8px;
            font-size: 0.7rem;
        }
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
        animation: fadeInUp 0.5s ease;
    }

    .no-results i {
        font-size: 3.5rem;
        color: #ddd;
        margin-bottom: 15px;
    }

    @media (min-width: 768px) {
        .no-results i {
            font-size: 4rem;
        }
    }

    .no-results h3 {
        font-size: 1.2rem;
        color: #333;
        margin-bottom: 10px;
    }

    .no-results p {
        color: #666;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }

    /* ===== DELETE MODAL ===== */
    .modal-content {
        border-radius: 15px;
        border: none;
        overflow: hidden;
        animation: modalPop 0.3s ease;
    }

    @keyframes modalPop {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        border: none;
        padding: 15px 20px;
    }

    .modal-header .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.8;
        transition: all 0.3s;
    }

    .modal-header .btn-close:hover {
        opacity: 1;
        transform: rotate(90deg);
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
        background: linear-gradient(135deg, #007bff, #0069d9);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        transition: all 0.3s;
        z-index: 1000;
        opacity: 0;
        visibility: hidden;
        border: 2px solid white;
    }

    .scroll-to-top.show {
        opacity: 1;
        visibility: visible;
    }

    .scroll-to-top:hover {
        background: #ff8c00;
        transform: translateY(-3px) scale(1.1);
        box-shadow: 0 8px 20px rgba(255,140,0,0.4);
    }

    /* ===== LOADING BAR ===== */
    .loading-bar {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: linear-gradient(90deg, #007bff, #00c6ff, #007bff);
        background-size: 300% 100%;
        animation: loading 2s ease-in-out infinite;
        z-index: 9999;
        display: none;
    }

    .loading-bar.show {
        display: block;
    }

    @keyframes loading {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* ===== MOBILE SPECIFIC STYLES ===== */
    @media (max-width: 767px) {
        .container-fluid {
            padding-right: 10px;
            padding-left: 10px;
        }

        .btn-add {
            width: 100%;
            padding: 12px;
        }

        .search-input, .filter-select {
            padding: 10px 12px;
            font-size: 16px; /* Prevents zoom on iOS */
        }

        .search-wrapper i {
            left: 12px;
        }

        .search-input {
            padding-left: 40px;
        }

        .btn-search, .btn-reset {
            padding: 10px 12px;
            font-size: 0.85rem;
        }

        .badge-role, .badge-status {
            padding: 4px 8px;
            font-size: 0.7rem;
        }

        .scroll-to-top {
            bottom: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            font-size: 1rem;
        }
    }

    /* Small phones */
    @media (max-width: 480px) {
        .table thead th {
            padding: 8px;
            font-size: 0.75rem;
        }

        .table tbody td {
            padding: 6px 8px;
            font-size: 0.75rem;
        }

        .badge-role, .badge-status {
            padding: 3px 6px;
            font-size: 0.65rem;
        }

        .btn-action {
            padding: 3px 6px;
            font-size: 0.65rem;
        }

        .search-term {
            font-size: 0.8rem;
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

<!-- Loading Bar -->
<div class="loading-bar" id="loadingBar"></div>

<div class="container-fluid">
    <!-- Section Header -->
    <div class="section-header">
        <h4>
            <i class="fas fa-user-cog"></i>
            Manage Users
        </h4>
        <a href="add.php" class="btn-add">
            <i class="fas fa-plus"></i> Add New User
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

    <!-- Search Section - 4x4 Grid on Mobile -->
    <div class="search-section">
        <form method="get" action="" class="search-form">
            <!-- Search Input -->
            <div class="form-group">
                <label for="search">Search Users</label>
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" 
                           id="search"
                           name="search" 
                           class="search-input" 
                           placeholder="Username or role..." 
                           value="<?php echo htmlspecialchars($searchTerm); ?>">
                </div>
            </div>
            
            <!-- Role Filter -->
            <div class="form-group">
                <label for="role">Role</label>
                <select name="role" id="role" class="filter-select">
                    <option value="">All Roles</option>
                    <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="manager" <?php echo $roleFilter === 'manager' ? 'selected' : ''; ?>>Manager</option>
                    <option value="cashier" <?php echo $roleFilter === 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                </select>
            </div>
            
            <!-- Status Filter -->
            <div class="form-group">
                <label for="status">Status</label>
                <select name="status" id="status" class="filter-select">
                    <option value="">All Status</option>
                    <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <!-- Buttons -->
            <div class="button-group">
                <button type="submit" class="btn-search">
                    <i class="fas fa-filter"></i> Filter
                </button>
                
                <?php if (!empty($searchTerm) || !empty($roleFilter) || $statusFilter !== ''): ?>
                    <a href="index.php" class="btn-reset">
                        <i class="fas fa-times"></i> Clear
                    </a>
                <?php endif; ?>
            </div>
        </form>
        
        <!-- Search Stats -->
        <?php if (!empty($searchTerm) || !empty($roleFilter) || $statusFilter !== ''): ?>
        <div class="search-stats">
            <div>
                <i class="fas fa-users me-1"></i>
                <strong><?php echo $totalUsers; ?></strong> user<?php echo $totalUsers != 1 ? 's' : ''; ?> found
            </div>
            
            <?php if (!empty($searchTerm)): ?>
                <div class="search-term">
                    <i class="fas fa-tag"></i> "<?php echo htmlspecialchars($searchTerm); ?>"
                </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Users Table -->
    <?php if (empty($users)): ?>
        <div class="no-results">
            <i class="fas fa-users-slash"></i>
            <h3>No Users Found</h3>
            <p>No users match your search criteria.</p>
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
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $counter = 0;
                    foreach ($users as $u): 
                        $counter++;
                        
                        // Highlight search term
                        $username = htmlspecialchars($u['username']);
                        $role = htmlspecialchars($u['role']);
                        
                        if (!empty($searchTerm)) {
                            $username = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="highlight">$1</span>', $username);
                            $role = preg_replace('/(' . preg_quote($searchTerm, '/') . ')/i', '<span class="highlight">$1</span>', $role);
                        }
                    ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td>
                            <i class="fas fa-user me-1" style="color: #007bff;"></i>
                            <?php echo $username; ?>
                        </td>
                        <td>
                            <span class="badge-role <?php echo $u['role']; ?>">
                                <i class="fas <?php 
                                    echo $u['role'] == 'admin' ? 'fa-crown' : 
                                        ($u['role'] == 'manager' ? 'fa-user-tie' : 'fa-cash-register'); 
                                ?>"></i>
                                <?php echo ucfirst($u['role']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['status']): ?>
                                <span class="badge-status active">
                                    <i class="fas fa-check-circle me-1"></i>Active
                                </span>
                            <?php else: ?>
                                <span class="badge-status inactive">
                                    <i class="fas fa-times-circle me-1"></i>Inactive
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <i class="far fa-calendar-alt me-1" style="color: #6c757d;"></i>
                            <?php echo date('M d, Y', strtotime($u['created_at'])); ?>
                        </td>
                        <td>
                            <a href="edit.php?id=<?php echo $u['id']; ?>" class="btn-action edit" title="Edit User">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            
                            <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                <button type="button" 
                                        class="btn-action delete" 
                                        title="Delete User"
                                        onclick="confirmDelete(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['username'])); ?>')">
                                    <i class="fas fa-trash"></i> Del
                                </button>
                            <?php else: ?>
                                <span class="btn-action disabled" title="Cannot delete yourself">
                                    <i class="fas fa-ban"></i> Del
                                </span>
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
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirm Delete User
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to delete user <span id="userName" class="text-danger"></span>?</strong></p>
                <p class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i>
                    This action cannot be undone. All orders and logs linked to this user will be set to NULL.
                </p>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="confirmDelete">
                    <label class="form-check-label" for="confirmDelete">
                        I understand this action is permanent.
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-modal" data-bs-dismiss="modal">Cancel</button>
                <a href="#" id="deleteLink" class="btn btn-danger btn-modal disabled">Delete Permanently</a>
            </div>
        </div>
    </div>
</div>

<!-- Scroll to Top Button -->
<button class="scroll-to-top" id="scrollToTop" onclick="scrollToTop()">
    <i class="fas fa-arrow-up"></i>
</button>

<script>
// Show loading bar
document.getElementById('loadingBar').classList.add('show');
setTimeout(() => {
    document.getElementById('loadingBar').classList.remove('show');
}, 1000);

// Delete confirmation modal
function confirmDelete(id, name) {
    document.getElementById('userName').textContent = name;
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