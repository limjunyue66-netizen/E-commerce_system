<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
$pdo = db();

if (is_post()) {
    require_csrf();
    $id = (int) request('id');
    $action = (string) request('action');
    if ($action === 'hide') {
        $pdo->prepare("UPDATE product_reviews SET status = 'hidden', updated_at = NOW() WHERE id = ?")->execute([$id]);
        flash('success', 'Review hidden.');
    }
    if ($action === 'approve') {
        $pdo->prepare("UPDATE product_reviews SET status = 'approved', updated_at = NOW() WHERE id = ?")->execute([$id]);
        flash('success', 'Review approved.');
    }
    if ($action === 'delete') {
        $pdo->prepare('DELETE FROM product_reviews WHERE id = ?')->execute([$id]);
        flash('success', 'Review deleted.');
    }
    redirect('/admin/reviews.php');
}

$reviews = $pdo->query("SELECT r.*, p.name AS product_name, u.name AS customer_name
    FROM product_reviews r
    INNER JOIN products p ON p.id = r.product_id
    INNER JOIN users u ON u.id = r.user_id
    ORDER BY r.created_at DESC")->fetchAll();

$pageTitle = 'Reviews — Admin';
$adminPage = 'reviews';
require NB_ROOT . '/includes/admin_header.php';
?>
<h1 class="h3 mb-3"><i class="fa-solid fa-star me-2"></i>Reviews</h1>
<div class="nb-panel table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Product</th><th>Customer</th><th>Rating</th><th>Comment</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($reviews as $r): ?>
            <tr>
                <td><?= e($r['product_name']) ?></td>
                <td><?= e($r['customer_name']) ?></td>
                <td><?= render_stars((float) $r['rating']) ?></td>
                <td style="max-width:280px"><?= e($r['comment']) ?></td>
                <td><?= status_badge($r['status']) ?></td>
                <td class="text-nowrap">
                    <form method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <?php if ($r['status'] === 'approved'): ?>
                            <button class="btn btn-sm btn-outline-secondary" name="action" value="hide" type="submit">Hide</button>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-success" name="action" value="approve" type="submit">Approve</button>
                        <?php endif; ?>
                        <button class="btn btn-sm btn-outline-danger" name="action" value="delete" type="submit" data-confirm="Delete review?">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require NB_ROOT . '/includes/admin_footer.php'; ?>
