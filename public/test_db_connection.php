<?php
/**
 * Test Database Connection
 */

require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../config/Config.php';

echo "<h1>Database Connection Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    pre { background: #f5f5f5; padding: 10px; border-radius: 3px; }
</style>";

echo "<h2>Database Configuration</h2>";
$dbConfig = Config::getDatabaseConfig();
echo "<p><strong>Host:</strong> " . $dbConfig['host'] . "</p>";
echo "<p><strong>Port:</strong> " . $dbConfig['port'] . "</p>";
echo "<p><strong>Database:</strong> " . $dbConfig['database'] . "</p>";
echo "<p><strong>Username:</strong> " . $dbConfig['username'] . "</p>";
echo "<p><strong>Password:</strong> " . (empty($dbConfig['password']) ? 'Empty' : 'Set (hidden)') . "</p>";

echo "<h2>Connection Test</h2>";

try {
    $db = Database::getInstance();
    $connection = $db->getConnection();
    
    if ($connection) {
        echo "<div class='success'>";
        echo "<p>✅ Database connection successful!</p>";
        echo "</div>";
        
        // Test a simple query
        echo "<h3>Query Test</h3>";
        $result = $connection->query("SELECT 1 as test");
        if ($result) {
            echo "<div class='success'>";
            echo "<p>✅ Query execution successful!</p>";
            echo "</div>";
            
            // Test users table
            echo "<h3>Users Table Test</h3>";
            $result = $connection->query("SHOW TABLES LIKE 'users'");
            if ($result && $result->num_rows > 0) {
                echo "<div class='success'>";
                echo "<p>✅ Users table exists!</p>";
                echo "</div>";
                
                // Check users table structure
                $result = $connection->query("DESCRIBE users");
                if ($result) {
                    echo "<h4>Users Table Structure:</h4>";
                    echo "<pre>";
                    while ($row = $result->fetch_assoc()) {
                        echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Key'] . "\n";
                    }
                    echo "</pre>";
                }
                
                // Count users
                $result = $connection->query("SELECT COUNT(*) as count FROM users");
                if ($result) {
                    $row = $result->fetch_assoc();
                    echo "<p><strong>Total users:</strong> " . $row['count'] . "</p>";
                }
            } else {
                echo "<div class='error'>";
                echo "<p>❌ Users table does not exist!</p>";
                echo "</div>";
            }
        } else {
            echo "<div class='error'>";
            echo "<p>❌ Query execution failed: " . $connection->error . "</p>";
            echo "</div>";
        }
    } else {
        echo "<div class='error'>";
        echo "<p>❌ Database connection failed!</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>";
    echo "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<p><em>Test completed at: " . date('Y-m-d H:i:s') . "</em></p>";
?>
