-- E-commerce System Schema + Sample Data
-- Import via phpMyAdmin or: mysql -u root < ecommerce_marketplace.sql

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS ecommerce CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecommerce;

DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS password_resets;
DROP TABLE IF EXISTS product_reviews;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS coupons;
DROP TABLE IF EXISTS wishlists;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS carts;
DROP TABLE IF EXISTS product_images;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  phone VARCHAR(40) NOT NULL DEFAULT '',
  address TEXT NOT NULL,
  role ENUM('admin','user') NOT NULL DEFAULT 'user',
  status ENUM('active','suspended') NOT NULL DEFAULT 'active',
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  avatar VARCHAR(255) DEFAULT NULL,
  store_name VARCHAR(150) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_users_role (role),
  INDEX idx_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE categories (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  slug VARCHAR(150) NOT NULL UNIQUE,
  description TEXT,
  image VARCHAR(255) DEFAULT NULL,
  icon VARCHAR(80) DEFAULT 'fa-tags',
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE products (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  seller_id INT UNSIGNED NOT NULL,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(200) NOT NULL,
  slug VARCHAR(220) NOT NULL UNIQUE,
  description TEXT NOT NULL,
  price DECIMAL(12,2) NOT NULL,
  compare_price DECIMAL(12,2) DEFAULT NULL,
  stock INT NOT NULL DEFAULT 0,
  sku VARCHAR(80) NOT NULL,
  status ENUM('active','inactive','archived') NOT NULL DEFAULT 'active',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_products_seller (seller_id),
  INDEX idx_products_category (category_id),
  INDEX idx_products_status (status),
  INDEX idx_products_featured (featured),
  INDEX idx_products_price (price),
  INDEX idx_products_sku (sku),
  FULLTEXT INDEX ft_products_search (name, description, sku),
  CONSTRAINT fk_products_seller FOREIGN KEY (seller_id) REFERENCES users(id),
  CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_images (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  image_path VARCHAR(255) NOT NULL,
  display_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX idx_pi_product (product_id),
  CONSTRAINT fk_pi_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE carts (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL UNIQUE,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_carts_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cart_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cart_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  price DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_cart_product (cart_id, product_id),
  CONSTRAINT fk_ci_cart FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
  CONSTRAINT fk_ci_product FOREIGN KEY (product_id) REFERENCES products(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE wishlists (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_wish_user_product (user_id, product_id),
  CONSTRAINT fk_wish_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wish_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE coupons (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code VARCHAR(40) NOT NULL UNIQUE,
  discount_type ENUM('percentage','fixed') NOT NULL,
  discount_value DECIMAL(12,2) NOT NULL,
  minimum_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  maximum_discount DECIMAL(12,2) DEFAULT NULL,
  usage_limit INT NOT NULL DEFAULT 0,
  used_count INT NOT NULL DEFAULT 0,
  start_date DATETIME DEFAULT NULL,
  expiry_date DATETIME DEFAULT NULL,
  status ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orders (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_number VARCHAR(40) NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  discount_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
  shipping_fee DECIMAL(12,2) NOT NULL DEFAULT 0,
  total_amount DECIMAL(12,2) NOT NULL,
  coupon_id INT UNSIGNED DEFAULT NULL,
  payment_method VARCHAR(40) NOT NULL,
  payment_status ENUM('pending','paid','failed','refunded') NOT NULL DEFAULT 'pending',
  transaction_id VARCHAR(120) DEFAULT NULL,
  order_status ENUM('pending','paid','processing','shipped','completed','cancelled') NOT NULL DEFAULT 'pending',
  shipping_name VARCHAR(120) NOT NULL,
  shipping_phone VARCHAR(40) NOT NULL,
  shipping_address TEXT NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_orders_user (user_id),
  INDEX idx_orders_status (order_status),
  INDEX idx_orders_payment (payment_status),
  CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id),
  CONSTRAINT fk_orders_coupon FOREIGN KEY (coupon_id) REFERENCES coupons(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  product_id INT UNSIGNED NOT NULL,
  seller_id INT UNSIGNED NOT NULL,
  product_name VARCHAR(200) NOT NULL,
  quantity INT NOT NULL,
  unit_price DECIMAL(12,2) NOT NULL,
  subtotal DECIMAL(12,2) NOT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_oi_order (order_id),
  INDEX idx_oi_seller (seller_id),
  CONSTRAINT fk_oi_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT fk_oi_product FOREIGN KEY (product_id) REFERENCES products(id),
  CONSTRAINT fk_oi_seller FOREIGN KEY (seller_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  order_id INT UNSIGNED NOT NULL,
  payment_method VARCHAR(40) NOT NULL,
  transaction_id VARCHAR(120) DEFAULT NULL,
  amount DECIMAL(12,2) NOT NULL,
  currency VARCHAR(10) NOT NULL DEFAULT 'MYR',
  status VARCHAR(40) NOT NULL,
  response_data LONGTEXT,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  INDEX idx_payments_order (order_id),
  INDEX idx_payments_txn (transaction_id),
  CONSTRAINT fk_payments_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE product_reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  product_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  order_id INT UNSIGNED NOT NULL,
  rating TINYINT NOT NULL,
  comment TEXT NOT NULL,
  status ENUM('approved','hidden') NOT NULL DEFAULT 'approved',
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_review_user_product_order (user_id, product_id, order_id),
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_reviews_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  CONSTRAINT chk_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE password_resets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  email VARCHAR(190) NOT NULL,
  token VARCHAR(64) NOT NULL,
  expires_at DATETIME NOT NULL,
  used_at DATETIME DEFAULT NULL,
  created_at DATETIME NOT NULL,
  INDEX idx_pr_token (token),
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  title VARCHAR(180) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(40) NOT NULL DEFAULT 'info',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  INDEX idx_notif_user (user_id),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Password hashes (PHP password_hash):
-- Admin: admin1234
-- Demo user Diana: diana1234

INSERT INTO users (id, name, email, password, phone, address, role, status, must_change_password, store_name, created_at, updated_at) VALUES
(1, 'Nova Admin', 'admin@gmail.com', '$2y$10$Hd99Hpj9R1kKbcomewfyOuZZay8khj.XlZggbgo70eMBpZUabQiIO', '+60110000001', '1 Admin Tower, Kuala Lumpur', 'admin', 'active', 0, 'NovaBazaar', NOW(), NOW()),
(2, 'Diana Lee', 'diana@gmail.com', '$2y$10$K24cEUUarSSyFr7LBDV3zu493zGVnFxhY4c1x3QOAwEJLcPACc2TG', '+60120000001', '10 Lakeview, KL', 'user', 'active', 0, NULL, NOW(), NOW());

INSERT INTO categories (id, name, slug, description, icon, status, created_at, updated_at) VALUES
(1, 'Computers', 'computers', 'Laptops, desktops and accessories', 'fa-laptop', 'active', NOW(), NOW()),
(2, 'Mobiles', 'mobiles', 'Smartphones and mobile gear', 'fa-mobile-screen', 'active', NOW(), NOW()),
(3, 'Audio', 'audio', 'Headphones, earbuds and speakers', 'fa-headphones', 'active', NOW(), NOW()),
(4, 'Gaming', 'gaming', 'Consoles, controllers and games', 'fa-gamepad', 'active', NOW(), NOW()),
(5, 'Cameras', 'cameras', 'Photo and video equipment', 'fa-camera', 'active', NOW(), NOW()),
(6, 'Fashion', 'fashion', 'Apparel and lifestyle wear', 'fa-shirt', 'active', NOW(), NOW());

-- 12 products = 2 shop pages (6 per page). Images are downloaded from verified URLs via tools/set_products_from_image_urls.php
INSERT INTO products (id, seller_id, category_id, name, slug, description, price, compare_price, stock, sku, status, featured, created_at, updated_at) VALUES
(1, 1, 1, 'AetherBook Pro 14', 'aetherbook-pro-14', 'Lightweight productivity laptop with vivid display and all-day battery.', 1299.00, 1499.00, 25, 'NB-CMP-001', 'active', 1, NOW(), NOW()),
(2, 1, 1, 'NovaTab S8 Plus', 'novatab-s8-plus', 'Large tablet for work and media with a vivid display and stylus support.', 699.00, 799.00, 40, 'NB-CMP-002', 'active', 1, NOW(), NOW()),
(3, 1, 2, 'Lumina Phone X', 'lumina-phone-x', 'Flagship smartphone with dual cameras and OLED panel.', 899.00, 999.00, 30, 'NB-MOB-001', 'active', 1, NOW(), NOW()),
(4, 1, 2, 'ChargeBeam Cable Kit', 'chargebeam-cable-kit', 'Fast wall charger and cable kit for phones and tablets.', 49.00, 59.00, 110, 'NB-MOB-002', 'active', 0, NOW(), NOW()),
(5, 1, 3, 'EchoBuds Air', 'echobuds-air', 'True wireless earbuds with adaptive ANC.', 149.00, 179.00, 60, 'NB-AUD-001', 'active', 1, NOW(), NOW()),
(6, 1, 3, 'WaveBar Mini Speaker', 'wavebar-mini-speaker', 'Portable smart speaker with rich room-filling sound.', 79.00, NULL, 55, 'NB-AUD-002', 'active', 0, NOW(), NOW()),
(7, 1, 4, 'Arcade Aorus Tee', 'arcade-aorus-tee', 'Gaming lifestyle tee for practice sessions and LAN nights.', 34.00, 44.00, 70, 'NB-GAM-001', 'active', 1, NOW(), NOW()),
(8, 1, 4, 'Neon Headset Pro', 'neon-headset-pro', 'Over-ear headset with clear voice mic and deep bass.', 99.00, 129.00, 45, 'NB-GAM-002', 'active', 0, NOW(), NOW()),
(9, 1, 5, 'Clarity Studio Pedestal', 'clarity-studio-pedestal', 'Studio camera pedestal for stable video and broadcast setups.', 459.00, 499.00, 18, 'NB-CAM-001', 'active', 1, NOW(), NOW()),
(10, 1, 5, 'Flex Tripod Pro', 'flex-tripod-pro', 'Travel phone tripod with extendable pole and stable legs.', 89.00, 109.00, 40, 'NB-CAM-002', 'active', 0, NOW(), NOW()),
(11, 1, 6, 'Urban Linen Shirt', 'urban-linen-shirt', 'Breathable short-sleeve shirt for everyday comfort.', 48.00, 65.00, 90, 'NB-FSH-001', 'active', 1, NOW(), NOW()),
(12, 1, 6, 'TrailFlex Runners', 'trailflex-runners', 'Lightweight sport sneakers with cushioned sole.', 54.00, NULL, 75, 'NB-FSH-002', 'active', 0, NOW(), NOW());

INSERT INTO product_images (product_id, image_path, display_order, created_at)
SELECT id, CONCAT('products/product-', id, '-url.webp'), 0, NOW() FROM products;

INSERT INTO coupons (id, code, discount_type, discount_value, minimum_amount, maximum_discount, usage_limit, used_count, start_date, expiry_date, status, created_at, updated_at) VALUES
(1, 'WELCOME10', 'percentage', 10.00, 50.00, 40.00, 200, 1, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 180 DAY), 'active', NOW(), NOW()),
(2, 'SAVE20', 'fixed', 20.00, 100.00, NULL, 100, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 90 DAY), 'active', NOW(), NOW()),
(3, 'FREESHIP', 'fixed', 8.00, 40.00, NULL, 500, 0, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 60 DAY), 'active', NOW(), NOW());

INSERT INTO orders (id, order_number, user_id, subtotal, discount_amount, shipping_fee, total_amount, coupon_id, payment_method, payment_status, transaction_id, order_status, shipping_name, shipping_phone, shipping_address, created_at, updated_at) VALUES
(1, 'ORD-20260901-000001', 2, 218.00, 20.00, 0.00, 198.00, 2, 'cod', 'paid', 'COD-1001', 'completed', 'Diana Lee', '+60120000001', '10 Lakeview, KL', DATE_SUB(NOW(), INTERVAL 20 DAY), NOW()),
(2, 'ORD-20260918-000002', 2, 69.00, 0.00, 8.00, 77.00, NULL, 'cod', 'pending', NULL, 'pending', 'Diana Lee', '+60120000001', '10 Lakeview, KL', DATE_SUB(NOW(), INTERVAL 5 DAY), NOW());

INSERT INTO order_items (order_id, product_id, seller_id, product_name, quantity, unit_price, subtotal, created_at) VALUES
(1, 2, 1, 'NovaTab S8 Plus', 1, 699.00, 699.00, NOW()),
(1, 5, 1, 'EchoBuds Air', 1, 149.00, 149.00, NOW()),
(2, 2, 1, 'NovaTab S8 Plus', 1, 699.00, 699.00, NOW());

INSERT INTO payments (order_id, payment_method, transaction_id, amount, currency, status, response_data, created_at, updated_at) VALUES
(1, 'cod', 'COD-1001', 198.00, 'MYR', 'completed', NULL, NOW(), NOW());

INSERT INTO product_reviews (product_id, user_id, order_id, rating, comment, status, created_at, updated_at) VALUES
(2, 2, 1, 5, 'Hub works perfectly with my laptop setup.', 'approved', NOW(), NOW()),
(5, 2, 1, 4, 'Great sound and comfortable fit.', 'approved', NOW(), NOW());

INSERT INTO wishlists (user_id, product_id, created_at) VALUES
(2, 1, NOW()),
(2, 9, NOW());

INSERT INTO notifications (user_id, title, message, type, is_read, created_at) VALUES
(2, 'Welcome', 'Thanks for joining NovaBazaar.', 'system', 1, NOW()),
(1, 'Store ready', 'Sample data loaded successfully.', 'system', 0, NOW());
