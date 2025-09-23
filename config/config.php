<?php
/**
 * NESRAH GROUP Management System
 * Main Configuration File
 */

// Start session
session_start();

// Include database connection
require_once __DIR__ . '/database.php';

// Site configuration
define('SITE_NAME', 'NESRAH GROUP Management System');
define('SITE_URL', 'http://localhost/nesrah');
define('ADMIN_EMAIL', 'admin@nesrahgroup.com');

// User roles
define('ROLE_ADMIN', 'admin');
define('ROLE_EMPLOYEE', 'employee');

// User status
define('STATUS_PENDING', 'pending');
define('STATUS_ACTIVE', 'active');
define('STATUS_INACTIVE', 'inactive');

// Task status
define('TASK_PENDING', 'pending');
define('TASK_IN_PROGRESS', 'in_progress');
define('TASK_COMPLETED', 'completed');
define('TASK_CANCELLED', 'cancelled');

// Stock request status
define('REQUEST_PENDING', 'pending');
define('REQUEST_APPROVED', 'approved');
define('REQUEST_REJECTED', 'rejected');

// Payment methods
define('PAYMENT_CASH', 'cash');
define('PAYMENT_CARD', 'card');
define('PAYMENT_BANK_TRANSFER', 'bank_transfer');
define('PAYMENT_CREDIT', 'credit');

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_ADMIN;
}

function isEmployee() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === ROLE_EMPLOYEE;
}

function redirect($url) {
    // Handle absolute URLs
    if (strpos($url, 'http') === 0) {
        header("Location: " . $url);
    } else {
        // Build the full URL to ensure proper redirection
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $script_name = $_SERVER['SCRIPT_NAME'];
        
        // Get the base path
        $base_path = dirname($script_name);
        
        // If we're in auth/ directory, go up one level
        if (strpos($base_path, '/auth') !== false) {
            $base_path = dirname($base_path);
        }
        
        // Ensure base path ends with /
        if (substr($base_path, -1) !== '/') {
            $base_path .= '/';
        }
        
        $full_url = $protocol . '://' . $host . $base_path . $url;
        header("Location: " . $full_url);
    }
    exit();
}

function formatCurrency($amount) {
    return '$' . number_format($amount ?? 0, 2);
}

function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('M d, Y g:i A', strtotime($datetime));
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data ?? '')));
}

function generateItemCode() {
    return 'ITM' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    return floor($time/31536000) . ' years ago';
}

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
