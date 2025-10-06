<?php

// Load the configuration helper if not already loaded
if (!function_exists('config')) {
    require_once __DIR__ . '/config_helper.php';
}

/**
 * Start the session if it's not already started
 */
function start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        // Set session cookie parameters
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
        $httponly = true; // Prevent JavaScript access to session cookie
        
        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => config('session.lifetime', 120) * 60, // in seconds
            'path' => '/',
            'domain' => $_SERVER['HTTP_HOST'],
            'secure' => $secure,
            'httponly' => $httponly,
            'samesite' => 'Lax' // CSRF protection
        ]);
        
        // Set session name
        session_name(config('session.cookie', 'nesrah_session'));
        
        // Start the session
        session_start();
        
        // Regenerate session ID to prevent session fixation attacks
        if (!isset($_SESSION['_last_activity'])) {
            session_regenerate_id(true);
            $_SESSION['_last_activity'] = time();
        }
        
        // Regenerate session ID periodically to prevent session fixation
        if ($_SESSION['_last_activity'] < time() - 1800) { // 30 minutes
            $_SESSION['_last_activity'] = time();
            session_regenerate_id(true);
        }
    }
}

/**
 * Set a session value
 *
 * @param string $key
 * @param mixed $value
 */
function session_set($key, $value) {
    start_session();
    $_SESSION[$key] = $value;
}

/**
 * Get a session value
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function session_get($key, $default = null) {
    start_session();
    return $_SESSION[$key] ?? $default;
}

/**
 * Check if a session key exists
 *
 * @param string $key
 * @return bool
 */
function session_has($key) {
    start_session();
    return isset($_SESSION[$key]);
}

/**
 * Remove a session value
 *
 * @param string $key
 */
function session_remove($key) {
    start_session();
    if (isset($_SESSION[$key])) {
        unset($_SESSION[$key]);
    }
}

/**
 * Set a flash message
 *
 * @param string $key
 * @param mixed $value
 */
function flash($key, $value) {
    start_session();
    if (!isset($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }
    $_SESSION['_flash'][$key] = $value;
}

/**
 * Get a flash message and remove it
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function get_flash($key, $default = null) {
    start_session();
    $value = $default;
    if (isset($_SESSION['_flash'][$key])) {
        $value = $_SESSION['_flash'][$key];
        unset($_SESSION['_flash'][$key]);
    }
    return $value;
}

/**
 * Check if a flash message exists
 *
 * @param string $key
 * @return bool
 */
function has_flash($key) {
    start_session();
    return isset($_SESSION['_flash'][$key]);
}

/**
 * Clear all flash messages
 */
function clear_flash() {
    start_session();
    if (isset($_SESSION['_flash'])) {
        unset($_SESSION['_flash']);
    }
}

/**
 * Set a success flash message
 *
 * @param string $message
 */
function set_success($message) {
    flash('success', $message);
}

/**
 * Set an error flash message
 *
 * @param string $message
 */
function set_error($message) {
    flash('error', $message);
}

/**
 * Set a warning flash message
 *
 * @param string $message
 */
function set_warning($message) {
    flash('warning', $message);
}

/**
 * Set an info flash message
 *
 * @param string $message
 */
function set_info($message) {
    flash('info', $message);
}

/**
 * Get all flash messages as HTML
 *
 * @return string
 */
function get_flash_messages() {
    start_session();
    $html = '';
    
    if (isset($_SESSION['_flash'])) {
        foreach ($_SESSION['_flash'] as $type => $message) {
            $alertClass = 'alert-' . $type;
            if ($type === 'error') {
                $alertClass = 'alert-danger';
            }
            
            $html .= '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">';
            $html .= htmlspecialchars($message);
            $html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            $html .= '</div>';
        }
        
        // Clear all flash messages after displaying them
        clear_flash();
    }
    
    return $html;
}
