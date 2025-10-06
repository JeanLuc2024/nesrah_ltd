<?php

// Load all helper files
$helperDir = __DIR__ . '/app/helpers';
$helperFiles = [
    'config_helper.php',
    'url_helper.php',
    'session_helper.php',
    'database_helper.php',
    // Add more helper files here as needed
];

foreach ($helperFiles as $helperFile) {
    $filePath = $helperDir . '/' . $helperFile;
    if (file_exists($filePath)) {
        require_once $filePath;
    }
}

// Start the session
if (function_exists('start_session')) {
    start_session();
}

// Load environment variables if .env file exists
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse the line into key and value
        list($key, $value) = explode('=', $line, 2) + [null, null];
        
        if ($key !== null && $value !== null) {
            $key = trim($key);
            $value = trim($value, "'\" \t\n\r\0\x0B");
            
            // Set the environment variable if it's not already set
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
}
