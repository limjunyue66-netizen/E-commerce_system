<?php
declare(strict_types=1);
require dirname(__DIR__) . '/config/bootstrap.php';

$pdo = db();

$adminHash = password_hash('admin1234', PASSWORD_DEFAULT);
$userHash = password_hash('diana1234', PASSWORD_DEFAULT);

// Update admin
$stmt = $pdo->prepare("UPDATE users SET email = ?, password = ?, must_change_password = 0, updated_at = NOW() WHERE role = 'admin' ORDER BY id ASC LIMIT 1");
// MySQL doesn't allow ORDER BY/LIMIT in UPDATE with prepared the same way on all versions - do by id
$adminId = (int) $pdo->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetchColumn();
if ($adminId) {
    $pdo->prepare("UPDATE users SET email = ?, password = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?")
        ->execute(['admin@gmail.com', $adminHash, $adminId]);
    echo "Admin updated id={$adminId}\n";
}

// Update Diana demo user (by old email or name)
$diana = $pdo->prepare("SELECT id FROM users WHERE email IN (?, ?) OR (name = ? AND role = 'user') ORDER BY id ASC LIMIT 1");
$diana->execute(['diana@example.com', 'diana@gmail.com', 'Diana Lee']);
$dianaId = (int) $diana->fetchColumn();
if ($dianaId) {
    $pdo->prepare("UPDATE users SET email = ?, password = ?, name = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?")
        ->execute(['diana@gmail.com', $userHash, 'Diana Lee', $dianaId]);
    echo "User updated id={$dianaId}\n";
} else {
    $pdo->prepare("INSERT INTO users (name, email, password, phone, address, role, status, must_change_password, created_at, updated_at)
        VALUES ('Diana Lee', 'diana@gmail.com', ?, '+60120000001', '10 Lakeview, KL', 'user', 'active', 0, NOW(), NOW())")
        ->execute([$userHash]);
    echo "User created\n";
}

echo "Admin hash: {$adminHash}\n";
echo "User hash: {$userHash}\n";

foreach ($pdo->query("SELECT id, role, email FROM users WHERE email IN ('admin@gmail.com','diana@gmail.com')") as $u) {
    echo "{$u['id']} {$u['role']} {$u['email']}\n";
}

// Verify
$a = $pdo->query("SELECT password FROM users WHERE email = 'admin@gmail.com'")->fetchColumn();
$d = $pdo->query("SELECT password FROM users WHERE email = 'diana@gmail.com'")->fetchColumn();
echo 'admin verify: ' . (password_verify('admin1234', (string) $a) ? 'OK' : 'FAIL') . "\n";
echo 'diana verify: ' . (password_verify('diana1234', (string) $d) ? 'OK' : 'FAIL') . "\n";
