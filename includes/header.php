<?php
$pageTitle = $pageTitle ?? '';
$pageDescription = $pageDescription ?? 'Cùng thiên nhiên tạo nên những giá trị xanh bền vững.';
$bodyClass = $bodyClass ?? '';
$fullTitle = $pageTitle === '' ? 'Vũ Điệu Rừng Xanh' : $pageTitle . ' | Vũ Điệu Rừng Xanh';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($fullTitle) ?></title>
    <?php if ($bodyClass === 'landing-page'): ?><link rel="preload" as="image" href="<?= e(app_url('images/optimized/hero-canopy-enhanced.webp')) ?>" fetchpriority="high"><?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset_url('css/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url('css/style.css')) ?>">
</head>
<body class="<?= e($bodyClass) ?>">
