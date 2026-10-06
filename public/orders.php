<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_login();
$user = current_user();
if ($user['role'] !== 'user') {
    redirect_by_role($user);
}

$stmt = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([(int) $user['id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders — ' . APP_NAME;
require NB_ROOT . '/includes/header.php';
?>
<div class="container pb-5">
    <h1 class="nb-section-title mb-3"><i class="fa-solid fa-box me-2"></i>My Orders</h1>
    <?php if (!$orders): ?>
        <div class="nb-empty nb-panel">
            <i class="fa-solid fa-receipt"></i>
            <h3>No orders yet</h3>
            <a class="btn btn-nb-primary" href="<?= e(url('/public/shop.php')) ?>">Start shopping</a>
        </div>
    <?php else: ?>
        <div class="nb-panel table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                <tr>
                    <th>Order</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><strong><?= e($order['order_number']) ?></strong></td>
                        <td><?= e(date('M j, Y', strtotime($order['created_at']))) ?></td>
                        <td><?= e(money((float) $order['total_amount'])) ?></td>
                        <td><?= status_badge($order['payment_status']) ?></td>
                        <td><?= status_badge($order['order_status']) ?></td>
                        <td><a class="btn btn-sm btn-nb-outline" href="<?= e(url('/public/order_detail.php?number=' . urlencode($order['order_number']))) ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<?php require NB_ROOT . '/includes/footer.php'; ?>
