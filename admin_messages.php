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

if (isset($_GET['mark_read']) && is_numeric($_GET['mark_read'])) {
    $message_id = intval($_GET['mark_read']);
    $stmt = $conn->prepare('UPDATE contacts SET is_read = 1 WHERE message_id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $message_id);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Message marked as read.';
        } else {
            $_SESSION['flash_error'] = 'Failed to mark message as read.';
        }
        $stmt->close();
    } else {
        $_SESSION['flash_error'] = 'Failed to prepare mark-as-read query: ' . $conn->error;
    }

    header('Location: admin_messages.php');
    exit();
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $message_id = intval($_GET['delete']);
    $stmt = $conn->prepare('DELETE FROM contacts WHERE message_id = ?');
    if ($stmt) {
        $stmt->bind_param('i', $message_id);
        if ($stmt->execute()) {
            $_SESSION['flash_success'] = 'Message deleted successfully!';
        } else {
            $_SESSION['flash_error'] = 'Failed to delete message.';
        }
        $stmt->close();
    } else {
        $_SESSION['flash_error'] = 'Failed to prepare delete query: ' . $conn->error;
    }

    header('Location: admin_messages.php');
    exit();
}

$sql = "SELECT * FROM contacts ORDER BY submitted_at DESC";
$result = $conn->query($sql);
$messages = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Store - Manage Messages</title>
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
            width: 100%;
            min-width: 0;
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
            flex: 1 1 auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.5rem;
            color: #333;
        }

        .button-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .primary-btn,
        .secondary-btn,
        .delete-btn,
        .mark-btn {
            padding: 0.9rem 1.25rem;
            border-radius: 8px;
            border: none;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.25s ease;
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

        .mark-btn {
            background: #388e3c;
        }

        .mark-btn:hover {
            background: #2e7d32;
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

        .messages-panel {
            display: grid;
            gap: 1rem;
        }

        .table-card {
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5e7eb;
            overflow: hidden;
        }

        .table-caption {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            padding: 1rem 1.1rem;
            border-bottom: 1px solid #edf2f7;
            background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
        }

        .table-caption h2 {
            font-size: 1.05rem;
            color: #1f2937;
        }

        .table-caption p {
            font-size: 0.92rem;
            color: #6b7280;
        }

        .table-card {
            background: #ffffff;
            border: 1px solid #e5ebf2;
            border-radius: 18px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            min-width: 980px;
            background: #fff;
        }

        thead th {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #ffffff;
            text-align: left;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            padding: 0.95rem 1rem;
            border-bottom: 1px solid #1f2937;
            white-space: nowrap;
        }

        tbody td {
            padding: 1rem;
            border-bottom: 1px solid #edf2f7;
            vertical-align: top;
            color: #374151;
            font-size: 0.95rem;
            word-break: break-word;
            overflow-wrap: anywhere;
            white-space: normal;
            box-sizing: border-box;
        }

        tbody tr:hover {
            background: #f8fbff;
        }

        th:nth-child(1), td:nth-child(1) { width: 180px; }
        th:nth-child(2), td:nth-child(2) { width: 220px; }
        th:nth-child(3), td:nth-child(3) { width: 180px; }
        th:nth-child(4), td:nth-child(4) { width: 320px; }
        th:nth-child(5), td:nth-child(5) { width: 140px; }
        th:nth-child(6), td:nth-child(6) { width: 120px; }
        th:nth-child(7), td:nth-child(7) { width: 220px; }

        tbody td:first-child,
        tbody td:nth-child(2) {
            font-weight: 600;
            color: #111827;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.35rem 0.65rem;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .status-unread {
            background: #fff1f2;
            color: #b91c1c;
        }

        .status-read {
            background: #ecfdf5;
            color: #047857;
        }

        .message-text-cell {
            max-width: 100%;
            white-space: normal;
            word-break: break-word;
            overflow-wrap: anywhere;
            line-height: 1.6;
            color: #334155;
        }

        td.name-cell,
        td.email-cell,
        td.message-text-cell {
            overflow: hidden;
        }

        .email-link {
            color: #2563eb;
            text-decoration: none;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .email-link:hover {
            text-decoration: underline;
        }

        .action-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .action-group a {
            text-decoration: none;
            color: white;
            border-radius: 10px;
            padding: 0.65rem 0.85rem;
            font-weight: 700;
            font-size: 0.88rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            white-space: nowrap;
            transition: transform 0.18s ease, opacity 0.18s ease;
        }

        .action-group a:hover {
            transform: translateY(-1px);
            opacity: 0.95;
        }

        .muted-note {
            color: #6b7280;
            font-size: 0.92rem;
        }

        @media (max-width: 900px) {
            .message-header,
            .message-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .table-caption {
                flex-direction: column;
                align-items: flex-start;
            }

            .table-card {
                border-radius: 14px;
            }

            table {
                min-width: 980px;
            }

            .messages-table-wrap {
                border-radius: 14px;
            }

            .messages-table {
                min-width: 900px;
            }

            .page-header {
                gap: 0.75rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .button-group {
                width: 100%;
            }

            .button-group > a {
                width: 100%;
            }

            .message-card {
                border-radius: 14px;
                overflow: hidden;
            }
        }

        @media (max-width: 720px) {
            .table-caption {
                padding: 1rem;
            }

            .table-caption h2 {
                font-size: 1rem;
            }

            .table-caption p {
                font-size: 0.85rem;
            }

            .header-content {
                padding: 0.85rem 1rem;
                gap: 0.6rem;
            }

            nav ul {
                gap: 0.75rem;
                justify-content: center;
            }

            .main-content {
                padding: 0 0.75rem;
            }

            .page-title {
                font-size: 1.6rem;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .button-group,
            .button-group > a {
                width: 100%;
            }

            .message-header {
                padding: 1rem;
                gap: 0.5rem;
                flex-direction: column;
                align-items: flex-start;
            }

            .message-title {
                font-size: 1rem;
            }

            .message-body {
                padding: 1rem;
            }

            .message-actions {
                flex-direction: column;
                gap: 0.5rem;
            }

            .message-actions a {
                display: inline-flex;
                width: 100%;
                justify-content: center;
                padding: 0.75rem 0.9rem;
                font-size: 0.95rem;
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
        <h1 class="page-title">Customer Messages</h1>
        <div class="button-group">
            <a href="admin_dashboard.php" class="secondary-btn">Back to Dashboard</a>
        </div>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="messages-panel">
        <?php if (empty($messages)): ?>
            <div class="message-card">
                <div class="message-body">
                    <p>No messages have been received yet.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="table-card">
                <div class="table-caption">
                    <div>
                        <h2>Recent customer enquiries</h2>
                        <p>Use the table below to review, mark as read, or delete messages.</p>
                    </div>
                    <span class="muted-note"><?php echo count($messages); ?> total message(s)</span>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $message): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($message['name']); ?></td>
                                    <td><a class="email-link" href="mailto:<?php echo htmlspecialchars($message['email']); ?>"><?php echo htmlspecialchars($message['email']); ?></a></td>
                                    <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                    <td class="message-text-cell"><?php echo nl2br(htmlspecialchars($message['message'])); ?></td>
                                    <td><?php echo date('M j, Y<br>g:i A', strtotime($message['submitted_at'])); ?></td>
                                    <td>
                                        <span class="status-pill <?php echo (!empty($message['is_read'])) ? 'status-read' : 'status-unread'; ?>">
                                            <?php echo (!empty($message['is_read'])) ? 'Read' : 'Unread'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-group">
                                            <?php if (empty($message['is_read'])): ?>
                                                <a class="mark-btn" href="admin_messages.php?mark_read=<?php echo (int)$message['message_id']; ?>">Mark as read</a>
                                            <?php endif; ?>
                                            <a class="delete-btn" href="admin_messages.php?delete=<?php echo (int)$message['message_id']; ?>" onclick="return confirm('Delete this message?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<script>
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
