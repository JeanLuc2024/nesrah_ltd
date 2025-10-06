<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/../app/config/database.php';

try {
    // Test database connection
    $pdo = getDBConnection();
    echo "✅ Database connection successful!<br>";
    
    // Test if database exists
    $stmt = $pdo->query("SELECT DATABASE() as db");
    $db = $stmt->fetch();
    echo "✅ Connected to database: " . htmlspecialchars($db['db']) . "<br>";
    
    // List tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "✅ Tables in database: " . (empty($tables) ? "No tables found" : implode(", ", $tables)) . "<br>";
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "<br>";
    echo "Trying to connect without database...<br>";
    
    try {
        // Try connecting without database
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );
        
        echo "✅ Connected to MySQL server successfully!<br>";
        echo "Trying to create database if not exists...<br>";
        
        // Try to create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `loan_management` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "✅ Database 'loan_management' created or already exists.<br>";
        
        // Now try to use the database
        $pdo->exec("USE `loan_management`");
        echo "✅ Using database 'loan_management'.<br>";
        
        // List tables again
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        echo "✅ Tables in database: " . (empty($tables) ? "No tables found" : implode(", ", $tables)) . "<br>";
        
        if (empty($tables)) {
            echo "<br>⚠️ No tables found. Please run the update_database.php script to create the required tables.";
        }
        
    } catch (PDOException $e2) {
        echo "❌ Could not connect to MySQL server: " . htmlspecialchars($e2->getMessage()) . "<br>";
        echo "<br>Please check your MySQL server is running and the database credentials in app/config/database.php";
    }
}

// Show PHP info link
echo "<br><a href='phpinfo.php'>View PHP Info</a>";
