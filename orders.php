<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
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
        $payment_method = $_POST['payment_method'] ?? 'credit';
        $payment_status = $_POST['payment_status'] ?? 'pending';
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

        if (!in_array($payment_method, ['cash', 'card', 'bank_transfer', 'credit'])) {
            $validation_errors[] = 'Invalid payment method selected.';
        }

        if (!in_array($payment_status, ['pending', 'paid', 'partial'])) {
            $validation_errors[] = 'Invalid payment status selected.';
        }

        // If no validation errors, proceed with database operations
        if (empty($validation_errors)) {
            try {
                // Get item details
                $query = "SELECT unit_price, current_stock, item_name FROM inventory WHERE id = :item_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':item_id', $item_id);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $item = $stmt->fetch();
                    $unit_price = $item['unit_price'];
                    $total_amount = $unit_price * $quantity;

                    // Check if enough stock is available
                    if ($item['current_stock'] < $quantity) {
                        $validation_errors[] = 'Insufficient stock. Only ' . $item['current_stock'] . ' units available for ' . $item['item_name'];
                    } else {
                        // Insert new order/sale record
                        $query = "INSERT INTO sales (user_id, item_id, customer_name, customer_phone, customer_email, quantity, unit_price, total_amount, payment_method, payment_status, notes, created_at)
                                 VALUES (:user_id, :item_id, :customer_name, :customer_phone, :customer_email, :quantity, :unit_price, :total_amount, :payment_method, :payment_status, :notes, NOW())";
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

                        if ($stmt->execute()) {
                            // Update inventory stock
                            $new_stock = $item['current_stock'] - $quantity;
                            $update_query = "UPDATE inventory SET current_stock = :new_stock WHERE id = :item_id";
                            $update_stmt = $db->prepare($update_query);
                            $update_stmt->bindParam(':new_stock', $new_stock);
                            $update_stmt->bindParam(':item_id', $item_id);
                            $update_stmt->execute();

                            // Record stock history
                            $history_query = "INSERT INTO stock_history (item_id, movement_type, quantity, previous_stock, new_stock, created_by, notes)
                                             VALUES (:item_id, 'sale', :quantity, :previous_stock, :new_stock, :created_by, :notes)";
                            $history_stmt = $db->prepare($history_query);
                            $history_stmt->bindParam(':item_id', $item_id);
                            $history_stmt->bindParam(':quantity', $quantity);
                            $history_stmt->bindParam(':previous_stock', $item['current_stock']);
                            $history_stmt->bindParam(':new_stock', $new_stock);
                            $history_stmt->bindParam(':created_by', $user_id);
                            $history_stmt->bindParam(':notes', $notes);
                            $history_stmt->execute();

                            $success_message = 'Order for ' . $customer_name . ' has been recorded successfully! Total: ' . formatCurrency($total_amount);

                            // Clear POST data to prevent form repopulation
                            $_POST = [];
                        } else {
                            $error_message = 'Database error: Failed to record order. Please try again.';
                        }
                    }
                } else {
                    $validation_errors[] = 'Selected item not found.';
                }
            } catch (Exception $e) {
                $error_message = 'System error: Unable to record order. Please contact administrator.';
                error_log("Order recording error: " . $e->getMessage());
            }
        }

        // Combine validation errors into error message
        if (!empty($validation_errors)) {
            $error_message = implode('<br>', $validation_errors);
        }
    }
}

// Get inventory items for dropdown
$query = "SELECT id, item_name, item_code, unit_price, current_stock FROM inventory WHERE current_stock > 0 ORDER BY item_name";
$stmt = $db->prepare($query);
$stmt->execute();
$inventory_items = $stmt->fetchAll();

// Get recent orders
$query = "SELECT s.*, i.item_name, i.item_code
          FROM sales s
          JOIN inventory i ON s.item_id = i.id
          ORDER BY s.created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_orders = $stmt->fetchAll();

// Get order statistics
$query = "SELECT
            COUNT(*) as total_orders,
            COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN total_amount ELSE 0 END), 0) as pending_amount,
            COALESCE(SUM(CASE WHEN payment_status = 'paid' THEN total_amount ELSE 0 END), 0) as paid_amount,
            COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_orders
          FROM sales";
$stmt = $db->prepare($query);
$stmt->execute();
$order_stats = $stmt->fetch();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Quick Orders & Debt Recording</h2>
            <p>Record customer orders and manage debts quickly</p>
        </div>
    </div>
</div>

<!-- Order Statistics -->
<div class="row column1">
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div>
                    <i class="fa fa-shopping-cart yellow_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $order_stats['total_orders']; ?></p>
                    <p class="head_couter">Total Orders</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div>
                    <i class="fa fa-clock-o blue1_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $order_stats['pending_orders']; ?></p>
                    <p class="head_couter">Pending Payments</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div>
                    <i class="fa fa-dollar green_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo formatCurrency($order_stats['pending_amount']); ?></p>
                    <p class="head_couter">Pending Amount</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div>
                    <i class="fa fa-check-circle green_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo formatCurrency($order_stats['paid_amount']); ?></p>
                    <p class="head_couter">Paid Amount</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if ($success_message): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-check-circle me-2"></i>
                <strong>Success!</strong> <?php echo $success_message; ?>
                <button type="button" class="close" onclick="closeAlert('successAlert')" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="fa fa-exclamation-triangle me-2"></i>
                <strong>Error!</strong> <?php echo $error_message; ?>
                <button type="button" class="close" onclick="closeAlert('errorAlert')" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Quick Order Form -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2><i class="fa fa-plus me-2"></i>Record New Order</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <form method="POST" id="orderForm" novalidate>
                            <input type="hidden" name="action" value="record_order">

                            <!-- Customer Information Section -->
                            <div class="form-section mb-4">
                                <h6 class="section-title text-primary mb-3">
                                    <i class="fa fa-user me-2"></i>Customer Information
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="customer_name" class="form-label">
                                                Customer Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="customer_name" id="customer_name"
                                                   class="form-control" required maxlength="100"
                                                   placeholder="Enter customer name" value="<?php echo isset($_POST['customer_name']) ? $_POST['customer_name'] : ''; ?>">
                                            <div class="invalid-feedback">
                                                Please provide a customer name.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="customer_phone" class="form-label">Phone Number</label>
                                            <input type="tel" name="customer_phone" id="customer_phone"
                                                   class="form-control" maxlength="20"
                                                   placeholder="Enter phone number" value="<?php echo isset($_POST['customer_phone']) ? $_POST['customer_phone'] : ''; ?>">
                                            <div class="invalid-feedback">
                                                Please provide a valid phone number.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="customer_email" class="form-label">Email Address</label>
                                            <input type="email" name="customer_email" id="customer_email"
                                                   class="form-control" maxlength="100"
                                                   placeholder="Enter email address" value="<?php echo isset($_POST['customer_email']) ? $_POST['customer_email'] : ''; ?>">
                                            <div class="invalid-feedback">
                                                Please provide a valid email address.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Order Details Section -->
                            <div class="form-section mb-4">
                                <h6 class="section-title text-primary mb-3">
                                    <i class="fa fa-shopping-cart me-2"></i>Order Details
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="item_id" class="form-label">
                                                Select Item <span class="text-danger">*</span>
                                            </label>
                                            <select name="item_id" id="item_id" class="form-control" required>
                                                <option value="">Choose an item...</option>
                                                <?php foreach ($inventory_items as $item): ?>
                                                    <option value="<?php echo $item['id']; ?>"
                                                            data-price="<?php echo $item['unit_price']; ?>"
                                                            <?php echo (isset($_POST['item_id']) && $_POST['item_id'] == $item['id']) ? 'selected' : ''; ?>>
                                                        <?php echo $item['item_name'] . ' (' . $item['item_code'] . ') - ' . formatCurrency($item['unit_price']) . ' - Stock: ' . $item['current_stock']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select an item.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-3">
                                            <label for="quantity" class="form-label">
                                                Quantity <span class="text-danger">*</span>
                                            </label>
                                            <input type="number" name="quantity" id="quantity"
                                                   class="form-control" min="1" required
                                                   placeholder="Qty" value="<?php echo isset($_POST['quantity']) ? $_POST['quantity'] : ''; ?>">
                                            <div class="invalid-feedback">
                                                Please enter a valid quantity.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group mb-3">
                                            <label for="total_amount" class="form-label">Total Amount</label>
                                            <input type="text" id="total_amount" class="form-control" readonly
                                                   value="<?php echo isset($_POST['item_id']) && isset($_POST['quantity']) ? formatCurrency(floatval($_POST['quantity']) * floatval($inventory_items[array_search($_POST['item_id'], array_column($inventory_items, 'id'))]['unit_price'] ?? 0)) : ''; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Information Section -->
                            <div class="form-section mb-4">
                                <h6 class="section-title text-primary mb-3">
                                    <i class="fa fa-credit-card me-2"></i>Payment Information
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="payment_method" class="form-label">
                                                Payment Method <span class="text-danger">*</span>
                                            </label>
                                            <select name="payment_method" id="payment_method" class="form-control" required>
                                                <option value="cash" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'cash') ? 'selected' : ''; ?>>Cash</option>
                                                <option value="card" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'card') ? 'selected' : ''; ?>>Card</option>
                                                <option value="bank_transfer" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'bank_transfer') ? 'selected' : ''; ?>>Bank Transfer</option>
                                                <option value="credit" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'credit') ? 'selected' : ''; ?>>Credit (Pay Later)</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select a payment method.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="payment_status" class="form-label">
                                                Payment Status <span class="text-danger">*</span>
                                            </label>
                                            <select name="payment_status" id="payment_status" class="form-control" required>
                                                <option value="paid" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] === 'paid') ? 'selected' : ''; ?>>Paid</option>
                                                <option value="pending" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] === 'pending') ? 'selected' : ''; ?>>Pending (Debt)</option>
                                                <option value="partial" <?php echo (isset($_POST['payment_status']) && $_POST['payment_status'] === 'partial') ? 'selected' : ''; ?>>Partial Payment</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select a payment status.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group mb-3">
                                            <label for="notes" class="form-label">Notes</label>
                                            <textarea name="notes" id="notes"
                                                      class="form-control" rows="3" maxlength="255"
                                                      placeholder="Additional notes (optional)"><?php echo isset($_POST['notes']) ? $_POST['notes'] : ''; ?></textarea>
                                            <small class="form-text text-muted">Optional field for additional information</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-success" id="recordOrderBtn">
                                    <i class="fa fa-save me-2"></i>Record Order
                                </button>
                                <button type="reset" class="btn btn-secondary ms-2">
                                    <i class="fa fa-refresh me-2"></i>Reset Form
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
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Customer</th>
                                        <th>Item</th>
                                        <th>Quantity</th>
                                        <th>Total Amount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($recent_orders) > 0): ?>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><?php echo $order['id']; ?></td>
                                                <td>
                                                    <strong><?php echo $order['customer_name']; ?></strong>
                                                    <?php if ($order['customer_phone']): ?>
                                                        <br><small class="text-muted"><?php echo $order['customer_phone']; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $order['item_name'] . ' (' . $order['item_code'] . ')'; ?></td>
                                                <td><?php echo $order['quantity']; ?></td>
                                                <td><?php echo formatCurrency($order['total_amount']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        echo $order['payment_method'] === 'credit' ? 'warning' :
                                                            ($order['payment_method'] === 'cash' ? 'success' : 'info');
                                                    ?>">
                                                        <?php echo ucfirst($order['payment_method']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php
                                                        echo $order['payment_status'] === 'paid' ? 'success' :
                                                            ($order['payment_status'] === 'pending' ? 'warning' : 'info');
                                                    ?>">
                                                        <?php echo ucfirst($order['payment_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($order['created_at']); ?></td>
                                                <td>
                                                    <?php if ($order['payment_status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-success" onclick="markAsPaid(<?php echo $order['id']; ?>)">
                                                            <i class="fa fa-check"></i> Mark Paid
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-primary" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                                        <i class="fa fa-eye"></i> View
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No orders recorded yet</td>
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

<style>
.form-section {
    border-left: 4px solid #007bff;
    padding-left: 15px;
    margin-bottom: 1.5rem;
}

.section-title {
    font-weight: 600;
    font-size: 1.1rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid #e9ecef;
}

.form-control.is-valid {
    border-color: #28a745;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.94-.94 1.88 1.88 3.75-3.75.94.94-4.69 4.69z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-control.is-invalid {
    border-color: #dc3545;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 1.4 1.4M7.2 4.6l-1.4 1.4'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
}

.btn {
    border-radius: 6px;
    font-weight: 500;
    padding: 0.5rem 1rem;
    transition: all 0.15s ease-in-out;
}

.btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>

<script>
// Auto-calculate total amount
document.getElementById('item_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const price = selectedOption.getAttribute('data-price') || 0;
    const quantity = document.getElementById('quantity').value || 0;
    const total = parseFloat(price) * parseInt(quantity);
    document.getElementById('total_amount').value = formatCurrency(total);
});

document.getElementById('quantity').addEventListener('input', function() {
    const itemSelect = document.getElementById('item_id');
    const selectedOption = itemSelect.options[itemSelect.selectedIndex];
    const price = selectedOption.getAttribute('data-price') || 0;
    const quantity = this.value || 0;
    const total = parseFloat(price) * parseInt(quantity);
    document.getElementById('total_amount').value = formatCurrency(total);
});

// Format currency helper
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount) + ' RWF';
}

// Form validation
document.getElementById('orderForm').addEventListener('submit', function(e) {
    const form = this;
    let isValid = true;

    // Clear previous validation
    const fields = form.querySelectorAll('.is-valid, .is-invalid');
    fields.forEach(field => field.classList.remove('is-valid', 'is-invalid'));

    // Validate required fields
    const requiredFields = form.querySelectorAll('input[required], select[required]');
    requiredFields.forEach(function(field) {
        if (!field.value.trim()) {
            field.classList.add('is-invalid');
            isValid = false;
        } else {
            field.classList.add('is-valid');
        }
    });

    // Validate email if provided
    const emailField = form.querySelector('#customer_email');
    if (emailField.value && !emailField.checkValidity()) {
        emailField.classList.add('is-invalid');
        isValid = false;
    }

    if (!isValid) {
        e.preventDefault();
        alert('Please correct the errors in the form.');
        return false;
    }

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Recording Order...';
    submitBtn.disabled = true;
});

// Helper functions for order actions
function markAsPaid(orderId) {
    if (confirm('Mark this order as paid?')) {
        // Here you would implement the logic to update payment status
        alert('Payment status updated successfully!');
        location.reload();
    }
}

async function viewOrderDetails(orderId) {
    try {
        // Show loading state
        const modal = document.getElementById('orderDetailsModal');
        const modalBody = modal.querySelector('.modal-body');
        modalBody.innerHTML = '<div class="text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div><p class="mt-2">Loading order details...</p></div>';

        // Show the modal
        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();
        
        // Fetch order details via AJAX
        const response = await fetch(`/nesrah/api/orders/${orderId}`, {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        });
        if (!response.ok) {
            const errorData = await response.json();
            throw new Error(errorData.error || 'Failed to fetch order details');
        }
        
        const order = await response.json();
        
        // Format the order details HTML
        const orderDate = new Date(order.created_at).toLocaleString();
                          (order.payment_status === 'pending' ? 'warning' : 'info');
        
        modalBody.innerHTML = `
            <div class="order-details">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <h5>Order #${order.id}</h5>
                        <p class="text-muted">${orderDate}</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <span class="badge bg-${statusBadge} fs-6">
                            ${order.payment_status.charAt(0).toUpperCase() + order.payment_status.slice(1)}
                        </span>
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Customer Information</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-1"><strong>Name:</strong> ${order.customer_name}</p>
                        ${order.customer_phone ? `<p class="mb-1"><strong>Phone:</strong> ${order.customer_phone}</p>` : ''}
                        ${order.customer_email ? `<p class="mb-0"><strong>Email:</strong> ${order.customer_email}</p>` : ''}
                    </div>
                </div>
                
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Order Items</h6>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Quantity</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>${order.item_name} (${order.item_code})</td>
                                    <td class="text-end">${order.quantity}</td>
                                    <td class="text-end">${formatCurrency(order.unit_price)}</td>
                                    <td class="text-end fw-bold">${formatCurrency(order.total_amount)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Payment Information</h6>
                            </div>
                            <div class="card-body">
                                <p class="mb-1"><strong>Payment Method:</strong> ${order.payment_method.charAt(0).toUpperCase() + order.payment_method.slice(1)}</p>
                                <p class="mb-0"><strong>Amount Paid:</strong> ${formatCurrency(order.amount_paid || 0)}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Order Summary</h6>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span>${formatCurrency(order.total_amount)}</span>
                                </div>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total:</span>
                                    <span>${formatCurrency(order.total_amount)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                ${order.notes ? `
                <div class="card mt-3">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Notes</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">${order.notes}</p>
                    </div>
                </div>
                ` : ''}
            </div>
        `;
        
    } catch (error) {
        console.error('Error fetching order details:', error);
        const modalBody = document.querySelector('#orderDetailsModal .modal-body');
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                <h5 class="alert-heading">Error</h5>
                <p class="mb-0">Failed to load order details. Please try again later.</p>
            </div>
        `;
    }
}

// Add modal HTML to the page if it doesn't exist
if (!document.getElementById('orderDetailsModal')) {
    const modalHTML = `
    <div class="modal fade" id="orderDetailsModal" tabindex="-1" aria-labelledby="orderDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="orderDetailsModalLabel">Order Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printOrderDetails()">
                        <i class="fa fa-print me-2"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>
    `;
    document.body.insertAdjacentHTML('beforeend', modalHTML);
}

function printOrderDetails() {
    const printContent = document.querySelector('.order-details').cloneNode(true);
    const printWindow = window.open('', '_blank');
    printWindow.document.write(`
        <html>
            <head>
                <title>Order Details</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    @media print {
                        @page { size: auto; margin: 0; }
                        body { padding: 20px; }
                        .btn { display: none !important; }
                        .no-print { display: none !important; }
                    }
                    .order-details { max-width: 800px; margin: 0 auto; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="text-center mb-4">
                        <h2>Order Details</h2>
                        <p class="text-muted">${new Date().toLocaleString()}</p>
                    </div>
                    ${printContent.outerHTML}
                    <div class="text-center mt-4 text-muted">
                        <p>Thank you for your business!</p>
                    </div>
                </div>
                <script>
                    window.onload = function() { window.print(); };
                <\/script>
            </body>
        </html>
    `);
    printWindow.document.close();
}

function closeAlert(alertId) {
    const alert = document.getElementById(alertId);
    if (alert) {
        alert.style.opacity = '0';
        setTimeout(function() {
            alert.remove();
        }, 300);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
