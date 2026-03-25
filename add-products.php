<?php
// Load Composer autoloader
require_once __DIR__ . '/vendor/autoload.php';

// Get connection string from environment
$uri = getenv('MONGODB_URI') ?: getenv('MONGO_URL');

if (!$uri) {
    die('MONGODB_URI environment variable not set');
}

echo "<h1>Adding Products to MongoDB</h1>";

try {
    $client = new MongoDB\Client($uri);
    $db = $client->selectDatabase('kakanin_db');
    $collection = $db->products;
    
    // Clear existing products
    $deleteResult = $collection->deleteMany([]);
    echo "<p>✅ Cleared " . $deleteResult->getDeletedCount() . " existing products</p>";
    
    // Add new products
    $products = [
        [
            'name' => 'Puto',
            'description' => 'Soft and fluffy rice cake, perfect for breakfast',
            'price' => 5.00,
            'stock' => 100,
            'tier' => 'budget',
            'min_order' => 20,
            'max_order' => 300,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Kutsinta',
            'description' => 'Brown rice cake topped with grated coconut',
            'price' => 8.00,
            'stock' => 100,
            'tier' => 'budget',
            'min_order' => 20,
            'max_order' => 300,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Bibingka',
            'description' => 'Baked rice cake with salted egg and cheese',
            'price' => 15.00,
            'stock' => 100,
            'tier' => 'regular',
            'min_order' => 20,
            'max_order' => 300,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Biko',
            'description' => 'Sweet sticky rice cake with coconut milk',
            'price' => 20.00,
            'stock' => 100,
            'tier' => 'regular',
            'min_order' => 20,
            'max_order' => 300,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Cassava Cake',
            'description' => 'Dense and moist cassava cake with custard topping',
            'price' => 25.00,
            'stock' => 100,
            'tier' => 'regular',
            'min_order' => 20,
            'max_order' => 300,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Ube Halaya',
            'description' => 'Rich and creamy purple yam dessert',
            'price' => 30.00,
            'stock' => 100,
            'tier' => 'regular',
            'min_order' => 20,
            'max_order' => 300,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Special Bilao',
            'description' => 'Assorted kakanin in bilao, perfect for parties',
            'price' => 250.00,
            'stock' => 50,
            'tier' => 'premium',
            'min_order' => 1,
            'max_order' => 10,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ],
        [
            'name' => 'Special Biko Bilao',
            'description' => 'Premium biko bilao with extra latik',
            'price' => 250.00,
            'stock' => 50,
            'tier' => 'premium',
            'min_order' => 1,
            'max_order' => 10,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]
    ];
    
    $insertResult = $collection->insertMany($products);
    echo "<p style='color:green'>✅ Added " . $insertResult->getInsertedCount() . " products!</p>";
    
    // Show all products
    echo "<h3>Products in Database:</h3>";
    echo "<ul>";
    $allProducts = $collection->find();
    foreach ($allProducts as $product) {
        echo "<li>" . $product['name'] . " - ₱" . $product['price'] . " (Stock: " . $product['stock'] . ")</li>";
    }
    echo "</ul>";
    
    echo "<p><a href='/'>Go back to homepage</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}