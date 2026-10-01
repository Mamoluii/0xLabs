-- =============================================
-- ByteStore - Database Setup
-- =============================================

CREATE DATABASE IF NOT EXISTS 0xlabs_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE 0xlabs_db;

-- =============================================
-- USERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    balance DECIMAL(10,2) DEFAULT 1500.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- PRODUCTS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    description TEXT,
    image_path VARCHAR(255),
    stock INT DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =============================================
-- CART TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    price DECIMAL(10,2) NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- =============================================
-- ORDERS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'completed',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- =============================================
-- ORDER ITEMS TABLE
-- =============================================
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- =============================================
-- SEED DATA - USERS
-- =============================================
INSERT INTO users (username, password, role, balance) VALUES
('admin', 'admin123', 'admin', 9999.99),
('leath', '123456', 'user', 1500.00),
('wiener', 'peter', 'user', 1500.00),
('carlos', 'montoya', 'user', 1500.00);

-- =============================================
-- SEED DATA - PRODUCTS
-- =============================================
INSERT INTO products (name, price, description, image_path, stock) VALUES
('Laptop Pro 15"', 1800.00, 'High-performance laptop with Intel Core i9, 32GB RAM, 1TB NVMe SSD, and a stunning 15-inch 4K display. Perfect for professionals and power users.', 'Laptop.jpg', 50),
('Gaming Keyboard RGB', 150.00, 'Mechanical gaming keyboard with Cherry MX switches, full RGB backlighting, and programmable macro keys. Anti-ghosting technology for competitive gaming.', 'Keyboard.jpg', 200),
('Wireless Headphones', 120.00, 'Premium over-ear wireless headphones with active noise cancellation, 30-hour battery life, and studio-quality sound. Compatible with all Bluetooth devices.', 'headphones.jpg', 150),
('Aviator Glasses', 85.00, 'Classic aviator-style optical glasses with lightweight titanium frame. UV400 protection lenses. Timeless design that suits every face shape.', 'glasses.jpg', 300),
('Luxury Watch', 450.00, 'Elegant smartwatch with sapphire crystal glass, heart rate monitor, GPS tracking, and 7-day battery life. Water resistant up to 50 meters.', 'watch.jpg', 75),
('Oxford Leather Shoes', 220.00, 'Handcrafted genuine leather Oxford shoes with Goodyear welt construction. Available in sizes 39-46. Perfect for formal and business occasions.', 'shoes.jpg', 100),
('Black Duffle Bag', 180.00, 'Premium genuine leather duffle bag with multiple compartments, detachable shoulder strap, and brass hardware. Ideal for travel and gym.', 'bag.jpg', 80),
('Signature Perfume', 95.00, 'Exclusive eau de parfum with top notes of bergamot and lemon, heart of jasmine and rose, base of sandalwood and musk. Long-lasting 12-hour fragrance.', 'perfume.jpg', 500);

-- Safety: add balance column if missing (for existing installs)
ALTER TABLE users ADD COLUMN IF NOT EXISTS balance DECIMAL(10,2) DEFAULT 1500.00;
UPDATE users SET balance = 1500.00 WHERE balance IS NULL;
UPDATE users SET balance = 9999.99 WHERE username = 'admin';
