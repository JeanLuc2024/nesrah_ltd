<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in, if not redirect to login
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    header('Location: /nesrah/public/login.php');
    exit();
}

// Get user data
$username = !empty($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'User';
$role = !empty($_SESSION['role']) ? htmlspecialchars($_SESSION['role']) : 'user';
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?? 'Loan Management System' ?></title>
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
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }
        
        .stats-card.primary .stats-icon { 
            background: rgba(67, 97, 238, 0.1);
            color: var(--primary-color);
        }
        
        .stats-card.success .stats-icon { 
            background: rgba(75, 181, 67, 0.1);
            color: var(--success-color);
        }
        
        .stats-card.warning .stats-icon { 
            background: rgba(255, 193, 7, 0.1);
            color: var(--warning-color);
        }
        
        .stats-card.danger .stats-icon { 
            background: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
        }
        
        .stats-card .stats-value {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 10px 0 5px;
        }
        
        .stats-card .stats-label {
            color: #718096;
            font-size: 0.875rem;
        }
        
        .stats-card .stats-change {
            display: flex;
            align-items: center;
            font-size: 0.75rem;
            margin-top: 10px;
        }
        
        .stats-card .stats-change.up {
            color: var(--success-color);
        }
        
        .stats-card .stats-change.down {
            color: var(--danger-color);
        }
        
        /* Card Styles */
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
            border-radius: 10px 10px 0 0 !important;
        }
        
        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }
        
        .card-body {
            padding: 20px;
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
            .main-content {
                margin-left: 0;
            }
        }
        
        /* Active menu item */
        .sidebar-menu li.active a {
            background: var(--sidebar-hover);
            color: #fff;
            border-left-color: var(--primary-color);
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="/nesrah/public/admin-dashboard.php" class="sidebar-brand">
                <i class="fas fa-hand-holding-usd"></i>
                <span>LoanPro</span>
            </a>
        </div>
            
        <ul class="sidebar-menu">
            <li class="menu-title">Main</li>
            <li class="<?= $current_page === 'admin-dashboard.php' ? 'active' : '' ?>">
                <a href="/nesrah/public/admin-dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="<?= $current_page === 'loans.php' ? 'active' : '' ?>">
                <a href="/nesrah/public/loans.php">
                    <i class="fas fa-hand-holding-usd"></i> Loans
                </a>
            </li>
            <li class="<?= $current_page === 'customers.php' ? 'active' : '' ?>">
                <a href="/nesrah/public/customers.php">
                    <i class="fas fa-users"></i> Customers
                </a>
            </li>
            <li class="<?= $current_page === 'payments.php' ? 'active' : '' ?>">
                <a href="/nesrah/public/payments.php">
                    <i class="fas fa-money-bill-wave"></i> Payments
                </a>
            </li>
            <li class="<?= $current_page === 'reports.php' ? 'active' : '' ?>">
                <a href="/nesrah/public/reports.php">
                    <i class="fas fa-chart-bar"></i> Reports
                </a>
            </li>
            
            <?php if ($role === 'admin'): ?>
            <li class="menu-title mt-3">Administration</li>
            <li class="<?= $current_page === 'users.php' ? 'active' : '' ?>">
                <a href="/nesrah/public/users.php">
                    <i class="fas fa-user-cog"></i> User Management
                </a>
            </li>
            <li class="<?= $current_page === 'settings.php' ? 'active' : '' ?>">
                <a href="/nesrah/public/settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Navigation -->
        <div class="top-navbar">
            <div class="d-flex align-items-center">
                <button id="sidebarToggle" class="btn btn-link me-3" aria-label="Toggle sidebar">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" class="form-control" placeholder="Search...">
                </div>
            </div>
            
            <div class="user-menu">
                <div class="dropdown">
                    <a href="#" class="dropdown-toggle" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="user-avatar">
                            <?= strtoupper(substr($username, 0, 1)) ?>
                        </div>
                        <span class="d-none d-md-inline"><?= $username ?></span>
                        <i class="fas fa-chevron-down ms-2 d-none d-md-inline"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-user me-2"></i> Profile</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i> Settings</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/nesrah/public/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Page Content -->
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0"><?= $page_title ?? 'Dashboard' ?></h1>
                <?php if (isset($page_actions)): ?>
                    <div>
                        <?= $page_actions ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <!-- Page-specific content will be included here -->
            <?= $page_content ?? '' ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            
            if (sidebarToggle && sidebar) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                });
            }
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const isClickInsideSidebar = sidebar.contains(event.target);
                const isClickOnToggle = sidebarToggle.contains(event.target);
                
                if (!isClickInsideSidebar && !isClickOnToggle && window.innerWidth <= 768) {
                    sidebar.classList.remove('active');
                }
            });
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
            
            // Auto-hide alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
    
    <?php if (isset($page_scripts)): ?>
        <?= $page_scripts ?>
    <?php endif; ?>
</body>
</html>
