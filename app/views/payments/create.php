<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Record New Payment</h5>
            </div>
            <div class="card-body">
                <form action="/nesrah/public/payments" method="post" id="paymentForm">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="loan_id" class="form-label">Select Loan <span class="text-danger">*</span></label>
                                <select class="form-select select2" id="loan_id" name="loan_id" required>
                                    <option value="">Search by loan number or customer name</option>
                                    <?php foreach ($loans as $loan): ?>
                                        <option value="<?= $loan['id'] ?>" 
                                                data-customer="<?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) ?>"
                                                data-phone="<?= htmlspecialchars($loan['phone']) ?>"
                                                data-amount="<?= $loan['amount'] ?>"
                                                data-paid="<?= $loan['total_paid'] ?? 0 ?>"
                                                data-balance="<?= $loan['total_payable'] - ($loan['total_paid'] ?? 0) ?>"
                                                <?= isset($data['loan_id']) && $data['loan_id'] == $loan['id'] ? 'selected' : '' ?>>
                                            #<?= $loan['loan_number'] ?> - <?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) ?> 
                                            (Balance: $<?= number_format($loan['total_payable'] - ($loan['total_paid'] ?? 0), 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['loan_id'])): ?>
                                    <div class="invalid-feedback d-block"><?= $errors['loan_id'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="payment_date" class="form-label">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" 
                                       value="<?= $data['payment_date'] ?? date('Y-m-d') ?>" required>
                                <?php if (isset($errors['payment_date'])): ?>
                                    <div class="invalid-feedback d-block"><?= $errors['payment_date'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Customer and Loan Info -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Customer & Loan Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <span class="text-muted">Customer:</span>
                                        <span id="customerName" class="fw-bold">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted">Phone:</span>
                                        <span id="customerPhone" class="fw-bold">-</span>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-2">
                                        <span class="text-muted">Loan Amount:</span>
                                        <span id="loanAmount" class="fw-bold">-</span>
                                    </div>
                                    <div class="mb-2">
                                        <span class="text-muted">Balance:</span>
                                        <span id="loanBalance" class="fw-bold">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="amount" class="form-label">Amount <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="text" class="form-control" id="amount" name="amount" 
                                           value="<?= $data['amount'] ?? '' ?>" 
                                           oninput="formatCurrency(this); updateChange()" required>
                                </div>
                                <small class="text-muted">Enter payment amount</small>
                                <?php if (isset($errors['amount'])): ?>
                                    <div class="invalid-feedback d-block"><?= $errors['amount'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="payment_method" class="form-label">Payment Method <span class="text-danger">*</span></label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="cash" <?= (isset($data['payment_method']) && $data['payment_method'] === 'cash') ? 'selected' : '' ?>>Cash</option>
                                    <option value="bank_transfer" <?= (isset($data['payment_method']) && $data['payment_method'] === 'bank_transfer') ? 'selected' : '' ?>>Bank Transfer</option>
                                    <option value="check" <?= (isset($data['payment_method']) && $data['payment_method'] === 'check') ? 'selected' : '' ?>>Check</option>
                                    <option value="mobile_money" <?= (isset($data['payment_method']) && $data['payment_method'] === 'mobile_money') ? 'selected' : '' ?>>Mobile Money</option>
                                    <option value="other" <?= (isset($data['payment_method']) && $data['payment_method'] === 'other') ? 'selected' : '' ?>>Other</option>
                                </select>
                                <?php if (isset($errors['payment_method'])): ?>
                                    <div class="invalid-feedback d-block"><?= $errors['payment_method'] ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="transaction_reference" class="form-label">Reference/Check #</label>
                                <input type="text" class="form-control" id="transaction_reference" name="transaction_reference" 
                                       value="<?= $data['transaction_reference'] ?? '' ?>">
                                <small class="text-muted">Optional: Check number, transaction ID, etc.</small>
                            </div>
                        </div>
                        <div class="col-md-6" id="checkFields" style="display: none;">
                            <div class="form-group mb-3">
                                <label for="check_date" class="form-label">Check Date</label>
                                <input type="date" class="form-control" id="check_date" name="check_date" 
                                       value="<?= $data['check_date'] ?? date('Y-m-d') ?>">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"><?= $data['notes'] ?? '' ?></textarea>
                        <small class="text-muted">Any additional notes about this payment</small>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Payment Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Amount Paid:</span>
                                <span id="summaryAmount" class="fw-bold">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Payment Method:</span>
                                <span id="summaryMethod" class="fw-bold">-</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Reference:</span>
                                <span id="summaryReference" class="fw-bold">-</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">New Balance:</span>
                                <span id="newBalance" class="fw-bold">$0.00</span>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/nesrah/public/payments" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Payments
                        </a>
                        <div>
                            <button type="button" class="btn btn-light me-2" onclick="window.print()">
                                <i class="fas fa-print me-1"></i> Print
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Record Payment
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Print Template (Hidden until printed) -->
<div id="receiptTemplate" class="d-none">
    <div class="receipt p-4" style="max-width: 400px; margin: 0 auto;">
        <div class="text-center mb-4">
            <h4 class="mb-1"><?= htmlspecialchars(get_setting('company_name', 'Loan Management System')) ?></h4>
            <p class="mb-1" style="font-size: 0.9rem;"><?= htmlspecialchars(get_setting('company_address', '')) ?></p>
            <p class="mb-1" style="font-size: 0.9rem;"><?= htmlspecialchars(get_setting('company_phone', '')) ?></p>
            <h5 class="mt-3 mb-0">PAYMENT RECEIPT</h5>
            <p class="mb-0" style="font-size: 0.9rem;">Date: <span id="receiptDate"></span></p>
            <p class="mb-0" style="font-size: 0.9rem;">Receipt #: <span id="receiptNumber"></span></p>
        </div>
        
        <div class="border-top border-bottom py-3 my-3">
            <div class="row mb-2">
                <div class="col-6">Customer:</div>
                <div class="col-6 text-end" id="receiptCustomer"></div>
            </div>
            <div class="row mb-2">
                <div class="col-6">Loan #:</div>
                <div class="col-6 text-end" id="receiptLoanNumber"></div>
            </div>
            <div class="row">
                <div class="col-6">Payment Method:</div>
                <div class="col-6 text-end" id="receiptMethod"></div>
            </div>
        </div>
        
        <div class="mb-3">
            <div class="d-flex justify-content-between py-2">
                <span>Amount Paid:</span>
                <span id="receiptAmount" class="fw-bold"></span>
            </div>
            <div class="d-flex justify-content-between py-2">
                <span>Reference:</span>
                <span id="receiptReference"></span>
            </div>
            <div class="d-flex justify-content-between py-2">
                <span>Received by:</span>
                <span id="receiptReceivedBy"></span>
            </div>
        </div>
        
        <div class="text-center mt-4 pt-3 border-top">
            <p class="mb-1">Thank you for your payment!</p>
            <p class="text-muted mb-0" style="font-size: 0.8rem;"><?= date('m/d/Y h:i A') ?></p>
        </div>
    </div>
</div>

<script>
// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Select2 for loan selection
    $('.select2').select2({
        theme: 'bootstrap-5',
        placeholder: 'Search by loan number or customer name',
        width: '100%'
    });
    
    // Toggle check date field when payment method is check
    $('#payment_method').on('change', function() {
        if ($(this).val() === 'check') {
            $('#checkFields').show();
            $('#transaction_reference').attr('placeholder', 'Check number');
        } else {
            $('#checkFields').hide();
            $('#transaction_reference').attr('placeholder', 'Reference number (optional)');
        }
        updateSummary();
    });
    
    // Trigger change event on page load
    $('#payment_method').trigger('change');
    
    // Update customer and loan info when a loan is selected
    $('#loan_id').on('change', function() {
        updateLoanInfo();
        updateSummary();
    });
    
    // Initialize with any existing loan selection
    if ($('#loan_id').val()) {
        updateLoanInfo();
    }
    
    // Initialize summary
    updateSummary();
    
    // Set default payment date to today if empty
    if (!$('#payment_date').val()) {
        $('#payment_date').val(new Date().toISOString().split('T')[0]);
    }
    
    // Handle form submission for printing receipt
    $('#paymentForm').on('submit', function(e) {
        // Form validation would go here
        // If validation passes, show receipt before submitting
        if (confirm('Would you like to print a receipt for this payment?')) {
            printReceipt();
        }
        // Form will submit normally after this
    });
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
    
    updateSummary();
}

// Get numeric value from formatted currency
function getNumericValue(currencyString) {
    if (!currencyString) return 0;
    return parseFloat(currencyString.replace(/[^0-9.-]+/g, '')) || 0;
}

// Update loan information when a loan is selected
function updateLoanInfo() {
    const selectedOption = $('#loan_id option:selected');
    
    if (selectedOption.val()) {
        // Update customer info
        $('#customerName').text(selectedOption.data('customer'));
        $('#customerPhone').text(selectedOption.data('phone') || '-');
        
        // Update loan info
        const amount = parseFloat(selectedOption.data('amount')) || 0;
        const paid = parseFloat(selectedOption.data('paid')) || 0;
        const balance = parseFloat(selectedOption.data('balance')) || 0;
        
        $('#loanAmount').text('$' + amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        $('#loanBalance').text('$' + balance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
        
        // Set payment amount to the remaining balance by default
        if (balance > 0) {
            $('#amount').val(balance.toFixed(2)).trigger('input');
        }
    } else {
        // Clear fields if no loan is selected
        $('#customerName, #customerPhone, #loanAmount, #loanBalance').text('-');
        $('#amount').val('').trigger('input');
    }
}

// Update payment summary
function updateSummary() {
    const amount = getNumericValue($('#amount').val());
    const method = $('#payment_method option:selected').text();
    const reference = $('#transaction_reference').val() || '-';
    
    // Update summary section
    $('#summaryAmount').text('$' + amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    $('#summaryMethod').text(method);
    $('#summaryReference').text(reference);
    
    // Calculate and update new balance
    const selectedOption = $('#loan_id option:selected');
    if (selectedOption.val()) {
        const currentBalance = parseFloat(selectedOption.data('balance')) || 0;
        const newBalance = Math.max(0, currentBalance - amount);
        $('#newBalance').text('$' + newBalance.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2}));
    } else {
        $('#newBalance').text('$0.00');
    }
}

// Print receipt
function printReceipt() {
    const receipt = $('#receiptTemplate').clone();
    receipt.removeClass('d-none');
    
    // Populate receipt data
    const today = new Date();
    const paymentDate = $('#payment_date').val() ? new Date($('#payment_date').val()) : today;
    
    receipt.find('#receiptDate').text(paymentDate.toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    }));
    
    // Receipt number would typically come from the server after saving
    receipt.find('#receiptNumber').text('TEMP-' + Math.floor(Math.random() * 10000));
    
    // Customer and loan info
    const selectedOption = $('#loan_id option:selected');
    if (selectedOption.val()) {
        receipt.find('#receiptCustomer').text(selectedOption.data('customer'));
        receipt.find('#receiptLoanNumber').text('#' + selectedOption.text().split(' - ')[0]);
    } else {
        receipt.find('#receiptCustomer').text('Walk-in Customer');
        receipt.find('#receiptLoanNumber').text('N/A');
    }
    
    // Payment details
    receipt.find('#receiptMethod').text($('#payment_method option:selected').text());
    receipt.find('#receiptAmount').text($('#summaryAmount').text());
    receipt.find('#receiptReference').text($('#transaction_reference').val() || '-');
    receipt.find('#receiptReceivedBy').text('<?= $_SESSION['user_name'] ?? 'System' ?>');
    
    // Create print window
    const printWindow = window.open('', '_blank', 'width=600,height=800');
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Payment Receipt</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .receipt { max-width: 400px; margin: 0 auto; padding: 20px; }
                .text-center { text-align: center; }
                .border-top { border-top: 1px solid #dee2e6; }
                .border-bottom { border-bottom: 1px solid #dee2e6; }
                .py-3 { padding-top: 1rem; padding-bottom: 1rem; }
                .my-3 { margin-top: 1rem; margin-bottom: 1rem; }
                .mb-0 { margin-bottom: 0; }
                .mb-1 { margin-bottom: 0.25rem; }
                .mb-2 { margin-bottom: 0.5rem; }
                .mb-3 { margin-bottom: 1rem; }
                .mb-4 { margin-bottom: 1.5rem; }
                .mt-3 { margin-top: 1rem; }
                .mt-4 { margin-top: 1.5rem; }
                .pt-3 { padding-top: 1rem; }
                .fw-bold { font-weight: bold; }
                .text-muted { color: #6c757d; }
                .d-flex { display: flex; }
                .justify-content-between { justify-content: space-between; }
            </style>
        </head>
        <body onload="window.print();">
            ${receipt.html()}
        </body>
        </html>
    `);
    printWindow.document.close();
}
</script>
