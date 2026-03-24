<?php
require_once '../../includes/config.php';
requireLogin();
requireRole('admin');

$id = $_GET['id'] ?? 0;
$user = $pdo->prepare("SELECT * FROM tbl_users WHERE id = ?");
$user->execute([$id]);
$user = $user->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $role = $_POST['role'];
    $status = isset($_POST['status']) ? 1 : 0;
    $password = $_POST['password'];

    // Validation
    if (empty($username)) {
        $error = "Username is required.";
    } else {
        // ===== FIXED: Check for duplicate username (excluding current user) =====
        $check = $pdo->prepare("SELECT id FROM tbl_users WHERE username = ? AND id != ?");
        $check->execute([$username, $id]);
        
        if ($check->fetch()) {
            $error = "Username '$username' is already taken by another user. Please choose a different username.";
        } else {
            // Update user
            if (!empty($password)) {
                // If password is provided, update with new password
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE tbl_users SET username = ?, password = ?, role = ?, status = ? WHERE id = ?");
                $result = $stmt->execute([$username, $hashed, $role, $status, $id]);
            } else {
                // If password is empty, update without changing password
                $stmt = $pdo->prepare("UPDATE tbl_users SET username = ?, role = ?, status = ? WHERE id = ?");
                $result = $stmt->execute([$username, $role, $status, $id]);
            }

            if ($result) {
                $_SESSION['success'] = "User updated successfully.";
                header('Location: index.php');
                exit;
            } else {
                $error = "Failed to update user. Please try again.";
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="section-header">
        <h4><i class="fas fa-user-edit me-2"></i>Edit User: <?php echo htmlspecialchars($user['username']); ?></h4>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card form-card">
        <div class="card-body">
            <form method="post" onsubmit="return validateForm()">
                <div class="mb-3">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" 
                           class="form-control" 
                           id="username" 
                           name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                           required
                           pattern="[a-zA-Z0-9_]+"
                           title="Username can only contain letters, numbers, and underscores">
                    <small class="text-muted">Username must be unique. Only letters, numbers, and underscores allowed.</small>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">New Password (leave blank to keep current)</label>
                    <div class="input-group">
                        <input type="password" 
                               class="form-control" 
                               id="password" 
                               name="password" 
                               placeholder="Enter new password">
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                            <i class="fas fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                    <small class="text-muted">Minimum 6 characters. Leave empty to keep current password.</small>
                </div>
                
                <div class="mb-3">
                    <label for="role" class="form-label">Role</label>
                    <select class="form-select" id="role" name="role">
                        <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="manager" <?php echo $user['role'] == 'manager' ? 'selected' : ''; ?>>Manager</option>
                        <option value="cashier" <?php echo $user['role'] == 'cashier' ? 'selected' : ''; ?>>Cashier</option>
                    </select>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" 
                           class="form-check-input" 
                           id="status" 
                           name="status" 
                           <?php echo $user['status'] ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="status">Active</label>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save me-2"></i>Update User
                </button>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-times me-2"></i>Cancel
                </a>
            </form>
        </div>
    </div>
</div>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('password');
    const toggleIcon = document.getElementById('toggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}

function validateForm() {
    const username = document.getElementById('username').value;
    const usernameRegex = /^[a-zA-Z0-9_]+$/;
    
    if (!usernameRegex.test(username)) {
        alert('Username can only contain letters, numbers, and underscores!');
        return false;
    }
    
    const password = document.getElementById('password').value;
    if (password.length > 0 && password.length < 6) {
        alert('Password must be at least 6 characters long!');
        return false;
    }
    
    return true;
}
</script>

<style>
.form-card {
    max-width: 600px;
    margin: 0 auto;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
}

.form-card .card-body {
    padding: 2rem;
}

.form-control, .form-select {
    border-radius: 10px;
    padding: 0.6rem 1rem;
    border: 1px solid #e0e0e0;
}

.form-control:focus, .form-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
}

.btn {
    border-radius: 50px;
    padding: 0.5rem 1.5rem;
}

.input-group .btn {
    border-radius: 0 50px 50px 0;
}
</style>

<?php include '../../includes/footer.php'; ?>