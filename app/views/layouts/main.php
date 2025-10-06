<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Loan Management System' ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="/nesrah/public/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div class="bg-dark text-white" id="sidebar-wrapper">
            <div class="sidebar-heading p-3">
                <h4>Loan System</h4>
            </div>
            <div class="list-group list-group-flush">
                <a href="/nesrah/public/dashboard" class="list-group-item list-group-item-action bg-dark text-white <?= ($activeMenu ?? '') === 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                </a>
                <a href="/nesrah/public/loans" class="list-group-item list-group-item-action bg-dark text-white <?= ($activeMenu ?? '') === 'loans' ? 'active' : '' ?>">
                    <i class="fas fa-hand-holding-usd me-2"></i> Loans
                </a>
                <a href="/nesrah/public/payments" class="list-group-item list-group-item-action bg-dark text-white <?= ($activeMenu ?? '') === 'payments' ? 'active' : '' ?>">
                    <i class="fas fa-money-bill-wave me-2"></i> Payments
                </a>
                <a href="/nesrah/public/customers" class="list-group-item list-group-item-action bg-dark text-white <?= ($activeMenu ?? '') === 'customers' ? 'active' : '' ?>">
                    <i class="fas fa-users me-2"></i> Customers
                </a>
                <a href="/nesrah/public/reports" class="list-group-item list-group-item-action bg-dark text-white <?= ($activeMenu ?? '') === 'reports' ? 'active' : '' ?>">
                    <i class="fas fa-chart-bar me-2"></i> Reports
                </a>
                <a href="/nesrah/public/settings" class="list-group-item list-group-item-action bg-dark text-white <?= ($activeMenu ?? '') === 'settings' ? 'active' : '' ?>">
                    <i class="fas fa-cog me-2"></i> Settings
                </a>
            </div>
        </div>

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <!-- Top Navigation -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <div class="container-fluid">
                    <button class="btn btn-link" id="menu-toggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="dropdown ms-auto">
                        <button class="btn btn-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i> <?= $_SESSION['username'] ?? 'User' ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="/nesrah/public/profile"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/nesrah/public/logout"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </nav>

            <!-- Main Content -->
            <div class="container-fluid p-4">
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> alert-dismissible fade show" role="alert">
                        <?= $_SESSION['flash_message'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php 
                    unset($_SESSION['flash_message']);
                    unset($_SESSION['flash_type']);
                    ?>
                <?php endif; ?>

                <?php include $content; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JS -->
    <script src="/nesrah/public/assets/js/app.js"></script>
</body>
</html>
