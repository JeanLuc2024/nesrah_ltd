<?php $this->extend('admin/layouts/main'); ?>

<?php $this->section('title', 'Manage Products'); ?>

<?php $this->section('active_products', 'active'); ?>

<?php $this->section('header_actions'); ?>
    <a href="/admin/products/create" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i> Add New Product
    </a>
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Products List</h5>
            <div class="d-flex
                <form action="/admin/products" method="get" class="d-flex">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search products..." value="<?= $filters['search'] ?? '' ?>">
                        <button class="btn btn-outline-secondary" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                        <?php if (!empty($filters['search']) || !empty($filters['category']) || !empty($filters['status'])): ?>
                            <a href="/admin/products" class="btn btn-outline-danger">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card-body">
        <?php if (session()->has('success')): ?>
            <div class="alert alert-success">
                <?= session('success') ?>
            </div>
        <?php endif; ?>
        
        <?php if (session()->has('error')): ?>
            <div class="alert alert-danger">
                <?= session('error') ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($products)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Image</th>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td>#<?= $product['id'] ?></td>
                                <td>
                                    <?php if (!empty($product['image'])): ?>
                                        <img src="/uploads/products/<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                            <i class="fas fa-box text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-medium"><?= htmlspecialchars($product['name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($product['category_name'] ?? 'Uncategorized') ?></small>
                                </td>
                                <td><?= htmlspecialchars($product['sku']) ?></td>
                                <td>$<?= number_format($product['price'], 2) ?></td>
                                <td>
                                    <?php if ($product['quantity_available'] > 0): ?>
                                        <span class="badge bg-success">In Stock (<?= $product['quantity_available'] ?>)</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Out of Stock</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= $product['status'] === 'active' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($product['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($product['created_at'])) ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="/admin/products/<?= $product['id'] ?>" class="btn btn-outline-primary" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/admin/products/<?= $product['id'] ?>/edit" class="btn btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger delete-product" data-id="<?= $product['id'] ?>" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['current_page'] > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?>" aria-label="Previous">
                                    <span aria-hidden="true">&laquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?= $i == $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?><?= !empty($filters['search']) ? '&search=' . urlencode($filters['search']) : '' ?>" aria-label="Next">
                                    <span aria-hidden="true">&raquo;</span>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-box-open fa-4x text-muted"></i>
                </div>
                <h4>No products found</h4>
                <p class="text-muted">There are no products matching your criteria.</p>
                <a href="/admin/products/create" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-2"></i> Add Your First Product
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteProductModal" tabindex="-1" aria-labelledby="deleteProductModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteProductModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this product? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form id="deleteProductForm" method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-danger">Delete Product</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $this->section('scripts'); ?>
<script>
    // Delete product confirmation
    document.addEventListener('DOMContentLoaded', function() {
        const deleteButtons = document.querySelectorAll('.delete-product');
        const deleteForm = document.getElementById('deleteProductForm');
        
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-id');
                deleteForm.action = `/admin/products/${productId}`;
                
                const modal = new bootstrap.Modal(document.getElementById('deleteProductModal'));
                modal.show();
            });
        });
        
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
<?php $this->endSection(); ?>

<?php $this->endSection(); ?>
