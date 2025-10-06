<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('dashboard.php');
}

$success_message = '';
$error_message = '';
$validation_errors = [];

// Employee CRUD operations (delete, approve, reject, activate, deactivate)
if ($_POST) {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
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

<!-- Enhanced Alert Messages -->
<?php if ($success_message): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" id="successAlert">
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
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert" id="errorAlert">
                <i class="fa fa-exclamation-triangle me-2"></i>
                <strong>Error!</strong> <?php echo $error_message; ?>
                <button type="button" class="close" onclick="closeAlert('errorAlert')" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
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
                        <a href="employee_add.php" class="btn btn-success btn-block">
                            <i class="fa fa-plus"></i> Add New Employee
                        </a>
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
                                                        <a href="employee_edit.php?id=<?php echo $employee['id']; ?>" class="btn btn-primary btn-sm">
                                                            <i class="fa fa-edit"></i> Edit
                                                        </a>
                                                        
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


<!-- Enhanced Edit Employee Modal -->

<style>
/* Ensure modal displays properly */
.modal {
    z-index: 1050 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    overflow: hidden !important;
    outline: 0 !important;
}
.modal-backdrop {
    z-index: 1040 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
}
.modal.show {
    display: block !important;
}
.modal-backdrop.show {
    opacity: 0.5 !important;
}
.modal-dialog {
    position: relative !important;
    width: auto !important;
    margin: 1.75rem auto !important;
    pointer-events: none !important;
}
.modal-content {
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    width: 100% !important;
    pointer-events: auto !important;
    background-color: #fff !important;
    background-clip: padding-box !important;
    border: 1px solid rgba(0, 0, 0, 0.2) !important;
    border-radius: 0.3rem !important;
    outline: 0 !important;
}
.modal-open {
    overflow: hidden !important;
}

/* Anti-extension interference styles */
.modal, .modal * {
    -webkit-user-select: text !important;
    -moz-user-select: text !important;
    -ms-user-select: text !important;
    user-select: text !important;
}

.modal input, .modal textarea, .modal select {
    pointer-events: auto !important;
    -webkit-user-select: text !important;
    -moz-user-select: text !important;
    -ms-user-select: text !important;
    user-select: text !important;
}

/* Prevent extension overlays on modal forms */
.modal-content {
    isolation: isolate !important;
    contain: layout style !important;
}

/* Enhanced form styling */
.modal form input:not([type="hidden"]),
.modal form textarea,
.modal form select {
    background-color: white !important;
    border: 1px solid #ced4da !important;
    color: #495057 !important;
    pointer-events: auto !important;
    z-index: 1060 !important;
    position: relative !important;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out !important;
}

.modal form input:focus,
.modal form textarea:focus,
.modal form select:focus {
    outline: 0 !important;
    border-color: #80bdff !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25) !important;
}

/* Form section styling */
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

/* Enhanced form validation styles */
.form-control.is-valid {
    border-color: #28a745 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='8' height='8' viewBox='0 0 8 8'%3e%3cpath fill='%2328a745' d='m2.3 6.73.94-.94 1.88 1.88 3.75-3.75.94.94-4.69 4.69z'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right calc(0.375em + 0.1875rem) center !important;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
}

.form-control.is-invalid {
    border-color: #dc3545 !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' stroke='%23dc3545' viewBox='0 0 12 12'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 1.4 1.4M7.2 4.6l-1.4 1.4'/%3e%3c/svg%3e") !important;
    background-repeat: no-repeat !important;
    background-position: right calc(0.375em + 0.1875rem) center !important;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem) !important;
}

.valid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #28a745;
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
}

/* Enhanced alert styling */
.alert {
    border: none !important;
    border-radius: 8px !important;
    padding: 1rem 1.25rem !important;
    margin-bottom: 1.5rem !important;
}

.alert-success {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%) !important;
    color: #155724 !important;
    border-left: 4px solid #28a745 !important;
}

.alert-danger {
    background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%) !important;
    color: #721c24 !important;
    border-left: 4px solid #dc3545 !important;
}

.alert .fa {
    margin-right: 8px;
    font-size: 1.1rem;
}

/* Modal enhancements */
.modal-header.bg-success,
.modal-header.bg-primary {
    border-bottom: none !important;
}

.modal-footer.bg-light {
    border-top: 1px solid #e9ecef !important;
    background-color: #f8f9fa !important;
}

/* Button enhancements */
.btn {
    border-radius: 6px !important;
    font-weight: 500 !important;
    padding: 0.5rem 1rem !important;
    transition: all 0.15s ease-in-out !important;
}

.btn:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1) !important;
}

/* Loading state for buttons */
.btn-loading {
    position: relative;
    pointer-events: none;
    opacity: 0.7;
}

.btn-loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid transparent;
    border-top-color: currentColor;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    }

    /* Ensure modals are always visible when shown */
    .modal.show {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    /* Fix modal backdrop visibility */
    .modal-backdrop.show {
        opacity: 0.5 !important;
        visibility: visible !important;
    }

    .modal-dialog {
        position: relative !important;
        width: auto !important;
        margin: 1.75rem auto !important;
        pointer-events: none !important;
    }

    .modal-content {
        position: relative !important;
        display: flex !important;
        flex-direction: column !important;
        width: 100% !important;
        pointer-events: auto !important;
        background-color: #fff !important;
        background-clip: padding-box !important;
        border: 1px solid rgba(0, 0, 0, 0.2) !important;
        border-radius: 0.3rem !important;
        outline: 0 !important;
    }

    .modal-open {
        overflow: hidden !important;
    }

    /* Anti-extension interference styles */
    .modal, .modal * {
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
        user-select: text !important;
    }

    .modal input, .modal textarea, .modal select {
        pointer-events: auto !important;
        -webkit-user-select: text !important;
        -moz-user-select: text !important;
        -ms-user-select: text !important;
        user-select: text !important;
    }

    /* Prevent extension overlays on modal forms */
    .modal-content {
        isolation: isolate !important;
        contain: layout style !important;
    }
</style>

<script>
    // Simplified and accessible modal system
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Initializing accessible modal system...');

        // Simple modal opening function
        window.openAddEmployeeModal = function() {
            console.log('Opening Add Employee modal...');

            // Get modal element
            var modal = document.getElementById('addEmployeeModal');
            if (!modal) {
                console.error('Add Employee modal not found');
                return false;
            }

            // Clear form
            var form = modal.querySelector('form');
            if (form) {
                form.reset();
                // Clear validation states
                var fields = form.querySelectorAll('.is-valid, .is-invalid');
                fields.forEach(field => field.classList.remove('is-valid', 'is-invalid'));
            }

            // Close any existing modals first
            closeAllModals();

            // Show modal using Bootstrap if available
            if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
                try {
                    $('#addEmployeeModal').modal({
                        backdrop: 'static',
                        keyboard: true,
                        focus: true
                    });
                    console.log('Bootstrap Add modal opened successfully');
                } catch (e) {
                    console.error('Bootstrap failed, using vanilla JS:', e);
                    showModalVanilla('addEmployeeModal');
                }
            } else {
                showModalVanilla('addEmployeeModal');
            }

            return false;
        };

        // Simple modal editing function
        window.editEmployee = function(id, firstName, lastName, email, phone, username, address, status) {
            console.log('Editing employee:', {id, firstName, lastName, email, phone, username, address, status});

            // Set form values
            try {
                document.getElementById('edit_user_id').value = id;
                document.getElementById('edit_first_name').value = firstName || '';
                document.getElementById('edit_last_name').value = lastName || '';
                document.getElementById('edit_email').value = email || '';
                document.getElementById('edit_phone').value = phone || '';
                document.getElementById('edit_username').value = username || '';
                document.getElementById('edit_address').value = address || '';
                document.getElementById('edit_status').value = status || 'active';

                console.log('Form values set successfully');
            } catch (e) {
                console.error('Error setting form values:', e);
                return false;
            }

            // Get modal element
            var modal = document.getElementById('editEmployeeModal');
            if (!modal) {
                console.error('Edit modal not found');
                return false;
            }

            // Close any existing modals first
            closeAllModals();

            // Show modal using Bootstrap if available
            if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
                try {
                    $('#editEmployeeModal').modal({
                        backdrop: 'static',
                        keyboard: true,
                        focus: true
                    });
                    console.log('Bootstrap Edit modal opened successfully');
                } catch (e) {
                    console.error('Bootstrap failed, using vanilla JS:', e);
                    showModalVanilla('editEmployeeModal');
                }
            } else {
                showModalVanilla('editEmployeeModal');
            }

            return false;
        };

        // Simple modal closing function
        window.closeAllModals = function() {
            // Try Bootstrap first
            if (typeof $ !== 'undefined' && $.fn && $.fn.modal) {
                try {
                    $('.modal').modal('hide');
                } catch (e) {
                    console.error('Bootstrap close failed:', e);
                }
            }

            // Vanilla JavaScript cleanup
            var modals = document.querySelectorAll('.modal.show');
            modals.forEach(function(modal) {
                modal.classList.remove('show');
                modal.style.display = 'none';
                // Remove aria-hidden to ensure accessibility
                modal.removeAttribute('aria-hidden');
            });

            var backdrops = document.querySelectorAll('.modal-backdrop');
            backdrops.forEach(function(backdrop) {
                backdrop.remove();
            });

            document.body.classList.remove('modal-open');
            document.body.style.paddingRight = '';
        };

        // Simple close functions
        window.closeEditModal = function() {
            closeAllModals();
        };

        window.closeAddModal = function() {
            closeAllModals();
        };

        // Handle close buttons with proper accessibility
        document.addEventListener('click', function(e) {
            if (e.target && (e.target.classList.contains('close') || e.target.closest('.close'))) {
                e.preventDefault();
                closeAllModals();
            }
        });

        // Handle escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                var visibleModals = document.querySelectorAll('.modal.show');
                if (visibleModals.length > 0) {
                    closeAllModals();
                }
            }
        });

        // Setup form validation
        var forms = ['addEmployeeForm', 'editEmployeeForm'];
        forms.forEach(function(formId) {
            var form = document.getElementById(formId);
            if (!form) return;

            form.addEventListener('submit', function(e) {
                var isValid = true;
                var fields = form.querySelectorAll('input[required], select[required]');

                fields.forEach(function(field) {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        field.classList.remove('is-invalid');
                        field.classList.add('is-valid');
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill in all required fields.');
                    return false;
                }
            });
        });

        console.log('Accessible modal system initialized successfully');
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
