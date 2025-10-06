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
    // Query to get order details
    $query = "SELECT 
                s.*, 
                i.item_name,
                i.item_code,
                u.first_name as sales_rep_first_name,
                u.last_name as sales_rep_last_name
              FROM sales s
              LEFT JOIN inventory i ON s.item_id = i.id
              LEFT JOIN users u ON s.user_id = u.id
              WHERE s.id = :order_id";
    
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
        'item_name' => $order['item_name'],
        'item_code' => $order['item_code'],
        'quantity' => $order['quantity'],
        'unit_price' => number_format($order['unit_price'], 2),
        'total_amount' => number_format($order['total_amount'], 2),
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
