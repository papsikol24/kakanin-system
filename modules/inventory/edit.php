<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

$id = (int)($_GET['id'] ?? 0);
$product = $pdo->prepare("SELECT * FROM tbl_products WHERE id = ?");
$product->execute([$id]);
$product = $product->fetch();

if (!$product) {
    $_SESSION['error'] = "Product not found.";
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $stock = (int)$_POST['stock'];
    $low_stock_threshold = (int)($_POST['low_stock_threshold'] ?? 10);
    $image = $_FILES['image'] ?? null;

    // Validate required fields
    if (empty($name)) {
        $error = "Product name is required.";
    } elseif (!is_numeric($price) || $price < 0) {
        $error = "Price must be a positive number.";
    } elseif ($stock < 0) {
        $error = "Stock cannot be negative.";
    } else {
        // ========== DUPLICATE CHECK (excluding current product) ==========
        $check_stmt = $pdo->prepare("SELECT id FROM tbl_products WHERE LOWER(name) = LOWER(?) AND id != ?");
        $check_stmt->execute([$name, $id]);
        if ($check_stmt->fetch()) {
            $error = "A product with this name already exists. Please use a different name.";
        } else {
            $imageName = $product['image']; // keep old image by default

            // Handle new image upload if provided
            if ($image && $image['error'] == UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                $maxSize = 2 * 1024 * 1024; // 2MB

                if (!in_array($image['type'], $allowedTypes)) {
                    $error = "Only JPG, PNG, and GIF images are allowed.";
                } elseif ($image['size'] > $maxSize) {
                    $error = "Image size must be less than 2MB.";
                } else {
                    $extension = pathinfo($image['name'], PATHINFO_EXTENSION);
                    $imageName = uniqid() . '_' . time() . '.' . $extension;
                    $targetDir = '../../assets/images/';

                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }

                    $targetPath = $targetDir . $imageName;
                    if (move_uploaded_file($image['tmp_name'], $targetPath)) {
                        // Delete old image if it exists
                        if ($product['image'] && file_exists($targetDir . $product['image'])) {
                            unlink($targetDir . $product['image']);
                        }
                    } else {
                        $error = "Failed to upload image.";
                    }
                }
            }

            if (empty($error)) {
                // Check if stock changed for logging
                if ($stock != $product['stock']) {
                    $change_type = ($stock > $product['stock']) ? 'add' : 'subtract';
                    $quantity_changed = abs($stock - $product['stock']);
                    logInventory($pdo, $id, $_SESSION['user_id'], $change_type, $quantity_changed, $product['stock'], $stock);
                }

                $stmt = $pdo->prepare("UPDATE tbl_products SET name=?, description=?, price=?, stock=?, low_stock_threshold=?, image=? WHERE id=?");
                if ($stmt->execute([$name, $description, $price, $stock, $low_stock_threshold, $imageName, $id])) {
                    $_SESSION['success'] = "Product updated successfully.";
                    header('Location: index.php');
                    exit;
                } else {
                    $error = "Database error. Please try again.";
                }
            }
        }
    }
}

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="section-header">
        <h4><i class="fas fa-edit me-2"></i>Edit Product</h4>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="card form-card">
        <div class="card-body">
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label for="name" class="form-label">Product Name *</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">Price (₱) *</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo $product['price']; ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="stock" class="form-label">Stock *</label>
                        <input type="number" class="form-control" id="stock" name="stock" value="<?php echo $product['stock']; ?>" required min="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                    <input type="number" class="form-control" id="low_stock_threshold" name="low_stock_threshold" value="<?php echo $product['low_stock_threshold']; ?>" min="0">
                </div>
                <div class="mb-4">
                    <label for="image" class="form-label">Product Image (JPG, PNG, GIF, max 2MB)</label>
                    <input type="file" class="form-control" id="image" name="image" accept="image/jpeg,image/png,image/gif">
                    <?php if ($product['image']): ?>
                        <div class="mt-2">
                            <small>Current image: <?php echo $product['image']; ?></small><br>
                            <img src="../../assets/images/<?php echo $product['image']; ?>" width="100" style="border-radius: 10px;">
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Product</button>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-times me-2"></i>Cancel</a>
            </form>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>