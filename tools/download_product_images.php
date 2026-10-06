<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

/**
 * Download real product photos from Unsplash Source (no API key) into uploads/products.
 * Then update product_images paths.
 */
$pdo = db();
$destDir = NB_ROOT . '/uploads/products';
if (!is_dir($destDir)) {
    mkdir($destDir, 0755, true);
}

// Curated Unsplash image IDs / direct CDN URLs (royalty-free photos)
$images = [
    1 => 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80', // laptop
    2 => 'https://images.unsplash.com/photo-1625948515291-69613efd103f?auto=format&fit=crop&w=800&q=80', // usb hub/tech
    3 => 'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80', // phone
    4 => 'https://images.unsplash.com/photo-1583863788434-e58a36330cf0?auto=format&fit=crop&w=800&q=80', // charger
    5 => 'https://images.unsplash.com/photo-1590658268037-6bf12165a8df?auto=format&fit=crop&w=800&q=80', // earbuds
    6 => 'https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?auto=format&fit=crop&w=800&q=80', // speaker
    7 => 'https://images.unsplash.com/photo-1592840496694-26d035b52b48?auto=format&fit=crop&w=800&q=80', // controller
    8 => 'https://images.unsplash.com/photo-1618366712010-f4ae9c647dcb?auto=format&fit=crop&w=800&q=80', // headset
    9 => 'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80', // camera
    10 => 'https://images.unsplash.com/photo-1606983340126-99ab4feaa64a?auto=format&fit=crop&w=800&q=80', // tripod
    11 => 'https://images.unsplash.com/photo-1596755094514-f87e34085b2c?auto=format&fit=crop&w=800&q=80', // shirt
    12 => 'https://images.unsplash.com/photo-1552902865-b72c031ac853?auto=format&fit=crop&w=800&q=80', // joggers/pants
];

function download_image(string $url, string $destPath): bool
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 40,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'NovaBazaarImageFetcher/1.0',
    ]);
    $data = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ctype = (string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
    curl_close($ch);

    if ($data === false || $code >= 400 || strlen($data) < 1000) {
        return false;
    }
    if (!str_contains($ctype, 'image') && !str_starts_with($data, "\xFF\xD8") && !str_starts_with($data, "\x89PNG")) {
        // still try if binary looks like jpeg/png
        if (!(str_starts_with($data, "\xFF\xD8") || str_starts_with($data, "\x89PNG") || str_starts_with($data, 'RIFF'))) {
            return false;
        }
    }
    return file_put_contents($destPath, $data) !== false;
}

$upd = $pdo->prepare('UPDATE product_images SET image_path = ? WHERE product_id = ?');
$ensure = $pdo->prepare('SELECT id FROM product_images WHERE product_id = ? LIMIT 1');
$insert = $pdo->prepare('INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, 0, NOW())');

foreach ($images as $productId => $url) {
    $exists = $pdo->prepare('SELECT id FROM products WHERE id = ?');
    $exists->execute([$productId]);
    if (!$exists->fetch()) {
        echo "Skip missing product {$productId}\n";
        continue;
    }

    $filename = 'product-' . $productId . '-' . bin2hex(random_bytes(4)) . '.jpg';
    $relative = 'products/' . $filename;
    $full = $destDir . '/' . $filename;

    echo "Downloading product {$productId}... ";
    if (!download_image($url, $full)) {
        echo "FAIL\n";
        continue;
    }
    echo "OK ({$filename})\n";

    $ensure->execute([$productId]);
    if ($ensure->fetch()) {
        // replace all images for product with the new one as primary
        $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$productId]);
    }
    $insert->execute([$productId, $relative]);
}

echo "Done. Open shop to see real photos.\n";
