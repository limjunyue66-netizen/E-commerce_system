<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

if (is_logged_in()) {
    redirect_by_role(current_user());
}

if (is_post()) {
    require_csrf();
    $result = register_customer($_POST);
    if ($result['ok']) {
        clear_old();
        flash('success', 'Account created. Please log in.');
        redirect('/public/login.php');
    }
    store_old($_POST);
    foreach ($result['errors'] as $err) {
        flash('danger', $err);
    }
    redirect('/public/register.php');
}

$pageTitle = 'Register — ' . APP_NAME;
require NB_ROOT . '/includes/header.php';
?>
<div class="container nb-auth-wrap">
    <div class="nb-auth-card nb-animate" style="width:min(560px,100%)">
        <h1><i class="fa-solid fa-user-plus me-2"></i>Create account</h1>
        <p class="text-muted">Create a user account and start shopping.</p>
        <form method="post">
            <?= csrf_field() ?>
            <div class="row g-3">
                <div class="col-12">
                    <label class="nb-form-label">Full Name</label>
                    <input class="form-control" name="name" required value="<?= e((string) old('name')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="nb-form-label">Email</label>
                    <input type="email" class="form-control" name="email" required value="<?= e((string) old('email')) ?>">
                </div>
                <div class="col-md-6">
                    <label class="nb-form-label">Phone</label>
                    <input class="form-control" name="phone" required value="<?= e((string) old('phone')) ?>">
                </div>
                <div class="col-12">
                    <label class="nb-form-label">Address</label>
                    <textarea class="form-control" name="address" rows="2" required><?= e((string) old('address')) ?></textarea>
                </div>
                <div class="col-md-6">
                    <label class="nb-form-label">Password</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
                <div class="col-md-6">
                    <label class="nb-form-label">Confirm Password</label>
                    <input type="password" class="form-control" name="password_confirm" required>
                </div>
            </div>
            <button class="btn btn-nb-primary w-100 mt-3" type="submit">Register</button>
        </form>
        <p class="mt-3 mb-0 small">Already have an account? <a href="<?= e(url('/public/login.php')) ?>">Login</a></p>
    </div>
</div>
<?php require NB_ROOT . '/includes/footer.php'; ?>
