<?php
require_once 'includes/config.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['customer_id'])) {
    header('Location: /dashboard');
    exit;
}

// ===== TIERED PRICING RULES =====
function getProductTier($price) {
    if ($price < 10) {
        return [
            'id' => 'budget',
            'name' => 'Budget Items',
            'range' => 'Below ₱10',
            'min' => 20,
            'max' => 300,
            'color' => 'budget',
            'icon' => 'fa-tag',
            'border' => '#28a745',
            'bg' => '#e8f5e9'
        ];
    } elseif ($price >= 10 && $price < 250) {
        return [
            'id' => 'regular',
            'name' => 'Regular Items',
            'range' => '₱10 - ₱249',
            'min' => 20,
            'max' => 300,
            'color' => 'regular',
            'icon' => 'fa-box',
            'border' => '#ffc107',
            'bg' => '#fff3e0'
        ];
    } else {
        return [
            'id' => 'premium',
            'name' => 'Premium Items',
            'range' => '₱250 and above',
            'min' => 1,
            'max' => 10,
            'color' => 'premium',
            'icon' => 'fa-crown',
            'border' => '#dc3545',
            'bg' => '#ffebee'
        ];
    }
}

// Fetch all products
$products = $pdo->query("
    SELECT *, 
    DATEDIFF(NOW(), created_at) <= 7 as is_new 
    FROM tbl_products 
    ORDER BY price, name
")->fetchAll();

// Group products by tier
$tieredProducts = [
    'budget' => ['name' => 'Budget Items', 'items' => [], 'color' => 'budget', 'icon' => 'fa-tag', 'border' => '#28a745'],
    'regular' => ['name' => 'Regular Items', 'items' => [], 'color' => 'regular', 'icon' => 'fa-box', 'border' => '#ffc107'],
    'premium' => ['name' => 'Premium Items', 'items' => [], 'color' => 'premium', 'icon' => 'fa-crown', 'border' => '#dc3545']
];

foreach ($products as $product) {
    if ($product['price'] < 10) {
        $tieredProducts['budget']['items'][] = $product;
    } elseif ($product['price'] >= 10 && $product['price'] < 250) {
        $tieredProducts['regular']['items'][] = $product;
    } else {
        $tieredProducts['premium']['items'][] = $product;
    }
}

// Get best selling products
$bestSellers = $pdo->query("
    SELECT p.id, p.name, p.image, p.price, p.description, SUM(oi.quantity) as total_sold
    FROM tbl_products p
    LEFT JOIN tbl_order_items oi ON p.id = oi.product_id
    GROUP BY p.id
    ORDER BY total_sold DESC
    LIMIT 8
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Jen's Kakanin · Authentic Filipino Rice Cakes</title>
    
    <!-- ===== PWA MANIFEST ===== -->
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Jen's Kakanin">
    <meta name="theme-color" content="#008080">
    <link rel="apple-touch-icon" href="/assets/images/owner.jpg">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- AOS Animation Library -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        /* ===== RESET & GLOBAL ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: #faf7f2;
            color: #333;
            overflow-x: hidden;
        }

        /* ===== CUSTOM SCROLLBAR ===== */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #008080, #20b2aa);
            border-radius: 6px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #006666;
        }

        /* ===== LOADING ANIMATION ===== */
        .loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, #008080, #ff8c00, #20b2aa, #008080);
            background-size: 300% 100%;
            animation: loading 2s ease-in-out infinite;
            z-index: 9999;
        }

        @keyframes loading {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* ===== NAVBAR ===== */
        .navbar {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            padding: 0.5rem 0;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            border-bottom: 1px solid rgba(0,128,128,0.1);
        }

        .navbar-brand {
            font-weight: 800;
            font-size: 1.3rem;
            background: linear-gradient(135deg, #008080, #20b2aa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .navbar-brand img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #008080;
            transition: transform 0.3s ease;
        }

        .navbar-brand:hover img {
            transform: rotate(360deg);
        }

        .nav-link {
            font-weight: 500;
            color: #2c3e50 !important;
            margin: 0 0.3rem;
            transition: all 0.3s ease;
            position: relative;
            padding: 0.4rem 0 !important;
            font-size: 0.85rem;
        }

        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #008080, #20b2aa);
            transition: width 0.3s ease;
        }

        .nav-link:hover {
            color: #008080 !important;
        }

        .nav-link:hover::after {
            width: 80%;
        }

        .btn-login-nav {
            background: linear-gradient(135deg, #008080, #20b2aa) !important;
            color: white !important;
            border-radius: 50px !important;
            padding: 0.4rem 1rem !important;
            margin-left: 0.3rem;
            box-shadow: 0 4px 15px rgba(0,128,128,0.3);
            transition: all 0.3s ease !important;
            font-size: 0.8rem;
        }

        .btn-login-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,128,128,0.4);
        }

        .btn-staff-nav {
            background: linear-gradient(135deg, #d35400, #e67e22) !important;
            color: white !important;
            border-radius: 50px !important;
            padding: 0.4rem 1rem !important;
            margin-left: 0.3rem;
            box-shadow: 0 4px 15px rgba(211,84,0,0.3);
            transition: all 0.3s ease !important;
            font-size: 0.8rem;
        }

        .btn-staff-nav:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(211,84,0,0.4);
        }

        /* ===== HERO SECTION ===== */
        .hero {
            min-height: auto;
            background: linear-gradient(135deg, rgba(0,128,128,0.05) 0%, rgba(255,140,0,0.05) 100%);
            position: relative;
            overflow: hidden;
            padding: 80px 0 40px;
            margin-top: 60px;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('/assets/images/background.jpg') center/cover fixed;
            opacity: 0.03;
            z-index: 0;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
        }

        .hero h1 {
            font-size: 2rem;
            font-weight: 800;
            color: #2c3e50;
            margin-bottom: 0.8rem;
            line-height: 1.2;
        }

        .hero h1 span {
            background: linear-gradient(135deg, #008080, #20b2aa);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
            display: inline-block;
        }

        .hero p {
            font-size: 0.95rem;
            color: #5a6b7a;
            margin-bottom: 1.5rem;
            max-width: 100%;
        }

        .hero-buttons {
            display: flex;
            flex-direction: column;
            gap: 0.8rem;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, #008080, #20b2aa);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 0.7rem 1.2rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0,128,128,0.3);
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,128,128,0.4);
            color: white;
        }

        .btn-outline-custom {
            border: 2px solid #008080;
            color: #008080;
            border-radius: 50px;
            padding: 0.7rem 1.2rem;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            background: transparent;
        }

        .btn-outline-custom:hover {
            background: #008080;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,128,128,0.2);
        }

        .hero-image {
            position: relative;
            margin-top: 1.5rem;
        }

        .hero-image img {
            max-width: 100%;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
            border: 3px solid white;
        }

        /* ===== SECTION TITLES ===== */
        .section-title {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }

        .section-title h2 {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .section-title h2 span {
            color: #008080;
            position: relative;
            display: inline-block;
        }

        .section-title h2 span::before {
            content: '';
            position: absolute;
            bottom: 5px;
            left: 0;
            width: 100%;
            height: 6px;
            background: rgba(255,140,0,0.2);
            z-index: -1;
            border-radius: 4px;
        }

        .section-title p {
            color: #5a6b7a;
            font-size: 0.95rem;
            max-width: 90%;
            margin: 0 auto;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background: linear-gradient(90deg, #008080, #ff8c00);
            border-radius: 2px;
        }

        /* ===== PRODUCT SECTIONS ===== */
        .products-section {
            padding: 40px 0;
            background: white;
        }

        /* Tier Headers */
        .tier-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin: 1.5rem 0 1rem;
            padding: 0.7rem 1rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.03);
            border-left: 4px solid;
            flex-wrap: wrap;
            gap: 0.5rem;
        }

        .tier-header.budget { 
            border-left-color: #28a745; 
            background: #f0fff4;
        }
        .tier-header.regular { 
            border-left-color: #ffc107; 
            background: #fff9e6;
        }
        .tier-header.premium { 
            border-left-color: #dc3545; 
            background: #fff0f0;
        }

        .tier-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .tier-title i {
            font-size: 1.3rem;
        }

        .tier-title i.budget { color: #28a745; }
        .tier-title i.regular { color: #ffc107; }
        .tier-title i.premium { color: #dc3545; }

        .tier-title h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin: 0;
            color: #333;
        }

        .tier-badge {
            padding: 0.2rem 0.8rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .tier-badge.budget {
            background: #28a745;
            color: white;
        }

        .tier-badge.regular {
            background: #ffc107;
            color: #333;
        }

        .tier-badge.premium {
            background: #dc3545;
            color: white;
        }

        /* ===== PRODUCT GRID - 4x4 on Mobile ===== */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
            padding: 0.5rem 0;
        }
        
        @media (min-width: 768px) {
            .product-grid {
                grid-template-columns: repeat(5, 1fr);
                gap: 1rem;
            }
        }
        
        @media (min-width: 992px) {
            .product-grid {
                grid-template-columns: repeat(6, 1fr);
                gap: 1.2rem;
            }
        }

        /* ===== PRODUCT CARDS WITH COLOR-CODED BORDERS ===== */
        .product-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            position: relative;
            border: 1px solid rgba(0,0,0,0.1);
            border-top: 4px solid;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card.budget { 
            border-top-color: #28a745; 
            border-left: 1px solid #28a745;
            border-right: 1px solid #28a745;
            border-bottom: 1px solid #28a745;
        }
        .product-card.regular { 
            border-top-color: #ffc107; 
            border-left: 1px solid #ffc107;
            border-right: 1px solid #ffc107;
            border-bottom: 1px solid #ffc107;
        }
        .product-card.premium { 
            border-top-color: #dc3545; 
            border-left: 1px solid #dc3545;
            border-right: 1px solid #dc3545;
            border-bottom: 1px solid #dc3545;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.1);
        }

        .product-image {
            height: 80px;
            overflow: hidden;
            position: relative;
        }
        
        @media (min-width: 768px) {
            .product-image {
                height: 120px;
            }
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-image img {
            transform: scale(1.1);
        }

        .product-badge {
            position: absolute;
            top: 5px;
            left: 5px;
            background: linear-gradient(135deg, #ff4444, #ff8c00);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 50px;
            font-size: 0.55rem;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(255,68,68,0.2);
        }

        .product-bestseller {
            position: absolute;
            top: 5px;
            right: 5px;
            background: linear-gradient(135deg, #ff8c00, #ff6b00);
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 50px;
            font-size: 0.55rem;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 2px 8px rgba(255,140,0,0.2);
        }

        .product-bestseller i {
            margin-right: 0.1rem;
            font-size: 0.5rem;
            color: gold;
        }

        .tier-tag {
            position: absolute;
            top: 30px;
            right: 5px;
            padding: 0.2rem 0.4rem;
            border-radius: 50px;
            font-size: 0.5rem;
            font-weight: 600;
            z-index: 2;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        .tier-tag.budget {
            background: #28a745;
            color: white;
        }

        .tier-tag.regular {
            background: #ffc107;
            color: #333;
        }

        .tier-tag.premium {
            background: #dc3545;
            color: white;
        }

        .product-info {
            padding: 0.5rem;
            background: white;
            position: relative;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-info h3 {
            font-size: 0.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.2rem;
            line-height: 1.2;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        @media (min-width: 768px) {
            .product-info h3 {
                font-size: 0.95rem;
            }
        }

        .product-description {
            display: none;
        }

        .product-price {
            font-size: 0.9rem;
            font-weight: 800;
            color: #008080;
            margin-bottom: 0.3rem;
        }
        
        @media (min-width: 768px) {
            .product-price {
                font-size: 1.1rem;
            }
        }

        .product-tier-info {
            font-size: 0.55rem;
            color: #666;
            margin-bottom: 0.3rem;
            padding: 0.2rem 0;
            border-top: 1px dashed #eee;
            border-bottom: 1px dashed #eee;
            display: flex;
            align-items: center;
            gap: 0.2rem;
        }

        .product-tier-info i.budget { color: #28a745; }
        .product-tier-info i.regular { color: #ffc107; }
        .product-tier-info i.premium { color: #dc3545; }

        .product-stock {
            font-size: 0.6rem;
            color: #28a745;
            display: flex;
            align-items: center;
            gap: 0.2rem;
            margin-bottom: 0.3rem;
        }

        .product-stock i {
            font-size: 0.6rem;
        }

        .product-stock.low-stock {
            color: #dc3545;
        }

        /* ===== BEST SELLERS SECTION ===== */
        .bestsellers-section {
            padding: 40px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .bestseller-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.5rem;
        }
        
        @media (min-width: 768px) {
            .bestseller-grid {
                grid-template-columns: repeat(4, 1fr);
                gap: 1rem;
            }
        }

        .bestseller-card {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: all 0.3s;
            position: relative;
            border: 1px solid #ff8c00;
        }

        .bestseller-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(255,140,0,0.15);
        }

        .bestseller-image {
            height: 80px;
            overflow: hidden;
        }
        
        @media (min-width: 768px) {
            .bestseller-image {
                height: 120px;
            }
        }

        .bestseller-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .bestseller-card:hover .bestseller-image img {
            transform: scale(1.1);
        }

        .bestseller-info {
            padding: 0.5rem;
            text-align: center;
        }

        .bestseller-info h4 {
            font-size: 0.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.2rem;
        }
        
        @media (min-width: 768px) {
            .bestseller-info h4 {
                font-size: 1rem;
            }
        }

        .bestseller-price {
            font-size: 0.9rem;
            font-weight: 700;
            color: #ff8c00;
        }
        
        @media (min-width: 768px) {
            .bestseller-price {
                font-size: 1.1rem;
            }
        }

        .bestseller-sold {
            font-size: 0.6rem;
            color: #28a745;
            margin-top: 0.2rem;
        }

        /* ===== FEATURES SECTION ===== */
        .features-section {
            padding: 40px 0;
            background: white;
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 1.2rem 1rem;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            transition: all 0.3s;
            height: 100%;
            border: 1px solid rgba(0,128,128,0.1);
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,128,128,0.1);
            border-color: #008080;
        }

        .feature-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #008080, #20b2aa);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.5rem;
        }

        .feature-card h4 {
            font-size: 1rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .feature-card p {
            color: #5a6b7a;
            font-size: 0.8rem;
            line-height: 1.4;
        }

        /* ===== TESTIMONIALS SECTION ===== */
        .testimonials-section {
            padding: 40px 0;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        }

        .testimonial-card {
            background: white;
            border-radius: 15px;
            padding: 1.2rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.03);
            transition: all 0.3s;
            margin: 0.5rem 0;
            border: 1px solid rgba(0,128,128,0.1);
        }

        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0,128,128,0.1);
            border-color: #008080;
        }

        .testimonial-rating {
            color: #ff8c00;
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }

        .testimonial-text {
            font-size: 0.85rem;
            color: #2c3e50;
            margin-bottom: 1rem;
            line-height: 1.5;
            font-style: italic;
        }

        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .testimonial-author img {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #008080;
        }

        .testimonial-author h5 {
            font-size: 0.9rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 0.2rem;
        }

        .testimonial-author p {
            color: #5a6b7a;
            font-size: 0.7rem;
            margin: 0;
        }

        /* ===== CTA SECTION ===== */
        .cta-section {
            padding: 50px 0;
            background: linear-gradient(135deg, #008080, #20b2aa);
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .cta-section h2 {
            font-size: 1.8rem;
            font-weight: 800;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
        }

        .cta-section p {
            font-size: 1rem;
            margin-bottom: 1.5rem;
            opacity: 0.95;
            position: relative;
            z-index: 2;
            max-width: 90%;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-cta {
            background: white;
            color: #008080;
            border: none;
            border-radius: 50px;
            padding: 0.8rem 2rem;
            font-size: 1rem;
            font-weight: 700;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            position: relative;
            z-index: 2;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.25);
            color: #ff8c00;
        }

        /* ===== FOOTER ===== */
        .footer {
            background: #2c3e50;
            color: white;
            padding: 40px 0 20px;
        }

        .footer h5 {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #ff8c00;
        }

        .footer p, .footer a {
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: color 0.3s;
            font-size: 0.8rem;
            line-height: 1.5;
        }

        .footer a:hover {
            color: #ff8c00;
        }

        .social-links {
            display: flex;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .social-links a {
            width: 35px;
            height: 35px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            font-size: 1rem;
        }

        .social-links a:hover {
            background: #ff8c00;
            transform: translateY(-3px);
        }

        .footer-bottom {
            text-align: center;
            padding-top: 1.5rem;
            margin-top: 2rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.6);
            font-size: 0.75rem;
        }

        /* ===== BACK TO TOP BUTTON ===== */
        .back-to-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #008080, #20b2aa);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 999;
            border: none;
            box-shadow: 0 5px 15px rgba(0,128,128,0.3);
        }

        .back-to-top.show {
            opacity: 1;
            visibility: visible;
        }

        .back-to-top:hover {
            background: #ff8c00;
            transform: translateY(-3px);
        }

        /* ===== PWA INSTALL BUTTON - VISIBLE ON ALL DEVICES ===== */
        .pwa-install-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: linear-gradient(135deg, #008080, #20b2aa);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            z-index: 9999;
            display: none;
            box-shadow: 0 4px 15px rgba(0,128,128,0.3);
            animation: slideInLeft 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .pwa-install-btn i {
            font-size: 1.1rem;
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .pwa-install-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0,128,128,0.4);
        }

        .pwa-install-btn:active {
            transform: translateY(-1px);
        }

        /* Desktop specific styles */
        @media (min-width: 1024px) {
            .pwa-install-btn {
                bottom: 30px;
                left: 30px;
                padding: 14px 28px;
                font-size: 1rem;
                box-shadow: 0 6px 20px rgba(0,128,128,0.4);
            }
            
            .pwa-install-btn:hover {
                transform: translateY(-4px);
                box-shadow: 0 12px 30px rgba(0,128,128,0.5);
            }
        }

        /* Tablet styles */
        @media (min-width: 768px) and (max-width: 1023px) {
            .pwa-install-btn {
                bottom: 25px;
                left: 25px;
                padding: 13px 26px;
            }
        }

        /* Mobile styles */
        @media (max-width: 576px) {
            .pwa-install-btn {
                bottom: 15px;
                left: 15px;
                padding: 10px 20px;
                font-size: 0.85rem;
                box-shadow: 0 4px 12px rgba(0,128,128,0.3);
            }
        }

        /* ===== RESPONSIVE ADJUSTMENTS ===== */
        @media (min-width: 768px) {
            .hero h1 {
                font-size: 3rem;
            }
            
            .hero p {
                font-size: 1.1rem;
            }
            
            .hero-buttons {
                flex-direction: row;
            }
            
            .product-description {
                display: block;
                font-size: 0.75rem;
                color: #5a6b7a;
                margin-bottom: 0.5rem;
                line-height: 1.3;
            }
            
            .product-info h3 {
                font-size: 0.9rem;
            }
        }

        @media (max-width: 480px) {
            .navbar-brand {
                font-size: 1.1rem;
            }
            
            .navbar-brand img {
                width: 30px;
                height: 30px;
            }
            
            .btn-login-nav, .btn-staff-nav {
                padding: 0.3rem 0.8rem !important;
                font-size: 0.7rem;
            }
            
            .hero h1 {
                font-size: 1.8rem;
            }
            
            .product-image {
                height: 70px;
            }
            
            .bestseller-image {
                height: 70px;
            }
            
            .product-info h3 {
                font-size: 0.7rem;
            }
            
            .product-price {
                font-size: 0.8rem;
            }
            
            .product-tier-info {
                font-size: 0.5rem;
            }
            
            .product-stock {
                font-size: 0.5rem;
            }
            
            .tier-title h3 {
                font-size: 1rem;
            }
            
            .tier-badge {
                font-size: 0.7rem;
                padding: 0.2rem 0.6rem;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Bar -->
    <div class="loading-bar"></div>

    <!-- PWA Install Button - Visible on ALL devices -->
    <button class="pwa-install-btn" id="installApp" style="display: none;">
        <i class="fas fa-download"></i>
        <span>Install App</span>
    </button>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="/">
                <img src="/assets/images/owner.jpg" alt="Jen's Kakanin">
                <span>Jen's Kakanin</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#products">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#bestsellers">Best Sellers</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Features</a>
                    </li>
                      <li class="nav-item">
                        <a class="nav-link" href="#testimonials">Testimonials</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-login-nav" href="/login">
                            <i class="fas fa-user me-1"></i>Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link btn-staff-nav" href="/staff-login">
                            <i class="fas fa-user-tie me-1"></i>Staff
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6 hero-content">
                    <h1>
                        <span>Authentic</span><br>
                        Filipino Kakanin
                    </h1>
                    <p>Experience the taste of tradition with our freshly made rice cakes, prepared daily using family recipes passed down through generations.</p>
                    <div class="hero-buttons">
                        <a href="#products" class="btn-primary-custom">
                            <i class="fas fa-utensils me-2"></i>View Products
                        </a>
                        <a href="/login" class="btn-outline-custom">
                            <i class="fas fa-user me-2"></i>Customer Login
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image">
                        <img src="/assets/images/bilao.jpg" alt="Assorted Kakanin" class="img-fluid">
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section with Color-Coded Borders -->
    <section id="products" class="products-section">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Our <span>Kakanin</span> Selection</h2>
                <p>Discover our wide variety of traditional Filipino rice cakes</p>
            </div>

            <!-- Budget Tier - Green Borders -->
            <?php if (!empty($tieredProducts['budget']['items'])): ?>
            <div class="tier-header budget" data-aos="fade-right">
                <div class="tier-title">
                    <i class="fas fa-tag budget"></i>
                    <h3>Budget Items</h3>
                </div>
                <span class="tier-badge budget">Below ₱10</span>
            </div>

            <div class="product-grid">
                <?php foreach ($tieredProducts['budget']['items'] as $index => $product): 
                    $desc = $product['description'] ? htmlspecialchars($product['description']) : 'Traditional Filipino delicacy';
                    
                    // Check if product is a best seller
                    $isBestSeller = false;
                    foreach ($bestSellers as $best) {
                        if ($best['id'] == $product['id']) {
                            $isBestSeller = true;
                            break;
                        }
                    }
                    
                    $imagePath = '/assets/images/' . $product['image'];
                    $imageFile = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/' . $product['image'];
                ?>
                <div class="product-card budget" data-aos="zoom-in" data-aos-delay="<?php echo $index * 30; ?>">
                    <?php if ($product['is_new']): ?>
                        <div class="product-badge">
                            <i class="fas fa-star"></i> NEW
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($isBestSeller): ?>
                        <div class="product-bestseller">
                            <i class="fas fa-crown"></i> Best
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-image">
                        <?php if ($product['image'] && file_exists($imageFile)): ?>
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <img src="/assets/images/placeholder.jpg" alt="Placeholder">
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-description"><?php echo $desc; ?></div>
                        <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                        <div class="product-tier-info">
                            <i class="fas fa-tag budget"></i> Min: 20 | Max: 300
                        </div>
                        <div class="product-stock <?php echo $product['stock'] <= 5 ? 'low-stock' : ''; ?>">
                            <i class="fas fa-box"></i>
                            <?php echo $product['stock'] > 0 ? $product['stock'] . ' available' : 'Out of stock'; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Regular Tier - Yellow Borders -->
            <?php if (!empty($tieredProducts['regular']['items'])): ?>
            <div class="tier-header regular" data-aos="fade-right">
                <div class="tier-title">
                    <i class="fas fa-box regular"></i>
                    <h3>Regular Items</h3>
                </div>
                <span class="tier-badge regular">₱10 - ₱249</span>
            </div>

            <div class="product-grid">
                <?php foreach ($tieredProducts['regular']['items'] as $index => $product): 
                    $desc = $product['description'] ? htmlspecialchars($product['description']) : 'Traditional Filipino delicacy';
                    
                    // Check if product is a best seller
                    $isBestSeller = false;
                    foreach ($bestSellers as $best) {
                        if ($best['id'] == $product['id']) {
                            $isBestSeller = true;
                            break;
                        }
                    }
                    
                    $imagePath = '/assets/images/' . $product['image'];
                    $imageFile = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/' . $product['image'];
                ?>
                <div class="product-card regular" data-aos="zoom-in" data-aos-delay="<?php echo $index * 30; ?>">
                    <?php if ($product['is_new']): ?>
                        <div class="product-badge">
                            <i class="fas fa-star"></i> NEW
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($isBestSeller): ?>
                        <div class="product-bestseller">
                            <i class="fas fa-crown"></i> Best
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-image">
                        <?php if ($product['image'] && file_exists($imageFile)): ?>
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <img src="/assets/images/placeholder.jpg" alt="Placeholder">
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-description"><?php echo $desc; ?></div>
                        <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                        <div class="product-tier-info">
                            <i class="fas fa-box regular"></i> Min: 20 | Max: 300
                        </div>
                        <div class="product-stock <?php echo $product['stock'] <= 5 ? 'low-stock' : ''; ?>">
                            <i class="fas fa-box"></i>
                            <?php echo $product['stock'] > 0 ? $product['stock'] . ' available' : 'Out of stock'; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Premium Tier - Red Borders -->
            <?php if (!empty($tieredProducts['premium']['items'])): ?>
            <div class="tier-header premium" data-aos="fade-right">
                <div class="tier-title">
                    <i class="fas fa-crown premium"></i>
                    <h3>Premium Items</h3>
                </div>
                <span class="tier-badge premium">₱250+</span>
            </div>

            <div class="product-grid">
                <?php foreach ($tieredProducts['premium']['items'] as $index => $product): 
                    $desc = $product['description'] ? htmlspecialchars($product['description']) : 'Traditional Filipino delicacy';
                    
                    // Check if product is a best seller
                    $isBestSeller = false;
                    foreach ($bestSellers as $best) {
                        if ($best['id'] == $product['id']) {
                            $isBestSeller = true;
                            break;
                        }
                    }
                    
                    $imagePath = '/assets/images/' . $product['image'];
                    $imageFile = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/' . $product['image'];
                ?>
                <div class="product-card premium" data-aos="zoom-in" data-aos-delay="<?php echo $index * 30; ?>">
                    <?php if ($product['is_new']): ?>
                        <div class="product-badge">
                            <i class="fas fa-star"></i> NEW
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($isBestSeller): ?>
                        <div class="product-bestseller">
                            <i class="fas fa-crown"></i> Best
                        </div>
                    <?php endif; ?>
                    
                    <div class="product-image">
                        <?php if ($product['image'] && file_exists($imageFile)): ?>
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <img src="/assets/images/placeholder.jpg" alt="Placeholder">
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-info">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <div class="product-description"><?php echo $desc; ?></div>
                        <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                        <div class="product-tier-info">
                            <i class="fas fa-crown premium"></i> Min: 1 | Max: 10
                        </div>
                        <div class="product-stock <?php echo $product['stock'] <= 5 ? 'low-stock' : ''; ?>">
                            <i class="fas fa-box"></i>
                            <?php echo $product['stock'] > 0 ? $product['stock'] . ' available' : 'Out of stock'; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Best Sellers Section -->
    <section id="bestsellers" class="bestsellers-section">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Our <span>Best</span> Sellers</h2>
                <p>Customer favorites that keep them coming back for more</p>
            </div>

            <div class="bestseller-grid">
                <?php foreach ($bestSellers as $best): 
                    $imagePath = '/assets/images/' . $best['image'];
                    $imageFile = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/' . $best['image'];
                ?>
                <div class="bestseller-card" data-aos="fade-up" data-aos-delay="50">
                    <div class="bestseller-image">
                        <?php if ($best['image'] && file_exists($imageFile)): ?>
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($best['name']); ?>">
                        <?php else: ?>
                            <img src="/assets/images/placeholder.jpg" alt="Placeholder">
                        <?php endif; ?>
                    </div>
                    <div class="bestseller-info">
                        <h4><?php echo htmlspecialchars($best['name']); ?></h4>
                        <div class="bestseller-price">₱<?php echo number_format($best['price'], 2); ?></div>
                        <div class="bestseller-sold">
                            <i class="fas fa-shopping-bag"></i> <?php echo $best['total_sold'] ?? 0; ?> sold
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>Why Choose <span>Us</span></h2>
                <p>We're committed to bringing you the best quality and service</p>
            </div>

            <div class="row g-3">
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="50">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <h4>Fresh Ingredients</h4>
                        <p>Locally-sourced ingredients</p>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clock"></i>
                        </div>
                        <h4>Made Daily</h4>
                        <p>Fresh every day</p>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="150">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-truck"></i>
                        </div>
                        <h4>Fast Delivery</h4>
                        <p>Quick to your doorstep</p>
                    </div>
                </div>
                <div class="col-6 col-md-3" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-heart"></i>
                        </div>
                        <h4>Family Recipe</h4>
                        <p>Generations of love</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="testimonials-section">
        <div class="container">
            <div class="section-title" data-aos="fade-up">
                <h2>What <span>Customers</span> Say</h2>
                <p>Hear from our happy customers</p>
            </div>

            <div class="row">
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="50">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"The best bibingka I've ever tasted! Perfect for family gatherings."</p>
                        <div class="testimonial-author">
                            <img src="/assets/images/paps.jpg" alt="Paps and Friends">
                            <div>
                                <h5>Paps and Friends</h5>
                                <p>Regular Customer</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="100">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"Love their puto and kutsinta! So soft and delicious. Fast delivery too!"</p>
                        <div class="testimonial-author">
                            <img src="/assets/images/renren.jpg" alt="Renren and Friends">
                            <div>
                                <h5>Renren and Friends</h5>
                                <p>Happy Customer</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" data-aos="fade-up" data-aos-delay="150">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <p class="testimonial-text">"The bilao assortment is perfect for parties! Great value for money!"</p>
                        <div class="testimonial-author">
                            <img src="/assets/images/dip.jpg" alt="DIP IT BLOCK 7 and 14">
                            <div>
                                <h5>DIP IT BLOCK 7 and 14</h5>
                                <p>Event Organizer</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <h2 data-aos="fade-up">Ready to Satisfy Your Cravings?</h2>
            <p data-aos="fade-up" data-aos-delay="50">Order now and experience authentic Filipino kakanin delivered to your doorstep!</p>
            <a href="/login" class="btn-cta" data-aos="fade-up" data-aos-delay="100">
                <i class="fas fa-shopping-cart me-2"></i>Order Now
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-3" data-aos="fade-up">
                    <h5>Jen's Kakanin</h5>
                    <p>Sari-saring sarap, siguradong tatak Pinoy! Since 2020.</p>
                    <div class="social-links">
                        <a href="https://www.facebook.com/jenelyndaantos.patagan" target="_blank" class="social-link" title="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" target="_blank" class="social-link" title="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" target="_blank" class="social-link" title="Twitter">
                            <i class="fab fa-twitter"></i>
                        </a>
                    </div>
                </div>
                
                <div class="col-md-4 mb-3" data-aos="fade-up" data-aos-delay="50">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li class="mb-1"><a href="#home"><i class="fas fa-chevron-right me-2"></i>Home</a></li>
                        <li class="mb-1"><a href="#products"><i class="fas fa-chevron-right me-2"></i>Products</a></li>
                        <li class="mb-1"><a href="#bestsellers"><i class="fas fa-chevron-right me-2"></i>Best Sellers</a></li>
                        <li class="mb-1"><a href="#features"><i class="fas fa-chevron-right me-2"></i>Features</a></li>
                        <li class="mb-1"><a href="#testimonials"><i class="fas fa-chevron-right me-2"></i>Testimonials</a></li>
                    </ul>
                </div>
                
                <div class="col-md-4 mb-3" data-aos="fade-up" data-aos-delay="100">
                    <h5>Contact Info</h5>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>Brgy 83-B Cogon San Jose, Tacloban City</li>
                        <li class="mb-2"><i class="fas fa-phone me-2"></i>0935 606 2163</li>
                        <li class="mb-2"><i class="fas fa-envelope me-2"></i>jenskakanin@gmail.com</li>
                        
                        <!-- BUSINESS HOURS ADDED HERE -->
                        <li class="mb-2 mt-3"><i class="fas fa-clock me-2" style="color: #ff8c00;"></i> <strong>Business Hours:</strong></li>
                        <li class="mb-1 ms-2">
                            <span style="display: inline-block; width: 70px; color: #28a745;">Mon-Fri:</span>
                            9:00 AM - 4:00 PM
                        </li>
                        <li class="mb-1 ms-2">
                            <span style="display: inline-block; width: 70px; color: #28a745;">Saturday:</span>
                            9:00 AM - 4:00 PM
                        </li>
                        <li class="mb-1 ms-2">
                            <span style="display: inline-block; width: 70px; color: #dc3545;">Sunday:</span>
                            <span style="color: #dc3545;">Closed</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Jen's Kakanin. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button class="back-to-top" id="backToTop">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <!-- ===== PWA Service Worker Registration ===== -->
    <script>
    // Register Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').then(function(registration) {
                console.log('✅ ServiceWorker registered with scope:', registration.scope);
            }, function(err) {
                console.log('❌ ServiceWorker registration failed:', err);
            });
        });
    }

    // PWA Install Prompt - Works on all devices
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        
        // Show install button on ALL devices
        const installBtn = document.getElementById('installApp');
        if (installBtn) {
            installBtn.style.display = 'flex';
            console.log('📱 Install button shown - App can be installed');
        }
    });

    // Handle install button click
    document.getElementById('installApp')?.addEventListener('click', async () => {
        if (!deferredPrompt) {
            alert('App installation is not available at this moment. You can also install from your browser menu.');
            return;
        }
        
        // Show install prompt
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`User response to install prompt: ${outcome}`);
        
        deferredPrompt = null;
        document.getElementById('installApp').style.display = 'none';
    });

    // Hide install button if app is already installed
    window.addEventListener('appinstalled', (evt) => {
        console.log('✅ App was installed successfully');
        document.getElementById('installApp').style.display = 'none';
    });

    // Detect online/offline status
    function updateOnlineStatus() {
        if (!navigator.onLine) {
            console.log('📴 App is offline');
        }
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();

    // Initialize AOS
    AOS.init({
        duration: 600,
        once: true,
        offset: 50
    });

    // Back to Top Button
    const backToTop = document.getElementById('backToTop');
    
    window.addEventListener('scroll', function() {
        if (window.scrollY > 200) {
            backToTop.classList.add('show');
        } else {
            backToTop.classList.remove('show');
        }
    });

    backToTop.addEventListener('click', function() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });

    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // Navbar background change on scroll
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,0.1)';
        } else {
            navbar.style.background = 'rgba(255, 255, 255, 0.98)';
            navbar.style.boxShadow = '0 4px 30px rgba(0,0,0,0.1)';
        }
    });

    // Loading bar animation complete
    setTimeout(() => {
        document.querySelector('.loading-bar').style.opacity = '0';
        setTimeout(() => {
            document.querySelector('.loading-bar').style.display = 'none';
        }, 500);
    }, 2000);
    </script>
</body>
</html>