<?php
$required = [
  'index.php','.htaccess','README.md','.env.example',
  'admin/index.php','admin/dashboard.php','admin/users.php','admin/user_view.php','admin/sellers.php','admin/categories.php','admin/products.php','admin/product_add.php','admin/product_edit.php','admin/orders.php','admin/order_view.php','admin/coupons.php','admin/reviews.php','admin/logout.php',
  'seller/dashboard.php','seller/products.php','seller/product_add.php','seller/product_edit.php','seller/orders.php','seller/order_view.php','seller/profile.php',
  'public/shop.php','public/product.php','public/cart.php','public/checkout.php','public/payment_success.php','public/payment_cancel.php','public/order_detail.php','public/orders.php','public/wishlist.php','public/profile.php','public/login.php','public/register.php','public/forgot_password.php','public/reset_password.php','public/logout.php',
  'config/db.php','config/app.php','config/paypal.php','config/mail.php','config/bootstrap.php',
  'includes/functions.php','includes/auth.php','includes/csrf.php','includes/header.php','includes/footer.php','includes/navbar.php','includes/admin_navbar.php','includes/seller_navbar.php','includes/mail_service.php','includes/paypal_service.php',
  'assets/css/style.css','assets/css/admin.css','assets/css/seller.css','assets/js/main.js','assets/js/cart.js','assets/js/checkout.js','assets/js/admin.js',
  'database/ecommerce_marketplace.sql'
];
$root = dirname(__DIR__);
$missing = [];
foreach ($required as $f) {
    if (!is_file($root . '/' . $f)) {
        $missing[] = $f;
    }
}
echo $missing ? ("MISSING:\n" . implode("\n", $missing) . "\n") : "ALL_REQUIRED_FILES_PRESENT\n";
echo 'placeholders=' . count(glob($root . '/uploads/products/placeholder-*.svg')) . "\n";
