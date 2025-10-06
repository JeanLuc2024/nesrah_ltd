<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is employee
if (!isEmployee()) {
    redirect('dashboard.php');
}

$success_message = '';
$error_message = '';
$validation_errors = [];

// Handle form submission
if ($_POST) {
    $action = $_POST['action'] ?? '';

    if ($action === 'record_order') {
        // Get and sanitize form data
        $customer_name = sanitizeInput($_POST['customer_name'] ?? '');
        $customer_phone = sanitizeInput($_POST['customer_phone'] ?? '');
        $customer_email = sanitizeInput($_POST['customer_email'] ?? '');
        $item_id = intval($_POST['item_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $payment_status = $_POST['payment_status'] ?? 'paid';
        $notes = sanitizeInput($_POST['notes'] ?? '');

        // Validation
        if (empty($customer_name)) {
            $validation_errors[] = 'Customer name is required.';
        }

        if ($item_id <= 0) {
            $validation_errors[] = 'Please select a valid item.';
        }

        if ($quantity <= 0) {
            $validation_errors[] = 'Quantity must be greater than 0.';
        }

        // If no validation errors, proceed with database operations
        if (empty($validation_errors)) {
            try {
                // Start transaction
                $db->beginTransaction();
                
                // Get item details
                $query = "SELECT unit_price, current_stock, item_name, item_code 
                          FROM inventory 
                          WHERE id = :item_id AND current_stock >= :quantity";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':item_id', $item_id);
                $stmt->bindParam(':quantity', $quantity);
                $stmt->execute();
                
                if ($stmt->rowCount() === 0) {
                    throw new Exception('Insufficient stock or item not available');
                }
                
                $item = $stmt->fetch();
                $unit_price = $item['unit_price'];
                $total_amount = $unit_price * $quantity;
                $new_stock = $item['current_stock'] - $quantity;
                
                // Insert new order/sale record
                $query = "INSERT INTO sales (
                            created_by, 
                            item_id, 
                            customer_name, 
                            customer_phone, 
                            customer_email, 
                            quantity, 
                            unit_price, 
                            total_amount, 
                            payment_method, 
                            payment_status, 
                            notes, 
                            created_at
                          ) VALUES (
                            :user_id, 
                            :item_id, 
                            :customer_name, 
                            :customer_phone, 
                            :customer_email, 
                            :quantity, 
                            :unit_price, 
                            :total_amount, 
                            :payment_method, 
                            :payment_status, 
                            :notes, 
                            NOW()
                          )";
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':item_id', $item_id);
                $stmt->bindParam(':customer_name', $customer_name);
                $stmt->bindParam(':customer_phone', $customer_phone);
                $stmt->bindParam(':customer_email', $customer_email);
                $stmt->bindParam(':quantity', $quantity);
                $stmt->bindParam(':unit_price', $unit_price);
                $stmt->bindParam(':total_amount', $total_amount);
                $stmt->bindParam(':payment_method', $payment_method);
                $stmt->bindParam(':payment_status', $payment_status);
                $stmt->bindParam(':notes', $notes);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to record order');
                }
                
                // Update inventory
                $query = "UPDATE inventory 
                          SET current_stock = :new_stock 
                          WHERE id = :item_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':new_stock', $new_stock);
                $stmt->bindParam(':item_id', $item_id);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to update inventory');
                }
                
                // Record stock history
                $query = "INSERT INTO stock_history (
                            item_id, 
                            movement_type, 
                            quantity, 
                            previous_stock, 
                            new_stock, 
                            reference_type, 
                            reference_id, 
                            created_by, 
                            notes
                          ) VALUES (
                            :item_id, 
                            'sale', 
                            :quantity, 
                            :previous_stock, 
                            :new_stock, 
                            'sale', 
                            :sale_id, 
                            :user_id, 
                            :notes
                          )";
                
                $sale_id = $db->lastInsertId();
                $notes = "Sold to: $customer_name" . ($customer_phone ? " ($customer_phone)" : '');
                
                $stmt = $db->prepare($query);
                $stmt->bindParam(':item_id', $item_id);
                $stmt->bindParam(':quantity', $quantity);
                $stmt->bindParam(':previous_stock', $item['current_stock']);
                $stmt->bindParam(':new_stock', $new_stock);
                $stmt->bindParam(':sale_id', $sale_id);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':notes', $notes);
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to record stock history');
                }
                
                // Commit transaction
                $db->commit();
                
                $success_message = 'Order recorded successfully!';
                
                // Clear form
                $_POST = [];
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $db->rollBack();
                $error_message = 'Error: ' . $e->getMessage();
            }
        } else {
            $error_message = implode('<br>', $validation_errors);
        }
    }
}

// Get inventory items for dropdown
$query = "SELECT id, item_name, item_code, unit_price, current_stock 
          FROM inventory 
          WHERE current_stock > 0 
          ORDER BY item_name";
$stmt = $db->prepare($query);
$stmt->execute();
$inventory_items = $stmt->fetchAll();

// Get recent orders for the current user
$query = "SELECT s.*, i.item_name, i.item_code 
          FROM sales s 
          JOIN inventory i ON s.item_id = i.id 
          WHERE s.created_by = :user_id 
          ORDER BY s.created_at DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$recent_orders = $stmt->fetchAll();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Record New Order</h2>
        </div>
    </div>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2><i class="fa fa-plus me-2"></i>New Order</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <form method="POST" id="orderForm" novalidate>
                            <input type="hidden" name="action" value="record_order">

                            <!-- Customer Information -->
                            <div class="form-section mb-4">
                                <h6 class="section-title text-primary mb-3">
                                    <i class="fa fa-user me-2"></i>Customer Information
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="customer_name" class="form-label">Customer Name <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" id="customer_name" name="customer_name" 
                                                   value="<?php echo $_POST['customer_name'] ?? ''; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-3">
                                            <label for="customer_phone" class="form-label">Phone</label>
                                            <input type="tel" class="form-control" id="customer_phone" name="customer_phone" 
                                                   value="<?php echo $_POST['customer_phone'] ?? ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-3">
                                            <label for="customer_email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="customer_email" name="customer_email" 
                                                   value="<?php echo $_POST['customer_email'] ?? ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Details -->
                            <div class="form-section mb-4">
                                <h6 class="section-title text-primary mb-3">
                                    <i class="fa fa-shopping-cart me-2"></i>Order Details
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="item_id" class="form-label">Item <span class="text-danger">*</span></label>
                                            <select class="form-select" id="item_id" name="item_id" required>
                                                <option value="">Select an item</option>
                                                <?php foreach ($inventory_items as $item): ?>
                                                    <option value="<?php echo $item['id']; ?>" 
                                                            data-price="<?php echo $item['unit_price']; ?>"
                                                            data-stock="<?php echo $item['current_stock']; ?>"
                                                            <?php echo (isset($_POST['item_id']) && $_POST['item_id'] == $item['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($item['item_name'] . ' (' . $item['item_code'] . ') - ' . formatCurrency($item['unit_price']) . ' - Stock: ' . $item['current_stock']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group mb-3">
                                            <label for="quantity" class="form-label">Quantity <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" id="quantity" name="quantity" 
                                                   min="1" value="<?php echo $_POST['quantity'] ?? '1'; ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-3">
                                            <label class="form-label">Unit Price</label>
                                            <input type="text" class="form-control" id="unit_price" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group mb-3">
                                            <label for="payment_method" class="form-label">Payment Method</label>
                                            <select class="form-select" id="payment_method" name="payment_method">
                                                <option value="cash" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'cash') ? 'selected' : ''; ?>>Cash</option>
                                                <option value="card" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'card') ? 'selected' : ''; ?>>Card</option>
                                                <option value="mobile_money" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'mobile_money') ? 'selected' : ''; ?>>Mobile Money</option>
                                                <option value="bank_transfer" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-3">
                                            <label for="payment_status" class="form-label">Payment Status</label>
                                            <select class="form-select" id="payment_status" name="payment_status">
                                                <option value="paid" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] === 'paid') ? 'selected' : ''; ?>>Paid</option>
                                                <option value="pending" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                <option value="partial" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] === 'partial') ? 'selected' : ''; ?>>Partial Payment</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="total_amount" class="form-label">Total Amount</label>
                                            <input type="text" class="form-control" id="total_amount" readonly>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group mb-3">
                                            <label for="notes" class="form-label">Notes</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="2"><?php echo $_POST['notes'] ?? ''; ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="reset" class="btn btn-secondary me-md-2">
                                    <i class="fa fa-undo me-1"></i> Reset
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-save me-1"></i> Record Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2><i class="fa fa-list me-2"></i>Recent Orders</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <?php if (count($recent_orders) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Order #</th>
                                            <th>Customer</th>
                                            <th>Item</th>
                                            <th class="text-end">Qty</th>
                                            <th class="text-end">Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                                <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($order['item_name']); ?>
                                                    <small class="text-muted d-block"><?php echo $order['item_code']; ?></small>
                                                </td>
                                                <td class="text-end"><?php echo $order['quantity']; ?></td>
                                                <td class="text-end"><?php echo formatCurrency($order['total_amount']); ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = [
                                                        'paid' => 'success',
                                                        'pending' => 'warning',
                                                        'partial' => 'info'
                                                    ][$order['payment_status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?php echo $status_class; ?>">
                                                        <?php echo ucfirst($order['payment_status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle me-2"></i> No orders found. Start by creating a new order above.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-calculate total amount
function calculateTotal() {
    const itemSelect = document.getElementById('item_id');
    const quantityInput = document.getElementById('quantity');
    const unitPriceInput = document.getElementById('unit_price');
    const totalAmountInput = document.getElementById('total_amount');
    
    if (itemSelect.selectedIndex > 0 && quantityInput.value > 0) {
        const selectedOption = itemSelect.options[itemSelect.selectedIndex];
        const price = parseFloat(selectedOption.getAttribute('data-price'));
        const quantity = parseInt(quantityInput.value);
        const total = price * quantity;
        
        unitPriceInput.value = formatCurrency(price);
        totalAmountInput.value = formatCurrency(total);
    } else {
        unitPriceInput.value = '';
        totalAmountInput.value = '';
    }
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(amount);
}

// Event listeners
document.getElementById('item_id').addEventListener('change', calculateTotal);
document.getElementById('quantity').addEventListener('input', calculateTotal);

// Initialize calculation on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateTotal();
});

// Form validation
document.getElementById('orderForm').addEventListener('submit', function(e) {
    const itemSelect = document.getElementById('item_id');
    const quantityInput = document.getElementById('quantity');
    
    if (itemSelect.selectedIndex <= 0) {
        e.preventDefault();
        alert('Please select an item');
        itemSelect.focus();
        return false;
    }
    
    if (quantityInput.value <= 0) {
        e.preventDefault();
        alert('Please enter a valid quantity');
        quantityInput.focus();
        return false;
    }
    
    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...';
    
    return true;
});
</script>

<style>
.form-section {
    border-left: 4px solid #e9ecef;
    padding-left: 1rem;
    margin-bottom: 2rem;
}

.section-title {
    font-weight: 600;
    margin-bottom: 1rem;
    color: #495057;
}

.table th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.table td, .table th {
    vertical-align: middle;
}

.badge {
    font-weight: 500;
    padding: 0.4em 0.6em;
}

.alert {
    border-left: 4px solid;
}

.btn {
    min-width: 120px;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
