<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token = null): bool
{
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    $sessionToken = $_SESSION['csrf_token'] ?? '';
    if ($token === '' || $sessionToken === '') {
        return false;
    }
    return hash_equals($sessionToken, $token);
}

function require_csrf(): void
{
    if (!verify_csrf()) {
        app_log('auth', 'CSRF validation failed from IP ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
        flash('danger', 'Security token mismatch. Please try again.');
        $ref = $_SERVER['HTTP_REFERER'] ?? url('/');
        if (!str_starts_with($ref, APP_URL) && !str_starts_with($ref, APP_BASE_PATH)) {
            redirect('/');
        }
        header('Location: ' . $ref);
        exit;
    }
}
