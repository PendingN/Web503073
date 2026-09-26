<?php
$activePage = $activePage ?? 'home';
$loginTarget = current_user() ? 'profile.php' : ($activePage === 'login' ? 'dashboard.php' : 'login.php');
$loginLabel = current_user() ? 'Tài khoản' : ($activePage === 'login' ? 'Khám phá' : 'Đăng nhập');
?>
<header class="navbar site-header">
    <a class="brand-link" href="index.php" aria-label="Vũ Điệu Rừng Xanh — Trang chủ">
        <span class="brand-mark" aria-hidden="true">↗</span>
        <span>VŨ ĐIỆU<br>RỪNG XANH</span>
    </a>
    <nav aria-label="Điều hướng chính" id="site-menu" class="nav-center">
        <a class="nav-link<?= $activePage === 'home' ? ' active' : '' ?>" <?= $activePage === 'home' ? 'aria-current="page"' : '' ?> href="index.php">Trang chủ</a>
        <a class="nav-link" href="index.php#about">Giới thiệu</a>
        <a class="nav-link<?= $activePage === 'collection' ? ' active' : '' ?>" <?= $activePage === 'collection' ? 'aria-current="page"' : '' ?> href="collection.php">Bộ sưu tập</a>
        <a class="nav-link<?= $activePage === 'blog' ? ' active' : '' ?>" <?= $activePage === 'blog' ? 'aria-current="page"' : '' ?> href="blog.php">Blog</a>
    </nav>
    <div class="nav-right">
        <a href="<?= e($loginTarget) ?>" class="btn-contact"><?= e($loginLabel) ?><span aria-hidden="true">↗</span></a>
        <button class="menu-icon" type="button" aria-label="Mở menu" aria-controls="site-menu" aria-expanded="false" data-menu-toggle>☰</button>
    </div>
</header>

