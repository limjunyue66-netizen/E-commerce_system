<?php
declare(strict_types=1);

define('PAYPAL_MODE', (string) env('PAYPAL_MODE', 'sandbox'));
define('PAYPAL_CLIENT_ID', (string) env('PAYPAL_CLIENT_ID', ''));
define('PAYPAL_CLIENT_SECRET', (string) env('PAYPAL_CLIENT_SECRET', ''));
define('PAYPAL_CURRENCY', (string) env('PAYPAL_CURRENCY', CURRENCY_CODE));
define('PAYPAL_API_BASE', rtrim((string) env(
    'PAYPAL_API_BASE',
    PAYPAL_MODE === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com'
), '/'));

function paypal_configured(): bool
{
    return PAYPAL_CLIENT_ID !== '' && PAYPAL_CLIENT_SECRET !== '';
}
