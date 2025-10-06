<?php
namespace App\Controllers\Admin;

use App\Models\Product;

class ProductController extends AdminController {
    protected $productModel;
    
    public function __construct() {
        parent::__construct();
        $this->productModel = new Product();
    }
    
    /**
     * List all products
     */
    public function index() {
        // Get query parameters
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;
        
        // Get filters
        $filters = [
            'search' => $_GET['search'] ?? '',
            'category' => $_GET['category'] ?? '',
            'status' => $_GET['status'] ?? ''
        ];
        
        // Get products
        $result = $this->productModel->getAll($filters, $page, $perPage);
        
        // Render view
        $this->render('admin/products/index', [
            'products' => $result['data'],
            'pagination' => $result['pagination'],
            'filters' => $filters
        ]);
    }
    
    /**
     * Show create product form
     */
    public function create() {
        $this->render('admin/products/form', [
            'title' => 'Add New Product',
            'product' => [
                'name' => '',
                'sku' => '',
                'description' => '',
                'quantity_available' => 0,
                'price' => 0,
                'cost_price' => 0,
                'category_id' => '',
                'status' => 'active'
            ]
        ]);
    }
    
    /**
     * Store a new product
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/products');
            return;
        }
        
        // Validate input
        $data = $this->validateProductData($_POST);
        
        try {
            // Create product
            $productId = $this->productModel->create($data);
            
            // Handle file upload if any
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $this->handleImageUpload($productId, $_FILES['image']);
            }
            
            $this->setFlash('success', 'Product created successfully');
            $this->redirect('/admin/products');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Error creating product: ' . $e->getMessage());
            $this->redirect('/admin/products/create');
        }
    }
    
    /**
     * Show edit product form
     */
    public function edit($id) {
        $product = $this->productModel->getById($id);
        
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('/admin/products');
            return;
        }
        
        $this->render('admin/products/form', [
            'title' => 'Edit Product',
            'product' => $product
        ]);
    }
    
    /**
     * Update an existing product
     */
    public function update($id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/products');
            return;
        }
        
        // Check if product exists
        $product = $this->productModel->getById($id);
        if (!$product) {
            $this->setFlash('error', 'Product not found');
            $this->redirect('/admin/products');
            return;
        }
        
        // Validate input
        $data = $this->validateProductData($_POST, $id);
        
        try {
            // Update product
            $this->productModel->update($id, $data);
            
            // Handle file upload if any
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $this->handleImageUpload($id, $_FILES['image']);
            }
            
            $this->setFlash('success', 'Product updated successfully');
            $this->redirect('/admin/products');
        } catch (\Exception $e) {
            $this->setFlash('error', 'Error updating product: ' . $e->getMessage());
            $this->redirect("/admin/products/{$id}/edit");
        }
    }
    
    /**
     * Delete a product
     */
    public function delete($id) {
        try {
            $result = $this->productModel->delete($id);
            
            if ($result) {
                // Delete associated image if exists
                $imagePath = "uploads/products/{$id}.jpg";
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
                
                $this->jsonResponse(['success' => true, 'message' => 'Product deleted successfully']);
            } else {
                $this->jsonResponse(['success' => false, 'message' => 'Failed to delete product'], 400);
            }
        } catch (\Exception $e) {
            $this->jsonResponse(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Validate product data
     */
    protected function validateProductData($postData, $id = null) {
        $data = [
            'name' => trim($postData['name'] ?? ''),
            'sku' => trim($postData['sku'] ?? ''),
            'description' => trim($postData['description'] ?? ''),
            'quantity_available' => (int)($postData['quantity_available'] ?? 0),
            'price' => (float)($postData['price'] ?? 0),
            'cost_price' => (float)($postData['cost_price'] ?? 0),
            'category_id' => (int)($postData['category_id'] ?? 0),
            'status' => in_array($postData['status'] ?? '', ['active', 'inactive']) ? $postData['status'] : 'active',
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Add created_at for new products
        if (!$id) {
            $data['created_at'] = date('Y-m-d H:i:s');
        }
        
        // Validate required fields
        $required = ['name', 'sku', 'price'];
        $errors = [];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }
        
        // Check for duplicate SKU
        if ($this->isDuplicateSku($data['sku'], $id)) {
            $errors[] = 'SKU already exists';
        }
        
        if (!empty($errors)) {
            $this->setFlash('error', implode('<br>', $errors));
            
            // Store form data in session for repopulation
            $_SESSION['form_data'] = $data;
            
            // Redirect back to form
            if ($id) {
                $this->redirect("/admin/products/{$id}/edit");
            } else {
                $this->redirect('/admin/products/create');
            }
            exit();
        }
        
        return $data;
    }
    
    /**
     * Check for duplicate SKU
     */
    protected function isDuplicateSku($sku, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM products WHERE sku = :sku";
        $params = [':sku' => $sku];
        
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params[':id'] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
    
    /**
     * Handle product image upload
     */
    protected function handleImageUpload($productId, $file) {
        $uploadDir = 'public/uploads/products/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        // Generate filename
        $filename = $productId . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $targetPath = $uploadDir . $filename;
        
        // Check file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $fileType = mime_content_type($file['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            throw new \Exception('Invalid file type. Only JPG, PNG and GIF are allowed.');
        }
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            // Update product with image path
            $this->productModel->update($productId, [
                'image' => $filename,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        }
        
        throw new \Exception('Failed to upload image');
    }
}
