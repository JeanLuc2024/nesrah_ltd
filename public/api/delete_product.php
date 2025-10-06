<?php
// Start session and check authentication
session_start();
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include database configuration
require_once __DIR__ . '/../../app/config/database.php';

header('Content-Type: application/json');

// Check if ID is provided
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit();
}

$productId = (int)$_POST['id'];

try {
    // Get database connection
    $pdo = getDBConnection();
    
    // Check if product exists
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    
    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }
    
    // Delete the product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $result = $stmt->execute([$productId]);
    
    if ($result) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        throw new PDOException('Failed to delete product');
    }
    
} catch (PDOException $e) {
    error_log("Delete product error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred while deleting the product']);
}
