<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_login();

$favoritePlants = plants_by_ids(favorite_ids());
$pageTitle = 'Yêu thích';
$bodyClass = 'dashboard-page';
$dashboardActive = 'favorites';
$activePage = 'collection';
$flashMessage = take_flash();
$pageStyles = ['css/shoe-orbit.css', 'css/favorite-orbit.css'];
$pageScripts = ['js/favorite-orbit.js', 'js/shoe-orbit.js'];
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/includes/dashboard-sidebar.php'; ?>
    <div class="dashboard-main">
        <?php require __DIR__ . '/includes/dashboard-toolbar.php'; ?>
        <main class="dashboard-content">
            <?php if ($flashMessage !== null): ?><div class="status-note" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
            <header>
                <div>
                    <p class="eyebrow">GÓC RIÊNG CỦA BẠN</p>
                    <h1>Cây yêu thích</h1>
                    <p class="lead">Những cây bạn đã lưu.</p>
                </div>
            </header>
            <?php require __DIR__ . '/includes/favorite-orbit.php'; ?>
            <div class="section-heading favorite-grid-heading"<?= $favoritePlants === [] ? ' hidden' : '' ?> data-favorites-heading>
                <div><p class="eyebrow">QUẢN LÝ</p><h2>Danh sách đã lưu</h2></div>
            </div>
            <div class="empty-state" data-favorites-empty<?= $favoritePlants === [] ? '' : ' hidden' ?>>
                <h2>Chưa có cây yêu thích</h2>
                <p>Bấm tim trên thẻ cây để lưu vào đây.</p>
                <a class="btn-brand" href="collection.php">Khám phá bộ sưu tập</a>
            </div>
            <div class="plant-grid" data-favorites-grid>
                <?php foreach ($favoritePlants as $plant): ?>
                    <?php $returnTo = 'favorites.php'; require __DIR__ . '/includes/plant-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</div>

<?php $showSiteFooter = false; require __DIR__ . '/includes/footer.php'; ?>
