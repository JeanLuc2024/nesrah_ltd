<?php
require_once __DIR__ . '/includes/header.php';

// Get recent activities
$recent_activities = array();

if (isAdmin()) {
    // Recent stock requests
    $query = "SELECT sr.*, u.first_name, u.last_name, i.item_name 
              FROM stock_requests sr 
              JOIN users u ON sr.user_id = u.id 
              JOIN inventory i ON sr.item_id = i.id 
              ORDER BY sr.created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_activities['stock_requests'] = $stmt->fetchAll();
    
    // Recent sales
    $query = "SELECT s.*, u.first_name, u.last_name, i.item_name 
              FROM sales s 
              JOIN users u ON s.user_id = u.id 
              JOIN inventory i ON s.item_id = i.id 
              ORDER BY s.created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_activities['sales'] = $stmt->fetchAll();
    
    // Recent tasks
    $query = "SELECT t.*, u1.first_name as assigned_to_name, u1.last_name as assigned_to_last, 
                     u2.first_name as assigned_by_name, u2.last_name as assigned_by_last
              FROM tasks t 
              JOIN users u1 ON t.assigned_to = u1.id 
              JOIN users u2 ON t.assigned_by = u2.id 
              ORDER BY t.created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $recent_activities['tasks'] = $stmt->fetchAll();
} else {
    // Employee recent activities
    $query = "SELECT * FROM tasks WHERE assigned_to = :user_id ORDER BY created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $recent_activities['my_tasks'] = $stmt->fetchAll();
    
    $query = "SELECT s.*, i.item_name FROM sales s 
              JOIN inventory i ON s.item_id = i.id 
              WHERE s.user_id = :user_id ORDER BY s.created_at DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $recent_activities['my_sales'] = $stmt->fetchAll();
}
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Dashboard</h2>
            <p>Welcome back, <?php echo $user_name; ?>!</p>
        </div>
    </div>
</div>

<?php if (isAdmin()): ?>
<!-- Admin Dashboard -->
<div class="row column1">
    <div class="col-md-6 col-lg-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-users yellow_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $stats['total_employees']; ?></p>
                    <p class="head_couter">Total Employees</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-cubes blue1_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $stats['total_items']; ?></p>
                    <p class="head_couter">Inventory Items</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-exclamation-triangle green_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $stats['low_stock_items']; ?></p>
                    <p class="head_couter">Low Stock Items</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-hand-paper-o red_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $stats['pending_requests']; ?></p>
                    <p class="head_couter">Pending Requests</p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row column1">
    <div class="col-md-6 col-lg-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-shopping-cart blue2_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $stats['sales_today_count']; ?></p>
                    <p class="head_couter">Sales Today</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-dollar green_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo formatCurrency($stats['sales_today_amount']); ?></p>
                    <p class="head_couter">Revenue Today</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-user-plus orange_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $stats['pending_approvals']; ?></p>
                    <p class="head_couter">Pending Approvals</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-tasks purple_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo count($recent_activities['tasks']); ?></p>
                    <p class="head_couter">Recent Tasks</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php else: ?>
<!-- Employee Dashboard -->
<div class="row column1">
    <div class="col-md-6 col-lg-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-cubes yellow_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $stats['my_allocations']; ?></p>
                    <p class="head_couter">My Allocations</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-tasks blue1_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $stats['my_tasks']; ?></p>
                    <p class="head_couter">My Tasks</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-shopping-cart green_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $stats['my_sales_today_count']; ?></p>
                    <p class="head_couter">Sales Today</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-dollar red_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo formatCurrency($stats['my_sales_today_amount']); ?></p>
                    <p class="head_couter">Revenue Today</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance Status -->
<div class="row column1">
    <div class="col-md-12">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-clock-o blue2_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <?php if ($stats['attendance_today']): ?>
                        <?php if ($stats['attendance_today']['check_out']): ?>
                            <p class="total_no" style="color: #28a745;">Checked Out</p>
                            <p class="head_couter"><?php echo formatDateTime($stats['attendance_today']['check_out']); ?></p>
                        <?php else: ?>
                            <p class="total_no" style="color: #007bff;">Checked In</p>
                            <p class="head_couter"><?php echo formatDateTime($stats['attendance_today']['check_in']); ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="total_no" style="color: #dc3545;">Not Checked In</p>
                        <p class="head_couter">Please check in to start your day</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Recent Activities -->
<div class="row column2">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Recent Activities</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <div class="content">
                            <?php if (isAdmin()): ?>
                                <!-- Admin Recent Activities -->
                                <div class="row">
                                    <div class="col-md-4">
                                        <h4>Recent Stock Requests</h4>
                                        <?php if (count($recent_activities['stock_requests']) > 0): ?>
                                            <?php foreach ($recent_activities['stock_requests'] as $request): ?>
                                                <div class="activity_item">
                                                    <p><strong><?php echo $request['first_name'] . ' ' . $request['last_name']; ?></strong> requested <strong><?php echo $request['requested_quantity']; ?></strong> units of <strong><?php echo $request['item_name']; ?></strong></p>
                                                    <small class="text-muted"><?php echo getTimeAgo($request['created_at']); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No recent stock requests</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <h4>Recent Sales</h4>
                                        <?php if (count($recent_activities['sales']) > 0): ?>
                                            <?php foreach ($recent_activities['sales'] as $sale): ?>
                                                <div class="activity_item">
                                                    <p><strong><?php echo $sale['first_name'] . ' ' . $sale['last_name']; ?></strong> sold <strong><?php echo $sale['quantity']; ?></strong> units of <strong><?php echo $sale['item_name']; ?></strong></p>
                                                    <small class="text-muted"><?php echo getTimeAgo($sale['created_at']); ?> - <?php echo formatCurrency($sale['total_amount']); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No recent sales</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <h4>Recent Tasks</h4>
                                        <?php if (count($recent_activities['tasks']) > 0): ?>
                                            <?php foreach ($recent_activities['tasks'] as $task): ?>
                                                <div class="activity_item">
                                                    <p><strong><?php echo $task['title']; ?></strong> assigned to <strong><?php echo $task['assigned_to_name'] . ' ' . $task['assigned_to_last']; ?></strong></p>
                                                    <small class="text-muted"><?php echo getTimeAgo($task['created_at']); ?> - Status: <?php echo ucfirst($task['status']); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No recent tasks</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- Employee Recent Activities -->
                                <div class="row">
                                    <div class="col-md-6">
                                        <h4>My Recent Tasks</h4>
                                        <?php if (count($recent_activities['my_tasks']) > 0): ?>
                                            <?php foreach ($recent_activities['my_tasks'] as $task): ?>
                                                <div class="activity_item">
                                                    <p><strong><?php echo $task['title']; ?></strong></p>
                                                    <small class="text-muted"><?php echo getTimeAgo($task['created_at']); ?> - Status: <?php echo ucfirst($task['status']); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No recent tasks</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <h4>My Recent Sales</h4>
                                        <?php if (count($recent_activities['my_sales']) > 0): ?>
                                            <?php foreach ($recent_activities['my_sales'] as $sale): ?>
                                                <div class="activity_item">
                                                    <p><strong><?php echo $sale['item_name']; ?></strong> - <?php echo $sale['quantity']; ?> units</p>
                                                    <small class="text-muted"><?php echo getTimeAgo($sale['created_at']); ?> - <?php echo formatCurrency($sale['total_amount']); ?></small>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No recent sales</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.activity_item {
    padding: 10px 0;
    border-bottom: 1px solid #eee;
}
.activity_item:last-child {
    border-bottom: none;
}
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
