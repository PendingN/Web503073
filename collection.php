<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$search = mb_substr(trim(request_string($_GET, 'search')), 0, 200, 'UTF-8');
$category = trim(request_string($_GET, 'category'));
$categories = array_values(array_unique(array_column($plants, 'category')));
sort($categories);

$filteredPlants = list_plants($search, $category);

$pageTitle = 'Bộ sưu tập';
$bodyClass = 'dashboard-page';
$dashboardActive = 'collection';
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
                    <p class="eyebrow"><?= count($plants) ?> GIỐNG CÂY XANH</p>
                    <h1>Bộ sưu tập</h1>
                    <p class="lead">Tìm một người bạn xanh phù hợp với ánh sáng, không gian và nhịp sống của bạn.</p>
                </div>
            </header>

            <form class="filter-panel" method="get" action="collection.php">
                <label>Tìm kiếm
                    <input type="search" name="search" value="<?= e($search) ?>" placeholder="Tên hoặc mô tả cây">
                </label>
                <label>Nhóm cây
                    <select name="category">
                        <option value="">Tất cả nhóm cây</option>
                        <?php foreach ($categories as $item): ?>
                            <option value="<?= e($item) ?>"<?= $category === $item ? ' selected' : '' ?>><?= e($item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit">Lọc kết quả</button>
                <?php if ($search !== '' || $category !== ''): ?><a class="btn-outline-brand" href="collection.php">Xóa bộ lọc</a><?php endif; ?>
            </form>

            <div class="section-heading">
                <h2><?= count($filteredPlants) ?> kết quả</h2>
                <span class="sample-label"><?= current_user() ? 'Yêu thích được lưu trong tài khoản của bạn' : 'Đăng nhập để lưu cây yêu thích' ?></span>
            </div>

            <?php if ($filteredPlants === []): ?>
                <div class="empty-state">
                    <h2>Chưa tìm thấy cây phù hợp</h2>
                    <p>Thử một từ khóa khác hoặc chọn lại nhóm cây.</p>
                    <a class="btn-outline-brand" href="collection.php">Xem toàn bộ cây</a>
                </div>
            <?php else: ?>
                <div class="plant-grid">
                    <?php foreach ($filteredPlants as $plant): ?>
                        <?php $returnTo = current_request_url(); require __DIR__ . '/includes/plant-card.php'; ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php $showSiteFooter = false; require __DIR__ . '/includes/footer.php'; ?>
