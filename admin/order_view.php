<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT o.*, u.name AS customer_name, u.email FROM orders o INNER JOIN users u ON u.id = o.user_id WHERE o.id = ?');
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) {
    flash('danger', 'Order not found.');
    redirect('/admin/orders.php');
}

if (is_post()) {
    require_csrf();
    $action = (string) request('action');
    if ($action === 'status') {
        $status = (string) request('order_status');
        $allowed = ['pending','paid','processing','shipped','completed','cancelled'];
        if (!in_array($status, $allowed, true)) {
            flash('danger', 'Invalid status.');
            redirect('/admin/order_view.php?id=' . $id);
        }
        if ($status === 'cancelled' && $order['order_status'] !== 'cancelled') {
            $result = cancel_order($id, (int) $order['user_id'], true);
            flash($result['ok'] ? 'success' : 'danger', $result['ok'] ? 'Order cancelled and stock restored where applicable.' : $result['message']);
        } else {
            $pdo->prepare('UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?')->execute([$status, $id]);
            if ($status === 'shipped') {
                send_order_email((int) $order['user_id'], $id, 'shipped');
            }
            if ($status === 'completed') {
                send_order_email((int) $order['user_id'], $id, 'completed');
            }
            notify_user((int) $order['user_id'], 'Order update', 'Order ' . $order['order_number'] . ' is now ' . $status, 'order');
            flash('success', 'Order status updated.');
        }
        redirect('/admin/order_view.php?id=' . $id);
    }
    if ($action === 'mark_paid') {
        if ($order['payment_status'] !== 'paid') {
            finalize_paid_order($id, 'ADMIN-MANUAL-' . time(), ['manual' => true, 'by' => current_user()['email']]);
            flash('success', 'Marked as paid and stock reduced.');
        }
        redirect('/admin/order_view.php?id=' . $id);
    }
}

$items = $pdo->prepare('SELECT oi.* FROM order_items oi WHERE oi.order_id = ?');
$items->execute([$id]);
$rows = $items->fetchAll();
$payments = $pdo->prepare('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC');
$payments->execute([$id]);
$paymentRows = $payments->fetchAll();
$timeline = order_timeline($order);

$pageTitle = 'Order ' . $order['order_number'];
$adminPage = 'orders';
require NB_ROOT . '/includes/admin_header.php';
?>
<div class="d-flex justify-content-between mb-3">
    <h1 class="h3 mb-0"><?= e($order['order_number']) ?></h1>
    <div><?= status_badge($order['order_status']) ?> <?= status_badge($order['payment_status']) ?></div>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="nb-panel mb-3">
            <h2 class="h5">Items</h2>
            <?php foreach ($rows as $item): ?>
                <div class="d-flex justify-content-between border-bottom py-2">
                    <div>
                        <strong><?= e($item['product_name']) ?></strong>
                        <div class="small text-muted">Qty <?= (int) $item['quantity'] ?></div>
                    </div>
                    <strong><?= e(money((float) $item['subtotal'])) ?></strong>
                </div>
            <?php endforeach; ?>
            <div class="mt-3">
                <div class="d-flex justify-content-between"><span>Subtotal</span><span><?= e(money((float)$order['subtotal'])) ?></span></div>
                <div class="d-flex justify-content-between"><span>Discount</span><span>- <?= e(money((float)$order['discount_amount'])) ?></span></div>
                <div class="d-flex justify-content-between"><span>Shipping</span><span><?= e(money((float)$order['shipping_fee'])) ?></span></div>
                <div class="d-flex justify-content-between"><strong>Total</strong><strong><?= e(money((float)$order['total_amount'])) ?></strong></div>
            </div>
        </div>
        <div class="nb-panel">
            <h2 class="h5">Transactions</h2>
            <?php foreach ($paymentRows as $pay): ?>
                <div class="border-bottom py-2">
                    <div><strong><?= e($pay['payment_method']) ?></strong> · <?= e($pay['status']) ?></div>
                    <div class="small text-muted">Txn: <?= e((string) $pay['transaction_id']) ?> · <?= e(money((float)$pay['amount'])) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$paymentRows): ?><p class="text-muted mb-0">No payment records yet.</p><?php endif; ?>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="nb-panel mb-3">
            <p><strong>Customer:</strong> <?= e($order['customer_name']) ?></p>
            <p><strong>Email:</strong> <?= e($order['email']) ?></p>
            <p><strong>Ship to:</strong><br><?= e($order['shipping_name']) ?><br><?= e($order['shipping_phone']) ?><br><?= nl2br(e($order['shipping_address'])) ?></p>
            <p><strong>Payment method:</strong> <?= e($order['payment_method']) ?></p>
            <p><strong>Txn:</strong> <?= e((string) $order['transaction_id']) ?></p>
        </div>
        <div class="nb-panel mb-3">
            <h2 class="h6">Timeline</h2>
            <ul class="nb-timeline">
                <?php foreach ($timeline as $step): ?>
                    <li class="<?= e($step['state']) ?>"><div class="dot"><i class="fa-solid <?= e($step['icon']) ?>"></i></div><div><?= e($step['label']) ?></div></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <form method="post" class="nb-panel mb-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="status">
            <label class="nb-form-label">Update status</label>
            <select class="form-select mb-2" name="order_status">
                <?php foreach (['pending','paid','processing','shipped','completed','cancelled'] as $st): ?>
                    <option value="<?= $st ?>" <?= $order['order_status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-nb-primary w-100" type="submit">Save status</button>
        </form>
        <?php if ($order['payment_status'] !== 'paid' && $order['order_status'] !== 'cancelled'): ?>
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="mark_paid">
                <button class="btn btn-nb-accent w-100" type="submit" data-confirm="Mark this order paid and reduce stock?">Mark paid</button>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require NB_ROOT . '/includes/admin_footer.php'; ?>
