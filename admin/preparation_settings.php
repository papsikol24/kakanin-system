<?php
require_once '../includes/config.php';
requireLogin();
requireRole(['admin', 'manager']);

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Update preparation time
    if (isset($_POST['update_time'])) {
        $id = (int)$_POST['id'];
        $preparation_time = (int)$_POST['preparation_time'];
        
        if ($preparation_time < 1) {
            $error = "Preparation time must be at least 1 minute.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE tbl_preparation_settings SET preparation_time = ? WHERE id = ?");
                $stmt->execute([$preparation_time, $id]);
                $message = "✅ Preparation time updated successfully!";
            } catch (Exception $e) {
                $error = "❌ Error: " . $e->getMessage();
            }
        }
    }
    
    // Add new custom setting
    if (isset($_POST['add_custom'])) {
        $item_name = trim($_POST['item_name']);
        $preparation_time = (int)$_POST['preparation_time'];
        $description = trim($_POST['description']);
        
        if (empty($item_name)) {
            $error = "Please enter an item name.";
        } elseif ($preparation_time < 1) {
            $error = "Preparation time must be at least 1 minute.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO tbl_preparation_settings (category, item_name, preparation_time, description) VALUES ('custom', ?, ?, ?)");
                $stmt->execute([$item_name, $preparation_time, $description]);
                $message = "✅ Custom item added successfully!";
            } catch (Exception $e) {
                $error = "❌ Error: " . $e->getMessage();
            }
        }
    }
    
    // Delete custom setting
    if (isset($_POST['delete_custom'])) {
        $id = (int)$_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM tbl_preparation_settings WHERE id = ? AND category = 'custom'");
            $stmt->execute([$id]);
            $message = "✅ Custom item deleted successfully!";
        } catch (Exception $e) {
            $error = "❌ Error: " . $e->getMessage();
        }
    }
}

// Get all preparation settings
$settings = $pdo->query("SELECT * FROM tbl_preparation_settings ORDER BY 
    CASE category 
        WHEN 'budget' THEN 1 
        WHEN 'regular' THEN 2 
        WHEN 'premium' THEN 3 
        WHEN 'custom' THEN 4 
    END, item_name")->fetchAll();

// Group by category
$grouped = [];
foreach ($settings as $s) {
    $grouped[$s['category']][] = $s;
}

include '../includes/header.php';
?>

<style>
    .settings-container {
        max-width: 1000px;
        margin: 30px auto;
        animation: fadeIn 0.5s ease;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .category-card {
        background: white;
        border-radius: 15px;
        margin-bottom: 25px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        overflow: hidden;
    }
    
    .category-header {
        padding: 15px 20px;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .category-header.budget {
        background: linear-gradient(135deg, #28a745, #20c997);
    }
    
    .category-header.regular {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        color: #333;
    }
    
    .category-header.premium {
        background: linear-gradient(135deg, #dc3545, #c82333);
    }
    
    .category-header.custom {
        background: linear-gradient(135deg, #17a2b8, #138496);
    }
    
    .category-header i {
        font-size: 1.3rem;
    }
    
    .settings-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .settings-table th {
        background: #f8f9fa;
        padding: 12px 15px;
        text-align: left;
        font-weight: 600;
        color: #555;
        border-bottom: 2px solid #dee2e6;
    }
    
    .settings-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #eee;
        vertical-align: middle;
    }
    
    .settings-table tr:hover {
        background: #f8f9fa;
    }
    
    .time-badge {
        display: inline-block;
        background: #008080;
        color: white;
        padding: 5px 15px;
        border-radius: 50px;
        font-weight: 600;
    }
    
    .time-input-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .time-input {
        width: 80px;
        padding: 8px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        text-align: center;
        font-weight: 600;
    }
    
    .time-input:focus {
        border-color: #008080;
        outline: none;
    }
    
    .btn-save {
        background: #28a745;
        color: white;
        border: none;
        border-radius: 50px;
        padding: 6px 15px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-save:hover {
        background: #218838;
        transform: translateY(-2px);
    }
    
    .btn-delete {
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 50px;
        padding: 6px 15px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-delete:hover {
        background: #c82333;
        transform: translateY(-2px);
    }
    
    .add-custom-form {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-top: 20px;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        align-items: end;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-group label {
        font-weight: 500;
        color: #555;
        margin-bottom: 5px;
        font-size: 0.85rem;
    }
    
    .form-control {
        padding: 10px;
        border: 2px solid #e0e0e0;
        border-radius: 10px;
        font-family: 'Poppins', sans-serif;
    }
    
    .form-control:focus {
        border-color: #008080;
        outline: none;
    }
    
    .btn-add {
        background: #008080;
        color: white;
        border: none;
        border-radius: 10px;
        padding: 10px 20px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        height: 42px;
    }
    
    .btn-add:hover {
        background: #20b2aa;
        transform: translateY(-2px);
    }
    
    .default-badge {
        background: #6c757d;
        color: white;
        padding: 3px 8px;
        border-radius: 50px;
        font-size: 0.7rem;
    }
    
    .info-box {
        background: #e7f3ff;
        border-left: 4px solid #17a2b8;
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    
    .info-box i {
        font-size: 2rem;
        color: #17a2b8;
    }
    
    .info-box p {
        margin: 0;
        color: #2c3e50;
    }
    
    .alert {
        padding: 15px 20px;
        border-radius: 10px;
        margin-bottom: 20px;
    }
    
    .alert-success {
        background: #d4edda;
        color: #155724;
        border-left: 4px solid #28a745;
    }
    
    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border-left: 4px solid #dc3545;
    }
</style>

<div class="container-fluid settings-container">
    <h2 class="mb-4"><i class="fas fa-clock me-2" style="color: #008080;"></i>Food Preparation Settings</h2>
    
    <?php if ($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Info Box -->
    <div class="info-box">
        <i class="fas fa-info-circle"></i>
        <div>
            <strong>How it works:</strong>
            <p>Set the estimated preparation time for different food categories. These times will be used to show customers how long their order will take. You can add custom items for special orders.</p>
        </div>
    </div>
    
    <!-- Budget Category -->
    <?php if (isset($grouped['budget'])): ?>
    <div class="category-card">
        <div class="category-header budget">
            <i class="fas fa-tag"></i>
            Budget Items (Below ₱10)
        </div>
        <table class="settings-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th>Preparation Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grouped['budget'] as $item): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                        <?php if ($item['is_default']): ?>
                            <span class="default-badge ms-2">Default</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Update preparation time?')">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="update_time" value="1">
                            <div class="time-input-group">
                                <input type="number" name="preparation_time" class="time-input" value="<?php echo $item['preparation_time']; ?>" min="1" max="180">
                                <span>minutes</span>
                                <button type="submit" class="btn-save">Save</button>
                            </div>
                        </form>
                    </td>
                    <td>
                        <?php if (!$item['is_default']): ?>
                            <form method="post" onsubmit="return confirm('Delete this item?')">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="delete_custom" value="1">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Regular Category -->
    <?php if (isset($grouped['regular'])): ?>
    <div class="category-card">
        <div class="category-header regular">
            <i class="fas fa-box"></i>
            Regular Items (₱10 - ₱249)
        </div>
        <table class="settings-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th>Preparation Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grouped['regular'] as $item): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                        <?php if ($item['is_default']): ?>
                            <span class="default-badge ms-2">Default</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Update preparation time?')">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="update_time" value="1">
                            <div class="time-input-group">
                                <input type="number" name="preparation_time" class="time-input" value="<?php echo $item['preparation_time']; ?>" min="1" max="180">
                                <span>minutes</span>
                                <button type="submit" class="btn-save">Save</button>
                            </div>
                        </form>
                    </td>
                    <td>
                        <?php if (!$item['is_default']): ?>
                            <form method="post" onsubmit="return confirm('Delete this item?')">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="delete_custom" value="1">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Premium Category -->
    <?php if (isset($grouped['premium'])): ?>
    <div class="category-card">
        <div class="category-header premium">
            <i class="fas fa-crown"></i>
            Premium Items (₱250+)
        </div>
        <table class="settings-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th>Preparation Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grouped['premium'] as $item): ?>
                <tr>
                    <td>
                        <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>
                        <?php if ($item['is_default']): ?>
                            <span class="default-badge ms-2">Default</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Update preparation time?')">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="update_time" value="1">
                            <div class="time-input-group">
                                <input type="number" name="preparation_time" class="time-input" value="<?php echo $item['preparation_time']; ?>" min="1" max="180">
                                <span>minutes</span>
                                <button type="submit" class="btn-save">Save</button>
                            </div>
                        </form>
                    </td>
                    <td>
                        <?php if (!$item['is_default']): ?>
                            <form method="post" onsubmit="return confirm('Delete this item?')">
                                <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="delete_custom" value="1">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
    
    <!-- Custom Items Category -->
    <div class="category-card">
        <div class="category-header custom">
            <i class="fas fa-plus-circle"></i>
            Custom Items
        </div>
        <?php if (isset($grouped['custom']) && !empty($grouped['custom'])): ?>
        <table class="settings-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Description</th>
                    <th>Preparation Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grouped['custom'] as $item): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($item['item_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($item['description']); ?></td>
                    <td>
                        <form method="post" style="display: inline;" onsubmit="return confirm('Update preparation time?')">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="update_time" value="1">
                            <div class="time-input-group">
                                <input type="number" name="preparation_time" class="time-input" value="<?php echo $item['preparation_time']; ?>" min="1" max="180">
                                <span>minutes</span>
                                <button type="submit" class="btn-save">Save</button>
                            </div>
                        </form>
                    </td>
                    <td>
                        <form method="post" onsubmit="return confirm('Delete this custom item?')">
                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                            <input type="hidden" name="delete_custom" value="1">
                            <button type="submit" class="btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="p-4 text-center text-muted">
            <i class="fas fa-info-circle fa-2x mb-2"></i>
            <p>No custom items added yet. Use the form below to add custom preparation times.</p>
        </div>
        <?php endif; ?>
        
        <!-- Add Custom Item Form -->
        <div class="add-custom-form">
            <h6 class="mb-3"><i class="fas fa-plus-circle me-2"></i>Add Custom Item</h6>
            <form method="post" class="form-grid" onsubmit="return validateCustomForm()">
                <div class="form-group">
                    <label>Item Name</label>
                    <input type="text" name="item_name" class="form-control" placeholder="e.g., Special Bilao" required>
                </div>
                <div class="form-group">
                    <label>Preparation Time (minutes)</label>
                    <input type="number" name="preparation_time" class="form-control" value="30" min="1" max="180" required>
                </div>
                <div class="form-group">
                    <label>Description (optional)</label>
                    <input type="text" name="description" class="form-control" placeholder="e.g., Family size bilao">
                </div>
                <div class="form-group">
                    <button type="submit" name="add_custom" class="btn-add">
                        <i class="fas fa-plus"></i> Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- How to Use -->
    <div class="card mt-4">
        <div class="card-header bg-info text-white">
            <i class="fas fa-question-circle"></i> How to Use
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6><i class="fas fa-clock me-2 text-info"></i>Setting Preparation Times:</h6>
                    <ul class="small">
                        <li>Default times are preset for each category</li>
                        <li>Click "Save" after changing any time</li>
                        <li>Times are in minutes</li>
                        <li>Customers will see estimated delivery time based on these settings</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6><i class="fas fa-plus-circle me-2 text-info"></i>Adding Custom Items:</h6>
                    <ul class="small">
                        <li>Use for special orders or unique items</li>
                        <li>Custom items can be deleted anytime</li>
                        <li>Default items cannot be deleted</li>
                        <li>These times will be used when processing orders</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validateCustomForm() {
    const itemName = document.querySelector('input[name="item_name"]').value.trim();
    const prepTime = document.querySelector('input[name="preparation_time"]').value;
    
    if (!itemName) {
        alert('Please enter an item name');
        return false;
    }
    
    if (prepTime < 1 || prepTime > 180) {
        alert('Preparation time must be between 1 and 180 minutes');
        return false;
    }
    
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

<?php include '../includes/footer.php'; ?>