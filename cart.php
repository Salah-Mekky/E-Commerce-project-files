<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: index.php');
    exit();
}
require_once 'config.php';
$addressSet = false;
if (isset($_SESSION['user_id'])) {
    $conn = getDBConnection();
    $stmt = $conn->prepare('SELECT address FROM users WHERE user_id = ?');
    $stmt->bind_param('i', $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $addressSet = !empty(trim($row['address'] ?? ''));
    }
    $stmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Store - Shopping Cart</title>
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
            padding: 0.25rem 0.4rem;
            border-radius: 6px;
            font-size: 1rem;
            line-height: 1.2;
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
            padding: 2rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            flex: 1 0 auto;
            width: 100%;
        }

        .cart-wrapper {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 2rem;
            align-items: start;
            min-width: 0;
        }

        .cart-wrapper.empty-state {
            grid-template-columns: 1fr;
        }

        #cartItemsContainer {
            width: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            min-width: 0;
        }

        .cart-items-section {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 1rem;
            min-width: 0;
            width: 100%;
        }

        .cart-items-section {
            overflow-x: auto;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 620px;
        }

        .cart-table thead {
            background: #f8f9fa;
            border-bottom: 2px solid #eee;
        }

        .cart-table th {
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            white-space: nowrap;
        }

        .cart-table td {
            padding: 1rem;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .product-cell {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .product-image {
            width: 80px;
            height: 80px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            flex-shrink: 0;
        }

        .product-info h3 {
            color: #333;
            margin-bottom: 0.3rem;
            font-size: 1rem;
        }

        .product-info p {
            color: #999;
            font-size: 0.9rem;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            direction: ltr;
        }

        .qty-btn {
            width: 30px;
            height: 30px;
            border: none;
            background: white;
            cursor: pointer;
            font-weight: bold;
            color: #0066cc;
            transition: background-color 0.3s;
        }

        .qty-btn:hover {
            background: #f0f0f0;
        }

        .qty-input {
            width: 50px;
            border: none;
            text-align: center;
            font-weight: 600;
            direction: ltr;
            font-family: inherit;
        }

        .price-cell {
            text-align: right;
            font-weight: 600;
            color: #0066cc;
        }

        .subtotal-cell {
            text-align: right;
            font-weight: 600;
            color: #333;
        }

        .remove-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 44px;
            height: 44px;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            background: #fff;
            cursor: pointer;
            transition: transform 0.25s ease, border-color 0.25s ease, background-color 0.25s ease;
            padding: 0;
            overflow: hidden;
        }

        .remove-btn:hover {
            transform: scale(1.05);
            border-color: #ff6b6b;
            background: #ffe5e5;
        }

        .remove-btn img {
            width: 22px;
            height: 22px;
            object-fit: contain;
            display: block;
        }

        .empty-cart {
            padding: 3rem;
            text-align: center;
            background: white;
            border-radius: 8px;
        }

        .empty-cart h2 {
            color: #666;
            margin-bottom: 1rem;
        }

        .empty-cart p {
            color: #999;
            margin-bottom: 2rem;
        }

        .continue-shopping {
            display: inline-block;
            background: #0066cc;
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s;
        }

        .continue-shopping:hover {
            background: #0052a3;
        }

        .cart-summary {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .cart-summary h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
            border-bottom: 2px solid #eee;
            padding-bottom: 1rem;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #f0f0f0;
        }

        .summary-row.total {
            font-size: 1.3rem;
            font-weight: bold;
            color: #0066cc;
            border-bottom: 2px solid #0066cc;
            padding-bottom: 1rem;
            margin-bottom: 1.5rem;
        }

        .summary-label {
            color: #666;
        }

        .summary-value {
            font-weight: 600;
            color: #333;
        }

        .checkout-btn {
            width: 100%;
            padding: 1rem;
            background: #ff6b35;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-bottom: 0.75rem;
        }

        .checkout-btn:hover {
            background: #e85a2a;
        }

        .continue-btn {
            width: 100%;
            padding: 0.9rem;
            background: white;
            color: #0066cc;
            border: 2px solid #0066cc;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 0.5rem;
            display: inline-flex;
            justify-content: center;
            text-decoration: none;
        }

        .continue-btn:hover {
            background: #e3f2fd;
        }

        .checkout-btn:hover {
            background: #e85a2a;
        }

        .continue-btn {
            width: 100%;
            padding: 0.8rem;
            background: white;
            color: #0066cc;
            border: 2px solid #0066cc;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .continue-btn:hover {
            background: #e3f2fd;
        }

        @media (max-width: 1024px) {
            nav ul {
                gap: 1rem;
                font-size: 0.94rem;
            }

            nav a {
                font-size: 0.94rem;
                padding: 0.25rem 0.4rem;
                line-height: 1.2;
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
                font-size: 0.95rem;
                padding: 0.25rem 0.4rem;
                line-height: 1.2;
            }

            .cart-wrapper {
                grid-template-columns: 1fr !important;
                align-items: stretch;
                grid-auto-flow: row;
                min-width: 0;
            }

            .cart-summary {
                position: static;
                top: auto;
                width: 100%;
                margin-top: 1.5rem;
                justify-self: stretch;
                min-width: 0;
                grid-column: 1 / -1;
            }

            .cart-items-section {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
                width: 100%;
                min-width: 0;
            }

            .cart-table {
                min-width: auto;
                width: 100%;
                table-layout: auto;
            }

            .cart-table th,
            .cart-table td {
                white-space: normal;
            }

            .product-cell {
                flex-direction: column;
                align-items: flex-start;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .container {
                padding: 1.5rem;
            }

            footer {
                padding: 2rem 1rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
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
                justify-content: center;
            }

            nav a {
                width: auto;
                text-align: center;
                padding: 0.5rem 0.4rem;
            }

            .page-header {
                padding: 1.5rem 1rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
                margin-bottom: 0.3rem;
            }

            .page-header p {
                font-size: 0.9rem;
            }

            .container {
                padding: 1rem 0.5rem;
            }

            .cart-wrapper {
                grid-template-columns: 1fr;
            }

            .cart-table {
                font-size: 0.85rem;
            }

            .cart-table th,
            .cart-table td {
                padding: 0.5rem;
            }

            .cart-items-section {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .cart-table {
                min-width: auto;
                font-size: 0.85rem;
                table-layout: auto;
                width: 100%;
            }

            .cart-table th,
            .cart-table td {
                padding: 0.5rem;
                white-space: normal;
            }

            .product-image {
                width: 50px;
                height: 50px;
            }

            .product-cell {
                flex-direction: column;
                gap: 0.5rem;
                flex-wrap: wrap;
            }

            .product-info h3 {
                font-size: 0.85rem;
            }

            .qty-input {
                width: 50px;
                padding: 0.3rem;
                font-size: 0.85rem;
            }

            .cart-summary {
                padding: 1rem;
            }

            .summary-item {
                font-size: 0.85rem;
            }

            .summary-total {
                font-size: 1rem;
            }

            footer {
                padding: 1.5rem 0.5rem;
            }

            .footer-section h3 {
                font-size: 1rem;
            }

            .footer-section {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 425px) {
            .header-content {
                padding: 0.55rem 0.35rem;
                gap: 0.45rem;
            }

            .logo {
                font-size: 1.02rem;
                gap: 0.35rem;
            }

            .logo::before {
                width: 38px;
                height: 38px;
                border-radius: 8px;
                margin-right: 0.35rem;
            }

            nav ul {
                gap: 0.45rem 0.55rem;
                font-size: 0.82rem;
                justify-content: center;
            }

            nav li {
                align-items: center;
            }

            nav a {
                font-size: 0.82rem;
                padding: 0.25rem 0.35rem;
                line-height: 1.1;
            }

            .cart-icon {
                font-size: 1rem;
            }
        }

        @media (max-width: 375px) {
            .header-content {
                padding: 0.45rem 0.3rem;
            }

            .logo {
                font-size: 0.95rem;
            }

            .logo::before {
                width: 34px;
                height: 34px;
                border-radius: 7px;
            }

            nav ul {
                gap: 0.5rem;
                font-size: 0.78rem;
            }

            nav a {
                font-size: 0.78rem;
                padding: 0.25rem 0.35rem;
            }

            .cart-count {
                width: 17px;
                height: 17px;
                font-size: 0.65rem;
                top: -6px;
                right: -6px;
            }
        }

        @media (max-width: 320px) {
            .header-content {
                padding: 0.35rem 0.2rem;
                gap: 0.35rem;
            }

            .logo {
                font-size: 0.9rem;
                justify-content: center;
            }

            .logo::before {
                width: 32px;
                height: 32px;
                border-radius: 7px;
            }

            nav ul {
                width: 100%;
                justify-content: space-between;
                gap: 0.45rem;
                font-size: 0.72rem;
            }

            nav li {
                flex: 1 1 auto;
                justify-content: center;
            }

            nav a {
                font-size: 0.72rem;
                padding: 0.25rem 0.3rem;
            }

            .cart-icon {
                min-width: 28px;
                justify-content: center;
            }

            .cart-count {
                top: -6px;
                right: -2px;
            }
        }

        @media (max-width: 360px) {
            .header-content {
                padding: 0.5rem;
            }

            .logo {
                font-size: 1rem;
            }

            nav ul {
                gap: 0.3rem;
                font-size: 0.7rem;
            }

            .page-header h1 {
                font-size: 1.3rem;
            }

            .product-image {
                width: 40px;
                height: 40px;
            }

            .cart-table {
                font-size: 0.75rem;
            }

            .qty-input {
                width: 40px;
            }
        }

        footer {
            background: linear-gradient(180deg, #1a1a1a 0%, #0f0f0f 100%);
            color: white;
            padding: 3rem 2rem;
            margin-top: auto;
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

        .success-message {
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            left: auto;
            min-width: 280px;
            max-width: 360px;
            background: #22c55e;
            color: white;
            padding: 1rem 1.25rem;
            border-radius: 12px;
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.12);
            z-index: 2000;
            text-align: left;
        }

        .success-message.show {
            display: block;
            animation: toastSlide 0.32s ease;
        }

        .checkout-modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.45);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 2000;
        }

        .checkout-modal.active {
            display: flex;
        }

        .checkout-modal-card {
            width: min(100%, 560px);
            background: white;
            border-radius: 20px;
            box-shadow: 0 30px 80px rgba(15, 23, 42, 0.2);
            overflow: hidden;
            animation: modalFadeIn 0.25s ease;
        }

        .modal-header,
        .modal-footer {
            padding: 1.25rem 1.5rem;
        }

        .modal-header {
            border-bottom: 1px solid #eef2f7;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.45rem;
            color: #111827;
        }

        .modal-body {
            padding: 1rem 1.5rem 0;
            color: #4b5563;
            display: grid;
            gap: 1rem;
        }

        .modal-body p {
            margin: 0;
            line-height: 1.7;
        }

        .checkout-summary {
            display: grid;
            gap: 0.85rem;
            background: #f8fafc;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 1rem;
        }

        .checkout-summary-row {
            display: flex;
            justify-content: space-between;
            gap: 1rem;
            color: #374151;
            font-size: 0.95rem;
        }

        .checkout-summary-row.total {
            font-weight: 700;
            color: #111827;
        }

        .confirm-btn,
        .cancel-btn {
            width: 100%;
            padding: 1rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .confirm-btn {
            background: #0f62fe;
            color: white;
            box-shadow: 0 16px 34px rgba(15, 98, 254, 0.18);
        }

        .confirm-btn:hover {
            transform: translateY(-1px);
        }

        .cancel-btn {
            background: #f8fafc;
            color: #111827;
            border: 1px solid #cbd5e1;
        }

        .address-warning {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 14px;
            padding: 1rem 1.15rem;
            color: #92400e;
            font-size: 0.95rem;
            display: grid;
            gap: 0.65rem;
        }

        .address-warning a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            background: #facc15;
            color: #1f2937;
            border-radius: 10px;
            padding: 0.85rem 1rem;
            text-decoration: none;
            font-weight: 700;
            transition: background-color 0.2s ease;
        }

        .address-warning a:hover {
            background: #fbbf24;
        }

        .cancel-btn:hover {
            transform: translateY(-1px);
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @keyframes toastSlide {
            from { opacity: 0; transform: translateY(-16px); }
            to { opacity: 1; transform: translateY(0); }
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
                            <li><a href="admin_dashboard.php">Admin</a></li>
                        <?php endif; ?>
                        <?php if (!isset($_SESSION['role']) || $_SESSION['role'] === 'customer'): ?>
                            <li><a href="profile.php">My Profile</a></li>
                        <?php endif; ?>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login/Register</a></li>
                    <?php endif; ?>
                    <li class="cart-icon" id="cartIcon">
                        🛒
                        <span class="cart-count" id="cartCount">0</span>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="page-header">
        <h1>Shopping Cart</h1>
        <p>Review your items before checkout</p>
    </div>

    <div class="container">
        <div class="cart-wrapper">
            <div id="cartItemsContainer">
                <div class="empty-cart">
                    <h2>Your cart is empty</h2>
                    <p>Add some products to get started</p>
                    <a href="products.php" class="continue-shopping">Continue Shopping</a>
                </div>
            </div>

            <div class="cart-summary" id="cartSummaryContainer" style="display: none;">
                <h3>Order Summary</h3>

                <div class="summary-row">
                    <span class="summary-label">Subtotal:</span>
                    <span class="summary-value">$<span id="subtotal">0.00</span></span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Shipping:</span>
                    <span class="summary-value">$<span id="shipping">0.00</span></span>
                </div>

                <div class="summary-row">
                    <span class="summary-label">Tax (10%):</span>
                    <span class="summary-value">$<span id="tax">0.00</span></span>
                </div>

                <div class="summary-row total">
                    <span class="summary-label">Total:</span>
                    <span>$<span id="total">0.00</span></span>
                </div>

                <button class="checkout-btn" onclick="checkout()">Proceed to Checkout</button>
                <a href="products.php" class="continue-btn">Continue Shopping</a>
            </div>
        </div>
    </div>

    <div class="success-message" id="successMessage">
        ✓ Order placed successfully!
    </div>

    <div class="checkout-modal" id="checkoutModal">
        <div class="checkout-modal-card">
            <div class="modal-header">
                <h2>Confirm Your Order</h2>
            </div>
            <div class="modal-body">
                <p>Please review your order and confirm to place it. Your shipping address will be taken from your profile.</p>
                <div class="address-warning" id="addressWarning" style="display: none;">
                    <div>Your shipping address is not set yet. Add it in your profile before placing your order.</div>
                    <a href="profile.php">Update Address in Profile</a>
                </div>
                <div class="checkout-summary">
                    <div class="checkout-summary-row"><span>Subtotal</span><span>$<span id="confirmSubtotal">0.00</span></span></div>
                    <div class="checkout-summary-row"><span>Shipping</span><span>$<span id="confirmShipping">0.00</span></span></div>
                    <div class="checkout-summary-row"><span>Tax (10%)</span><span>$<span id="confirmTax">0.00</span></span></div>
                    <div class="checkout-summary-row total"><span>Total</span><span>$<span id="confirmTotal">0.00</span></span></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="confirm-btn" id="placeOrderButton" onclick="placeOrder()">Place Order</button>
                <button class="cancel-btn" onclick="closeCheckoutModal()">Cancel</button>
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
                    <li><img src="Icons/clock.png" alt="Clock Icon"> Sat - Fri: 9AM - 6PM</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 SM E-commerce Store. All rights reserved.</p>
        </div>
    </footer>


    <script>
        let cart = [];
        const cartKey = 'cart';
        const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
        const userId = <?php echo isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 'null'; ?>;
        const hasShippingAddress = <?php echo $addressSet ? 'true' : 'false'; ?>;

        function loadCart() {
            if (isLoggedIn) {
                const guestCart = JSON.parse(localStorage.getItem(cartKey)) || [];
                if (guestCart.length > 0) {
                    fetch('merge_cart.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ cart: guestCart })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            localStorage.removeItem(cartKey);
                        }
                        loadCartFromServer();
                    })
                    .catch(err => {
                        console.error('Failed to merge guest cart:', err);
                        loadCartFromServer();
                    });
                    return;
                }
                loadCartFromServer();
            } else {
                cart = JSON.parse(localStorage.getItem(cartKey)) || [];
                updateCartCount();
                renderCart();
            }
        }

        function loadCartFromServer() {
            fetch('get_cart.php')
                .then(res => res.json())
                .then(data => {
                    cart = Array.isArray(data.cart) ? data.cart : [];
                    updateCartCount();
                    renderCart();
                })
                .catch(err => {
                    console.error('Failed to load cart:', err);
                    cart = [];
                    renderCart();
                });
        }

        function saveCart() {
            if (!isLoggedIn) {
                localStorage.setItem('cart', JSON.stringify(cart));
            }
        }

        function updateCartCount() {
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            document.getElementById('cartCount').textContent = count;
        }

        function renderCart() {
            const container = document.getElementById('cartItemsContainer');
            const summaryContainer = document.getElementById('cartSummaryContainer');

            if (cart.length === 0) {
                document.querySelector('.cart-wrapper').classList.add('empty-state');
                container.innerHTML = `
                    <div class="empty-cart">
                        <h2>Your cart is empty</h2>
                        <p>Add some products to get started</p>
                        <a href="products.php" class="continue-shopping">Continue Shopping</a>
                    </div>
                `;
                summaryContainer.style.display = 'none';
                return;
            }
            document.querySelector('.cart-wrapper').classList.remove('empty-state');

            summaryContainer.style.display = 'block';

            const table = `
                <div class="cart-items-section">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            ${cart.map((item, index) => `
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <div class="product-image"><img src="${item.image || 'products-imgs/no-image.png'}" alt="${item.name}" style="width:100%;height:100%;object-fit:cover;"></div>
                                            <div class="product-info">
                                                <h3>${item.name}</h3>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="price-cell">$${item.price.toFixed(2)}</td>
                                    <td>
                                        <div class="quantity-control">
                                            <button class="qty-btn" onclick="updateQuantity(${item.product_id}, -1)">−</button>
                                            <input type="text" class="qty-input" dir="ltr" lang="en" inputmode="numeric" pattern="[0-9]*" autocomplete="off" value="${item.quantity}" min="1" oninput="this.value = this.value.replace(/[^0-9]/g, '');" onchange="updateQuantityInput(${item.product_id}, this.value)">
                                            <button class="qty-btn" onclick="updateQuantity(${item.product_id}, 1)">+</button>
                                        </div>
                                    </td>
                                    <td class="subtotal-cell">$${(item.price * item.quantity).toFixed(2)}</td>
                                    <td>
                                        <button class="remove-btn" onclick="removeItem(${item.product_id})" title="Remove item">
                                            <img src="Icons/delete element Icon.png" alt="Delete item">
                                        </button>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            `;

            container.innerHTML = table;
            updateSummary();
            updateCartCount();
        }

        function updateQuantity(productId, change) {
            const item = cart.find(c => c.product_id === productId);
            if (item) {
                const availableStock = Number.isFinite(item.stock) ? item.stock : 0;
                if (change > 0 && availableStock <= 0) {
                    showErrorMessage('This product is out of stock.');
                    return;
                }

                item.quantity += change;
                if (item.quantity <= 0) {
                    removeItem(productId);
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
                            renderCart();
                            updateCartCount();
                        }
                    } else {
                        removeItem(productId);
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
                    renderCart();
                    updateCartCount();
                }
            }
        }

        function updateQuantityInput(productId, value) {
            const qty = parseInt(value);
            const item = cart.find(c => c.product_id === productId);
            if (item) {
                const availableStock = Number.isFinite(item.stock) ? item.stock : 0;
                if (qty > 0) {
                    item.quantity = qty;
                } else {
                    item.quantity = 1;
                }

                if (item.quantity > availableStock) {
                    if (availableStock <= 0) {
                        showErrorMessage('This product is out of stock.');
                        if (isLoggedIn) {
                            removeItem(productId);
                            return;
                        }
                        item.quantity = 1;
                    } else {
                        item.quantity = availableStock;
                        showErrorMessage('There are only ' + availableStock + ' units available in stock.');
                    }
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
                    renderCart();
                }
            }
        }

        function removeItem(productId) {
            cart = cart.filter(item => item.product_id !== productId);
            if (isLoggedIn) {
                fetch('remove_from_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ product_id: productId })
                }).then(() => loadCart());
            } else {
                saveCart();
                renderCart();
                updateCartCount();
            }
        }

        function updateSummary() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const shipping = subtotal > 100 ? 0 : 10;
            const tax = subtotal * 0.1;
            const total = subtotal + shipping + tax;

            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('shipping').textContent = shipping.toFixed(2);
            document.getElementById('tax').textContent = tax.toFixed(2);
            document.getElementById('total').textContent = total.toFixed(2);
        }

        function checkout() {
            if (cart.length === 0) {
                showErrorMessage('Your cart is empty');
                return;
            }

            if (!isLoggedIn) {
                window.location.href = 'login.php?redirect=cart.php';
                return;
            }

            openCheckoutModal();
        }

        function openCheckoutModal() {
            const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const shipping = subtotal > 100 ? 0 : 10;
            const tax = subtotal * 0.1;
            const total = subtotal + shipping + tax;
            const addressWarning = document.getElementById('addressWarning');
            const placeButton = document.getElementById('placeOrderButton');

            document.getElementById('confirmSubtotal').textContent = subtotal.toFixed(2);
            document.getElementById('confirmShipping').textContent = shipping.toFixed(2);
            document.getElementById('confirmTax').textContent = tax.toFixed(2);
            document.getElementById('confirmTotal').textContent = total.toFixed(2);

            if (!hasShippingAddress) {
                addressWarning.style.display = 'grid';
                placeButton.disabled = true;
                placeButton.textContent = 'Address Required';
            } else {
                addressWarning.style.display = 'none';
                placeButton.disabled = false;
                placeButton.textContent = 'Place Order';
            }

            document.getElementById('checkoutModal').classList.add('active');
        }

        function closeCheckoutModal() {
            document.getElementById('checkoutModal').classList.remove('active');
        }

        function placeOrder() {
            if (!hasShippingAddress) {
                closeCheckoutModal();
                showErrorMessage('Please add your shipping address in your profile before placing an order.');
                return;
            }
            const button = document.getElementById('placeOrderButton');
            button.disabled = true;
            button.textContent = 'Placing Order...';

            fetch('checkout.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({})
            })
            .then(res => res.json())
            .then(data => {
                button.disabled = false;
                button.textContent = 'Place Order';
                if (data.success && data.order_id) {
                    window.location.href = 'order_confirmation.php?order_id=' + encodeURIComponent(data.order_id);
                    return;
                }
                showErrorMessage('Checkout failed: ' + (data.message || 'Unknown error'));
            })
            .catch(err => {
                console.error('Checkout error:', err);
                button.disabled = false;
                button.textContent = 'Place Order';
                showErrorMessage('Checkout failed. Please try again.');
            });
        }

        function showMessage(type, message) {
            const msg = document.createElement('div');
            msg.textContent = message;
            msg.style.position = 'fixed';
            msg.style.top = '20px';
            msg.style.right = '20px';
            msg.style.padding = '1rem 1.5rem';
            msg.style.borderRadius = '8px';
            msg.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
            msg.style.color = '#fff';
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

        function showSuccessMessage(message = 'Product added to cart!') {
            showMessage('success', message);
        }

        function showErrorMessage(message) {
            showMessage('error', message);
        }

        function showSuccess() {
            const msg = document.getElementById('successMessage');
            msg.classList.add('show');
            setTimeout(() => msg.classList.remove('show'), 2000);
        }
        loadCart();
    </script>
</body>
</html>