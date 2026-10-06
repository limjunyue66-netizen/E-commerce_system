<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

$pdo = db();

echo "Migrating roles to admin + user only...\n";

// Convert ENUM to include user, then migrate data
try {
    $pdo->exec("ALTER TABLE users MODIFY role ENUM('customer','seller','admin','user') NOT NULL DEFAULT 'user'");
} catch (Throwable $e) {
    echo "ENUM expand note: " . $e->getMessage() . "\n";
}

$pdo->exec("UPDATE users SET role = 'user' WHERE role IN ('customer', 'seller')");
$pdo->exec("UPDATE users SET store_name = NULL WHERE role <> 'admin'");

// Keep only one admin (lowest id)
$admins = $pdo->query("SELECT id, email FROM users WHERE role = 'admin' ORDER BY id ASC")->fetchAll();
if (count($admins) > 1) {
    $keep = (int) $admins[0]['id'];
    $pdo->prepare("UPDATE users SET role = 'user', must_change_password = 0 WHERE role = 'admin' AND id <> ?")->execute([$keep]);
    echo "Kept single admin id={$keep}\n";
}

$adminId = (int) $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetchColumn();
if (!$adminId) {
    throw new RuntimeException('No admin account found.');
}

// All products belong to the single admin storefront
$pdo->prepare('UPDATE products SET seller_id = ?')->execute([$adminId]);
$pdo->prepare('UPDATE order_items SET seller_id = ?')->execute([$adminId]);

// Finalize ENUM
$pdo->exec("ALTER TABLE users MODIFY role ENUM('admin','user') NOT NULL DEFAULT 'user'");

$rows = $pdo->query('SELECT id, email, role FROM users ORDER BY id')->fetchAll();
foreach ($rows as $r) {
    echo "{$r['id']} {$r['role']} {$r['email']}\n";
}
echo "DONE admin_id={$adminId}\n";
