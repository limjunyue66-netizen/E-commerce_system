<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
$pdo = db();

if (is_post()) {
    require_csrf();
    $id = (int) request('user_id');
    $action = (string) request('action');
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $target = $stmt->fetch();
    if (!$target) {
        flash('danger', 'User not found.');
        redirect('/admin/users.php');
    }

    if ($action === 'toggle_status') {
        if ($target['role'] === 'admin' && $target['status'] === 'active') {
            flash('danger', 'Cannot suspend the only administrator account.');
            redirect('/admin/users.php');
        }
        $new = $target['status'] === 'active' ? 'suspended' : 'active';
        $pdo->prepare('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?')->execute([$new, $id]);
        flash('success', 'User status updated.');
    }
    redirect('/admin/users.php');
}

$q = trim((string) ($_GET['q'] ?? ''));
$role = (string) ($_GET['role'] ?? '');
$where = ['1=1'];
$params = [];
if ($q !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if (in_array($role, ['user', 'admin'], true)) {
    $where[] = 'role = ?';
    $params[] = $role;
}
$sql = 'SELECT * FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY FIELD(role, "admin", "user"), created_at DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Users — Admin';
$adminPage = 'users';
require NB_ROOT . '/includes/admin_header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0"><i class="fa-solid fa-users me-2"></i>Users</h1>
    <span class="text-muted small">Roles: Admin (1 only) + User</span>
</div>
<form class="row g-2 mb-3" method="get">
    <div class="col-md-5"><input class="form-control" name="q" value="<?= e($q) ?>" placeholder="Search name, email, phone"></div>
    <div class="col-md-3">
        <select class="form-select" name="role">
            <option value="">All roles</option>
            <?php foreach (['admin','user'] as $r): ?>
                <option value="<?= $r ?>" <?= $role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2"><button class="btn btn-nb-primary w-100" type="submit">Filter</button></div>
</form>
<div class="nb-panel table-responsive">
    <table class="table align-middle">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= e($u['name']) ?></td>
                <td><?= e($u['email']) ?></td>
                <td><span class="badge text-bg-<?= $u['role'] === 'admin' ? 'dark' : 'secondary' ?>"><?= e($u['role']) ?></span></td>
                <td><?= status_badge($u['status']) ?></td>
                <td class="text-nowrap">
                    <a class="btn btn-sm btn-nb-outline" href="<?= e(url('/admin/user_view.php?id=' . (int) $u['id'])) ?>">View</a>
                    <?php if ($u['role'] !== 'admin'): ?>
                    <form method="post" class="d-inline">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                        <button class="btn btn-sm btn-outline-warning" name="action" value="toggle_status" type="submit" data-confirm="Change user status?"><?= $u['status'] === 'active' ? 'Suspend' : 'Activate' ?></button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require NB_ROOT . '/includes/admin_footer.php'; ?>
