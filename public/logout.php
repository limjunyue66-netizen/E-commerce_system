<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
logout_user();
session_start();
flash('success', 'You have been logged out.');
redirect('/public/login.php');
