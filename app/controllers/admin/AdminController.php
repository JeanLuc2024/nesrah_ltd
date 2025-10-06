<?php
namespace App\Controllers\Admin;

use App\Core\Controller;

class AdminController extends Controller {
    protected $user;
    
    public function __construct() {
        parent::__construct();
        
        // Check if user is logged in and is admin
        $this->checkAdmin();
        
        // Set user data
        $this->user = [
            'id' => $_SESSION['user_id'] ?? null,
            'name' => $_SESSION['user_name'] ?? 'Admin',
            'email' => $_SESSION['user_email'] ?? '',
            'is_admin' => $_SESSION['is_admin'] ?? false
        ];
        
        // Set default layout and data
        $this->view->setLayout('admin');
        $this->view->data['user'] = $this->user;
    }
    
    protected function checkAdmin() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /login');
            exit();
        }
        
        if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
            header('HTTP/1.0 403 Forbidden');
            echo 'Access Denied';
            exit();
        }
    }
    
    protected function render($view, $data = []) {
        // Merge with global data
        $data = array_merge($this->view->data, $data);
        
        // Set flash messages if any
        if (isset($_SESSION['flash_messages'])) {
            $data['flash_messages'] = $_SESSION['flash_messages'];
            unset($_SESSION['flash_messages']);
        }
        
        // Render the view
        $this->view->render($view, $data);
    }
    
    protected function redirect($url, $statusCode = 302) {
        header('Location: ' . $url, true, $statusCode);
        exit();
    }
    
    protected function setFlash($type, $message) {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }
        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }
    
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}
