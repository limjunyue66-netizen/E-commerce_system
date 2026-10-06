<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$id = (int) ($_GET['id'] ?? 0);
$pdo = db();

if ($slug !== '') {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.slug = ? AND p.status = 'active' LIMIT 1");
    $stmt->execute([$slug]);
} else {
    $stmt = $pdo->prepare("SELECT p.*, c.name AS category_name
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.id = ? AND p.status = 'active' LIMIT 1");
    $stmt->execute([$id]);
}
$product = $stmt->fetch();
if (!$product) {
    flash('danger', 'Product not found.');
    redirect('/public/shop.php');
}

$images = product_images((int) $product['id']);
$rating = average_rating((int) $product['id']);
$reviews = $pdo->prepare("SELECT r.*, u.name AS customer_name FROM product_reviews r INNER JOIN users u ON u.id = r.user_id WHERE r.product_id = ? AND r.status = 'approved' ORDER BY r.created_at DESC");
$reviews->execute([(int) $product['id']]);
$reviewRows = $reviews->fetchAll();

$related = $pdo->prepare("SELECT p.*, (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY display_order ASC, id ASC LIMIT 1) AS image_path
    FROM products p WHERE p.category_id = ? AND p.id <> ? AND p.status = 'active' ORDER BY p.featured DESC LIMIT 4");
$related->execute([(int) $product['category_id'], (int) $product['id']]);
$relatedProducts = $related->fetchAll();

$user = current_user();
$canReview = false;
$existingReview = null;
if ($user && $user['role'] === 'user') {
    $check = $pdo->prepare("SELECT oi.order_id FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status IN ('paid','processing','shipped','completed') LIMIT 1");
    $check->execute([(int) $user['id'], (int) $product['id']]);
    $purchaseOrderId = $check->fetchColumn();
    if ($purchaseOrderId) {
        $canReview = true;
        $er = $pdo->prepare('SELECT * FROM product_reviews WHERE user_id = ? AND product_id = ? LIMIT 1');
        $er->execute([(int) $user['id'], (int) $product['id']]);
        $existingReview = $er->fetch() ?: null;
    }
}

if (is_post() && (string) request('action') === 'review') {
    require_login();
    require_csrf();
    $user = current_user();
    $ratingVal = (int) request('rating');
    $comment = trim((string) request('comment'));
    if ($ratingVal < 1 || $ratingVal > 5 || $comment === '') {
        flash('danger', 'Please provide a rating and comment.');
        redirect('/public/product.php?slug=' . urlencode($product['slug']));
    }
    $check = $pdo->prepare("SELECT oi.order_id FROM order_items oi INNER JOIN orders o ON o.id = oi.order_id
        WHERE o.user_id = ? AND oi.product_id = ? AND o.order_status IN ('paid','processing','shipped','completed') ORDER BY o.id DESC LIMIT 1");
    $check->execute([(int) $user['id'], (int) $product['id']]);
    $orderId = (int) $check->fetchColumn();
    if (!$orderId) {
        flash('danger', 'Only purchasers can review this product.');
        redirect('/public/product.php?slug=' . urlencode($product['slug']));
    }
    $exists = $pdo->prepare('SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ? LIMIT 1');
    $exists->execute([(int) $user['id'], (int) $product['id']]);
    $rid = $exists->fetchColumn();
    if ($rid) {
        $pdo->prepare("UPDATE product_reviews SET rating = ?, comment = ?, order_id = ?, updated_at = NOW(), status = 'approved' WHERE id = ?")
            ->execute([$ratingVal, $comment, $orderId, $rid]);
        flash('success', 'Review updated.');
    } else {
        $pdo->prepare("INSERT INTO product_reviews (product_id, user_id, order_id, rating, comment, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'approved', NOW(), NOW())")
            ->execute([(int) $product['id'], (int) $user['id'], $orderId, $ratingVal, $comment]);
        flash('success', 'Review submitted.');
    }
    redirect('/public/product.php?slug=' . urlencode($product['slug']));
}

$inStock = (int) $product['stock'] > 0;
$pageTitle = $product['name'] . ' — ' . APP_NAME;
$extraJs = ['cart.js'];
require NB_ROOT . '/includes/header.php';
$mainImage = $images ? upload_url($images[0]['image_path']) : asset('images/placeholder-product.svg');
?>
<div class="container pb-5">
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="nb-gallery-main mb-3"><img src="<?= e($mainImage) ?>" alt="<?= e($product['name']) ?>"></div>
            <?php if (count($images) > 1): ?>
                <div class="d-flex gap-2 flex-wrap nb-gallery-thumbs">
                    <?php foreach ($images as $i => $img): ?>
                        <img class="nb-thumb <?= $i === 0 ? 'active' : '' ?>" src="<?= e(upload_url($img['image_path'])) ?>" data-full="<?= e(upload_url($img['image_path'])) ?>" alt="">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="col-lg-6">
            <div class="nb-panel">
                <div class="text-muted mb-2">
                    <i class="fa-solid fa-tag me-1"></i><?= e($product['category_name']) ?>
                </div>
                <h1 class="nb-section-title"><?= e($product['name']) ?></h1>
                <div class="mb-2"><?= render_stars($rating['avg']) ?> <span class="text-muted">(<?= (int) $rating['count'] ?> reviews)</span></div>
                <div class="mb-3">
                    <span class="nb-price fs-3"><?= e(money((float) $product['price'])) ?></span>
                    <?php if (!empty($product['compare_price']) && (float) $product['compare_price'] > (float) $product['price']): ?>
                        <span class="nb-price-compare fs-5"><?= e(money((float) $product['compare_price'])) ?></span>
                    <?php endif; ?>
                </div>
                <p class="mb-2"><strong>SKU:</strong> <?= e($product['sku']) ?></p>
                <p class="<?= $inStock ? 'nb-stock-ok' : 'nb-stock-out' ?>">
                    <i class="fa-solid <?= $inStock ? 'fa-circle-check' : 'fa-ban' ?>"></i>
                    <?= $inStock ? ('In stock (' . (int) $product['stock'] . ' available)') : 'Out of Stock' ?>
                </p>
                <p><?= nl2br(e($product['description'])) ?></p>
                <form class="row g-2 align-items-end" method="post" action="<?= e(url('/public/cart.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <div class="col-auto">
                        <label class="nb-form-label">Qty</label>
                        <div class="input-group" style="width:140px">
                            <button class="btn btn-outline-secondary nb-qty-btn" type="button" data-dir="down" data-target="#qtyInput">-</button>
                            <input id="qtyInput" class="form-control text-center" type="number" name="quantity" value="1" min="1" max="<?= max(1, (int) $product['stock']) ?>">
                            <button class="btn btn-outline-secondary nb-qty-btn" type="button" data-dir="up" data-target="#qtyInput">+</button>
                        </div>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-nb-primary" type="submit" <?= $inStock ? '' : 'disabled' ?>><i class="fa-solid fa-cart-plus me-1"></i>Add to Cart</button>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-nb-accent" name="action" value="buy_now" formaction="<?= e(url('/public/checkout.php')) ?>" formmethod="post" <?= $inStock ? '' : 'disabled' ?>>
                            <i class="fa-solid fa-bolt me-1"></i>Buy Now
                        </button>
                    </div>
                </form>
                <form class="mt-3" method="post" action="<?= e(url('/public/wishlist.php')) ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <button class="btn btn-nb-outline" type="submit"><i class="fa-solid fa-heart me-1"></i>Add to Wishlist</button>
                </form>
            </div>
        </div>
    </div>

    <div class="nb-panel mt-4">
        <h2 class="h4 mb-3"><i class="fa-solid fa-comments me-2"></i>Reviews</h2>
        <?php if (!$reviewRows): ?>
            <p class="text-muted">No reviews yet.</p>
        <?php else: ?>
            <?php foreach ($reviewRows as $rev): ?>
                <div class="border-bottom py-3">
                    <strong><?= e($rev['customer_name']) ?></strong>
                    <?= render_stars((float) $rev['rating']) ?>
                    <div class="small text-muted"><?= e(date('M j, Y', strtotime($rev['created_at']))) ?></div>
                    <p class="mb-0 mt-1"><?= nl2br(e($rev['comment'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if ($canReview): ?>
            <form method="post" class="mt-4">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="review">
                <h3 class="h5"><?= $existingReview ? 'Edit your review' : 'Write a review' ?></h3>
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="nb-form-label">Rating</label>
                        <select class="form-select" name="rating" required>
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>" <?= (int) ($existingReview['rating'] ?? 5) === $i ? 'selected' : '' ?>><?= $i ?> stars</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-9">
                        <label class="nb-form-label">Comment</label>
                        <textarea class="form-control" name="comment" rows="3" required><?= e((string) ($existingReview['comment'] ?? '')) ?></textarea>
                    </div>
                </div>
                <button class="btn btn-nb-primary mt-3" type="submit">Save review</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if ($relatedProducts): ?>
        <div class="mt-4">
            <h2 class="nb-section-title"><i class="fa-solid fa-layer-group me-2"></i>Related products</h2>
            <div class="row g-3">
                <?php foreach ($relatedProducts as $product): ?>
                    <div class="col-6 col-lg-3"><?php require NB_ROOT . '/includes/product_card.php'; ?></div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require NB_ROOT . '/includes/footer.php'; ?>
