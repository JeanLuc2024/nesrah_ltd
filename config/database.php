<?php
/**
 * NESRAH GROUP Management System
 * Database Configuration
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'nesrah_group';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name,
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            // Log error instead of echoing to prevent headers already sent error
            error_log("Database connection error: " . $exception->getMessage());
        }
        
        return $this->conn;
    }
}

// Global database connection
$database = new Database();
$db = $database->getConnection();
?>
