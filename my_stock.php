<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is employee
if (!isEmployee()) {
    redirect('dashboard.php');
}

// Get my stock allocations
$query = "SELECT sa.*, i.item_name, i.item_code, i.unit_price
          FROM stock_allocations sa 
          JOIN inventory i ON sa.item_id = i.id 
          WHERE sa.user_id = :user_id 
          ORDER BY sa.allocated_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_allocations = $stmt->fetchAll();

// Get my stock statistics
$query = "SELECT 
            COUNT(*) as total_allocations,
            SUM(allocated_quantity) as total_allocated,
            SUM(remaining_quantity) as total_remaining,
            SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_allocations
          FROM stock_allocations WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$stock_stats = $stmt->fetch();

// Get my sales from allocated stock
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
            <h2>My Stock Allocations</h2>
        </div>
    </div>
</div>

<!-- Stock Statistics -->
<div class="row column1">
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-cubes yellow_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $stock_stats['total_allocations']; ?></p>
                    <p class="head_couter">Total Allocations</p>
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
                    <p class="total_no"><?php echo $stock_stats['active_allocations']; ?></p>
                    <p class="head_couter">Active</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-arrow-up blue1_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $stock_stats['total_allocated']; ?></p>
                    <p class="head_couter">Total Allocated</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-arrow-down red_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $stock_stats['total_remaining']; ?></p>
                    <p class="head_couter">Remaining</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Stock Allocations -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>My Stock Allocations</h2>
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
                                            <th>Unit Price</th>
                                            <th>Status</th>
                                            <th>Allocated Date</th>
                                            <th>Progress</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($my_allocations as $allocation): ?>
                                            <?php 
                                            $progress_percentage = $allocation['allocated_quantity'] > 0 ? 
                                                (($allocation['allocated_quantity'] - $allocation['remaining_quantity']) / $allocation['allocated_quantity']) * 100 : 0;
                                            ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $allocation['item_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $allocation['item_code']; ?></small>
                                                </td>
                                                <td><?php echo $allocation['allocated_quantity']; ?></td>
                                                <td>
                                                    <span class="<?php 
                                                        echo $allocation['remaining_quantity'] == 0 ? 'text-success font-weight-bold' : 
                                                            ($allocation['remaining_quantity'] <= 5 ? 'text-warning font-weight-bold' : ''); 
                                                    ?>">
                                                        <?php echo $allocation['remaining_quantity']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatCurrency($allocation['unit_price']); ?></td>
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
                                                    <div class="progress" style="height: 20px;">
                                                        <div class="progress-bar <?php 
                                                            echo $progress_percentage == 100 ? 'bg-success' : 
                                                                ($progress_percentage >= 50 ? 'bg-info' : 'bg-warning'); 
                                                        ?>" 
                                                             role="progressbar" 
                                                             style="width: <?php echo $progress_percentage; ?>%" 
                                                             aria-valuenow="<?php echo $progress_percentage; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                            <?php echo number_format($progress_percentage, 1); ?>%
                                                        </div>
                                                    </div>
                                                </td>
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

<!-- Recent Sales from My Stock -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Recent Sales from My Stock</h2>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
