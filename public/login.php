<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

if (is_logged_in()) {
    redirect_by_role(current_user());
}

if (is_post()) {
    require_csrf();
    $result = attempt_login((string) request('email'), (string) request('password'));
    if ($result['ok']) {
        clear_old();
        flash('success', 'Welcome back, ' . $result['user']['name'] . '!');
        redirect_by_role($result['user']);
    }
    store_old($_POST);
    flash('danger', $result['message']);
    redirect('/public/login.php');
}

$pageTitle = 'Login — ' . APP_NAME;
require NB_ROOT . '/includes/header.php';
?>
<div class="container nb-auth-wrap">
    <div class="nb-auth-card nb-animate">
        <div class="mb-3 text-center">
            <img src="<?= e(asset('images/logo.svg')) ?>" width="48" height="48" alt="">
            <h1 class="mt-2">Sign in</h1>
            <p class="text-muted mb-0">Sign in as Admin or User.</p>
        </div>
        <form method="post" autocomplete="on" id="loginForm">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="nb-form-label"><i class="fa-solid fa-envelope me-1"></i>Email</label>
                <input type="email" name="email" id="loginEmail" class="form-control" required value="<?= e((string) old('email')) ?>">
            </div>
            <div class="mb-3">
                <label class="nb-form-label"><i class="fa-solid fa-lock me-1"></i>Password</label>
                <input type="password" name="password" id="loginPassword" class="form-control" required>
            </div>
            <button class="btn btn-nb-primary w-100" type="submit">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Login
            </button>
        </form>
        <div class="d-flex justify-content-between mt-3 small">
            <a href="<?= e(url('/public/register.php')) ?>">Create account</a>
            <a href="<?= e(url('/public/forgot_password.php')) ?>">Forgot password?</a>
        </div>

        <div class="mt-3 p-3 rounded-3" style="background:#f0fdfa;border:1px solid #99f6e4;font-size:.88rem;">
            <strong><i class="fa-solid fa-circle-info me-1"></i>Demo accounts</strong><br>
            Admin: admin@gmail.com / admin1234<br>
            User: diana@gmail.com / diana1234
            <div class="d-flex gap-2 mt-3">
                <button type="button" class="btn btn-sm btn-nb-primary flex-fill" id="autoAdminBtn"
                        data-email="admin@gmail.com" data-password="admin1234">
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Auto info Admin
                </button>
                <button type="button" class="btn btn-sm btn-nb-accent flex-fill" id="autoUserBtn"
                        data-email="diana@gmail.com" data-password="diana1234">
                    <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Auto info User
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const emailInput = document.getElementById('loginEmail');
  const passwordInput = document.getElementById('loginPassword');

  document.querySelectorAll('[data-email][data-password]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      emailInput.value = btn.getAttribute('data-email') || '';
      passwordInput.value = btn.getAttribute('data-password') || '';
      emailInput.focus();
    });
  });
});
</script>
<?php require NB_ROOT . '/includes/footer.php'; ?>
