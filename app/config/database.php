<?php
/**
 * Database Configuration
 * 
 * This file contains the database connection settings for the application.
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'loan_management');

// Create database if it doesn't exist
function ensureDatabaseExists() {
    try {
        // Connect to MySQL without selecting a database
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
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE `" . DB_NAME . "`");
        
        return true;
    } catch (PDOException $e) {
        error_log("Database creation error: " . $e->getMessage());
        return false;
    }
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('UTC');

/**
 * Get database connection
 * 
 * @return PDO Database connection instance
 * @throws PDOException If connection fails
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            // First, ensure the database exists
            if (!ensureDatabaseExists()) {
                throw new PDOException("Failed to create or access the database.");
            }
            
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 5, // 5 second timeout
            ];
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Test the connection
            $pdo->query('SELECT 1');
            
        } catch (PDOException $e) {
            // Log the detailed error
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Provide a more helpful error message
            $errorMsg = "Database connection failed. ";
            $errorMsg .= "Please check if MySQL is running and the credentials in app/config/database.php are correct.";
            
            throw new PDOException($errorMsg, (int)$e->getCode(), $e);
        }
    }
    
    return $pdo;
}

/**
 * Execute a database query with parameters
 * 
 * @param string $query The SQL query
 * @param array $params Parameters for prepared statement
 * @return PDOStatement The executed statement
 */
function executeQuery($query, $params = []) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Get a single row from the database
 * 
 * @param string $query The SQL query
 * @param array $params Parameters for prepared statement
 * @return array|false The fetched row or false if no results
 */
function fetchOne($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetch();
}

/**
 * Get multiple rows from the database
 * 
 * @param string $query The SQL query
 * @param array $params Parameters for prepared statement
 * @return array Array of fetched rows
 */
function fetchAll($query, $params = []) {
    $stmt = executeQuery($query, $params);
    return $stmt->fetchAll();
}

/**
 * Get the ID of the last inserted row
 * 
 * @return string The last insert ID
 */
function lastInsertId() {
    $pdo = getDBConnection();
    return $pdo->lastInsertId();
}

// Test database connection (comment out in production)
try {
    $test = getDBConnection();
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
