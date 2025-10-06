<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Loans</h2>
    <a href="/nesrah/public/loans/create" class="btn btn-primary">
        <i class="fas fa-plus me-1"></i> New Loan
    </a>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-0">Total Loans</h6>
                        <h3 class="mt-2 mb-0"><?= number_format($stats['total_loans']) ?></h3>
                    </div>
                    <div class="icon-shape">
                        <i class="fas fa-hand-holding-usd fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-0">Active Loans</h6>
                        <h3 class="mt-2 mb-0"><?= number_format($stats['active_loans']) ?></h3>
                    </div>
                    <div class="icon-shape">
                        <i class="fas fa-chart-line fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-0">Total Disbursed</h6>
                        <h3 class="mt-2 mb-0"><?= number_format($stats['total_disbursed'], 2) ?></h3>
                    </div>
                    <div class="icon-shape">
                        <i class="fas fa-money-bill-wave fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-0">Total Outstanding</h6>
                        <h3 class="mt-2 mb-0"><?= number_format($stats['total_outstanding'], 2) ?></h3>
                    </div>
                    <div class="icon-shape">
                        <i class="fas fa-file-invoice-dollar fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-4">
                <label for="search" class="form-label">Search</label>
                <input type="text" class="form-control" id="search" name="search" 
                       value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or loan number">
            </div>
            <div class="col-md-4">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="disbursed" <?= $status === 'disbursed' ? 'selected' : '' ?>>Disbursed</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    <option value="closed" <?= $status === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-search me-1"></i> Filter
                </button>
                <a href="/nesrah/public/loans" class="btn btn-outline-secondary">
                    <i class="fas fa-sync-alt"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Loans Table -->
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Loan #</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th class="text-end">Amount</th>
                        <th class="text-center">Term</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($loans) > 0): ?>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td><?= htmlspecialchars($loan['loan_number']) ?></td>
                                <td>
                                    <a href="/nesrah/public/customers/<?= $loan['customer_id'] ?>">
                                        <?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($loan['product_name']) ?></td>
                                <td class="text-end"><?= number_format($loan['amount'], 2) ?></td>
                                <td class="text-center"><?= $loan['term_months'] ?> months</td>
                                <td class="text-center">
                                    <span class="badge bg-<?= $this->getStatusBadgeClass($loan['status']) ?>">
                                        <?= ucfirst($loan['status']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group">
                                        <a href="/nesrah/public/loans/<?= $loan['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if ($loan['status'] === 'pending'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    data-bs-toggle="modal" data-bs-target="#approveLoanModal" 
                                                    data-id="<?= $loan['id'] ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    data-bs-toggle="modal" data-bs-target="#rejectLoanModal" 
                                                    data-id="<?= $loan['id'] ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($loan['status'] === 'approved'): ?>
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    data-bs-toggle="modal" data-bs-target="#disburseLoanModal" 
                                                    data-id="<?= $loan['id'] ?>">
                                                <i class="fas fa-hand-holding-usd"></i> Disburse
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">No loans found</div>
                                <a href="/nesrah/public/loans/create" class="btn btn-sm btn-primary mt-2">
                                    <i class="fas fa-plus me-1"></i> Create New Loan
                                </a>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($pagination['total'] > 1): ?>
            <nav class="p-3 border-top">
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($pagination['current'] > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=1<?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $pagination['current'] - 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pagination['total']; $i++): ?>
                        <?php if ($i == 1 || $i == $pagination['total'] || ($i >= $pagination['current'] - 2 && $i <= $pagination['current'] + 2)): ?>
                            <li class="page-item <?= $i == $pagination['current'] ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php elseif ($i == $pagination['current'] - 3 || $i == $pagination['current'] + 3): ?>
                            <li class="page-item disabled">
                                <span class="page-link">...</span>
                            </li>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['current'] < $pagination['total']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $pagination['current'] + 1 ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $pagination['total'] ?><?= $status ? '&status=' . urlencode($status) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Approve Loan Modal -->
<div class="modal fade" id="approveLoanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/nesrah/public/loans/approve" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Loan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this loan?</p>
                    <input type="hidden" name="id" id="approve_loan_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Approve</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Loan Modal -->
<div class="modal fade" id="rejectLoanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/nesrah/public/loans/reject" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Loan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Reason for rejection</label>
                        <textarea class="form-control" id="reject_reason" name="reason" rows="3" required></textarea>
                    </div>
                    <input type="hidden" name="id" id="reject_loan_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Reject</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Disburse Loan Modal -->
<div class="modal fade" id="disburseLoanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/nesrah/public/loans/disburse" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Disburse Loan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to disburse this loan? This action cannot be undone.</p>
                    <input type="hidden" name="id" id="disburse_loan_id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Disburse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Handle modals
document.addEventListener('DOMContentLoaded', function() {
    // Approve modal
    var approveModal = document.getElementById('approveLoanModal');
    if (approveModal) {
        approveModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var loanId = button.getAttribute('data-id');
            document.getElementById('approve_loan_id').value = loanId;
        });
    }
    
    // Reject modal
    var rejectModal = document.getElementById('rejectLoanModal');
    if (rejectModal) {
        rejectModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var loanId = button.getAttribute('data-id');
            document.getElementById('reject_loan_id').value = loanId;
        });
    }
    
    // Disburse modal
    var disburseModal = document.getElementById('disburseLoanModal');
    if (disburseModal) {
        disburseModal.addEventListener('show.bs.modal', function (event) {
            var button = event.relatedTarget;
            var loanId = button.getAttribute('data-id');
            document.getElementById('disburse_loan_id').value = loanId;
        });
    }
});
</script>

<?php 
// Helper function to get badge class based on status
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'approved':
            return 'info';
        case 'disbursed':
            return 'primary';
        case 'rejected':
            return 'danger';
        case 'closed':
            return 'success';
        default:
            return 'secondary';
    }
}
?>
