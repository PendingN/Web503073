<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$loginError = null;
$email = '';
$returnTo = request_string($_POST, 'return_to', request_string($_GET, 'return_to'));
$flashMessage = take_flash();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $email = strtolower(trim(request_string($_POST, 'email')));
    $password = request_string($_POST, 'password');
    $retryAfter = login_throttle_begin(substr($email, 0, 254));
    if ($retryAfter > 0) {
        // Too many recent attempts: refuse without checking the password at all.
        http_response_code(429);
        header('Retry-After: ' . $retryAfter);
        $loginError = login_throttle_message($retryAfter);
    } else {
        $user = strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) ? find_user_by_email($email) : null;
        // Unknown emails are checked against a throwaway hash so response time does not reveal which accounts exist.
        $passwordValid = strlen($password) <= 72
            && password_verify($password, $user['password_hash'] ?? LOGIN_DUMMY_HASH)
            && $user !== null;
        if (!$passwordValid) {
            $loginError = 'Email hoặc mật khẩu chưa đúng.';
        } elseif ($user['status'] === 'blocked') {
            $loginError = 'Tài khoản đang bị khóa. Vui lòng liên hệ quản trị viên.';
        } else {
            login_throttle_succeeded(substr($email, 0, 254));
            if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                $statement = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
                $statement->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
            }
            sign_in($user);
            set_flash('Đăng nhập thành công. Chào mừng bạn trở lại!');
            redirect_to(safe_return_url($returnTo !== '' ? $returnTo : null, $user['role'] === 'admin' ? 'admin-dashboard.php' : 'profile.php'));
        }
    }
}

$pageTitle = 'Đăng nhập';
$pageDescription = 'Đăng nhập vào không gian xanh Là Lá La của bạn.';
$bodyClass = 'login-page auth-papercut-page';
$activePage = 'login';
$showSiteFooter = false;
$authMode = 'login';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
require __DIR__ . '/includes/auth-sliding-card.php';
require __DIR__ . '/includes/footer.php'; ?>
