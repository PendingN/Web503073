<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Tổng quan bộ sưu tập';
$bodyClass = 'dashboard-page';
$dashboardActive = 'overview';
$flashMessage = take_flash();
$featuredIds = [22, 21, 23, 27];
$featuredPlants = plants_by_ids($featuredIds);
$catalog = plant_catalog_statistics();
$activePage = 'dashboard';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/includes/dashboard-sidebar.php'; ?>
    <div class="dashboard-main">
        <?php require __DIR__ . '/includes/dashboard-toolbar.php'; ?>
        <main id="main-content" class="dashboard-content" tabindex="-1">
            <?php if ($flashMessage !== null): ?><div class="status-note" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
            <header>
                <div>
                    <h1>Chào ngày mới,<br><em>người làm vườn.</em></h1>
                    <p class="lead">Khám phá cây và chăm góc xanh mỗi ngày.</p>
                </div>
                <div class="dashboard-actions">
                    <a class="btn-brand" href="collection.php">Khám phá cây</a>
                    <a class="btn-outline-brand" href="blog.php">Đọc nhật ký</a>
                </div>
            </header>

            <section class="dashboard-hero-card" aria-label="Gợi ý chăm cây">
                <div>
                    <h2>Chăm cây cũng là<br>chăm mình.</h2>
                    <p>Kiểm tra độ ẩm đất và lau lá hôm nay.</p>
                </div>
                <div class="dashboard-hero-art">
                    <?php require __DIR__ . '/includes/plant-illustration.php'; render_plant_illustration('leaf'); ?>
                </div>
            </section>

            <section class="stats-grid" aria-label="Thống kê bộ sưu tập">
                <article class="stat-card"><span>Tổng số giống cây</span><strong><?= (int) $catalog['total'] ?></strong></article>
                <article class="stat-card"><span>Nhóm cây</span><strong><?= count($catalog['categories']) ?></strong></article>
                <article class="stat-card"><span>Đã yêu thích</span><strong><?= count(favorite_ids()) ?></strong></article>
                <article class="stat-card"><span>Bài viết chăm cây</span><strong><?= (int) count_blog_posts() ?></strong></article>
            </section>

            <section>
                <div class="section-heading">
                    <h2>Bốn người bạn xanh</h2>
                    <a href="collection.php">Xem tất cả cây</a>
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

<?php $showSiteFooter = true; require __DIR__ . '/includes/footer.php'; ?>
