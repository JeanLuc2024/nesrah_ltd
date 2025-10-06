<div class="row g-4 mb-4">
    <!-- Stats Cards -->
    <div class="col-md-6 col-xl-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-0">Total Loans</h6>
                        <h2 class="mt-2 mb-0"><?= number_format($stats['total_loans']) ?></h2>
                    </div>
                    <div class="icon-shape">
                        <i class="fas fa-hand-holding-usd fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="/loans" class="text-white text-decoration-none">
                    View all loans <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-0">Active Loans</h6>
                        <h2 class="mt-2 mb-0"><?= number_format($stats['active_loans']) ?></h2>
                    </div>
                    <div class="icon-shape">
                        <i class="fas fa-chart-line fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="/loans?status=active" class="text-white text-decoration-none">
                    View active loans <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-0">Total Customers</h6>
                        <h2 class="mt-2 mb-0"><?= number_format($stats['total_customers']) ?></h2>
                    </div>
                    <div class="icon-shape">
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="/customers" class="text-white text-decoration-none">
                    View all customers <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6 col-xl-3">
        <div class="card bg-warning text-dark h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-0">Today's Payments</h6>
                        <h2 class="mt-2 mb-0"><?= number_format($stats['total_payments'], 2) ?></h2>
                    </div>
                    <div class="icon-shape">
                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="/payments?date=<?= date('Y-m-d') ?>" class="text-dark text-decoration-none">
                    View payments <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Loans -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Loans</h5>
                <a href="/loans" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($stats['recent_loans'], 0, 5) as $loan): ?>
                            <tr>
                                <td>
                                    <a href="/loans/view/<?= $loan['id'] ?>" class="text-decoration-none">
                                        <?= htmlspecialchars($loan['first_name'] . ' ' . $loan['last_name']) ?>
                                    </a>
                                </td>
                                <td><?= number_format($loan['amount'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= getStatusBadgeClass($loan['status']) ?>">
                                        <?= ucfirst($loan['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('M d, Y', strtotime($loan['created_at'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats['recent_loans'])): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">No recent loans found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Upcoming Payments -->
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Upcoming Payments (7 days)</h5>
                <a href="/payments/upcoming" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Customer</th>
                                <th>Due Date</th>
                                <th>Amount Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($stats['upcoming_payments'], 0, 5) as $payment): ?>
                            <tr>
                                <td><?= htmlspecialchars($payment['customer_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($payment['due_date'])) ?></td>
                                <td><?= number_format($payment['amount_due'], 2) ?></td>
                                <td>
                                    <span class="badge bg-<?= $payment['status'] === 'overdue' ? 'danger' : 'warning' ?>">
                                        <?= ucfirst($payment['status']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($stats['upcoming_payments'])): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">No upcoming payments</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
/**
 * Get Bootstrap badge class based on loan status
 * 
 * @param string $status
 * @return string
 */
function getStatusBadgeClass($status) {
    switch (strtolower($status)) {
        case 'approved':
        case 'completed':
            return 'success';
        case 'pending':
            return 'warning';
        case 'rejected':
            return 'danger';
        case 'disbursed':
            return 'info';
        case 'defaulted':
            return 'dark';
        default:
            return 'secondary';
    }
}
?>
