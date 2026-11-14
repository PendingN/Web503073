<?php $dashboardActive = $dashboardActive ?? 'overview'; ?>
<aside class="dashboard-sidebar">
    <?php if (empty($hasSiteHeader)): ?>
    <a class="dashboard-logo" href="index.php">
        <span class="dashboard-logo-mark" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 4 13V6s3 0 6 3 7 3 7 3v1a7 7 0 0 1-6 7Z"/><path d="M4 6s2 6 8 14"/></svg>
        </span>
        <span class="dashboard-brand">VŨ ĐIỆU RỪNG XANH</span>
    </a>
    <?php endif; ?>
    <nav aria-label="Điều hướng không gian xanh">
        <a class="dashboard-nav-link<?= $dashboardActive === 'overview' ? ' is-active' : '' ?>" <?= $dashboardActive === 'overview' ? 'aria-current="page"' : '' ?> href="dashboard.php">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 9.5 9-6.5 9 6.5V20a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Z"/><path d="M9 21v-9h6v9"/></svg>
            Tổng quan
        </a>
        <a class="dashboard-nav-link<?= $dashboardActive === 'advisor' ? ' is-active' : '' ?>" <?= $dashboardActive === 'advisor' ? 'aria-current="page"' : '' ?> href="plant-advisor.php">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v3M12 18v3M3 12h3M18 12h3M5.6 5.6l2.1 2.1M16.3 16.3l2.1 2.1M5.6 18.4l2.1-2.1M16.3 7.7l2.1-2.1"/><circle cx="12" cy="12" r="3"/></svg>
            Gợi ý chọn cây
        </a>
        <a class="dashboard-nav-link<?= $dashboardActive === 'collection' ? ' is-active' : '' ?>" <?= $dashboardActive === 'collection' ? 'aria-current="page"' : '' ?> href="collection.php">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
            Bộ sưu tập
        </a>
        <a class="dashboard-nav-link<?= $dashboardActive === 'favorites' ? ' is-active' : '' ?>" <?= $dashboardActive === 'favorites' ? 'aria-current="page"' : '' ?> href="favorites.php">
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78Z"/></svg>
            Yêu thích <span class="favorite-count"><?= count(favorite_ids()) ?></span>
        </a>
    </nav>
    <div class="dashboard-sidebar-foot">
        <span><?= (int) plant_catalog_statistics()['total'] ?> giống cây xanh</span>
        <a href="blog.php">Nhật ký chăm cây</a>
    </div>
</aside>
