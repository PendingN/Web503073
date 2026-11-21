<?php
declare(strict_types=1);

require_once __DIR__ . '/mailer.php';

/**
 * Password reset by email. One request sends both a link and a 6-digit code; either one proves
 * the user owns the mailbox. The verified reset is then held in the session (never in the URL)
 * until the new password is saved, after which every session of that account is signed out
 * and the user logs in manually.
 */
const PASSWORD_RESET_TTL_MINUTES = 15;
const PASSWORD_RESET_MAX_CODE_ATTEMPTS = 5;
const PASSWORD_RESET_RESEND_SECONDS = 60;

/**
 * Creates a reset for an active account and emails it. Unknown or blocked emails are ignored
 * silently so the page never reveals which addresses are registered. Throws when sending fails.
 */
function password_reset_request(string $email): void
{
    $user = find_user_by_email($email);
    if ($user === null || $user['status'] !== 'active') {
        return;
    }
    $connection = db();
    $statement = $connection->prepare('SELECT TIMESTAMPDIFF(SECOND, created_at, NOW()) FROM password_resets'
        . ' WHERE user_id = ? AND expires_at > NOW()');
    $statement->execute([$user['id']]);
    $age = $statement->fetchColumn();
    if ($age !== false && (int) $age < PASSWORD_RESET_RESEND_SECONDS) {
        return; // A message was sent moments ago; do not flood the mailbox.
    }

    $token = bin2hex(random_bytes(32));
    $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    $statement = $connection->prepare('INSERT INTO password_resets (user_id, token_hash, otp_hash, otp_attempts, expires_at, created_at)'
        . ' VALUES (?, ?, ?, 0, NOW() + INTERVAL ' . PASSWORD_RESET_TTL_MINUTES . ' MINUTE, NOW())'
        . ' ON DUPLICATE KEY UPDATE token_hash = VALUES(token_hash), otp_hash = VALUES(otp_hash), otp_attempts = 0,'
        . ' expires_at = VALUES(expires_at), created_at = VALUES(created_at)');
    $statement->execute([$user['id'], hash('sha256', $token), password_hash($code, PASSWORD_DEFAULT)]);

    $link = absolute_app_url('reset-password.php') . '?token=' . $token;
    $body = implode("\n", [
        'Xin chào ' . $user['name'] . ',',
        '',
        'Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản Là Lá La gắn với email này.',
        '',
        'Cách 1 - Bấm vào liên kết sau để đặt mật khẩu mới:',
        $link,
        '',
        'Cách 2 - Nhập mã xác nhận này trên trang Quên mật khẩu:',
        $code,
        '',
        'Liên kết và mã có hiệu lực trong ' . PASSWORD_RESET_TTL_MINUTES . ' phút và chỉ dùng được một lần.',
        'Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này; mật khẩu hiện tại vẫn giữ nguyên.',
        '',
        'Là Lá La',
    ]);
    try {
        send_mail($user['email'], 'Đặt lại mật khẩu Là Lá La', $body);
    } catch (Throwable $exception) {
        // An unsent reset is useless; remove it so the user can ask again immediately.
        $connection->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
        throw $exception;
    }
    record_activity($user, 'auth.password_reset_requested', 'Yêu cầu đặt lại mật khẩu qua email', $user);
}

/** Pending, unexpired reset for a link token, or null. */
function password_reset_find_by_token(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    $statement = db()->prepare('SELECT r.* FROM password_resets r JOIN users u ON u.id = r.user_id'
        . " WHERE r.token_hash = ? AND r.expires_at > NOW() AND u.status = 'active'");
    $statement->execute([hash('sha256', $token)]);
    $reset = $statement->fetch();
    return $reset ?: null;
}

/**
 * Checks a 6-digit code. Returns ['status' => 'ok', 'reset' => row], or 'invalid' (wrong code or
 * unknown email, deliberately indistinguishable) or 'locked' (too many wrong codes or expired).
 * Each attempt is reserved with one atomic UPDATE, so parallel guesses cannot exceed the limit.
 */
function password_reset_verify_code(string $email, string $code): array
{
    $statement = db()->prepare('SELECT r.* FROM password_resets r JOIN users u ON u.id = r.user_id'
        . " WHERE u.email = ? AND u.status = 'active' AND r.expires_at > NOW()");
    $statement->execute([$email]);
    $reset = $statement->fetch();
    if (!$reset) {
        password_verify($code, LOGIN_DUMMY_HASH); // Keep timing similar to a real check.
        return ['status' => 'invalid'];
    }
    $statement = db()->prepare('UPDATE password_resets SET otp_attempts = otp_attempts + 1 WHERE id = ? AND otp_attempts < ?');
    $statement->execute([$reset['id'], PASSWORD_RESET_MAX_CODE_ATTEMPTS]);
    if ($statement->rowCount() === 0) {
        return ['status' => 'locked'];
    }
    if (!preg_match('/^\d{6}$/', $code) || !password_verify($code, $reset['otp_hash'])) {
        return ['status' => (int) $reset['otp_attempts'] + 1 >= PASSWORD_RESET_MAX_CODE_ATTEMPTS ? 'locked' : 'invalid'];
    }
    return ['status' => 'ok', 'reset' => $reset];
}

/** Remembers a verified reset in a fresh session so the new-password screen can use it. */
function password_reset_begin_session(array $reset): void
{
    session_regenerate_id(true);
    $_SESSION['password_reset'] = ['id' => (int) $reset['id'], 'user_id' => (int) $reset['user_id'], 'token_hash' => $reset['token_hash']];
}

/** The verified reset of this session if it is still pending and unexpired, with the user's email and name. */
function password_reset_session(): ?array
{
    $pending = $_SESSION['password_reset'] ?? null;
    if (!is_array($pending) || !is_int($pending['id'] ?? null) || !is_int($pending['user_id'] ?? null) || !is_string($pending['token_hash'] ?? null)) {
        unset($_SESSION['password_reset']);
        return null;
    }
    $statement = db()->prepare('SELECT r.*, u.email, u.name FROM password_resets r JOIN users u ON u.id = r.user_id'
        . " WHERE r.id = ? AND r.user_id = ? AND r.token_hash = ? AND r.expires_at > NOW() AND u.status = 'active'");
    $statement->execute([$pending['id'], $pending['user_id'], $pending['token_hash']]);
    $reset = $statement->fetch();
    if (!$reset) {
        unset($_SESSION['password_reset']);
        return null;
    }
    return $reset;
}

/**
 * Saves the new password, consumes the reset, and signs the account out everywhere (including
 * this browser). Returns false when the reset was used or expired in the meantime.
 */
function password_reset_complete(array $reset, string $password): bool
{
    $connection = db();
    $connection->beginTransaction();
    try {
        $statement = $connection->prepare('SELECT id, name, email, status FROM users WHERE id = ? FOR UPDATE');
        $statement->execute([$reset['user_id']]);
        $user = $statement->fetch();
        $statement = $connection->prepare('SELECT id FROM password_resets WHERE id = ? AND token_hash = ? AND expires_at > NOW() FOR UPDATE');
        $statement->execute([$reset['id'], $reset['token_hash']]);
        if (!$user || $user['status'] !== 'active' || !$statement->fetch()) {
            $connection->rollBack();
            unset($_SESSION['password_reset']);
            return false;
        }
        $statement = $connection->prepare('UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?');
        $statement->execute([password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        $connection->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['id']]);
        record_activity($user, 'auth.password_reset', 'Đặt lại mật khẩu qua email', $user);
        $connection->commit();
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    }
    // Earlier failed logins should not lock the owner out of the new password.
    login_throttle_succeeded($user['email']);
    unset($_SESSION['password_reset'], $_SESSION['user_id'], $_SESSION['auth_version']);
    session_regenerate_id(true);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    return true;
}
