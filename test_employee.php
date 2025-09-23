<?php
/**
 * NESRAH GROUP Management System - Employee Functionality Test
 * This script tests all employee-specific functionality
 */

// Start session and simulate employee login
session_start();
require_once __DIR__ . '/config/config.php';

// Create a test employee first
$test_username = 'test_emp_' . time();
$test_email = 'testemp' . time() . '@example.com';
$hashed_password = password_hash('test123', PASSWORD_DEFAULT);

try {
    $query = "INSERT INTO users (first_name, last_name, username, email, password, role, status) 
             VALUES ('Test', 'Employee', :username, :email, :password, 'employee', 'active')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $test_username);
    $stmt->bindParam(':email', $test_email);
    $stmt->bindParam(':password', $hashed_password);
    $stmt->execute();
    
    $employee_id = $db->lastInsertId();
    
    // Simulate employee login
    $_SESSION['user_id'] = $employee_id;
    $_SESSION['username'] = $test_username;
    $_SESSION['user_role'] = 'employee';
    $_SESSION['user_name'] = 'Test Employee';
    
} catch (Exception $e) {
    echo "<h1>Employee Functionality Test</h1>";
    echo "<p style='color: red;'>Failed to create test employee: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h1>Employee Functionality Test</h1>";
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

function runEmployeeTest($test_name, $test_function) {
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

// Test 1: Employee Role Check
runEmployeeTest("Employee Role Check", function() {
    if (!isEmployee()) {
        return ['success' => false, 'message' => 'isEmployee() function returned false'];
    }
    
    if (!isLoggedIn()) {
        return ['success' => false, 'message' => 'isLoggedIn() function returned false'];
    }
    
    if (isAdmin()) {
        return ['success' => false, 'message' => 'isAdmin() function returned true for employee'];
    }
    
    return ['success' => true, 'message' => 'Employee role check passed'];
});

// Test 2: Attendance Management
runEmployeeTest("Attendance Management", function() {
    global $db, $employee_id;
    
    $work_date = date('Y-m-d');
    
    // Test check-in
    $query = "INSERT INTO attendance (user_id, check_in, work_date, status) VALUES (:user_id, NOW(), :work_date, 'present')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $employee_id);
    $stmt->bindParam(':work_date', $work_date);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to check in'];
    }
    
    $attendance_id = $db->lastInsertId();
    
    // Test check-out
    $query = "UPDATE attendance SET check_out = NOW(), total_hours = 8.0 WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $attendance_id);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to check out'];
    }
    
    // Clean up
    $query = "DELETE FROM attendance WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $attendance_id);
    $stmt->execute();
    
    return ['success' => true, 'message' => 'Attendance management functions work correctly'];
});

// Test 3: Task Management
runEmployeeTest("Task Management", function() {
    global $db, $employee_id;
    
    // Create a test task assigned to this employee
    $title = 'Test Employee Task ' . time();
    $description = 'Test task description for employee';
    $assigned_to = $employee_id;
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
    
    // Test updating task status (employee can update their own tasks)
    $query = "UPDATE tasks SET status = 'in_progress' WHERE id = :id AND assigned_to = :assigned_to";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $task_id);
    $stmt->bindParam(':assigned_to', $assigned_to);
    
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

// Test 4: Stock Request
runEmployeeTest("Stock Request", function() {
    global $db, $employee_id;
    
    // Get first inventory item
    $stmt = $db->query("SELECT id FROM inventory LIMIT 1");
    $item = $stmt->fetch();
    
    if (!$item) {
        return ['success' => false, 'message' => 'No inventory item found for testing'];
    }
    
    // Test creating stock request
    $item_id = $item['id'];
    $requested_quantity = 5;
    $reason = 'Test stock request';
    
    $query = "INSERT INTO stock_requests (item_id, user_id, requested_quantity, reason) 
             VALUES (:item_id, :user_id, :requested_quantity, :reason)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->bindParam(':user_id', $employee_id);
    $stmt->bindParam(':requested_quantity', $requested_quantity);
    $stmt->bindParam(':reason', $reason);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to create test stock request'];
    }
    
    $request_id = $db->lastInsertId();
    
    // Clean up
    $query = "DELETE FROM stock_requests WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $request_id);
    $stmt->execute();
    
    return ['success' => true, 'message' => 'Stock request functions work correctly'];
});

// Test 5: Sales Recording
runEmployeeTest("Sales Recording", function() {
    global $db, $employee_id;
    
    // Get first inventory item
    $stmt = $db->query("SELECT id FROM inventory LIMIT 1");
    $item = $stmt->fetch();
    
    if (!$item) {
        return ['success' => false, 'message' => 'No inventory item found for testing'];
    }
    
    // First create a stock allocation for the employee
    $item_id = $item['id'];
    $allocated_quantity = 10;
    $remaining_quantity = 10;
    $allocated_by = 1; // Admin
    
    $query = "INSERT INTO stock_allocations (item_id, user_id, allocated_quantity, remaining_quantity, allocated_by) 
             VALUES (:item_id, :user_id, :allocated_quantity, :remaining_quantity, :allocated_by)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->bindParam(':user_id', $employee_id);
    $stmt->bindParam(':allocated_quantity', $allocated_quantity);
    $stmt->bindParam(':remaining_quantity', $remaining_quantity);
    $stmt->bindParam(':allocated_by', $allocated_by);
    $stmt->execute();
    
    $allocation_id = $db->lastInsertId();
    
    // Test creating sale
    $customer_name = 'Test Customer';
    $customer_phone = '123-456-7890';
    $customer_email = 'test@example.com';
    $quantity = 2;
    $unit_price = 50.00;
    $total_amount = 100.00;
    $payment_method = 'cash';
    $notes = 'Test sale by employee';
    
    $query = "INSERT INTO sales (user_id, item_id, customer_name, customer_phone, customer_email, quantity, unit_price, total_amount, payment_method, notes) 
             VALUES (:user_id, :item_id, :customer_name, :customer_phone, :customer_email, :quantity, :unit_price, :total_amount, :payment_method, :notes)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $employee_id);
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
    
    $query = "DELETE FROM stock_allocations WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $allocation_id);
    $stmt->execute();
    
    return ['success' => true, 'message' => 'Sales recording functions work correctly'];
});

// Test 6: Profile Management
runEmployeeTest("Profile Management", function() {
    global $db, $employee_id;
    
    // Test updating profile
    $new_first_name = 'Updated';
    $new_last_name = 'Employee';
    $new_email = 'updated' . time() . '@example.com';
    $new_phone = '987-654-3210';
    $new_address = '123 Test Street';
    
    $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, address = :address WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':first_name', $new_first_name);
    $stmt->bindParam(':last_name', $new_last_name);
    $stmt->bindParam(':email', $new_email);
    $stmt->bindParam(':phone', $new_phone);
    $stmt->bindParam(':address', $new_address);
    $stmt->bindParam(':id', $employee_id);
    
    if (!$stmt->execute()) {
        return ['success' => false, 'message' => 'Failed to update profile'];
    }
    
    // Verify update
    $query = "SELECT first_name, last_name, email, phone, address FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $employee_id);
    $stmt->execute();
    $user = $stmt->fetch();
    
    if ($user['first_name'] !== $new_first_name || $user['email'] !== $new_email) {
        return ['success' => false, 'message' => 'Profile update verification failed'];
    }
    
    return ['success' => true, 'message' => 'Profile management functions work correctly'];
});

// Test 7: My Stock View
runEmployeeTest("My Stock View", function() {
    global $db, $employee_id;
    
    // Create a stock allocation for testing
    $stmt = $db->query("SELECT id FROM inventory LIMIT 1");
    $item = $stmt->fetch();
    
    if (!$item) {
        return ['success' => false, 'message' => 'No inventory item found for testing'];
    }
    
    $item_id = $item['id'];
    $allocated_quantity = 5;
    $remaining_quantity = 5;
    $allocated_by = 1; // Admin
    
    $query = "INSERT INTO stock_allocations (item_id, user_id, allocated_quantity, remaining_quantity, allocated_by) 
             VALUES (:item_id, :user_id, :allocated_quantity, :remaining_quantity, :allocated_by)";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':item_id', $item_id);
    $stmt->bindParam(':user_id', $employee_id);
    $stmt->bindParam(':allocated_quantity', $allocated_quantity);
    $stmt->bindParam(':remaining_quantity', $remaining_quantity);
    $stmt->bindParam(':allocated_by', $allocated_by);
    $stmt->execute();
    
    $allocation_id = $db->lastInsertId();
    
    // Test querying my stock
    $query = "SELECT sa.*, i.item_name, i.item_code, i.unit_price
              FROM stock_allocations sa 
              JOIN inventory i ON sa.item_id = i.id 
              WHERE sa.user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $employee_id);
    $stmt->execute();
    $my_allocations = $stmt->fetchAll();
    
    if (count($my_allocations) === 0) {
        return ['success' => false, 'message' => 'Failed to retrieve my stock allocations'];
    }
    
    // Clean up
    $query = "DELETE FROM stock_allocations WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $allocation_id);
    $stmt->execute();
    
    return ['success' => true, 'message' => 'My stock view functions work correctly'];
});

// Summary
echo "<div class='test-section test-info'>";
echo "<h2>Employee Test Summary</h2>";
echo "<p><strong>Total Tests:</strong> $total_tests</p>";
echo "<p><strong>Passed:</strong> $passed_tests</p>";
echo "<p><strong>Failed:</strong> " . ($total_tests - $passed_tests) . "</p>";
echo "<p><strong>Success Rate:</strong> " . round(($passed_tests / $total_tests) * 100, 2) . "%</p>";

if ($passed_tests === $total_tests) {
    echo "<p style='color: green; font-weight: bold;'>🎉 All employee tests passed! Employee functionality is working correctly.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠️ Some employee tests failed. Please review the issues above.</p>";
}

echo "</div>";

// Clean up
$query = "DELETE FROM users WHERE id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $employee_id);
$stmt->execute();

session_destroy();
?>
