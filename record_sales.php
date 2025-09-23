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
    
    if ($action === 'record_sale') {
        $item_id = intval($_POST['item_id']);
        $customer_name = sanitizeInput($_POST['customer_name']);
        $customer_phone = sanitizeInput($_POST['customer_phone']);
        $customer_email = sanitizeInput($_POST['customer_email']);
        $quantity = intval($_POST['quantity']);
        $unit_price = floatval($_POST['unit_price']);
        $payment_method = $_POST['payment_method'];
        $notes = sanitizeInput($_POST['notes']);
        
        $total_amount = $quantity * $unit_price;
        
        // Check if employee has enough allocated stock
        $query = "SELECT remaining_quantity FROM stock_allocations WHERE user_id = :user_id AND item_id = :item_id AND status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':item_id', $item_id);
        $stmt->execute();
        $allocation = $stmt->fetch();
        
        if (!$allocation) {
            $error_message = 'You do not have any allocated stock for this item.';
        } elseif ($quantity > $allocation['remaining_quantity']) {
            $error_message = 'Insufficient allocated stock. Available: ' . $allocation['remaining_quantity'];
        } else {
            // Record the sale
            $query = "INSERT INTO sales (user_id, item_id, customer_name, customer_phone, customer_email, quantity, unit_price, total_amount, payment_method, notes) 
                     VALUES (:user_id, :item_id, :customer_name, :customer_phone, :customer_email, :quantity, :unit_price, :total_amount, :payment_method, :notes)";
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
            $stmt->bindParam(':notes', $notes);
            
            if ($stmt->execute()) {
                // Update stock allocation
                $new_remaining = $allocation['remaining_quantity'] - $quantity;
                $query = "UPDATE stock_allocations SET remaining_quantity = :remaining_quantity WHERE user_id = :user_id AND item_id = :item_id AND status = 'active'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':remaining_quantity', $new_remaining);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':item_id', $item_id);
                $stmt->execute();
                
                // Mark allocation as completed if no stock left
                if ($new_remaining == 0) {
                    $query = "UPDATE stock_allocations SET status = 'completed', completed_at = NOW() WHERE user_id = :user_id AND item_id = :item_id AND status = 'active'";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':user_id', $user_id);
                    $stmt->bindParam(':item_id', $item_id);
                    $stmt->execute();
                }
                
                $success_message = 'Sale recorded successfully! Total amount: ' . formatCurrency($total_amount);
            } else {
                $error_message = 'Failed to record sale.';
            }
        }
    }
}

// Get my allocated stock for sales
$query = "SELECT sa.*, i.item_name, i.item_code, i.unit_price
          FROM stock_allocations sa 
          JOIN inventory i ON sa.item_id = i.id 
          WHERE sa.user_id = :user_id AND sa.status = 'active'
          ORDER BY sa.allocated_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_stock = $stmt->fetchAll();

// Get my recent sales
$query = "SELECT s.*, i.item_name, i.item_code
          FROM sales s 
          JOIN inventory i ON s.item_id = i.id 
          WHERE s.user_id = :user_id 
          ORDER BY s.created_at DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$recent_sales = $stmt->fetchAll();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Record Sales</h2>
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

<!-- My Available Stock -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>My Available Stock for Sales</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <?php if (count($my_stock) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Allocated Qty</th>
                                            <th>Remaining Qty</th>
                                            <th>Unit Price</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($my_stock as $stock): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $stock['item_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $stock['item_code']; ?></small>
                                                </td>
                                                <td><?php echo $stock['allocated_quantity']; ?></td>
                                                <td>
                                                    <span class="<?php echo $stock['remaining_quantity'] <= 5 ? 'text-warning font-weight-bold' : ''; ?>">
                                                        <?php echo $stock['remaining_quantity']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatCurrency($stock['unit_price']); ?></td>
                                                <td>
                                                    <button class="btn btn-primary btn-sm" onclick="fillSaleForm(<?php echo $stock['item_id']; ?>, '<?php echo $stock['item_name']; ?>', <?php echo $stock['remaining_quantity']; ?>, <?php echo $stock['unit_price']; ?>)">
                                                        <i class="fa fa-shopping-cart"></i> Sell
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No available stock for sales. Contact administrator to get stock allocated.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Record Sale Form -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Record New Sale</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <form method="POST" id="saleForm">
                            <input type="hidden" name="action" value="record_sale">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Select Item *</label>
                                        <select name="item_id" id="item_id" class="form-control" required onchange="updateItemDetails(this)">
                                            <option value="">Select Item</option>
                                            <?php foreach ($my_stock as $stock): ?>
                                                <option value="<?php echo $stock['item_id']; ?>" 
                                                        data-remaining="<?php echo $stock['remaining_quantity']; ?>" 
                                                        data-price="<?php echo $stock['unit_price']; ?>">
                                                    <?php echo $stock['item_name'] . ' (' . $stock['item_code'] . ') - Available: ' . $stock['remaining_quantity']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Customer Name *</label>
                                        <input type="text" name="customer_name" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Customer Phone</label>
                                        <input type="tel" name="customer_phone" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Customer Email</label>
                                        <input type="email" name="customer_email" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Quantity *</label>
                                        <input type="number" name="quantity" id="quantity" class="form-control" min="1" required onchange="calculateTotal()">
                                        <small class="form-text text-muted" id="available-quantity-text">Select an item to see available quantity</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Unit Price *</label>
                                        <input type="number" name="unit_price" id="unit_price" class="form-control" step="0.01" min="0" required onchange="calculateTotal()">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Payment Method *</label>
                                        <select name="payment_method" class="form-control" required>
                                            <option value="cash">Cash</option>
                                            <option value="card">Card</option>
                                            <option value="bank_transfer">Bank Transfer</option>
                                            <option value="credit">Credit</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Total Amount</label>
                                        <input type="text" id="total_amount" class="form-control" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Notes</label>
                                        <textarea name="notes" class="form-control" rows="2"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Record Sale</button>
                                <button type="reset" class="btn btn-secondary">Reset Form</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Sales -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>My Recent Sales</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <?php if (count($recent_sales) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Item</th>
                                            <th>Customer</th>
                                            <th>Qty</th>
                                            <th>Unit Price</th>
                                            <th>Total</th>
                                            <th>Payment</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_sales as $sale): ?>
                                            <tr>
                                                <td><?php echo formatDateTime($sale['created_at']); ?></td>
                                                <td>
                                                    <strong><?php echo $sale['item_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $sale['item_code']; ?></small>
                                                </td>
                                                <td>
                                                    <?php echo $sale['customer_name']; ?>
                                                    <?php if ($sale['customer_phone']): ?>
                                                        <br><small class="text-muted"><?php echo $sale['customer_phone']; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $sale['quantity']; ?></td>
                                                <td><?php echo formatCurrency($sale['unit_price']); ?></td>
                                                <td><strong><?php echo formatCurrency($sale['total_amount']); ?></strong></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No sales recorded yet.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateItemDetails(select) {
    const selectedOption = select.options[select.selectedIndex];
    const remaining = selectedOption.getAttribute('data-remaining');
    const price = selectedOption.getAttribute('data-price');
    
    document.getElementById('unit_price').value = price || '';
    document.getElementById('quantity').max = remaining || '';
    
    const quantityText = document.getElementById('available-quantity-text');
    if (remaining) {
        quantityText.textContent = 'Available quantity: ' + remaining + ' units';
        quantityText.className = 'form-text text-info';
    } else {
        quantityText.textContent = 'Select an item to see available quantity';
        quantityText.className = 'form-text text-muted';
    }
    
    calculateTotal();
}

function calculateTotal() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const unitPrice = parseFloat(document.getElementById('unit_price').value) || 0;
    const total = quantity * unitPrice;
    document.getElementById('total_amount').value = total.toFixed(2);
}

function fillSaleForm(itemId, itemName, remaining, price) {
    document.getElementById('item_id').value = itemId;
    document.getElementById('unit_price').value = price;
    document.getElementById('quantity').max = remaining;
    document.getElementById('quantity').value = 1;
    
    const quantityText = document.getElementById('available-quantity-text');
    quantityText.textContent = 'Available quantity: ' + remaining + ' units';
    quantityText.className = 'form-text text-info';
    
    calculateTotal();
    
    // Scroll to form
    document.getElementById('saleForm').scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
