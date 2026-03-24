<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kakanin Business Management System</title>
      <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="/assets/images/owner.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="/assets/images/owner.jpg">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    
    <!-- ===== NOTIFICATION SOUND SYSTEM ===== -->
    <script src="<?php echo SITE_URL; ?>/assets/js/notification-sound.js"></script>
    
    <style>
        .badge.bg-order { background: #e67e22; color: white; padding: 0.3rem 0.6rem; border-radius: 50px; font-size: 0.75rem; }
        .badge.bg-payment { background: #3498db; color: white; padding: 0.3rem 0.6rem; border-radius: 50px; font-size: 0.75rem; }
        .badge.bg-completed { background: #27ae60; color: white; padding: 0.3rem 0.6rem; border-radius: 50px; font-size: 0.75rem; }
        .badge.bg-pending { background: #f39c12; color: white; padding: 0.3rem 0.6rem; border-radius: 50px; font-size: 0.75rem; }
        .badge.bg-cancelled { background: #e74c3c; color: white; padding: 0.3rem 0.6rem; border-radius: 50px; font-size: 0.75rem; }
        .btn-outline-view {
            border: 1px solid #e67e22;
            color: #e67e22;
            padding: 0.25rem 1rem;
            border-radius: 50px;
            text-decoration: none;
            font-size: 0.85rem;
            transition: all 0.2s;
        }
        .btn-outline-view:hover {
            background: #e67e22;
            color: white;
        }
    </style>
</head>
<body class="<?php echo isLoggedIn() ? 'role-' . currentUser()['role'] : 'role-guest'; ?>">
<?php if (isLoggedIn()): ?>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
<?php endif; ?>