<?php
declare(strict_types=1);

/**
 * NovaBazaar bootstrap — load environment and core constants.
 */

if (defined('NB_BOOTSTRAPPED')) {
    return;
}
define('NB_BOOTSTRAPPED', true);

define('NB_ROOT', dirname(__DIR__));

/**
 * Load .env key/value pairs into getenv/$_ENV without Composer.
 */
function nb_load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$key] = $value;
        putenv($key . '=' . $value);
    }
}

nb_load_env(NB_ROOT . DIRECTORY_SEPARATOR . '.env');

function env(string $key, mixed $default = null): mixed
{
    // Distinguish "missing key" from "empty string" (needed for blank DB passwords).
    $existsInEnv = array_key_exists($key, $_ENV);
    $getenv = getenv($key);
    if (!$existsInEnv && ($getenv === false)) {
        return $default;
    }
    $value = $existsInEnv ? $_ENV[$key] : $getenv;
    if ($value === null) {
        return $default;
    }
    return match (strtolower((string) $value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        default => $value,
    };
}

date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Kuala_Lumpur'));

$debug = (bool) env('APP_DEBUG', true);
ini_set('display_errors', $debug ? '1' : '0');
error_reporting($debug ? E_ALL : E_ALL & ~E_NOTICE & ~E_DEPRECATED);

if (session_status() === PHP_SESSION_NONE) {
    $sessionName = (string) env('SESSION_NAME', 'novabazaar_session');
    session_name($sessionName);
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

require_once NB_ROOT . '/config/app.php';
require_once NB_ROOT . '/config/db.php';
require_once NB_ROOT . '/config/paypal.php';
require_once NB_ROOT . '/config/mail.php';
require_once NB_ROOT . '/includes/functions.php';
require_once NB_ROOT . '/includes/csrf.php';
require_once NB_ROOT . '/includes/auth.php';
require_once NB_ROOT . '/includes/mail_service.php';
require_once NB_ROOT . '/includes/paypal_service.php';
