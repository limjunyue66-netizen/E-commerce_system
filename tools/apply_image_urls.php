<?php
declare(strict_types=1);
/**
 * Apply curated matching image URLs to all products + verify category pairing.
 */
require dirname(__DIR__) . '/config/bootstrap.php';

$pdo = db();
$destDir = NB_ROOT . '/uploads/products';
if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

$expectedCategory = [
    1 => ['id' => 1, 'name' => 'Computers'],
    2 => ['id' => 1, 'name' => 'Computers'],
    3 => ['id' => 2, 'name' => 'Mobiles'],
    4 => ['id' => 2, 'name' => 'Mobiles'],
    5 => ['id' => 3, 'name' => 'Audio'],
    6 => ['id' => 3, 'name' => 'Audio'],
    7 => ['id' => 4, 'name' => 'Gaming'],
    8 => ['id' => 4, 'name' => 'Gaming'],
    9 => ['id' => 5, 'name' => 'Cameras'],
    10 => ['id' => 5, 'name' => 'Cameras'],
    11 => ['id' => 6, 'name' => 'Fashion'],
    12 => ['id' => 6, 'name' => 'Fashion'],
];

// Subject-matched image URLs (DummyJSON product CDN + Unsplash photo IDs)
$catalog = [
    1 => [
        'label' => 'Laptop',
        'urls' => [
            'https://cdn.dummyjson.com/product-images/laptops/apple-macbook-pro-14-inch-space-grey/1.webp',
            'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=900&h=900&q=80',
        ],
    ],
    2 => [
        'label' => 'USB hub / dock',
        'urls' => [
            'https://images.unsplash.com/photo-1625842268584-8f3296236761?auto=format&fit=crop&w=900&h=900&q=80',
            'https://images.unsplash.com/photo-1527864550417-7fd91fc51a46?auto=format&fit=crop&w=900&h=900&q=80',
            'https://placehold.co/900x900/0F766E/ffffff/png?text=USB-C+Hub',
        ],
    ],
    3 => [
        'label' => 'Smartphone',
        'urls' => [
            'https://cdn.dummyjson.com/product-images/smartphones/iphone-13-pro/1.webp',
            'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=900&h=900&q=80',
        ],
    ],
    4 => [
        'label' => 'Phone charger',
        'urls' => [
            'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-iphone-charger/1.webp',
            'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=900&h=900&q=80',
        ],
    ],
    5 => [
        'label' => 'Earbuds',
        'urls' => [
            'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-airpods/1.webp',
            'https://cdn.dummyjson.com/product-images/mobile-accessories/beats-flex-wireless-earphones/1.webp',
        ],
    ],
    6 => [
        'label' => 'Speaker',
        'urls' => [
            'https://cdn.dummyjson.com/product-images/mobile-accessories/amazon-echo-plus/1.webp',
            'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-homepod-mini-cosmic-grey/1.webp',
        ],
    ],
    7 => [
        'label' => 'Game controller',
        'urls' => [
            'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?auto=format&fit=crop&w=900&h=900&q=80',
            'https://images.unsplash.com/photo-1592840496694-26d035b52b48?auto=format&fit=crop&w=900&h=900&q=80',
            'https://placehold.co/900x900/7C3AED/ffffff/png?text=Game+Controller',
        ],
    ],
    8 => [
        'label' => 'Gaming headset',
        'urls' => [
            'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-airpods-max-silver/1.webp',
            'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?auto=format&fit=crop&w=900&h=900&q=80',
        ],
    ],
    9 => [
        'label' => 'Camera',
        'urls' => [
            'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=900&h=900&q=80',
            'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?auto=format&fit=crop&w=900&h=900&q=80',
            'https://placehold.co/900x900/0369A1/ffffff/png?text=4K+Camera',
        ],
    ],
    10 => [
        'label' => 'Tripod',
        'urls' => [
            'https://images.unsplash.com/photo-1471341971237-484825f54c5e?auto=format&fit=crop&w=900&h=900&q=80',
            'https://cdn.dummyjson.com/product-images/mobile-accessories/monopod/1.webp',
            'https://placehold.co/900x900/0D9488/ffffff/png?text=Tripod+Flex+Pro',
        ],
    ],
    11 => [
        'label' => 'Shirt',
        'urls' => [
            'https://cdn.dummyjson.com/product-images/mens-shirts/man-short-sleeve-shirt/1.webp',
            'https://cdn.dummyjson.com/product-images/mens-shirts/man-plaid-shirt/1.webp',
        ],
    ],
    12 => [
        'label' => 'Joggers / pants',
        'urls' => [
            'https://images.unsplash.com/photo-1552902865-b72c031ac5ea?auto=format&fit=crop&w=900&h=900&q=80',
            'https://images.unsplash.com/photo-1506629082955-711b1d8d8ea0?auto=format&fit=crop&w=900&h=900&q=80',
            'https://placehold.co/900x900/334155/ffffff/png?text=TrailFlex+Joggers',
        ],
    ],
];

function download_bytes(string $url): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 50,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => ['Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8'],
    ]);
    $data = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($data === false || $code >= 400 || strlen($data) < 800) {
        return ['ok' => false, 'reason' => "http={$code} err={$err} len=" . strlen((string) $data)];
    }

    $isJpeg = str_starts_with($data, "\xFF\xD8\xFF");
    $isPng = str_starts_with($data, "\x89PNG");
    $isWebp = str_starts_with($data, 'RIFF') && str_contains(substr($data, 0, 16), 'WEBP');
    if (!$isJpeg && !$isPng && !$isWebp) {
        return ['ok' => false, 'reason' => 'not an image binary'];
    }

    $ext = $isPng ? 'png' : ($isWebp ? 'webp' : 'jpg');
    return ['ok' => true, 'data' => $data, 'ext' => $ext];
}

echo "=== Product ↔ Category check ===\n";
$products = $pdo->query(
    'SELECT p.id, p.name, p.category_id, c.name AS category
     FROM products p LEFT JOIN categories c ON c.id = p.category_id ORDER BY p.id'
)->fetchAll(PDO::FETCH_ASSOC);

$catMismatch = 0;
foreach ($products as $p) {
    $id = (int) $p['id'];
    $want = $expectedCategory[$id] ?? null;
    $ok = $want && (int) $p['category_id'] === $want['id'];
    if (!$ok) {
        $catMismatch++;
    }
    echo sprintf(
        "[%2d] %-22s → %-10s %s\n",
        $id,
        $p['name'],
        $p['category'] ?? 'NONE',
        $ok ? 'MATCH' : 'MISMATCH (want ' . ($want['name'] ?? '?') . ')'
    );
}
echo $catMismatch === 0 ? "All categories match.\n\n" : "Category mismatches: {$catMismatch}\n\n";

echo "=== Download matching image URLs ===\n";
$insert = $pdo->prepare('INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, 0, NOW())');
$okCount = 0;

foreach ($catalog as $id => $meta) {
    $name = '';
    foreach ($products as $p) {
        if ((int) $p['id'] === $id) {
            $name = $p['name'];
            break;
        }
    }
    echo "[{$id}] {$name} ({$meta['label']})\n";
    $saved = false;
    foreach ($meta['urls'] as $url) {
        $result = download_bytes($url);
        if (!$result['ok']) {
            echo "  - skip: {$result['reason']} | {$url}\n";
            continue;
        }
        $fn = 'product-' . $id . '-match.' . $result['ext'];
        if (file_put_contents($destDir . '/' . $fn, $result['data']) === false) {
            echo "  - skip: write failed\n";
            continue;
        }
        $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
        $insert->execute([$id, 'products/' . $fn]);
        echo "  OK products/{$fn}\n";
        $saved = true;
        $okCount++;
        break;
    }
    if (!$saved) {
        echo "  FAIL — kept previous image\n";
    }
    usleep(250000);
}

echo "\nImages updated: {$okCount}/" . count($catalog) . "\n";

echo "\n=== Final mapping ===\n";
$rows = $pdo->query(
    'SELECT p.id, p.name, c.name AS category, pi.image_path
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN product_images pi ON pi.product_id = p.id
     ORDER BY p.id'
)->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $r) {
    $exists = is_file(NB_ROOT . '/uploads/' . ($r['image_path'] ?? '')) ? 'file-ok' : 'missing';
    echo sprintf("%2d | %-22s | %-10s | %-35s | %s\n", $r['id'], $r['name'], $r['category'], $r['image_path'], $exists);
}
echo "DONE\n";
