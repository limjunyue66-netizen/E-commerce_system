<?php
declare(strict_types=1);

/**
 * PDO database connection for the e-commerce system.
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = (string) env('DB_HOST', '127.0.0.1');
    $name = (string) env('DB_NAME', 'ecommerce');
    $user = (string) env('DB_USER', 'root');
    $pass = (string) env('DB_PASS', '');
    $charset = (string) env('DB_CHARSET', 'utf8mb4');

    $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        if (function_exists('app_log')) {
            app_log('database', 'Connection failed: ' . $e->getMessage());
        }
        $debug = defined('APP_DEBUG') ? APP_DEBUG : true;
        if ($debug) {
            http_response_code(500);
            echo '<h1>Database connection error</h1><p>Please check config/.env and ensure MySQL is running and the database is imported.</p>';
            if (function_exists('e')) {
                echo '<pre>' . e($e->getMessage()) . '</pre>';
            } else {
                echo '<pre>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>';
            }
            exit;
        }
        http_response_code(500);
        echo '<h1>Service temporarily unavailable</h1><p>Please try again later.</p>';
        exit;
    }

    return $pdo;
}
