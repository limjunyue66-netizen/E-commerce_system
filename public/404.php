<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/config/bootstrap.php';
http_response_code(404);
$pageTitle = 'Page Not Found — ' . APP_NAME;
require NB_ROOT . '/includes/header.php';
?>
<div class="container pb-5">
    <div class="nb-empty nb-panel">
        <i class="fa-solid fa-compass"></i>
        <h1>404 — Page not found</h1>
        <p>The page you requested does not exist.</p>
        <a class="btn btn-nb-primary" href="<?= e(url('/')) ?>">Back to home</a>
    </div>
</div>
<?php require NB_ROOT . '/includes/footer.php'; ?>
