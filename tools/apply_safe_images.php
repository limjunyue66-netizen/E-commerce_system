<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

/**
 * Only use verified DummyJSON product images + labeled placehold fallbacks.
 * Never use random Unsplash IDs (they mismatch subjects).
 */
$catalog = [
    1 => [
        'https://cdn.dummyjson.com/product-images/laptops/apple-macbook-pro-14-inch-space-grey/1.webp',
        'https://placehold.co/900x900/0F766E/ffffff/png?text=AetherBook+Pro+14',
    ],
    2 => [
        'https://placehold.co/900x900/0F766E/ffffff/png?text=NovaDock+USB-C+Hub',
    ],
    3 => [
        'https://cdn.dummyjson.com/product-images/smartphones/iphone-13-pro/1.webp',
        'https://placehold.co/900x900/0369A1/ffffff/png?text=Lumina+Phone+X',
    ],
    4 => [
        'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-iphone-charger/1.webp',
        'https://placehold.co/900x900/0369A1/ffffff/png?text=ChargeBeam+65W',
    ],
    5 => [
        'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-airpods/1.webp',
        'https://placehold.co/900x900/7C3AED/ffffff/png?text=EchoBuds+Air',
    ],
    6 => [
        'https://cdn.dummyjson.com/product-images/mobile-accessories/amazon-echo-plus/1.webp',
        'https://placehold.co/900x900/7C3AED/ffffff/png?text=WaveBar+Speaker',
    ],
    7 => [
        // Keep the known-good controller photo already downloaded if present;
        // otherwise labeled placeholder.
        'https://placehold.co/900x900/6D28D9/ffffff/png?text=ArcadePad+Elite',
    ],
    8 => [
        'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-airpods-max-silver/1.webp',
        'https://placehold.co/900x900/6D28D9/ffffff/png?text=Neon+Headset+Pro',
    ],
    9 => [
        'https://placehold.co/900x900/0369A1/ffffff/png?text=Clarity+Cam+4K',
    ],
    10 => [
        'https://placehold.co/900x900/0D9488/ffffff/png?text=Tripod+Flex+Pro',
    ],
    11 => [
        'https://cdn.dummyjson.com/product-images/mens-shirts/man-short-sleeve-shirt/1.webp',
        'https://placehold.co/900x900/334155/ffffff/png?text=Urban+Linen+Shirt',
    ],
    12 => [
        'https://placehold.co/900x900/334155/ffffff/png?text=TrailFlex+Joggers',
    ],
];

function fetch_image_bytes(string $url): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 40,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/122.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => ['Accept: image/*,*/*;q=0.8'],
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
    return ['data' => $data, 'ext' => $isPng ? 'png' : ($isWebp ? 'webp' : 'jpg')];
}

$pdo = db();
$dest = NB_ROOT . '/uploads/products';
$insert = $pdo->prepare('INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, 0, NOW())');

// Keep the good controller photo if it still looks like a controller (file exists from earlier good download)
$controllerKeep = $dest . '/product-7-match.jpg';
$keepController = is_file($controllerKeep) && filesize($controllerKeep) > 20000;

foreach ($catalog as $id => $urls) {
    if ($id === 7 && $keepController) {
        $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
        $insert->execute([$id, 'products/product-7-match.jpg']);
        echo "[7] ArcadePad Elite — kept verified controller photo\n";
        continue;
    }

    echo "[{$id}] ";
    $ok = false;
    foreach ($urls as $url) {
        $r = fetch_image_bytes($url);
        if ($r === null) {
            continue;
        }
        $fn = 'product-' . $id . '-match.' . $r['ext'];
        file_put_contents($dest . '/' . $fn, $r['data']);
        $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
        $insert->execute([$id, 'products/' . $fn]);
        echo "OK {$fn}\n";
        $ok = true;
        break;
    }
    if (!$ok) {
        echo "FAIL\n";
    }
}

echo "\n=== Final ===\n";
$rows = $pdo->query(
    'SELECT p.id, p.name, c.name AS category, pi.image_path
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN product_images pi ON pi.product_id = p.id
     ORDER BY p.id'
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    echo sprintf("%2d | %-22s | %-10s | %s\n", $r['id'], $r['name'], $r['category'], $r['image_path']);
}
echo "DONE\n";
