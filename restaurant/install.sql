-- Restaurant Digital Menu System Database
CREATE DATABASE IF NOT EXISTS restaurant_menu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE restaurant_menu;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) DEFAULT '🍽️',
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    is_popular TINYINT(1) DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number VARCHAR(20) NOT NULL UNIQUE,
    table_code VARCHAR(20) NOT NULL UNIQUE,
    capacity INT DEFAULT 4,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_id INT NOT NULL,
    session_token VARCHAR(64) NOT NULL,
    status ENUM('pending','preparing','ready','completed') DEFAULT 'pending',
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (table_id) REFERENCES tables(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(10,2) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Sample categories
INSERT INTO categories (name, icon, sort_order) VALUES
('Ana Yemekler', '🍖', 1),
('Çorbalar', '🍲', 2),
('Salatalar', '🥗', 3),
('Pizzalar', '🍕', 4),
('Burgerler', '🍔', 5),
('Tatlılar', '🍰', 6),
('Dondurma', '🍦', 7),
('Sıcak İçecekler', '☕', 8),
('Soğuk İçecekler', '🥤', 9);

-- Sample products
INSERT INTO products (category_id, name, description, price, is_popular) VALUES
(1, 'Izgara Köfte', 'El yapımı dana köfte, yanında pilav ve salata ile', 120.00, 1),
(1, 'Tavuk Şiş', 'Marine edilmiş tavuk, közlenmiş sebzeler ile', 110.00, 1),
(1, 'Kuzu Tandır', 'Fırında pişirilmiş kuzu eti, yanında bulgur pilavı', 160.00, 0),
(2, 'Mercimek Çorbası', 'Geleneksel kırmızı mercimek çorbası', 45.00, 1),
(2, 'Ezogelin Çorbası', 'Baharatlı ezogelin çorbası', 45.00, 0),
(3, 'Çoban Salatası', 'Taze domates, salatalık, biber, soğan', 55.00, 0),
(3, 'Sezar Salatası', 'Marul, kruton, parmezan, sezar sos', 75.00, 1),
(4, 'Margarita Pizza', 'Domates sos, mozzarella, fesleğen', 130.00, 1),
(4, 'Karışık Pizza', 'Sucuk, mantar, biber, mısır, mozzarella', 150.00, 1),
(5, 'Klasik Burger', 'Dana köfte, marul, domates, soğan, patates kızartması ile', 125.00, 1),
(5, 'Cheese Burger', 'Dana köfte, cheddar peyniri, özel sos, patates kızartması ile', 135.00, 0),
(6, 'Sütlaç', 'Fırında pişirilmiş geleneksel sütlaç', 65.00, 1),
(6, 'Tiramisu', 'İtalyan tiramisu, mascarpone peyniri ile', 85.00, 0),
(6, 'Baklava', 'Fıstıklı baklava, 6 dilim', 90.00, 1),
(7, 'Çilekli Dondurma', '2 top çilek dondurma', 55.00, 0),
(7, 'Karışık Dondurma', '3 top karışık dondurma', 65.00, 1),
(8, 'Türk Kahvesi', 'Geleneksel Türk kahvesi', 35.00, 0),
(8, 'Çay', 'Demlik çayı', 15.00, 0),
(8, 'Filtre Kahve', 'Öğütülmüş filtre kahve', 45.00, 1),
(9, 'Cola', '330ml kutu', 25.00, 0),
(9, 'Ayran', 'Ev yapımı soğuk ayran', 20.00, 1),
(9, 'Taze Sıkılmış OJ', 'Taze portakal suyu', 55.00, 1);

-- Sample tables
INSERT INTO tables (table_number, table_code, capacity) VALUES
('1', 'MASA01', 4),
('2', 'MASA02', 4),
('3', 'MASA03', 6),
('4', 'MASA04', 2),
('5', 'MASA05', 4),
('6', 'MASA06', 8),
('7', 'MASA07', 4),
('8', 'MASA08', 4);
