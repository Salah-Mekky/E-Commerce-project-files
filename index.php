<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';
$conn = getDBConnection();

$categories = getAllCategories($conn);
if (!is_array($categories)) {
    $categories = [];
}

$filters = [
    'category_id' => $_GET['category_id'] ?? '',
    'sort' => $_GET['sort'] ?? 'price_low'
];

if (!empty($filters['category_id'])) {
    $allProducts = getFilteredProducts($conn, $filters);
} else {
    $allProducts = getAllProducts($conn);
}

$carouselProducts = getCarouselProducts($conn, 6);

function resolveProductImage($product) {
    $img = '';
    if (!empty($product['image_url'])) {
        $img = $product['image_url'];
    }

    if ($img) {
        $candidate = trim($img);
        if (filter_var($candidate, FILTER_VALIDATE_URL)) {
            return $candidate;
        }
        if (file_exists($candidate) || file_exists(__DIR__ . '/' . $candidate)) {
            return $candidate;
        }
    }

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" width="400" height="300"><rect width="100%" height="100%" fill="#f0f0f0"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#999" font-family="Segoe UI, Arial, sans-serif" font-size="18">No image available</text></svg>');
}

if (isset($_POST['contact_submit'])) {
    $name = trim($_POST['contact_name'] ?? '');
    $email = trim($_POST['contact_email'] ?? '');
    $subject = trim($_POST['contact_subject'] ?? '');
    $message = trim($_POST['contact_message'] ?? '');
    $allowed_subjects = ['inquiry', 'complaint', 'suggestion'];

    if (!$name || !$email || !$message) {
        $error = 'Please fill in all required contact fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please provide a valid email address.';
    } elseif (!in_array(strtolower($subject), $allowed_subjects, true)) {
        $error = 'Please choose a valid subject.';
    } else {
        $subject = strtolower($subject);
        $stmt = $conn->prepare('INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('ssss', $name, $email, $subject, $message);
        if ($stmt->execute()) {
            $success = 'Thank you for contacting us! We will get back to you soon.';
        } else {
            $error = 'Failed to submit your message. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM - E-commerce Store</title>
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

        .hero {
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 50%, #003d7a 100%);
            background-attachment: fixed;
            color: white;
            padding: 5rem 2rem;
            text-align: center;
            position: relative;
            overflow: visible;
            min-height: auto;
            display: block;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255, 107, 53, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(0, 102, 204, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: 1;
        }

        .hero::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                repeating-linear-gradient(45deg, transparent, transparent 2px, rgba(255, 255, 255, 0.02) 2px, rgba(255, 255, 255, 0.02) 4px);
            pointer-events: none;
            z-index: 2;
        }

        .hero-content {
            max-width: 1100px;
            margin: 0 auto;
            position: relative;
            z-index: 3;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 2.5rem;
            width: 100%;
        }

        .hero-left {
            flex: 1 1 100%;
            max-width: 640px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 1.5rem;
            width: 100%;
            text-align: center;
        }

        .hero-welcome {
            width: 100%;
        }

        .hero-shop-btn {
            margin-top: 0;
            width: auto;
            align-self: center;
        }

        .advertising-carousel {
            width: 100%;
            max-width: 1000px;
        }

        .hero-welcome,
        .advertising-carousel,
        .hero-shop-btn {
            animation: slideInDown 0.8s ease both;
        }

        .hero-shop-btn {
            animation-delay: 0.2s;
        }

        .hero h1 {
            font-size: 3.5rem;
            margin-bottom: 1rem;
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
        }

        .hero p {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            opacity: 0.95;
            font-weight: 500;
        }

        .hero-btn {
            display: inline-block;
            background-color: #ff6b35;
            color: white;
            padding: 0.9rem 2.5rem;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            align-self: center;
            box-shadow: 0 5px 20px rgba(255, 107, 53, 0.3);
        }

        .hero-btn:hover {
            background-color: #e85a2a;
            transform: translateY(-3px);
            box-shadow: 0 8px 30px rgba(255, 107, 53, 0.4);
        }

        .advertising-carousel {
            position: relative;
            width: 100%;
            max-width: 1000px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 15px 50px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            animation: slideInDown 0.8s ease both;
        }

        .carousel-container {
            position: relative;
            width: 100%;
            min-height: 500px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
            padding-bottom: 2.5rem;
        }

        .carousel-slide {
            position: absolute;
            width: 100%;
            height: auto;
            min-height: 500px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 3rem;
            opacity: 0;
            transition: opacity 0.5s ease;
            align-items: center;
            justify-items: center;
            top: 0;
            left: 0;
        }

        .carousel-slide.active {
            opacity: 1;
            position: relative;
            z-index: 10;
        }

        .slide-image {
            width: 280px;
            height: 280px;
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .slide-image img {
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            object-position: center;
        }

        .slide-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            justify-content: flex-start;
            align-items: center;
            text-align: center;
            width: 100%;
        }

        .slide-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ff6b35;
            color: white;
            padding: 0.5rem 1.2rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            width: fit-content;
            margin: 0 auto;
        }

        .slide-title {
            font-size: 2rem;
            font-weight: 800;
            color: #333;
            margin: 0;
        }

        .slide-description {
            display: none;
        }

        .slide-promo {
            font-size: 1rem;
            color: #4a4a4a;
            margin: 1rem 0 0;
            line-height: 1.7;
            max-width: 520px;
        }

        .slide-price-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-top: 1.4rem;
            flex-wrap: wrap;
            width: 100%;
        }

        .slide-price,
        .slide-discount {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            vertical-align: middle;
            height: 2.6rem;
            line-height: 1;
        }

        .slide-price {
            font-size: 2rem;
            color: #0c4d92;
            font-weight: 800;
            margin: 0;
        }

        .slide-discount {
            background: linear-gradient(135deg, #ffecd2 0%, #ffb6a0 100%);
            color: #b33a12;
            font-size: 0.85rem;
            font-weight: 800;
            padding: 0 0.95rem;
            border-radius: 999px;
            border: 1px solid rgba(179, 58, 18, 0.15);
            letter-spacing: 0.02em;
        }

        .slide-offer {
            display: none;
        }

        .carousel-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s;
            z-index: 100;
            pointer-events: auto;
        }

        .carousel-nav:hover {
            background: rgba(0, 0, 0, 0.8);
            transform: translateY(-50%) scale(1.1);
        }

        .carousel-nav.prev {
            left: 1rem;
        }

        .carousel-nav.next {
            right: 1rem;
        }

        .carousel-dots {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 0.5rem;
            z-index: 20;
        }

        .carousel-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.5);
            cursor: pointer;
            transition: all 0.3s;
            border: none;
        }

        .carousel-dot.active {
            background: #ff6b35;
            width: 30px;
            border-radius: 6px;
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .search-section {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .search-container {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .search-input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #0066cc;
            box-shadow: 0 0 0 3px rgba(0, 102, 204, 0.1);
        }

        .filter-controls {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 0.6rem 1.2rem;
            border: 2px solid #0066cc;
            background: white;
            color: #0066cc;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: #0066cc;
            color: white;
        }

        .section-title {
            text-align: center;
            margin: 2rem 0 1rem;
            font-size: 2rem;
            color: #333;
        }

        .section-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 2rem;
        }

        .products-section {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(220px, 1fr));
            gap: 1.5rem;
            justify-content: center;
            margin-bottom: 3rem;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            min-height: 400px;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        }

        .product-image {
            width: 100%;
            min-height: 180px;
            aspect-ratio: 4 / 3;
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .product-image a {
            display: block;
            width: 100%;
            height: 100%;
        }

        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            object-position: center;
            display: block;
            background: #fff;
        }

        .product-info {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .product-price {
            font-size: 1.5rem;
            color: #0066cc;
            font-weight: bold;
            margin-bottom: 1rem;
        }

        .product-footer {
            display: flex;
            gap: 0.5rem;
            margin-top: auto;
        }

        .add-to-cart-btn {
            flex: 1;
            padding: 0.8rem;
            background: #0066cc;
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .add-to-cart-btn:hover {
            background: #0052a3;
        }

        .add-to-cart-btn.in-cart {
            background: #4caf50;
        }

        .wishlist-btn {
            padding: 0.8rem 1rem;
            background: #f0f0f0;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.2rem;
            transition: all 0.3s;
        }

        .wishlist-btn:hover {
            background: #ff6b6b;
            color: white;
        }

        .wishlist-btn.liked {
            background: #ff6b6b;
            color: white;
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

            .hero {
                background-attachment: scroll;
                padding: 4rem 1.5rem;
                min-height: auto;
                overflow: visible;
            }

            .hero-content {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 2rem;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .hero p {
                font-size: 1rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
                gap: 1rem;
            }

            .search-container {
                flex-direction: column;
            }

            .filter-controls {
                flex-direction: column;
                width: 100%;
            }

            .filter-btn {
                width: 100%;
            }

            .cart-panel {
                max-width: 100%;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .product-card {
                margin: 0;
            }

            .advertising-carousel {
                margin-top: 2rem;
            }

            .carousel-container {
                min-height: 550px;
                padding: 0;
                overflow: visible;
            }

            .carousel-slide {
                grid-template-columns: 1fr;
                padding: 2rem 1.5rem 3rem;
                gap: 1.5rem;
                height: auto;
                min-height: 550px;
            }

            .slide-image {
                width: 220px;
                height: 220px;
                order: -1;
            }

            .slide-title {
                font-size: 1.5rem;
            }

            .slide-description {
                font-size: 0.9rem;
            }

            .hero h1 {
                font-size: 2.5rem;
            }

            .hero p {
                font-size: 1.1rem;
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
            }

            nav a {
                width: 100%;
                text-align: center;
                padding: 0.5rem 0;
            }

            .hero {
                background-attachment: scroll;
                padding: 3rem 1rem;
                min-height: auto;
                overflow: visible;
            }

            .hero-content {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 1.5rem;
            }

            .hero-shop-btn {
                width: 100%;
                max-width: 420px;
                justify-self: stretch;
                text-align: center;
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

            nav a {
                padding: 0.5rem;
            }

            .hero {
                padding: 2rem 1rem;
            }

            .hero h1 {
                font-size: 1.5rem;
                margin-bottom: 0.5rem;
            }

            .hero p {
                font-size: 0.9rem;
                margin-bottom: 1rem;
            }

            .hero-btn {
                padding: 0.6rem 1.5rem;
                font-size: 0.9rem;
            }

            .search-section {
                padding: 1rem 0.5rem;
            }

            .search-container {
                gap: 0.5rem;
            }

            .search-input {
                padding: 0.6rem 0.8rem;
                font-size: 0.9rem;
            }

            .filter-controls {
                gap: 0.5rem;
            }

            .filter-btn {
                padding: 0.5rem 0.8rem;
                font-size: 0.8rem;
                width: 100%;
            }

            .products-section {
                padding: 1rem 0.5rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
                gap: 0.8rem;
                margin-bottom: 2rem;
            }

            .product-card {
                border-radius: 8px;
            }

            .product-image {
                height: 120px;
            }

            .product-info {
                padding: 0.8rem;
            }

            .product-name {
                font-size: 0.9rem;
            }

            .product-description {
                font-size: 0.75rem;
                margin-bottom: 0.5rem;
            }

            .product-price {
                font-size: 1.1rem;
                margin-bottom: 0.5rem;
            }

            .add-to-cart-btn,
            .wishlist-btn {
                padding: 0.5rem;
                font-size: 0.8rem;
            }

            .section-title {
                font-size: 1.3rem;
                margin: 1rem 0 0.5rem;
            }

            .section-subtitle {
                font-size: 0.85rem;
                margin-bottom: 1rem;
            }

            .cart-panel {
                max-width: 100%;
                height: 85vh;
            }

            .cart-header {
                padding: 1rem;
            }

            .cart-header h2 {
                font-size: 1.2rem;
            }

            .cart-item {
                gap: 0.8rem;
                margin-bottom: 1rem;
                padding-bottom: 1rem;
            }

            .cart-item-image {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
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
                margin-bottom: 0.8rem;
            }

            .checkout-btn {
                padding: 0.8rem;
                font-size: 0.9rem;
            }

            footer {
                padding: 2rem 1rem;
                margin-top: 2rem;
            }

            .footer-content {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }

            .footer-section h3 {
                font-size: 1rem;
            }

            .footer-section {
                font-size: 0.9rem;
            }

            .footer-section a {
                font-size: 0.85rem;
            }

            .social-icons {
                gap: 0.8rem;
            }

            .social-icon {
                width: 35px;
                height: 35px;
                font-size: 0.9rem;
            }

            .footer-bottom {
                font-size: 0.8rem;
                margin-top: 1rem;
            }

            .hero {
                padding: 3rem 1rem;
                min-height: auto;
                overflow: visible;
            }

            .hero-content {
                gap: 2rem;
                align-items: center;
            }

            .hero h1 {
                font-size: 1.8rem;
            }

            .hero p {
                font-size: 0.95rem;
                margin-bottom: 1rem;
            }

            .advertising-carousel {
                margin-top: 1.5rem;
                border-radius: 12px;
            }

            .carousel-container {
                min-height: auto;
                padding: 0 0 2.5rem;
                overflow: visible;
            }

            .carousel-slide {
                grid-template-columns: 1fr;
                padding: 1.5rem 1rem 3rem;
                gap: 1rem;
                height: auto;
                min-height: auto;
            }

            .slide-image {
                width: 180px;
                height: 180px;
                order: -1;
                margin: 0 auto;
            }

            .slide-content {
                gap: 0.8rem;
                justify-content: space-between;
                min-height: 360px;
            }

            .slide-title {
                font-size: 1.3rem;
                margin: 0;
            }

            .slide-description {
                font-size: 0.85rem;
                margin: 0;
            }

            .slide-badge {
                font-size: 0.7rem;
                padding: 0.4rem 0.8rem;
                margin-bottom: 0.5rem;
            }

            .slide-price,
            .slide-discount {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                vertical-align: middle;
                height: 2rem;
                line-height: 1;
            }

            .slide-price {
                font-size: 1.5rem;
            }

            .slide-discount {
                padding: 0 0.9rem;
            }

            .slide-offer {
                font-size: 0.9rem;
                margin: 0.5rem 0 0 0;
            }

            .hero-btn {
                padding: 0.7rem 1.5rem;
                font-size: 0.85rem;
                align-self: center;
            }

            .carousel-nav {
                width: 40px;
                height: 40px;
                font-size: 1.2rem;
            }

            .carousel-nav.prev {
                left: 0.5rem;
            }

            .carousel-nav.next {
                right: 0.5rem;
            }

            .carousel-dots {
                bottom: 2.4rem;
                gap: 0.4rem;
            }

            .carousel-dot {
                width: 10px;
                height: 10px;
            }

            .carousel-dot.active {
                width: 24px;
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

            .hero {
                padding: 1.5rem 0.5rem;
            }

            .hero h1 {
                font-size: 1.3rem;
                margin-bottom: 0.3rem;
            }

            .hero p {
                font-size: 0.8rem;
                margin-bottom: 0.8rem;
            }

            .products-grid {
                grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
                gap: 0.6rem;
            }

            .product-image {
                height: 100px;
            }

            .product-name {
                font-size: 0.8rem;
            }

            .product-price {
                font-size: 0.95rem;
            }

            .cart-item-image {
                width: 50px;
                height: 50px;
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



    <section class="hero" id="home">
        <div class="hero-content">
            <div class="hero-left">
                <div class="hero-welcome">
                    <h1>Welcome to SM Store</h1>
                    <p>Discover an amazing collection of quality products at unbeatable prices</p>
                </div>

                <a href="products.php" class="hero-btn hero-shop-btn">Shop All Products</a>
            </div>

            <div class="advertising-carousel">
                <div class="carousel-container">
                    <?php
                    foreach ($carouselProducts as $i => $cprod):
                        $cimg = resolveProductImage($cprod);
                        $ctitle = htmlspecialchars($cprod['name']);
                        $productDesc = trim($cprod['description']);
                        $promoSummary = $productDesc !== '' ? preg_replace('/\s+/', ' ', strip_tags($productDesc)) : 'an outstanding value for everyday use';
                        if (mb_strlen($promoSummary) > 90) {
                            $promoSummary = mb_substr($promoSummary, 0, 90) . '...';
                        }
                        $promoSummary = rtrim($promoSummary, '.');
                        $promoTemplates = [
                            'Don’t miss the {name}: {summary}.',
                            'Make every moment better with the {name}, offering {summary}.',
                            'Style and performance unite in {name} for {summary}.',
                            'Your next upgrade is here: {name} brings {summary}.',
                            '{name} delivers {summary} with premium quality.',
                            'Experience excellence with the {name}: {summary}.'
                        ];
                        $templateIndex = $i % count($promoTemplates);
                        $promoPhrase = htmlspecialchars(str_replace(['{name}', '{summary}'], [$cprod['name'], $promoSummary], $promoTemplates[$templateIndex]));
                        $displayPrice = number_format($cprod['price'], 2);
                        $Discount = 10 + ($cprod['product_id'] * 7) % 16;
                        $discountLabel = $Discount . '% OFF';
                    ?>
                    <div class="carousel-slide <?php echo $i === 0 ? 'active' : ''; ?>">
                        <div class="slide-image">
                            <img src="<?php echo $cimg; ?>" alt="<?php echo $ctitle; ?>">
                        </div>
                        <div class="slide-content">
                            <span class="slide-badge"><?php echo (!empty($cprod['is_new']) ? 'New Arrival' : (isset($cprod['discount']) && $cprod['discount'] ? 'Limited Offer' : 'Featured')); ?></span>
                            <h2 class="slide-title"><?php echo $ctitle; ?></h2>
                            <p class="slide-promo"><?php echo $promoPhrase; ?></p>
                            <div class="slide-price-row">
                                <p class="slide-price">$<?php echo $displayPrice; ?></p>
                                <span class="slide-discount"><?php echo $discountLabel; ?></span>
                            </div>
                            <a href="product-detail.php?id=<?php echo intval($cprod['product_id']); ?>" class="hero-btn">Discover Now</a>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <button type="button" class="carousel-nav prev" onclick="prevSlide()">❮</button>
                    <button type="button" class="carousel-nav next" onclick="nextSlide()">❯</button>
                </div>

                <div class="carousel-dots">
                    <?php foreach ($carouselProducts as $i => $c): ?>
                        <button class="carousel-dot <?php echo $i === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $i; ?>)"></button>
                    <?php endforeach; ?>
                </div>
            </div> 
        </div>
    </section>

    <section class="search-section">
        <form action="products.php" method="get" class="search-container" style="display:flex;gap:0.75rem;flex-wrap:wrap;">
            <input 
                type="text" 
                class="search-input" 
                id="searchInput" 
                name="search"
                placeholder="Search products..."
                value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                style="flex:1;"
            >
            <button type="submit" class="hero-btn" style="margin-left:0;">Search</button>
        </form>
        <div class="filter-controls">
            <a href="index.php" class="filter-btn <?php echo empty($filters['category_id']) ? 'active' : ''; ?>" style="text-decoration: none; display: inline-block;">All Products</a>
            <?php foreach ($categories as $cat): ?>
                <a href="index.php?category_id=<?php echo $cat['category_id']; ?>" class="filter-btn <?php echo $filters['category_id'] == $cat['category_id'] ? 'active' : ''; ?>" style="text-decoration: none; display: inline-block;"><?php echo htmlspecialchars($cat['name']); ?></a>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="products-section" id="products">
        <h2 class="section-title">Featured Products</h2>
        <p class="section-subtitle">Check out our best sellers</p>
        <div class="products-grid">
        <?php
        $products = array_slice($allProducts, 0, 6);
        foreach ($products as $product):
            $img = resolveProductImage($product);
            $categorySlug = 'other';
            if (!empty($product['category_name'])) {
                $category = strtolower($product['category_name']);
                if (strpos($category, 'electronics') !== false) {
                    $categorySlug = 'electronics';
                } elseif (strpos($category, 'fashion') !== false) {
                    $categorySlug = 'fashion';
                } elseif (strpos($category, 'home') !== false) {
                    $categorySlug = 'home';
                }
            }
        ?>
            <div class="product-card" data-category="<?php echo $categorySlug; ?>">
                <div class="product-image">
                    <a href="product-detail.php?id=<?php echo $product['product_id']; ?>">
                        <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%27400%27 height=%27300%27%3E%3Crect width=%27100%25%27 height=%27100%25%27 fill=%27%23f0f0f0%27/%3E%3Ctext x=%2750%25%27 y=%2750%25%27 dominant-baseline=%27middle%27 text-anchor=%27middle%27 fill=%27%23999%27 font-family=%27Segoe UI, Arial, sans-serif%27 font-size=%2718%27%3ENo image available%3C/text%3E%3C/svg%3E'">
                    </a>
                </div>
                <div class="product-info">
                    <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                    <div class="product-price">$<?php echo number_format($product['price'], 2); ?></div>
                    <div class="product-footer">
                        <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
                        <button class="add-to-cart-btn" type="button" data-product-id="<?php echo $product['product_id']; ?>" data-stock="<?php echo intval($product['stock_quantity'] ?? 0); ?>">Add to Cart</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        </div>
    </section>

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
                <button class="checkout-btn">Checkout</button>
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
                    <li><a href="#">Home</a></li>
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

    <script>
    const isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;
    const userId = <?php echo isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 'null'; ?>;

    let cart = [];

    function loadCart() {
        if (isLoggedIn) {
            fetch('get_cart.php?user_id=' + userId)
                .then(res => res.json())
                .then(data => {
                    cart = data.cart || [];
                    updateCartDisplay();
                });
        } else {
            const savedCart = localStorage.getItem('cart');
            cart = savedCart ? JSON.parse(savedCart) : [];
            updateCartDisplay();
        }
    }

    function saveCart() {
        if (!isLoggedIn) {
            localStorage.setItem('cart', JSON.stringify(cart));
        }
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

    function addToCart(productId, name, price, image, stock) {
        productId = Number(productId);
        if (stock <= 0) {
            showErrorMessage('This product is out of stock.');
            return;
        }

        const idx = cart.findIndex(item => Number(item.product_id) === productId);
        const currentQty = idx > -1 ? cart[idx].quantity : 0;
        if (currentQty + 1 > stock) {
            showErrorMessage('There are only ' + stock + ' units available in stock.');
            return;
        }

        if (idx > -1) {
            cart[idx].quantity += 1;
        } else {
            cart.push({ product_id: productId, name, price, image, quantity: 1, stock });
        }
        if (isLoggedIn) {
            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, product_id: productId, quantity: 1 })
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
            updateCartDisplay();
            showSuccessMessage('Added to cart. Cart will be synced after login.');
        }
    }

    function removeFromCart(productId) {
        productId = Number(productId);
        cart = cart.filter(item => Number(item.product_id) !== productId);
        if (isLoggedIn) {
            fetch('remove_from_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, product_id: productId })
            }).then(() => loadCart());
        } else {
            saveCart();
            updateCartDisplay();
        }
    }

    function updateQuantity(productId, change) {
        const idx = cart.findIndex(item => Number(item.product_id) === Number(productId));
        if (idx > -1) {
            const item = cart[idx];
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
                updateCartDisplay();
            }
        }
    }

    function updateCartDisplay() {
        const cartCount = document.getElementById('cartCount');
        const cartItems = document.getElementById('cartItems');
        const cartTotal = document.getElementById('cartTotal');
        let totalItems = 0;
        let total = 0;
        if (cart.length === 0) {
            cartItems.innerHTML = '<p class="cart-empty">Your cart is empty</p>';
        } else {
            cartItems.innerHTML = cart.map(item => `
                <div class="cart-item">
                    <div class="cart-item-image">
                        <img src="${item.image}" alt="${item.name}" style="width:100%; height:100%; object-fit:cover; border-radius:4px;">
                    </div>
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
        }
        cart.forEach(item => {
            totalItems += item.quantity;
            total += item.price * item.quantity;
        });
        cartCount.textContent = totalItems;
        cartTotal.textContent = `$${total.toFixed(2)}`;
    }

    const cartIcon = document.getElementById('cartIcon');
    const cartModal = document.getElementById('cartModal');
    const closeCart = document.getElementById('closeCart');
    const checkoutBtn = document.querySelector('.checkout-btn');

    if (cartIcon && cartModal) {
        cartIcon.addEventListener('click', () => {
            updateCartDisplay();
            cartModal.classList.add('active');
        });
    }
    if (closeCart && cartModal) {
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

    if (checkoutBtn) {
        checkoutBtn.addEventListener('click', function() {
            window.location.href = 'cart.php';
        });
    }


    document.querySelectorAll('.add-to-cart-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const card = btn.closest('.product-card');
            const productId = parseInt(btn.dataset.productId, 10);
            const stock = parseInt(btn.dataset.stock, 10) || 0;
            const name = card.querySelector('.product-name').textContent;
            const price = parseFloat(card.querySelector('.product-price').textContent.replace('$',''));
            const image = card.querySelector('img').src;
            addToCart(productId, name, price, image, stock);
        });
    });

    <?php if (isset($_SESSION['user_id']) && (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin')): ?>
    if (localStorage.getItem('cart')) {
        fetch('merge_cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ user_id: userId, cart: JSON.parse(localStorage.getItem('cart')) })
        }).then(() => {
            localStorage.removeItem('cart');
            loadCart();
        });
    } else {
        loadCart();
    }
    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
    localStorage.removeItem('cart');
    <?php else: ?>
    loadCart();
    <?php endif; ?>

    let currentSlide = 0;

    function showSlide(n) {
        const slides = document.querySelectorAll('.carousel-slide');
        const dots = document.querySelectorAll('.carousel-dot');
        const totalSlides = slides.length;

        currentSlide = parseInt(n, 10);
        if (isNaN(currentSlide)) currentSlide = 0;

        if (currentSlide >= totalSlides) {
            currentSlide = 0;
        }
        if (currentSlide < 0) {
            currentSlide = totalSlides - 1;
        }

        slides.forEach(slide => slide.classList.remove('active'));
        dots.forEach(dot => dot.classList.remove('active'));

        if (slides[currentSlide]) slides[currentSlide].classList.add('active');
        if (dots[currentSlide]) dots[currentSlide].classList.add('active');
    }

    function nextSlide() {
        showSlide(currentSlide + 1);
    }

    function prevSlide() {
        showSlide(currentSlide - 1);
    }

    function goToSlide(n) {
        showSlide(n);
    }

    let carouselInterval = setInterval(() => {
        nextSlide();
    }, 8000);

    const advertisingCarousel = document.querySelector('.advertising-carousel');
    if (advertisingCarousel) {
        advertisingCarousel.addEventListener('mouseenter', () => clearInterval(carouselInterval));
        advertisingCarousel.addEventListener('mouseleave', () => { carouselInterval = setInterval(() => nextSlide(), 8000); });
    }

    showSlide(0);
    </script>
</body>
</html>
