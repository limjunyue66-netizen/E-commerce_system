<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

$pdo = db();
$pdo->exec('SET FOREIGN_KEY_CHECKS = 0');

$keepEmails = ['admin@gmail.com', 'diana@gmail.com'];
$placeholders = implode(',', array_fill(0, count($keepEmails), '?'));

// Find users to remove
$stmt = $pdo->prepare("SELECT id, email FROM users WHERE email NOT IN ($placeholders)");
$stmt->execute($keepEmails);
$remove = $stmt->fetchAll();

foreach ($remove as $u) {
    $id = (int) $u['id'];
    echo "Removing {$u['email']} (id={$id})\n";
    $pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM wishlists WHERE user_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM product_reviews WHERE user_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM cart_items WHERE cart_id IN (SELECT id FROM carts WHERE user_id = ?)')->execute([$id]);
    $pdo->prepare('DELETE FROM carts WHERE user_id = ?')->execute([$id]);

    // Orders owned by removed users
    $orders = $pdo->prepare('SELECT id FROM orders WHERE user_id = ?');
    $orders->execute([$id]);
    foreach ($orders->fetchAll() as $o) {
        $oid = (int) $o['id'];
        $pdo->prepare('DELETE FROM payments WHERE order_id = ?')->execute([$oid]);
        $pdo->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$oid]);
        $pdo->prepare('DELETE FROM product_reviews WHERE order_id = ?')->execute([$oid]);
        $pdo->prepare('DELETE FROM orders WHERE id = ?')->execute([$oid]);
    }
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
}

// Ensure diana exists with correct credentials
$adminHash = password_hash('admin1234', PASSWORD_DEFAULT);
$userHash = password_hash('diana1234', PASSWORD_DEFAULT);

$admin = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1")->fetchColumn();
if ($admin) {
    $pdo->prepare("UPDATE users SET email='admin@gmail.com', password=?, must_change_password=0, updated_at=NOW() WHERE id=?")
        ->execute([$adminHash, $admin]);
}

$diana = $pdo->prepare("SELECT id FROM users WHERE email = ? OR (role='user' AND name LIKE 'Diana%') LIMIT 1");
$diana->execute(['diana@gmail.com']);
$dianaId = (int) $diana->fetchColumn();
if ($dianaId) {
    $pdo->prepare("UPDATE users SET name='Diana Lee', email='diana@gmail.com', password=?, role='user', status='active', must_change_password=0, phone='+60120000001', address='10 Lakeview, KL', updated_at=NOW() WHERE id=?")
        ->execute([$userHash, $dianaId]);
} else {
    $pdo->prepare("INSERT INTO users (name, email, password, phone, address, role, status, must_change_password, created_at, updated_at)
        VALUES ('Diana Lee', 'diana@gmail.com', ?, '+60120000001', '10 Lakeview, KL', 'user', 'active', 0, NOW(), NOW())")
        ->execute([$userHash]);
    $dianaId = (int) $pdo->lastInsertId();
}

$pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

echo "Remaining users:\n";
foreach ($pdo->query('SELECT id, name, email, role FROM users ORDER BY id') as $u) {
    echo "{$u['id']} {$u['role']} {$u['email']}\n";
}
