<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/avatars.php';

require_post();
require_csrf();
$user = require_login('account-settings.php');

$operation = request_string($_POST, 'operation', 'upload');
$returnTo = safe_return_url($_POST['return_to'] ?? null, 'account-settings.php');
if (!in_array($operation, ['upload', 'remove'], true)) {
    abort_request(400, 'Thao tác không hợp lệ.');
}

$newAvatar = null;
if ($operation === 'upload') {
    try {
        $newAvatar = store_user_avatar($_FILES['avatar'] ?? null);
    } catch (InvalidArgumentException $exception) {
        $_SESSION['profile_errors'] = [$exception->getMessage()];
        $_SESSION['profile_section'] = 'avatar';
        redirect_to(app_url('account-settings.php'));
    }
    if ($newAvatar === null) {
        $_SESSION['profile_errors'] = ['Vui lòng chọn ảnh JPG, PNG hoặc WebP tối đa 2 MB.'];
        $_SESSION['profile_section'] = 'avatar';
        redirect_to(app_url('account-settings.php'));
    }
}

$connection = db();
$connection->beginTransaction();
try {
    $statement = $connection->prepare('SELECT id, name, status, session_version, avatar_path FROM users WHERE id = ? FOR UPDATE');
    $statement->execute([$user['id']]);
    $record = $statement->fetch();
    if (!$record || $record['status'] !== 'active' || (int) $record['session_version'] !== $_SESSION['auth_version']) {
        $connection->rollBack();
        delete_user_avatar($newAvatar);
        abort_request(403, 'Phiên đăng nhập đã kết thúc. Vui lòng đăng nhập lại.');
    }

    $oldAvatar = is_string($record['avatar_path'] ?? null) ? $record['avatar_path'] : null;
    $nextAvatar = $operation === 'remove' ? null : $newAvatar;
    $update = $connection->prepare('UPDATE users SET avatar_path = ? WHERE id = ?');
    $update->execute([$nextAvatar, $user['id']]);
    record_activity(
        $user,
        'profile.avatar',
        $operation === 'remove' ? 'Xóa ảnh đại diện' : 'Cập nhật ảnh đại diện',
        ['id' => $user['id'], 'name' => $record['name']]
    );
    $connection->commit();
} catch (Throwable $exception) {
    if ($connection->inTransaction()) {
        $connection->rollBack();
    }
    delete_user_avatar($newAvatar);
    throw $exception;
}

if ($oldAvatar !== null && $oldAvatar !== $nextAvatar) {
    delete_user_avatar($oldAvatar);
}

set_flash($operation === 'remove' ? 'Đã xóa ảnh đại diện.' : 'Ảnh đại diện đã được cập nhật.');
redirect_to($returnTo);
