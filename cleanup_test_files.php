<?php
/**
 * Cleanup Test Files - Remove test files for security
 * Run this script after verifying the system works correctly
 */

$test_files = [
    'test_system.php',
    'test_admin.php', 
    'test_employee.php',
    'test_db.php',
    'cleanup_test_files.php' // This file will delete itself
];

echo "<h1>Cleaning up test files...</h1>";

foreach ($test_files as $file) {
    if (file_exists($file)) {
        if (unlink($file)) {
            echo "<p style='color: green;'>✅ Deleted: $file</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to delete: $file</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ File not found: $file</p>";
    }
}

echo "<h2>Cleanup Complete!</h2>";
echo "<p>The system is now ready for production use.</p>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Login as admin: <a href='auth/login.php'>Login Page</a></li>";
echo "<li>Default admin credentials: admin / password</li>";
echo "<li>Register new employees: <a href='auth/register.php'>Register Page</a></li>";
echo "<li>Review the system analysis report: SYSTEM_ANALYSIS_REPORT.md</li>";
echo "</ol>";
?>
