<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Edit Loan #<?= htmlspecialchars($loan['loan_number']) ?></h5>
            </div>
            <div class="card-body">
                <form action="/nesrah/public/loans/<?= $loan['id'] ?>" method="post">
                    <input type="hidden" name="_method" value="PUT">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                                <select class="form-select" id="customer_id" name="customer_id" required 
                                        data-control="select2" data-placeholder="Select customer">
                                    <option value="">Select customer</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?= $customer['id'] ?>" 
                                            <?= $customer['id'] == $loan['customer_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] . ' (' . $customer['phone'] . ')') ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['customer_id'])): ?>
                                    <div class="invalid-feedback d-block"><?= $errors['customer_id'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="loan_product_id" class="form-label">Loan Product <span class="text-danger">*</span></label>
                                <select class="form-select" id="loan_product_id" name="loan_product_id" required 
                                        onchange="updateLoanProductDetails()">
                                    <option value="">Select loan product</option>
                                    <?php foreach ($loanProducts as $product): ?>
                                        <option value="<?= $product['id'] ?>" 
                                            data-interest-rate="<?= $product['interest_rate'] ?>"
                                            data-interest-type="<?= $product['interest_type'] ?>"
                                            data-min-amount="<?= $product['min_amount'] ?>"
                                            data-max-amount="<?= $product['max_amount'] ?>"
                                            data-min-term="<?= $product['min_term_months'] ?>"
                                            data-max-term="<?= $product['max_term_months'] ?>"
                                            data-payment-frequency="<?= $product['payment_frequency'] ?>"
                                            data-penalty-rate="<?= $product['penalty_rate'] ?>"
                                            <?= $product['id'] == $loan['loan_product_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($product['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['loan_product_id'])): ?>
                                    <div class="invalid-feedback d-block"><?= $errors['loan_product_id'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="amount" class="form-label">Loan Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control" id="amount" name="amount" 
                                           value="<?= number_format($loan['amount'], 2) ?>" 
                                           oninput="formatCurrency(this); calculateLoanSummary()" required>
                                </div>
                                <small class="text-muted" id="amount_range"></small>
                                <?php if (isset($errors['amount'])): ?>
                                    <div class="invalid-feedback d-block"><?= $errors['amount'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="term_months" class="form-label">Loan Term (Months) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="term_months" name="term_months" 
                                       min="1" step="1" value="<?= $loan['term_months'] ?>" 
                                       onchange="calculateLoanSummary()" required>
                                <small class="text-muted" id="term_range"></small>
                                <?php if (isset($errors['term_months'])): ?>
                                    <div class="invalid-feedback d-block"><?= $errors['term_months'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="application_date" class="form-label">Application Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="application_date" name="application_date" 
                                       value="<?= date('Y-m-d', strtotime($loan['application_date'])) ?>" required>
                                <?php if (isset($errors['application_date'])): ?>
                                    <div class="invalid-feedback d-block"><?= $errors['application_date'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="first_payment_date" class="form-label">First Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="first_payment_date" name="first_payment_date" 
                                       value="<?= date('Y-m-d', strtotime($loan['first_payment_date'])) ?>" required>
                                <?php if (isset($errors['first_payment_date'])): ?>
                                    <div class="invalid-feedback d-block"><?= $errors['first_payment_date'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="purpose" class="form-label">Purpose of Loan</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="2"><?= htmlspecialchars($loan['purpose']) ?></textarea>
                        <?php if (isset($errors['purpose'])): ?>
                            <div class="invalid-feedback d-block"><?= $errors['purpose'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"><?= htmlspecialchars($loan['notes'] ?? '') ?></textarea>
                        <?php if (isset($errors['notes'])): ?>
                            <div class="invalid-feedback d-block"><?= $errors['notes'] ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="d-flex justify-content-between mt-4">
                        <a href="/nesrah/public/loans/<?= $loan['id'] ?>" class="btn btn-light">
                            <i class="fas fa-arrow-left me-1"></i> Back to Loan
                        </a>
                        <div>
                            <a href="/nesrah/public/loans/<?= $loan['id'] ?>" class="btn btn-secondary me-2">
                                <i class="fas fa-times me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Save Changes
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <!-- Loan Summary Card -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Loan Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Principal:</span>
                    <span id="summary_principal">$<?= number_format($loan['amount'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Interest Rate:</span>
                    <span id="summary_interest_rate"><?= $loan['interest_rate'] ?>% (<?= ucfirst($loan['interest_type']) ?>)</span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-muted">Loan Term:</span>
                    <span id="summary_term"><?= $loan['term_months'] ?> months</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-bold">Monthly Payment:</span>
                    <span class="fw-bold" id="summary_monthly_payment">$<?= number_format($loan['monthly_payment'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Total Interest:</span>
                    <span id="summary_total_interest">$<?= number_format($loan['total_interest'], 2) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Total Payable:</span>
                    <span class="fw-bold" id="summary_total_payable">$<?= number_format($loan['total_payable'], 2) ?></span>
                </div>
                <hr>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-muted">Application Date:</span>
                    <span><?= date('M j, Y', strtotime($loan['application_date'])) ?></span>
                </div>
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted">First Payment:</span>
                    <span><?= date('M j, Y', strtotime($loan['first_payment_date'])) ?></span>
                </div>
            </div>
        </div>
        
        <!-- Status Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Loan Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="pending" <?= $loan['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $loan['status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $loan['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        <option value="disbursed" <?= $loan['status'] === 'disbursed' ? 'selected' : '' ?>>Disbursed</option>
                        <option value="closed" <?= $loan['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
                    </select>
                </div>
                <?php if ($loan['disbursement_date']): ?>
                    <div class="mb-3">
                        <label class="form-label">Disbursement Date</label>
                        <p class="form-control-static"><?= date('M j, Y', strtotime($loan['disbursement_date'])) ?></p>
                    </div>
                <?php endif; ?>
                <?php if ($loan['maturity_date']): ?>
                    <div class="mb-3">
                        <label class="form-label">Maturity Date</label>
                        <p class="form-control-static"><?= date('M j, Y', strtotime($loan['maturity_date'])) ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for customer dropdown
    $('select[data-control="select2"]').select2({
        theme: 'bootstrap-5',
        width: '100%'
    });
    
    // Update loan product details on page load
    updateLoanProductDetails();
    
    // Calculate loan summary on page load
    calculateLoanSummary();
});

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

// Get numeric value from formatted currency
function getNumericValue(currencyString) {
    if (!currencyString) return 0;
    return parseFloat(currencyString.replace(/[^0-9.-]+/g, '')) || 0;
}

// Update loan product details when product selection changes
function updateLoanProductDetails() {
    const productSelect = document.getElementById('loan_product_id');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        // Update amount range hint
        const minAmount = parseFloat(selectedOption.getAttribute('data-min-amount')) || 0;
        const maxAmount = parseFloat(selectedOption.getAttribute('data-max-amount')) || 0;
        document.getElementById('amount_range').textContent = `Range: $${minAmount.toLocaleString()} - $${maxAmount.toLocaleString()}`;
        
        // Update term range hint
        const minTerm = selectedOption.getAttribute('data-min-term') || 1;
        const maxTerm = selectedOption.getAttribute('data-max-term') || 120;
        document.getElementById('term_range').textContent = `Range: ${minTerm} - ${maxTerm} months`;
        
        // Update term input min/max
        const termInput = document.getElementById('term_months');
        termInput.min = minTerm;
        termInput.max = maxTerm;
        
        // If current term is outside new range, adjust it
        if (parseInt(termInput.value) < minTerm) termInput.value = minTerm;
        if (parseInt(termInput.value) > maxTerm) termInput.value = maxTerm;
        
        // Recalculate loan summary
        calculateLoanSummary();
    } else {
        document.getElementById('amount_range').textContent = '';
        document.getElementById('term_range').textContent = '';
    }
}

// Calculate loan summary
function calculateLoanSummary() {
    const amount = getNumericValue(document.getElementById('amount').value);
    const termMonths = parseInt(document.getElementById('term_months').value) || 1;
    
    // Get loan product details
    const productSelect = document.getElementById('loan_product_id');
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    
    if (selectedOption && selectedOption.value) {
        const interestRate = parseFloat(selectedOption.getAttribute('data-interest-rate')) || 0;
        const interestType = selectedOption.getAttribute('data-interest-type') || 'flat';
        
        // Calculate loan details based on interest type
        let monthlyInterest, totalInterest, monthlyPayment, totalPayable;
        
        if (interestType === 'flat') {
            // Flat interest calculation
            totalInterest = (amount * interestRate * termMonths) / (12 * 100);
            totalPayable = amount + totalInterest;
            monthlyPayment = totalPayable / termMonths;
        } else {
            // Reducing balance interest calculation
            const monthlyRate = interestRate / 12 / 100;
            monthlyPayment = (amount * monthlyRate * Math.pow(1 + monthlyRate, termMonths)) / (Math.pow(1 + monthlyRate, termMonths) - 1);
            totalPayable = monthlyPayment * termMonths;
            totalInterest = totalPayable - amount;
        }
        
        // Update summary
        document.getElementById('summary_principal').textContent = `$${amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('summary_interest_rate').textContent = `${interestRate}% (${interestType === 'flat' ? 'Flat' : 'Reducing'})`;
        document.getElementById('summary_term').textContent = `${termMonths} months`;
        document.getElementById('summary_monthly_payment').textContent = `$${monthlyPayment.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('summary_total_interest').textContent = `$${totalInterest.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('summary_total_payable').textContent = `$${totalPayable.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }
}
</script>
