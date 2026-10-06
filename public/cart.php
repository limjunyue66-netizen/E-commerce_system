<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_login();
$user = current_user();
if ($user['role'] !== 'user') {
    flash('warning', 'Only user accounts use the shopping cart.');
    redirect_by_role($user);
}

$pdo = db();

if (is_post()) {
    require_csrf();
    $action = (string) request('action');

    if ($action === 'add') {
        $result = add_to_cart((int) $user['id'], (int) request('product_id'), (int) request('quantity', 1));
        flash($result['ok'] ? 'success' : 'danger', $result['message']);
        redirect('/public/cart.php');
    }

    if ($action === 'update') {
        $quantities = $_POST['qty'] ?? [];
        if (is_array($quantities)) {
            foreach ($quantities as $itemId => $qty) {
                $itemId = (int) $itemId;
                $qty = max(1, (int) $qty);
                $stmt = $pdo->prepare("SELECT ci.*, p.stock, p.price, p.status FROM cart_items ci
                    INNER JOIN carts c ON c.id = ci.cart_id
                    INNER JOIN products p ON p.id = ci.product_id
                    WHERE ci.id = ? AND c.user_id = ?");
                $stmt->execute([$itemId, (int) $user['id']]);
                $row = $stmt->fetch();
                if (!$row || $row['status'] !== 'active') {
                    continue;
                }
                if ($qty > (int) $row['stock']) {
                    flash('danger', 'Quantity exceeds stock for one or more items.');
                    continue;
                }
                $pdo->prepare('UPDATE cart_items SET quantity = ?, price = ?, updated_at = NOW() WHERE id = ?')
                    ->execute([$qty, $row['price'], $itemId]);
            }
            flash('success', 'Cart updated.');
        }
        redirect('/public/cart.php');
    }

    if ($action === 'remove') {
        $itemId = (int) request('item_id');
        $stmt = $pdo->prepare('DELETE ci FROM cart_items ci INNER JOIN carts c ON c.id = ci.cart_id WHERE ci.id = ? AND c.user_id = ?');
        $stmt->execute([$itemId, (int) $user['id']]);
        flash('success', 'Item removed.');
        redirect('/public/cart.php');
    }

    if ($action === 'apply_coupon') {
        $totals = cart_totals((int) $user['id']);
        $valid = fetch_valid_coupon((string) request('coupon_code'), (float) $totals['subtotal']);
        if ($valid['ok']) {
            $_SESSION['coupon'] = $valid['coupon'];
            flash('success', 'Coupon applied.');
        } else {
            unset($_SESSION['coupon']);
            flash('danger', $valid['message']);
        }
        redirect('/public/cart.php');
    }

    if ($action === 'remove_coupon') {
        unset($_SESSION['coupon']);
        flash('success', 'Coupon removed.');
        redirect('/public/cart.php');
    }
}

$totals = cart_totals((int) $user['id']);
$availableCoupons = list_public_coupons();
$pageTitle = 'Cart — ' . APP_NAME;
$extraJs = ['cart.js'];
require NB_ROOT . '/includes/header.php';
?>
<div class="container pb-5">
    <h1 class="nb-section-title mb-3"><i class="fa-solid fa-cart-shopping me-2"></i>Shopping Cart</h1>
    <?php if (!$totals['items']): ?>
        <div class="nb-empty nb-panel">
            <i class="fa-solid fa-basket-shopping"></i>
            <h3>Your cart is empty</h3>
            <a class="btn btn-nb-primary mt-2" href="<?= e(url('/public/shop.php')) ?>">Browse products</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <div class="col-lg-8">
                <form method="post" class="nb-panel">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="update">
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th>Subtotal</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($totals['items'] as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex gap-2 align-items-center">
                                            <img src="<?= e(!empty($item['image_path']) ? upload_url($item['image_path']) : asset('images/placeholder-product.svg')) ?>" width="64" height="64" class="rounded-3 object-fit-cover" alt="">
                                            <div>
                                                <strong><?= e($item['name']) ?></strong>
                                                <div class="small text-muted">SKU: <?= e($item['sku']) ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= e(money((float) $item['price'])) ?></td>
                                    <td style="max-width:100px">
                                        <input class="form-control nb-cart-qty" type="number" min="1" max="<?= (int) $item['stock'] ?>" name="qty[<?= (int) $item['id'] ?>]" value="<?= (int) $item['quantity'] ?>">
                                    </td>
                                    <td><?= e(money((float) $item['price'] * (int) $item['quantity'])) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger" type="submit" form="remove-<?= (int) $item['id'] ?>" data-confirm="Remove this item?"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <a class="btn btn-nb-primary" href="<?= e(url('/public/shop.php')) ?>"><i class="fa-solid fa-arrow-left me-1"></i>Continue shopping</a>
                </form>
                <?php foreach ($totals['items'] as $item): ?>
                    <form method="post" id="remove-<?= (int) $item['id'] ?>" class="d-none">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="remove">
                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                    </form>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-4">
                <div class="nb-panel mb-3">
                    <h2 class="h5"><i class="fa-solid fa-ticket me-2"></i>Coupon</h2>
                    <?php if (!empty($totals['coupon'])): ?>
                        <p class="mb-2">Applied: <strong><?= e($totals['coupon']['code']) ?></strong></p>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="remove_coupon">
                            <button class="btn btn-outline-danger btn-sm" type="submit">Remove coupon</button>
                        </form>
                    <?php else: ?>
                        <?php if ($availableCoupons): ?>
                            <p class="small text-muted mb-2">Available coupons — tap to apply:</p>
                            <div class="nb-coupon-list mb-3">
                                <?php foreach ($availableCoupons as $c): ?>
                                    <form method="post" class="nb-coupon-chip">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="apply_coupon">
                                        <input type="hidden" name="coupon_code" value="<?= e($c['code']) ?>">
                                        <button type="submit" class="nb-coupon-btn" title="Apply <?= e($c['code']) ?>">
                                            <strong><?= e($c['code']) ?></strong>
                                            <span><?= e(coupon_offer_label($c)) ?></span>
                                        </button>
                                    </form>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <form method="post" class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="apply_coupon">
                            <input class="form-control" name="coupon_code" placeholder="Or type a code" required>
                            <button class="btn btn-nb-primary" type="submit">Apply</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="nb-panel">
                    <h2 class="h5 mb-3"><i class="fa-solid fa-calculator me-2"></i>Summary</h2>
                    <div class="d-flex justify-content-between mb-2"><span>Subtotal</span><strong><?= e(money($totals['subtotal'])) ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Discount</span><strong>- <?= e(money($totals['discount'])) ?></strong></div>
                    <div class="d-flex justify-content-between mb-2"><span>Shipping</span><strong><?= e(money($totals['shipping'])) ?></strong></div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3"><span>Total</span><strong class="fs-5 text-success"><?= e(money($totals['total'])) ?></strong></div>
                    <a class="btn btn-nb-accent w-100" href="<?= e(url('/public/checkout.php')) ?>"><i class="fa-solid fa-lock me-1"></i>Checkout</a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require NB_ROOT . '/includes/footer.php'; ?>
