<?php

namespace App\Core;

/**
 * Base Controller
 * 
 * All controllers should extend this class
 */
class Controller {
    /**
     * The database connection
     * 
     * @var \PDO
     */
    protected $db;
    
    /**
     * The view data
     * 
     * @var array
     */
    protected $data = [];
    
    /**
     * The layout to use
     * 
     * @var string
     */
    protected $layout = 'layouts/main';
    
    /**
     * The current route
     * 
     * @var string
     */
    protected $currentRoute;
    
    /**
     * The current route parameters
     * 
     * @var array
     */
    protected $routeParams = [];
    
    /**
     * Constructor
     * 
     * @param array $routeParams Parameters from the matched route
     */
    public function __construct($routeParams = []) {
        $this->routeParams = $routeParams;
        
        // Set the current route
        $this->currentRoute = $this->getCurrentRoute();
        
        // Initialize the database connection
        $this->initDb();
        
        // Initialize the controller
        $this->init();
    }
    
    /**
     * Initialize the controller
     * 
     * This method is called after the constructor
     * and can be overridden in child classes
     */
    protected function init() {
        // Can be overridden in child classes
    }
    
    /**
     * Initialize the database connection
     */
    protected function initDb() {
        global $pdo;
        $this->db = $pdo;
    }
    
    /**
     * Get the current route
     * 
     * @return string
     */
    protected function getCurrentRoute() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
        
        // Remove base path from the current path
        if (strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        
        return '/' . trim($path, '/');
    }
    
    /**
     * Render a view
     * 
     * @param string $view The view name (e.g., 'home.index')
     * @param array $data The data to pass to the view
     * @return void
     */
    protected function render($view, $data = []) {
        // Merge global data with view-specific data
        $data = array_merge($this->data, $data);
        
        // Add current route to the view data
        $data['currentRoute'] = $this->currentRoute;
        
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        $viewFile = $this->getViewPath($view);
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new \Exception("View file not found: " . $view);
        }
        
        // Get the view content
        $content = ob_get_clean();
        
        // Include the layout
        $layoutFile = $this->viewPath . $this->layout . '.php';
        if (file_exists($layoutFile)) {
            include $layoutFile;
        } else {
            echo $content; // If no layout, just output the content
        }
    }
    
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
    
    protected function redirect($url, $statusCode = 302) {
        header('Location: ' . $url, true, $statusCode);
        exit();
    }
    
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }
    
    protected function getInput($key = null, $default = null) {
        if ($key === null) {
            return array_merge($_GET, $_POST);
        }
        return $_POST[$key] ?? $_GET[$key] ?? $default;
    }
    
    protected function requireLogin($role = null) {
        if (!isset($_SESSION['user_id'])) {
            $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
            $this->redirect('/nesrah/public/login');
        }
        
        if ($role && (!isset($_SESSION['role']) || $_SESSION['role'] !== $role)) {
            $this->redirect('/nesrah/public/error/403');
        }
    }
    
    protected function setFlash($type, $message) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    protected function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
    
    /**
     * Get the full path to a view file
     * 
     * @param string $view The view name (e.g., 'home.index' or 'errors/404')
     * @return string
     */
    protected function getViewPath($view) {
        // Replace dots with directory separators
        $view = str_replace('.', '/', $view);
        
        // Check if the view exists in the views directory
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        
        // If the view doesn't exist, try to find it in the shared directory
        if (!file_exists($viewFile)) {
            $sharedViewFile = __DIR__ . '/../views/shared/' . $view . '.php';
            if (file_exists($sharedViewFile)) {
                return $sharedViewFile;
            }
        }
        
        return $viewFile;
    }
    
    /**
     * Set a layout for the view
     * 
     * @param string $layout The layout name (without .php extension)
     * @return $this
     */
    protected function setLayout($layout) {
        $this->layout = $layout;
        return $this;
    }
    
    /**
     * Set a title for the page
     * 
     * @param string $title
     * @return $this
     */
    protected function setTitle($title) {
        $this->data['pageTitle'] = $title;
        return $this;
    }
    
    /**
     * Add data to be passed to the view
     * 
     * @param string|array $key The key or an array of key-value pairs
     * @param mixed $value The value (if $key is a string)
     * @return $this
     */
    protected function with($key, $value = null) {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }
        return $this;
    }
    
    /**
     * Render a view with a layout
     * 
     * @param string $view The view name
     * @param array $data The data to pass to the view
     * @param string|null $layout The layout to use (optional)
     * @return void
     */
    protected function view($view, $data = [], $layout = null) {
        // Merge data
        $data = array_merge($this->data, $data);
        
        // Set the content variable
        $content = $this->renderView($view, $data);
        
        // If no layout is specified, use the default one
        $layout = $layout ?: $this->layout;
        
        // If a layout is set, render it with the content
        if ($layout) {
            // Add content to data
            $data['content'] = $content;
            
            // Include the layout
            $layoutFile = $this->getViewPath($layout);
            if (file_exists($layoutFile)) {
                extract($data);
                include $layoutFile;
                return;
            }
        }
        
        // If no layout or layout not found, just output the content
        echo $content;
    }
    
    /**
     * Render a view and return its content
     * 
     * @param string $view The view name
     * @param array $data The data to pass to the view
     * @return string The rendered view content
     */
    protected function renderView($view, $data = []) {
        // Start output buffering
        ob_start();
        
        // Extract data to variables
        extract($data);
        
        // Include the view file
        $viewFile = $this->getViewPath($view);
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            throw new \RuntimeException("View [{$view}] not found");
        }
        
        // Get the contents of the buffer and clean it
        return ob_get_clean();
    }
    
    /**
     * Render a JSON response
     * 
     * @param mixed $data The data to encode as JSON
     * @param int $statusCode The HTTP status code
     * @param array $headers Additional headers to send
     * @return void
     */
    protected function json($data, $statusCode = 200, $headers = []) {
        // Set the content type header
        header('Content-Type: application/json');
        
        // Set the status code
        http_response_code($statusCode);
        
        // Set additional headers
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        
        // Output the JSON-encoded data
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Redirect to a URL
     * 
     * @param string $url The URL to redirect to
     * @param int $statusCode The HTTP status code (default: 302)
     * @return void
     */
    protected function redirect($url, $statusCode = 302) {
        header('Location: ' . $url, true, $statusCode);
        exit;
    }
    
    /**
     * Redirect to a named route
     * 
     * @param string $name The route name
     * @param array $params Route parameters
     * @param int $statusCode The HTTP status code (default: 302)
     * @return void
     */
    protected function redirectToRoute($name, $params = [], $statusCode = 302) {
        global $router;
        $url = $router->route($name, $params);
        $this->redirect($url, $statusCode);
    }
    
    /**
     * Redirect back to the previous page
     * 
     * @param int $statusCode The HTTP status code (default: 302)
     * @return void
     */
    protected function redirectBack($statusCode = 302) {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        $this->redirect($referer, $statusCode);
    }
    
    /**
     * Set a flash message
     * 
     * @param string $key The message key
     * @param string $message The message content
     * @return $this
     */
    protected function flash($key, $message) {
        if (function_exists('flash')) {
            flash($key, $message);
        } else {
            // Fallback if the flash function is not available
            if (!isset($_SESSION['_flash'])) {
                $_SESSION['_flash'] = [];
            }
            $_SESSION['_flash'][$key] = $message;
        }
        return $this;
    }
    
    /**
     * Set a success flash message
     * 
     * @param string $message The success message
     * @return $this
     */
    protected function success($message) {
        return $this->flash('success', $message);
    }
    
    /**
     * Set an error flash message
     * 
     * @param string $message The error message
     * @return $this
     */
    protected function error($message) {
        return $this->flash('error', $message);
    }
    
    /**
     * Set a warning flash message
     * 
     * @param string $message The warning message
     * @return $this
     */
    protected function warning($message) {
        return $this->flash('warning', $message);
    }
    
    /**
     * Set an info flash message
     * 
     * @param string $message The info message
     * @return $this
     */
    protected function info($message) {
        return $this->flash('info', $message);
    }
    
    /**
     * Check if the request is an AJAX request
     * 
     * @return bool
     */
    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }
    
    /**
     * Check if the request is a POST request
     * 
     * @return bool
     */
    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }
    
    /**
     * Check if the request is a GET request
     * 
     * @return bool
     */
    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }
    
    /**
     * Get a request parameter
     * 
     * @param string $key The parameter key
     * @param mixed $default The default value if the key doesn't exist
     * @return mixed
     */
    protected function getParam($key, $default = null) {
        return $_REQUEST[$key] ?? $default;
    }
    
    /**
     * Get all request parameters
     * 
     * @return array
     */
    protected function getParams() {
        return $_REQUEST;
    }
    
    /**
     * Get a route parameter
     * 
     * @param string $key The parameter key
     * @param mixed $default The default value if the key doesn't exist
     * @return mixed
     */
    protected function getRouteParam($key, $default = null) {
        return $this->routeParams[$key] ?? $default;
    }
    
    /**
     * Get all route parameters
     * 
     * @return array
     */
    protected function getRouteParams() {
        return $this->routeParams;
    }
    
    /**
     * Send a 404 Not Found response
     * 
     * @param string $message The error message
     * @return void
     */
    protected function notFound($message = 'Page not found') {
        http_response_code(404);
        
        if ($this->isAjax()) {
            $this->json(['error' => $message], 404);
        } else {
            $this->view('errors/404', [
                'title' => '404 Not Found',
                'message' => $message
            ]);
        }
        
        exit;
    }
    
    /**
     * Send a 403 Forbidden response
     * 
     * @param string $message The error message
     * @return void
     */
    protected function forbidden($message = 'Access denied') {
        http_response_code(403);
        
        if ($this->isAjax()) {
            $this->json(['error' => $message], 403);
        } else {
            $this->view('errors/403', [
                'title' => '403 Forbidden',
                'message' => $message
            ]);
        }
        
        exit;
    }
    
    /**
     * Send a 500 Internal Server Error response
     * 
     * @param string $message The error message
     * @return void
     */
    protected function serverError($message = 'An error occurred') {
        http_response_code(500);
        
        if ($this->isAjax()) {
            $this->json(['error' => $message], 500);
        } else {
            $this->view('errors/500', [
                'title' => '500 Internal Server Error',
                'message' => $message
            ]);
        }
        
        exit;
    }
}
