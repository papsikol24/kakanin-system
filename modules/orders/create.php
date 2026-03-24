<?php
require_once '../../includes/config.php';
requireLogin();

$customers = $pdo->query("SELECT id, name FROM tbl_customers ORDER BY name")->fetchAll();
$products = $pdo->query("SELECT * FROM tbl_products WHERE stock > 0 ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $customer_id = $_POST['customer_id'] ?: null;
    $payment_method = $_POST['payment_method'];
    $product_ids = $_POST['product_id'];
    $quantities = $_POST['quantity'];
    $created_by = $_SESSION['user_id'];

    // Get customer name if selected
    $customer_name = '';
    if ($customer_id) {
        $cust = $pdo->prepare("SELECT name FROM tbl_customers WHERE id = ?");
        $cust->execute([$customer_id]);
        $customer_name = $cust->fetchColumn();
    }

    $total = 0;
    $items = [];
    for ($i = 0; $i < count($product_ids); $i++) {
        if ($quantities[$i] > 0) {
            $pid = $product_ids[$i];
            $qty = $quantities[$i];
            $product = $pdo->prepare("SELECT * FROM tbl_products WHERE id = ?");
            $product->execute([$pid]);
            $p = $product->fetch();
            if ($p && $p['stock'] >= $qty) {
                $items[] = [
                    'product_id' => $pid,
                    'quantity' => $qty,
                    'price' => $p['price']
                ];
                $total += $p['price'] * $qty;
            } else {
                $_SESSION['error'] = "Insufficient stock for product: " . $p['name'];
                header('Location: create.php');
                exit;
            }
        }
    }

    if (empty($items)) {
        $_SESSION['error'] = "No items selected.";
        header('Location: create.php');
        exit;
    }

    $pdo->beginTransaction();
    try {
        // ===== FIXED: Insert order with customer_name =====
        $stmt = $pdo->prepare("INSERT INTO tbl_orders 
            (customer_id, customer_name, total_amount, payment_method, status, created_by) 
            VALUES (?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([$customer_id, $customer_name, $total, $payment_method, $created_by]);
        $order_id = $pdo->lastInsertId();

        foreach ($items as $item) {
            $stmt = $pdo->prepare("INSERT INTO tbl_order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);

            $prod = $pdo->prepare("SELECT stock FROM tbl_products WHERE id = ?");
            $prod->execute([$item['product_id']]);
            $old_stock = $prod->fetchColumn();
            $new_stock = $old_stock - $item['quantity'];

            $update = $pdo->prepare("UPDATE tbl_products SET stock = ? WHERE id = ?");
            $update->execute([$new_stock, $item['product_id']]);

            logInventory($pdo, $item['product_id'], $created_by, 'subtract', $item['quantity'], $old_stock, $new_stock);
        }

        $pdo->commit();
        $_SESSION['success'] = "Order created successfully. Order #$order_id";
        header('Location: view.php?id=' . $order_id);
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Order failed: " . $e->getMessage();
        header('Location: create.php');
        exit;
    }
}

include '../../includes/header.php';
?>
<div class="container-fluid">
    <div class="section-header">
        <h4><i class="fas fa-plus-circle me-2"></i>Create New Order</h4>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="card form-card">
        <div class="card-body">
            <form method="post" id="orderForm">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="customer_id" class="form-label">Customer (optional)</label>
                        <select class="form-select" id="customer_id" name="customer_id">
                            <option value="">Walk-in Customer</option>
                            <?php foreach ($customers as $c): ?>
                            <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="payment_method" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="gcash">GCash</option>
                        </select>
                    </div>
                </div>

                <h5 class="mb-3">Select Products</h5>
                <div class="table-responsive">
                    <table class="table table-bordered" id="productTable">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Available Stock</th>
                                <th>Quantity</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($p['name']); ?>
                                    <input type="hidden" name="product_id[]" value="<?php echo $p['id']; ?>">
                                </td>
                                <td>₱<?php echo number_format($p['price'], 2); ?></td>
                                <td><?php echo $p['stock']; ?></td>
                                <td><input type="number" name="quantity[]" class="form-control qty" min="0" max="<?php echo $p['stock']; ?>" value="0"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-3">
                    <div class="col-md-6">
                        <h5>Total: ₱<span id="totalDisplay">0.00</span></h5>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary mt-3"><i class="fas fa-check me-2"></i>Place Order</button>
                <a href="index.php" class="btn btn-secondary mt-3"><i class="fas fa-times me-2"></i>Cancel</a>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    function updateTotal() {
        let total = 0;
        $('#productTable tbody tr').each(function() {
            let price = parseFloat($(this).find('td:eq(1)').text().replace('₱', '')) || 0;
            let qty = parseInt($(this).find('.qty').val()) || 0;
            total += price * qty;
        });
        $('#totalDisplay').text(total.toFixed(2));
    }
    $('.qty').on('input', updateTotal);
});
</script>

<?php include '../../includes/footer.php'; ?>