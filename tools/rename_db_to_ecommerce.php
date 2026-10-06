<?php
declare(strict_types=1);
/**
 * Rename/migrate database from novabazaar -> ecommerce (keep existing data).
 */
require dirname(__DIR__) . '/config/bootstrap.php';

$host = (string) env('DB_HOST', '127.0.0.1');
$user = (string) env('DB_USER', 'root');
$pass = (string) env('DB_PASS', '');
$old = 'novabazaar';
$new = (string) env('DB_NAME', 'ecommerce');

$pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$oldExists = (bool) $pdo->query('SHOW DATABASES LIKE ' . $pdo->quote($old))->fetch();
$newExists = (bool) $pdo->query('SHOW DATABASES LIKE ' . $pdo->quote($new))->fetch();

echo "Old DB ({$old}): " . ($oldExists ? 'yes' : 'no') . PHP_EOL;
echo "New DB ({$new}): " . ($newExists ? 'yes' : 'no') . PHP_EOL;

if (!$oldExists && $newExists) {
    echo "Already using {$new}. Nothing to migrate.\n";
    exit(0);
}

if (!$oldExists && !$newExists) {
    echo "Neither database exists. Import database/ecommerce_marketplace.sql first.\n";
    exit(1);
}

$pdo->exec("CREATE DATABASE IF NOT EXISTS `{$new}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

if ($oldExists) {
    $tables = $pdo->query("SHOW TABLES FROM `{$old}`")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $existsInNew = (bool) $pdo->query("SHOW TABLES FROM `{$new}` LIKE " . $pdo->quote($table))->fetch();
        if ($existsInNew) {
            $pdo->exec("DROP TABLE `{$new}`.`{$table}`");
        }
        echo "Moving {$old}.{$table} -> {$new}.{$table}\n";
        $pdo->exec("RENAME TABLE `{$old}`.`{$table}` TO `{$new}`.`{$table}`");
    }
    // Drop empty old database if no tables left
    $left = $pdo->query("SHOW TABLES FROM `{$old}`")->fetchAll();
    if (!$left) {
        $pdo->exec("DROP DATABASE `{$old}`");
        echo "Dropped empty database {$old}\n";
    }
}

$count = (int) $pdo->query("SELECT COUNT(*) FROM `{$new}`.products")->fetchColumn();
echo "Done. Products in {$new}: {$count}\n";
