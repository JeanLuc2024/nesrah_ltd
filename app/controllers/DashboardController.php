<?php

namespace App\Controllers;

use App\Core\Controller;

class DashboardController extends Controller {
    public function __construct() {
        parent::__construct();
        $this->requireLogin();
    }
    
    public function index() {
        $stats = [
            'total_loans' => 0,
            'total_customers' => 0,
            'total_payments' => 0,
            'total_revenue' => 0,
            'active_loans' => 0,
            'overdue_loans' => 0,
            'pending_loans' => 0,
            'recent_loans' => [],
            'recent_payments' => [],
            'upcoming_payments' => []
        ];
        
        try {
            // Get total loans
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM loans");
            $stats['total_loans'] = $stmt->fetch()['count'];
            
            // Get total customers
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM customers");
            $stats['total_customers'] = $stmt->fetch()['count'];
            
            // Get total payments
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM loan_payments");
            $stats['total_payments'] = $stmt->fetch()['count'];
            
            // Get total revenue (sum of all payments)
            $stmt = $this->pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM loan_payments");
            $stats['total_revenue'] = $stmt->fetch()['total'];
            
            // Get active loans count
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM loans WHERE status = 'disbursed'");
            $stats['active_loans'] = $stmt->fetch()['count'];
            
            // Get overdue loans count
            $stmt = $this->pdo->query("
                SELECT COUNT(DISTINCT l.id) as count 
                FROM loans l
                JOIN loan_payment_schedule lps ON l.id = lps.loan_id
                WHERE lps.due_date < CURDATE() 
                AND lps.status IN ('pending', 'partial')
                AND l.status = 'disbursed'
            ");
            $stats['overdue_loans'] = $stmt->fetch()['count'];
            
            // Get pending loans count
            $stmt = $this->pdo->query("SELECT COUNT(*) as count FROM loans WHERE status = 'pending'");
            $stats['pending_loans'] = $stmt->fetch()['count'];
            
            // Get recent loans
            $stmt = $this->pdo->query("
                SELECT l.*, c.first_name, c.last_name, c.phone
                FROM loans l
                JOIN customers c ON l.customer_id = c.id
                ORDER BY l.created_at DESC
                LIMIT 5
            ");
            $stats['recent_loans'] = $stmt->fetchAll();
            
            // Get recent payments
            $stmt = $this->pdo->query("
                SELECT lp.*, l.loan_number, c.first_name, c.last_name
                FROM loan_payments lp
                JOIN loans l ON lp.loan_id = l.id
                JOIN customers c ON l.customer_id = c.id
                ORDER BY lp.payment_date DESC
                LIMIT 5
            ");
            $stats['recent_payments'] = $stmt->fetchAll();
            
            // Get upcoming payments (next 7 days)
            $stmt = $this->pdo->query("
                SELECT lps.*, l.loan_number, c.first_name, c.last_name, c.phone
                FROM loan_payment_schedule lps
                JOIN loans l ON lps.loan_id = l.id
                JOIN customers c ON l.customer_id = c.id
                WHERE lps.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                AND lps.status IN ('pending', 'partial')
                AND l.status = 'disbursed'
                ORDER BY lps.due_date ASC
                LIMIT 10
            ");
            $stats['upcoming_payments'] = $stmt->fetchAll();
            
        } catch (\PDOException $e) {
            // Log error
            error_log('Dashboard stats error: ' . $e->getMessage());
        }
        
        $this->render('dashboard/index', [
            'stats' => $stats,
            'pageTitle' => 'Dashboard',
            'activeMenu' => 'dashboard'
        ]);
    }
    
    public function getLoanStats() {
        $this->requireLogin();
        
        $stats = [
            'labels' => [],
            'data' => [
                'disbursed' => [],
                'pending' => [],
                'repaid' => []
            ]
        ];
        
        try {
            // Get last 6 months data
            for ($i = 5; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $stats['labels'][] = date('M Y', strtotime($month . '-01'));
                
                // Get disbursed loans count for the month
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM loans 
                    WHERE DATE_FORMAT(disbursement_date, '%Y-%m') = ?
                    AND status = 'disbursed'
                
                ");
                $stmt->execute([$month]);
                $stats['data']['disbursed'][] = (int) $stmt->fetch()['count'];
                
                // Get pending loans count for the month
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM loans 
                    WHERE DATE_FORMAT(created_at, '%Y-%m') = ?
                    AND status = 'pending'
                
                ");
                $stmt->execute([$month]);
                $stats['data']['pending'][] = (int) $stmt->fetch()['count'];
                
                // Get repaid loans count for the month
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(DISTINCT l.id) as count 
                    FROM loans l
                    JOIN loan_payment_schedule lps ON l.id = lps.loan_id
                    WHERE DATE_FORMAT(lps.payment_date, '%Y-%m') = ?
                    AND lps.status = 'paid'
                    AND l.status = 'closed'
                
                ");
                $stmt->execute([$month]);
                $stats['data']['repaid'][] = (int) $stmt->fetch()['count'];
            }
            
        } catch (\PDOException $e) {
            // Log error
            error_log('Loan stats error: ' . $e->getMessage());
        }
        
        $this->json($stats);
    }
    
    public function getPaymentStats() {
        $this->requireLogin();
        
        $stats = [
            'labels' => [],
            'data' => []
        ];
        
        try {
            // Get last 6 months data
            for ($i = 5; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $stats['labels'][] = date('M Y', strtotime($month . '-01'));
                
                // Get total payments for the month
                $stmt = $this->pdo->prepare("
                    SELECT COALESCE(SUM(amount), 0) as total 
                    FROM loan_payments 
                    WHERE DATE_FORMAT(payment_date, '%Y-%m') = ?
                
                ");
                $stmt->execute([$month]);
                $stats['data'][] = (float) $stmt->fetch()['total'];
            }
            
        } catch (\PDOException $e) {
            // Log error
            error_log('Payment stats error: ' . $e->getMessage());
        }
        
        $this->json($stats);
    }
}php
class DashboardController extends Controller {
    public function index() {
        $stats = [
            'total_loans' => $this->getTotalLoans(),
            'active_loans' => $this->getActiveLoans(),
            'total_customers' => $this->getTotalCustomers(),
            'total_payments' => $this->getTotalPayments(),
            'recent_loans' => $this->getRecentLoans(),
            'upcoming_payments' => $this->getUpcomingPayments(),
            'overdue_loans' => $this->getOverdueLoans()
        ];
        
        $this->view('dashboard/index', ['stats' => $stats]);
    }
    
    private function getTotalLoans() {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM loans");
        return $stmt->fetch()['count'];
    }
    
    private function getActiveLoans() {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM loans WHERE status IN ('approved', 'disbursed')");
        return $stmt->fetch()['count'];
    }
    
    private function getTotalCustomers() {
        $stmt = $this->db->query("SELECT COUNT(*) as count FROM customers");
        return $stmt->fetch()['count'];
    }
    
    private function getTotalPayments() {
        $stmt = $this->db->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE payment_date = CURDATE()");
        return $stmt->fetch()['total'];
    }
    
    private function getRecentLoans($limit = 5) {
        $stmt = $this->db->prepare("
            SELECT l.*, c.first_name, c.last_name 
            FROM loans l
            JOIN customers c ON l.customer_id = c.id
            ORDER BY l.created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    private function getUpcomingPayments($days = 7) {
        $stmt = $this->db->prepare("
            SELECT ps.*, l.amount as loan_amount, 
                   CONCAT(c.first_name, ' ', c.last_name) as customer_name
            FROM payment_schedule ps
            JOIN loans l ON ps.loan_id = l.id
            JOIN customers c ON l.customer_id = c.id
            WHERE ps.due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
            AND ps.status = 'pending'
            ORDER BY ps.due_date ASC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }
    
    private function getOverdueLoans() {
        $stmt = $this->db->query("
            SELECT l.*, c.first_name, c.last_name,
                   DATEDIFF(CURDATE(), ps.due_date) as days_overdue
            FROM payment_schedule ps
            JOIN loans l ON ps.loan_id = l.id
            JOIN customers c ON l.customer_id = c.id
            WHERE ps.due_date < CURDATE() 
            AND ps.status = 'pending'
            ORDER BY ps.due_date ASC
            LIMIT 10
        ");
        return $stmt->fetchAll();
    }
}
