<?php

require_once __DIR__ . '/../config/config.php';

// Helper function to get system setting value
function getSetting($key) {
    global $db;
    $stmt = $db->prepare("SELECT setting_value FROM system_settings WHERE setting_key = :key LIMIT 1");
    $stmt->bindParam(':key', $key);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : '';
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error_message = '';

if ($_POST) {
    $username = sanitizeInput($_POST['username']);
    $password = $_POST['password'];
    $login_as = $_POST['login_as'] ?? '';
    
    if (empty($username) || empty($password) || empty($login_as)) {
        $error_message = 'Please fill in all fields.';
    } else {
        $query = "SELECT id, username, email, password, first_name, last_name, role, status FROM users WHERE (username = :username OR email = :username) AND role = :role";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':role', $login_as);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            if (password_verify($password, $user['password'])) {
                if ($user['status'] === STATUS_ACTIVE) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    redirect('dashboard.php');
                } elseif ($user['status'] === 'pending') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    redirect('dashboard.php?pending=1');
                } else {
                    $error_message = 'Your account is not active. Please contact administrator.';
                }
            } else {
                $error_message = 'Invalid username or password.';
            }
        } else {
            $error_message = 'Invalid credentials or role selection.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo SITE_NAME; ?> - Login</title>
    <!-- Favicon reference removed: file not found -->
    <link rel="stylesheet" href="../css/bootstrap.min.css" />
    <link rel="stylesheet" href="../style.css" />
    <link rel="stylesheet" href="../css/responsive.css" />
    <link rel="stylesheet" href="../css/custom.css" />
</head>
<body class="inner_page login">
    <div class="full_container">
        <div class="container">
            <div class="center verticle_center full_height">
                <div class="login_section">
                    <div class="company-info text-center mb-4" style="color: #fff;">
                        <h4><?php echo getSetting('company_name'); ?></h4>
                        <p><?php echo getSetting('company_address'); ?> | <?php echo getSetting('company_phone'); ?> | <?php echo getSetting('company_email'); ?></p>
                    </div>
                    <div class="logo_login">
                        <div class="center">
                            <!-- Pluto logo removed -->
                            <h2 style="color: #fff; margin-top: 20px; background: #007bff; padding: 10px; border-radius: 5px; font-size: 2rem;">NESRAH GROUP Management System</h2>
                        </div>
                    </div>
                    <div class="login_form">
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <fieldset>
                                <div class="field">
                                    <label class="label_field">Username or Email</label>
                                    <input type="text" name="username" placeholder="Enter username or email" required />
                                </div>
                                <div class="field">
                                    <label class="label_field">Password</label>
                                    <input type="password" name="password" placeholder="Enter password" required />
                                </div>
                                <div class="field">
                                    <label class="label_field">Login As</label>
                                    <select name="login_as" class="form-control" required>
                                        <option value="">Select Role</option>
                                        <option value="admin">Administrator</option>
                                        <option value="employee">Employee</option>
                                    </select>
                                </div>
                                <div class="field">
                                    <label class="label_field hidden">hidden label</label>
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input"> Remember Me
                                    </label>
                                </div>
                                <div class="field margin_0">
                                    <label class="label_field hidden">hidden label</label>
                                    <button class="main_bt" type="submit">Sign In</button>
                                </div>
                            </fieldset>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Don't have an account? <a href="register.php">Register here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../js/jquery.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="../js/custom.js"></script>
</body>
</html>
