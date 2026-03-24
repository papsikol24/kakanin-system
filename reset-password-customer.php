<?php
require_once 'includes/config.php';

$error = '';
$message = '';

$token = $_GET['token'] ?? '';

if (!$token) {
    die('Invalid token.');
}

// Verify token exists and is not expired
$stmt = $pdo->prepare("SELECT * FROM tbl_customers WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$token]);
$customer = $stmt->fetch();

if (!$customer) {
    die('Invalid or expired token.');
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE tbl_customers SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
        $stmt->execute([$hash, $customer['id']]);
        $message = "Password updated successfully. <a href='/customer-login' class='alert-link'>Login now</a>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password · Kakanin Customer</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: url('assets/images/background.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
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
        }
        .reset-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 400px;
            padding: 15px;
        }
        .reset-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            animation: fadeInUp 0.8s ease-out;
            overflow: hidden;
        }
        .reset-card .card-header {
            background: linear-gradient(135deg, #008080, #20b2aa);
            color: white;
            text-align: center;
            padding: 1.5rem;
            border-bottom: none;
        }
        .reset-card .card-header h4 {
            margin: 0;
            font-weight: 600;
        }
        .reset-card .card-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 50px;
            padding: 0.75rem 1.5rem;
            border: 1px solid #ddd;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: #008080;
            box-shadow: 0 0 0 0.25rem rgba(0,128,128,0.25);
        }
        .btn-reset {
            background: linear-gradient(135deg, #008080, #20b2aa);
            border: none;
            border-radius: 50px;
            padding: 0.75rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,128,128,0.4);
        }
        .alert {
            border-radius: 50px;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <div class="reset-container">
        <div class="card reset-card">
            <div class="card-header">
                <h4><i class="fas fa-key me-2"></i>Reset Password</h4>
            </div>
            <div class="card-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <?php if ($message): ?>
                    <div class="alert alert-success"><?php echo $message; ?></div>
                <?php else: ?>
                <p class="text-muted text-center mb-4">Enter your new password below.</p>
                <form method="post">
                    <div class="mb-3">
                        <label for="password" class="form-label">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-reset mt-2">Update Password</button>
                </form>
                <?php endif; ?>
                <div class="text-center mt-3">
                    <a href="/customer-login" class="text-decoration-none">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>