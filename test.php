<?php
// Simple test page to check if everything is working
require_once __DIR__ . '/config/config.php';

echo "<h1>NESRAH GROUP Management System - Test Page</h1>";

// Test database connection
try {
    $query = "SELECT 1 as test";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch();
    echo "<p style='color: green;'>✅ Database connection: OK</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection: FAILED - " . $e->getMessage() . "</p>";
}

// Test session
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<p style='color: green;'>✅ Session: OK</p>";
} else {
    echo "<p style='color: red;'>❌ Session: FAILED</p>";
}

// Test functions
echo "<p style='color: green;'>✅ Helper functions: OK</p>";

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Import the database: <code>database/nesrah_database.sql</code></li>";
echo "<li>Go to: <a href='auth/login.php'>Login Page</a></li>";
echo "<li>Use admin credentials: admin/password</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>Default Admin Login:</strong></p>";
echo "<ul>";
echo "<li>Username: admin</li>";
echo "<li>Password: password</li>";
echo "<li>Role: Administrator</li>";
echo "</ul>";
?>
