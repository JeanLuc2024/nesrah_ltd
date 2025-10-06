<?php
// Set page title
$page_title = 'Customer Management';

// Include the template
ob_start(); // Start output buffering
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Customers</h5>
        <div class="d-flex">
            <div class="input-group me-3" style="width: 300px;">
                <input type="text" class="form-control" placeholder="Search customers...">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            <a href="add-customer.php" class="btn btn-primary">
                <i class="fas fa-user-plus me-2"></i> Add Customer
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Active Loans</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>John Doe</td>
                        <td>john@example.com</td>
                        <td>+1 234 567 890</td>
                        <td>123 Main St, City</td>
                        <td>2</td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td>
                            <div class="btn-group">
                                <a href="view-customer.php?id=1" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit-customer.php?id=1" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- More rows will be populated from the database -->
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-end">
                <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1" aria-disabled="true">Previous</a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
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
