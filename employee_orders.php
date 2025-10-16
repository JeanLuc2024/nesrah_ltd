<?php
// Start output buffering
ob_start();

require_once __DIR__ . '/includes/header.php';

// Check if user is employee or admin
if (!isEmployee() && !isAdmin()) {
    redirect('dashboard.php');
}

// Get current user ID
$user_id = $_SESSION['user_id'] ?? 0;
$user_role = $_SESSION['user_role'] ?? '';

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
        $payment_method = $_POST['payment_method'] ?? 'cash';
        $payment_status = $_POST['payment_status'] ?? 'paid';
        $notes = sanitizeInput($_POST['notes'] ?? '');

        // Get items from the form
        $items = $_POST['items'] ?? [];

        // Validate that we have at least one item
        if (empty($items) || !is_array($items)) {
            $validation_errors[] = 'Please add at least one item to the order.';
        } else {
            // Validate each item
            foreach ($items as $index => $item) {
                $item_id = intval($item['item_id'] ?? 0);
                $manual_item_name = sanitizeInput($item['manual_item_name'] ?? '');
                $quantity = intval($item['quantity'] ?? 0);
                $unit_price = floatval($item['unit_price'] ?? 0);
        
                // Check if either item_id is selected OR manual item name is provided
                if ($item_id <= 0 && empty($manual_item_name)) {
                    $validation_errors[] = 'Please select an item from dropdown or enter item name manually for item #' . ($index + 1);
                }
        
                if ($quantity <= 0) {
                    $validation_errors[] = 'Quantity must be greater than 0 for item #' . ($index + 1);
                }
        
                if ($unit_price <= 0) {
                    $validation_errors[] = 'Unit price must be greater than 0 for item #' . ($index + 1);
                }
            }
        }

        // Validation
        if (empty($customer_name)) {
            $validation_errors[] = 'Customer name is required.';
        }

        // If no validation errors, proceed with database operations
        if (empty($validation_errors)) {
            try {
                // Start transaction
                $db->beginTransaction();

                // Process each item in the order and calculate total
                $total_amount = 0;
                $order_items = [];

                // First, validate all items and calculate total
                foreach ($items as $item) {
                    $item_id = intval($item['item_id']);
                    $manual_item_name = sanitizeInput($item['manual_item_name'] ?? '');
                    $quantity = intval($item['quantity']);
                    $unit_price = floatval($item['unit_price']);

                    $item_data = null;
                    $is_manual_item = false;

                    if ($item_id > 0) {
                        // Regular inventory item
                        $query = "SELECT i.id, i.unit_price as default_price, i.item_name, i.item_code, i.current_stock
                                  FROM inventory i
                                  WHERE i.id = :item_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
                        $stmt->execute();

                        if ($stmt->rowCount() === 0) {
                            throw new Exception('Item not found: ' . $item['item_name']);
                        }

                        $item_data = $stmt->fetch();

                        // For employees, check if they have allocation OR allow direct sales from inventory
                        if ($user_role === 'employee') {
                            // Check if employee has allocation for this item
                            $alloc_query = "SELECT sa.id as allocation_id, sa.remaining_quantity
                                           FROM stock_allocations sa
                                           WHERE sa.item_id = :item_id
                                           AND sa.user_id = :user_id
                                           AND sa.status = 'active'
                                           AND sa.remaining_quantity >= :quantity";
                            $alloc_stmt = $db->prepare($alloc_query);
                            $alloc_stmt->bindParam(':item_id', $item_id, PDO::PARAM_INT);
                            $alloc_stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                            $alloc_stmt->bindParam(':quantity', $quantity, PDO::PARAM_INT);
                            $alloc_stmt->execute();

                            $has_allocation = $alloc_stmt->rowCount() > 0;
                            $allocation_data = $has_allocation ? $alloc_stmt->fetch() : null;

                            // If no allocation, check if there's enough stock in inventory
                            if (!$has_allocation && $item_data['current_stock'] < $quantity) {
                                throw new Exception('Insufficient stock for ' . $item_data['item_name'] . '. Available: ' . $item_data['current_stock']);
                            }
                        }
                    } elseif (!empty($manual_item_name)) {
                        // Manual item entry (customer requested item)
                        $is_manual_item = true;
                        $item_data = [
                            'id' => null, // No inventory ID for manual items
                            'item_name' => $manual_item_name,
                            'item_code' => 'MANUAL-' . time() . '-' . rand(100, 999),
                            'current_stock' => 0,
                            'unit_price' => $unit_price
                        ];
                    } else {
                        throw new Exception('Invalid item data for order processing');
                    }

                    $item_total = $unit_price * $quantity;
                    $total_amount += $item_total;

                    // Store item data for processing after validation
                    $order_items[] = [
                        'item_id' => $item_id,
                        'manual_item_name' => $manual_item_name,
                        'is_manual_item' => $is_manual_item,
                        'quantity' => $quantity,
                        'unit_price' => $unit_price,
                        'item_name' => $item_data['item_name'],
                        'item_code' => $item_data['item_code'],
                        'allocation_id' => (!$is_manual_item && $user_role === 'employee' && isset($allocation_data)) ? $allocation_data['allocation_id'] : null,
                        'has_allocation' => (!$is_manual_item && $user_role === 'employee' && isset($allocation_data)),
                        'current_stock' => $item_data['current_stock'],
                        'new_stock' => (!$is_manual_item) ? $item_data['current_stock'] - $quantity : 0
                    ];
                }

                // Insert the main order record
                $query = "INSERT INTO orders (
                            user_id,
                            customer_name,
                            customer_phone,
                            customer_email,
                            total_amount,
                            payment_method,
                            payment_status,
                            notes,
                            created_at
                          ) VALUES (
                            :user_id,
                            :customer_name,
                            :customer_phone,
                            :customer_email,
                            :total_amount,
                            :payment_method,
                            :payment_status,
                            :notes,
                            NOW()
                          )";

                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                $stmt->bindParam(':customer_name', $customer_name, PDO::PARAM_STR);
                $stmt->bindParam(':customer_phone', $customer_phone, PDO::PARAM_STR);
                $stmt->bindParam(':customer_email', $customer_email, PDO::PARAM_STR);
                $stmt->bindParam(':total_amount', $total_amount, PDO::PARAM_STR);
                $stmt->bindParam(':payment_method', $payment_method, PDO::PARAM_STR);
                $stmt->bindParam(':payment_status', $payment_status, PDO::PARAM_STR);
                $stmt->bindParam(':notes', $notes, PDO::PARAM_STR);

                if (!$stmt->execute()) {
                    throw new Exception('Failed to create order');
                }

                $order_id = $db->lastInsertId();

                // Process each item in the order
                foreach ($order_items as $item) {
                    // Insert order item - handle both inventory items and manual items
                    $query = "INSERT INTO order_items (
                                order_id,
                                item_id,
                                manual_item_name,
                                quantity,
                                unit_price,
                                total_price,
                                created_at
                              ) VALUES (
                                :order_id,
                                :item_id,
                                :manual_item_name,
                                :quantity,
                                :unit_price,
                                :total_price,
                                NOW()
                              )";

                    $item_total = $item['unit_price'] * $item['quantity'];

                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);

                    // For manual items, use NULL for item_id and set manual_item_name
                    if ($item['is_manual_item']) {
                        $stmt->bindValue(':item_id', null, PDO::PARAM_NULL);
                        $stmt->bindParam(':manual_item_name', $item['manual_item_name'], PDO::PARAM_STR);
                    } else {
                        $stmt->bindParam(':item_id', $item['item_id'], PDO::PARAM_INT);
                        $stmt->bindValue(':manual_item_name', null, PDO::PARAM_NULL);
                    }

                    $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                    $stmt->bindParam(':unit_price', $item['unit_price'], PDO::PARAM_STR);
                    $stmt->bindParam(':total_price', $item_total, PDO::PARAM_STR);

                    if (!$stmt->execute()) {
                        throw new Exception('Failed to add item to order');
                    }

                    // Only update inventory and stock history for actual inventory items, not manual items
                    if (!$item['is_manual_item']) {
                        // Update inventory for this item
                        $query = "UPDATE inventory
                                  SET current_stock = current_stock - :quantity
                                  WHERE id = :item_id
                                  AND current_stock >= :quantity";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':item_id', $item['item_id'], PDO::PARAM_INT);
                        $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                        $stmt->execute();

                        if ($stmt->rowCount() === 0) {
                            throw new Exception('Inventory update failed for ' . $item['item_name'] . '. The item stock may have been modified by another user.');
                        }

                        // If employee has allocation, update it
                        if ($item['has_allocation'] && $item['allocation_id']) {
                            $query = "UPDATE stock_allocations
                                      SET remaining_quantity = remaining_quantity - :quantity
                                      WHERE id = :allocation_id
                                      AND remaining_quantity >= :quantity";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':allocation_id', $item['allocation_id'], PDO::PARAM_INT);
                            $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                            $stmt->execute();

                            if ($stmt->rowCount() === 0) {
                                throw new Exception('Failed to update stock allocation for ' . $item['item_name']);
                            }

                            // Mark allocation as completed if no remaining quantity
                            $query = "UPDATE stock_allocations
                                      SET status = 'completed',
                                          completed_at = NOW()
                                      WHERE id = :allocation_id
                                      AND remaining_quantity = 0";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':allocation_id', $item['allocation_id'], PDO::PARAM_INT);
                            $stmt->execute();
                        }

                        // Record stock history
                        $query = "INSERT INTO stock_history (
                                    item_id,
                                    movement_type,
                                    quantity,
                                    previous_stock,
                                    new_stock,
                                    reference_id,
                                    created_by,
                                    notes
                                  ) VALUES (
                                    :item_id,
                                    'sale',
                                    :quantity,
                                    :previous_stock,
                                    :new_stock,
                                    :sale_id,
                                    :user_id,
                                    :notes
                                  )";

                        $previous_stock = $item['current_stock'];
                        $new_stock = $item['new_stock'];

                        // Prepare notes for stock history
                        $notes = "Sold to: $customer_name" . ($customer_phone ? " ($customer_phone)" : '');

                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':item_id', $item['item_id'], PDO::PARAM_INT);
                        $stmt->bindParam(':quantity', $item['quantity'], PDO::PARAM_INT);
                        $stmt->bindParam(':previous_stock', $previous_stock, PDO::PARAM_INT);
                        $stmt->bindParam(':new_stock', $new_stock, PDO::PARAM_INT);
                        $stmt->bindParam(':sale_id', $order_id, PDO::PARAM_INT);
                        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
                        $stmt->bindValue(':notes', $notes, PDO::PARAM_STR);

                        if (!$stmt->execute()) {
                            throw new Exception('Failed to record stock history');
                        }
                    }
                }

                // If we got here, everything was successful
                $db->commit();

                // Clear the output buffer
                ob_clean();

                // Redirect to prevent form resubmission
                header('Location: ' . $_SERVER['PHP_SELF'] . '?success=1');
                exit();

            } catch (Exception $e) {
                // Rollback transaction on error
                if ($db->inTransaction()) {
                    $db->rollBack();
                }

                // Log the error
                error_log('Order processing error: ' . $e->getMessage() .
                         ' in ' . $e->getFile() .
                         ' on line ' . $e->getLine() .
                         '\nStack trace:\n' . $e->getTraceAsString());

                // Store error in session to display after redirect
                $_SESSION['error_message'] = 'Error processing your order. Please try again. ';
                if (ini_get('display_errors')) {
                    $_SESSION['error_message'] .= '<br><small>Error: ' . htmlspecialchars($e->getMessage()) . '</small>';
                }

                // Clear the output buffer and redirect
                ob_clean();
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            }
        } else {
            $error_message = implode('<br>', $validation_errors);
        }
    }
}

// Get all inventory items (for both employees and admins)
$query = "SELECT i.id, i.item_name, i.item_code, i.unit_price, i.current_stock
          FROM inventory i
          WHERE i.current_stock > 0
          ORDER BY i.item_name";
$stmt = $db->prepare($query);
$stmt->execute();
$inventory_items = $stmt->fetchAll();

// Get recent orders based on user role
if ($user_role === 'admin') {
    // Admins can see all orders
    $query = "SELECT o.*, u.first_name, u.last_name,
                     (SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', COALESCE(i.item_name, oi.manual_item_name)) SEPARATOR ', ')
                      FROM order_items oi
                      LEFT JOIN inventory i ON oi.item_id = i.id
                      WHERE oi.order_id = o.id) as order_items
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.id
              ORDER BY o.created_at DESC
              LIMIT 20";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_orders = $stmt->fetchAll();
} else {
    // Employees can only see their own orders
    $query = "SELECT o.*, u.first_name, u.last_name,
                     (SELECT GROUP_CONCAT(CONCAT(oi.quantity, 'x ', COALESCE(i.item_name, oi.manual_item_name)) SEPARATOR ', ')
                      FROM order_items oi
                      LEFT JOIN inventory i ON oi.item_id = i.id
                      WHERE oi.order_id = o.id) as order_items
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.id
              WHERE o.user_id = :user_id
              ORDER BY o.created_at DESC
              LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $recent_orders = $stmt->fetchAll();
}
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2><?php echo $user_role === 'admin' ? 'Order Management' : 'Record New Order'; ?></h2>
        </div>
    </div>
</div>

<?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Order recorded successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error_message'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error_message']; 
        // Clear the error message after displaying it
        unset($_SESSION['error_message']);
        ?>
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

                            <!-- Order Items -->
                            <div class="form-section mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="section-title text-primary mb-0">
                                        <i class="fa fa-shopping-cart me-2"></i>Order Items
                                    </h6>
                                    <button type="button" class="btn btn-sm btn-primary" id="add-item-btn">
                                        <i class="fa fa-plus me-1"></i> Add Item
                                    </button>
                                </div>

                                <div id="order-items">
                                    <!-- Item row template (hidden) -->
                                    <div class="item-row mb-3 border-bottom pb-3" style="display: none;">
                                        <div class="row g-2">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Item <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <select class="form-select item-select" name="items[0][item_id]" required style="display: block;">
                                                            <option value="">Select an item</option>
                                                            <option value="add_new" style="font-weight: bold; color: #007bff;">➕ Add New Item</option>
                                                            <?php foreach ($inventory_items as $item): ?>
                                                                <option value="<?php echo $item['id']; ?>"
                                                                        data-price="<?php echo $item['unit_price']; ?>"
                                                                        data-stock="<?php echo $item['current_stock']; ?>">
                                                                    <?php echo htmlspecialchars(sprintf(
                                                                        '%s (%s) - Available: %d',
                                                                        $item['item_name'],
                                                                        $item['item_code'],
                                                                        $item['current_stock']
                                                                    )); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <input type="text" class="form-control manual-item-input" name="items[0][manual_item_name]" placeholder="Enter item name manually" style="display: none;">
                                                        <button type="button" class="btn btn-outline-secondary switch-to-dropdown" style="display: none;" onclick="switchToDropdown(this)">
                                                            <i class="fa fa-list"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label class="form-label">Qty <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control quantity" name="items[0][quantity]" min="1" value="1" required>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rwf</span>
                                                        <input type="number" class="form-control unit-price" name="items[0][unit_price]" step="0.01" min="0" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label class="form-label">Total</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rwf</span>
                                                        <input type="text" class="form-control item-total" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Notes</label>
                                                    <input type="text" class="form-control" name="items[0][notes]" placeholder="Optional notes">
                                                </div>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-sm btn-danger remove-item" style="margin-bottom: 0.5rem;">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- First item (always visible) -->
                                    <div class="item-row mb-3 border-bottom pb-3">
                                        <div class="row g-2">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Item <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <select class="form-select item-select" name="items[0][item_id]" required style="display: block;">
                                                            <option value="">Select an item</option>
                                                            <option value="add_new" style="font-weight: bold; color: #007bff;">➕ Add New Item</option>
                                                            <?php foreach ($inventory_items as $item): ?>
                                                                <option value="<?php echo $item['id']; ?>"
                                                                        data-price="<?php echo $item['unit_price']; ?>"
                                                                        data-stock="<?php echo $item['current_stock']; ?>">
                                                                    <?php echo htmlspecialchars(sprintf(
                                                                        '%s (%s) - Available: %d',
                                                                        $item['item_name'],
                                                                        $item['item_code'],
                                                                        $item['current_stock']
                                                                    )); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                        <input type="text" class="form-control manual-item-input" name="items[0][manual_item_name]" placeholder="Enter item name manually" style="display: none;">
                                                        <button type="button" class="btn btn-outline-secondary switch-to-dropdown" style="display: none;" onclick="switchToDropdown(this)">
                                                            <i class="fa fa-list"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label class="form-label">Qty <span class="text-danger">*</span></label>
                                                    <input type="number" class="form-control quantity" name="items[0][quantity]" min="1" value="1" required>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label class="form-label">Unit Price <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rwf</span>
                                                        <input type="number" class="form-control unit-price" name="items[0][unit_price]" step="0.01" min="0" required>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="form-group">
                                                    <label class="form-label">Total</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Rwf</span>
                                                        <input type="text" class="form-control item-total" readonly>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label class="form-label">Notes</label>
                                                    <input type="text" class="form-control" name="items[0][notes]" placeholder="Optional notes">
                                                </div>
                                            </div>
                                            <div class="col-md-1 d-flex align-items-end">
                                                <button type="button" class="btn btn-sm btn-danger remove-item" style="margin-bottom: 0.5rem;" disabled>
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
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
                                            <div class="input-group">
                                                <span class="input-group-text">Rwf</span>
                                                <input type="text" class="form-control" id="total_amount" readonly>
                                            </div>
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
                    <h2><i class="fa fa-list me-2"></i><?php echo $user_role === 'admin' ? 'All Orders' : 'My Recent Orders'; ?></h2>
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
                                            <th>Items</th>
                                            <th class="text-end">Total Amount</th>
                                            <th>Payment</th>
                                            <th>Status</th>
                                            <?php if ($user_role === 'admin'): ?>
                                            <th>Created By</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                                <td>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                                <td><?php echo htmlspecialchars($order['order_items'] ?? 'N/A'); ?></td>
                                                <td class="text-end"><?php echo formatCurrency($order['total_amount']); ?></td>
                                                <td><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></td>
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
                                                <?php if ($user_role === 'admin'): ?>
                                                <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                                <?php endif; ?>
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
// Format currency
function formatCurrency(amount) {
    return 'Rwf ' + new Intl.NumberFormat('en-US', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 2
    }).format(amount);
}

// Calculate total for a single row
function calculateRowTotal(row) {
    const quantityInput = row.querySelector('.quantity');
    const unitPriceInput = row.querySelector('.unit-price');
    const itemTotalInput = row.querySelector('.item-total');

    if (quantityInput.value && unitPriceInput.value) {
        const quantity = parseInt(quantityInput.value);
        const unitPrice = parseFloat(unitPriceInput.value);

        if (quantity > 0 && unitPrice >= 0) {
            const total = quantity * unitPrice;
            itemTotalInput.value = formatCurrency(total);
            return total;
        }
    }

    itemTotalInput.value = '';
    return 0;
}

// Calculate and update the total amount
function updateTotal() {
    let total = 0;
    document.querySelectorAll('.item-row:not([style*="display: none"])').forEach(row => {
        total += calculateRowTotal(row);
    });

    const totalAmountInput = document.getElementById('total_amount');
    if (totalAmountInput) {
        totalAmountInput.value = formatCurrency(total);
    }
}

// Add new item row
document.getElementById('add-item-btn').addEventListener('click', function() {
    const itemRows = document.querySelectorAll('.item-row:not([style*="display: none"])');
    const template = document.querySelector('.item-row[style*="display: none"]');
    const newRow = template.cloneNode(true);

    // Update the index in the name attributes
    const newIndex = itemRows.length;
    newRow.style.display = '';

    // Update all form control names
    newRow.querySelectorAll('[name]').forEach(el => {
        el.name = el.name.replace(/\[\d+\]/, `[${newIndex}]`);
    });

    // Clear values and reset display states
    const itemSelect = newRow.querySelector('.item-select');
    const manualInput = newRow.querySelector('.manual-item-input');
    const switchBtn = newRow.querySelector('.switch-to-dropdown');
    const quantityInput = newRow.querySelector('.quantity');
    const unitPriceInput = newRow.querySelector('.unit-price');
    const itemTotalInput = newRow.querySelector('.item-total');
    const notesInput = newRow.querySelector('[name*="notes"]');

    if (itemSelect) {
        itemSelect.value = '';
        itemSelect.style.display = 'block';
    }
    if (manualInput) {
        manualInput.value = '';
        manualInput.style.display = 'none';
    }
    if (switchBtn) {
        switchBtn.style.display = 'none';
    }
    if (quantityInput) quantityInput.value = '1';
    if (unitPriceInput) unitPriceInput.value = '';
    if (itemTotalInput) itemTotalInput.value = '';
    if (notesInput) notesInput.value = '';

    // Insert before the template
    template.parentNode.insertBefore(newRow, template);

    // Add event listeners to the new row
    addRowEventListeners(newRow);

    // Enable remove buttons appropriately
    updateRemoveButtons();

    // Focus on the new item select
    if (itemSelect) itemSelect.focus();
});

// Add event listeners to a row
function addRowEventListeners(row) {
    // Item select change - populate unit price
    const select = row.querySelector('.item-select');
    const quantityInput = row.querySelector('.quantity');
    const unitPriceInput = row.querySelector('.unit-price');
    const removeBtn = row.querySelector('.remove-item');

    if (select) {
        select.addEventListener('change', function() {
            if (this.value === 'add_new') {
                // Switch to manual input mode
                switchToManualInput(row);
            } else if (this.value) {
                const selectedOption = this.options[this.selectedIndex];
                const defaultPrice = selectedOption.dataset.price;
                if (defaultPrice && unitPriceInput && !unitPriceInput.value) {
                    unitPriceInput.value = defaultPrice;
                }
            }
            updateTotal();
        });
    }

    if (quantityInput) quantityInput.addEventListener('input', updateTotal);
    if (unitPriceInput) unitPriceInput.addEventListener('input', updateTotal);

    // Remove button
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            row.remove();
            updateTotal();
            updateRemoveButtons();
        });
    }
}

// Function to update remove button states
function updateRemoveButtons() {
    const visibleRows = document.querySelectorAll('.item-row:not([style*="display: none"])');
    visibleRows.forEach((row, index) => {
        const removeBtn = row.querySelector('.remove-item');
        if (removeBtn) {
            removeBtn.disabled = index === 0; // Disable for first row
        }
    });
}

// Function to switch to manual input mode
function switchToManualInput(row) {
    const select = row.querySelector('.item-select');
    const manualInput = row.querySelector('.manual-item-input');
    const switchBtn = row.querySelector('.switch-to-dropdown');

    // Hide select and show manual input
    select.style.display = 'none';
    manualInput.style.display = 'block';
    switchBtn.style.display = 'block';

    // Focus on manual input
    manualInput.focus();

    // Clear any previous values
    select.value = '';
    manualInput.value = '';

    // Update total
    updateTotal();
}

// Function to switch back to dropdown
function switchToDropdown(btn) {
    const row = btn.closest('.item-row');
    const select = row.querySelector('.item-select');
    const manualInput = row.querySelector('.manual-item-input');
    const switchBtn = row.querySelector('.switch-to-dropdown');

    // Show select and hide manual input
    select.style.display = 'block';
    manualInput.style.display = 'none';
    switchBtn.style.display = 'none';

    // Clear values
    select.value = '';
    manualInput.value = '';

    // Update total
    updateTotal();
}


// Update form field names when adding new rows
function updateFormFieldNames() {
    const itemRows = document.querySelectorAll('.item-row:not([style*="display: none"])');
    itemRows.forEach((row, index) => {
        const inputs = row.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.name) {
                input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
            }
        });
    });
}

// Initialize event listeners for existing rows
document.addEventListener('DOMContentLoaded', function() {
    // Add event listeners to existing rows
    document.querySelectorAll('.item-row:not([style*="display: none"])').forEach(row => {
        addRowEventListeners(row);
    });

    // Initialize remove button states
    updateRemoveButtons();

    // Initial total calculation
    updateTotal();
});

// Form validation
document.getElementById('orderForm').addEventListener('submit', function(e) {
    let isValid = true;
    const itemRows = document.querySelectorAll('.item-row:not([style*="display: none"])');

    // Validate each visible row
    itemRows.forEach((row, index) => {
        const itemSelect = row.querySelector('.item-select');
        const manualInput = row.querySelector('.manual-item-input');
        const quantityInput = row.querySelector('.quantity');

        // Check if either dropdown has selection OR manual input has value
        const hasDropdownSelection = itemSelect && itemSelect.selectedIndex > 0;
        const hasManualInput = manualInput && manualInput.value.trim() !== '';

        if (!hasDropdownSelection && !hasManualInput) {
            e.preventDefault();
            alert(`Please select an item from dropdown or enter item name manually for item #${index + 1}`);
            if (itemSelect && itemSelect.style.display !== 'none') {
                itemSelect.focus();
            } else if (manualInput && manualInput.style.display !== 'none') {
                manualInput.focus();
            }
            isValid = false;
            return false;
        }

        if (!quantityInput || quantityInput.value <= 0) {
            e.preventDefault();
            alert(`Please enter a valid quantity for item #${index + 1}`);
            if (quantityInput) quantityInput.focus();
            isValid = false;
            return false;
        }
    });

    if (!isValid) {
        return false;
    }

    // Show loading state
    const submitBtn = this.querySelector('button[type="submit"]');
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Processing...';
    }

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
