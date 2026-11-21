<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_post();
require_csrf();

$operation = request_string($_POST, 'operation');
if (!in_array($operation, ['rate', 'unrate', 'comment', 'delete-comment'], true)) {
    abort_request(400, 'Thao tác đánh giá không hợp lệ.');
}
$plantId = filter_var($_POST['plant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$plant = $plantId === false ? null : find_plant((int) $plantId);
if ($plant === null) {
    abort_request(404, 'Không tìm thấy cây này trong bộ sưu tập.');
}
$plantId = (int) $plant['id'];

// Back to the same plant page (keeping its own "back to" link), scrolled to the reviews.
$returnTo = safe_return_url($_POST['return_to'] ?? null, 'plant-detail.php?id=' . $plantId);
if (basename((string) parse_url($returnTo, PHP_URL_PATH)) !== 'plant-detail.php') {
    $returnTo = app_url('plant-detail.php?id=' . $plantId);
}
$returnTo = strtok($returnTo, '#') . '#reviews';
$user = require_login($returnTo);
if (!plant_reviews_ready()) {
    abort_request(503, 'Tính năng đánh giá chưa sẵn sàng. Vui lòng thử lại sau.');
}

$content = '';
if ($operation === 'comment') {
    $content = trim(str_replace(["\r\n", "\r"], "\n", request_string($_POST, 'content')));
    $length = mb_strlen($content, 'UTF-8');
    if ($length === 0 || $length > PLANT_COMMENT_MAX_LENGTH) {
        $_SESSION['plant_comment_draft'] = ['plant_id' => $plantId, 'content' => mb_substr($content, 0, PLANT_COMMENT_MAX_LENGTH, 'UTF-8')];
        $_SESSION['plant_review_error'] = $length === 0 ? 'Bình luận không được để trống.' : 'Bình luận tối đa ' . PLANT_COMMENT_MAX_LENGTH . ' ký tự.';
        redirect_to($returnTo);
    }
}
$rating = null;
if ($operation === 'rate') {
    $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
    if ($rating === false) {
        $_SESSION['plant_review_error'] = 'Hãy chọn từ 1 đến 5 sao trước khi lưu.';
        redirect_to($returnTo);
    }
}

// Lock the account row so a blocked or signed-out session cannot write in parallel.
$connection = db();
$connection->beginTransaction();
try {
    $statement = $connection->prepare('SELECT status, session_version FROM users WHERE id = ? FOR UPDATE');
    $statement->execute([$user['id']]);
    $account = $statement->fetch();
    if (!$account || $account['status'] !== 'active' || (int) $account['session_version'] !== $_SESSION['auth_version']) {
        $connection->rollBack();
        abort_request(403, 'Phiên đăng nhập đã kết thúc. Vui lòng đăng nhập lại.');
    }

    $label = 'Cây #' . $plantId . ': ' . $plant['name'];
    if ($operation === 'rate') {
        $statement = $connection->prepare('INSERT INTO plant_ratings (plant_id, user_id, rating) VALUES (?, ?, ?)'
            . ' ON DUPLICATE KEY UPDATE rating = VALUES(rating)');
        $statement->execute([$plantId, $user['id'], $rating]);
        record_activity($user, 'plant.rated', $label . ' · ' . $rating . ' sao', $user);
        $message = 'Đã lưu đánh giá ' . $rating . ' sao cho ' . $plant['name'] . '.';
    } elseif ($operation === 'unrate') {
        $statement = $connection->prepare('DELETE FROM plant_ratings WHERE plant_id = ? AND user_id = ?');
        $statement->execute([$plantId, $user['id']]);
        if ($statement->rowCount() > 0) {
            record_activity($user, 'plant.unrated', $label, $user);
        }
        $message = 'Đã xóa đánh giá của bạn.';
    } elseif ($operation === 'comment') {
        $statement = $connection->prepare('INSERT INTO plant_comments (plant_id, user_id, content) VALUES (?, ?, ?)');
        $statement->execute([$plantId, $user['id'], $content]);
        record_activity($user, 'plant.commented', $label . ' · bình luận #' . $connection->lastInsertId(), $user);
        unset($_SESSION['plant_comment_draft']);
        $message = 'Đã gửi bình luận.';
    } else {
        $commentId = filter_var($_POST['comment_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $statement = $connection->prepare('SELECT c.user_id, u.name FROM plant_comments c JOIN users u ON u.id = c.user_id WHERE c.id = ? AND c.plant_id = ? FOR UPDATE');
        $statement->execute([$commentId ?: 0, $plantId]);
        $comment = $statement->fetch();
        if (!$comment) {
            $connection->rollBack();
            abort_request(404, 'Bình luận này không còn tồn tại.');
        }
        if ((int) $comment['user_id'] !== (int) $user['id'] && $user['role'] !== 'admin') {
            $connection->rollBack();
            abort_request(403, 'Bạn chỉ có thể xóa bình luận của mình.');
        }
        $connection->prepare('DELETE FROM plant_comments WHERE id = ?')->execute([$commentId]);
        record_activity($user, 'plant.comment_deleted', $label . ' · bình luận #' . $commentId, ['id' => $comment['user_id'], 'name' => $comment['name']]);
        $message = 'Đã xóa bình luận.';
    }
    $connection->commit();
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    throw $exception;
}

set_flash($message);
redirect_to($returnTo);
