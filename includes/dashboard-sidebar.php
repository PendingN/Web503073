<?php $dashboardActive = $dashboardActive ?? 'overview'; ?>
<aside class="dashboard-sidebar">
    <a class="dashboard-logo" href="index.php">
        <span class="dashboard-logo-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 4 13V6s3 0 6 3 7 3 7 3v1a7 7 0 0 1-6 7Z"/><path d="M4 6s2 6 8 14"/></svg>
        </span>
        <span class="dashboard-brand">VŨ ĐIỆU RỪNG XANH</span>
    </a>
    <nav aria-label="Điều hướng không gian xanh">
        <a class="dashboard-nav-link<?= $dashboardActive === 'overview' ? ' is-active' : '' ?>" <?= $dashboardActive === 'overview' ? 'aria-current="page"' : '' ?> href="dashboard.php">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9.5 9-6.5 9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Z"/><path d="M9 21v-9h6v9"/></svg>
            Tổng quan
        </a>
        <a class="dashboard-nav-link<?= $dashboardActive === 'collection' ? ' is-active' : '' ?>" <?= $dashboardActive === 'collection' ? 'aria-current="page"' : '' ?> href="collection.php">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            Bộ sưu tập
        </a>
        <a class="dashboard-nav-link<?= $dashboardActive === 'favorites' ? ' is-active' : '' ?>" <?= $dashboardActive === 'favorites' ? 'aria-current="page"' : '' ?> href="favorites.php">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z"/></svg>
            Yêu thích <span class="favorite-count"><?= count(favorite_ids()) ?></span>
        </a>
        <a class="dashboard-nav-link<?= $dashboardActive === 'profile' ? ' is-active' : '' ?>" <?= $dashboardActive === 'profile' ? 'aria-current="page"' : '' ?> href="profile.php">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="3.5"/><path d="M5 20c.8-3.6 3.1-5.5 7-5.5s6.2 1.9 7 5.5"/></svg>
            Trang cá nhân
        </a>
    </nav>
    <div class="dashboard-sidebar-foot">
        <span><?= count($plants) ?> giống cây xanh</span>
        <a href="blog.php">Nhật ký chăm cây →</a>
    </div>
</aside>
