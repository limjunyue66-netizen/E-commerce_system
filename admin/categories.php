<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
$pdo = db();

if (is_post()) {
    require_csrf();
    $action = (string) request('action');
    if ($action === 'save') {
        $id = (int) request('id');
        $name = trim((string) request('name'));
        $description = trim((string) request('description'));
        $icon = trim((string) request('icon')) ?: 'fa-tags';
        $status = (string) request('status') === 'inactive' ? 'inactive' : 'active';
        if ($name === '') {
            flash('danger', 'Category name required.');
            redirect('/admin/categories.php');
        }
        $slug = unique_slug($name, 'categories', $id ?: null);
        if ($id > 0) {
            $pdo->prepare('UPDATE categories SET name=?, slug=?, description=?, icon=?, status=?, updated_at=NOW() WHERE id=?')
                ->execute([$name, $slug, $description, $icon, $status, $id]);
            flash('success', 'Category updated.');
        } else {
            $pdo->prepare('INSERT INTO categories (name, slug, description, icon, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())')
                ->execute([$name, $slug, $description, $icon, $status]);
            flash('success', 'Category created.');
        }
    }
    if ($action === 'delete') {
        $id = (int) request('id');
        $count = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $count->execute([$id]);
        if ((int) $count->fetchColumn() > 0) {
            flash('danger', 'Cannot delete a category that still has products. Reassign or deactivate instead.');
        } else {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('success', 'Category deleted.');
        }
    }
    redirect('/admin/categories.php');
}

$categories = $pdo->query("SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count FROM categories c ORDER BY c.name")->fetchAll();
$editId = (int) ($_GET['edit'] ?? 0);
$edit = null;
if ($editId) {
    foreach ($categories as $c) {
        if ((int) $c['id'] === $editId) {
            $edit = $c;
            break;
        }
    }
}

$pageTitle = 'Categories — Admin';
$adminPage = 'categories';
require NB_ROOT . '/includes/admin_header.php';
?>
<h1 class="h3 mb-3"><i class="fa-solid fa-tags me-2"></i>Categories</h1>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="nb-panel table-responsive">
            <table class="table align-middle">
                <thead><tr><th>Category</th><th>Products</th><th>Status</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($categories as $c): ?>
                    <tr>
                        <td><i class="fa-solid <?= e($c['icon']) ?> me-2 text-success"></i><?= e($c['name']) ?></td>
                        <td><?= (int) $c['product_count'] ?></td>
                        <td><?= status_badge($c['status']) ?></td>
                        <td class="text-nowrap">
                            <a class="btn btn-sm btn-nb-outline" href="<?= e(url('/admin/categories.php?edit=' . (int) $c['id'])) ?>">Edit</a>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit" data-confirm="Delete this category?">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-5">
        <form method="post" class="nb-panel">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($edit['id'] ?? 0) ?>">
            <h2 class="h5 mb-3"><?= $edit ? 'Edit category' : 'Add category' ?></h2>
            <div class="mb-2"><input class="form-control" name="name" value="<?= e((string) ($edit['name'] ?? '')) ?>" placeholder="Name" required></div>
            <div class="mb-2"><input class="form-control" name="icon" value="<?= e((string) ($edit['icon'] ?? 'fa-tags')) ?>" placeholder="Font Awesome icon e.g. fa-laptop"></div>
            <div class="mb-2"><textarea class="form-control" name="description" rows="3" placeholder="Description"><?= e((string) ($edit['description'] ?? '')) ?></textarea></div>
            <div class="mb-2">
                <select class="form-select" name="status">
                    <option value="active" <?= ($edit['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($edit['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <button class="btn btn-nb-primary w-100" type="submit">Save</button>
        </form>
    </div>
</div>
<?php require NB_ROOT . '/includes/admin_footer.php'; ?>
