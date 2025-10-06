<?php
// Include database configuration
require_once __DIR__ . '/../app/config/database.php';

try {
    // Create a new PDO instance without selecting a database
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
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `loan_management` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `loan_management`");

    // Drop all existing tables (in case they exist)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $pdo->exec("DROP TABLE IF EXISTS `$table`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

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
        echo "<div class='alert alert-success'><strong>Success:</strong> Default admin user created (username: admin, password: admin123)</div>";
    }

    echo "<div class='alert alert-success'><strong>Success:</strong> Database has been updated successfully!</div>";
    echo "<p>You can now <a href='login.php'>login to the admin panel</a>.</p>";

} catch (PDOException $e) {
    die("<div class='alert alert-danger'><strong>Database Error:</strong> " . $e->getMessage() . "</div>");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Update</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center">Database Update</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($e)): ?>
                            <div class="alert alert-danger">
                                <strong>Error:</strong> <?php echo htmlspecialchars($e->getMessage()); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
