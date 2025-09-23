<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is employee
if (!isEmployee()) {
    redirect('dashboard.php');
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_POST) {
    $action = $_POST['action'];
    
    if ($action === 'request_stock') {
        $item_id = intval($_POST['item_id']);
        $requested_quantity = intval($_POST['requested_quantity']);
        $reason = sanitizeInput($_POST['reason']);
        
        $query = "INSERT INTO stock_requests (item_id, user_id, requested_quantity, reason) 
                 VALUES (:item_id, :user_id, :requested_quantity, :reason)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':requested_quantity', $requested_quantity);
        $stmt->bindParam(':reason', $reason);
        
        if ($stmt->execute()) {
            $success_message = 'Stock request submitted successfully.';
        } else {
            $error_message = 'Failed to submit stock request.';
        }
    }
}

// Get my stock requests
$query = "SELECT sr.*, i.item_name, i.item_code, i.current_stock
          FROM stock_requests sr 
          JOIN inventory i ON sr.item_id = i.id 
          WHERE sr.user_id = :user_id 
          ORDER BY sr.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_requests = $stmt->fetchAll();

// Get available inventory items
$query = "SELECT id, item_name, item_code, current_stock FROM inventory ORDER BY item_name";
$stmt = $db->prepare($query);
$stmt->execute();
$inventory_items = $stmt->fetchAll();

// Get my current stock allocations
$query = "SELECT sa.*, i.item_name, i.item_code
          FROM stock_allocations sa 
          JOIN inventory i ON sa.item_id = i.id 
          WHERE sa.user_id = :user_id AND sa.status = 'active' 
          ORDER BY sa.allocated_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_allocations = $stmt->fetchAll();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Stock Management</h2>
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

<!-- My Current Stock Allocations -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>My Current Stock Allocations</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <?php if (count($my_allocations) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Allocated Qty</th>
                                            <th>Remaining Qty</th>
                                            <th>Allocated Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($my_allocations as $allocation): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $allocation['item_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $allocation['item_code']; ?></small>
                                                </td>
                                                <td><?php echo $allocation['allocated_quantity']; ?></td>
                                                <td>
                                                    <span class="<?php echo $allocation['remaining_quantity'] == 0 ? 'text-success font-weight-bold' : ''; ?>">
                                                        <?php echo $allocation['remaining_quantity']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($allocation['allocated_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No stock allocations found. Contact administrator to get stock allocated.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Request Stock Form -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Request Additional Stock</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <form method="POST">
                            <input type="hidden" name="action" value="request_stock">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Select Item *</label>
                                        <select name="item_id" class="form-control" required onchange="updateAvailableStock(this)">
                                            <option value="">Select Item</option>
                                            <?php foreach ($inventory_items as $item): ?>
                                                <option value="<?php echo $item['id']; ?>" data-stock="<?php echo $item['current_stock']; ?>">
                                                    <?php echo $item['item_name'] . ' (' . $item['item_code'] . ') - Available: ' . $item['current_stock']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Requested Quantity *</label>
                                        <input type="number" name="requested_quantity" class="form-control" min="1" required>
                                        <small class="form-text text-muted" id="available-stock-text">Select an item to see available stock</small>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Reason for Request *</label>
                                <textarea name="reason" class="form-control" rows="3" placeholder="Please explain why you need additional stock..." required></textarea>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Submit Request</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Stock Requests -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>My Stock Requests</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <?php if (count($my_requests) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Item</th>
                                            <th>Requested Qty</th>
                                            <th>Available Stock</th>
                                            <th>Status</th>
                                            <th>Reason</th>
                                            <th>Requested Date</th>
                                            <th>Admin Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($my_requests as $request): ?>
                                            <tr>
                                                <td><?php echo $request['id']; ?></td>
                                                <td>
                                                    <strong><?php echo $request['item_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $request['item_code']; ?></small>
                                                </td>
                                                <td><?php echo $request['requested_quantity']; ?></td>
                                                <td><?php echo $request['current_stock']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $request['status'] === 'approved' ? 'success' : 
                                                            ($request['status'] === 'rejected' ? 'danger' : 'warning'); 
                                                    ?>">
                                                        <?php echo ucfirst($request['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $request['reason']; ?></td>
                                                <td><?php echo formatDate($request['created_at']); ?></td>
                                                <td>
                                                    <?php if ($request['admin_notes']): ?>
                                                        <?php echo $request['admin_notes']; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No notes</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No stock requests found.</p>
                        <?php endif; ?>
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
