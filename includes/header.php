<?php
$pageTitle = $pageTitle ?? '';
$pageDescription = $pageDescription ?? 'Cùng thiên nhiên tạo nên những giá trị xanh bền vững.';
$bodyClass = $bodyClass ?? '';
$pageStyles = $pageStyles ?? [];
$fullTitle = $pageTitle === '' ? 'Vũ Điệu Rừng Xanh' : $pageTitle . ' | Vũ Điệu Rừng Xanh';
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($pageDescription) ?>">
    <title><?= e($fullTitle) ?></title>
    <?php if ($bodyClass === 'landing-page'): ?><link rel="preload" as="image" href="<?= e(image_url('indoor-room.png')) ?>" fetchpriority="high"><?php endif; ?>
    <link rel="stylesheet" href="<?= e(asset_url('css/fonts.css')) ?>">
    <link rel="stylesheet" href="<?= e(asset_url($bodyClass === 'admin-page' ? 'css/admin.css' : 'css/style.css')) ?>">
    <?php if ($bodyClass === 'landing-page'): ?><link rel="stylesheet" href="<?= e(asset_url('css/landing.css')) ?>"><?php endif; ?>
    <?php if (in_array($bodyClass, ['blog-page', 'blog-post-page'], true)): ?><link rel="stylesheet" href="<?= e(asset_url('css/blog.css')) ?>"><?php endif; ?>
    <?php foreach ($pageStyles as $pageStyle): ?><link rel="stylesheet" href="<?= e(asset_url($pageStyle)) ?>">
    <?php endforeach; ?>
</head>
<body class="<?= e($bodyClass) ?>">
