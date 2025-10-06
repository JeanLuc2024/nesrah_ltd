<?php
session_start();
require_once 'config/database.php';

// Start output buffering
ob_start();

echo "<h2>Testing NESRAH Group Fixes</h2>";

// Test database connection
function testDatabaseConnection($db) {
    echo "<h3>1. Testing Database Connection</h3>";
    try {
        $stmt = $db->query("SELECT DATABASE()");
        $dbname = $stmt->fetchColumn();
        echo "✅ Connected to database: " . htmlspecialchars($dbname) . "<br>";
        return true;
    } catch (PDOException $e) {
        echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Test stock allocations
function testStockAllocations($db) {
    echo "<h3>2. Testing Stock Allocations</h3>";
    try {
        // Test if stock_allocations table exists and has required columns
        $stmt = $db->query("SHOW COLUMNS FROM stock_allocations LIKE 'item_id'");
        if ($stmt->rowCount() > 0) {
            echo "✅ stock_allocations table exists with required columns<br>";
            
            // Test a sample query
            $stmt = $db->query("SELECT COUNT(*) as count FROM stock_allocations LIMIT 1");
            $result = $stmt->fetch();
            echo "✅ Stock allocations query successful. Found " . $result['count'] . " records<br>";
            return true;
        } else {
            echo "❌ stock_allocations table is missing required columns<br>";
            return false;
        }
    } catch (PDOException $e) {
        echo "❌ Stock allocations test failed: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Test orders functionality
function testOrders($db) {
    echo "<h3>3. Testing Orders</h3>";
    try {
        // Test if orders table exists and has required columns
        $stmt = $db->query("SHOW COLUMNS FROM sales LIKE 'customer_name'");
        if ($stmt->rowCount() > 0) {
            echo "✅ sales table exists with required columns<br>";
            
            // Test a sample query
            $stmt = $db->query("SELECT COUNT(*) as count FROM sales LIMIT 1");
            $result = $stmt->fetch();
            echo "✅ Sales query successful. Found " . $result['count'] . " records<br>";
            return true;
        } else {
            echo "❌ sales table is missing required columns<br>";
            return false;
        }
    } catch (PDOException $e) {
        echo "❌ Orders test failed: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Test reports
function testReports($db) {
    echo "<h3>4. Testing Reports</h3>";
    try {
        // Test stock movement history
        $query = "SELECT sh.*, i.item_name, s.customer_name 
                 FROM stock_history sh
                 LEFT JOIN inventory i ON sh.item_id = i.id
                 LEFT JOIN sales s ON sh.reference_id = s.id AND sh.reference_type = 'sale'
                 ORDER BY sh.created_at DESC LIMIT 1";
        $stmt = $db->query($query);
        $result = $stmt->fetch();
        
        if ($result) {
            echo "✅ Stock movement history query successful<br>";
            if (isset($result['customer_name'])) {
                echo "✅ Customer name is being retrieved in stock movement history<br>";
            } else {
                echo "⚠️ Customer name is not available in stock movement history<br>";
            }
            return true;
        } else {
            echo "⚠️ No stock movement history found<br>";
            return true; // Not necessarily an error if no data exists
        }
    } catch (PDOException $e) {
        echo "❌ Reports test failed: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Test my_sales page
function testMySales($db, $user_id = 1) {
    echo "<h3>5. Testing My Sales</h3>";
    try {
        // Test the query that was previously failing
        $query = "SELECT s.*, i.item_name, i.item_code
                 FROM sales s 
                 JOIN inventory i ON s.item_id = i.id 
                 WHERE s.user_id = :user_id 
                 ORDER BY s.created_at DESC 
                 LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            echo "✅ My Sales query successful. Found sales records.<br>";
            return true;
        } else {
            echo "⚠️ No sales records found for user ID $user_id<br>";
            return true; // Not necessarily an error if no data exists
        }
    } catch (PDOException $e) {
        echo "❌ My Sales test failed: " . $e->getMessage() . "<br>";
        return false;
    }
}

// Run all tests
$db = $database->getConnection();
$allTestsPassed = true;

// Set a test user ID (you may need to adjust this)
$test_user_id = 1;

// Run tests
$allTestsPassed &= testDatabaseConnection($db);
$allTestsPassed &= testStockAllocations($db);
$allTestsPassed &= testOrders($db);
$allTestsPassed &= testReports($db);
$allTestsPassed &= testMySales($db, $test_user_id);

// Display final result
echo "<h2>Test Results: " . ($allTestsPassed ? "✅ All tests passed!" : "❌ Some tests failed!") . "</h2>";

// End output buffering and display results
$output = ob_get_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <title>NESRAH Group - Test Results</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; line-height: 1.6; }
        h2 { color: #333; border-bottom: 1px solid #eee; padding-bottom: 10px; }
        h3 { color: #444; margin-top: 20px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <?php echo $output; ?>
    
    <h3>Next Steps:</h3>
    <ol>
        <li>If all tests passed, your fixes have been successfully applied.</li>
        <li>If any tests failed, please share the error messages with me so I can help you fix them.</li>
        <li>You can also manually test these features in the application:
            <ul>
                <li><a href="stock_allocations.php" target="_blank">Test Stock Allocations</a></li>
                <li><a href="orders.php" target="_blank">Test Orders</a></li>
                <li><a href="reports.php" target="_blank">Test Reports</a></li>
                <li><a href="my_sales.php" target="_blank">Test My Sales</a></li>
            </ul>
        </li>
    </ol>
</body>
</html>
