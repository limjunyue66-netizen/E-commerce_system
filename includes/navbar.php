<?php
declare(strict_types=1);
$user = current_user();
$searchQ = trim((string) ($_GET['q'] ?? ''));
?>
<header class="nb-topbar">
    <div class="container d-flex justify-content-between align-items-center">
        <span><i class="fa-solid fa-bolt me-1"></i> Free shipping on orders over $100</span>
        <span class="d-none d-md-inline"><i class="fa-solid fa-headset me-1"></i> Support: help@novabazaar.local</span>
    </div>
</header>
<nav class="navbar navbar-expand-lg nb-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand nb-brand" href="<?= e(url('/')) ?>">
            <img src="<?= e(asset('images/logo.svg')) ?>" alt="<?= e(APP_NAME) ?>" width="36" height="36">
            <span><?= e(APP_NAME) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nbNav" aria-controls="nbNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nbNav">
            <form class="nb-search ms-lg-4 me-lg-auto my-3 my-lg-0" action="<?= e(url('/public/shop.php')) ?>" method="get" role="search">
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="search" class="form-control" name="q" value="<?= e($searchQ) ?>" placeholder="Search products, SKU...">
                    <button class="btn btn-nb-primary" type="submit">Search</button>
                </div>
            </form>
            <ul class="navbar-nav align-items-lg-center gap-lg-1">
                <li class="nav-item"><a class="nav-link" href="<?= e(url('/public/shop.php')) ?>"><i class="fa-solid fa-store me-1"></i>Shop</a></li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?= e(url('/public/wishlist.php')) ?>">
                        <i class="fa-solid fa-heart me-1"></i>Wishlist
                        <?php if ($wishCount > 0): ?><span class="nb-badge"><?= (int) $wishCount ?></span><?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?= e(url('/public/cart.php')) ?>">
                        <i class="fa-solid fa-cart-shopping me-1"></i>Cart
                        <?php if ($cartCount > 0): ?><span class="nb-badge"><?= (int) $cartCount ?></span><?php endif; ?>
                    </a>
                </li>
                <?php if ($user): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fa-solid fa-user-circle me-1"></i><?= e($user['name']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php if ($user['role'] === 'admin'): ?>
                                <li><a class="dropdown-item" href="<?= e(url('/admin/dashboard.php')) ?>"><i class="fa-solid fa-gauge me-2"></i>Admin</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="<?= e(url('/public/orders.php')) ?>"><i class="fa-solid fa-box me-2"></i>Orders</a></li>
                                <li><a class="dropdown-item" href="<?= e(url('/public/profile.php')) ?>"><i class="fa-solid fa-id-card me-2"></i>Profile</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= e(url('/public/logout.php')) ?>"><i class="fa-solid fa-right-from-bracket me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= e(url('/public/login.php')) ?>"><i class="fa-solid fa-right-to-bracket me-1"></i>Login</a></li>
                    <li class="nav-item"><a class="btn btn-nb-accent ms-lg-2" href="<?= e(url('/public/register.php')) ?>">Join Free</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
