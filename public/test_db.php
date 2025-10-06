<?php
// Include database configuration
require_once __DIR__ . '/../app/config/database.php';

try {
    // Test database connection
    $pdo = getDBConnection();
    echo "Database connection successful!<br>";
    
    // Check if tables exist
    $tables = ['users', 'products', 'loans', 'payments'];
    $missingTables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() === 0) {
            $missingTables[] = $table;
        }
    }
    
    if (empty($missingTables)) {
        echo "All required tables exist.<br>";
    } else {
        echo "Missing tables: " . implode(', ', $missingTables) . "<br>";
    }
    
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Test if we can access the database
$stmt = $pdo->query("SELECT DATABASE() as db");
$db = $stmt->fetch();
echo "Connected to database: " . $db['db'] . "<br>";

// Show PHP info
phpinfo();
