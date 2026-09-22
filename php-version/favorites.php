<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$favoritePlants = array_values(array_filter(array_map('find_plant', favorite_ids())));
$pageTitle = 'Yêu thích';
$bodyClass = 'dashboard-page';
$dashboardActive = 'favorites';
$flashMessage = take_flash();
require __DIR__ . '/includes/header.php';
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
                    <p class="lead">Những lựa chọn bạn muốn quay lại xem hoặc chăm sóc trong thời gian tới.</p>
                </div>
            </header>
            <?php if ($favoritePlants === []): ?>
                <div class="empty-state">
                    <h2>Góc này vẫn đang chờ những chiếc lá đầu tiên.</h2>
                    <p>Hãy bấm biểu tượng trái tim trên một thẻ cây để lưu lại lựa chọn của bạn.</p>
                    <a class="btn-brand" href="collection.php">Khám phá bộ sưu tập ↗</a>
                </div>
            <?php else: ?>
                <div class="plant-grid">
                    <?php foreach ($favoritePlants as $plant): ?>
                        <?php $returnTo = 'favorites.php'; require __DIR__ . '/includes/plant-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php $showSiteFooter = false; require __DIR__ . '/includes/footer.php'; ?>
