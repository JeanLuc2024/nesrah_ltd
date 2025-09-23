<?php
require_once __DIR__ . '/../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('auth/login.php');
}

// Get user information
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;
$user_name = $_SESSION['user_name'] ?? 'User';

// Get system statistics for admin
$stats = array(
    'total_employees' => 0,
    'pending_approvals' => 0,
    'total_items' => 0,
    'low_stock_items' => 0,
    'pending_requests' => 0,
    'sales_today_count' => 0,
    'sales_today_amount' => 0,
    'my_allocations' => 0,
    'my_tasks' => 0,
    'my_sales_today_count' => 0,
    'my_sales_today_amount' => 0,
    'attendance_today' => null
);
if (isAdmin()) {
    // Total employees
    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'employee'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_employees'] = $stmt->fetch()['total'] ?? 0;
    
    // Pending approvals
    $query = "SELECT COUNT(*) as total FROM users WHERE status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending_approvals'] = $stmt->fetch()['total'] ?? 0;
    
    // Total inventory items
    $query = "SELECT COUNT(*) as total FROM inventory";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['total_items'] = $stmt->fetch()['total'] ?? 0;
    
    // Low stock items
    $query = "SELECT COUNT(*) as total FROM inventory WHERE current_stock <= reorder_level";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['low_stock_items'] = $stmt->fetch()['total'] ?? 0;
    
    // Pending stock requests
    $query = "SELECT COUNT(*) as total FROM stock_requests WHERE status = 'pending'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $stats['pending_requests'] = $stmt->fetch()['total'] ?? 0;
    
    // Total sales today
    $query = "SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as amount FROM sales WHERE DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $sales_today = $stmt->fetch();
    $stats['sales_today_count'] = $sales_today['total'] ?? 0;
    $stats['sales_today_amount'] = $sales_today['amount'] ?? 0;
}

// Get employee statistics
if (isEmployee()) {
    // My allocated stock
    $query = "SELECT COUNT(*) as total FROM stock_allocations WHERE user_id = :user_id AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $stats['my_allocations'] = $stmt->fetch()['total'] ?? 0;
    
    // My pending tasks
    $query = "SELECT COUNT(*) as total FROM tasks WHERE assigned_to = :user_id AND status IN ('pending', 'in_progress')";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $stats['my_tasks'] = $stmt->fetch()['total'] ?? 0;
    
    // My sales today
    $query = "SELECT COUNT(*) as total, COALESCE(SUM(total_amount), 0) as amount FROM sales WHERE user_id = :user_id AND DATE(created_at) = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $sales_today = $stmt->fetch();
    $stats['my_sales_today_count'] = $sales_today['total'] ?? 0;
    $stats['my_sales_today_amount'] = $sales_today['amount'] ?? 0;
    
    // My attendance today
    $query = "SELECT * FROM attendance WHERE user_id = :user_id AND work_date = CURDATE()";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $stats['attendance_today'] = $stmt->fetch() ?: null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo SITE_NAME; ?></title>
    <!-- Favicon reference removed: file not found -->
    <link rel="stylesheet" href="css/bootstrap.min.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="css/responsive.css" />
    <link rel="stylesheet" href="css/custom.css" />
    <link rel="stylesheet" href="css/font-awesome.min.css" />
    <style>
        /* Responsive improvements */
        @media (max-width: 768px) {
            .logo_section h3 {
                font-size: 1.2rem !important;
            }
            .logo_section small {
                font-size: 0.7rem !important;
            }
            .name_user {
                display: none !important;
            }
            .user_profile_dd .dropdown-toggle {
                padding: 5px !important;
            }
            .icon_info ul li {
                margin: 0 5px !important;
            }
            .icon_info ul li a {
                padding: 8px !important;
            }
        }
        
        @media (max-width: 480px) {
            .logo_section h3 {
                font-size: 1rem !important;
            }
            .logo_section small {
                font-size: 0.6rem !important;
            }
            .icon_info ul li a i {
                font-size: 0.9rem !important;
            }
        }
        
        /* Improve text visibility */
        .logo_section h3 {
            text-shadow: 2px 2px 4px rgba(0,0,0,0.7) !important;
            font-weight: 800 !important;
        }
        
        .logo_section small {
            text-shadow: 1px 1px 2px rgba(0,0,0,0.7) !important;
        }
    </style>
</head>
<body class="dashboard dashboard_1">
    <div class="full_container">
        <div class="inner_container">
            <!-- Sidebar -->
            <nav id="sidebar">
                <div class="sidebar_blog_1">
                    <div class="sidebar-header">
                        <!-- Logo removed as requested -->
                    </div>
                    <div class="sidebar_user_info">
                        <div class="icon_setting"></div>
                        <div class="user_profle_side">
                            <div class="user_img" style="background: #007bff; border-radius: 50%; width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; margin: 0 auto 10px;">
                                <i class="fa fa-user" style="color: white; font-size: 1.5rem;"></i>
                            </div>
                            <div class="user_info" style="text-align: center;">
                                <h6><?php echo $user_name; ?></h6>
                                <p><span class="online_animation"></span> <?php echo ucfirst($user_role ?? 'user'); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="sidebar_blog_2">
                    <h4>Navigation</h4>
                    <ul class="list-unstyled components">
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                            <a href="dashboard.php"><i class="fa fa-dashboard yellow_color"></i> <span>Dashboard</span></a>
                        </li>
                        
                        <?php if (isAdmin()): ?>
                        <!-- Admin Menu -->
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'employees.php' ? 'active' : ''; ?>">
                            <a href="employees.php"><i class="fa fa-users blue1_color"></i> <span>Employees</span></a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                            <a href="inventory.php"><i class="fa fa-cubes green_color"></i> <span>Inventory</span></a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'stock_allocations.php' ? 'active' : ''; ?>">
                            <a href="stock_allocations.php"><i class="fa fa-share-alt orange_color"></i> <span>Stock Allocations</span></a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'stock_requests.php' ? 'active' : ''; ?>">
                            <a href="stock_requests.php"><i class="fa fa-hand-paper-o red_color"></i> <span>Stock Requests</span></a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'tasks.php' ? 'active' : ''; ?>">
                            <a href="tasks.php"><i class="fa fa-tasks purple_color"></i> <span>Tasks</span></a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>">
                            <a href="sales.php"><i class="fa fa-shopping-cart blue2_color"></i> <span>Sales</span></a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                            <a href="reports.php"><i class="fa fa-bar-chart-o green_color"></i> <span>Reports</span></a>
                        </li>
                        <?php else: ?>
                        <!-- Employee Menu -->
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'attendance.php' ? 'active' : ''; ?>">
                            <a href="attendance.php"><i class="fa fa-clock-o blue1_color"></i> <span>Attendance</span></a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_tasks.php' ? 'active' : ''; ?>">
                            <a href="my_tasks.php"><i class="fa fa-tasks purple_color"></i> <span>My Tasks</span></a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_stock.php' ? 'active' : ''; ?>">
                            <a href="my_stock.php"><i class="fa fa-cubes green_color"></i> <span>My Stock</span></a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'request_stock.php' ? 'active' : ''; ?>">
                            <a href="request_stock.php"><i class="fa fa-hand-paper-o red_color"></i> <span>Request Stock</span></a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'record_sales.php' ? 'active' : ''; ?>">
                            <a href="record_sales.php"><i class="fa fa-shopping-cart blue2_color"></i> <span>Record Sales</span></a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'my_sales.php' ? 'active' : ''; ?>">
                            <a href="my_sales.php"><i class="fa fa-chart-line green_color"></i> <span>My Sales</span></a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : ''; ?>">
                            <a href="profile.php"><i class="fa fa-user yellow_color"></i> <span>Profile</span></a>
                        </li>
                        <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                            <a href="settings.php"><i class="fa fa-cog yellow_color"></i> <span>Settings</span></a>
                        </li>
                    </ul>
                </div>
            </nav>
            <!-- end sidebar -->
            
            <!-- right content -->
            <div id="content">
                <!-- topbar -->
                <div class="topbar">
                    <nav class="navbar navbar-expand-lg navbar-light">
                        <div class="full">
                            <button type="button" id="sidebarCollapse" class="sidebar_toggle"><i class="fa fa-bars"></i></button>
                            <!-- Logo removed as requested -->
                            <div class="right_topbar">
                                <div class="icon_info">
                                    <ul>
                                        <?php if (isAdmin()): ?>
                                        <li><a href="stock_requests.php"><i class="fa fa-hand-paper-o"></i><span class="badge"><?php echo $stats['pending_requests']; ?></span></a></li>
                                        <li><a href="employees.php"><i class="fa fa-users"></i><span class="badge"><?php echo $stats['pending_approvals']; ?></span></a></li>
                                        <?php endif; ?>
                                        <li><a href="auth/logout.php"><i class="fa fa-sign-out"></i></a></li>
                                    </ul>
                                    <ul class="user_profile_dd">
                                        <li>
                                            <a class="dropdown-toggle" data-toggle="dropdown" style="display: flex; align-items: center; text-decoration: none; color: white;">
                                                <div style="background: #007bff; border-radius: 50%; width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                                    <i class="fa fa-user" style="color: white; font-size: 1rem;"></i>
                                                </div>
                                                <span class="name_user"><?php echo $user_name; ?></span>
                                            </a>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="profile.php">My Profile</a>
                                                <a class="dropdown-item" href="settings.php">Settings</a>
                                                <a class="dropdown-item" href="auth/logout.php">
                                                    <span>Log Out</span> <i class="fa fa-sign-out"></i>
                                                </a>
                                            </div>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </nav>
                </div>
                <!-- end topbar -->
                
                <!-- dashboard inner -->
                <div class="midde_cont">
                    <div class="container-fluid">
