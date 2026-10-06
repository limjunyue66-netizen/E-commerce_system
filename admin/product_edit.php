<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
$pdo = db();
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) {
    flash('danger', 'Product not found.');
    redirect('/admin/products.php');
}
$categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
$images = product_images($id);
$adminId = admin_user_id();

if (is_post()) {
    require_csrf();
    $name = trim((string) request('name'));
    $slug = unique_slug($name, 'products', $id);
    $pdo->prepare('UPDATE products SET seller_id=?, category_id=?, name=?, slug=?, description=?, price=?, compare_price=?, stock=?, sku=?, status=?, featured=?, updated_at=NOW() WHERE id=?')
        ->execute([
            $adminId,
            (int) request('category_id'),
            $name,
            $slug,
            trim((string) request('description')),
            (float) request('price'),
            request('compare_price') !== '' ? (float) request('compare_price') : null,
            max(0, (int) request('stock')),
            trim((string) request('sku')),
            in_array(request('status'), ['active','inactive','archived'], true) ? request('status') : 'active',
            (int) request('featured') === 1 ? 1 : 0,
            $id,
        ]);
    $orderBase = count($images);
    if (!empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $i => $n) {
            $file = ['name'=>$n,'type'=>$_FILES['images']['type'][$i],'tmp_name'=>$_FILES['images']['tmp_name'][$i],'error'=>$_FILES['images']['error'][$i],'size'=>$_FILES['images']['size'][$i]];
            $up = handle_image_upload($file, 'products');
            if ($up['ok']) {
                $pdo->prepare('INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, ?, NOW())')
                    ->execute([$id, $up['path'], $orderBase + $i]);
                $orderBase++;
            } else {
                flash('warning', $up['message']);
            }
        }
    }

    $imageUrl = trim((string) request('image_url'));
    if ($imageUrl !== '') {
        $fromUrl = handle_image_from_url($imageUrl, 'products');
        if ($fromUrl['ok']) {
            $pdo->prepare('INSERT INTO product_images (product_id, image_path, display_order, created_at) VALUES (?, ?, ?, NOW())')
                ->execute([$id, $fromUrl['path'], $orderBase]);
        } else {
            flash('warning', $fromUrl['message']);
        }
    }

    flash('success', 'Product updated.');
    redirect('/admin/product_edit.php?id=' . $id);
}

$pageTitle = 'Edit Product — Admin';
$adminPage = 'products';
require NB_ROOT . '/includes/admin_header.php';
?>
<h1 class="h3 mb-3">Edit Product</h1>
<form method="post" enctype="multipart/form-data" class="nb-panel">
    <?= csrf_field() ?>
    <div class="row g-3">
        <div class="col-md-6">
            <label class="nb-form-label">Category</label>
            <select class="form-select" name="category_id">
                <?php foreach ($categories as $c): ?>
                    <option value="<?= (int) $c['id'] ?>" <?= (int)$product['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-8"><label class="nb-form-label">Name</label><input class="form-control" name="name" value="<?= e($product['name']) ?>" required></div>
        <div class="col-md-4"><label class="nb-form-label">SKU</label><input class="form-control" name="sku" value="<?= e($product['sku']) ?>" required></div>
        <div class="col-md-3"><label class="nb-form-label">Price</label><input type="number" step="0.01" class="form-control" name="price" value="<?= e((string)$product['price']) ?>" required></div>
        <div class="col-md-3"><label class="nb-form-label">Compare</label><input type="number" step="0.01" class="form-control" name="compare_price" value="<?= e((string)($product['compare_price'] ?? '')) ?>"></div>
        <div class="col-md-3"><label class="nb-form-label">Stock</label><input type="number" class="form-control" name="stock" value="<?= (int)$product['stock'] ?>" required></div>
        <div class="col-md-3">
            <label class="nb-form-label">Status</label>
            <select class="form-select" name="status">
                <?php foreach (['active','inactive','archived'] as $st): ?>
                    <option value="<?= $st ?>" <?= $product['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-12"><label class="nb-form-label">Description</label><textarea class="form-control" name="description" rows="4" required><?= e($product['description']) ?></textarea></div>
        <div class="col-12">
            <div class="d-flex gap-2 flex-wrap mb-2">
                <?php foreach ($images as $img): ?><img src="<?= e(upload_url($img['image_path'])) ?>" width="72" height="72" class="rounded-3" alt=""><?php endforeach; ?>
            </div>
            <label class="nb-form-label">Upload image file</label>
            <input type="file" class="form-control" name="images[]" accept=".jpg,.jpeg,.png,.webp" multiple>
            <label class="nb-form-label mt-3">Or paste image URL</label>
            <input type="url" class="form-control" name="image_url" placeholder="https://example.com/photo.jpg">
            <div class="form-text">Paste a direct JPG/PNG/WEBP link. The image will be downloaded to the server.</div>
            <div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="featured" value="1" id="f" <?= (int)$product['featured'] ? 'checked' : '' ?>><label for="f" class="form-check-label">Featured</label></div>
        </div>
    </div>
    <button class="btn btn-nb-primary mt-3" type="submit">Save</button>
</form>
<?php require NB_ROOT . '/includes/admin_footer.php'; ?>
