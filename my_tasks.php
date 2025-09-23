<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is employee
if (!isEmployee()) {
    redirect('dashboard.php');
}

$success_message = '';
$error_message = '';

// Handle task status updates
if ($_POST) {
    $action = $_POST['action'];
    $task_id = intval($_POST['task_id']);
    
    if ($action === 'update_status') {
        $status = $_POST['status'];
        
        $query = "UPDATE tasks SET status = :status";
        if ($status === 'completed') {
            $query .= ", completed_at = NOW()";
        }
        $query .= " WHERE id = :task_id AND assigned_to = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':task_id', $task_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $success_message = 'Task status updated successfully.';
        } else {
            $error_message = 'Failed to update task status.';
        }
    }
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
$where_clause = "WHERE t.assigned_to = :user_id";
if ($filter === 'pending') {
    $where_clause .= " AND t.status = 'pending'";
} elseif ($filter === 'in_progress') {
    $where_clause .= " AND t.status = 'in_progress'";
} elseif ($filter === 'completed') {
    $where_clause .= " AND t.status = 'completed'";
}

$query = "SELECT t.*, u.first_name as assigned_by_name, u.last_name as assigned_by_last
          FROM tasks t 
          JOIN users u ON t.assigned_by = u.id 
          $where_clause ORDER BY t.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$tasks = $stmt->fetchAll();

// Get task statistics
$query = "SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks
          FROM tasks WHERE assigned_to = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$task_stats = $stmt->fetch();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>My Tasks</h2>
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

<!-- Task Statistics -->
<div class="row column1">
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-tasks yellow_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $task_stats['total_tasks']; ?></p>
                    <p class="head_couter">Total Tasks</p>
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
                    <p class="total_no"><?php echo $task_stats['pending_tasks']; ?></p>
                    <p class="head_couter">Pending</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-play green_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $task_stats['in_progress_tasks']; ?></p>
                    <p class="head_couter">In Progress</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-check-circle red_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $task_stats['completed_tasks']; ?></p>
                    <p class="head_couter">Completed</p>
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
                    <h2>Filter Tasks</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-group" role="group">
                            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                            <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
                            <a href="?filter=in_progress" class="btn <?php echo $filter === 'in_progress' ? 'btn-info' : 'btn-outline-info'; ?>">In Progress</a>
                            <a href="?filter=completed" class="btn <?php echo $filter === 'completed' ? 'btn-success' : 'btn-outline-success'; ?>">Completed</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tasks Table -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>My Tasks (<?php echo count($tasks); ?> total)</h2>
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
                                        <th>Title</th>
                                        <th>Assigned By</th>
                                        <th>Priority</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($tasks) > 0): ?>
                                        <?php foreach ($tasks as $task): ?>
                                            <tr>
                                                <td><?php echo $task['id']; ?></td>
                                                <td>
                                                    <strong><?php echo $task['title']; ?></strong>
                                                    <?php if ($task['description']): ?>
                                                        <br><small class="text-muted"><?php echo substr($task['description'], 0, 100) . (strlen($task['description']) > 100 ? '...' : ''); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $task['assigned_by_name'] . ' ' . $task['assigned_by_last']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $task['priority'] === 'urgent' ? 'danger' : 
                                                            ($task['priority'] === 'high' ? 'warning' : 
                                                            ($task['priority'] === 'medium' ? 'info' : 'secondary')); 
                                                    ?>">
                                                        <?php echo ucfirst($task['priority']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $task['status'] === 'completed' ? 'success' : 
                                                            ($task['status'] === 'in_progress' ? 'info' : 
                                                            ($task['status'] === 'pending' ? 'warning' : 'danger')); 
                                                    ?>">
                                                        <?php echo ucfirst($task['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($task['due_date']): ?>
                                                        <?php 
                                                        $due_date = strtotime($task['due_date']);
                                                        $today = time();
                                                        $days_left = ceil(($due_date - $today) / (60 * 60 * 24));
                                                        ?>
                                                        <?php echo formatDate($task['due_date']); ?>
                                                        <?php if ($days_left < 0): ?>
                                                            <br><small class="text-danger">Overdue by <?php echo abs($days_left); ?> days</small>
                                                        <?php elseif ($days_left == 0): ?>
                                                            <br><small class="text-warning">Due today</small>
                                                        <?php elseif ($days_left <= 3): ?>
                                                            <br><small class="text-warning"><?php echo $days_left; ?> days left</small>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        No due date
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo formatDate($task['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <?php if ($task['status'] === 'pending'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                                <input type="hidden" name="status" value="in_progress">
                                                                <button type="submit" class="btn btn-info btn-sm" title="Start Task">
                                                                    <i class="fa fa-play"></i> Start
                                                                </button>
                                                            </form>
                                                        <?php elseif ($task['status'] === 'in_progress'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                                <input type="hidden" name="status" value="completed">
                                                                <button type="submit" class="btn btn-success btn-sm" title="Complete Task" onclick="return confirm('Mark this task as completed?')">
                                                                    <i class="fa fa-check"></i> Complete
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No tasks found</td>
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
