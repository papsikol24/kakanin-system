<?php
require_once 'includes/config.php';

// Check if user is logged in to determine which header to use
$isStaff = isset($_SESSION['user_id']);
$isCustomer = isset($_SESSION['customer_id']);

if ($isStaff) {
    include 'includes/header.php';
} elseif ($isCustomer) {
    include 'includes/customer_header.php';
} else {
    // Not logged in - use simple header
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>404 - Page Not Found | Jen's Kakanin</title>
        <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
        <style>
            body { font-family: 'Poppins', sans-serif; background: #f8f9fa; }
            .error-container { min-height: 100vh; display: flex; align-items: center; justify-content: center; }
            .error-card { background: white; border-radius: 20px; padding: 3rem; box-shadow: 0 10px 30px rgba(0,0,0,0.05); text-align: center; max-width: 500px; }
            .error-code { font-size: 8rem; font-weight: 800; color: #008080; line-height: 1; margin-bottom: 1rem; }
            .error-title { font-size: 2rem; font-weight: 600; color: #333; margin-bottom: 1rem; }
            .error-message { color: #666; margin-bottom: 2rem; }
            .btn-home { background: linear-gradient(135deg, #008080, #20b2aa); color: white; border: none; border-radius: 50px; padding: 0.8rem 2rem; text-decoration: none; display: inline-block; }
        </style>
    </head>
    <body>
    <?php
}
?>

<style>
    .error-container {
        min-height: 60vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
    }
    
    .error-card {
        background: white;
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        text-align: center;
        max-width: 500px;
        width: 100%;
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
    
    .error-code {
        font-size: 8rem;
        font-weight: 800;
        background: linear-gradient(135deg, #d35400, #e67e22);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        line-height: 1;
        margin-bottom: 1rem;
    }
    
    .error-title {
        font-size: 2rem;
        font-weight: 600;
        color: #333;
        margin-bottom: 1rem;
    }
    
    .error-message {
        color: #666;
        margin-bottom: 2rem;
        font-size: 1.1rem;
    }
    
    .btn-home {
        background: linear-gradient(135deg, #d35400, #e67e22);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.8rem 2rem;
        font-size: 1rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        margin: 0 0.5rem;
    }
    
    .btn-home:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(211,84,0,0.3);
        color: white;
    }
    
    .btn-dashboard {
        background: linear-gradient(135deg, #008080, #20b2aa);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.8rem 2rem;
        font-size: 1rem;
        font-weight: 500;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s;
        margin: 0 0.5rem;
    }
    
    .btn-dashboard:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,128,128,0.3);
        color: white;
    }
    
    @media (max-width: 768px) {
        .error-code { font-size: 6rem; }
        .error-title { font-size: 1.5rem; }
        .btn-home, .btn-dashboard { display: block; margin: 0.5rem 0; }
    }
</style>

<div class="error-container">
    <div class="error-card">
        <div class="error-code">404</div>
        <div class="error-title">Page Not Found</div>
        <div class="error-message">
            The page you're looking for doesn't exist or has been moved.
        </div>
        <div>
            <?php if ($isStaff): ?>
                <a href="/staff-dashboard" class="btn-home">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            <?php elseif ($isCustomer): ?>
                <a href="/dashboard" class="btn-dashboard">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            <?php else: ?>
                <a href="/" class="btn-home">
                    <i class="fas fa-home me-2"></i>Go to Homepage
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
if ($isStaff) {
    include 'includes/footer.php';
} elseif ($isCustomer) {
    include 'includes/footer.php';
} else {
    echo '</body></html>';
}
?>