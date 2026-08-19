<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config.php';
    $conn = getDBConnection();

    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    $isJSON = stripos($content_type, 'application/json') !== false;

    if ($isJSON) {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $subject = trim($data['subject'] ?? '');
        $message = trim($data['message'] ?? '');
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
    }

    if (!$name || !$email || !$message) {
        $error_msg = 'Please fill in all required fields.';
        if ($isJSON) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            $_SESSION['contact_error'] = $error_msg;
            header('Location: contact.php');
        }
        exit;
    }

    if (!preg_match('/^[\p{L}_ ]+$/u', $name)) {
        $error_msg = 'Name can only contain letters, spaces, and underscores. No digits or special characters are allowed.';
        if ($isJSON) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            $_SESSION['contact_error'] = $error_msg;
            header('Location: contact.php');
        }
        exit;
    }

    if (!preg_match('/[\p{L}]/u', $name)) {
        $error_msg = 'Name must contain at least one letter.';
        if ($isJSON) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            $_SESSION['contact_error'] = $error_msg;
            header('Location: contact.php');
        }
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = 'Please provide a valid email address.';
        if ($isJSON) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            $_SESSION['contact_error'] = $error_msg;
            header('Location: contact.php');
        }
        exit;
    }

    $allowed_subjects = ['inquiry', 'complaint', 'suggestion'];
    if ($subject !== '') {
        $subject_l = strtolower($subject);
        if (!in_array($subject_l, $allowed_subjects, true)) {
            $error_msg = 'Invalid subject selected.';
            if ($isJSON) {
                echo json_encode(['success' => false, 'message' => $error_msg]);
            } else {
                $_SESSION['contact_error'] = $error_msg;
                header('Location: contact.php');
            }
            exit;
        }
        $subject = $subject_l;
    } else {
        $subject = 'inquiry';
    }

    $stmt = $conn->prepare('INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        $error_msg = 'Database error. Please try again later.';
        if ($isJSON) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            $_SESSION['contact_error'] = $error_msg;
            header('Location: contact.php');
        }
        exit;
    }

    $stmt->bind_param('ssss', $name, $email, $subject, $message);
    if ($stmt->execute()) {
        $_SESSION['contact_success'] = 'Message sent successfully! We\'ll get back to you soon.';
        header('Location: contact.php');
        exit;
    } else {
        $error_msg = 'Failed to submit your message. Please try again.';
        if ($isJSON) {
            echo json_encode(['success' => false, 'message' => $error_msg]);
        } else {
            $_SESSION['contact_error'] = $error_msg;
            header('Location: contact.php');
        }
    }
    $stmt->close();
    $conn->close();
}
?>
