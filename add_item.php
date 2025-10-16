<?php
require_once __DIR__ . '/includes/header.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get and sanitize input
        $item_name = sanitizeInput($_POST['item_name'] ?? '');
        $unit_price = floatval($_POST['unit_price'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);

        // Validation
        if (empty($item_name)) {
            throw new Exception('Item name is required');
        }

        if ($unit_price <= 0) {
            throw new Exception('Unit price must be greater than 0');
        }

        if ($quantity <= 0) {
            throw new Exception('Quantity must be greater than 0');
        }

        // Generate a temporary item code for tracking
        $item_code = 'REQ-' . time() . '-' . rand(100, 999);

        // Create a temporary item object for the order
        // This item won't be added to inventory, just used for this order
        $temp_item = [
            'id' => 'temp_' . $item_code, // Temporary ID
            'item_name' => $item_name,
            'item_code' => $item_code,
            'unit_price' => $unit_price,
            'current_stock' => 0, // Not in stock
            'is_requested_item' => true,
            'quantity' => $quantity
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Customer requested item added successfully',
            'item' => $temp_item
        ]);

    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>