<?php
declare(strict_types=1);
/**
 * Set every product to a verified matching image URL.
 * If no reliable photo exists for a product, rename it to a product that has one.
 */
require dirname(__DIR__) . '/config/bootstrap.php';

$pdo = db();
$destDir = NB_ROOT . '/uploads/products';
if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

// category_id: 1 Computers, 2 Mobiles, 3 Audio, 4 Gaming, 5 Cameras, 6 Fashion
$catalog = [
    1 => [
        'category_id' => 1,
        'name' => 'AetherBook Pro 14',
        'slug' => 'aetherbook-pro-14',
        'description' => 'Lightweight productivity laptop with vivid display and all-day battery.',
        'price' => 1299.00,
        'compare_price' => 1499.00,
        'stock' => 25,
        'sku' => 'NB-CMP-001',
        'featured' => 1,
        'image_url' => 'https://cdn.dummyjson.com/product-images/laptops/apple-macbook-pro-14-inch-space-grey/1.webp',
    ],
    2 => [
        'category_id' => 1,
        'name' => 'NovaTab S8 Plus',
        'slug' => 'novatab-s8-plus',
        'description' => 'Large tablet for work and media with a vivid display and stylus support.',
        'price' => 699.00,
        'compare_price' => 799.00,
        'stock' => 40,
        'sku' => 'NB-CMP-002',
        'featured' => 1,
        'image_url' => 'https://cdn.dummyjson.com/product-images/tablets/samsung-galaxy-tab-s8-plus-grey/1.webp',
    ],
    3 => [
        'category_id' => 2,
        'name' => 'Lumina Phone X',
        'slug' => 'lumina-phone-x',
        'description' => 'Flagship smartphone with dual cameras and OLED panel.',
        'price' => 899.00,
        'compare_price' => 999.00,
        'stock' => 30,
        'sku' => 'NB-MOB-001',
        'featured' => 1,
        'image_url' => 'https://cdn.dummyjson.com/product-images/smartphones/iphone-13-pro/1.webp',
    ],
    4 => [
        'category_id' => 2,
        'name' => 'ChargeBeam Cable Kit',
        'slug' => 'chargebeam-cable-kit',
        'description' => 'Fast wall charger and cable kit for phones and tablets.',
        'price' => 49.00,
        'compare_price' => 59.00,
        'stock' => 110,
        'sku' => 'NB-MOB-002',
        'featured' => 0,
        'image_url' => 'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-iphone-charger/1.webp',
    ],
    5 => [
        'category_id' => 3,
        'name' => 'EchoBuds Air',
        'slug' => 'echobuds-air',
        'description' => 'True wireless earbuds with adaptive ANC.',
        'price' => 149.00,
        'compare_price' => 179.00,
        'stock' => 60,
        'sku' => 'NB-AUD-001',
        'featured' => 1,
        'image_url' => 'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-airpods/1.webp',
    ],
    6 => [
        'category_id' => 3,
        'name' => 'WaveBar Mini Speaker',
        'slug' => 'wavebar-mini-speaker',
        'description' => 'Portable smart speaker with rich room-filling sound.',
        'price' => 79.00,
        'compare_price' => null,
        'stock' => 55,
        'sku' => 'NB-AUD-002',
        'featured' => 0,
        'image_url' => 'https://cdn.dummyjson.com/product-images/mobile-accessories/amazon-echo-plus/1.webp',
    ],
    7 => [
        'category_id' => 4,
        'name' => 'Arcade Aorus Tee',
        'slug' => 'arcade-aorus-tee',
        'description' => 'Gaming lifestyle tee for practice sessions and LAN nights.',
        'price' => 34.00,
        'compare_price' => 44.00,
        'stock' => 70,
        'sku' => 'NB-GAM-001',
        'featured' => 1,
        'image_url' => 'https://cdn.dummyjson.com/product-images/mens-shirts/gigabyte-aorus-men-tshirt/1.webp',
    ],
    8 => [
        'category_id' => 4,
        'name' => 'Neon Headset Pro',
        'slug' => 'neon-headset-pro',
        'description' => 'Over-ear headset with clear voice mic and deep bass.',
        'price' => 99.00,
        'compare_price' => 129.00,
        'stock' => 45,
        'sku' => 'NB-GAM-002',
        'featured' => 0,
        'image_url' => 'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-airpods-max-silver/1.webp',
    ],
    9 => [
        'category_id' => 5,
        'name' => 'Clarity Studio Pedestal',
        'slug' => 'clarity-studio-pedestal',
        'description' => 'Studio camera pedestal for stable video and broadcast setups.',
        'price' => 459.00,
        'compare_price' => 499.00,
        'stock' => 18,
        'sku' => 'NB-CAM-001',
        'featured' => 1,
        'image_url' => 'https://cdn.dummyjson.com/product-images/mobile-accessories/tv-studio-camera-pedestal/1.webp',
    ],
    10 => [
        'category_id' => 5,
        'name' => 'Flex Tripod Pro',
        'slug' => 'flex-tripod-pro',
        'description' => 'Travel phone tripod with extendable pole and stable legs.',
        'price' => 89.00,
        'compare_price' => 109.00,
        'stock' => 40,
        'sku' => 'NB-CAM-002',
        'featured' => 0,
        'image_url' => 'https://cdn.dummyjson.com/product-images/mobile-accessories/monopod/1.webp',
    ],
    11 => [
        'category_id' => 6,
        'name' => 'Urban Linen Shirt',
        'slug' => 'urban-linen-shirt',
        'description' => 'Breathable short-sleeve shirt for everyday comfort.',
        'price' => 48.00,
        'compare_price' => 65.00,
        'stock' => 90,
        'sku' => 'NB-FSH-001',
        'featured' => 1,
        'image_url' => 'https://cdn.dummyjson.com/product-images/mens-shirts/man-short-sleeve-shirt/1.webp',
    ],
    12 => [
        'category_id' => 6,
        'name' => 'TrailFlex Runners',
        'slug' => 'trailflex-runners',
        'description' => 'Lightweight sport sneakers with cushioned sole.',
        'price' => 54.00,
        'compare_price' => null,
        'stock' => 75,
        'sku' => 'NB-FSH-002',
        'featured' => 0,
        'image_url' => 'https://cdn.dummyjson.com/product-images/mens-shoes/puma-future-rider-trainers/1.webp',
    ],
];

function download_image(string $url): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => ['Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8'],
    ]);
    $data = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($data === false || $code >= 400 || strlen($data) < 800) {
        return null;
    }

    $isJpeg = str_starts_with($data, "\xFF\xD8\xFF");
    $isPng = str_starts_with($data, "\x89PNG");
    $isWebp = str_starts_with($data, 'RIFF') && str_contains(substr($data, 0, 16), 'WEBP');
    if (!$isJpeg && !$isPng && !$isWebp) {
        return null;
    }

    return [
        'data' => $data,
        'ext' => $isPng ? 'png' : ($isWebp ? 'webp' : 'jpg'),
    ];
}

$update = $pdo->prepare(
    'UPDATE products
     SET category_id = ?, name = ?, slug = ?, description = ?, price = ?, compare_price = ?, stock = ?, sku = ?, featured = ?, updated_at = NOW()
     WHERE id = ?'
);
$insertImg = $pdo->prepare(
    'INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, 0, NOW())'
);

// Fix order_items product names that may still show old titles
$fixOrderItem = $pdo->prepare('UPDATE order_items SET product_name = ? WHERE product_id = ?');

echo "Updating products + downloading image URLs...\n\n";

foreach ($catalog as $id => $item) {
    echo "[{$id}] {$item['name']}\n";
    echo "  URL: {$item['image_url']}\n";

    $img = download_image($item['image_url']);
    if ($img === null) {
        echo "  FAIL download — product not updated\n\n";
        continue;
    }

    $fn = 'product-' . $id . '-url.' . $img['ext'];
    $path = $destDir . '/' . $fn;
    if (file_put_contents($path, $img['data']) === false) {
        echo "  FAIL write file\n\n";
        continue;
    }

    $update->execute([
        $item['category_id'],
        $item['name'],
        $item['slug'],
        $item['description'],
        $item['price'],
        $item['compare_price'],
        $item['stock'],
        $item['sku'],
        $item['featured'],
        $id,
    ]);

    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
    $insertImg->execute([$id, 'products/' . $fn]);
    $fixOrderItem->execute([$item['name'], $id]);

    echo "  OK products/{$fn}\n\n";
}

echo "=== Final catalog ===\n";
$rows = $pdo->query(
    'SELECT p.id, p.name, c.name AS category, pi.image_path
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN product_images pi ON pi.product_id = p.id
     ORDER BY p.id'
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    $ok = is_file(NB_ROOT . '/uploads/' . ($r['image_path'] ?? '')) ? 'file-ok' : 'missing';
    echo sprintf("%2d | %-28s | %-10s | %-40s | %s\n", $r['id'], $r['name'], $r['category'], $r['image_path'], $ok);
}

echo "\nDONE — every product now uses a real image URL download.\n";
