<?php
require_once 'includes/config.php';

if (isset($_SESSION['customer_id'])) {
    header('Location: /customer-dashboard');
    exit;
}

// Remember me - check cookie
$saved_username = $_COOKIE['remember_customer'] ?? '';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $stmt = $pdo->prepare("SELECT * FROM tbl_customers WHERE username = ? AND status = 1");
    $stmt->execute([$username]);
    $customer = $stmt->fetch();

    if ($customer && password_verify($password, $customer['password'])) {
        $_SESSION['customer_id'] = $customer['id'];
        $_SESSION['customer_name'] = $customer['name'];
        
        if ($remember) {
            setcookie('remember_customer', $username, time() + (86400 * 30), "/");
        } else {
            setcookie('remember_customer', '', time() - 3600, "/");
        }
        
        header('Location: /customer-dashboard');
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

// Fetch products with best seller calculation
$products = $pdo->query("
    SELECT p.*, 
    DATEDIFF(NOW(), p.created_at) <= 7 as is_new,
    COALESCE(SUM(oi.quantity), 0) as total_ordered
    FROM tbl_products p
    LEFT JOIN tbl_order_items oi ON p.id = oi.product_id
    GROUP BY p.id
    ORDER BY total_ordered DESC, name ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Customer Login · Jen's Kakanin</title> <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="/assets/images/owner.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="/assets/images/owner.jpg">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        /* ===== RESET & GLOBAL ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: url('assets/images/background.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow-x: hidden;
        }

        /* ===== LIGHTER OVERLAY ===== */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.4);
            z-index: 0;
            animation: fadeIn 1.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* ===== FLOATING PARTICLES ===== */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
            pointer-events: none;
        }

        .particle {
            position: absolute;
            display: block;
            list-style: none;
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            animation: float 25s linear infinite;
            bottom: -150px;
            border-radius: 50%;
            opacity: 0.3;
            backdrop-filter: blur(5px);
        }

        .particle:nth-child(1) {
            left: 10%;
            width: 80px;
            height: 80px;
            animation-delay: 0s;
            animation-duration: 30s;
            background: rgba(255, 140, 0, 0.1);
        }

        .particle:nth-child(2) {
            left: 20%;
            width: 50px;
            height: 50px;
            animation-delay: 2s;
            animation-duration: 25s;
            background: rgba(0, 128, 128, 0.1);
        }

        .particle:nth-child(3) {
            left: 35%;
            width: 100px;
            height: 100px;
            animation-delay: 4s;
            animation-duration: 35s;
            background: rgba(255, 193, 7, 0.1);
        }

        .particle:nth-child(4) {
            left: 50%;
            width: 40px;
            height: 40px;
            animation-delay: 0s;
            animation-duration: 20s;
            background: rgba(0, 128, 128, 0.1);
        }

        .particle:nth-child(5) {
            left: 65%;
            width: 70px;
            height: 70px;
            animation-delay: 3s;
            animation-duration: 28s;
            background: rgba(255, 140, 0, 0.1);
        }

        .particle:nth-child(6) {
            left: 80%;
            width: 60px;
            height: 60px;
            animation-delay: 1s;
            animation-duration: 32s;
            background: rgba(255, 193, 7, 0.1);
        }

        .particle:nth-child(7) {
            left: 90%;
            width: 90px;
            height: 90px;
            animation-delay: 5s;
            animation-duration: 38s;
            background: rgba(0, 128, 128, 0.1);
        }

        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.3;
                border-radius: 50%;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 40%;
            }
        }

        /* ===== HOME BUTTON ===== */
        .home-button-container {
            position: absolute;
            top: 20px;
            left: 20px;
            z-index: 100;
        }

        .btn-home {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.95rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .btn-home:hover {
            background: #ff8c00;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 140, 0, 0.4);
        }

        @media (max-width: 768px) {
            .home-button-container {
                top: 15px;
                left: 15px;
            }
            .btn-home {
                padding: 8px 16px;
                font-size: 0.85rem;
            }
        }

        /* ===== MAIN CONTAINER ===== */
        .split-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
            padding: 15px;
        }

        /* ===== PRODUCT SHOWCASE - SINGLE PRODUCT PER SLIDE ===== */
        .product-showcase {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 40px;
            padding: 2rem;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            height: 90vh;
            position: relative;
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: slideInLeft 1s ease;
            display: flex;
            flex-direction: column;
        }

        .showcase-header {
            background: rgba(0, 128, 128, 0.3);
            backdrop-filter: blur(10px);
            padding: 1.5rem;
            border-radius: 30px;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }

        .showcase-header h2 {
            color: white;
            font-weight: 700;
            font-size: 2rem;
            margin: 0;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
        }

        .showcase-header h2 i {
            color: #ff8c00;
            animation: floatSlow 3s ease-in-out infinite;
        }

        .showcase-subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
            margin-top: 0.5rem;
            letter-spacing: 1px;
        }

        @keyframes floatSlow {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }

        /* Single Product Carousel */
        .carousel-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            padding: 20px 0;
        }

        .product-slide {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            display: none;
        }

        .product-slide.active {
            display: block;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }

        /* Product Card - Single Large Display */
        .product-card {
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 30px 50px rgba(0, 0, 0, 0.3);
            transition: all 0.3s ease;
            position: relative;
            border: 3px solid transparent;
            width: 100%;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 40px 60px rgba(255, 140, 0, 0.3);
            border: 3px solid #ff8c00;
        }

        .product-image {
            width: 100%;
            height: 300px;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.05);
        }

        .product-details {
            padding: 2rem;
            background: white;
        }

        .product-name {
            font-weight: 700;
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        .product-description {
            font-size: 1rem;
            color: #555;
            line-height: 1.6;
            margin-bottom: 1rem;
            max-height: 100px;
            overflow-y: auto;
            padding-right: 10px;
        }

        /* Custom scrollbar for description */
        .product-description::-webkit-scrollbar {
            width: 5px;
        }

        .product-description::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .product-description::-webkit-scrollbar-thumb {
            background: #008080;
            border-radius: 10px;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 2px solid #f0f0f0;
        }

        .product-price {
            font-weight: 700;
            color: #008080;
            font-size: 1.8rem;
        }

        .product-stock {
            color: #28a745;
            font-weight: 500;
            font-size: 1rem;
        }

        .product-stock i {
            margin-right: 5px;
        }

        /* ===== BADGES ===== */
        .badge-container {
            position: absolute;
            top: 15px;
            left: 15px;
            right: 15px;
            display: flex;
            justify-content: space-between;
            z-index: 10;
            pointer-events: none;
        }

        .badge-left, .badge-right {
            display: flex;
            gap: 8px;
        }

        .best-seller-badge {
            background: linear-gradient(135deg, #ff8c00, #ff6b00);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 700;
            box-shadow: 0 5px 15px rgba(255, 140, 0, 0.3);
            animation: pulse 2s infinite;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(5px);
        }

        .best-seller-badge i {
            color: gold;
            font-size: 1rem;
        }

        .new-badge {
            background: linear-gradient(135deg, #ff4444, #ff8c00);
            color: white;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 700;
            box-shadow: 0 5px 15px rgba(255, 68, 68, 0.3);
            animation: tagPulse 1.5s ease-in-out infinite;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(5px);
        }

        .new-badge i {
            font-size: 0.9rem;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes tagPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        /* Carousel Controls */
        .carousel-controls {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 30px;
            margin-top: 30px;
        }

        .carousel-btn {
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.2rem;
        }

        .carousel-btn:hover {
            background: #ff8c00;
            transform: scale(1.1);
        }

        .carousel-dots {
            display: flex;
            gap: 10px;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: #ff8c00;
            transform: scale(1.2);
        }

        .dot:hover {
            background: rgba(255, 140, 0, 0.8);
        }

        /* Slide Counter */
        .slide-counter {
            text-align: center;
            color: white;
            margin-top: 15px;
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* Hide on mobile */
        @media (max-width: 991px) {
            .product-showcase {
                display: none;
            }
        }

        /* ===== LOGIN CARD ===== */
        .login-card {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: none;
            border-radius: 30px;
            box-shadow: 0 40px 80px rgba(0, 0, 0, 0.4);
            max-width: 450px;
            margin: 0 auto;
            overflow: hidden;
            animation: slideInRight 1s ease;
            border: 1px solid rgba(255, 255, 255, 0.3);
            width: 100%;
        }

        @media (max-width: 576px) {
            .login-card {
                border-radius: 25px;
            }
        }

        .login-card .card-header {
            text-align: center;
            padding: 2rem 1.5rem 1rem;
            background: linear-gradient(135deg, #008080, #20b2aa);
            position: relative;
            overflow: hidden;
        }

        .login-card .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .login-card .card-header h3 {
            font-weight: 700;
            color: white;
            font-size: 2rem;
            margin-bottom: 0.25rem;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }

        .brand-icon {
            font-size: 4rem;
            color: white;
            margin-bottom: 0.5rem;
            animation: floatSlow 3s ease-in-out infinite;
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            width: 100px;
            height: 100px;
            line-height: 100px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        @media (max-width: 576px) {
            .brand-icon {
                width: 70px;
                height: 70px;
                line-height: 70px;
                font-size: 2.5rem;
            }
            .login-card .card-header h3 {
                font-size: 1.6rem;
            }
        }

        .card-body {
            padding: 2rem;
        }

        /* ===== FORM FIELDS ===== */
        .form-floating {
            margin-bottom: 1.2rem;
        }

        .form-control {
            border-radius: 50px;
            padding: 1rem 1.5rem;
            height: auto;
            border: 2px solid #e0e0e0;
            font-size: 1rem;
            transition: all 0.3s ease;
            -webkit-appearance: none;
        }

        .form-control:focus {
            border-color: #008080;
            box-shadow: 0 0 0 0.25rem rgba(0, 128, 128, 0.25);
            transform: translateY(-2px);
        }

        .form-floating label {
            padding-left: 1.5rem;
            color: #666;
        }

        .form-floating label i {
            margin-right: 0.5rem;
            color: #008080;
        }

        /* ===== PASSWORD TOGGLE ===== */
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #666;
            z-index: 10;
            width: 45px;
            height: 45px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }

        .password-toggle:hover {
            background: #008080;
            color: white;
            transform: translateY(-50%) scale(1.1);
        }

        /* ===== REMEMBER ME & FORGOT PASSWORD ===== */
        @media (max-width: 480px) {
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 10px;
                align-items: flex-start !important;
            }
        }

        .form-check-input:checked {
            background-color: #008080;
            border-color: #008080;
        }

        .forgot-link a {
            color: #666;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            position: relative;
            display: inline-block;
            padding: 5px 0;
        }

        .forgot-link a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: #008080;
            transition: width 0.3s ease;
        }

        .forgot-link a:hover {
            color: #008080;
        }

        .forgot-link a:hover::after {
            width: 100%;
        }

        /* ===== LOGIN BUTTON ===== */
        .btn-login {
            background: linear-gradient(135deg, #008080, #20b2aa);
            border: none;
            border-radius: 50px;
            padding: 1rem;
            font-weight: 600;
            font-size: 1.1rem;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 1rem;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 128, 128, 0.4);
        }

        .btn-login:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-login.loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }

        .btn-login.loading::after {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 50%;
            left: 50%;
            margin-left: -10px;
            margin-top: -10px;
            border: 3px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 0.8s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* ===== SIGN UP LINK ===== */
        .signup-link {
            margin-top: 1.5rem;
        }

        .signup-link a {
            color: #008080;
            text-decoration: none;
            font-weight: 500;
            font-size: 1rem;
            transition: all 0.3s ease;
            position: relative;
            display: inline-block;
            padding: 5px 0;
        }

        .signup-link a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: #008080;
            transition: width 0.3s ease;
        }

        .signup-link a:hover {
            color: #20b2aa;
        }

        .signup-link a:hover::after {
            width: 100%;
        }

        /* ===== ALERT ===== */
        .alert {
            border-radius: 50px;
            animation: slideInDown 0.5s ease;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        .alert-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        /* ===== FOOTER ===== */
        footer {
            text-align: center;
            margin-top: 1rem;
            color: rgba(255, 255, 255, 0.9);
            font-size: 0.85rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 991px) {
            .split-container .row {
                flex-direction: column-reverse;
            }
            .col-lg-6 {
                width: 100%;
                max-width: 100%;
            }
            .order-lg-2 {
                order: 1;
            }
            .order-lg-1 {
                order: 2;
                margin-top: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Bar Animation -->
    <div class="loading-bar"></div>

    <!-- Floating Particles -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <!-- Home Button -->
    <div class="home-button-container">
        <a href="/" class="btn-home">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
    </div>

    <div class="split-container">
        <div class="row g-4 align-items-center">
            <!-- Left Column - Product Carousel (Single Product per Slide) -->
            <div class="col-lg-6 order-lg-1 order-2">
                <div class="product-showcase">
                    <div class="showcase-header">
                        <h2>
                            <i class="fas fa-utensils"></i>
                            Our Kakanin Selection
                        </h2>
                        <div class="showcase-subtitle">
                            <i class="fas fa-star me-1" style="color: #ff8c00;"></i>
                            Freshly made daily since 2020
                        </div>
                    </div>
                    
                    <!-- Carousel Container -->
                    <div class="carousel-container">
                        <?php foreach ($products as $index => $product): 
                            $desc = $product['description'] ? htmlspecialchars($product['description']) : 'No description available.';
                            
                            // Determine if product is a best seller (top 3 by orders)
                            $isBestSeller = $index < 3 && $product['total_ordered'] > 0;
                            $isNew = $product['is_new'] == 1;
                            
                            // Check if image exists
                            $imagePath = 'assets/images/' . $product['image'];
                            $imageFile = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/' . $product['image'];
                        ?>
                        <div class="product-slide <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>">
                            <div class="product-card">
                                <!-- Badge Container -->
                                <div class="badge-container">
                                    <div class="badge-left">
                                        <?php if ($isNew): ?>
                                            <span class="new-badge">
                                                <i class="fas fa-tag"></i> NEW
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="badge-right">
                                        <?php if ($isBestSeller): ?>
                                            <span class="best-seller-badge">
                                                <i class="fas fa-crown"></i> BEST SELLER
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <!-- Product Image -->
                                <div class="product-image">
                                    <?php if ($product['image'] && file_exists($imageFile)): ?>
                                        <img src="<?php echo $imagePath; ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                                    <?php else: ?>
                                        <div style="width:100%; height:100%; background: linear-gradient(135deg, #ddd, #eee); display:flex; align-items:center; justify-content:center;">
                                            <i class="fas fa-image fa-4x" style="color: #999;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Product Details - Fully Readable -->
                                <div class="product-details">
                                    <div class="product-name">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </div>
                                    <div class="product-description">
                                        <?php echo nl2br($desc); ?>
                                    </div>
                                    <div class="product-meta">
                                        <span class="product-price">₱<?php echo number_format($product['price'], 2); ?></span>
                                        <span class="product-stock">
                                            <i class="fas fa-box"></i> 
                                            <?php echo $product['stock']; ?> available
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Carousel Controls -->
                        <div class="carousel-controls">
                            <button class="carousel-btn" id="prevBtn">
                                <i class="fas fa-chevron-left"></i>
                            </button>
                            <div class="carousel-dots" id="carouselDots">
                                <?php foreach ($products as $index => $product): ?>
                                    <span class="dot <?php echo $index === 0 ? 'active' : ''; ?>" data-index="<?php echo $index; ?>"></span>
                                <?php endforeach; ?>
                            </div>
                            <button class="carousel-btn" id="nextBtn">
                                <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>

                        <!-- Slide Counter -->
                        <div class="slide-counter">
                            <span id="currentSlide">1</span> / <span id="totalSlides"><?php echo count($products); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column - Login Form -->
            <div class="col-lg-6 order-lg-2 order-1">
                <div class="card login-card">
                    <div class="card-header">
                        <div class="brand-icon">
                            <i class="fas fa-utensil-spoon"></i>
                        </div>
                        <h3>Welcome Back!</h3>
                        <p style="color: rgba(255,255,255,0.9);">Sign in to continue ordering</p>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" id="loginForm">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" id="username" name="username" 
                                       placeholder="Username" required
                                       value="<?php echo htmlspecialchars($saved_username); ?>">
                                <label for="username"><i class="far fa-user"></i> Username</label>
                            </div>

                            <div class="form-floating mb-3 position-relative">
                                <input type="password" class="form-control" id="password" name="password" 
                                       placeholder="Password" required>
                                <label for="password"><i class="fas fa-lock"></i> Password</label>
                                <span class="password-toggle" onclick="togglePassword('password', this)">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember"
                                           <?php echo $saved_username ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="remember">
                                        <i class="far fa-check-circle me-1"></i> Remember me
                                    </label>
                                </div>
                                <div class="forgot-link">
                                    <a href="/forgot-password">
                                        <i class="fas fa-question-circle me-1"></i> Forgot password?
                                    </a>
                                </div>
                            </div>

                            <button type="submit" class="btn-login" id="loginBtn">
                                <i class="fas fa-sign-in-alt"></i> LOGIN
                            </button>

                            <div class="signup-link text-center">
                                <a href="/signup">
                                    <i class="fas fa-user-plus me-2"></i> New customer? Sign up
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <footer>
                    <i class="fas fa-copyright me-1"></i> <?php echo date('Y'); ?> Jen's Kakanin
                </footer>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            once: true,
            offset: 100
        });

        // Toggle password visibility
        function togglePassword(fieldId, element) {
            const field = document.getElementById(fieldId);
            const icon = element.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                element.style.background = '#008080';
                element.style.color = 'white';
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                element.style.background = '#f0f0f0';
                element.style.color = '#666';
            }
        }

        // Loading animation
        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            const btn = document.getElementById('loginBtn');
            btn.classList.add('loading');
            btn.disabled = true;
        });

        // Auto-hide alerts
        setTimeout(function() {
            document.querySelectorAll('.alert').forEach(function(alert) {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.style.display = 'none', 500);
            });
        }, 5000);

        // ===== CAROUSEL FUNCTIONALITY =====
        const slides = document.querySelectorAll('.product-slide');
        const dots = document.querySelectorAll('.dot');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const currentSlideSpan = document.getElementById('currentSlide');
        const totalSlides = slides.length;

        let currentIndex = 0;

        function showSlide(index) {
            // Handle wrap-around
            if (index < 0) index = totalSlides - 1;
            if (index >= totalSlides) index = 0;

            // Hide all slides
            slides.forEach(slide => {
                slide.classList.remove('active');
            });

            // Remove active class from all dots
            dots.forEach(dot => {
                dot.classList.remove('active');
            });

            // Show current slide and activate dot
            slides[index].classList.add('active');
            dots[index].classList.add('active');
            
            // Update counter
            currentSlideSpan.textContent = index + 1;

            currentIndex = index;
        }

        // Event listeners for buttons
        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                showSlide(currentIndex - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                showSlide(currentIndex + 1);
            });
        }

        // Event listeners for dots
        dots.forEach((dot, index) => {
            dot.addEventListener('click', () => {
                showSlide(index);
            });
        });

        // Auto-advance slides every 5 seconds
        let autoAdvance = setInterval(() => {
            showSlide(currentIndex + 1);
        }, 5000);

        // Pause auto-advance on hover
        const carouselContainer = document.querySelector('.carousel-container');
        if (carouselContainer) {
            carouselContainer.addEventListener('mouseenter', () => {
                clearInterval(autoAdvance);
            });

            carouselContainer.addEventListener('mouseleave', () => {
                autoAdvance = setInterval(() => {
                    showSlide(currentIndex + 1);
                }, 5000);
            });
        }

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') {
                showSlide(currentIndex - 1);
            } else if (e.key === 'ArrowRight') {
                showSlide(currentIndex + 1);
            }
        });

        // Touch feedback
        document.querySelectorAll('.btn-login, .btn-home, .password-toggle, .form-check-input, a, .carousel-btn, .dot').forEach(el => {
            el.addEventListener('touchstart', function() {
                this.style.opacity = '0.8';
            });
            el.addEventListener('touchend', function() {
                this.style.opacity = '1';
            });
        });
    </script>
</body>
</html>