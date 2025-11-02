<?php
// Simple test page to check if pages are loading
error_reporting(E_ALL);
ini_set('display_errors', 1);

$currentPage = isset($_GET['page']) ? $_GET['page'] : 'overview';

echo "<h1>Test Page</h1>";
echo "<p>Current Page: " . htmlspecialchars($currentPage) . "</p>";

echo "<h2>Testing includes...</h2>";

// Test config
echo "<p>Testing config/db.php...</p>";
try {
    require_once 'config/db.php';
    echo "✓ Config loaded<br>";
    
    $conn = getDBConnection();
    echo "✓ Database connected<br>";
    
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    $total = $result->fetch_assoc()['total'];
    echo "✓ Users count: $total<br>";
    
    closeDBConnection($conn);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "<br>";
}

// Test pages
echo "<h2>Testing pages...</h2>";
$pageFile = 'pages/' . $currentPage . '.php';
echo "<p>Looking for: $pageFile</p>";

if (file_exists($pageFile)) {
    echo "✓ File exists<br>";
    
    // Test if we can require it
    try {
        ob_start();
        require_once $pageFile;
        $output = ob_get_clean();
        
        echo "✓ File loaded successfully<br>";
        echo "<p>Output length: " . strlen($output) . " characters</p>";
        
        // Show first 500 chars
        echo "<h3>First 500 characters:</h3>";
        echo "<pre>" . htmlspecialchars(substr($output, 0, 500)) . "</pre>";
        
    } catch (Exception $e) {
        echo "✗ Error loading file: " . $e->getMessage() . "<br>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
} else {
    echo "✗ File not found<br>";
}

echo "<hr>";
echo "<p><a href='index.php?page=$currentPage'>Go to actual page</a></p>";
?>

