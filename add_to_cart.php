<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
$conn = getDBConnection();

if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Cart operations are not available for admin users.']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);
if (!is_array($data) || count($data) === 0) {
    $data = $_POST;
}
if (!is_array($data) || count($data) === 0) {
    $data = $_GET;
}

$user_id = intval($_SESSION['user_id']);
$product_id = intval($data['product_id'] ?? 0);
$quantity = intval($data['quantity'] ?? 1);

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$stmt = $conn->prepare('SELECT product_id, stock_quantity FROM products WHERE product_id = ?');
$stmt->bind_param('i', $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    $stmt->close();
    exit;
}
$product = $result->fetch_assoc();
$stmt->close();

$stmt = $conn->prepare('SELECT quantity FROM cart_items WHERE user_id = ? AND product_id = ?');
$stmt->bind_param('ii', $user_id, $product_id);
$stmt->execute();
$result = $stmt->get_result();
$currentQuantity = 0;
if ($row = $result->fetch_assoc()) {
    $currentQuantity = intval($row['quantity']);
}
$stmt->close();

if ($product['stock_quantity'] < $currentQuantity + $quantity) {
    echo json_encode(['success' => false, 'message' => 'There are only ' . $product['stock_quantity'] . ' units available in stock.']);
    exit;
}

$stmt = $conn->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)');
$stmt->bind_param('iii', $user_id, $product_id, $quantity);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Item added to cart']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add item to cart']);
}
$stmt->close();
$conn->close();
