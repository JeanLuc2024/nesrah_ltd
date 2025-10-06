<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #<?= $payment['id'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media print {
            body { 
                -webkit-print-color-adjust: exact !important;
                padding: 20px;
            }
            .no-print { display: none !important; }
            .receipt { 
                max-width: 400px; 
                margin: 0 auto;
                border: 1px solid #ddd !important;
                box-shadow: none !important;
            }
        }
        body { 
            background-color: #f8f9fa;
            padding: 20px 0;
        }
        .receipt {
            background: white;
            border-radius: 8px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
            margin: 0 auto;
            max-width: 400px;
        }
        .receipt-header {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .receipt-body {
            padding: 25px;
        }
        .receipt-footer {
            background-color: #f8f9fc;
            padding: 15px 25px;
            text-align: center;
            font-size: 0.9em;
            color: #6c757d;
        }
        .divider {
            border-top: 1px dashed #dee2e6;
            margin: 20px 0;
        }
        .text-primary { color: #4e73df !important; }
        .text-success { color: #1cc88a !important; }
        .badge { font-weight: 500; }
        .receipt-logo {
            max-width: 120px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <!-- Print Button -->
                <div class="text-center mb-4 no-print">
                    <button onclick="window.print()" class="btn btn-primary me-2">
                        <i class="fas fa-print me-2"></i> Print Receipt
                    </button>
                    <a href="/nesrah/public/payments/<?= $payment['id'] ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Back to Payment
                    </a>
                </div>
                
                <!-- Receipt -->
                <div class="receipt">
                    <!-- Header -->
                    <div class="receipt-header">
                        <?php if (!empty($company['logo'])): ?>
                            <img src="/nesrah/public/uploads/<?= $company['logo'] ?>" alt="Logo" class="receipt-logo">
                        <?php else: ?>
                            <h4 class="mb-1"><?= htmlspecialchars($company['name'] ?? 'Loan Management System') ?></h4>
                        <?php endif; ?>
                        <p class="mb-0"><?= htmlspecialchars($company['address'] ?? '') ?></p>
                        <p class="mb-0"><?= htmlspecialchars($company['phone'] ?? '') ?> 
                            <?php if (!empty($company['email'])): ?>
                                • <?= htmlspecialchars($company['email']) ?>
                            <?php endif; ?>
                        </p>
                        <h5 class="mt-3 mb-0">PAYMENT RECEIPT</h5>
                        <p class="mb-0">#<?= $payment['id'] ?></p>
                    </div>
                    
                    <!-- Body -->
                    <div class="receipt-body">
                        <!-- Payment Details -->
                        <div class="row mb-3">
                            <div class="col-6">
                                <small class="text-muted d-block">Date</small>
                                <strong><?= date('M j, Y', strtotime($payment['payment_date'])) ?></strong>
                            </div>
                            <div class="col-6 text-end">
                                <small class="text-muted d-block">Time</small>
                                <strong><?= date('g:i A', strtotime($payment['payment_date'])) ?></strong>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Received from</small>
                            <strong><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></strong>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">For loan</small>
                            <strong>#<?= htmlspecialchars($loan['loan_number']) ?></strong>
                        </div>
                        
                        <div class="mb-4">
                            <small class="text-muted d-block">Payment method</small>
                            <span class="badge bg-<?= getPaymentMethodBadgeClass($payment['payment_method']) ?>">
                                <?= ucfirst($payment['payment_method']) ?>
                            </span>
                            <?php if (!empty($payment['transaction_reference'])): ?>
                                <div class="mt-1">
                                    <small class="text-muted">Reference:</small>
                                    <span class="ms-1"><?= htmlspecialchars($payment['transaction_reference']) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="bg-light p-3 rounded mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span>Amount Paid:</span>
                                <h3 class="mb-0 text-success">$<?= number_format($payment['amount'], 2) ?></h3>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Received by:</span>
                                <span><?= htmlspecialchars($payment['received_by_name'] ?? 'System') ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($payment['notes'])): ?>
                            <div class="border-top pt-3">
                                <small class="text-muted d-block mb-1">Notes</small>
                                <p class="mb-0"><?= nl2br(htmlspecialchars($payment['notes'])) ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="divider"></div>
                        
                        <!-- Loan Summary -->
                        <h6 class="text-uppercase text-muted mb-3">Loan Summary</h6>
                        <div class="mb-2 d-flex justify-content-between">
                            <span>Loan Amount:</span>
                            <span>$<?= number_format($loan['amount'], 2) ?></span>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span>Interest Rate:</span>
                            <span><?= $loan['interest_rate'] ?>% (<?= ucfirst($loan['interest_type']) ?>)</span>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span>Term:</span>
                            <span><?= $loan['term_months'] ?> months</span>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span>Total Payable:</span>
                            <span>$<?= number_format($loan['total_payable'], 2) ?></span>
                        </div>
                        <div class="mb-2 d-flex justify-content-between">
                            <span>Total Paid:</span>
                            <span class="fw-bold">$<?= number_format($loan['total_paid'] ?? 0, 2) ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Remaining Balance:</span>
                            <span class="fw-bold text-primary">$<?= number_format(($loan['total_payable'] ?? 0) - ($loan['total_paid'] ?? 0), 2) ?></span>
                        </div>
                    </div>
                    
                    <!-- Footer -->
                    <div class="receipt-footer">
                        <p class="mb-1">Thank you for your payment!</p>
                        <p class="small mb-0">This is a computer-generated receipt. No signature required.</p>
                        <p class="small text-muted mt-2">
                            Generated on <?= date('M j, Y g:i A') ?>
                        </p>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="text-center mt-4 no-print">
                    <a href="/nesrah/public/payments/<?= $payment['id'] ?>/receipt?download=pdf" class="btn btn-outline-primary me-2">
                        <i class="fas fa-file-pdf me-2"></i> Download as PDF
                    </a>
                    <a href="mailto:<?= $customer['email'] ?>?subject=Payment%20Receipt%20%23<?= $payment['id'] ?>&body=Dear%20<?= urlencode($customer['first_name']) ?>,%0A%0AAttached%20is%20your%20payment%20receipt.%0A%0AThank%20you!" 
                       class="btn btn-outline-info">
                        <i class="fas fa-envelope me-2"></i> Email to Customer
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Auto-print when the page loads if print parameter is in URL
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('print') === '1') {
            window.print();
        }
    });
    </script>
</body>
</html>

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
