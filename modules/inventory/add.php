<?php
require_once '../../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

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
        // ========== DUPLICATE CHECK ==========
        $check_stmt = $pdo->prepare("SELECT id FROM tbl_products WHERE LOWER(name) = LOWER(?)");
        $check_stmt->execute([$name]);
        if ($check_stmt->fetch()) {
            $error = "A product with this name already exists. Please use a different name.";
        } else {
            // Handle image upload - FIXED to preserve original filename
            $imageName = '';
            
            if ($image && $image['error'] == UPLOAD_ERR_OK) {
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                $maxSize = 2 * 1024 * 1024; // 2MB

                if (!in_array($image['type'], $allowedTypes)) {
                    $error = "Only JPG, PNG, and GIF images are allowed.";
                } elseif ($image['size'] > $maxSize) {
                    $error = "Image size must be less than 2MB.";
                } else {
                    // FIX: Preserve original filename but make it URL-safe
                    $original_filename = $image['name'];
                    
                    // Remove spaces and special characters, but keep the name readable
                    $original_filename = preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $original_filename);
                    
                    // Make sure filename isn't too long
                    if (strlen($original_filename) > 100) {
                        $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
                        $basename = pathinfo($original_filename, PATHINFO_FILENAME);
                        $basename = substr($basename, 0, 90);
                        $original_filename = $basename . '.' . $extension;
                    }
                    
                    // Check if file already exists, if yes, add number
                    $targetDir = '../../assets/images/';
                    $targetPath = $targetDir . $original_filename;
                    $counter = 1;
                    
                    while (file_exists($targetPath)) {
                        $extension = pathinfo($original_filename, PATHINFO_EXTENSION);
                        $basename = pathinfo($original_filename, PATHINFO_FILENAME);
                        // Remove existing counter if any
                        $basename = preg_replace('/_\d+$/', '', $basename);
                        $new_filename = $basename . '_' . $counter . '.' . $extension;
                        $targetPath = $targetDir . $new_filename;
                        $counter++;
                    }
                    
                    if ($counter > 1) {
                        $original_filename = $new_filename;
                    }
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($targetDir)) {
                        mkdir($targetDir, 0777, true);
                    }

                    // Move the uploaded file
                    if (move_uploaded_file($image['tmp_name'], $targetPath)) {
                        $imageName = $original_filename;
                    } else {
                        $error = "Failed to upload image.";
                    }
                }
            }

            if (empty($error)) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO tbl_products (name, description, price, stock, low_stock_threshold, image) VALUES (?, ?, ?, ?, ?, ?)");
                    
                    if ($stmt->execute([$name, $description, $price, $stock, $low_stock_threshold, $imageName])) {
                        $productId = $pdo->lastInsertId();
                        
                        // Log inventory change
                        if (function_exists('logInventory')) {
                            logInventory($pdo, $productId, $_SESSION['user_id'], 'set', $stock, 0, $stock);
                        }
                        
                        $_SESSION['success'] = "Product '{$name}' added successfully.";
                        header('Location: index.php');
                        exit;
                    } else {
                        $error = "Database error. Please try again.";
                    }
                } catch (PDOException $e) {
                    if ($e->errorInfo[1] == 1062) {
                        $error = "A product with this name already exists.";
                    } else {
                        $error = "Database error: " . $e->getMessage();
                    }
                }
            }
        }
    }
}

include '../../includes/header.php';
?>

<style>
    /* ===== FORM STYLES ===== */
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
        color: #28a745;
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
        background: linear-gradient(90deg, #28a745, #218838);
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
        color: #28a745;
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
        width: 100%;
    }

    .form-control:focus, .form-select:focus {
        border-color: #28a745;
        box-shadow: 0 0 0 0.2rem rgba(40,167,69,0.25);
        outline: none;
    }

    textarea.form-control {
        min-height: 100px;
        resize: vertical;
    }

    /* Image upload styles */
    .image-upload-area {
        border: 2px dashed #28a745;
        border-radius: 15px;
        padding: 2rem;
        text-align: center;
        background: #f8f9fa;
        cursor: pointer;
        transition: all 0.3s;
        margin-bottom: 1rem;
    }

    .image-upload-area:hover {
        background: #e8f5e9;
        border-color: #218838;
    }

    .image-upload-area i {
        font-size: 3rem;
        color: #28a745;
        margin-bottom: 1rem;
    }

    .image-upload-area p {
        margin-bottom: 0.5rem;
        color: #666;
    }

    .image-upload-area small {
        color: #999;
    }

    .image-preview {
        max-width: 200px;
        max-height: 200px;
        border-radius: 10px;
        border: 2px solid #28a745;
        margin-top: 1rem;
        display: none;
    }

    .filename-preview {
        background: #e8f5e9;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0.5rem;
        font-size: 0.9rem;
        color: #28a745;
    }

    .filename-preview i {
        font-size: 1rem;
    }

    /* Button styles */
    .btn-submit {
        background: linear-gradient(135deg, #28a745, #218838);
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
        box-shadow: 0 5px 15px rgba(40,167,69,0.3);
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(40,167,69,0.4);
    }

    .btn-submit:active {
        transform: translateY(-1px);
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

    /* Alert styles */
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

    /* Loading state */
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

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .form-card {
            padding: 1.5rem;
        }

        .form-header h4 {
            font-size: 1.6rem;
        }

        .btn-submit, .btn-cancel {
            width: 100%;
            margin: 0.5rem 0;
            justify-content: center;
        }

        .image-upload-area {
            padding: 1.5rem;
        }
    }

    @media (max-width: 576px) {
        .form-card {
            padding: 1rem;
        }

        .form-header h4 {
            font-size: 1.4rem;
        }

        .form-label {
            font-size: 0.9rem;
        }
    }
</style>

<div class="container-fluid">
    <div class="form-container">
        <div class="form-card">
            <div class="form-header">
                <h4>
                    <i class="fas fa-plus-circle"></i>
                    Add New Product
                </h4>
                <p>Fill in the product details below</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" id="addProductForm" onsubmit="return validateForm()">
                <!-- Product Name -->
                <div class="mb-4">
                    <label for="name" class="form-label">
                        <i class="fas fa-tag"></i>
                        Product Name <span class="required">*</span>
                    </label>
                    <input type="text" 
                           class="form-control" 
                           id="name" 
                           name="name" 
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                           placeholder="Enter product name"
                           required
                           maxlength="100">
                    <small class="text-muted">Must be unique. Max 100 characters.</small>
                </div>

                <!-- Description -->
                <div class="mb-4">
                    <label for="description" class="form-label">
                        <i class="fas fa-align-left"></i>
                        Description
                    </label>
                    <textarea class="form-control" 
                              id="description" 
                              name="description" 
                              rows="4"
                              placeholder="Enter product description (optional)"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <!-- Price and Stock Row -->
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <label for="price" class="form-label">
                            <i class="fas fa-peso-sign"></i>
                            Price <span class="required">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text" style="border-radius: 10px 0 0 10px; background: #28a745; color: white;">₱</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   class="form-control" 
                                   id="price" 
                                   name="price" 
                                   value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                                   placeholder="0.00"
                                   required
                                   style="border-radius: 0 10px 10px 0;">
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label for="stock" class="form-label">
                            <i class="fas fa-boxes"></i>
                            Initial Stock <span class="required">*</span>
                        </label>
                        <input type="number" 
                               min="0" 
                               class="form-control" 
                               id="stock" 
                               name="stock" 
                               value="<?php echo (int)($_POST['stock'] ?? 0); ?>" 
                               placeholder="0"
                               required>
                    </div>
                </div>

                <!-- Low Stock Threshold -->
                <div class="mb-4">
                    <label for="low_stock_threshold" class="form-label">
                        <i class="fas fa-exclamation-triangle"></i>
                        Low Stock Threshold
                    </label>
                    <input type="number" 
                           min="0" 
                           class="form-control" 
                           id="low_stock_threshold" 
                           name="low_stock_threshold" 
                           value="<?php echo (int)($_POST['low_stock_threshold'] ?? 10); ?>"
                           placeholder="10">
                    <small class="text-muted">You'll be alerted when stock falls below this number.</small>
                </div>

                <!-- Image Upload with Preview -->
                <div class="mb-4">
                    <label class="form-label">
                        <i class="fas fa-image"></i>
                        Product Image
                    </label>
                    
                    <div class="image-upload-area" id="uploadArea" onclick="document.getElementById('image').click()">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <p><strong>Click to upload or drag and drop</strong></p>
                        <p class="mb-0">JPG, PNG, GIF (max 2MB)</p>
                    </div>
                    
                    <input type="file" 
                           class="d-none" 
                           id="image" 
                           name="image" 
                           accept="image/jpeg,image/png,image/gif,image/jpg"
                           onchange="previewImage(this)">
                    
                    <!-- Preview Container -->
                    <div id="previewContainer" style="display: none; text-align: center; margin-top: 1rem;">
                        <img id="imagePreview" class="image-preview" src="#" alt="Preview">
                        <div id="filenamePreview" class="filename-preview">
                            <i class="fas fa-check-circle"></i>
                            <span id="filename"></span>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn-submit" id="submitBtn">
                        <i class="fas fa-save"></i>
                        Save Product
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
// Image preview function
function previewImage(input) {
    const previewContainer = document.getElementById('previewContainer');
    const preview = document.getElementById('imagePreview');
    const filenameSpan = document.getElementById('filename');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const fileName = file.name;
        const fileSize = file.size / 1024 / 1024; // Size in MB
        const fileExt = fileName.split('.').pop().toLowerCase();
        
        // Validate file type
        const allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!allowedTypes.includes(fileExt)) {
            alert('Only JPG, PNG, and GIF images are allowed.');
            input.value = '';
            previewContainer.style.display = 'none';
            return;
        }
        
        // Validate file size (max 2MB)
        if (fileSize > 2) {
            alert('Image size must be less than 2MB.');
            input.value = '';
            previewContainer.style.display = 'none';
            return;
        }
        
        // Show preview
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.style.display = 'block';
            filenameSpan.textContent = fileName + ' (' + fileSize.toFixed(2) + ' MB)';
            previewContainer.style.display = 'block';
            
            // Highlight upload area
            document.getElementById('uploadArea').style.background = '#e8f5e9';
        }
        reader.readAsDataURL(file);
    } else {
        previewContainer.style.display = 'none';
        document.getElementById('uploadArea').style.background = '#f8f9fa';
    }
}

// Form validation
function validateForm() {
    const name = document.getElementById('name').value.trim();
    const price = document.getElementById('price').value;
    const stock = document.getElementById('stock').value;
    
    if (!name) {
        alert('Please enter a product name.');
        return false;
    }
    
    if (price < 0) {
        alert('Price cannot be negative.');
        return false;
    }
    
    if (stock < 0) {
        alert('Stock cannot be negative.');
        return false;
    }
    
    // Disable submit button
    document.getElementById('submitBtn').classList.add('loading');
    document.getElementById('submitBtn').disabled = true;
    
    return true;
}

// Drag and drop functionality
const uploadArea = document.getElementById('uploadArea');

['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, preventDefaults, false);
});

function preventDefaults(e) {
    e.preventDefault();
    e.stopPropagation();
}

['dragenter', 'dragover'].forEach(eventName => {
    uploadArea.addEventListener(eventName, highlight, false);
});

['dragleave', 'drop'].forEach(eventName => {
    uploadArea.addEventListener(eventName, unhighlight, false);
});

function highlight() {
    uploadArea.style.background = '#e8f5e9';
    uploadArea.style.borderColor = '#218838';
}

function unhighlight() {
    uploadArea.style.background = '#f8f9fa';
    uploadArea.style.borderColor = '#28a745';
}

uploadArea.addEventListener('drop', handleDrop, false);

function handleDrop(e) {
    const dt = e.dataTransfer;
    const files = dt.files;
    document.getElementById('image').files = files;
    previewImage(document.getElementById('image'));
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