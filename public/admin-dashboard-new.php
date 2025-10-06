<?php
// Set page title
$page_title = 'Admin Dashboard';

// Start output buffering
ob_start();

// Include database configuration
require_once __DIR__ . '/../app/config/database.php';

// Include custom autoloader
require_once __DIR__ . '/../app/autoload.php';

// Initialize database connection
try {
    $db = getDBConnection();
    
    // Get stats from database
    // Total Products
    $result = fetchOne("SELECT COUNT(*) as count FROM products");
    $totalProducts = $result ? (int)$result['count'] : 0;
    
    // Total Loans
    $result = fetchOne("SELECT COUNT(*) as count FROM loans");
    $totalLoans = $result ? (int)$result['count'] : 0;
    
    // Active Loans
    $result = fetchOne("SELECT COUNT(*) as count FROM loans WHERE status = 'active'");
    $activeLoans = $result ? (int)$result['count'] : 0;
    
    // Completed Loans
    $result = fetchOne("SELECT COUNT(*) as count FROM loans WHERE status = 'completed'");
    $completedLoans = $result ? (int)$result['count'] : 0;
    
    // Recent Loans with product names and borrower names
    $recentLoans = fetchAll("
        SELECT l.*, p.name as product_name, CONCAT(u.first_name, ' ', u.last_name) as borrower_name
        FROM loans l 
        LEFT JOIN products p ON l.product_id = p.id 
        LEFT JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC 
        LIMIT 5
    ") ?: [];
    
} catch (PDOException $e) {
    // Log error and set default values
    error_log("Error fetching dashboard stats: " . $e->getMessage());
    $totalProducts = 0;
    $totalLoans = 0;
    $activeLoans = 0;
    $completedLoans = 0;
    $recentLoans = [];
    
    // Show error message to admin
    if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']) {
        $error = "Error loading dashboard data: " . $e->getMessage();
    } else {
        $error = "Unable to load dashboard data. Please try again later.";
    }
}
?>

<div class="container-fluid py-4">
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-12">
            <div class="page-header mb-4">
                <h2>Dashboard Overview</h2>
                <p class="text-muted">Welcome back, <?= $_SESSION['username'] ?? 'Admin' ?>! Here's what's happening with your business today.</p>
            </div>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Products</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalProducts) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-box fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Loans</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalLoans) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Active Loans</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($activeLoans) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sync-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Completed Loans</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($completedLoans) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Content Row -->
    <div class="row">
        <!-- Recent Loans -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Loans</h6>
                    <a href="loans.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Borrower</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recentLoans)): ?>
                                    <?php foreach ($recentLoans as $loan): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($loan['product_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($loan['borrower_name'] ?? 'N/A') ?></td>
                                            <td>$<?= number_format($loan['amount'] ?? 0, 2) ?></td>
                                            <td>
                                                <?php 
                                                $statusClass = '';
                                                switch(strtolower($loan['status'] ?? '')) {
                                                    case 'active':
                                                        $statusClass = 'badge-success';
                                                        break;
                                                    case 'pending':
                                                        $statusClass = 'badge-warning';
                                                        break;
                                                    case 'completed':
                                                        $statusClass = 'badge-info';
                                                        break;
                                                    case 'defaulted':
                                                        $statusClass = 'badge-danger';
                                                        break;
                                                    default:
                                                        $statusClass = 'badge-secondary';
                                                }
                                                ?>
                                                <span class="badge <?= $statusClass ?>"><?= ucfirst($loan['status'] ?? 'N/A') ?></span>
                                            </td>
                                            <td>
                                                <a href="view-loan.php?id=<?= $loan['id'] ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4">No recent loans found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="add-loan.php" class="btn btn-primary btn-block py-3">
                                <i class="fas fa-plus-circle fa-lg mr-2"></i> New Loan
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="add-product.php" class="btn btn-success btn-block py-3">
                                <i class="fas fa-box fa-lg mr-2"></i> Add Product
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="customers.php" class="btn btn-info btn-block py-3">
                                <i class="fas fa-users fa-lg mr-2"></i> Manage Customers
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="reports.php" class="btn btn-warning btn-block py-3">
                                <i class="fas fa-chart-bar fa-lg mr-2"></i> View Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Activity</h6>
                </div>
                <div class="card-body">
                    <div class="activity-feed">
                        <?php if (!empty($recentLoans)): ?>
                            <?php foreach (array_slice($recentLoans, 0, 5) as $activity): ?>
                                <div class="mb-3">
                                    <div class="small text-gray-500"><?= date('M d, Y', strtotime($activity['created_at'] ?? 'now')) ?></div>
                                    <span class="font-weight-bold">New loan</span> of $<?= number_format($activity['amount'] ?? 0, 2) ?>
                                    for <?= htmlspecialchars($activity['borrower_name'] ?? 'a customer') ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center text-muted py-3">No recent activity</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Get the buffered content and assign to $page_content
$page_content = ob_get_clean();

// Include the template which will handle the layout
require_once 'includes/template.php';
?>
