<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
$pdo = db();

if (is_post()) {
    require_csrf();
    $id = (int) request('product_id');
    $action = (string) request('action');
    $product = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $product->execute([$id]);
    $p = $product->fetch();
    if (!$p) {
        flash('danger', 'Product not found.');
        redirect('/admin/products.php');
    }
    if ($action === 'toggle') {
        $new = $p['status'] === 'active' ? 'inactive' : 'active';
        $pdo->prepare('UPDATE products SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$new, $id]);
        flash('success', 'Status updated.');
    }
    if ($action === 'feature') {
        $pdo->prepare('UPDATE products SET featured = IF(featured=1,0,1), updated_at = NOW() WHERE id = ?')->execute([$id]);
        flash('success', 'Featured flag updated.');
    }
    if ($action === 'archive') {
        $has = $pdo->prepare('SELECT id FROM order_items WHERE product_id = ? LIMIT 1');
        $has->execute([$id]);
        if ($has->fetch()) {
            $pdo->prepare("UPDATE products SET status = 'archived', updated_at = NOW() WHERE id = ?")->execute([$id]);
            flash('success', 'Product archived.');
        } else {
            $pdo->prepare('DELETE FROM product_images WHERE product_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            flash('success', 'Product deleted.');
        }
    }
    redirect('/admin/products.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.sku LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
}
if (in_array($status, ['active', 'inactive', 'archived'], true)) {
    $where[] = 'p.status = ?';
    $params[] = $status;
}
$sql = "SELECT p.*, c.name AS category_name FROM products p
    INNER JOIN categories c ON c.id = p.category_id
    WHERE " . implode(' AND ', $where) . " ORDER BY p.updated_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Products — Admin';
$adminPage = 'products';
require NB_ROOT . '/includes/admin_header.php';
?>
<div class="d-flex justify-content-between mb-3">
    <h1 class="h3 mb-0"><i class="fa-solid fa-boxes-stacked me-2"></i>Products</h1>
    <a class="btn btn-nb-primary" href="<?= e(url('/admin/product_add.php')) ?>"><i class="fa-solid fa-plus me-1"></i>Add</a>
</div>
<form class="row g-2 mb-3" method="get">
    <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search name or SKU"></div>
    <div class="col-md-3">
        <select class="form-select" name="status">
            <option value="">All statuses</option>
            <?php foreach (['active','inactive','archived'] as $st): ?>
                <option value="<?= $st ?>" <?= $status === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-nb-primary w-100">Filter</button></div>
</form>
<div class="nb-panel table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Product</th><th>Price</th><th>Stock</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><strong><?= e($p['name']) ?></strong><div class="small text-muted"><?= e($p['sku']) ?> · <?= e($p['category_name']) ?><?= (int)$p['featured'] ? ' · Featured' : '' ?></div></td>
                <td><?= e(money((float) $p['price'])) ?></td>
                <td><?= (int) $p['stock'] ?></td>
                <td><?= status_badge($p['status']) ?></td>
                <td class="text-nowrap">
                    <a class="btn btn-sm btn-nb-outline" href="<?= e(url('/admin/product_edit.php?id=' . (int) $p['id'])) ?>"><i class="fa-solid fa-pen"></i></a>
                    <form method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $p['id'] ?>">
                        <button class="btn btn-sm btn-outline-secondary" name="action" value="toggle" type="submit"><i class="fa-solid fa-toggle-on"></i></button>
                        <button class="btn btn-sm btn-outline-warning" name="action" value="feature" type="submit"><i class="fa-solid fa-star"></i></button>
                        <button class="btn btn-sm btn-outline-danger" name="action" value="archive" type="submit" data-confirm="Archive/delete product?"><i class="fa-solid fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require NB_ROOT . '/includes/admin_footer.php'; ?>
