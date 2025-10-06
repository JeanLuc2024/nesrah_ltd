<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/../app/config/database.php';

// Include custom autoloader
require_once __DIR__ . '/../app/autoload.php';

echo "<h2>System Test</h2>";

try {
    // Test database connection
    $pdo = getDBConnection();
    echo "✅ Database connection successful!<br>";
    
    // Test if tables exist
    $tables = ['users', 'products', 'loans'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "✅ All required tables exist.<br>";
    } else {
        echo "⚠️ Missing tables: " . implode(', ', $missingTables) . "<br>";
        echo "<a href='setup-tables.php' class='btn btn-primary'>Create Missing Tables</a><br>";
    }
    
    // Show tables and their row counts
    echo "<h3>Database Status</h3>";
    $result = $pdo->query("SHOW TABLES");
    echo "<table class='table'>";
    echo "<tr><th>Table</th><th>Rows</th></tr>";
    
    foreach ($result as $row) {
        $table = $row[array_key_first($row)];
        $count = $pdo->query("SELECT COUNT(*) as count FROM `$table`")->fetch()['count'];
        echo "<tr><td>$table</td><td>$count</td></tr>";
    }
    
    echo "</table>";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
    echo "<p>Please check your database configuration in <code>app/config/database.php</code></p>";
    echo "<p>Make sure MySQL is running and the database user has proper permissions.</p>";
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .table { border-collapse: collapse; width: 100%; max-width: 600px; }
    .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    .table th { background-color: #f2f2f2; }
    .btn { display: inline-block; padding: 6px 12px; margin: 5px 0; background: #007bff; color: white; text-decoration: none; border-radius: 4px; }
    .btn:hover { background: #0056b3; }
</style>

<p><a href='admin-dashboard.php'>Go to Admin Dashboard</a> | <a href='setup-tables.php'>Setup Database Tables</a></p>
