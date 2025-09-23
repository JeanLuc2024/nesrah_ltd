<?php
/**
 * NESRAH GROUP Management System - Comprehensive Test Script
 * This script tests all major functionality of the system
 */

require_once __DIR__ . '/config/config.php';

echo "<h1>NESRAH GROUP Management System - System Test</h1>";
echo "<style>
    .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .test-pass { background-color: #d4edda; border-color: #c3e6cb; }
    .test-fail { background-color: #f8d7da; border-color: #f5c6cb; }
    .test-warning { background-color: #fff3cd; border-color: #ffeaa7; }
    .test-info { background-color: #d1ecf1; border-color: #bee5eb; }
    .test-result { font-weight: bold; margin: 10px 0; }
    .test-details { margin-left: 20px; }
</style>";

$test_results = [];
$total_tests = 0;
$passed_tests = 0;

function runTest($test_name, $test_function) {
    global $total_tests, $passed_tests, $test_results;
    $total_tests++;
    
    echo "<div class='test-section'>";
    echo "<h3>$test_name</h3>";
    
    try {
        $result = $test_function();
        if ($result['success']) {
            $passed_tests++;
            echo "<div class='test-result test-pass'>✅ PASSED</div>";
        } else {
            echo "<div class='test-result test-fail'>❌ FAILED</div>";
        }
        echo "<div class='test-details'>" . $result['message'] . "</div>";
        $test_results[] = ['name' => $test_name, 'success' => $result['success'], 'message' => $result['message']];
    } catch (Exception $e) {
        echo "<div class='test-result test-fail'>❌ ERROR</div>";
        echo "<div class='test-details'>Error: " . $e->getMessage() . "</div>";
        $test_results[] = ['name' => $test_name, 'success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
    
    echo "</div>";
}

// Test 1: Database Connection
runTest("Database Connection", function() {
    global $db;
    
    if ($db === null) {
        return ['success' => false, 'message' => 'Database connection is null'];
    }
    
    try {
        $stmt = $db->query("SELECT 1");
        $result = $stmt->fetch();
        return ['success' => true, 'message' => 'Database connection successful'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
    }
});

// Test 2: Database Tables
runTest("Database Tables", function() {
    global $db;
    
    $required_tables = ['users', 'inventory', 'attendance', 'tasks', 'stock_allocations', 'stock_requests', 'sales', 'employee_communications', 'system_settings'];
    $existing_tables = [];
    $missing_tables = [];
    
    foreach ($required_tables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() > 0) {
                $existing_tables[] = $table;
            } else {
                $missing_tables[] = $table;
            }
        } catch (Exception $e) {
            $missing_tables[] = $table;
        }
    }
    
    if (count($missing_tables) > 0) {
        return ['success' => false, 'message' => 'Missing tables: ' . implode(', ', $missing_tables)];
    }
    
    return ['success' => true, 'message' => 'All required tables exist: ' . implode(', ', $existing_tables)];
});

// Test 3: Admin User
runTest("Admin User", function() {
    global $db;
    
    try {
        $stmt = $db->query("SELECT id, username, email, role, status FROM users WHERE role = 'admin'");
        $admin = $stmt->fetch();
        
        if (!$admin) {
            return ['success' => false, 'message' => 'No admin user found'];
        }
        
        if ($admin['status'] !== 'active') {
            return ['success' => false, 'message' => 'Admin user exists but is not active'];
        }
        
        return ['success' => true, 'message' => "Admin user found: {$admin['username']} ({$admin['email']})"];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error checking admin user: ' . $e->getMessage()];
    }
});

// Test 4: Sample Data
runTest("Sample Data", function() {
    global $db;
    
    $checks = [];
    
    // Check inventory items
    $stmt = $db->query("SELECT COUNT(*) as count FROM inventory");
    $inventory_count = $stmt->fetch()['count'];
    $checks[] = "Inventory items: $inventory_count";
    
    // Check system settings
    $stmt = $db->query("SELECT COUNT(*) as count FROM system_settings");
    $settings_count = $stmt->fetch()['count'];
    $checks[] = "System settings: $settings_count";
    
    if ($inventory_count > 0 && $settings_count > 0) {
        return ['success' => true, 'message' => 'Sample data loaded: ' . implode(', ', $checks)];
    } else {
        return ['success' => false, 'message' => 'Insufficient sample data: ' . implode(', ', $checks)];
    }
});

// Test 5: Login System
runTest("Login System", function() {
    global $db;
    
    // Test admin login
    $stmt = $db->prepare("SELECT id, username, password, role, status FROM users WHERE username = 'admin' AND role = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if (!$admin) {
        return ['success' => false, 'message' => 'Admin user not found for login test'];
    }
    
    // Test password verification
    if (password_verify('password', $admin['password'])) {
        return ['success' => true, 'message' => 'Admin login credentials work correctly'];
    } else {
        return ['success' => false, 'message' => 'Admin password verification failed'];
    }
});

// Test 6: File Structure
runTest("File Structure", function() {
    $required_files = [
        'config/config.php',
        'config/database.php',
        'includes/header.php',
        'includes/footer.php',
        'auth/login.php',
        'auth/register.php',
        'auth/logout.php',
        'dashboard.php',
        'employees.php',
        'inventory.php',
        'my_tasks.php',
        'attendance.php'
    ];
    
    $missing_files = [];
    $existing_files = [];
    
    foreach ($required_files as $file) {
        if (file_exists($file)) {
            $existing_files[] = $file;
        } else {
            $missing_files[] = $file;
        }
    }
    
    if (count($missing_files) > 0) {
        return ['success' => false, 'message' => 'Missing files: ' . implode(', ', $missing_files)];
    }
    
    return ['success' => true, 'message' => 'All required files exist (' . count($existing_files) . ' files)'];
});

// Test 7: JavaScript Files
runTest("JavaScript Files", function() {
    $js_files = [
        'js/jquery.min.js',
        'js/bootstrap.min.js',
        'js/custom.js',
        'js/Chart.min.js',
        'js/calendar.min.js'
    ];
    
    $missing_js = [];
    $existing_js = [];
    
    foreach ($js_files as $file) {
        if (file_exists($file)) {
            $existing_js[] = $file;
        } else {
            $missing_js[] = $file;
        }
    }
    
    if (count($missing_js) > 0) {
        return ['success' => false, 'message' => 'Missing JS files: ' . implode(', ', $missing_js)];
    }
    
    return ['success' => true, 'message' => 'All required JS files exist (' . count($existing_js) . ' files)'];
});

// Test 8: CSS Files
runTest("CSS Files", function() {
    $css_files = [
        'css/bootstrap.min.css',
        'css/custom.css',
        'style.css'
    ];
    
    $missing_css = [];
    $existing_css = [];
    
    foreach ($css_files as $file) {
        if (file_exists($file)) {
            $existing_css[] = $file;
        } else {
            $missing_css[] = $file;
        }
    }
    
    if (count($missing_css) > 0) {
        return ['success' => false, 'message' => 'Missing CSS files: ' . implode(', ', $missing_css)];
    }
    
    return ['success' => true, 'message' => 'All required CSS files exist (' . count($existing_css) . ' files)'];
});

// Test 9: PHP Syntax
runTest("PHP Syntax", function() {
    $php_files = [
        'config/config.php',
        'config/database.php',
        'includes/header.php',
        'includes/footer.php',
        'auth/login.php',
        'auth/register.php',
        'dashboard.php',
        'employees.php',
        'inventory.php',
        'my_tasks.php',
        'attendance.php'
    ];
    
    $syntax_errors = [];
    
    foreach ($php_files as $file) {
        if (file_exists($file)) {
            $output = [];
            $return_code = 0;
            exec("php -l \"$file\" 2>&1", $output, $return_code);
            
            if ($return_code !== 0) {
                $syntax_errors[] = "$file: " . implode(' ', $output);
            }
        }
    }
    
    if (count($syntax_errors) > 0) {
        return ['success' => false, 'message' => 'PHP syntax errors found: ' . implode('; ', $syntax_errors)];
    }
    
    return ['success' => true, 'message' => 'All PHP files have valid syntax'];
});

// Test 10: Session Management
runTest("Session Management", function() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Test session functions
    if (!function_exists('isLoggedIn')) {
        return ['success' => false, 'message' => 'isLoggedIn function not defined'];
    }
    
    if (!function_exists('isAdmin')) {
        return ['success' => false, 'message' => 'isAdmin function not defined'];
    }
    
    if (!function_exists('isEmployee')) {
        return ['success' => false, 'message' => 'isEmployee function not defined'];
    }
    
    return ['success' => true, 'message' => 'Session management functions are available'];
});

// Summary
echo "<div class='test-section test-info'>";
echo "<h2>Test Summary</h2>";
echo "<p><strong>Total Tests:</strong> $total_tests</p>";
echo "<p><strong>Passed:</strong> $passed_tests</p>";
echo "<p><strong>Failed:</strong> " . ($total_tests - $passed_tests) . "</p>";
echo "<p><strong>Success Rate:</strong> " . round(($passed_tests / $total_tests) * 100, 2) . "%</p>";

if ($passed_tests === $total_tests) {
    echo "<p style='color: green; font-weight: bold;'>🎉 All tests passed! System is ready for use.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Some tests failed. Please review the issues above.</p>";
}

echo "</div>";

// Detailed Results
echo "<div class='test-section'>";
echo "<h2>Detailed Test Results</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Test Name</th><th>Status</th><th>Message</th></tr>";

foreach ($test_results as $result) {
    $status = $result['success'] ? '✅ PASS' : '❌ FAIL';
    $color = $result['success'] ? 'green' : 'red';
    echo "<tr>";
    echo "<td>{$result['name']}</td>";
    echo "<td style='color: $color; font-weight: bold;'>$status</td>";
    echo "<td>{$result['message']}</td>";
    echo "</tr>";
}

echo "</table>";
echo "</div>";

// Next Steps
echo "<div class='test-section test-info'>";
echo "<h2>Next Steps</h2>";
echo "<ol>";
echo "<li>If all tests passed, you can start using the system</li>";
echo "<li>Login as admin: <a href='auth/login.php'>Login Page</a></li>";
echo "<li>Default admin credentials: admin / password</li>";
echo "<li>Register new employees: <a href='auth/register.php'>Register Page</a></li>";
echo "<li>Delete this test file for security: <code>test_system.php</code></li>";
echo "</ol>";
echo "</div>";
?>
