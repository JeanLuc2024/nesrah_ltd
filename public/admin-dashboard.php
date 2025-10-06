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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Loan Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --success-color: #4bb543;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #212529;
            --sidebar-bg: #1e1e2d;
            --sidebar-hover: #2b2b40;
            --content-bg: #f5f7fb;
            --card-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--content-bg);
            color: #4a5568;
            line-height: 1.6;
        }
        
        /* Sidebar Styles */
        .sidebar {
            min-height: 100vh;
            background: var(--sidebar-bg);
            color: #9899ac;
            transition: all 0.3s;
            padding: 0;
            position: fixed;
            width: 250px;
            z-index: 1000;
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .sidebar-brand {
            color: #fff;
            font-size: 1.25rem;
            font-weight: 600;
            text-decoration: none;
            display: flex;
            align-items: center;
        }
        
        .sidebar-brand i {
            margin-right: 10px;
            color: var(--primary-color);
            font-size: 1.5rem;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #9899ac;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li.active a {
            background: var(--sidebar-hover);
            color: #fff;
            border-left-color: var(--primary-color);
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }
        
        .sidebar-menu .menu-title {
            padding: 15px 20px 5px;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            color: #6c7293;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            transition: all 0.3s;
        }
        
        /* Top Navigation */
        .top-navbar {
            background: #fff;
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .search-box {
            position: relative;
            max-width: 400px;
            width: 100%;
        }
        
        .search-box input {
            padding-left: 40px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            width: 100%;
            height: 40px;
        }
        
        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
        }
        
        .user-menu .dropdown-toggle {
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #2d3748;
            font-weight: 500;
        }
        
        .user-menu .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-right: 10px;
            background: var(--primary-color);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        /* Stats Cards */
        .stats-card {
            background: #fff;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s;
            border-left: 4px solid var(--primary-color);
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .stats-card.primary { border-left-color: var(--primary-color); }
        .stats-card.success { border-left-color: var(--success-color); }
        .stats-card.warning { border-left-color: var(--warning-color); }
        .stats-card.danger { border-left-color: var(--danger-color); }
        
        .stats-card .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .stats-card.primary .stats-icon { background: rgba(67, 97, 238, 0.1); color: var(--primary-color); }
        .stats-card.success .stats-icon { background: rgba(75, 181, 67, 0.1); color: var(--success-color); }
        .stats-card.warning .stats-icon { background: rgba(255, 193, 7, 0.1); color: var(--warning-color); }
        .stats-card.danger .stats-icon { background: rgba(220, 53, 69, 0.1); color: var(--danger-color); }
        
        .stats-card h3 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 5px 0;
            color: #2d3748;
        }
        
        .stats-card p {
            margin: 0;
            color: #718096;
            font-size: 0.875rem;
        }
        
        /* Recent Activity */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid #edf2f7;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
            color: #2d3748;
        }
        
        .card-body {
            padding: 20px;
        }
        
        /* Table Styles */
        .table {
            margin-bottom: 0;
        }
        
        .table th {
            border-top: none;
            font-weight: 600;
            color: #718096;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            background: #f8fafc;
        }
        
        .table td {
            vertical-align: middle;
            padding: 12px 15px;
            border-color: #edf2f7;
        }
        
        .badge {
            padding: 6px 10px;
            font-weight: 500;
            border-radius: 4px;
            font-size: 0.75rem;
        }
        
        .badge-success { background-color: #e6f7ee; color: #10b981; }
        .badge-warning { background-color: #fffbeb; color: #f59e0b; }
        .badge-danger { background-color: #fef2f2; color: #ef4444; }
        
        /* Responsive */
        @media (max-width: 992px) {
            .sidebar {
                margin-left: -250px;
            }
            .sidebar.active {
                margin-left: 0;
            }
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Toggle Button */
        #sidebarToggle {
            background: none;
            border: none;
            font-size: 1.25rem;
            color: #4a5568;
            cursor: pointer;
            padding: 5px;
            margin-right: 15px;
        }
        
        /* Active State */
        .sidebar-menu li.active > a {
            background: var(--sidebar-hover);
            color: #fff;
            border-left-color: var(--primary-color);
        }
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --sidebar-bg: #2a3042;
            --sidebar-hover: #3a4054;
            --content-bg: #f5f7fb;
            --card-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.05);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--content-bg);
        }
        
        /* Sidebar Styles */
        .sidebar {
            min-height: 100vh;
            background: var(--sidebar-bg);
            color: #fff;
            padding: 0;
            transition: all 0.3s;
        }
        
        .sidebar-header {
            padding: 20px;
            background: rgba(0, 0, 0, 0.1);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li a {
            display: block;
            padding: 12px 20px;
            color: #a8b1c7;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }
        
        .sidebar-menu li a:hover,
        .sidebar-menu li.active a {
            background: var(--sidebar-hover);
            color: #fff;
            border-left-color: var(--primary-color);
        }
        
        .sidebar-menu li a i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        
        /* Main Content Styles */
        .main-content {
            padding: 20px;
            width: 100%;
        }
        
        .navbar-custom {
            background: #fff;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 0.8rem 1.5rem;
        }
        
        .user-dropdown img {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin-right: 10px;
        }
        
        /* Card Styles */
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            margin-bottom: 20px;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
            font-weight: 600;
            padding: 15px 20px;
        }
        
        .stat-card {
            text-align: center;
            padding: 20px;
        }
        
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: var(--primary-color);
        }
        
        .stat-card h2 {
            font-size: 2rem;
            font-weight: 700;
            margin: 10px 0;
            color: #2c3e50;
        }
        
        .stat-card p {
            color: #7f8c8d;
            margin: 0;
            font-size: 0.9rem;
        }
        
        /* Table Styles */
        .table th {
            border-top: none;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            .sidebar.active {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
        <!-- Include Common Sidebar -->
        <?php include 'includes/sidebar.php'; ?>
        
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid py-4">
            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-12">
                    <div class="page-header mb-4">
                        <h2>Dashboard Overview</h2>
                        <p class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>! Here's what's happening with your business today.</p>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row">
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card stats-card primary">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-2">Total Products</h6>
                                    <h3 class="mb-0"><?= number_format($totalProducts) ?></h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-box"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card stats-card success">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-2">Total Loans</h6>
                                    <h3 class="mb-0"><?= number_format($totalLoans) ?></h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-hand-holding-usd"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card stats-card info">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-2">Active Loans</h6>
                                    <h3 class="mb-0"><?= number_format($activeLoans) ?></h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card stats-card warning">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="text-uppercase text-muted mb-2">Completed Loans</h6>
                                    <h3 class="mb-0"><?= number_format($completedLoans) ?></h3>
                                </div>
                                <div class="stats-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
                
        <!-- Recent Activity -->
        <!-- Quick Stats -->
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card stats-card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-white-50">Total Products</h6>
                                <h2 class="mb-0"><?= $totalProducts ?></h2>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stats-card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-white-50">Total Loans</h6>
                                <h2 class="mb-0"><?= $totalLoans ?></h2>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-hand-holding-usd"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stats-card bg-warning text-dark">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-dark-50">Active Loans</h6>
                                <h2 class="mb-0"><?= $activeLoans ?></h2>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-sync-alt"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card stats-card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase text-white-50">Paid Loans</h6>
                                <h2 class="mb-0"><?= $paidLoans ?></h2>
                            </div>
                            <div class="stats-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Loans -->
        <div class="row mt-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Loans</h5>
                        <a href="loans.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recentLoans)): ?>
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
                                                            $statusClass = 'bg-success';
                                                            break;
                                                        case 'pending':
                                                            $statusClass = 'bg-warning';
                                                            break;
                                                        case 'completed':
                                                            $statusClass = 'bg-info';
                                                            break;
                                                        case 'defaulted':
                                                            $statusClass = 'bg-danger';
                                                            break;
                                                        default:
                                                            $statusClass = 'bg-secondary';
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
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mb-0">No recent loans found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="new-loan.php" class="btn btn-primary mb-2">
                                <i class="fas fa-plus-circle me-2"></i> New Loan
                            </a>
                            <a href="new-customer.php" class="btn btn-success mb-2">
                                <i class="fas fa-user-plus me-2"></i> Add Customer
                            </a>
                            <a href="reports.php" class="btn btn-info mb-2">
                                <i class="fas fa-chart-bar me-2"></i> View Reports
                            </a>
                            <a href="settings.php" class="btn btn-outline-secondary">
                                <i class="fas fa-cog me-2"></i> Settings
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End of main content -->
    </div>
    <!-- End of container -->
    
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Toggle sidebar on mobile
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.body.classList.toggle('sidebar-toggled');
            document.querySelector('.sidebar').classList.toggle('toggled');
        });

        // Handle window resize events
        function handleResize() {
            // Close any open menu accordions when window is resized below 768px
            if (window.innerWidth < 768) {
                var openDropdowns = document.querySelectorAll('.sidebar .collapse.show');
                openDropdowns.forEach(function(dropdown) {
                    dropdown.classList.remove('show');
                });
            }
            
            // Handle sidebar on larger screens
            if (window.innerWidth > 992) {
                document.getElementById('sidebar').classList.remove('active');
            }
        }
        
        // Add event listener for window resize
        window.addEventListener('resize', handleResize);
        
        // Initial check on page load
        handleResize();
    </script>
</body>
</html>
