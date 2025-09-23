<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is employee
if (!isEmployee()) {
    redirect('dashboard.php');
}

$success_message = '';
$error_message = '';

// Handle check-in/check-out
if ($_POST) {
    $action = $_POST['action'];
    $work_date = date('Y-m-d');
    
    if ($action === 'check_in') {
        // Check if already checked in today
        $query = "SELECT id FROM attendance WHERE user_id = :user_id AND work_date = :work_date";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':work_date', $work_date);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $error_message = 'You have already checked in today.';
        } else {
            $query = "INSERT INTO attendance (user_id, check_in, work_date, status) VALUES (:user_id, NOW(), :work_date, 'present')";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':work_date', $work_date);
            
            if ($stmt->execute()) {
                $success_message = 'Successfully checked in!';
            } else {
                $error_message = 'Failed to check in. Please try again.';
            }
        }
    } elseif ($action === 'check_out') {
        // Check if checked in today
        $query = "SELECT id, check_in FROM attendance WHERE user_id = :user_id AND work_date = :work_date AND check_out IS NULL";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':work_date', $work_date);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $attendance = $stmt->fetch();
            $check_in_time = strtotime($attendance['check_in']);
            $check_out_time = time();
            $total_hours = ($check_out_time - $check_in_time) / 3600;
            
            $query = "UPDATE attendance SET check_out = NOW(), total_hours = :total_hours WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':total_hours', $total_hours);
            $stmt->bindParam(':id', $attendance['id']);
            
            if ($stmt->execute()) {
                $success_message = 'Successfully checked out! Total hours: ' . number_format($total_hours, 2);
            } else {
                $error_message = 'Failed to check out. Please try again.';
            }
        } else {
            $error_message = 'You have not checked in today or already checked out.';
        }
    }
}

// Get today's attendance status
$work_date = date('Y-m-d');
$query = "SELECT * FROM attendance WHERE user_id = :user_id AND work_date = :work_date";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':work_date', $work_date);
$stmt->execute();
$today_attendance = $stmt->fetch();

// Get attendance history (last 30 days)
$query = "SELECT * FROM attendance WHERE user_id = :user_id ORDER BY work_date DESC LIMIT 30";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$attendance_history = $stmt->fetchAll();

// Calculate monthly statistics
$current_month = date('Y-m');
$query = "SELECT 
            COUNT(*) as total_days,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(total_hours) as total_hours
          FROM attendance 
          WHERE user_id = :user_id AND DATE_FORMAT(work_date, '%Y-%m') = :current_month";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->bindParam(':current_month', $current_month);
$stmt->execute();
$monthly_stats = $stmt->fetch();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Attendance Management</h2>
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
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error_message; ?>
        <button type="button" class="close" data-dismiss="alert">
            <span>&times;</span>
        </button>
    </div>
<?php endif; ?>

<!-- Today's Attendance Status -->
<div class="row column1">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Today's Attendance - <?php echo date('M d, Y'); ?></h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <div class="text-center">
                            <?php if ($today_attendance): ?>
                                <?php if ($today_attendance['check_out']): ?>
                                    <div class="alert alert-info">
                                        <h4><i class="fa fa-check-circle"></i> Checked Out</h4>
                                        <p><strong>Check In:</strong> <?php echo formatDateTime($today_attendance['check_in']); ?></p>
                                        <p><strong>Check Out:</strong> <?php echo formatDateTime($today_attendance['check_out']); ?></p>
                                        <p><strong>Total Hours:</strong> <?php echo number_format($today_attendance['total_hours'], 2); ?> hours</p>
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-success">
                                        <h4><i class="fa fa-clock-o"></i> Checked In</h4>
                                        <p><strong>Check In Time:</strong> <?php echo formatDateTime($today_attendance['check_in']); ?></p>
                                        <p><strong>Status:</strong> Currently working</p>
                                    </div>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="check_out">
                                        <button type="submit" class="btn btn-danger btn-lg" onclick="return confirm('Are you sure you want to check out?')">
                                            <i class="fa fa-sign-out"></i> Check Out
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <h4><i class="fa fa-exclamation-triangle"></i> Not Checked In</h4>
                                    <p>You have not checked in today. Click the button below to check in.</p>
                                </div>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="check_in">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="fa fa-sign-in"></i> Check In
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Monthly Statistics -->
<div class="row column1">
    <div class="col-md-4">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-calendar yellow_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $monthly_stats['total_days']; ?></p>
                    <p class="head_couter">Total Days</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-check-circle blue1_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $monthly_stats['present_days']; ?></p>
                    <p class="head_couter">Present Days</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-clock-o green_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo number_format($monthly_stats['total_hours'], 1); ?></p>
                    <p class="head_couter">Total Hours</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Attendance History -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Attendance History (Last 30 Days)</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Total Hours</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($attendance_history) > 0): ?>
                                        <?php foreach ($attendance_history as $attendance): ?>
                                            <tr>
                                                <td><?php echo formatDate($attendance['work_date']); ?></td>
                                                <td>
                                                    <?php echo $attendance['check_in'] ? formatDateTime($attendance['check_in']) : 'N/A'; ?>
                                                </td>
                                                <td>
                                                    <?php echo $attendance['check_out'] ? formatDateTime($attendance['check_out']) : 'N/A'; ?>
                                                </td>
                                                <td>
                                                    <?php echo $attendance['total_hours'] ? number_format($attendance['total_hours'], 2) . ' hours' : 'N/A'; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $attendance['status'] === 'present' ? 'success' : 
                                                            ($attendance['status'] === 'late' ? 'warning' : 'danger'); 
                                                    ?>">
                                                        <?php echo ucfirst($attendance['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No attendance records found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
