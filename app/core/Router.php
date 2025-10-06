<?php

namespace App\Core;

class Router {
    /**
     * Array of routes
     * 
     * @var array
     */
    protected $routes = [];
    
    /**
     * The current URL path
     * 
     * @var string
     */
    protected $currentPath = '';
    
    /**
     * The current HTTP method
     * 
     * @var string
     */
    protected $currentMethod = '';
    
    /**
     * The current route parameters
     * 
     * @var array
     */
    protected $params = [];
    
    /**
     * The current route name
     * 
     * @var string
     */
    protected $currentRouteName = null;
    
    /**
     * Named routes
     * 
     * @var array
     */
    protected $namedRoutes = [];
    
    /**
     * Router constructor
     * 
     * @param string $url The current URL
     * @param string $method The HTTP method
     */
    public function __construct($url = null, $method = null) {
        $this->currentPath = $url ?? $this->getCurrentPath();
        $this->currentMethod = $method ?? $this->getCurrentMethod();
    }
    
    /**
     * Add a GET route
     * 
     * @param string $path
     * @param mixed $handler
     * @param string|null $name
     * @return void
     */
    public function get($path, $handler, $name = null) {
        $this->addRoute('GET', $path, $handler, $name);
    }
    
    /**
     * Add a POST route
     * 
     * @param string $path
     * @param mixed $handler
     * @param string|null $name
     * @return void
     */
    public function post($path, $handler, $name = null) {
        $this->addRoute('POST', $path, $handler, $name);
    }
    
    /**
     * Add a PUT route
     * 
     * @param string $path
     * @param mixed $handler
     * @param string|null $name
     * @return void
     */
    public function put($path, $handler, $name = null) {
        $this->addRoute('PUT', $path, $handler, $name);
    }
    
    /**
     * Add a PATCH route
     * 
     * @param string $path
     * @param mixed $handler
     * @param string|null $name
     * @return void
     */
    public function patch($path, $handler, $name = null) {
        $this->addRoute('PATCH', $path, $handler, $name);
    }
    
    /**
     * Add a DELETE route
     * 
     * @param string $path
     * @param mixed $handler
     * @param string|null $name
     * @return void
     */
    public function delete($path, $handler, $name = null) {
        $this->addRoute('DELETE', $path, $handler, $name);
    }
    
    /**
     * Add a route that matches any HTTP method
     * 
     * @param string $path
     * @param mixed $handler
     * @param string|null $name
     * @return void
     */
    public function any($path, $handler, $name = null) {
        $this->addRoute(['GET', 'POST', 'PUT', 'PATCH', 'DELETE'], $path, $handler, $name);
    }
    
    /**
     * Add a route with the given methods
     * 
     * @param array|string $methods
     * @param string $path
     * @param mixed $handler
     * @param string|null $name
     * @return void
     */
    public function match($methods, $path, $handler, $name = null) {
        $this->addRoute($methods, $path, $handler, $name);
    }
    
    /**
     * Add a route to the router
     * 
     * @param array|string $methods
     * @param string $path
     * @param mixed $handler
     * @param string|null $name
     * @return void
     */
    protected function addRoute($methods, $path, $handler, $name = null) {
        $methods = (array) $methods;
        $path = '/' . trim($path, '/');
        
        // Add route for each method
        foreach ($methods as $method) {
            $method = strtoupper($method);
            $this->routes[$method][$path] = [
                'handler' => $handler,
                'name' => $name
            ];
            
            // Store named routes
            if ($name) {
                $this->namedRoutes[$name] = [
                    'path' => $path,
                    'method' => $method
                ];
            }
        }
    }
    
    /**
     * Dispatch the router to handle the current request
     * 
     * @return mixed
     * @throws \Exception
     */
    public function dispatch() {
        $method = $this->currentMethod;
        $path = $this->currentPath;
        
        // Check if route exists for the current method
        if (!isset($this->routes[$method])) {
            $this->handleNotFound();
            return;
        }
        
        // Try to find a matching route
        foreach ($this->routes[$method] as $routePath => $route) {
            $pattern = $this->compileRoute($routePath);
            
            if (preg_match($pattern, $path, $matches)) {
                // Remove full match from matches
                array_shift($matches);
                
                // Set the current route name
                $this->currentRouteName = $route['name'];
                
                // Call the handler
                return $this->callHandler($route['handler'], $matches);
            }
        }
        
        // No matching route found
        $this->handleNotFound();
    }
    
    /**
     * Compile a route pattern to a regex
     * 
     * @param string $route
     * @return string
     */
    protected function compileRoute($route) {
        // Escape forward slashes
        $pattern = preg_quote($route, '#');
        
        // Replace route parameters with regex patterns
        $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        
        // Match the entire path
        return '#^' . $pattern . '$#i';
    }
    
    /**
     * Call the route handler
     * 
     * @param mixed $handler
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    protected function callHandler($handler, $params = []) {
        $this->params = $params;
        
        // If handler is a closure
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        }
        
        // If handler is a string in format 'Controller@method'
        if (is_string($handler) && strpos($handler, '@') !== false) {
            list($controller, $method) = explode('@', $handler, 2);
            
            // Ensure controller class exists
            $controllerClass = 'App\\Controllers\\' . $controller;
            if (!class_exists($controllerClass)) {
                throw new \Exception("Controller class {$controllerClass} not found");
            }
            
            // Create controller instance
            $controllerInstance = new $controllerClass();
            
            // Ensure method exists
            if (!method_exists($controllerInstance, $method)) {
                throw new \Exception("Method {$method} not found in controller {$controller}");
            }
            
            // Call the controller method
            return call_user_func_array([$controllerInstance, $method], $params);
        }
        
        // If handler is a string (view name)
        if (is_string($handler)) {
            return $this->renderView($handler, $params);
        }
        
        throw new \Exception('Invalid route handler');
    }
    
    /**
     * Render a view
     * 
     * @param string $view
     * @param array $data
     * @return string
     */
    protected function renderView($view, $data = []) {
        $viewPath = __DIR__ . '/../views/' . str_replace('.', '/', $view) . '.php';
        
        if (!file_exists($viewPath)) {
            throw new \Exception("View {$view} not found");
        }
        
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file
        include $viewPath;
        
        // Get the contents of the buffer and clean it
        return ob_get_clean();
    }
    
    /**
     * Handle 404 Not Found
     * 
     * @return void
     */
    protected function handleNotFound() {
        header('HTTP/1.0 404 Not Found');
        
        if (file_exists(__DIR__ . '/../views/errors/404.php')) {
            include __DIR__ . '/../views/errors/404.php';
        } else {
            echo '404 Not Found';
        }
        
        exit;
    }
    
    /**
     * Get the current URL path
     * 
     * @return string
     */
    protected function getCurrentPath() {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $basePath = str_replace('index.php', '', $_SERVER['SCRIPT_NAME']);
        
        // Remove base path from the current path
        if (strpos($path, $basePath) === 0) {
            $path = substr($path, strlen($basePath));
        }
        
        return '/' . trim($path, '/');
    }
    
    /**
     * Get the current HTTP method
     * 
     * @return string
     */
    protected function getCurrentMethod() {
        $method = $_SERVER['REQUEST_METHOD'];
        
        // Handle method spoofing for PUT, PATCH, DELETE
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }
        
        return $method;
    }
    
    /**
     * Get the current route name
     * 
     * @return string|null
     */
    public function currentRouteName() {
        return $this->currentRouteName;
    }
    
    /**
     * Get the current route parameters
     * 
     * @return array
     */
    public function currentParams() {
        return $this->params;
    }
    
    /**
     * Generate a URL for a named route
     * 
     * @param string $name
     * @param array $params
     * @return string
     * @throws \Exception
     */
    public function route($name, $params = []) {
        if (!isset($this->namedRoutes[$name])) {
            throw new \Exception("Route [{$name}] not found");
        }
        
        $route = $this->namedRoutes[$name];
        $path = $route['path'];
        
        // Replace route parameters
        foreach ($params as $key => $value) {
            $path = str_replace('{' . $key . '}', $value, $path);
        }
        
        return $path;
    }
    
    /**
     * Redirect to a named route
     * 
     * @param string $name
     * @param array $params
     * @param int $status
     * @return void
     */
    public function redirectToRoute($name, $params = [], $status = 302) {
        $url = $this->route($name, $params);
        $this->redirect($url, $status);
    }
    
    /**
     * Redirect to a URL
     * 
     * @param string $url
     * @param int $status
     * @return void
     */
    public function redirect($url, $status = 302) {
        header('Location: ' . $url, true, $status);
        exit;
    }
}
