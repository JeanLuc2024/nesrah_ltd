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
    
    // Recent Loans with product names
    $recentLoans = fetchAll("
        SELECT l.*, p.name as product_name 
        FROM loans l 
        LEFT JOIN products p ON l.product_id = p.id 
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
        <!-- Top Navigation -->
        <div class="top-navbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Search...">
                </div>
            </div>
            
            <div class="user-menu">
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle" role="button" id="userDropdown" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($username, 0, 1)); ?>
                        </div>
                        <span class="d-none d-md-inline"><?php echo $username; ?></span>
                        <i class="fas fa-chevron-down ms-2 d-none d-md-inline"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/profile.php"><i class="fas fa-user me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="/settings.php"><i class="fas fa-cog me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
            
        <!-- Stats Cards -->
        <div class="row">
            <div class="col-md-6 col-xl-3">
                <div class="stats-card primary">
                    <div class="stats-icon">
                        <i class="fas fa-hand-holding-usd"></i>
                    </div>
                    <h3>$45,231</h3>
                    <p>Total Loans</p>
                    <div class="mt-2">
                        <span class="text-success"><i class="fas fa-arrow-up"></i> 12.5%</span> from last month
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stats-card success">
                    <div class="stats-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3>1,254</h3>
                    <p>Active Customers</p>
                    <div class="mt-2">
                        <span class="text-success"><i class="fas fa-arrow-up"></i> 8.2%</span> from last month
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stats-card warning">
                    <div class="stats-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3>$12,345</h3>
                    <p>Payments This Month</p>
                    <div class="mt-2">
                        <span class="text-success"><i class="fas fa-arrow-up"></i> 5.7%</span> from last month
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-3">
                <div class="stats-card danger">
                    <div class="stats-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h3>12</h3>
                    <p>Overdue Loans</p>
                    <div class="mt-2">
                        <span class="text-danger"><i class="fas fa-arrow-up"></i> 2.3%</span> from last month
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

        <!-- Recent Activity -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Loans</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Loan ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>#LN-1001</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2">JD</div>
                                                <span>John Doe</span>
                                            </div>
                                        </td>
                                        <td>$5,000.00</td>
                                        <td><span class="badge badge-success">Approved</span></td>
                                        <td>Oct 1, 2023</td>
                                    </tr>
                                    <tr>
                                        <td>#LN-1002</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2" style="background: #f59e0b;">JS</div>
                                                <span>Jane Smith</span>
                                            </div>
                                        </td>
                                        <td>$10,000.00</td>
                                        <td><span class="badge badge-warning">Pending</span></td>
                                        <td>Sep 28, 2023</td>
                                    </tr>
                                    <tr>
                                        <td>#LN-1003</td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-2" style="background: #10b981;">RJ</div>
                                                <span>Robert Johnson</span>
                                            </div>
                                        </td>
                                        <td>$7,500.00</td>
                                        <td><span class="badge badge-danger">Overdue</span></td>
                                        <td>Sep 15, 2023</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-end mt-3">
                            <a href="/loans.php" class="btn btn-sm btn-outline-primary">View All Loans</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="products.php" class="btn btn-primary">
                                <i class="fas fa-box me-2"></i> Manage Products
                            </a>
                            <a href="loans.php" class="btn btn-success">
                                <i class="fas fa-hand-holding-usd me-2"></i> Manage Loans
                            </a>
                            <a href="settings.php" class="btn btn-info text-white">
                                <i class="fas fa-cog me-2"></i> Account Settings
                            </a>
                            <a href="logout.php" class="btn btn-danger">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 fw-semibold">John Doe</h6>
                                    <small class="text-muted">Tomorrow</small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">#LN-1001</span>
                                    <span class="fw-bold">$500.00</span>
                                </div>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action border-0 px-4 py-3">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 fw-semibold">Sarah Williams</h6>
                                    <small class="text-muted">In 2 days</small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">#LN-1005</span>
                                    <span class="fw-bold">$350.00</span>
                                </div>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action border-0 px-4 py-3">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1 fw-semibold">Michael Brown</h6>
                                    <small class="text-muted">In 3 days</small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-muted">#LN-1008</span>
                                    <span class="fw-bold">$1,200.00</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Toggle sidebar
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    document.body.classList.toggle('sidebar-toggled');
                    sidebar.classList.toggle('active');
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('active') ? 'false' : 'true');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const isClickInside = sidebar.contains(event.target) || 
                                    (sidebarToggle && (event.target === sidebarToggle || sidebarToggle.contains(event.target)));
                
                if (!isClickInside && window.innerWidth <= 992) {
                    sidebar.classList.remove('active');
                    document.body.classList.remove('sidebar-toggled');
                }
            });
            
            // Check for saved sidebar state
            if (localStorage.getItem('sidebarCollapsed') === 'true') {
                sidebar.classList.remove('active');
                document.body.classList.add('sidebar-toggled');
            }
            
            // Update active menu item
            const currentPage = window.location.pathname.split('/').pop() || 'admin-dashboard.php';
            const menuItems = document.querySelectorAll('.sidebar-menu li a');
            
            menuItems.forEach(item => {
                const href = item.getAttribute('href');
                if (href && (href.endsWith(currentPage) || (currentPage === 'admin-dashboard.php' && href.endsWith('admin-dashboard.php')))) {
                    item.parentElement.classList.add('active');
                } else {
                    item.parentElement.classList.remove('active');
                }
            });
            
            // Initialize charts (example with Chart.js)
            if (typeof Chart !== 'undefined') {
                // You can add chart initialization code here
            }
        });
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 992) {
                document.getElementById('sidebar').classList.remove('active');
            }
        });
    </script>
</body>
</html>
