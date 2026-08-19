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
ensureContactsIsReadColumn($conn);

$stats = [];

$result = $conn->query("SELECT COUNT(*) as total FROM products");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['products'] = isset($row['total']) ? (int)$row['total'] : 0;
    $result->free();
} else {
    error_log('admin_dashboard products query failed: ' . $conn->error);
    $stats['products'] = 0;
}

$result = $conn->query("SELECT COUNT(*) as total FROM orders");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['orders'] = isset($row['total']) ? (int)$row['total'] : 0;
    $result->free();
} else {
    error_log('admin_dashboard orders query failed: ' . $conn->error);
    $stats['orders'] = 0;
}

$result = $conn->query("SELECT COUNT(*) as total FROM users");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['users'] = isset($row['total']) ? (int)$row['total'] : 0;
    $result->free();
} else {
    error_log('admin_dashboard users query failed: ' . $conn->error);
    $stats['users'] = 0;
}

$result = $conn->query("SELECT COUNT(*) as total FROM contacts WHERE is_read = 0");
if ($result) {
    $row = $result->fetch_assoc();
    $stats['unread_messages'] = isset($row['total']) ? (int)$row['total'] : 0;
    $result->free();
} else {
    error_log('admin_dashboard unread messages query failed: ' . $conn->error);
    $stats['unread_messages'] = 0;
}

$result = $conn->query("SELECT o.order_id, o.order_date, o.total_amount, o.status, u.full_name FROM orders o JOIN users u ON o.user_id = u.user_id ORDER BY o.order_date DESC LIMIT 5");
$recent_orders = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

$result = $conn->query("SELECT message_id, name, email, subject, submitted_at, is_read FROM contacts ORDER BY submitted_at DESC LIMIT 5");
$recent_messages = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Store - Admin Dashboard</title>
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
            background: linear-gradient(180deg, #f8fbff 0%, #f3f6fb 45%, #edf2f7 100%);
            color: #334155;
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
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            flex: 1 1 auto;
        }

        .dashboard-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .dashboard-title {
            font-size: 2.5rem;
            color: #0f172a;
            margin-bottom: 0.5rem;
            letter-spacing: -0.02em;
        }

        .dashboard-subtitle {
            color: #475569;
            font-size: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border-radius: 18px;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.10);
            padding: 1.85rem;
            text-align: center;
            transition: transform 0.25s ease, box-shadow 0.25s ease, border-color 0.25s ease;
            border: 1px solid #e2e8f0;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 22px 48px rgba(15, 23, 42, 0.14);
            border-color: #cbd5e1;
        }

        .stat-icon {
            margin-bottom: 1rem;
            opacity: 0.95;
            display: flex;
            justify-content: center;
        }

        .stat-icon img {
            width: 60px;
            height: 60px;
            object-fit: contain;
            border-radius: 14px;
        }

        .stat-number {
            font-size: 2.25rem;
            font-weight: 700;
            color: #2563eb;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1rem;
            color: #475569;
            font-weight: 600;
        }

        .dashboard-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .section-card {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border-radius: 18px;
            box-shadow: 0 14px 36px rgba(15, 23, 42, 0.10);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .section-header {
            background: linear-gradient(135deg, #f8fbff 0%, #eff6ff 100%);
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e5edf6;
        }

        .section-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: #0f172a;
        }

        .section-link {
            color: #2563eb;
            text-decoration: none;
            font-size: 0.95rem;
        }

        .section-link:hover {
            text-decoration: underline;
        }

        .section-body {
            padding: 1.5rem;
        }

        .item-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f4f7;
            align-items: start;
        }

        .item-row:last-child {
            border-bottom: none;
        }

        .item-text {
            min-width: 0;
            overflow-x: auto;
            overflow-y: hidden;
            white-space: nowrap;
            -webkit-overflow-scrolling: touch;
        }

        .item-text::-webkit-scrollbar {
            height: 6px;
        }

        .item-text::-webkit-scrollbar-thumb {
            background: rgba(0, 102, 204, 0.35);
            border-radius: 999px;
        }

        .item-label {
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.35rem;
            white-space: nowrap;
        }

        .item-meta {
            color: #475569;
            font-size: 0.95rem;
            white-space: nowrap;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.4rem 0.75rem;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        .badge-unread {
            background: #fff1f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }

        .badge-read {
            background: #ecfdf5;
            color: #15803d;
            border: 1px solid #a7f3d0;
        }

        .quick-actions {
            margin: 1.5rem 0;
            text-align: center;
        }
        .actions-grid {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .action-btn {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
            color: #fff;
            padding: 0.6rem 1rem;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);
            transition: transform 0.12s ease, box-shadow 0.18s ease, opacity 0.12s ease;
            border: 1px solid rgba(37, 99, 235, 0.25);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            opacity: 0.97;
            box-shadow: 0 10px 22px rgba(37, 99, 235, 0.22);
        }

        @media (max-width: 900px) {
            .header-content {
                padding: 1rem 1.25rem;
                justify-content: center;
            }

            nav ul {
                gap: 1rem;
            }

            .dashboard-sections {
                grid-template-columns: 1fr;
            }
            .actions-grid { gap: 0.5rem; }
            .action-btn { padding: 0.5rem 0.8rem; }
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
                padding: 0.65rem 0.5rem;
                width: 100%;
            }

            .dashboard-title {
                font-size: 2rem;
            }

            .dashboard-subtitle {
                font-size: 0.95rem;
            }

            .main-content {
                padding: 0 1rem;
            }

            .stats-grid {
                gap: 1rem;
            }

            .section-card {
                width: 100%;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.75rem;
            }

            .item-row {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
        }

        @media (max-width: 520px) {
            .dashboard-title {
                font-size: 1.75rem;
            }

            .header-content {
                padding: 0.85rem 0.75rem;
            }

            .logo {
                width: 100%;
                justify-content: center;
            }

            .action-btn {
                width: 100%;
            }

            .stat-card {
                padding: 1.5rem;
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
                width: 100%;
                justify-content: center;
                text-align: center;
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
    <section class="dashboard-header">
        <h1 class="dashboard-title">Store Administration</h1>
        <p class="dashboard-subtitle">Use the admin panel to manage products, orders, users, and customer messages.</p>
    </section>

    <section class="quick-actions">
        <div class="actions-grid">
            <a class="action-btn" href="admin_products.php">Manage Products</a>
            <a class="action-btn" href="admin_orders.php">Manage Orders</a>
            <a class="action-btn" href="admin_users.php">Manage Users</a>
            <a class="action-btn" href="admin_messages.php">Manage Messages</a>
        </div>
    </section>

    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-icon"><img src="Icons/Total Products Icon.jpg" alt="Total Products"></div>
            <div class="stat-number"><?php echo $stats['products']; ?></div>
            <div class="stat-label">Total Products</div>
        </article>
        <article class="stat-card">
            <div class="stat-icon"><img src="Icons/Total Orders Icon.jpg" alt="Total Orders"></div>
            <div class="stat-number"><?php echo $stats['orders']; ?></div>
            <div class="stat-label">Total Orders</div>
        </article>
        <article class="stat-card">
            <div class="stat-icon"><img src="Icons/Customers Icon.jpg" alt="Total Users"></div>
            <div class="stat-number"><?php echo $stats['users']; ?></div>
            <div class="stat-label">Total Users</div>
        </article>
        <article class="stat-card">
            <div class="stat-icon"><img src="Icons/Unread Messages Icon.jpg" alt="Unread Messages"></div>
            <div class="stat-number"><?php echo $stats['unread_messages']; ?></div>
            <div class="stat-label">Unread Messages</div>
        </article>
    </section>

    <section class="dashboard-sections">
        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Recent Orders</span>
                <a class="section-link" href="admin_orders.php">View All</a>
            </div>
            <div class="section-body">
                <?php if (count($recent_orders) === 0): ?>
                    <p class="item-meta">No recent orders available.</p>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="item-row">
                            <div class="item-text">
                                <div class="item-label">Order <?php echo htmlspecialchars($order['order_id']); ?> • <?php echo htmlspecialchars($order['full_name']); ?></div>
                                <div class="item-meta"><?php echo date('M j, Y', strtotime($order['order_date'])); ?> • $<?php echo number_format($order['total_amount'], 2); ?></div>
                            </div>
                            <div class="badge badge-read"><?php echo ucfirst($order['status']); ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="section-card">
            <div class="section-header">
                <span class="section-title">Latest Messages</span>
                <a class="section-link" href="admin_messages.php">View All</a>
            </div>
            <div class="section-body">
                <?php if (count($recent_messages) === 0): ?>
                    <p class="item-meta">No messages yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_messages as $message): ?>
                        <div class="item-row">
                            <div class="item-text">
                                <div class="item-label"><?php echo htmlspecialchars($message['subject']); ?></div>
                                <div class="item-meta"><?php echo htmlspecialchars($message['name']); ?> • <?php echo date('M j, Y', strtotime($message['submitted_at'])); ?></div>
                            </div>
                            <div class="badge <?php echo $message['is_read'] ? 'badge-read' : 'badge-unread'; ?>">
                                <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>
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
