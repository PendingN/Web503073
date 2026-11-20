<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
$returnTo = safe_return_url($_POST['return_to'] ?? $_GET['return_to'] ?? null, 'profile.php');
if (current_user() !== null) {
    redirect_to($returnTo);
}
$errors = [];
$name = '';
$email = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $name = trim(request_string($_POST, 'name'));
    $email = strtolower(trim(request_string($_POST, 'email')));
    $password = request_string($_POST, 'password');
    $confirmation = request_string($_POST, 'password_confirmation');
    $errors = account_errors($name, $email);
    if (!valid_password($password)) {
        $errors[] = 'Mật khẩu cần ít nhất 8 ký tự và không vượt quá 72 ký tự (hoặc ít hơn nếu dùng nhiều ký tự có dấu).';
    }
    if ($password !== $confirmation) {
        $errors[] = 'Mật khẩu nhập lại chưa khớp.';
    }
    if ($errors === [] && email_in_use($email)) {
        $errors[] = 'Email này đã được đăng ký.';
    }
    if ($errors === []) {
        try {
            // Public registration always creates an ordinary user.
            db()->beginTransaction();
            $id = create_user($name, $email, $password);
            record_activity(['id' => $id, 'name' => $name], 'account.created', 'Đăng ký tài khoản thành viên', ['id' => $id, 'name' => $name]);
            db()->commit();
            sign_in(find_user($id));
            set_flash('Đăng ký thành công. Chào mừng bạn đến với khu vườn!');
            redirect_to($returnTo);
        } catch (PDOException $exception) {
            if (db()->inTransaction()) {
                db()->rollBack();
            }
            if (!duplicate_email_error($exception)) {
                throw $exception;
            }
            $errors[] = 'Email này đã được đăng ký.';
        }
    }
}
$pageTitle = 'Đăng ký';
$pageDescription = 'Tạo tài khoản mới tại Là Lá La để lưu những giống cây yêu thích.';
$bodyClass = 'login-page auth-papercut-page';
$activePage = 'login';
$showSiteFooter = false;
$authMode = 'register';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
require __DIR__ . '/includes/auth-sliding-card.php';
require __DIR__ . '/includes/footer.php'; ?>
