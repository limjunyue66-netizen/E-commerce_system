<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? 'Admin — ' . APP_NAME;
$adminPage = $adminPage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;600;700;800&family=Sora:wght@600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
    <link href="<?= e(asset('css/admin.css')) ?>" rel="stylesheet">
</head>
<body class="nb-admin">
<div class="nb-admin-shell">
    <?php require NB_ROOT . '/includes/admin_navbar.php'; ?>
    <div class="nb-admin-content">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
