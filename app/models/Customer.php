<?php

namespace App\Models;

use App\Core\Model;

class Customer extends Model {
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'id_number',
        'date_of_birth',
        'employment_status',
        'monthly_income',
        'credit_score',
        'status',
        'created_by'
    ];
    
    public function getFullName() {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    public function loans() {
        $stmt = $this->pdo->prepare("SELECT * FROM loans WHERE customer_id = ?");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
    
    public function activeLoans() {
        $stmt = $this->pdo->prepare("SELECT * FROM loans WHERE customer_id = ? AND status = 'disbursed'");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
    
    public function totalBorrowed() {
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) as total FROM loans WHERE customer_id = ?");
        $stmt->execute([$this->id]);
        return $stmt->fetch()['total'];
    }
    
    public function totalRepaid() {
        $stmt = $this->pdo->prepare("
            SELECT COALESCE(SUM(lp.amount), 0) as total 
            FROM loan_payments lp
            JOIN loans l ON lp.loan_id = l.id
            WHERE l.customer_id = ?
        
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetch()['total'];
    }
    
    public function getNextPayment() {
        $stmt = $this->pdo->prepare("
            SELECT lps.*, l.loan_number
            FROM loan_payment_schedule lps
            JOIN loans l ON lps.loan_id = l.id
            WHERE l.customer_id = ?
            AND lps.status IN ('pending', 'partial')
            AND lps.due_date >= CURDATE()
            ORDER BY lps.due_date ASC
            LIMIT 1
        
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetch();
    }
    
    public function getOverduePayments() {
        $stmt = $this->pdo->prepare("
            SELECT lps.*, l.loan_number
            FROM loan_payment_schedule lps
            JOIN loans l ON lps.loan_id = l.id
            WHERE l.customer_id = ?
            AND lps.status IN ('pending', 'partial')
            AND lps.due_date < CURDATE()
            ORDER BY lps.due_date ASC
        
        ");
        $stmt->execute([$this->id]);
        return $stmt->fetchAll();
    }
    
    public static function search($query, $limit = 10) {
        global $pdo;
        
        $stmt = $pdo->prepare("
            SELECT *, CONCAT(first_name, ' ', last_name) as full_name 
            FROM customers 
            WHERE first_name LIKE ? 
               OR last_name LIKE ? 
               OR email LIKE ? 
               OR phone LIKE ?
               OR id_number LIKE ?
            ORDER BY first_name, last_name
            LIMIT ?
        
        ");
        
        $searchTerm = "%$query%";
        $stmt->execute([
            $searchTerm, 
            $searchTerm, 
            $searchTerm, 
            $searchTerm, 
            $searchTerm,
            $limit
        ]);
        
        return $stmt->fetchAll();
    }
    
    public static function getStats() {
        global $pdo;
        
        $stats = [
            'total' => 0,
            'active' => 0,
            'inactive' => 0,
            'blacklisted' => 0,
            'with_loans' => 0,
            'with_overdue' => 0
        ];
        
        try {
            // Total customers
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers");
            $stats['total'] = $stmt->fetch()['count'];
            
            // Active customers
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers WHERE status = 'active'");
            $stats['active'] = $stmt->fetch()['count'];
            
            // Inactive customers
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers WHERE status = 'inactive'");
            $stats['inactive'] = $stmt->fetch()['count'];
            
            // Blacklisted customers
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM customers WHERE status = 'blacklisted'");
            $stats['blacklisted'] = $stmt->fetch()['count'];
            
            // Customers with active loans
            $stmt = $pdo->query("
                SELECT COUNT(DISTINCT c.id) as count 
                FROM customers c
                JOIN loans l ON c.id = l.customer_id
                WHERE l.status = 'disbursed'
            
            ");
            $stats['with_loans'] = $stmt->fetch()['count'];
            
            // Customers with overdue payments
            $stmt = $pdo->query("
                SELECT COUNT(DISTINCT c.id) as count
                FROM customers c
                JOIN loans l ON c.id = l.customer_id
                JOIN loan_payment_schedule lps ON l.id = lps.loan_id
                WHERE lps.due_date < CURDATE()
                AND lps.status IN ('pending', 'partial')
                AND l.status = 'disbursed'
            
            ");
            $stats['with_overdue'] = $stmt->fetch()['count'];
            
        } catch (\PDOException $e) {
            // Log error
            error_log('Customer stats error: ' . $e->getMessage());
        }
        
        return $stats;
    }
}
