<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database configuration
require_once __DIR__ . '/../app/config/database.php';

// Function to create tables
function createTables($pdo) {
    try {
        // Create users table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `username` VARCHAR(50) NOT NULL,
                `email` VARCHAR(100) NOT NULL,
                `password` VARCHAR(255) NOT NULL,
                `full_name` VARCHAR(100) DEFAULT NULL,
                `is_admin` TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `username` (`username`),
                UNIQUE KEY `email` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create products table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `products` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `name` VARCHAR(100) NOT NULL,
                `description` TEXT DEFAULT NULL,
                `cost` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                `selling_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
                `quantity` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create loans table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `loans` (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `borrower_name` VARCHAR(100) NOT NULL,
                `borrower_contact` VARCHAR(50) DEFAULT NULL,
                `product_id` INT UNSIGNED DEFAULT NULL,
                `product_name` VARCHAR(100) NOT NULL,
                `quantity` INT NOT NULL DEFAULT 1,
                `amount` DECIMAL(10, 2) NOT NULL,
                `due_date` DATE NOT NULL,
                `status` ENUM('pending', 'active', 'completed', 'defaulted') NOT NULL DEFAULT 'pending',
                `notes` TEXT DEFAULT NULL,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `product_id` (`product_id`),
                CONSTRAINT `fk_loans_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        // Create default admin user if not exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->prepare("
                INSERT INTO users (username, email, password, full_name, is_admin) 
                VALUES ('admin', 'admin@example.com', ?, 'Administrator', 1)
            ")->execute([$hashedPassword]);
            echo "✅ Created default admin user (username: admin, password: admin123)<br>";
        }
        
        echo "✅ Database tables created successfully!<br>";
        return true;
        
    } catch (PDOException $e) {
        echo "❌ Error creating tables: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Main execution
echo "<h2>Database Setup</h2>";

try {
    // Get database connection
    $pdo = getDBConnection();
    echo "✅ Connected to database successfully!<br>";
    
    // Create tables
    createTables($pdo);
    
    echo "<p>Setup completed! <a href='login.php'>Go to login page</a></p>";
    
} catch (PDOException $e) {
    echo "<div style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</div>";
    echo "<p>Please check your database configuration in <code>app/config/database.php</code></p>";
    echo "<p>Make sure MySQL is running and the database user has proper permissions.</p>";
}

// Show PHP info link
echo "<p><a href='phpinfo.php'>View PHP Info</a> | <a href='test_connection.php'>Test Connection</a></p>";
?>
