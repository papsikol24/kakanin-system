<?php
// Set session cookie parameters
ini_set('session.cookie_lifetime', 0);
ini_set('session.use_cookies', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// Set cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

session_start();

require_once 'includes/config.php';

// Generate a unique tab ID for this browser tab
if (!isset($_SESSION['tab_id'])) {
    $_SESSION['tab_id'] = uniqid('tab_', true);
    // Set cookie for JavaScript
    setcookie('tab_id', $_SESSION['tab_id'], time() + 3600, "/");
}

$valid_access_code = 'STAFF2026';
$error = '';
$show_access_form = true;
$access_verified = false;

// Check if access code is already verified for THIS TAB
if (isset($_SESSION['access_verified']) && 
    isset($_SESSION['access_tab_id']) && 
    $_SESSION['access_tab_id'] === $_SESSION['tab_id']) {
    
    // Check if verification hasn't expired (30 minutes)
    if (time() - $_SESSION['access_time'] < 1800) { // 30 minutes
        $access_verified = true;
        $show_access_form = false;
    } else {
        // Expired, clear it
        unset($_SESSION['access_verified']);
        unset($_SESSION['access_tab_id']);
        unset($_SESSION['access_time']);
    }
}

// Handle access code submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['access_code'])) {
    $entered_code = $_POST['access_code'];
    
    if ($entered_code === $valid_access_code) {
        // Set access verification for THIS TAB
        $_SESSION['access_verified'] = true;
        $_SESSION['access_tab_id'] = $_SESSION['tab_id'];
        $_SESSION['access_time'] = time();
        
        // Redirect to clear POST data
        header('Location: /staff-login');
        exit;
    } else {
        $error = "Invalid access code.";
    }
}

// Remember me cookie
$saved_username = $_COOKIE['remember_staff'] ?? '';

// Handle login form submission
if ($access_verified && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['username'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    $stmt = $pdo->prepare("SELECT * FROM tbl_users WHERE username = ? AND status = 1");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Set user session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_tab_id'] = $_SESSION['tab_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        
        // IMPORTANT: Register the tab immediately after login
        if (!isset($_SESSION['active_tabs'])) {
            $_SESSION['active_tabs'] = [];
        }
        
        $_SESSION['active_tabs'][$_SESSION['tab_id']] = [
            'user_id' => $user['id'],
            'last_activity' => time(),
            'tab_id' => $_SESSION['tab_id']
        ];
        
        // ===== NEW: Track active session in database for real-time monitoring =====
        try {
            $session_id = session_id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // First, create the table if it doesn't exist (safety check)
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `tbl_active_sessions` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `user_id` int(11) NOT NULL,
                  `session_id` varchar(255) NOT NULL,
                  `tab_id` varchar(255) DEFAULT NULL,
                  `last_activity` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
                  `ip_address` varchar(45) DEFAULT NULL,
                  `user_agent` text DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `user_id` (`user_id`),
                  KEY `last_activity` (`last_activity`),
                  KEY `tab_id` (`tab_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
            ");
            
            // Insert or update active session
            $stmt = $pdo->prepare("
                INSERT INTO tbl_active_sessions (user_id, session_id, tab_id, ip_address, user_agent, last_activity)
                VALUES (?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                session_id = VALUES(session_id),
                tab_id = VALUES(tab_id),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                last_activity = NOW()
            ");
            $stmt->execute([$user['id'], $session_id, $_SESSION['tab_id'], $ip, $user_agent]);
            
        } catch (Exception $e) {
            // Log error but don't stop login
            error_log("Failed to track active session: " . $e->getMessage());
        }
        
        // Keep access verification for this tab
        if ($remember) {
            setcookie('remember_staff', $username, time() + (86400 * 30), "/");
        }
        
        // Refresh tab ID cookie
        setcookie('tab_id', $_SESSION['tab_id'], time() + 3600, "/");
        
        // Redirect to dashboard
        header('Location: /staff-dashboard');
        exit;
    } else {
        $error = "Invalid username or password.";
    }
}

// Check if already logged in
if (isset($_SESSION['user_id']) && 
    isset($_SESSION['user_tab_id']) && 
    $_SESSION['user_tab_id'] === $_SESSION['tab_id']) {
    header('Location: /staff-dashboard');
    exit;
}

// Check for expired parameter
$show_expired_warning = isset($_GET['expired']) || isset($_GET['newtab']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Staff Login · Kakanin System</title>
    <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="/assets/images/owner.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="/assets/images/owner.jpg">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: url('/assets/images/background.jpg') no-repeat center center fixed;
            background-size: cover;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 0;
        }

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
            -webkit-tap-highlight-color: transparent;
        }

        .btn-home:hover {
            background: #ff8c00;
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(255, 140, 0, 0.4);
            border-color: transparent;
        }

        .btn-home:active {
            transform: translateY(-1px);
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

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 400px;
            padding: 15px;
            margin: 0 auto;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            animation: fadeInUp 0.8s ease-out;
            overflow: hidden;
            width: 100%;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .login-card .card-header {
            background: transparent;
            text-align: center;
            padding: 2rem 1.5rem 0.5rem;
            position: relative;
        }

        .login-card .card-header h3 {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.25rem;
            font-size: 1.8rem;
        }

        .owner-logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid #d35400;
            padding: 3px;
            object-fit: cover;
            margin-bottom: 0.5rem;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }

        .card-body {
            padding: 2rem;
        }

        .tab-warning {
            background: #ffc107;
            color: #333;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            font-size: 0.9rem;
            animation: slideDown 0.5s ease;
            border-left: 4px solid #dc3545;
        }
        
        .tab-warning i {
            margin-right: 8px;
            color: #dc3545;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .form-floating {
            margin-bottom: 1.2rem;
        }

        .form-control {
            border-radius: 50px;
            padding: 1rem 1.5rem;
            height: auto;
            border: 1px solid #ddd;
            font-size: 0.95rem;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #d35400;
            box-shadow: 0 0 0 0.25rem rgba(211, 84, 0, 0.25);
            outline: none;
        }

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
            background: #d35400;
            color: white;
            transform: translateY(-50%) scale(1.1);
        }

        .btn-login {
            background: linear-gradient(135deg, #d35400, #e67e22);
            border: none;
            border-radius: 50px;
            padding: 1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(211, 84, 0, 0.4);
        }

        .btn-access {
            background: linear-gradient(135deg, #28a745, #20c997);
            border: none;
            border-radius: 50px;
            padding: 1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s;
        }

        .btn-access:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4);
        }

        .form-check-input:checked {
            background-color: #d35400;
            border-color: #d35400;
        }

        .alert {
            border-radius: 50px;
            animation: shake 0.5s;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
        }

        @keyframes shake {
            0%,100% { transform: translateX(0); }
            10%,30%,50%,70%,90% { transform: translateX(-5px); }
            20%,40%,60%,80% { transform: translateX(5px); }
        }

        .alert-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }

        .security-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0,0,0,0.3);
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.7rem;
            backdrop-filter: blur(5px);
        }

        .info-text {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        footer {
            text-align: center;
            margin-top: 1.5rem;
            color: rgba(255,255,255,0.8);
            font-size: 0.8rem;
        }

        .btn-login.loading,
        .btn-access.loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }

        .btn-login.loading::after,
        .btn-access.loading::after {
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
    </style>
</head>
<body>
    <div class="home-button-container">
        <a href="/" class="btn-home" id="homeButton">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
    </div>

    <div class="login-container">
        <div class="card login-card">
            <div class="card-header position-relative">
                <span class="security-badge">
                    <i class="fas fa-shield-alt me-1"></i> Secured Access
                </span>
                <img src="/assets/images/owner.jpg" alt="Owner Logo" class="owner-logo">
                <h3>Staff Login</h3>
                <p class="text-muted">Restricted to authorized personnel only</p>
            </div>
            <div class="card-body">
                <?php if ($show_expired_warning): ?>
                    <div class="tab-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        Session expired or new tab detected. Please re-enter access code.
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if (!$access_verified): ?>
                    <!-- Access Code Form -->
                    <div class="info-text">
                        <i class="fas fa-lock"></i> Enter staff access code to continue
                    </div>
                    <form method="post" id="accessForm">
                        <div class="form-floating mb-3">
                            <input type="password" class="form-control" id="access_code" name="access_code" 
                                   placeholder="Access Code" required autofocus>
                            <label for="access_code"><i class="fas fa-key me-2"></i>Staff Access Code</label>
                        </div>
                        <button type="submit" name="submit_access" class="btn-access">
                            <i class="fas fa-lock-open me-2"></i>Verify Access
                        </button>
                    </form>

                <?php else: ?>
                    <!-- Login Form -->
                    <div class="info-text text-success">
                        <i class="fas fa-check-circle"></i> Access verified. Please log in.
                    </div>
                    <form method="post" id="loginForm">
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="username" name="username" 
                                   placeholder="Username" required
                                   value="<?php echo htmlspecialchars($saved_username); ?>"
                                   autocomplete="username">
                            <label for="username"><i class="far fa-user me-2"></i>Username</label>
                        </div>
                        <div class="form-floating mb-3 position-relative">
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Password" required
                                   autocomplete="current-password">
                            <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                            <span class="password-toggle" onclick="togglePassword('password', this)">
                                <i class="far fa-eye"></i>
                            </span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember" 
                                       <?php echo $saved_username ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="remember">Remember me</label>
                            </div>
                        </div>
                        <button type="submit" name="submit_login" class="btn-login">
                            <i class="fas fa-sign-in-alt me-2"></i>LOGIN
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        <footer>
            &copy; <?php echo date('Y'); ?> Jen's Kakanin. All rights reserved.
        </footer>
    </div>

    <script>
        // Set tab ID cookie for JavaScript
        document.cookie = "tab_id=<?php echo $_SESSION['tab_id']; ?>; path=/; max-age=3600";

        function togglePassword(fieldId, element) {
            const field = document.getElementById(fieldId);
            const icon = element.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                element.style.background = '#d35400';
                element.style.color = 'white';
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                element.style.background = '#f0f0f0';
                element.style.color = '#666';
            }
        }

        // Auto-focus on access code field if visible
        if (document.getElementById('access_code')) {
            document.getElementById('access_code').focus();
        }

        // Loading animation for forms
        document.getElementById('accessForm')?.addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.classList.add('loading');
            btn.disabled = true;
        });

        document.getElementById('loginForm')?.addEventListener('submit', function(e) {
            const btn = this.querySelector('button[type="submit"]');
            btn.classList.add('loading');
            btn.disabled = true;
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
</body>
</html>