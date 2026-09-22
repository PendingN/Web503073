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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="<?= e($bodyClass) ?>">

