<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_post();
require_csrf();
$user = require_login('profile.php');
$name = trim(request_string($_POST, 'name'));
$email = strtolower(trim(request_string($_POST, 'email')));
$password = request_string($_POST, 'new_password');
$currentPassword = request_string($_POST, 'current_password');
$errors = account_errors($name, $email);
if (email_in_use($email, (int) $user['id'])) {
    $errors[] = 'Email này đã được đăng ký.';
}
if ($password !== '' && !valid_password($password)) {
    $errors[] = 'Mật khẩu mới cần ít nhất 8 ký tự và tối đa 72 byte.';
}
if ($password !== '' && $password !== request_string($_POST, 'password_confirmation')) {
    $errors[] = 'Mật khẩu nhập lại chưa khớp.';
}

$connection = db();
$connection->beginTransaction();
$statement = $connection->prepare('SELECT * FROM users WHERE id = ? FOR UPDATE');
$statement->execute([$user['id']]);
$record = $statement->fetch();
if (!$record || $record['status'] !== 'active' || (int) $record['session_version'] !== $_SESSION['auth_version']) {
    $connection->rollBack();
    abort_request(403, 'Phiên đăng nhập đã kết thúc. Vui lòng đăng nhập lại.');
}
$credentialsChanged = $email !== $record['email'] || $password !== '';
if ($credentialsChanged && (strlen($currentPassword) > 72 || !password_verify($currentPassword, $record['password_hash']))) {
    $errors[] = 'Cần nhập đúng mật khẩu hiện tại để đổi email hoặc mật khẩu.';
}
if ($errors !== []) {
    $connection->rollBack();
    $_SESSION['profile_errors'] = $errors;
    $_SESSION['profile_input'] = ['name' => $name, 'email' => $email];
    redirect_to(app_url('profile.php'));
}
try {
    $version = (int) $record['session_version'] + ($credentialsChanged ? 1 : 0);
    $hash = $password === '' ? $record['password_hash'] : password_hash($password, PASSWORD_DEFAULT);
    $statement = $connection->prepare('UPDATE users SET name = ?, email = ?, password_hash = ?, session_version = ? WHERE id = ?');
    $statement->execute([$name, $email, $hash, $version, $user['id']]);
    $connection->commit();
    if ($credentialsChanged) {
        session_regenerate_id(true);
        $_SESSION['auth_version'] = $version;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    set_flash('Thông tin tài khoản đã được cập nhật.');
} catch (PDOException $exception) {
    $connection->rollBack();
    if (!duplicate_email_error($exception)) {
        throw $exception;
    }
    $_SESSION['profile_errors'] = ['Email này đã được đăng ký.'];
    $_SESSION['profile_input'] = ['name' => $name, 'email' => $email];
}
redirect_to(app_url('profile.php'));
