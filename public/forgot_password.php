<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

if (is_post()) {
    require_csrf();
    $result = create_password_reset((string) request('email'));
    flash('success', $result['message']);
    if (!empty($_SESSION['dev_reset_link'])) {
        flash('info', 'Dev reset link: ' . $_SESSION['dev_reset_link']);
        unset($_SESSION['dev_reset_link']);
    }
    redirect('/public/forgot_password.php');
}

$pageTitle = 'Forgot Password — ' . APP_NAME;
require NB_ROOT . '/includes/header.php';
?>
<div class="container nb-auth-wrap">
    <div class="nb-auth-card">
        <h1><i class="fa-solid fa-key me-2"></i>Reset password</h1>
        <p class="text-muted">Enter your account email and we will send a reset link.</p>
        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="nb-form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <button class="btn btn-nb-primary w-100" type="submit">Send reset link</button>
        </form>
    </div>
</div>
<?php require NB_ROOT . '/includes/footer.php'; ?>
