<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is employee
if (!isEmployee()) {
    redirect('dashboard.php');
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'today';

// Build query based on filters
$where_clause = "WHERE s.user_id = :user_id";
if ($date_filter === 'today') {
    $where_clause .= " AND DATE(s.created_at) = CURDATE()";
} elseif ($date_filter === 'week') {
    $where_clause .= " AND s.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
} elseif ($date_filter === 'month') {
    $where_clause .= " AND s.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
}

$query = "SELECT s.*, i.item_name, i.item_code
          FROM sales s 
          JOIN inventory i ON s.item_id = i.id 
          $where_clause ORDER BY s.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_sales = $stmt->fetchAll();

// Get my sales statistics
$query = "SELECT 
            COUNT(*) as total_sales,
            COALESCE(SUM(total_amount), 0) as total_revenue,
            COALESCE(AVG(total_amount), 0) as average_sale
          FROM sales $where_clause";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$sales_stats = $stmt->fetch();

// Get my top selling items
$query = "SELECT i.item_name, i.item_code, SUM(s.quantity) as total_quantity, SUM(s.total_amount) as total_revenue
          FROM sales s 
          JOIN inventory i ON s.item_id = i.id 
          $where_clause
          GROUP BY s.item_id, i.item_name, i.item_code 
          ORDER BY total_quantity DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_top_items = $stmt->fetchAll();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>My Sales Performance</h2>
        </div>
    </div>
</div>

<!-- My Sales Statistics -->
<div class="row column1">
    <div class="col-md-4">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-shopping-cart yellow_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $sales_stats['total_sales']; ?></p>
                    <p class="head_couter">My Sales</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-dollar green_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo formatCurrency($sales_stats['total_revenue']); ?></p>
                    <p class="head_couter">My Revenue</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-chart-line blue1_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo formatCurrency($sales_stats['average_sale']); ?></p>
                    <p class="head_couter">Average Sale</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Date Filter -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Filter by Date</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-group" role="group">
                            <a href="?date_filter=today" class="btn <?php echo $date_filter === 'today' ? 'btn-primary' : 'btn-outline-primary'; ?>">Today</a>
                            <a href="?date_filter=week" class="btn <?php echo $date_filter === 'week' ? 'btn-primary' : 'btn-outline-primary'; ?>">This Week</a>
                            <a href="?date_filter=month" class="btn <?php echo $date_filter === 'month' ? 'btn-primary' : 'btn-outline-primary'; ?>">This Month</a>
                            <a href="?date_filter=all" class="btn <?php echo $date_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All Time</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Top Selling Items -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>My Top Selling Items</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <?php if (count($my_top_items) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Quantity Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($my_top_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $item['item_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $item['item_code']; ?></small>
                                                </td>
                                                <td><?php echo $item['total_quantity']; ?></td>
                                                <td><?php echo formatCurrency($item['total_revenue']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No sales data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Sales Table -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>My Sales Records (<?php echo count($my_sales); ?> total)</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <button class="btn btn-primary mb-3" onclick="window.print()">
                            <i class="fa fa-print"></i> Print Sales Report
                        </button>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date</th>
                                        <th>Item</th>
                                        <th>Customer</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($my_sales) > 0): ?>
                                        <?php foreach ($my_sales as $sale): ?>
                                            <tr>
                                                <td><?php echo $sale['id']; ?></td>
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
                                                    <?php if ($sale['customer_email']): ?>
                                                        <br><small class="text-muted"><?php echo $sale['customer_email']; ?></small>
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
                                                <td>
                                                    <?php if ($sale['notes']): ?>
                                                        <?php echo substr($sale['notes'], 0, 50) . (strlen($sale['notes']) > 50 ? '...' : ''); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No notes</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No sales found</td>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
