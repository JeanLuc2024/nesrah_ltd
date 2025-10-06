<?php

class PaymentsController extends Controller {
    private $pdo;
    private $paymentModel;
    private $loanModel;
    private $customerModel;

    public function __construct() {
        parent::__construct();
        $this->pdo = Database::getInstance()->getConnection();
        // Load models (you'll need to create these)
        $this->paymentModel = new Payment();
        $this->loanModel = new Loan();
        $this->customerModel = new Customer();
    }

    public function index() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        // Get filter parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 15;
        $offset = ($page - 1) * $perPage;

        // Build where clause
        $where = [];
        $params = [];

        if (!empty($_GET['loan_number'])) {
            $where[] = "l.loan_number LIKE ?";
            $params[] = '%' . $_GET['loan_number'] . '%';
        }

        if (!empty($_GET['customer_name'])) {
            $where[] = "(c.first_name LIKE ? OR c.last_name LIKE ?)";
            $params[] = '%' . $_GET['customer_name'] . '%';
            $params[] = '%' . $_GET['customer_name'] . '%';
        }

        if (!empty($_GET['payment_method'])) {
            $where[] = "p.payment_method = ?";
            $params[] = $_GET['payment_method'];
        }

        if (!empty($_GET['date_from'])) {
            $where[] = "p.payment_date >= ?";
            $params[] = $_GET['date_from'];
        }

        if (!empty($_GET['date_to'])) {
            $where[] = "p.payment_date <= ?";
            $params[] = $_GET['date_to'] . ' 23:59:59';
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        // Get payments with pagination
        $sql = "SELECT p.*, l.loan_number, c.first_name, c.last_name, c.phone, 
                       CONCAT(u.first_name, ' ', u.last_name) as received_by_name
                FROM loan_payments p
                JOIN loans l ON p.loan_id = l.id
                JOIN customers c ON l.customer_id = c.id
                LEFT JOIN users u ON p.received_by = u.id
                $whereClause
                ORDER BY p.payment_date DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $perPage;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $payments = $stmt->fetchAll();

        // Get total count for pagination
        $countSql = "SELECT COUNT(*) as total 
                     FROM loan_payments p
                     JOIN loans l ON p.loan_id = l.id
                     JOIN customers c ON l.customer_id = c.id
                     $whereClause";
        
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute(array_slice($params, 0, -2)); // Remove LIMIT and OFFSET from params
        $totalPayments = $countStmt->fetch()['total'];
        $totalPages = ceil($totalPayments / $perPage);

        // Get payment statistics
        $statsSql = "SELECT 
                        COUNT(*) as total_payments,
                        COALESCE(SUM(amount), 0) as total_amount,
                        SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END) as cash_amount,
                        SUM(CASE WHEN payment_method = 'bank_transfer' THEN amount ELSE 0 END) as bank_transfer_amount,
                        SUM(CASE WHEN payment_method = 'check' THEN amount ELSE 0 END) as check_amount,
                        SUM(CASE WHEN payment_method = 'mobile_money' THEN amount ELSE 0 END) as mobile_money_amount
                    FROM loan_payments";
        
        if (!empty($whereClause)) {
            $statsSql .= ' ' . str_replace('p.', '', $whereClause);
        }
        
        $statsStmt = $this->pdo->prepare($statsSql);
        $statsStmt->execute(array_slice($params, 0, -2)); // Remove LIMIT and OFFSET from params
        $stats = $statsStmt->fetch();

        $this->render('payments/index', [
            'pageTitle' => 'Payment Transactions',
            'activeMenu' => 'payments',
            'payments' => $payments,
            'stats' => $stats,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalPayments' => $totalPayments,
            'filters' => [
                'loan_number' => $_GET['loan_number'] ?? '',
                'customer_name' => $_GET['customer_name'] ?? '',
                'payment_method' => $_GET['payment_method'] ?? '',
                'date_from' => $_GET['date_from'] ?? '',
                'date_to' => $_GET['date_to'] ?? ''
            ]
        ]);
    }

    public function create() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        // Get all active loans for dropdown
        $loans = $this->loanModel->getActiveLoans();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'loan_id' => $_POST['loan_id'] ?? null,
                'amount' => $_POST['amount'] ?? 0,
                'payment_date' => $_POST['payment_date'] ?? date('Y-m-d'),
                'payment_method' => $_POST['payment_method'] ?? 'cash',
                'transaction_reference' => $_POST['transaction_reference'] ?? null,
                'notes' => $_POST['notes'] ?? null,
                'received_by' => $_SESSION['user_id']
            ];

            // Validate input
            $errors = $this->validatePayment($data);

            if (empty($errors)) {
                try {
                    // Format amount
                    $data['amount'] = str_replace(',', '', $data['amount']);
                    
                    // Record payment
                    $paymentId = $this->paymentModel->create($data);
                    
                    // Update loan status if fully paid
                    $this->updateLoanStatus($data['loan_id']);
                    
                    $this->setFlash('success', 'Payment recorded successfully');
                    $this->redirect("/payments");
                } catch (PDOException $e) {
                    $errors[] = 'Failed to record payment: ' . $e->getMessage();
                }
            }

            $this->render('payments/create', [
                'pageTitle' => 'Record Payment',
                'activeMenu' => 'payments',
                'loans' => $loans,
                'data' => $data,
                'errors' => $errors
            ]);
            return;
        }

        $this->render('payments/create', [
            'pageTitle' => 'Record Payment',
            'activeMenu' => 'payments',
            'loans' => $loans,
            'data' => [
                'payment_date' => date('Y-m-d'),
                'payment_method' => 'cash'
            ],
            'errors' => []
        ]);
    }

    public function view($id) {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $payment = $this->paymentModel->findById($id);
        if (!$payment) {
            $this->setFlash('error', 'Payment not found');
            $this->redirect('/payments');
        }

        // Get loan and customer details
        $loan = $this->loanModel->find($payment['loan_id']);
        $customer = $this->customerModel->find($loan['customer_id']);

        $this->render('payments/view', [
            'pageTitle' => 'Payment Details',
            'activeMenu' => 'payments',
            'payment' => $payment,
            'loan' => $loan,
            'customer' => $customer
        ]);
    }

    public function receipt($id) {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }

        $payment = $this->paymentModel->findById($id);
        if (!$payment) {
            $this->setFlash('error', 'Payment not found');
            $this->redirect('/payments');
        }

        // Get loan and customer details
        $loan = $this->loanModel->find($payment['loan_id']);
        $customer = $this->customerModel->find($loan['customer_id']);

        // Generate receipt HTML
        $receipt = $this->generateReceipt($payment, $loan, $customer);

        // Output as PDF or HTML based on request
        if (isset($_GET['download']) && $_GET['download'] === 'pdf') {
            // Generate PDF (you'll need to implement this)
            $this->generatePdf($receipt, "payment-receipt-{$payment['id']}.pdf");
        } else {
            echo $receipt;
            exit;
        }
    }

    private function validatePayment($data) {
        $errors = [];

        if (empty($data['loan_id'])) {
            $errors['loan_id'] = 'Please select a loan';
        }

        if (empty($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Please enter a valid amount';
        }

        if (empty($data['payment_date'])) {
            $errors['payment_date'] = 'Please select a payment date';
        } elseif (strtotime($data['payment_date']) > strtotime('today')) {
            $errors['payment_date'] = 'Payment date cannot be in the future';
        }

        if (empty($data['payment_method'])) {
            $errors['payment_method'] = 'Please select a payment method';
        }

        return $errors;
    }

    private function updateLoanStatus($loanId) {
        // Check if loan is fully paid
        $loan = $this->loanModel->find($loanId);
        if ($loan) {
            $totalPaid = $this->paymentModel->getTotalPaid($loanId);
            if ($totalPaid >= $loan['total_payable']) {
                $this->loanModel->updateStatus($loanId, 'closed');
            } elseif ($loan['status'] === 'pending') {
                $this->loanModel->updateStatus($loanId, 'disbursed');
            }
        }
    }

    private function generateReceipt($payment, $loan, $customer) {
        // Implement receipt HTML generation
        // This is a simplified version
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <title>Payment Receipt #{$payment['id']}</title>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; max-width: 800px; margin: 0 auto; padding: 20px; }
                    .header { text-align: center; margin-bottom: 30px; }
                    .receipt-details { margin-bottom: 30px; }
                    .receipt-details table { width: 100%; border-collapse: collapse; }
                    .receipt-details th, .receipt-details td { padding: 8px; border: 1px solid #ddd; text-align: left; }
                    .receipt-details th { background-color: #f5f5f5; }
                    .text-right { text-align: right; }
                    .footer { margin-top: 50px; text-align: center; font-size: 0.9em; color: #666; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>Payment Receipt</h1>
                    <p>Receipt #{$payment['id']} | Date: " . date('F j, Y', strtotime($payment['payment_date'])) . "</p>
                </div>
                
                <div class="receipt-details">
                    <h3>Payment Information</h3>
                    <table>
                        <tr>
                            <th>Payment ID</th>
                            <td>#{$payment['id']}</td>
                            <th>Payment Date</th>
                            <td>" . date('F j, Y', strtotime($payment['payment_date'])) . "</td>
                        </tr>
                        <tr>
                            <th>Payment Method</th>
                            <td>" . ucfirst($payment['payment_method']) . "</td>
                            <th>Reference</th>
                            <td>" . htmlspecialchars($payment['transaction_reference'] ?: 'N/A') . "</td>
                        </tr>
                        <tr>
                            <th>Loan Number</th>
                            <td>{$loan['loan_number']}</td>
                            <th>Loan Amount</th>
                            <td>$" . number_format($loan['amount'], 2) . "</td>
                        </tr>
                        <tr>
                            <th>Customer Name</th>
                            <td>" . htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) . "</td>
                            <th>Phone</th>
                            <td>" . htmlspecialchars($customer['phone']) . "</td>
                        </tr>
                        <tr>
                            <th>Amount Paid</th>
                            <td colspan="3" class="text-right"><strong>$" . number_format($payment['amount'], 2) . "</strong></td>
                        </tr>
                    </table>
                </div>
                
                <div class="footer">
                    <p>Thank you for your payment!</p>
                    <p>" . htmlspecialchars(get_setting('company_name', 'Loan Management System')) . "</p>
                    <p>" . htmlspecialchars(get_setting('company_address', '')) . "</p>
                    <p>" . htmlspecialchars(get_setting('company_phone', '')) . " | " . 
                       htmlspecialchars(get_setting('company_email', '')) . "</p>
                </div>
            </body>
            </html>
        ";
    }
}
