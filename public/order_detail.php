<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_login();
$user = current_user();
if ($user['role'] !== 'user') {
    redirect_by_role($user);
}

$number = trim((string) ($_GET['number'] ?? ''));
$stmt = db()->prepare('SELECT * FROM orders WHERE order_number = ? AND user_id = ? LIMIT 1');
$stmt->execute([$number, (int) $user['id']]);
$order = $stmt->fetch();
if (!$order) {
    flash('danger', 'Order not found.');
    redirect('/public/orders.php');
}

if (is_post() && (string) request('action') === 'cancel') {
    require_csrf();
    $result = cancel_order((int) $order['id'], (int) $user['id'], false);
    flash($result['ok'] ? 'success' : 'danger', $result['ok'] ? 'Order cancelled.' : $result['message']);
    redirect('/public/order_detail.php?number=' . urlencode($number));
}

$items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$items->execute([(int) $order['id']]);
$orderItems = $items->fetchAll();
$timeline = order_timeline($order);

$pageTitle = 'Order ' . $order['order_number'] . ' — ' . APP_NAME;
require NB_ROOT . '/includes/header.php';
?>
<div class="container pb-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="nb-section-title mb-1"><i class="fa-solid fa-receipt me-2"></i><?= e($order['order_number']) ?></h1>
            <div class="text-muted">Placed <?= e(date('M j, Y g:i A', strtotime($order['created_at']))) ?></div>
        </div>
        <div class="d-flex gap-2">
            <?= status_badge($order['order_status']) ?>
            <?= status_badge($order['payment_status']) ?>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="nb-panel mb-3">
                <h2 class="h5 mb-3">Items</h2>
                <?php foreach ($orderItems as $item): ?>
                    <div class="d-flex justify-content-between border-bottom py-2">
                        <div>
                            <strong><?= e($item['product_name']) ?></strong>
                            <div class="small text-muted">Qty <?= (int) $item['quantity'] ?> · <?= e(money((float) $item['unit_price'])) ?></div>
                        </div>
                        <strong><?= e(money((float) $item['subtotal'])) ?></strong>
                    </div>
                <?php endforeach; ?>
                <div class="mt-3">
                    <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= e(money((float) $order['subtotal'])) ?></span></div>
                    <div class="d-flex justify-content-between"><span>Discount</span><span>- <?= e(money((float) $order['discount_amount'])) ?></span></div>
                    <div class="d-flex justify-content-between"><span>Shipping</span><span><?= e(money((float) $order['shipping_fee'])) ?></span></div>
                    <div class="d-flex justify-content-between mt-2"><strong>Total</strong><strong><?= e(money((float) $order['total_amount'])) ?></strong></div>
                </div>
            </div>
            <div class="nb-panel">
                <h2 class="h5 mb-3">Shipping</h2>
                <p class="mb-1"><strong><?= e($order['shipping_name']) ?></strong></p>
                <p class="mb-1"><?= e($order['shipping_phone']) ?></p>
                <p class="mb-0"><?= nl2br(e($order['shipping_address'])) ?></p>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="nb-panel mb-3">
                <h2 class="h5 mb-3">Timeline</h2>
                <ul class="nb-timeline">
                    <?php foreach ($timeline as $step): ?>
                        <li class="<?= e($step['state']) ?>">
                            <div class="dot"><i class="fa-solid <?= e($step['icon']) ?>"></i></div>
                            <div><strong><?= e($step['label']) ?></strong></div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <div class="nb-panel mb-3">
                <p class="mb-1"><strong>Payment:</strong> <?= e($order['payment_method'] === 'bank' ? 'Bank / Card' : strtoupper($order['payment_method'])) ?></p>
                <p class="mb-1"><strong>Txn ID:</strong> <?= e($order['transaction_id'] ?: '—') ?></p>
                <?php
                $bankPayment = null;
                if ($order['payment_method'] === 'bank') {
                    $payStmt = db()->prepare("SELECT response_data FROM payments WHERE order_id = ? AND payment_method = 'bank' ORDER BY id DESC LIMIT 1");
                    $payStmt->execute([(int) $order['id']]);
                    $raw = $payStmt->fetchColumn();
                    $bankPayment = $raw ? json_decode((string) $raw, true) : null;
                }
                ?>
                <?php if (is_array($bankPayment)): ?>
                    <hr>
                    <p class="mb-1"><strong>Account name:</strong> <?= e((string) ($bankPayment['payer_account_name'] ?? '—')) ?></p>
                    <p class="mb-0"><strong>Account number:</strong> <span class="nb-bank-account"><?= e((string) ($bankPayment['payer_account_number'] ?? '—')) ?></span></p>
                <?php endif; ?>
            </div>
            <?php if (order_can_cancel($order)): ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="cancel">
                    <button class="btn btn-outline-danger w-100" type="submit" data-confirm="Cancel this order?">
                        <i class="fa-solid fa-ban me-1"></i>Cancel order
                    </button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require NB_ROOT . '/includes/footer.php'; ?>
