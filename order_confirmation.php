<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
if (!$order_id) {
    header('Location: order_history.php');
    exit();
}

if (empty($_SESSION['user_id'])) {
    $redirect = 'order_confirmation.php?order_id=' . $order_id;
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

$stmt = $conn->prepare('SELECT full_name, email FROM users WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$customer = $stmt->get_result()->fetch_assoc();
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

$subtotal = 0;
$totalItems = 0;
foreach ($items as $item) {
    $itemSubtotal = floatval($item['unit_price']) * intval($item['quantity']);
    $subtotal += $itemSubtotal;
    $totalItems += intval($item['quantity']);
}
$shipping = $subtotal > 100 ? 0 : 10;
$tax = round($subtotal * 0.1, 2);
$displayTotal = number_format(floatval($order['total_amount']), 2);
$statusClass = strtolower($order['status']);
$customerName = $customer['full_name'] ?? ($_SESSION['full_name'] ?? 'Customer');
$customerEmail = $customer['email'] ?? ($_SESSION['email'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Order Confirmation - SM Store</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background:
                radial-gradient(circle at top, rgba(15, 98, 254, 0.08), transparent 28%),
                linear-gradient(180deg, #eff6ff 0%, #f8fbff 35%, #f4f7fb 100%);
            color: #111827;
            line-height: 1.6;
            animation: pageFadeIn 0.65s ease both;
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
            white-space: nowrap;
        }

        nav a:hover {
            opacity: 0.8;
        }

        .cart-icon {
            position: relative;
            cursor: pointer;
            font-size: 1.2rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            min-width: 1.4rem;
            min-height: 1.4rem;
            padding: 0.12rem;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #ff4444;
            color: white;
            border-radius: 999px;
            min-width: 20px;
            height: 20px;
            padding: 0 0.25rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
            font-weight: 700;
            line-height: 1;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.18);
        }

        .container {
            max-width: 1200px;
            margin: 2rem auto 4rem;
            padding: 0 1rem;
        }

        @media (max-width: 1024px) {
            .cart-icon {
                font-size: 1.15rem;
                line-height: 1;
                min-width: 1.35rem;
                min-height: 1.35rem;
                padding: 0.12rem;
            }

            .cart-count {
                top: -8px;
                right: -8px;
                min-width: 18px;
                height: 18px;
                font-size: 0.68rem;
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

            nav a {
                padding: 0.25rem 0.4rem;
            }

            .cart-icon {
                font-size: 1.15rem;
                line-height: 1;
                min-width: 1.35rem;
                min-height: 1.35rem;
                padding: 0.12rem;
            }

            .cart-count {
                top: -8px;
                right: -8px;
                min-width: 18px;
                height: 18px;
                font-size: 0.68rem;
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
                justify-content: center;
                gap: 0.5rem;
                font-size: 0.8rem;
                width: 100%;
            }

            nav a {
                width: 100%;
                text-align: center;
                padding: 0.5rem 0;
            }

            nav a.cart-icon {
                width: auto;
                min-width: 1.2rem;
                display: inline-flex;
                justify-content: center;
                padding: 0.15rem 0.2rem;
            }

            .cart-icon {
                font-size: 1.05rem;
                line-height: 1;
                min-width: 1.25rem;
                min-height: 1.25rem;
                padding: 0.12rem;
            }

            .cart-count {
                top: -8px;
                right: -8px;
                min-width: 16px;
                height: 16px;
                font-size: 0.62rem;
            }
        }

        @media (max-width: 425px) {
            .cart-icon {
                font-size: 1rem;
                min-width: 1.15rem;
                min-height: 1.15rem;
                padding: 0.12rem;
            }

            .cart-count {
                top: -8px;
                right: -8px;
                min-width: 16px;
                height: 16px;
                font-size: 0.62rem;
            }
        }

        @media (max-width: 375px) {
            .cart-icon {
                font-size: 1rem;
                min-width: 1.15rem;
                min-height: 1.15rem;
                padding: 0.12rem;
            }

            .cart-count {
                top: -8px;
                right: -8px;
                min-width: 16px;
                height: 16px;
                font-size: 0.62rem;
            }
        }

        @media (max-width: 320px) {
            .cart-icon {
                font-size: 0.92rem;
                min-width: 1rem;
                min-height: 1rem;
                padding: 0.08rem;
            }

            .cart-count {
                top: -10px;
                right: -10px;
                min-width: 15px;
                height: 15px;
                font-size: 0.58rem;
            }
        }

        @media (max-width: 360px) {
            .header-content {
                padding: 0.5rem;
            }

            .logo {
                font-size: 1rem;
            }

            .logo::before {
                width: 36px;
                height: 36px;
            }

            nav ul {
                gap: 0.3rem;
                font-size: 0.7rem;
            }

            nav a.cart-icon {
                width: auto;
                display: inline-flex;
                justify-content: center;
                padding: 0.12rem 0.18rem;
            }

            .cart-icon {
                font-size: 0.95rem;
                min-width: 1.1rem;
                min-height: 1.1rem;
                padding: 0.08rem;
            }

            .cart-count {
                top: -10px;
                right: -10px;
                min-width: 15px;
                height: 15px;
                font-size: 0.58rem;
            }
        }

        @media (min-width: 1440px) {
            .cart-icon {
                font-size: 1.25rem;
                min-width: 1.4rem;
                min-height: 1.4rem;
                padding: 0.12rem;
            }

            .cart-count {
                top: -8px;
                right: -8px;
                min-width: 20px;
                height: 20px;
                font-size: 0.75rem;
            }
        }

        @media (min-width: 2560px) {
            .cart-icon {
                font-size: 1.3rem;
                min-width: 1.45rem;
                min-height: 1.45rem;
                padding: 0.14rem;
            }

            .cart-count {
                top: -8px;
                right: -8px;
                min-width: 20px;
                height: 20px;
                font-size: 0.75rem;
            }
        }

        .page-shell {
            display: grid;
            gap: 1.5rem;
            animation: slideUp 0.55s ease both;
        }

        .hero-card {
            background: linear-gradient(135deg, rgba(255,255,255,0.98) 0%, rgba(237,244,255,0.98) 100%);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 28px;
            padding: 2rem;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.08);
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.25rem;
            position: relative;
            overflow: hidden;
        }

        .hero-card::after {
            content: "";
            position: absolute;
            inset: auto -20% -30% auto;
            width: 220px;
            height: 220px;
            background: radial-gradient(circle, rgba(15, 98, 254, 0.12) 0%, rgba(15, 98, 254, 0) 60%);
            pointer-events: none;
        }

        .hero-card h1 {
            font-size: clamp(2rem, 2.5vw, 2.75rem);
            margin-bottom: 0.75rem;
            color: #0f172a;
        }

        .hero-card p {
            max-width: 680px;
            color: #475569;
            font-size: 1rem;
        }

        .hero-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .hero-meta .meta-item {
            background: #ffffff;
            border-radius: 18px;
            padding: 1rem 1.25rem;
            border: 1px solid rgba(15, 23, 42, 0.08);
        }

        .hero-meta strong {
            display: block;
            color: #64748b;
            margin-bottom: 0.45rem;
            font-size: 0.95rem;
        }

        .hero-meta span {
            color: #0f172a;
            font-size: 1.05rem;
            font-weight: 700;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.55rem 0.9rem;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-approved { background: #dcfce7; color: #166534; }
        .status-paid { background: #dbeafe; color: #1e40af; }
        .status-shipped { background: #e0f2fe; color: #0c4a6e; }
        .status-cancelled { background: #fee2e2; color: #991b1b; }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.85rem;
            margin-top: 1.5rem;
        }

        .primary-btn,
        .secondary-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 48px;
            border-radius: 999px;
            padding: 0 1.6rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            text-decoration: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .primary-btn {
            background: #0f62fe;
            color: white;
            box-shadow: 0 16px 34px rgba(15, 98, 254, 0.18);
        }

        .secondary-btn {
            background: #f8fafc;
            color: #0f172a;
            border: 1px solid rgba(15, 23, 42, 0.10);
        }

        .primary-btn:hover,
        .secondary-btn:hover {
            transform: translateY(-1px);
        }

        .details-section {
            margin-top: 2rem;
            display: grid;
            gap: 1.5rem;
        }

        .details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.1rem;
            align-items: stretch;
        }

        .details-card {
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            gap: 0.9rem;
            background: linear-gradient(145deg, #ffffff 0%, #edf4ff 48%, #f8fbff 100%);
            border-radius: 24px;
            padding: 1.2rem;
            border: 1px solid rgba(148, 163, 184, 0.24);
            box-shadow: 0 18px 45px rgba(15, 23, 42, 0.10), inset 0 1px 0 rgba(255, 255, 255, 0.95);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .details-card::before {
            content: "";
            position: absolute;
            inset: 0 auto 0 0;
            width: 5px;
            background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%);
            border-radius: 0 18px 18px 0;
        }

        .details-card::after {
            content: "";
            position: absolute;
            right: -30px;
            bottom: -40px;
            width: 150px;
            height: 150px;
            background: radial-gradient(circle, rgba(59, 130, 246, 0.10) 0%, rgba(59, 130, 246, 0) 70%);
            pointer-events: none;
        }

        .details-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 22px 50px rgba(15, 23, 42, 0.14);
        }

        .details-card-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.15rem;
        }

        .card-title-wrap {
            display: grid;
            gap: 0.2rem;
        }

        .card-kicker {
            display: inline-flex;
            align-items: center;
            width: fit-content;
            padding: 0.35rem 0.6rem;
            border-radius: 999px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.10), rgba(148, 163, 184, 0.14));
            border: 1px solid rgba(37, 99, 235, 0.12);
            color: #1d4ed8;
            font-size: 0.72rem;
            font-weight: 800;
            letter-spacing: 0.18em;
            text-transform: uppercase;
        }

        .details-card h2 {
            font-size: 1.15rem;
            margin: 0;
            color: #0f172a;
            font-weight: 800;
            line-height: 1.25;
        }

        .card-copy {
            color: #64748b;
            font-size: 0.92rem;
            line-height: 1.45;
            max-width: 30ch;
        }

        .details-card p,
        .details-card li {
            color: #475569;
            font-size: 0.97rem;
        }

        .summary-list {
            list-style: none;
            display: grid;
            gap: 0.6rem;
            margin-top: 0.15rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            padding: 0.8rem 0.9rem;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, 0.15);
            background: rgba(248, 250, 252, 0.95);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.92);
        }

        .summary-row span:first-child {
            color: #475569;
            font-weight: 700;
        }

        .summary-row strong {
            color: #111827;
            font-weight: 800;
            font-size: 0.98rem;
        }

        .summary-row.total {
            background: linear-gradient(135deg, #eff6ff 0%, #ffffff 100%);
            border-color: rgba(37, 99, 235, 0.18);
        }

        .summary-row.total strong {
            color: #1d4ed8;
            font-size: 1.02rem;
        }

        .address-box {
            border-radius: 18px;
            padding: 1rem 1.05rem;
            background: linear-gradient(135deg, #f8fbff 0%, #edf5ff 100%);
            border: 1px solid rgba(37, 99, 235, 0.18);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9), 0 10px 20px rgba(15, 23, 42, 0.04);
            overflow-x: auto;
            overflow-y: hidden;
            white-space: normal;
        }

        .address-box p {
            color: #111827;
            font-weight: 700;
            line-height: 1.6;
            margin: 0;
            min-width: max-content;
        }

        .customer-grid {
            display: grid;
            gap: 0.75rem;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .customer-name {
            color: #0f172a;
            font-size: 1.02rem;
            font-weight: 800;
            margin: 0;
        }

        .customer-meta {
            color: #475569;
            font-size: 0.95rem;
            margin: 0;
            line-height: 1.5;
            min-width: max-content;
            word-break: break-word;
        }

        .order-table {
            width: 100%;
            border-collapse: collapse;
        }

        .order-table th,
        .order-table td {
            padding: 0.95rem 0.8rem;
            text-align: left;
            border-bottom: 1px solid rgba(148, 163, 184, 0.18);
        }

        .order-table th {
            color: #475569;
            font-weight: 800;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            background: linear-gradient(180deg, #f8fbff 0%, #edf4ff 100%);
        }

        .order-table tbody tr:last-child td {
            border-bottom: none;
        }

        .order-table tbody tr:hover {
            background: rgba(239, 246, 255, 0.55);
        }

        .order-table td {
            color: #334155;
            font-size: 0.96rem;
            vertical-align: middle;
        }

        .order-table .subtotal {
            font-weight: 800;
            color: #0f172a;
        }

        .item-summary-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            align-items: center;
        }

        .item-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 999px;
            padding: 0.35rem 0.65rem;
            background: linear-gradient(135deg, #eff6ff, #ffffff);
            border: 1px solid rgba(37, 99, 235, 0.16);
            color: #1e3a8a;
            font-size: 0.78rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .item-product {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            min-width: 0;
        }

        .item-product-thumb {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            overflow: hidden;
            background: linear-gradient(135deg, #dbeafe, #eff6ff);
            border: 1px solid rgba(148, 163, 184, 0.25);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.9);
        }

        .item-product-thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .item-product-thumb span {
            font-size: 1.05rem;
        }

        .item-name {
            color: #0f172a;
            font-weight: 800;
            line-height: 1.25;
        }

        .item-meta {
            color: #64748b;
            font-size: 0.88rem;
            margin-top: 0.1rem;
        }

        .totals-row {
            display: grid;
            gap: 0.75rem;
            margin-top: 1.5rem;
        }

        .totals-row div {
            display: flex;
            justify-content: space-between;
            color: #475569;
        }

        .totals-row div strong {
            color: #0f172a;
        }

        @keyframes pageFadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 980px) {
            .header-content {
                padding: 1rem;
            }

            nav ul {
                gap: 0.9rem;
            }
        }

        @media (max-width: 980px) {
            .details-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 820px) {
            .hero-card {
                padding: 1.5rem;
            }

            .details-card {
                padding: 1.1rem;
            }
        }

        @media (max-width: 640px) {
            .header-content {
                padding: 1rem;
            }

            .hero-meta,
            .details-grid {
                grid-template-columns: 1fr;
            }

            .details-card-header {
                flex-direction: column;
            }

            .item-summary-row {
                width: 100%;
            }

            .order-table th,
            .order-table td {
                padding: 0.8rem 0.45rem;
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
                <li><a href="profile.php">My Profile</a></li>
                <li><a href="logout.php">Logout</a></li>
                <li><a href="cart.php" class="cart-icon" id="cartIcon" aria-label="View cart">🛒<span class="cart-count" id="cartCount">0</span></a></li>
            </ul>
        </nav>
    </div>
</header>
<main class="container">
    <div class="page-shell">
    <section class="hero-card">
        <div>
            <h1>Order Confirmed</h1>
            <p>Your order is confirmed and being processed. You can review the full details below or continue shopping for more great products.</p>
        </div>
        <div class="hero-meta">
            <div class="meta-item">
                <strong>Order Number</strong>
                <span><?php echo htmlspecialchars($order_id); ?></span>
            </div>
            <div class="meta-item">
                <strong>Order Date</strong>
                <span><?php echo htmlspecialchars(date('F j, Y \a\t g:i A', strtotime($order['order_date']))); ?></span>
            </div>
            <div class="meta-item">
                <strong>Status</strong>
                <span class="status-pill status-<?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($order['status']); ?></span>
            </div>
            <div class="meta-item">
                <strong>Items</strong>
                <span><?php echo htmlspecialchars($totalItems); ?> products</span>
            </div>
        </div>
        <div class="hero-actions">
            <a href="products.php" class="primary-btn">Continue Shopping</a>
            <a href="order_history.php" class="secondary-btn">View Orders</a>
        </div>
    </section>

    <section class="details-section">
        <div class="details-grid">
            <article class="details-card">
                <div class="details-card-header">
                    <div class="card-title-wrap">
                        <span class="card-kicker">Summary</span>
                        <h2>Order Summary</h2>
                        <p class="card-copy">A clear summary of your order totals and payment details.</p>
                    </div>
                </div>
                <ul class="summary-list">
                    <li class="summary-row"><span>Subtotal</span><strong>$<?php echo number_format($subtotal, 2); ?></strong></li>
                    <li class="summary-row"><span>Shipping</span><strong>$<?php echo number_format($shipping, 2); ?></strong></li>
                    <li class="summary-row"><span>Tax (10%)</span><strong>$<?php echo number_format($tax, 2); ?></strong></li>
                    <li class="summary-row total"><span>Total Paid</span><strong>$<?php echo $displayTotal; ?></strong></li>
                </ul>
            </article>

            <article class="details-card">
                <div class="details-card-header">
                    <div class="card-title-wrap">
                        <span class="card-kicker">Delivery</span>
                        <h2>Shipping Address</h2>
                        <p class="card-copy">Your delivery destination for this order, ready for shipment.</p>
                    </div>
                </div>
                <div class="address-box">
                    <p><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></p>
                </div>
            </article>

            <article class="details-card">
                <div class="details-card-header">
                    <div class="card-title-wrap">
                        <span class="card-kicker">Account</span>
                        <h2>Customer</h2>
                        <p class="card-copy">Your account information and current order status at a glance.</p>
                    </div>
                </div>
                <div class="customer-grid">
                    <p class="customer-name"><?php echo htmlspecialchars($customerName); ?></p>
                    <p class="customer-meta">Email: <?php echo htmlspecialchars($customerEmail); ?></p>
                    <p class="customer-meta">Status: <?php echo htmlspecialchars($order['status']); ?></p>
                    <p class="customer-meta">Order created on <?php echo htmlspecialchars(date('F j, Y', strtotime($order['order_date']))); ?></p>
                </div>
            </article>
        </div>
    </section>

    <section class="details-card" style="margin-top: 1.5rem;">
        <div class="details-card-header" style="align-items:flex-start;">
            <div class="card-title-wrap">
                <span class="card-kicker">Items</span>
                <h2>Items in this Order</h2>
                <p class="card-copy">A quick review of the items included in your order.</p>
            </div>
            <div class="item-summary-row">
                <span class="item-badge"><?php echo intval($totalItems); ?> item<?php echo $totalItems === 1 ? '' : 's'; ?></span>
                <span class="item-badge">Total $<?php echo $displayTotal; ?></span>
            </div>
        </div>
        <div style="overflow-x:auto;margin-top:1rem;">
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item):
                        $rowSubtotal = floatval($item['unit_price']) * intval($item['quantity']);
                    ?>
                    <tr>
                        <td>
                            <div class="item-product">
                                <div class="item-product-thumb">
                                    <?php if (!empty($item['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?>">
                                    <?php else: ?>
                                        <span>📦</span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="item-name"><?php echo htmlspecialchars($item['product_name'] ?? 'Product'); ?></div>
                                    <div class="item-meta">Item from order <?php echo htmlspecialchars($order_id); ?></div>
                                </div>
                            </div>
                        </td>
                        <td>$<?php echo number_format(floatval($item['unit_price']), 2); ?></td>
                        <td><?php echo intval($item['quantity']); ?></td>
                        <td class="subtotal">$<?php echo number_format($rowSubtotal, 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    </div>
</main>
<script>
    function loadCartCount() {
        fetch('get_cart.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.cart)) {
                    const total = data.cart.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
                    document.getElementById('cartCount').textContent = total;
                }
            })
            .catch(() => {
                document.getElementById('cartCount').textContent = '0';
            });
    }

    document.addEventListener('DOMContentLoaded', loadCartCount);
</script>
</body>
</html>
