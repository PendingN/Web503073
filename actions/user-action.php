<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_post();
require_csrf();
$admin = require_admin();
$id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$operation = request_string($_POST, 'operation');
$returnTo = safe_return_url($_POST['return_to'] ?? null, 'admin.php');
if ($id === false || !in_array($operation, ['update', 'block', 'unblock'], true)) {
    abort_request(400, 'Thao tác người dùng không hợp lệ.');
}
$connection = db();
$connection->beginTransaction();
$statement = $connection->prepare('SELECT * FROM users WHERE id = ? FOR UPDATE');
$statement->execute([$id]);
$user = $statement->fetch();
if (!$user) {
    $connection->rollBack();
    abort_request(404, 'Không tìm thấy tài khoản này.');
}
if ($operation !== 'update') {
    if ($user['role'] === 'admin' || (int) $user['id'] === (int) $admin['id']) {
        $connection->rollBack();
        abort_request(403, 'Không thể khóa tài khoản quản trị viên.');
    }
    $status = $operation === 'block' ? 'blocked' : 'active';
    if ($status !== $user['status']) {
        // Increasing the version prevents an old session returning after unblock.
        $statement = $connection->prepare('UPDATE users SET status = ?, session_version = session_version + 1 WHERE id = ?');
        $statement->execute([$status, $id]);
    }
    $connection->commit();
    set_flash($operation === 'block' ? 'Đã khóa tài khoản và kết thúc các phiên đăng nhập.' : 'Đã mở khóa tài khoản. Người dùng có thể đăng nhập lại.');
    redirect_to($returnTo);
}
$name = trim(request_string($_POST, 'name'));
$email = strtolower(trim(request_string($_POST, 'email')));
$errors = account_errors($name, $email);
if (email_in_use($email, (int) $id)) {
    $errors[] = 'Email này đã được đăng ký.';
}
if ($errors !== []) {
    $connection->rollBack();
    $_SESSION['admin_errors'] = $errors;
    $_SESSION['admin_input'] = ['id' => (int) $id, 'name' => $name, 'email' => $email];
    redirect_to(app_url('admin.php') . '?edit=' . $id);
}
try {
    $statement = $connection->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?');
    $statement->execute([$name, $email, $id]);
    $connection->commit();
    set_flash('Thông tin người dùng đã được cập nhật.');
} catch (PDOException $exception) {
    $connection->rollBack();
    if (!duplicate_email_error($exception)) {
        throw $exception;
    }
    $_SESSION['admin_errors'] = ['Email này đã được đăng ký.'];
    $_SESSION['admin_input'] = ['id' => (int) $id, 'name' => $name, 'email' => $email];
    redirect_to(app_url('admin.php') . '?edit=' . $id);
}
redirect_to($returnTo);
