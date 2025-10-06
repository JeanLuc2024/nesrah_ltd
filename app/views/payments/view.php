<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Payment Details</h5>
                <div>
                    <a href="/nesrah/public/payments/<?= $payment['id'] ?>/receipt" target="_blank" class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-print me-1"></i> Print Receipt
                    </a>
                    <a href="/nesrah/public/payments" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Back to Payments
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <span class="text-muted d-block">Payment ID</span>
                            <h5 class="mb-0">#<?= $payment['id'] ?></h5>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted d-block">Payment Date</span>
                            <h5 class="mb-0"><?= date('F j, Y', strtotime($payment['payment_date'])) ?></h5>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted d-block">Payment Method</span>
                            <h5 class="mb-0">
                                <span class="badge bg-<?= getPaymentMethodBadgeClass($payment['payment_method']) ?>">
                                    <?= ucfirst($payment['payment_method']) ?>
                                </span>
                            </h5>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <span class="text-muted d-block">Amount</span>
                            <h3 class="text-primary mb-0">$<?= number_format($payment['amount'], 2) ?></h3>
                        </div>
                        <?php if ($payment['transaction_reference']): ?>
                            <div class="mb-3">
                                <span class="text-muted d-block">Reference</span>
                                <h5 class="mb-0"><?= htmlspecialchars($payment['transaction_reference']) ?></h5>
                            </div>
                        <?php endif; ?>
                        <div class="mb-3">
                            <span class="text-muted d-block">Received By</span>
                            <h5 class="mb-0"><?= htmlspecialchars($payment['received_by_name'] ?? 'System') ?></h5>
                        </div>
                    </div>
                </div>

                <div class="border-top pt-3">
                    <h6 class="mb-3">Related Loan Information</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="text-muted">Loan Number:</span>
                                <span class="ms-2">
                                    <a href="/nesrah/public/loans/<?= $loan['id'] ?>">
                                        <?= htmlspecialchars($loan['loan_number']) ?>
                                    </a>
                                </span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted">Customer:</span>
                                <span class="ms-2">
                                    <a href="/nesrah/public/customers/<?= $customer['id'] ?>">
                                        <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                                    </a>
                                </span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted">Phone:</span>
                                <span class="ms-2"><?= htmlspecialchars($customer['phone']) ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-2">
                                <span class="text-muted">Loan Amount:</span>
                                <span class="ms-2">$<?= number_format($loan['amount'], 2) ?></span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted">Total Paid:</span>
                                <span class="ms-2">$<?= number_format($loan['total_paid'] ?? 0, 2) ?></span>
                            </div>
                            <div class="mb-2">
                                <span class="text-muted">Remaining Balance:</span>
                                <span class="ms-2 fw-bold">$<?= number_format(($loan['total_payable'] ?? 0) - ($loan['total_paid'] ?? 0), 2) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($payment['notes'])): ?>
                    <div class="border-top mt-4 pt-3">
                        <h6>Notes</h6>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($payment['notes'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small">Created: <?= date('M j, Y g:i A', strtotime($payment['created_at'])) ?></span>
                        <?php if ($payment['created_at'] != $payment['updated_at']): ?>
                            <span class="text-muted small ms-3">Updated: <?= date('M j, Y g:i A', strtotime($payment['updated_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePaymentModal">
                            <i class="fas fa-trash-alt me-1"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Recent Payments for this Loan -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Recent Payments for This Loan</h6>
            </div>
            <div class="card-body p-0">
                <?php if (count($recentPayments) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentPayments as $recent): ?>
                            <a href="/nesrah/public/payments/<?= $recent['id'] ?>" 
                               class="list-group-item list-group-item-action <?= $recent['id'] == $payment['id'] ? 'active' : '' ?>">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">$<?= number_format($recent['amount'], 2) ?></h6>
                                    <small><?= date('M j, Y', strtotime($recent['payment_date'])) ?></small>
                                </div>
                                <div class="d-flex w-100 justify-content-between">
                                    <small class="text-<?= $recent['id'] == $payment['id'] ? 'white' : 'muted' ?>">
                                        <?= ucfirst($recent['payment_method']) ?>
                                        <?= $recent['transaction_reference'] ? ' (' . htmlspecialchars($recent['transaction_reference']) . ')' : '' ?>
                                    </small>
                                    <small>
                                        <?php if ($recent['id'] == $payment['id']): ?>
                                            <span class="badge bg-light text-dark">Current</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-money-bill-wave fa-2x text-muted mb-2"></i>
                        <p class="mb-0 text-muted">No other payments found</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (count($recentPayments) > 5): ?>
                <div class="card-footer text-center">
                    <a href="/nesrah/public/loans/<?= $loan['id'] ?>#payments" class="btn btn-sm btn-link">
                        View All Payments
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Payment Actions -->
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Payment Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/nesrah/public/payments/<?= $payment['id'] ?>/receipt?download=pdf" class="btn btn-outline-primary mb-2">
                        <i class="fas fa-file-pdf me-2"></i> Download Receipt (PDF)
                    </a>
                    <a href="/nesrah/public/payments/<?= $payment['id'] ?>/receipt/print" target="_blank" class="btn btn-outline-secondary mb-2">
                        <i class="fas fa-print me-2"></i> Print Receipt
                    </a>
                    <a href="/nesrah/public/payments/<?= $payment['id'] ?>/email" class="btn btn-outline-info mb-2">
                        <i class="fas fa-envelope me-2"></i> Email Receipt to Customer
                    </a>
                    <button type="button" class="btn btn-outline-warning mb-2" data-bs-toggle="modal" data-bs-target="#editPaymentModal">
                        <i class="fas fa-edit me-2"></i> Edit Payment
                    </button>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deletePaymentModal">
                        <i class="fas fa-trash-alt me-2"></i> Delete Payment
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Payment Modal -->
<div class="modal fade" id="editPaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/nesrah/public/payments/<?= $payment['id'] ?>" method="post">
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_amount" class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control" id="edit_amount" name="amount" 
                                   value="<?= number_format($payment['amount'], 2) ?>" oninput="formatCurrency(this)" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_payment_date" class="form-label">Payment Date</label>
                        <input type="date" class="form-control" id="edit_payment_date" name="payment_date" 
                               value="<?= date('Y-m-d', strtotime($payment['payment_date'])) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_payment_method" class="form-label">Payment Method</label>
                        <select class="form-select" id="edit_payment_method" name="payment_method" required>
                            <option value="cash" <?= $payment['payment_method'] === 'cash' ? 'selected' : '' ?>>Cash</option>
                            <option value="bank_transfer" <?= $payment['payment_method'] === 'bank_transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                            <option value="check" <?= $payment['payment_method'] === 'check' ? 'selected' : '' ?>>Check</option>
                            <option value="mobile_money" <?= $payment['payment_method'] === 'mobile_money' ? 'selected' : '' ?>>Mobile Money</option>
                            <option value="other" <?= $payment['payment_method'] === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_transaction_reference" class="form-label">Reference/Check #</label>
                        <input type="text" class="form-control" id="edit_transaction_reference" name="transaction_reference" 
                               value="<?= htmlspecialchars($payment['transaction_reference'] ?? '') ?>">
                    </div>
                    <div class="mb-0">
                        <label for="edit_notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="edit_notes" name="notes" rows="3"><?= htmlspecialchars($payment['notes'] ?? '') ?></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Payment Modal -->
<div class="modal fade" id="deletePaymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="/nesrah/public/payments/<?= $payment['id'] ?>" method="post">
                <input type="hidden" name="_method" value="DELETE">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Delete Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this payment? This action cannot be undone.</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> Deleting this payment will update the loan balance.
                    </div>
                    <div class="mb-3">
                        <label for="delete_reason" class="form-label">Reason for Deletion</label>
                        <textarea class="form-control" id="delete_reason" name="reason" rows="2" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Payment</button>
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

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
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
