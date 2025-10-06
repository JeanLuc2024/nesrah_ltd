<?php
class Auth {
    private $db;
    private $user = null;

    public function __construct() {
        $this->db = getDBConnection();
        $this->checkSession();
    }

    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND status = 1 LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Update last login
            $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")
                ->execute([$user['id']]);

            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            
            // Log the login
            $this->logActivity($user['id'], 'login', 'users', $user['id']);
            
            return true;
        }
        
        return false;
    }

    public function logout() {
        if ($this->isLoggedIn()) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'users', $_SESSION['user_id']);
        }
        
        session_unset();
        session_destroy();
        session_start();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function isAdmin() {
        return $this->isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }

    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        if ($this->user === null) {
            $stmt = $this->db->prepare("SELECT id, name, email, role FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $this->user = $stmt->fetch();
        }

        return $this->user;
    }

    public function hasPermission($permission) {
        // Implement role-based permissions here
        $user = $this->getCurrentUser();
        if (!$user) return false;

        // Admin has all permissions
        if ($user['role'] === 'admin') return true;

        // Define permissions for each role
        $permissions = [
            'staff' => [
                'view_loans', 'view_customers', 'create_payment', 'view_payments'
            ]
        ];

        return in_array($permission, $permissions[$user['role']] ?? []);
    }

    private function checkSession() {
        if ($this->isLoggedIn()) {
            // Verify user still exists and is active
            $stmt = $this->db->prepare("SELECT id FROM users WHERE id = ? AND status = 1");
            $stmt->execute([$_SESSION['user_id']]);
            
            if ($stmt->rowCount() === 0) {
                $this->logout();
                $this->redirect('/auth/login');
            }
        }
    }

    private function logActivity($userId, $action, $table, $recordId) {
        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $table,
            $recordId,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }
}
