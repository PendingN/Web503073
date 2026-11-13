<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$search = mb_substr(trim(request_string($_GET, 'search')), 0, 200, 'UTF-8');
$category = trim(request_string($_GET, 'category'));
$light = trim(request_string($_GET, 'light'));
if (!in_array($light, ['bright', 'indirect', 'low'], true)) {
    $light = '';
}
$catalog = plant_catalog_statistics();
$categories = $catalog['categories'];

$filteredPlants = list_plants($search, $category, $light);

$pageTitle = 'Bộ sưu tập';
$bodyClass = 'dashboard-page';
$dashboardActive = 'collection';
$activePage = 'collection';
$flashMessage = take_flash();
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="dashboard-shell">
    <?php require __DIR__ . '/includes/dashboard-sidebar.php'; ?>
    <div class="dashboard-main">
        <main id="main-content" class="dashboard-content" tabindex="-1">
            <?php if ($flashMessage !== null): ?><div class="status-note" data-auto-dismiss><?= e($flashMessage) ?></div><?php endif; ?>
            <header>
                <div>
                    <h1>Bộ sưu tập</h1>
                    <p class="lead">Khám phá <?= (int) $catalog['total'] ?> giống cây xanh phù hợp với không gian của bạn.</p>
                </div>
            </header>

            <form class="filter-panel" method="get" action="collection.php">
                <label for="plant-search">Tìm kiếm
                    <input id="plant-search" type="search" name="search" value="<?= e($search) ?>" placeholder="Tên hoặc mô tả cây">
                </label>
                <label for="plant-category">Nhóm cây
                    <select id="plant-category" name="category" data-auto-submit>
                        <option value="">Tất cả nhóm cây</option>
                        <?php foreach ($categories as $item): ?>
                            <option value="<?= e($item) ?>"<?= $category === $item ? ' selected' : '' ?>><?= e($item) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label for="plant-light">Ánh sáng
                    <select id="plant-light" name="light" data-auto-submit>
                        <option value="">Tất cả mức sáng</option>
                        <option value="bright"<?= $light === 'bright' ? ' selected' : '' ?>>Nhiều ánh sáng</option>
                        <option value="indirect"<?= $light === 'indirect' ? ' selected' : '' ?>>Ánh sáng tán xạ</option>
                        <option value="low"<?= $light === 'low' ? ' selected' : '' ?>>Ít sáng / chịu bóng</option>
                    </select>
                </label>
                <button type="submit">Lọc kết quả</button>
                <?php if ($search !== '' || $category !== '' || $light !== ''): ?><a class="btn-outline-brand" href="collection.php">Xóa bộ lọc</a><?php endif; ?>
            </form>

            <div class="section-heading" id="results">
                <h2><?= count($filteredPlants) ?> kết quả</h2>
                <span class="sample-label"><?= current_user() ? 'Bấm tim để lưu cây' : 'Đăng nhập để lưu cây' ?></span>
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

<?php $showSiteFooter = true; require __DIR__ . '/includes/footer.php'; ?>
