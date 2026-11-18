<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/blog-images.php';

$pageTitle = 'Tổng quan bộ sưu tập';
$bodyClass = 'dashboard-page';
$dashboardActive = 'overview';
$activePage = 'dashboard';
$flashMessage = take_flash();
$user = current_user();

$hour = (int) date('G');
$greeting = $hour < 11 ? 'Chào buổi sáng' : ($hour < 14 ? 'Chào buổi trưa' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối'));
// Vietnamese given names come last: "Nguyễn Thanh Loan" -> "Loan".
$nameParts = $user === null ? [] : (preg_split('/\s+/u', trim((string) $user['name']), -1, PREG_SPLIT_NO_EMPTY) ?: []);
$firstName = $nameParts === [] ? '' : (string) end($nameParts);

// Favorites come newest first; fall back to the whole catalog for guests or empty lists.
$favoriteIds = favorite_ids();
$myPlants = plants_by_ids(array_slice($favoriteIds, 0, 4));
$allPlants = list_plants();
$showcasePlants = $myPlants !== [] ? $myPlants : array_slice($allPlants, 0, 4);

// Daily tip: one plant and one care line, both rotating by day so the card changes every morning.
$tipPool = $favoriteIds !== [] ? plants_by_ids($favoriteIds) : $allPlants;
$dayNumber = (int) date('z') + (int) date('Y') * 366;
$tipPlant = $tipPool !== [] ? $tipPool[$dayNumber % count($tipPool)] : null;
$tipLines = [];
if ($tipPlant !== null) {
    foreach (preg_split('/\R/u', (string) $tipPlant['care']) as $line) {
        $parts = explode(':', $line, 2);
        if (count($parts) === 2 && trim($parts[1]) !== '') {
            $tipLines[] = [trim($parts[0]), trim($parts[1])];
        }
    }
}
$tipLine = $tipLines !== [] ? $tipLines[$dayNumber % count($tipLines)] : null;

$blogStats = $user === null ? null : user_blog_statistics((int) $user['id']);
$latestPosts = array_slice(latest_blog_posts(), 0, 3);

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/includes/dashboard-sidebar.php'; ?>
    <div class="dashboard-main">
        <main id="main-content" class="dashboard-content overview" tabindex="-1">
            <?php if ($flashMessage !== null): ?><div class="status-note" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
            <header>
                <div>
                    <h1><?= e($greeting) ?>,<br><em><?= $firstName !== '' ? e($firstName) : 'người làm vườn' ?>.</em></h1>
                    <?php if ($user === null): ?><p class="lead">Đăng nhập để lưu cây và theo dõi góc xanh của bạn.</p><?php endif; ?>
                </div>
                <div class="dashboard-actions">
                    <?php if ($user === null): ?><a class="btn-outline-brand" href="login.php">Đăng nhập</a><?php endif; ?>
                    <a class="btn-brand" href="plant-advisor.php">Gợi ý chọn cây</a>
                </div>
            </header>

            <?php if ($tipPlant !== null): ?>
                <section class="dashboard-hero-card overview-tip" aria-labelledby="tip-title">
                    <div>
                        <p class="eyebrow">Mẹo hôm nay · <?= e(date('d/m')) ?></p>
                        <h2 id="tip-title"><?= e($tipPlant['name']) ?></h2>
                        <?php if ($tipLine !== null): ?><p><strong><?= e($tipLine[0]) ?>:</strong> <?= e($tipLine[1]) ?></p><?php endif; ?>
                        <a class="overview-tip-link" href="plant-detail.php?<?= e(http_build_query(['id' => (int) $tipPlant['id'], 'return_to' => 'dashboard.php'])) ?>">Xem cách chăm đầy đủ →</a>
                    </div>
                    <div class="overview-tip-image">
                        <img src="<?= e(image_url($tipPlant['image'])) ?>" alt="" width="240" height="320" loading="lazy" decoding="async">
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($blogStats !== null): ?>
                <section class="stats-grid overview-stats" aria-label="Hoạt động của bạn">
                    <a class="stat-card" href="favorites.php"><span>Cây yêu thích</span><strong><?= count($favoriteIds) ?></strong></a>
                    <a class="stat-card" href="blog.php?view=saved#blog-content"><span>Bài đã lưu</span><strong><?= $blogStats['saved'] ?></strong></a>
                    <a class="stat-card" href="blog.php?view=mine#blog-content"><span>Bài bạn viết</span><strong><?= $blogStats['posts'] ?></strong></a>
                    <a class="stat-card" href="blog.php"><span>Bình luận</span><strong><?= $blogStats['comments'] ?></strong></a>
                </section>
            <?php endif; ?>

            <section aria-labelledby="my-plants-title">
                <div class="section-heading">
                    <h2 id="my-plants-title"><?= $myPlants !== [] ? 'Cây của bạn' : 'Bắt đầu với vài người bạn xanh' ?></h2>
                    <a href="<?= $myPlants !== [] ? 'favorites.php' : 'collection.php' ?>"><?= $myPlants !== [] ? 'Xem tất cả yêu thích' : 'Xem bộ sưu tập' ?></a>
                </div>
                <?php if ($myPlants === [] && $user !== null): ?><p class="overview-hint">Bấm ♥ trên một cây để nó xuất hiện ở đây.</p><?php endif; ?>
                <div class="plant-grid">
                    <?php foreach ($showcasePlants as $plant): ?>
                        <?php $returnTo = 'dashboard.php'; require __DIR__ . '/includes/plant-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            </section>

            <a class="overview-advisor" href="plant-advisor.php">
                <span>
                    <strong>Chưa biết chọn cây gì?</strong>
                    Trả lời vài câu hỏi về không gian và thói quen, Là Lá La sẽ gợi ý cây hợp với bạn.
                </span>
                <span class="overview-advisor-cta" aria-hidden="true">Bắt đầu →</span>
            </a>

            <?php if ($latestPosts !== []): ?>
                <section aria-labelledby="latest-posts-title">
                    <div class="section-heading">
                        <h2 id="latest-posts-title">Bài viết mới</h2>
                        <a href="blog.php">Vào blog</a>
                    </div>
                    <div class="overview-posts">
                        <?php foreach ($latestPosts as $post): ?>
                            <a class="overview-post" href="blog-post.php?slug=<?= e(rawurlencode($post['slug'])) ?>">
                                <img src="<?= e(blog_cover_url($post)) ?>" alt="" width="320" height="200" loading="lazy" decoding="async">
                                <span class="overview-post-tag"><?= e($post['tag']) ?></span>
                                <strong><?= e($post['title']) ?></strong>
                                <small><?= e($post['author']) ?> · <time datetime="<?= e(str_replace(' ', 'T', $post['created_at'])) ?>"><?= e(date('d/m/Y', strtotime($post['created_at']))) ?></time></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php $showSiteFooter = true; require __DIR__ . '/includes/footer.php'; ?>
