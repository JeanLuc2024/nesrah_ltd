<?php
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="admin-dashboard.php" class="sidebar-brand">
            <i class="fas fa-hand-holding-usd"></i>
            <span>LoanPro</span>
        </a>
    </div>
    
    <ul class="sidebar-menu">
        <li class="menu-title">Main</li>
        <li class="<?= basename($_SERVER['PHP_SELF']) == 'admin-dashboard.php' ? 'active' : '' ?>">
            <a href="admin-dashboard.php">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        <li class="<?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>">
            <a href="products.php">
                <i class="fas fa-box"></i> Products
            </a>
        </li>
        <li class="<?= basename($_SERVER['PHP_SELF']) == 'loans.php' ? 'active' : '' ?>">
            <a href="loans.php">
                <i class="fas fa-hand-holding-usd"></i> Loans
            </a>
        </li>
        
        <li class="menu-title mt-3">Account</li>
        <li class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
            <a href="settings.php">
                <i class="fas fa-cog"></i> Settings
            </a>
        </li>
        <li>
            <a href="logout.php">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</div>
