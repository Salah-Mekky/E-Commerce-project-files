<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


define('DB_HOST', 'your_database_host');
define('DB_USER', 'your_database_user');
define('DB_PASSWORD', 'your_database_password');
define('DB_NAME', 'your_database_name');

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8");
    return $conn;
}

function getUsersPasswordColumn($conn) {
    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'password'");
    if ($result && $result->num_rows === 1) {
        return 'password';
    }

    $result = $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
    if ($result && $result->num_rows === 1) {
        return 'password_hash';
    }

    return null;
}

function ensureContactsIsReadColumn($conn) {
    $result = $conn->query("SHOW COLUMNS FROM contacts LIKE 'is_read'");
    if ($result && $result->num_rows === 0) {
        $conn->query("ALTER TABLE contacts ADD COLUMN is_read BOOLEAN DEFAULT FALSE AFTER submitted_at");
    }
}

function getAllProducts($conn) {
    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            ORDER BY p.created_at DESC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getProductsByCategory($conn, $category_id) {
    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.category_id = ? 
            ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getFilteredProducts($conn, $filters = []) {
    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE 1=1";
    
    $bind_params = [];
    $bind_types = "";
    
    if (!empty($filters['category_id'])) {
        $category_id = intval($filters['category_id']);
        $sql .= " AND p.category_id = ?";
        $bind_params[] = $category_id;
        $bind_types .= "i";
    }
    
    if (!empty($filters['price'])) {
        if ($filters['price'] === 'low') {
            $price = 50;
            $sql .= " AND p.price < ?";
            $bind_params[] = $price;
            $bind_types .= "d";
        } elseif ($filters['price'] === 'medium') {
            $price_low = 50;
            $price_high = 150;
            $sql .= " AND p.price >= ? AND p.price <= ?";
            $bind_params[] = $price_low;
            $bind_params[] = $price_high;
            $bind_types .= "dd";
        } elseif ($filters['price'] === 'high') {
            $price = 150;
            $sql .= " AND p.price > ?";
            $bind_params[] = $price;
            $bind_types .= "d";
        }
    }
    
    if (!empty($filters['in_stock']) && $filters['in_stock'] === '1') {
        $sql .= " AND p.stock_quantity > 0";
    }
    
    if (!empty($filters['sort'])) {
        if ($filters['sort'] === 'price_high') {
            $sql .= " ORDER BY p.price DESC";
        } elseif ($filters['sort'] === 'price_low') {
            $sql .= " ORDER BY p.price ASC";
        } else {
            $sql .= " ORDER BY p.created_at DESC";
        }
    } else {
        $sql .= " ORDER BY p.created_at DESC";
    }
    
    $stmt = $conn->prepare($sql);
    if ($bind_types && count($bind_params) > 0) {
        $stmt->bind_param($bind_types, ...$bind_params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getCarouselProducts($conn, $limit = 6) {
    $sql = "SELECT p.*, c.name as category_name FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            ORDER BY p.created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getAllCategories($conn) {
    $sql = "SELECT * FROM categories ORDER BY name ASC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function getProductById($conn, $product_id) {
    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.product_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getRelatedProducts($conn, $product_id, $category_id, $limit = 3) {
    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.category_id = ? AND p.product_id != ? 
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $category_id, $product_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function searchProducts($conn, $search_term) {
    $search_term = "%" . $search_term . "%";
    $sql = "SELECT p.*, c.name as category_name FROM products p 
            LEFT JOIN categories c ON p.category_id = c.category_id 
            WHERE p.name LIKE ? OR p.description LIKE ?
            ORDER BY p.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function saveContactMessage($conn, $name, $email, $subject, $message) {
    $sql = "INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $name, $email, $subject, $message);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function calculateCartTotal($conn, $cart_items) {
    $total = 0;
    foreach ($cart_items as $item) {
        $product = getProductById($conn, $item['product_id']);
        if ($product) {
            $total += $product['price'] * $item['quantity'];
        }
    }
    return $total;
}
?>