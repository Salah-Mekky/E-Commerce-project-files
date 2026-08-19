<?php
header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
$conn = getDBConnection();

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

$user_id = intval($_SESSION['user_id']);
$data = json_decode(file_get_contents('php://input'), true);

$stmt = $conn->prepare('SELECT c.product_id, p.name, p.price, c.quantity FROM cart_items c JOIN products p ON c.product_id = p.product_id WHERE c.user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cart = [];
while ($row = $result->fetch_assoc()) {
    $row['price'] = floatval($row['price']);
    $row['quantity'] = intval($row['quantity']);
    $cart[] = $row;
}
$stmt->close();

if (empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Cart is empty']);
    exit;
}

$stmt = $conn->prepare('SELECT address FROM users WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$shipping_address = trim($user['address'] ?? '');
$stmt->close();

if ($shipping_address === '') {
    echo json_encode(['success' => false, 'message' => 'Please add your shipping address in your profile before placing an order.']);
    exit;
}

$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$shipping = $subtotal > 100 ? 0 : 10;
$tax = $subtotal * 0.1;
$total = $subtotal + $shipping + $tax;

$conn->begin_transaction();

try {
    $checkStockStmt = $conn->prepare('SELECT stock_quantity, name FROM products WHERE product_id = ? FOR UPDATE');
    foreach ($cart as $item) {
        $checkStockStmt->bind_param('i', $item['product_id']);
        $checkStockStmt->execute();
        $stockResult = $checkStockStmt->get_result();
        if ($stockResult->num_rows === 0) {
            throw new Exception('Product not found');
        }
        $productStock = $stockResult->fetch_assoc();
        if ($productStock['stock_quantity'] < $item['quantity']) {
            throw new Exception('Insufficient stock for ' . $productStock['name']);
        }
    }
    $checkStockStmt->close();

    $status = 'pending';
    $stmt = $conn->prepare('INSERT INTO orders (user_id, total_amount, shipping_address, status, order_date) VALUES (?, ?, ?, ?, NOW())');
    $stmt->bind_param('idss', $user_id, $total, $shipping_address, $status);
    if (!$stmt->execute()) {
        throw new Exception('Failed to create order: ' . $stmt->error);
    }
    $order_id = $stmt->insert_id;
    $stmt->close();

    $stmt = $conn->prepare('INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)');
    foreach ($cart as $item) {
        $stmt->bind_param('iiid', $order_id, $item['product_id'], $item['quantity'], $item['price']);
        if (!$stmt->execute()) {
            throw new Exception('Failed to add order items: ' . $stmt->error);
        }
    }
    $stmt->close();

    $updateStockStmt = $conn->prepare('UPDATE products SET stock_quantity = stock_quantity - ? WHERE product_id = ?');
    foreach ($cart as $item) {
        $updateStockStmt->bind_param('ii', $item['quantity'], $item['product_id']);
        if (!$updateStockStmt->execute()) {
            throw new Exception('Failed to update product stock: ' . $updateStockStmt->error);
        }
    }
    $updateStockStmt->close();

    $stmt = $conn->prepare('DELETE FROM cart_items WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to clear cart: ' . $stmt->error);
    }
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Order created successfully', 'order_id' => $order_id]);
} catch (Exception $e) {
    $conn->rollback();
    error_log('Checkout error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Checkout failed. Please try again.']);
}

$conn->close();



