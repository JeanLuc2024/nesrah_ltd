<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get order ID from URL
$orderId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => 'Order ID is required']);
    exit;
}

try {
    // Query to get order details from orders table
    $query = "SELECT
                o.*,
                u.first_name as sales_rep_first_name,
                u.last_name as sales_rep_last_name,
                GROUP_CONCAT(
                    CONCAT(oi.quantity, 'x ', COALESCE(i.item_name, oi.manual_item_name), ' (', FORMAT(oi.unit_price, 0), ' RWF each)')
                    SEPARATOR '; '
                ) as order_items,
                SUM(oi.total_price) as calculated_total
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.id
              LEFT JOIN order_items oi ON o.id = oi.order_id
              LEFT JOIN inventory i ON oi.item_id = i.id
              WHERE o.id = :order_id
              GROUP BY o.id, u.first_name, u.last_name";

    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $orderId, PDO::PARAM_INT);
    $stmt->execute();

    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        http_response_code(404);
        echo json_encode(['error' => 'Order not found']);
        exit;
    }

    // Format the response
    $response = [
        'id' => $order['id'],
        'order_number' => 'ORD-' . str_pad($order['id'], 6, '0', STR_PAD_LEFT),
        'customer_name' => $order['customer_name'],
        'customer_phone' => $order['customer_phone'],
        'customer_email' => $order['customer_email'],
        'item_name' => $order['order_items'] ?: 'Multiple Items',
        'item_code' => 'MULTI',
        'quantity' => 'Multiple',
        'unit_price' => 'Multiple',
        'total_amount' => number_format($order['total_amount'], 0),
        'amount_paid' => number_format($order['total_amount'], 0), // Assuming full payment for now
        'payment_method' => ucfirst(str_replace('_', ' ', $order['payment_method'])),
        'payment_status' => ucfirst($order['payment_status']),
        'notes' => $order['notes'],
        'order_date' => date('F j, Y, g:i a', strtotime($order['created_at'])),
        'sales_rep' => $order['sales_rep_first_name'] . ' ' . $order['sales_rep_last_name']
    ];

    echo json_encode($response);
    
} catch (PDOException $e) {
    error_log('Error fetching order details: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch order details']);
}
?>
