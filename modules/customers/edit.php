<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

$id = (int)($_GET['id'] ?? 0);
$customer = $pdo->prepare("SELECT * FROM tbl_customers WHERE id = ?");
$customer->execute([$id]);
$customer = $customer->fetch();

if (!$customer) {
    $_SESSION['error'] = "Customer not found.";
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $phone = trim($_POST['phone']);
    $security_question = $_POST['security_question'] ?? '';
    $security_answer = trim($_POST['security_answer'] ?? '');
    $status = isset($_POST['status']) ? 1 : 0;

    // Validation
    $errors = [];

    if (empty($name)) {
        $errors[] = "Full name is required.";
    }

    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    }

    if (!empty($password) && strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters if you want to change it.";
    }

    if (!empty($phone)) {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's a valid Philippine mobile number
        if (strlen($phone) == 11 && substr($phone, 0, 2) == '09') {
            // Valid, format it
            $phone = '+63' . substr($phone, 1);
        } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '639') {
            $phone = '+' . $phone;
        } elseif (strlen($phone) == 13 && substr($phone, 0, 4) == '+639') {
            // Already formatted correctly
        } else {
            $errors[] = "Invalid phone number format. Please use a valid Philippine mobile number (e.g., 09171234567).";
        }
    }

    // Check for duplicate username/email (excluding current customer)
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM tbl_customers WHERE (username = ? OR email = ?) AND id != ?");
        $check->execute([$username, $email, $id]);
        $duplicate = $check->fetch();

        if ($duplicate) {
            // Check which field is duplicate
            $check_username = $pdo->prepare("SELECT id FROM tbl_customers WHERE username = ? AND id != ?");
            $check_username->execute([$username, $id]);
            if ($check_username->fetch()) {
                $errors[] = "Username '$username' is already taken by another customer.";
            }

            $check_email = $pdo->prepare("SELECT id FROM tbl_customers WHERE email = ? AND id != ?");
            $check_email->execute([$email, $id]);
            if ($check_email->fetch()) {
                $errors[] = "Email address '$email' is already registered to another customer.";
            }
        }
    }

    // If no errors, update the customer
    if (empty($errors)) {
        try {
            if (!empty($password)) {
                // Update with new password
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                
                if (!empty($security_answer)) {
                    // Update with new security answer
                    $hashed_answer = password_hash(strtolower(trim($security_answer)), PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE tbl_customers SET name=?, email=?, username=?, password=?, phone=?, security_question=?, security_answer=?, status=? WHERE id=?");
                    $result = $stmt->execute([$name, $email, $username, $hashed, $phone, $security_question, $hashed_answer, $status, $id]);
                } else {
                    // Keep old security answer
                    $stmt = $pdo->prepare("UPDATE tbl_customers SET name=?, email=?, username=?, password=?, phone=?, status=? WHERE id=?");
                    $result = $stmt->execute([$name, $email, $username, $hashed, $phone, $status, $id]);
                }
            } else {
                // Keep old password
                if (!empty($security_answer)) {
                    // Update with new security answer
                    $hashed_answer = password_hash(strtolower(trim($security_answer)), PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE tbl_customers SET name=?, email=?, username=?, phone=?, security_question=?, security_answer=?, status=? WHERE id=?");
                    $result = $stmt->execute([$name, $email, $username, $phone, $security_question, $hashed_answer, $status, $id]);
                } else {
                    // Keep old security answer
                    $stmt = $pdo->prepare("UPDATE tbl_customers SET name=?, email=?, username=?, phone=?, status=? WHERE id=?");
                    $result = $stmt->execute([$name, $email, $username, $phone, $status, $id]);
                }
            }

            if ($result) {
                $_SESSION['success'] = "Customer '{$name}' has been updated successfully.";
                header('Location: index.php');
                exit;
            } else {
                $errors[] = "Failed to update customer. Please try again.";
            }
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $errors[] = "Username or email already exists. Please choose different values.";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }

    if (!empty($errors)) {
        $error = implode("<br>", $errors);
    }
}

// Predefined security questions
$questions = [
    "What is your mother's maiden name?",
    "What was the name of your first pet?",
    "What was your childhood nickname?",
    "What is your favorite book?",
    "What city were you born in?",
    "What is your favorite food?",
    "What was the name of your first school?",
    "Who is your childhood hero?"
];

include '../../includes/header.php';
?>

<style>
    .form-container {
        max-width: 800px;
        margin: 0 auto;
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

    .form-card {
        background: white;
        border-radius: 20px;
        padding: 2rem;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        border: 1px solid rgba(0,0,0,0.05);
    }

    .form-header {
        margin-bottom: 2rem;
        text-align: center;
        position: relative;
    }

    .form-header h4 {
        color: #2c3e50;
        font-weight: 700;
        font-size: 2rem;
        margin-bottom: 0.5rem;
    }

    .form-header h4 i {
        color: #17a2b8;
        margin-right: 0.5rem;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0%, 100% { transform: scale(1); }
        50% { transform: scale(1.1); }
    }

    .form-header p {
        color: #666;
        font-size: 1rem;
    }

    .form-header::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 80px;
        height: 3px;
        background: linear-gradient(90deg, #17a2b8, #138496);
        border-radius: 2px;
    }

    .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.3rem;
    }

    .form-label i {
        color: #17a2b8;
        font-size: 1rem;
    }

    .form-label .required {
        color: #dc3545;
        font-size: 0.9rem;
        margin-left: 0.2rem;
    }

    .form-control, .form-select {
        border-radius: 10px;
        padding: 0.7rem 1rem;
        border: 2px solid #e0e0e0;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: #17a2b8;
        box-shadow: 0 0 0 0.2rem rgba(23,162,184,0.25);
        outline: none;
    }

    .input-group {
        position: relative;
    }

    .input-group .btn {
        border-radius: 0 10px 10px 0;
        border: 2px solid #e0e0e0;
        border-left: none;
        background: white;
        color: #666;
    }

    .input-group .btn:hover {
        background: #f8f9fa;
        color: #17a2b8;
    }

    .form-check-input {
        width: 1.2rem;
        height: 1.2rem;
        margin-right: 0.5rem;
        cursor: pointer;
    }

    .form-check-input:checked {
        background-color: #28a745;
        border-color: #28a745;
    }

    .form-check-label {
        color: #2c3e50;
        cursor: pointer;
        display: flex;
        align-items: center;
    }

    .btn-submit {
        background: linear-gradient(135deg, #17a2b8, #138496);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.8rem 2.5rem;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 5px 15px rgba(23,162,184,0.3);
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(23,162,184,0.4);
    }

    .btn-cancel {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.8rem 2.5rem;
        font-weight: 600;
        font-size: 1rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-left: 1rem;
    }

    .btn-cancel:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(108,117,125,0.4);
        color: white;
    }

    .alert {
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        animation: slideInDown 0.3s ease;
        border-left: 4px solid;
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border-left-color: #dc3545;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left-color: #28a745;
    }

    .info-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-left: 4px solid #17a2b8;
    }

    .info-card i {
        color: #17a2b8;
        margin-right: 0.5rem;
    }

    .info-card small {
        color: #666;
        display: block;
        margin-top: 0.5rem;
    }

    .password-strength {
        margin-top: 0.5rem;
        height: 5px;
        background: #e0e0e0;
        border-radius: 5px;
        overflow: hidden;
    }

    .password-strength-bar {
        height: 100%;
        width: 0;
        transition: width 0.3s ease;
    }

    .strength-weak {
        background: #dc3545;
    }

    .strength-medium {
        background: #ffc107;
    }

    .strength-strong {
        background: #28a745;
    }

    .password-hint {
        font-size: 0.8rem;
        color: #666;
        margin-top: 0.3rem;
    }

    .password-hint i {
        color: #17a2b8;
        margin-right: 0.3rem;
    }

    .phone-preview {
        background: #e8f4f4;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        display: inline-block;
        margin-top: 0.5rem;
        font-size: 0.9rem;
        color: #17a2b8;
    }

    .phone-preview i {
        margin-right: 0.3rem;
    }

    .row {
        margin-bottom: 1rem;
    }

    @media (max-width: 768px) {
        .form-card {
            padding: 1.5rem;
        }

        .btn-submit, .btn-cancel {
            width: 100%;
            margin: 0.5rem 0;
            justify-content: center;
        }
    }
</style>

<div class="container-fluid">
    <div class="form-container">
        <div class="form-card">
            <div class="form-header">
                <h4>
                    <i class="fas fa-user-edit"></i>
                    Edit Customer
                </h4>
                <p>Update information for <?php echo htmlspecialchars($customer['name']); ?></p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post" id="editCustomerForm" onsubmit="return validateForm()">
                <!-- Customer Information Section -->
                <div class="info-card">
                    <i class="fas fa-info-circle"></i>
                    <strong>Customer Information</strong>
                    <small>Update the customer's basic information below</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">
                            <i class="fas fa-user"></i>
                            Full Name <span class="required">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="name" 
                               name="name" 
                               value="<?php echo htmlspecialchars($_POST['name'] ?? $customer['name']); ?>" 
                               required
                               placeholder="Enter full name">
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">
                            <i class="fas fa-envelope"></i>
                            Email Address <span class="required">*</span>
                        </label>
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($_POST['email'] ?? $customer['email']); ?>" 
                               required
                               placeholder="customer@example.com">
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">
                            <i class="fas fa-at"></i>
                            Username <span class="required">*</span>
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="username" 
                               name="username" 
                               value="<?php echo htmlspecialchars($_POST['username'] ?? $customer['username']); ?>" 
                               required
                               pattern="[a-zA-Z0-9_]+"
                               title="Username can only contain letters, numbers, and underscores"
                               placeholder="Choose a username">
                        <small class="text-muted">Only letters, numbers, and underscores allowed.</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">
                            <i class="fas fa-phone"></i>
                            Phone Number
                        </label>
                        <input type="tel" 
                               class="form-control" 
                               id="phone" 
                               name="phone" 
                               value="<?php echo htmlspecialchars($_POST['phone'] ?? $customer['phone']); ?>" 
                               placeholder="09171234567">
                        <small class="text-muted">Format: 09171234567 (11 digits starting with 09)</small>
                        <div id="phonePreview" class="phone-preview" style="display: none;">
                            <i class="fas fa-check-circle text-success"></i>
                            Formatted: <span id="formattedPhone"></span>
                        </div>
                    </div>
                </div>

                <!-- Password Section -->
                <div class="info-card mt-4">
                    <i class="fas fa-lock"></i>
                    <strong>Password Change</strong>
                    <small>Leave password blank to keep current password</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">
                            <i class="fas fa-key"></i>
                            New Password
                        </label>
                        <div class="input-group">
                            <input type="password" 
                                   class="form-control" 
                                   id="password" 
                                   name="password" 
                                   placeholder="Leave blank to keep current"
                                   minlength="6">
                            <button class="btn" type="button" onclick="togglePassword('password')">
                                <i class="fas fa-eye" id="togglePasswordIcon"></i>
                            </button>
                        </div>
                        <div class="password-strength mt-2">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="password-hint">
                            <i class="fas fa-info-circle"></i>
                            Minimum 6 characters. Leave empty to keep current password.
                        </div>
                    </div>
                </div>

                <!-- Security Section -->
                <div class="info-card mt-4">
                    <i class="fas fa-shield-alt"></i>
                    <strong>Security Settings</strong>
                    <small>Update security question and answer (for password recovery)</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="security_question" class="form-label">
                            <i class="fas fa-question-circle"></i>
                            Security Question
                        </label>
                        <select class="form-select" id="security_question" name="security_question">
                            <option value="">None (keep current)</option>
                            <?php foreach ($questions as $q): ?>
                                <option value="<?php echo htmlspecialchars($q); ?>" 
                                    <?php echo (isset($_POST['security_question']) && $_POST['security_question'] == $q) || 
                                            (!isset($_POST['security_question']) && $customer['security_question'] == $q) ? 'selected' : ''; ?>>
                                    <?php echo $q; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="security_answer" class="form-label">
                            <i class="fas fa-pencil-alt"></i>
                            Security Answer
                        </label>
                        <input type="text" 
                               class="form-control" 
                               id="security_answer" 
                               name="security_answer" 
                               value="<?php echo htmlspecialchars($_POST['security_answer'] ?? ''); ?>" 
                               placeholder="New answer (leave blank to keep current)">
                        <small class="text-muted">Leave blank to keep current answer. Answer is case-insensitive.</small>
                    </div>
                </div>

                <!-- Status Section -->
                <div class="info-card mt-4">
                    <i class="fas fa-toggle-on"></i>
                    <strong>Account Status</strong>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="form-check">
                            <input type="checkbox" 
                                   class="form-check-input" 
                                   id="status" 
                                   name="status" 
                                   <?php echo (isset($_POST['status']) && $_POST['status']) || 
                                           (!isset($_POST['status']) && $customer['status']) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="status">
                                <i class="fas fa-check-circle text-success"></i>
                                Active Account
                            </label>
                        </div>
                        <small class="text-muted">Inactive customers cannot log in to their account.</small>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Update Customer
                    </button>
                    <a href="index.php" class="btn-cancel">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById('togglePasswordIcon');
    
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
const password = document.getElementById('password');
const strengthBar = document.getElementById('strengthBar');

if (password) {
    password.addEventListener('input', function() {
        const val = this.value;
        
        if (val.length === 0) {
            strengthBar.style.width = '0';
            return;
        }
        
        let strength = 0;
        
        // Check length
        if (val.length >= 6) strength++;
        if (val.length >= 8) strength++;
        
        // Check for numbers
        if (/\d/.test(val)) strength++;
        
        // Check for letters
        if (/[a-zA-Z]/.test(val)) strength++;
        
        // Check for special characters
        if (/[!@#$%^&*(),.?":{}|<>]/.test(val)) strength++;
        
        // Update strength bar
        let percentage = (strength / 5) * 100;
        strengthBar.style.width = percentage + '%';
        
        if (strength <= 2) {
            strengthBar.className = 'password-strength-bar strength-weak';
        } else if (strength <= 3) {
            strengthBar.className = 'password-strength-bar strength-medium';
        } else {
            strengthBar.className = 'password-strength-bar strength-strong';
        }
    });
}

// Phone number formatting
const phoneInput = document.getElementById('phone');
const phonePreview = document.getElementById('phonePreview');
const formattedPhoneSpan = document.getElementById('formattedPhone');

if (phoneInput) {
    phoneInput.addEventListener('input', function() {
        let phone = this.value.replace(/\D/g, '');
        
        if (phone.length === 11 && phone.startsWith('09')) {
            let formatted = '+63 ' + phone.substring(1, 4) + ' ' + phone.substring(4, 7) + ' ' + phone.substring(7);
            formattedPhoneSpan.textContent = formatted;
            phonePreview.style.display = 'block';
        } else if (phone.length === 0) {
            phonePreview.style.display = 'none';
        } else {
            phonePreview.style.display = 'none';
        }
    });
}

// Form validation
function validateForm() {
    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const username = document.getElementById('username').value.trim();
    const phone = document.getElementById('phone').value.trim();
    const passwordVal = document.getElementById('password').value;
    
    if (!name) {
        alert('Please enter the customer\'s full name.');
        return false;
    }
    
    if (!email) {
        alert('Please enter an email address.');
        return false;
    }
    
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('Please enter a valid email address.');
        return false;
    }
    
    if (!username) {
        alert('Please enter a username.');
        return false;
    }
    
    const usernameRegex = /^[a-zA-Z0-9_]+$/;
    if (!usernameRegex.test(username)) {
        alert('Username can only contain letters, numbers, and underscores.');
        return false;
    }
    
    if (passwordVal.length > 0 && passwordVal.length < 6) {
        alert('Password must be at least 6 characters long if you want to change it.');
        return false;
    }
    
    if (phone) {
        const phoneDigits = phone.replace(/\D/g, '');
        if (phoneDigits.length !== 11 || !phoneDigits.startsWith('09')) {
            alert('Please enter a valid Philippine mobile number (11 digits starting with 09).');
            return false;
        }
    }
    
    // Disable submit button
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
    
    return true;
}

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