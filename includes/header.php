<?php
declare(strict_types=1);
$pageTitle = $pageTitle ?? APP_NAME;
$pageDescription = $pageDescription ?? 'Shop curated products on ' . APP_NAME;
$bodyClass = $bodyClass ?? '';
$user = current_user();
$cartCount = $user && $user['role'] === 'user' ? cart_totals((int) $user['id'])['count'] : 0;
$wishCount = $user && $user['role'] === 'user' ? wishlist_count((int) $user['id']) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <meta name="description" content="<?= e($pageDescription) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Sora:wght@500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link href="<?= e(asset('css/style.css')) ?>" rel="stylesheet">
    <?php if (!empty($extraCss)): foreach ((array) $extraCss as $css): ?>
        <link href="<?= e(asset('css/' . $css)) ?>" rel="stylesheet">
    <?php endforeach; endif; ?>
</head>
<body class="<?= e($bodyClass) ?>">
<?php require NB_ROOT . '/includes/navbar.php'; ?>
<main class="nb-main">
    <div class="container py-3">
        <?php foreach (get_flashes() as $flash): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show nb-alert" role="alert">
                <i class="fa-solid fa-circle-info me-2"></i><?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>
    </div>
