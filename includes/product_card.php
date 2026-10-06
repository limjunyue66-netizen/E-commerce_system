<?php
declare(strict_types=1);
/** @var array $product */
$img = !empty($product['image_path']) ? upload_url($product['image_path']) : product_primary_image((int) $product['id']);
$rating = average_rating((int) $product['id']);
$inStock = (int) $product['stock'] > 0;
?>
<article class="nb-product-card">
    <a class="nb-product-media" href="<?= e(url('/public/product.php?slug=' . urlencode($product['slug']))) ?>">
        <img src="<?= e($img) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
    </a>
    <div class="nb-product-body">
        <a href="<?= e(url('/public/product.php?slug=' . urlencode($product['slug']))) ?>"><h3><?= e($product['name']) ?></h3></a>
        <div><?= render_stars($rating['avg']) ?> <small class="text-muted">(<?= (int) $rating['count'] ?>)</small></div>
        <div>
            <span class="nb-price"><?= e(money((float) $product['price'])) ?></span>
            <?php if (!empty($product['compare_price']) && (float) $product['compare_price'] > (float) $product['price']): ?>
                <span class="nb-price-compare"><?= e(money((float) $product['compare_price'])) ?></span>
            <?php endif; ?>
        </div>
        <div class="<?= $inStock ? 'nb-stock-ok' : 'nb-stock-out' ?>">
            <i class="fa-solid <?= $inStock ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
            <?= $inStock ? 'In stock' : 'Out of stock' ?>
        </div>
        <div class="nb-card-actions">
            <form method="post" action="<?= e(url('/public/cart.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="quantity" value="1">
                <button class="btn btn-nb-primary btn-sm w-100" type="submit" <?= $inStock ? '' : 'disabled' ?>>
                    <i class="fa-solid fa-cart-plus me-1"></i>Cart
                </button>
            </form>
            <form method="post" action="<?= e(url('/public/wishlist.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <button class="btn btn-nb-outline btn-sm w-100" type="submit" title="Wishlist">
                    <i class="fa-solid fa-heart"></i>
                </button>
            </form>
        </div>
    </div>
</article>
