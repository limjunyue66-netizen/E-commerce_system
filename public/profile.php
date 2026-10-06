<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_login();
$user = current_user();
if ($user['role'] !== 'user') {
    redirect_by_role($user);
}

if (is_post()) {
    require_csrf();
    $action = (string) request('action');
    if ($action === 'profile') {
        $name = trim((string) request('name'));
        $phone = trim((string) request('phone'));
        $address = trim((string) request('address'));
        if ($name === '' || $phone === '' || $address === '') {
            flash('danger', 'All profile fields are required.');
            redirect('/public/profile.php');
        }
        db()->prepare('UPDATE users SET name = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$name, $phone, $address, (int) $user['id']]);
        flash('success', 'Profile updated.');
        redirect('/public/profile.php');
    }
    if ($action === 'password') {
        $current = (string) request('current_password');
        $new = (string) request('new_password');
        $confirm = (string) request('confirm_password');
        if (!password_verify($current, $user['password'])) {
            flash('danger', 'Current password is incorrect.');
            redirect('/public/profile.php');
        }
        $err = validate_password_strength($new);
        if ($err) {
            flash('danger', $err);
            redirect('/public/profile.php');
        }
        if ($new !== $confirm) {
            flash('danger', 'Password confirmation does not match.');
            redirect('/public/profile.php');
        }
        db()->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), (int) $user['id']]);
        flash('success', 'Password changed.');
        redirect('/public/profile.php');
    }
}

$user = current_user();
$pageTitle = 'My Profile — ' . APP_NAME;
require NB_ROOT . '/includes/header.php';
?>
<div class="container pb-5">
    <h1 class="nb-section-title mb-3"><i class="fa-solid fa-id-card me-2"></i>My Profile</h1>
    <div class="row g-3">
        <div class="col-lg-7">
            <form method="post" class="nb-panel">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="profile">
                <div class="mb-3">
                    <label class="nb-form-label">Full name</label>
                    <input class="form-control" name="name" value="<?= e($user['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="nb-form-label">Email</label>
                    <input class="form-control" value="<?= e($user['email']) ?>" readonly>
                </div>
                <div class="mb-3">
                    <label class="nb-form-label">Phone</label>
                    <input class="form-control" name="phone" value="<?= e($user['phone']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="nb-form-label">Address</label>
                    <textarea class="form-control" name="address" rows="3" required><?= e($user['address']) ?></textarea>
                </div>
                <button class="btn btn-nb-primary" type="submit">Save profile</button>
            </form>
        </div>
        <div class="col-lg-5">
            <form method="post" class="nb-panel">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="password">
                <h2 class="h5 mb-3"><i class="fa-solid fa-key me-2"></i>Change password</h2>
                <div class="mb-3">
                    <label class="nb-form-label">Current password</label>
                    <input type="password" class="form-control" name="current_password" required>
                </div>
                <div class="mb-3">
                    <label class="nb-form-label">New password</label>
                    <input type="password" class="form-control" name="new_password" required>
                </div>
                <div class="mb-3">
                    <label class="nb-form-label">Confirm new password</label>
                    <input type="password" class="form-control" name="confirm_password" required>
                </div>
                <button class="btn btn-nb-outline" type="submit">Update password</button>
            </form>
        </div>
    </div>
</div>
<?php require NB_ROOT . '/includes/footer.php'; ?>
