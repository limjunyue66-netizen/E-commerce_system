<?php
declare(strict_types=1);
require_once __DIR__ . '/config/bootstrap.php';

$pageTitle = APP_NAME . ' — Modern Marketplace';
$pdo = db();

$categories = $pdo->query("SELECT * FROM categories WHERE status = 'active' ORDER BY name ASC LIMIT 6")->fetchAll();
$featured = $pdo->query("SELECT p.*, (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY display_order ASC, id ASC LIMIT 1) AS image_path
    FROM products p WHERE p.status = 'active' AND p.featured = 1 ORDER BY p.created_at DESC LIMIT 6")->fetchAll();
$newArrivals = $pdo->query("SELECT p.*, (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY display_order ASC, id ASC LIMIT 1) AS image_path
    FROM products p WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 6")->fetchAll();
$bestSellers = $pdo->query("SELECT p.*, COALESCE(SUM(oi.quantity),0) AS sold,
    (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY display_order ASC, id ASC LIMIT 1) AS image_path
    FROM products p
    LEFT JOIN order_items oi ON oi.product_id = p.id
    WHERE p.status = 'active'
    GROUP BY p.id
    ORDER BY sold DESC, p.id ASC
    LIMIT 6")->fetchAll();

require NB_ROOT . '/includes/header.php';
?>
<section class="nb-hero">
    <div class="container">
        <div class="nb-hero-inner nb-animate">
            <div class="eyebrow"><i class="fa-solid fa-sparkles"></i> NovaBazaar Marketplace</div>
            <h1>Shop smarter every day</h1>
            <p>Discover curated gadgets, fashion, home essentials, and outdoor gear — with secure checkout and real inventory control.</p>
            <div class="nb-hero-actions">
                <a class="btn btn-nb-accent btn-lg" href="<?= e(url('/public/shop.php')) ?>"><i class="fa-solid fa-bag-shopping me-2"></i>Start Shopping</a>
                <a class="btn btn-light btn-lg" href="<?= e(url('/public/register.php')) ?>"><i class="fa-solid fa-user-plus me-2"></i>Create Account</a>
            </div>
        </div>
    </div>
</section>

<section class="nb-section">
    <div class="container">
        <h2 class="nb-section-title"><i class="fa-solid fa-table-cells-large me-2"></i>Featured Categories</h2>
        <p class="nb-section-sub">Browse by collection and find what you need faster.</p>
        <div class="nb-cat-grid">
            <?php foreach ($categories as $i => $cat): ?>
                <a class="nb-cat-tile nb-animate nb-animate-delay-<?= min(3, $i % 4) ?>" href="<?= e(url('/public/shop.php?category=' . (int) $cat['id'])) ?>">
                    <div class="nb-cat-icon"><i class="fa-solid <?= e($cat['icon'] ?: category_icon($cat['name'])) ?>"></i></div>
                    <strong><?= e($cat['name']) ?></strong>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="nb-section pt-0">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-3">
            <div>
                <h2 class="nb-section-title mb-1"><i class="fa-solid fa-star me-2"></i>Featured Products</h2>
                <p class="nb-section-sub mb-0">Handpicked highlights from the marketplace.</p>
            </div>
            <a class="btn btn-nb-outline" href="<?= e(url('/public/shop.php')) ?>">View all</a>
        </div>
        <div class="row g-3">
            <?php foreach ($featured as $product): ?>
                <div class="col-6 col-md-4 col-xl-3"><?php require NB_ROOT . '/includes/product_card.php'; ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="nb-section pt-0">
    <div class="container">
        <div class="nb-promo nb-animate">
            <div class="row align-items-center g-3">
                <div class="col-lg-8">
                    <h2 class="mb-2"><i class="fa-solid fa-ticket me-2"></i>Welcome offer: WELCOME10</h2>
                    <p class="mb-0">Save 10% on your first eligible order over $50. Limited marketplace promotion.</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a class="btn btn-nb-accent btn-lg" href="<?= e(url('/public/shop.php')) ?>">Claim in Cart</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="nb-section pt-0">
    <div class="container">
        <h2 class="nb-section-title"><i class="fa-solid fa-clock me-2"></i>New Arrivals</h2>
        <p class="nb-section-sub">Fresh listings from the NovaBazaar catalog.</p>
        <div class="row g-3">
            <?php foreach ($newArrivals as $product): ?>
                <div class="col-6 col-md-4 col-xl-3"><?php require NB_ROOT . '/includes/product_card.php'; ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="nb-section pt-0">
    <div class="container">
        <h2 class="nb-section-title"><i class="fa-solid fa-fire me-2"></i>Best Sellers</h2>
        <p class="nb-section-sub">Popular products based on actual order volume.</p>
        <div class="row g-3">
            <?php foreach ($bestSellers as $product): ?>
                <div class="col-6 col-md-4 col-xl-3"><?php require NB_ROOT . '/includes/product_card.php'; ?></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php require NB_ROOT . '/includes/footer.php'; ?>
