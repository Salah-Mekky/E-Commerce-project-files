CREATE DATABASE IF NOT EXISTS ecommerce_db;
USE ecommerce_db;

CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;


INSERT INTO users (full_name, email, password, phone, address, role)
VALUES (
  'Admin User',
  'admin@example.com',
  '$2y$10$X8Ldbe6C6.M8xWhF8jO5f.YmD68vL/JkXUvXy.T9g6Lz9H1zQxWSm', -- Password: 'password' (or whatever dummy password this hash resolves to)
  '0000000000',
  'Default Address',
  'admin'
);

CREATE TABLE IF NOT EXISTS categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) UNIQUE NOT NULL,
    description TEXT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    stock_quantity INT DEFAULT 0,
    image_url VARCHAR(255),
    category_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart_items (
    cart_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL CHECK (quantity > 0),
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_cart_item (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT,
    quantity INT NOT NULL,
    unit_price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contacts (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(100),
    message TEXT NOT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_read BOOLEAN DEFAULT FALSE
) ENGINE=InnoDB;

INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and gadgets'),
('Fashion', 'Clothing and accessories'),
('Home & Garden', 'Home decor and garden items'),
('Accessories', 'Small useful items and accessories');

INSERT INTO products (name, description, price, stock_quantity, image_url, category_id) VALUES
('Denon AH-GC30 Premium Wireless Headphones', 'High-quality sound with active noise cancellation technology', 49.99, 25, 'products-imgs/Denon AH-GC30 Premium Wireless Headphones.jpeg', 1),
('iPhone 17 pro max','The Ultimate Pro Experience.Redefining power with the A19 Pro chip, a groundbreaking 48MP triple-camera system with 8x optical zoom, and our longest-lasting battery ever. Featuring a stunning 6.9-inch ProMotion display and sleek aluminum unibody, this is the ultimate iPhone for creators and power users.',399.99,30,'products-imgs/iPhone 17 pro max.jpg',1),
('Apple Watch SE 3', 'Advanced fitness tracking with heart rate monitor and notifications', 249.99, 15, 'products-imgs/Apple watch series 10.jpeg', 1),
('Mag+ Fast Wireless Charger', 'Fast wireless charging for all compatible devices', 29.99, 40, 'products-imgs/Mag+ Fast Wireless Charger.jpeg', 1),
('Gucci Sunglasses', 'Premium UV protection with stylish design and durable frame', 149.99, 18, 'products-imgs/Apollo Sunglasses.jpeg', 2),
("Men's Slim Fit Polo T-Shirt | Black", 'Comfortable 100% cotton t-shirt in modern designs', 89.99, 50, 'products-imgs/Classic Polo Cotton T-shirt.jpeg', 2),
('Cole Hann casual shoes', 'Comfortable everyday shoes with superior support', 79.99, 30, 'products-imgs/COLE HAAN casual shoes.jpg', 2),
('Breville Espresso Machine', 'Programmable coffee maker with thermal carafe', 99.99, 20, 'products-imgs/Espresso Machine for Every Skill level.jpeg', 3),
('Desk Lamp', 'LED desk lamp with adjustable brightness levels', 14.99, 35, 'products-imgs/Architect Desk Lamp Black.jpg', 3),
('Portronics Breeze 5 25W TWS Black Portable Wireless Bluetooth Speaker', 'Waterproof Bluetooth speaker with 6-hour battery', 9.99, 28, 'products-imgs/Portronics Breeze 5 25W Black Wireless Bluetooth.jpeg', 1),
('Premium Leather Belt', 'Premium genuine leather belt with premium buckle', 34.99, 22, 'products-imgs/Brown Solid Leather Belt.jpeg', 2),
('Ceramic Plant Pot', 'Beautiful ceramic pot with drainage holes', 24.99, 45, 'products-imgs/Ceramic plant plots.jpg', 3),
('Small mobile/tablet stand','Universal Adjustable Desktop Phone Stand – Multi-Angle Holder for Smartphones & Tablets',9.99,30,'products-imgs/universal-aluminium-phone-stand.jpg',4),
('Heart Lab Grown Diamond Bezel Necklace - 1.5 Carat','Elevate your look with this breathtaking 1.5-carat heart-shaped lab-grown diamond necklace. Nestled in a sleek, modern bezel setting, it offers maximum brilliance and secure, snag-free wear. Crafted from ethically sourced materials, this premium piece delivers eco-friendly luxury that transitions effortlessly from day to night.Stone: 1.5 CT Heart-Cut Lab-Grown DiamondSetting: Modern Protective BezelStyle: Sustainable Luxury for Daily Elegance',1499.99,10,'products-imgs/necklace.jpg',4),
('SABO 43 Professional Lawn Mower','Engineered for demanding landscape maintenance, the SABO 43 Pro is a commercial-grade petrol lawn mower designed for precise cutting in tight, obstacle-heavy spaces. It features an ultra-robust aluminum chassis with steel reinforcements, high-traction steel wheels, and a powerful 4-stroke engine. Perfect for professionals needing agility without sacrificing heavy-duty performance.',979.99,50,'products-imgs/lawn mower.jpg',3),
('Samsung 85" QLED 4K Smart TV (QE85QN70FAUXXH)',"Transform your living room into a world-class home theater with the Samsung 85-Inch Smart TV. Driven by Samsung's advanced processor, this massive display delivers breathtaking 4K resolution with lifelike color accuracy and deep, dramatic contrast. Its sleek, ultra-slim bezel design blends seamlessly into your wall like a piece of art. Powered by a smart operating system, it centralizes all your favorite streaming apps, gaming hubs, and voice assistants into a fast, intuitive, and immersive entertainment experience.",1349.99,40,'products-imgs/Samsung television.jpg',1),
('Gardening Tools Set of 10','Complete Garden Tool Kit Comes With Bag & Gloves,Garden Tool Set with Spray-Bottle Indoors & Outdoors - Durable Garden Tools Set Ideal Garden Tool Kit Gifts for Women & Men',59.99,60,'products-imgs/Garden toolset.jpg',3);
