<?php
declare(strict_types=1);

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    static $cached = null;
    if ($cached !== null && (int) $cached['id'] === (int) $_SESSION['user_id']) {
        return $cached;
    }
    $stmt = db()->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([(int) $_SESSION['user_id']]);
    $user = $stmt->fetch();
    if (!$user || $user['status'] !== 'active') {
        logout_user();
        return null;
    }
    $cached = $user;
    return $user;
}

function is_logged_in(): bool
{
    return current_user() !== null;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['must_change_password'] = (
        FORCE_PASSWORD_CHANGE
        && $user['role'] === 'admin'
        && !empty($user['must_change_password'])
    );
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('warning', 'Please log in to continue.');
        redirect('/public/login.php');
    }
}

function require_role(string ...$roles): void
{
    require_login();
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        flash('danger', 'You do not have permission to access that page.');
        redirect('/');
    }
}

function require_admin(): void
{
    require_role('admin');
}

function require_user(): void
{
    require_role('user');
}

/** @deprecated Use require_user() */
function require_customer(): void
{
    require_user();
}

/** Seller role removed — redirect away */
function require_seller(): void
{
    flash('danger', 'Seller accounts are not supported.');
    redirect('/');
}

function admin_user_id(): int
{
    static $id = null;
    if ($id !== null) {
        return $id;
    }
    $id = (int) db()->query("SELECT id FROM users WHERE role = 'admin' ORDER BY id ASC LIMIT 1")->fetchColumn();
    return $id;
}

function count_admins(): int
{
    return (int) db()->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
}

function attempt_login(string $email, string $password): array
{
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([strtolower(trim($email))]);
    $user = $stmt->fetch();
    if (!$user || !password_verify($password, $user['password'])) {
        app_log('auth', 'Failed login for ' . $email);
        return ['ok' => false, 'message' => 'Invalid email or password.'];
    }
    if ($user['status'] !== 'active') {
        return ['ok' => false, 'message' => 'Your account is suspended. Contact support.'];
    }
    login_user($user);
    return ['ok' => true, 'user' => $user];
}

function register_customer(array $data): array
{
    $errors = [];
    $name = trim($data['name'] ?? '');
    $email = strtolower(trim($data['email'] ?? ''));
    $password = $data['password'] ?? '';
    $confirm = $data['password_confirm'] ?? '';
    $phone = trim($data['phone'] ?? '');
    $address = trim($data['address'] ?? '');

    if ($name === '') {
        $errors[] = 'Full name is required.';
    }
    if (!validate_email($email)) {
        $errors[] = 'A valid email is required.';
    }
    if ($phone === '') {
        $errors[] = 'Phone is required.';
    }
    if ($address === '') {
        $errors[] = 'Address is required.';
    }
    $pwError = validate_password_strength($password);
    if ($pwError) {
        $errors[] = $pwError;
    }
    if ($password !== $confirm) {
        $errors[] = 'Password confirmation does not match.';
    }

    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = 'Email is already registered.';
    }

    if ($errors) {
        return ['ok' => false, 'errors' => $errors];
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare("INSERT INTO users (name, email, password, phone, address, role, status, must_change_password, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 'user', 'active', 0, NOW(), NOW())");
    $stmt->execute([$name, $email, $hash, $phone, $address]);
    $userId = (int) db()->lastInsertId();

    notify_user($userId, 'Welcome to ' . APP_NAME, 'Your account was created successfully.', 'system');
    send_mail_safe($email, 'Welcome to ' . APP_NAME, "Hi {$name},\n\nWelcome to " . APP_NAME . "!\n\nHappy shopping.");

    return ['ok' => true, 'user_id' => $userId];
}

function redirect_by_role(array $user): never
{
    match ($user['role']) {
        'admin' => redirect('/admin/dashboard.php'),
        default => redirect('/public/shop.php'),
    };
}
