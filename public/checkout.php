<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_login();
$user = current_user();
if ($user['role'] !== 'user') {
    redirect_by_role($user);
}

// Buy Now from product page
if (is_post() && (string) request('action') === 'buy_now') {
    require_csrf();
    $result = add_to_cart((int) $user['id'], (int) request('product_id'), max(1, (int) request('quantity', 1)));
    if (!$result['ok']) {
        flash('danger', $result['message']);
        redirect('/public/product.php?id=' . (int) request('product_id'));
    }
    redirect('/public/checkout.php');
}

$totals = cart_totals((int) $user['id']);
if (!$totals['items']) {
    flash('warning', 'Your cart is empty.');
    redirect('/public/cart.php');
}

if (is_post() && (string) request('action') === 'place_order') {
    require_csrf();
    $shipping = [
        'name' => trim((string) request('shipping_name')),
        'phone' => trim((string) request('shipping_phone')),
        'address' => trim((string) request('shipping_address')),
    ];
    $paymentMethod = (string) request('payment_method');
    if ($shipping['name'] === '' || $shipping['phone'] === '' || $shipping['address'] === '') {
        flash('danger', 'Please complete shipping details.');
        redirect('/public/checkout.php');
    }
    if (!in_array($paymentMethod, ['paypal', 'cod', 'bank'], true)) {
        flash('danger', 'Select a valid payment method.');
        redirect('/public/checkout.php');
    }

    $paymentMeta = [];
    if ($paymentMethod === 'bank') {
        $accountName = trim((string) request('bank_account_name'));
        $accountNumber = preg_replace('/\s+/', '', (string) request('bank_account_number')) ?? '';
        if ($accountName === '' || $accountNumber === '') {
            store_old($_POST);
            flash('danger', 'Please fill in your bank account name and account number.');
            redirect('/public/checkout.php');
        }
        if (!preg_match('/^[0-9]{8,20}$/', $accountNumber)) {
            store_old($_POST);
            flash('danger', 'Enter a valid account number (8–20 digits).');
            redirect('/public/checkout.php');
        }
        $paymentMeta = [
            'account_name' => $accountName,
            'account_number' => $accountNumber,
        ];
    }

    $created = create_order_from_cart((int) $user['id'], $shipping, $paymentMethod, $paymentMeta);
    if (!$created['ok']) {
        flash('danger', $created['message']);
        redirect('/public/checkout.php');
    }

    if ($paymentMethod === 'paypal') {
        if (!paypal_configured()) {
            // Dev fallback: mark as pending PayPal without credentials
            flash('warning', 'PayPal credentials are not configured. Order created as pending. Add PAYPAL_CLIENT_ID/SECRET in .env to enable Sandbox.');
            redirect('/public/order_detail.php?number=' . urlencode($created['order_number']));
        }
        $stmt = db()->prepare('SELECT * FROM orders WHERE id = ?');
        $stmt->execute([$created['order_id']]);
        $order = $stmt->fetch();
        $paypal = paypal_create_order($order);
        if (!$paypal['ok']) {
            flash('danger', $paypal['message']);
            redirect('/public/order_detail.php?number=' . urlencode($created['order_number']));
        }
        $_SESSION['paypal_pending'][$created['order_number']] = $paypal['paypal_order_id'];
        header('Location: ' . $paypal['approve_url']);
        exit;
    }

    if ($paymentMethod === 'bank') {
        flash('success', 'Order placed with Bank / Card. Account number saved for this order.');
        redirect('/public/order_detail.php?number=' . urlencode($created['order_number']));
    }

    flash('success', 'Order placed successfully. Pay on delivery.');
    redirect('/public/order_detail.php?number=' . urlencode($created['order_number']));
}

$pageTitle = 'Checkout — ' . APP_NAME;
$extraJs = ['checkout.js'];
require NB_ROOT . '/includes/header.php';
?>
<div class="container pb-5">
    <h1 class="nb-section-title mb-3"><i class="fa-solid fa-credit-card me-2"></i>Checkout</h1>
    <form method="post" id="checkoutForm" class="row g-3">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="place_order">
        <div class="col-lg-7">
            <div class="nb-panel">
                <h2 class="h5 mb-3"><i class="fa-solid fa-truck me-2"></i>Shipping details</h2>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="nb-form-label">Full name</label>
                        <input class="form-control" name="shipping_name" required value="<?= e($user['name']) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="nb-form-label">Email</label>
                        <input class="form-control" type="email" value="<?= e($user['email']) ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="nb-form-label">Phone</label>
                        <input class="form-control" name="shipping_phone" required value="<?= e($user['phone']) ?>">
                    </div>
                    <div class="col-12">
                        <label class="nb-form-label">Shipping address</label>
                        <textarea class="form-control" name="shipping_address" rows="3" required><?= e($user['address']) ?></textarea>
                    </div>
                </div>
                <h2 class="h5 mt-4 mb-3"><i class="fa-solid fa-wallet me-2"></i>Payment method</h2>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="payment_method" id="payPaypal" value="paypal" <?= paypal_configured() ? 'checked' : '' ?>>
                    <label class="form-check-label" for="payPaypal"><i class="fa-brands fa-paypal me-1"></i>PayPal <?= paypal_configured() ? '(Sandbox ready)' : '(configure .env)' ?></label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="radio" name="payment_method" id="payCod" value="cod">
                    <label class="form-check-label" for="payCod"><i class="fa-solid fa-money-bill-wave me-1"></i>Cash on Delivery</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="payment_method" id="payBank" value="bank" <?= paypal_configured() ? '' : 'checked' ?>>
                    <label class="form-check-label" for="payBank"><i class="fa-solid fa-credit-card me-1"></i>Bank / Card</label>
                </div>
                <div id="bankDetails" class="nb-bank-box mt-3 <?= paypal_configured() ? 'd-none' : '' ?>">
                    <div class="small text-muted mb-3">Fill in your bank / card account details for this payment.</div>
                    <div class="mb-3">
                        <label class="nb-form-label" for="bank_account_name">Account name</label>
                        <input class="form-control" type="text" name="bank_account_name" id="bank_account_name"
                               value="<?= e((string) old('bank_account_name', $user['name'])) ?>"
                               placeholder="e.g. Diana Lee" autocomplete="name">
                    </div>
                    <div class="mb-1">
                        <label class="nb-form-label" for="bank_account_number">Account number</label>
                        <input class="form-control nb-bank-account" type="text" name="bank_account_number" id="bank_account_number"
                               value="<?= e((string) old('bank_account_number', '')) ?>"
                               placeholder="e.g. 512345678901" inputmode="numeric" maxlength="20" autocomplete="off">
                    </div>
                    <div class="form-text">Demo tip: you can use <strong><?= e(str_replace(' ', '', BANK_ACCOUNT_NUMBER)) ?></strong></div>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="nb-panel">
                <h2 class="h5 mb-3"><i class="fa-solid fa-receipt me-2"></i>Order summary</h2>
                <?php foreach ($totals['items'] as $item): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span><?= e($item['name']) ?> × <?= (int) $item['quantity'] ?></span>
                        <strong><?= e(money((float) $item['price'] * (int) $item['quantity'])) ?></strong>
                    </div>
                <?php endforeach; ?>
                <hr>
                <div class="d-flex justify-content-between mb-1"><span>Subtotal</span><span><?= e(money($totals['subtotal'])) ?></span></div>
                <div class="d-flex justify-content-between mb-1"><span>Discount</span><span>- <?= e(money($totals['discount'])) ?></span></div>
                <div class="d-flex justify-content-between mb-1"><span>Shipping</span><span><?= e(money($totals['shipping'])) ?></span></div>
                <div class="d-flex justify-content-between mt-2 mb-3"><strong>Total</strong><strong><?= e(money($totals['total'])) ?></strong></div>
                <button class="btn btn-nb-accent w-100" type="submit"><i class="fa-solid fa-circle-check me-1"></i>Place order</button>
            </div>
        </div>
    </form>
</div>
<?php require NB_ROOT . '/includes/footer.php'; ?>
