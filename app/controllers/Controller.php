<?php
class Controller {
    protected $db;
    protected $auth;

    public function __construct() {
        $this->db = getDBConnection();
        $this->auth = new Auth();
        
        // Check if user is logged in for protected routes
        $this->checkAuth();
    }

    protected function checkAuth() {
        $publicRoutes = ['auth/login', 'auth/authenticate'];
        $currentRoute = isset($_GET['url']) ? $_GET['url'] : 'home';
        
        if (!in_array($currentRoute, $publicRoutes) && !$this->auth->isLoggedIn()) {
            header('Location: /auth/login');
            exit();
        }
    }

    protected function view($view, $data = []) {
        extract($data);
        $viewFile = '../app/views/' . $view . '.php';
        
        if (file_exists($viewFile)) {
            require_once '../app/views/layouts/header.php';
            require_once $viewFile;
            require_once '../app/views/layouts/footer.php';
        } else {
            die('View does not exist');
        }
    }

    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    protected function redirect($url) {
        header('Location: ' . $url);
        exit();
    }
}
