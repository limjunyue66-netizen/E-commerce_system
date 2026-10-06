<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$colors = ['#0F766E', '#0369A1', '#B45309', '#BE123C', '#7C3AED', '#15803D', '#C2410C', '#1D4ED8'];

for ($i = 1; $i <= 30; $i++) {
    $c = $colors[($i - 1) % count($colors)];
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$c}"/>
      <stop offset="100%" stop-color="#0B1220"/>
    </linearGradient>
  </defs>
  <rect width="800" height="800" fill="url(#g)"/>
  <circle cx="640" cy="160" r="120" fill="rgba(255,255,255,0.08)"/>
  <text x="50%" y="48%" text-anchor="middle" fill="white" font-family="Segoe UI, Arial" font-size="42" font-weight="700">NovaBazaar</text>
  <text x="50%" y="56%" text-anchor="middle" fill="rgba(255,255,255,0.85)" font-family="Segoe UI, Arial" font-size="28">Product {$i}</text>
</svg>
SVG;
    file_put_contents($root . "/uploads/products/placeholder-{$i}.svg", $svg);
}

file_put_contents($root . '/assets/images/logo.svg', <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120">
  <rect rx="28" width="120" height="120" fill="#0F766E"/>
  <path d="M30 78 L48 34 L66 78 Z" fill="#F8FAFC"/>
  <circle cx="82" cy="70" r="18" fill="#FBBF24"/>
</svg>
SVG);

file_put_contents($root . '/assets/images/placeholder-product.svg', <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">
  <rect width="800" height="800" fill="#E2E8F0"/>
  <text x="50%" y="50%" text-anchor="middle" fill="#64748B" font-family="Segoe UI" font-size="36">No Image</text>
</svg>
SVG);

file_put_contents($root . '/assets/images/hero-bg.svg', <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="900" viewBox="0 0 1600 900">
  <defs>
    <linearGradient id="h" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#0F766E"/>
      <stop offset="55%" stop-color="#115E59"/>
      <stop offset="100%" stop-color="#134E4A"/>
    </linearGradient>
  </defs>
  <rect width="1600" height="900" fill="url(#h)"/>
  <circle cx="1300" cy="180" r="220" fill="rgba(251,191,36,0.18)"/>
  <circle cx="200" cy="700" r="260" fill="rgba(255,255,255,0.06)"/>
</svg>
SVG);

echo "Placeholders generated\n";
