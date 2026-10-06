<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
$status = (string) ($_GET['status'] ?? '');
$sql = "SELECT o.*, u.name AS customer_name FROM orders o INNER JOIN users u ON u.id = o.user_id";
$params = [];
if (in_array($status, ['pending','paid','processing','shipped','completed','cancelled'], true)) {
    $sql .= ' WHERE o.order_status = ?';
    $params[] = $status;
}
$sql .= ' ORDER BY o.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$pageTitle = 'Orders — Admin';
$adminPage = 'orders';
require NB_ROOT . '/includes/admin_header.php';
?>
<div class="d-flex justify-content-between mb-3">
    <h1 class="h3 mb-0"><i class="fa-solid fa-receipt me-2"></i>Orders</h1>
    <form method="get" class="d-flex gap-2">
        <select class="form-select" name="status" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <?php foreach (['pending','paid','processing','shipped','completed','cancelled'] as $st): ?>
                <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
</div>
<div class="nb-panel table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?= e($o['order_number']) ?></td>
                <td><?= e($o['customer_name']) ?></td>
                <td><?= e(money((float) $o['total_amount'])) ?></td>
                <td><?= status_badge($o['payment_status']) ?></td>
                <td><?= status_badge($o['order_status']) ?></td>
                <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
                <td><a class="btn btn-sm btn-nb-outline" href="<?= e(url('/admin/order_view.php?id=' . (int) $o['id'])) ?>">View</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require NB_ROOT . '/includes/admin_footer.php'; ?>
