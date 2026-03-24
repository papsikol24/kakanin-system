<?php
header('Content-Type: application/json');

try {
    require_once '../includes/config.php';
    
    // Test simple query
    $stmt = $pdo->query("SELECT 1");
    $result = $stmt->fetch();
    
    // Check for open transactions
    $inTransaction = $pdo->inTransaction();
    
    echo json_encode([
        'success' => true,
        'inTransaction' => $inTransaction,
        'message' => 'Database connection is healthy'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>