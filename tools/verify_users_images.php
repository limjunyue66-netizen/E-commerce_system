<?php
require dirname(__DIR__) . '/config/bootstrap.php';
echo 'users=' . db()->query('SELECT COUNT(*) FROM users')->fetchColumn() . PHP_EOL;
foreach (db()->query('SELECT id,email,role FROM users ORDER BY id') as $u) {
    echo "{$u['id']} {$u['role']} {$u['email']}\n";
}
echo 'product_images=' . db()->query('SELECT COUNT(*) FROM product_images')->fetchColumn() . PHP_EOL;
echo 'jpg_files=' . count(glob(NB_ROOT . '/uploads/products/product-*.jpg')) . PHP_EOL;
