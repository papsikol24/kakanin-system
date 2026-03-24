<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

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
    if (empty($name) || empty($email) || empty($username) || empty($password)) {
        $error = "Name, email, username, and password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif (!empty($phone) && !preg_match('/^[0-9]{11}$/', $phone)) {
        $error = "Phone number must be 11 digits (e.g., 09171234567).";
    } else {
        // Check if username or email exists
        $stmt = $pdo->prepare("SELECT id FROM tbl_customers WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username or email already taken.";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $hashed_answer = !empty($security_answer) ? password_hash(strtolower(trim($security_answer)), PASSWORD_DEFAULT) : null;

            $stmt = $pdo->prepare("INSERT INTO tbl_customers (name, email, username, password, phone, security_question, security_answer, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            if ($stmt->execute([$name, $email, $username, $hashed, $phone, $security_question, $hashed_answer, $status])) {
                $_SESSION['success'] = "Customer added successfully.";
                header('Location: index.php');
                exit;
            } else {
                $error = "Failed to add customer.";
            }
        }
    }
}

// Predefined security questions
$questions = [
    "What is your mother's maiden name?",
    "What was the name of your first pet?",
    "What was your childhood nickname?",
    "What is your favorite book?",
    "What city were you born in?"
];

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="section-header">
        <h4><i class="fas fa-user-plus me-2"></i>Add New Customer</h4>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" placeholder="09171234567">
                        <small class="text-muted">11 digits starting with 09</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="1" selected>Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="security_question" class="form-label">Security Question</label>
                        <select class="form-select" id="security_question" name="security_question">
                            <option value="">None (optional)</option>
                            <?php foreach ($questions as $q): ?>
                                <option value="<?php echo htmlspecialchars($q); ?>"><?php echo $q; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="security_answer" class="form-label">Security Answer</label>
                        <input type="text" class="form-control" id="security_answer" name="security_answer" value="<?php echo htmlspecialchars($_POST['security_answer'] ?? ''); ?>">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Save Customer</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i>Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>