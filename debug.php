<?php
// File debug để kiểm tra kết nối database và cấu trúc
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Debug Information</h1>";
echo "<style>
    body { font-family: Arial; padding: 20px; background: #f5f5f5; }
    .success { color: green; }
    .error { color: red; }
    .info { background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 10px 0; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
</style>";

require_once 'config/db.php';

echo "<div class='info'>";
echo "<h2>1. Kiểm tra kết nối Database</h2>";

try {
    $conn = getDBConnection();
    echo "<p class='success'>✓ Kết nối database thành công!</p>";
    echo "<p><strong>Database:</strong> " . DB_NAME . "</p>";
    echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
    echo "<p><strong>User:</strong> " . DB_USER . "</p>";
    
    echo "<h2>2. Kiểm tra các bảng có tồn tại</h2>";
    $tables = $conn->query("SHOW TABLES");
    $requiredTables = ['users', 'payments', 'subscriptions', 'subscription_plans', 
                       'affiliate_referrals', 'affiliate_withdrawals', 
                       'qna_category', 'qna', 'system_logs', 'subscription_details'];
    
    echo "<table>";
    echo "<tr><th>Tên bảng</th><th>Trạng thái</th><th>Số dòng</th></tr>";
    
    $existingTables = [];
    if ($tables) {
        while ($row = $tables->fetch_array()) {
            $tableName = $row[0];
            $existingTables[] = $tableName;
            $countResult = $conn->query("SELECT COUNT(*) as count FROM `$tableName`");
            $count = $countResult ? $countResult->fetch_assoc()['count'] : 0;
            
            $status = in_array($tableName, $requiredTables) ? 
                     "<span class='success'>✓ Cần thiết</span>" : 
                     "<span style='color: orange;'>Optional</span>";
            
            echo "<tr>";
            echo "<td>$tableName</td>";
            echo "<td>$status</td>";
            echo "<td>$count</td>";
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    echo "<h2>3. Kiểm tra bảng thiếu</h2>";
    $missingTables = array_diff($requiredTables, $existingTables);
    if (count($missingTables) > 0) {
        echo "<p class='error'>✗ Các bảng bị thiếu:</p>";
        echo "<ul>";
        foreach ($missingTables as $table) {
            echo "<li class='error'>$table</li>";
        }
        echo "</ul>";
        echo "<p><strong>Giải pháp:</strong> Vui lòng import SQL schema vào database.</p>";
    } else {
        echo "<p class='success'>✓ Tất cả các bảng cần thiết đã tồn tại!</p>";
    }
    
    echo "<h2>4. Kiểm tra dữ liệu trong bảng users</h2>";
    if (in_array('users', $existingTables)) {
        $usersCount = $conn->query("SELECT COUNT(*) as count FROM users");
        $count = $usersCount->fetch_assoc()['count'];
        echo "<p><strong>Số lượng users:</strong> $count</p>";
        
        if ($count > 0) {
            $sampleUsers = $conn->query("SELECT user_id, username, language, created_at FROM users LIMIT 5");
            echo "<table>";
            echo "<tr><th>User ID</th><th>Username</th><th>Language</th><th>Created At</th></tr>";
            while ($user = $sampleUsers->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $user['user_id'] . "</td>";
                echo "<td>" . htmlspecialchars($user['username'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($user['language'] ?? 'en') . "</td>";
                echo "<td>" . ($user['created_at'] ?? 'N/A') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>Bảng users tồn tại nhưng chưa có dữ liệu.</p>";
        }
    } else {
        echo "<p class='error'>✗ Bảng users chưa tồn tại!</p>";
    }
    
    closeDBConnection($conn);
    
} catch (Exception $e) {
    echo "<p class='error'>✗ Lỗi: " . $e->getMessage() . "</p>";
    echo "<p><strong>Vui lòng kiểm tra:</strong></p>";
    echo "<ul>";
    echo "<li>MySQL đã chạy chưa?</li>";
    echo "<li>Thông tin trong config/db.php đúng chưa?</li>";
    echo "<li>Database '" . DB_NAME . "' đã được tạo chưa?</li>";
    echo "</ul>";
}

echo "</div>";

echo "<div class='info'>";
echo "<h2>5. Thông tin PHP</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>MySQLi Extension:</strong> " . (extension_loaded('mysqli') ? '✓ Enabled' : '✗ Disabled') . "</p>";
echo "</div>";

echo "<p><a href='index.php'>← Quay lại Dashboard</a></p>";
?>

