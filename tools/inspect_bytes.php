<?php
$path = dirname(__DIR__) . '/public/wishlist.php';
$c = file_get_contents($path);
$pos = strpos($c, 'role');
$snippet = substr($c, $pos, 50);
echo $snippet . "\n";
for ($i = 0; $i < strlen($snippet); $i++) {
    echo $i . ':' . $snippet[$i] . '=' . ord($snippet[$i]) . "\n";
}
