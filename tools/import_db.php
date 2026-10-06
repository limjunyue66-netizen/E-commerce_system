<?php
declare(strict_types=1);

$sqlFile = dirname(__DIR__) . '/database/ecommerce_marketplace.sql';
if (!is_file($sqlFile)) {
    fwrite(STDERR, "SQL file missing\n");
    exit(1);
}

try {
    $pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, 'Connect failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

$sql = file_get_contents($sqlFile);
if ($sql === false) {
    fwrite(STDERR, "Could not read SQL\n");
    exit(1);
}

// Split on semicolons carefully enough for this seed file
$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql) ?: []));
$count = 0;
foreach ($statements as $statement) {
    if ($statement === '' || str_starts_with($statement, '--')) {
        continue;
    }
    // skip pure comment blocks
    $noComments = preg_replace('/^--.*$/m', '', $statement);
    if (trim((string) $noComments) === '') {
        continue;
    }
    $pdo->exec($statement);
    $count++;
}

echo "Imported OK ({$count} statements)\n";

$check = $pdo->query('SELECT COUNT(*) FROM ecommerce.products')->fetchColumn();
echo "Products: {$check}\n";
