<?php
// Set page title
$page_title = 'User Management';

// Check if user has admin privileges
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('HTTP/1.0 403 Forbidden');
    echo 'Access Denied: Admin privileges required';
    exit();
}

// Include the template
ob_start(); // Start output buffering
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">System Users</h5>
        <a href="add-user.php" class="btn btn-primary">
            <i class="fas fa-user-plus me-2"></i> Add User
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3">
                                    <span class="avatar-initial bg-primary text-white rounded-circle">A</span>
                                </div>
                                <div>
                                    <h6 class="mb-0">Admin User</h6>
                                    <small class="text-muted">System Administrator</small>
                                </div>
                            </div>
                        </td>
                        <td>admin</td>
                        <td>admin@example.com</td>
                        <td><span class="badge bg-primary">Administrator</span></td>
                        <td><?= date('Y-m-d H:i:s') ?></td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td>
                            <div class="btn-group">
                                <a href="edit-user.php?id=1" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger" disabled>
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-info" title="Reset Password">
                                    <i class="fas fa-key"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>2</td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar me-3">
                                    <span class="avatar-initial bg-info text-white rounded-circle">L</span>
                                </div>
                                <div>
                                    <h6 class="mb-0">Loan Officer</h6>
                                    <small class="text-muted">Loan Department</small>
                                </div>
                            </div>
                        </td>
                        <td>loanofficer</td>
                        <td>loanofficer@example.com</td>
                        <td><span class="badge bg-info">Loan Officer</span></td>
                        <td>2025-01-15 14:30:00</td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td>
                            <div class="btn-group">
                                <a href="edit-user.php?id=2" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-info" title="Reset Password">
                                    <i class="fas fa-key"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- More rows will be populated from the database -->
                </tbody>
            </table>
        </div>
        
        <!-- User Roles Summary -->
        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Total Users</h6>
                        <h3 class="mb-0">8</h3>
                        <small>Active system users</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Active Now</h6>
                        <h3 class="mb-0">3</h3>
                        <small>Currently online</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Pending</h6>
                        <h3 class="mb-0">2</h3>
                        <small>Awaiting approval</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white mb-3">
                    <div class="card-body">
                        <h6 class="card-title">Inactive</h6>
                        <h3 class="mb-0">1</h3>
                        <small>Disabled accounts</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-end">
                <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item">
                    <a class="page-link" href="#">Next</a>
                </li>
            </ul>
        </nav>
    </div>
</div>

<?php
// Get the buffered content and assign to $page_content
$page_content = ob_get_clean();

// Include the template
require_once 'includes/template.php';
?>
