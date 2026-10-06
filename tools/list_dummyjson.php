<?php
declare(strict_types=1);

$ch = curl_init('https://dummyjson.com/products?limit=100');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 40,
    CURLOPT_USERAGENT => 'Mozilla/5.0',
]);
$json = curl_exec($ch);
curl_close($ch);
$data = json_decode((string) $json, true);
foreach (($data['products'] ?? []) as $p) {
    $imgs = $p['images'] ?? [];
    $first = $imgs[0] ?? ($p['thumbnail'] ?? '');
    echo $p['id'] . ' | ' . $p['category'] . ' | ' . $p['title'] . ' | ' . $first . "\n";
}
