<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

/**
 * Hybrid catalog images:
 * - Real photos from DummyJSON CDN (verified product subjects)
 * - Matched SVG icons for products with no reliable photo URL
 */
$pdo = db();
$destDir = NB_ROOT . '/uploads/products';

$photoUrls = [
    1 => 'https://cdn.dummyjson.com/product-images/laptops/apple-macbook-pro-14-inch-space-grey/1.webp',
    3 => 'https://cdn.dummyjson.com/product-images/smartphones/iphone-13-pro/1.webp',
    4 => 'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-iphone-charger/1.webp',
    5 => 'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-airpods/1.webp',
    6 => 'https://cdn.dummyjson.com/product-images/mobile-accessories/amazon-echo-plus/1.webp',
    8 => 'https://cdn.dummyjson.com/product-images/mobile-accessories/apple-airpods-max-silver/1.webp',
    11 => 'https://cdn.dummyjson.com/product-images/mens-shirts/man-short-sleeve-shirt/1.webp',
];

$svgStyles = [
    2 => ['#0369A1', '#0C4A6E', 'hub', 'USB-C Hub'],
    7 => ['#DC2626', '#7F1D1D', 'controller', 'Controller'],
    9 => ['#57534E', '#292524', 'camera', '4K Camera'],
    10 => ['#0D9488', '#115E59', 'tripod', 'Tripod'],
    12 => ['#64748B', '#334155', 'joggers', 'Joggers'],
];

function icon_paths(string $key): string
{
    return match ($key) {
        'hub' => '<rect x="250" y="300" width="300" height="160" rx="24" fill="rgba(255,255,255,0.2)" stroke="white" stroke-width="14"/><circle cx="300" cy="380" r="18" fill="white"/><circle cx="360" cy="380" r="18" fill="white"/><circle cx="420" cy="380" r="18" fill="white"/><circle cx="480" cy="380" r="18" fill="white"/>',
        'controller' => '<rect x="200" y="300" width="400" height="180" rx="70" fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="14"/><circle cx="300" cy="390" r="34" fill="white"/><circle cx="500" cy="390" r="18" fill="white"/><circle cx="545" cy="350" r="18" fill="white"/><circle cx="545" cy="430" r="18" fill="white"/><circle cx="455" cy="350" r="18" fill="white"/>',
        'camera' => '<rect x="200" y="280" width="400" height="240" rx="36" fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="14"/><circle cx="400" cy="400" r="78" fill="none" stroke="white" stroke-width="16"/><circle cx="400" cy="400" r="36" fill="white"/><rect x="250" y="250" width="90" height="40" rx="10" fill="white"/>',
        'tripod' => '<circle cx="400" cy="220" r="55" fill="rgba(255,255,255,0.2)" stroke="white" stroke-width="12"/><path d="M400 275 V430" stroke="white" stroke-width="16" stroke-linecap="round"/><path d="M400 430 L260 560" stroke="white" stroke-width="16" stroke-linecap="round"/><path d="M400 430 L540 560" stroke="white" stroke-width="16" stroke-linecap="round"/><path d="M400 430 L400 580" stroke="white" stroke-width="16" stroke-linecap="round"/>',
        'joggers' => '<path d="M300 220 H500 L520 300 L560 580 H440 L400 360 L360 580 H240 L280 300 Z" fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="14" stroke-linejoin="round"/>',
        default => '',
    };
}

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

$insert = $pdo->prepare('INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, 0, NOW())');
$nameStmt = $pdo->prepare('SELECT name FROM products WHERE id = ?');

echo "=== Real photo URLs ===\n";
foreach ($photoUrls as $id => $url) {
    $nameStmt->execute([$id]);
    $name = (string) $nameStmt->fetchColumn();
    echo "[{$id}] {$name} ... ";
    $img = fetch_image($url);
    if ($img === null) {
        echo "FAIL\n";
        continue;
    }
    $fn = 'product-' . $id . '-match.' . $img['ext'];
    file_put_contents($destDir . '/' . $fn, $img['data']);
    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
    $insert->execute([$id, 'products/' . $fn]);
    echo "OK from URL\n";
}

// Keep controller photo if already good
$controller = $destDir . '/product-7-match.jpg';
if (is_file($controller) && filesize($controller) > 20000) {
    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([7]);
    $insert->execute([7, 'products/product-7-match.jpg']);
    echo "[7] ArcadePad Elite ... OK kept photo\n";
    unset($svgStyles[7]);
}

echo "\n=== Matched SVG icons (no reliable photo URL) ===\n";
foreach ($svgStyles as $id => [$c1, $c2, $icon, $label]) {
    $nameStmt->execute([$id]);
    $name = (string) $nameStmt->fetchColumn();
    $title = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $labelSafe = htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    $paths = icon_paths($icon);
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="900" height="900" viewBox="0 0 800 800">
  <defs>
    <linearGradient id="g{$id}" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$c1}"/>
      <stop offset="100%" stop-color="{$c2}"/>
    </linearGradient>
  </defs>
  <rect width="800" height="800" fill="url(#g{$id})"/>
  <circle cx="650" cy="120" r="140" fill="rgba(255,255,255,0.08)"/>
  <circle cx="120" cy="700" r="160" fill="rgba(0,0,0,0.12)"/>
  {$paths}
  <text x="400" y="660" text-anchor="middle" fill="white" font-family="Segoe UI, Arial, sans-serif" font-size="36" font-weight="700">{$title}</text>
  <text x="400" y="705" text-anchor="middle" fill="rgba(255,255,255,0.85)" font-family="Segoe UI, Arial, sans-serif" font-size="22">{$labelSafe}</text>
</svg>
SVG;
    $fn = 'product-' . $id . '-match.svg';
    file_put_contents($destDir . '/' . $fn, $svg);
    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
    $insert->execute([$id, 'products/' . $fn]);
    echo "[{$id}] {$name} -> {$fn}\n";
}

echo "\nDONE\n";
echo "Tip: In Admin → Products → Edit, paste any https image URL into 'Image URL' to replace these.\n";
