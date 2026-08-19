<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=profile.php');
    exit();
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'customer') {
    header('Location: index.php');
    exit();
}

$conn = getDBConnection();
$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

if (isset($_POST['update_profile'])) {
    $full_name = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (!$full_name) {
        $error = 'Name is required.';
    } elseif (!preg_match('/^[\p{L}_ ]+$/u', $full_name)) {
        $error = 'Name can only contain letters, spaces and underscores.';
    } elseif (!preg_match('/[\p{L}]/u', $full_name)) {
        $error = 'Name must include at least one letter in addition to underscores or spaces.';
    } elseif ($phone !== '' && !preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Phone number must be exactly 10 digits.';
    } else {
        $stmt = $conn->prepare('SELECT full_name, phone, address FROM users WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $current_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $current_phone = $current_data['phone'] ?? '';
        $current_address = $current_data['address'] ?? '';

        if ($full_name === $current_data['full_name'] && $phone === $current_phone && $address === $current_address) {
            $error = 'The values you entered are already saved.';
        } else {
            $stmt = $conn->prepare('UPDATE users SET full_name = ?, phone = ?, address = ? WHERE user_id = ?');
            $stmt->bind_param('sssi', $full_name, $phone, $address, $user_id);
            if ($stmt->execute()) {
                $success = 'Profile updated successfully!';
                $_SESSION['full_name'] = $full_name;
            } else {
                $error = 'Failed to update profile.';
            }
            $stmt->close();
        }
    }
}

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$current_password || !$new_password || !$confirm_password) {
        $error = 'All password fields are required.';
    } elseif (preg_match('/\s/', $current_password) || preg_match('/\s/', $new_password) || preg_match('/\s/', $confirm_password)) {
        $error = 'Passwords cannot contain spaces.';
    } elseif (strlen($new_password) < 6 || strlen($confirm_password) < 6) {
        $error = 'Passwords must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (!preg_match('/^[\x21-\x7E]+$/', $new_password)) {
        $error = 'Password contains invalid characters.';
    } else {
        $stmt = $conn->prepare('SELECT password FROM users WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        if (!password_verify($current_password, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif ($new_password === $current_password) {
            $error = 'New password must be different from current password.';
        } else {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('UPDATE users SET password = ? WHERE user_id = ?');
            $stmt->bind_param('si', $new_hash, $user_id);
            if ($stmt->execute()) {
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to change password.';
            }
            $stmt->close();
        }
    }
}

$stmt = $conn->prepare('SELECT full_name, email, phone, address, created_at FROM users WHERE user_id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();
$stmt->close();

$profile_orders = [];
$sql = "SELECT o.order_id, o.order_date, o.total_amount, o.status, COUNT(oi.order_item_id) AS item_count
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.order_id, o.order_date, o.total_amount, o.status
        ORDER BY o.order_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$profile_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Store - My Profile</title>
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

        .main-content {
            flex: 1 0 auto;
            width: 100%;
            max-width: 920px;
            margin: 2rem auto;
            padding: 0 2rem;
        }

        .profile-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 22px;
            border: 1px solid rgba(37, 99, 235, 0.14);
            box-shadow: 0 32px 90px rgba(15, 23, 42, 0.08);
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .profile-header {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            padding: 2.5rem 2rem 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .profile-header::after {
            content: '';
            position: absolute;
            inset: 0;
            background: radial-gradient(circle at top right, rgba(255,255,255,0.25), transparent 35%);
            pointer-events: none;
        }

        .profile-avatar {
            width: 96px;
            height: 96px;
            background: rgba(255, 255, 255, 0.16);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            position: relative;
            border: 1px solid rgba(255,255,255,0.22);
            box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
        }

        .profile-avatar svg {
            width: 42px;
            height: 42px;
            fill: white;
        }

        .profile-header h1 {
            font-size: 2.25rem;
            margin-bottom: 0.5rem;
        }

        .profile-header p {
            color: rgba(255,255,255,0.85);
            max-width: 560px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .profile-tabs {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            padding: 1rem 1.25rem 0.75rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .tab-btn {
            flex: 1;
            min-width: 120px;
            padding: 0.95rem 1rem;
            background: white;
            border: 1px solid transparent;
            border-radius: 999px;
            cursor: pointer;
            font-weight: 600;
            color: #475569;
            transition: all 0.25s ease;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        }

        .tab-btn:hover {
            transform: translateY(-1px);
        }

        .tab-btn.active {
            color: white;
            box-shadow: 0 10px 28px rgba(15, 23, 42, 0.12);
        }

        .tab-overview.active {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            border-color: #1d4ed8;
        }

        .tab-overview:not(.active) {
            border-color: rgba(37, 99, 235, 0.16);
        }

        .tab-edit.active {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-color: #059669;
        }

        .tab-edit:not(.active) {
            border-color: rgba(16, 185, 129, 0.18);
        }

        .tab-password.active {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
            border-color: #5b21b6;
        }

        .tab-password:not(.active) {
            border-color: rgba(124, 58, 237, 0.18);
        }

        .tab-orders.active {
            background: linear-gradient(135deg, #0891b2 0%, #0f766e 100%);
            border-color: #0f766e;
        }

        .tab-orders:not(.active) {
            border-color: rgba(8, 145, 178, 0.18);
        }

        .tab-content {
            display: none;
            margin-top: 1rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 2rem;
            box-shadow: 0 20px 60px rgba(15, 23, 42, 0.05);
        }

        .tab-content.active {
            display: block;
        }

        .user-info {
            background: #f8fafc;
            padding: 1.25rem;
            border-radius: 18px;
            margin-bottom: 1.5rem;
            display: grid;
            grid-template-columns: repeat(2, minmax(180px, 1fr));
            gap: 0.85rem;
            align-items: stretch;
            border: 1px solid rgba(148, 163, 184, 0.16);
            min-width: 0;
        }

        .info-item {
            background: linear-gradient(180deg, #ffffff 0%, #fbfdff 100%);
            border-radius: 18px;
            padding: 1.15rem 1.2rem;
            box-shadow: 0 14px 35px rgba(15, 23, 42, 0.06);
            border: 1px solid rgba(148, 163, 184, 0.16);
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
            min-height: 92px;
            justify-content: center;
            align-items: center;
            text-align: center;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            min-width: 0;
        }

        .info-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 22px 45px rgba(15, 23, 42, 0.08);
        }

        .info-label {
            font-size: 0.85rem;
            font-weight: 800;
            color: #334155;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .info-value {
            display: block;
            width: 100%;
            max-width: 100%;
            min-width: 0;
            font-size: 1rem;
            color: #0f172a;
            font-weight: 700;
            line-height: 1.3;
            text-align: center;
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 0.25rem;
        }

        .info-value::-webkit-scrollbar {
            height: 8px;
        }

        .info-value::-webkit-scrollbar-track {
            background: rgba(226, 232, 240, 0.65);
            border-radius: 999px;
        }

        .info-value::-webkit-scrollbar-thumb {
            background: rgba(15, 23, 42, 0.2);
            border-radius: 999px;
        }

        .info-value {
            scrollbar-width: thin;
            -ms-overflow-style: auto;
        }

        .order-history-panel {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .order-history-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .order-history-header h2 {
            font-size: 1.35rem;
            color: #0f172a;
        }

        .order-table-wrapper {
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #ffffff;
        }

        .order-table {
            width: 100%;
            min-width: 700px;
            border-collapse: collapse;
        }

        .order-table th,
        .order-table td {
            padding: 0.95rem 1rem;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .order-table th {
            background: #f8fafc;
            color: #334155;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .order-table tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 92px;
            padding: 0.35rem 0.7rem;
            border-radius: 999px;
            font-weight: 800;
            font-size: 0.85rem;
            text-transform: capitalize;
        }

        .status-pending {
            background: #fff7ed;
            color: #9a3412;
        }

        .status-paid,
        .status-shipped {
            background: #eff6ff;
            color: #1d4ed8;
        }

        .status-delivered {
            background: #ecfdf5;
            color: #047857;
        }

        .status-cancelled {
            background: #fef2f2;
            color: #b91c1c;
        }

        .details-link {
            color: #0f766e;
            font-weight: 800;
            text-decoration: none;
        }

        .details-link:hover {
            text-decoration: underline;
        }

        .no-orders {
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #f8fafc;
            color: #475569;
            text-align: center;
            font-weight: 700;
        }

        .form-group input,
        .form-group textarea {
            border-color: #e6eef8;
            background: #ffffff;
            padding: 0.9rem 1rem;
            border-radius: 10px;
            box-shadow: inset 0 1px 0 rgba(15,23,42,0.02);
            transition: box-shadow 0.18s ease, border-color 0.18s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            border-color: #2563eb;
            box-shadow: 0 8px 30px rgba(37, 99, 235, 0.08);
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: white;
            box-shadow: 0 12px 24px rgba(37, 99, 235, 0.12);
            padding: 0.85rem 1.6rem;
            border-radius: 10px;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 18px 36px rgba(37, 99, 235, 0.14);
        }

        .form-group {
            margin-bottom: 1.25rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 700;
            color: #475569;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            font-size: 1rem;
        }

        .tab-content form {
            max-width: 720px;
            margin: 0 auto;
        }

        .form-actions {
            text-align: center;
            margin-top: 1rem;
        }

        .form-actions .btn {
            display: inline-block;
            min-width: 180px;
        }
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
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

        .qty-display {
            width: 35px;
            text-align: center;
        }

        .cart-empty {
            text-align: center;
            color: #999;
            padding: 1rem;
        }

        .logout-section {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            padding: 2.5rem 2rem;
            border-radius: 20px;
            margin-top: 2rem;
            text-align: center;
            box-shadow: 0 20px 50px rgba(99, 102, 241, 0.15);
            border: 1px solid rgba(99, 102, 241, 0.2);
        }

        .logout-section h3 {
            color: white;
            margin-bottom: 0.5rem;
            font-size: 1.3rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .logout-section p {
            color: rgba(255, 255, 255, 0.95);
            margin-bottom: 1.75rem;
            font-size: 0.95rem;
            line-height: 1.6;
            max-width: 380px;
            margin-left: auto;
            margin-right: auto;
        }

        .btn-logout {
            background: white;
            color: #4f46e5;
            border: none;
            padding: 0.95rem 2.5rem;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            letter-spacing: 0.3px;
        }

        .btn-logout:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.15);
            background: rgba(255, 255, 255, 0.98);
        }

        .btn-logout:active {
            transform: translateY(-1px);
        }

        @media (max-width: 1024px) {
            .profile-header {
                padding: 2rem 1.5rem 1.5rem;
            }

            .profile-header h1 {
                font-size: 2.1rem;
            }

            .profile-header p {
                max-width: 100%;
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

            .main-content {
                padding: 0 1rem;
            }

            .profile-header {
                padding: 1.8rem 1.2rem 1.2rem;
            }

            .profile-header h1 {
                font-size: 1.9rem;
            }

            .profile-header p {
                font-size: 0.98rem;
            }

            .profile-tabs {
                flex-direction: column;
                gap: 0.75rem;
            }

            .tab-btn {
                width: 100%;
                min-width: auto;
                text-align: left;
                padding: 0.95rem 1rem;
            }

            .tab-content {
                margin-top: 0.75rem;
                padding: 1.5rem;
            }

            .user-info {
                grid-template-columns: 1fr;
            }

            .info-item {
                flex-direction: column;
                gap: 0.5rem;
            }

            .btn-primary,
            .btn {
                width: 100%;
            }

            .form-group input,
            .form-group textarea {
                padding: 0.85rem 0.95rem;
            }

            .logout-section {
                padding: 2rem 1.5rem;
                margin-top: 1.5rem;
            }

            .logout-section h3 {
                font-size: 1.15rem;
            }

            .logout-section p {
                font-size: 0.9rem;
                margin-bottom: 1.5rem;
            }

            .btn-logout {
                width: 100%;
                padding: 0.9rem 1.5rem;
            }
        }

        @media (max-width: 480px) {
            .header-content {
                padding: 0.75rem 0.75rem;
                flex-direction: column;
                gap: 0.5rem;
            }

            .logo {
                font-size: 1.3rem;
            }

            nav ul {
                gap: 0.75rem;
                font-size: 0.85rem;
                width: 100%;
                justify-content: center;
            }

            nav a {
                padding: 0.3rem 0.2rem;
            }

            .main-content {
                padding: 0 0.75rem;
            }

            .profile-header {
                padding: 1.5rem 1rem 1rem;
            }

            .profile-avatar {
                width: 76px;
                height: 76px;
            }

            .profile-header h1 {
                font-size: 1.75rem;
            }

            .profile-header p {
                font-size: 0.94rem;
            }

            .profile-tabs {
                gap: 0.5rem;
            }

            .tab-btn {
                padding: 0.75rem 0.9rem;
                font-size: 0.95rem;
            }

            .tab-content {
                padding: 1.2rem;
            }

            .form-group input,
            .form-group textarea {
                padding: 0.75rem 0.9rem;
            }

            .btn-primary,
            .btn {
                padding: 0.8rem 1rem;
            }

            .logout-section {
                padding: 1.75rem 1.25rem;
                margin-top: 1.25rem;
            }

            .logout-section h3 {
                font-size: 1.1rem;
            }

            .logout-section p {
                font-size: 0.9rem;
                margin-bottom: 1.25rem;
            }

            .btn-logout {
                width: 100%;
                padding: 0.85rem 1.25rem;
                font-size: 0.95rem;
            }
        }

        @media (min-width: 2560px) {
            body {
                min-height: 100vh;
            }

            .main-content {
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

        @media (max-width: 360px) {
            .logo {
                font-size: 1.05rem;
            }

            nav ul {
                gap: 0.5rem;
                font-size: 0.8rem;
            }

            .profile-header h1 {
                font-size: 1.55rem;
            }

            .profile-header p {
                font-size: 0.9rem;
            }

            .tab-btn {
                padding: 0.65rem 0.8rem;
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
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin_dashboard.php">Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout</a></li>
                    <li class="cart-icon" id="cartIcon">
                        🛒
                        <span class="cart-count" id="cartCount">0</span>
                    </li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="main-content">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar" aria-hidden="true">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 12c2.761 0 5-2.239 5-5s-2.239-5-5-5-5 2.239-5 5 2.239 5 5 5zm0 2c-3.866 0-7 2.239-7 5v1h14v-1c0-2.761-3.134-5-7-5z"/>
                    </svg>
                </div>
                <h1>My Profile</h1>
                <p>Manage your account information in one modern and responsive dashboard.</p>
            </div>

            <div class="profile-tabs">
                <button class="tab-btn tab-overview active" onclick="showTab(event, 'overview')">Overview</button>
                <button class="tab-btn tab-orders" onclick="showTab(event, 'orders')">Order History</button>
                <button class="tab-btn tab-edit" onclick="showTab(event, 'edit')">Edit Profile</button>
                <button class="tab-btn tab-password" onclick="showTab(event, 'password')">Change Password</button>
            </div>

            <div id="overview" class="tab-content active">
                <?php if (($success || $error) && (isset($_POST['update_profile']) || isset($_POST['change_password']))): ?>
                    <div class="alert <?php echo $error ? 'alert-error' : 'alert-success'; ?>" id="profileMessage"><?php echo htmlspecialchars($error ?: $success); ?></div>
                <?php endif; ?>
                <div class="user-info">
                    <div class="info-item">
                        <span class="info-label">Full Name:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user_data['full_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user_data['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user_data['phone'] ?: 'Not provided'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Address:</span>
                        <span class="info-value"><?php echo htmlspecialchars($user_data['address'] ?: 'Not provided'); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Member Since:</span>
                        <span class="info-value"><?php echo date('F j, Y', strtotime($user_data['created_at'])); ?></span>
                    </div>
                </div>
            </div>

            <div id="orders" class="tab-content">
                <div class="order-history-panel">
                    <div class="order-history-header">
                        <h2>Order History</h2>
                        <a href="order_history.php" class="details-link">Open full history</a>
                    </div>

                    <?php if (empty($profile_orders)): ?>
                        <div class="no-orders">You have not placed any orders yet.</div>
                    <?php else: ?>
                        <div class="order-table-wrapper">
                            <table class="order-table">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Date</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($profile_orders as $order): ?>
                                        <tr>
                                            <td><?php echo (int)$order['order_id']; ?></td>
                                            <td><?php echo htmlspecialchars(date('F j, Y', strtotime($order['order_date']))); ?></td>
                                            <td><?php echo (int)$order['item_count']; ?></td>
                                            <td>$<?php echo number_format((float)$order['total_amount'], 2); ?></td>
                                            <td>
                                                <span class="status-badge status-<?php echo htmlspecialchars(strtolower($order['status'])); ?>">
                                                    <?php echo htmlspecialchars($order['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a class="details-link" href="order_details.php?order_id=<?php echo urlencode($order['order_id']); ?>">View Details</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div id="edit" class="tab-content">
                <form method="post">
                    <div class="form-group">
                        <label for="full_name">Full Name </label>
                        <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required title="Name can only contain letters, spaces, and underscores" oninput="this.value = this.value.replace(/[^a-zA-Z_ ]/g, '')">
                    </div>
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user_data['phone']); ?>" inputmode="numeric" pattern="[0-9]{10}" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')" placeholder="0591234567">
                    </div>
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address"><?php echo htmlspecialchars($user_data['address']); ?></textarea>
                    </div>
                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                </form>
            </div>

            <div id="password" class="tab-content">
                <form method="post">
                    <div class="form-group">
                        <label for="current_password">Current Password </label>
                        <input type="password" id="current_password" name="current_password" required minlength="6" pattern="^\S{6,}$" title="6 or more characters, no spaces" oninput="this.value = this.value.replace(/\s/g, '')">
                    </div>
                    <div class="form-group">
                        <label for="new_password">New Password </label>
                        <input type="password" id="new_password" name="new_password" required minlength="6" pattern="^\S{6,}$" title="6 or more characters, no spaces" oninput="this.value = this.value.replace(/\s/g, '')">
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password </label>
                        <input type="password" id="confirm_password" name="confirm_password" required minlength="6" pattern="^\S{6,}$" title="6 or more characters, no spaces" oninput="this.value = this.value.replace(/\s/g, '')">
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                </form>
            </div>

            <div class="logout-section">
                <h3>See You Soon!</h3>
                <p>Thanks for visiting SM Store. Your session will be securely ended when you log out.</p>
                <a href="logout.php" class="btn-logout">Logout</a>
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
        const count = cart.reduce((sum, item) => sum + (item.quantity||0), 0);
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
                    <div class="cart-item-price">$${(item.price||0).toFixed(2)}</div>
                    <div class="quantity-control">
                        <button class="qty-btn" type="button" onclick="updateQuantity(${item.product_id}, -1)">−</button>
                        <div class="qty-display">${item.quantity}</div>
                        <button class="qty-btn" type="button" onclick="updateQuantity(${item.product_id}, 1)">+</button>
                        <button class="remove-item" type="button" onclick="removeFromCart(${item.product_id})">Remove</button>
                    </div>
                </div>
            </div>
        `).join('');

        const total = cart.reduce((sum, item) => sum + ((item.price||0) * (item.quantity||0)), 0);
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
                }).catch(() => { cart = []; updateCartCount(); updateCartDisplay(); });

            if (guestCart.length > 0) {
                fetch('merge_cart.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: userId, cart: guestCart })
                }).then(() => { localStorage.removeItem(cartKey); return fetchCart(); }).catch(() => { cart = []; updateCartCount(); updateCartDisplay(); });
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

        item.quantity = (item.quantity || 0) + change;
        if (item.quantity <= 0) {
            removeFromCart(productId);
            return;
        }

        if (item.quantity > availableStock) {
            item.quantity = availableStock;
            showErrorMessage('There are only ' + availableStock + ' units available in stock.');
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

    function checkoutCart() { window.location.href = 'cart.php'; }

    function showTab(event, tabName) {
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });

        const targetTab = document.getElementById(tabName);
        if (targetTab) {
            targetTab.classList.add('active');
        }
        if (event && event.currentTarget) {
            event.currentTarget.classList.add('active');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        loadCart();

        const cartIcon = document.getElementById('cartIcon');
        const cartModal = document.getElementById('cartModal');
        const closeCart = document.getElementById('closeCart');

        if (cartIcon && cartModal) {
            cartIcon.addEventListener('click', () => { updateCartDisplay(); cartModal.classList.add('active'); });
        }
        if (closeCart) closeCart.addEventListener('click', () => cartModal.classList.remove('active'));
        if (cartModal) cartModal.addEventListener('click', (e) => { if (e.target === cartModal) cartModal.classList.remove('active'); });

        const profileMessage = document.getElementById('profileMessage');
        if (profileMessage) {
            setTimeout(() => {
                profileMessage.style.transition = 'opacity 0.4s ease';
                profileMessage.style.opacity = '0';
                setTimeout(() => profileMessage.remove(), 400);
            }, 3000);
        }
    });
</script>
</body>
</html>
