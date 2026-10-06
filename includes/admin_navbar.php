<?php
declare(strict_types=1);
$adminPage = $adminPage ?? '';
$links = [
    'dashboard' => ['Dashboard', 'fa-gauge', '/admin/dashboard.php'],
    'users' => ['Users', 'fa-users', '/admin/users.php'],
    'categories' => ['Categories', 'fa-tags', '/admin/categories.php'],
    'products' => ['Products', 'fa-boxes-stacked', '/admin/products.php'],
    'orders' => ['Orders', 'fa-receipt', '/admin/orders.php'],
    'coupons' => ['Coupons', 'fa-ticket', '/admin/coupons.php'],
    'reviews' => ['Reviews', 'fa-star', '/admin/reviews.php'],
];
?>
<aside class="nb-admin-sidebar">
    <div class="nb-admin-brand">
        <img src="<?= e(asset('images/logo.svg')) ?>" width="34" height="34" alt="">
        <span>Admin</span>
    </div>
    <?php foreach ($links as $key => $link): ?>
        <a class="<?= $adminPage === $key ? 'active' : '' ?>" href="<?= e(url($link[2])) ?>">
            <i class="fa-solid <?= e($link[1]) ?>"></i><?= e($link[0]) ?>
        </a>
    <?php endforeach; ?>
    <hr class="border-light opacity-25 my-3">
    <a href="<?= e(url('/')) ?>"><i class="fa-solid fa-house"></i>Storefront</a>
    <a href="<?= e(url('/admin/logout.php')) ?>"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
</aside>
