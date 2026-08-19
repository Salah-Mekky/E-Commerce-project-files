<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
$conn = getDBConnection();
$categories = getAllCategories($conn);

$search = trim($_GET['search'] ?? '');

$allowed_sorts = ['default', 'price_low', 'price_high'];
$sort_value = $_GET['sort'] ?? 'default';
$sort_value = in_array($sort_value, $allowed_sorts, true) ? $sort_value : 'default';

$allowed_prices = ['low', 'medium', 'high'];
$price_value = $_GET['price'] ?? '';
$price_value = in_array($price_value, $allowed_prices, true) ? $price_value : '';

$category_id = isset($_GET['category_id']) && is_numeric($_GET['category_id']) ? intval($_GET['category_id']) : '';

$in_stock = isset($_GET['in_stock']) && $_GET['in_stock'] === '1' ? '1' : '';

$filters = [
    'category_id' => $category_id,
    'price' => $price_value,
    'in_stock' => $in_stock,
    'sort' => $sort_value
];

if (!empty($search)) {
    $products = searchProducts($conn, $search);
} elseif (!empty($filters['category_id']) || !empty($filters['price']) || !empty($filters['in_stock']) || $filters['sort'] !== 'default') {
    $products = getFilteredProducts($conn, $filters);
} else {
    $products = getAllProducts($conn);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Store - Products</title>
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

        a {
            color: inherit;
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
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.18);
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

        .page-header {
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            padding: 2rem 1rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            font-size: 1rem;
            opacity: 0.95;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .products-wrapper {
            display: grid;
            grid-template-columns: minmax(250px, 320px) 1fr;
            gap: 2rem;
        }

        .sidebar {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            height: fit-content;
        }

        .filter-section {
            margin-bottom: 2rem;
        }

        .filter-section h3 {
            color: #0066cc;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }

        .filter-section label {
            display: block;
            margin-bottom: 0.65rem;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .filter-section input[type="radio"],
        .filter-section input[type="checkbox"] {
            margin-right: 0.5rem;
            cursor: pointer;
        }

        .filter-select {
            width: 100%;
            padding: 0.7rem 0.85rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            cursor: pointer;
            transition: border-color 0.3s, box-shadow 0.3s;
            background-color: white;
        }

        .filter-select:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 0 4px rgba(0, 102, 204, 0.12);
        }

        .products-grid-section {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
        }

        .grid-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .grid-header h2 {
            color: #333;
            font-size: 1.5rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }

        .product-card {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            display: flex;
            flex-direction: column;
            min-height: 100%;
            border: 1px solid #eee;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
        }

        .product-image {
            width: 100%;
            height: 180px;
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            overflow: hidden;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            background: #fff;
        }

        .product-image-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 0.9rem;
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
        }

        .product-info {
            padding: 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .product-name {
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.3rem;
            min-height: 2.4em;
        }

        .product-category {
            display: inline-flex;
            background: #e3f2fd;
            color: #0066cc;
            padding: 0.35rem 0.9rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            margin-bottom: 1rem;
            width: fit-content;
        }

        .product-price {
            font-size: 1.3rem;
            color: #0066cc;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .product-stock {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .product-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: auto;
            flex-wrap: wrap;
        }

        .btn {
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: all 0.25s ease;
            font-size: 0.95rem;
            min-width: 0;
        }

        .btn-add-cart {
            background: #0066cc;
            color: white;
            flex: 1;
        }

        .btn-add-cart:hover {
            background: #0052a3;
        }

        .filter-button-group {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-top: 2rem;
        }

        .btn-filter-submit {
            background: #0066cc;
            color: white;
            width: 100%;
        }

        .btn-filter-submit:hover {
            background: #0052a3;
        }

        .btn-filter-reset {
            background: #6c757d;
            color: white;
            width: 100%;
        }

        .btn-filter-reset:hover {
            background: #5a6268;
        }

        .btn-details {
            background: #f0f0f0;
            color: #333;
            flex: 1;
        }

        .btn-details:hover {
            background: #e0e0e0;
        }

        .no-products {
            text-align: center;
            padding: 3rem;
            color: #666;
        }

        .no-products h3 {
            color: #333;
            margin-bottom: 1rem;
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
            width: 70px;
            height: 70px;
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
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .qty-btn {
            width: 32px;
            height: 32px;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            border-radius: 6px;
            font-size: 1rem;
        }

        .remove-item {
            border: none;
            background: none;
            color: #ff4d4d;
            cursor: pointer;
            font-size: 0.9rem;
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

        @media (max-width: 1024px) {
            .products-wrapper {
                grid-template-columns: 1fr;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1.5rem;
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

            .page-header {
                padding: 1.75rem 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .container {
                padding: 1.5rem 1rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
                gap: 1rem;
            }

            .sidebar {
                order: -1;
                margin-bottom: 1rem;
            }

            .product-image {
                height: 160px;
            }

            .product-info {
                padding: 1rem;
            }

            .product-actions {
                gap: 0.5rem;
            }

            .grid-header {
                margin-bottom: 1.5rem;
            }

            .cart-panel {
                max-width: 100%;
            }
        }

        @media (max-width: 600px) {
            body {
                font-size: 14px;
            }

            .header-content {
                padding: 0.9rem 0.75rem;
                flex-wrap: wrap;
            }

            .logo {
                font-size: 1.35rem;
                width: 100%;
                justify-content: center;
            }

            nav ul {
                justify-content: center;
                gap: 0.5rem;
                flex-wrap: wrap;
                width: 100%;
            }

            nav a {
                width: 100%;
                text-align: center;
                padding: 0.5rem;
            }

            .page-header {
                padding: 1.25rem 0.85rem;
            }

            .page-header h1 {
                font-size: 1.6rem;
            }

            .page-header p {
                font-size: 0.95rem;
            }

            .container {
                padding: 1rem 0.75rem;
            }

            .sidebar {
                padding: 1rem;
            }

            .filter-section {
                margin-bottom: 1rem;
            }

            .filter-section h3 {
                font-size: 1rem;
            }

            .filter-section label {
                font-size: 0.9rem;
            }

            .filter-select {
                padding: 0.75rem;
                font-size: 0.9rem;
            }

            .products-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .product-card {
                border-radius: 10px;
            }

            .product-image {
                height: 140px;
            }

            .product-name {
                font-size: 0.98rem;
            }

            .product-category {
                padding: 0.3rem 0.75rem;
                font-size: 0.75rem;
                margin-bottom: 0.8rem;
            }

            .product-price {
                font-size: 1.15rem;
            }

            .product-stock {
                font-size: 0.8rem;
            }

            .product-actions {
                gap: 0.5rem;
            }

            .btn {
                padding: 0.7rem 0.9rem;
                font-size: 0.88rem;
            }

            .btn-add-cart,
            .btn-details {
                flex: 1;
            }

            .cart-panel {
                max-width: 100%;
                height: 85vh;
            }

            .cart-item-image {
                width: 60px;
                height: 60px;
            }

            .cart-item-name {
                font-size: 0.85rem;
            }

            .cart-item-price {
                font-size: 0.9rem;
            }

            .quantity-control {
                gap: 0.3rem;
            }

            .qty-btn {
                width: 25px;
                height: 25px;
                font-size: 0.8rem;
            }

            .qty-display {
                width: 35px;
                font-size: 0.8rem;
            }

            .remove-item {
                font-size: 0.75rem;
                padding: 0.25rem;
            }

            .cart-total {
                font-size: 1.1rem;
            }

            footer {
                padding: 2rem 1rem;
            }

            .footer-content {
                gap: 1.5rem;
            }

            .footer-section h3 {
                font-size: 1rem;
            }

            .footer-section {
                font-size: 0.95rem;
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

            .logo::before {
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
                padding: 8px;
                font-size: 0.8rem;
            }

            .page-header {
                padding: 1rem 0.75rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .page-header p {
                font-size: 0.9rem;
            }

            .container {
                padding: 1rem 0.5rem;
            }

            .products-wrapper {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .sidebar {
                padding: 0.75rem;
            }

            .filter-section {
                margin-bottom: 0.75rem;
            }

            .products-grid {
                grid-template-columns: 1fr;
                gap: 0.8rem;
            }

            .product-card {
                border-radius: 8px;
            }

            .product-image {
                height: 120px;
            }

            .product-name {
                font-size: 0.9rem;
            }

            .product-price {
                font-size: 0.95rem;
            }

            .btn {
                font-size: 0.8rem;
                padding: 0.4rem 0.6rem;
            }
        }

        @media (max-width: 425px) {
            .header-content {
                padding: 0.75rem 0.5rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            nav ul {
                gap: 0.5rem;
                justify-content: center;
                font-size: 0.8rem;
                width: 100%;
            }

            nav a {
                width: 100%;
                text-align: center;
                padding: 8px;
                font-size: 0.8rem;
            }

            .page-header h1 {
                font-size: 1.4rem;
            }

            .page-header p {
                font-size: 0.9rem;
            }

            .container {
                padding: 0.75rem 0.5rem;
            }

            .products-grid {
                gap: 0.8rem;
            }

            .product-image {
                height: 120px;
            }

            .product-actions {
                flex-direction: column;
            }

            .cart-panel {
                border-radius: 12px;
            }
        }

        @media (max-width: 375px) {
            .header-content {
                padding: 0.75rem 0.5rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .logo {
                font-size: 1.1rem;
                width: 100%;
                justify-content: center;
            }

            nav ul {
                justify-content: center;
                gap: 0.35rem;
                font-size: 0.8rem;
                width: 100%;
            }

            nav a {
                width: 100%;
                text-align: center;
                padding: 8px;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 320px) {
            .header-content {
                padding: 0.55rem 0.35rem;
                flex-direction: column;
                gap: 0.4rem;
            }

            .logo {
                font-size: 0.95rem;
                width: 100%;
                justify-content: center;
            }

            nav ul {
                gap: 0.2rem;
                font-size: 0.7rem;
                width: 100%;
            }

            nav a {
                width: 100%;
                text-align: center;
                padding: 8px;
                font-size: 0.7rem;
            }
        }

        @media (max-width: 360px) {
            .header-content {
                padding: 0.5rem;
            }

            nav ul {
                gap: 0.3rem;
                font-size: 0.7rem;
            }

            nav a {
                font-size: 0.7rem;
            }

            .logo {
                font-size: 1rem;
            }

            .logo::before {
                font-size: 1rem;
            }

            .page-header h1 {
                font-size: 1.2rem;
            }

            .products-grid {
                grid-template-columns: 1fr;
                gap: 0.6rem;
            }

            .product-image {
                height: 110px;
            }

            .product-category {
                font-size: 0.7rem;
            }

            .product-name {
                font-size: 0.85rem;
            }

            .product-price {
                font-size: 1rem;
            }

            .btn {
                font-size: 0.8rem;
            }

            .cart-item-image {
                width: 55px;
                height: 55px;
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

    <div class="page-header">
        <h1>Our Products</h1>
        <p>Discover our wide selection of quality products</p>
    </div>

    <div class="container">
        <div class="products-wrapper">
            <aside class="sidebar">
                <form id="filterForm" method="GET" action="products.php">
                    <div class="filter-section">
                        <h3>Category</h3>
                        <select id="categorySelect" name="category_id" class="filter-select">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>" <?php echo ($filters['category_id'] == $category['category_id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-section">
                        <h3>Price Range</h3>
                        <label>
                            <input type="radio" name="price" value="" <?php echo empty($filters['price']) ? 'checked' : ''; ?>> All Prices
                        </label>
                        <label>
                            <input type="radio" name="price" value="low" <?php echo $filters['price'] === 'low' ? 'checked' : ''; ?>> Under $50
                        </label>
                        <label>
                            <input type="radio" name="price" value="medium" <?php echo $filters['price'] === 'medium' ? 'checked' : ''; ?>> $50 - $150
                        </label>
                        <label>
                            <input type="radio" name="price" value="high" <?php echo $filters['price'] === 'high' ? 'checked' : ''; ?>> Over $150
                        </label>
                    </div>

                    <div class="filter-section">
                        <h3>Availability</h3>
                        <label>
                            <input type="checkbox" name="in_stock" value="1" id="inStock" <?php echo $filters['in_stock'] ? 'checked' : ''; ?>> In Stock Only
                        </label>
                    </div>

                    <div class="filter-section">
                        <h3>Sort By</h3>
                        <select id="sortSelect" name="sort" class="filter-select">
                            <option value="price_low" <?php echo $filters['sort'] === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                            <option value="price_high" <?php echo $filters['sort'] === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                        </select>
                    </div>

                    <div class="filter-button-group">
                        <button type="submit" class="btn btn-filter-submit">Apply Filters</button>
                        <button type="button" class="btn btn-filter-reset" onclick="document.getElementById('filterForm').reset(); window.location.href='products.php';">Reset Filters</button>
                    </div>
                </form>
            </aside>

            <div class="products-grid-section">
                <div class="grid-header">
                    <h2>Products</h2>
                </div>

                <div class="products-grid" id="productsGrid">
                </div>

                <div id="noProducts" class="no-products" style="display: none;">
                    <h3>No products found</h3>
                    <p>Try adjusting your filters</p>
                </div>
            </div>
        </div>
    </div>

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
                <a href="cart.php" class="checkout-btn">Checkout</a>
            </div>
        </div>
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
                    <li><img src="Icons/clock.png" alt="Clock Icon"> Sat - Thu:  9AM - 6PM</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 SM E-commerce Store. All rights reserved.</p>
        </div>
    </footer>


    <script>
        const products = <?php echo json_encode(array_map(function($product) {
            return [
                'id' => intval($product['product_id']),
                'name' => $product['name'],
                'price' => floatval($product['price']),
                'stock' => intval($product['stock_quantity']),
                'category_name' => $product['category_name'] ?: 'Uncategorized',
                'image_url' => $product['image_url'] ?: '',
            ];
        }, $products), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

        const cartKey = 'cart';
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        const userId = <?php echo isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 'null'; ?>;
        const userRole = <?php echo isset($_SESSION['role']) ? json_encode($_SESSION['role']) : 'null'; ?>;
        const isAdmin = userRole === 'admin';

        let cart = [];

        function loadCart() {
            if (isLoggedIn) {
                const guestCart = JSON.parse(localStorage.getItem(cartKey)) || [];
                const loadFromServer = () => {
                    fetch('get_cart.php')
                        .then(res => res.json())
                        .then(data => {
                            cart = data.success ? data.cart || [] : [];
                            updateCartCount();
                            updateCartDisplay();
                        })
                        .catch(err => {
                            console.error('Failed to load cart:', err);
                            cart = [];
                            updateCartCount();
                            updateCartDisplay();
                        });
                };

                if (guestCart.length > 0 && !isAdmin) {
                    fetch('merge_cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ cart: guestCart })
                    })
                    .then(() => {
                        localStorage.removeItem(cartKey);
                        loadFromServer();
                    })
                    .catch(err => {
                        console.error('Failed to merge guest cart:', err);
                        loadFromServer();
                    });
                } else if (isAdmin) {
                    localStorage.removeItem(cartKey);
                    loadFromServer();
                } else {
                    loadFromServer();
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

        function renderProducts() {
            const grid = document.getElementById('productsGrid');
            const noProducts = document.getElementById('noProducts');

            if (products.length === 0) {
                grid.innerHTML = '';
                noProducts.style.display = 'block';
                return;
            }

            noProducts.style.display = 'none';
            grid.innerHTML = products.map(product => `
                <div class="product-card">
                    <div class="product-image">
                        ${product.image_url ? `<img src="${product.image_url}" alt="${product.name}">` : '<div class="product-image-placeholder">No image</div>'}
                    </div>
                    <div class="product-info">
                        <div class="product-category">${product.category_name}</div>
                        <div class="product-name">${product.name}</div>
                        <div class="product-price">$${product.price.toFixed(2)}</div>
                        <div class="product-stock">${product.stock > 0 ? `${product.stock} in stock` : 'Out of stock'}</div>
                        <div class="product-actions">
                            <a href="product-detail.php?id=${product.id}" class="btn btn-details">View Details</a>
                            ${!isAdmin ? `<button class="btn btn-add-cart" onclick="addToCart(${product.id})">Add to Cart</button>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function addToCart(productId) {
            const product = products.find(p => p.id === productId);
            if (!product) return;

            const existing = cart.find(item => Number(item.product_id) === Number(productId));
            const currentQty = existing ? existing.quantity : 0;

            if (product.stock <= 0) {
                showErrorMessage('This product is out of stock.');
                return;
            }

            if (currentQty + 1 > product.stock) {
                showErrorMessage('There are only ' + product.stock + ' units available in stock.');
                return;
            }

            if (existing) {
                existing.quantity += 1;
            } else {
                cart.push({
                    product_id: Number(productId),
                    name: product.name,
                    price: product.price,
                    image: product.image_url || '',
                    quantity: 1,
                    stock: product.stock
                });
            }

            if (isLoggedIn) {
                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId, quantity: 1 })
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        showErrorMessage(data.message || 'Unable to add product to cart.');
                    } else {
                        showSuccessMessage('Added to cart successfully.');
                    }
                    loadCart();
                });
            } else {
                saveCart();
                updateCartCount();
                updateCartDisplay();
                showSuccessMessage('Added to cart. Cart will be synced after login.');
            }
        }

        function updateCartCount() {
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cartCount').textContent = count;
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
            msg.style.color = 'white';
            msg.style.zIndex = 2000;
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

        function updateCartDisplay() {
            const cartItems = document.getElementById('cartItems');
            const cartTotal = document.getElementById('cartTotal');

            if (cart.length === 0) {
                cartItems.innerHTML = '<p class="cart-empty">Your cart is empty</p>';
                cartTotal.textContent = '$0.00';
            } else {
                cartItems.innerHTML = cart.map(item => `
                    <div class="cart-item">
                        <div class="cart-item-image">${item.image ? `<img src="${item.image}" alt="${item.name}" style="width:100%;height:100%;object-fit:cover;" />` : '<div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#999;font-size:0.85rem;">No image</div>'}</div>
                        <div class="cart-item-details">
                            <div class="cart-item-name">${item.name}</div>
                            <div class="cart-item-price">$${item.price.toFixed(2)}</div>
                            <div class="quantity-control">
                                <button class="qty-btn" onclick="updateQuantity(${item.product_id}, -1)">−</button>
                                <div class="qty-display">${item.quantity}</div>
                                <button class="qty-btn" onclick="updateQuantity(${item.product_id}, 1)">+</button>
                                <button class="remove-item" onclick="removeFromCart(${item.product_id})">Remove</button>
                            </div>
                        </div>
                    </div>
                `).join('');

                const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                cartTotal.textContent = `$${total.toFixed(2)}`;
            }
        }

        function updateQuantity(productId, change) {
            const item = cart.find(c => Number(c.product_id) === Number(productId));
            if (item) {
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
                                body: JSON.stringify({ product_id: productId, quantity: item.quantity })
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
                        body: JSON.stringify({ product_id: productId, quantity: item.quantity })
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
        }

        function removeFromCart(productId) {
            productId = Number(productId);
            cart = cart.filter(item => Number(item.product_id) !== productId);
            if (isLoggedIn) {
                fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                }).then(() => loadCart());
            } else {
                saveCart();
                updateCartCount();
                updateCartDisplay();
            }
        }

        document.addEventListener('DOMContentLoaded', () => {
            loadCart();
            renderProducts();

            const filterForm = document.getElementById('filterForm');
            if (filterForm) {
                const checkboxes = filterForm.querySelectorAll('input[type="checkbox"]');
                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', () => {
                        filterForm.submit();
                    });
                });

                const sortSelect = document.getElementById('sortSelect');
                if (sortSelect) {
                    sortSelect.addEventListener('change', () => {
                        filterForm.submit();
                    });
                }
            }

            const cartIcon = document.getElementById('cartIcon');
            const cartModal = document.getElementById('cartModal');
            const closeCart = document.getElementById('closeCart');

            if (cartIcon) {
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
