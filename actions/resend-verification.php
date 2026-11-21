<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_post();
require_csrf();

$email = strtolower(trim(request_string($_POST, 'email')));
$returnTo = request_string($_POST, 'return_to');
$loginUrl = app_url('login.php') . ($returnTo !== '' ? '?return_to=' . rawurlencode(safe_return_url($returnTo, 'profile.php')) : '');

if (strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Every request counts toward the login limits, valid account or not, so this cannot be used to flood mailboxes.
    $retryAfter = login_throttle_begin('verify:' . $email);
    if ($retryAfter > 0) {
        set_flash(login_throttle_message($retryAfter));
        redirect_to($loginUrl);
    }
    $user = find_user_by_email($email);
    if ($user !== null && $user['status'] === 'active' && !user_email_verified($user)
        && email_verification_resend_allowed((int) $user['id'])) {
        send_verification_email($user, issue_email_verification((int) $user['id']));
    }
}
// The same answer for every address, so the form cannot be used to find out who is registered.
set_flash('Nếu email này đang chờ kích hoạt, chúng tôi đã gửi lại liên kết. Vui lòng kiểm tra cả thư rác.');
redirect_to($loginUrl);
