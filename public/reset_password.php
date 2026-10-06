<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
if ($token === '') {
    flash('danger', 'Invalid reset link.');
    redirect('/public/forgot_password.php');
}

if (is_post()) {
    require_csrf();
    if ((string) request('password') !== (string) request('password_confirm')) {
        flash('danger', 'Passwords do not match.');
        redirect('/public/reset_password.php?token=' . urlencode($token));
    }
    $result = consume_password_reset($token, (string) request('password'));
    flash($result['ok'] ? 'success' : 'danger', $result['message']);
    redirect($result['ok'] ? '/public/login.php' : '/public/forgot_password.php');
}

$pageTitle = 'Set New Password — ' . APP_NAME;
require NB_ROOT . '/includes/header.php';
?>
<div class="container nb-auth-wrap">
    <div class="nb-auth-card">
        <h1><i class="fa-solid fa-lock me-2"></i>New password</h1>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="token" value="<?= e($token) ?>">
            <div class="mb-3">
                <label class="nb-form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="nb-form-label">Confirm Password</label>
                <input type="password" name="password_confirm" class="form-control" required>
            </div>
            <button class="btn btn-nb-primary w-100" type="submit">Update password</button>
        </form>
    </div>
</div>
<?php require NB_ROOT . '/includes/footer.php'; ?>
