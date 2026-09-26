<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$plantId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$plant = $plantId === false ? null : find_plant((int) $plantId);
if ($plant === null) {
    http_response_code(404);
}
$pageTitle = $plant === null ? 'Không tìm thấy cây' : $plant['name'];
$bodyClass = 'detail-page';
$activePage = 'collection';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<?php if ($plant === null): ?>
    <main class="not-found">
        <p class="eyebrow">404 · KHÔNG TÌM THẤY</p>
        <h1>Cây này chưa có trong khu vườn.</h1>
        <p>Đường dẫn bạn mở không trỏ tới một giống cây hợp lệ. Hãy quay lại bộ sưu tập để tiếp tục khám phá.</p>
        <a class="btn-brand" href="collection.php">Về bộ sưu tập ↗</a>
    </main>
<?php else: ?>
    <main class="detail-main">
        <a class="back-link" href="collection.php">← Quay lại bộ sưu tập</a>
        <article class="plant-detail">
            <div class="detail-image"><img src="images/<?= e($plant['image']) ?>" alt="<?= e($plant['name']) ?>"></div>
            <div class="detail-copy">
                <p class="plant-category"><?= e($plant['category']) ?></p>
                <h1><?= e($plant['name']) ?></h1>
                <p class="detail-description"><?= e($plant['description']) ?></p>
                <div class="care-box"><strong>Gợi ý chăm sóc</strong><?= e($plant['care']) ?></div>
                <div class="dashboard-actions">
                    <form class="favorite-form" method="post" action="actions/favorite-action.php" style="position:static;">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="plant_id" value="<?= (int) $plant['id'] ?>">
                        <input type="hidden" name="return_to" value="<?= e(current_request_url()) ?>">
                        <button class="btn-brand" type="submit"><?= is_favorite((int) $plant['id']) ? '♥ Đã yêu thích' : '♡ Thêm vào yêu thích' ?></button>
                    </form>
                    <a class="btn-outline-brand" href="collection.php?category=<?= rawurlencode($plant['category']) ?>">Xem cây cùng nhóm</a>
                </div>
            </div>
        </article>
    </main>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
