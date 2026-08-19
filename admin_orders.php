<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php');
    exit();
}

$conn = getDBConnection();

$error = '';
$success = '';

if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

if (isset($_SESSION['flash_success'])) {
    $success = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $valid_statuses = ['pending', 'paid', 'shipped', 'delivered', 'cancelled'];

    if (!in_array($status, $valid_statuses, true)) {
        $_SESSION['flash_error'] = 'Invalid status.';
    } else {
        $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
        $stmt->bind_param('si', $status, $order_id);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Order status updated successfully!';
        } else {
            $_SESSION['flash_error'] = 'Failed to update order status.';
        }
        $stmt->close();
    }

    header('Location: admin_orders.php');
    exit();
}

if (isset($_GET['order_json']) && is_numeric($_GET['order_json'])) {
    $view_order_id = intval($_GET['order_json']);
    $stmt = $conn->prepare('SELECT o.order_id, o.order_date, o.total_amount, o.status, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.user_id WHERE o.order_id = ?');
    $stmt->bind_param('i', $view_order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$order) {
        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Order not found']);
        exit();
    }

    $stmt = $conn->prepare('SELECT oi.quantity, oi.unit_price, p.name AS product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?');
    $stmt->bind_param('i', $view_order_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $order['items'] = $items;
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($order);
    exit();
}

$sql = "SELECT o.order_id, o.order_date, o.total_amount, o.status, u.full_name, u.email, COUNT(oi.order_item_id) AS item_count
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        GROUP BY o.order_id
        ORDER BY o.order_date DESC";
$result = $conn->query($sql);
$orders = $result->fetch_all(MYSQLI_ASSOC);

$orderDetails = [];
foreach ($orders as $order) {
    $orderId = (int)$order['order_id'];
    $orderDetails[$orderId] = [
        'order_id' => $orderId,
        'order_date' => $order['order_date'],
        'total_amount' => (float)$order['total_amount'],
        'status' => $order['status'],
        'full_name' => $order['full_name'],
        'email' => $order['email'],
        'items' => [],
    ];
}

if (!empty($orderDetails)) {
    $orderIdsArr = array_map('intval', array_keys($orderDetails));
    $placeholders = implode(',', array_fill(0, count($orderIdsArr), '?'));
    $sqlItems = "SELECT oi.order_id, oi.quantity, oi.unit_price, p.name AS product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id IN ($placeholders) ORDER BY oi.order_id, oi.order_item_id";
    $stmt = $conn->prepare($sqlItems);
    if ($stmt) {
        $types = str_repeat('i', count($orderIdsArr));
        $bindParams = array_merge([$types], $orderIdsArr);
        $refs = [];
        foreach ($bindParams as $k => $v) {
            $refs[$k] = &$bindParams[$k];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);
        $stmt->execute();
        $itemResult = $stmt->get_result();
        while ($row = $itemResult->fetch_assoc()) {
            $orderId = (int)$row['order_id'];
            if (isset($orderDetails[$orderId])) {
                $orderDetails[$orderId]['items'][] = [
                    'product_name' => $row['product_name'] ?? 'Product',
                    'unit_price' => (float)$row['unit_price'],
                    'quantity' => (int)$row['quantity'],
                ];
            }
        }
        $stmt->close();
    } else {
        $orderIds = implode(',', $orderIdsArr);
        $sqlItems = "SELECT oi.order_id, oi.quantity, oi.unit_price, p.name AS product_name FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id IN ($orderIds) ORDER BY oi.order_id, oi.order_item_id";
        $itemResult = $conn->query($sqlItems);
        while ($row = $itemResult->fetch_assoc()) {
            $orderId = (int)$row['order_id'];
            if (isset($orderDetails[$orderId])) {
                $orderDetails[$orderId]['items'][] = [
                    'product_name' => $row['product_name'] ?? 'Product',
                    'unit_price' => (float)$row['unit_price'],
                    'quantity' => (int)$row['quantity'],
                ];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Store - Manage Orders</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html { overflow-y: scroll; min-height: 100%; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .header-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
            max-width: 1200px;
            margin: 0 auto;
            padding: 1rem 2rem;
        }

        .logo {
            font-size: 1.95rem;
            font-weight: 800;
            text-decoration: none;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .logo::before {
            content: "";
            display: inline-block;
            width: 48px;
            height: 48px;
            margin-right: 0.5rem;
            padding: 6px;
            background-image: url('Icons/Website logo Icon test.jpg');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            background-color: #0052a3;
            border-radius: 10px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.18);
            vertical-align: middle;
        }

        nav ul {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            list-style: none;
            gap: 1.5rem;
            align-items: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
        }

        nav a:hover {
            opacity: 0.8;
        }


        .main-content {
            width: 100%;
            min-width: 0;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            flex: 1 1 auto;
        }

        .orders-panel {
            width: 100%;
            min-width: 0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.5rem;
            color: #333;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .secondary-btn,
        .primary-btn,
        .delete-btn {
            padding: 0.9rem 1.25rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.25s ease;
        }

        .primary-btn {
            background: #0066cc;
        }

        .primary-btn:hover {
            background: #0052a3;
        }

        .secondary-btn {
            background: #4f5d75;
        }

        .secondary-btn:hover {
            background: #3c4a60;
        }

        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1rem;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .orders-panel {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border-radius: 18px;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.12);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
        }

        .order-items-table {
            width: 100%;
            min-width: 640px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 1rem;
            border-bottom: 1px solid #edf2f7;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
            font-weight: 700;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        tbody tr:hover {
            background: linear-gradient(90deg, #f8fbff 0%, #eff6ff 100%);
        }

        .status-select {
            width: 100%;
            min-width: 140px;
            max-width: 100%;
            padding: 0.85rem 1rem;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
            font-size: 0.95rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
            color: #0f172a;
        }

        .status-select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .table-actions a,
        .table-actions button {
            font-size: 0.9rem;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        .order-details-card {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border-radius: 22px;
            box-shadow: 0 22px 50px rgba(15, 23, 42, 0.14);
            border: 1px solid #e2e8f0;
            padding: 1.75rem;
            margin-top: 1.75rem;
            opacity: 0;
            transform: translateY(-18px);
            max-height: 0;
            overflow: hidden;
            visibility: hidden;
            transition: opacity 0.35s ease, transform 0.35s ease, max-height 0.35s ease, visibility 0.35s ease;
            box-sizing: border-box;
        }

        .order-details-card.open {
            opacity: 1;
            transform: translateY(0);
            max-height: 1600px;
            visibility: visible;
        }

        .order-details-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 0.85rem;
            border-bottom: 1px solid #e5edf6;
        }

        .order-details-card h2 {
            margin: 0;
            font-size: 1.35rem;
            color: #0f172a;
            font-weight: 800;
        }

        .order-details-badge {
            padding: 0.6rem 1rem;
            border-radius: 999px;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            color: #1d4ed8;
            border: 1px solid #bfdbfe;
        }

        .order-details-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 1.35rem;
        }

        .summary-item {
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
            border-radius: 14px;
            padding: 1rem 1.1rem;
            border: 1px solid #e5edf6;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.06);
        }

        .summary-item strong {
            display: block;
            color: #475569;
            margin-bottom: 0.35rem;
            font-size: 0.88rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .summary-item span {
            display: block;
            font-size: 1rem;
            font-weight: 700;
            color: #0f172a;
            word-break: break-word;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .summary-item {
            min-width: 0;
        }

        .summary-item span[ id="order-details-customer" ],
        .summary-item span[id="order-details-email"] {
            display: block;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .order-items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.75rem;
            min-width: 640px;
        }

        .order-items-table th,
        .order-items-table td {
            padding: 0.95rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .order-items-table th:first-child,
        .order-items-table td:first-child {
            min-width: 240px;
            max-width: 420px;
            width: 42%;
        }

        .order-items-table td:first-child {
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .order-items-table td:first-child span,
        .order-items-table td:first-child div {
            display: block;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        .order-items-table th {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
            font-weight: 700;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .order-items-table td {
            color: #334155;
            background: #ffffff;
        }

        .order-items-table tbody tr:hover {
            background: linear-gradient(90deg, #f8fbff 0%, #eff6ff 100%);
        }

        .order-items-table tbody tr:last-child td {
            border-bottom: none;
        }

        .order-items-total {
            text-align: right;
            padding: 1rem;
            font-weight: 700;
            color: #111827;
        }

        @media (max-width: 900px) {
            .page-header,
            .table-actions {
                flex-direction: column;
                align-items: stretch;
            }
            .order-details-summary {
                grid-template-columns: 1fr;
            }

            .header-content {
                padding: 1rem;
            }

            nav ul {
                gap: 0.75rem;
                justify-content: center;
            }

            .button-group {
                width: 100%;
                justify-content: flex-start;
            }

            .button-group > a {
                width: auto;
                flex: 1 1 auto;
            }
        }

        @media (max-width: 820px) {
            .main-content {
                padding: 0 0.75rem;
            }
        }

        @media (max-width: 720px) {
            .main-content {
                padding: 0 0.75rem;
            }

            .header-content {
                padding: 1rem;
            }

            .page-title {
                font-size: 1.85rem;
            }

            nav ul {
                gap: 0.5rem;
                justify-content: flex-start;
            }

            .button-group {
                width: 100%;
            }

            .button-group > a {
                width: 100%;
            }

            .secondary-btn,
            .primary-btn,
            .delete-btn {
                width: auto;
                min-width: 120px;
                justify-content: center;
            }

            .table-actions {
                flex-direction: row;
                flex-wrap: wrap;
                justify-content: flex-start;
                gap: 0.5rem;
            }

            .table-actions button,
            .table-actions form {
                width: auto;
            }

            .status-select {
                max-width: 100%;
            }

            .orders-panel {
                padding: 0.5rem;
            }

            .orders-panel table {
                min-width: 100%;
            }

            .table-responsive thead {
                display: none;
            }

            .table-responsive tbody,
            .table-responsive tr,
            .table-responsive td {
                display: block;
                width: 100%;
            }

            .table-responsive tr {
                margin-bottom: 1rem;
                border-radius: 16px;
                box-shadow: 0 10px 24px rgba(0, 0, 0, 0.06);
                background: #fff;
                border: 1px solid #e5e7eb;
                overflow: hidden;
            }

            .table-responsive td {
                text-align: left;
                padding: 0.95rem 0.9rem;
                border-bottom: 1px solid #eef2f7;
                position: relative;
            }

            .table-responsive td:last-child {
                border-bottom: none;
            }

            .table-responsive td[data-label]:before {
                content: attr(data-label);
                display: block;
                font-weight: 700;
                color: #334155;
                margin-bottom: 0.35rem;
                text-transform: uppercase;
                letter-spacing: 0.03em;
            }

            .table-responsive td > .table-actions {
                justify-content: flex-start;
            }

            .order-details-card {
                padding: 1.25rem;
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                min-width: 0;
            }

            .order-items-table {
                display: table;
                width: 100%;
                min-width: 640px;
                border-collapse: collapse;
            }

            .order-items-table thead {
                display: table-header-group;
            }

            .order-items-table tbody {
                display: table-row-group;
            }

            .order-items-table tr {
                display: table-row;
            }

            .order-items-table th,
            .order-items-table td {
                display: table-cell;
                padding: 0.95rem 1rem;
                text-align: left;
                border-bottom: 1px solid #e5e7eb;
            }

            .order-items-table th {
                background: #f8f9fa;
                color: #334155;
                font-weight: 600;
                font-size: 0.95rem;
            }

            .order-items-table td {
                color: #475569;
            }

            .order-items-table tbody tr:last-child td {
                border-bottom: none;
            }

            .order-details-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .order-details-summary {
                grid-template-columns: 1fr;
            }

            .table-responsive {
                min-width: 100%;
            }

            .order-items-table th,
            .order-items-table td {
                padding: 0.75rem 0.8rem;
            }
        }

        footer {
            background: linear-gradient(180deg, #1a1a1a 0%, #0f0f0f 100%);
            color: white;
            padding: 3rem 2rem;
            margin-top: 4rem;
            border-top: 2px solid #0066cc;
            width: 100%;
            margin-top: auto;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2.5rem;
        }

        .footer-section h3 {
            margin-bottom: 1.25rem;
            color: #0066cc;
            font-size: 1.1rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .footer-section p,
        .footer-section a {
            color: #b0b0b0;
            font-size: 0.9rem;
            line-height: 1.8;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .footer-section ul li {
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .footer-section ul li img {
            width: 20px;
            height: 20px;
            object-fit: contain;
            filter: brightness(0.8) contrast(1.2);
            transition: filter 0.25s ease;
        }

        .footer-section ul li img[src*="clock"] {
            width: 20px;
            height: 20px;
            object-fit: contain;
            filter: invert(1) sepia(1) saturate(700%) hue-rotate(10deg) brightness(1.05) contrast(1.05);
            box-shadow: 0 0 6px rgba(255, 107, 53, 0.16);
        }

        .footer-section ul li:hover img {
            filter: brightness(1) contrast(1.3);
        }

        .footer-section a {
            color: #b0b0b0;
            text-decoration: none;
            transition: color 0.25s ease;
        }

        .footer-section a:hover {
            color: #0066cc;
        }

        .social-icons {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-top: 1rem;
        }

        .social-icon {
            width: 44px;
            height: 44px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(0, 102, 204, 0.1), rgba(0, 102, 204, 0.05));
            border: 2px solid rgba(0, 102, 204, 0.3);
            border-radius: 0;
            color: white;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .social-icon img {
            width: 40px;
            height: 40px;
            object-fit: contain;
            filter: brightness(0.9) contrast(1.1);
            transition: filter 0.3s ease, transform 0.3s ease;
        }

        .social-icon:hover {
            background: linear-gradient(135deg, rgba(0, 102, 204, 0.25), rgba(0, 102, 204, 0.15));
            border-color: #0066cc;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0, 102, 204, 0.25);
        }

        .social-icon:hover img {
            filter: brightness(1.1) contrast(1.2);
            transform: scale(1.1);
        }

        .footer-bottom {
            text-align: center;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #888;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            footer {
                padding: 2.5rem 1.5rem;
            }

            .footer-content {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 2rem;
            }

            .footer-section h3 {
                font-size: 1rem;
            }

            .footer-section p,
            .footer-section a {
                font-size: 0.85rem;
            }

            .social-icons {
                gap: 0.75rem;
            }

            .social-icon {
                width: 40px;
                height: 40px;
            }

            .social-icon img {
                width: 36px;
                height: 36px;
            }
        }

        @media (max-width: 480px) {
            footer {
                padding: 2rem 1rem;
                margin-top: 3rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .footer-section h3 {
                font-size: 0.95rem;
            }

            .footer-section p,
            .footer-section a {
                font-size: 0.8rem;
            }

            .footer-section ul li {
                margin-bottom: 0.6rem;
                gap: 0.6rem;
            }

            .footer-section ul li img {
                width: 18px;
                height: 18px;
            }

            .social-icons {
                gap: 0.6rem;
            }

            .social-icon {
                width: 38px;
                height: 38px;
            }

            .social-icon img {
                width: 34px;
                height: 34px;
            }
        }

        @media (max-width: 1024px) {
            .header-content {
                padding: 0.95rem 1.5rem;
            }

            nav ul {
                gap: 1.2rem;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                padding: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }

            .logo {
                font-size: 1.4rem;
            }

            nav ul {
                gap: 0.9rem;
                font-size: 0.95rem;
                flex-wrap: wrap;
                justify-content: center;
            }

            nav li {
                flex: 0 1 auto;
                min-width: 0;
            }

            nav a {
                padding: 0.25rem 0.4rem;
            }
        }

        @media (max-width: 425px) {
            .header-content {
                flex-direction: column;
                align-items: stretch;
                padding: 0.8rem 0.9rem;
            }

            .logo {
                justify-content: center;
            }

            nav ul {
                gap: 0.75rem;
                justify-content: center;
                width: 100%;
            }

            nav li {
                flex: 0 1 auto;
                min-width: 0;
            }

            nav a {
                padding: 0.25rem 0.35rem;
                font-size: 0.9rem;
            }
        }

        @media (max-width: 375px) {
            .header-content {
                padding: 0.75rem 0.85rem;
            }

            .logo {
                font-size: 1.3rem;
            }

            nav ul {
                gap: 0.65rem;
            }

            nav a {
                font-size: 0.88rem;
            }
        }

        @media (max-width: 320px) {
            .header-content {
                padding: 0.6rem 0.75rem;
            }

            .logo {
                font-size: 1.2rem;
            }

            nav ul {
                gap: 0.55rem;
            }

            nav a {
                font-size: 0.82rem;
                padding: 0.25rem 0.35rem;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="header-content">
        <a href="index.php" class="logo">SM Store</a>
        <nav>
            <ul>
                <li><a href="index.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="contact.php">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin_dashboard.php">Admin Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login/Register</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main class="main-content">
    <section class="page-header">
        <h1 class="page-title">Order Management</h1>
        <div class="button-group">
            <a href="admin_dashboard.php" class="secondary-btn">Back to Dashboard</a>
        </div>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="orders-panel">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7">No orders found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td data-label="Order ID">
                                <strong><?php echo (int)$order['order_id']; ?></strong>
                            </td>
                            <td data-label="Customer">
                                <?php echo htmlspecialchars($order['full_name']); ?><br>
                                <span style="color:#666;font-size:0.95rem;"><?php echo htmlspecialchars($order['email']); ?></span>
                            </td>
                            <td data-label="Date">
                                <span style="color:#666;font-size:0.95rem;"><?php echo date('M j, Y', strtotime($order['order_date'])); ?></span>
                            </td>
                            <td data-label="Items"><?php echo (int)$order['item_count']; ?></td>
                            <td data-label="Total">$<?php echo number_format($order['total_amount'], 2); ?></td>
                            <td data-label="Status">
                                <select name="status" form="update-status-<?php echo (int)$order['order_id']; ?>" class="status-select" aria-label="Order status">
                                    <?php foreach (['pending', 'paid', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td data-label="Actions">
                                <div class="table-actions">
                                    <form id="update-status-<?php echo (int)$order['order_id']; ?>" method="post" action="admin_orders.php">
                                        <input type="hidden" name="order_id" value="<?php echo (int)$order['order_id']; ?>">
                                        <button type="submit" name="update_status" class="secondary-btn">Save</button>
                                    </form>
                                    <button type="button" data-order-id="<?php echo (int)$order['order_id']; ?>" onclick="toggleOrderDetails('<?php echo (int)$order['order_id']; ?>')" class="secondary-btn details-toggle-btn">Details</button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        </div>

        <section id="order-details-card" class="order-details-card" aria-hidden="true">
            <div class="order-details-header">
                <h2 id="order-details-title">Order Details</h2>
                <span id="order-details-badge" class="order-status"></span>
            </div>
            <div class="order-details-summary">
                <div class="summary-item"><strong>Order</strong><span id="order-details-id"></span></div>
                <div class="summary-item"><strong>Customer</strong><span id="order-details-customer"></span></div>
                <div class="summary-item"><strong>Email</strong><span id="order-details-email"></span></div>
                <div class="summary-item"><strong>Order Date</strong><span id="order-details-date"></span></div>
                <div class="summary-item"><strong>Total Amount</strong><span id="order-details-total"></span></div>
                <div class="summary-item"><strong>Items</strong><span id="order-details-items"></span></div>
            </div>

            <table class="order-items-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody id="order-details-items-body"></tbody>
            </table>
        </section>
    </div>
</main>

<script>
    const orderDetailsData = <?php echo json_encode($orderDetails, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
    const orderDetailsCard = document.getElementById('order-details-card');
    const orderDetailsTitle = document.getElementById('order-details-title');
    const orderDetailsBadge = document.getElementById('order-details-badge');
    const orderDetailsId = document.getElementById('order-details-id');
    const orderDetailsCustomer = document.getElementById('order-details-customer');
    const orderDetailsEmail = document.getElementById('order-details-email');
    const orderDetailsDate = document.getElementById('order-details-date');
    const orderDetailsTotal = document.getElementById('order-details-total');
    const orderDetailsItemsCount = document.getElementById('order-details-items');
    const orderDetailsItemsBody = document.getElementById('order-details-items-body');

    function renderOrderDetails(order) {
        orderDetailsTitle.textContent = `Order ${order.order_id} Details`;
        orderDetailsId.textContent = `${order.order_id}`;
        orderDetailsCustomer.textContent = order.full_name;
        orderDetailsEmail.textContent = order.email;
        orderDetailsDate.textContent = new Date(order.order_date).toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
        orderDetailsTotal.textContent = `$${parseFloat(order.total_amount).toFixed(2)}`;
        orderDetailsItemsCount.textContent = Array.isArray(order.items) ? order.items.length : 0;
        orderDetailsBadge.textContent = order.status.charAt(0).toUpperCase() + order.status.slice(1);
        orderDetailsBadge.className = `order-status order-details-badge status-${order.status}`;

        const items = Array.isArray(order.items) ? order.items : [];
        if (items.length === 0) {
            orderDetailsItemsBody.innerHTML = '<tr><td colspan="4" style="padding:1.25rem;color:#64748b;">No items found for this order.</td></tr>';
            return;
        }

        orderDetailsItemsBody.innerHTML = items.map(item => {
            const subtotal = (item.quantity * item.unit_price).toFixed(2);
            const safeName = item.product_name ? String(item.product_name).replace(/</g, '&lt;').replace(/>/g, '&gt;') : 'Product';
            return `
                <tr>
                    <td>${safeName}</td>
                    <td>$${parseFloat(item.unit_price).toFixed(2)}</td>
                    <td>${parseInt(item.quantity, 10)}</td>
                    <td>$${subtotal}</td>
                </tr>
            `;
        }).join('');
    }

    function fetchOrderDetails(orderId) {
        const endpoint = window.location.pathname + '?order_json=' + encodeURIComponent(orderId);
        return fetch(endpoint)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load order details');
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                orderDetailsData[String(orderId)] = data;
                return data;
            });
    }

    function openOrderDetails(orderId, order) {
        renderOrderDetails(order);
        orderDetailsCard.dataset.orderId = orderId;
        orderDetailsCard.classList.add('open');
        orderDetailsCard.setAttribute('aria-hidden', 'false');
        document.querySelectorAll('.details-toggle-btn').forEach(btn => {
            btn.textContent = btn.dataset.orderId === String(orderId) ? 'Hide details' : 'Details';
        });
        orderDetailsCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function closeOrderDetails() {
        if (!orderDetailsCard) return;
        orderDetailsCard.classList.remove('open');
        orderDetailsCard.setAttribute('aria-hidden', 'true');
        document.querySelectorAll('.details-toggle-btn').forEach(btn => btn.textContent = 'Details');
    }

    function showOrderDetailsLoading(orderId) {
        orderDetailsTitle.textContent = `Order ${orderId} Details`;
        orderDetailsId.textContent = `${orderId}`;
        orderDetailsCustomer.textContent = 'Loading...';
        orderDetailsEmail.textContent = '';
        orderDetailsDate.textContent = '';
        orderDetailsTotal.textContent = '';
        orderDetailsItemsCount.textContent = '0';
        orderDetailsBadge.textContent = 'Loading';
        orderDetailsBadge.className = 'order-status order-details-badge status-pending';
        orderDetailsItemsBody.innerHTML = '<tr><td colspan="4" style="padding:1.25rem;color:#64748b;">Loading order details...</td></tr>';
        orderDetailsCard.dataset.orderId = orderId;
        orderDetailsCard.classList.add('open');
        orderDetailsCard.setAttribute('aria-hidden', 'false');
        document.querySelectorAll('.details-toggle-btn').forEach(btn => {
            btn.textContent = btn.dataset.orderId === String(orderId) ? 'Hide details' : 'Details';
        });
        orderDetailsCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function toggleOrderDetails(orderId) {
        if (!orderDetailsCard) return;
        const isOpen = orderDetailsCard.classList.contains('open');
        const currentOrder = orderDetailsCard.dataset.orderId;

        if (isOpen && currentOrder === String(orderId)) {
            closeOrderDetails();
            return;
        }

        const cachedOrder = orderDetailsData[String(orderId)];
        if (cachedOrder) {
            openOrderDetails(orderId, cachedOrder);
            return;
        }

        showOrderDetailsLoading(orderId);
        fetchOrderDetails(orderId)
            .then(order => openOrderDetails(orderId, order))
            .catch(() => {
                orderDetailsItemsBody.innerHTML = '<tr><td colspan="4" style="padding:1.25rem;color:#d32f2f;">Unable to load order details.</td></tr>';
            });
    }

    function initOrderDetailsToggle() {
        document.querySelectorAll('.details-toggle-btn').forEach(btn => {
            btn.addEventListener('click', () => toggleOrderDetails(btn.dataset.orderId));
        });
    }

    function openOrderFromUrl() {
        const params = new URLSearchParams(window.location.search);
        const viewOrderId = params.get('view');
        if (viewOrderId) {
            toggleOrderDetails(viewOrderId);
        }
    }

    initOrderDetailsToggle();
    openOrderFromUrl();

    const alertBoxes = document.querySelectorAll('.alert');
    if (alertBoxes.length) {
        setTimeout(() => {
            alertBoxes.forEach(alert => {
                alert.style.transition = 'opacity 0.35s ease, max-height 0.35s ease, margin 0.35s ease, padding 0.35s ease';
                alert.style.opacity = '0';
                alert.style.maxHeight = '0';
                alert.style.margin = '0';
                alert.style.padding = '0';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.parentNode.removeChild(alert);
                    }
                }, 350);
            });
        }, 4500);
    }

    initOrderDetailsToggle();
</script>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>About SM Store</h3>
                <p>Your trusted online marketplace for quality products and great deals.</p>
                <div class="social-icons">
                    <a href="https://www.facebook.com/share/1Fne4j3EXc/" class="social-icon"><img src="Icons/facebook Icon.png" alt="Facebook Icon"></a>
                    <a href="https://x.com/Salah_Store2005" class="social-icon"><img src="Icons/X Icon.jpg" alt="X Icon"></a>
                    <a href="https://www.instagram.com/sm_store_2005?igsh=d2Job2UyeXczeGto" class="social-icon"><img src="Icons/Instagram Icon.jpg" alt="Instagram Icon"></a>
                    <a href="https://www.linkedin.com/in/sm-store-a28714410/" class="social-icon"><img src="Icons/Linkedin Icon.png" alt="LinkedIn Icon"></a>
                </div>
            </div>
            <div class="footer-section">
                <h3>Quick Links</h3>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="products.php">Products</a></li>
                    
                </ul>
            </div>
            <div class="footer-section">
                <h3>Customer Service</h3>
                <ul>
                    <li><a href="contact.php">Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h3>Contact</h3>
                <ul>
                    <li><img src="Icons/email.png" alt="email icon"> slahmikki00720@gmail.com</li>
                    <li><img src="Icons/phone.png" alt="phone icon"> +970-592552356</li>
                    <li><img src="Icons/clock.png" alt="clock icon"> Sat - Thu:  9AM - 6PM</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 SM E-commerce Store. All rights reserved.</p>
        </div>
    </footer>
</body></html>
