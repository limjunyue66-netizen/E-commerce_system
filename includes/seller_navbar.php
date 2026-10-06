<?php
declare(strict_types=1);
$sellerPage = $sellerPage ?? '';
?>
<div class="nb-seller-top">
    <div class="container d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="d-flex align-items-center gap-2">
            <img src="<?= e(asset('images/logo.svg')) ?>" width="32" height="32" alt="">
            <strong>Seller Hub</strong>
        </div>
        <nav class="nb-seller-nav">
            <a class="<?= $sellerPage === 'dashboard' ? 'active' : '' ?>" href="<?= e(url('/seller/dashboard.php')) ?>"><i class="fa-solid fa-chart-line"></i>Dashboard</a>
            <a class="<?= $sellerPage === 'products' ? 'active' : '' ?>" href="<?= e(url('/seller/products.php')) ?>"><i class="fa-solid fa-box"></i>Products</a>
            <a class="<?= $sellerPage === 'orders' ? 'active' : '' ?>" href="<?= e(url('/seller/orders.php')) ?>"><i class="fa-solid fa-receipt"></i>Orders</a>
            <a class="<?= $sellerPage === 'profile' ? 'active' : '' ?>" href="<?= e(url('/seller/profile.php')) ?>"><i class="fa-solid fa-user"></i>Profile</a>
            <a href="<?= e(url('/public/logout.php')) ?>"><i class="fa-solid fa-right-from-bracket"></i>Logout</a>
        </nav>
    </div>
</div>
