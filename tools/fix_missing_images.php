<?php
require dirname(__DIR__) . '/config/bootstrap.php';
$pdo = db();
$destDir = NB_ROOT . '/uploads/products';

$fixes = [
    2 => 'https://images.pexels.com/photos/163100/circuit-circuit-board-resistor-computer-163100.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
    // fallback usb cables
    // 2 => 'https://images.pexels.com/photos/4219862/pexels-photo-4219862.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
    7 => 'https://images.pexels.com/photos/3945683/pexels-photo-3945683.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
];

// Better dedicated URLs
$fixes = [
    2 => [
        'https://images.pexels.com/photos/4219862/pexels-photo-4219862.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        'https://images.pexels.com/photos/577769/pexels-photo-577769.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        'https://images.unsplash.com/photo-1625842265867-d1b3f0c0c0e6?auto=format&fit=crop&w=900&h=900&q=80',
        'https://images.unsplash.com/photo-1558618666-fcd25c85cd64?auto=format&fit=crop&w=900&h=900&q=80',
    ],
    7 => [
        'https://images.pexels.com/photos/3945683/pexels-photo-3945683.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        'https://images.pexels.com/photos/1298601/pexels-photo-1298601.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        'https://images.unsplash.com/photo-1606144042614-b2417e99c4e3?auto=format&fit=crop&w=900&h=900&q=80',
    ],
];

function dl(string $url, string $dest): bool {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $data = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$data || $code >= 400 || strlen($data) < 2000) return false;
    if (!str_starts_with($data, "\xFF\xD8\xFF") && !str_starts_with($data, "\x89PNG")) return false;
    return file_put_contents($dest, $data) !== false;
}

$insert = $pdo->prepare('INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, 0, NOW())');

foreach ($fixes as $id => $urls) {
    $ok = false;
    foreach ($urls as $url) {
        $fn = 'product-' . $id . '-' . bin2hex(random_bytes(4)) . '.jpg';
        $full = $destDir . '/' . $fn;
        echo "Try product {$id}: {$url}\n";
        if (dl($url, $full)) {
            $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
            $insert->execute([$id, 'products/' . $fn]);
            echo "OK {$fn}\n";
            $ok = true;
            break;
        }
        echo "fail\n";
    }
    if (!$ok) echo "ALL FAILED for {$id}\n";
}
