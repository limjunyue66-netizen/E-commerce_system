<?php
/**
 * Database configuration EXAMPLE — copy to database.php or use .env instead.
 *
 * DO NOT commit real passwords.
 * Recommended setup for this project:
 *   1. Copy .env.example to .env
 *   2. Set DB_* values in .env
 *   3. Import database/ecommerce_marketplace.sql
 *
 * This file is only a reference for how DB settings look.
 */

declare(strict_types=1);

return [
    'host' => '127.0.0.1',
    'name' => 'ecommerce',
    'user' => 'root',
    'pass' => '', // set your MySQL password locally — never commit real secrets
    'charset' => 'utf8mb4',
];
