<?php
// This file is for CUSTOMER pages only
if (!isset($_SESSION['customer_id'])) {
    header('Location: /login');
    exit;
}

// ===== TRACK ONLINE CUSTOMER =====
function trackCustomerActivity($pdo, $customer_id, $customer_name) {
    try {
        $session_id = session_id();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $current_page = $_SERVER['REQUEST_URI'] ?? '';
        
        // Get cart count
        $cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
        
        // Update or insert online customer
        $stmt = $pdo->prepare("
            INSERT INTO tbl_online_customers 
            (customer_id, customer_name, session_id, ip_address, user_agent, current_page, cart_count, last_activity)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            last_activity = NOW(),
            current_page = VALUES(current_page),
            cart_count = VALUES(cart_count),
            ip_address = VALUES(ip_address),
            user_agent = VALUES(user_agent)
        ");
        $stmt->execute([$customer_id, $customer_name, $session_id, $ip, $user_agent, $current_page, $cart_count]);
        
        // Clean up inactive customers (older than 5 minutes)
        $pdo->exec("DELETE FROM tbl_online_customers WHERE last_activity < DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
        
    } catch (Exception $e) {
        error_log("Failed to track customer: " . $e->getMessage());
    }
}

// Track this customer
if (isset($_SESSION['customer_id']) && isset($_SESSION['customer_name'])) {
    trackCustomerActivity($pdo, $_SESSION['customer_id'], $_SESSION['customer_name']);
}

// Get cart count from session
$cart_count = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'quantity')) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Kakanin Customer</title>
    
    <!-- ===== PWA MANIFEST ===== -->
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Jen's Kakanin">
    <meta name="theme-color" content="#008080">
    <link rel="apple-touch-icon" href="/assets/images/owner.jpg">
    <link rel="apple-touch-startup-image" href="/assets/images/owner.jpg">
    <meta name="mobile-web-app-capable" content="yes">
    
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="/assets/images/owner.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="/assets/images/owner.jpg">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <!-- Notification Sound System -->
    <script src="/assets/js/notification-sound.js"></script>
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            padding-top: 80px;
        }
        .navbar {
            background: linear-gradient(135deg, #008080, #20b2aa) !important;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            padding: 0.5rem 1rem;
        }
        .navbar .container {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
        }
        .navbar-brand {
            font-weight: 600;
            font-size: 1.2rem;
            white-space: nowrap;
            color: white;
            text-decoration: none;
        }
        .navbar-brand:hover {
            color: white;
        }
        .navbar .ms-auto {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }
        .navbar .btn {
            padding: 0.4rem 1rem;
            font-size: 0.9rem;
            border-radius: 50px;
            white-space: nowrap;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        .btn-dashboard {
            background: transparent;
            color: white;
            border: 1px solid white;
        }
        .btn-dashboard:hover {
            background: white;
            color: #008080;
        }
        .btn-order-now {
            background: #ff8c00;
            color: white;
            border: 2px solid #ff8c00;
        }
        .btn-order-now:hover {
            background: #e07b00;
            border-color: #e07b00;
        }
        .btn-about {
            background: transparent;
            color: white;
            border: 1px solid white;
        }
        .btn-about:hover {
            background: white;
            color: #008080;
        }
        .btn-cart {
            background: white;
            color: #008080;
            border: 2px solid white;
        }
        .btn-cart:hover {
            background: #008080;
            color: white;
        }
        .btn-logout {
            background: #dc3545;
            color: white;
            border: 2px solid #dc3545;
        }
        .btn-logout:hover {
            background: #bb2d3b;
        }
        .badge-cart {
            background: #dc3545;
            color: white;
            border-radius: 50px;
            padding: 0.2rem 0.6rem;
            font-size: 0.7rem;
            margin-left: 0.3rem;
            display: inline-block;
            animation: badgePop 0.3s ease;
        }
        @keyframes badgePop {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        /* ===== INSTALL BUTTON ===== */
        .install-btn {
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: linear-gradient(135deg, #ff8c00, #ff6b00);
            color: white;
            border: none;
            border-radius: 50px;
            padding: 12px 24px;
            font-weight: 600;
            font-size: 0.95rem;
            cursor: pointer;
            z-index: 9999;
            display: none;
            box-shadow: 0 4px 15px rgba(255,140,0,0.3);
            animation: slideInLeft 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
            backdrop-filter: blur(5px);
        }
        
        .install-btn i {
            margin-right: 8px;
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
        
        .install-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255,140,0,0.4);
        }
        
        .install-btn:active {
            transform: translateY(-1px);
        }
        
        /* ===== OFFLINE INDICATOR ===== */
        .offline-indicator {
            position: fixed;
            top: 80px;
            left: 0;
            right: 0;
            background: #dc3545;
            color: white;
            text-align: center;
            padding: 8px;
            font-size: 0.85rem;
            z-index: 999;
            display: none;
            animation: slideDown 0.3s ease;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .offline-indicator i {
            margin-right: 8px;
        }
        
        @media (max-width: 576px) {
            body {
                padding-top: 120px;
            }
            .navbar .container {
                flex-direction: column;
                align-items: stretch;
            }
            .navbar-brand {
                margin-bottom: 0.5rem;
                text-align: center;
            }
            .navbar .ms-auto {
                justify-content: center;
                width: 100%;
            }
            .navbar .btn {
                padding: 0.3rem 0.6rem;
                font-size: 0.8rem;
            }
            .install-btn {
                bottom: 15px;
                left: 15px;
                padding: 10px 20px;
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
    <!-- Offline Indicator -->
    <div class="offline-indicator" id="offlineIndicator">
        <i class="fas fa-wifi-slash"></i>
        You are currently offline. Some features may be limited.
    </div>

    <!-- Install App Button -->
    <button class="install-btn" id="installApp" style="display: none;">
        <i class="fas fa-download"></i>
        Install App
    </button>

    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/dashboard">
                <i class="fas fa-store me-2"></i>Jen's Kakanin
            </a>
            <div class="ms-auto d-flex align-items-center flex-wrap">
                <a href="/dashboard" class="btn btn-dashboard">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
                <a href="/menu" class="btn btn-order-now">
                    <i class="fas fa-utensils me-1"></i>Order Now
                </a>
                <a href="/about" class="btn btn-about">
                    <i class="fas fa-info-circle me-1"></i>About
                </a>
                
                <!-- Cart Button with Count -->
                <a href="/cart" class="btn btn-cart">
                    <i class="fas fa-shopping-cart"></i> Cart 
                    <?php if ($cart_count > 0): ?>
                        <span class="badge-cart" id="cartCountBadge"><?php echo $cart_count; ?></span>
                    <?php endif; ?>
                </a>
                
                <!-- Logout Button -->
                <a href="/logout?type=customer" class="btn btn-logout">
                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                </a>
            </div>
        </div>
    </nav>
    
    <!-- ===== PWA SERVICE WORKER REGISTRATION ===== -->
    <script>
    // Register Service Worker
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            navigator.serviceWorker.register('/sw.js').then(function(registration) {
                console.log('✅ ServiceWorker registered with scope:', registration.scope);
                
                // Request notification permission
                if ('Notification' in window && Notification.permission !== 'granted' && Notification.permission !== 'denied') {
                    Notification.requestPermission();
                }
            }, function(err) {
                console.log('❌ ServiceWorker registration failed:', err);
            });
        });
    }

    // Check if app is installed (PWA)
    let deferredPrompt;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        
        // Show install button
        const installBtn = document.getElementById('installApp');
        if (installBtn) {
            installBtn.style.display = 'block';
        }
    });

    // Handle install button click
    document.getElementById('installApp')?.addEventListener('click', async () => {
        if (!deferredPrompt) {
            return;
        }
        
        deferredPrompt.prompt();
        const { outcome } = await deferredPrompt.userChoice;
        console.log(`User response to install prompt: ${outcome}`);
        
        deferredPrompt = null;
        document.getElementById('installApp').style.display = 'none';
    });

    // Detect online/offline status
    function updateOnlineStatus() {
        const indicator = document.getElementById('offlineIndicator');
        if (navigator.onLine) {
            indicator.style.display = 'none';
        } else {
            indicator.style.display = 'block';
        }
    }

    window.addEventListener('online', updateOnlineStatus);
    window.addEventListener('offline', updateOnlineStatus);
    updateOnlineStatus();

    // Listen for app installed
    window.addEventListener('appinstalled', (evt) => {
        console.log('✅ App was installed');
        document.getElementById('installApp').style.display = 'none';
    });
    </script>

    <div class="container-fluid" style="padding-top: 10px;">
<!-- No closing PHP tag -->