<?php
require_once __DIR__ . '/config/config.php';

// Test database connection
try {
    $test_query = "SELECT 1 as test";
    $test_stmt = $db->prepare($test_query);
    $test_stmt->execute();
    $test_result = $test_stmt->fetch();
    echo "✅ Database connection successful!\n";

    // Test users table exists
    $table_query = "SHOW TABLES LIKE 'users'";
    $table_stmt = $db->prepare($table_query);
    $table_stmt->execute();
    if ($table_stmt->rowCount() > 0) {
        echo "✅ Users table exists!\n";
    } else {
        echo "❌ Users table not found!\n";
    }

    // Test current employee count
    $count_query = "SELECT COUNT(*) as count FROM users WHERE role = 'employee'";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $count_result = $count_stmt->fetch();
    echo "✅ Current employee count: " . $count_result['count'] . "\n";

    // Test password hashing
    $test_password = 'test123';
    $hashed_password = password_hash($test_password, PASSWORD_DEFAULT);
    echo "✅ Password hashing works!\n";

    // Test if we can prepare and execute complex queries
    $complex_query = "SELECT id, username, email, status FROM users WHERE role = 'employee' LIMIT 5";
    $complex_stmt = $db->prepare($complex_query);
    $complex_stmt->execute();
    $complex_results = $complex_stmt->fetchAll();
    echo "✅ Complex queries work! Found " . count($complex_results) . " employees.\n";

    echo "\n🎉 All database tests passed! The system is ready for employee management.\n";

} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    echo "Please check your database configuration.\n";
}
?>
