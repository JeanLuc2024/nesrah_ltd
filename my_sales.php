<?php
require_once __DIR__ . '/includes/header.php';

// Add print stylesheet
echo '<link rel="stylesheet" href="' . SITE_URL . '/css/print.css" media="print">';

// Check if user is employee
if (!isEmployee()) {
    redirect('dashboard.php');
}

// Get filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$date_filter = isset($_GET['date_filter']) ? $_GET['date_filter'] : 'today';

// Get current user ID
$user_id = $_SESSION['user_id'] ?? 0;

// Get current user ID
$user_id = $_SESSION['user_id'] ?? 0;

// Build query based on filters
$where_conditions = ["s.user_id = :user_id"];
$params = [':user_id' => $user_id];

if ($date_filter === 'today') {
    $where_conditions[] = "DATE(s.created_at) = CURDATE()";
} elseif ($date_filter === 'week') {
    $where_conditions[] = "s.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
} elseif ($date_filter === 'month') {
    $where_conditions[] = "s.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

try {
    $query = "SELECT s.*, i.item_name, i.item_code
              FROM sales s 
              JOIN inventory i ON s.item_id = i.id 
              $where_clause 
              ORDER BY s.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $my_sales = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error in my_sales.php: " . $e->getMessage());
    $my_sales = [];
    $error_message = "Error loading sales data. Please try again later.";
}

// Get my sales statistics
$query = "SELECT 
            COUNT(*) as total_sales,
            COALESCE(SUM(total_amount), 0) as total_revenue,
            COALESCE(AVG(total_amount), 0) as average_sale
          FROM sales s $where_clause";
$stmt = $db->prepare($query);
foreach ($params as $key => &$value) {
    $stmt->bindParam($key, $value);
}
$stmt->execute();
$sales_stats = $stmt->fetch();

// Get my top selling items
$query = "SELECT i.item_name, i.item_code, SUM(s.quantity) as total_quantity, 
                 SUM(s.total_amount) as total_revenue
          FROM sales s 
          JOIN inventory i ON s.item_id = i.id 
          $where_clause
          GROUP BY s.item_id, i.item_name, i.item_code 
          ORDER BY total_quantity DESC LIMIT 5";
$stmt = $db->prepare($query);
foreach ($params as $key => &$value) {
    $stmt->bindParam($key, $value);
}
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$my_top_items = $stmt->fetchAll();
?>

<div class="row column_title">
    <div class="col-md-12">
        <div class="page_title">
            <h2>My Sales Performance</h2>
        </div>
    </div>
</div>

<!-- My Sales Statistics -->
<div class="row column1">
    <div class="col-md-4">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-shopping-cart yellow_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo $sales_stats['total_sales']; ?></p>
                    <p class="head_couter">My Sales</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-dollar green_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo formatCurrency($sales_stats['total_revenue']); ?></p>
                    <p class="head_couter">My Revenue</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="full counter_section margin_bottom_30">
            <div class="couter_icon">
                <div> 
                    <i class="fa fa-chart-line blue1_color"></i>
                </div>
            </div>
            <div class="counter_no">
                <div>
                    <p class="total_no"><?php echo formatCurrency($sales_stats['average_sale']); ?></p>
                    <p class="head_couter">Average Sale</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Date Filter -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>Filter by Date</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <div class="btn-group" role="group">
                            <a href="?date_filter=today" class="btn <?php echo $date_filter === 'today' ? 'btn-primary' : 'btn-outline-primary'; ?>">Today</a>
                            <a href="?date_filter=week" class="btn <?php echo $date_filter === 'week' ? 'btn-primary' : 'btn-outline-primary'; ?>">This Week</a>
                            <a href="?date_filter=month" class="btn <?php echo $date_filter === 'month' ? 'btn-primary' : 'btn-outline-primary'; ?>">This Month</a>
                            <a href="?date_filter=all" class="btn <?php echo $date_filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">All Time</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Top Selling Items -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>My Top Selling Items</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <?php if (count($my_top_items) > 0): ?>
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
                                        <?php foreach ($my_top_items as $item): ?>
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
                            <p class="text-muted">No sales data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- My Sales Table -->
<div class="row">
    <div class="col-md-12">
        <div class="white_shd full margin_bottom_30">
            <div class="full graph_head">
                <div class="heading1 margin_0">
                    <h2>My Sales Records (<?php echo count($my_sales); ?> total)</h2>
                </div>
            </div>
            <div class="full graph_revenue">
                <div class="row">
                    <div class="col-md-12">
                        <button class="btn btn-primary mb-3 print-button" onclick="printSalesReport()">
                            <i class="fa fa-print"></i> Print Sales Report
                        </button>
                        
                        <div class="print-header" style="display: none;">
                            <h1>NESRAH GROUP</h1>
                            <div class="company-info">
                                <div>123 Business Street, Kigali, Rwanda</div>
                                <div>Phone: +250 700 000 000 | Email: info@nesrahgroup.com</div>
                            </div>
                            <div class="report-title">SALES REPORT</div>
                            <div class="report-meta">
                                <?php 
                                $date_range = '';
                                if ($date_filter === 'today') {
                                    $date_range = 'For ' . date('F d, Y');
                                } elseif ($date_filter === 'week') {
                                    $date_range = 'For the Week of ' . date('F d, Y', strtotime('-1 week')) . ' to ' . date('F d, Y');
                                } elseif ($date_filter === 'month') {
                                    $date_range = 'For ' . date('F Y');
                                } else {
                                    $date_range = 'All Time';
                                }
                                echo $date_range . ' | Generated on: ' . date('F d, Y h:i A');
                                ?>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date</th>
                                        <th>Item</th>
                                        <th>Customer</th>
                                        <th>Qty</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($my_sales) > 0): ?>
                                        <?php foreach ($my_sales as $sale): ?>
                                            <tr>
                                                <td><?php echo $sale['id']; ?></td>
                                                <td><?php echo formatDateTime($sale['created_at']); ?></td>
                                                <td>
                                                    <strong><?php echo $sale['item_name']; ?></strong>
                                                    <br><small class="text-muted"><?php echo $sale['item_code']; ?></small>
                                                </td>
                                                <td>
                                                    <?php echo $sale['customer_name']; ?>
                                                    <?php if ($sale['customer_phone']): ?>
                                                        <br><small class="text-muted"><?php echo $sale['customer_phone']; ?></small>
                                                    <?php endif; ?>
                                                    <?php if ($sale['customer_email']): ?>
                                                        <br><small class="text-muted"><?php echo $sale['customer_email']; ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo $sale['quantity']; ?></td>
                                                <td><?php echo formatCurrency($sale['unit_price']); ?></td>
                                                <td><strong><?php echo formatCurrency($sale['total_amount']); ?></strong></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?php echo ucfirst(str_replace('_', ' ', $sale['payment_method'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($sale['notes']): ?>
                                                        <?php echo substr($sale['notes'], 0, 50) . (strlen($sale['notes']) > 50 ? '...' : ''); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No notes</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">No sales found</td>
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

<script>
function printSalesReport() {
    // Create a print window
    const printWindow = window.open('', '_blank');
    
    // Get the content to print
    const content = document.documentElement.innerHTML;
    
    // Get the print header and styles
    const printHeader = document.querySelector('.print-header');
    const styles = Array.from(document.styleSheets)
        .map(sheet => {
            try {
                return Array.from(sheet.cssRules || []).map(rule => rule.cssText).join('\n');
            } catch (e) {
                return '';
            }
        })
        .join('\n');
    
    // Create the print content
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Sales Report - NESRAH GROUP</title>
            <style>
                ${styles}
                @page { size: A4; margin: 15mm 10mm; }
                body { font-family: Arial, sans-serif; color: #000; }
                .print-header { display: block !important; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f5f5f5; }
                .no-print { display: none !important; }
                .print-footer { 
                    position: fixed; 
                    bottom: 0; 
                    left: 0; 
                    right: 0; 
                    text-align: center; 
                    font-size: 10px; 
                    color: #666; 
                    border-top: 1px solid #ddd; 
                    padding: 5px 0;
                }
            </style>
        </head>
        <body>
            ${printHeader.outerHTML}
            <div class="content">
                <h3>Sales Summary</h3>
                <table>
                    <tr>
                        <th>Total Sales</th>
                        <td><?php echo $sales_stats['total_sales']; ?></td>
                        <th>Total Revenue</th>
                        <td><?php echo formatCurrency($sales_stats['total_revenue']); ?></td>
                        <th>Average Sale</th>
                        <td><?php echo formatCurrency($sales_stats['average_sale']); ?></td>
                    </tr>
                </table>
                
                <h3>Top Selling Items</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Code</th>
                            <th>Quantity Sold</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_top_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['item_code']); ?></td>
                            <td><?php echo $item['total_quantity']; ?></td>
                            <td><?php echo formatCurrency($item['total_revenue']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <h3>Recent Sales</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Item</th>
                            <th>Code</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($my_sales as $sale): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($sale['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($sale['item_name']); ?></td>
                            <td><?php echo htmlspecialchars($sale['item_code']); ?></td>
                            <td><?php echo $sale['quantity']; ?></td>
                            <td><?php echo formatCurrency($sale['unit_price']); ?></td>
                            <td><?php echo formatCurrency($sale['total_amount']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="print-footer">
                Report generated on <?php echo date('F d, Y h:i A'); ?> | Page <span class="page-number"></span>
            </div>
            <script>
                // Add page numbers
                document.addEventListener('DOMContentLoaded', function() {
                    const pages = document.querySelectorAll('.page-break');
                    pages.forEach((page, index) => {
                        const pageNumber = document.createElement('div');
                        pageNumber.style.position = 'absolute';
                        pageNumber.style.bottom = '10px';
                        pageNumber.style.right = '20px';
                        pageNumber.style.fontSize = '10px';
                        pageNumber.style.color = '#666';
                        pageNumber.textContent = 'Page ' + (index + 1) + ' of ' + (pages.length + 1);
                        page.appendChild(pageNumber);
                    });
                    
                    // Add page number to the last page
                    const pageNumber = document.createElement('div');
                    pageNumber.style.position = 'fixed';
                    pageNumber.style.bottom = '10px';
                    pageNumber.style.right = '20px';
                    pageNumber.style.fontSize = '10px';
                    pageNumber.style.color = '#666';
                    pageNumber.textContent = 'Page ' + (pages.length + 1) + ' of ' + (pages.length + 1);
                    document.body.appendChild(pageNumber);
                    
                    // Trigger print
                    window.print();
                });
            </script>
        </body>
        </html>
    `;
    
    // Write the content to the print window
    printWindow.document.open();
    printWindow.document.write(printContent);
    printWindow.document.close();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
