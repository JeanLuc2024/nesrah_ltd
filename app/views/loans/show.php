<div class="row">
    <div class="col-lg-8">
        <!-- Loan Details Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Loan Details</h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" 
                            id="loanActionsDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="loanActionsDropdown">
                        <li>
                            <a class="dropdown-item" href="/nesrah/public/loans/<?= $loan['id'] ?>/edit">
                                <i class="fas fa-edit me-2"></i> Edit
                            </a>
                        </li>
                        <?php if ($loan['status'] === 'pending'): ?>
                            <li>
                                <button class="dropdown-item text-success" data-bs-toggle="modal" 
                                        data-bs-target="#approveLoanModal">
                                    <i class="fas fa-check me-2"></i> Approve
                                </button>
                            </li>
                            <li>
                                <button class="dropdown-item text-danger" data-bs-toggle="modal" 
                                        data-bs-target="#rejectLoanModal">
                                    <i class="fas fa-times me-2"></i> Reject
                                </button>
                            </li>
                        <?php endif; ?>
                        <?php if ($loan['status'] === 'approved'): ?>
                            <li>
                                <button class="dropdown-item text-primary" data-bs-toggle="modal" 
                                        data-bs-target="#disburseLoanModal">
                                    <i class="fas fa-hand-holding-usd me-2"></i> Disburse
                                </button>
                            </li>
                        <?php endif; ?>
                        <?php if ($loan['status'] === 'disbursed'): ?>
                            <li>
                                <button class="dropdown-item text-info" data-bs-toggle="modal" 
                                        data-bs-target="#recordPaymentModal">
                                    <i class="fas fa-money-bill-wave me-2"></i> Record Payment
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item text-success" data-bs-toggle="modal" 
                                        data-bs-target="#closeLoanModal">
                                    <i class="fas fa-flag-checkered me-2"></i> Close Loan
                                </button>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="bg-light rounded p-3">
                                    <i class="fas fa-hand-holding-usd fa-2x text-primary"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0"><?= htmlspecialchars($loan['loan_number']) ?></h6>
                                <span class="text-muted">Loan Number</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <div class="bg-light rounded p-3">
                                    <i class="fas fa-user fa-2x text-info"></i>
                                </div>
                            </div>
                            <div class="flex-grow-1 ms-3">
                                <h6 class="mb-0">
                                    <a href="/nesrah/public/customers/<?= $loan['customer_id'] ?>">
                                        <?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) ?>
                                    </a>
                                </h6>
                                <span class="text-muted">Customer</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th class="text-muted">Loan Product:</th>
                                <td><?= htmlspecialchars($loan['product_name']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Amount:</th>
                                <td>$<?= number_format($loan['amount'], 2) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Interest Rate:</th>
                                <td><?= $loan['interest_rate'] ?>% (<?= ucfirst($loan['interest_type']) ?>)</td>
                            </tr>
                            <tr>
                                <th class="text-muted">Term:</th>
                                <td><?= $loan['term_months'] ?> months</td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-sm">
                            <tr>
                                <th class="text-muted">Status:</th>
                                <td>
                                    <span class="badge bg-<?= getStatusBadgeClass($loan['status']) ?>">
                                        <?= ucfirst($loan['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Disbursement Date:</th>
                                <td><?= $loan['disbursement_date'] ? date('M j, Y', strtotime($loan['disbursement_date'])) : '-' ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">First Payment:</th>
                                <td><?= $loan['first_payment_date'] ? date('M j, Y', strtotime($loan['first_payment_date'])) : '-' ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Maturity Date:</th>
                                <td><?= $loan['maturity_date'] ? date('M j, Y', strtotime($loan['maturity_date'])) : '-' ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($loan['purpose'])): ?>
                    <div class="mt-3">
                        <h6>Purpose of Loan</h6>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($loan['purpose'])) ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($loan['notes'])): ?>
                    <div class="mt-3">
                        <h6>Notes</h6>
                        <p class="mb-0 text-muted"><?= nl2br(htmlspecialchars($loan['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Payment Schedule -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Payment Schedule</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Due Date</th>
                                <th class="text-end">Principal</th>
                                <th class="text-end">Interest</th>
                                <th class="text-end">Total Due</th>
                                <th class="text-end">Paid</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($schedule) > 0): ?>
                                <?php foreach ($schedule as $index => $payment): ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= date('M j, Y', strtotime($payment['due_date'])) ?></td>
                                        <td class="text-end">$<?= number_format($payment['principal_amount'], 2) ?></td>
                                        <td class="text-end">$<?= number_format($payment['interest_amount'], 2) ?></td>
                                        <td class="text-end fw-bold">$<?= number_format($payment['amount_due'], 2) ?></td>
                                        <td class="text-end">
                                            <?php if ($payment['paid_amount'] > 0): ?>
                                                $<?= number_format($payment['paid_amount'], 2) ?>
                                                <?php if ($payment['paid_date']): ?>
                                                    <br><small class="text-muted"><?= date('M j, Y', strtotime($payment['paid_date'])) ?></small>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-<?= getPaymentStatusBadgeClass($payment['status']) ?>">
                                                <?= ucfirst($payment['status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No payment schedule available.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Loan Summary -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Loan Summary</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tr>
                        <th>Principal:</th>
                        <td class="text-end">$<?= number_format($loan['amount'], 2) ?></td>
                    </tr>
                    <tr>
                        <th>Total Interest:</th>
                        <td class="text-end">$<?= number_format($loan['total_interest'], 2) ?></td>
                    </tr>
                    <tr class="table-light">
                        <th>Total Payable:</th>
                        <th class="text-end">$<?= number_format($loan['total_payable'], 2) ?></th>
                    </tr>
                    <tr>
                        <th>Paid Amount:</th>
                        <td class="text-end">$<?= number_format($loan['paid_amount'] ?? 0, 2) ?></td>
                    </tr>
                    <tr>
                        <th>Outstanding:</th>
                        <td class="text-end fw-bold">$<?= number_format(($loan['total_payable'] - ($loan['paid_amount'] ?? 0)), 2) ?></td>
                    </tr>
                    <tr class="table-light">
                        <th>Monthly Payment:</th>
                        <th class="text-end">$<?= number_format($loan['monthly_payment'], 2) ?></th>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Recent Payments -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Payments</h5>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" 
                        data-bs-target="#recordPaymentModal">
                    <i class="fas fa-plus me-1"></i> New
                </button>
            </div>
            <div class="card-body p-0">
                <?php if (count($payments) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($payments as $payment): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-0">$<?= number_format($payment['amount'], 2) ?></h6>
                                        <small class="text-muted">
                                            <?= date('M j, Y', strtotime($payment['payment_date'])) ?>
                                            <?php if ($payment['transaction_reference']): ?>
                                                &middot; <?= htmlspecialchars($payment['transaction_reference']) ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-light text-dark">
                                            <?= ucfirst($payment['payment_method']) ?>
                                        </span>
                                        <?php if ($payment['received_by_name']): ?>
                                            <div class="text-muted small">
                                                <?= htmlspecialchars($payment['received_by_name']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if (!empty($payment['notes'])): ?>
                                    <div class="mt-2 small text-muted">
                                        <?= nl2br(htmlspecialchars($payment['notes'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($payments) >= 5): ?>
                        <div class="card-footer text-center">
                            <a href="/nesrah/public/loans/<?= $loan['id'] ?>/payments" class="btn btn-sm btn-link">
                                View All Payments
                            </a>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-money-bill-wave fa-2x mb-2"></i>
                        <p class="mb-0">No payments recorded yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Record Payment Modal -->
<div class="modal fade" id="recordPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/nesrah/public/loans/<?= $loan['id'] ?>/record-payment" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="payment_amount" class="form-label">Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control" id="payment_amount" name="amount" 
                                   oninput="formatCurrency(this)" required>
                        </div>
                        <div class="form-text">Outstanding: $<?= number_format(($loan['total_payable'] - ($loan['paid_amount'] ?? 0)), 2) ?></div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="check">Check</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="transaction_reference" class="form-label">Reference/Check #</label>
                        <input type="text" class="form-control" id="transaction_reference" name="transaction_reference">
                    </div>
                    
                    <div class="mb-0">
                        <label for="payment_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="payment_notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Approve Loan Modal -->
<div class="modal fade" id="approveLoanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/nesrah/public/loans/<?= $loan['id'] ?>/approve" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Approve Loan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to approve this loan?</p>
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
            <form action="/nesrah/public/loans/<?= $loan['id'] ?>/reject" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Loan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="reject_reason" class="form-label">Reason for Rejection <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="reject_reason" name="reason" rows="3" required></textarea>
                    </div>
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
            <form action="/nesrah/public/loans/<?= $loan['id'] ?>/disburse" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Disburse Loan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to disburse this loan? This action cannot be undone.</p>
                    <div class="mb-3">
                        <label for="disbursement_date" class="form-label">Disbursement Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="disbursement_date" name="disbursement_date" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Disburse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Close Loan Modal -->
<div class="modal fade" id="closeLoanModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/nesrah/public/loans/<?= $loan['id'] ?>/close" method="post">
                <div class="modal-header">
                    <h5 class="modal-title">Close Loan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to close this loan? This action cannot be undone.</p>
                    <div class="mb-3">
                        <label for="close_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="close_notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Close Loan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Format currency input
function formatCurrency(input) {
    // Remove non-numeric characters except decimal point
    let value = input.value.replace(/[^0-9.]/g, '');
    
    // Format with commas
    if (value) {
        const parts = value.split('.');
        parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        input.value = parts.length > 1 ? parts[0] + '.' + parts[1] : parts[0];
    }
}
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

// Helper function to get badge class based on payment status
function getPaymentStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'paid':
            return 'success';
        case 'overdue':
            return 'danger';
        case 'partial':
            return 'info';
        case 'advance':
            return 'primary';
        default:
            return 'secondary';
    }
}
?>
