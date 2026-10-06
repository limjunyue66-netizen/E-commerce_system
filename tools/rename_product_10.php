<?php
require dirname(__DIR__) . '/config/bootstrap.php';
$pdo = db();
$pdo->prepare('UPDATE products SET name=?, slug=?, description=?, updated_at=NOW() WHERE id=10')
    ->execute(['Flex Tripod Pro', 'flex-tripod-pro', 'Travel phone tripod with extendable pole and stable legs.']);
$pdo->prepare('UPDATE order_items SET product_name=? WHERE product_id=10')->execute(['Flex Tripod Pro']);
echo "OK\n";
