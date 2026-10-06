<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
$pdo = db();

if (is_post()) {
    require_csrf();
    $action = (string) request('action');
    if ($action === 'save') {
        $id = (int) request('id');
        $code = strtoupper(trim((string) request('code')));
        $type = (string) request('discount_type') === 'fixed' ? 'fixed' : 'percentage';
        $value = (float) request('discount_value');
        $min = (float) request('minimum_amount');
        $max = request('maximum_discount') !== '' ? (float) request('maximum_discount') : null;
        $limit = max(0, (int) request('usage_limit'));
        $start = request('start_date') ? str_replace('T', ' ', (string) request('start_date')) . ':00' : null;
        $expiry = request('expiry_date') ? str_replace('T', ' ', (string) request('expiry_date')) . ':00' : null;
        if ($start && strlen($start) > 19) {
            $start = substr($start, 0, 19);
        }
        if ($expiry && strlen($expiry) > 19) {
            $expiry = substr($expiry, 0, 19);
        }
        $status = (string) request('status') === 'inactive' ? 'inactive' : 'active';
        if ($code === '' || $value <= 0) {
            flash('danger', 'Coupon code and value are required.');
            redirect('/admin/coupons.php');
        }
        if ($id > 0) {
            $pdo->prepare('UPDATE coupons SET code=?, discount_type=?, discount_value=?, minimum_amount=?, maximum_discount=?, usage_limit=?, start_date=?, expiry_date=?, status=?, updated_at=NOW() WHERE id=?')
                ->execute([$code, $type, $value, $min, $max, $limit, $start, $expiry, $status, $id]);
            flash('success', 'Coupon updated.');
        } else {
            $pdo->prepare('INSERT INTO coupons (code, discount_type, discount_value, minimum_amount, maximum_discount, usage_limit, used_count, start_date, expiry_date, status, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW(), NOW())')
                ->execute([$code, $type, $value, $min, $max, $limit, $start, $expiry, $status]);
            flash('success', 'Coupon created.');
        }
    }
    if ($action === 'toggle') {
        $id = (int) request('id');
        $pdo->prepare("UPDATE coupons SET status = IF(status='active','inactive','active'), updated_at = NOW() WHERE id = ?")->execute([$id]);
        flash('success', 'Coupon status toggled.');
    }
    redirect('/admin/coupons.php');
}

$coupons = $pdo->query('SELECT * FROM coupons ORDER BY created_at DESC')->fetchAll();
$editId = (int) ($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    $st = $pdo->prepare('SELECT * FROM coupons WHERE id = ?');
    $st->execute([$editId]);
    $edit = $st->fetch() ?: null;
}

$pageTitle = 'Coupons — Admin';
$adminPage = 'coupons';
require NB_ROOT . '/includes/admin_header.php';
?>
<h1 class="h3 mb-3"><i class="fa-solid fa-ticket me-2"></i>Coupons</h1>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="nb-panel table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Code</th><th>Type</th><th>Value</th><th>Used</th><th>Expiry</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($coupons as $c): ?>
                    <tr>
                        <td><strong><?= e($c['code']) ?></strong></td>
                        <td><?= e($c['discount_type']) ?></td>
                        <td><?= $c['discount_type'] === 'percentage' ? e($c['discount_value'] . '%') : e(money((float)$c['discount_value'])) ?></td>
                        <td><?= (int)$c['used_count'] ?> / <?= (int)$c['usage_limit'] ?: '∞' ?></td>
                        <td><?= e($c['expiry_date'] ? date('Y-m-d', strtotime($c['expiry_date'])) : '—') ?></td>
                        <td><?= status_badge($c['status']) ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-nb-outline" href="<?= e(url('/admin/coupons.php?edit=' . (int)$c['id'])) ?>">Edit</a>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button class="btn btn-sm btn-outline-secondary" type="submit">Toggle</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <form method="post" class="nb-panel">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <h2 class="h5 mb-3"><?= $edit ? 'Edit coupon' : 'Create coupon' ?></h2>
            <div class="mb-2"><input class="form-control" name="code" value="<?= e((string)($edit['code'] ?? '')) ?>" placeholder="CODE" required></div>
            <div class="mb-2">
                <select class="form-select" name="discount_type">
                    <option value="percentage" <?= ($edit['discount_type'] ?? '') === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                    <option value="fixed" <?= ($edit['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Fixed</option>
                </select>
            </div>
            <div class="mb-2"><input type="number" step="0.01" class="form-control" name="discount_value" value="<?= e((string)($edit['discount_value'] ?? '')) ?>" placeholder="Value" required></div>
            <div class="mb-2"><input type="number" step="0.01" class="form-control" name="minimum_amount" value="<?= e((string)($edit['minimum_amount'] ?? '0')) ?>" placeholder="Minimum amount"></div>
            <div class="mb-2"><input type="number" step="0.01" class="form-control" name="maximum_discount" value="<?= e((string)($edit['maximum_discount'] ?? '')) ?>" placeholder="Max discount (optional)"></div>
            <div class="mb-2"><input type="number" class="form-control" name="usage_limit" value="<?= e((string)($edit['usage_limit'] ?? '0')) ?>" placeholder="Usage limit (0 = unlimited)"></div>
            <div class="mb-2"><label class="small">Start</label><input type="datetime-local" class="form-control" name="start_date" value="<?= e($edit && $edit['start_date'] ? date('Y-m-d\TH:i', strtotime($edit['start_date'])) : '') ?>"></div>
            <div class="mb-2"><label class="small">Expiry</label><input type="datetime-local" class="form-control" name="expiry_date" value="<?= e($edit && $edit['expiry_date'] ? date('Y-m-d\TH:i', strtotime($edit['expiry_date'])) : '') ?>"></div>
            <div class="mb-2">
                <select class="form-select" name="status">
                    <option value="active" <?= ($edit['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <button class="btn btn-nb-primary w-100" type="submit">Save coupon</button>
        </form>
    </div>
</div>
<?php require NB_ROOT . '/includes/admin_footer.php'; ?>
