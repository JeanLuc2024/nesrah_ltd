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
