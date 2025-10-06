<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Payment Transactions</h1>
    <a href="/nesrah/public/payments/create" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> Record Payment
    </a>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Payments</h6>
                        <h2 class="mb-0"><?= number_format($stats['total_payments']) ?></h2>
                    </div>
                    <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="#payments-table">View Details</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Total Amount</h6>
                        <h2 class="mb-0">$<?= number_format($stats['total_amount'], 2) ?></h2>
                    </div>
                    <i class="fas fa-dollar-sign fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="#payments-table">View Details</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Cash Payments</h6>
                        <h2 class="mb-0">$<?= number_format($stats['cash_amount'], 2) ?></h2>
                    </div>
                    <i class="fas fa-money-bill fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="?payment_method=cash">View Details</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase">Bank Transfers</h6>
                        <h2 class="mb-0">$<?= number_format($stats['bank_transfer_amount'], 2) ?></h2>
                    </div>
                    <i class="fas fa-university fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer d-flex align-items-center justify-content-between">
                <a class="small text-white stretched-link" href="?payment_method=bank_transfer">View Details</a>
                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-filter me-1"></i>
        Filter Payments
    </div>
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label for="loan_number" class="form-label">Loan Number</label>
                <input type="text" class="form-control" id="loan_number" name="loan_number" 
                       value="<?= htmlspecialchars($filters['loan_number']) ?>">
            </div>
            <div class="col-md-3">
                <label for="customer_name" class="form-label">Customer Name</label>
                <input type="text" class="form-control" id="customer_name" name="customer_name" 
                       value="<?= htmlspecialchars($filters['customer_name']) ?>">
            </div>
            <div class="col-md-2">
                <label for="payment_method" class="form-label">Payment Method</label>
                <select class="form-select" id="payment_method" name="payment_method">
                    <option value="">All Methods</option>
                    <option value="cash" <?= $filters['payment_method'] === 'cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="bank_transfer" <?= $filters['payment_method'] === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                    <option value="check" <?= $filters['payment_method'] === 'check' ? 'selected' : '' ?>>Check</option>
                    <option value="mobile_money" <?= $filters['payment_method'] === 'mobile_money' ? 'selected' : '' ?>>Mobile Money</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from" class="form-label">From Date</label>
                <input type="date" class="form-control" id="date_from" name="date_from" 
                       value="<?= htmlspecialchars($filters['date_from']) ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to" class="form-label">To Date</label>
                <input type="date" class="form-control" id="date_to" name="date_to" 
                       value="<?= htmlspecialchars($filters['date_to']) ?>">
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search me-1"></i> Search
                </button>
                <a href="/nesrah/public/payments" class="btn btn-secondary">
                    <i class="fas fa-undo me-1"></i> Reset
                </a>
                <div class="btn-group float-end">
                    <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-download me-1"></i> Export
                    </button>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="#" onclick="exportToCSV()">CSV</a></li>
                        <li><a class="dropdown-item" href="#" onclick="exportToPDF()">PDF</a></li>
                        <li><a class="dropdown-item" href="#" onclick="printPayments()">Print</a></li>
                    </ul>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Payments Table -->
<div class="card" id="payments-table">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <i class="fas fa-table me-1"></i>
            Payment Transactions
        </div>
        <div class="text-muted small">
            Showing <?= count($payments) ?> of <?= number_format($totalPayments) ?> records
        </div>
    </div>
    <div class="card-body">
        <?php if (count($payments) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Payment Date</th>
                            <th>Loan Number</th>
                            <th>Customer</th>
                            <th class="text-end">Amount</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Received By</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $index => $payment): ?>
                            <tr>
                                <td><?= $index + 1 + (($currentPage - 1) * 15) ?></td>
                                <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                <td>
                                    <a href="/nesrah/public/loans/<?= $payment['loan_id'] ?>">
                                        <?= htmlspecialchars($payment['loan_number']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']) ?></td>
                                <td class="text-end">$<?= number_format($payment['amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= getPaymentMethodBadgeClass($payment['payment_method']) ?>">
                                        <?= ucfirst($payment['payment_method']) ?>
                                    </span>
                                </td>
                                <td><?= $payment['transaction_reference'] ? htmlspecialchars($payment['transaction_reference']) : '-' ?></td>
                                <td><?= $payment['received_by_name'] ?? 'System' ?></td>
                                <td class="text-center">
                                    <div class="btn-group">
                                        <a href="/nesrah/public/payments/<?= $payment['id'] ?>" class="btn btn-sm btn-outline-primary" 
                                           data-bs-toggle="tooltip" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="/nesrah/public/payments/<?= $payment['id'] ?>/receipt" target="_blank" 
                                           class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Print Receipt">
                                            <i class="fas fa-receipt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="4" class="text-end fw-bold">Total:</td>
                            <td class="text-end fw-bold">$<?= number_format(array_sum(array_column($payments, 'amount')), 2) ?></td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-4">
                        <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $currentPage - 1 ?><?= $queryString ?>">Previous</a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i == 1 || $i == $totalPages || ($i >= $currentPage - 2 && $i <= $currentPage + 2)): ?>
                                <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= $queryString ?>"><?= $i ?></a>
                                </li>
                            <?php elseif ($i == $currentPage - 3 || $i == $currentPage + 3): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= $queryString ?>">Next</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
            
        <?php else: ?>
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="fas fa-money-bill-wave fa-4x text-muted"></i>
                </div>
                <h4>No payments found</h4>
                <p class="text-muted">There are no payment records matching your criteria.</p>
                <a href="/nesrah/public/payments/create" class="btn btn-primary mt-3">
                    <i class="fas fa-plus me-1"></i> Record New Payment
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Set default dates in filter
    const today = new Date().toISOString().split('T')[0];
    const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().split('T')[0];
    
    if (!document.getElementById('date_from').value) {
        document.getElementById('date_from').value = firstDay;
    }
    
    if (!document.getElementById('date_to').value) {
        document.getElementById('date_to').value = today;
    }
});

// Export functions
function exportToCSV() {
    // Build query string from current filters
    const queryParams = new URLSearchParams(window.location.search);
    queryParams.set('export', 'csv');
    window.location.href = '/nesrah/public/payments/export?' + queryParams.toString();
}

function exportToPDF() {
    // Build query string from current filters
    const queryParams = new URLSearchParams(window.location.search);
    queryParams.set('export', 'pdf');
    window.location.href = '/nesrah/public/payments/export?' + queryParams.toString();
}

function printPayments() {
    window.open('/nesrah/public/payments/print?' + window.location.search, '_blank');
}
</script>

<?php
// Helper function to get badge class for payment method
function getPaymentMethodBadgeClass($method) {
    switch ($method) {
        case 'cash':
            return 'success';
        case 'bank_transfer':
            return 'primary';
        case 'check':
            return 'info';
        case 'mobile_money':
            return 'warning';
        default:
            return 'secondary';
    }
}
?>
