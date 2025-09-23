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
    $action = $_POST['action'];
    
    if ($action === 'create_task') {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $assigned_to = intval($_POST['assigned_to']);
        $priority = $_POST['priority'];
        $due_date = $_POST['due_date'];
        
        $query = "INSERT INTO tasks (title, description, assigned_to, assigned_by, priority, due_date) 
                 VALUES (:title, :description, :assigned_to, :assigned_by, :priority, :due_date)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':assigned_to', $assigned_to);
        $stmt->bindParam(':assigned_by', $user_id);
        $stmt->bindParam(':priority', $priority);
        $stmt->bindParam(':due_date', $due_date);
        
        if ($stmt->execute()) {
            $success_message = 'Task created successfully.';
        } else {
            $error_message = 'Failed to create task.';
        }
    } elseif ($action === 'update_status') {
        $task_id = intval($_POST['task_id']);
        $status = $_POST['status'];
        
        $query = "UPDATE tasks SET status = :status";
        if ($status === 'completed') {
            $query .= ", completed_at = NOW()";
        }
        $query .= " WHERE id = :task_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':task_id', $task_id);
        
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
$where_clause = "WHERE 1=1";
if ($filter === 'pending') {
    $where_clause .= " AND status = 'pending'";
} elseif ($filter === 'in_progress') {
    $where_clause .= " AND status = 'in_progress'";
} elseif ($filter === 'completed') {
    $where_clause .= " AND status = 'completed'";
} elseif ($filter === 'cancelled') {
    $where_clause .= " AND status = 'cancelled'";
}

$query = "SELECT t.*, u1.first_name as assigned_to_name, u1.last_name as assigned_to_last, 
                 u2.first_name as assigned_by_name, u2.last_name as assigned_by_last
          FROM tasks t 
          JOIN users u1 ON t.assigned_to = u1.id 
          JOIN users u2 ON t.assigned_by = u2.id 
          $where_clause ORDER BY t.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$tasks = $stmt->fetchAll();

// Get active employees for task assignment
$query = "SELECT id, first_name, last_name FROM users WHERE role = 'employee' AND status = 'active' ORDER BY first_name";
$stmt = $db->prepare($query);
$stmt->execute();
$employees = $stmt->fetchAll();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Task Management</h2>
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

<!-- Create New Task Form -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Create New Task</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <form method="POST">
                            <input type="hidden" name="action" value="create_task">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Task Title *</label>
                                        <input type="text" name="title" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Assign To *</label>
                                        <select name="assigned_to" class="form-control" required>
                                            <option value="">Select Employee</option>
                                            <?php if (empty($employees)): ?>
                                                <option value="" disabled>No active employees found</option>
                                            <?php else: ?>
                                                <?php foreach ($employees as $employee): ?>
                                                    <option value="<?php echo $employee['id']; ?>">
                                                        <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Priority</label>
                                        <select name="priority" class="form-control">
                                            <option value="low">Low</option>
                                            <option value="medium" selected>Medium</option>
                                            <option value="high">High</option>
                                            <option value="urgent">Urgent</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Due Date</label>
                                        <input type="date" name="due_date" class="form-control">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" class="form-control" rows="4"></textarea>
                            </div>
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">Create Task</button>
                            </div>
                        </form>
                    </div>
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
                            <a href="?filter=cancelled" class="btn <?php echo $filter === 'cancelled' ? 'btn-danger' : 'btn-outline-danger'; ?>">Cancelled</a>
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
                    <h2>Tasks List (<?php echo count($tasks); ?> total)</h2>
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
                                        <th>Assigned To</th>
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
                                                <td><?php echo $task['assigned_to_name'] . ' ' . $task['assigned_to_last']; ?></td>
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
                                                        <?php if ($task['status'] !== 'completed' && $task['status'] !== 'cancelled'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                                <input type="hidden" name="status" value="in_progress">
                                                                <button type="submit" class="btn btn-info btn-sm" title="Mark as In Progress">
                                                                    <i class="fa fa-play"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                                <input type="hidden" name="status" value="completed">
                                                                <button type="submit" class="btn btn-success btn-sm" title="Mark as Completed" onclick="return confirm('Mark this task as completed?')">
                                                                    <i class="fa fa-check"></i>
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="update_status">
                                                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                                                <input type="hidden" name="status" value="cancelled">
                                                                <button type="submit" class="btn btn-danger btn-sm" title="Cancel Task" onclick="return confirm('Cancel this task?')">
                                                                    <i class="fa fa-times"></i>
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
