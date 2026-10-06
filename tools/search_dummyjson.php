<?php
declare(strict_types=1);

$queries = ['smartphone', 'laptop', 'headphones', 'tablet', 'watch', 'shirt', 'shoes', 'camera', 'speaker', 'charger', 'earbuds', 'gaming'];
foreach ($queries as $q) {
    $ch = curl_init('https://dummyjson.com/products/search?q=' . urlencode($q));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 40,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $json = curl_exec($ch);
    curl_close($ch);
    $data = json_decode((string) $json, true);
    echo "\n## {$q}\n";
    foreach (array_slice($data['products'] ?? [], 0, 5) as $p) {
        $img = ($p['images'][0] ?? $p['thumbnail'] ?? '');
        echo $p['id'] . ' | ' . $p['category'] . ' | ' . $p['title'] . ' | ' . $img . "\n";
    }
}
