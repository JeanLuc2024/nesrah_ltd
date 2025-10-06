<?php
namespace App\Controllers\Admin;

use App\Models\Loan;
use App\Models\Product;
use App\Models\Customer;

class LoanController extends AdminController {
    protected $loanModel;
    protected $productModel;
    protected $customerModel;
    
    public function __construct() {
        parent::__construct();
        $this->loanModel = new Loan();
        $this->productModel = new Product();
        $this->customerModel = new Customer();
    }
    
    /**
     * List all loans
     */
    public function index() {
        // Get query parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        
        // Get filters
        $filters = [
            'search' => $_GET['search'] ?? '',
            'status' => $_GET['status'] ?? '',
            'customer_id' => $_GET['customer_id'] ?? '',
            'product_id' => $_GET['product_id'] ?? '',
            'start_date' => $_GET['start_date'] ?? '',
            'end_date' => $_GET['end_date'] ?? ''
        ];
        
        // Get loans with pagination
        $result = $this->loanModel->getAll($filters, $page, $perPage);
        
        // Get customers and products for filters
        $customers = $this->customerModel->getCustomersForDropdown();
        $products = $this->productModel->getProductsForDropdown();
        
        // Render view
        $this->render('admin/loans/index', [
            'loans' => $result['data'],
            'pagination' => $result['pagination'],
            'filters' => $filters,
            'customers' => $customers,
            'products' => $products,
            'statuses' => [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'disbursed' => 'Disbursed',
                'completed' => 'Completed',
                'defaulted' => 'Defaulted'
            ]
        ]);
    }
    
    /**
     * Show create loan form
     */
    public function create() {
        // Get customers and products for dropdowns
        $customers = $this->customerModel->getCustomersForDropdown();
        $products = $this->productModel->getProductsForDropdown();
        
        $this->render('admin/loans/form', [
            'title' => 'Create New Loan',
            'loan' => [
                'customer_id' => '',
                'product_id' => '',
                'amount' => '',
                'interest_rate' => 10, // Default interest rate
                'term_months' => 12,   // Default term
                'purpose' => '',
                'status' => 'pending',
                'disbursement_date' => date('Y-m-d'),
                'first_payment_date' => date('Y-m-d', strtotime('+1 month'))
            ],
            'customers' => $customers,
            'products' => $products,
            'isEdit' => false
        ]);
    }
    
    /**
     * Store a new loan
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/loans');
            return;
        }
        
        // Validate input
        $data = $this->validateLoanData($_POST);
        
        try {
            // Calculate loan details
            $loanDetails = $this->calculateLoanDetails(
                $data['amount'], 
                $data['interest_rate'], 
                $data['term_months']
            );
            
            // Add calculated fields
            $data = array_merge($data, [
                'monthly_payment' => $loanDetails['monthly_payment'],
                'total_payable' => $loanDetails['total_payable'],
                'total_interest' => $loanDetails['total_interest'],
                'created_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            // Create loan
            $loanId = $this->loanModel->create($data);
            
            // Create loan schedule
            $this->createLoanSchedule($loanId, $data, $loanDetails['schedule']);
            
            $this->setFlash('success', 'Loan created successfully');
            $this->redirect('/admin/loans');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Error creating loan: ' . $e->getMessage());
            $this->redirect('/admin/loans/create');
        }
    }
    
    /**
     * Show loan details
     */
    public function show($id) {
        $loan = $this->loanModel->getById($id);
        
        if (!$loan) {
            $this->setFlash('error', 'Loan not found');
            $this->redirect('/admin/loans');
            return;
        }
        
        // Get loan schedule
        $schedule = $this->loanModel->getLoanSchedule($id);
        
        // Get payments
        $payments = $this->loanModel->getLoanPayments($id);
        
        // Get customer and product details
        $customer = $this->customerModel->getById($loan['customer_id']);
        $product = $this->productModel->getById($loan['product_id']);
        
        $this->render('admin/loans/show', [
            'loan' => $loan,
            'customer' => $customer,
            'product' => $product,
            'schedule' => $schedule,
            'payments' => $payments,
            'statuses' => [
                'pending' => 'Pending',
                'approved' => 'Approved',
                'rejected' => 'Rejected',
                'disbursed' => 'Disbursed',
                'completed' => 'Completed',
                'defaulted' => 'Defaulted'
            ]
        ]);
    }
    
    /**
     * Show edit loan form
     */
    public function edit($id) {
        $loan = $this->loanModel->getById($id);
        
        if (!$loan) {
            $this->setFlash('error', 'Loan not found');
            $this->redirect('/admin/loans');
            return;
        }
        
        // Get customers and products for dropdowns
        $customers = $this->customerModel->getCustomersForDropdown();
        $products = $this->productModel->getProductsForDropdown();
        
        $this->render('admin/loans/form', [
            'title' => 'Edit Loan',
            'loan' => $loan,
            'customers' => $customers,
            'products' => $products,
            'isEdit' => true
        ]);
    }
    
    /**
     * Update an existing loan
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/loans');
            return;
        }
        
        // Check if loan exists
        $loan = $this->loanModel->getById($id);
        if (!$loan) {
            $this->setFlash('error', 'Loan not found');
            $this->redirect('/admin/loans');
            return;
        }
        
        // Validate input
        $data = $this->validateLoanData($_POST, $id);
        
        try {
            // If amount, interest rate or term changed, recalculate
            if ($data['amount'] != $loan['amount'] || 
                $data['interest_rate'] != $loan['interest_rate'] || 
                $data['term_months'] != $loan['term_months']) {
                
                $loanDetails = $this->calculateLoanDetails(
                    $data['amount'], 
                    $data['interest_rate'], 
                    $data['term_months']
                );
                
                // Add calculated fields
                $data = array_merge($data, [
                    'monthly_payment' => $loanDetails['monthly_payment'],
                    'total_payable' => $loanDetails['total_payable'],
                    'total_interest' => $loanDetails['total_interest']
                ]);
                
                // Update loan schedule
                $this->loanModel->deleteLoanSchedule($id);
                $this->createLoanSchedule($id, $data, $loanDetails['schedule']);
            }
            
            // Update loan
            $data['updated_at'] = date('Y-m-d H:i:s');
            $this->loanModel->update($id, $data);
            
            $this->setFlash('success', 'Loan updated successfully');
            $this->redirect("/admin/loans/{$id}");
        } catch (\Exception $e) {
            $this->setFlash('error', 'Error updating loan: ' . $e->getMessage());
            $this->redirect("/admin/loans/{$id}/edit");
        }
    }
    
    /**
     * Update loan status
     */
    public function updateStatus($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }
        
        $status = $_POST['status'] ?? '';
        $notes = $_POST['notes'] ?? '';
        
        if (empty($status)) {
            $this->jsonResponse(['success' => false, 'message' => 'Status is required'], 400);
            return;
        }
        
        try {
            $loan = $this->loanModel->getById($id);
            if (!$loan) {
                $this->jsonResponse(['success' => false, 'message' => 'Loan not found'], 404);
                return;
            }
            
            // Update status
            $this->loanModel->updateStatus($id, $status, $notes);
            
            // If status is disbursed, update product quantity if applicable
            if ($status === 'disbursed' && $loan['product_id']) {
                // Assuming the product quantity should be reduced by 1 when loan is disbursed
                $this->productModel->updateQuantity($loan['product_id'], -1);
            }
            
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Loan status updated successfully',
                'status' => $status
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Error updating loan status: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Record a payment for a loan
     */
    public function recordPayment($loanId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }
        
        $amount = (float)($_POST['amount'] ?? 0);
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d');
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $notes = $_POST['notes'] ?? '';
        
        if ($amount <= 0) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid payment amount'], 400);
            return;
        }
        
        try {
            // Record payment
            $paymentId = $this->loanModel->recordPayment([
                'loan_id' => $loanId,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'notes' => $notes,
                'received_by' => $this->user['id'],
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Update loan status if fully paid
            $loan = $this->loanModel->getById($loanId);
            $totalPaid = $this->loanModel->getTotalPaid($loanId);
            
            if ($totalPaid >= $loan['total_payable']) {
                $this->loanModel->updateStatus($loanId, 'completed', 'Loan fully paid');
            } elseif ($loan['status'] === 'pending' || $loan['status'] === 'approved') {
                $this->loanModel->updateStatus($loanId, 'active', 'First payment received');
            }
            
            $this->jsonResponse([
                'success' => true, 
                'message' => 'Payment recorded successfully',
                'payment_id' => $paymentId
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Error recording payment: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculate loan details
     */
    protected function calculateLoanDetails($amount, $annualInterestRate, $termMonths) {
        if ($amount <= 0 || $annualInterestRate < 0 || $termMonths <= 0) {
            throw new \Exception('Invalid loan parameters');
        }
        
        $monthlyRate = ($annualInterestRate / 100) / 12;
        $termMonths = (int)$termMonths;
        
        // Calculate monthly payment using the formula: P * (r * (1 + r)^n) / ((1 + r)^n - 1)
        if ($monthlyRate == 0) {
            // Handle zero interest rate
            $monthlyPayment = $amount / $termMonths;
        } else {
            $monthlyPayment = $amount * ($monthlyRate * pow(1 + $monthlyRate, $termMonths)) / 
                             (pow(1 + $monthlyRate, $termMonths) - 1);
        }
        
        $totalPayable = $monthlyPayment * $termMonths;
        $totalInterest = $totalPayable - $amount;
        
        // Generate payment schedule
        $schedule = [];
        $balance = $amount;
        $paymentDate = new \DateTime();
        
        for ($i = 1; $i <= $termMonths; $i++) {
            $interest = $balance * $monthlyRate;
            $principal = $monthlyPayment - $interest;
            
            // Adjust last payment to account for rounding errors
            if ($i === $termMonths) {
                $principal = $balance;
                $monthlyPayment = $principal + $interest;
                $balance = 0;
            } else {
                $balance -= $principal;
            }
            
            $paymentDate->add(new \DateInterval('P1M'));
            
            $schedule[] = [
                'payment_number' => $i,
                'due_date' => $paymentDate->format('Y-m-d'),
                'payment' => round($monthlyPayment, 2),
                'principal' => round($principal, 2),
                'interest' => round($interest, 2),
                'balance' => round($balance, 2)
            ];
        }
        
        return [
            'monthly_payment' => round($monthlyPayment, 2),
            'total_payable' => round($totalPayable, 2),
            'total_interest' => round($totalInterest, 2),
            'schedule' => $schedule
        ];
    }
    
    /**
     * Create loan payment schedule
     */
    protected function createLoanSchedule($loanId, $loanData, $schedule) {
        foreach ($schedule as $payment) {
            $this->loanModel->addScheduleItem([
                'loan_id' => $loanId,
                'payment_number' => $payment['payment_number'],
                'due_date' => $payment['due_date'],
                'amount_due' => $payment['payment'],
                'principal_amount' => $payment['principal'],
                'interest_amount' => $payment['interest'],
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Validate loan data
     */
    protected function validateLoanData($postData, $loanId = null) {
        $data = [
            'customer_id' => (int)($postData['customer_id'] ?? 0),
            'product_id' => !empty($postData['product_id']) ? (int)$postData['product_id'] : null,
            'amount' => (float)($postData['amount'] ?? 0),
            'interest_rate' => (float)($postData['interest_rate'] ?? 0),
            'term_months' => (int)($postData['term_months'] ?? 0),
            'purpose' => trim($postData['purpose'] ?? ''),
            'disbursement_date' => $postData['disbursement_date'] ?? date('Y-m-d'),
            'first_payment_date' => $postData['first_payment_date'] ?? date('Y-m-d', strtotime('+1 month')),
            'status' => in_array($postData['status'] ?? '', ['pending', 'approved', 'rejected', 'disbursed', 'completed', 'defaulted']) 
                       ? $postData['status'] 
                       : 'pending',
            'notes' => trim($postData['notes'] ?? '')
        ];
        
        // Validate required fields
        $errors = [];
        
        if ($data['customer_id'] <= 0) {
            $errors[] = 'Customer is required';
        }
        
        if ($data['amount'] <= 0) {
            $errors[] = 'Loan amount must be greater than 0';
        }
        
        if ($data['interest_rate'] < 0) {
            $errors[] = 'Interest rate cannot be negative';
        }
        
        if ($data['term_months'] <= 0) {
            $errors[] = 'Loan term must be greater than 0 months';
        }
        
        if (!empty($errors)) {
            $this->setFlash('error', implode('<br>', $errors));
            
            // Store form data in session for repopulation
            $_SESSION['form_data'] = $data;
            
            // Redirect back to form
            if ($loanId) {
                $this->redirect("/admin/loans/{$loanId}/edit");
            } else {
                $this->redirect('/admin/loans/create');
            }
            exit();
        }
        
        return $data;
    }
    
    /**
     * Get loan details for AJAX request
     */
    public function getLoanDetails($id) {
        try {
            $loan = $this->loanModel->getById($id);
            
            if (!$loan) {
                $this->jsonResponse(['success' => false, 'message' => 'Loan not found'], 404);
                return;
            }
            
            // Get customer details
            $customer = $this->customerModel->getById($loan['customer_id']);
            
            // Get payment schedule
            $schedule = $this->loanModel->getLoanSchedule($id);
            
            // Get payment history
            $payments = $this->loanModel->getLoanPayments($id);
            
            // Calculate totals
            $totalPaid = array_sum(array_column($payments, 'amount'));
            $outstanding = max(0, $loan['total_payable'] - $totalPaid);
            
            // Get next payment due
            $nextPayment = null;
            foreach ($schedule as $payment) {
                if ($payment['status'] === 'pending') {
                    $nextPayment = $payment;
                    break;
                }
            }
            
            $this->jsonResponse([
                'success' => true,
                'loan' => $loan,
                'customer' => $customer,
                'next_payment' => $nextPayment,
                'total_paid' => $totalPaid,
                'outstanding_balance' => $outstanding,
                'payment_schedule' => $schedule,
                'payment_history' => $payments
            ]);
            
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Error retrieving loan details: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Calculate loan payment preview
     */
    public function calculate() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }
        
        $amount = (float)($_POST['amount'] ?? 0);
        $interestRate = (float)($_POST['interest_rate'] ?? 0);
        $termMonths = (int)($_POST['term_months'] ?? 0);
        
        try {
            $details = $this->calculateLoanDetails($amount, $interestRate, $termMonths);
            
            $this->jsonResponse([
                'success' => true,
                'monthly_payment' => number_format($details['monthly_payment'], 2),
                'total_interest' => number_format($details['total_interest'], 2),
                'total_payable' => number_format($details['total_payable'], 2)
            ]);
            
        } catch (\Exception $e) {
            $this->jsonResponse([
                'success' => false, 
                'message' => 'Error calculating loan: ' . $e->getMessage()
            ], 400);
        }
    }
}
