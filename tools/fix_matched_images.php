<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

$pdo = db();
$destDir = NB_ROOT . '/uploads/products';

// Keyword-locked Flickr proxies + Wikimedia fallbacks for reliable subject match
$catalog = [
    1 => [
        'name' => 'Laptop',
        'urls' => [
            'https://loremflickr.com/900/900/laptop,macbook/all',
            'https://images.pexels.com/photos/18105/pexels-photo.jpg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        ],
    ],
    2 => [
        'name' => 'USB hub cables',
        'urls' => [
            'https://loremflickr.com/900/900/usb,cable,dongle/all',
            'https://images.pexels.com/photos/163117/keyboard-computer-hardware-technology-163117.jpeg?auto=compress&cs=tinysrgb&w=900',
            'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5a/USB_3.0_hub.jpg/800px-USB_3.0_hub.jpg',
        ],
    ],
    3 => [
        'name' => 'Smartphone',
        'urls' => [
            'https://loremflickr.com/900/900/smartphone,iphone/all',
            'https://images.pexels.com/photos/788946/pexels-photo-788946.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        ],
    ],
    4 => [
        'name' => 'Charger',
        'urls' => [
            'https://loremflickr.com/900/900/phone,charger,adapter/all',
            'https://images.pexels.com/photos/4219861/pexels-photo-4219861.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        ],
    ],
    5 => [
        'name' => 'Earbuds',
        'urls' => [
            'https://loremflickr.com/900/900/earbuds,airpods/all',
            'https://images.pexels.com/photos/3780681/pexels-photo-3780681.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        ],
    ],
    6 => [
        'name' => 'Speaker',
        'urls' => [
            'https://loremflickr.com/900/900/bluetooth,speaker/all',
            'https://images.pexels.com/photos/1279107/pexels-photo-1279107.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        ],
    ],
    7 => [
        'name' => 'Game controller',
        'urls' => [
            'https://loremflickr.com/900/900/gamepad,controller,xbox/all',
            'https://images.pexels.com/photos/3945683/pexels-photo-3945683.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
            'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2b/Xbox_Wireless_Controller.png/800px-Xbox_Wireless_Controller.png',
        ],
    ],
    8 => [
        'name' => 'Headset',
        'urls' => [
            'https://loremflickr.com/900/900/gaming,headset,headphones/all',
            'https://images.pexels.com/photos/3394650/pexels-photo-3394650.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        ],
    ],
    9 => [
        'name' => 'Camera',
        'urls' => [
            'https://loremflickr.com/900/900/dslr,camera/all',
            'https://images.pexels.com/photos/90946/pexels-photo-90946.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        ],
    ],
    10 => [
        'name' => 'Tripod',
        'urls' => [
            'https://loremflickr.com/900/900/camera,tripod/all',
            'https://upload.wikimedia.org/wikipedia/commons/thumb/1/1b/Tripod.jpg/800px-Tripod.jpg',
            'https://upload.wikimedia.org/wikipedia/commons/thumb/9/9a/Manfrotto_tripod.jpg/640px-Manfrotto_tripod.jpg',
            'https://images.pexels.com/photos/51383/photo-camera-subject-photographer-51383.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        ],
    ],
    11 => [
        'name' => 'Shirt',
        'urls' => [
            'https://loremflickr.com/900/900/linen,shirt,mens/all',
            'https://images.pexels.com/photos/297933/pexels-photo-297933.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        ],
    ],
    12 => [
        'name' => 'Joggers',
        'urls' => [
            'https://loremflickr.com/900/900/joggers,sweatpants/all',
            'https://images.pexels.com/photos/6311387/pexels-photo-6311387.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
            'https://images.pexels.com/photos/7671166/pexels-photo-7671166.jpeg?auto=compress&cs=tinysrgb&w=900&h=900&fit=crop',
        ],
    ],
];

function download_bytes(string $url): ?string
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 45,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        CURLOPT_HTTPHEADER => ['Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8'],
    ]);
    $data = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($data === false || $code >= 400 || strlen($data) < 1500) {
        return null;
    }
    // Accept jpeg/png/webp
    if (!(
        str_starts_with($data, "\xFF\xD8\xFF") ||
        str_starts_with($data, "\x89PNG") ||
        (str_starts_with($data, 'RIFF') && str_contains(substr($data, 0, 20), 'WEBP'))
    )) {
        return null;
    }
    return $data;
}

foreach (glob($destDir . '/product-*.{jpg,png,webp}', GLOB_BRACE) ?: [] as $old) {
    @unlink($old);
}

$insert = $pdo->prepare('INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, 0, NOW())');
$pname = $pdo->prepare('SELECT name FROM products WHERE id = ?');

foreach ($catalog as $id => $meta) {
    $pname->execute([$id]);
    $productName = (string) $pname->fetchColumn();
    echo "[{$id}] {$productName} ({$meta['name']}) ... ";
    $saved = false;
    foreach ($meta['urls'] as $url) {
        $data = download_bytes($url);
        if ($data === null) {
            continue;
        }
        $ext = str_starts_with($data, "\x89PNG") ? 'png' : (str_starts_with($data, 'RIFF') ? 'webp' : 'jpg');
        $fn = 'product-' . $id . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (file_put_contents($destDir . '/' . $fn, $data) === false) {
            continue;
        }
        $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
        $insert->execute([$id, 'products/' . $fn]);
        echo "OK {$fn}\n";
        $saved = true;
        break;
    }
    if (!$saved) {
        echo "FAIL\n";
    }
}
echo "DONE\n";
