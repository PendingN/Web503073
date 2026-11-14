<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$plantId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$plant = $plantId === false ? null : find_plant((int) $plantId);
$returnTo = safe_return_url($_GET['return_to'] ?? null, 'collection.php');
$returnPage = basename((string) parse_url($returnTo, PHP_URL_PATH));
$backLabel = match ($returnPage) {
    'favorites.php' => 'Quay lại cây yêu thích',
    'dashboard.php' => 'Quay lại tổng quan',
    'plant-advisor.php' => 'Quay lại gợi ý chọn cây',
    default => 'Quay lại bộ sưu tập',
};
if ($plant === null) {
    http_response_code(404);
}
$careItems = [];
$labeledCareCount = 0;
if ($plant !== null) {
    $careLines = preg_split('/\R+/u', trim((string) $plant['care'])) ?: [];
    foreach ($careLines as $careLine) {
        $careLine = trim($careLine);
        if ($careLine === '') {
            continue;
        }
        $careParts = explode(':', $careLine, 2);
        if (
            count($careParts) === 2
            && trim($careParts[0]) !== ''
            && trim($careParts[1]) !== ''
            && mb_strlen(trim($careParts[0]), 'UTF-8') <= 40
        ) {
            $labeledCareCount++;
            $careItems[] = [
                'label' => trim($careParts[0]),
                'value' => trim($careParts[1]),
            ];
        } else {
            $careItems[] = [
                'label' => null,
                'value' => $careLine,
            ];
        }
    }
}
$useCareGrid = count($careItems) >= 2 && $labeledCareCount >= 2;
$pageTitle = $plant === null ? 'Không tìm thấy cây' : $plant['name'];
$bodyClass = 'detail-page';
$activePage = 'collection';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<?php if ($plant === null): ?>
    <main id="main-content" class="not-found" tabindex="-1">
        <h1>Cây này chưa có trong khu vườn.</h1>
        <p>Đường dẫn bạn mở không trỏ tới một giống cây hợp lệ. Hãy quay lại bộ sưu tập để tiếp tục khám phá.</p>
        <a class="btn-brand" href="collection.php">Về bộ sưu tập</a>
    </main>
<?php else: ?>
    <main id="main-content" class="detail-main" tabindex="-1">
        <a class="back-link" href="<?= e($returnTo) ?>"><?= e($backLabel) ?></a>
        <article class="plant-detail">
            <div class="detail-copy">
                <p class="plant-category"><?= e($plant['category']) ?></p>
                <h1><?= e($plant['name']) ?></h1>
                <p class="detail-description"><?= e($plant['description']) ?></p>
                <div class="dashboard-actions detail-actions">
                    <form class="favorite-form" data-reaction="favorite" data-plant-name="<?= e($plant['name']) ?>" method="post" action="actions/favorite-action.php">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="plant_id" value="<?= (int) $plant['id'] ?>">
                        <input type="hidden" name="return_to" value="<?= e(current_request_url()) ?>">
                        <button class="btn-outline-brand heart-button" type="submit" aria-pressed="<?= is_favorite((int) $plant['id']) ? 'true' : 'false' ?>">
                            <?php require __DIR__ . '/includes/heart-icon.php'; ?>
                            <span data-reaction-label><?= is_favorite((int) $plant['id']) ? 'Đã yêu thích' : 'Thêm vào yêu thích' ?></span>
                        </button>
                    </form>
                </div>
            </div>
            <div class="detail-image"><img src="<?= e(image_url($plant['image'])) ?>" alt="<?= e($plant['name']) ?>" width="900" height="1200" decoding="async" fetchpriority="high"></div>
            <?php if ($careItems !== []): ?>
                <div class="care-box">
                    <strong class="care-title">Gợi ý chăm sóc</strong>
                    <ul class="care-list<?= $useCareGrid ? ' care-list--grid' : '' ?>">
                        <?php foreach ($careItems as $careItem): ?>
                            <?php if ($careItem['label'] !== null): ?>
                                <li class="care-item care-item--card"><b class="care-label"><?= e($careItem['label']) ?>:</b> <span class="care-value"><?= e($careItem['value']) ?></span></li>
                            <?php else: ?>
                                <li class="care-item care-item--plain"><?= e($careItem['value']) ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </article>
    </main>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
