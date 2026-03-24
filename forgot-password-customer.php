<?php
require_once 'includes/config.php';

$step = 1; // 1: username, 2: security question, 3: new password
$username = '';
$error = '';
$question = '';
$success = '';

// Start session if not already
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Animation variables
$pageAnimation = 'fadeIn';
$cardAnimation = 'popIn';

// Handle step 1: username submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['username'])) {
        // Step 1: check username
        $username = trim($_POST['username']);
        $stmt = $pdo->prepare("SELECT id, security_question, security_answer FROM tbl_customers WHERE username = ?");
        $stmt->execute([$username]);
        $customer = $stmt->fetch();
        if ($customer) {
            $_SESSION['reset_customer_id'] = $customer['id'];
            $_SESSION['reset_question'] = $customer['security_question'];
            $_SESSION['reset_answer'] = $customer['security_answer'];
            $step = 2;
            $pageAnimation = 'fadeIn';
            $cardAnimation = 'popIn';
        } else {
            $error = "Username not found.";
            $cardAnimation = 'shake';
        }
    } elseif (isset($_POST['security_answer'])) {
        // Step 2: verify security answer
        if (!isset($_SESSION['reset_customer_id'])) {
            header('Location: /forgot-password');
            exit;
        }
        $answer = strtolower(trim($_POST['security_answer']));
        $stored_answer = $_SESSION['reset_answer'];
        if (password_verify($answer, $stored_answer)) {
            $step = 3;
            $pageAnimation = 'fadeIn';
            $cardAnimation = 'popIn';
        } else {
            $error = "Incorrect answer.";
            $step = 2;
            $question = $_SESSION['reset_question'];
            $cardAnimation = 'shake';
        }
    } elseif (isset($_POST['new_password'])) {
        // Step 3: set new password
        if (!isset($_SESSION['reset_customer_id'])) {
            header('Location: /forgot-password');
            exit;
        }
        $new_password = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];
        if ($new_password !== $confirm) {
            $error = "Passwords do not match.";
            $step = 3;
            $cardAnimation = 'shake';
        } elseif (strlen($new_password) < 6) {
            $error = "Password must be at least 6 characters.";
            $step = 3;
            $cardAnimation = 'shake';
        } else {
            $hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE tbl_customers SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $_SESSION['reset_customer_id']]);
            // Clear session
            unset($_SESSION['reset_customer_id'], $_SESSION['reset_question'], $_SESSION['reset_answer']);
            $success = "Password updated successfully!";
            $step = 4; // done
            $cardAnimation = 'popIn';
        }
    }
} else {
    // Initial load, maybe clear any old session
    if (isset($_SESSION['reset_customer_id'])) {
        session_destroy();
        session_start();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes, viewport-fit=cover">
    <title>Forgot Password · Jen's Kakanin</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* ===== RESET & GLOBAL ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            overflow-x: hidden;
            -webkit-overflow-scrolling: touch;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: url('assets/images/background.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background overlay */
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.6);
            z-index: 0;
            animation: fadeIn 1.5s ease;
        }
        
        /* Floating particles animation */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 1;
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
            opacity: 0.5;
        }
        
        .particle:nth-child(1) {
            left: 10%;
            width: 60px;
            height: 60px;
            animation-delay: 0s;
            background: rgba(0, 128, 128, 0.1);
        }
        
        .particle:nth-child(2) {
            left: 20%;
            width: 40px;
            height: 40px;
            animation-delay: 2s;
            animation-duration: 17s;
            background: rgba(255, 140, 0, 0.1);
        }
        
        .particle:nth-child(3) {
            left: 35%;
            width: 80px;
            height: 80px;
            animation-delay: 4s;
            background: rgba(0, 128, 128, 0.1);
        }
        
        .particle:nth-child(4) {
            left: 50%;
            width: 30px;
            height: 30px;
            animation-delay: 0s;
            animation-duration: 20s;
            background: rgba(255, 140, 0, 0.1);
        }
        
        .particle:nth-child(5) {
            left: 65%;
            width: 50px;
            height: 50px;
            animation-delay: 0s;
            background: rgba(0, 128, 128, 0.1);
        }
        
        .particle:nth-child(6) {
            left: 80%;
            width: 45px;
            height: 45px;
            animation-delay: 3s;
            background: rgba(255, 140, 0, 0.1);
        }
        
        .particle:nth-child(7) {
            left: 90%;
            width: 70px;
            height: 70px;
            animation-delay: 1s;
            animation-duration: 30s;
            background: rgba(0, 128, 128, 0.1);
        }
        
        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0.5;
                border-radius: 50%;
            }
            100% {
                transform: translateY(-1000px) rotate(720deg);
                opacity: 0;
                border-radius: 40%;
            }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        /* POP-UP ANIMATION */
        @keyframes popIn {
            0% {
                opacity: 0;
                transform: scale(0.3);
            }
            50% {
                opacity: 0.8;
                transform: scale(1.05);
            }
            100% {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        /* Shake animation for errors */
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .forgot-container {
            position: relative;
            z-index: 2;
            max-width: 450px;
            width: 100%;
            padding: 15px;
            animation: <?php echo $pageAnimation; ?> 0.5s ease;
        }
        
        .forgot-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: none;
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            animation: <?php echo $cardAnimation; ?> 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transition: all 0.3s ease;
            transform-origin: center;
            width: 100%;
        }
        
        .forgot-card:hover {
            box-shadow: 0 30px 60px rgba(0, 128, 128, 0.3);
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(135deg, #008080, #20b2aa);
            color: white;
            text-align: center;
            padding: 1.8rem 1.2rem;
            border-bottom: none;
            position: relative;
            overflow: hidden;
        }
        
        .card-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            animation: rotate 10s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .card-header h4 {
            margin: 0;
            font-weight: 600;
            font-size: 1.8rem;
            position: relative;
            z-index: 1;
            animation: fadeInDown 0.5s ease;
        }
        
        .card-header i {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            animation: bounce 2s infinite;
            position: relative;
            z-index: 1;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .card-body {
            padding: 2rem;
        }

        @media (max-width: 576px) {
            .card-body {
                padding: 1.5rem;
            }
            .card-header h4 {
                font-size: 1.5rem;
            }
            .card-header i {
                font-size: 2rem;
            }
        }
        
        /* Step indicators */
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
            gap: 10px;
        }
        
        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #6c757d;
            transition: all 0.3s ease;
            position: relative;
            animation: fadeIn 0.5s ease backwards;
        }
        
        .step:nth-child(1) { animation-delay: 0.1s; }
        .step:nth-child(2) { animation-delay: 0.2s; }
        .step:nth-child(3) { animation-delay: 0.3s; }
        
        .step.active {
            background: #008080;
            color: white;
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(0, 128, 128, 0.5);
            animation: pulse 1.5s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1.1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1.1); }
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step i {
            font-size: 1.2rem;
        }
        
        .step-connector {
            width: 50px;
            height: 2px;
            background: #e9ecef;
            align-self: center;
            animation: growWidth 0.5s ease;
        }
        
        .step-connector.active {
            background: #008080;
        }
        
        @keyframes growWidth {
            from { width: 0; }
            to { width: 50px; }
        }
        
        /* Password field with eye icon */
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        
        .password-wrapper .form-control {
            padding-right: 45px;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
            z-index: 10;
            transition: all 0.2s ease;
            background: transparent;
            border: none;
            padding: 5px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .password-toggle:hover {
            color: #008080;
            transform: translateY(-50%) scale(1.1);
        }
        
        .password-toggle:active {
            transform: translateY(-50%) scale(0.95);
        }
        
        .password-toggle i {
            font-size: 1.2rem;
            pointer-events: none;
        }
        
        /* Form elements */
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
            animation: fadeIn 0.5s ease;
            font-size: 0.95rem;
        }
        
        .form-control {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            border: 2px solid #e9ecef;
            font-size: 16px;
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease;
            width: 100%;
        }
        
        .form-control:focus {
            border-color: #008080;
            box-shadow: 0 0 0 0.25rem rgba(0, 128, 128, 0.25);
            transform: translateY(-2px);
            outline: none;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #008080, #20b2aa);
            border: none;
            border-radius: 50px;
            padding: 0.9rem 1rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 1rem;
            font-size: 1rem;
            -webkit-tap-highlight-color: transparent;
        }
        
        .btn-submit::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.5s, height 0.5s;
        }
        
        .btn-submit:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 128, 128, 0.4);
        }
        
        .btn-submit:active {
            transform: scale(0.98);
        }
        
        .btn-submit i {
            margin-right: 0.5rem;
            transition: transform 0.3s ease;
        }
        
        /* Alert animations */
        .alert {
            border-radius: 50px;
            animation: slideInDown 0.5s ease;
            border: none;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        @keyframes slideInDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
        }
        
        .alert i {
            margin-right: 0.5rem;
            animation: pulse 2s infinite;
        }
        
        /* Question display */
        .question-box {
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            border-radius: 50px;
            padding: 1.5rem;
            text-align: center;
            margin-bottom: 1.5rem;
            border-left: 5px solid #008080;
            animation: fadeIn 0.5s ease;
            position: relative;
            overflow: hidden;
        }
        
        .question-box::before {
            content: '\f059';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 2rem;
            color: rgba(0, 128, 128, 0.1);
            animation: spin 10s linear infinite;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .question-box p {
            margin: 0;
            font-size: 1.1rem;
            color: #333;
            font-weight: 500;
        }
        
        /* Back link */
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            animation: fadeIn 0.7s ease;
        }
        
        .back-link a {
            color: #008080;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            font-size: 0.95rem;
            -webkit-tap-highlight-color: transparent;
        }
        
        .back-link a:hover {
            color: #20b2aa;
        }
        
        .back-link a:active {
            transform: translateX(-5px);
        }
        
        .back-link a i {
            transition: transform 0.3s ease;
        }
        
        /* Success animation */
        .success-checkmark {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            animation: popIn 0.5s ease;
        }
        
        .success-checkmark i {
            font-size: 5rem;
            color: #28a745;
            animation: checkmark 0.5s ease 0.2s both;
        }
        
        @keyframes checkmark {
            0% { transform: scale(0); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        
        /* Password strength indicator */
        .password-strength {
            height: 5px;
            background: #e9ecef;
            border-radius: 5px;
            margin-top: 0.5rem;
            overflow: hidden;
            animation: growWidth 0.5s ease;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }
        
        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #28a745; }
        
        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 1rem;
            height: 1rem;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s linear infinite;
            margin-right: 0.5rem;
        }
        
        .btn-submit.loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        
        .btn-submit.loading::after {
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
        
        /* Responsive */
        @media (max-width: 576px) {
            .step-connector {
                width: 30px;
            }
            @keyframes growWidth {
                from { width: 0; }
                to { width: 30px; }
            }
            .question-box p {
                font-size: 1rem;
            }
            .question-box {
                padding: 1.2rem;
            }
        }
        
        /* Touch optimizations */
        .btn-submit,
        .password-toggle,
        .back-link a {
            -webkit-tap-highlight-color: transparent;
        }
    </style>
</head>
<body>
    <!-- Floating particles for background animation -->
    <div class="particles">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>

    <div class="forgot-container">
        <div class="card forgot-card">
            <div class="card-header">
                <i class="fas fa-key"></i>
                <h4>Forgot Password</h4>
            </div>
            <div class="card-body">
                <!-- Step Indicators -->
                <?php if ($step != 4): ?>
                <div class="step-indicator">
                    <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                        <?php if ($step > 1): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            1
                        <?php endif; ?>
                    </div>
                    <div class="step-connector <?php echo $step > 1 ? 'active' : ''; ?>"></div>
                    <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                        <?php if ($step > 2): ?>
                            <i class="fas fa-check"></i>
                        <?php else: ?>
                            2
                        <?php endif; ?>
                    </div>
                    <div class="step-connector <?php echo $step > 2 ? 'active' : ''; ?>"></div>
                    <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                        3
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="text-center">
                        <div class="success-checkmark">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <?php echo $success; ?>
                        </div>
                        <a href="/login" class="btn-submit mt-3" style="display: inline-block; width: auto; padding: 0.9rem 2rem;">
                            <i class="fas fa-sign-in-alt"></i> Proceed to Login
                        </a>
                    </div>
                <?php else: ?>
                    <?php if ($step == 1): ?>
                        <!-- Step 1: Username -->
                        <form method="post" id="usernameForm">
                            <div class="mb-4">
                                <label for="username" class="form-label">
                                    <i class="fas fa-user me-2"></i>Username
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="username" 
                                       name="username" 
                                       placeholder="Enter your username"
                                       value="<?php echo htmlspecialchars($username); ?>"
                                       required 
                                       autofocus>
                            </div>
                            <button type="submit" class="btn-submit" id="submitBtn">
                                <span class="spinner" style="display: none;"></span>
                                <span class="btn-text">Next Step <i class="fas fa-arrow-right"></i></span>
                            </button>
                        </form>

                    <?php elseif ($step == 2): ?>
                        <!-- Step 2: Security Question -->
                        <form method="post" id="securityForm">
                            <div class="question-box">
                                <p><?php echo htmlspecialchars($_SESSION['reset_question']); ?></p>
                            </div>
                            <div class="mb-4">
                                <label for="security_answer" class="form-label">
                                    <i class="fas fa-pencil-alt me-2"></i>Your Answer
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="security_answer" 
                                       name="security_answer" 
                                       placeholder="Type your answer here"
                                       required 
                                       autofocus>
                            </div>
                            <button type="submit" class="btn-submit">
                                <i class="fas fa-check"></i> Verify Answer
                            </button>
                        </form>

                    <?php elseif ($step == 3): ?>
                        <!-- Step 3: New Password with Eye Icons -->
                        <form method="post" id="passwordForm">
                            <div class="mb-4">
                                <label for="new_password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>New Password
                                </label>
                                <div class="password-wrapper">
                                    <input type="password" 
                                           class="form-control" 
                                           id="new_password" 
                                           name="new_password" 
                                           placeholder="Enter new password (min. 6 characters)"
                                           required 
                                           autofocus>
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength mt-2">
                                    <div class="password-strength-bar" id="strengthBar"></div>
                                </div>
                                <small class="text-muted" id="strengthText"></small>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-lock me-2"></i>Confirm Password
                                </label>
                                <div class="password-wrapper">
                                    <input type="password" 
                                           class="form-control" 
                                           id="confirm_password" 
                                           name="confirm_password" 
                                           placeholder="Re-enter your password"
                                           required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="far fa-eye"></i>
                                    </button>
                                </div>
                                <div class="invalid-feedback" id="passwordMatch"></div>
                            </div>
                            
                            <button type="submit" class="btn-submit" id="resetBtn">
                                <i class="fas fa-sync-alt"></i> Reset Password
                            </button>
                        </form>
                    <?php endif; ?>

                    <?php if ($step != 4): ?>
                    <div class="back-link">
                        <a href="/login">
                            <i class="fas fa-arrow-left me-2"></i>Back to Login
                        </a>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const toggleBtn = field.nextElementSibling.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                toggleBtn.classList.remove('fa-eye');
                toggleBtn.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                toggleBtn.classList.remove('fa-eye-slash');
                toggleBtn.classList.add('fa-eye');
            }
        }

        // Password strength checker
        const passwordInput = document.getElementById('new_password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Check length
                if (password.length >= 6) strength += 1;
                if (password.length >= 8) strength += 1;
                
                // Check for numbers
                if (/\d/.test(password)) strength += 1;
                
                // Check for special characters
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 1;
                
                // Check for uppercase
                if (/[A-Z]/.test(password)) strength += 1;
                
                // Update bar
                let percentage = (strength / 5) * 100;
                strengthBar.style.width = percentage + '%';
                
                if (strength <= 2) {
                    strengthBar.className = 'password-strength-bar strength-weak';
                    strengthText.textContent = 'Weak password';
                    strengthText.style.color = '#dc3545';
                } else if (strength <= 3) {
                    strengthBar.className = 'password-strength-bar strength-medium';
                    strengthText.textContent = 'Medium password';
                    strengthText.style.color = '#ffc107';
                } else {
                    strengthBar.className = 'password-strength-bar strength-strong';
                    strengthText.textContent = 'Strong password';
                    strengthText.style.color = '#28a745';
                }
            });
        }
        
        // Password match checker
        const confirmInput = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('passwordMatch');
        
        if (confirmInput && passwordInput) {
            confirmInput.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    this.classList.add('is-invalid');
                    passwordMatch.textContent = 'Passwords do not match';
                    passwordMatch.style.color = '#dc3545';
                } else {
                    this.classList.remove('is-invalid');
                    passwordMatch.textContent = '✓ Passwords match';
                    passwordMatch.style.color = '#28a745';
                }
            });
        }
        
        // Loading spinner on submit
        const forms = document.querySelectorAll('form');
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.classList.add('loading');
                    submitBtn.disabled = true;
                }
            });
        });
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        // Touch feedback
        document.querySelectorAll('.btn-submit, .password-toggle, .back-link a').forEach(el => {
            el.addEventListener('touchstart', function() {
                this.style.opacity = '0.8';
            });
            el.addEventListener('touchend', function() {
                this.style.opacity = '1';
            });
        });

        // Auto-focus on first input
        window.addEventListener('load', function() {
            const firstInput = document.querySelector('input');
            if (firstInput) firstInput.focus();
        });

        // Prevent zoom on double tap for iOS
        document.addEventListener('touchstart', function(e) {
            if (e.touches.length > 1) {
                e.preventDefault();
            }
        }, { passive: false });
    </script>
</body>
</html>