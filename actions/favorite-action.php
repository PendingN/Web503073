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
$statement = $connection->prepare('DELETE FROM favorites WHERE user_id = ? AND plant_id = ?');
$statement->execute([$user['id'], $plantId]);
$removed = $statement->rowCount() > 0;
if (!$removed) {
    $statement = $connection->prepare('INSERT INTO favorites (user_id, plant_id) VALUES (?, ?)');
    $statement->execute([$user['id'], $plantId]);
}
$connection->commit();
set_flash($removed ? 'Đã xóa ' . $plant['name'] . ' khỏi mục yêu thích.' : 'Đã thêm ' . $plant['name'] . ' vào mục yêu thích.');
redirect_to($returnTo);
