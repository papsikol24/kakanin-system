<?php
// Turn on errors
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Daily Counter Test</h2>";

// Check if file exists
$file_path = __DIR__ . '/../includes/daily_counter.php';
echo "<p>Checking: " . $file_path . "</p>";

if (file_exists($file_path)) {
    echo "<p style='color:green'>✅ File exists</p>";
    
    // Check if readable
    if (is_readable($file_path)) {
        echo "<p style='color:green'>✅ File is readable</p>";
        
        // Include the file
        echo "<p>Including file...</p>";
        include $file_path;
        
        // Check if function exists
        if (function_exists('getNextDailyOrderNumber')) {
            echo "<p style='color:green'>✅ Function exists after include</p>";
        } else {
            echo "<p style='color:red'>❌ Function still not found after include</p>";
            
            // Show file contents first 20 lines
            echo "<p>First 20 lines of file:</p>";
            echo "<pre>";
            $lines = file($file_path);
            for ($i = 0; $i < min(20, count($lines)); $i++) {
                echo htmlspecialchars($lines[$i]);
            }
            echo "</pre>";
        }
    } else {
        echo "<p style='color:red'>❌ File is not readable</p>";
    }
} else {
    echo "<p style='color:red'>❌ File does not exist</p>";
}
?>