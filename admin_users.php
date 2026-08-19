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

if (isset($_POST['update_role'])) {
    $user_id = intval($_POST['user_id'] ?? 0);
    $role = $_POST['role'] ?? '';

    if (!in_array($role, ['customer', 'admin'], true)) {
        $_SESSION['flash_error'] = 'Invalid role.';
    } elseif ($user_id === $_SESSION['user_id']) {
        $_SESSION['flash_error'] = 'You cannot change your own role.';
    } else {
        $currentRoleStmt = $conn->prepare('SELECT role FROM users WHERE user_id = ?');
        $currentRoleStmt->bind_param('i', $user_id);
        $currentRoleStmt->execute();
        $currentRole = $currentRoleStmt->get_result()->fetch_assoc()['role'] ?? '';
        $currentRoleStmt->close();

        $shouldClearCart = ($currentRole === 'customer' && $role === 'admin');

        $conn->begin_transaction();

        try {
            if ($shouldClearCart) {
                $clearCartStmt = $conn->prepare('DELETE FROM cart_items WHERE user_id = ?');
                $clearCartStmt->bind_param('i', $user_id);
                $clearCartStmt->execute();
                $clearCartStmt->close();
            }

            $stmt = $conn->prepare('UPDATE users SET role = ? WHERE user_id = ?');
            $stmt->bind_param('si', $role, $user_id);
            if (!$stmt->execute()) {
                throw new Exception('Failed to update user role.');
            }
            $stmt->close();

            $conn->commit();
            $_SESSION['flash_success'] = 'User role updated successfully!';
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['flash_error'] = $e->getMessage();
        }
    }

    header('Location: admin_users.php');
    exit();
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    if ($user_id === $_SESSION['user_id']) {
        $_SESSION['flash_error'] = 'You cannot delete your own account.';
    } else {
        $stmt = $conn->prepare('SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order_count = $result->fetch_assoc()['order_count'] ?? 0;
        $stmt->close();

        if ($order_count > 0) {
            $_SESSION['flash_error'] = 'Cannot delete a user who has placed orders.';
        } else {
            $stmt = $conn->prepare('DELETE FROM users WHERE user_id = ?');
            $stmt->bind_param('i', $user_id);
            if ($stmt->execute()) {
                $_SESSION['flash_success'] = 'User deleted successfully!';
            } else {
                $_SESSION['flash_error'] = 'Failed to delete user.';
            }
            $stmt->close();
        }
    }

    header('Location: admin_users.php');
    exit();
}

$sql = "SELECT user_id, full_name, email, phone, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($sql);
$users = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SM Store - Manage Users</title>
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
            background-color: #f4f7fb;
            color: #1f2937;
            line-height: 1.6;
            min-height: 100vh;
            width: 100%;
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
            padding: 0 1.25rem;
            flex: 1 1 auto;
        }

        .users-panel {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2.25rem;
            color: #111827;
            line-height: 1.05;
        }

        .primary-btn,
        .secondary-btn,
        .delete-btn {
            padding: 0.9rem 1.25rem;
            border-radius: 10px;
            border: none;
            font-weight: 600;
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background-color 0.25s ease, transform 0.25s ease;
            cursor: pointer;
        }

        .primary-btn {
            background: #0066cc;
        }

        .primary-btn:hover,
        .primary-btn:focus {
            background: #0052a3;
            transform: translateY(-1px);
        }

        .secondary-btn {
            background: #4f5d75;
        }

        .secondary-btn:hover,
        .secondary-btn:focus {
            background: #3c4a60;
            transform: translateY(-1px);
        }

        .delete-btn {
            background: #d32f2f;
        }

        .delete-btn:hover,
        .delete-btn:focus {
            background: #b71c1c;
            transform: translateY(-1px);
        }

        .alert {
            padding: 1rem 1.2rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
            transition: opacity 0.35s ease, max-height 0.35s ease;
        }

        .alert-error {
            background: #fff1f0;
            color: #b71c1c;
            border: 1px solid #ffcdd2;
        }

        .alert-success {
            background: #edf7ed;
            color: #1b5e20;
            border: 1px solid #c8e6c9;
        }

        .users-panel {
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            border-radius: 22px;
            box-shadow: 0 18px 42px rgba(15, 23, 42, 0.12);
            overflow: auto;
            border: 1px solid #e2e8f0;
        }

        .users-panel table {
            width: 100%;
            min-width: 720px;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 1rem 1.1rem;
            border-bottom: 1px solid #edf2f7;
            text-align: left;
            vertical-align: middle;
        }

        th {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            color: #f8fafc;
            font-weight: 700;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        tbody tr:hover {
            background: linear-gradient(90deg, #f8fbff 0%, #eff6ff 100%);
        }

        select {
            padding: 0.8rem 0.95rem;
            width: 100%;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
            transition: border-color 0.25s ease, box-shadow 0.25s ease;
        }

        select:focus {
            outline: none;
            border-color: #2563eb;
            box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.12);
        }

        .role-select {
            min-width: 130px;
            max-width: 220px;
            width: 100%;
            background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%);
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .table-actions a,
        .table-actions button {
            font-size: 0.9rem;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
        }

        @media (max-width: 1024px) {
            .header-content {
                padding: 0.85rem 1rem;
                gap: 0.75rem;
            }

            nav ul {
                gap: 0.75rem;
                justify-content: center;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .table-actions {
                width: 100%;
                flex-direction: column;
                align-items: stretch;
            }

            .page-header > a {
                width: 100%;
            }

            th,
            td {
                padding: 0.85rem 0.9rem;
            }
        }

        @media (max-width: 900px) {
            .table-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .page-header {
                gap: 0.75rem;
            }

            .page-header > a {
                width: 100%;
            }

            .page-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 720px) {
            .header-content {
                padding: 0.85rem 1rem;
                gap: 0.75rem;
            }

            nav ul {
                gap: 0.75rem;
                justify-content: center;
            }

            .main-content {
                padding: 0 0.75rem;
            }

            .page-title {
                font-size: 1.75rem;
            }

            .page-header {
                flex-direction: column;
                align-items: stretch;
            }

            .secondary-btn,
            .primary-btn,
            .delete-btn {
                width: 100%;
                min-width: auto;
            }

            .table-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .table-actions a,
            .table-actions button {
                width: 100%;
            }

            .role-select {
                max-width: 100%;
            }

            .users-panel {
                padding: 0.75rem;
            }

            th,
            td {
                padding: 0.85rem 0.9rem;
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
        <h1 class="page-title">User Management</h1>
        <a href="admin_dashboard.php" class="secondary-btn">Back to Dashboard</a>
    </section>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="users-panel">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="7">No users found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo (int)$user['user_id']; ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone']); ?></td>
                            <td>
                                <select name="role" form="update-role-<?php echo (int)$user['user_id']; ?>" class="role-select" aria-label="Role">
                                    <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </td>
                            <td><?php echo htmlspecialchars(date('M j, Y', strtotime($user['created_at']))); ?></td>
                            <td>
                                <div class="table-actions">
                                    <form id="update-role-<?php echo (int)$user['user_id']; ?>" method="post" action="admin_users.php">
                                        <input type="hidden" name="user_id" value="<?php echo (int)$user['user_id']; ?>">
                                        <button type="submit" name="update_role" class="secondary-btn">Save</button>
                                    </form>
                                    <?php if ((int)$user['user_id'] !== $_SESSION['user_id']): ?>
                                        <a href="admin_users.php?delete=<?php echo (int)$user['user_id']; ?>" class="delete-btn" onclick="return confirm('Delete this user?');">Delete</a>
                                    <?php else: ?>
                                        <span style="color:#666;font-size:0.9rem;">Current admin</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
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
