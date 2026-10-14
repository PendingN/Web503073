<?php
declare(strict_types=1);

// Shared shell for admin pages. The caller sets $admin, $adminActive, $adminHeading and $pageTitle.
$pageTitle = $pageTitle ?? 'Quản trị';
$bodyClass = 'admin-page';
$flashMessage = take_flash();
require __DIR__ . '/header.php';

$adminNav = [
    'overview' => ['admin.php', 'Tổng quan', '<path d="M4 4h7v7H4zM13 4h7v4h-7zM13 10h7v10h-7zM4 13h7v7H4z"/>'],
    'users' => ['admin-users.php', 'Người dùng', '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-7 8-7s8 3 8 7"/>'],
    'plants' => ['admin-plants.php', 'Quản lý cây', '<path d="M5 19c0-8 5-13 14-14-1 9-6 14-14 14z"/><path d="M5 19l7-7"/>'],
    'blog' => ['admin-blog.php', 'Kiểm duyệt blog', '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 8h6M9 12h6M9 16h4"/>'],
];
?>
<div class="admin-shell">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="index.php"><strong>VŨ ĐIỆU<br>RỪNG XANH</strong></a>
        <p class="admin-label">KHÔNG GIAN QUẢN TRỊ</p>
        <nav aria-label="Điều hướng quản trị">
            <?php foreach ($adminNav as $key => [$href, $label, $icon]): ?>
                <a href="<?= e($href) ?>"<?= $key === $adminActive ? ' class="is-active" aria-current="page"' : '' ?>><span><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><?= $icon ?></svg></span><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="admin-sidebar-foot"><a class="admin-avatar" href="profile.php" aria-label="Trang cá nhân">AD</a><div><strong><?= e($admin['name']) ?></strong><small>Quản trị viên</small></div></div>
    </aside>
    <div class="admin-content">
        <header class="admin-topbar">
            <div><p><?= e(date('d/m/Y')) ?></p><h1><?= e($adminHeading) ?></h1></div>
            <div class="admin-actions">
                <a href="index.php">Xem website</a>
                <form class="logout-form" method="post" action="actions/logout.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <button type="submit">Đăng xuất</button>
                </form>
            </div>
        </header>
        <main class="admin-main">
            <?php if ($flashMessage !== null): ?><div class="status-note" role="status" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
