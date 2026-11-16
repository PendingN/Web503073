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
    $user = strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) ? find_user_by_email($email) : null;
    if ($user === null || strlen($password) > 72 || !password_verify($password, $user['password_hash'])) {
        $loginError = 'Email hoặc mật khẩu chưa đúng.';
    } elseif ($user['status'] === 'blocked') {
        $loginError = 'Tài khoản đang bị khóa. Vui lòng liên hệ quản trị viên.';
    } else {
        if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
            $statement = db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
            $statement->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        sign_in($user);
        set_flash('Đăng nhập thành công. Chào mừng bạn trở lại!');
        redirect_to(safe_return_url($returnTo !== '' ? $returnTo : null, $user['role'] === 'admin' ? 'admin-dashboard.php' : 'profile.php'));
    }
}

$pageTitle = 'Đăng nhập';
$pageDescription = 'Đăng nhập vào không gian xanh Là Lá La của bạn.';
$bodyClass = 'login-page auth-papercut-page';
$activePage = 'login';
$showSiteFooter = false;
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<main id="main-content" class="auth-papercut-stage" tabindex="-1">
    <section class="auth-papercut-card" aria-labelledby="login-heading">
        <div class="auth-papercut-form-col">
            <div class="auth-papercut-wrap">
                <h1 id="login-heading" class="auth-papercut-title">Đăng nhập</h1>
                <p class="auth-papercut-sub">Tiếp tục hành trình chăm cây cùng Là Lá La.</p>

                <?php if ($signedInUser = current_user()): ?>
                    <p class="login-help auth-papercut-notice">Bạn đang đăng nhập với tên <?= e($signedInUser['name']) ?>. <a href="profile.php">Mở trang cá nhân</a> hoặc dùng biểu mẫu dưới đây để đăng nhập tài khoản khác.</p>
                <?php endif; ?>

                <?php if ($flashMessage !== null): ?><div class="status-note" role="status"><?= e($flashMessage) ?></div><?php endif; ?>

                <?php if ($loginError !== null): ?>
                    <div class="login-error" id="login-error" role="alert"><?= e($loginError) ?></div>
                <?php endif; ?>

                <form class="login-form auth-papercut-form" method="post" action="login.php">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="return_to" value="<?= e($returnTo) ?>">
                    <div class="form-group">
                        <label class="form-label" for="email">Email</label>
                        <input class="form-control auth-papercut-input" id="email" name="email" type="email" value="<?= e($email) ?>" autocomplete="email" maxlength="254" placeholder="tenban@email.com" required<?= $loginError !== null ? ' aria-invalid="true" aria-describedby="login-error"' : '' ?>>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="password">Mật khẩu</label>
                        <div class="password-field">
                            <input class="form-control auth-papercut-input" id="password" name="password" type="password" autocomplete="current-password" maxlength="72" placeholder="••••••••" required<?= $loginError !== null ? ' aria-invalid="true" aria-describedby="login-error"' : '' ?>>
                            <button class="password-toggle" type="button" data-password-toggle aria-controls="password" aria-label="Hiện mật khẩu" aria-pressed="false">Hiện</button>
                        </div>
                    </div>
                    <button class="btn-brand auth-papercut-submit" type="submit">Đăng nhập</button>
                </form>

                <div class="auth-papercut-divider" aria-hidden="true"><span>hoặc</span></div>

                <div class="auth-papercut-links">
                    <p class="login-help">Chưa có tài khoản? <a href="register.php?return_to=<?= rawurlencode($returnTo) ?>">Đăng ký ngay</a></p>
                    <p class="login-help"><a href="collection.php">Khám phá bộ sưu tập cây</a></p>
                </div>
            </div>
        </div>

        <div class="auth-papercut-art" aria-hidden="true">
            <svg viewBox="0 0 520 620" preserveAspectRatio="xMidYMid slice">
                <defs>
                    <filter id="auth-paper-shadow" x="-20%" y="-10%" width="145%" height="125%">
                        <feDropShadow dx="7" dy="4" stdDeviation="9" flood-color="#0d1c13" flood-opacity="0.3"/>
                    </filter>
                    <filter id="auth-leaf-shadow" x="-15%" y="-15%" width="135%" height="135%">
                        <feDropShadow dx="-4" dy="5" stdDeviation="6" flood-color="#08140d" flood-opacity="0.34"/>
                    </filter>
                </defs>

                <!-- Deep forest botanical cavity -->
                <rect width="520" height="620" fill="#183022"/>

                <!-- Layered dark & olive botanical leaves -->
                <g filter="url(#auth-leaf-shadow)">
                    <!-- Back broad foliage -->
                    <path d="M190 40 C275 -10 410 10 510 110 C455 180 340 225 220 190 C180 155 170 95 190 40 Z" fill="#213d2c"/>
                    <path d="M165 240 C260 195 395 210 505 315 C425 405 280 420 180 365 C145 320 140 275 165 240 Z" fill="#1f3a29"/>
                    <path d="M210 430 C305 385 430 415 515 510 C445 585 300 605 205 550 C175 505 180 460 210 430 Z" fill="#254431"/>

                    <!-- Monstera & mid-tone leaves -->
                    <path d="M235 90 C320 45 435 80 485 165 L425 180 L470 210 C425 265 340 280 265 250 L315 215 L245 205 C215 170 215 125 235 90 Z" fill="#2b4f39"/>
                    <path d="M265 280 C345 235 450 265 505 345 L445 362 L492 392 C445 448 360 468 285 435 L338 398 L268 390 C242 352 242 310 265 280 Z" fill="#325a41"/>

                    <!-- Delicate fern / palm fronds in sage & matcha pistachio accents -->
                    <path d="M360 18 C320 88 285 175 265 265" stroke="#556f4d" stroke-width="4" fill="none"/>
                    <path d="M342 52 L288 42 L334 74 L276 74 L326 98 L268 108 L318 126 L262 144 L310 156 L258 182 L302 188" fill="#556f4d"/>
                    <path d="M346 58 L398 38 L354 82 L408 72 L360 108 L414 108 L364 136 L412 146 L362 166" fill="#466448"/>

                    <path d="M180 390 C255 360 335 395 390 475" stroke="#859d65" stroke-width="3.5" fill="none"/>
                    <path d="M202 382 L175 430 L226 392 L202 452 L252 406 L232 474 L278 424 L264 494 L306 442 L298 512 L334 460 L332 528 L362 474" fill="#3e6449"/>

                    <path d="M250 185 C300 220 355 275 405 340" stroke="#b5c97a" stroke-width="2.5" stroke-opacity="0.55" fill="none"/>
                    <path d="M272 204 L238 242 L292 222 L264 266 L316 244 L292 292 L342 268 L324 318 L368 292 L354 342" fill="#4f7450"/>
                </g>

                <!-- 3D Paper-cut wavy left layers casting shadows onto leaves -->
                <path d="M0 0 H282 C255 48 272 95 245 142 C215 194 260 245 232 298 C204 350 258 402 224 456 C192 508 232 560 182 620 H0 Z" fill="#dce5cc" filter="url(#auth-paper-shadow)"/>
                <path d="M0 0 H248 C222 52 242 98 214 146 C184 198 228 248 198 302 C170 354 222 405 190 458 C158 510 198 560 148 620 H0 Z" fill="#f4f5e9" filter="url(#auth-paper-shadow)"/>
                <path d="M0 0 H212 C186 55 208 102 178 152 C148 202 194 252 162 306 C132 358 184 408 152 462 C122 512 160 562 112 620 H0 Z" fill="#ffffff" filter="url(#auth-paper-shadow)"/>

                <!-- Bottom-right subtle white paper curl -->
                <path d="M520 365 C468 388 456 442 422 486 C386 532 396 582 350 620 H520 Z" fill="#eef2e2" filter="url(#auth-paper-shadow)"/>
                <path d="M520 402 C480 422 472 470 442 508 C412 546 420 588 382 620 H520 Z" fill="#ffffff" filter="url(#auth-paper-shadow)"/>
            </svg>
        </div>
    </section>
</main>

<?php require __DIR__ . '/includes/footer.php'; ?>
