<?php $this->extend('admin/layouts/main'); ?>

<?php $this->section('title', 'Dashboard'); ?>

<?php $this->section('active_dashboard', 'active'); ?>

<?php $this->section('content'); ?>
<div class="row g-4 mb-4">
    <!-- Total Loans Card -->
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Loans</h6>
                        <h2 class="mb-0"><?= number_format($stats['total_loans'] ?? 0) ?></h2>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="fas fa-hand-holding-usd text-primary fs-2"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-arrow-up"></i> 12.5% from last month
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Active Loans Card -->
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Active Loans</h6>
                        <h2 class="mb-0"><?= number_format($stats['active_loans'] ?? 0) ?></h2>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="fas fa-check-circle text-success fs-2"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-success bg-opacity-10 text-success">
                        <i class="fas fa-arrow-up"></i> 8.2% from last month
                    </span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Pending Approvals Card -->
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Pending Approvals</h6>
                        <h2 class="mb-0"><?= number_format($stats['pending_approvals'] ?? 0) ?></h2>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="fas fa-clock text-warning fs-2"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="/admin/loans?status=pending" class="btn btn-sm btn-warning">Review Now</a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Total Repayment Card -->
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted mb-1">Total Repayment</h6>
                        <h2 class="mb-0">$<?= number_format($stats['total_repayment'] ?? 0, 2) ?></h2>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="fas fa-dollar-sign text-info fs-2"></i>
                    </div>
                </div>
                <div class="mt-3">
                    <span class="badge bg-info bg-opacity-10 text-info">
                        <i class="fas fa-arrow-up"></i> 15.3% from last month
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Loans -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Loans</h5>
                <a href="/admin/loans" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Loan ID</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentLoans)): ?>
                                <?php foreach ($recentLoans as $loan): ?>
                                    <tr>
                                        <td>#<?= $loan['id'] ?></td>
                                        <td><?= htmlspecialchars($loan['customer_name']) ?></td>
                                        <td>$<?= number_format($loan['amount'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getStatusBadgeClass($loan['status']) ?>">
                                                <?= ucfirst($loan['status']) ?>
                                            </span>
                                        </td>
                                        <td><?= date('M d, Y', strtotime($loan['next_payment_date'])) ?></td>
                                        <td>
                                            <a href="/admin/loans/<?= $loan['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">No recent loans found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Payments -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Upcoming Payments</h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($upcomingPayments)): ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($upcomingPayments as $payment): ?>
                            <div class="list-group-item border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0"><?= htmlspecialchars($payment['customer_name']) ?></h6>
                                    <span class="badge bg-primary">$<?= number_format($payment['amount'], 2) ?></span>
                                </div>
                                <div class="d-flex justify-content-between text-muted small">
                                    <span>Due: <?= date('M d, Y', strtotime($payment['due_date'])) ?></span>
                                    <a href="/admin/loans/<?= $payment['loan_id'] ?>" class="text-primary">View</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-check-circle text-muted mb-2" style="font-size: 2rem;"></i>
                        <p class="text-muted mb-0">No upcoming payments</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-transparent">
                <a href="/admin/payments" class="btn btn-sm btn-outline-primary w-100">View All Payments</a>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="/admin/loans/create" class="btn btn-primary">
                        <i class="fas fa-plus-circle me-2"></i> New Loan
                    </a>
                    <a href="/admin/customers/create" class="btn btn-outline-primary">
                        <i class="fas fa-user-plus me-2"></i> Add Customer
                    </a>
                    <a href="/admin/products/create" class="btn btn-outline-primary">
                        <i class="fas fa-box-open me-2"></i> Add Product
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Loan Status Overview -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Loan Status Overview</h5>
            </div>
            <div class="card-body">
                <canvas id="loanStatusChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Recent Activities -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Activities</h5>
                <a href="/admin/activities" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (!empty($recentActivities)): ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="list-group-item border-0 py-3">
                                <div class="d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        <div class="avatar-sm">
                                            <span class="avatar-title rounded-circle bg-<?= $activity['type'] === 'payment' ? 'success' : 'primary' ?>-subtle text-<?= $activity['type'] === 'payment' ? 'success' : 'primary' ?>">
                                                <i class="fas fa-<?= $activity['type'] === 'payment' ? 'dollar-sign' : 'file-invoice-dollar' ?>"></i>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h6 class="mb-1"><?= htmlspecialchars($activity['title']) ?></h6>
                                        <p class="text-muted mb-0 small"><?= htmlspecialchars($activity['description']) ?></p>
                                        <small class="text-muted"><?= timeAgo($activity['created_at']) ?></small>
                                    </div>
                                    <?php if (isset($activity['amount'])): ?>
                                        <div class="flex-shrink-0 ms-2">
                                            <span class="fw-medium">$<?= number_format($activity['amount'], 2) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history text-muted mb-2" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">No recent activities</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $this->section('scripts'); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Loan Status Chart
    const ctx = document.getElementById('loanStatusChart').getContext('2d');
    const loanStatusChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Pending', 'Completed', 'Defaulted', 'Rejected'],
            datasets: [{
                data: [
                    <?= $stats['active_loans'] ?? 0 ?>, 
                    <?= $stats['pending_approvals'] ?? 0 ?>, 
                    <?= $stats['completed_loans'] ?? 0 ?>, 
                    <?= $stats['defaulted_loans'] ?? 0 ?>, 
                    <?= $stats['rejected_loans'] ?? 0 ?>
                ],
                backgroundColor: [
                    '#0d6efd',
                    '#ffc107',
                    '#198754',
                    '#dc3545',
                    '#6c757d'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100) || 0;
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
</script>
<?php $this->endSection(); ?>

<?php $this->endSection(); ?>

<?php
// Helper function to get badge class based on status
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'active':
            return 'success';
        case 'pending':
            return 'warning';
        case 'completed':
            return 'info';
        case 'defaulted':
            return 'danger';
        case 'rejected':
            return 'secondary';
        default:
            return 'secondary';
    }
}

// Helper function to format time ago
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $time = time() - $time;
    
    $units = [
        31536000 => 'year',
        2592000 => 'month',
        604800 => 'week',
        86400 => 'day',
        3600 => 'hour',
        60 => 'minute',
        1 => 'second'
    ];
    
    foreach ($units as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '') . ' ago';
    }
    
    return 'just now';
}
?>
