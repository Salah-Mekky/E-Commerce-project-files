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

$edit_product = null;

if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $product_id = intval($_GET['edit']);
    $stmt = $conn->prepare('SELECT * FROM products WHERE product_id = ?');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_product = $result->fetch_assoc();
    $stmt->close();
}

if (isset($_POST['add_product'])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');

    if (!$name || $price <= 0 || $category_id <= 0) {
        $_SESSION['flash_error'] = 'Please fill in all required fields with valid values.';
    } else {
        $stmt = $conn->prepare('INSERT INTO products (name, description, price, stock_quantity, image_url, category_id) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->bind_param('ssdisi', $name, $description, $price, $stock_quantity, $image_url, $category_id);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Product added successfully!';
        } else {
            $_SESSION['flash_error'] = 'Failed to add product.';
        }
        $stmt->close();
    }
    header('Location: admin_products.php');
    exit();
}

if (isset($_POST['update_product'])) {
    $product_id = intval($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $image_url = trim($_POST['image_url'] ?? '');

    if (!$name || $price <= 0 || $category_id <= 0) {
        $_SESSION['flash_error'] = 'Please fill in all required fields with valid values.';
    } else {
        $stmt = $conn->prepare('UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ?, image_url = ?, category_id = ? WHERE product_id = ?');
        $stmt->bind_param('ssdisii', $name, $description, $price, $stock_quantity, $image_url, $category_id, $product_id);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Product updated successfully!';
        } else {
            $_SESSION['flash_error'] = 'Failed to update product.';
        }
        $stmt->close();
    }
    header('Location: admin_products.php');
    exit();
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $product_id = intval($_GET['delete']);

    $checkStmt = $conn->prepare('SELECT COUNT(*) AS existing_orders FROM order_items WHERE product_id = ?');
    $checkStmt->bind_param('i', $product_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $row = $checkResult->fetch_assoc();
    $checkStmt->close();

    if ($row && intval($row['existing_orders']) > 0) {
        $_SESSION['flash_error'] = 'Cannot delete this product because it is referenced by existing orders.';
    } else {
        $stmt = $conn->prepare('DELETE FROM products WHERE product_id = ?');
        $stmt->bind_param('i', $product_id);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Product deleted successfully!';
        } else {
            if ($conn->errno === 1451) {
                $_SESSION['flash_error'] = 'Cannot delete this product because it is referenced by existing data.';
            } else {
                $_SESSION['flash_error'] = 'Failed to delete product: ' . $conn->error;
            }
        }
        $stmt->close();
    }
    header('Location: admin_products.php');
    exit();
}

$products = getAllProducts($conn);
$categories = getAllCategories($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Store - Manage Products</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            overflow-y: scroll;
            min-height: 100%;
        }

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

        .products-table {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .page-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.5rem;
            color: #333;
        }

        .action-bar {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .secondary-btn,
        .delete-btn,
        .primary-btn,
        .mark-btn {
            padding: 0.9rem 1.25rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.25s ease;
            text-decoration: none;
            color: white;
            display: inline-block;
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

        .delete-btn {
            background: #d32f2f;
        }

        .delete-btn:hover {
            background: #b71c1c;
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

        .grid-two {
            display: grid;
            grid-template-columns: 1fr 360px;
            gap: 1.5rem;
        }

        .panel {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border-radius: 18px;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.12);
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
            border-radius: 16px;
            overflow: hidden;
            background: #fff;
        }

        th,
        td {
            padding: 1rem;
            border-bottom: 1px solid #e5edf6;
            text-align: left;
            vertical-align: middle;
            color: #334155;
            background-color: rgba(255,255,255,0.96);
        }

        th {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
            font-weight: 700;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            box-shadow: inset 0 -1px 0 rgba(255,255,255,0.04);
        }

        tbody tr {
            transition: background-color 0.18s ease, box-shadow 0.18s ease;
        }

        tbody tr:hover {
            background: linear-gradient(90deg, #f8fbff 0%, #eff6ff 100%);
            box-shadow: inset 0 0 0 1px rgba(148, 163, 184, 0.12);
        }

        .product-image {
            width: 72px;
            height: 72px;
            min-width: 72px;
            min-height: 72px;
            background: linear-gradient(135deg, #f8fbff 0%, #eef6ff 100%);
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 0;
            border: 1px solid #dbeafe;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
        }

        td.product-image {
            vertical-align: middle;
            margin-top: 20px;
        }

        .product-image img {
            display: block;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            border-radius: 10px;
        }

        .form-group {
            display: grid;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        label {
            font-weight: 600;
            color: #333;
        }

        input,
        textarea,
        select {
            width: 100%;
            padding: 0.9rem 1rem;
            border-radius: 10px;
            border: 1px solid #d9e2ec;
            background: #fff;
            font-size: 0.98rem;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .table-actions a,
        .table-actions button {
            font-size: 0.9rem;
        }

        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            z-index: 1200;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .modal.active { display: flex; }
        .modal-panel {
            width: min(100%, 760px);
            max-width: 760px;
            max-height: calc(100vh - 2rem);
            overflow-y: auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
            padding: 1.25rem 1.5rem;
        }
        .modal-header { display:flex; justify-content:space-between; align-items:center; gap:1rem; margin-bottom:0.5rem; }
        .modal-title { font-size:1.25rem; font-weight:700; }
        .modal-close { background:transparent; border:none; font-size:1.4rem; cursor:pointer; }

        .products-table {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        table {
            min-width: 720px;
            width: 100%;
            border-collapse: collapse;
        }

        @media (max-width: 980px) {
            .main-content {
                padding: 0 1rem;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
                text-align: left;
            }

            .page-title {
                font-size: 1.8rem;
            }

            .action-bar {
                width: 100%;
                justify-content: flex-start;
            }

            .panel {
                padding: 1.25rem;
            }

            .products-table {
                overflow-x: auto;
            }

            table {
                min-width: 100%;
            }

            th,
            td {
                padding: 0.85rem;
            }

            .product-image {
                width: 58px;
                height: 58px;
                min-width: 58px;
                min-height: 58px;
            }

            .page-header .action-bar {
                flex-wrap: wrap;
            }
        }

        @media (max-width: 820px) {
            .grid-two {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 0 1rem;
            }
        }

        @media (max-width: 680px) {
            .header-content {
                flex-direction: column;
                align-items: stretch;
                text-align: center;
            }

            nav ul {
                width: 100%;
                justify-content: center;
            }

            nav li {
                flex: 1 1 auto;
            }

            nav a {
                display: inline-block;
                width: 100%;
                text-align: center;
                padding: 0.65rem 0;
            }

            .page-header {
                align-items: flex-start;
            }

            .page-title {
                font-size: 1.6rem;
            }

            .action-bar {
                justify-content: center;
                gap: 0.75rem;
            }

            .panel {
                padding: 1rem;
            }

            .table-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .product-image {
                width: 48px;
                height: 48px;
                min-width: 48px;
                min-height: 48px;
            }

            th,
            td {
                padding: 0.75rem;
            }

            .modal-panel {
                width: min(100%, 100%);
                max-width: 100%;
            }
        }

        @media (max-width: 520px) {
            body {
                font-size: 14px;
            }

            .header-content {
                padding: 0.75rem 0.75rem;
            }

            .logo {
                font-size: 1.2rem;
                width: 100%;
                justify-content: center;
            }

            nav ul {
                gap: 0.5rem;
                font-size: 0.8rem;
            }

            .page-header {
                gap: 0.75rem;
            }

            .page-title {
                font-size: 1.4rem;
            }

            .panel {
                padding: 0.95rem;
            }

            .action-bar {
                flex-direction: column;
                width: 100%;
            }

            .primary-btn,
            .secondary-btn,
            .delete-btn {
                width: 100%;
            }

            table {
                font-size: 0.85rem;
            }

            th,
            td {
                padding: 0.65rem;
            }

            .product-image {
                width: 42px;
                height: 42px;
                min-width: 42px;
                min-height: 42px;
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
            width: min(100%, 1400px);
            max-width: 1400px;
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
        <h1 class="page-title">Product Management</h1>
        <div class="action-bar">
            <a href="admin_dashboard.php" class="secondary-btn">Back to Dashboard</a>
            <?php if ($edit_product): ?>
                <a href="admin_products.php" class="secondary-btn">Create New Product</a>
            <?php endif; ?>
        </div>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="panel">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem;">
            <h2>Products</h2>
            <div>
                <button type="button" id="addProductBtn" class="primary-btn">Add Product</button>
            </div>
        </div>

        <div class="products-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Category</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="7">No products available.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <?php
                                $prodData = json_encode([
                                    'product_id' => (int)$product['product_id'],
                                    'name' => $product['name'] ?? '',
                                    'description' => $product['description'] ?? '',
                                    'price' => (float)($product['price'] ?? 0),
                                    'stock_quantity' => (int)($product['stock_quantity'] ?? 0),
                                    'category_id' => (int)($product['category_id'] ?? 0),
                                    'image_url' => $product['image_url'] ?? ''
                                ]);
                            ?>
                            <tr data-product="<?php echo htmlspecialchars($prodData, ENT_QUOTES, 'UTF-8'); ?>">
                                <td><?php echo (int)$product['product_id']; ?></td>
                                <td class="product-image"><img src="<?php echo htmlspecialchars($product['image_url'] ?? ''); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>"></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td>$<?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo (int)$product['stock_quantity']; ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></td>
                                <td>
                                    <div class="table-actions">
                                        <button type="button" class="secondary-btn edit-btn">Edit</button>
                                        <a href="admin_products.php?delete=<?php echo (int)$product['product_id']; ?>" class="delete-btn" onclick="return confirm('Delete this product?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal" id="productModal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="productModalTitle">
        <div class="modal-panel" role="document" tabindex="-1">
            <div class="modal-header">
                <h3 class="modal-title" id="productModalTitle">Add Product</h3>
                <button class="modal-close" id="closeProductModal" aria-label="Close product dialog">&times;</button>
            </div>

            <form id="productForm" method="post" action="admin_products.php">
                <input type="hidden" name="product_id" id="product_id" value="">

                <div class="form-group">
                    <label for="modal_name">Product Name</label>
                    <input type="text" id="modal_name" name="name" required>
                </div>

                <div class="form-group">
                    <label for="modal_description">Description</label>
                    <textarea id="modal_description" name="description"></textarea>
                </div>

                <div class="form-group">
                    <label for="modal_price">Price</label>
                    <input type="number" step="0.01" id="modal_price" name="price" required>
                </div>

                <div class="form-group">
                    <label for="modal_stock_quantity">Stock Quantity</label>
                    <input type="number" id="modal_stock_quantity" name="stock_quantity" required>
                </div>

                <div class="form-group">
                    <label for="modal_category_id">Category</label>
                    <select id="modal_category_id" name="category_id" required>
                        <option value="">Select category</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo (int)$category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="modal_image_url">Image URL</label>
                    <input type="text" id="modal_image_url" name="image_url">
                </div>

                <div style="display:flex; gap:0.75rem; justify-content:flex-end; margin-top:0.5rem;">
                    <button type="button" class="secondary-btn" id="cancelProduct">Cancel</button>
                    <button type="submit" id="productSubmit" class="primary-btn" name="add_product">Add Product</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function(){
            const modal = document.getElementById('productModal');
            const panel = modal.querySelector('.modal-panel');
            const openBtn = document.getElementById('addProductBtn');
            const closeBtn = document.getElementById('closeProductModal');
            const cancelBtn = document.getElementById('cancelProduct');
            const title = document.getElementById('productModalTitle');
            const submit = document.getElementById('productSubmit');
            const form = document.getElementById('productForm');

            function openAdd() {
                form.reset();
                document.getElementById('product_id').value = '';
                title.textContent = 'Add Product';
                submit.name = 'add_product';
                submit.textContent = 'Add Product';
                modal.setAttribute('aria-hidden', 'false');
                modal.classList.add('active');
                document.getElementById('modal_name').focus();
            }

            function openEdit(data) {
                document.getElementById('product_id').value = data.product_id || '';
                document.getElementById('modal_name').value = data.name || '';
                document.getElementById('modal_description').value = data.description || '';
                document.getElementById('modal_price').value = data.price || '';
                document.getElementById('modal_stock_quantity').value = data.stock_quantity || '';
                document.getElementById('modal_category_id').value = data.category_id || '';
                document.getElementById('modal_image_url').value = data.image_url || '';
                title.textContent = 'Edit Product';
                submit.name = 'update_product';
                submit.textContent = 'Update Product';
                modal.setAttribute('aria-hidden', 'false');
                modal.classList.add('active');
                document.getElementById('modal_name').focus();
            }

            function closeModal() {
                modal.setAttribute('aria-hidden', 'true');
                modal.classList.remove('active');
                openBtn.focus();
            }

            openBtn.addEventListener('click', openAdd);
            closeBtn.addEventListener('click', closeModal);
            cancelBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && modal.classList.contains('active')) closeModal(); });

            document.querySelectorAll('.edit-btn').forEach(function(btn){
                btn.addEventListener('click', function(e){
                    const tr = e.currentTarget.closest('tr');
                    if (!tr) return;
                    try {
                        const data = JSON.parse(tr.getAttribute('data-product'));
                        openEdit(data);
                    } catch (err) {
                        console.error('Invalid product data', err);
                    }
                });
            });

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
        })();
    </script>
</main>



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
</body>
</html>
