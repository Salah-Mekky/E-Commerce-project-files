<?php
session_start();

$redirect = 'index.php';
if (!empty($_REQUEST['redirect'])) {
    $requested = basename($_REQUEST['redirect']);
    $allowedRedirects = ['index.php', 'cart.php'];
    if (in_array($requested, $allowedRedirects, true)) {
        $redirect = $requested;
    }
}

if (isset($_SESSION['user_id'])) {
    header('Location: ' . $redirect);
    exit();
}

require_once 'config.php';

$error = '';
$full_name = '';
$email = '';
$phone = '';
$address = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = 'customer';

        $phone = $phone === '' ? null : $phone;
        $address = $address === '' ? null : $address;

        if ($full_name === '') {
            $error = 'Full name is required.';
        } elseif (!preg_match('/^[\p{L}_ ]+$/u', $full_name)) {
            $error = 'Full name can only contain letters, spaces, and underscores.';
        } elseif (!preg_match('/[\p{L}]/u', $full_name)) {
            $error = 'Full name must include at least one letter.';
        } elseif ($email === '') {
            $error = 'Email address is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password === '' || $confirm_password === '') {
            $error = 'Password and confirmation are required.';
        } elseif (preg_match('/\s/', $password) || preg_match('/\s/', $confirm_password)) {
            $error = 'Passwords cannot contain spaces.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } elseif ($phone !== null && !preg_match('/^[0-9]{10}$/', $phone)) {
            $error = 'Phone number must be exactly 10 digits if provided.';
        } else {
        	$conn = getDBConnection();
        $passwordColumn = getUsersPasswordColumn($conn);

        if (!$passwordColumn) {
            $error = 'Database error: missing password column in users table.';
        } else {
            $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = ?');

            if ($stmt) {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result && $result->num_rows > 0) {
                    $error = 'Email address already registered';
                    $stmt->close();
                } else {
                    $stmt->close();
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("INSERT INTO users (full_name, email, `$passwordColumn`, phone, address, role) VALUES (?, ?, ?, ?, ?, ?)");

                    if ($stmt) {
                        $stmt->bind_param('ssssss', $full_name, $email, $hashed_password, $phone, $address, $role);

                        if ($stmt->execute()) {
                            $stmt->close();
                            $conn->close();
                            header('Location: login.php?registered=1&redirect=' . urlencode($redirect));
                            exit();
                        }

                        $error = 'Registration failed. Please try again.';
                        $stmt->close();
                    } else {
                        $error = 'Database error: could not prepare registration statement. ' . $conn->error;
                    }
                }
            } else {
                $error = 'Database error: could not prepare email check statement. ' . $conn->error;
            }
        }

        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - SM Store</title>
    <style>
        :root {
            --bg-start: #0f3057;
            --bg-end: #1c4c8b;
            --surface: #ffffff;
            --surface-soft: #f6faff;
            --border: #d4e1f3;
            --text: #122140;
            --muted: #5d728e;
            --accent: #1d73d9;
            --accent-strong: #103a7d;
            --danger: #b82f3d;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            height: 100%;
            width: 100%;
            overflow-x: hidden;
            overflow-y: auto;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #0f3057;
            background-image: linear-gradient(135deg, #0f3057 0%, #1c4c8b 100%);
            background-repeat: no-repeat;
            background-size: cover;
            background-position: center center;
            min-height: 100vh;
            min-width: 100vw;
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: flex-start;
            padding: 0;
            color: var(--text);
            position: relative;
        }

        .page-main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem 2rem;
            width: 100%;
        }

        header {
            background: linear-gradient(135deg, #0066cc 0%, #0052a3 100%);
            color: white;
            padding: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            position: -webkit-sticky;
            position: sticky;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            width: 100%;
            align-self: stretch;
            display: block;
            transform: translateZ(0);
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


        body::before,
        body::after {
            content: '';
            position: fixed;
            border-radius: 50%;
            filter: blur(22px);
            pointer-events: none;
            z-index: -1;
        }

        body::before {
            width: 520px;
            height: 520px;
            top: -220px;
            right: -200px;
            background: rgba(255, 255, 255, 0.14);
        }

        body::after {
            width: 560px;
            height: 560px;
            bottom: -220px;
            left: -180px;
            background: rgba(25, 115, 217, 0.16);
        }

        .register-container {
            background: rgba(255, 255, 255, 0.96);
            border-radius: 32px;
            box-shadow: 0 40px 90px rgba(1, 23, 66, 0.21);
            max-width: 640px;
            width: min(100%, 640px);
            margin: 1.5rem auto;
            padding: 2.2rem;
            border: 1px solid rgba(255, 255, 255, 0.72);
            position: relative;
            overflow: hidden;
            transition: transform 0.35s ease, box-shadow 0.35s ease;
        }

        .register-container:hover {
            transform: translateY(-3px);
            box-shadow: 0 45px 100px rgba(1, 23, 66, 0.25);
        }

        .register-container::before {
            content: '';
            position: absolute;
            left: -50px;
            top: -50px;
            width: 180px;
            height: 180px;
            background: rgba(29, 115, 217, 0.14);
            border-radius: 50%;
            pointer-events: none;
        }

        .register-header {
            position: relative;
            z-index: 1;
            margin-bottom: 2rem;
        }

        .register-header .auth-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.22em;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .register-header .auth-brand::before {
            content: '';
            width: 30px;
            height: 30px;
            border-radius: 12px;
            background: rgba(29, 115, 217, 0.18);
            box-shadow: inset 0 0 0 1px rgba(29, 115, 217, 0.2);
        }

        .register-header h1 {
            color: #0b224b;
            font-size: 2.25rem;
            margin-bottom: 0.6rem;
            line-height: 1.05;
        }

        .register-header p {
            color: var(--muted);
            font-size: 1rem;
            line-height: 1.75;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
            min-width: 0;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            margin-bottom: 1.3rem;
            min-width: 0;
        }

        input,
        textarea {
            min-width: 0;
        }

        label {
            display: block;
            margin-bottom: 0.55rem;
            color: #384568;
            font-weight: 700;
            font-size: 0.93rem;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        textarea {
            width: 100%;
            padding: 1rem 1.1rem;
            border: 1px solid var(--border);
            border-radius: 18px;
            font-size: 1rem;
            transition: border-color 0.25s ease, box-shadow 0.25s ease, background-color 0.25s ease;
            background: #f4f8ff;
            color: var(--text);
            box-shadow: inset 0 1px 3px rgba(14, 48, 101, 0.06);
        }

        input::placeholder,
        textarea::placeholder {
            color: #8e9db3;
            font-weight: 500;
        }

        input:focus,
        textarea:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 5px rgba(29, 115, 217, 0.14);
            background: #ffffff;
            transform: translateY(-1px);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .error {
            background: #ffe7e9;
            color: var(--danger);
            padding: 1rem;
            border-radius: 18px;
            margin-bottom: 1.5rem;
            border: 1px solid #f1bcc4;
        }

        .register-btn {
            width: 100%;
            padding: 1rem 1.1rem;
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-strong) 100%);
            color: white;
            border: none;
            border-radius: 18px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            box-shadow: 0 16px 34px rgba(12, 68, 131, 0.26);
            margin-top: 0.5rem;
        }

        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px rgba(12, 68, 131, 0.32);
        }

        .login-link {
            text-align: center;
            margin-top: 1.8rem;
            color: var(--muted);
            font-size: 0.95rem;
            line-height: 1.6;
        }

        .login-link a {
            color: var(--accent);
            text-decoration: none;
            font-weight: 700;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 680px) {
            .page-main {
                padding: 2rem 0.75rem 2rem;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .register-container {
                padding: 1.6rem;
                border-radius: 26px;
                margin: 1rem auto;
            }

            .register-header h1 {
                font-size: 2rem;
            }

            .register-header p {
                font-size: 0.96rem;
            }
        }

        @media (max-width: 520px) {
            .page-main {
                padding: 2rem 0.75rem 2rem;
            }
            .register-container {
                padding: 1.3rem;
                margin: 0.8rem auto;
            }

            .register-header h1 {
                font-size: 1.95rem;
            }

            input[type="text"],
            input[type="email"],
            input[type="password"],
            input[type="tel"],
            textarea {
                padding: 0.95rem 1rem;
                border-radius: 16px;
            }
        }

        @media (max-width: 420px) {
            body::before, body::after { display:none; }
            .register-container {
                padding: 1rem;
                border-radius: 12px;
                margin: 0.6rem auto;
                box-shadow: none;
            }

            .register-header h1 {
                font-size: 1.6rem;
                margin-bottom: 0.35rem;
            }

            .form-row { gap: 0.6rem; }

            input[type="text"],
            input[type="email"],
            input[type="password"],
            input[type="tel"],
            textarea,
            .register-btn {
                padding: 0.8rem 0.9rem;
                font-size: 0.95rem;
                border-radius: 12px;
            }
        }

        footer {
            width: 100%;
            background: linear-gradient(180deg, #1a1a1a 0%, #0f0f0f 100%);
            color: white;
            padding: 3rem 2rem;
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
                padding-left: 40px;
            }

            nav a {
                padding: 0.25rem 0.4rem;
            }

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
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.8rem;
                width: 100%;
                margin: 0;
                padding: 0;
            }

            nav li {
                display: flex;
                justify-content: center;
            }

            nav a {
                display: flex;
                justify-content: center;
                align-items: center;
                width: 100%;
                text-align: center;
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
                box-sizing: border-box;
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
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.8rem;
                width: 100%;
                margin: 0;
                padding: 0;
            }

            nav li {
                display: flex;
                justify-content: center;
            }

            nav a {
                display: flex;
                justify-content: center;
                align-items: center;
                width: 100%;
                text-align: center;
                padding: 0.5rem 0.75rem;
                font-size: 0.8rem;
                box-sizing: border-box;
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
                display: flex;
                justify-content: center;
                align-items: center;
                flex-wrap: nowrap;
                gap: 0.25rem;
                font-size: 0.75rem;
                width: 100%;
                margin: 0;
                padding: 0 0 0 0.35rem;
            }

            nav li {
                display: flex;
                justify-content: center;
                flex: 0 1 auto;
            }

            nav a {
                display: flex;
                justify-content: center;
                align-items: center;
                width: auto;
                max-width: 100%;
                text-align: center;
                padding: 0.35rem 0.4rem;
                font-size: 0.72rem;
                line-height: 1.1;
                white-space: nowrap;
                box-sizing: border-box;
            }
        }

        @media (max-width: 480px) {
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
                display: flex;
                justify-content: center;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.8rem;
                width: 100%;
                margin: 0;
                padding: 0;
            }

            nav li {
                display: flex;
                justify-content: center;
            }

            nav a {
                display: flex;
                justify-content: center;
                align-items: center;
                width: 100%;
                text-align: center;
                padding: 0.5rem 0.75rem;
                box-sizing: border-box;
            }

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
                </ul>
            </nav>
        </div>
    </header>

    <main class="page-main">
        <div class="register-container">
        <div class="register-header">
            <div class="auth-brand">SM Store</div>
            <h1>Create an Account</h1>
            <p>Register now and start shopping with SM Store.</p>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="post" action="register.php?redirect=<?php echo urlencode($redirect); ?>">
            <div class="form-row full">
                <div class="form-group">
                    <label for="full_name">Full Name</label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required pattern="^[A-Za-z_ ]+$" title="Only letters, spaces and underscores are allowed" placeholder="e.g. Salah Mekky">
                </div>
            </div>
            <div class="form-row full">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required minlength="6" pattern="^\S{6,}$" title="Password must be at least 6 characters and cannot contain spaces">
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="6" pattern="^\S{6,}$" title="Password must be at least 6 characters and cannot contain spaces">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Phone</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" inputmode="numeric" pattern="[0-9]{10}" title="Please enter a valid 10-digit phone number" maxlength="10" placeholder="Optional: 10 digits only">
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($address); ?>" placeholder="Optional">
                </div>
            </div>
            <button type="submit" class="register-btn">Register</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="login.php?redirect=<?php echo urlencode($redirect); ?>">Login here</a>
        </div>
    </div>
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
</body>
</html>
