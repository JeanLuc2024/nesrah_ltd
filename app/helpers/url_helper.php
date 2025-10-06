<?php

// Load the configuration helper if not already loaded
if (!function_exists('config')) {
    require_once __DIR__ . '/config_helper.php';
}

/**
 * Generate a URL for the application
 *
 * @param string $path The path relative to the base URL
 * @param array $parameters URL parameters
 * @param bool $secure Force HTTPS if true
 * @return string
 */
function url($path = '', $parameters = [], $secure = null) {
    // Get base URL from config
    $baseUrl = rtrim(config('app.url', ''), '/');
    
    // If no base URL is configured, auto-detect it
    if (empty($baseUrl)) {
        $scheme = (($secure === true) || 
                  (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
                  (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) 
                ? 'https' : 'http';
                
        $baseUrl = $scheme . '://' . 
                 $_SERVER['HTTP_HOST'] . 
                 str_replace('/index.php', '', dirname($_SERVER['SCRIPT_NAME']));
        $baseUrl = rtrim($baseUrl, '/');
    }
    
    // Force HTTPS if requested
    if ($secure === true && strpos($baseUrl, 'http://') === 0) {
        $baseUrl = 'https://' . substr($baseUrl, 7);
    }
    
    // Remove any leading slashes from the path
    $path = ltrim($path, '/');
    
    // Build the URL
    $url = $baseUrl . '/' . $path;
    
    // Remove any double slashes that might have been created
    $url = preg_replace('/([^:])(\/\/+)/', '$1/', $url);
    
    // Add query string if parameters are provided
    if (!empty($parameters)) {
        $url .= (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . http_build_query($parameters);
    }
    
    return $url;
}

/**
 * Generate an asset URL
 *
 * @param string $path
 * @param bool $secure
 * @return string
 */
function asset($path, $secure = null) {
    return url('assets/' . ltrim($path, '/'), [], $secure);
}

/**
 * Generate a secure asset URL
 *
 * @param string $path
 * @return string
 */
function secure_asset($path) {
    return asset($path, true);
}

/**
 * Get the current URL
 *
 * @return string
 */
function current_url() {
    return url($_SERVER['REQUEST_URI']);
}

/**
 * Get the previous URL from the session or the referrer
 *
 * @param string $default
 * @return string
 */
function previous_url($default = '/') {
    // If we have a session with previous URL, use that
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['_previous']['url'])) {
        return $_SESSION['_previous']['url'];
    }
    
    // Otherwise use the HTTP_REFERER
    return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $default;
}

/**
 * Redirect to a URL
 *
 * @param string $url The URL to redirect to
 * @param int $statusCode HTTP status code (default: 302)
 */
function redirect($url, $statusCode = 302) {
    header('Location: ' . $url, true, $statusCode);
    exit();
}

/**
 * Get the current URL
 *
 * @return string
 */
function current_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Get the base URL of the application
 *
 * @return string
 */
function base_url() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    return $protocol . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
}
