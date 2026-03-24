<?php
$user = currentUser();
$role = $user['role'];
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>/staff-dashboard">
            <img src="<?php echo SITE_URL; ?>/assets/images/owner.jpg" alt="Jen's Kakanin" class="sidebar-logo">
            <span class="brand-text">Jen's Kakanin</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="sidebarNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/staff-dashboard"><i class="fas fa-dashboard"></i> Dashboard</a></li>

                <?php if ($role == 'admin' || $role == 'manager'): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/modules/inventory/index"><i class="fas fa-box"></i> Inventory</a></li>
                <?php endif; ?>

                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/modules/orders/index"><i class="fas fa-shopping-cart"></i> Orders</a></li>

                <?php if ($role == 'admin' || $role == 'manager' || $role == 'cashier'): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/modules/customers/index"><i class="fas fa-users"></i> Customers</a></li>
                <?php endif; ?>

                <?php if ($role == 'admin' || $role == 'manager'): ?>
                <!-- Reports Section -->
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/modules/reports/sales"><i class="fas fa-chart-line"></i> Sales Report</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/modules/reports/inventory"><i class="fas fa-clipboard-list"></i> Inventory Logs</a></li>
                
                <!-- NEW: Cashier Performance Report -->
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/modules/reports/cashier_performance"><i class="fas fa-chart-bar"></i> Cashier Performance</a></li>
                
                <!-- Tools Section -->
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/modules/tools/index"><i class="fas fa-tools"></i> Tools</a></li>
                
                <!-- NEW: Activity Logs (for monitoring cashiers) -->
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/modules/tools/activity_logs"><i class="fas fa-history"></i> Activity Logs</a></li>
                <?php endif; ?>

                <?php if ($role == 'admin'): ?>
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/modules/users/index"><i class="fas fa-user-cog"></i> Manage Users</a></li>
                <?php endif; ?>

                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/about"><i class="fas fa-info-circle"></i> About</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo SITE_URL; ?>/logout?type=staff"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="sidebar-description">
    <p class="text-muted small text-center">Authentic Filipino Rice Cakes since 2020</p>
</div>

<style>
    body { padding-top: 60px; }
    .main-content { margin-left: 0; padding: 20px; }
    
    /* Sidebar Logo */
    .sidebar-logo {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #fff;
        margin-right: 10px;
        transition: transform 0.3s ease;
    }
    .sidebar-logo:hover {
        transform: rotate(5deg) scale(1.05);
    }
    
    /* Brand Text */
    .brand-text {
        font-size: 1.2rem;
        font-weight: 600;
        color: #fff;
    }
    
    /* Sidebar Description */
    .sidebar-description {
        position: fixed;
        left: 0;
        bottom: 10px;
        width: 250px;
        padding: 10px;
        background: rgba(52, 58, 64, 0.9);
        backdrop-filter: blur(5px);
        border-top: 1px solid rgba(255,255,255,0.1);
        border-radius: 0 20px 0 0;
        text-align: center;
        animation: slideUp 0.5s ease;
    }
    .sidebar-description p {
        margin: 0;
        color: #fff;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
    }
    
    /* Menu Item Animations */
    .navbar-nav .nav-item {
        opacity: 0;
        animation: slideInLeft 0.4s ease forwards;
    }
    .navbar-nav .nav-item:nth-child(1) { animation-delay: 0.1s; }
    .navbar-nav .nav-item:nth-child(2) { animation-delay: 0.15s; }
    .navbar-nav .nav-item:nth-child(3) { animation-delay: 0.2s; }
    .navbar-nav .nav-item:nth-child(4) { animation-delay: 0.25s; }
    .navbar-nav .nav-item:nth-child(5) { animation-delay: 0.3s; }
    .navbar-nav .nav-item:nth-child(6) { animation-delay: 0.35s; }
    .navbar-nav .nav-item:nth-child(7) { animation-delay: 0.4s; }
    .navbar-nav .nav-item:nth-child(8) { animation-delay: 0.45s; }
    .navbar-nav .nav-item:nth-child(9) { animation-delay: 0.5s; }
    .navbar-nav .nav-item:nth-child(10) { animation-delay: 0.55s; }
    .navbar-nav .nav-item:nth-child(11) { animation-delay: 0.6s; }
    .navbar-nav .nav-item:nth-child(12) { animation-delay: 0.65s; }
    .navbar-nav .nav-item:nth-child(13) { animation-delay: 0.7s; }

    @keyframes slideInLeft {
        from {
            opacity: 0;
            transform: translateX(-20px);
        }
        to {
            opacity: 1;
            transform: translateX(0);
        }
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* Hover Effects */
    .navbar-nav .nav-link {
        position: relative;
        transition: all 0.3s;
        padding: 0.5rem 1rem;
        border-radius: 5px;
    }
    .navbar-nav .nav-link:hover {
        background: rgba(255,255,255,0.1);
        padding-left: 1.5rem;
    }
    .navbar-nav .nav-link i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
        transition: transform 0.3s;
    }
    .navbar-nav .nav-link:hover i {
        transform: translateX(5px);
    }

    /* Active Link Highlight */
    .navbar-nav .nav-link.active {
        background: rgba(255,255,255,0.2);
        border-left: 3px solid #ff8c00;
    }

    /* Section Headers in Sidebar */
    .nav-section {
        color: #6c757d;
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding: 1rem 1rem 0.3rem 1rem;
        font-weight: 600;
    }

    /* Desktop Specific */
    @media (min-width: 992px) {
        .main-content { 
            margin-left: 250px; 
        }
        .navbar { 
            width: 100%; 
            z-index: 1030; 
        }
        #sidebarNav { 
            position: fixed; 
            left: 0; 
            top: 56px; 
            bottom: 0; 
            width: 250px; 
            background-color: #343a40; 
            padding-top: 20px; 
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #ff8c00 #343a40;
        }
        #sidebarNav::-webkit-scrollbar {
            width: 6px;
        }
        #sidebarNav::-webkit-scrollbar-track {
            background: #343a40;
        }
        #sidebarNav::-webkit-scrollbar-thumb {
            background: #ff8c00;
            border-radius: 3px;
        }
        #sidebarNav .navbar-nav { 
            flex-direction: column; 
            width: 100%; 
        }
        .navbar-brand { 
            margin-left: 15px;
            display: flex;
            align-items: center;
        }
        .sidebar-description {
            left: 0;
            width: 250px;
        }
    }

    /* Mobile Adjustments */
    @media (max-width: 991px) {
        .sidebar-description { 
            display: none; 
        }
        .sidebar-logo { 
            width: 30px; 
            height: 30px; 
        }
        .brand-text { 
            font-size: 1rem; 
        }
        .navbar-nav .nav-link {
            padding: 0.75rem 1rem;
        }
    }

    /* Small Devices */
    @media (max-width: 576px) {
        .navbar-brand {
            font-size: 0.9rem;
        }
        .brand-text {
            font-size: 0.9rem;
        }
    }
</style>

<!-- Optional: Add script to highlight active link -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get current URL path
    const currentPath = window.location.pathname;
    
    // Remove SITE_URL from comparison if needed
    const siteUrl = '<?php echo SITE_URL; ?>';
    const relativePath = currentPath.replace(siteUrl, '');
    
    // Find all nav links
    document.querySelectorAll('.navbar-nav .nav-link').forEach(link => {
        const linkPath = link.getAttribute('href').replace(siteUrl, '');
        
        // Check if current path matches link
        if (relativePath === linkPath || 
            (linkPath !== '/staff-dashboard' && relativePath.startsWith(linkPath) && linkPath !== '')) {
            link.classList.add('active');
        }
        
        // Special case for dashboard
        if (relativePath === '/staff-dashboard' && linkPath === '/staff-dashboard') {
            link.classList.add('active');
        }
    });
});
</script>