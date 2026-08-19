<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
$conn = getDBConnection();

if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    echo json_encode(['success' => false, 'cart' => [], 'message' => 'Cart is not available for admin users.']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$user_id = intval($_SESSION['user_id']);

$sql = "SELECT c.product_id, p.name, p.price, p.image_url, p.stock_quantity AS stock, c.quantity FROM cart_items c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart = [];
while ($row = $result->fetch_assoc()) {
    $row['price'] = floatval($row['price']);
    $row['quantity'] = intval($row['quantity']);
    $row['stock'] = intval($row['stock']);
    $row['image'] = $row['image_url'];
    unset($row['image_url']);
    $cart[] = $row;
}
$stmt->close();

echo json_encode(['success' => true, 'cart' => $cart]);
$conn->close();
