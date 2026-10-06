<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

$pdo = db();
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

echo "Trimming catalog to 6 categories + 12 products...\n";

// Clear dependent rows first
$pdo->exec('DELETE FROM product_reviews');
$pdo->exec('DELETE FROM wishlists');
$pdo->exec('DELETE FROM cart_items');
$pdo->exec('DELETE FROM payments');
$pdo->exec('DELETE FROM order_items');
$pdo->exec('DELETE FROM orders');
$pdo->exec('DELETE FROM product_images');
$pdo->exec('DELETE FROM products');
$pdo->exec('DELETE FROM categories');
$pdo->exec('DELETE FROM coupons');
$pdo->exec('DELETE FROM notifications');

$pdo->exec("INSERT INTO categories (id, name, slug, description, icon, status, created_at, updated_at) VALUES
(1, 'Computers', 'computers', 'Laptops, desktops and accessories', 'fa-laptop', 'active', NOW(), NOW()),
(2, 'Mobiles', 'mobiles', 'Smartphones and mobile gear', 'fa-mobile-screen', 'active', NOW(), NOW()),
(3, 'Audio', 'audio', 'Headphones, earbuds and speakers', 'fa-headphones', 'active', NOW(), NOW()),
(4, 'Gaming', 'gaming', 'Consoles, controllers and games', 'fa-gamepad', 'active', NOW(), NOW()),
(5, 'Cameras', 'cameras', 'Photo and video equipment', 'fa-camera', 'active', NOW(), NOW()),
(6, 'Fashion', 'fashion', 'Apparel and lifestyle wear', 'fa-shirt', 'active', NOW(), NOW())");

$adminId = (int) $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetchColumn();
if ($adminId < 1) {
    $adminId = 1;
}

$products = [
    [1, 1, 'AetherBook Pro 14', 'aetherbook-pro-14', 'Lightweight productivity laptop with vivid display and all-day battery.', 1299, 1499, 25, 'NB-CMP-001', 1],
    [2, 1, 'NovaDock USB-C Hub', 'novadock-usb-c-hub', '7-in-1 hub with HDMI, SD, and fast charging passthrough.', 69, 89, 80, 'NB-CMP-002', 1],
    [3, 2, 'Lumina Phone X', 'lumina-phone-x', 'Flagship smartphone with dual cameras and OLED panel.', 899, 999, 30, 'NB-MOB-001', 1],
    [4, 2, 'ChargeBeam 65W GaN', 'chargebeam-65w-gan', 'Compact dual-port GaN charger for laptops and phones.', 49, 59, 110, 'NB-MOB-002', 0],
    [5, 3, 'EchoBuds Air', 'echobuds-air', 'True wireless earbuds with adaptive ANC.', 149, 179, 60, 'NB-AUD-001', 1],
    [6, 3, 'WaveBar Mini Speaker', 'wavebar-mini-speaker', 'Portable Bluetooth speaker with rich bass.', 79, null, 55, 'NB-AUD-002', 0],
    [7, 4, 'ArcadePad Elite', 'arcadepad-elite', 'Wireless controller with programmable rear buttons.', 64, 79, 70, 'NB-GAM-001', 1],
    [8, 4, 'Neon Headset Pro', 'neon-headset-pro', 'Surround gaming headset with detachable mic.', 99, 129, 45, 'NB-GAM-002', 0],
    [9, 5, 'Clarity Cam 4K', 'clarity-cam-4k', 'Compact 4K vlogging camera with stabilization.', 459, 499, 18, 'NB-CAM-001', 1],
    [10, 5, 'Tripod Flex Pro', 'tripod-flex-pro', 'Travel tripod with ball head and phone mount.', 89, 109, 40, 'NB-CAM-002', 0],
    [11, 6, 'Urban Linen Shirt', 'urban-linen-shirt', 'Breathable linen shirt for everyday comfort.', 48, 65, 90, 'NB-FSH-001', 1],
    [12, 6, 'TrailFlex Joggers', 'trailflex-joggers', 'Stretch joggers with zip pockets.', 54, null, 75, 'NB-FSH-002', 0],
];

$ins = $pdo->prepare("INSERT INTO products (id, seller_id, category_id, name, slug, description, price, compare_price, stock, sku, status, featured, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW(), NOW())");
$img = $pdo->prepare("INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, 0, NOW())");

foreach ($products as $p) {
    $ins->execute([$p[0], $adminId, $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9]]);
    $img->execute([$p[0], 'products/placeholder-' . $p[0] . '.svg']);
}

$pdo->exec("INSERT INTO coupons (id, code, discount_type, discount_value, minimum_amount, maximum_discount, usage_limit, used_count, start_date, expiry_date, status, created_at, updated_at) VALUES
(1, 'WELCOME10', 'percentage', 10.00, 50.00, 40.00, 200, 1, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(NOW(), INTERVAL 180 DAY), 'active', NOW(), NOW()),
(2, 'SAVE20', 'fixed', 20.00, 100.00, NULL, 100, 1, DATE_SUB(NOW(), INTERVAL 3 DAY), DATE_ADD(NOW(), INTERVAL 90 DAY), 'active', NOW(), NOW()),
(3, 'FREESHIP', 'fixed', 8.00, 40.00, NULL, 500, 0, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(NOW(), INTERVAL 60 DAY), 'active', NOW(), NOW())");

// Keep diana user id mapping flexible
$dianaId = (int) $pdo->query("SELECT id FROM users WHERE email = 'diana@gmail.com' LIMIT 1")->fetchColumn();
if (!$dianaId) {
    $dianaId = (int) $pdo->query("SELECT id FROM users WHERE role = 'user' ORDER BY id ASC LIMIT 1")->fetchColumn();
}

if ($dianaId) {
    $pdo->prepare("INSERT INTO orders (id, order_number, user_id, subtotal, discount_amount, shipping_fee, total_amount, coupon_id, payment_method, payment_status, transaction_id, order_status, shipping_name, shipping_phone, shipping_address, created_at, updated_at)
        VALUES (1, 'ORD-20261006-000001', ?, 218.00, 20.00, 0.00, 198.00, 2, 'cod', 'paid', 'COD-1001', 'completed', 'Diana Lee', '+60120000001', '10 Lakeview, KL', DATE_SUB(NOW(), INTERVAL 5 DAY), NOW())")
        ->execute([$dianaId]);
    $pdo->exec("INSERT INTO order_items (order_id, product_id, seller_id, product_name, quantity, unit_price, subtotal, created_at) VALUES
        (1, 2, {$adminId}, 'NovaDock USB-C Hub', 1, 69.00, 69.00, NOW()),
        (1, 5, {$adminId}, 'EchoBuds Air', 1, 149.00, 149.00, NOW())");
    $pdo->exec("INSERT INTO payments (order_id, payment_method, transaction_id, amount, currency, status, response_data, created_at, updated_at)
        VALUES (1, 'cod', 'COD-1001', 198.00, 'USD', 'completed', NULL, NOW(), NOW())");
    $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, order_id, rating, comment, status, created_at, updated_at) VALUES
        (2, ?, 1, 5, 'Hub works perfectly with my laptop setup.', 'approved', NOW(), NOW()),
        (5, ?, 1, 4, 'Great sound and comfortable fit.', 'approved', NOW(), NOW())")->execute([$dianaId, $dianaId]);
    $pdo->prepare('INSERT INTO wishlists (user_id, product_id, created_at) VALUES (?, 1, NOW()), (?, 9, NOW())')
        ->execute([$dianaId, $dianaId]);
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

$cats = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$prods = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
echo "DONE categories={$cats}, products={$prods}\n";
echo "Shop shows 6 products per page => 2 pages.\n";
echo "Upload real images in Admin > Products > Edit.\n";
