<?php $this->extend('admin/layouts/main'); ?>

<?php $this->section('title', isset($product['id']) ? 'Edit Product' : 'Add New Product'); ?>

<?php $this->section(isset($product['id']) ? 'active_products_edit' : 'active_products_create', 'active'); ?>

<?php $this->section('header_actions'); ?>
    <a href="/admin/products" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i> Back to Products
    </a>
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?= isset($product['id']) ? 'Edit Product' : 'Add New Product' ?></h5>
            </div>
            <form action="<?= isset($product['id']) ? "/admin/products/{$product['id']}" : '/admin/products' ?>" method="POST" enctype="multipart/form-data">
                <?php if (isset($product['id'])): ?>
                    <input type="hidden" name="_method" value="PUT">
                <?php endif; ?>
                
                <?= csrf_field() ?>
                
                <div class="card-body">
                    <?php if (session()->has('errors')): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach (session('errors') as $error): ?>
                                    <li><?= $error ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Basic Information -->
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Basic Information</h6>
                                
                                <div class="mb-3">
                                    <label for="name" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?= session('errors.name') ? 'is-invalid' : '' ?>" 
                                           id="name" name="name" required 
                                           value="<?= old('name', $product['name'] ?? '') ?>">
                                    <?php if (session('errors.name')): ?>
                                        <div class="invalid-feedback">
                                            <?= session('errors.name') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="sku" class="form-label">SKU <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control <?= session('errors.sku') ? 'is-invalid' : '' ?>" 
                                                   id="sku" name="sku" required
                                                   value="<?= old('sku', $product['sku'] ?? '') ?>">
                                            <?php if (session('errors.sku')): ?>
                                                <div class="invalid-feedback">
                                                    <?= session('errors.sku') ?>
                                                </div>
                                            <?php endif; ?>
                                            <small class="text-muted">Unique product identifier</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="category_id" class="form-label">Category</label>
                                            <select class="form-select <?= session('errors.category_id') ? 'is-invalid' : '' ?>" 
                                                    id="category_id" name="category_id">
                                                <option value="">-- Select Category --</option>
                                                <?php foreach ($categories as $category): ?>
                                                    <option value="<?= $category['id'] ?>"
                                                        <?= (old('category_id', $product['category_id'] ?? '') == $category['id']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($category['name']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (session('errors.category_id')): ?>
                                                <div class="invalid-feedback">
                                                    <?= session('errors.category_id') ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control <?= session('errors.description') ? 'is-invalid' : '' ?>" 
                                              id="description" name="description" 
                                              rows="3"><?= old('description', $product['description'] ?? '') ?></textarea>
                                    <?php if (session('errors.description')): ?>
                                        <div class="invalid-feedback">
                                            <?= session('errors.description') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Pricing -->
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Pricing</h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="price" class="form-label">Price <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" min="0" 
                                                       class="form-control <?= session('errors.price') ? 'is-invalid' : '' ?>" 
                                                       id="price" name="price" required
                                                       value="<?= old('price', $product['price'] ?? '0.00') ?>">
                                            </div>
                                            <?php if (session('errors.price')): ?>
                                                <div class="invalid-feedback d-block">
                                                    <?= session('errors.price') ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="cost_price" class="form-label">Cost Price</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input type="number" step="0.01" min="0" 
                                                       class="form-control <?= session('errors.cost_price') ? 'is-invalid' : '' ?>" 
                                                       id="cost_price" name="cost_price"
                                                       value="<?= old('cost_price', $product['cost_price'] ?? '0.00') ?>">
                                            </div>
                                            <small class="text-muted">For internal use only</small>
                                            <?php if (session('errors.cost_price')): ?>
                                                <div class="invalid-feedback d-block">
                                                    <?= session('errors.cost_price') ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="taxable" name="taxable" value="1"
                                           <?= (old('taxable', $product['taxable'] ?? 0) ? 'checked' : '') ?>>
                                    <label class="form-check-label" for="taxable">Taxable</label>
                                </div>
                            </div>
                            
                            <!-- Inventory -->
                            <div class="mb-4">
                                <h6 class="border-bottom pb-2 mb-3">Inventory</h6>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="quantity_available" class="form-label">Quantity Available</label>
                                            <input type="number" min="0" step="1" 
                                                   class="form-control <?= session('errors.quantity_available') ? 'is-invalid' : '' ?>" 
                                                   id="quantity_available" name="quantity_available"
                                                   value="<?= old('quantity_available', $product['quantity_available'] ?? 0) ?>">
                                            <?php if (session('errors.quantity_available')): ?>
                                                <div class="invalid-feedback">
                                                    <?= session('errors.quantity_available') ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                                            <input type="number" min="0" step="1" 
                                                   class="form-control" 
                                                   id="low_stock_threshold" name="low_stock_threshold"
                                                   value="<?= old('low_stock_threshold', $product['low_stock_threshold'] ?? 5) ?>">
                                            <small class="text-muted">Get notified when stock is low</small>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="track_inventory" name="track_inventory" value="1"
                                           <?= (old('track_inventory', $product['track_inventory'] ?? 1) ? 'checked' : '') ?>>
                                    <label class="form-check-label" for="track_inventory">Track inventory for this product</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Product Image -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Product Image</h6>
                                </div>
                                <div class="card-body text-center">
                                    <div class="mb-3">
                                        <?php if (!empty($product['image'])): ?>
                                            <img id="imagePreview" src="/uploads/products/<?= $product['image'] ?>" 
                                                 class="img-fluid rounded" style="max-height: 200px;" alt="Product Image">
                                        <?php else: ?>
                                            <div id="imagePreview" class="bg-light d-flex align-items-center justify-content-center" 
                                                 style="width: 100%; height: 200px; border: 1px dashed #ddd; border-radius: 5px;">
                                                <div class="text-center p-3">
                                                    <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                                    <p class="mb-0 text-muted">No image selected</p>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="d-grid">
                                        <input type="file" class="d-none" id="image" name="image" accept="image/*">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('image').click()">
                                            <i class="fas fa-upload me-2"></i> Upload Image
                                        </button>
                                        <?php if (!empty($product['image'])): ?>
                                            <button type="button" class="btn btn-outline-danger btn-sm mt-2" id="removeImage">
                                                <i class="fas fa-trash me-2"></i> Remove Image
                                            </button>
                                            <input type="hidden" name="remove_image" id="remove_image" value="0">
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted d-block mt-2">Recommended size: 800x800px</small>
                                    <?php if (session('errors.image')): ?>
                                        <div class="text-danger small mt-2">
                                            <?= session('errors.image') ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Status -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0">Status</h6>
                                </div>
                                <div class="card-body">
                                    <div class="form-check form-switch mb-3">
                                        <input class="form-check-input" type="checkbox" id="status" name="status" value="active"
                                               <?= (old('status', $product['status'] ?? 'active') === 'active' ? 'checked' : '') ?>>
                                        <label class="form-check-label" for="status">Active</label>
                                    </div>
                                    <p class="small text-muted mb-0">
                                        Inactive products won't be visible to customers.
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Organization -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0">Organization</h6>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <label for="barcode" class="form-label">Barcode (ISBN, UPC, etc.)</label>
                                        <input type="text" class="form-control" id="barcode" name="barcode"
                                               value="<?= old('barcode', $product['barcode'] ?? '') ?>">
                                    </div>
                                    
                                    <div class="mb-0">
                                        <label for="tags" class="form-label">Tags</label>
                                        <input type="text" class="form-control" id="tags" name="tags"
                                               value="<?= old('tags', $product['tags'] ?? '') ?>">
                                        <small class="text-muted">Separate tags with commas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card-footer bg-transparent border-top">
                    <div class="d-flex justify-content-between">
                        <a href="/admin/products" class="btn btn-outline-secondary">
                            <i class="fas fa-times me-2"></i> Cancel
                        </a>
                        <div>
                            <button type="submit" name="save_and_new" value="1" class="btn btn-outline-primary me-2">
                                <i class="fas fa-save me-2"></i> Save & New
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i> Save Product
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $this->section('scripts'); ?>
<script>
    // Image preview
    document.addEventListener('DOMContentLoaded', function() {
        const imageInput = document.getElementById('image');
        const imagePreview = document.getElementById('imagePreview');
        const removeImageBtn = document.getElementById('removeImage');
        const removeImageInput = document.getElementById('remove_image');
        
        if (imageInput) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (imagePreview.tagName === 'IMG') {
                            imagePreview.src = e.target.result;
                        } else {
                            const img = document.createElement('img');
                            img.id = 'imagePreview';
                            img.src = e.target.result;
                            img.className = 'img-fluid rounded';
                            img.style.maxHeight = '200px';
                            img.alt = 'Product Image';
                            
                            // Replace the div with the new image
                            const parent = imagePreview.parentNode;
                            parent.removeChild(imagePreview);
                            parent.appendChild(img);
                            
                            // Show remove button if not already shown
                            if (removeImageBtn) {
                                removeImageBtn.style.display = 'block';
                            } else if (removeImageInput) {
                                // If we're adding an image to a product that didn't have one before
                                const removeBtn = document.createElement('button');
                                removeBtn.type = 'button';
                                removeBtn.className = 'btn btn-outline-danger btn-sm mt-2';
                                removeBtn.id = 'removeImage';
                                removeBtn.innerHTML = '<i class="fas fa-trash me-2"></i> Remove Image';
                                removeBtn.onclick = function() {
                                    document.getElementById('image').value = '';
                                    parent.removeChild(img);
                                    
                                    const div = document.createElement('div');
                                    div.id = 'imagePreview';
                                    div.className = 'bg-light d-flex align-items-center justify-content-center';
                                    div.style.width = '100%';
                                    div.style.height = '200px';
                                    div.style.border = '1px dashed #ddd';
                                    div.style.borderRadius = '5px';
                                    div.innerHTML = `
                                        <div class="text-center p-3">
                                            <i class="fas fa-image fa-3x text-muted mb-2"></i>
                                            <p class="mb-0 text-muted">No image selected</p>
                                        </div>
                                    `;
                                    
                                    parent.insertBefore(div, removeBtn);
                                    parent.removeChild(removeBtn);
                                    
                                    // Add hidden input to indicate image removal
                                    if (removeImageInput) {
                                        removeImageInput.value = '1';
                                    }
                                };
                                
                                const uploadBtn = parent.querySelector('button');
                                parent.insertBefore(removeBtn, uploadBtn.nextSibling);
                            }
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });
        }
        
        // Handle remove image button
        if (removeImageBtn && removeImageInput) {
            removeImageBtn.addEventListener('click', function() {
                const parent = imagePreview.parentNode;
                parent.removeChild(imagePreview);
                
                const div = document.createElement('div');
                div.id = 'imagePreview';
                div.className = 'bg-light d-flex align-items-center justify-content-center';
                div.style.width = '100%';
                div.style.height = '200px';
                div.style.border = '1px dashed #ddd';
                div.style.borderRadius = '5px';
                div.innerHTML = `
                    <div class="text-center p-3">
                        <i class="fas fa-image fa-3x text-muted mb-2"></i>
                        <p class="mb-0 text-muted">No image selected</p>
                    </div>
                `;
                
                parent.insertBefore(div, removeImageBtn);
                removeImageBtn.style.display = 'none';
                
                // Clear file input
                if (imageInput) {
                    imageInput.value = '';
                }
                
                // Set remove image flag
                removeImageInput.value = '1';
            });
        }
        
        // Initialize tag input
        if (document.getElementById('tags')) {
            // This would be replaced with a proper tag input library in a real application
            // For example: https://github.com/developit/tag-input-element
            // Or using a library like Select2, Tagify, etc.
            console.log('Tag input would be initialized here with a proper library');
        }
    });
</script>
<?php $this->endSection(); ?>

<?php $this->endSection(); ?>
