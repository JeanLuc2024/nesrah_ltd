<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('dashboard.php');
}

$success_message = '';
$error_message = '';
$validation_errors = [];
$employee_id = intval($_GET['id'] ?? 0);

// Get employee data
if ($employee_id > 0) {
    $query = "SELECT * FROM users WHERE id = :employee_id AND role = 'employee'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':employee_id', $employee_id);
    $stmt->execute();
    $employee = $stmt->fetch();

    if (!$employee) {
        redirect('employees.php');
    }
} else {
    redirect('employees.php');
}

// Handle form submission
if ($_POST) {
    $action = $_POST['action'] ?? '';

    if ($action === 'edit') {
        // Get and sanitize form data
        $first_name = sanitizeInput($_POST['first_name'] ?? '');
        $last_name = sanitizeInput($_POST['last_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $username = sanitizeInput($_POST['username'] ?? '');
        $status = $_POST['status'] ?? 'active';
        $password = $_POST['password'] ?? '';

        // Validation
        if (empty($first_name)) {
            $validation_errors[] = 'First name is required.';
        } elseif (strlen($first_name) < 2) {
            $validation_errors[] = 'First name must be at least 2 characters long.';
        }

        if (empty($last_name)) {
            $validation_errors[] = 'Last name is required.';
        } elseif (strlen($last_name) < 2) {
            $validation_errors[] = 'Last name must be at least 2 characters long.';
        }

        if (empty($email)) {
            $validation_errors[] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $validation_errors[] = 'Please enter a valid email address.';
        }

        if (empty($username)) {
            $validation_errors[] = 'Username is required.';
        } elseif (strlen($username) < 3) {
            $validation_errors[] = 'Username must be at least 3 characters long.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $validation_errors[] = 'Username can only contain letters, numbers, and underscores.';
        }

        if (!empty($password) && strlen($password) < 6) {
            $validation_errors[] = 'Password must be at least 6 characters long if provided.';
        }

        if (!empty($phone) && !preg_match('/^[\d\s\-\+\(\)]+$/', $phone)) {
            $validation_errors[] = 'Please enter a valid phone number.';
        }

        if (!in_array($status, ['active', 'inactive', 'pending'])) {
            $validation_errors[] = 'Invalid status selected.';
        }

        // If no validation errors, proceed with database operations
        if (empty($validation_errors)) {
            try {
                // Check if username or email already exists (excluding current user)
                $query = "SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :employee_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':email', $email);
                $stmt->bindParam(':employee_id', $employee_id);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $existing_user = $stmt->fetch();
                    if ($existing_user['username'] === $username) {
                        $validation_errors[] = 'Username already exists. Please choose a different username.';
                    }
                    if ($existing_user['email'] === $email) {
                        $validation_errors[] = 'Email already exists. Please use a different email address.';
                    }
                } else {
                    // Update employee
                    if (!empty($password)) {
                        // Update with password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email,
                                 phone = :phone, address = :address, username = :username, password = :password, status = :status
                                 WHERE id = :employee_id AND role = 'employee'";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':password', $hashed_password);
                    } else {
                        // Update without password
                        $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email,
                                 phone = :phone, address = :address, username = :username, status = :status
                                 WHERE id = :employee_id AND role = 'employee'";
                        $stmt = $db->prepare($query);
                    }

                    $stmt->bindParam(':first_name', $first_name);
                    $stmt->bindParam(':last_name', $last_name);
                    $stmt->bindParam(':email', $email);
                    $stmt->bindParam(':phone', $phone);
                    $stmt->bindParam(':address', $address);
                    $stmt->bindParam(':username', $username);
                    $stmt->bindParam(':status', $status);
                    $stmt->bindParam(':employee_id', $employee_id);

                    if ($stmt->execute() && $stmt->rowCount() > 0) {
                        $success_message = 'Employee "' . $first_name . ' ' . $last_name . '" has been updated successfully!';
                        // Refresh employee data
                        $query = "SELECT * FROM users WHERE id = :employee_id AND role = 'employee'";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':employee_id', $employee_id);
                        $stmt->execute();
                        $employee = $stmt->fetch();
                    } else {
                        $error_message = 'No changes were made or employee not found.';
                    }
                }
            } catch (Exception $e) {
                $error_message = 'System error: Unable to update employee. Please contact administrator.';
                error_log("Employee update error: " . $e->getMessage());
            }
        }

        // Combine validation errors into error message
        if (!empty($validation_errors)) {
            $error_message = implode('<br>', $validation_errors);
        }
    }
}
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Edit Employee</h2>
        </div>
    </div>
</div>

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

<!-- Back Button -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>
                        <a href="employees.php" class="btn btn-secondary btn-sm">
                            <i class="fa fa-arrow-left"></i> Back to Employees
                        </a>
                    </h2>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Employee Form -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Edit Employee: <?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <form method="POST" id="editEmployeeForm" novalidate>
                            <input type="hidden" name="action" value="edit">

                            <!-- Personal Information Section -->
                            <div class="form-section mb-4">
                                <h6 class="section-title text-primary mb-3">
                                    <i class="fa fa-user me-2"></i>Personal Information
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="first_name" class="form-label">
                                                First Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="first_name" id="first_name"
                                                   class="form-control" required minlength="2" maxlength="50"
                                                   placeholder="Enter first name" value="<?php echo $employee['first_name']; ?>">
                                            <div class="invalid-feedback">
                                                Please provide a valid first name (2-50 characters).
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="last_name" class="form-label">
                                                Last Name <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="last_name" id="last_name"
                                                   class="form-control" required minlength="2" maxlength="50"
                                                   placeholder="Enter last name" value="<?php echo $employee['last_name']; ?>">
                                            <div class="invalid-feedback">
                                                Please provide a valid last name (2-50 characters).
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contact Information Section -->
                            <div class="form-section mb-4">
                                <h6 class="section-title text-primary mb-3">
                                    <i class="fa fa-envelope me-2"></i>Contact Information
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="email" class="form-label">
                                                Email Address <span class="text-danger">*</span>
                                            </label>
                                            <input type="email" name="email" id="email"
                                                   class="form-control" required maxlength="100"
                                                   placeholder="Enter email address" value="<?php echo $employee['email']; ?>">
                                            <div class="invalid-feedback">
                                                Please provide a valid email address.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="phone" class="form-label">Phone Number</label>
                                            <input type="tel" name="phone" id="phone"
                                                   class="form-control" maxlength="20"
                                                   placeholder="Enter phone number" value="<?php echo $employee['phone']; ?>">
                                            <div class="invalid-feedback">
                                                Please provide a valid phone number.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group mb-3">
                                            <label for="address" class="form-label">Address</label>
                                            <textarea name="address" id="address"
                                                      class="form-control" rows="3" maxlength="255"
                                                      placeholder="Enter full address (optional)"><?php echo $employee['address']; ?></textarea>
                                            <small class="form-text text-muted">Optional field</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Account Information Section -->
                            <div class="form-section mb-4">
                                <h6 class="section-title text-primary mb-3">
                                    <i class="fa fa-key me-2"></i>Account Information
                                </h6>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="username" class="form-label">
                                                Username <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" name="username" id="username"
                                                   class="form-control" required minlength="3" maxlength="30"
                                                   pattern="[a-zA-Z0-9_]+"
                                                   placeholder="Enter username" value="<?php echo $employee['username']; ?>">
                                            <div class="invalid-feedback">
                                                Username must be 3-30 characters (letters, numbers, underscore only).
                                            </div>
                                            <small class="form-text text-muted">Only letters, numbers, and underscores allowed</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group mb-3">
                                            <label for="status" class="form-label">
                                                Status <span class="text-danger">*</span>
                                            </label>
                                            <select name="status" id="status" class="form-control" required>
                                                <option value="active" <?php echo $employee['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                                <option value="inactive" <?php echo $employee['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                <option value="pending" <?php echo $employee['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                            </select>
                                            <div class="invalid-feedback">
                                                Please select a valid status.
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="form-group mb-3">
                                            <label for="password" class="form-label">
                                                New Password (leave blank to keep current)
                                            </label>
                                            <input type="password" name="password" id="password"
                                                   class="form-control" minlength="6" maxlength="100"
                                                   placeholder="Leave blank to keep current password">
                                            <div class="invalid-feedback">
                                                Password must be at least 6 characters long if provided.
                                            </div>
                                            <small class="form-text text-muted">Leave blank to keep current password</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary" id="editEmployeeBtn">
                                    <i class="fa fa-save me-2"></i>Update Employee
                                </button>
                                <a href="employees.php" class="btn btn-secondary ms-2">
                                    <i class="fa fa-times me-2"></i>Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
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
</style>

<script>
// Form validation
document.getElementById('editEmployeeForm').addEventListener('submit', function(e) {
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

    // Validate email
    const emailField = form.querySelector('#email');
    if (emailField.value && !emailField.checkValidity()) {
        emailField.classList.add('is-invalid');
        isValid = false;
    }

    // Validate username pattern
    const usernameField = form.querySelector('#username');
    if (usernameField.value && !/^[a-zA-Z0-9_]+$/.test(usernameField.value)) {
        usernameField.classList.add('is-invalid');
        isValid = false;
    }

    // Validate password if provided
    const passwordField = form.querySelector('#password');
    if (passwordField.value && passwordField.value.length < 6) {
        passwordField.classList.add('is-invalid');
        isValid = false;
    }

    if (!isValid) {
        e.preventDefault();
        alert('Please correct the errors in the form.');
        return false;
    }

    // Show loading state
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i>Updating Employee...';
    submitBtn.disabled = true;
});

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
