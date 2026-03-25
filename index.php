<?php
// Test MongoDB connection
require_once __DIR__ . '/config/database.php';

echo "<h1>Jen's Kakanin - Dockerized System</h1>";

// Check connection
$connected = checkDbConnection();

if ($connected) {
    echo "<p style='color:green'>✅ MongoDB Connected Successfully!</p>";
    
    // Try to get products
    try {
        $collection = getCollection('products');
        $products = $collection->find([], ['limit' => 5]);
        
        echo "<h2>Products:</h2>";
        echo "<ul>";
        foreach ($products as $product) {
            echo "<li>" . $product['name'] . " - ₱" . $product['price'] . "</li>";
        }
        echo "</ul>";
        
        if ($products->isDead()) {
            echo "<p>No products found. Add some products to get started.</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>Error fetching products: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color:red'>❌ MongoDB Connection Failed</p>";
    echo "<p>Please check your environment variables.</p>";
}

echo "<hr>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";