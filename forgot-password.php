<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$errors = [];
$email = '';
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $email = strtolower(trim(request_string($_POST, 'email')));
    if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vui lòng nhập địa chỉ email hợp lệ.';
    } else {
        // Every request counts toward the login limits, whether or not the account exists, so this cannot flood mailboxes.
        $retryAfter = login_throttle_begin('reset:' . $email);
        if ($retryAfter > 0) {
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            $errors[] = login_throttle_message($retryAfter);
        } else {
            $user = find_user_by_email($email);
            if ($user !== null && $user['status'] === 'active') {
                $userId = (int) $user['id'];
                if (!user_email_verified($user)) {
                    // A reset would skip the email check: send the activation link instead.
                    if (email_verification_resend_allowed($userId)) {
                        send_verification_email($user, issue_email_verification($userId));
                    }
                } elseif (password_reset_resend_allowed($userId)) {
                    send_password_reset_email($user, issue_password_reset($userId));
                }
            }
            // The same answer for every address, so the form cannot be used to find out who is registered.
            set_flash('Nếu email này đã đăng ký, chúng tôi đã gửi hướng dẫn tiếp theo tới hộp thư (liên kết có hiệu lực ' . PASSWORD_RESET_MINUTES . ' phút, kiểm tra cả thư rác).');
            redirect_to(app_url('login.php'));
        }
    }
}

$flashMessage = take_flash();
$recoveryMode = 'request';
$pageTitle = 'Quên mật khẩu';
$pageDescription = 'Nhận liên kết đặt lại mật khẩu tài khoản Là Lá La qua email.';
$bodyClass = 'login-page auth-papercut-page';
$activePage = 'login';
$showSiteFooter = false;
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
require __DIR__ . '/includes/auth-recovery-card.php';
require __DIR__ . '/includes/footer.php'; ?>
