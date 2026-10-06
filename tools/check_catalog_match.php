<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

$pdo = db();

$expected = [
    1 => ['cat' => 1, 'cat_name' => 'Computers', 'keywords' => ['laptop', 'notebook', 'computer', 'aetherbook', 'book']],
    2 => ['cat' => 1, 'cat_name' => 'Computers', 'keywords' => ['hub', 'dock', 'usb', 'dongle']],
    3 => ['cat' => 2, 'cat_name' => 'Mobiles', 'keywords' => ['phone', 'smartphone', 'mobile']],
    4 => ['cat' => 2, 'cat_name' => 'Mobiles', 'keywords' => ['charger', 'gan', 'charge']],
    5 => ['cat' => 3, 'cat_name' => 'Audio', 'keywords' => ['earbud', 'buds', 'earphone']],
    6 => ['cat' => 3, 'cat_name' => 'Audio', 'keywords' => ['speaker', 'bluetooth']],
    7 => ['cat' => 4, 'cat_name' => 'Gaming', 'keywords' => ['pad', 'controller', 'game']],
    8 => ['cat' => 4, 'cat_name' => 'Gaming', 'keywords' => ['headset', 'headphone']],
    9 => ['cat' => 5, 'cat_name' => 'Cameras', 'keywords' => ['cam', 'camera']],
    10 => ['cat' => 5, 'cat_name' => 'Cameras', 'keywords' => ['tripod']],
    11 => ['cat' => 6, 'cat_name' => 'Fashion', 'keywords' => ['shirt', 'linen']],
    12 => ['cat' => 6, 'cat_name' => 'Fashion', 'keywords' => ['jogger', 'pant', 'trail']],
];

$rows = $pdo->query(
    'SELECT p.id, p.name, p.category_id, c.name AS category, pi.image_path
     FROM products p
     LEFT JOIN categories c ON c.id = p.category_id
     LEFT JOIN product_images pi ON pi.product_id = p.id
     ORDER BY p.id'
)->fetchAll(PDO::FETCH_ASSOC);

$mismatch = 0;
echo "ID | Product | Category | Image | Status\n";
echo str_repeat('-', 100) . "\n";

foreach ($rows as $row) {
    $id = (int) $row['id'];
    $exp = $expected[$id] ?? null;
    $okCat = $exp && (int) $row['category_id'] === $exp['cat'];
    $nameLower = strtolower((string) $row['name']);
    $okName = false;
    if ($exp) {
        foreach ($exp['keywords'] as $kw) {
            if (str_contains($nameLower, $kw)) {
                $okName = true;
                break;
            }
        }
    }
    $img = (string) ($row['image_path'] ?? '');
    $imgOk = $img !== '' && is_file(NB_ROOT . '/uploads/' . $img);
    $status = ($okCat && $okName && $imgOk) ? 'OK' : 'CHECK';
    if ($status !== 'OK') {
        $mismatch++;
    }
    $notes = [];
    if (!$okCat) {
        $notes[] = 'wrong category (want ' . ($exp['cat_name'] ?? '?') . ')';
    }
    if (!$okName) {
        $notes[] = 'name/category keyword weak';
    }
    if (!$imgOk) {
        $notes[] = 'missing image file';
    }
    echo sprintf(
        "%d | %s | %s | %s | %s%s\n",
        $id,
        $row['name'],
        $row['category'] ?? 'NONE',
        $img ?: '(none)',
        $status,
        $notes ? ' — ' . implode('; ', $notes) : ''
    );
}

echo "\nMismatches: {$mismatch}\n";
exit($mismatch > 0 ? 1 : 0);
