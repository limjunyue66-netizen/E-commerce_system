<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
flash('info', 'Seller portal is disabled. This store uses Admin and User roles only.');
redirect('/');
