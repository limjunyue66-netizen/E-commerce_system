<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';

$pdo = db();
$q = trim((string) ($_GET['q'] ?? ''));
$category = (int) ($_GET['category'] ?? 0);
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;
$availability = (string) ($_GET['availability'] ?? '');
$minRating = (float) ($_GET['rating'] ?? 0);
$sort = (string) ($_GET['sort'] ?? 'newest');
$page = max(1, (int) ($_GET['page'] ?? 1));

$where = ["p.status = 'active'"];
$params = [];

if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}
if ($category > 0) {
    $where[] = 'p.category_id = ?';
    $params[] = $category;
}
if ($minPrice !== null) {
    $where[] = 'p.price >= ?';
    $params[] = $minPrice;
}
if ($maxPrice !== null) {
    $where[] = 'p.price <= ?';
    $params[] = $maxPrice;
}
if ($availability === 'in_stock') {
    $where[] = 'p.stock > 0';
} elseif ($availability === 'out_of_stock') {
    $where[] = 'p.stock <= 0';
}
if ($minRating > 0) {
    $where[] = "(SELECT AVG(rating) FROM product_reviews pr WHERE pr.product_id = p.id AND pr.status = 'approved') >= ?";
    $params[] = $minRating;
}

$orderBy = match ($sort) {
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'rating' => 'avg_rating DESC',
    'popular' => 'sold DESC',
    default => 'p.created_at DESC',
};

$whereSql = implode(' AND ', $where);
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$meta = paginate($total, $page, 6);

$sql = "SELECT p.*,
        COALESCE((SELECT AVG(rating) FROM product_reviews pr WHERE pr.product_id = p.id AND pr.status = 'approved'), 0) AS avg_rating,
        COALESCE((SELECT SUM(quantity) FROM order_items oi WHERE oi.product_id = p.id), 0) AS sold,
        (SELECT image_path FROM product_images pi WHERE pi.product_id = p.id ORDER BY display_order ASC, id ASC LIMIT 1) AS image_path
        FROM products p
        WHERE {$whereSql}
        ORDER BY {$orderBy}
        LIMIT {$meta['per_page']} OFFSET {$meta['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();
$categories = $pdo->query("SELECT id, name, icon FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();

$queryParams = $_GET;
unset($queryParams['page']);
$baseUrl = url('/public/shop.php') . ($queryParams ? ('?' . http_build_query($queryParams)) : '');

$pageTitle = 'Shop — ' . APP_NAME;
require NB_ROOT . '/includes/header.php';
?>
<div class="container pb-5">
    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-3">
        <div>
            <h1 class="nb-section-title mb-1"><i class="fa-solid fa-store me-2"></i>Shop</h1>
            <p class="text-muted mb-0"><?= (int) $total ?> products found<?= $q !== '' ? ' for “' . e($q) . '”' : '' ?></p>
        </div>
    </div>
    <div class="row g-3">
        <aside class="col-lg-3">
            <form class="nb-filters" method="get">
                <h5 class="mb-3"><i class="fa-solid fa-sliders me-2"></i>Filters</h5>
                <div class="mb-3">
                    <label class="nb-form-label">Search</label>
                    <input class="form-control" name="q" value="<?= e($q) ?>">
                </div>
                <div class="mb-3">
                    <label class="nb-form-label">Category</label>
                    <select class="form-select" name="category">
                        <option value="0">All categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= $category === (int) $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="nb-form-label">Min price</label>
                        <input type="number" step="0.01" class="form-control" name="min_price" value="<?= e((string) ($_GET['min_price'] ?? '')) ?>">
                    </div>
                    <div class="col-6">
                        <label class="nb-form-label">Max price</label>
                        <input type="number" step="0.01" class="form-control" name="max_price" value="<?= e((string) ($_GET['max_price'] ?? '')) ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="nb-form-label">Availability</label>
                    <select class="form-select" name="availability">
                        <option value="">Any</option>
                        <option value="in_stock" <?= $availability === 'in_stock' ? 'selected' : '' ?>>In stock</option>
                        <option value="out_of_stock" <?= $availability === 'out_of_stock' ? 'selected' : '' ?>>Out of stock</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="nb-form-label">Min rating</label>
                    <select class="form-select" name="rating">
                        <?php foreach ([0, 3, 4, 4.5] as $r): ?>
                            <option value="<?= $r ?>" <?= abs($minRating - $r) < 0.01 ? 'selected' : '' ?>><?= $r === 0 ? 'Any' : $r . '+' ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="nb-form-label">Sort by</label>
                    <select class="form-select" name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>Best rated</option>
                        <option value="popular" <?= $sort === 'popular' ? 'selected' : '' ?>>Popular</option>
                    </select>
                </div>
                <button class="btn btn-nb-primary w-100" type="submit"><i class="fa-solid fa-filter me-1"></i>Apply</button>
            </form>
        </aside>
        <div class="col-lg-9">
            <?php if (!$products): ?>
                <div class="nb-empty nb-panel">
                    <i class="fa-solid fa-box-open"></i>
                    <h3>No products found</h3>
                    <p>Try adjusting your filters or search keywords.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($products as $product): ?>
                        <div class="col-6 col-xl-4"><?php require NB_ROOT . '/includes/product_card.php'; ?></div>
                    <?php endforeach; ?>
                </div>
                <div class="mt-4"><?= pagination_links($meta, $baseUrl) ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require NB_ROOT . '/includes/footer.php'; ?>
