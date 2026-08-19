<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$success_message = '';
if (isset($_SESSION['contact_success'])) {
    $success_message = $_SESSION['contact_success'];
    unset($_SESSION['contact_success']);
}

$error_message = '';
if (isset($_SESSION['contact_error'])) {
    $error_message = $_SESSION['contact_error'];
    unset($_SESSION['contact_error']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Store - Contact Us</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: radial-gradient(circle at top left, #f4f8ff 0%, #e6f0ff 45%, #eef5ff 100%);
            color: #1e2d4c;
            line-height: 1.65;
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

        .page-header {
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            padding: 3rem 2rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            font-size: 1.1rem;
            opacity: 0.95;
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
            max-width: 1180px;
            margin: 0 auto;
            padding: 2.5rem 2rem 4rem;
        }

        .contact-wrapper {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2.5rem;
            row-gap: 2.5rem;
            align-items: start;
        }

        .contact-form-section {
            background: rgba(255, 255, 255, 0.98);
            padding: 2.2rem;
            border-radius: 28px;
            border: 1px solid rgba(12, 111, 207, 0.1);
            box-shadow: 0 24px 60px rgba(8, 29, 60, 0.08);
        }

        .contact-form-section h2 {
            color: #14264b;
            margin-bottom: 1.4rem;
            font-size: 1.95rem;
        }

        .form-group {
            margin-bottom: 1.35rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.55rem;
            font-weight: 700;
            color: #1f334f;
            letter-spacing: 0.02em;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 1rem 1.05rem;
            border: 1px solid rgba(23, 43, 79, 0.16);
            border-radius: 18px;
            background: #f5fbff;
            font-size: 1rem;
            font-family: inherit;
            color: #1d2d48;
            transition: border-color 0.25s ease, transform 0.25s ease;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #0d6ecc;
            box-shadow: 0 0 0 4px rgba(13, 110, 204, 0.12);
            transform: translateY(-1px);
        }

        .form-group textarea {
            min-height: 180px;
            resize: vertical;
        }

        .submit-btn {
            width: 100%;
            padding: 1.1rem 1.05rem;
            background: linear-gradient(135deg, #ff6b35 0%, #ff8d4b 100%);
            color: white;
            border: none;
            border-radius: 16px;
            font-weight: 700;
            font-size: 1.02rem;
            letter-spacing: 0.01em;
            cursor: pointer;
            box-shadow: 0 18px 28px rgba(255, 107, 53, 0.22);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 24px 38px rgba(255, 107, 53, 0.26);
        }

        .contact-info-section {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.4rem;
        }

        .info-card {
            display: flex;
            gap: 1rem;
            padding: 1.6rem;
            border-radius: 22px;
            background: linear-gradient(180deg, #ffffff 0%, #f6faff 100%);
            border: 1px solid rgba(12, 111, 207, 0.08);
            box-shadow: 0 20px 40px rgba(12, 79, 143, 0.08);
            align-items: flex-start;
            min-width: 0;
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
        }

        .info-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 28px 50px rgba(12, 79, 143, 0.12);
            border-color: rgba(13, 110, 204, 0.18);
        }

        .info-icon {
            width: 56px;
            height: 56px;
            display: grid;
            place-items: center;
            border-radius: 18px;
            background: #e7f0ff;
            flex-shrink: 0;
            min-width: 56px;
        }

        .info-icon img {
            width: 28px;
            height: 28px;
            object-fit: contain;
        }

        .info-content {
            min-width: 0;
        }

        .info-content h3 {
            color: #102d52;
            margin-bottom: 0.45rem;
            font-size: 1.05rem;
        }

        .info-content p {
            color: #45576d;
            line-height: 1.75;
        }

        .info-content a {
            color: #0d6ecc;
            text-decoration: none;
        }

        .info-content a:hover {
            text-decoration: underline;
        }

        .hours-list {
            list-style: none;
        }

        .hours-list li {
            display: flex;
            justify-content: space-between;
            gap: 0.85rem;
            padding: 0.65rem 0;
            border-bottom: 1px solid rgba(13, 110, 204, 0.12);
        }

        .hours-list li:last-child {
            border-bottom: none;
        }

        .day {
            font-weight: 700;
            color: #182e57;
        }

        .time {
            color: #0d6ecc;
            font-weight: 700;
        }

        .success-message {
            display: none;
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: #4caf50;
            color: white;
            padding: 1rem 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            z-index: 2000;
            animation: slideDown 0.3s ease;
        }

        .success-message.show {
            display: block;
        }

        @keyframes slideDown {
            from { transform: translateX(-50%) translateY(-100%); }
            to { transform: translateX(-50%) translateY(0); }
        }

        .error-message {
            display: none;
            color: #991b1b;
            background: #ffe8e8;
            padding: 1rem 1.1rem;
            border-radius: 14px;
            margin-bottom: 1rem;
            border-left: 4px solid #d32f2f;
        }

        .error-message.show {
            display: block;
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
            border-bottom: 1px solid #eee;
        }

        .cart-header h2 {
            color: #333;
            font-size: 1.25rem;
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
            color: #333;
            margin-bottom: 0.25rem;
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

            .contact-wrapper {
                grid-template-columns: 1fr;
            }

            .contact-form-section,
            .contact-info-section {
                width: 100%;
            }

            .contact-info-section {
                grid-template-columns: 1fr;
            }

            .page-header h1 {
                font-size: 1.8rem;
            }

            .page-header p {
                font-size: 1rem;
            }

            .info-card {
                flex-direction: column;
                text-align: center;
                align-items: center;
            }

            .info-icon {
                width: 56px;
                height: 56px;
                margin: 0 auto;
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
                padding: 0.5rem;
                font-size: 0.8rem;
            }

            .page-header {
                padding: 1.5rem 1rem;
            }

            .page-header h1 {
                font-size: 1.5rem;
                margin-bottom: 0.3rem;
            }

            .page-header p {
                font-size: 0.85rem;
            }

            .container {
                padding: 1rem 0.5rem;
            }

            .contact-wrapper {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .contact-form-section {
                padding: 1rem;
            }

            .form-group {
                margin-bottom: 0.8rem;
            }

            label {
                font-size: 0.85rem;
                margin-bottom: 0.3rem;
            }

            input[type="text"],
            input[type="email"],
            input[type="tel"],
            select,
            textarea {
                padding: 0.6rem;
                font-size: 0.85rem;
            }

            textarea {
                min-height: 80px;
            }

            .submit-btn {
                padding: 0.6rem;
                font-size: 0.85rem;
            }

            .contact-info-section {
                grid-template-columns: 1fr;
                gap: 1rem;
            }

            .info-card {
                padding: 1rem;
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
                align-items: center;
            }

            .info-icon {
                width: 56px;
                height: 56px;
                margin: 0 auto 0.75rem;
            }

            .info-card h3 {
                font-size: 1rem;
                margin-bottom: 0.5rem;
            }

            .info-card p,
            .info-card a {
                font-size: 0.85rem;
            }

            .business-hours {
                font-size: 0.75rem;
            }

            .business-hours tr td {
                padding: 0.3rem;
            }

            .map-container {
                margin-top: 1.5rem;
                height: 300px;
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

            .footer-section ul {
                margin-left: 0;
            }

            .footer-section ul li {
                list-style: none;
                margin-bottom: 0.3rem;
            }

            .social-icons {
                gap: 0.5rem;
            }

            .social-icon {
                width: 30px;
                height: 30px;
                font-size: 0.8rem;
            }

            .footer-bottom {
                font-size: 0.75rem;
                margin-top: 0.8rem;
            }
        }

        @media (max-width: 425px) {
            .header-content {
                padding: 0.75rem 0.5rem;
                flex-direction: column;
                gap: 0.5rem;
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

            .logo {
                width: 100%;
                justify-content: center;
                font-size: 1.2rem;
            }
        }

        @media (max-width: 375px) {
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
                padding: 0.5rem;
                font-size: 0.8rem;
            }
        }

        @media (max-width: 320px) {
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

            .page-header h1 {
                font-size: 1.3rem;
            }

            .page-header p {
                font-size: 0.8rem;
            }

            input[type="text"],
            input[type="email"],
            input[type="tel"],
            select,
            textarea {
                font-size: 0.8rem;
            }

            .info-card h3 {
                font-size: 0.9rem;
            }

            .footer-section h3 {
                font-size: 0.9rem;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('successMessage');
            const showSuccess = <?php echo !empty($success_message) ? 'true' : 'false'; ?>;

            if (showSuccess && successMessage) {
                successMessage.classList.add('show');
                setTimeout(function() {
                    successMessage.classList.remove('show');
                }, 5000);
            }
        });
    </script>
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
        <h1>Contact Us</h1>
        <p>We'd love to hear from you. Get in touch with us today!</p>
    </div>

    <div class="container">
        <div class="contact-wrapper">
            <div class="contact-form-section">
                <h2>Send us a Message</h2>
                <?php if (!empty($error_message)): ?>
                    <div class="error-message show"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <div class="error-message" id="errorMessage"></div>

                <form id="contactForm" action="contact_process.php" method="POST">
                    <div class="form-group">
                        <label for="name">Name :</label>
                        <input type="text" id="name" name="name" required pattern="^[A-Za-z_ ]+$" title="Only letters, spaces and underscores are allowed. Name must contain at least one letter." placeholder="e.g. Salah Mekky">
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address :</label>
                        <input type="email" id="email" name="email" required>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject :</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="inquiry">Inquiry</option>
                            <option value="complaint">Complaint</option>
                            <option value="suggestion">Suggestion</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message :</label>
                        <textarea id="message" name="message" required></textarea>
                    </div>

                    <button type="submit" class="submit-btn">Send Message</button>
                </form>
            </div>

            <div class="contact-info-section">
                <div class="info-card">
                    <div class="info-icon"><img src="Icons/location.png" alt="Location icon"></div>
                    <div class="info-content">
                        <h3>Address</h3>
                        <p>
                            SM Store E-Commerce<br>
                            Palestine Street<br>
                            Rimal District<br>
                            Gaza - Palestine<br>
                            
                        </p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><img src="Icons/phone.png" alt="Phone icon"></div>
                    <div class="info-content">
                        <h3>Phone</h3>
                        <p>
                            <strong>Main:</strong> <a href="tel:+970592552356">+970 (59) 255-2356</a><br>
                            <strong>Support:</strong> <a href="tel:+970567432450">+970 (56) 743-2450</a><br>
                        </p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><img src="Icons/email.png" alt="Email icon"></div>
                    <div class="info-content">
                        <h3>Email</h3>
                        <p>
                            <strong>General:</strong> <a href="mailto:slahmikki00720@gmail.com.com">info@SM_Store.com</a><br>
                        </p>
                    </div>
                </div>

                <div class="info-card">
                    <div class="info-icon"><img src="Icons/clock.png" alt="Hours icon"></div>
                    <div class="info-content">
                        <h3>Working Hours</h3>
                        <ul class="hours-list">
                            <li>
                                <span class="day">Saturday - Thuresday</span>
                                <span class="time">9:00 AM - 6:00 PM</span>
                            </li>
                            <li>
                                <span class="day">Friday</span>
                                <span class="time">Closed</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="success-message" id="successMessage">
        <?php echo !empty($success_message)
            ? htmlspecialchars($success_message)
            : '✓ Message sent successfully! We\'ll get back to you soon.'; ?>
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
                <button type="button" class="checkout-btn" onclick="checkoutCart()">Checkout</button>
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
                    <li><img src="Icons/clock.png" alt="Clock Icon"> Sat - Thu: 9AM - 6PM</li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 SM E-commerce Store. All rights reserved.</p>
        </div>
    </footer>


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
            const count = cart.reduce((sum, item) => sum + item.quantity, 0);
            const countElement = document.getElementById('cartCount');
            if (countElement) {
                countElement.textContent = count;
            }
        }

        function showMessage(type, message) {
            const msg = document.createElement('div');
            msg.textContent = message;
            msg.style.position = 'fixed';
            msg.style.top = '20px';
            msg.style.right = '20px';
            msg.style.padding = '1rem 1.5rem';
            msg.style.borderRadius = '10px';
            msg.style.boxShadow = '0 10px 25px rgba(0, 0, 0, 0.12)';
            msg.style.color = '#fff';
            msg.style.zIndex = 2000;
            msg.style.maxWidth = '320px';
            msg.style.fontWeight = '600';
            if (type === 'error') {
                msg.style.background = '#d32f2f';
            } else {
                msg.style.background = '#388e3c';
            }
            document.body.appendChild(msg);
            setTimeout(() => {
                if (msg.parentNode) {
                    msg.parentNode.removeChild(msg);
                }
            }, 3500);
        }

        function showErrorMessage(message) {
            showMessage('error', message);
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
                            <button class="qty-btn" type="button" onclick="updateQuantity(${item.product_id}, -1)">−</button>
                            <div class="qty-display">${item.quantity}</div>
                            <button class="qty-btn" type="button" onclick="updateQuantity(${item.product_id}, 1)">+</button>
                            <button class="remove-item" type="button" onclick="removeFromCart(${item.product_id})">Remove</button>
                        </div>
                    </div>
                </div>
            `).join('');

            const total = cart.reduce((sum, item) => sum + item.price * item.quantity, 0);
            cartTotal.textContent = `$${total.toFixed(2)}`;
        }

        function loadCart() {
            if (isLoggedIn) {
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

            if (isLoggedIn) {
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
            cart = cart.filter(item => item.product_id !== productId);
            if (isLoggedIn) {
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
</body>
</html>