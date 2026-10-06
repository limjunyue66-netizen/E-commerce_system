</main>
<footer class="nb-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="nb-footer-brand">
                    <img src="<?= e(asset('images/logo.svg')) ?>" alt="<?= e(APP_NAME) ?>" width="42" height="42">
                    <span><?= e(APP_NAME) ?></span>
                </div>
                <p class="mt-3 mb-0">A modern multi-vendor marketplace for everyday essentials, gadgets, and lifestyle finds.</p>
            </div>
            <div class="col-6 col-lg-2">
                <h6><i class="fa-solid fa-compass me-2"></i>Explore</h6>
                <ul class="list-unstyled">
                    <li><a href="<?= e(url('/public/shop.php')) ?>">Shop</a></li>
                    <li><a href="<?= e(url('/public/wishlist.php')) ?>">Wishlist</a></li>
                    <li><a href="<?= e(url('/public/cart.php')) ?>">Cart</a></li>
                </ul>
            </div>
            <div class="col-6 col-lg-3">
                <h6><i class="fa-solid fa-headset me-2"></i>Support</h6>
                <ul class="list-unstyled">
                    <li><a href="<?= e(url('/public/orders.php')) ?>">Orders</a></li>
                    <li><a href="<?= e(url('/public/profile.php')) ?>">Account</a></li>
                    <li><a href="<?= e(url('/public/forgot_password.php')) ?>">Password Help</a></li>
                </ul>
            </div>
            <div class="col-lg-3">
                <h6><i class="fa-solid fa-shield-halved me-2"></i>Trust</h6>
                <p class="mb-2">Secure checkout and responsive support.</p>
                <div class="nb-footer-icons">
                    <i class="fa-brands fa-cc-paypal" title="PayPal"></i>
                    <i class="fa-solid fa-truck-fast" title="Fast shipping"></i>
                    <i class="fa-solid fa-lock" title="Secure"></i>
                </div>
            </div>
        </div>
        <div class="nb-footer-bottom">
            <span>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</span>
        </div>
    </div>
</footer>
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="nbToastHost"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= e(asset('js/main.js')) ?>"></script>
<?php if (!empty($extraJs)): foreach ((array) $extraJs as $js): ?>
    <script src="<?= e(asset('js/' . $js)) ?>"></script>
<?php endforeach; endif; ?>
</body>
</html>
