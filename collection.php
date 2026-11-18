<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$search = mb_substr(trim(request_string($_GET, 'search')), 0, 200, 'UTF-8');
$category = trim(request_string($_GET, 'category'));
$light = trim(request_string($_GET, 'light'));
if (!in_array($light, ['bright', 'indirect', 'low'], true)) {
    $light = '';
}
$sortOptions = plant_sort_options();
$sort = trim(request_string($_GET, 'sort'));
if (!array_key_exists($sort, $sortOptions)) {
    $sort = '';
}
$catalog = plant_catalog_statistics();
$categories = $catalog['categories'];

$filteredPlants = list_plants($search, $category, $light, $sort);
$suggestReturn = safe_return_url(current_request_url());
$searchSuggestions = array_map(static fn (array $plant): array => [
    'name' => $plant['name'],
    'category' => $plant['category'],
    'image' => image_url($plant['image']),
    'url' => 'plant-detail.php?' . http_build_query(['id' => (int) $plant['id'], 'return_to' => $suggestReturn]),
], list_plants());
$pageScripts = ['js/search-suggest.js'];

$pageTitle = 'Bộ sưu tập';
$bodyClass = 'dashboard-page';
$dashboardActive = 'collection';
$activePage = 'collection';
$flashMessage = take_flash();
require __DIR__ . '/includes/header.php';
$hideNavLogout = true;
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
                </div>
            </header>

            <form class="catalog-toolbar" method="get" action="collection.php" role="search" id="results">
                <div class="catalog-search" data-search-suggest="<?= e(json_encode($searchSuggestions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>">
                    <label class="sr-only" for="plant-search">Tìm kiếm cây</label>
                    <input id="plant-search" type="search" name="search" value="<?= e($search) ?>" placeholder="Tìm tên hoặc mô tả cây" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="plant-search-list">
                    <ul class="search-suggest" id="plant-search-list" role="listbox" aria-label="Gợi ý cây" hidden></ul>
                    <button type="submit" aria-label="Tìm kiếm">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
                    </button>
                </div>
                <?php if ($search !== '' || $category !== '' || $light !== '' || $sort !== ''): ?><a class="catalog-reset" href="collection.php">Xóa lọc</a><?php endif; ?>
                <div class="catalog-sort">
                    <label class="catalog-label" for="plant-sort">Sắp xếp:</label>
                    <select id="plant-sort" name="sort" data-auto-submit>
                        <?php foreach ($sortOptions as $value => $option): ?>
                            <option value="<?= e($value) ?>"<?= $sort === $value ? ' selected' : '' ?>><?= e($option['label']) ?></option>
                        <?php endforeach; ?>
                        <option value="rating" disabled>Đánh giá cao nhất (sắp có)</option>
                    </select>
                    <span class="catalog-count" aria-live="polite"><?= count($filteredPlants) ?> sản phẩm</span>
                </div>
            </form>

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
