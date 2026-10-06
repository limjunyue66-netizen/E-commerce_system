<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_login();
$user = current_user();

$orderNumber = trim((string) ($_GET['order'] ?? ''));
$paypalToken = trim((string) ($_GET['token'] ?? '')); // PayPal returns token = order id

$stmt = db()->prepare('SELECT * FROM orders WHERE order_number = ? AND user_id = ? LIMIT 1');
$stmt->execute([$orderNumber, (int) $user['id']]);
$order = $stmt->fetch();
if (!$order) {
    flash('danger', 'Order not found.');
    redirect('/public/orders.php');
}

$paypalOrderId = $paypalToken !== '' ? $paypalToken : ($_SESSION['paypal_pending'][$orderNumber] ?? '');
if ($paypalOrderId === '') {
    flash('danger', 'Missing PayPal reference.');
    redirect('/public/order_detail.php?number=' . urlencode($orderNumber));
}

if ($order['payment_status'] === 'paid') {
    flash('success', 'Payment already confirmed.');
    redirect('/public/order_detail.php?number=' . urlencode($orderNumber));
}

$capture = paypal_capture($paypalOrderId);
if (!$capture['ok']) {
    flash('danger', 'Payment verification failed. Please contact support if you were charged.');
    redirect('/public/order_detail.php?number=' . urlencode($orderNumber));
}

$status = $capture['data']['status'] ?? '';
$purchase = $capture['data']['purchase_units'][0] ?? [];
$captureRow = $purchase['payments']['captures'][0] ?? [];
$txnId = $captureRow['id'] ?? $paypalOrderId;
$amount = (float) ($captureRow['amount']['value'] ?? 0);
$currency = $captureRow['amount']['currency_code'] ?? '';

if ($status !== 'COMPLETED' && ($captureRow['status'] ?? '') !== 'COMPLETED') {
    flash('danger', 'PayPal payment was not completed.');
    redirect('/public/order_detail.php?number=' . urlencode($orderNumber));
}

if (abs($amount - (float) $order['total_amount']) > 0.01 || ($currency && $currency !== PAYPAL_CURRENCY)) {
    app_log('payment', "Amount mismatch for {$orderNumber}: expected {$order['total_amount']} got {$amount} {$currency}");
    flash('danger', 'Payment amount mismatch. Order not marked as paid.');
    redirect('/public/order_detail.php?number=' . urlencode($orderNumber));
}

$result = finalize_paid_order((int) $order['id'], (string) $txnId, $capture['data']);
unset($_SESSION['paypal_pending'][$orderNumber]);
flash($result['ok'] ? 'success' : 'danger', $result['ok'] ? 'Payment confirmed. Thank you!' : ($result['message'] ?? 'Could not finalize payment.'));
redirect('/public/order_detail.php?number=' . urlencode($orderNumber));
