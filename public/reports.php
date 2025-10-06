<?php
// Set page title
$page_title = 'Reports & Analytics';

// Include the template
ob_start(); // Start output buffering
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Reports & Analytics</h5>
        <p class="text-muted mb-0">Generate and analyze reports</p>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="form-group">
                    <label>Report Type</label>
                    <select class="form-select">
                        <option>Select Report Type</option>
                        <option>Loan Portfolio</option>
                        <option>Payment Collection</option>
                        <option>Delinquency Report</option>
                        <option>Profit & Loss</option>
                        <option>Customer Statement</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Date From</label>
                    <input type="date" class="form-control" value="<?= date('Y-m-01') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    <label>Date To</label>
                    <input type="date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button class="btn btn-primary me-2">
                    <i class="fas fa-search me-2"></i> Generate
                </button>
                <button class="btn btn-outline-secondary">
                    <i class="fas fa-download me-2"></i> Export
                </button>
            </div>
        </div>
        
        <!-- Report Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h6 class="card-title">Total Loans</h6>
                        <h3 class="mb-0">$245,000</h3>
                        <small>Active portfolio</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h6 class="card-title">Monthly Collection</h6>
                        <h3 class="mb-0">$45,200</h3>
                        <small>This month</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body">
                        <h6 class="card-title">Delinquent Loans</h6>
                        <h3 class="mb-0">12</h3>
                        <small>Over 30 days</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h6 class="card-title">Active Customers</h6>
                        <h3 class="mb-0">87</h3>
                        <small>With active loans</small>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chart Placeholder -->
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Loan Portfolio Overview</h6>
            </div>
            <div class="card-body">
                <div style="height: 300px;" class="d-flex align-items-center justify-content-center bg-light rounded">
                    <div class="text-center text-muted">
                        <i class="fas fa-chart-line fa-3x mb-2 d-block"></i>
                        <p>Loan portfolio performance chart will be displayed here</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Report Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Report Data</h6>
                <div>
                    <button class="btn btn-sm btn-outline-secondary me-2">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                    <button class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-file-pdf me-1"></i> Save as PDF
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Loan ID</th>
                                <th>Customer</th>
                                <th>Principal</th>
                                <th>Interest</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Due Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td>L-1001</td>
                                <td>John Doe</td>
                                <td>$5,000.00</td>
                                <td>$600.00</td>
                                <td>$5,600.00</td>
                                <td><span class="badge bg-success">Active</span></td>
                                <td>2025-11-15</td>
                            </tr>
                            <!-- More rows will be populated from the database -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Get the buffered content and assign to $page_content
$page_content = ob_get_clean();

// Include the template
require_once 'includes/template.php';
?>
