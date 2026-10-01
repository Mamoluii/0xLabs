<?php
// Disable exceptions so vulnerable SQL injection labs fail gracefully
mysqli_report(MYSQLI_REPORT_OFF);

$host = "localhost";
$user = "root";
$pass = "";
$db_name = "0xlabs_db";

// Connect without selecting a database first
$conn = mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Create database if it doesn't exist
mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_select_db($conn, $db_name);

// Create tables if they don't exist
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    balance DECIMAL(10,2) DEFAULT 1500.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    stock INT DEFAULT 100,
    category VARCHAR(100) DEFAULT 'General',
    visible TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
// Add columns if upgrading old DB
@mysqli_query($conn, "ALTER TABLE products ADD COLUMN IF NOT EXISTS category VARCHAR(100) DEFAULT 'General'");
@mysqli_query($conn, "ALTER TABLE products ADD COLUMN IF NOT EXISTS visible TINYINT(1) DEFAULT 1");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending','completed','cancelled') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)");

mysqli_query($conn, "CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
)");

// SQL-09 lab: tracking IDs, looked up by a value taken straight from a cookie
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS tracking (
    id VARCHAR(64) PRIMARY KEY
)");

// Seed default users ONLY the first time (was running on every single
// page load before, wiping cart/orders/users on every request)
$check_users = mysqli_query($conn, "SELECT COUNT(*) as c FROM users");
$row_users = mysqli_fetch_assoc($check_users);
if ($row_users['c'] == 0) {
    mysqli_query($conn, "INSERT INTO users (username, password, role, balance) VALUES
        ('administrator', 'admin123', 'admin', 9999.99),
        ('wiener', 'peter', 'user', 1500.00),
        ('carlos', 'montoya', 'user', 1500.00),
        ('leath', '123456', 'user', 1500.00)
    ");
}

// Seed products if empty
$check2 = mysqli_query($conn, "SELECT COUNT(*) as c FROM products");
$row2 = mysqli_fetch_assoc($check2);
if ($row2['c'] == 0) {
    mysqli_query($conn, "INSERT INTO products (name, price, description, image_path, stock, category, visible) VALUES
        ('Laptop Pro 15', 1800.00, 'High-performance laptop with Intel Core i9, 32GB RAM, 1TB NVMe SSD, and a stunning 15-inch 4K display.', 'Laptop.jpg', 50, 'Electronics', 1),
        ('Gaming Keyboard RGB', 150.00, 'Mechanical gaming keyboard with Cherry MX switches, full RGB backlighting, and programmable macro keys.', 'Keyboard.jpg', 200, 'Electronics', 1),
        ('Wireless Headphones', 120.00, 'Premium over-ear wireless headphones with active noise cancellation and 30-hour battery life.', 'headphones.jpg', 150, 'Electronics', 1),
        ('Aviator Glasses', 85.00, 'Classic aviator-style optical glasses with lightweight titanium frame and UV400 protection.', 'glasses.jpg', 300, 'Accessories', 1),
        ('Luxury Watch', 450.00, 'Elegant smartwatch with sapphire crystal glass, heart rate monitor, GPS tracking, and 7-day battery.', 'watch.jpg', 75, 'Accessories', 1),
        ('Oxford Leather Shoes', 220.00, 'Handcrafted genuine leather Oxford shoes with Goodyear welt construction. Sizes 39-46.', 'shoes.jpg', 100, 'Fashion', 1),
        ('Black Duffle Bag', 180.00, 'Premium genuine leather duffle bag with multiple compartments and detachable shoulder strap.', 'bag.jpg', 80, 'Fashion', 1),
        ('Signature Perfume', 95.00, 'Exclusive eau de parfum with bergamot, jasmine, and sandalwood notes. 12-hour lasting fragrance.', 'perfume.jpg', 500, 'Gifts', 1),
        ('Secret Prototype X1', 2999.00, 'Unreleased prototype — not available to the public. Internal use only.', 'Laptop.jpg', 0, 'Electronics', 0),
        ('VIP Gift Bundle', 750.00, 'Exclusive VIP bundle not listed publicly. Hidden from catalog.', 'bag.jpg', 10, 'Gifts', 0),
        ('Beta Smartwatch', 399.00, 'Unreleased beta version of our next smartwatch. Hidden from store.', 'watch.jpg', 5, 'Electronics', 0)
    ");
}
?>
