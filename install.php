<?php
/**
 * NESRAH GROUP Management System - Installation Script
 */

// Database configuration
$host = 'localhost';
$dbname = 'nesrah_group';
$username = 'root';
$password = '';

echo "<h1>NESRAH GROUP Management System - Installation</h1>";

try {
    // Connect to MySQL server
    $pdo = new PDO("mysql:host=$host", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Connected to MySQL server</p>";
    
    // Create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
    echo "<p style='color: green;'>✅ Database '$dbname' created/verified</p>";
    
    // Connect to the specific database
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if tables already exist
    $tables = ['users', 'inventory', 'attendance', 'tasks', 'stock_allocations', 'stock_requests', 'sales', 'employee_communications'];
    $existing_tables = [];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            $existing_tables[] = $table;
        }
    }
    
    if (count($existing_tables) > 0) {
        echo "<p style='color: orange;'>⚠️ Some tables already exist: " . implode(', ', $existing_tables) . "</p>";
        echo "<p>Database appears to be already set up. Skipping table creation.</p>";
    } else {
        // Read and execute the SQL file
        $sql = file_get_contents('database/nesrah_database.sql');
        
        // Split the SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        echo "<p style='color: green;'>✅ Database tables created successfully</p>";
    }
    
    // Test the installation
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "<p style='color: green;'>✅ Admin user found</p>";
    } else {
        echo "<p style='color: red;'>❌ Admin user not found</p>";
    }
    
    echo "<hr>";
    echo "<h2>Installation Complete!</h2>";
    echo "<p><strong>Next Steps:</strong></p>";
    echo "<ol>";
    echo "<li>Delete this install.php file for security</li>";
    echo "<li>Go to: <a href='auth/login.php'>Login Page</a></li>";
    echo "<li>Login with: admin / password</li>";
    echo "</ol>";
    
    echo "<p><strong>Default Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li>Username: admin</li>";
    echo "<li>Password: password</li>";
    echo "<li>Role: Administrator</li>";
    echo "</ul>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Installation failed: " . $e->getMessage() . "</p>";
    echo "<p>Please check your XAMPP configuration and try again.</p>";
}
?>
