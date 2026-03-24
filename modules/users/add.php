<?php
require_once '../../includes/config.php';
requireLogin();
requireRole('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'];
    $status = isset($_POST['status']) ? 1 : 0;

    // Validation
    $errors = [];

    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if username already exists
    if (empty($errors)) {
        $check = $pdo->prepare("SELECT id FROM tbl_users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            $errors[] = "Username '$username' is already taken. Please choose a different username.";
        }
    }

    // If no errors, insert the user
    if (empty($errors)) {
        try {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("INSERT INTO tbl_users (username, password, role, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $hashed_password, $role, $status]);
            
            $_SESSION['success'] = "User '$username' created successfully with role: $role.";
            header('Location: index.php');
            exit;
            
        } catch (PDOException $e) {
            if ($e->errorInfo[1] == 1062) {
                $error = "Username '$username' already exists. Please choose a different username.";
            } else {
                $error = "Database error: " . $e->getMessage();
            }
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

include '../../includes/header.php';
?>

<style>
    .form-container {
        max-width: 600px;
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
    }

    .form-header h4 {
        color: #2c3e50;
        font-weight: 700;
        font-size: 1.8rem;
        margin-bottom: 0.5rem;
    }

    .form-header h4 i {
        color: #007bff;
        margin-right: 0.5rem;
    }

    .form-header p {
        color: #666;
        font-size: 0.95rem;
    }

    .form-label {
        font-weight: 600;
        color: #2c3e50;
        margin-bottom: 0.5rem;
    }

    .form-label i {
        color: #007bff;
        margin-right: 0.3rem;
    }

    .form-control, .form-select {
        border-radius: 10px;
        padding: 0.7rem 1rem;
        border: 2px solid #e0e0e0;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .form-control:focus, .form-select:focus {
        border-color: #007bff;
        box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
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
        color: #007bff;
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
    }

    .btn-submit {
        background: linear-gradient(135deg, #28a745, #218838);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.8rem 2rem;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        box-shadow: 0 5px 15px rgba(40,167,69,0.3);
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(40,167,69,0.4);
    }

    .btn-cancel {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 0.8rem 2rem;
        font-weight: 600;
        font-size: 1rem;
        text-decoration: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-left: 0.5rem;
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
        border-left: 4px solid #dc3545;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
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
        color: #007bff;
        margin-right: 0.3rem;
    }

    .requirements-list {
        list-style: none;
        padding: 0;
        margin-top: 0.5rem;
        font-size: 0.85rem;
    }

    .requirements-list li {
        margin-bottom: 0.2rem;
        color: #666;
    }

    .requirements-list li i {
        margin-right: 0.3rem;
        font-size: 0.8rem;
    }

    .requirements-list li.valid {
        color: #28a745;
    }

    .requirements-list li.invalid {
        color: #dc3545;
    }

    .role-badge {
        display: inline-block;
        padding: 0.3rem 0.8rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        margin-left: 0.5rem;
    }

    .role-badge.admin {
        background: #007bff;
        color: white;
    }

    .role-badge.manager {
        background: #ffc107;
        color: #333;
    }

    .role-badge.cashier {
        background: #17a2b8;
        color: white;
    }
</style>

<div class="container-fluid">
    <div class="form-container">
        <div class="form-card">
            <div class="form-header">
                <h4>
                    <i class="fas fa-user-plus"></i>
                    Add New User
                </h4>
                <p>Create a new staff account with specific role permissions</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php endif; ?>

            <form method="post" id="addUserForm" onsubmit="return validateForm()">
                <!-- Username Field -->
                <div class="mb-4">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i>
                        Username <span class="text-danger">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="username" 
                           name="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           placeholder="Enter username"
                           required
                           pattern="[a-zA-Z0-9_]+"
                           title="Username can only contain letters, numbers, and underscores"
                           autocomplete="off">
                    <div class="password-hint">
                        <i class="fas fa-info-circle"></i>
                        Only letters, numbers, and underscores allowed. Must be unique.
                    </div>
                </div>

                <!-- Password Field -->
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Password <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Enter password"
                               required
                               minlength="6">
                        <button class="btn" type="button" onclick="togglePassword('password')">
                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                    <div class="password-strength mt-2">
                        <div class="password-strength-bar" id="strengthBar"></div>
                    </div>
                    <ul class="requirements-list" id="passwordRequirements">
                        <li id="lengthReq" class="invalid">
                            <i class="fas fa-times-circle"></i> At least 6 characters
                        </li>
                        <li id="numberReq" class="invalid">
                            <i class="fas fa-times-circle"></i> Contains at least one number
                        </li>
                        <li id="letterReq" class="invalid">
                            <i class="fas fa-times-circle"></i> Contains at least one letter
                        </li>
                    </ul>
                </div>

                <!-- Confirm Password Field -->
                <div class="mb-4">
                    <label for="confirm_password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Confirm Password <span class="text-danger">*</span>
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="confirm_password" 
                               name="confirm_password" 
                               placeholder="Confirm password"
                               required>
                        <button class="btn" type="button" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye" id="toggleConfirmIcon"></i>
                        </button>
                    </div>
                    <div class="password-hint" id="matchMessage"></div>
                </div>

                <!-- Role Field -->
                <div class="mb-4">
                    <label for="role" class="form-label">
                        <i class="fas fa-user-tag"></i>
                        Role <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="role" name="role" required onchange="updateRoleBadge()">
                        <option value="">Select role...</option>
                        <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                        <option value="manager" <?php echo (isset($_POST['role']) && $_POST['role'] == 'manager') ? 'selected' : ''; ?>>Manager</option>
                        <option value="cashier" <?php echo (isset($_POST['role']) && $_POST['role'] == 'cashier') ? 'selected' : ''; ?>>Cashier</option>
                    </select>
                    <div id="roleBadge" class="mt-2"></div>
                </div>

                <!-- Status Field -->
                <div class="mb-4">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="status" name="status" checked>
                        <label class="form-check-label" for="status">
                            <i class="fas fa-check-circle text-success"></i>
                            Active Account
                        </label>
                    </div>
                    <div class="password-hint">
                        <i class="fas fa-info-circle"></i>
                        Inactive users cannot log in to the system.
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="d-flex justify-content-center mt-4">
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Create User
                    </button>
                    <a href="index.php" class="btn-cancel">
                        <i class="fas fa-times"></i>
                        Cancel
                    </a>
                </div>
            </form>

            <!-- Role Permissions Info -->
            <div class="mt-4 p-3 bg-light rounded">
                <h6 class="mb-2"><i class="fas fa-info-circle text-info"></i> Role Permissions:</h6>
                <div class="row small">
                    <div class="col-md-4">
                        <span class="role-badge admin">Admin</span>
                        <ul class="mt-2 ps-3">
                            <li>Full system access</li>
                            <li>Manage users</li>
                            <li>All reports</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <span class="role-badge manager">Manager</span>
                        <ul class="mt-2 ps-3">
                            <li>Inventory management</li>
                            <li>View reports</li>
                            <li>Manage products</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <span class="role-badge cashier">Cashier</span>
                        <ul class="mt-2 ps-3">
                            <li>Process orders</li>
                            <li>View customers</li>
                            <li>Print receipts</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = document.getElementById(fieldId === 'password' ? 'togglePasswordIcon' : 'toggleConfirmIcon');
    
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
const lengthReq = document.getElementById('lengthReq');
const numberReq = document.getElementById('numberReq');
const letterReq = document.getElementById('letterReq');
const matchMessage = document.getElementById('matchMessage');
const confirmPassword = document.getElementById('confirm_password');

password.addEventListener('input', function() {
    const val = this.value;
    let strength = 0;
    
    // Check length
    if (val.length >= 6) {
        strength++;
        lengthReq.className = 'valid';
        lengthReq.innerHTML = '<i class="fas fa-check-circle"></i> At least 6 characters';
    } else {
        lengthReq.className = 'invalid';
        lengthReq.innerHTML = '<i class="fas fa-times-circle"></i> At least 6 characters';
    }
    
    // Check for number
    if (/\d/.test(val)) {
        strength++;
        numberReq.className = 'valid';
        numberReq.innerHTML = '<i class="fas fa-check-circle"></i> Contains at least one number';
    } else {
        numberReq.className = 'invalid';
        numberReq.innerHTML = '<i class="fas fa-times-circle"></i> Contains at least one number';
    }
    
    // Check for letter
    if (/[a-zA-Z]/.test(val)) {
        strength++;
        letterReq.className = 'valid';
        letterReq.innerHTML = '<i class="fas fa-check-circle"></i> Contains at least one letter';
    } else {
        letterReq.className = 'invalid';
        letterReq.innerHTML = '<i class="fas fa-times-circle"></i> Contains at least one letter';
    }
    
    // Update strength bar
    let percentage = (strength / 3) * 100;
    strengthBar.style.width = percentage + '%';
    
    if (strength <= 1) {
        strengthBar.className = 'password-strength-bar strength-weak';
    } else if (strength <= 2) {
        strengthBar.className = 'password-strength-bar strength-medium';
    } else {
        strengthBar.className = 'password-strength-bar strength-strong';
    }
    
    // Check password match
    checkPasswordMatch();
});

// Check password match
function checkPasswordMatch() {
    if (confirmPassword.value) {
        if (password.value === confirmPassword.value) {
            matchMessage.innerHTML = '<i class="fas fa-check-circle text-success"></i> Passwords match';
            matchMessage.style.color = '#28a745';
        } else {
            matchMessage.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Passwords do not match';
            matchMessage.style.color = '#dc3545';
        }
    } else {
        matchMessage.innerHTML = '';
    }
}

confirmPassword.addEventListener('input', checkPasswordMatch);

// Update role badge
function updateRoleBadge() {
    const role = document.getElementById('role').value;
    const badgeDiv = document.getElementById('roleBadge');
    
    if (role) {
        badgeDiv.innerHTML = `<span class="role-badge ${role}">${role.charAt(0).toUpperCase() + role.slice(1)}</span>`;
    } else {
        badgeDiv.innerHTML = '';
    }
}

// Form validation
function validateForm() {
    const username = document.getElementById('username').value;
    const usernameRegex = /^[a-zA-Z0-9_]+$/;
    
    if (!usernameRegex.test(username)) {
        alert('Username can only contain letters, numbers, and underscores!');
        return false;
    }
    
    if (password.value.length < 6) {
        alert('Password must be at least 6 characters long!');
        return false;
    }
    
    if (password.value !== confirmPassword.value) {
        alert('Passwords do not match!');
        return false;
    }
    
    if (!document.getElementById('role').value) {
        alert('Please select a role!');
        return false;
    }
    
    // Disable submit button to prevent double submission
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    
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