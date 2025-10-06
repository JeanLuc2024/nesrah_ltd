<?php

// Load the configuration helper if not already loaded
if (!function_exists('config')) {
    require_once __DIR__ . '/config_helper.php';
}

// Global database connection variable
$db = null;

/**
 * Get a database connection
 * 
 * @return PDO
 * @throws PDOException If connection fails
 */
function get_db_connection() {
    global $db;
    
    // Return existing connection if available
    if ($db !== null) {
        return $db;
    }
    
    // Get database configuration
    $driver = config('database.connections.mysql.driver', 'mysql');
    $host = config('database.connections.mysql.host', '127.0.0.1');
    $port = config('database.connections.mysql.port', '3306');
    $database = config('database.connections.mysql.database', '');
    $username = config('database.connections.mysql.username', 'root');
    $password = config('database.connections.mysql.password', '');
    $charset = config('database.connections.mysql.charset', 'utf8mb4');
    
    // Set DSN
    $dsn = "{$driver}:host={$host};port={$port};dbname={$database};charset={$charset}";
    
    // Set PDO options
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    try {
        // Create a new PDO instance
        $db = new PDO($dsn, $username, $password, $options);
        return $db;
    } catch (PDOException $e) {
        // Log the error
        error_log('Database connection failed: ' . $e->getMessage());
        
        // Show a user-friendly error message
        if (config('app.debug', false)) {
            die('Database connection failed: ' . $e->getMessage());
        } else {
            die('Database connection failed. Please try again later.');
        }
    }
}

/**
 * Execute a query with parameters
 * 
 * @param string $sql The SQL query
 * @param array $params The parameters to bind
 * @return PDOStatement
 */
function query($sql, $params = []) {
    $db = get_db_connection();
    
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Query failed: ' . $e->getMessage() . '\nSQL: ' . $sql);
        throw $e;
    }
}

/**
 * Get a single record
 * 
 * @param string $sql The SQL query
 * @param array $params The parameters to bind
 * @return object|null
 */
function get_row($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetch() ?: null;
}

/**
 * Get multiple records
 * 
 * @param string $sql The SQL query
 * @param array $params The parameters to bind
 * @return array
 */
function get_rows($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchAll();
}

/**
 * Get a single column value
 * 
 * @param string $sql The SQL query
 * @param array $params The parameters to bind
 * @return mixed
 */
function get_var($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchColumn();
}

/**
 * Get a column of values
 * 
 * @param string $sql The SQL query
 * @param array $params The parameters to bind
 * @return array
 */
function get_col($sql, $params = []) {
    $stmt = query($sql, $params);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Insert a record into a table
 * 
 * @param string $table The table name
 * @param array $data The data to insert (column => value)
 * @return int The ID of the inserted record
 */
function insert($table, $data) {
    $columns = array_keys($data);
    $placeholders = array_map(fn($col) => ":$col", $columns);
    
    $sql = "INSERT INTO `$table` (`" . implode('`, `', $columns) . "`) 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    $db = get_db_connection();
    $stmt = $db->prepare($sql);
    $stmt->execute($data);
    
    return $db->lastInsertId();
}

/**
 * Update records in a table
 * 
 * @param string $table The table name
 * @param array $data The data to update (column => value)
 * @param string $where The WHERE clause
 * @param array $where_params The parameters for the WHERE clause
 * @return int The number of affected rows
 */
function update($table, $data, $where, $where_params = []) {
    $set = [];
    $params = [];
    
    foreach ($data as $column => $value) {
        $param = ":set_$column";
        $set[] = "`$column` = $param";
        $params[$param] = $value;
    }
    
    $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE $where";
    
    // Add where params to the params array
    foreach ($where_params as $key => $value) {
        $params[$key] = $value;
    }
    
    $stmt = query($sql, $params);
    return $stmt->rowCount();
}

/**
 * Delete records from a table
 * 
 * @param string $table The table name
 * @param string $where The WHERE clause
 * @param array $params The parameters for the WHERE clause
 * @return int The number of affected rows
 */
function delete($table, $where, $params = []) {
    $sql = "DELETE FROM `$table` WHERE $where";
    $stmt = query($sql, $params);
    return $stmt->rowCount();
}

/**
 * Begin a transaction
 */
function begin_transaction() {
    $db = get_db_connection();
    return $db->beginTransaction();
}

/**
 * Commit a transaction
 */
function commit() {
    $db = get_db_connection();
    return $db->commit();
}

/**
 * Rollback a transaction
 */
function rollback() {
    $db = get_db_connection();
    return $db->rollBack();
}

/**
 * Get the last insert ID
 * 
 * @return string
 */
function last_insert_id() {
    $db = get_db_connection();
    return $db->lastInsertId();
}

/**
 * Quote a value for use in a query
 * 
 * @param mixed $value The value to quote
 * @return string
 */
function db_quote($value) {
    $db = get_db_connection();
    return $db->quote($value);
}

/**
 * Get the number of rows in a table
 * 
 * @param string $table The table name
 * @param string $where Optional WHERE clause
 * @param array $params Optional parameters for the WHERE clause
 * @return int
 */
function count_rows($table, $where = '', $params = []) {
    $sql = "SELECT COUNT(*) FROM `$table`";
    if ($where) {
        $sql .= " WHERE $where";
    }
    
    return (int) get_var($sql, $params);
}

/**
 * Check if a record exists in a table
 * 
 * @param string $table The table name
 * @param string $where The WHERE clause
 * @param array $params The parameters for the WHERE clause
 * @return bool
 */
function record_exists($table, $where, $params = []) {
    $sql = "SELECT 1 FROM `$table` WHERE $where LIMIT 1";
    return (bool) get_var($sql, $params);
}

/**
 * Get paginated results
 * 
 * @param string $sql The base SQL query
 * @param array $params The parameters for the query
 * @param int $page The current page number (1-based)
 * @param int $per_page Number of records per page
 * @return array [results, total_pages, total_records]
 */
function paginate($sql, $params = [], $page = 1, $per_page = 10) {
    // Get total number of records
    $count_sql = "SELECT COUNT(*) FROM ($sql) AS total";
    $total_records = (int) get_var($count_sql, $params);
    
    // Calculate total pages
    $total_pages = max(1, ceil($total_records / $per_page));
    
    // Ensure page is within bounds
    $page = max(1, min($page, $total_pages));
    
    // Calculate offset
    $offset = ($page - 1) * $per_page;
    
    // Add LIMIT and OFFSET to the query
    $sql .= " LIMIT $per_page OFFSET $offset";
    
    // Get the paginated results
    $results = get_rows($sql, $params);
    
    return [
        'data' => $results,
        'current_page' => $page,
        'per_page' => $per_page,
        'total' => $total_records,
        'total_pages' => $total_pages,
        'from' => $total_records ? (($page - 1) * $per_page) + 1 : 0,
        'to' => min($page * $per_page, $total_records),
    ];
}
