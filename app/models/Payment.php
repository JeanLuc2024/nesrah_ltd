<?php

class Payment {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Create a new payment record
     * 
     * @param array $data Payment data
     * @return int|bool The ID of the created payment or false on failure
     */
    public function create($data) {
        $sql = "INSERT INTO loan_payments (
                    loan_id, 
                    amount, 
                    payment_date, 
                    payment_method, 
                    transaction_reference, 
                    notes, 
                    received_by, 
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $stmt = $this->pdo->prepare($sql);
        $success = $stmt->execute([
            $data['loan_id'],
            $data['amount'],
            $data['payment_date'],
            $data['payment_method'],
            $data['transaction_reference'] ?? null,
            $data['notes'] ?? null,
            $data['received_by']
        ]);

        if ($success) {
            return $this->pdo->lastInsertId();
        }
        
        return false;
    }

    /**
     * Find a payment by ID
     * 
     * @param int $id Payment ID
     * @return array|bool Payment data or false if not found
     */
    public function findById($id) {
        $sql = "SELECT p.*, l.loan_number, 
                       CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                       CONCAT(u.first_name, ' ', u.last_name) as received_by_name
                FROM loan_payments p
                JOIN loans l ON p.loan_id = l.id
                JOIN customers c ON l.customer_id = c.id
                LEFT JOIN users u ON p.received_by = u.id
                WHERE p.id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }

    /**
     * Get all payments for a loan
     * 
     * @param int $loanId Loan ID
     * @return array Array of payment records
     */
    public function getByLoanId($loanId) {
        $sql = "SELECT p.*, CONCAT(u.first_name, ' ', u.last_name) as received_by_name
                FROM loan_payments p
                LEFT JOIN users u ON p.received_by = u.id
                WHERE p.loan_id = ?
                ORDER BY p.payment_date DESC, p.id DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$loanId]);
        
        return $stmt->fetchAll();
    }

    /**
     * Get total amount paid for a loan
     * 
     * @param int $loanId Loan ID
     * @return float Total amount paid
     */
    public function getTotalPaid($loanId) {
        $sql = "SELECT COALESCE(SUM(amount), 0) as total_paid 
                FROM loan_payments 
                WHERE loan_id = ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$loanId]);
        
        return (float) $stmt->fetch()['total_paid'];
    }

    /**
     * Get payment statistics
     * 
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Payment statistics
     */
    public function getStats($startDate = null, $endDate = null) {
        $where = [];
        $params = [];
        
        if ($startDate) {
            $where[] = "payment_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $where[] = "payment_date <= ?";
            $params[] = $endDate . ' 23:59:59';
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT 
                    COUNT(*) as total_payments,
                    COALESCE(SUM(amount), 0) as total_amount,
                    SUM(CASE WHEN payment_method = 'cash' THEN amount ELSE 0 END) as cash_amount,
                    SUM(CASE WHEN payment_method = 'bank_transfer' THEN amount ELSE 0 END) as bank_transfer_amount,
                    SUM(CASE WHEN payment_method = 'check' THEN amount ELSE 0 END) as check_amount,
                    SUM(CASE WHEN payment_method = 'mobile_money' THEN amount ELSE 0 END) as mobile_money_amount
                FROM loan_payments
                $whereClause";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch();
    }

    /**
     * Get recent payments
     * 
     * @param int $limit Number of recent payments to return
     * @return array Array of recent payments
     */
    public function getRecentPayments($limit = 10) {
        $sql = "SELECT p.*, l.loan_number, 
                       CONCAT(c.first_name, ' ', c.last_name) as customer_name
                FROM loan_payments p
                JOIN loans l ON p.loan_id = l.id
                JOIN customers c ON l.customer_id = c.id
                ORDER BY p.payment_date DESC, p.id DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll();
    }

    /**
     * Get payments by date range
     * 
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Array of payments
     */
    public function getPaymentsByDateRange($startDate, $endDate) {
        $sql = "SELECT p.*, l.loan_number, 
                       CONCAT(c.first_name, ' ', c.last_name) as customer_name,
                       CONCAT(u.first_name, ' ', u.last_name) as received_by_name
                FROM loan_payments p
                JOIN loans l ON p.loan_id = l.id
                JOIN customers c ON l.customer_id = c.id
                LEFT JOIN users u ON p.received_by = u.id
                WHERE p.payment_date BETWEEN ? AND ?
                ORDER BY p.payment_date DESC, p.id DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate . ' 23:59:59']);
        
        return $stmt->fetchAll();
    }

    /**
     * Get payments by payment method
     * 
     * @param string $method Payment method
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Array of payments
     */
    public function getPaymentsByMethod($method, $startDate = null, $endDate = null) {
        $where = ["p.payment_method = ?"];
        $params = [$method];
        
        if ($startDate) {
            $where[] = "p.payment_date >= ?";
            $params[] = $startDate;
        }
        
        if ($endDate) {
            $where[] = "p.payment_date <= ?";
            $params[] = $endDate . ' 23:59:59';
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT p.*, l.loan_number, 
                       CONCAT(c.first_name, ' ', c.last_name) as customer_name
                FROM loan_payments p
                JOIN loans l ON p.loan_id = l.id
                JOIN customers c ON l.customer_id = c.id
                WHERE $whereClause
                ORDER BY p.payment_date DESC, p.id DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    /**
     * Get payment summary by date range
     * 
     * @param string $startDate Start date (YYYY-MM-DD)
     * @param string $endDate End date (YYYY-MM-DD)
     * @return array Payment summary
     */
    public function getPaymentSummary($startDate, $endDate) {
        $sql = "SELECT 
                    DATE(payment_date) as payment_day,
                    COUNT(*) as payment_count,
                    SUM(amount) as total_amount
                FROM loan_payments
                WHERE payment_date BETWEEN ? AND ?
                GROUP BY DATE(payment_date)
                ORDER BY payment_day DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$startDate, $endDate . ' 23:59:59']);
        
        return $stmt->fetchAll();
    }

    /**
     * Get payment methods
     * 
     * @return array Array of payment methods
     */
    public function getPaymentMethods() {
        return [
            'cash' => 'Cash',
            'bank_transfer' => 'Bank Transfer',
            'check' => 'Check',
            'mobile_money' => 'Mobile Money',
            'other' => 'Other'
        ];
    }
}
