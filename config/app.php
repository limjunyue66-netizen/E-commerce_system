<?php
declare(strict_types=1);

define('APP_NAME', (string) env('APP_NAME', 'NovaBazaar'));
define('APP_ENV', (string) env('APP_ENV', 'local'));
define('APP_DEBUG', (bool) env('APP_DEBUG', true));
define('APP_URL', rtrim((string) env('APP_URL', 'http://localhost/E-commerce_system'), '/'));
define('APP_BASE_PATH', rtrim((string) env('APP_BASE_PATH', '/E-commerce_system'), '/'));
define('UPLOAD_MAX_MB', (int) env('UPLOAD_MAX_MB', 5));
define('SHIPPING_FEE', (float) env('SHIPPING_FEE', 8.00));
define('FORCE_PASSWORD_CHANGE', (bool) env('FORCE_PASSWORD_CHANGE', true));
define('CURRENCY_SYMBOL', (string) env('CURRENCY_SYMBOL', 'RM'));
define('CURRENCY_CODE', (string) env('CURRENCY_CODE', 'MYR'));

// Demo bank transfer details (Malaysia) — not a real account
define('BANK_NAME', (string) env('BANK_NAME', 'Maybank Berhad'));
define('BANK_ACCOUNT_NAME', (string) env('BANK_ACCOUNT_NAME', 'NovaBazaar Sdn Bhd'));
define('BANK_ACCOUNT_NUMBER', (string) env('BANK_ACCOUNT_NUMBER', '5123 4567 8901'));
