<?php
/**
 * Get the next daily order number
 * This will reset to 1 every day at midnight
 */
function getNextDailyOrderNumber($pdo) {
    $today = date('Y-m-d');
    
    try {
        // Check if counter exists for today
        $stmt = $pdo->prepare("SELECT order_counter FROM tbl_daily_counters WHERE counter_date = ?");
        $stmt->execute([$today]);
        $counter = $stmt->fetchColumn();
        
        if ($counter === false) {
            // First order today - insert with value 1
            $stmt = $pdo->prepare("INSERT INTO tbl_daily_counters (counter_date, order_counter, inventory_log_counter) VALUES (?, 1, 1)");
            $stmt->execute([$today]);
            return 1;
        } else {
            // Increment existing counter
            $new_counter = $counter + 1;
            $stmt = $pdo->prepare("UPDATE tbl_daily_counters SET order_counter = ?, last_reset_at = NOW() WHERE counter_date = ?");
            $stmt->execute([$new_counter, $today]);
            return $new_counter;
        }
    } catch (Exception $e) {
        error_log("Daily counter error: " . $e->getMessage());
        // Fallback - return a number to prevent blocking orders
        return rand(1000, 9999);
    }
}

/**
 * Get the next daily inventory log number
 */
function getNextDailyInventoryNumber($pdo) {
    $today = date('Y-m-d');
    
    try {
        $stmt = $pdo->prepare("SELECT inventory_log_counter FROM tbl_daily_counters WHERE counter_date = ?");
        $stmt->execute([$today]);
        $counter = $stmt->fetchColumn();
        
        if ($counter === false) {
            $stmt = $pdo->prepare("INSERT INTO tbl_daily_counters (counter_date, order_counter, inventory_log_counter) VALUES (?, 1, 1)");
            $stmt->execute([$today]);
            return 1;
        } else {
            $new_counter = $counter + 1;
            $stmt = $pdo->prepare("UPDATE tbl_daily_counters SET inventory_log_counter = ? WHERE counter_date = ?");
            $stmt->execute([$new_counter, $today]);
            return $new_counter;
        }
    } catch (Exception $e) {
        error_log("Daily inventory counter error: " . $e->getMessage());
        return 1;
    }
}

/**
 * Format order number with date
 * Example: ORD-2026-02-28-0001
 */
function formatDailyOrderNumber($pdo, $order_id = null) {
    $daily_number = getNextDailyOrderNumber($pdo);
    $date = date('Y-m-d');
    
    if ($order_id) {
        // Store the mapping between daily number and actual order ID
        if (!isset($_SESSION['daily_order_map'])) {
            $_SESSION['daily_order_map'] = [];
        }
        if (!isset($_SESSION['daily_order_map'][$date])) {
            $_SESSION['daily_order_map'][$date] = [];
        }
        $_SESSION['daily_order_map'][$date][$daily_number] = $order_id;
    }
    
    return "ORD-{$date}-" . str_pad($daily_number, 4, '0', STR_PAD_LEFT);
}

/**
 * Get original order ID from daily order number
 */
function getOrderIdFromDailyNumber($pdo, $daily_number, $date = null) {
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    // Check session first (for today's orders)
    if (isset($_SESSION['daily_order_map'][$date][$daily_number])) {
        return $_SESSION['daily_order_map'][$date][$daily_number];
    }
    
    // Check archive for past orders
    $stmt = $pdo->prepare("SELECT original_order_id FROM tbl_daily_orders_archive WHERE archive_date = ? AND daily_order_number = ?");
    $stmt->execute([$date, $daily_number]);
    return $stmt->fetchColumn();
}

/**
 * Reset daily counter to 1 (admin function)
 */
function resetDailyCounter($pdo) {
    $today = date('Y-m-d');
    
    $stmt = $pdo->prepare("
        INSERT INTO tbl_daily_counters (counter_date, order_counter, inventory_log_counter)
        VALUES (?, 1, 1)
        ON DUPLICATE KEY UPDATE 
            order_counter = 1,
            inventory_log_counter = 1,
            last_reset_at = NOW()
    ");
    $stmt->execute([$today]);
    
    // Clear session mapping
    if (isset($_SESSION['daily_order_map'][$today])) {
        unset($_SESSION['daily_order_map'][$today]);
    }
    
    return true;
}
?>