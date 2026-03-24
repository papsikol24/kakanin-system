<?php
// Load MongoDB configuration
require_once __DIR__ . '/config/database.php';

session_start();

// Check MongoDB connection
$dbConnected = false;
$products = [];

try {
    $dbConnected = checkDbConnection();
    
    if ($dbConnected) {
        $collection = getCollection('products');
        $cursor = $collection->find([], ['limit' => 8]);
        foreach ($cursor as $product) {
            $products[] = formatDocument($product);
        }
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jen's Kakanin - Online Ordering System</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .header { background: #008080; color: white; padding: 20px; text-align: center; }
        .status { padding: 10px; text-align: center; margin: 20px; border-radius: 10px; }
        .status-online { background: #d4edda; color: #155724; }
        .status-offline { background: #f8d7da; color: #721c24; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
        .product-card { background: white; border-radius: 10px; padding: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .product-price { font-size: 20px; font-weight: bold; color: #008080; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px; background: #ff8c00; color: white; text-decoration: none; border-radius: 5px; }
        .footer { background: #333; color: white; text-align: center; padding: 20px; margin-top: 40px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🍚 Jen's Kakanin</h1>
        <p>Authentic Filipino Rice Cakes | Dockerized with MongoDB</p>
    </div>

    <div class="status <?php echo $dbConnected ? 'status-online' : 'status-offline'; ?>">
        <?php if ($dbConnected): ?>
            ✅ MongoDB Connected Successfully
        <?php else: ?>
            ❌ MongoDB Connecting... (Create MongoDB on Render first)
        <?php endif; ?>
    </div>

    <div class="container">
        <h2>Our Products</h2>
        
        <?php if (!empty($products)): ?>
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                <p><?php echo htmlspecialchars($product['description']); ?></p>
                <div class="product-price">₱<?php echo number_format($product['price'], 2); ?></div>
                <small>Stock: <?php echo $product['stock']; ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <p>No products found. Please initialize the database.</p>
        <?php endif; ?>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="/login" class="btn">Customer Login</a>
            <a href="/staff-login" class="btn">Staff Login</a>
        </div>
    </div>

    <div class="footer">
        <p>Jen's Kakanin | Brgy 83-B Cogon San Jose, Tacloban City | 0935 606 2163</p>
        <p>Dockerized System | MongoDB Database | Deployed on Render</p>
    </div>
</body>
</html>