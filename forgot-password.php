<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/password-reset.php';

// Step 1 asks for the email; step 2 accepts the 6-digit code from that email.
// The link in the same email skips step 2 and opens reset-password.php directly.
$step = request_string($_GET, 'step') === 'code' ? 'code' : 'email';
$email = is_string($_SESSION['password_reset_email'] ?? null) ? $_SESSION['password_reset_email'] : '';
$errors = [];
$flashMessage = take_flash();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $operation = request_string($_POST, 'operation');
    if (!in_array($operation, ['request', 'verify'], true)) {
        abort_request(400, 'Yêu cầu không hợp lệ.');
    }
    $step = $operation === 'verify' ? 'code' : 'email';
    $email = strtolower(trim(request_string($_POST, 'email')));
    if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Vui lòng nhập địa chỉ email hợp lệ.';
    } elseif ($operation === 'request') {
        // Limits how often one address or one IP can trigger emails.
        $retryAfter = login_throttle_begin('reset:' . $email);
        if ($retryAfter > 0) {
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            $errors[] = login_throttle_message($retryAfter);
        } else {
            try {
                password_reset_request($email);
            } catch (Throwable $exception) {
                error_log('Password reset email failed: ' . $exception->getMessage());
                $errors[] = 'Hiện chưa gửi được email. Vui lòng thử lại sau ít phút.';
            }
        }
        if ($errors === []) {
            $_SESSION['password_reset_email'] = $email;
            set_flash('Nếu email này đã đăng ký, chúng tôi vừa gửi một liên kết và mã xác nhận 6 số. Hãy kiểm tra hộp thư, kể cả mục Spam.');
            redirect_to(app_url('forgot-password.php?step=code'));
        }
    } else {
        $code = preg_replace('/\s+/', '', request_string($_POST, 'code')) ?? '';
        $retryAfter = login_throttle_begin('reset-code:' . $email);
        if ($retryAfter > 0) {
            http_response_code(429);
            header('Retry-After: ' . $retryAfter);
            $errors[] = login_throttle_message($retryAfter);
        } else {
            $result = password_reset_verify_code($email, $code);
            if ($result['status'] === 'ok') {
                login_throttle_succeeded('reset-code:' . $email);
                unset($_SESSION['password_reset_email']);
                password_reset_begin_session($result['reset']);
                redirect_to(app_url('reset-password.php'));
            }
            $errors[] = $result['status'] === 'locked'
                ? 'Mã đã bị khóa do nhập sai quá nhiều lần. Vui lòng gửi lại mã mới.'
                : 'Mã xác nhận chưa đúng hoặc đã hết hạn.';
        }
    }
}

$pageTitle = 'Quên mật khẩu';
$pageDescription = 'Lấy lại mật khẩu tài khoản Là Lá La qua email.';
$bodyClass = 'login-page auth-papercut-page';
$activePage = 'login';
$showSiteFooter = false;
$describedBy = $errors !== [] ? ' aria-invalid="true" aria-describedby="reset-errors"' : '';
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>
<main id="main-content" class="auth-papercut-stage" tabindex="-1">
    <section class="auth-single-card" aria-labelledby="forgot-heading">
        <div class="auth-papercut-wrap">
            <h1 id="forgot-heading" class="auth-papercut-title">Quên mật khẩu</h1>
            <?php if ($step === 'email'): ?>
                <p class="auth-form-hint">Nhập email đã đăng ký. Chúng tôi sẽ gửi một liên kết và mã xác nhận để bạn đặt mật khẩu mới.</p>
            <?php else: ?>
                <p class="auth-form-hint">Nhập mã 6 số trong email, hoặc bấm vào liên kết trong email đó. Mã có hiệu lực <?= PASSWORD_RESET_TTL_MINUTES ?> phút.</p>
            <?php endif; ?>

            <?php if ($flashMessage !== null): ?><div class="status-note" role="status"><?= e($flashMessage) ?></div><?php endif; ?>
            <?php if ($errors !== []): ?>
                <div class="login-error" id="reset-errors" role="alert"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div>
            <?php endif; ?>

            <?php if ($step === 'email'): ?>
                <form class="login-form auth-papercut-form" method="post" action="forgot-password.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="operation" value="request">
                    <div class="form-group">
                        <label class="sr-only" for="reset-email">Email</label>
                        <input class="form-control auth-video-input" id="reset-email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" maxlength="254" placeholder="Email" required autofocus<?= $describedBy ?>>
                    </div>
                    <button class="btn-brand auth-video-submit" type="submit">Gửi liên kết và mã</button>
                </form>
            <?php else: ?>
                <form class="login-form auth-papercut-form" method="post" action="forgot-password.php?step=code">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="operation" value="verify">
                    <div class="form-group">
                        <label class="sr-only" for="reset-code-email">Email</label>
                        <input class="form-control auth-video-input" id="reset-code-email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" maxlength="254" placeholder="Email" required>
                    </div>
                    <div class="form-group">
                        <label class="sr-only" for="reset-code">Mã xác nhận 6 số</label>
                        <input class="form-control auth-video-input auth-code-input" id="reset-code" name="code" inputmode="numeric" pattern="[0-9 ]{6,7}" maxlength="7" autocomplete="one-time-code" placeholder="Mã 6 số" required autofocus<?= $describedBy ?>>
                    </div>
                    <button class="btn-brand auth-video-submit" type="submit">Xác nhận mã</button>
                </form>
                <form class="auth-resend-form" method="post" action="forgot-password.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="operation" value="request">
                    <input type="hidden" name="email" value="<?= e($email) ?>">
                    <p>Chưa nhận được email? <?php if ($email !== ''): ?><button class="auth-text-button" type="submit">Gửi lại mã</button><?php else: ?><a href="forgot-password.php">Gửi mã mới</a><?php endif; ?></p>
                </form>
            <?php endif; ?>

            <p class="auth-single-switch">Nhớ ra mật khẩu? <a href="login.php">Đăng nhập</a></p>
        </div>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
