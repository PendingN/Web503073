<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$plantId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
$plant = $plantId === false ? null : find_plant((int) $plantId);
$returnTo = safe_return_url($_GET['return_to'] ?? null, 'collection.php');
$returnPage = basename((string) parse_url($returnTo, PHP_URL_PATH));
$backLabel = match ($returnPage) {
    'favorites.php' => 'Quay lại cây yêu thích',
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
// Kích thước thật của ảnh: khung ảnh không được phóng to vượt quá ảnh gốc (tránh mờ).
$imageWidth = 900;
$imageHeight = 1200;
if ($plant !== null) {
    $imageSize = @getimagesize(__DIR__ . '/images/' . basename((string) $plant['image']));
    if (is_array($imageSize) && $imageSize[0] > 0 && $imageSize[1] > 0) {
        [$imageWidth, $imageHeight] = $imageSize;
    }
}
// Star ratings and comments (hidden until the review tables exist).
$viewer = current_user();
$reviewsReady = $plant !== null && plant_reviews_ready();
if ($reviewsReady) {
    $plantId = (int) $plant['id'];
    $ratingStats = plant_rating_stats($plantId);
    $ratingDistribution = plant_rating_distribution($plantId);
    $myRating = $viewer === null ? null : user_plant_rating((int) $viewer['id'], $plantId);
    $commentPage = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
    $comments = list_plant_comments($plantId, $commentPage);
    $reviewFlash = take_flash();
    $reviewError = is_string($_SESSION['plant_review_error'] ?? null) ? $_SESSION['plant_review_error'] : null;
    $draft = $_SESSION['plant_comment_draft'] ?? null;
    $commentDraft = is_array($draft) && ($draft['plant_id'] ?? null) === $plantId ? (string) $draft['content'] : '';
    unset($_SESSION['plant_review_error'], $_SESSION['plant_comment_draft']);
    $pageUrl = strtok(current_request_url(), '#');
    $loginForReviews = 'login.php?return_to=' . rawurlencode(safe_return_url($pageUrl . '#reviews', 'plant-detail.php?id=' . $plantId));
    $pageQuery = ['id' => $plantId] + (isset($_GET['return_to']) && is_string($_GET['return_to']) ? ['return_to' => $returnTo] : []);
    $starPath = 'M12 2.8l2.84 5.75 6.35.92-4.6 4.48 1.09 6.32L12 17.29l-5.68 2.98 1.09-6.32-4.6-4.48 6.35-.92z';
}
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
                <?php if ($reviewsReady): ?>
                    <a class="detail-rating-link" href="#reviews">
                        <?= plant_stars_html($ratingStats['average']) ?>
                        <span><?= $ratingStats['count'] > 0 ? e(number_format($ratingStats['average'], 1, ',', '')) . ' · ' . $ratingStats['count'] . ' đánh giá' : 'Chưa có đánh giá' ?><?= $comments['total'] > 0 ? ' · ' . $comments['total'] . ' bình luận' : '' ?></span>
                    </a>
                <?php endif; ?>
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
            <div class="detail-image" style="--img-w: <?= (int) $imageWidth ?>px; --img-ratio: <?= (int) $imageWidth ?> / <?= (int) $imageHeight ?>;"><img src="<?= e(image_url($plant['image'])) ?>" alt="<?= e($plant['name']) ?>" width="<?= (int) $imageWidth ?>" height="<?= (int) $imageHeight ?>" decoding="async" fetchpriority="high"></div>
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

        <?php if ($reviewsReady): ?>
            <section class="plant-reviews" id="reviews" aria-labelledby="reviews-heading" tabindex="-1">
                <h2 id="reviews-heading">Đánh giá và bình luận</h2>
                <?php if ($reviewFlash !== null): ?><p class="status-note" role="status"><?= e($reviewFlash) ?></p><?php endif; ?>
                <?php if ($reviewError !== null): ?><p class="login-error" id="review-error" role="alert"><?= e($reviewError) ?></p><?php endif; ?>

                <div class="reviews-layout">
                    <div class="reviews-summary">
                        <?php if ($ratingStats['count'] > 0): ?>
                            <p class="reviews-score"><strong><?= e(number_format($ratingStats['average'], 1, ',', '')) ?></strong><span>/5</span></p>
                            <?= plant_stars_html($ratingStats['average'], 'star-meter--large') ?>
                            <p class="reviews-count"><?= $ratingStats['count'] ?> lượt đánh giá</p>
                        <?php else: ?>
                            <p class="reviews-score reviews-score--empty"><strong>–</strong><span>/5</span></p>
                            <?= plant_stars_html(0, 'star-meter--large') ?>
                            <p class="reviews-count">Chưa có ai đánh giá cây này.</p>
                        <?php endif; ?>
                        <dl class="reviews-bars">
                            <?php foreach ($ratingDistribution as $stars => $total): ?>
                                <div>
                                    <dt><?= $stars ?> sao</dt>
                                    <dd><span class="reviews-bar" aria-hidden="true"><span style="--share: <?= $ratingStats['count'] > 0 ? round($total / $ratingStats['count'] * 100) : 0 ?>%"></span></span><span class="reviews-bar-count"><?= $total ?></span></dd>
                                </div>
                            <?php endforeach; ?>
                        </dl>
                    </div>

                    <div class="reviews-forms">
                        <?php if ($viewer !== null): ?>
                            <form class="rating-form" method="post" action="actions/plant-review-action.php">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="operation" value="rate">
                                <input type="hidden" name="plant_id" value="<?= $plantId ?>">
                                <input type="hidden" name="return_to" value="<?= e($pageUrl) ?>">
                                <fieldset class="star-input">
                                    <legend>Đánh giá của bạn</legend>
                                    <div class="star-input-options">
                                        <?php for ($star = 1; $star <= 5; $star++): ?>
                                            <input class="star-input-radio" type="radio" name="rating" id="rating-<?= $star ?>" value="<?= $star ?>"<?= $myRating === $star ? ' checked' : '' ?> required>
                                            <label for="rating-<?= $star ?>" title="<?= $star ?> sao"><svg viewBox="0 0 24 24" width="32" height="32" aria-hidden="true"><path d="<?= $starPath ?>"/></svg><span class="sr-only"><?= $star ?> sao</span></label>
                                        <?php endfor; ?>
                                    </div>
                                </fieldset>
                                <p class="rating-hint"><?= $myRating === null ? 'Chọn số sao rồi bấm lưu.' : 'Bạn đã chấm ' . $myRating . ' sao. Chọn lại để đổi.' ?></p>
                                <div class="rating-actions">
                                    <button class="btn-brand" type="submit"><?= $myRating === null ? 'Lưu đánh giá' : 'Cập nhật đánh giá' ?></button>
                                    <?php if ($myRating !== null): ?><button class="reviews-text-button" type="submit" form="rating-remove-form">Xóa đánh giá của tôi</button><?php endif; ?>
                                </div>
                            </form>
                            <?php if ($myRating !== null): ?>
                                <form id="rating-remove-form" class="rating-remove-form" method="post" action="actions/plant-review-action.php" data-confirm="Đánh giá <?= $myRating ?> sao của bạn cho “<?= e($plant['name']) ?>” sẽ bị xóa." data-confirm-title="Xóa đánh giá?" data-confirm-label="Xóa đánh giá" data-confirm-tone="danger">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="operation" value="unrate">
                                    <input type="hidden" name="plant_id" value="<?= $plantId ?>">
                                    <input type="hidden" name="return_to" value="<?= e($pageUrl) ?>">
                                </form>
                            <?php endif; ?>

                            <form class="comment-form" method="post" action="actions/plant-review-action.php">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="operation" value="comment">
                                <input type="hidden" name="plant_id" value="<?= $plantId ?>">
                                <input type="hidden" name="return_to" value="<?= e($pageUrl) ?>">
                                <label for="plant-comment">Bình luận của bạn</label>
                                <textarea id="plant-comment" name="content" rows="4" maxlength="<?= PLANT_COMMENT_MAX_LENGTH ?>" required placeholder="Chia sẻ kinh nghiệm trồng, chăm hoặc câu hỏi về cây này..."<?= $reviewError !== null ? ' aria-invalid="true" aria-describedby="review-error"' : '' ?>><?= e($commentDraft) ?></textarea>
                                <div class="comment-form-footer">
                                    <span>Tối đa <?= PLANT_COMMENT_MAX_LENGTH ?> ký tự</span>
                                    <button class="btn-brand" type="submit">Gửi bình luận</button>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="reviews-gate">
                                <p>Đăng nhập để chấm sao và bình luận về cây này.</p>
                                <a class="btn-brand" href="<?= e($loginForReviews) ?>">Đăng nhập</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="reviews-comments">
                    <h3>Bình luận <span>(<?= $comments['total'] ?>)</span></h3>
                    <?php if ($comments['comments'] === []): ?>
                        <p class="reviews-empty">Chưa có bình luận nào. Hãy là người đầu tiên chia sẻ về cây này.</p>
                    <?php endif; ?>
                    <?php foreach ($comments['comments'] as $comment): ?>
                        <article class="plant-comment" id="plant-comment-<?= (int) $comment['id'] ?>">
                            <div class="plant-comment-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $comment['author'], 0, 1, 'UTF-8'), 'UTF-8')) ?></div>
                            <div class="plant-comment-body">
                                <p class="plant-comment-meta">
                                    <strong><?= e($comment['author']) ?></strong>
                                    <?php if ($comment['rating'] !== null): ?><?= plant_stars_html((float) $comment['rating'], 'star-meter--small') ?><?php endif; ?>
                                    <time datetime="<?= e(str_replace(' ', 'T', (string) $comment['created_at'])) ?>"><?= e(date('d/m/Y H:i', strtotime((string) $comment['created_at']))) ?></time>
                                </p>
                                <p class="plant-comment-text"><?= e($comment['content']) ?></p>
                                <?php if ($viewer !== null && ($viewer['role'] === 'admin' || (int) $comment['user_id'] === (int) $viewer['id'])): ?>
                                    <form method="post" action="actions/plant-review-action.php" data-confirm="Bình luận này sẽ bị xóa. Thao tác này không thể hoàn tác." data-confirm-title="Xóa bình luận?" data-confirm-label="Xóa bình luận" data-confirm-tone="danger">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="operation" value="delete-comment">
                                        <input type="hidden" name="plant_id" value="<?= $plantId ?>">
                                        <input type="hidden" name="return_to" value="<?= e($pageUrl) ?>">
                                        <button class="reviews-text-button" type="submit" name="comment_id" value="<?= (int) $comment['id'] ?>">Xóa bình luận</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                    <?php if ($comments['pages'] > 1): ?>
                        <nav class="pagination" aria-label="Phân trang bình luận">
                            <?php if ($comments['page'] > 1): ?><a href="plant-detail.php?<?= e(http_build_query($pageQuery + ['page' => $comments['page'] - 1])) ?>#reviews">Trước</a><?php endif; ?>
                            <span>Trang <?= $comments['page'] ?> / <?= $comments['pages'] ?></span>
                            <?php if ($comments['page'] < $comments['pages']): ?><a href="plant-detail.php?<?= e(http_build_query($pageQuery + ['page' => $comments['page'] + 1])) ?>#reviews">Sau</a><?php endif; ?>
                        </nav>
                    <?php endif; ?>
                </div>
            </section>
        <?php endif; ?>
    </main>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
