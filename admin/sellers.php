<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
require_admin();
flash('info', 'Seller accounts are disabled. This store only uses Admin and User roles.');
redirect('/admin/users.php');
