<?php
require_once __DIR__ . '/vendor/autoload.php';

echo "<h1>🍚 Jen's Kakanin</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check MongoDB extension
if (extension_loaded('mongodb')) {
    echo "<p style='color:green'>✅ MongoDB extension is LOADED (version: " . phpversion('mongodb') . ")</p>";
} else {
    echo "<p style='color:red'>❌ MongoDB extension is NOT loaded</p>";
}

// Check Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    echo "<p style='color:green'>✅ Composer autoloader loaded</p>";
    
    if (class_exists('MongoDB\Client')) {
        echo "<p style='color:green'>✅ MongoDB\Client class found!</p>";
        
        // Try to get products from database
        try {
            $uri = getenv('MONGODB_URI') ?: getenv('MONGO_URL');
            $client = new MongoDB\Client($uri);
            $db = $client->selectDatabase('kakanin_db');
            $collection = $db->products;
            
            $products = $collection->find();
            $productCount = iterator_count($products);
            
            echo "<p style='color:green'>✅ Connected to MongoDB! Found $productCount products.</p>";
            
            // Display products
            echo "<h2>Our Products</h2>";
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #008080; color: white;'><th>Product</th><th>Price</th><th>Stock</th><th>Tier</th><th>Min/Max</th></tr>";
            
            $products = $collection->find();
            foreach ($products as $product) {
                echo "<tr>";
                echo "<td>" . $product['name'] . "</td>";
                echo "<td>₱" . number_format($product['price'], 2) . "</td>";
                echo "<td>" . $product['stock'] . "</td>";
                echo "<td>" . ucfirst($product['tier']) . "</td>";
                echo "<td>" . $product['min_order'] . " / " . $product['max_order'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } catch (Exception $e) {
            echo "<p style='color:red'>❌ Database error: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color:red'>❌ MongoDB\Client class NOT found</p>";
    }
} else {
    echo "<p style='color:red'>❌ vendor/autoload.php not found. Run composer install</p>";
}

echo "<hr>";
echo "<p><strong>Jen's Kakanin</strong> | Brgy 83-B Cogon San Jose, Tacloban City</p>";
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";