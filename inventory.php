<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('dashboard.php');
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action === 'add_item') {
        $item_name = sanitizeInput($_POST['item_name']);
        $description = sanitizeInput($_POST['description']);
        $category = sanitizeInput($_POST['category']);
        $unit_price = floatval($_POST['unit_price']);
        $current_stock = intval($_POST['current_stock']);
        $reorder_level = intval($_POST['reorder_level']);
        
        // Generate item code
        $item_code = generateItemCode();
        
        // Check if item code already exists
        $check_query = "SELECT id FROM inventory WHERE item_code = :item_code";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(':item_code', $item_code);
        $check_stmt->execute();
        
        while ($check_stmt->rowCount() > 0) {
            $item_code = generateItemCode();
            $check_stmt->execute();
        }
        
        $query = "INSERT INTO inventory (item_code, item_name, description, category, unit_price, current_stock, reorder_level) 
                 VALUES (:item_code, :item_name, :description, :category, :unit_price, :current_stock, :reorder_level)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':item_code', $item_code);
        $stmt->bindParam(':item_name', $item_name);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':unit_price', $unit_price);
        $stmt->bindParam(':current_stock', $current_stock);
        $stmt->bindParam(':reorder_level', $reorder_level);
        
        if ($stmt->execute()) {
            // Record stock history
            $item_id = $db->lastInsertId();
            $history_query = "INSERT INTO stock_history (item_id, movement_type, quantity, previous_stock, new_stock, created_by, notes) 
                             VALUES (:item_id, 'in', :quantity, 0, :new_stock, :created_by, 'Initial stock')";
            $history_stmt = $db->prepare($history_query);
            $history_stmt->bindParam(':item_id', $item_id);
            $history_stmt->bindParam(':quantity', $current_stock);
            $history_stmt->bindParam(':new_stock', $current_stock);
            $history_stmt->bindParam(':created_by', $user_id);
            $history_stmt->execute();
            
            $success_message = 'Item added successfully.';
        } else {
            $error_message = 'Failed to add item.';
        }
    } elseif ($action === 'update_stock') {
        $item_id = intval($_POST['item_id']);
        $movement_type = $_POST['movement_type'];
        $quantity = intval($_POST['quantity']);
        $notes = sanitizeInput($_POST['notes']);
        // Validate input
        if ($item_id <= 0 || !in_array($movement_type, ['in', 'out']) || $quantity <= 0) {
            $error_message = 'Please enter a valid item, movement type, and quantity (> 0).';
        } else {
            // Get current stock
            $query = "SELECT current_stock FROM inventory WHERE id = :item_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':item_id', $item_id);
            $stmt->execute();
            $row = $stmt->fetch();
            $current_stock = $row ? intval($row['current_stock']) : 0;
            $new_stock = $movement_type === 'in' ? $current_stock + $quantity : $current_stock - $quantity;
            if ($new_stock < 0) {
                $error_message = 'Insufficient stock for this operation.';
            } else {
                // Update inventory
                $query = "UPDATE inventory SET current_stock = :new_stock WHERE id = :item_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':new_stock', $new_stock);
                $stmt->bindParam(':item_id', $item_id);
                if ($stmt->execute()) {
                    // Record stock history
                    $history_query = "INSERT INTO stock_history (item_id, movement_type, quantity, previous_stock, new_stock, created_by, notes) VALUES (:item_id, :movement_type, :quantity, :previous_stock, :new_stock, :created_by, :notes)";
                    $history_stmt = $db->prepare($history_query);
                    $history_stmt->bindParam(':item_id', $item_id);
                    $history_stmt->bindParam(':movement_type', $movement_type);
                    $history_stmt->bindParam(':quantity', $quantity);
                    $history_stmt->bindParam(':previous_stock', $current_stock);
                    $history_stmt->bindParam(':new_stock', $new_stock);
                    $history_stmt->bindParam(':created_by', $user_id);
                    $history_stmt->bindParam(':notes', $notes);
                    $history_stmt->execute();
                    $success_message = 'Stock updated successfully.';
                } else {
                    $error_message = 'Failed to update stock.';
                }
            }
        }
    }
}

// Get inventory items
$query = "SELECT * FROM inventory ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$items = $stmt->fetchAll();

// Get low stock items
$query = "SELECT * FROM inventory WHERE current_stock <= reorder_level ORDER BY current_stock ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$low_stock_items = $stmt->fetchAll();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Inventory Management</h2>
        </div>
    </div>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php endif; ?>

<!-- Low Stock Alert -->
<?php if (count($low_stock_items) > 0): ?>
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-warning" role="alert">
            <h4><i class="fa fa-exclamation-triangle"></i> Low Stock Alert</h4>
            <p>The following items are running low on stock:</p>
            <ul>
                <?php foreach ($low_stock_items as $item): ?>
                    <li><strong><?php echo $item['item_name']; ?></strong> - Only <?php echo $item['current_stock']; ?> units left (Reorder level: <?php echo $item['reorder_level']; ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Add New Item Form -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Add New Item</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <form method="POST">
                            <input type="hidden" name="action" value="add_item">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Item Name *</label>
                                        <input type="text" name="item_name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Category</label>
                                        <input type="text" name="category" class="form-control" placeholder="e.g., Electronics, Furniture">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Unit Price *</label>
                                        <input type="number" name="unit_price" class="form-control" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Initial Stock *</label>
                                        <input type="number" name="current_stock" class="form-control" min="0" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Reorder Level</label>
                                        <input type="number" name="reorder_level" class="form-control" min="0" value="10">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Description</label>
                                        <textarea name="description" class="form-control" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Add Item</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Inventory Table -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Inventory Items (<?php echo count($items); ?> total)</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Item Code</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Unit Price</th>
                                        <th>Current Stock</th>
                                        <th>Reorder Level</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($items) > 0): ?>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td><?php echo $item['item_code']; ?></td>
                                                <td><?php echo $item['item_name']; ?></td>
                                                <td><?php echo $item['category'] ?: 'N/A'; ?></td>
                                                <td><?php echo formatCurrency($item['unit_price']); ?></td>
                                                <td>
                                                    <span class="<?php echo $item['current_stock'] <= $item['reorder_level'] ? 'text-danger font-weight-bold' : ''; ?>">
                                                        <?php echo $item['current_stock']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $item['reorder_level']; ?></td>
                                                <td>
                                                    <?php if ($item['current_stock'] <= $item['reorder_level']): ?>
                                                        <span class="badge badge-warning">Low Stock</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-success">In Stock</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#stockModal<?php echo $item['id']; ?>">
                                                        <i class="fa fa-edit"></i> Update Stock
                                                    </button>
                                                </td>
                                            </tr>
                                            
                                            <!-- Stock Update Modal -->
                                            <div class="modal fade" id="stockModal<?php echo $item['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Update Stock - <?php echo $item['item_name']; ?></h5>
                                                            <button type="button" class="close" data-dismiss="modal">
                                                                <span>&times;</span>
                                                            </button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="update_stock">
                                                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                                
                                                                <div class="form-group">
                                                                    <label>Current Stock: <?php echo $item['current_stock']; ?></label>
                                                                </div>
                                                                
                                                                <div class="form-group">
                                                                    <label>Movement Type</label>
                                                                    <select name="movement_type" class="form-control" required>
                                                                        <option value="in">Stock In (+)</option>
                                                                        <option value="out">Stock Out (-)</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="form-group">
                                                                    <label>Quantity</label>
                                                                    <input type="number" name="quantity" class="form-control" min="1" required>
                                                                </div>
                                                                
                                                                <div class="form-group">
                                                                    <label>Notes</label>
                                                                    <textarea name="notes" class="form-control" rows="3"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Update Stock</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No items found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('[id^=stockModal] .btn-secondary[data-dismiss="modal"]').on('click', function () {
        var modal = $(this).closest('.modal');
        if (typeof $.fn.modal !== 'undefined') {
            modal.modal('hide');
        } else {
            modal[0].style.display = 'none';
            modal[0].classList.remove('show');
        }
        modal.find('form')[0].reset();
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
