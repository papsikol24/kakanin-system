<?php
require_once 'includes/config.php';

$success = '';
$error = '';

// Predefined security questions
$questions = [
    "What is your mother's maiden name?",
    "What was the name of your first pet?",
    "What was your childhood nickname?",
    "What is your favorite book?",
    "What city were you born in?"
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $security_question = $_POST['security_question'] ?? '';
    $security_answer = trim($_POST['security_answer'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($username) || empty($password) || empty($security_question) || empty($security_answer)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (strlen($security_answer) < 2) {
        $error = "Security answer must be at least 2 characters.";
    } elseif (!preg_match('/^\d{10}$/', $phone)) {
        $error = "Phone number must be exactly 10 digits (e.g., 9356062163).";
    } elseif (!preg_match('/^9/', $phone)) {
        $error = "Phone number must start with 9 (e.g., 9356062163).";
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM tbl_customers WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username or email already taken.";
        } else {
            // Hash password and security answer
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $hashed_answer = password_hash(strtolower(trim($security_answer)), PASSWORD_DEFAULT);

            // Insert new customer
            $full_phone = '+63' . $phone;
            $stmt = $pdo->prepare("INSERT INTO tbl_customers (name, email, username, password, phone, security_question, security_answer) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $username, $hashed, $full_phone, $security_question, $hashed_answer])) {
                $success = "Registration successful! You can now log in.";
                // Clear form
                $name = $email = $username = $phone = '';
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Sign Up · Kakanin System</title>
     <!-- Favicon -->
    <link rel="icon" type="image/jpeg" href="/assets/images/owner.jpg">
    <link rel="shortcut icon" type="image/jpeg" href="/assets/images/owner.jpg">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('assets/images/background.jpg') no-repeat center center fixed;
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
            background: rgba(0, 0, 0, 0.6);
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
        
        .signup-container {
            position: relative;
            z-index: 2;
            width: 100%;
            max-width: 500px;
            padding: 20px;
            animation: popIn 0.8s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .signup-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 30px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            transition: all 0.3s ease;
            transform-origin: center;
        }
        
        .signup-card:hover {
            box-shadow: 0 30px 60px rgba(0, 128, 128, 0.3);
            transform: translateY(-5px);
        }
        
        .card-header {
            background: linear-gradient(135deg, #008080, #20b2aa);
            color: white;
            text-align: center;
            padding: 2rem 1.5rem 1rem;
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
        
        .card-header h3 {
            font-weight: 600;
            font-size: 2rem;
            margin-bottom: 0.25rem;
            position: relative;
            z-index: 1;
        }
        
        .brand-icon {
            font-size: 3.5rem;
            margin-bottom: 0.5rem;
            animation: bounce 2s infinite;
            position: relative;
            z-index: 1;
        }
        
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .card-body {
            padding: 2.5rem;
        }
        
        /* Form field animations */
        .form-group {
            margin-bottom: 1.5rem;
            animation: slideInUp 0.5s ease backwards;
        }
        
        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.15s; }
        .form-group:nth-child(3) { animation-delay: 0.2s; }
        .form-group:nth-child(4) { animation-delay: 0.25s; }
        .form-group:nth-child(5) { animation-delay: 0.3s; }
        .form-group:nth-child(6) { animation-delay: 0.35s; }
        .form-group:nth-child(7) { animation-delay: 0.4s; }
        
        @keyframes slideInUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .form-label {
            font-weight: 500;
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .form-label i {
            color: #008080;
            font-size: 1rem;
        }
        
        .form-control, .form-select {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            border: 2px solid #e9ecef;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: #008080;
            box-shadow: 0 0 0 0.25rem rgba(0, 128, 128, 0.25);
            transform: translateY(-2px);
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
        
        /* Phone input with +63 country code */
        .phone-input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .phone-prefix {
            position: absolute;
            left: 20px;
            color: white;
            font-weight: 600;
            z-index: 10;
            pointer-events: none;
            background: #008080;
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 0.9rem;
        }
        
        .phone-input-group .form-control {
            padding-left: 100px;
        }
        
        /* Password strength indicator */
        .password-strength {
            height: 5px;
            background: #e9ecef;
            border-radius: 5px;
            margin-top: 0.5rem;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }
        
        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #28a745; }
        
        /* Button styles */
        .btn-signup {
            background: linear-gradient(135deg, #008080, #20b2aa);
            border: none;
            border-radius: 50px;
            padding: 0.75rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 1rem;
        }
        
        .btn-signup::before {
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
        
        .btn-signup:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 128, 128, 0.4);
        }
        
        .btn-signup:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-signup:hover i {
            transform: translateX(5px);
        }
        
        .btn-signup i {
            transition: transform 0.3s ease;
        }
        
        /* Alert animations */
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
        
        .alert-success {
            background: linear-gradient(135deg, #28a745, #218838);
            color: white;
            text-align: center;
        }
        
        .alert-success i {
            margin-right: 0.5rem;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
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
        
        /* Login button inside success message */
        .btn-login-now {
            background: white;
            color: #008080;
            border: 2px solid #008080;
            border-radius: 50px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            margin-top: 1rem;
            transition: all 0.3s ease;
            width: 100%;
            max-width: 200px;
        }
        
        .btn-login-now:hover {
            background: #008080;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 128, 128, 0.3);
        }
        
        /* Login link at bottom */
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            animation: fadeIn 0.8s ease;
        }
        
        .login-link a {
            color: #008080;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .login-link a:hover {
            color: #20b2aa;
            transform: scale(1.05);
        }
        
        .login-link a i {
            transition: transform 0.3s ease;
        }
        
        .login-link a:hover i {
            transform: translateX(-5px);
        }
        
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
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Phone number hint */
        .phone-hint {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 0.25rem;
            padding-left: 0.5rem;
        }
        
        .phone-hint i {
            color: #008080;
            margin-right: 0.25rem;
        }
        
        .phone-hint strong {
            color: #008080;
            font-weight: 600;
        }
        
        /* Invalid feedback */
        .invalid-feedback {
            display: block;
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            padding-left: 1rem;
        }
        
        /* Responsive */
        @media (max-width: 576px) {
            .card-body {
                padding: 1.5rem;
            }
            
            .card-header h3 {
                font-size: 1.6rem;
            }
            
            .brand-icon {
                font-size: 2.5rem;
            }
            
            .phone-prefix {
                font-size: 0.8rem;
                padding: 3px 8px;
            }
            
            .phone-input-group .form-control {
                padding-left: 85px;
            }
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

    <div class="signup-container">
        <div class="card signup-card">
            <div class="card-header">
                <div class="brand-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <h3>Create Account</h3>
                <p class="text-white-50">Join Jen's Kakanin family!</p>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle fa-2x mb-3"></i>
                        <p class="mb-3"><?php echo $success; ?></p>
                        <a href="/login" class="btn-login-now">
                            <i class="fas fa-sign-in-alt me-2"></i>Log In
                        </a>
                    </div>
                <?php endif; ?>
                
                <form method="post" id="signupForm">
                    <!-- Name Field -->
                    <div class="form-group">
                        <label for="name" class="form-label">
                            <i class="fas fa-user"></i> Full Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="name" 
                               name="name" 
                               value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                               placeholder="Enter your full name"
                               required>
                    </div>
                    
                    <!-- Email Field -->
                    <div class="form-group">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i> Email Address <span class="text-danger">*</span>
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                               placeholder="your@email.com"
                               required>
                    </div>
                    
                    <!-- Username Field -->
                    <div class="form-group">
                        <label for="username" class="form-label">
                            <i class="fas fa-at"></i> Username <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                               placeholder="Choose a username"
                               required>
                    </div>
                    
                    <!-- Password Field with Eye Icon -->
                    <div class="form-group">
                        <label for="password" class="form-label">
                            <i class="fas fa-lock"></i> Password <span class="text-danger">*</span>
                        </label>
                        <div class="password-wrapper">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Minimum 6 characters"
                                   required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password', this)">
                                <i class="far fa-eye"></i>
                            </button>
                        </div>
                        <div class="password-strength mt-2">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <small class="text-muted" id="strengthText"></small>
                    </div>
                    
                    <!-- Phone Field with +63 Country Code -->
                    <div class="form-group">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone"></i> Phone Number <span class="text-danger">*</span>
                        </label>
                        <div class="phone-input-group">
                            <span class="phone-prefix">+63</span>
                            <input type="tel" 
                                   class="form-control" 
                                   id="phone" 
                                   name="phone" 
                                   value="<?php echo htmlspecialchars($phone ?? ''); ?>" 
                                   placeholder="9*********"
                                   pattern="[0-9]{10}"
                                   maxlength="10"
                                   title="Please enter exactly 10 digits starting with 9"
                                   required>
                        </div>
                        <div class="phone-hint">
                            <i class="fas fa-info-circle"></i> Enter <strong>10 digits</strong> (e.g., 9356062163). Must start with 9.
                        </div>
                        <div class="invalid-feedback" id="phoneError"></div>
                    </div>
                    
                    <!-- Security Question -->
                    <div class="form-group">
                        <label for="security_question" class="form-label">
                            <i class="fas fa-question-circle"></i> Security Question <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" id="security_question" name="security_question" required>
                            <option value="">Select a question...</option>
                            <?php foreach ($questions as $q): ?>
                                <option value="<?php echo htmlspecialchars($q); ?>" <?php echo (isset($_POST['security_question']) && $_POST['security_question'] == $q) ? 'selected' : ''; ?>>
                                    <?php echo $q; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Security Answer -->
                    <div class="form-group">
                        <label for="security_answer" class="form-label">
                            <i class="fas fa-pencil-alt"></i> Security Answer <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="security_answer" 
                               name="security_answer" 
                               value="<?php echo htmlspecialchars($security_answer ?? ''); ?>" 
                               placeholder="Your answer (case insensitive)"
                               required>
                    </div>
                    
                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-signup" id="submitBtn">
                        <span class="spinner" style="display: none;"></span>
                        <span class="btn-text"><i class="fas fa-user-plus"></i> Create Account</span>
                    </button>
                </form>
                
                <div class="login-link">
                    <a href="/login">
                        <i class="fas fa-arrow-left me-2"></i>Already have an account? Log in
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword(fieldId, button) {
            const field = document.getElementById(fieldId);
            const icon = button.querySelector('i');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password strength checker
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                if (password.length >= 6) strength += 1;
                if (password.length >= 8) strength += 1;
                if (/\d/.test(password)) strength += 1;
                if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength += 1;
                if (/[A-Z]/.test(password)) strength += 1;
                
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
        
        // Phone number validation
        const phoneInput = document.getElementById('phone');
        const phoneError = document.getElementById('phoneError');
        
        if (phoneInput) {
            phoneInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '');
                
                if (this.value.length > 10) {
                    this.value = this.value.slice(0, 10);
                }
                
                if (this.value.length > 0 && this.value[0] !== '9') {
                    this.classList.add('is-invalid');
                    phoneError.textContent = 'Phone number must start with 9';
                } else if (this.value.length === 10) {
                    this.classList.remove('is-invalid');
                    phoneError.textContent = '';
                } else if (this.value.length > 0) {
                    this.classList.add('is-invalid');
                    phoneError.textContent = 'Please enter exactly 10 digits';
                } else {
                    this.classList.remove('is-invalid');
                }
            });
            
            phoneInput.addEventListener('blur', function() {
                if (this.value.length > 0) {
                    if (this.value[0] !== '9') {
                        this.classList.add('is-invalid');
                        phoneError.textContent = 'Phone number must start with 9';
                    } else if (this.value.length !== 10) {
                        this.classList.add('is-invalid');
                        phoneError.textContent = 'Phone number must be exactly 10 digits';
                    }
                }
            });
        }
        
        // Form validation before submit
        document.getElementById('signupForm')?.addEventListener('submit', function(e) {
            if (phoneInput) {
                if (phoneInput.value.length !== 10) {
                    e.preventDefault();
                    phoneInput.classList.add('is-invalid');
                    phoneError.textContent = 'Phone number must be exactly 10 digits';
                } else if (phoneInput.value[0] !== '9') {
                    e.preventDefault();
                    phoneInput.classList.add('is-invalid');
                    phoneError.textContent = 'Phone number must start with 9';
                }
            }
        });
        
        // Loading spinner on submit
        const form = document.getElementById('signupForm');
        if (form) {
            form.addEventListener('submit', function(e) {
                const submitBtn = document.getElementById('submitBtn');
                const spinner = submitBtn.querySelector('.spinner');
                const btnText = submitBtn.querySelector('.btn-text');
                
                if (spinner) {
                    spinner.style.display = 'inline-block';
                }
                if (btnText) {
                    btnText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
                }
                submitBtn.disabled = true;
            });
        }
        
        // Auto-hide alerts after 5 seconds
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        // Add animation on input focus
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('focus', function() {
                this.style.transform = 'translateY(-2px)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            input.addEventListener('blur', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>