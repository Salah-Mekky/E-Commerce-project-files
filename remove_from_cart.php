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

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

$stmt = $conn->prepare('DELETE FROM cart_items WHERE user_id = ? AND product_id = ?');
$stmt->bind_param('ii', $user_id, $product_id);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Item removed from cart']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove item']);
}
$stmt->close();
$conn->close();
