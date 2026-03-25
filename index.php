<?php
echo "<h1>Jen's Kakanin - Dockerized System</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";

// Check if MongoDB extension is loaded
if (extension_loaded('mongodb')) {
    echo "<p style='color:green'>✅ MongoDB extension is LOADED (version: " . phpversion('mongodb') . ")</p>";
} else {
    echo "<p style='color:red'>❌ MongoDB extension is NOT loaded</p>";
}

// Try to load MongoDB library
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "<p style='color:green'>✅ Composer autoloader loaded</p>";
    
    if (class_exists('MongoDB\Client')) {
        echo "<p style='color:green'>✅ MongoDB\Client class found!</p>";
    } else {
        echo "<p style='color:red'>❌ MongoDB\Client class NOT found</p>";
    }
} else {
    echo "<p style='color:red'>❌ vendor/autoload.php not found. Run composer install</p>";
}

echo "<hr>";
echo "<p>Server Time: " . date('Y-m-d H:i:s') . "</p>";