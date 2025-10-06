<?php

namespace App\Models;

use App\Utils\DB;

class Loan extends Model {
    protected $table = 'loans';
    protected $primaryKey = 'id';
    protected $fillable = [
        'borrower_name',
        'borrower_contact',
        'borrower_address',
        'product_id',
        'quantity',
        'amount',
        'interest_rate',
        'total_amount',
        'paid_amount',
        'due_date',
        'status',
        'created_by'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DEFAULTED = 'defaulted';

    // Get the product associated with this loan
    public function product() {
        return Product::find($this->product_id);
    }

    // Get all payments for this loan
    public function payments() {
        $db = DB::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM payments WHERE loan_id = :loan_id ORDER BY payment_date DESC");
        $stmt->execute(['loan_id' => $this->id]);
        
        $payments = [];
        while ($row = $stmt->fetch()) {
            $payments[] = (new Payment())->fill($row);
        }
        
        return $payments;
    }

    // Get the creator of this loan
    public function creator() {
        return User::find($this->created_by);
    }

    // Calculate the remaining amount to be paid
    public function getRemainingAmount() {
        return $this->total_amount - $this->paid_amount;
    }

    // Check if loan is overdue
    public function isOverdue() {
        return $this->status === self::STATUS_ACTIVE && 
               strtotime($this->due_date) < time();
    }

    // Record a payment for this loan
    public function recordPayment($amount, $paymentDate, $paymentMethod, $reference = null, $notes = null, $userId) {
        $db = DB::getInstance()->getConnection();
        
        try {
            $db->beginTransaction();
            
            // Create payment record
            $payment = new Payment([
                'loan_id' => $this->id,
                'amount' => $amount,
                'payment_date' => $paymentDate,
                'payment_method' => $paymentMethod,
                'reference_number' => $reference,
                'notes' => $notes,
                'created_by' => $userId
            ]);
            
            if (!$payment->save()) {
                throw new \Exception('Failed to save payment record');
            }
            
            // Update loan's paid amount
            $this->paid_amount += $amount;
            
            // Update loan status if fully paid
            if ($this->paid_amount >= $this->total_amount) {
                $this->status = self::STATUS_COMPLETED;
            }
            
            if (!$this->save()) {
                throw new \Exception('Failed to update loan record');
            }
            
            $db->commit();
            return true;
            
        } catch (\Exception $e) {
            $db->rollBack();
            error_log('Error recording payment: ' . $e->getMessage());
            return false;
        }
    }

    // Get loans with pagination and filters
    public static function getPaginated($filters = [], $page = 1, $perPage = 10) {
        $db = DB::getInstance()->getConnection();
        $offset = ($page - 1) * $perPage;
        
        // Build base queries
        $query = "SELECT l.*, p.name as product_name 
                 FROM loans l 
                 LEFT JOIN products p ON l.product_id = p.id";
        $countQuery = "SELECT COUNT(*) as total FROM loans l";
        
        $where = [];
        $params = [];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $where[] = "l.status = :status";
            $params['status'] = $filters['status'];
        }
        
        if (!empty($filters['borrower_name'])) {
            $where[] = "l.borrower_name LIKE :borrower_name";
            $params['borrower_name'] = "%{$filters['borrower_name']}%";
        }
        
        if (!empty($filters['product_id'])) {
            $where[] = "l.product_id = :product_id";
            $params['product_id'] = $filters['product_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = "l.due_date >= :start_date";
            $params['start_date'] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "l.due_date <= :end_date";
            $params['end_date'] = $filters['end_date'];
        }
        
        // Add WHERE clause if needed
        if (!empty($where)) {
            $whereClause = " WHERE " . implode(' AND ', $where);
            $query .= $whereClause;
            $countQuery .= $whereClause;
        }
        
        // Add sorting and pagination
        $query .= " ORDER BY l.due_date ASC LIMIT :offset, :perPage";
        
        // Get total count
        $stmt = $db->prepare($countQuery);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->execute();
        $total = $stmt->fetch()['total'];
        
        // Get paginated results
        $stmt = $db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }
        $stmt->bindValue(":offset", (int)$offset, \PDO::PARAM_INT);
        $stmt->bindValue(":perPage", (int)$perPage, \PDO::PARAM_INT);
        $stmt->execute();
        
        $loans = [];
        while ($row = $stmt->fetch()) {
            $loans[] = (new self())->fill($row);
        }
        
        return [
            'data' => $loans,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
    
    /**
     * Get loan with customer and product details
     */
    public function findWithDetails($id) {
        $sql = "SELECT l.*, 
                       c.first_name, c.last_name, c.email, c.phone,
                       lp.name as product_name, lp.interest_rate, lp.interest_type,
                       u.full_name as created_by_name
                FROM loans l
                JOIN customers c ON l.customer_id = c.id
                JOIN loan_products lp ON l.loan_product_id = lp.id
                LEFT JOIN users u ON l.created_by = u.id
                WHERE l.id = ?";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Get loan payment schedule
     */
    public function getPaymentSchedule($loanId) {
        $sql = "SELECT * FROM loan_payment_schedule 
                WHERE loan_id = ? 
                ORDER BY due_date ASC";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$loanId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get loan payments
     */
    public function getPayments($loanId) {
        $sql = "SELECT p.*, u.full_name as received_by_name
                FROM loan_payments p
                LEFT JOIN users u ON p.received_by = u.id
                WHERE p.loan_id = ?
                ORDER BY p.payment_date DESC, p.created_at DESC";
                
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$loanId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Create payment schedule
     */
    public function createPaymentSchedule($loanId, $schedule) {
        $sql = "INSERT INTO loan_payment_schedule 
                (loan_id, due_date, amount_due, principal_amount, interest_amount, status)
                VALUES (?, ?, ?, ?, ?, ?)";
                
        $stmt = $this->pdo->prepare($sql);
        
        foreach ($schedule as $payment) {
            $stmt->execute([
                $loanId,
                $payment['due_date'],
                $payment['amount_due'],
                $payment['principal_amount'],
                $payment['interest_amount'],
                $payment['status']
            ]);
        }
    }
    
    /**
     * Update payment schedule
     */
    public function updatePaymentSchedule($loanId, $schedule) {
        // First, delete existing schedule
        $this->pdo->prepare("DELETE FROM loan_payment_schedule WHERE loan_id = ?")
                 ->execute([$loanId]);
        
        // Then create new schedule
        $this->createPaymentSchedule($loanId, $schedule);
    }
    
    /**
     * Update loan status
     */
    public function updateStatus($id, $status, $userId, $notes = null) {
        $data = [
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        if ($status === 'approved') {
            $data['approved_by'] = $userId;
            $data['approved_at'] = date('Y-m-d H:i:s');
        } elseif ($status === 'rejected' && $notes) {
            $data['notes'] = $notes;
        }
        
        return $this->update($id, $data);
    }
    
    /**
     * Disburse loan
     */
    public function disburse($id, $userId) {
        $loan = $this->find($id);
        
        if (!$loan) {
            throw new \Exception('Loan not found');
        }
        
        if ($loan['status'] !== 'approved') {
            throw new \Exception('Only approved loans can be disbursed');
        }
        
        $data = [
            'status' => 'disbursed',
            'disbursement_date' => date('Y-m-d'),
            'first_payment_date' => date('Y-m-d', strtotime('+1 month')),
            'maturity_date' => date('Y-m-d', strtotime('+' . $loan['term_months'] . ' months')),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($id, $data);
    }
    
    /**
     * Record loan payment
     */
    public function recordPayment($data) {
        // Start transaction
        $this->pdo->beginTransaction();
        
        try {
            // Insert payment
            $sql = "INSERT INTO loan_payments 
                    (loan_id, amount, payment_date, payment_method, transaction_reference, notes, received_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
                    
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                $data['loan_id'],
                $data['amount'],
                $data['payment_date'],
                $data['payment_method'],
                $data['transaction_reference'],
                $data['notes'],
                $data['received_by']
            ]);
            
            // Update loan payment schedule
            $this->applyPaymentToSchedule($data['loan_id'], $data['amount'], $data['payment_date']);
            
            // Update loan status if fully paid
            $this->checkAndUpdateLoanStatus($data['loan_id']);
            
            // Commit transaction
            $this->pdo->commit();
            
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }
    
    /**
     * Apply payment to schedule
     */
    private function applyPaymentToSchedule($loanId, $amount, $paymentDate) {
        // Get pending payments ordered by due date
        $stmt = $this->pdo->prepare("
            SELECT * FROM loan_payment_schedule 
            WHERE loan_id = ? AND status != 'paid'
            ORDER BY due_date ASC
        
        ");
        $stmt->execute([$loanId]);
        $payments = $stmt->fetchAll();
        
        foreach ($payments as $payment) {
            if ($amount <= 0) break;
            
            $paymentId = $payment['id'];
            $amountDue = (float)$payment['amount_due'] - (float)$payment['paid_amount'];
            $paymentAmount = min($amount, $amountDue);
            
            if ($paymentAmount > 0) {
                // Update payment schedule
                $newPaidAmount = (float)$payment['paid_amount'] + $paymentAmount;
                $status = $newPaidAmount >= (float)$payment['amount_due'] ? 'paid' : 'partial';
                
                $stmt = $this->pdo->prepare("
                    UPDATE loan_payment_schedule 
                    SET paid_amount = ?, 
                        status = ?,
                        paid_date = CASE WHEN ? = 'paid' THEN ? ELSE paid_date END
                    WHERE id = ?
                
                ");
                $stmt->execute([
                    $newPaidAmount,
                    $status,
                    $status,
                    $paymentDate,
                    $paymentId
                ]);
                
                $amount -= $paymentAmount;
            }
        }
        
        // If there's any remaining amount, apply it as an advance payment
        if ($amount > 0) {
            $this->recordAdvancePayment($loanId, $amount, $paymentDate);
        }
    }
    
    /**
     * Record advance payment
     */
    private function recordAdvancePayment($loanId, $amount, $paymentDate) {
        // Get the last payment date
        $stmt = $this->pdo->prepare("
            SELECT MAX(due_date) as last_due_date 
            FROM loan_payment_schedule 
            WHERE loan_id = ?
        
        ");
        $stmt->execute([$loanId]);
        $result = $stmt->fetch();
        $lastDueDate = $result ? new \DateTime($result['last_due_date']) : new \DateTime();
        
        // Create a new payment schedule entry for the advance payment
        $nextMonth = clone $lastDueDate;
        $nextMonth->modify('+1 month');
        
        $stmt = $this->pdo->prepare("
            INSERT INTO loan_payment_schedule 
            (loan_id, due_date, amount_due, principal_amount, interest_amount, paid_amount, status)
            VALUES (?, ?, ?, 0, 0, ?, 'advance')
        
        ");
        $stmt->execute([
            $loanId,
            $nextMonth->format('Y-m-d'),
            $amount,
            $amount
        ]);
    }
    
    /**
     * Check and update loan status
     */
    private function checkAndUpdateLoanStatus($loanId) {
        // Check if all payments are made
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as pending_count 
            FROM loan_payment_schedule 
            WHERE loan_id = ? AND status != 'paid' AND status != 'advance'
        
        ");
        $stmt->execute([$loanId]);
        $result = $stmt->fetch();
        
        if ($result['pending_count'] == 0) {
            // All payments made, close the loan
            $this->update($loanId, [
                'status' => 'closed',
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Get total number of loans
     */
    public function getTotalLoans() {
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM loans");
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    /**
     * Get number of active loans
     */
    public function getActiveLoans() {
        $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM loans WHERE status = 'disbursed'");
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    /**
     * Get total amount disbursed
     */
    public function getTotalDisbursed() {
        $stmt = $this->pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM loans WHERE status = 'disbursed'");
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Get total outstanding amount
     */
    public function getTotalOutstanding() {
        $stmt = $this->pdo->query("
            SELECT COALESCE(SUM(l.amount - IFNULL(SUM(p.amount), 0)), 0) as total
            FROM loans l
            LEFT JOIN loan_payments p ON l.id = p.loan_id
            WHERE l.status = 'disbursed'
            GROUP BY l.id
        
        ");
        $result = $stmt->fetch();
        return $result ? $result['total'] : 0;
    }
}
