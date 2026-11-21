<?php
declare(strict_types=1);

// Target of the link in the activation email. Tokens are single-use, so a repeated visit (or an email
// scanner that opened the link first) ends on the "already used" message rather than an error page.
require_once __DIR__ . '/includes/init.php';

$user = activate_account_by_token(request_string($_GET, 'token'));
set_flash($user !== null
    ? 'Đã kích hoạt tài khoản. Bạn có thể đăng nhập ngay bây giờ.'
    : 'Liên kết kích hoạt không hợp lệ, đã được dùng hoặc đã hết hạn. Nếu tài khoản chưa kích hoạt, hãy đăng nhập để nhận liên kết mới.');
redirect_to(app_url('login.php'));
