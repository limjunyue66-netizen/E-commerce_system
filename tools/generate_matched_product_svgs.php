<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

/**
 * Create product-matched SVG images (name + icon) so every photo clearly matches the listing.
 * Users can later replace these via Admin upload / image URL.
 */
$pdo = db();
$destDir = NB_ROOT . '/uploads/products';

$styles = [
    1 => ['#0F766E', '#134E4A', 'laptop', 'Laptop'],
    2 => ['#0369A1', '#0C4A6E', 'hub', 'USB-C Hub'],
    3 => ['#7C3AED', '#4C1D95', 'phone', 'Smartphone'],
    4 => ['#EA580C', '#9A3412', 'charger', 'GaN Charger'],
    5 => ['#0891B2', '#155E75', 'earbuds', 'Earbuds'],
    6 => ['#CA8A04', '#854D0E', 'speaker', 'Speaker'],
    7 => ['#DC2626', '#7F1D1D', 'controller', 'Controller'],
    8 => ['#4F46E5', '#312E81', 'headset', 'Headset'],
    9 => ['#57534E', '#292524', 'camera', '4K Camera'],
    10 => ['#0D9488', '#115E59', 'tripod', 'Tripod'],
    11 => ['#2563EB', '#1E3A8A', 'shirt', 'Linen Shirt'],
    12 => ['#64748B', '#334155', 'joggers', 'Joggers'],
];

function icon_paths(string $key): string
{
    return match ($key) {
        'laptop' => '<rect x="210" y="250" width="380" height="240" rx="18" fill="none" stroke="white" stroke-width="18"/><rect x="250" y="290" width="300" height="160" rx="8" fill="rgba(255,255,255,0.2)"/><rect x="180" y="500" width="440" height="28" rx="10" fill="white"/>',
        'hub' => '<rect x="250" y="300" width="300" height="160" rx="24" fill="rgba(255,255,255,0.2)" stroke="white" stroke-width="14"/><circle cx="300" cy="380" r="18" fill="white"/><circle cx="360" cy="380" r="18" fill="white"/><circle cx="420" cy="380" r="18" fill="white"/><circle cx="480" cy="380" r="18" fill="white"/>',
        'phone' => '<rect x="290" y="180" width="220" height="400" rx="28" fill="none" stroke="white" stroke-width="16"/><rect x="320" y="230" width="160" height="280" rx="8" fill="rgba(255,255,255,0.18)"/><circle cx="400" cy="545" r="16" fill="white"/>',
        'charger' => '<rect x="300" y="250" width="200" height="220" rx="28" fill="rgba(255,255,255,0.18)" stroke="white" stroke-width="14"/><rect x="350" y="200" width="100" height="55" rx="10" fill="white"/><path d="M400 470 V560" stroke="white" stroke-width="14" stroke-linecap="round"/>',
        'earbuds' => '<circle cx="320" cy="320" r="70" fill="none" stroke="white" stroke-width="16"/><circle cx="480" cy="320" r="70" fill="none" stroke="white" stroke-width="16"/><path d="M320 390 Q400 520 480 390" fill="none" stroke="white" stroke-width="14"/>',
        'speaker' => '<rect x="270" y="220" width="260" height="320" rx="40" fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="14"/><circle cx="400" cy="360" r="70" fill="none" stroke="white" stroke-width="14"/><circle cx="400" cy="360" r="28" fill="white"/>',
        'controller' => '<rect x="200" y="300" width="400" height="180" rx="70" fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="14"/><circle cx="300" cy="390" r="34" fill="white"/><circle cx="500" cy="390" r="18" fill="white"/><circle cx="545" cy="350" r="18" fill="white"/><circle cx="545" cy="430" r="18" fill="white"/><circle cx="455" cy="350" r="18" fill="white"/>',
        'headset' => '<path d="M220 380 Q220 220 400 220 Q580 220 580 380" fill="none" stroke="white" stroke-width="22" stroke-linecap="round"/><rect x="180" y="360" width="90" height="140" rx="28" fill="white"/><rect x="530" y="360" width="90" height="140" rx="28" fill="white"/>',
        'camera' => '<rect x="200" y="280" width="400" height="240" rx="36" fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="14"/><circle cx="400" cy="400" r="78" fill="none" stroke="white" stroke-width="16"/><circle cx="400" cy="400" r="36" fill="white"/><rect x="250" y="250" width="90" height="40" rx="10" fill="white"/>',
        'tripod' => '<circle cx="400" cy="220" r="55" fill="rgba(255,255,255,0.2)" stroke="white" stroke-width="12"/><path d="M400 275 V430" stroke="white" stroke-width="16" stroke-linecap="round"/><path d="M400 430 L260 560" stroke="white" stroke-width="16" stroke-linecap="round"/><path d="M400 430 L540 560" stroke="white" stroke-width="16" stroke-linecap="round"/><path d="M400 430 L400 580" stroke="white" stroke-width="16" stroke-linecap="round"/>',
        'shirt' => '<path d="M260 260 L340 220 L400 270 L460 220 L540 260 L500 340 L500 560 L300 560 L300 340 Z" fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="14" stroke-linejoin="round"/>',
        'joggers' => '<path d="M300 220 H500 L520 300 L560 580 H440 L400 360 L360 580 H240 L280 300 Z" fill="rgba(255,255,255,0.15)" stroke="white" stroke-width="14" stroke-linejoin="round"/>',
        default => '',
    };
}

foreach (glob($destDir . '/product-*.{jpg,png,webp,svg}', GLOB_BRACE) ?: [] as $old) {
    @unlink($old);
}

$insert = $pdo->prepare('INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, 0, NOW())');
$get = $pdo->prepare('SELECT id, name FROM products WHERE id = ?');

foreach ($styles as $id => [$c1, $c2, $icon, $label]) {
    $get->execute([$id]);
    $row = $get->fetch();
    if (!$row) {
        continue;
    }
    $title = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
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

    $filename = 'product-' . $id . '-match.svg';
    file_put_contents($destDir . '/' . $filename, $svg);
    $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
    $insert->execute([$id, 'products/' . $filename]);
    echo "[{$id}] {$row['name']} -> {$filename}\n";
}

echo "DONE - every product image now shows matching product name + icon.\n";
