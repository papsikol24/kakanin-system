<?php
require_once 'includes/config.php';
session_start();

// Clear any existing transactions
try {
    while ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
} catch (Exception $e) {}

echo "<h2>Checkout Test</h2>";

// Test 1: Basic connection
try {
    $pdo->query("SELECT 1");
    echo "✅ Test 1: Database connection OK<br>";
} catch (Exception $e) {
    echo "❌ Test 1: Connection failed: " . $e->getMessage() . "<br>";
}

// Test 2: Transaction test
try {
    $pdo->beginTransaction();
    $pdo->query("SELECT 1");
    $pdo->commit();
    echo "✅ Test 2: Transaction test OK<br>";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Test 2: Transaction failed: " . $e->getMessage() . "<br>";
}

// Test 3: Check for active transactions
try {
    $inTrans = $pdo->inTransaction();
    echo "✅ Test 3: Active transaction check: " . ($inTrans ? 'YES' : 'NO') . "<br>";
} catch (Exception $e) {
    echo "❌ Test 3: Check failed: " . $e->getMessage() . "<br>";
}

echo "<br><a href='/checkout'>Try Checkout</a>";
?>