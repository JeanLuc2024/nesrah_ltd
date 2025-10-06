<?php

namespace App\Controllers;

use App\Core\Controller;

class AuthController extends Controller {
    public function login() {
        // If already logged in, redirect to dashboard
        if (isset($_SESSION['user_id'])) {
            $this->redirect('/nesrah/public/dashboard');
        }

        $error = '';
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            
            if (empty($username) || empty($password)) {
                $error = 'Please enter both username and password';
            } else {
                try {
                    $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 1 LIMIT 1");
                    $stmt->execute([$username]);
                    $user = $stmt->fetch();
                    
                    if ($user && password_verify($password, $user['password'])) {
                        // Set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['full_name'] = $user['full_name'];
                        
                        // Update last login
                        $updateStmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                        $updateStmt->execute([$user['id']]);
                        
                        // Redirect to dashboard or intended URL
                        $redirectUrl = $_SESSION['redirect_url'] ?? '/nesrah/public/dashboard';
                        unset($_SESSION['redirect_url']);
                        
                        $this->redirect($redirectUrl);
                    } else {
                        $error = 'Invalid username or password';
                    }
                } catch (\PDOException $e) {
                    $error = 'Database error: ' . $e->getMessage();
                }
            }
        }
        
        $this->render('auth/login', [
            'error' => $error,
            'username' => $username ?? ''
        ]);
    }
    
    public function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy the session
        session_destroy();
        
        // Redirect to login page
        $this->redirect('/nesrah/public/login');
    }
    
    public function profile() {
        $this->requireLogin();
        
        $user = [];
        $error = '';
        $success = '';
        
        // Get user data
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if (!$user) {
                $this->redirect('/nesrah/public/logout');
            }
            
            // Handle profile update
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $fullName = trim($_POST['full_name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $currentPassword = trim($_POST['current_password'] ?? '');
                $newPassword = trim($_POST['new_password'] ?? '');
                $confirmPassword = trim($_POST['confirm_password'] ?? '');
                
                // Validate inputs
                if (empty($fullName)) {
                    $error = 'Full name is required';
                } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $error = 'Please enter a valid email address';
                } else {
                    // Check if email is already taken by another user
                    $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$email, $_SESSION['user_id']]);
                    if ($stmt->fetch()) {
                        $error = 'Email is already taken';
                    } else {
                        // Update profile
                        $updateData = [
                            'full_name' => $fullName,
                            'email' => $email,
                            'id' => $_SESSION['user_id']
                        ];
                        
                        // Check if changing password
                        if (!empty($currentPassword) || !empty($newPassword) || !empty($confirmPassword)) {
                            if (empty($currentPassword)) {
                                $error = 'Current password is required to change password';
                            } elseif (!password_verify($currentPassword, $user['password'])) {
                                $error = 'Current password is incorrect';
                            } elseif (strlen($newPassword) < 6) {
                                $error = 'New password must be at least 6 characters long';
                            } elseif ($newPassword !== $confirmPassword) {
                                $error = 'New password and confirm password do not match';
                            } else {
                                $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                            }
                        }
                        
                        if (empty($error)) {
                            $setClause = [];
                            foreach (array_keys($updateData) as $key) {
                                if ($key !== 'id') {
                                    $setClause[] = "$key = :$key";
                                }
                            }
                            
                            $sql = "UPDATE users SET " . implode(', ', $setClause) . " WHERE id = :id";
                            $stmt = $this->pdo->prepare($sql);
                            $stmt->execute($updateData);
                            
                            // Update session
                            $_SESSION['full_name'] = $fullName;
                            
                            $success = 'Profile updated successfully';
                            
                            // Refresh user data
                            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            $user = $stmt->fetch();
                        }
                    }
                }
            }
            
        } catch (\PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
        
        $this->render('auth/profile', [
            'user' => $user,
            'error' => $error,
            'success' => $success
        ]);
    }
}php
class AuthController extends Controller {
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            
            if ($this->auth->login($email, $password)) {
                $this->redirect('/dashboard');
            } else {
                $error = 'Invalid email or password';
            }
        }
        
        $this->view('auth/login', ['error' => $error ?? null]);
    }
    
    public function logout() {
        $this->auth->logout();
        $this->redirect('/auth/login');
    }
    
    public function authenticate() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit();
        }
        
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        
        if ($this->auth->login($email, $password)) {
            echo json_encode(['success' => true, 'redirect' => '/dashboard']);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid credentials']);
        }
        exit();
    }
}
