<?php
require_once __DIR__ . '/includes/header.php';

$success_message = '';
$error_message = '';

// Handle settings updates
if ($_POST) {
    if (isAdmin()) {
        // Admin can update system settings
        $setting_key = $_POST['setting_key'];
        $setting_value = sanitizeInput($_POST['setting_value']);
        
        $query = "UPDATE system_settings SET setting_value = :setting_value WHERE setting_key = :setting_key";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':setting_value', $setting_value);
        $stmt->bindParam(':setting_key', $setting_key);
        
        if ($stmt->execute()) {
            $success_message = 'Setting updated successfully.';
        } else {
            $error_message = 'Failed to update setting.';
        }
    } else {
        // Employee can only update their profile
        $first_name = sanitizeInput($_POST['first_name']);
        $last_name = sanitizeInput($_POST['last_name']);
        $email = sanitizeInput($_POST['email']);
        $phone = sanitizeInput($_POST['phone']);
        $address = sanitizeInput($_POST['address']);
        
        $query = "UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, phone = :phone, address = :address WHERE id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':first_name', $first_name);
        $stmt->bindParam(':last_name', $last_name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':phone', $phone);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $success_message = 'Profile updated successfully.';
        } else {
            $error_message = 'Failed to update profile.';
        }
    }
}

// Get settings based on user role
if (isAdmin()) {
    // Get system settings for admin
    $query = "SELECT * FROM system_settings ORDER BY setting_key";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $settings = $stmt->fetchAll();
} else {
    // Get user profile for employee
    $query = "SELECT * FROM users WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $user_profile = $stmt->fetch();
}
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2><?php echo isAdmin() ? 'System Settings' : 'My Settings'; ?></h2>
        </div>
    </div>
</div>

<?php if ($success_message): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php echo $success_message; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php if (isAdmin()): ?>
<!-- System Settings -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Company Information</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <?php foreach ($settings as $setting): ?>
                            <form method="POST" class="mb-3">
                                <input type="hidden" name="setting_key" value="<?php echo $setting['setting_key']; ?>">
                                <div class="row">
                                    <div class="col-md-3">
                                        <label><?php echo ucwords(str_replace('_', ' ', $setting['setting_key'])); ?></label>
                                    </div>
                                    <div class="col-md-6">
                                        <?php if ($setting['setting_key'] === 'mission' || $setting['setting_key'] === 'vision'): ?>
                                            <textarea name="setting_value" class="form-control" rows="3"><?php echo $setting['setting_value']; ?></textarea>
                                        <?php else: ?>
                                            <input type="text" name="setting_value" class="form-control" value="<?php echo $setting['setting_value']; ?>">
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-3">
                                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                                    </div>
                                </div>
                            </form>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php else: ?>
<!-- Employee Profile Settings -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>My Profile</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="first_name">First Name</label>
                                        <input type="text" name="first_name" id="first_name" class="form-control" value="<?php echo $user_profile['first_name']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="last_name">Last Name</label>
                                        <input type="text" name="last_name" id="last_name" class="form-control" value="<?php echo $user_profile['last_name']; ?>" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="email">Email</label>
                                        <input type="email" name="email" id="email" class="form-control" value="<?php echo $user_profile['email']; ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="phone">Phone</label>
                                        <input type="text" name="phone" id="phone" class="form-control" value="<?php echo $user_profile['phone']; ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label for="address">Address</label>
                                        <textarea name="address" id="address" class="form-control" rows="3"><?php echo $user_profile['address']; ?></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <button type="submit" class="btn btn-primary">Update Profile</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>


<?php require_once __DIR__ . '/includes/footer.php'; ?>
