<?php
// Include database configuration
require_once __DIR__ . '/../config/database.php';

try {
    // Create a new PDO instance
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

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `" . DB_NAME . "`");

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
            `created_by` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `created_by` (`created_by`),
            CONSTRAINT `fk_products_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Create loans table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `loans` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `borrower_name` VARCHAR(100) NOT NULL,
            `borrower_contact` VARCHAR(50) DEFAULT NULL,
            `borrower_address` TEXT DEFAULT NULL,
            `product_id` INT UNSIGNED NOT NULL,
            `quantity` INT NOT NULL DEFAULT 1,
            `amount` DECIMAL(10, 2) NOT NULL,
            `interest_rate` DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
            `total_amount` DECIMAL(10, 2) NOT NULL,
            `paid_amount` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            `due_date` DATE NOT NULL,
            `status` ENUM('pending', 'active', 'completed', 'defaulted') NOT NULL DEFAULT 'pending',
            `created_by` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `product_id` (`product_id`),
            KEY `created_by` (`created_by`),
            CONSTRAINT `fk_loans_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
            CONSTRAINT `fk_loans_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Create payments table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `payments` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `loan_id` INT UNSIGNED NOT NULL,
            `amount` DECIMAL(10, 2) NOT NULL,
            `payment_date` DATE NOT NULL,
            `payment_method` ENUM('cash', 'bank_transfer', 'mobile_money') NOT NULL DEFAULT 'cash',
            `reference_number` VARCHAR(100) DEFAULT NULL,
            `notes` TEXT DEFAULT NULL,
            `created_by` INT UNSIGNED NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `loan_id` (`loan_id`),
            KEY `created_by` (`created_by`),
            CONSTRAINT `fk_payments_loan` FOREIGN KEY (`loan_id`) REFERENCES `loans` (`id`) ON DELETE CASCADE,
            CONSTRAINT `fk_payments_created_by` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Create an admin user if not exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'admin' LIMIT 1");
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("
            INSERT INTO users (username, email, password, full_name, is_admin) 
            VALUES ('admin', 'admin@example.com', ?, 'Administrator', 1)
        ")->execute([$hashedPassword]);
        
        echo "<p>Created admin user with username: <strong>admin</strong> and password: <strong>admin123</strong></p>";
        echo "<p class='text-danger'>IMPORTANT: Please change the default admin password after login!</p>";
    }

    echo "<p>Database setup completed successfully!</p>";
    echo "<p><a href='/nesrah/public/login.php' class='btn btn-primary'>Go to Login Page</a></p>";

} catch (PDOException $e) {
    die("<div class='alert alert-danger'><strong>Database Error:</strong> " . $e->getMessage() . "</div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .alert { margin-top: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Setup</h1>
        <div class="alert alert-success">
            <?= isset($e) ? $e->getMessage() : 'Database tables created successfully!' ?>
        </div>
    </div>
</body>
</html>
