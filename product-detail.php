<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
$conn = getDBConnection();
$product = null;
$relatedProducts = [];
if (isset($_GET['id'])) {
    $product = getProductById($conn, intval($_GET['id']));
    if ($product) {
        $relatedProducts = getRelatedProducts($conn, intval($product['product_id']), intval($product['category_id']));
    }
}
$loggedInUserId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
$cartCount = 0;
if ($loggedInUserId) {
    $cartCount = 0;
}
$img = 'products-imgs/no-image.png';
if ($product) {
    if (!empty($product['image_url'])) {
        $img = htmlspecialchars($product['image_url']);
    }
    if (!filter_var($img, FILTER_VALIDATE_URL) && !file_exists($img)) {
        $img = 'products-imgs/no-image.png';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['name']) . ' - SM Store' : 'Product Details - SM Store'; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
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

        .cart-icon {
            position: relative;
            cursor: pointer;
            font-size: 1.2rem;
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

        .breadcrumb {
            background: white;
            padding: 1rem 2rem;
            max-width: 1200px;
            margin: 0 auto;
            font-size: 0.9rem;
        }

        .breadcrumb a {
            color: #0066cc;
            text-decoration: none;
            margin: 0 0.4rem;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .product-detail {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            margin-bottom: 3rem;
        }

        .product-detail-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            padding: 2rem;
        }

        .product-image-section {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .main-image {
            width: 100%;
            height: 420px;
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            background: #fff;
        }

        .product-info {
            display: flex;
            flex-direction: column;
        }

        .product-category {
            display: inline-block;
            background: #e3f2fd;
            color: #0066cc;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 1rem;
            width: fit-content;
        }

        .product-title {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .price-section {
            padding: 1rem 0;
            margin-bottom: 1rem;
        }

        .price {
            font-size: 2.5rem;
            color: #0066cc;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stock-status {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 600;
            width: fit-content;
        }

        .in-stock {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .low-stock {
            background: #fff3e0;
            color: #e65100;
        }

        .out-stock {
            background: #ffebee;
            color: #c62828;
        }

        .description-section {
            margin-bottom: 2rem;
        }

        .description-section h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .description-section p {
            color: #666;
            line-height: 1.8;
            margin-bottom: 1rem;
        }

        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            direction: ltr;
        }

        .quantity-label {
            font-weight: 600;
            color: #333;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            border: 2px solid #ddd;
            border-radius: 6px;
            overflow: hidden;
            direction: ltr;
        }

        .qty-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: white;
            cursor: pointer;
            font-size: 1.2rem;
            font-weight: bold;
            color: #0066cc;
            transition: all 0.3s;
        }

        .qty-btn:hover {
            background: #e3f2fd;
        }

        .qty-input {
            width: 80px;
            border: none;
            text-align: center;
            font-size: 1rem;
            font-weight: 600;
            padding: 0.5rem;
            direction: ltr;
            font-family: inherit;
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }

        .btn-add-cart {
            background: #ff6b35;
            color: white;
        }

        .btn-add-cart:hover {
            background: #e85a2a;
        }

        .btn-back {
            background: #f0f0f0;
            color: #333;
        }

        .btn-back:hover {
            background: #e0e0e0;
        }

        .cart-modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.7);
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
            border-bottom: 1px solid #eee;
        }

        .cart-header h2 {
            font-size: 1.25rem;
            color: #333;
        }

        .close-btn {
            border: none;
            background: transparent;
            font-size: 1.6rem;
            cursor: pointer;
            color: #333;
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
            width: 75px;
            height: 75px;
            background: #f5f5f5;
            border-radius: 12px;
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
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #333;
        }

        .cart-item-price {
            color: #0066cc;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .cart-item .qty-btn {
            color: #333;
        }

        .remove-item {
            border: none;
            background: none;
            color: #ff4d4d;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 0;
        }

        .cart-footer {
            padding: 1.5rem;
            border-top: 1px solid #eee;
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
            border: none;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            transition: background-color 0.3s;
            font-size: 1rem;
        }

        .checkout-btn:hover {
            background: #e85a2a;
        }

        .no-product {
            padding: 4rem;
            text-align: center;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
        }

        .related-products {
            margin-top: 3rem;
        }

        .related-products h2 {
            margin-bottom: 1.5rem;
            color: #333;
            font-size: 1.8rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s, box-shadow 0.3s;
            display: flex;
            flex-direction: column;
            border: 1px solid #eee;
            min-height: 100%;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .product-image {
            width: 100%;
            height: 220px;
            background: #f8f9fa;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
        }

        .product-info-card {
            padding: 1rem;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            flex: 1;
        }

        .product-name {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            min-height: 2.4em;
        }

        .product-price {
            color: #0066cc;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .product-actions {
            margin-top: auto;
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .btn-small {
            flex: 1;
            padding: 0.75rem 0.85rem;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.3s, color 0.3s;
            min-width: 0;
        }

        .btn-details {
            background: #f0f0f0;
            color: #333;
        }

        .btn-details:hover {
            background: #e0e0e0;
        }

        .cart-footer {
            padding: 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .checkout-btn {
            display: inline-flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            padding: 1rem;
            background: #ff6b35;
            border: none;
            color: white;
            text-decoration: none;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 700;
            transition: background-color 0.3s;
            font-size: 1rem;
            font-family: inherit;
        }

        .checkout-btn:hover {
            background: #e85a2a;
        }

        footer {
            background: linear-gradient(180deg, #1a1a1a 0%, #0f0f0f 100%);
            color: white;
            padding: 3rem 2rem;
            margin-top: 4rem;
            border-top: 2px solid #0066cc;
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

        @media (max-width: 768px) {
            .header-content {
                padding: 1rem;
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.75rem;
            }

            .logo {
                font-size: 1.4rem;
            }

            nav ul {
                gap: 0.9rem;
                font-size: 0.95rem;
                flex-wrap: wrap;
                justify-content: center;
                width: 100%;
            }

            nav a {
                padding: 0.25rem 0.4rem;
            }

            .container {
                padding: 1.5rem;
            }

            .product-detail-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .main-image {
                height: 320px;
            }

            .product-info {
                width: 100%;
            }

            .action-buttons,
            .product-actions {
                width: 100%;
            }

            .cart-panel {
                width: min(100%, 520px);
            }

            .related-products {
                margin-top: 2rem;
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

            nav ul {
                justify-content: center;
                gap: 0.5rem;
                width: 100%;
            }

            nav a {
                width: 100%;
                text-align: center;
                padding: 0.5rem;
                font-size: 0.8rem;
            }

            .logo {
                font-size: 1.2rem;
                width: 100%;
                justify-content: center;
            }

            .logo::before {
                font-size: 1.2rem;
            }

            nav ul {
                gap: 0.5rem;
                font-size: 0.8rem;
                width: 100%;
            }

            .container {
                padding: 1rem;
            }

            .product-detail-content {
                gap: 1rem;
            }

            .main-image {
                height: 260px;
            }

            .price {
                font-size: 2rem;
            }

            .quantity-controls {
                gap: 0.5rem;
            }

            .qty-input {
                width: 60px;
            }

            .action-buttons {
                flex-direction: column;
                width: 100%;
            }

            .btn {
                width: 100%;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .product-image {
                height: 200px;
            }

            .footer-content {
                gap: 1.5rem;
            }
        }

        @media (max-width: 425px) {
            .header-content {
                padding: 0.75rem 0.5rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .logo {
                width: 100%;
                justify-content: center;
                font-size: 1.2rem;
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
                padding: 0.5rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 375px) {
            .header-content {
                padding: 0.5rem;
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
                padding: 0.5rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 320px) {
            .header-content {
                padding: 0.5rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .logo {
                font-size: 1rem;
                width: 100%;
                justify-content: center;
            }

            .logo::before {
                font-size: 1rem;
            }

            nav ul {
                justify-content: center;
                gap: 0.3rem;
                font-size: 0.7rem;
                width: 100%;
            }

            nav a {
                width: 100%;
                text-align: center;
                padding: 0.5rem;
                font-size: 0.7rem;
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
                font-size: 1rem;
            }

            nav ul {
                gap: 0.3rem;
                font-size: 0.7rem;
            }

            .main-image {
                height: 220px;
            }

            .container {
                padding: 0.75rem;
            }

            .product-title {
                font-size: 1.5rem;
            }

            .price {
                font-size: 1.7rem;
            }

            .action-buttons,
            .product-actions {
                width: 100%;
            }

            .btn {
                width: 100%;
            }

            .product-detail-content {
                gap: 0.9rem;
            }

            .qty-input {
                width: 55px;
            }

            .footer {
                padding: 2rem 1rem;
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
                        <?php else: ?>
                            <li><a href="profile.php">My Profile</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login/Register</a></li>
                    <?php endif; ?>
                    <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                    <li class="cart-icon" id="cartIcon">
                        🛒
                        <span class="cart-count" id="cartCount">0</span>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <div class="container">
        <?php if ($product): ?>
            <div class="product-detail">
                <div class="product-detail-content">
                    <div class="product-image-section">
                        <div class="main-image">
                            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        </div>
                    </div>
                    <div class="product-info">
                        <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        <div class="product-title"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="price-section">
                            <span class="price">$<?php echo number_format($product['price'], 2); ?></span>
                        </div>
                        <div class="stock-status <?php echo ($product['stock_quantity'] > 10 ? 'in-stock' : ($product['stock_quantity'] > 0 ? 'low-stock' : 'out-stock')); ?>" id="stockStatus">
                            <?php
                                if ($product['stock_quantity'] > 10) {
                                    echo 'In Stock';
                                } elseif ($product['stock_quantity'] > 0) {
                                    echo 'Low Stock';
                                } else {
                                    echo 'Out of Stock';
                                }
                            ?>
                        </div>
                        <div class="description-section">
                            <h3>Description</h3>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                        <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                        <div class="quantity-selector">
                            <label class="quantity-label" for="quantityInput">Quantity:</label>
                            <div class="quantity-controls">
                                <button class="qty-btn" type="button" onclick="decreaseQuantity()">-</button>
                                <input type="text" id="quantityInput" name="quantity" class="qty-input" dir="ltr" lang="en" inputmode="numeric" pattern="[0-9]*" autocomplete="off" value="1" min="1" max="<?php echo max(1, intval($product['stock_quantity'])); ?>" step="1" oninput="this.value = normalizeDigits(this.value).replace(/[^0-9]/g, '');">
                                <button class="qty-btn" type="button" onclick="increaseQuantity()">+</button>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="quantity-selector">
                            <label class="quantity-label">Stock Available:</label>
                            <div style="padding: 1rem; background: #e3f2fd; border-radius: 8px; font-size: 1.2rem; font-weight: 600; color: #0066cc;">
                                <?php echo intval($product['stock_quantity']); ?> units
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="action-buttons">
                            <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                            <button class="btn btn-add-cart" id="addToCartBtn" type="button" onclick="addToCart(<?php echo intval($product['product_id']); ?>)">Add to Cart</button>
                            <?php endif; ?>
                            <a href="products.php" class="btn btn-back">Back to Products</a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($relatedProducts)): ?>
                <div class="related-products">
                    <h2>Related Products</h2>
                    <div class="products-grid">
                        <?php foreach ($relatedProducts as $related): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <img src="<?php echo !empty($related['image_url']) ? htmlspecialchars($related['image_url']) : 'products-imgs/no-image.png'; ?>" alt="<?php echo htmlspecialchars($related['name']); ?>" style="width:100%;height:100%;object-fit:contain;object-position:center;background:#fff;">
                                </div>
                                <div class="product-info-card">
                                    <div class="product-name"><?php echo htmlspecialchars($related['name']); ?></div>
                                    <div class="product-price">$<?php echo number_format($related['price'], 2); ?></div>
                                    <div class="product-actions">
                                        <a href="product-detail.php?id=<?php echo intval($related['product_id']); ?>" class="btn btn-small btn-details">View</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-product">
                <h2>Product not found</h2>
                <p>The product you are looking for does not exist or has been removed.</p>
                <a href="products.php" class="btn btn-back">Back to Products</a>
            </div>
        <?php endif; ?>
    </div>

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
                    <li><img src="Icons/email.png" alt="Email Icon"> slahmikki00720@gmail.com</li>
                    <li><img src="Icons/phone.png" alt="Phone Icon"> +970-592552356</li>
                    <li><img src="Icons/clock.png" alt="Clock Icon"> Sat - Thu: 9AM - 6PM</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 SM E-commerce Store. All rights reserved.</p>
        </div>
    </footer>

    <div class="cart-modal" id="cartModal">
        <div class="cart-panel">
            <div class="cart-header">
                <h2>Your Cart</h2>
                <button class="close-btn" id="closeCart">&times;</button>
            </div>
            <div class="cart-items" id="cartItems">
                <p class="cart-empty">Loading cart...</p>
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
        const userId = <?php echo $loggedInUserId !== null ? intval($loggedInUserId) : 'null'; ?>;
        const productId = <?php echo $product ? intval($product['product_id']) : 0; ?>;
        const productName = '<?php echo $product ? addslashes($product['name']) : ''; ?>';
        const productPrice = <?php echo $product ? floatval($product['price']) : 0; ?>;
        const productImage = '<?php echo $product ? addslashes($img) : 'products-imgs/no-image.png'; ?>';
        const productStock = <?php echo $product ? intval($product['stock_quantity']) : 0; ?>;
        const cartKey = 'cart';
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        const userRole = <?php echo isset($_SESSION['role']) ? json_encode($_SESSION['role']) : 'null'; ?>;
        const isAdmin = userRole === 'admin';
        const userIdForCart = <?php echo isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 'null'; ?>;

        let cart = [];

        function loadCart() {
            if (isLoggedIn && !isAdmin) {
                const guestCart = JSON.parse(localStorage.getItem(cartKey)) || [];
                const fetchCart = () => fetch('get_cart.php?user_id=' + userIdForCart)
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
                        body: JSON.stringify({ user_id: userIdForCart, cart: guestCart })
                    })
                    .then(() => {
                        localStorage.removeItem(cartKey);
                        return fetchCart();
                    })
                    .catch(() => {
                        cart = [];
                        updateCartCount();
                        updateCartDisplay();
                    });
                } else {
                    fetchCart();
                }
            } else if (isAdmin) {
                localStorage.removeItem(cartKey);
                cart = [];
                updateCartCount();
                updateCartDisplay();
            } else {
                cart = JSON.parse(localStorage.getItem(cartKey)) || [];
                updateCartCount();
                updateCartDisplay();
            }
        }

        function saveCart() {
            if (!isLoggedIn) {
                localStorage.setItem(cartKey, JSON.stringify(cart));
            }
        }

        function updateCartCount() {
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            const countElement = document.getElementById('cartCount');
            if (countElement) {
                countElement.textContent = count;
            }
        }

        function updateCartDisplay() {
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');
            if (!cartItems || !cartTotal) return;

            if (cart.length === 0) {
                cartItems.innerHTML = '<p class="cart-empty">Your cart is empty</p>';
                cartTotal.textContent = '$0.00';
                return;
            }

            cartItems.innerHTML = cart.map(item => `
                <div class="cart-item">
                    <div class="cart-item-image">${item.image ? `<img src="${item.image}" alt="${item.name}">` : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#999;font-size:0.85rem;">No image</div>'}</div>
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                        <div class="quantity-control">
                            <button class="qty-btn" type="button" onclick="updateQuantity(${item.product_id}, -1)">-</button>
                            <div class="qty-display">${item.quantity}</div>
                            <button class="qty-btn" type="button" onclick="updateQuantity(${item.product_id}, 1)">+</button>
                            <button class="remove-item" type="button" onclick="removeFromCart(${item.product_id})">Remove</button>
                        </div>
                    </div>
                </div>
            `).join('');

            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            cartTotal.textContent = `$${total.toFixed(2)}`;
        }

        function updateQuantity(productId, change) {
            const item = cart.find(c => Number(c.product_id) === Number(productId));
            if (!item) return;

            const availableStock = Number.isFinite(item.stock) ? item.stock : 0;
            if (change > 0 && availableStock <= 0) {
                showErrorMessage('This product is out of stock.');
                return;
            }

            item.quantity += change;
            if (item.quantity <= 0) {
                removeFromCart(productId);
                return;
            }

            if (item.quantity > availableStock) {
                item.quantity = availableStock;
                showErrorMessage('There are only ' + availableStock + ' units available in stock.');
                if (availableStock > 0) {
                    if (isLoggedIn) {
                        fetch('update_cart.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ user_id: userIdForCart, product_id: productId, quantity: item.quantity })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (!data.success) {
                                showErrorMessage(data.message || 'There are only ' + availableStock + ' units available in stock.');
                            }
                            loadCart();
                        });
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

            if (isLoggedIn) {
                fetch('update_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userIdForCart, product_id: productId, quantity: item.quantity })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        showErrorMessage(data.message || 'There are only ' + availableStock + ' units available in stock.');
                    }
                    loadCart();
                });
            } else {
                saveCart();
                updateCartCount();
                updateCartDisplay();
            }
        }

        function removeFromCart(productId) {
            productId = Number(productId);
            cart = cart.filter(item => Number(item.product_id) !== productId);

            if (isLoggedIn) {
                fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userIdForCart, product_id: productId })
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

        function normalizeDigits(value) {
            if (typeof value !== 'string') {
                return '';
            }
            return value
                .replace(/[\u0660-\u0669]/g, ch => String.fromCharCode(48 + ch.charCodeAt(0) - 0x0660))
                .replace(/[\u06F0-\u06F9]/g, ch => String.fromCharCode(48 + ch.charCodeAt(0) - 0x06F0));
        }

        function increaseQuantity() {
            const input = document.getElementById('quantityInput');
            let value = parseInt(normalizeDigits(input.value), 10) || 1;
            if (value < productStock) {
                input.value = value + 1;
            }
        }

        function decreaseQuantity() {
            const input = document.getElementById('quantityInput');
            let value = parseInt(normalizeDigits(input.value), 10) || 1;
            if (value > 1) {
                input.value = value - 1;
            }
        }

        function validateQuantity() {
            const input = document.getElementById('quantityInput');
            let value = parseInt(normalizeDigits(input.value), 10) || 1;
            if (value < 1) {
                value = 1;
            }
            if (value > productStock) {
                value = productStock;
            }
            input.value = value;
        }

        function addToCart(productId) {
            const quantity = parseInt(normalizeDigits(document.getElementById('quantityInput').value), 10) || 1;
            if (productStock <= 0) {
                showErrorMessage('This product is out of stock.');
                return;
            }
            if (quantity < 1 || quantity > productStock) {
                showErrorMessage('Please select a valid quantity.');
                return;
            }

            if (isLoggedIn) {
                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userIdForCart,
                        product_id: productId,
                        quantity: quantity
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showSuccessMessage('Added to cart successfully.');
                        loadCart();
                    } else {
                        showErrorMessage(data.message || 'Could not add product to cart.');
                    }
                })
                .catch(() => {
                    showErrorMessage('Unable to reach the cart service.');
                });
            } else {
                const existing = cart.find(item => Number(item.product_id) === Number(productId));
                const currentQty = existing ? existing.quantity : 0;
                if (currentQty + quantity > productStock) {
                    showErrorMessage('There are only ' + productStock + ' units available in stock.');
                    return;
                }

                if (existing) {
                    existing.quantity += quantity;
                } else {
                    cart.push({
                        product_id: Number(productId),
                        name: productName,
                        price: productPrice,
                        image: productImage,
                        quantity: quantity,
                        stock: productStock
                    });
                }
                saveCart();
                updateCartCount();
                updateCartDisplay();
                showSuccessMessage('Added to cart. Cart will be synced after login.');
            }
        }

        function showSuccessMessage(message = 'Product added to cart!') {
            showMessage('success', message);
        }

        function showMessage(type, message) {
            const msg = document.createElement('div');
            msg.textContent = message;
            msg.style.position = 'fixed';
            msg.style.top = '20px';
            msg.style.right = '20px';
            msg.style.padding = '1rem 1.5rem';
            msg.style.borderRadius = '8px';
            msg.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
            msg.style.zIndex = 2000;
            msg.style.color = 'white';
            msg.style.maxWidth = '320px';
            msg.style.fontWeight = '600';
            msg.style.background = type === 'error' ? '#d32f2f' : '#4caf50';
            document.body.appendChild(msg);
            setTimeout(() => {
                if (msg.parentNode) {
                    msg.parentNode.removeChild(msg);
                }
            }, 3000);
        }

        function showErrorMessage(message) {
            showMessage('error', message);
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadCart();
            const quantityInput = document.getElementById('quantityInput');
            if (quantityInput) {
                quantityInput.addEventListener('change', validateQuantity);
            }

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
</body>
</html>
