<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_login();
$orderNumber = trim((string) ($_GET['order'] ?? ''));
flash('warning', 'PayPal payment was cancelled. Your order remains pending.');
if ($orderNumber !== '') {
    redirect('/public/order_detail.php?number=' . urlencode($orderNumber));
}
redirect('/public/orders.php');
