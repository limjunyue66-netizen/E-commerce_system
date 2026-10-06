<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

$fixes = [
    2 => [
        'label' => 'USB-C Hub',
        'urls' => [
            'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=900&h=900&q=80',
            'https://placehold.co/900x900/0F766E/ffffff/png?text=NovaDock+USB-C+Hub',
        ],
    ],
    7 => [
        'label' => 'Controller',
        'urls' => [
            'https://images.unsplash.com/photo-1612287230202-1ff1d85d1bdf?auto=format&fit=crop&w=900&h=900&q=80',
            'https://placehold.co/900x900/7C3AED/ffffff/png?text=ArcadePad+Elite',
        ],
    ],
    9 => [
        'label' => 'Camera',
        'urls' => [
            'https://images.unsplash.com/photo-1606983340126-99ab4feaa64a?auto=format&fit=crop&w=900&h=900&q=80',
            'https://placehold.co/900x900/0369A1/ffffff/png?text=Clarity+Cam+4K',
        ],
    ],
    10 => [
        'label' => 'Tripod',
        'urls' => [
            'https://placehold.co/900x900/0D9488/ffffff/png?text=Tripod+Flex+Pro',
            'https://images.unsplash.com/photo-1542567455-cd3f7f0f2fcd?auto=format&fit=crop&w=900&h=900&q=80',
        ],
    ],
    12 => [
        'label' => 'Joggers',
        'urls' => [
            'https://images.unsplash.com/photo-1624378439575-d8705ad7ae80?auto=format&fit=crop&w=900&h=900&q=80',
            'https://placehold.co/900x900/334155/ffffff/png?text=TrailFlex+Joggers',
        ],
    ],
];

function fetch_image(string $url): ?array
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

foreach ($fixes as $id => $meta) {
    echo "[{$id}] {$meta['label']} ... ";
    $ok = false;
    foreach ($meta['urls'] as $url) {
        $r = fetch_image($url);
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
echo "DONE\n";
