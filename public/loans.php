<?php
// Set page title
$page_title = 'Loan Management';

// Include the template
ob_start(); // Start output buffering
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">All Loans</h5>
        <a href="add-loan.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i> New Loan
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Borrower</th>
                        <th>Loan Amount</th>
                        <th>Interest Rate</th>
                        <th>Term</th>
                        <th>Status</th>
                        <th>Due Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td>John Doe</td>
                        <td>$5,000.00</td>
                        <td>12%</td>
                        <td>12 months</td>
                        <td><span class="badge bg-success">Active</span></td>
                        <td>2025-11-15</td>
                        <td>
                            <div class="btn-group">
                                <a href="view-loan.php?id=1" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="edit-loan.php?id=1" class="btn btn-sm btn-outline-secondary">
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
                <li class="page-item"><a class="page-link" href="#">3</a></li>
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
