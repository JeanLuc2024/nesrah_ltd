<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is admin
if (!isAdmin()) {
    redirect('dashboard.php');
}

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Sales Report
$query = "SELECT 
            COUNT(*) as total_sales,
            COALESCE(SUM(total_amount), 0) as total_revenue,
            COALESCE(AVG(total_amount), 0) as average_sale,
            SUM(quantity) as total_quantity_sold
          FROM sales 
          WHERE DATE(created_at) BETWEEN :start_date AND :end_date";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$sales_report = $stmt->fetch();

// Top Selling Items Report
$query = "SELECT i.item_name, i.item_code, SUM(s.quantity) as total_quantity, SUM(s.total_amount) as total_revenue
          FROM sales s 
          JOIN inventory i ON s.item_id = i.id 
          WHERE DATE(s.created_at) BETWEEN :start_date AND :end_date
          GROUP BY s.item_id, i.item_name, i.item_code 
          ORDER BY total_quantity DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$top_items = $stmt->fetchAll();

// Employee Performance Report
$query = "SELECT u.first_name, u.last_name, COUNT(s.id) as total_sales, SUM(s.total_amount) as total_revenue
          FROM sales s
          JOIN users u ON s.user_id = u.id
          WHERE DATE(s.created_at) BETWEEN :start_date AND :end_date
          GROUP BY s.user_id, u.first_name, u.last_name
          ORDER BY total_revenue DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$employee_performance = $stmt->fetchAll();

// Stock Movement Report
$query = "SELECT
            sh.*,
            i.item_name,
            i.item_code,
            u.first_name,
            u.last_name,
            s.customer_name,
            s.customer_phone
          FROM stock_history sh
          JOIN inventory i ON sh.item_id = i.id
          LEFT JOIN users u ON sh.created_by = u.id
          LEFT JOIN sales s ON sh.reference_id = s.id
          WHERE DATE(sh.created_at) BETWEEN :start_date AND :end_date
          ORDER BY sh.created_at DESC LIMIT 50";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$stock_movements = $stmt->fetchAll();

// Task Completion Report
$query = "SELECT 
            COUNT(*) as total_tasks,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
            SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks
          FROM tasks 
          WHERE DATE(created_at) BETWEEN :start_date AND :end_date";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$task_report = $stmt->fetch();

// Attendance Report
$query = "SELECT 
            COUNT(*) as total_attendance,
            SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days,
            SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
            SUM(total_hours) as total_hours_worked
          FROM attendance 
          WHERE DATE(work_date) BETWEEN :start_date AND :end_date";
$stmt = $db->prepare($query);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$attendance_report = $stmt->fetch();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>Reports & Analytics</h2>
        </div>
    </div>
</div>

<!-- Date Range Filter -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Select Date Range</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <form method="GET" class="form-inline">
                            <div class="form-group mr-3">
                                <label for="start_date" class="mr-2">From:</label>
                                <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo $start_date; ?>" required>
                            </div>
                            <div class="form-group mr-3">
                                <label for="end_date" class="mr-2">To:</label>
                                <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo $end_date; ?>" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Generate Report</button>
                            <button type="button" class="btn btn-success ml-2" onclick="printStockMovement()">
                                <i class="fa fa-print"></i> Print Report
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row column1">
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div>
                    <i class="fa fa-shopping-cart yellow_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $sales_report['total_sales'] ?? 0; ?></p>
                    <p class="head_couter">Total Sales</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div>
                    <i class="fa fa-dollar green_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo formatCurrency($sales_report['total_revenue'] ?? 0); ?></p>
                    <p class="head_couter">Total Revenue</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div>
                    <i class="fa fa-tasks blue1_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo ($task_report['completed_tasks'] ?? 0) . '/' . ($task_report['total_tasks'] ?? 0); ?></p>
                    <p class="head_couter">Tasks Completed</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div>
                    <i class="fa fa-clock-o red_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $attendance_report['total_hours_worked'] !== null ? number_format((float)$attendance_report['total_hours_worked'], 1) : '0.0'; ?></p>
                    <p class="head_couter">Hours Worked</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Top Selling Items -->
<div class="row">
    <div class="col-md-6">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Top Selling Items</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <?php if (count($top_items) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Quantity Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_items as $item): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo $item['item_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $item['item_code']; ?></small>
                                                </td>
                                                <td><?php echo $item['total_quantity']; ?></td>
                                                <td><?php echo formatCurrency($item['total_revenue']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No sales data available for the selected period</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Employee Performance -->
    <div class="col-md-6">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Employee Performance</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <?php if (count($employee_performance) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Employee</th>
                                            <th>Sales Count</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($employee_performance as $employee): ?>
                                            <tr>
                                                <td><?php echo $employee['first_name'] . ' ' . $employee['last_name']; ?></td>
                                                <td><?php echo $employee['total_sales']; ?></td>
                                                <td><?php echo formatCurrency($employee['total_revenue']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No performance data available for the selected period</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stock Movements -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Stock Movement History</h2>
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
                                            <th>Item</th>
                                            <th>Movement Type</th>
                                            <th>Quantity</th>
                                            <th>Previous Stock</th>
                                            <th>New Stock</th>
                                            <th>User</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (count($stock_movements) > 0): ?>
                                        <?php foreach ($stock_movements as $movement): ?>
                                            <tr>
                                                <td><?php echo formatDateTime($movement['created_at']); ?></td>
                                                <td>
                                                    <strong><?php echo $movement['item_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $movement['item_code']; ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge badge-<?php 
                                                        echo $movement['movement_type'] === 'in' ? 'success' : 
                                                            ($movement['movement_type'] === 'out' ? 'danger' : 
                                                            ($movement['movement_type'] === 'allocation' ? 'info' : 'warning')); 
                                                    ?>">
                                                        <?php echo ucfirst($movement['movement_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $movement['quantity']; ?></td>
                                                <td><?php echo $movement['previous_stock']; ?></td>
                                                <td><?php echo $movement['new_stock']; ?></td>
                                                <td>
                                                    <?php 
                                                    if (!empty($movement['customer_name'])) {
                                                        echo htmlspecialchars($movement['customer_name']);
                                                        if (!empty($movement['customer_phone'])) {
                                                            echo '<br><small class="text-muted">' . htmlspecialchars($movement['customer_phone']) . '</small>';
                                                        }
                                                    } else {
                                                        echo !empty($movement['first_name']) ? 
                                                            htmlspecialchars($movement['first_name'] . ' ' . $movement['last_name']) : 
                                                            'System';
                                                    }
                                                    ?>
                                                </td>
                                                <td><?php echo $movement['notes'] ?: 'N/A'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted">No stock movements found for the selected period</td>
                                        </tr>
                                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    /* Hide everything except the print content */
    * {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }

    /* Show the print header */
    .print-header,
    .print-header * {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    /* Show the Stock Movement History section */
    .row:has(.white_shd),
    .row:has(.white_shd) *,
    .row:has(.white_shd) .heading1 h2,
    .row:has(.white_shd) .table-responsive,
    .row:has(.white_shd) table,
    .row:has(.white_shd) thead,
    .row:has(.white_shd) tbody,
    .row:has(.white_shd) tr,
    .row:has(.white_shd) th,
    .row:has(.white_shd) td,
    .row:has(.white_shd) .badge,
    .row:has(.white_shd) .text-muted,
    .row:has(.white_shd) strong,
    .row:has(.white_shd) small {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    /* Reset body styles */
    body {
        margin: 0 !important;
        padding: 20px !important;
        font-family: Arial, sans-serif !important;
        font-size: 12px !important;
        line-height: 1.4 !important;
        color: #000 !important;
        background: white !important;
    }

    /* Style the print header */
    .print-header {
        text-align: center !important;
        margin-bottom: 20px !important;
        border-bottom: 2px solid #000 !important;
        padding-bottom: 10px !important;
    }

    .print-header h1 {
        margin: 0 0 5px 0 !important;
        font-size: 24px !important;
        color: #000 !important;
        font-weight: bold !important;
    }

    .print-header p {
        margin: 0 !important;
        font-size: 12px !important;
        color: #666 !important;
    }

    /* Style the Stock Movement History section */
    .row:has(.white_shd) {
        margin: 0 !important;
        padding: 0 !important;
        page-break-inside: avoid !important;
    }

    /* Style the table title */
    .row:has(.white_shd) .heading1 h2 {
        color: #000 !important;
        font-size: 18px !important;
        font-weight: bold !important;
        margin-bottom: 15px !important;
        text-align: center !important;
        border-bottom: 1px solid #000 !important;
        padding-bottom: 5px !important;
    }

    /* Style the table */
    .row:has(.white_shd) table {
        border-collapse: collapse !important;
        width: 100% !important;
        margin: 0 !important;
        page-break-inside: avoid !important;
    }

    /* Style table headers */
    .row:has(.white_shd) thead th {
        border: 1px solid #000 !important;
        padding: 8px !important;
        background-color: #f5f5f5 !important;
        font-weight: bold !important;
        color: #000 !important;
        text-align: left !important;
        vertical-align: top !important;
        font-size: 11px !important;
    }

    /* Style table body cells */
    .row:has(.white_shd) tbody td {
        border: 1px solid #000 !important;
        padding: 6px !important;
        text-align: left !important;
        vertical-align: top !important;
        font-size: 10px !important;
        color: #000 !important;
        word-wrap: break-word !important;
    }

    /* Style badges */
    .row:has(.white_shd) .badge {
        display: inline-block !important;
        background-color: #000 !important;
        color: #fff !important;
        padding: 2px 6px !important;
        border-radius: 3px !important;
        font-size: 9px !important;
        font-weight: normal !important;
        margin: 0 !important;
    }

    /* Style text-muted elements */
    .row:has(.white_shd) .text-muted {
        color: #666 !important;
        font-size: 9px !important;
    }

    /* Style strong elements */
    .row:has(.white_shd) strong {
        font-weight: bold !important;
        color: #000 !important;
    }

    /* Style small elements */
    .row:has(.white_shd) small {
        font-size: 8px !important;
        color: #666 !important;
    }

    /* Hide line breaks for cleaner display */
    .row:has(.white_shd) br {
        display: none !important;
    }

    /* Ensure table rows are visible */
    .row:has(.white_shd) tbody tr {
        display: table-row !important;
        page-break-inside: avoid !important;
    }

    /* Ensure table cells are visible */
    .row:has(.white_shd) tbody tr td {
        display: table-cell !important;
    }
}

.print-header {
    display: none;
}

@media print {
    .print-header {
        display: block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }
}
</style>

<div class="print-header">
    <h1>NESRAH GROUP Management System</h1>
    <p>Reports & Analytics - <?php echo date('F d, Y', strtotime($start_date)) . ' to ' . date('F d, Y', strtotime($end_date)); ?></p>
    <p>Generated on: <?php echo date('F d, Y g:i A'); ?></p>
</div>

<script>
function printStockMovement() {
    // Create a new window for printing
    const printWindow = window.open('', '_blank', 'width=800,height=600');

    // Get the content to print
    const printHeader = document.querySelector('.print-header').outerHTML;
    const tableTitle = '<h2 style="text-align: center; margin: 20px 0; border-bottom: 1px solid #000; padding-bottom: 5px;">Stock Movement History</h2>';

    // Get the table content
    const tableElement = document.querySelector('.row:has(.white_shd) table');
    let tableHTML = '';

    if (tableElement) {
        // Clone the table and clean it up for printing
        const clonedTable = tableElement.cloneNode(true);

        // Remove Bootstrap classes and add print-friendly styles
        clonedTable.className = '';
        clonedTable.style.width = '100%';
        clonedTable.style.borderCollapse = 'collapse';
        clonedTable.style.margin = '0';

        // Style table headers
        const headers = clonedTable.querySelectorAll('th');
        headers.forEach(th => {
            th.style.border = '1px solid #000';
            th.style.padding = '8px';
            th.style.backgroundColor = '#f5f5f5';
            th.style.fontWeight = 'bold';
            th.style.color = '#000';
            th.style.textAlign = 'left';
            th.style.verticalAlign = 'top';
            th.style.fontSize = '11px';
        });

        // Style table cells
        const cells = clonedTable.querySelectorAll('td');
        cells.forEach(td => {
            td.style.border = '1px solid #000';
            td.style.padding = '6px';
            td.style.textAlign = 'left';
            td.style.verticalAlign = 'top';
            td.style.fontSize = '10px';
            td.style.color = '#000';
        });

        // Style badges
        const badges = clonedTable.querySelectorAll('.badge');
        badges.forEach(badge => {
            badge.style.display = 'inline-block';
            badge.style.backgroundColor = '#000';
            badge.style.color = '#fff';
            badge.style.padding = '2px 6px';
            badge.style.borderRadius = '3px';
            badge.style.fontSize = '9px';
            badge.style.fontWeight = 'normal';
        });

        // Style text-muted elements
        const mutedElements = clonedTable.querySelectorAll('.text-muted');
        mutedElements.forEach(el => {
            el.style.color = '#666';
            el.style.fontSize = '9px';
        });

        // Style strong elements
        const strongElements = clonedTable.querySelectorAll('strong');
        strongElements.forEach(el => {
            el.style.fontWeight = 'bold';
            el.style.color = '#000';
        });

        // Style small elements
        const smallElements = clonedTable.querySelectorAll('small');
        smallElements.forEach(el => {
            el.style.fontSize = '8px';
            el.style.color = '#666';
        });

        // Remove line breaks
        const brElements = clonedTable.querySelectorAll('br');
        brElements.forEach(br => br.remove());

        tableHTML = clonedTable.outerHTML;
    } else {
        tableHTML = '<p style="text-align: center; color: #666;">No stock movements found for the selected period</p>';
    }

    // Create the print document
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Stock Movement History Report</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    font-size: 12px;
                    line-height: 1.4;
                    color: #000;
                    margin: 20px;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                }
                th, td {
                    border: 1px solid #000;
                    padding: 6px 8px;
                    text-align: left;
                    vertical-align: top;
                }
                th {
                    background-color: #f5f5f5;
                    font-weight: bold;
                }
                .badge {
                    display: inline-block;
                    background-color: #000;
                    color: #fff;
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-size: 9px;
                }
                .text-muted {
                    color: #666;
                    font-size: 9px;
                }
                strong {
                    font-weight: bold;
                }
                small {
                    font-size: 8px;
                    color: #666;
                }
                h1 {
                    text-align: center;
                    margin: 0 0 5px 0;
                    font-size: 24px;
                    color: #000;
                }
                h2 {
                    text-align: center;
                    margin: 20px 0;
                    border-bottom: 1px solid #000;
                    padding-bottom: 5px;
                    font-size: 18px;
                }
                p {
                    margin: 5px 0;
                    font-size: 12px;
                    color: #666;
                }
                .header-info {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 2px solid #000;
                    padding-bottom: 10px;
                }
            </style>
        </head>
        <body>
            <div class="header-info">
                ${printHeader}
            </div>
            ${tableTitle}
            ${tableHTML}
        </body>
        </html>
    `;

    // Write content to the print window
    printWindow.document.open();
    printWindow.document.write(printContent);
    printWindow.document.close();

    // Wait for content to load then print
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
