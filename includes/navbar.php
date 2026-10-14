<?php
$activePage = $activePage ?? 'home';
$loginTarget = 'login.php';
$loginLabel = 'Đăng nhập';
?>
<header class="navbar site-header">
    <a class="brand-link" href="index.php" aria-label="Vũ Điệu Rừng Xanh — Trang chủ">
        <span>VŨ ĐIỆU<br>RỪNG XANH</span>
    </a>
    <nav aria-label="Điều hướng chính" id="site-menu" class="nav-center">
        <a class="nav-link<?= $activePage === 'home' ? ' active' : '' ?>" <?= $activePage === 'home' ? 'aria-current="page"' : '' ?> href="index.php">Trang chủ</a>
        <a class="nav-link" href="index.php#about">Giới thiệu</a>
        <a class="nav-link<?= $activePage === 'collection' ? ' active' : '' ?>" <?= $activePage === 'collection' ? 'aria-current="page"' : '' ?> href="collection.php">Bộ sưu tập</a>
        <a class="nav-link<?= $activePage === 'blog' ? ' active' : '' ?>" <?= $activePage === 'blog' ? 'aria-current="page"' : '' ?> href="blog.php">Blog</a>
        <a class="nav-link<?= $activePage === 'profile' ? ' active' : '' ?>" <?= $activePage === 'profile' ? 'aria-current="page"' : '' ?> href="profile.php">Trang cá nhân</a>
        <?php if (is_admin()): ?><a class="nav-link<?= $activePage === 'admin' ? ' active' : '' ?>" <?= $activePage === 'admin' ? 'aria-current="page"' : '' ?> href="admin-dashboard.php">Quản trị</a><?php endif; ?>
    </nav>
    <div class="nav-right">
        <?php if (current_user() === null): ?>
        <a href="<?= e($loginTarget) ?>" class="btn-contact"><?= e($loginLabel) ?></a>
        <?php endif; ?>
        <button class="menu-icon" type="button" aria-label="Mở menu" aria-controls="site-menu" aria-expanded="false" data-menu-toggle>☰</button>
    </div>
</header>

