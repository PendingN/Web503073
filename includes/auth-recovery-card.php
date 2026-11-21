<?php
declare(strict_types=1);

// Single-card layout for the password recovery pages: "request" asks for an email, "reset" sets a new password.
$isReset = ($recoveryMode ?? 'request') === 'reset';
$errors = $errors ?? [];
$email = $email ?? '';
$token = $token ?? '';
$flashMessage = $flashMessage ?? null;
$errorAttributes = $errors !== [] ? ' aria-invalid="true" aria-describedby="recovery-errors"' : '';
?>
<main id="main-content" class="auth-papercut-stage" tabindex="-1">
    <section class="auth-single-card" aria-labelledby="recovery-heading">
        <div class="auth-papercut-wrap">
            <h1 id="recovery-heading" class="auth-papercut-title"><?= $isReset ? 'Đặt mật khẩu mới' : 'Quên mật khẩu' ?></h1>
            <p class="auth-form-hint"><?= $isReset ? 'Chọn mật khẩu mới cho tài khoản của bạn' : 'Nhập email đã đăng ký, chúng tôi sẽ gửi liên kết đặt lại mật khẩu' ?></p>

            <?php if ($flashMessage !== null): ?><div class="status-note" role="status"><?= e($flashMessage) ?></div><?php endif; ?>
            <?php if ($errors !== []): ?>
                <div class="login-error" id="recovery-errors" role="alert"><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div>
            <?php endif; ?>

            <?php if ($isReset): ?>
                <form class="login-form auth-papercut-form" method="post" action="reset-password.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="token" value="<?= e($token) ?>">
                    <div class="form-group">
                        <label class="sr-only" for="reset-password">Mật khẩu mới (ít nhất 8 ký tự)</label>
                        <div class="password-field">
                            <input class="form-control auth-video-input" id="reset-password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" placeholder="Mật khẩu mới (ít nhất 8 ký tự)" required<?= $errorAttributes ?>>
                            <button class="password-toggle" type="button" data-password-toggle aria-controls="reset-password" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="sr-only" for="reset-password-confirmation">Nhập lại mật khẩu mới</label>
                        <div class="password-field">
                            <input class="form-control auth-video-input" id="reset-password-confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" maxlength="72" placeholder="Nhập lại mật khẩu mới" required<?= $errorAttributes ?>>
                            <button class="password-toggle" type="button" data-password-toggle aria-controls="reset-password-confirmation" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button>
                        </div>
                    </div>
                    <button class="btn-brand auth-video-submit" type="submit">Đặt mật khẩu</button>
                </form>
            <?php else: ?>
                <form class="login-form auth-papercut-form" method="post" action="forgot-password.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <div class="form-group">
                        <label class="sr-only" for="recovery-email">Email</label>
                        <input class="form-control auth-video-input" id="recovery-email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" maxlength="254" placeholder="Email" required<?= $errorAttributes ?>>
                    </div>
                    <button class="btn-brand auth-video-submit" type="submit">Gửi liên kết</button>
                </form>
            <?php endif; ?>

            <p class="auth-recovery-back"><a href="login.php">Quay lại đăng nhập</a></p>
        </div>
    </section>
</main>
