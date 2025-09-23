<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('dashboard.php');
}

$success_message = '';
$error_message = '';

// Handle employee actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        // Add new employee
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        
        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($email) || empty($username) || empty($password)) {
            $error_message = 'All required fields must be filled.';
        } else {
            // Check if username or email already exists
            $query = "SELECT id FROM users WHERE username = :username OR email = :email";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->fetch()) {
                $error_message = 'Username or email already exists.';
            } else {
                // Insert new employee
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $query = "INSERT INTO users (first_name, last_name, email, phone, address, username, password, role, status) 
                         VALUES (:first_name, :last_name, :email, :phone, :address, :username, :password, 'employee', 'active')";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':first_name', $first_name);
                $stmt->bindParam(':last_name', $last_name);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':address', $address);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $hashed_password);
                
                if ($stmt->execute()) {
                    $success_message = 'Employee added successfully.';
                } else {
                    $error_message = 'Failed to add employee.';
                }
            }
        }
    } elseif ($action === 'edit') {
        // Edit employee
        $user_id = $_POST['user_id'];
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        $username = sanitizeInput($_POST['username']);
        $status = $_POST['status'];
        
        // Check if username or email already exists (excluding current user)
        $query = "SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            $error_message = 'Username or email already exists.';
        } else {
            // Update employee
            $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, 
                     phone = :phone, address = :address, username = :username, status = :status WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':user_id', $user_id);
            
            if ($stmt->execute()) {
                $success_message = 'Employee updated successfully.';
            } else {
                $error_message = 'Failed to update employee.';
            }
        }
    } elseif ($action === 'delete') {
        // Delete employee
        $user_id = $_POST['user_id'];
        
        $query = "DELETE FROM users WHERE id = :user_id AND role = 'employee'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $success_message = 'Employee deleted successfully.';
        } else {
            $error_message = 'Failed to delete employee.';
        }
    } else {
        // Handle other actions (approve, reject, activate, deactivate)
        $user_id = $_POST['user_id'];
        
        if ($action === 'approve') {
            $query = "UPDATE users SET status = 'active' WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            if ($stmt->execute()) {
                $success_message = 'Employee approved successfully.';
            } else {
                $error_message = 'Failed to approve employee.';
            }
        } elseif ($action === 'reject') {
            $query = "UPDATE users SET status = 'inactive' WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            if ($stmt->execute()) {
                $success_message = 'Employee rejected successfully.';
            } else {
                $error_message = 'Failed to reject employee.';
            }
        } elseif ($action === 'activate') {
            $query = "UPDATE users SET status = 'active' WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            if ($stmt->execute()) {
                $success_message = 'Employee activated successfully.';
            } else {
                $error_message = 'Failed to activate employee.';
            }
        } elseif ($action === 'deactivate') {
            $query = "UPDATE users SET status = 'inactive' WHERE id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            if ($stmt->execute()) {
                $success_message = 'Employee deactivated successfully.';
            } else {
                $error_message = 'Failed to deactivate employee.';
            }
        }
    }
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Build query based on filter
$where_clause = "WHERE role = 'employee'";
if ($filter === 'pending') {
    $where_clause .= " AND status = 'pending'";
} elseif ($filter === 'active') {
    $where_clause .= " AND status = 'active'";
} elseif ($filter === 'inactive') {
    $where_clause .= " AND status = 'inactive'";
}

$query = "SELECT * FROM users $where_clause ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$employees = $stmt->fetchAll();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Employee Management</h2>
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

<!-- Filter and Add Employee Section -->
<div class="row">
    <div class="col-md-8">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Filter Employees</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-group" role="group">
                            <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All</a>
                            <a href="?filter=pending" class="btn <?php echo $filter === 'pending' ? 'btn-warning' : 'btn-outline-warning'; ?>">Pending</a>
                            <a href="?filter=active" class="btn <?php echo $filter === 'active' ? 'btn-success' : 'btn-outline-success'; ?>">Active</a>
                            <a href="?filter=inactive" class="btn <?php echo $filter === 'inactive' ? 'btn-danger' : 'btn-outline-danger'; ?>">Inactive</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Actions</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-success btn-block" data-toggle="modal" data-target="#addEmployeeModal">
                            <i class="fa fa-plus"></i> Add New Employee
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Employees Table -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Employees List (<?php echo count($employees); ?> total)</h2>
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
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Joined</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($employees) > 0): ?>
                                        <?php foreach ($employees as $employee): ?>
                                            <tr>
                                                <td><?php echo $employee['id']; ?></td>
                                                <td><?php echo $employee['username']; ?></td>
                                                <td><?php echo $employee['email']; ?></td>
                                                <td><?php echo $employee['phone'] ?: 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $employee['status'] === 'active' ? 'success' : 
                                                            ($employee['status'] === 'pending' ? 'warning' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($employee['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo formatDate($employee['created_at']); ?></td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <!-- Edit Button -->
                                                        <button type="button" class="btn btn-primary btn-sm" onclick="editEmployee('<?php echo $employee['id']; ?>', '<?php echo $employee['first_name']; ?>', '<?php echo $employee['last_name']; ?>', '<?php echo $employee['email']; ?>', '<?php echo $employee['phone']; ?>', '<?php echo $employee['username']; ?>', '<?php echo $employee['address']; ?>', '<?php echo $employee['status']; ?>')">
                                                            <i class="fa fa-edit"></i> Edit
                                                        </button>
                                                        
                                                        <!-- Delete Button -->
                                                        <form method="POST" style="display: inline;">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="user_id" value="<?php echo $employee['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this employee? This action cannot be undone.')">
                                                                <i class="fa fa-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                        
                                                        <!-- Status Action Buttons -->
                                                        <?php if ($employee['status'] === 'pending'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="approve">
                                                                <input type="hidden" name="user_id" value="<?php echo $employee['id']; ?>">
                                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Approve this employee?')">
                                                                    <i class="fa fa-check"></i> Approve
                                                                </button>
                                                            </form>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="reject">
                                                                <input type="hidden" name="user_id" value="<?php echo $employee['id']; ?>">
                                                                <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Reject this employee?')">
                                                                    <i class="fa fa-times"></i> Reject
                                                                </button>
                                                            </form>
                                                        <?php elseif ($employee['status'] === 'active'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="deactivate">
                                                                <input type="hidden" name="user_id" value="<?php echo $employee['id']; ?>">
                                                                <button type="submit" class="btn btn-warning btn-sm" onclick="return confirm('Deactivate this employee?')">
                                                                    <i class="fa fa-pause"></i> Deactivate
                                                                </button>
                                                            </form>
                                                        <?php elseif ($employee['status'] === 'inactive'): ?>
                                                            <form method="POST" style="display: inline;">
                                                                <input type="hidden" name="action" value="activate">
                                                                <input type="hidden" name="user_id" value="<?php echo $employee['id']; ?>">
                                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Activate this employee?')">
                                                                    <i class="fa fa-play"></i> Activate
                                                                </button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No employees found</td>
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

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Employee</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="first_name">First Name *</label>
                                <input type="text" name="first_name" id="first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="last_name">Last Name *</label>
                                <input type="text" name="last_name" id="last_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" name="email" id="email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="text" name="phone" id="phone" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="username">Username *</label>
                                <input type="text" name="username" id="username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" name="password" id="password" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea name="address" id="address" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Add Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Employee Modal -->
<div class="modal fade" id="editEmployeeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Employee</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_first_name">First Name *</label>
                                <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_last_name">Last Name *</label>
                                <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_email">Email *</label>
                                <input type="email" name="email" id="edit_email" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_phone">Phone</label>
                                <input type="text" name="phone" id="edit_phone" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_username">Username *</label>
                                <input type="text" name="username" id="edit_username" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_status">Status</label>
                                <select name="status" id="edit_status" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="pending">Pending</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_password">Password</label>
                                <input type="password" name="password" id="edit_password" class="form-control" placeholder="Leave blank to keep current password">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_address">Address</label>
                                <textarea name="address" id="edit_address" class="form-control" rows="3"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Employee</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Edit employee function
function editEmployee(id, firstName, lastName, email, phone, username, address, status) {
    document.getElementById('edit_user_id').value = id;
    document.getElementById('edit_first_name').value = firstName;
    document.getElementById('edit_last_name').value = lastName;
    document.getElementById('edit_email').value = email;
    document.getElementById('edit_phone').value = phone;
    document.getElementById('edit_username').value = username;
    document.getElementById('edit_address').value = address;
    document.getElementById('edit_status').value = status;
    // Ensure modal opens even if jQuery/Bootstrap is not loaded
    if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
        $('#editEmployeeModal').modal('show');
    } else {
        var modal = document.getElementById('editEmployeeModal');
        if (modal) {
            modal.style.display = 'block';
            modal.classList.add('show');
        }
    }
}

// Cancel button handler for edit modal
$(document).ready(function () {
    $('#editEmployeeModal .btn-secondary[data-dismiss="modal"]').on('click', function () {
        // Hide modal
        if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
            $('#editEmployeeModal').modal('hide');
        } else {
            var modal = document.getElementById('editEmployeeModal');
            if (modal) {
                modal.style.display = 'none';
                modal.classList.remove('show');
            }
        }
        // Reset form fields
        $('#editEmployeeModal form')[0].reset();
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
