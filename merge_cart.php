<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
$conn = getDBConnection();

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    echo json_encode(['success' => true]);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['cart']) || !is_array($data['cart'])) {
    echo json_encode(['success' => false, 'message' => 'Cart must be an array']);
    exit;
}

foreach ($data['cart'] as $item) {
    $product_id = intval($item['product_id'] ?? 0);
    $quantity = intval($item['quantity'] ?? 1);

    if ($product_id <= 0 || $quantity <= 0) {
        continue;
    }

    $stmt = $conn->prepare('SELECT stock_quantity FROM products WHERE product_id = ?');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        continue;
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

    $available = max(0, intval($product['stock_quantity']) - $currentQuantity);
    if ($available <= 0) {
        continue;
    }

    $quantityToAdd = min($quantity, $available);

    $stmt = $conn->prepare('INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)');
    $stmt->bind_param('iii', $user_id, $product_id, $quantityToAdd);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => true, 'message' => 'Cart merged successfully']);
$conn->close();
