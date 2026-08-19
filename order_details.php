<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!isset($_SESSION['user_id'])) {
    $redirect = 'order_details.php?order_id=' . $order_id;
    header('Location: login.php?redirect=' . urlencode($redirect));
    exit();
}
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header('Location: index.php');
    exit();
}

$conn = getDBConnection();
$user_id = intval($_SESSION['user_id']);

$stmt = $conn->prepare('SELECT * FROM orders WHERE order_id = ? AND user_id = ?');
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: order_history.php');
    exit();
}

$stmt = $conn->prepare('SELECT oi.*, p.name AS product_name, p.image_url FROM order_items oi LEFT JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = ?');
$stmt->bind_param('i', $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Order <?php echo htmlspecialchars($order['order_id']); ?> - SM Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #eef4fb;
            color: #0f172a;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        html {
            overflow-y: scroll;
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
            background-color: #0052a3 !important;
            background-image: url('Icons/Website logo Icon test.jpg');
            background-repeat: no-repeat;
            background-position: center center;
            background-size: contain;
            background-origin: padding-box;
            background-clip: padding-box;
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

        nav li {
            list-style: none;
            display: inline-flex;
            align-items: center;
        }

        nav a {
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: opacity 0.3s;
            display: inline-flex;
            align-items: center;
        }

        nav a:hover {
            opacity: 0.8;
        }

        .cart-icon {
            position: relative;
            cursor: pointer;
            font-size: 1.2rem;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4444;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: bold;
        }

        .container {
            flex: 1 0 auto;
            width: 100%;
            max-width: 1140px;
            margin: 2.5rem auto;
            padding: 0 1.5rem;
        }

        .page-heading {
            margin-bottom: 1.5rem;
        }

        .page-heading h1 {
            font-size: 2.75rem;
            color: #0f172a;
            margin-bottom: 0.75rem;
        }

        .page-heading p {
            max-width: 720px;
            color: #475569;
            font-size: 1rem;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .summary-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.06);
            border: 1px solid rgba(148, 163, 184, 0.18);
            text-align: center;
        }

        .summary-card {
            text-align: center;
        }

        .summary-card strong {
            display: block;
            font-size: 0.95rem;
            color: #475569;
            margin-bottom: 0.75rem;
        }

        .summary-card span {
            display: block;
            font-size: 1.2rem;
            font-weight: 700;
            color: #0f172a;
        }

        .details-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 18px 60px rgba(15, 23, 42, 0.08);
            border: 1px solid rgba(148, 163, 184, 0.18);
        }

        .details-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .table-wrapper {
            width: 100%;
            overflow-x: auto;
            border-radius: 22px;
            background: #ffffff;
        }

        .details-header h2 {
            font-size: 1.5rem;
            color: #0f172a;
        }

        .order-status {
            padding: 0.55rem 0.95rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-paid { background: #dbeafe; color: #1e40af; }
        .status-shipped { background: #dcfce7; color: #166534; }
        .status-delivered { background: #e0f2fe; color: #0c4a6e; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .order-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            background: #ffffff;
            border-radius: 22px;
            overflow: hidden;
        }

        .order-table th,
        .order-table td {
            padding: 1.25rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .order-table th {
            font-size: 0.95rem;
            color: #475569;
            font-weight: 700;
            background: #f8fafc;
        }

        .order-table td {
            color: #334155;
            vertical-align: middle;
        }

        .order-table tbody tr:last-child td {
            border-bottom: none;
        }

        .order-table img {
            width: 72px;
            height: 72px;
            object-fit: cover;
            border-radius: 16px;
        }

        .product-name {
            font-weight: 700;
            color: #0f172a;
        }

        .subtotal {
            font-weight: 700;
            color: #0f172a;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.95rem 1.75rem;
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            margin-top: 1.25rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(37, 99, 235, 0.25);
        }

        .back-link::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,0.1) 100%);
            pointer-events: none;
        }

        .back-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 48px rgba(37, 99, 235, 0.35);
        }

        .cart-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.65);
            z-index: 1000;
            align-items: flex-end;
            justify-content: center;
        }

        .cart-modal.active {
            display: flex;
        }

        .cart-panel {
            width: min(100%, 520px);
            max-height: 90vh;
            background: white;
            border-radius: 16px 16px 0 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .cart-header h2 {
            color: #0f172a;
            font-size: 1.25rem;
        }

        .close-btn {
            border: none;
            background: transparent;
            font-size: 1.6rem;
            cursor: pointer;
            color: #334155;
        }

        .cart-items {
            flex: 1;
            overflow-y: auto;
            padding: 1rem 1.5rem;
        }

        .cart-item {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: center;
        }

        .cart-item-image {
            width: 70px;
            height: 70px;
            background: #f8fafc;
            border-radius: 14px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .cart-item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .cart-item-details {
            flex: 1;
        }

        .cart-item-name {
            font-weight: 700;
            margin-bottom: 0.25rem;
            color: #0f172a;
        }

        .cart-item-price {
            color: #2563eb;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .quantity-control {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border: 1px solid #cbd5e1;
            background: white;
            cursor: pointer;
            border-radius: 8px;
            font-size: 1rem;
        }

        .qty-display {
            width: 35px;
            text-align: center;
        }

        .remove-item {
            border: none;
            background: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .cart-footer {
            padding: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .cart-total {
            display: flex;
            justify-content: space-between;
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .checkout-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 1rem;
            background: #ff6b35;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            transition: background-color 0.3s;
            font-size: 1rem;
        }

        .checkout-btn:hover {
            background: #e85a2a;
        }

        .cart-empty {
            text-align: center;
            color: #64748b;
            padding: 1rem;
        }

        @media (max-width: 2560px) {
            .header-content {
                padding: 1rem 2rem;
            }

            nav ul {
                gap: 1.2rem;
            }
        }

        @media (max-width: 1440px) {
            .header-content {
                padding: 1rem 1.5rem;
            }

            nav ul {
                gap: 1.05rem;
            }
        }

        @media (max-width: 1024px) {
            .header-content {
                padding: 1rem 1.25rem;
            }

            nav ul {
                gap: 0.95rem;
                font-size: 0.92rem;
            }
        }

        @media (max-width: 768px) {
            .header-content {
                padding: 1rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .logo {
                font-size: 1.4rem;
            }

            nav ul {
                gap: 1rem;
                font-size: 0.9rem;
                justify-content: center;
            }

            .container {
                margin: 2rem auto;
                padding: 0 1rem;
            }

            .page-heading h1 {
                font-size: 2rem;
            }

            .page-heading p {
                font-size: 0.95rem;
            }

            .summary-grid {
                grid-template-columns: 1fr;
            }

            .details-header {
                flex-direction: column;
                align-items: stretch;
            }

            .back-link {
                width: 100%;
                justify-content: center;
            }

            .order-table th,
            .order-table td {
                padding: 1rem 0.85rem;
            }

            .order-table img {
                width: 56px;
                height: 56px;
            }
        }

        @media (max-width: 480px) {
            body {
                font-size: 14px;
            }

            .header-content {
                padding: 0.75rem 0.5rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .logo {
                font-size: 1.2rem;
                width: 100%;
                justify-content: center;
            }

            nav ul {
                gap: 0.5rem;
                font-size: 0.8rem;
                width: 100%;
                flex-wrap: wrap;
            }

            nav a {
                padding: 0.5rem;
            }

            .container {
                margin: 1.5rem auto;
                padding: 0 0.75rem;
            }

            .page-heading h1 {
                font-size: 1.75rem;
                margin-bottom: 0.5rem;
            }

            .page-heading p {
                font-size: 0.9rem;
            }

            .summary-grid {
                gap: 0.75rem;
            }

            .details-card {
                padding: 1.25rem;
            }

            .details-header {
                gap: 0.75rem;
            }

            .back-link {
                width: 100%;
                padding: 0.85rem 1rem;
                font-size: 0.9rem;
            }

            .order-table th,
            .order-table td {
                padding: 0.9rem 0.75rem;
            }

            .order-table th {
                font-size: 0.85rem;
            }

            .order-table td {
                font-size: 0.9rem;
            }

            .order-table img {
                width: 56px;
                height: 56px;
            }

            .checkout-btn {
                font-size: 0.95rem;
            }

            .cart-panel {
                width: 100%;
                border-radius: 12px 12px 0 0;
            }

            .cart-header {
                padding: 1rem;
            }

            .cart-header h2 {
                font-size: 1.1rem;
            }

            .cart-items {
                padding: 0.75rem 1rem;
            }

            .cart-item {
                gap: 0.75rem;
                margin-bottom: 0.75rem;
            }

            .cart-item-image {
                width: 60px;
                height: 60px;
            }
        }

        @media (max-width: 425px) {
            .header-content {
                padding: 0.75rem 0.5rem;
            }

            .logo {
                font-size: 1.2rem;
                width: auto;
            }

            nav ul {
                gap: 0.75rem;
                font-size: 0.85rem;
            }

            nav a {
                padding: 0.3rem 0.2rem;
            }
        }

        @media (max-width: 375px) {
            .logo {
                font-size: 1.05rem;
                width: auto;
            }

            nav ul {
                gap: 0.75rem;
                font-size: 0.85rem;
            }

            nav a {
                padding: 0.3rem 0.2rem;
            }
        }

        @media (max-width: 320px) {
            header {
                padding: 0;
            }

            .header-content {
                padding: 1rem 0.75rem;
                gap: 0.75rem;
                width: 100%;
                align-items: center;
            }

            .logo {
                font-size: 1.05rem;
                width: auto;
                justify-content: center;
            }

            nav {
                width: 100%;
            }

            nav ul {
                width: 100%;
                gap: 0.5rem;
                font-size: 0.8rem;
                justify-content: center;
                flex-wrap: wrap;
            }

            nav li {
                display: inline-flex;
                align-items: center;
            }

            nav a {
                font-size: 0.8rem;
                line-height: 1.15;
                padding: 0.35rem 0.25rem;
                min-height: 1.9rem;
                white-space: nowrap;
            }
        }

        @media (max-width: 360px) {
            .logo {
                font-size: 1rem;
            }

            nav ul {
                gap: 0.35rem;
                font-size: 0.75rem;
            }

            nav a {
                padding: 0.35rem;
            }

            .container {
                padding: 0 0.5rem;
            }

            .page-heading h1 {
                font-size: 1.5rem;
            }

            .page-heading p {
                font-size: 0.85rem;
            }

            .details-card {
                padding: 1rem;
            }

            .order-table th,
            .order-table td {
                padding: 0.75rem 0.65rem;
            }

            .order-table th {
                font-size: 0.8rem;
            }

            .order-table td {
                font-size: 0.85rem;
            }
        }

        footer {
            background: linear-gradient(180deg, #1a1a1a 0%, #0f0f0f 100%);
            color: white;
            padding: 3rem 2rem;
            margin-top: 4rem;
            border-top: 2px solid #0066cc;
            width: 100%;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2.5rem;
            align-items: start;
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

        @media (min-width: 2560px) {
            body {
                min-height: 100vh;
            }

            .container {
                flex: 1 0 auto;
                max-width: 1480px;
                padding: 0 2.5rem;
                margin: 3rem auto 0;
            }

            footer {
                margin-top: auto;
                padding: 4rem 2.5rem 2.5rem;
            }

            .footer-content {
                max-width: 1600px;
                gap: 3rem;
            }

            .footer-section h3 {
                font-size: 1.15rem;
            }

            .footer-section p,
            .footer-section a {
                font-size: 1rem;
            }

            .footer-bottom {
                margin-top: 3rem;
                padding-top: 2.25rem;
                font-size: 1rem;
            }
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
                <li><a href="order_history.php">My Orders</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
                <li class="cart-icon" id="cartIcon">🛒<span class="cart-count" id="cartCount">0</span></li>
            </ul>
        </nav>
    </div>
</header>
<main class="container">
    <section class="page-heading">
        <h1>Order <?php echo htmlspecialchars($order['order_id']); ?></h1>
        <p>Details for your selected order, including items, quantities, and totals.</p>
    </section>
    <div class="summary-grid">
        <div class="summary-card">
            <strong>Order Date</strong>
            <span><?php echo htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($order['order_date']))); ?></span>
        </div>
        <div class="summary-card">
            <strong>Status</strong>
            <span class="order-status <?php echo 'status-' . strtolower($order['status']); ?>"><?php echo htmlspecialchars($order['status']); ?></span>
        </div>
        <div class="summary-card">
            <strong>Total Amount</strong>
            <span>$<?php echo number_format($order['total_amount'], 2); ?></span>
        </div>
    </div>
    <article class="details-card">
        <div class="details-header">
            <div>
                <h2>Order Items</h2>
                <p style="color:#64748b;margin-top:.5rem;">Review each item purchased in this order.</p>
            </div>
            <a href="order_history.php" class="back-link">Back to Orders</a>
        </div>
        <div class="table-wrapper">
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
            <tbody>
                <?php foreach ($items as $it):
                    $itemPrice = isset($it['unit_price']) ? floatval($it['unit_price']) : (isset($it['price']) ? floatval($it['price']) : 0.0);
                    $itemSubtotal = $itemPrice * intval($it['quantity']);
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:0.75rem">
                            <?php if (!empty($it['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($it['image_url']); ?>" alt="<?php echo htmlspecialchars($it['product_name'] ?? 'Product'); ?>">
                            <?php endif; ?>
                            <div>
                                <div class="product-name"><?php echo htmlspecialchars($it['product_name'] ?? 'Product'); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>$<?php echo number_format($itemPrice, 2); ?></td>
                    <td><?php echo intval($it['quantity']); ?></td>
                    <td class="subtotal">$<?php echo number_format($itemSubtotal, 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>
    </article>
</main>

<div class="cart-modal" id="cartModal">
    <div class="cart-panel">
        <div class="cart-header">
            <h2>Your Cart</h2>
            <button class="close-btn" id="closeCart">&times;</button>
        </div>
        <div class="cart-items" id="cartItems">
            <p class="cart-empty">Your cart is empty</p>
        </div>
        <div class="cart-footer">
            <div class="cart-total">
                <span>Total</span>
                <span id="cartTotal">$0.00</span>
            </div>
            <button type="button" class="checkout-btn" onclick="checkoutCart()">Checkout</button>
        </div>
    </div>
</div>

<script>
    const cartKey = 'cart';
    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    const userId = <?php echo isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 'null'; ?>;
    let cart = [];

    function saveCart() {
        if (!isLoggedIn) {
            localStorage.setItem(cartKey, JSON.stringify(cart));
        }
    }

    function updateCartCount() {
        const count = cart.reduce((sum, item) => sum + (item.quantity || 0), 0);
        const countElement = document.getElementById('cartCount');
        if (countElement) {
            countElement.textContent = count;
        }
    }

    function updateCartDisplay() {
        const cartItems = document.getElementById('cartItems');
        const cartTotal = document.getElementById('cartTotal');
        if (!cartItems || !cartTotal) return;

        if (!cart || cart.length === 0) {
            cartItems.innerHTML = '<p class="cart-empty">Your cart is empty</p>';
            cartTotal.textContent = '$0.00';
            return;
        }

        cartItems.innerHTML = cart.map(item => `
            <div class="cart-item">
                <div class="cart-item-image">${item.image ? `<img src="${item.image}" alt="${item.name}">` : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#999;font-size:0.85rem;">No image</div>'}</div>
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">$${(item.price || 0).toFixed(2)}</div>
                    <div class="quantity-control">
                        <button class="qty-btn" type="button" onclick="updateQuantity(${item.product_id}, -1)">−</button>
                        <div class="qty-display">${item.quantity}</div>
                        <button class="qty-btn" type="button" onclick="updateQuantity(${item.product_id}, 1)">+</button>
                        <button class="remove-item" type="button" onclick="removeFromCart(${item.product_id})">Remove</button>
                    </div>
                </div>
            </div>
        `).join('');

        const total = cart.reduce((sum, item) => sum + ((item.price || 0) * (item.quantity || 0)), 0);
        cartTotal.textContent = `$${total.toFixed(2)}`;
    }

    function loadCart() {
        if (isLoggedIn && userId) {
            const guestCart = JSON.parse(localStorage.getItem(cartKey)) || [];
            const fetchCart = () => fetch('get_cart.php?user_id=' + userId)
                .then(res => res.json())
                .then(data => {
                    cart = data.cart || [];
                    updateCartCount();
                    updateCartDisplay();
                })
                .catch(() => {
                    cart = [];
                    updateCartCount();
                    updateCartDisplay();
                });

            if (guestCart.length > 0) {
                fetch('merge_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, cart: guestCart })
                }).then(() => {
                    localStorage.removeItem(cartKey);
                    return fetchCart();
                }).catch(() => {
                    cart = [];
                    updateCartCount();
                    updateCartDisplay();
                });
            } else {
                fetchCart();
            }
        } else {
            cart = JSON.parse(localStorage.getItem(cartKey)) || [];
            updateCartCount();
            updateCartDisplay();
        }
    }

    function updateQuantity(productId, change) {
        const item = cart.find(c => c.product_id === productId);
        if (!item) return;

        const availableStock = Number.isFinite(item.stock) ? item.stock : 0;
        if (change > 0 && availableStock <= 0) {
            alert('This product is out of stock.');
            return;
        }

        item.quantity = (item.quantity || 0) + change;
        if (item.quantity <= 0) {
            removeFromCart(productId);
            return;
        }

        if (item.quantity > availableStock) {
            item.quantity = availableStock;
            alert('There are only ' + availableStock + ' units available in stock.');
            if (availableStock > 0) {
                if (isLoggedIn && userId) {
                    fetch('update_cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: userId, product_id: productId, quantity: item.quantity })
                    }).then(() => loadCart());
                } else {
                    saveCart();
                    updateCartCount();
                    updateCartDisplay();
                }
            } else {
                removeFromCart(productId);
            }
            return;
        }

        if (isLoggedIn && userId) {
            fetch('update_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, product_id: productId, quantity: item.quantity })
            }).then(() => loadCart());
        } else {
            saveCart();
            updateCartCount();
            updateCartDisplay();
        }
    }

    function removeFromCart(productId) {
        cart = (cart || []).filter(item => item.product_id !== productId);
        if (isLoggedIn && userId) {
            fetch('remove_from_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, product_id: productId })
            }).then(() => loadCart());
        } else {
            saveCart();
            updateCartCount();
            updateCartDisplay();
        }
    }

    function checkoutCart() {
        window.location.href = 'cart.php';
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadCart();

        const cartIcon = document.getElementById('cartIcon');
        const cartModal = document.getElementById('cartModal');
        const closeCart = document.getElementById('closeCart');

        if (cartIcon && cartModal) {
            cartIcon.addEventListener('click', () => {
                updateCartDisplay();
                cartModal.classList.add('active');
            });
        }

        if (closeCart) {
            closeCart.addEventListener('click', () => {
                cartModal.classList.remove('active');
            });
        }

        if (cartModal) {
            cartModal.addEventListener('click', (e) => {
                if (e.target === cartModal) {
                    cartModal.classList.remove('active');
                }
            });
        }
    });
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
                    <li><a href="order_history.php">Order Tracking</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    
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
</body>
</html>
