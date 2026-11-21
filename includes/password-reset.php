<?php
declare(strict_types=1);

// How long a reset link stays valid. Short on purpose: it opens an account to whoever holds the mailbox.
const PASSWORD_RESET_MINUTES = 60;
const PASSWORD_RESET_RESEND_SECONDS = 60;

/** Creates a fresh reset token for the user, replacing older ones, and returns the raw token to email. */
function issue_password_reset(int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $connection = db();
    $connection->prepare('DELETE FROM password_resets WHERE user_id = ? OR expires_at < NOW()')->execute([$userId]);
    $connection->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL '
        . PASSWORD_RESET_MINUTES . ' MINUTE)')->execute([$userId, hash('sha256', $token)]);
    return $token;
}

/** False while the last link was issued less than a minute ago, so the form cannot flood a mailbox. */
function password_reset_resend_allowed(int $userId): bool
{
    $statement = db()->prepare('SELECT 1 FROM password_resets WHERE user_id = ? AND created_at > NOW() - INTERVAL '
        . PASSWORD_RESET_RESEND_SECONDS . ' SECOND LIMIT 1');
    $statement->execute([$userId]);
    return $statement->fetchColumn() === false;
}

/** The active member a live token belongs to, without consuming it (used to decide whether to show the form). */
function find_password_reset_user(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    $statement = db()->prepare("SELECT u.* FROM password_resets r JOIN users u ON u.id = r.user_id"
        . " WHERE r.token_hash = ? AND r.expires_at > NOW() AND u.status = 'active'");
    $statement->execute([hash('sha256', $token)]);
    return $statement->fetch() ?: null;
}

/** Emails the reset link. Returns false when it could not be sent. */
function send_password_reset_email(array $user, string $token): bool
{
    try {
        $baseUrl = email_base_url();
    } catch (Throwable $exception) {
        error_log('Cannot send email: ' . $exception->getMessage());
        return false;
    }
    if ($baseUrl === null) {
        error_log('Cannot send password reset email: set APP_URL (or MAIL app_url in config/mail.local.php) so links do not depend on the Host header.');
        return false;
    }
    $link = $baseUrl . '/reset-password.php?token=' . $token;
    $name = (string) $user['name'];
    $text = "Xin chào $name,\n\n"
        . "Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản này tại Là Lá La. Hãy mở liên kết dưới đây để đặt mật khẩu mới (có hiệu lực trong "
        . PASSWORD_RESET_MINUTES . " phút, dùng được một lần):\n\n$link\n\n"
        . "Nếu bạn không yêu cầu, hãy bỏ qua email này. Mật khẩu hiện tại vẫn giữ nguyên.\n\nLà Lá La\n";
    $html = mail_button_html('Đặt lại mật khẩu', $name,
        'Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản này tại Là Lá La. Nhấn nút bên dưới để đặt mật khẩu mới (có hiệu lực trong '
        . PASSWORD_RESET_MINUTES . ' phút, dùng được một lần).',
        $link, 'Đặt mật khẩu mới', 'Nếu bạn không yêu cầu, hãy bỏ qua email này. Mật khẩu hiện tại vẫn giữ nguyên.');
    return send_mail((string) $user['email'], 'Đặt lại mật khẩu Là Lá La', $text, $html);
}

/**
 * Consumes a reset token and sets the new password. Raising session_version signs the account out everywhere,
 * and every other link for it is discarded. Returns the user, or null when the token is malformed, unknown,
 * expired, already used, or belongs to a blocked account.
 */
function reset_password_by_token(string $token, string $newPassword): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    $connection = db();
    $connection->beginTransaction();
    try {
        $statement = $connection->prepare('SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW() FOR UPDATE');
        $statement->execute([hash('sha256', $token)]);
        $userId = $statement->fetchColumn();
        $user = false;
        if ($userId !== false) {
            $statement = $connection->prepare('SELECT * FROM users WHERE id = ? FOR UPDATE');
            $statement->execute([$userId]);
            $user = $statement->fetch();
        }
        if (!$user || $user['status'] !== 'active') {
            $connection->rollBack();
            return null;
        }
        $connection->prepare('UPDATE users SET password_hash = ?, session_version = session_version + 1 WHERE id = ?')
            ->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
        $connection->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$userId]);
        record_activity(['id' => (int) $user['id'], 'name' => $user['name']], 'account.password_reset', 'Đặt lại mật khẩu qua email', $user);
        $connection->commit();
        // The owner just proved control of the mailbox, so earlier wrong guesses should not keep them locked out.
        login_throttle_succeeded((string) $user['email']);
        return $user;
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    }
}
