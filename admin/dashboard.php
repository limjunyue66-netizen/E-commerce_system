<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
$user = current_user();

if (!empty($_SESSION['must_change_password']) || (!empty($user['must_change_password']) && FORCE_PASSWORD_CHANGE)) {
    if (!(is_post() && (string) request('action') === 'force_password')) {
        // show force password form below
    }
}

if (is_post() && (string) request('action') === 'force_password') {
    require_csrf();
    $new = (string) request('new_password');
    $confirm = (string) request('confirm_password');
    $err = validate_password_strength($new);
    if ($err || $new !== $confirm) {
        flash('danger', $err ?: 'Passwords do not match.');
        redirect('/admin/dashboard.php');
    }
    db()->prepare('UPDATE users SET password = ?, must_change_password = 0, updated_at = NOW() WHERE id = ?')
        ->execute([password_hash($new, PASSWORD_DEFAULT), (int) $user['id']]);
    $_SESSION['must_change_password'] = false;
    flash('success', 'Password updated. You can continue.');
    redirect('/admin/dashboard.php');
}

$pdo = db();
$stats = [
    'users' => (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn(),
    'products' => (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'orders' => (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'pending' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status IN ('pending','paid','processing')")->fetchColumn(),
    'completed' => (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'completed'")->fetchColumn(),
    'revenue' => (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE payment_status = 'paid' AND order_status <> 'cancelled'")->fetchColumn(),
];

$monthlyRaw = $pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COALESCE(SUM(total_amount),0) AS total
    FROM orders WHERE payment_status = 'paid' AND order_status <> 'cancelled' AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MONTH)
    GROUP BY ym ORDER BY ym ASC")->fetchAll(PDO::FETCH_ASSOC);
$monthlyMap = [];
foreach ($monthlyRaw as $row) {
    $monthlyMap[$row['ym']] = (float) $row['total'];
}
$monthly = [];
for ($i = 5; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime("-{$i} month"));
    $monthly[] = [
        'ym' => $ym,
        'label' => date('M', strtotime($ym . '-01')),
        'total' => $monthlyMap[$ym] ?? 0.0,
    ];
}
$maxMonth = max(array_map(fn($r) => (float) $r['total'], $monthly) ?: [1.0]);
if ($maxMonth <= 0) {
    $maxMonth = 1.0;
}

$statusRows = $pdo->query("SELECT order_status, COUNT(*) AS c FROM orders GROUP BY order_status")->fetchAll();
$recent = $pdo->query("SELECT o.*, u.name AS customer_name FROM orders o INNER JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC LIMIT 10")->fetchAll();

$pageTitle = 'Admin Dashboard — ' . APP_NAME;
$bodyClass = 'nb-admin';
$extraCss = ['admin.css'];
$extraJs = ['admin.js'];
$adminPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/admin.css')) ?>" rel="stylesheet">
</head>
<body class="nb-admin">
<div class="nb-admin-shell">
    <?php require NB_ROOT . '/includes/admin_navbar.php'; ?>
    <div class="nb-admin-content">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
        <?php endforeach; ?>

        <?php if (!empty($user['must_change_password']) && FORCE_PASSWORD_CHANGE): ?>
            <div class="nb-panel mb-4 border border-warning">
                <h2 class="h5"><i class="fa-solid fa-shield-halved me-2"></i>Change default admin password</h2>
                <p class="text-muted">For security, update the development password before using the admin panel.</p>
                <form method="post" class="row g-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="force_password">
                    <div class="col-md-4"><input type="password" class="form-control" name="new_password" placeholder="New password" required></div>
                    <div class="col-md-4"><input type="password" class="form-control" name="confirm_password" placeholder="Confirm password" required></div>
                    <div class="col-md-4"><button class="btn btn-nb-primary w-100" type="submit">Update password</button></div>
                </form>
            </div>
        <?php endif; ?>

        <h1 class="h3 mb-3"><i class="fa-solid fa-gauge me-2"></i>Dashboard</h1>
        <div class="row g-3 mb-4">
            <?php
            $cards = [
                ['Users', $stats['users'], 'fa-users'],
                ['Products', $stats['products'], 'fa-boxes-stacked'],
                ['Orders', $stats['orders'], 'fa-receipt'],
                ['Pending', $stats['pending'], 'fa-hourglass-half'],
                ['Completed', $stats['completed'], 'fa-circle-check'],
                ['Revenue', money($stats['revenue']), 'fa-chart-line'],
            ];
            foreach ($cards as $c): ?>
                <div class="col-6 col-lg-3">
                    <div class="nb-stat-card">
                        <div class="icon"><i class="fa-solid <?= e($c[2]) ?>"></i></div>
                        <h3><?= e((string) $c[1]) ?></h3>
                        <p><?= e($c[0]) ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-lg-7">
                <div class="nb-panel">
                    <h2 class="h5 mb-3">Sales by month</h2>
                    <div class="nb-sales-chart">
                        <?php foreach ($monthly as $m): ?>
                            <?php
                            $pct = ((float) $m['total'] / $maxMonth) * 100;
                            $barPx = (int) max(6, round(($pct / 100) * 140));
                            ?>
                            <div class="nb-sales-col">
                                <div class="nb-sales-amount"><?= e($m['total'] > 0 ? money((float) $m['total']) : '—') ?></div>
                                <div class="nb-sales-track">
                                    <div class="nb-chart-bar" style="height: <?= $barPx ?>px" title="<?= e(money((float) $m['total'])) ?>"></div>
                                </div>
                                <div class="nb-sales-label"><?= e($m['label']) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="nb-panel">
                    <h2 class="h5 mb-3">Orders by status</h2>
                    <?php foreach ($statusRows as $row): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span><?= status_badge($row['order_status']) ?></span>
                            <strong><?= (int) $row['c'] ?></strong>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="nb-panel table-responsive">
            <h2 class="h5 mb-3">Recent orders</h2>
            <table class="table align-middle">
                <thead><tr><th>Order</th><th>Customer</th><th>Amount</th><th>Payment</th><th>Status</th><th>Date</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($recent as $order): ?>
                    <tr>
                        <td><?= e($order['order_number']) ?></td>
                        <td><?= e($order['customer_name']) ?></td>
                        <td><?= e(money((float) $order['total_amount'])) ?></td>
                        <td><?= status_badge($order['payment_status']) ?></td>
                        <td><?= status_badge($order['order_status']) ?></td>
                        <td><?= e(date('M j, Y', strtotime($order['created_at']))) ?></td>
                        <td><a class="btn btn-sm btn-nb-outline" href="<?= e(url('/admin/order_view.php?id=' . (int) $order['id'])) ?>">View</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/admin.js')) ?>"></script>
</body>
</html>
