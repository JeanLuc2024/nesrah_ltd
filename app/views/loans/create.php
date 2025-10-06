<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Create New Loan</h5>
    </div>
    <div class="card-body">
        <form method="post" action="/nesrah/public/loans/store">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="loan_number" class="form-label">Loan Number</label>
                        <input type="text" class="form-control" id="loan_number" name="loan_number" 
                               value="<?= $loan['loan_number'] ?>" readonly>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Customer <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['customer_id']) ? 'is-invalid' : '' ?>" 
                                id="customer_id" name="customer_id" required>
                            <option value="">Select Customer</option>
                            <?php foreach ($customers as $customer): ?>
                                <option value="<?= $customer['id'] ?>" 
                                    <?= $loan['customer_id'] == $customer['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?>
                                    (<?= htmlspecialchars($customer['phone']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['customer_id'])): ?>
                            <div class="invalid-feedback"><?= $errors['customer_id'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="loan_product_id" class="form-label">Loan Product <span class="text-danger">*</span></label>
                        <select class="form-select <?= isset($errors['loan_product_id']) ? 'is-invalid' : '' ?>" 
                                id="loan_product_id" name="loan_product_id" required>
                            <option value="">Select Loan Product</option>
                            <?php foreach ($products as $product): ?>
                                <option value="<?= $product['id'] ?>" 
                                    data-interest-rate="<?= $product['interest_rate'] ?>"
                                    data-min-amount="<?= $product['min_amount'] ?>"
                                    data-max-amount="<?= $product['max_amount'] ?>"
                                    data-min-term="<?= $product['term_min'] ?>"
                                    data-max-term="<?= $product['term_max'] ?>"
                                    <?= $loan['loan_product_id'] == $product['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($product['name']) ?> 
                                    (<?= $product['interest_rate'] ?>% <?= $product['interest_type'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors['loan_product_id'])): ?>
                            <div class="invalid-feedback"><?= $errors['loan_product_id'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Loan Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="text" class="form-control <?= isset($errors['amount']) ? 'is-invalid' : '' ?>" 
                                   id="amount" name="amount" value="<?= $loan['amount'] ?>" 
                                   oninput="formatCurrency(this)" required>
                            <?php if (isset($errors['amount'])): ?>
                                <div class="invalid-feedback"><?= $errors['amount'] ?></div>
                            <?php endif; ?>
                        </div>
                        <small class="text-muted" id="amount_range"></small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="term_months" class="form-label">Loan Term (Months) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control <?= isset($errors['term_months']) ? 'is-invalid' : '' ?>" 
                               id="term_months" name="term_months" value="<?= $loan['term_months'] ?>" 
                               min="1" required>
                        <?php if (isset($errors['term_months'])): ?>
                            <div class="invalid-feedback"><?= $errors['term_months'] ?></div>
                        <?php endif; ?>
                        <small class="text-muted" id="term_range"></small>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="disbursement_date" class="form-label">Disbursement Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control <?= isset($errors['disbursement_date']) ? 'is-invalid' : '' ?>" 
                               id="disbursement_date" name="disbursement_date" 
                               value="<?= $loan['disbursement_date'] ?>" required>
                        <?php if (isset($errors['disbursement_date'])): ?>
                            <div class="invalid-feedback"><?= $errors['disbursement_date'] ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-12">
                    <div class="mb-3">
                        <label for="purpose" class="form-label">Purpose of Loan</label>
                        <textarea class="form-control" id="purpose" name="purpose" rows="2"><?= htmlspecialchars($loan['purpose']) ?></textarea>
                    </div>
                </div>
                
                <!-- Loan Summary -->
                <div class="col-12">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h6 class="mb-0">Loan Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Interest Rate</small>
                                        <span id="interest_rate_display">-</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Monthly Payment</small>
                                        <span id="monthly_payment">-</span>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-2">
                                        <small class="text-muted d-block">Total Payable</small>
                                        <span id="total_payable">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-12 mt-4">
                    <div class="d-flex justify-content-between">
                        <a href="/nesrah/public/loans" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to List
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-1"></i> Create Loan
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const loanProductSelect = document.getElementById('loan_product_id');
    const amountInput = document.getElementById('amount');
    const termInput = document.getElementById('term_months');
    const interestRateDisplay = document.getElementById('interest_rate_display');
    const monthlyPaymentDisplay = document.getElementById('monthly_payment');
    const totalPayableDisplay = document.getElementById('total_payable');
    const amountRange = document.getElementById('amount_range');
    const termRange = document.getElementById('term_range');
    
    // Format currency input
    function formatCurrency(input) {
        // Remove non-numeric characters
        let value = input.value.replace(/[^0-9.]/g, '');
        
        // Format with commas
        if (value) {
            const parts = value.split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            input.value = parts.length > 1 ? parts[0] + '.' + parts[1] : parts[0];
        }
        
        // Trigger calculation
        calculateLoan();
    }
    
    // Calculate loan details
    function calculateLoan() {
        const selectedOption = loanProductSelect.options[loanProductSelect.selectedIndex];
        
        if (!selectedOption.value) {
            resetLoanSummary();
            return;
        }
        
        const interestRate = parseFloat(selectedOption.getAttribute('data-interest-rate'));
        const minAmount = parseFloat(selectedOption.getAttribute('data-min-amount'));
        const maxAmount = parseFloat(selectedOption.getAttribute('data-max-amount'));
        const minTerm = parseInt(selectedOption.getAttribute('data-min-term'));
        const maxTerm = parseInt(selectedOption.getAttribute('data-max-term'));
        
        // Update amount and term ranges
        amountRange.textContent = `Range: $${minAmount.toLocaleString()} - $${maxAmount.toLocaleString()}`;
        termRange.textContent = `Range: ${minTerm} - ${maxTerm} months`;
        
        // Get loan amount (remove commas for calculation)
        const amount = parseFloat(amountInput.value.replace(/,/g, '')) || 0;
        const term = parseInt(termInput.value) || 0;
        
        // Update interest rate display
        interestRateDisplay.textContent = interestRate + '%';
        
        if (amount > 0 && term > 0) {
            // Calculate monthly payment (simple interest for now)
            const monthlyRate = interestRate / 100 / 12;
            const monthlyPayment = (amount * monthlyRate * Math.pow(1 + monthlyRate, term)) / (Math.pow(1 + monthlyRate, term) - 1);
            const totalPayable = monthlyPayment * term;
            
            // Update displays
            monthlyPaymentDisplay.textContent = '$' + monthlyPayment.toFixed(2);
            totalPayableDisplay.textContent = '$' + totalPayable.toFixed(2) + ' (Total Interest: $' + (totalPayable - amount).toFixed(2) + ')';
        } else {
            monthlyPaymentDisplay.textContent = '-';
            totalPayableDisplay.textContent = '-';
        }
    }
    
    function resetLoanSummary() {
        interestRateDisplay.textContent = '-';
        monthlyPaymentDisplay.textContent = '-';
        totalPayableDisplay.textContent = '-';
        amountRange.textContent = '';
        termRange.textContent = '';
    }
    
    // Add event listeners
    loanProductSelect.addEventListener('change', calculateLoan);
    amountInput.addEventListener('input', () => calculateLoan());
    termInput.addEventListener('input', () => calculateLoan());
    
    // Initial calculation
    calculateLoan();
});
</script>
