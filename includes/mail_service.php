<?php
declare(strict_types=1);

/**
 * Lightweight mail helper. Works without SMTP crash when disabled.
 */
function send_mail_safe(string $to, string $subject, string $body): bool
{
    if (!MAIL_ENABLED || MAIL_HOST === '') {
        app_log('mail', "Skipped (disabled): to={$to}; subject={$subject}");
        return false;
    }

    $headers = [
        'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
    ];

    $ok = @mail($to, $subject, $body, implode("\r\n", $headers));
    if (!$ok) {
        app_log('mail', "Failed sending to {$to}: {$subject}");
    }
    return $ok;
}

function send_order_email(int $userId, int $orderId, string $event): void
{
    $stmt = db()->prepare('SELECT u.email, u.name, o.order_number, o.total_amount, o.order_status FROM users u INNER JOIN orders o ON o.user_id = u.id WHERE u.id = ? AND o.id = ?');
    $stmt->execute([$userId, $orderId]);
    $row = $stmt->fetch();
    if (!$row) {
        return;
    }

    $subjects = [
        'placed' => 'Order confirmation ' . $row['order_number'],
        'paid' => 'Payment confirmed ' . $row['order_number'],
        'shipped' => 'Your order has shipped ' . $row['order_number'],
        'completed' => 'Order completed ' . $row['order_number'],
        'cancelled' => 'Order cancelled ' . $row['order_number'],
    ];
    $subject = $subjects[$event] ?? ('Order update ' . $row['order_number']);
    $body = "Hi {$row['name']},\n\nYour order {$row['order_number']} status update: {$event}.\nTotal: " . money((float) $row['total_amount']) . "\nCurrent status: {$row['order_status']}\n\nThank you for shopping with " . APP_NAME . '.';
    send_mail_safe($row['email'], $subject, $body);
}

function create_password_reset(string $email): array
{
    $stmt = db()->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();
    // Always return success-looking message to avoid email enumeration in UI; still process if found
    if (!$user) {
        return ['ok' => true, 'message' => 'If that email exists, a reset link has been sent.'];
    }

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date('Y-m-d H:i:s', time() + 3600);

    db()->prepare('INSERT INTO password_resets (user_id, email, token, expires_at, used_at, created_at) VALUES (?, ?, ?, ?, NULL, NOW())')
        ->execute([(int) $user['id'], $user['email'], $hash, $expires]);

    $link = APP_URL . '/public/reset_password.php?token=' . urlencode($token);
    send_mail_safe($user['email'], 'Password reset - ' . APP_NAME, "Hi {$user['name']},\n\nReset your password using this link (valid 1 hour):\n{$link}\n\nIf you did not request this, ignore this email.");

    // In local/dev without mail, store token hint in session flash for testing
    if (!MAIL_ENABLED) {
        $_SESSION['dev_reset_link'] = $link;
    }

    return ['ok' => true, 'message' => 'If that email exists, a reset link has been sent.'];
}

function consume_password_reset(string $token, string $password): array
{
    $hash = hash('sha256', $token);
    $stmt = db()->prepare('SELECT * FROM password_resets WHERE token = ? AND used_at IS NULL AND expires_at > NOW() ORDER BY id DESC LIMIT 1');
    $stmt->execute([$hash]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['ok' => false, 'message' => 'Invalid or expired reset token.'];
    }
    $pwError = validate_password_strength($password);
    if ($pwError) {
        return ['ok' => false, 'message' => $pwError];
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE users SET password = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?')
            ->execute([password_hash($password, PASSWORD_DEFAULT), $row['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([$row['id']]);
        $pdo->commit();
        return ['ok' => true, 'message' => 'Password updated. You can log in now.'];
    } catch (Throwable $e) {
        $pdo->rollBack();
        return ['ok' => false, 'message' => 'Could not reset password.'];
    }
}
