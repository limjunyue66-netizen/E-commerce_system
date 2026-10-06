<?php
foreach ([3306, 3307, 3308] as $p) {
    try {
        new PDO('mysql:host=127.0.0.1;port=' . $p, 'root', '');
        echo "port {$p} OK (empty password)\n";
    } catch (Throwable $e) {
        echo "port {$p} FAIL: " . $e->getMessage() . "\n";
    }
}
