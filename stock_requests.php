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
    error_log('POST DATA: ' . print_r($_POST, true));
    $action = $_POST['action'] ?? '';
    $request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
    error_log('Action: ' . $action . ', Request ID: ' . $request_id);
    
    if ($action === 'approve') {
        $admin_notes = sanitizeInput($_POST['admin_notes'] ?? '');
        
        // Get request details
        $query = "SELECT * FROM stock_requests WHERE id = :request_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':request_id', $request_id);
        $stmt->execute();
        $request = $stmt->fetch();
        
        // Check if enough stock is available
        $query = "SELECT current_stock FROM inventory WHERE id = :item_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':item_id', $request['item_id']);
        $stmt->execute();
        $current_stock = $stmt->fetch()['current_stock'];
        
        if ($request['requested_quantity'] > $current_stock) {
            $error_message = 'Insufficient stock. Available: ' . $current_stock;
        } else {
            // Update request status
            $query = "UPDATE stock_requests SET status = 'approved', reviewed_by = :reviewed_by, reviewed_at = NOW(), admin_notes = :admin_notes WHERE id = :request_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':reviewed_by', $user_id);
            $stmt->bindParam(':admin_notes', $admin_notes);
            $stmt->bindParam(':request_id', $request_id);
            
            if ($stmt->execute()) {
                // Create stock allocation
                $query = "INSERT INTO stock_allocations (item_id, user_id, allocated_quantity, remaining_quantity, allocated_by) 
                         VALUES (:item_id, :user_id, :quantity, :quantity, :allocated_by)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':item_id', $request['item_id']);
                $stmt->bindParam(':user_id', $request['user_id']);
                $stmt->bindParam(':quantity', $request['requested_quantity']);
                $stmt->bindParam(':allocated_by', $user_id);
                $stmt->execute();
                
                // Update inventory
                $new_stock = $current_stock - $request['requested_quantity'];
                $query = "UPDATE inventory SET current_stock = :new_stock WHERE id = :item_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':new_stock', $new_stock);
                $stmt->bindParam(':item_id', $request['item_id']);
                $stmt->execute();
                
                // Record stock history
                $history_query = "INSERT INTO stock_history (item_id, movement_type, quantity, previous_stock, new_stock, created_by, notes) 
                                 VALUES (:item_id, 'allocation', :quantity, :previous_stock, :new_stock, :created_by, 'Stock allocated from approved request')";
                $history_stmt = $db->prepare($history_query);
                $history_stmt->bindParam(':item_id', $request['item_id']);
                $history_stmt->bindParam(':quantity', $request['requested_quantity']);
                $history_stmt->bindParam(':previous_stock', $current_stock);
                $history_stmt->bindParam(':new_stock', $new_stock);
                $history_stmt->bindParam(':created_by', $user_id);
                $history_stmt->execute();
                
                $success_message = 'Stock request approved and allocated successfully.';
            } else {
                $error_message = 'Failed to approve request.';
            }
        }
    } elseif ($action === 'reject') {
        $admin_notes = sanitizeInput($_POST['admin_notes']);
        
        $query = "UPDATE stock_requests SET status = 'rejected', reviewed_by = :reviewed_by, reviewed_at = NOW(), admin_notes = :admin_notes WHERE id = :request_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':reviewed_by', $user_id);
        $stmt->bindParam(':admin_notes', $admin_notes);
        $stmt->bindParam(':request_id', $request_id);
        
        if ($stmt->execute()) {
            $success_message = 'Stock request rejected successfully.';
        } else {
            $error_message = 'Failed to reject request.';
        }
    }
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
$where_clause = "WHERE 1=1";
if ($filter === 'pending') {
    $where_clause .= " AND sr.status = 'pending'";
} elseif ($filter === 'approved') {
    $where_clause .= " AND sr.status = 'approved'";
} elseif ($filter === 'rejected') {
    $where_clause .= " AND sr.status = 'rejected'";
}

$query = "SELECT sr.*, i.item_name, i.item_code, i.current_stock, u.first_name, u.last_name, 
                 admin.first_name as reviewed_by_name, admin.last_name as reviewed_by_last
          FROM stock_requests sr 
          JOIN inventory i ON sr.item_id = i.id 
          JOIN users u ON sr.user_id = u.id 
          LEFT JOIN users admin ON sr.reviewed_by = admin.id 
          $where_clause ORDER BY sr.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$requests = $stmt->fetchAll();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Stock Requests Management</h2>
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

<!-- Filter Buttons -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Filter Requests</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-group" role="group">
                            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                            <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
                            <a href="?filter=approved" class="btn <?php echo $filter === 'approved' ? 'btn-success' : 'btn-outline-success'; ?>">Approved</a>
                            <a href="?filter=rejected" class="btn <?php echo $filter === 'rejected' ? 'btn-danger' : 'btn-outline-danger'; ?>">Rejected</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Requests Table -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Stock Requests (<?php echo count($requests); ?> total)</h2>
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
                                        <th>Employee</th>
                                        <th>Item</th>
                                        <th>Requested Qty</th>
                                        <th>Available Stock</th>
                                        <th>Status</th>
                                        <th>Reason</th>
                                        <th>Requested Date</th>
                                        <th>Reviewed By</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($requests) > 0): ?>
                                        <?php foreach ($requests as $request): ?>
                                            <tr>
                                                <td><?php echo $request['id']; ?></td>
                                                <td><?php echo $request['first_name'] . ' ' . $request['last_name']; ?></td>
                                                <td>
                                                    <strong><?php echo $request['item_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $request['item_code']; ?></small>
                                                </td>
                                                <td><?php echo $request['requested_quantity']; ?></td>
                                                <td>
                                                    <span class="<?php echo $request['requested_quantity'] > $request['current_stock'] ? 'text-danger font-weight-bold' : ''; ?>">
                                                        <?php echo $request['current_stock']; ?>
                                                    </span>
                                                </td>
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
                                                    <?php if ($request['reviewed_by_name']): ?>
                                                        <?php echo $request['reviewed_by_name'] . ' ' . $request['reviewed_by_last']; ?>
                                                        <br><small class="text-muted"><?php echo formatDate($request['reviewed_at']); ?></small>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not reviewed</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($request['status'] === 'pending'): ?>
                                                        <div class="btn-group" role="group">
                                                            <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#approveModal<?php echo $request['id']; ?>" title="Approve Request">
                                                                <i class="fa fa-check"></i> Approve
                                                            </button>
                                                            <button class="btn btn-danger btn-sm" data-toggle="modal" data-target="#rejectModal<?php echo $request['id']; ?>" title="Reject Request">
                                                                <i class="fa fa-times"></i> Reject
                                                            </button>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted">Reviewed</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            
                                            <!-- Approve Modal -->
                                            <div class="modal fade" id="approveModal<?php echo $request['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Approve Stock Request</h5>
                                                            <button type="button" class="close" data-dismiss="modal">
                                                                <span>&times;</span>
                                                            </button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="approve">
                                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                                
                                                                <p><strong>Employee:</strong> <?php echo $request['first_name'] . ' ' . $request['last_name']; ?></p>
                                                                <p><strong>Item:</strong> <?php echo $request['item_name']; ?> (<?php echo $request['item_code']; ?>)</p>
                                                                <p><strong>Requested Quantity:</strong> <?php echo $request['requested_quantity']; ?></p>
                                                                <p><strong>Available Stock:</strong> <?php echo $request['current_stock']; ?></p>
                                                                <p><strong>Reason:</strong> <?php echo $request['reason']; ?></p>
                                                                
                                                                <div class="form-group">
                                                                    <label>Admin Notes</label>
                                                                    <textarea name="admin_notes" class="form-control" rows="3" placeholder="Optional notes for the employee"></textarea>
                                                                </div>
                                                                
                                                                <?php if ($request['requested_quantity'] > $request['current_stock']): ?>
                                                                    <div class="alert alert-warning">
                                                                        <strong>Warning:</strong> Requested quantity exceeds available stock!
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-success">Approve Request</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Reject Modal -->
                                            <div class="modal fade" id="rejectModal<?php echo $request['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Reject Stock Request</h5>
                                                            <button type="button" class="close" data-dismiss="modal">
                                                                <span>&times;</span>
                                                            </button>
                                                        </div>
                                                        <form method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="reject">
                                                                <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                                
                                                                <p><strong>Employee:</strong> <?php echo $request['first_name'] . ' ' . $request['last_name']; ?></p>
                                                                <p><strong>Item:</strong> <?php echo $request['item_name']; ?> (<?php echo $request['item_code']; ?>)</p>
                                                                <p><strong>Requested Quantity:</strong> <?php echo $request['requested_quantity']; ?></p>
                                                                <p><strong>Reason:</strong> <?php echo $request['reason']; ?></p>
                                                                
                                                                <div class="form-group">
                                                                    <label>Rejection Reason *</label>
                                                                    <textarea name="admin_notes" class="form-control" rows="3" placeholder="Please provide reason for rejection" required></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Reject Request</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center">No requests found</td>
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
    // Approve modal cancel button
    $('[id^=approveModal] .btn-secondary[data-dismiss="modal"]').on('click', function () {
        var modal = $(this).closest('.modal');
        if (typeof $.fn.modal !== 'undefined') {
            modal.modal('hide');
        } else {
            modal[0].style.display = 'none';
            modal[0].classList.remove('show');
        }
        modal.find('form')[0].reset();
    });
    // Reject modal cancel button
    $('[id^=rejectModal] .btn-secondary[data-dismiss="modal"]').on('click', function () {
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
