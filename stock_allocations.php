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
    
    if ($action === 'allocate_stock') {
        $item_id = isset($_POST['item_id']) ? intval($_POST['item_id']) : 0;
        $user_id_allocate = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;
        // Validate input
        if ($item_id <= 0 || $user_id_allocate <= 0 || $quantity <= 0) {
            $error_message = 'Please select an item, employee, and enter a valid quantity.';
        } else {
            // Check if enough stock is available
            $query = "SELECT current_stock FROM inventory WHERE id = :item_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':item_id', $item_id);
            $stmt->execute();
            $row = $stmt->fetch();
            $current_stock = $row ? intval($row['current_stock']) : 0;
            if ($quantity > $current_stock) {
                $error_message = 'Insufficient stock. Available: ' . $current_stock;
            } elseif ($current_stock <= 0) {
                $error_message = 'No stock available for this item.';
            } else {
                // Create allocation
                $query = "INSERT INTO stock_allocations (item_id, user_id, allocated_quantity, remaining_quantity, allocated_by) VALUES (:item_id, :user_id, :quantity, :quantity, :allocated_by)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':item_id', $item_id);
                $stmt->bindParam(':user_id', $user_id_allocate);
                $stmt->bindParam(':quantity', $quantity);
                $stmt->bindParam(':allocated_by', $_SESSION['user_id']);
                if ($stmt->execute()) {
                    // Update inventory
                    $new_stock = $current_stock - $quantity;
                    $query = "UPDATE inventory SET current_stock = :new_stock WHERE id = :item_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':new_stock', $new_stock);
                    $stmt->bindParam(':item_id', $item_id);
                    $stmt->execute();
                    // Record stock history
                    $history_query = "INSERT INTO stock_history (item_id, movement_type, quantity, previous_stock, new_stock, created_by, notes) VALUES (:item_id, 'allocation', :quantity, :previous_stock, :new_stock, :created_by, 'Stock allocated to employee')";
                    $history_stmt = $db->prepare($history_query);
                    $history_stmt->bindParam(':item_id', $item_id);
                    $history_stmt->bindParam(':quantity', $quantity);
                    $history_stmt->bindParam(':previous_stock', $current_stock);
                    $history_stmt->bindParam(':new_stock', $new_stock);
                    $history_stmt->bindParam(':created_by', $user_id);
                    $history_stmt->execute();
                    $success_message = 'Stock allocated successfully.';
                } else {
                    $error_message = 'Failed to allocate stock. Please try again.';
                }
            }
        }
    } elseif ($action === 'update_status') {
        $allocation_id = intval($_POST['allocation_id']);
        $status = $_POST['status'];
        
        $query = "UPDATE stock_allocations SET status = :status";
        if ($status === 'completed') {
            $query .= ", completed_at = NOW()";
        }
        $query .= " WHERE id = :allocation_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':allocation_id', $allocation_id);
        
        if ($stmt->execute()) {
            $success_message = 'Allocation status updated successfully.';
        } else {
            $error_message = 'Failed to update allocation status.';
        }
    }
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
$where_clause = "WHERE 1=1";
if ($filter === 'active') {
    $where_clause .= " AND sa.status = 'active'";
} elseif ($filter === 'completed') {
    $where_clause .= " AND sa.status = 'completed'";
} elseif ($filter === 'cancelled') {
    $where_clause .= " AND sa.status = 'cancelled'";
}

$query = "SELECT sa.*, i.item_name, i.item_code, u.first_name, u.last_name
          FROM stock_allocations sa 
          JOIN inventory i ON sa.item_id = i.id 
          JOIN users u ON sa.user_id = u.id 
          $where_clause ORDER BY sa.allocated_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$allocations = $stmt->fetchAll();

// Get active employees for allocation
$query = "SELECT id, first_name, last_name FROM users WHERE role = 'employee' AND status = 'active' ORDER BY first_name";
$stmt = $db->prepare($query);
$stmt->execute();
$employees = $stmt->fetchAll();

// Get available inventory items
$query = "SELECT id, item_name, item_code, current_stock FROM inventory WHERE current_stock > 0 ORDER BY item_name";
$stmt = $db->prepare($query);
$stmt->execute();
$inventory_items = $stmt->fetchAll();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Stock Allocations</h2>
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

<!-- Allocate Stock Form -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Allocate Stock to Employee</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <form method="POST">
                            <input type="hidden" name="action" value="allocate_stock">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Select Item *</label>
                                        <select name="item_id" class="form-control" required onchange="updateAvailableStock(this)">
                                            <option value="">Select Item</option>
                                            <?php foreach ($inventory_items as $item): ?>
                                                <option value="<?php echo $item['id']; ?>" data-stock="<?php echo $item['current_stock']; ?>">
                                                    <?php echo $item['item_name'] . ' (' . $item['item_code'] . ') - Stock: ' . $item['current_stock']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Select Employee *</label>
                                        <select name="user_id" class="form-control" required>
                                            <option value="">Select Employee</option>
                                            <?php if (empty($employees)): ?>
                                                <option value="" disabled>No active employees found</option>
                                            <?php else: ?>
                                                <?php foreach ($employees as $employee): ?>
                                                    <option value="<?php echo $employee['id']; ?>">
                                                        <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Quantity *</label>
                                        <input type="number" name="quantity" class="form-control" min="1" required>
                                        <small class="form-text text-muted" id="available-stock-text">Select an item to see available stock</small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Allocate Stock</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Buttons -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Filter Allocations</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-group" role="group">
                            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                            <a href="?filter=active" class="btn <?php echo $filter === 'active' ? 'btn-success' : 'btn-outline-success'; ?>">Active</a>
                            <a href="?filter=completed" class="btn <?php echo $filter === 'completed' ? 'btn-info' : 'btn-outline-info'; ?>">Completed</a>
                            <a href="?filter=cancelled" class="btn <?php echo $filter === 'cancelled' ? 'btn-danger' : 'btn-outline-danger'; ?>">Cancelled</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Allocations Table -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Stock Allocations (<?php echo count($allocations); ?> total)</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Item</th>
                                        <th>Employee</th>
                                        <th>Allocated Qty</th>
                                        <th>Remaining Qty</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($allocations) > 0): ?>
                                        <?php foreach ($allocations as $allocation): ?>
                                            <tr>
                                                <td><?php echo $allocation['id']; ?></td>
                                                <td>
                                                    <strong><?php echo $allocation['item_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $allocation['item_code']; ?></small>
                                                </td>
                                                <td><?php echo $allocation['first_name'] . ' ' . $allocation['last_name']; ?></td>
                                                <td><?php echo $allocation['allocated_quantity']; ?></td>
                                                <td>
                                                    <span class="<?php echo $allocation['remaining_quantity'] == 0 ? 'text-success font-weight-bold' : ''; ?>">
                                                        <?php echo $allocation['remaining_quantity']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $allocation['status'] === 'active' ? 'success' : 
                                                            ($allocation['status'] === 'completed' ? 'info' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($allocation['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($allocation['allocated_at']); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if ($allocation['status'] === 'active'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="allocation_id" value="<?php echo $allocation['id']; ?>">
                                                                <input type="hidden" name="status" value="completed">
                                                                <button type="submit" class="btn btn-info btn-sm" title="Mark as Completed" onclick="return confirm('Mark this allocation as completed?')">
                                                                    <i class="fa fa-check"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="allocation_id" value="<?php echo $allocation['id']; ?>">
                                                                <input type="hidden" name="status" value="cancelled">
                                                                <button type="submit" class="btn btn-danger btn-sm" title="Cancel Allocation" onclick="return confirm('Cancel this allocation?')">
                                                                    <i class="fa fa-times"></i>
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No allocations found</td>
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
function updateAvailableStock(select) {
    const selectedOption = select.options[select.selectedIndex];
    const availableStock = selectedOption.getAttribute('data-stock');
    const stockText = document.getElementById('available-stock-text');
    
    if (availableStock) {
        stockText.textContent = 'Available stock: ' + availableStock + ' units';
        stockText.className = 'form-text text-info';
    } else {
        stockText.textContent = 'Select an item to see available stock';
        stockText.className = 'form-text text-muted';
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
