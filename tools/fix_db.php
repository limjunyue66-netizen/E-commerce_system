<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

$host = (string) env('DB_HOST', '127.0.0.1');
$user = (string) env('DB_USER', 'root');
$pass = (string) env('DB_PASS', '');
$name = (string) env('DB_NAME', 'ecommerce');

echo "Host={$host}\nUser={$user}\nDB={$name}\nPass=" . ($pass === '' ? '(empty)' : '(set)') . "\n\n";

try {
    $pdo = new PDO("mysql:host={$host};charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "Server connect: OK\n";
} catch (Throwable $e) {
    echo "Server connect FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

$exists = $pdo->query("SHOW DATABASES LIKE " . $pdo->quote($name))->fetch();
echo $exists ? "Database '{$name}' exists\n" : "Database '{$name}' MISSING\n";

if (!$exists) {
    echo "Creating and importing schema...\n";
    $sqlFile = dirname(__DIR__) . '/database/ecommerce_marketplace.sql';
    $sql = file_get_contents($sqlFile);
    if ($sql === false) {
        echo "Cannot read SQL file\n";
        exit(1);
    }

    // Execute statement by statement
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `{$name}`");

    // Remove CREATE DATABASE / USE lines; run rest
    $sql = preg_replace('/^CREATE DATABASE.*?;/mi', '', $sql) ?? $sql;
    $sql = preg_replace('/^USE\s+\w+\s*;/mi', '', $sql) ?? $sql;

    $parts = preg_split('/;\s*\R/', $sql) ?: [];
    $ok = 0;
    foreach ($parts as $part) {
        $stmt = trim(preg_replace('/^--.*$/m', '', $part) ?? $part);
        if ($stmt === '') {
            continue;
        }
        try {
            $pdo->exec($stmt);
            $ok++;
        } catch (Throwable $e) {
            echo "Statement failed: " . $e->getMessage() . "\n";
            echo "Near: " . substr($stmt, 0, 120) . "...\n";
            exit(1);
        }
    }
    echo "Imported {$ok} statements\n";
}

$pdo->exec("USE `{$name}`");
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo 'Tables: ' . count($tables) . ' (' . implode(', ', $tables) . ")\n";
$products = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$users = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
echo "users={$users}, products={$products}\n";
echo "DONE\n";
