<?php
declare(strict_types=1);

require_once __DIR__ . '/mail.php';

const EMAIL_VERIFICATION_HOURS = 24;
const EMAIL_VERIFICATION_RESEND_SECONDS = 60;

function user_email_verified(array $user): bool
{
    // A schema that predates activation has no column yet: treat those accounts as verified so a deploy
    // that runs before scripts/setup-database.php does not lock everyone out. No unverified account can exist then.
    return !array_key_exists('email_verified_at', $user) || $user['email_verified_at'] !== null;
}

/** Creates a fresh activation token for the user, replacing older ones, and returns the raw token to email. */
function issue_email_verification(int $userId): string
{
    $token = bin2hex(random_bytes(32));
    $connection = db();
    $connection->prepare('DELETE FROM email_verifications WHERE user_id = ? OR expires_at < NOW()')->execute([$userId]);
    $connection->prepare('INSERT INTO email_verifications (user_id, token_hash, expires_at) VALUES (?, ?, NOW() + INTERVAL '
        . EMAIL_VERIFICATION_HOURS . ' HOUR)')->execute([$userId, hash('sha256', $token)]);
    return $token;
}

/** False while the last link was issued less than a minute ago, so "resend" cannot be used to flood a mailbox. */
function email_verification_resend_allowed(int $userId): bool
{
    $statement = db()->prepare('SELECT 1 FROM email_verifications WHERE user_id = ? AND created_at > NOW() - INTERVAL '
        . EMAIL_VERIFICATION_RESEND_SECONDS . ' SECOND LIMIT 1');
    $statement->execute([$userId]);
    return $statement->fetchColumn() === false;
}

/**
 * Public address of the site for links in emails. The configured APP_URL always wins; without it only a
 * localhost request is trusted, because anywhere else the Host header is attacker-controlled and an
 * activation link pointing at a forged host would hand the token to that host.
 */
function email_base_url(): ?string
{
    $configured = load_mail_config()['app_url'];
    if ($configured !== '') {
        return $configured;
    }
    $host = $_SERVER['HTTP_HOST'] ?? '';
    if (!is_string($host) || !preg_match('/^(?:localhost|127\.0\.0\.1|\[::1\])(?::\d{1,5})?$/i', $host)) {
        return null;
    }
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    return $scheme . '://' . $host . rtrim(app_url(), '/');
}

/** Emails the activation link. Returns false when it could not be sent; the account and token stay valid. */
function send_verification_email(array $user, string $token): bool
{
    try {
        $baseUrl = email_base_url();
    } catch (Throwable $exception) {
        error_log('Cannot send email: ' . $exception->getMessage());
        return false;
    }
    if ($baseUrl === null) {
        error_log('Cannot send activation email: set APP_URL (or MAIL app_url in config/mail.local.php) so links do not depend on the Host header.');
        return false;
    }
    $link = $baseUrl . '/verify-email.php?token=' . $token;
    $name = (string) $user['name'];
    $text = "Xin chào $name,\n\n"
        . "Cảm ơn bạn đã đăng ký tài khoản tại Là Lá La. Hãy mở liên kết dưới đây để kích hoạt tài khoản (có hiệu lực trong "
        . EMAIL_VERIFICATION_HOURS . " giờ):\n\n$link\n\n"
        . "Nếu bạn không đăng ký, hãy bỏ qua email này. Tài khoản sẽ không được kích hoạt.\n\nLà Lá La\n";
    $html = mail_button_html('Kích hoạt tài khoản', $name,
        'Cảm ơn bạn đã đăng ký tài khoản tại Là Lá La. Nhấn nút bên dưới để kích hoạt tài khoản (có hiệu lực trong ' . EMAIL_VERIFICATION_HOURS . ' giờ).',
        $link, 'Kích hoạt tài khoản', 'Nếu bạn không đăng ký, hãy bỏ qua email này.');
    return send_mail((string) $user['email'], 'Kích hoạt tài khoản Là Lá La', $text, $html);
}

/**
 * Consumes an activation token: marks the account verified and discards its links. Returns the user,
 * or null when the token is malformed, unknown, expired or already used.
 */
function activate_account_by_token(string $token): ?array
{
    if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }
    $connection = db();
    $connection->beginTransaction();
    try {
        $statement = $connection->prepare('SELECT user_id FROM email_verifications WHERE token_hash = ? AND expires_at > NOW() FOR UPDATE');
        $statement->execute([hash('sha256', $token)]);
        $userId = $statement->fetchColumn();
        if ($userId === false) {
            $connection->rollBack();
            return null;
        }
        $statement = $connection->prepare('SELECT * FROM users WHERE id = ? FOR UPDATE');
        $statement->execute([$userId]);
        $user = $statement->fetch();
        if (!$user) {
            $connection->rollBack();
            return null;
        }
        $connection->prepare('UPDATE users SET email_verified_at = COALESCE(email_verified_at, CURRENT_TIMESTAMP) WHERE id = ?')->execute([$userId]);
        $connection->prepare('DELETE FROM email_verifications WHERE user_id = ?')->execute([$userId]);
        record_activity(['id' => (int) $user['id'], 'name' => $user['name']], 'account.verified', 'Kích hoạt tài khoản qua email', $user);
        $connection->commit();
        return $user;
    } catch (Throwable $exception) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $exception;
    }
}
