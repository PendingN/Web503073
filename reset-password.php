<?php
declare(strict_types=1);

// Target of the link in the reset email. The form is shown only for a live token; the token is consumed
// by the POST that sets the new password, so a link works once.
require_once __DIR__ . '/includes/init.php';

$isPost = ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
$token = request_string($isPost ? $_POST : $_GET, 'token');
$deadLink = static function (): void {
    set_flash('Liên kết đặt lại mật khẩu không hợp lệ, đã được dùng hoặc đã hết hạn. Hãy yêu cầu liên kết mới.');
    redirect_to(app_url('forgot-password.php'));
};

$errors = [];
if ($isPost) {
    require_csrf();
    $password = request_string($_POST, 'password');
    if (!valid_password($password)) {
        $errors[] = 'Mật khẩu cần ít nhất 8 ký tự và không vượt quá 72 ký tự (hoặc ít hơn nếu dùng nhiều ký tự có dấu).';
    }
    if ($password !== request_string($_POST, 'password_confirmation')) {
        $errors[] = 'Mật khẩu nhập lại chưa khớp.';
    }
    if ($errors === []) {
        if (reset_password_by_token($token, $password) === null) {
            $deadLink();
        }
        set_flash('Đã đặt lại mật khẩu. Hãy đăng nhập bằng mật khẩu mới; các phiên đăng nhập cũ đã bị đăng xuất.');
        redirect_to(app_url('login.php'));
    }
}
if (find_password_reset_user($token) === null) {
    $deadLink();
}

$flashMessage = null;
$recoveryMode = 'reset';
$pageTitle = 'Đặt lại mật khẩu';
$pageDescription = 'Đặt mật khẩu mới cho tài khoản Là Lá La.';
$bodyClass = 'login-page auth-papercut-page';
$activePage = 'login';
$showSiteFooter = false;
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
require __DIR__ . '/includes/auth-recovery-card.php';
require __DIR__ . '/includes/footer.php'; ?>
