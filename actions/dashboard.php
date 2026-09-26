<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Dashboard';
$bodyClass = 'dashboard-page';
$dashboardActive = 'overview';
$flashMessage = take_flash();
$featuredIds = [22, 21, 23, 27];
$featuredPlants = array_values(array_filter(array_map('find_plant', $featuredIds)));
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
                    <p class="eyebrow">KHÔNG GIAN CỦA BẠN</p>
                    <h1>Chào ngày mới,<br><em>người làm vườn.</em></h1>
                    <p class="lead">Một vài phút quan sát hôm nay sẽ giúp những mầm xanh lớn lên khỏe mạnh hơn.</p>
                </div>
                <div class="dashboard-actions">
                    <a class="btn-brand" href="collection.php">Khám phá cây ↗</a>
                    <a class="btn-outline-brand" href="blog.php">Đọc nhật ký</a>
                </div>
            </header>

            <section class="dashboard-hero-card" aria-label="Gợi ý chăm cây">
                <div>
                    <p class="eyebrow" style="color:#dfe8d2;">GỢI Ý HÔM NAY</p>
                    <h2>Chăm cây cũng là<br>chăm mình.</h2>
                    <p>Hãy kiểm tra độ ẩm của đất, lau nhẹ những chiếc lá và dành cho góc xanh của bạn một chút thời gian.</p>
                </div>
                <div class="dashboard-hero-art">
                    <?php require __DIR__ . '/includes/plant-illustration.php'; render_plant_illustration('leaf'); ?>
                </div>
            </section>

            <section class="stats-grid" aria-label="Thống kê bộ sưu tập">
                <article class="stat-card"><span>Tổng số giống cây</span><strong><?= count($plants) ?></strong></article>
                <article class="stat-card"><span>Nhóm cây</span><strong><?= count(array_unique(array_column($plants, 'category'))) ?></strong></article>
                <article class="stat-card"><span>Đã yêu thích</span><strong><?= count(favorite_ids()) ?></strong></article>
                <article class="stat-card"><span>Bài viết chăm cây</span><strong><?= count($posts) + 1 ?></strong></article>
            </section>

            <section>
                <div class="section-heading">
                    <div><p class="eyebrow">GỢI Ý CHO BẠN</p><h2>Bốn người bạn xanh</h2></div>
                    <a href="collection.php">Xem toàn bộ bộ sưu tập ↗</a>
                </div>
                <div class="plant-grid">
                    <?php foreach ($featuredPlants as $plant): ?>
                        <?php $returnTo = 'dashboard.php'; require __DIR__ . '/includes/plant-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</div>

<?php $showSiteFooter = false; require __DIR__ . '/includes/footer.php'; ?>
