<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $loaded = false;
    static $user = null;
    if (!$loaded) {
        $loaded = true;
        $id = $_SESSION['user_id'] ?? null;
        if (is_int($id)) {
            $record = find_user($id);
            if ($record !== null && $record['status'] === 'active'
                && (int) $record['session_version'] === ($_SESSION['auth_version'] ?? null)) {
                unset($record['password_hash']);
                $user = $record;
            } else {
                unset($_SESSION['user_id'], $_SESSION['auth_version']);
                session_regenerate_id(true);
                set_flash('Phiên đăng nhập đã kết thúc. Vui lòng đăng nhập lại; tài khoản bị khóa cần được quản trị viên mở khóa.');
            }
        }
    }
    return $user;
}

function is_admin(): bool
{
    return (current_user()['role'] ?? '') === 'admin';
}

function require_login(?string $returnTo = null): array
{
    $user = current_user();
    if ($user === null) {
        if (!isset($_SESSION['flash_message'])) {
            set_flash('Vui lòng đăng nhập để tiếp tục.');
        }
        $target = safe_return_url($returnTo ?? current_request_url(), 'dashboard.php');
        redirect_to(app_url('login.php') . '?return_to=' . rawurlencode($target));
    }
    return $user;
}

function require_admin(): array
{
    $user = require_login('admin.php');
    if ($user['role'] !== 'admin') {
        abort_request(403, 'Bạn không có quyền truy cập trang quản trị.');
    }
    return $user;
}

function sign_in(array $user): void
{
    session_regenerate_id(true);
    unset($_SESSION['favorites'], $_SESSION['user_email']);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['auth_version'] = (int) $user['session_version'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    $statement = db()->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = ?');
    $statement->execute([$user['id']]);
}

function account_errors(string $name, string $email): array
{
    $errors = [];
    if (mb_strlen($name, 'UTF-8') < 2 || mb_strlen($name, 'UTF-8') > 100) {
        $errors[] = 'Tên cần có từ 2 đến 100 ký tự.';
    }
    if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vui lòng nhập địa chỉ email hợp lệ.';
    }
    return $errors;
}

function valid_password(string $password): bool
{
    // PASSWORD_DEFAULT uses bcrypt on the supported PHP 8.0 runtime.
    return mb_strlen($password, 'UTF-8') >= 8 && strlen($password) <= 72;
}
