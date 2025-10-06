<?php

namespace App\Models;

use App\Core\Model;

class LoanProduct extends Model {
    protected $table = 'loan_products';
    protected $primaryKey = 'id';
    
    /**
     * Get all active loan products
     */
    public function getAllActive() {
        $stmt = $this->pdo->query("SELECT * FROM loan_products WHERE status = 'active' ORDER BY name");
        return $stmt->fetchAll();
    }
}
