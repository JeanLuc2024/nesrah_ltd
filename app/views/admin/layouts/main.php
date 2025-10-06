<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - <?= htmlspecialchars($title ?? 'Dashboard') ?></title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link href="/assets/css/admin.css" rel="stylesheet">
    
    <?php if (isset($styles)): ?>
        <?php foreach ($styles as $style): ?>
            <link href="<?= $style ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?= $this->section('head') ?>
</head>
<body class="admin-layout">
    <!-- Page Wrapper -->
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark border-right" id="sidebar-wrapper">
            <div class="sidebar-heading text-white p-3">
                <h4 class="mb-0">Nesrah Admin</h4>
                <small>Loan Management System</small>
            </div>
            <div class="list-group list-group-flush">
                <a href="/admin/dashboard" class="list-group-item list-group-item-action bg-dark text-white <?= $this->section('active_dashboard') ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                
                <div class="sidebar-heading mt-3 px-3 text-white-50">LOAN MANAGEMENT</div>
                <a href="/admin/loans" class="list-group-item list-group-item-action bg-dark text-white <?= $this->section('active_loans') ? 'active' : '' ?>">
                    <i class="fas fa-hand-holding-usd me-2"></i> All Loans
                </a>
                <a href="/admin/loans/create" class="list-group-item list-group-item-action bg-dark text-white <?= $this->section('active_loans_create') ? 'active' : '' ?>">
                    <i class="fas fa-plus-circle me-2"></i> New Loan
                </a>
                
                <div class="sidebar-heading mt-3 px-3 text-white-50">PRODUCT MANAGEMENT</div>
                <a href="/admin/products" class="list-group-item list-group-item-action bg-dark text-white <?= $this->section('active_products') ? 'active' : '' ?>">
                    <i class="fas fa-boxes me-2"></i> Products
                </a>
                <a href="/admin/products/create" class="list-group-item list-group-item-action bg-dark text-white <?= $this->section('active_products_create') ? 'active' : '' ?>">
                    <i class="fas fa-plus-circle me-2"></i> Add Product
                </a>
                
                <div class="sidebar-heading mt-3 px-3 text-white-50">CUSTOMER MANAGEMENT</div>
                <a href="/admin/customers" class="list-group-item list-group-item-action bg-dark text-white <?= $this->section('active_customers') ? 'active' : '' ?>">
                    <i class="fas fa-users me-2"></i> Customers
                </a>
                <a href="/admin/customers/create" class="list-group-item list-group-item-action bg-dark text-white <?= $this->section('active_customers_create') ? 'active' : '' ?>">
                    <i class="fas fa-user-plus me-2"></i> Add Customer
                </a>
                
                <div class="sidebar-heading mt-3 px-3 text-white-50">REPORTS</div>
                <a href="/admin/reports/loans" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fas fa-file-invoice-dollar me-2"></i> Loan Reports
                </a>
                <a href="/admin/reports/payments" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fas fa-money-bill-wave me-2"></i> Payment Reports
                </a>
                
                <div class="sidebar-heading mt-3 px-3 text-white-50">SETTINGS</div>
                <a href="/admin/settings" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fas fa-cog me-2"></i> System Settings
                </a>
                <a href="/admin/users" class="list-group-item list-group-item-action bg-dark text-white">
                    <i class="fas fa-users-cog me-2"></i> Users & Permissions
                </a>
            </div>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-link" id="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <div class="d-flex align-items-center">
                        <!-- Notifications Dropdown -->
                        <div class="dropdown me-3">
                            <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                    3
                                    <span class="visually-hidden">unread notifications</span>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                                <li><h6 class="dropdown-header">Notifications</h6></li>
                                <li><a class="dropdown-item" href="#">New loan application received</a></li>
                                <li><a class="dropdown-item" href="#">Payment received from John Doe</a></li>
                                <li><a class="dropdown-item" href="#">System update available</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="#">View all notifications</a></li>
                            </ul>
                        </div>
                        
                        <!-- User Dropdown -->
                        <div class="dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="https://ui-avatars.com/api/?name=<?= urlencode($user['name'] ?? 'Admin') ?>" class="rounded-circle me-2" width="32" height="32" alt="User">
                                <span class="d-none d-md-inline"><?= htmlspecialchars($user['name'] ?? 'Admin') ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="/admin/profile"><i class="fas fa-user me-2"></i> Profile</a></li>
                                <li><a class="dropdown-item" href="/admin/settings"><i class="fas fa-cog me-2"></i> Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid px-4 py-3">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0"><?= $this->section('title', 'Dashboard') ?></h1>
                    <div>
                        <?= $this->section('header_actions') ?>
                    </div>
                </div>
                
                <!-- Flash Messages -->
                <?php if (isset($flash_messages)): ?>
                    <?php foreach ($flash_messages as $message): ?>
                        <div class="alert alert-<?= $message['type'] ?> alert-dismissible fade show" role="alert">
                            <?= $message['message'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <!-- Page Content -->
                <?= $this->section('content') ?>
            </div>
            <!-- /.container-fluid -->
            
            <!-- Footer -->
            <footer class="footer mt-auto py-3 bg-light border-top">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">© <?= date('Y') ?> Nesrah Loan Management System. All rights reserved.</span>
                        <div>
                            <span class="text-muted me-3">v1.0.0</span>
                            <a href="#" class="text-muted me-3"><i class="fas fa-question-circle"></i> Help</a>
                            <a href="#" class="text-muted"><i class="fas fa-bug"></i> Report an Issue</a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
        <!-- /#page-content-wrapper -->
    </div>
    <!-- /#wrapper -->

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <!-- Custom Scripts -->
    <script>
        // Toggle sidebar
        document.addEventListener('DOMContentLoaded', function() {
            const menuToggle = document.getElementById('menu-toggle');
            const wrapper = document.getElementById('wrapper');
            
            if (menuToggle) {
                menuToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    wrapper.classList.toggle('toggled');
                });
            }
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);
        });
        
        // Initialize tooltips
        document.addEventListener('DOMContentLoaded', function() {
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
    
    <?php if (isset($scripts)): ?>
        <?php foreach ($scripts as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <?= $this->section('scripts') ?>
</body>
</html>
