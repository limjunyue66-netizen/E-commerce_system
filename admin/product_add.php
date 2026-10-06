<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
$pdo = db();
$categories = $pdo->query("SELECT id, name FROM categories WHERE status = 'active' ORDER BY name")->fetchAll();
$adminId = admin_user_id();

if (is_post()) {
    require_csrf();
    $name = trim((string) request('name'));
    $description = trim((string) request('description'));
    $price = (float) request('price');
    $compare = request('compare_price') !== '' ? (float) request('compare_price') : null;
    $stock = max(0, (int) request('stock'));
    $sku = trim((string) request('sku'));
    $categoryId = (int) request('category_id');
    $featured = (int) request('featured') === 1 ? 1 : 0;
    if ($adminId <= 0 || $name === '' || $sku === '' || $price <= 0) {
        flash('danger', 'Invalid product data.');
        redirect('/admin/product_add.php');
    }
    $slug = unique_slug($name);
    $pdo->prepare("INSERT INTO products (seller_id, category_id, name, slug, description, price, compare_price, stock, sku, status, featured, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, NOW(), NOW())")
        ->execute([$adminId, $categoryId, $name, $slug, $description, $price, $compare, $stock, $sku, $featured]);
    $productId = (int) $pdo->lastInsertId();
    $hasImage = false;
    $order = 0;
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $i => $n) {
            $file = ['name'=>$n,'type'=>$_FILES['images']['type'][$i],'tmp_name'=>$_FILES['images']['tmp_name'][$i],'error'=>$_FILES['images']['error'][$i],'size'=>$_FILES['images']['size'][$i]];
            $up = handle_image_upload($file, 'products');
            if ($up['ok']) {
                $pdo->prepare('INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, ?, NOW())')
                    ->execute([$productId, $up['path'], $order++]);
                $hasImage = true;
            }
        }
    }
    $imageUrl = trim((string) request('image_url'));
    if ($imageUrl !== '') {
        $fromUrl = handle_image_from_url($imageUrl, 'products');
        if ($fromUrl['ok']) {
            $pdo->prepare('INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, ?, NOW())')
                ->execute([$productId, $fromUrl['path'], $order]);
            $hasImage = true;
        } else {
            flash('warning', $fromUrl['message']);
        }
    }
    if (!$hasImage) {
        $pdo->prepare('INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, 0, NOW())')
            ->execute([$productId, 'products/placeholder-1.svg']);
    }
    flash('success', 'Product created.');
    redirect('/admin/products.php');
}

$pageTitle = 'Add Product — Admin';
$adminPage = 'products';
require NB_ROOT . '/includes/admin_header.php';
?>
<h1 class="h3 mb-3">Add Product</h1>
<form method="post" enctype="multipart/form-data" class="nb-panel">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="nb-form-label">Category</label>
            <select class="form-select" name="category_id" required>
                <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8"><label class="nb-form-label">Name</label><input class="form-control" name="name" required></div>
        <div class="col-md-4"><label class="nb-form-label">SKU</label><input class="form-control" name="sku" required></div>
        <div class="col-md-4"><label class="nb-form-label">Price</label><input type="number" step="0.01" class="form-control" name="price" required></div>
        <div class="col-md-4"><label class="nb-form-label">Compare price</label><input type="number" step="0.01" class="form-control" name="compare_price"></div>
        <div class="col-md-4"><label class="nb-form-label">Stock</label><input type="number" class="form-control" name="stock" value="10" required></div>
        <div class="col-12"><label class="nb-form-label">Description</label><textarea class="form-control" name="description" rows="4" required></textarea></div>
        <div class="col-md-6">
            <label class="nb-form-label">Upload image file</label>
            <input type="file" class="form-control" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple>
        </div>
        <div class="col-md-6">
            <label class="nb-form-label">Or paste image URL</label>
            <input type="url" class="form-control" name="image_url" placeholder="https://example.com/photo.jpg">
            <div class="form-text">Direct JPG/PNG/WEBP link will be downloaded to the server.</div>
        </div>
        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="featured" value="1" id="f"><label class="form-check-label" for="f">Featured</label></div></div>
    </div>
    <button class="btn btn-nb-primary mt-3" type="submit">Create</button>
</form>
<?php require NB_ROOT . '/includes/admin_footer.php'; ?>
