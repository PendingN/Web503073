<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/password-reset.php';

// The emailed link carries a one-time token. It is exchanged for a session entry right away and the
// browser is redirected, so the token does not stay in the address bar, history, or Referer headers.
$token = request_string($_GET, 'token');
$invalidLink = false;
if ($token !== '') {
    $reset = password_reset_find_by_token($token);
    if ($reset !== null) {
        unset($_SESSION['password_reset_email']);
        password_reset_begin_session($reset);
        redirect_to(app_url('reset-password.php'));
    }
    $invalidLink = true;
    http_response_code(400);
}

$errors = [];
$reset = $invalidLink ? null : password_reset_session();
if (!$invalidLink && $reset === null) {
    set_flash('Phiên đặt lại mật khẩu đã hết hạn. Vui lòng yêu cầu mã mới.');
    redirect_to(app_url('forgot-password.php'));
}

if ($reset !== null && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $password = request_string($_POST, 'password');
    if (!valid_password($password)) {
        $errors[] = 'Mật khẩu cần ít nhất 8 ký tự và không vượt quá 72 ký tự (hoặc ít hơn nếu dùng nhiều ký tự có dấu).';
    }
    if ($password !== request_string($_POST, 'password_confirmation')) {
        $errors[] = 'Mật khẩu nhập lại chưa khớp.';
    }
    if ($errors === []) {
        if (password_reset_complete($reset, $password)) {
            set_flash('Đặt lại mật khẩu thành công. Vui lòng đăng nhập bằng mật khẩu mới.');
            redirect_to(app_url('login.php'));
        }
        set_flash('Phiên đặt lại mật khẩu đã hết hạn. Vui lòng yêu cầu mã mới.');
        redirect_to(app_url('forgot-password.php'));
    }
}

$pageTitle = 'Đặt mật khẩu mới';
$pageDescription = 'Đặt mật khẩu mới cho tài khoản Là Lá La.';
$bodyClass = 'login-page auth-papercut-page';
$activePage = 'login';
$showSiteFooter = false;
$describedBy = $errors !== [] ? ' aria-invalid="true" aria-describedby="reset-errors"' : ' aria-describedby="reset-password-hint"';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main id="main-content" class="auth-papercut-stage" tabindex="-1">
    <section class="auth-single-card" aria-labelledby="reset-heading">
        <div class="auth-papercut-wrap">
            <?php if ($invalidLink): ?>
                <h1 id="reset-heading" class="auth-papercut-title">Liên kết không còn hiệu lực</h1>
                <p class="auth-form-hint">Liên kết đặt lại mật khẩu đã hết hạn, đã được dùng, hoặc đã có một liên kết mới thay thế.</p>
                <a class="btn-brand auth-video-submit auth-single-action" href="forgot-password.php">Gửi liên kết mới</a>
            <?php else: ?>
                <h1 id="reset-heading" class="auth-papercut-title">Đặt mật khẩu mới</h1>
                <p class="auth-form-hint" id="reset-password-hint">Cho tài khoản <strong><?= e($reset['email']) ?></strong>. Mật khẩu cần ít nhất 8 ký tự. Sau khi lưu, mọi thiết bị đang đăng nhập sẽ bị đăng xuất.</p>

                <?php if ($errors !== []): ?>
                    <div class="login-error" id="reset-errors" role="alert"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div>
                <?php endif; ?>

                <form class="login-form auth-papercut-form" method="post" action="reset-password.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="text" name="username" value="<?= e($reset['email']) ?>" autocomplete="username" hidden>
                    <div class="form-group">
                        <label class="sr-only" for="reset-password">Mật khẩu mới</label>
                        <div class="password-field">
                            <input class="form-control auth-video-input" id="reset-password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" placeholder="Mật khẩu mới" required autofocus<?= $describedBy ?>>
                            <button class="password-toggle" type="button" data-password-toggle aria-controls="reset-password" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="sr-only" for="reset-password-confirmation">Nhập lại mật khẩu mới</label>
                        <div class="password-field">
                            <input class="form-control auth-video-input" id="reset-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" placeholder="Nhập lại mật khẩu mới" required<?= $describedBy ?>>
                            <button class="password-toggle" type="button" data-password-toggle aria-controls="reset-password-confirmation" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button>
                        </div>
                    </div>
                    <button class="btn-brand auth-video-submit" type="submit">Lưu mật khẩu mới</button>
                </form>
            <?php endif; ?>
            <p class="auth-single-switch"><a href="login.php">Quay lại đăng nhập</a></p>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
