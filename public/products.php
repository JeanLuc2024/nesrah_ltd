<?php
// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Include database connection
require_once __DIR__ . '/../app/config/database.php';

// Handle form submission for adding/editing products
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    try {
        // Get and validate input
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $cost = (float)($_POST['cost'] ?? 0);
        $price = (float)($_POST['price'] ?? 0);
        $productId = !empty($_POST['product_id']) ? (int)$_POST['product_id'] : null;
        
        // Validation
        $errors = [];
        
        if (empty($name)) {
            $errors['name'] = 'Product name is required';
        } elseif (strlen($name) > 255) {
            $errors['name'] = 'Product name is too long (max 255 characters)';
        }
        
        if (strlen($description) > 1000) {
            $errors['description'] = 'Description is too long (max 1000 characters)';
        }
        
        if ($cost <= 0) {
            $errors['cost'] = 'Cost must be greater than 0';
        }
        
        if ($price <= 0) {
            $errors['price'] = 'Price must be greater than 0';
        } elseif ($price < $cost) {
            $errors['price'] = 'Price cannot be less than cost';
        }
        
        // If there are validation errors, return them
        if (!empty($errors)) {
            if ($isAjax) {
                header('Content-Type: application/json');
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $errors
                ]);
                exit();
            } else {
                $error = 'Please correct the errors in the form';
                $_SESSION['form_errors'] = $errors;
                $_SESSION['form_data'] = $_POST;
                header('Location: products.php?error=' . urlencode($error));
                exit();
            }
        }
        
        // Get database connection
        $pdo = getDBConnection();
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            if ($productId) {
                // Update existing product
                $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, cost = ?, selling_price = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$name, $description, $cost, $price, $productId]);
                $message = 'Product updated successfully';
            } else {
                // Insert new product
                $stmt = $pdo->prepare("INSERT INTO products (name, description, cost, selling_price, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
                $stmt->execute([$name, $description, $cost, $price]);
                $productId = $pdo->lastInsertId();
                $message = 'Product added successfully';
            }
            
            // Commit transaction
            $pdo->commit();
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => $message,
                    'product_id' => $productId
                ]);
                exit();
            } else {
                // Redirect to refresh the page
                $_SESSION['success'] = $message;
                header('Location: products.php');
                exit();
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (PDOException $e) {
        error_log("Product save error: " . $e->getMessage());
        $error = 'An error occurred while saving the product';
        
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => $error
            ]);
            exit();
        } else {
            $_SESSION['error'] = $error;
            header('Location: products.php?error=' . urlencode($error));
            exit();
        }
    }
}

// Handle product deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    // Check if this is an AJAX request
    $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
              strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    
    try {
        $productId = (int)$_GET['delete'];
        $pdo = getDBConnection();
        
        // Check if product exists and get its details for logging
        $stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            throw new Exception('Product not found');
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        try {
            // Check if product is being used in any loans
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM loans WHERE product_id = ?");
            $stmt->execute([$productId]);
            $loanCount = $stmt->fetch()['count'];
            
            if ($loanCount > 0) {
                throw new Exception('Cannot delete product because it is associated with existing loans');
            }
            
            // Delete the product
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            
            // Log the deletion (you might want to add this to an audit log)
            error_log(sprintf(
                'Product deleted - ID: %d, Name: %s, Deleted by user ID: %d',
                $productId,
                $product['name'],
                $_SESSION['user_id'] ?? 0
            ));
            
            // Commit transaction
            $pdo->commit();
            
            if ($isAjax) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'message' => 'Product deleted successfully'
                ]);
                exit();
            } else {
                $_SESSION['success'] = 'Product deleted successfully';
                header('Location: products.php');
                exit();
            }
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
        
    } catch (Exception $e) {
        error_log("Product delete error: " . $e->getMessage());
        $error = $e->getMessage() ?: 'An error occurred while deleting the product';
        
        if ($isAjax) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $error
            ]);
            exit();
        } else {
            $_SESSION['error'] = $error;
            header('Location: products.php?error=' . urlencode($error));
            exit();
        }
    }
}

// Initialize pagination variables
$perPage = 10; // Number of items per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1
$offset = ($page - 1) * $perPage;

// Get all products from database with pagination
try {
    $pdo = getDBConnection();
    
    // Base query for counting
    $countQuery = "SELECT COUNT(*) as total FROM products";
    $query = "SELECT * FROM products";
    $params = [];
    $whereClause = '';
    
    // Apply search filter
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    if (!empty($search)) {
        $whereClause = " WHERE name LIKE ? OR description LIKE ?";
        $searchTerm = "%$search%";
        $params = array_fill(0, 2, $searchTerm);
    }
    
    // Get total count for pagination
    $countStmt = $pdo->prepare($countQuery . $whereClause);
    $countStmt->execute($params);
    $totalProducts = (int)$countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = max(1, ceil($totalProducts / $perPage));
    
    // Ensure page doesn't exceed total pages
    $page = min($page, $totalPages);
    $offset = ($page - 1) * $perPage;
    
    // Apply sorting
    $sort = $_GET['sort'] ?? 'id';
    $order = strtoupper($_GET['order'] ?? 'DESC');
    $validSorts = ['id', 'name', 'cost', 'selling_price', 'created_at'];
    $validOrders = ['ASC', 'DESC'];
    
    $sortClause = '';
    if (in_array($sort, $validSorts) && in_array($order, $validOrders)) {
        $sortClause = " ORDER BY $sort $order";
    } else {
        $sortClause = " ORDER BY id DESC"; // Default sort
    }
    
    // Add pagination
    $paginationClause = " LIMIT ? OFFSET ?";
    
    // Prepare and execute the query
    $stmt = $pdo->prepare($query . $whereClause . $sortClause . $paginationClause);
    
    // Add pagination parameters to existing params
    if (!empty($params)) {
        $params = array_merge($params, [$perPage, $offset]);
    } else {
        $params = [$perPage, $offset];
    }
    
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Products fetch error: " . $e->getMessage());
    $error = 'An error occurred while fetching products';
    $products = [];
    $totalProducts = 0;
    $totalPages = 1;
}

// Set page title
$page_title = 'Product Management';

// Include header and sidebar
include 'includes/header.php';
include 'includes/sidebar.php';

// Start output buffering
ob_start();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Product Management</h5>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
            <i class="fas fa-plus me-2"></i> Add Product
        </button>
    </div>
    
    <div class="card-body">
        <!-- Search and Filter -->
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="input-group">
                    <input 
                        type="search" 
                        id="searchInput" 
                        class="form-control" 
                        placeholder="Search products..." 
                        value="<?= htmlspecialchars($search) ?>"
                        autocomplete="off"
                        aria-label="Search products"
                    >
                    <?php if (!empty($search)): ?>
                        <button class="btn btn-outline-secondary search-clear" type="button" title="Clear search">
                            <i class="fas fa-times"></i>
                        </button>
                    <?php endif; ?>
                    <button class="btn btn-primary" type="button" id="searchButton" title="Search">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                <?php if (!empty($search)): ?>
                    <div class="mt-2 small text-muted">
                        Showing results for: <strong><?= htmlspecialchars($search) ?></strong>
                        <a href="products.php" class="ms-2">(Clear search)</a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-6 text-md-end">
                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">
                    <i class="fas fa-plus me-1"></i> Add Product
                </button>
            </div>
        </div>
        
        <!-- Products Table -->
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>
                            <a href="?sort=id&order=<?= ($sort === 'id' && $order === 'ASC') ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search) ?>&page=<?= $page ?>" class="text-decoration-none text-dark">
                                ID <?= $sort === 'id' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=name&order=<?= ($sort === 'name' && $order === 'ASC') ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search) ?>&page=<?= $page ?>" class="text-decoration-none text-dark">
                                Name <?= $sort === 'name' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th>Description</th>
                        <th class="text-end">
                            <a href="?sort=cost&order=<?= ($sort === 'cost' && $order === 'ASC') ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search) ?>&page=<?= $page ?>" class="text-decoration-none text-dark">
                                Cost <?= $sort === 'cost' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th class="text-end">
                            <a href="?sort=selling_price&order=<?= ($sort === 'selling_price' && $order === 'ASC') ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search) ?>&page=<?= $page ?>" class="text-decoration-none text-dark">
                                Price <?= $sort === 'selling_price' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th>
                            <a href="?sort=created_at&order=<?= ($sort === 'created_at' && $order === 'ASC') ? 'DESC' : 'ASC' ?>&search=<?= urlencode($search) ?>&page=<?= $page ?>" class="text-decoration-none text-dark">
                                Created <?= $sort === 'created_at' ? ($order === 'ASC' ? '↑' : '↓') : '' ?>
                            </a>
                        </th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4">
                            <div class="text-muted">
                                <i class="fas fa-box-open fa-2x mb-2 d-block"></i>
                                No products found
                            </div>
                            <?php if (!empty($search)): ?>
                                <a href="?" class="btn btn-sm btn-outline-primary mt-2">
                                    <i class="fas fa-times me-1"></i> Clear search
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                        <tr class="align-middle">
                            <td class="text-muted">#<?= $product['id'] ?></td>
                            <td>
                                <div class="fw-medium"><?= htmlspecialchars($product['name']) ?></div>
                            </td>
                            <td>
                                <div class="text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($product['description']) ?>">
                                    <?= htmlspecialchars($product['description'] ?: 'No description') ?>
                                </div>
                            </td>
                            <td class="text-end text-muted">
                                $<?= number_format($product['cost'], 2) ?>
                            </td>
                            <td class="text-end fw-medium text-success">
                                $<?= number_format($product['selling_price'], 2) ?>
                            </td>
                            <td>
                                <div class="small text-muted">
                                    <i class="far fa-calendar-alt me-1"></i>
                                    <?= date('M j, Y', strtotime($product['created_at'])) ?>
                                </div>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button class="btn btn-outline-primary edit-product" 
                                            data-id="<?= $product['id'] ?>"
                                            data-name="<?= htmlspecialchars($product['name']) ?>"
                                            data-description="<?= htmlspecialchars($product['description']) ?>"
                                            data-cost="<?= $product['cost'] ?>"
                                            data-price="<?= $product['selling_price'] ?>"
                                            title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-outline-danger delete-product" 
                                            data-id="<?= $product['id'] ?>"
                                            data-name="<?= htmlspecialchars($product['name']) ?>"
                                            title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Products pagination" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&sort=<?= $sort ?>&order=<?= $order ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-angle-left"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-double-left"></i></span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-left"></i></span>
                            </li>
                        <?php endif; ?>
                        
                        <?php
                        // Calculate start and end page numbers
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $startPage + 4);
                        $startPage = max(1, $endPage - 4);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&sort=<?= $sort ?>&order=<?= $order ?>&search=<?= urlencode($search) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&sort=<?= $sort ?>&order=<?= $order ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $totalPages ?>&sort=<?= $sort ?>&order=<?= $order ?>&search=<?= urlencode($search) ?>">
                                    <i class="fas fa-angle-double-right"></i>
                                </a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-right"></i></span>
                            </li>
                            <li class="page-item disabled">
                                <span class="page-link"><i class="fas fa-angle-double-right"></i></span>
                            </li>
                        <?php endif; ?>
                    </ul>
                    <div class="text-center text-muted small">
                        Showing <?= count($products) ?> of <?= number_format($totalProducts) ?> products
                    </div>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add/Edit Product Modal -->
<!-- Add/Edit Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addProductModalLabel">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="productForm" method="POST">
                <input type="hidden" name="product_id" id="productId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required>
                        <div class="invalid-feedback">Please enter a product name</div>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cost" class="form-label">Cost Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="cost" name="cost" step="0.01" min="0.01" required>
                                    <div class="invalid-feedback">Please enter a valid cost</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="price" class="form-label">Selling Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0.01" required>
                                    <div class="invalid-feedback">Please enter a valid price</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">Save Product</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirm Deletion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the product <strong id="deleteProductName"></strong>?</p>
                <p class="text-muted small mb-0">This action cannot be undone and will permanently remove the product from the system.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    <span class="btn-text">
                        <i class="fas fa-trash-alt me-1"></i> Delete Product
                    </span>
                </button>
            </div>

<script>
// Initialize Bootstrap tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
{{ ... }}
    return new bootstrap.Tooltip(tooltipTriggerEl);
});

// Handle add product button
document.addEventListener('DOMContentLoaded', function() {
    const addProductModal = new bootstrap.Modal(document.getElementById('addProductModal'));
    const productForm = document.getElementById('productForm');
    
    // Show success/error messages
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        showAlert('success', urlParams.get('success'));
    } else if (urlParams.has('error')) {
        showAlert('danger', urlParams.get('error'));
    }
    
    // Handle add product button
    document.querySelector('[data-bs-target="#addProductModal"]').addEventListener('click', function() {
        // Reset form
        productForm.reset();
        
        // Set modal title
        document.getElementById('addProductModalLabel').textContent = 'Add New Product';
        
        // Clear product ID
        document.getElementById('productId').value = '';
        
        // Update form action and button text
        const submitBtn = productForm.querySelector('button[type="submit"]');
        submitBtn.setAttribute('data-original-text', 'Add Product');
        submitBtn.querySelector('.btn-text').textContent = 'Add Product';
        productForm.action = 'products.php';
        
        // Reset validation
        productForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        
        // Set default values
        document.getElementById('cost').value = '0.00';
        document.getElementById('price').value = '0.00';
    });
    
    // Handle edit button click
    document.querySelectorAll('.edit-product').forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.getAttribute('data-id');
            const productName = this.getAttribute('data-name');
            const productDesc = this.getAttribute('data-description');
            const productCost = this.getAttribute('data-cost');
            const productPrice = this.getAttribute('data-price');
            
            // Set form values
            document.getElementById('addProductModalLabel').textContent = 'Edit Product';
            document.getElementById('productId').value = productId;
            document.getElementById('name').value = productName || '';
            document.getElementById('description').value = productDesc || '';
            document.getElementById('cost').value = productCost || '0.00';
            document.getElementById('price').value = productPrice || '0.00';
            
            // Update form action and button text
            const submitBtn = productForm.querySelector('button[type="submit"]');
            submitBtn.setAttribute('data-original-text', 'Update Product');
            submitBtn.querySelector('.btn-text').textContent = 'Update Product';
            productForm.action = `products.php?edit=${productId}`;
            
            // Reset validation
            productForm.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            
            // Show the modal
            addProductModal.show();
        });
    });
    
    // Handle delete product button
    let productToDelete = null;
    let productToDeleteName = '';
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const deleteProductNameEl = document.getElementById('deleteProductName');
    const confirmDeleteBtn = document.getElementById('confirmDelete');
    
    // Handle delete button clicks
    document.querySelectorAll('.delete-product').forEach(button => {
        button.addEventListener('click', function() {
            productToDelete = this.getAttribute('data-id');
            productToDeleteName = this.getAttribute('data-name') || 'this product';
            
            // Update the modal content with the product name
            deleteProductNameEl.textContent = `"${productToDeleteName}"`;
            
            // Reset button state
            const spinner = confirmDeleteBtn.querySelector('.spinner-border');
            const btnText = confirmDeleteBtn.querySelector('.btn-text');
            
            if (spinner) spinner.classList.add('d-none');
            if (btnText) btnText.textContent = 'Delete Product';
            
            confirmDeleteBtn.disabled = false;
            
            // Show the modal
            deleteModal.show();
        });
    });
    
    // Reset modal when hidden
    document.getElementById('deleteModal').addEventListener('hidden.bs.modal', function () {
        productToDelete = null;
        productToDeleteName = '';
        deleteProductNameEl.textContent = '';
    });
    
    // Handle delete confirmation
    confirmDeleteBtn.addEventListener('click', function() {
        if (!productToDelete) return;
        
        // Show loading state
        const spinner = this.querySelector('.spinner-border');
        const btnText = this.querySelector('.btn-text');
        
        spinner.classList.remove('d-none');
        btnText.textContent = 'Deleting...';
        this.disabled = true;
        
        // Add X-Requested-With header for AJAX detection
        const headers = new Headers();
        headers.append('X-Requested-With', 'XMLHttpRequest');
        
        // Send delete request
        fetch(`products.php?delete=${productToDelete}`, {
            method: 'GET',
            headers: headers
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Failed to delete product');
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                // Close modal and reload after a short delay
                deleteModal.hide();
                setTimeout(() => window.location.reload(), 1000);
            } else {
                throw new Error(data.message || 'Failed to delete product');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const errorMessage = error.message || 'An error occurred while deleting the product';
            showAlert('danger', errorMessage);
            
            // Reset button state
            const spinner = confirmDeleteBtn.querySelector('.spinner-border');
            const btnText = confirmDeleteBtn.querySelector('.btn-text');
            
            if (spinner) spinner.classList.add('d-none');
            if (btnText) btnText.textContent = 'Delete Product';
            
            confirmDeleteBtn.disabled = false;
            
            // Close the modal
            deleteModal.hide();
        })
        .finally(() => {
            // Reset button state
            confirmDeleteBtn.disabled = false;
            confirmDeleteBtn.innerHTML = originalBtnText;
            productToDelete = null;
{{ ... }}
        });
    });
    
    // Debounce function to limit how often search is triggered
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Handle search
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    
    function performSearch() {
        const searchTerm = searchInput.value.trim();
        const url = new URL(window.location.href);
        
        // Show loading state
        const originalBtnText = searchButton.innerHTML;
        searchButton.disabled = true;
        searchButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>';
        
        // Update URL parameters
        if (searchTerm) {
            url.searchParams.set('search', searchTerm);
        } else {
            url.searchParams.delete('search');
        }
        
        // Remove page parameter when searching
        url.searchParams.delete('page');
        
        // Update URL without page reload if using History API is desired
        // window.history.pushState({}, '', url);
        
        // Reload the page with new search parameters
        window.location.href = url.toString();
    }
    
    // Add click event to search button
    searchButton.addEventListener('click', debounce(performSearch, 300));
    
    // Handle Enter key in search input
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            performSearch();
        }
    });
    
    // Optional: Auto-search when user stops typing (after 500ms of inactivity)
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            performSearch();
        }, 500);
    });
    
    // Clear search button
    const clearSearchBtn = document.querySelector('.search-clear');
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            performSearch();
        });
    }
    
    // Function to show alert messages
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.role = 'alert';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        const container = document.querySelector('.container');
        container.insertBefore(alertDiv, container.firstChild);
        
        // Auto-remove alert after 5 seconds
        setTimeout(() => {
            const alert = bootstrap.Alert.getOrCreateInstance(alertDiv);
            alert.close();
        }, 5000);
    }
    });
    
    // Handle delete product button
    let productToDelete = null;
    document.querySelectorAll('.delete-product').forEach(button => {
        button.addEventListener('click', function() {
            productToDelete = this.getAttribute('data-id');
            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        });
    });
    
    // Confirm delete
    document.getElementById('confirmDelete').addEventListener('click', function() {
        if (productToDelete) {
            // Send AJAX request to delete the product
            fetch('api/delete_product.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=${productToDelete}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error deleting product: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('danger', 'An error occurred while deleting the product');
            });
        }
    });
    
    // Close any open modals when clicking outside
    document.querySelectorAll('.modal').forEach(modalEl => {
        modalEl.addEventListener('hidden.bs.modal', function () {
            // Reset form when modal is closed
            if (modalEl.id === 'addProductModal') {
                productForm.reset();
                document.getElementById('productId').value = '';
            }
        });
    });
    
    // Show success/error messages from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const successMessage = urlParams.get('success');
    const errorMessage = urlParams.get('error');
    
    if (successMessage) {
        showAlert('success', successMessage);
        // Remove success message from URL without page reload
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
    
    if (errorMessage) {
        showAlert('danger', errorMessage);
        // Remove error message from URL without page reload
        const newUrl = window.location.pathname;
        window.history.replaceState({}, document.title, newUrl);
    }
    
    // Handle form submission
    if (productForm) {
        productForm.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // Get form data
            const formData = new FormData(productForm);
            const productId = formData.get('id');
            const url = productId ? `products.php?edit=${productId}` : 'products.php';
            
            // Basic client-side validation
            const name = formData.get('name')?.trim();
            const cost = parseFloat(formData.get('cost'));
            const price = parseFloat(formData.get('price'));
            
            if (!name) {
                showAlert('warning', 'Please enter a product name');
                return;
            }
            
            if (isNaN(cost) || cost <= 0) {
                showAlert('warning', 'Please enter a valid cost');
                return;
            }
            
            if (isNaN(price) || price <= 0) {
                showAlert('warning', 'Please enter a valid price');
                return;
            }
            
            if (price < cost) {
                showAlert('warning', 'Selling price cannot be less than cost');
                return;
            }
            
            // Show loading state
            const submitBtn = productForm.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.spinner-border');
            const btnText = submitBtn.querySelector('.btn-text');
            
            submitBtn.disabled = true;
            spinner.classList.remove('d-none');
            btnText.textContent = 'Processing...';
            
            // Add X-Requested-With header for AJAX detection
            const headers = new Headers();
            headers.append('X-Requested-With', 'XMLHttpRequest');
            
            // Submit the form
            fetch(url, {
                method: 'POST',
                headers: headers,
                body: formData
            })
            .then(async response => {
                const data = await response.json();
                
                if (!response.ok) {
                    // Handle validation errors
                    if (response.status === 400 && data.errors) {
                        // Clear previous error states
                        productForm.querySelectorAll('.is-invalid').forEach(el => {
                            el.classList.remove('is-invalid');
                            const feedback = el.nextElementSibling;
                            if (feedback && feedback.classList.contains('invalid-feedback')) {
                                feedback.textContent = '';
                            }
                        });
                        
                        // Show validation errors
                        Object.entries(data.errors).forEach(([field, message]) => {
                            const input = productForm.querySelector(`[name="${field}"]`);
                            if (input) {
                                input.classList.add('is-invalid');
                                const feedback = input.nextElementSibling;
                                if (feedback && feedback.classList.contains('invalid-feedback')) {
                                    feedback.textContent = message;
                                }
                            } else {
                                // If we can't find the specific field, show a general error
                                showAlert('warning', message);
                            }
                        });
                        throw new Error('Validation failed');
                    }
                    throw new Error(data.message || 'Failed to save product');
                }
                
                return data;
            })
            .then(data => {
                showAlert('success', data.message);
                // Close modal and reload after a short delay
                addProductModal.hide();
                setTimeout(() => window.location.reload(), 1000);
            })
            .catch(error => {
                console.error('Error:', error);
                if (error.message !== 'Validation failed') {
                    showAlert('danger', error.message || 'An error occurred while saving the product');
                }
            })
            .finally(() => {
                // Reset button state
                submitBtn.disabled = false;
                spinner.classList.add('d-none');
                btnText.textContent = submitBtn.getAttribute('data-original-text') || 'Save Product';
            });
        });
    }
    
    // Allow pressing Enter in search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchButton = document.getElementById('searchButton');
                if (searchButton) searchButton.click();
            }
        });
    }
});
</script>
