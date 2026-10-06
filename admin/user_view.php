<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
$stmt->execute([$id]);
$u = $stmt->fetch();
if (!$u) {
    flash('danger', 'User not found.');
    redirect('/admin/users.php');
}

$orders = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$orders->execute([$id]);
$orderRows = $orders->fetchAll();

$pageTitle = 'User Detail — Admin';
$adminPage = 'users';
require NB_ROOT . '/includes/admin_header.php';
?>
<h1 class="h3 mb-3"><i class="fa-solid fa-user me-2"></i><?= e($u['name']) ?></h1>
<div class="row g-3">
    <div class="col-lg-6">
        <div class="nb-panel">
            <p><strong>Email:</strong> <?= e($u['email']) ?></p>
            <p><strong>Phone:</strong> <?= e($u['phone']) ?></p>
            <p><strong>Address:</strong> <?= nl2br(e($u['address'])) ?></p>
            <p><strong>Role:</strong> <span class="badge text-bg-<?= $u['role'] === 'admin' ? 'dark' : 'secondary' ?>"><?= e($u['role']) ?></span></p>
            <p><strong>Status:</strong> <?= status_badge($u['status']) ?></p>
            <p class="text-muted small mb-0">This system allows only one admin account. Roles cannot be changed to create another admin.</p>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="nb-panel">
            <h2 class="h5">Recent orders</h2>
            <?php foreach ($orderRows as $o): ?>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <a href="<?= e(url('/admin/order_view.php?id=' . (int) $o['id'])) ?>"><?= e($o['order_number']) ?></a>
                    <span><?= e(money((float) $o['total_amount'])) ?></span>
                </div>
            <?php endforeach; ?>
            <?php if (!$orderRows): ?><p class="text-muted mb-0">No orders.</p><?php endif; ?>
        </div>
    </div>
</div>
<?php require NB_ROOT . '/includes/admin_footer.php'; ?>
