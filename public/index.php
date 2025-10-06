<?php
// Start session
session_start();

// Define base path
define('BASE_PATH', dirname(dirname(__FILE__)));

// Load configuration
require_once BASE_PATH . '/config/database.php';

// Set default timezone
date_default_timezone_set('UTC');

// Autoload classes
spl_autoload_register(function ($class) {
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Start the application
$app = new App();
$app->run();
