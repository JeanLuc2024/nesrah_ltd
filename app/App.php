<?php
class App {
    protected $controller = 'Home';
    protected $method = 'index';
    protected $params = [];
    protected $isAdmin = false;

    public function __construct() {
        session_start();
        $url = $this->parseUrl();

        // Check if this is an admin route
        if (isset($url[0]) && $url[0] === 'admin') {
            $this->isAdmin = true;
            array_shift($url); // Remove 'admin' from URL
            
            // Set admin controller
            if (isset($url[0]) && file_exists('../app/controllers/admin/' . ucfirst($url[0]) . 'Controller.php')) {
                $this->controller = ucfirst($url[0]);
                unset($url[0]);
            } else {
                $this->controller = 'Dashboard';
            }
            
            $controllerFile = '../app/controllers/admin/' . $this->controller . 'Controller.php';
            $controllerClass = 'App\Controllers\Admin\' . $this->controller . 'Controller';
        } else {
            // Regular controller
            if (isset($url[0]) && file_exists('../app/controllers/' . ucfirst($url[0]) . 'Controller.php')) {
                $this->controller = ucfirst($url[0]);
                unset($url[0]);
            }
            $controllerFile = '../app/controllers/' . $this->controller . 'Controller.php';
            $controllerClass = 'App\Controllers\' . $this->controller . 'Controller';
        }

        // Require the controller file
        if (file_exists($controllerFile)) {
            require_once $controllerFile;
            
            // Create controller instance
            $this->controller = new $controllerClass();
            
            // Check if user is logged in and has access
            $this->checkAccess();
            
            // Set method
            if (isset($url[1])) {
                if (method_exists($this->controller, $url[1])) {
                    $this->method = $url[1];
                    unset($url[1]);
                }
            }
            
            // Set params
            $this->params = $url ? array_values($url) : [];
        } else {
            $this->notFound();
        }
    }
    
    protected function checkAccess() {
        // If this is an admin route
        if ($this->isAdmin) {
            // Check if user is logged in
            if (!isset($_SESSION['user_id'])) {
                header('Location: /login');
                exit();
            }
            
            // Check if user is admin
            // You'll need to implement your own admin check logic here
            if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
                $this->forbidden();
            }
        }
    }
    
    public function run() {
        call_user_func_array([$this->controller, $this->method], $this->params);
    }
    
    protected function notFound() {
        header("HTTP/1.0 404 Not Found");
        require_once '../app/views/errors/404.php';
        exit();
    }
    
    protected function forbidden() {
        header('HTTP/1.0 403 Forbidden');
        echo 'Access Denied';
        exit();
    }

    protected function parseUrl() {
        if (isset($_GET['url'])) {
            return explode('/', filter_var(rtrim($_GET['url'], '/'), FILTER_SANITIZE_URL));
        }
        return [];
    }
}
