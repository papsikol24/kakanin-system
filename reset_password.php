<?php
require_once 'includes/config.php';

// Only allow access if user is logged in as admin
if (!isset($_SESSION['user_id'])) {
    header('Location: staff-login.php');
    exit;
}

// Check if user is admin
$stmt = $pdo->prepare("SELECT role FROM tbl_users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || $user['role'] !== 'admin') {
    $_SESSION['error'] = "Access denied. Admin only.";
    header('Location: index.php');
    exit;
}

$message = '';
$error = '';

// Handle password reset
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if (empty($username)) {
        $error = "Please enter a username.";
    } elseif (empty($new_password)) {
        $error = "Please enter a new password.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id, role FROM tbl_users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE tbl_users SET password = ? WHERE id = ?");
            
            if ($update->execute([$hashed_password, $user['id']])) {
                $message = "✅ Password for user '{$username}' has been reset successfully!";
                
                // Log the action
                error_log("Admin {$_SESSION['user_id']} reset password for user {$username}");
            } else {
                $error = "❌ Failed to update password.";
            }
        } else {
            $error = "❌ User '{$username}' not found.";
        }
    }
}

include 'includes/header.php';
?>

<style>
    /* ===== RESET PASSWORD PAGE STYLES ===== */
    .reset-container {
        max-width: 500px;
        margin: 40px auto;
        padding: 0 15px;
    }

    .reset-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .card-header {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        padding: 20px;
        text-align: center;
    }

    .card-header h4 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
    }

    .card-header i {
        margin-right: 10px;
    }

    .card-body {
        padding: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-label {
        font-weight: 500;
        color: #333;
        margin-bottom: 8px;
        display: block;
    }

    .form-label i {
        color: #dc3545;
        margin-right: 8px;
        width: 20px;
    }

    .form-control {
        width: 100%;
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-size: 1rem;
        transition: all 0.3s;
    }

    .form-control:focus {
        border-color: #dc3545;
        outline: none;
        box-shadow: 0 0 0 3px rgba(220,53,69,0.1);
    }

    .password-input-group {
        position: relative;
    }

    .password-toggle {
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        color: #666;
        cursor: pointer;
        padding: 5px;
    }

    .password-toggle:hover {
        color: #dc3545;
    }

    .btn-reset {
        background: linear-gradient(135deg, #dc3545, #c82333);
        color: white;
        border: none;
        border-radius: 50px;
        padding: 14px 30px;
        font-size: 1rem;
        font-weight: 600;
        width: 100%;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-reset:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(220,53,69,0.3);
    }

    .btn-reset:active {
        transform: translateY(0);
    }

    .btn-reset:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    .alert {
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert i {
        font-size: 1.2rem;
    }

    .user-info {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
        border-left: 4px solid #dc3545;
    }

    .user-info p {
        margin: 5px 0;
        color: #666;
    }

    .user-info strong {
        color: #dc3545;
    }

    .back-link {
        text-align: center;
        margin-top: 20px;
    }

    .back-link a {
        color: #666;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: color 0.3s;
    }

    .back-link a:hover {
        color: #dc3545;
    }

    .password-requirements {
        font-size: 0.85rem;
        color: #666;
        margin-top: 5px;
        padding-left: 20px;
    }

    .password-requirements li {
        margin-bottom: 3px;
    }

    .password-requirements i {
        margin-right: 5px;
        font-size: 0.8rem;
    }

    .fa-check-circle {
        color: #28a745;
    }

    .fa-times-circle {
        color: #dc3545;
    }

    /* Loading state */
    .btn-reset.loading {
        position: relative;
        color: transparent !important;
    }

    .btn-reset.loading::after {
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

    /* Responsive */
    @media (max-width: 576px) {
        .card-body {
            padding: 20px;
        }
        
        .btn-reset {
            padding: 12px 20px;
        }
    }
</style>

<div class="reset-container">
    <div class="reset-card">
        <div class="card-header">
            <h4><i class="fas fa-key"></i> Reset User Password</h4>
        </div>
        <div class="card-body">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="user-info">
                <p><i class="fas fa-shield-alt"></i> You are resetting a password as: <strong><?php echo htmlspecialchars($_SESSION['user_id']); ?></strong></p>
                <p><small>This action will be logged for security purposes.</small></p>
            </div>

            <form method="post" id="resetForm">
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" 
                           name="username" 
                           class="form-control" 
                           placeholder="Enter username to reset"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                           required
                           autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> New Password
                    </label>
                    <div class="password-input-group">
                        <input type="password" 
                               name="new_password" 
                               id="new_password"
                               class="form-control" 
                               placeholder="Enter new password (min. 6 characters)"
                               required
                               minlength="6">
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <ul class="password-requirements" id="passwordRequirements">
                        <li id="lengthReq"><i class="far fa-circle"></i> At least 6 characters</li>
                    </ul>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Confirm Password
                    </label>
                    <div class="password-input-group">
                        <input type="password" 
                               name="confirm_password" 
                               id="confirm_password"
                               class="form-control" 
                               placeholder="Confirm new password"
                               required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="far fa-eye"></i>
                        </button>
                    </div>
                    <small class="text-muted" id="passwordMatch"></small>
                </div>

                <button type="submit" class="btn-reset" id="resetBtn">
                    <i class="fas fa-sync-alt me-2"></i> Reset Password
                </button>
            </form>

            <div class="back-link">
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const icon = field.nextElementSibling.querySelector('i');
    
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

// Password requirements checker
const newPassword = document.getElementById('new_password');
const confirmPassword = document.getElementById('confirm_password');
const lengthReq = document.getElementById('lengthReq');
const passwordMatch = document.getElementById('passwordMatch');

if (newPassword) {
    newPassword.addEventListener('input', function() {
        // Check length
        if (this.value.length >= 6) {
            lengthReq.innerHTML = '<i class="fas fa-check-circle"></i> At least 6 characters ✓';
            lengthReq.style.color = '#28a745';
        } else {
            lengthReq.innerHTML = '<i class="fas fa-times-circle"></i> At least 6 characters';
            lengthReq.style.color = '#dc3545';
        }
        
        // Check match if confirm has value
        if (confirmPassword.value) {
            checkPasswordMatch();
        }
    });
}

if (confirmPassword) {
    confirmPassword.addEventListener('input', checkPasswordMatch);
}

function checkPasswordMatch() {
    if (newPassword.value === confirmPassword.value) {
        passwordMatch.innerHTML = '<i class="fas fa-check-circle text-success"></i> Passwords match';
        passwordMatch.style.color = '#28a745';
    } else {
        passwordMatch.innerHTML = '<i class="fas fa-times-circle text-danger"></i> Passwords do not match';
        passwordMatch.style.color = '#dc3545';
    }
}

// Form submission with loading state
document.getElementById('resetForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('resetBtn');
    const newPass = document.getElementById('new_password').value;
    const confirmPass = document.getElementById('confirm_password').value;
    
    if (newPass !== confirmPass) {
        e.preventDefault();
        alert('Passwords do not match!');
        return;
    }
    
    if (newPass.length < 6) {
        e.preventDefault();
        alert('Password must be at least 6 characters long!');
        return;
    }
    
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

<?php include 'includes/footer.php'; ?>