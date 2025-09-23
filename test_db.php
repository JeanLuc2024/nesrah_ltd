<?php
/**
 * Simple Database Connection Test
 */

echo "<h1>Database Connection Test</h1>";

try {
    require_once __DIR__ . '/config/database.php';
    
    echo "<p style='color: green;'>✅ Database connection successful</p>";
    
    // Test basic queries
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $result = $stmt->fetch();
    echo "<p>Users in database: " . $result['count'] . "</p>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM inventory");
    $result = $stmt->fetch();
    echo "<p>Inventory items: " . $result['count'] . "</p>";
    
    $stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'admin'");
    $result = $stmt->fetch();
    echo "<p>Admin users: " . $result['count'] . "</p>";
    
    if ($result['count'] > 0) {
        $stmt = $db->query("SELECT username, email, status FROM users WHERE role = 'admin' LIMIT 1");
        $admin = $stmt->fetch();
        echo "<p>Admin user: " . $admin['username'] . " (" . $admin['email'] . ") - Status: " . $admin['status'] . "</p>";
    }
    
    echo "<p style='color: green;'>✅ All database tests passed</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}
?>
