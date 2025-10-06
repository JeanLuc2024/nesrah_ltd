<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;

class CustomerController extends Controller {
    private $customerModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireLogin();
        $this->customerModel = new Customer();
    }
    
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 15;
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $where = [];
        $params = [];
        
        if (!empty($search)) {
            $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR phone LIKE ? OR id_number LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, array_fill(0, 5, $searchTerm));
        }
        
        if (!empty($status)) {
            $where[] = "status = ?";
            $params[] = $status;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM customers $whereClause");
        $stmt->execute($params);
        $total = $stmt->fetch()['count'];
        
        // Calculate pagination
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        // Get customers
        $sql = "SELECT * FROM customers $whereClause ORDER BY first_name, last_name LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($params, [$perPage, $offset]));
        $customers = $stmt->fetchAll();
        
        // Get stats
        $stats = Customer::getStats();
        
        $this->render('customers/index', [
            'customers' => $customers,
            'stats' => $stats,
            'search' => $search,
            'status' => $status,
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'per_page' => $perPage,
                'total_items' => $total
            ],
            'pageTitle' => 'Customers',
            'activeMenu' => 'customers'
        ]);
    }
    
    public function create() {
        $this->render('customers/create', [
            'pageTitle' => 'Add New Customer',
            'activeMenu' => 'customers',
            'customer' => [
                'first_name' => '',
                'last_name' => '',
                'email' => '',
                'phone' => '',
                'address' => '',
                'id_number' => '',
                'date_of_birth' => '',
                'employment_status' => '',
                'monthly_income' => '',
                'credit_score' => '',
                'status' => 'active'
            ],
            'errors' => []
        ]);
    }
    
    public function store() {
        if (!$this->isPost()) {
            $this->redirect('/customers');
        }
        
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'id_number' => trim($_POST['id_number'] ?? ''),
            'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
            'employment_status' => trim($_POST['employment_status'] ?? ''),
            'monthly_income' => !empty($_POST['monthly_income']) ? (float)str_replace(',', '', $_POST['monthly_income']) : 0,
            'credit_score' => !empty($_POST['credit_score']) ? (int)$_POST['credit_score'] : null,
            'status' => $_POST['status'] ?? 'active',
            'created_by' => $_SESSION['user_id']
        ];
        
        $errors = $this->validateCustomer($data);
        
        if (empty($errors)) {
            try {
                $customer = new Customer();
                $customer->create($data);
                
                $this->setFlash('success', 'Customer added successfully');
                $this->redirect('/customers');
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    // Duplicate entry
                    if (strpos($e->getMessage(), 'email') !== false) {
                        $errors['email'] = 'Email already exists';
                    } elseif (strpos($e->getMessage(), 'id_number') !== false) {
                        $errors['id_number'] = 'ID number already exists';
                    } else {
                        $errors['general'] = 'An error occurred. Please try again.';
                    }
                } else {
                    $errors['general'] = 'Database error: ' . $e->getMessage();
                }
                
                $this->render('customers/create', [
                    'pageTitle' => 'Add New Customer',
                    'activeMenu' => 'customers',
                    'customer' => $data,
                    'errors' => $errors
                ]);
            }
        } else {
            $this->render('customers/create', [
                'pageTitle' => 'Add New Customer',
                'activeMenu' => 'customers',
                'customer' => $data,
                'errors' => $errors
            ]);
        }
    }
    
    /**
     * Validate customer data
     * @param array $data Customer data to validate
     * @return array Array of validation errors, empty if valid
     */
    private function validateCustomer($data) {
        $errors = [];
        
        // Required fields
        if (empty($data['first_name'])) {
            $errors['first_name'] = 'First name is required';
        } elseif (strlen($data['first_name']) > 50) {
            $errors['first_name'] = 'First name cannot exceed 50 characters';
        }
        
        if (empty($data['last_name'])) {
            $errors['last_name'] = 'Last name is required';
        } elseif (strlen($data['last_name']) > 50) {
            $errors['last_name'] = 'Last name cannot exceed 50 characters';
        }
        
        // Email validation
        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address';
        } elseif (strlen($data['email']) > 100) {
            $errors['email'] = 'Email cannot exceed 100 characters';
        }
        
        // Phone validation
        if (empty($data['phone'])) {
            $errors['phone'] = 'Phone number is required';
        } elseif (!preg_match('/^[0-9+\-\s()]{10,20}$/', $data['phone'])) {
            $errors['phone'] = 'Please enter a valid phone number';
        }
        
        // ID Number validation
        if (!empty($data['id_number']) && !preg_match('/^[A-Z0-9\-]+$/', $data['id_number'])) {
            $errors['id_number'] = 'Please enter a valid ID number';
        }
        
        // Date of Birth validation
        if (!empty($data['date_of_birth'])) {
            $dob = strtotime($data['date_of_birth']);
            if ($dob === false || $dob > strtotime('-13 years')) {
                $errors['date_of_birth'] = 'Customer must be at least 13 years old';
            }
        }
        
        // Monthly Income validation
        if ($data['monthly_income'] < 0) {
            $errors['monthly_income'] = 'Monthly income cannot be negative';
        }
        
        // Credit Score validation
        if (!empty($data['credit_score']) && ($data['credit_score'] < 300 || $data['credit_score'] > 850)) {
            $errors['credit_score'] = 'Credit score must be between 300 and 850';
        }
        
        // Status validation
        if (!in_array($data['status'], ['active', 'inactive', 'blacklisted'])) {
            $errors['status'] = 'Invalid status';
        }
        
        return $errors;
    }
    
    /**
     * Show customer details
     * @param int $id Customer ID
     */
    public function show($id) {
        $customer = $this->customerModel->find($id);
        
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('/customers');
        }
        
        // Get customer's loans
        $stmt = $this->pdo->prepare("
            SELECT l.*, lp.name as product_name 
            FROM loans l 
            JOIN loan_products lp ON l.loan_product_id = lp.id 
            WHERE l.customer_id = ? 
            ORDER BY l.created_at DESC
        
        ");
        $stmt->execute([$id]);
        $loans = $stmt->fetchAll();
        
        $this->render('customers/show', [
            'pageTitle' => 'Customer Details',
            'activeMenu' => 'customers',
            'customer' => $customer,
            'loans' => $loans
        ]);
    }
    
    /**
     * Show edit customer form
     * @param int $id Customer ID
     */
    public function edit($id) {
        $customer = $this->customerModel->find($id);
        
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('/customers');
        }
        
        $this->render('customers/edit', [
            'pageTitle' => 'Edit Customer',
            'activeMenu' => 'customers',
            'customer' => $customer,
            'errors' => []
        ]);
    }
    
    /**
     * Update customer
     * @param int $id Customer ID
     */
    public function update($id) {
        if (!$this->isPost()) {
            $this->redirect("/customers/$id/edit");
        }
        
        $customer = $this->customerModel->find($id);
        
        if (!$customer) {
            $this->setFlash('error', 'Customer not found');
            $this->redirect('/customers');
        }
        
        $data = [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'id_number' => trim($_POST['id_number'] ?? ''),
            'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
            'employment_status' => trim($_POST['employment_status'] ?? ''),
            'monthly_income' => !empty($_POST['monthly_income']) ? (float)str_replace(',', '', $_POST['monthly_income']) : 0,
            'credit_score' => !empty($_POST['credit_score']) ? (int)$_POST['credit_score'] : null,
            'status' => $_POST['status'] ?? 'active'
        ];
        
        $errors = $this->validateCustomer($data);
        
        // Skip email uniqueness check if email hasn't changed
        if (isset($errors['email']) && $data['email'] === $customer['email']) {
            unset($errors['email']);
        }
        
        // Skip ID number uniqueness check if it hasn't changed
        if (isset($errors['id_number']) && $data['id_number'] === $customer['id_number']) {
            unset($errors['id_number']);
        }
        
        if (empty($errors)) {
            try {
                $this->customerModel->update($id, $data);
                $this->setFlash('success', 'Customer updated successfully');
                $this->redirect("/customers/$id");
            } catch (\PDOException $e) {
                if ($e->getCode() == 23000) {
                    if (strpos($e->getMessage(), 'email') !== false) {
                        $errors['email'] = 'Email already exists';
                    } elseif (strpos($e->getMessage(), 'id_number') !== false) {
                        $errors['id_number'] = 'ID number already exists';
                    } else {
                        $errors['general'] = 'An error occurred. Please try again.';
                    }
                } else {
                    $errors['general'] = 'Database error: ' . $e->getMessage();
                }
                
                $this->render('customers/edit', [
                    'pageTitle' => 'Edit Customer',
                    'activeMenu' => 'customers',
                    'customer' => array_merge(['id' => $id], $data),
                    'errors' => $errors
                ]);
            }
        } else {
            $this->render('customers/edit', [
                'pageTitle' => 'Edit Customer',
                'activeMenu' => 'customers',
                'customer' => array_merge(['id' => $id], $data),
                'errors' => $errors
            ]);
        }
    }
}
