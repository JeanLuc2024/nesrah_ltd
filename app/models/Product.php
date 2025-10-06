<?php

namespace App\Models;

use App\Utils\DB;

class Product extends Model {
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name',
        'description',
        'cost',
        'selling_price',
        'quantity',
        'created_by'
    ];

    // Get the user who created this product
    public function creator() {
        return User::find($this->created_by);
    }

    // Get all loans associated with this product
    public function loans() {
        $db = DB::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM loans WHERE product_id = :product_id");
        $stmt->execute(['product_id' => $this->id]);
        
        $loans = [];
        while ($row = $stmt->fetch()) {
            $loans[] = (new Loan())->fill($row);
        }
        
        return $loans;
    }

    // Get the available quantity (total - loaned out)
    public function getAvailableQuantity() {
        $db = DB::getInstance()->getConnection();
        
        // Get total loaned quantity
        $stmt = $db->prepare("
            SELECT COALESCE(SUM(quantity), 0) as total_loaned 
            FROM loans 
            WHERE product_id = :product_id 
            AND status IN ('pending', 'active')
        ");
        $stmt->execute(['product_id' => $this->id]);
        $result = $stmt->fetch();
        
        $loanedQuantity = (int)$result['total_loaned'];
        return $this->quantity - $loanedQuantity;
    }

    // Get products with pagination and search
    public static function getPaginated($page = 1, $perPage = 10, $search = '') {
        $db = DB::getInstance()->getConnection();
        $offset = ($page - 1) * $perPage;
        
        // Build query
        $query = "SELECT * FROM products";
        $countQuery = "SELECT COUNT(*) as total FROM products";
        $params = [];
        $where = [];
        
        // Add search condition
        if (!empty($search)) {
            $where[] = "(name LIKE :search OR description LIKE :search)";
            $params['search'] = "%{$search}%";
        }
        
        // Add WHERE clause if needed
        if (!empty($where)) {
            $whereClause = " WHERE " . implode(' AND ', $where);
            $query .= $whereClause;
            $countQuery .= $whereClause;
        }
        
        // Add sorting and pagination
        $query .= " ORDER BY created_at DESC LIMIT :offset, :perPage";
        
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
        
        $products = [];
        while ($row = $stmt->fetch()) {
            $products[] = (new self())->fill($row);
        }
        
        return [
            'data' => $products,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ];
    }
        parent::__construct();
    }
    
    /**
     * Get all products with optional filtering and pagination
     */
    public function getAll($filters = [], $page = 1, $perPage = 10) {
        $where = [];
        $params = [];
        
        // Build WHERE clause based on filters
        if (!empty($filters['search'])) {
            $where[] = "(name LIKE :search OR description LIKE :search OR sku LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (!empty($filters['category'])) {
            $where[] = "category_id = :category_id";
            $params[':category_id'] = $filters['category'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "status = :status";
            $params[':status'] = $filters['status'];
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Count total records for pagination
        $countSql = "SELECT COUNT(*) as total FROM {$this->table} $whereClause";
        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Calculate pagination
        $offset = ($page - 1) * $perPage;
        $totalPages = ceil($total / $perPage);
        
        // Get paginated results
        $sql = "SELECT * FROM {$this->table} $whereClause ORDER BY created_at DESC LIMIT :offset, :per_page";
        $stmt = $this->db->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':per_page', (int)$perPage, PDO::PARAM_INT);
        $stmt->execute();
        
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'data' => $products,
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages
            ]
        ];
    }
    
    /**
     * Get a single product by ID
     */
    public function getById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Create a new product
     */
    public function create($data) {
        $fields = array_keys($data);
        $placeholders = array_map(function($field) {
            return ":$field";
        }, $fields);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
                
        $stmt = $this->db->prepare($sql);
        
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        try {
            $this->db->beginTransaction();
            $stmt->execute();
            $id = $this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Update an existing product
     */
    public function update($id, $data) {
        if (empty($data)) {
            return false;
        }
        
        $updates = [];
        foreach ($data as $key => $value) {
            $updates[] = "$key = :$key";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        
        // Bind ID
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        // Bind other values
        foreach ($data as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        try {
            $this->db->beginTransaction();
            $result = $stmt->execute();
            $this->db->commit();
            return $result;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Delete a product
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        
        try {
            $this->db->beginTransaction();
            $result = $stmt->execute();
            $this->db->commit();
            return $result;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Get products by IDs (for dropdowns, etc.)
     */
    public function getProductsForDropdown() {
        $sql = "SELECT id, name, price FROM {$this->table} WHERE status = 'active' ORDER BY name";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check if a product exists and is available for loan
     */
    public function isAvailableForLoan($productId, $quantity = 1) {
        $sql = "SELECT quantity_available FROM {$this->table} 
                WHERE id = :id AND status = 'active' AND quantity_available >= :quantity";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $productId, PDO::PARAM_INT);
        $stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }
    
    /**
     * Update product quantity
     */
    public function updateQuantity($productId, $quantityChange) {
        $sql = "UPDATE {$this->table} 
                SET quantity_available = quantity_available + :change 
                WHERE id = :id AND (quantity_available + :change) >= 0";
                
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':change', $quantityChange, PDO::PARAM_INT);
        $stmt->bindValue(':id', $productId, PDO::PARAM_INT);
        
        try {
            $this->db->beginTransaction();
            $result = $stmt->execute();
            $this->db->commit();
            return $result;
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
