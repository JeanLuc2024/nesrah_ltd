<?php
/**
 * NESRAH GROUP Management System - Admin Functionality Test
 * This script tests all admin-specific functionality
 */

// Start session and simulate admin login
session_start();
require_once __DIR__ . '/config/config.php';

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['user_role'] = 'admin';
$_SESSION['user_name'] = 'System Administrator';

echo "<h1>Admin Functionality Test</h1>";
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

function runAdminTest($test_name, $test_function) {
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

// Test 1: Admin Role Check
runAdminTest("Admin Role Check", function() {
    if (!isAdmin()) {
        return ['success' => false, 'message' => 'isAdmin() function returned false'];
    }
    
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'isLoggedIn() function returned false'];
    }
    
    return ['success' => true, 'message' => 'Admin role check passed'];
});

// Test 2: Employee Management
runAdminTest("Employee Management", function() {
    global $db;
    
    // Test creating a test employee
    $test_username = 'test_employee_' . time();
    $test_email = 'test' . time() . '@example.com';
    $hashed_password = password_hash('test123', PASSWORD_DEFAULT);
    
    $query = "INSERT INTO users (first_name, last_name, username, email, password, role, status) 
             VALUES ('Test', 'Employee', :username, :email, :password, 'employee', 'pending')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $test_username);
    $stmt->bindParam(':email', $test_email);
    $stmt->bindParam(':password', $hashed_password);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to create test employee'];
    }
    
    $employee_id = $db->lastInsertId();
    
    // Test approving employee
    $query = "UPDATE users SET status = 'active' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $employee_id);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to approve test employee'];
    }
    
    // Clean up
    $query = "DELETE FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $employee_id);
    $stmt->execute();
    
    return ['success' => true, 'message' => 'Employee management functions work correctly'];
});

// Test 3: Inventory Management
runAdminTest("Inventory Management", function() {
    global $db;
    
    // Test creating inventory item
    $item_code = 'TEST' . time();
    $item_name = 'Test Item ' . time();
    $description = 'Test description';
    $category = 'Test Category';
    $unit_price = 99.99;
    $current_stock = 100;
    $reorder_level = 10;
    
    $query = "INSERT INTO inventory (item_code, item_name, description, category, unit_price, current_stock, reorder_level) 
             VALUES (:item_code, :item_name, :description, :category, :unit_price, :current_stock, :reorder_level)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':item_code', $item_code);
    $stmt->bindParam(':item_name', $item_name);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':category', $category);
    $stmt->bindParam(':unit_price', $unit_price);
    $stmt->bindParam(':current_stock', $current_stock);
    $stmt->bindParam(':reorder_level', $reorder_level);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to create test inventory item'];
    }
    
    $item_id = $db->lastInsertId();
    
    // Test updating inventory
    $new_stock = 150;
    $query = "UPDATE inventory SET current_stock = :new_stock WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':new_stock', $new_stock);
    $stmt->bindParam(':id', $item_id);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to update test inventory item'];
    }
    
    // Clean up
    $query = "DELETE FROM inventory WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $item_id);
    $stmt->execute();
    
    return ['success' => true, 'message' => 'Inventory management functions work correctly'];
});

// Test 4: Task Management
runAdminTest("Task Management", function() {
    global $db;
    
    // Get first employee for testing
    $stmt = $db->query("SELECT id FROM users WHERE role = 'employee' LIMIT 1");
    $employee = $stmt->fetch();
    
    if (!$employee) {
        return ['success' => false, 'message' => 'No employee found for task testing'];
    }
    
    // Test creating task
    $title = 'Test Task ' . time();
    $description = 'Test task description';
    $assigned_to = $employee['id'];
    $assigned_by = 1; // Admin
    $priority = 'medium';
    $due_date = date('Y-m-d', strtotime('+7 days'));
    
    $query = "INSERT INTO tasks (title, description, assigned_to, assigned_by, priority, due_date) 
             VALUES (:title, :description, :assigned_to, :assigned_by, :priority, :due_date)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':description', $description);
    $stmt->bindParam(':assigned_to', $assigned_to);
    $stmt->bindParam(':assigned_by', $assigned_by);
    $stmt->bindParam(':priority', $priority);
    $stmt->bindParam(':due_date', $due_date);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to create test task'];
    }
    
    $task_id = $db->lastInsertId();
    
    // Test updating task status
    $query = "UPDATE tasks SET status = 'in_progress' WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $task_id);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to update test task'];
    }
    
    // Clean up
    $query = "DELETE FROM tasks WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $task_id);
    $stmt->execute();
    
    return ['success' => true, 'message' => 'Task management functions work correctly'];
});

// Test 5: Stock Allocation
runAdminTest("Stock Allocation", function() {
    global $db;
    
    // Get first inventory item and employee
    $stmt = $db->query("SELECT id FROM inventory LIMIT 1");
    $item = $stmt->fetch();
    
    $stmt = $db->query("SELECT id FROM users WHERE role = 'employee' LIMIT 1");
    $employee = $stmt->fetch();
    
    if (!$item || !$employee) {
        return ['success' => false, 'message' => 'No inventory item or employee found for testing'];
    }
    
    // Test creating stock allocation
    $item_id = $item['id'];
    $user_id = $employee['id'];
    $allocated_quantity = 10;
    $remaining_quantity = 10;
    $allocated_by = 1; // Admin
    
    $query = "INSERT INTO stock_allocations (item_id, user_id, allocated_quantity, remaining_quantity, allocated_by) 
             VALUES (:item_id, :user_id, :allocated_quantity, :remaining_quantity, :allocated_by)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':allocated_quantity', $allocated_quantity);
    $stmt->bindParam(':remaining_quantity', $remaining_quantity);
    $stmt->bindParam(':allocated_by', $allocated_by);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to create test stock allocation'];
    }
    
    $allocation_id = $db->lastInsertId();
    
    // Clean up
    $query = "DELETE FROM stock_allocations WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $allocation_id);
    $stmt->execute();
    
    return ['success' => true, 'message' => 'Stock allocation functions work correctly'];
});

// Test 6: Sales Management
runAdminTest("Sales Management", function() {
    global $db;
    
    // Get first inventory item and employee
    $stmt = $db->query("SELECT id FROM inventory LIMIT 1");
    $item = $stmt->fetch();
    
    $stmt = $db->query("SELECT id FROM users WHERE role = 'employee' LIMIT 1");
    $employee = $stmt->fetch();
    
    if (!$item || !$employee) {
        return ['success' => false, 'message' => 'No inventory item or employee found for testing'];
    }
    
    // Test creating sale
    $user_id = $employee['id'];
    $item_id = $item['id'];
    $customer_name = 'Test Customer';
    $customer_phone = '123-456-7890';
    $customer_email = 'test@example.com';
    $quantity = 2;
    $unit_price = 50.00;
    $total_amount = 100.00;
    $payment_method = 'cash';
    $notes = 'Test sale';
    
    $query = "INSERT INTO sales (user_id, item_id, customer_name, customer_phone, customer_email, quantity, unit_price, total_amount, payment_method, notes) 
             VALUES (:user_id, :item_id, :customer_name, :customer_phone, :customer_email, :quantity, :unit_price, :total_amount, :payment_method, :notes)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->bindParam(':customer_name', $customer_name);
    $stmt->bindParam(':customer_phone', $customer_phone);
    $stmt->bindParam(':customer_email', $customer_email);
    $stmt->bindParam(':quantity', $quantity);
    $stmt->bindParam(':unit_price', $unit_price);
    $stmt->bindParam(':total_amount', $total_amount);
    $stmt->bindParam(':payment_method', $payment_method);
    $stmt->bindParam(':notes', $notes);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to create test sale'];
    }
    
    $sale_id = $db->lastInsertId();
    
    // Clean up
    $query = "DELETE FROM sales WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $sale_id);
    $stmt->execute();
    
    return ['success' => true, 'message' => 'Sales management functions work correctly'];
});

// Test 7: Reports Generation
runAdminTest("Reports Generation", function() {
    global $db;
    
    // Test sales report query
    $query = "SELECT 
                COUNT(*) as total_sales,
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COALESCE(AVG(total_amount), 0) as average_sale
              FROM sales 
              WHERE DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    $stmt = $db->prepare($query);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to execute sales report query'];
    }
    
    $report = $stmt->fetch();
    
    if (!isset($report['total_sales'])) {
        return ['success' => false, 'message' => 'Sales report query returned invalid data'];
    }
    
    return ['success' => true, 'message' => 'Reports generation functions work correctly'];
});

// Summary
echo "<div class='test-section test-info'>";
echo "<h2>Admin Test Summary</h2>";
echo "<p><strong>Total Tests:</strong> $total_tests</p>";
echo "<p><strong>Passed:</strong> $passed_tests</p>";
echo "<p><strong>Failed:</strong> " . ($total_tests - $passed_tests) . "</p>";
echo "<p><strong>Success Rate:</strong> " . round(($passed_tests / $total_tests) * 100, 2) . "%</p>";

if ($passed_tests === $total_tests) {
    echo "<p style='color: green; font-weight: bold;'>🎉 All admin tests passed! Admin functionality is working correctly.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Some admin tests failed. Please review the issues above.</p>";
}

echo "</div>";

// Clean up session
session_destroy();
?>
