<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Loan;
use App\Models\Customer;
use App\Models\LoanProduct;

class LoanController extends Controller {
    private $loanModel;
    private $customerModel;
    private $loanProductModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireLogin();
        $this->loanModel = new Loan();
        $this->customerModel = new Customer();
        $this->loanProductModel = new LoanProduct();
    }
    
    public function index() {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 15;
        $status = $_GET['status'] ?? '';
        $search = $_GET['search'] ?? '';
        
        // Build where clause
        $where = [];
        $params = [];
        
        if (!empty($status)) {
            $where[] = "l.status = ?";
            $params[] = $status;
        }
        
        if (!empty($search)) {
            $where[] = "(c.first_name LIKE ? OR c.last_name LIKE ? OR l.loan_number LIKE ?)";
            $searchTerm = "%$search%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Get total count
        $sql = "SELECT COUNT(*) as count 
                FROM loans l
                JOIN customers c ON l.customer_id = c.id
                $whereClause";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $total = $stmt->fetch()['count'];
        
        // Calculate pagination
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        
        // Get loans
        $sql = "SELECT l.*, c.first_name, c.last_name, lp.name as product_name 
                FROM loans l
                JOIN customers c ON l.customer_id = c.id
                JOIN loan_products lp ON l.loan_product_id = lp.id
                $whereClause
                ORDER BY l.created_at DESC
                LIMIT ? OFFSET ?";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_merge($params, [$perPage, $offset]));
        $loans = $stmt->fetchAll();
        
        // Get loan stats
        $stats = [
            'total_loans' => $this->loanModel->getTotalLoans(),
            'active_loans' => $this->loanModel->getActiveLoans(),
            'total_disbursed' => $this->loanModel->getTotalDisbursed(),
            'total_outstanding' => $this->loanModel->getTotalOutstanding()
        ];
        
        $this->render('loans/index', [
            'loans' => $loans,
            'stats' => $stats,
            'search' => $search,
            'status' => $status,
            'pagination' => [
                'current' => $page,
                'total' => $totalPages,
                'per_page' => $perPage,
                'total_items' => $total
            ],
            'pageTitle' => 'Loans',
            'activeMenu' => 'loans'
        ]);
    }
    
    public function create() {
        // Get customers and loan products for dropdowns
        $customers = $this->customerModel->getAllActive();
        $products = $this->loanProductModel->getAllActive();
        
        $this->render('loans/create', [
            'customers' => $customers,
            'products' => $products,
            'pageTitle' => 'Create New Loan',
            'activeMenu' => 'loans',
            'loan' => [
                'loan_number' => 'LN-' . strtoupper(uniqid()),
                'customer_id' => '',
                'loan_product_id' => '',
                'amount' => '',
                'term_months' => '',
                'purpose' => '',
                'disbursement_date' => date('Y-m-d')
            ],
            'errors' => []
        ]);
    }
    
    public function store() {
        if (!$this->isPost()) {
            $this->redirect('/loans');
        }
        
        // Validate and process the form data
        $data = [
            'loan_number' => trim($_POST['loan_number'] ?? ''),
            'customer_id' => (int)($_POST['customer_id'] ?? 0),
            'loan_product_id' => (int)($_POST['loan_product_id'] ?? 0),
            'amount' => (float)str_replace(',', '', $_POST['amount'] ?? 0),
            'term_months' => (int)($_POST['term_months'] ?? 0),
            'purpose' => trim($_POST['purpose'] ?? ''),
            'disbursement_date' => trim($_POST['disbursement_date'] ?? ''),
            'status' => 'pending',
            'created_by' => $_SESSION['user_id']
        ];
        
        $errors = $this->validateLoan($data);
        
        if (empty($errors)) {
            try {
                // Calculate payment schedule and other loan details
                $loanProduct = $this->loanProductModel->find($data['loan_product_id']);
                $schedule = $this->calculatePaymentSchedule($data, $loanProduct);
                
                // Create loan
                $loanId = $this->loanModel->create($data);
                
                // Create payment schedule
                $this->loanModel->createPaymentSchedule($loanId, $schedule);
                
                $this->setFlash('success', 'Loan created successfully');
                $this->redirect("/loans/$loanId");
            } catch (\PDOException $e) {
                $errors['general'] = 'Database error: ' . $e->getMessage();
                
                // Get customers and loan products for dropdowns again
                $customers = $this->customerModel->getAllActive();
                $products = $this->loanProductModel->getAllActive();
                
                $this->render('loans/create', [
                    'customers' => $customers,
                    'products' => $products,
                    'pageTitle' => 'Create New Loan',
                    'activeMenu' => 'loans',
                    'loan' => $data,
                    'errors' => $errors
                ]);
            }
        } else {
            // Get customers and loan products for dropdowns again
            $customers = $this->customerModel->getAllActive();
            $products = $this->loanProductModel->getAllActive();
            
            $this->render('loans/create', [
                'customers' => $customers,
                'products' => $products,
                'pageTitle' => 'Create New Loan',
                'activeMenu' => 'loans',
                'loan' => $data,
                'errors' => $errors
            ]);
        }
    }
    
    public function show($id) {
        $loan = $this->loanModel->findWithDetails($id);
        
        if (!$loan) {
            $this->setFlash('error', 'Loan not found');
            $this->redirect('/loans');
        }
        
        // Get payment schedule
        $schedule = $this->loanModel->getPaymentSchedule($id);
        
        // Get payment history
        $payments = $this->loanModel->getPayments($id);
        
        $this->render('loans/show', [
            'loan' => $loan,
            'schedule' => $schedule,
            'payments' => $payments,
            'pageTitle' => 'Loan Details',
            'activeMenu' => 'loans'
        ]);
    }
    
    public function edit($id) {
        $loan = $this->loanModel->find($id);
        
        if (!$loan) {
            $this->setFlash('error', 'Loan not found');
            $this->redirect('/loans');
        }
        
        // Get customers and loan products for dropdowns
        $customers = $this->customerModel->getAllActive();
        $products = $this->loanProductModel->getAllActive();
        
        $this->render('loans/edit', [
            'loan' => $loan,
            'customers' => $customers,
            'products' => $products,
            'pageTitle' => 'Edit Loan',
            'activeMenu' => 'loans',
            'errors' => []
        ]);
    }
    
    public function update($id) {
        if (!$this->isPost()) {
            $this->redirect("/loans/$id/edit");
        }
        
        $loan = $this->loanModel->find($id);
        
        if (!$loan) {
            $this->setFlash('error', 'Loan not found');
            $this->redirect('/loans');
        }
        
        // Validate and process the form data
        $data = [
            'customer_id' => (int)($_POST['customer_id'] ?? 0),
            'loan_product_id' => (int)($_POST['loan_product_id'] ?? 0),
            'amount' => (float)str_replace(',', '', $_POST['amount'] ?? 0),
            'term_months' => (int)($_POST['term_months'] ?? 0),
            'purpose' => trim($_POST['purpose'] ?? ''),
            'disbursement_date' => trim($_POST['disbursement_date'] ?? ''),
            'status' => trim($_POST['status'] ?? 'pending')
        ];
        
        $errors = $this->validateLoan($data, $id);
        
        if (empty($errors)) {
            try {
                // Only update payment schedule if loan terms changed
                $termsChanged = $loan['amount'] != $data['amount'] || 
                               $loan['term_months'] != $data['term_months'] ||
                               $loan['loan_product_id'] != $data['loan_product_id'];
                
                // Update loan
                $this->loanModel->update($id, $data);
                
                if ($termsChanged) {
                    // Recalculate and update payment schedule
                    $loanProduct = $this->loanProductModel->find($data['loan_product_id']);
                    $schedule = $this->calculatePaymentSchedule($data, $loanProduct);
                    $this->loanModel->updatePaymentSchedule($id, $schedule);
                }
                
                $this->setFlash('success', 'Loan updated successfully');
                $this->redirect("/loans/$id");
            } catch (\PDOException $e) {
                $errors['general'] = 'Database error: ' . $e->getMessage();
                
                // Get customers and loan products for dropdowns again
                $customers = $this->customerModel->getAllActive();
                $products = $this->loanProductModel->getAllActive();
                
                $this->render('loans/edit', [
                    'loan' => array_merge(['id' => $id], $data),
                    'customers' => $customers,
                    'products' => $products,
                    'pageTitle' => 'Edit Loan',
                    'activeMenu' => 'loans',
                    'errors' => $errors
                ]);
            }
        } else {
            // Get customers and loan products for dropdowns again
            $customers = $this->customerModel->getAllActive();
            $products = $this->loanProductModel->getAllActive();
            
            $this->render('loans/edit', [
                'loan' => array_merge(['id' => $id], $data),
                'customers' => $customers,
                'products' => $products,
                'pageTitle' => 'Edit Loan',
                'activeMenu' => 'loans',
                'errors' => $errors
            ]);
        }
    }
    
    public function approve($id) {
        if (!$this->isPost()) {
            $this->redirect("/loans/$id");
        }
        
        try {
            $this->loanModel->updateStatus($id, 'approved', $_SESSION['user_id']);
            $this->setFlash('success', 'Loan approved successfully');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Error approving loan: ' . $e->getMessage());
        }
        
        $this->redirect("/loans/$id");
    }
    
    public function disburse($id) {
        if (!$this->isPost()) {
            $this->redirect("/loans/$id");
        }
        
        try {
            $this->loanModel->disburse($id, $_SESSION['user_id']);
            $this->setFlash('success', 'Loan disbursed successfully');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Error disbursing loan: ' . $e->getMessage());
        }
        
        $this->redirect("/loans/$id");
    }
    
    public function reject($id) {
        if (!$this->isPost()) {
            $this->redirect("/loans/$id");
        }
        
        $reason = trim($_POST['reason'] ?? '');
        
        if (empty($reason)) {
            $this->setFlash('error', 'Please provide a reason for rejection');
            $this->redirect("/loans/$id");
        }
        
        try {
            $this->loanModel->updateStatus($id, 'rejected', $_SESSION['user_id'], $reason);
            $this->setFlash('success', 'Loan rejected successfully');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Error rejecting loan: ' . $e->getMessage());
        }
        
        $this->redirect("/loans/$id");
    }
    
    public function close($id) {
        if (!$this->isPost()) {
            $this->redirect("/loans/$id");
        }
        
        try {
            $this->loanModel->updateStatus($id, 'closed', $_SESSION['user_id']);
            $this->setFlash('success', 'Loan closed successfully');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Error closing loan: ' . $e->getMessage());
        }
        
        $this->redirect("/loans/$id");
    }
    
    public function recordPayment($loanId) {
        if (!$this->isPost()) {
            $this->redirect("/loans/$loanId");
        }
        
        $data = [
            'loan_id' => $loanId,
            'amount' => (float)str_replace(',', '', $_POST['amount'] ?? 0),
            'payment_date' => trim($_POST['payment_date'] ?? date('Y-m-d')),
            'payment_method' => trim($_POST['payment_method'] ?? 'cash'),
            'transaction_reference' => trim($_POST['transaction_reference'] ?? ''),
            'notes' => trim($_POST['notes'] ?? ''),
            'received_by' => $_SESSION['user_id']
        ];
        
        $errors = $this->validatePayment($data);
        
        if (empty($errors)) {
            try {
                $this->loanModel->recordPayment($data);
                $this->setFlash('success', 'Payment recorded successfully');
            } catch (\Exception $e) {
                $this->setFlash('error', 'Error recording payment: ' . $e->getMessage());
            }
        } else {
            $this->setFlash('error', 'Please fix the following errors: ' . implode(', ', $errors));
        }
        
        $this->redirect("/loans/$loanId");
    }
    
    /**
     * Validate loan data
     */
    private function validateLoan($data, $id = null) {
        $errors = [];
        
        if (empty($data['customer_id'])) {
            $errors['customer_id'] = 'Customer is required';
        }
        
        if (empty($data['loan_product_id'])) {
            $errors['loan_product_id'] = 'Loan product is required';
        }
        
        if (empty($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Valid loan amount is required';
        }
        
        if (empty($data['term_months']) || $data['term_months'] <= 0) {
            $errors['term_months'] = 'Valid loan term is required';
        }
        
        if (empty($data['disbursement_date'])) {
            $errors['disbursement_date'] = 'Disbursement date is required';
        }
        
        return $errors;
    }
    
    /**
     * Validate payment data
     */
    private function validatePayment($data) {
        $errors = [];
        
        if (empty($data['amount']) || $data['amount'] <= 0) {
            $errors[] = 'Valid payment amount is required';
        }
        
        if (empty($data['payment_date'])) {
            $errors[] = 'Payment date is required';
        }
        
        if (empty($data['payment_method'])) {
            $errors[] = 'Payment method is required';
        }
        
        return $errors;
    }
    
    /**
     * Calculate payment schedule
     */
    private function calculatePaymentSchedule($loanData, $loanProduct) {
        $schedule = [];
        $amount = $loanData['amount'];
        $term = $loanData['term_months'];
        $interestRate = $loanProduct['interest_rate'] / 100; // Convert to decimal
        $disbursementDate = new \DateTime($loanData['disbursement_date']);
        
        // Calculate monthly payment (PMT formula)
        $monthlyRate = $interestRate / 12;
        $monthlyPayment = $amount * ($monthlyRate * pow(1 + $monthlyRate, $term)) / (pow(1 + $monthlyRate, $term) - 1);
        
        // For reducing balance method
        $balance = $amount;
        
        for ($i = 1; $i <= $term; $i++) {
            $interest = $balance * $monthlyRate;
            $principal = $monthlyPayment - $interest;
            
            // For the last payment, adjust to match the remaining balance
            if ($i === $term) {
                $principal = $balance;
                $monthlyPayment = $principal + $interest;
            }
            
            $dueDate = clone $disbursementDate;
            $dueDate->add(new \DateInterval("P{$i}M"));
            
            $schedule[] = [
                'due_date' => $dueDate->format('Y-m-d'),
                'amount_due' => $monthlyPayment,
                'principal_amount' => $principal,
                'interest_amount' => $interest,
                'status' => 'pending'
            ];
            
            $balance -= $principal;
        }
        
        return $schedule;
    }
}
