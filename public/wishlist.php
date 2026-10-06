<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_login();
$user = current_user();
if ($user['role'] !== 'user') {
    redirect_by_role($user);
}

$pdo = db();

if (is_post()) {
    require_csrf();
    $action = (string) request('action');
    $productId = (int) request('product_id');

    if ($action === 'add' && $productId > 0) {
        $exists = $pdo->prepare('SELECT id FROM products WHERE id = ? AND status = ?');
        $exists->execute([$productId, 'active']);
        if (!$exists->fetch()) {
            flash('danger', 'Product not available.');
            redirect('/public/wishlist.php');
        }
        try {
            $pdo->prepare('INSERT INTO wishlists (user_id, product_id, created_at) VALUES (?, ?, NOW())')
                ->execute([(int) $user['id'], $productId]);
            flash('success', 'Added to wishlist.');
        } catch (PDOException $e) {
            flash('info', 'Already in your wishlist.');
        }
        redirect('/public/wishlist.php');
    }

    if ($action === 'remove' && $productId > 0) {
        $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?')
            ->execute([(int) $user['id'], $productId]);
        flash('success', 'Removed from wishlist.');
        redirect('/public/wishlist.php');
    }

    if ($action === 'move_cart' && $productId > 0) {
        $result = add_to_cart((int) $user['id'], $productId, 1);
        if ($result['ok']) {
            $pdo->prepare('DELETE FROM wishlists WHERE user_id = ? AND product_id = ?')
                ->execute([(int) $user['id'], $productId]);
        }
        flash($result['ok'] ? 'success' : 'danger', $result['ok'] ? 'Moved to cart.' : $result['message']);
        redirect('/public/wishlist.php');
    }
}

$stmt = $pdo->prepare("SELECT w.*, p.name, p.slug, p.price, p.stock, p.status, p.compare_price,
    (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY display_order ASC, id ASC LIMIT 1) AS image_path
    FROM wishlists w
    INNER JOIN products p ON p.id = w.product_id
    WHERE w.user_id = ?
    ORDER BY w.created_at DESC");
$stmt->execute([(int) $user['id']]);
$items = $stmt->fetchAll();

$pageTitle = 'Wishlist — ' . APP_NAME;
require NB_ROOT . '/includes/header.php';
?>
<div class="container pb-5">
    <h1 class="nb-section-title mb-3"><i class="fa-solid fa-heart me-2"></i>Wishlist</h1>
    <?php if (!$items): ?>
        <div class="nb-empty nb-panel">
            <i class="fa-regular fa-heart"></i>
            <h3>No saved products yet</h3>
            <a class="btn btn-nb-primary" href="<?= e(url('/public/shop.php')) ?>">Explore shop</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($items as $item): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="nb-panel h-100">
                        <div class="d-flex gap-3">
                            <img src="<?= e($item['image_path'] ? upload_url($item['image_path']) : asset('images/placeholder-product.svg')) ?>" width="88" height="88" class="rounded-3 object-fit-cover" alt="">
                            <div class="flex-grow-1">
                                <a href="<?= e(url('/public/product.php?slug=' . urlencode($item['slug']))) ?>"><strong><?= e($item['name']) ?></strong></a>
                                <div class="nb-price"><?= e(money((float) $item['price'])) ?></div>
                                <?php if ($item['status'] !== 'active' || (int) $item['stock'] < 1): ?>
                                    <div class="nb-stock-out"><i class="fa-solid fa-triangle-exclamation"></i> Unavailable</div>
                                <?php else: ?>
                                    <div class="nb-stock-ok"><i class="fa-solid fa-circle-check"></i> Available</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex gap-2 mt-3">
                            <form method="post" class="flex-grow-1">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="move_cart">
                                <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                <button class="btn btn-nb-primary btn-sm w-100" type="submit" <?= ($item['status'] === 'active' && (int) $item['stock'] > 0) ? '' : 'disabled' ?>>
                                    <i class="fa-solid fa-cart-plus me-1"></i>Move to cart
                                </button>
                            </form>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= (int) $item['product_id'] ?>">
                                <button class="btn btn-outline-danger btn-sm" type="submit"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php require NB_ROOT . '/includes/footer.php'; ?>
