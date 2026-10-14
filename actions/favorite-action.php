<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_post();
require_csrf();
$returnTo = safe_return_url($_POST['return_to'] ?? null);
$user = require_login($returnTo);
$plantId = filter_var($_POST['plant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$plant = $plantId === false ? null : find_plant((int) $plantId);
if ($plant === null) {
    abort_request(404, 'Không tìm thấy cây này trong bộ sưu tập.');
}
$desiredFavorite = request_string($_POST, 'favorite');
if ($desiredFavorite !== '' && !in_array($desiredFavorite, ['0', '1'], true)) {
    abort_request(400, 'Trạng thái yêu thích không hợp lệ.');
}

// Lock the account so two favorite requests cannot toggle the same row at once.
$connection = db();
$connection->beginTransaction();
$statement = $connection->prepare('SELECT status, session_version FROM users WHERE id = ? FOR UPDATE');
$statement->execute([$user['id']]);
$account = $statement->fetch();
if (!$account || $account['status'] !== 'active' || (int) $account['session_version'] !== $_SESSION['auth_version']) {
    $connection->rollBack();
    abort_request(403, 'Phiên đăng nhập đã kết thúc. Vui lòng đăng nhập lại.');
}
$statement = $connection->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND plant_id = ?');
$statement->execute([$user['id'], $plantId]);
$wasFavorite = (bool) $statement->fetchColumn();
$favorite = $desiredFavorite === '' ? !$wasFavorite : $desiredFavorite === '1';
if ($favorite !== $wasFavorite) {
    $statement = $connection->prepare($favorite
        ? 'INSERT INTO favorites (user_id, plant_id) VALUES (?, ?)'
        : 'DELETE FROM favorites WHERE user_id = ? AND plant_id = ?');
    $statement->execute([$user['id'], $plantId]);
    record_activity($user, $favorite ? 'plant.saved' : 'plant.unsaved', 'Cây #' . $plantId . ': ' . $plant['name'], $user);
}
$statement = $connection->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?');
$statement->execute([$user['id']]);
$count = (int) $statement->fetchColumn();
$connection->commit();
$message = $favorite ? 'Đã thêm ' . $plant['name'] . ' vào mục yêu thích.' : 'Đã xóa ' . $plant['name'] . ' khỏi mục yêu thích.';
if (wants_json()) {
    json_response(['favorite' => $favorite, 'count' => $count, 'message' => $message]);
}
set_flash($message);
redirect_to($returnTo);
