<?php
require_once __DIR__ . '/../config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$success_message = '';
$error_message = '';

if ($_POST) {
    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $username = sanitizeInput($_POST['username']);
    $email = sanitizeInput($_POST['email']);
    $phone = sanitizeInput($_POST['phone']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($first_name) || empty($last_name) || empty($username) || empty($email) || empty($password)) {
        $error_message = 'Please fill in all required fields.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        // Check if username or email already exists
        $query = "SELECT id FROM users WHERE username = :username OR email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error_message = 'Username or email already exists.';
        } else {
            // Insert new user
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "INSERT INTO users (first_name, last_name, username, email, phone, password, role, status) 
                     VALUES (:first_name, :last_name, :username, :email, :phone, :password, 'employee', 'pending')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':password', $hashed_password);
            
            if ($stmt->execute()) {
                $success_message = 'Registration successful! Your account is pending approval from administrator.';
            } else {
                $error_message = 'Registration failed. Please try again.';
            }
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
    <title><?php echo SITE_NAME; ?> - Register</title>
    <link rel="icon" href="../images/fevicon.png" type="image/png" />
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
                    <div class="logo_login">
                        <div class="center">
                            <img width="210" src="../images/logo/logo.png" alt="NESRAH GROUP" />
                            <h2 style="color: #333; margin-top: 20px;">NESRAH GROUP</h2>
                            <p style="color: #666;">Employee Registration</p>
                        </div>
                    </div>
                    <div class="login_form">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success" role="alert">
                                <?php echo $success_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo $error_message; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST">
                            <fieldset>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="field">
                                            <label class="label_field">First Name *</label>
                                            <input type="text" name="first_name" placeholder="Enter first name" required style="padding-left: 15px; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 5px; width: 100%; height: 40px;" />
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="field">
                                            <label class="label_field">Last Name *</label>
                                            <input type="text" name="last_name" placeholder="Enter last name" required style="padding-left: 15px; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 5px; width: 100%; height: 40px;" />
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="field">
                                    <label class="label_field">Username *</label>
                                    <input type="text" name="username" placeholder="Enter username" required style="padding-left: 15px; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 5px; width: 100%; height: 40px;" />
                                </div>
                                
                                <div class="field">
                                    <label class="label_field">Email Address *</label>
                                    <input type="email" name="email" placeholder="Enter email address" required style="padding-left: 15px; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 5px; width: 100%; height: 40px;" />
                                </div>
                                
                                <div class="field">
                                    <label class="label_field">Phone Number</label>
                                    <input type="tel" name="phone" placeholder="Enter phone number" style="padding-left: 15px; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 5px; width: 100%; height: 40px;" />
                                </div>
                                
                                <div class="field">
                                    <label class="label_field">Password *</label>
                                    <input type="password" name="password" placeholder="Enter password (min 6 characters)" required style="padding-left: 15px; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 5px; width: 100%; height: 40px;" />
                                </div>
                                
                                <div class="field">
                                    <label class="label_field">Confirm Password *</label>
                                    <input type="password" name="confirm_password" placeholder="Confirm password" required style="padding-left: 15px; color: #333; background: #fff; border: 1px solid #ddd; border-radius: 5px; width: 100%; height: 40px;" />
                                </div>
                                
                                <div class="field margin_0">
                                    <label class="label_field hidden">hidden label</label>
                                    <button class="main_bt" type="submit">Register</button>
                                </div>
                            </fieldset>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Already have an account? <a href="login.php">Login here</a></p>
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
