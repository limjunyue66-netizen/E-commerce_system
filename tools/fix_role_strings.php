<?php
$files = [
    'public/wishlist.php',
    'public/checkout.php',
    'public/orders.php',
    'public/order_detail.php',
    'public/profile.php',
    'public/cart.php',
    'public/product.php',
];
foreach ($files as $f) {
    $path = dirname(__DIR__) . '/' . $f;
    $c = file_get_contents($path);
    $n = str_replace("'customer'", "'user'", $c);
    if ($n !== $c) {
        file_put_contents($path, $n);
        echo "fixed {$f}\n";
    } else {
        echo "ok {$f}\n";
    }
}
