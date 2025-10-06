<?php
// Set page title
$page_title = 'Payment Management';

// Include the template
ob_start(); // Start output buffering
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Payment Records</h5>
        <div class="d-flex">
            <div class="input-group me-3" style="width: 300px;">
                <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
                <button class="btn btn-outline-secondary" type="button">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
            <a href="receive-payment.php" class="btn btn-primary">
                <i class="fas fa-money-bill-wave me-2"></i> Receive Payment
            </a>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Payment Date</th>
                        <th>Customer</th>
                        <th>Loan ID</th>
                        <th>Amount</th>
                        <th>Payment Method</th>
                        <th>Received By</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1</td>
                        <td><?= date('Y-m-d') ?></td>
                        <td>John Doe</td>
                        <td>L-1001</td>
                        <td>$500.00</td>
                        <td>Bank Transfer</td>
                        <td>Admin</td>
                        <td><span class="badge bg-success">Completed</span></td>
                        <td>
                            <div class="btn-group">
                                <a href="view-payment.php?id=1" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-receipt"></i> Receipt
                                </a>
                                <button class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-times"></i> Void
                                </button>
                            </div>
                        </td>
                    </tr>
                    <!-- More rows will be populated from the database -->
                </tbody>
            </table>
        </div>
        
        <!-- Summary Cards -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Today's Collection</h6>
                        <h3 class="mb-0">$2,450.00</h3>
                        <small>Updated just now</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">This Month</h6>
                        <h3 class="mb-0">$12,890.00</h3>
                        <small>25% more than last month</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Pending Payments</h6>
                        <h3 class="mb-0">$3,750.00</h3>
                        <small>12 payments overdue</small>
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
