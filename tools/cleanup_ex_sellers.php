<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';
$pdo = db();

// Remove former seller demo accounts (now unused users)
$emails = [
    'ava.seller@novabazaar.local',
    'ben.seller@novabazaar.local',
    'clara.seller@novabazaar.local',
];
foreach ($emails as $email) {
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND role = ?');
    $stmt->execute([$email, 'user']);
    $id = (int) $stmt->fetchColumn();
    if (!$id) {
        continue;
    }
    $pdo->prepare('DELETE FROM notifications WHERE user_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM wishlists WHERE user_id = ?')->execute([$id]);
    $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$id]);
    // Do not delete if they own orders
    $hasOrders = (int) $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?')->execute([$id]) ? 0 : 0;
    $c = $pdo->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
    $c->execute([$id]);
    if ((int) $c->fetchColumn() > 0) {
        echo "Skip {$email} (has orders)\n";
        continue;
    }
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
    echo "Deleted {$email}\n";
}

echo "Admins=" . count_admins() . "\n";
foreach ($pdo->query('SELECT id, role, email FROM users ORDER BY id') as $u) {
    echo "{$u['id']} {$u['role']} {$u['email']}\n";
}
