<?php
declare(strict_types=1);

define('MAIL_ENABLED', (bool) env('MAIL_ENABLED', false));
define('MAIL_HOST', (string) env('MAIL_HOST', ''));
define('MAIL_PORT', (int) env('MAIL_PORT', 587));
define('MAIL_USERNAME', (string) env('MAIL_USERNAME', ''));
define('MAIL_PASSWORD', (string) env('MAIL_PASSWORD', ''));
define('MAIL_ENCRYPTION', (string) env('MAIL_ENCRYPTION', 'tls'));
define('MAIL_FROM_ADDRESS', (string) env('MAIL_FROM_ADDRESS', 'noreply@novabazaar.local'));
define('MAIL_FROM_NAME', (string) env('MAIL_FROM_NAME', APP_NAME));
