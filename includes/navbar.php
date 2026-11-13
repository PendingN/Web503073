<?php
$activePage = $activePage ?? 'home';
$loginTarget = 'login.php';
$loginLabel = 'Đăng nhập';
$navbarUser = current_user();
$hasSiteHeader = true;
$hasCustomSkipLink = in_array($bodyClass ?? '', ['landing-page', 'blog-page', 'blog-post-page'], true);
?>
<?php if (!$hasCustomSkipLink): ?>
<a class="skip-link" href="#main-content">Đến nội dung chính</a>
<?php endif; ?>
<header class="navbar site-header">
    <a class="brand-link" href="index.php" aria-label="Vũ Điệu Rừng Xanh — Trang chủ">
        <svg class="room-brand-leaf" viewBox="0 0 32 40" fill="none" aria-hidden="true"><path d="M6 35C6 20 24 18 25 4c9 15 1 28-11 25M6 35C2 25 2 17 8 12c1 9 10 10 6 17M6 35c5-10 13-15 16-20"/></svg>
        <span>VŨ ĐIỆU<br>RỪNG XANH</span>
    </a>
    <nav aria-label="Điều hướng chính" id="site-menu" class="nav-center">
        <a class="nav-link<?= $activePage === 'home' ? ' active' : '' ?>" <?= $activePage === 'home' ? 'aria-current="page"' : '' ?> href="index.php">Trang chủ</a>
        <a class="nav-link<?= $activePage === 'collection' ? ' active' : '' ?>" <?= $activePage === 'collection' ? 'aria-current="page"' : '' ?> href="collection.php">Bộ sưu tập</a>
        <a class="nav-link<?= $activePage === 'blog' ? ' active' : '' ?>" <?= $activePage === 'blog' ? 'aria-current="page"' : '' ?> href="blog.php">Blog</a>
        <a class="nav-link<?= $activePage === 'profile' ? ' active' : '' ?>" <?= $activePage === 'profile' ? 'aria-current="page"' : '' ?> href="profile.php">Trang cá nhân</a>
        <?php if (is_admin()): ?><a class="nav-link<?= $activePage === 'admin' ? ' active' : '' ?>" <?= $activePage === 'admin' ? 'aria-current="page"' : '' ?> href="admin-dashboard.php">Quản trị</a><?php endif; ?>
    </nav>
    <div class="nav-right">
        <?php if ($navbarUser === null): ?>
        <a href="<?= e($loginTarget) ?>" class="btn-contact"><?= e($loginLabel) ?></a>
        <?php else: ?>
        <a href="favorites.php" class="nav-favorites-link">Yêu thích</a>
        <form class="inline-logout-form" method="post" action="actions/logout.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <button type="submit" class="nav-logout-btn">Đăng xuất</button>
        </form>
        <?php endif; ?>
        <button class="menu-icon" type="button" aria-label="Mở menu" aria-controls="site-menu" aria-expanded="false" data-menu-toggle><svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16"/></svg></button>
    </div>
</header>

