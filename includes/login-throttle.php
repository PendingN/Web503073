<?php
declare(strict_types=1);

/**
 * Slows down password guessing. Every attempt is recorded before the password is checked, so
 * parallel requests cannot slip past the limit, and a successful sign-in clears the record.
 *
 * Three independent limits apply within one window; the identity is a lower-cased email address
 * (login) or "user:<id>" (re-entering the current password on the profile page):
 *   pair     - the same identity from the same IP address (stops a guesser without locking the owner out)
 *   identity - the same identity from anywhere (stops a distributed guesser)
 *   ip       - one IP address across identities (stops password spraying)
 * Only REMOTE_ADDR is trusted; forwarded-for headers are client-controlled.
 */
const LOGIN_WINDOW_SECONDS = 900;
const LOGIN_LIMITS = ['pair' => 5, 'identity' => 20, 'ip' => 40];
// A valid bcrypt hash of a random string, used to keep unknown-email logins as slow as real ones.
const LOGIN_DUMMY_HASH = '$2y$10$aJaKjPFO9x89OyeIt6oGNuSY0hfVBYd9UYr6caAXkiwH0HYUMh2wq';

function login_throttle_ready(): bool
{
    static $ready = null;
    if ($ready === null) {
        try {
            db()->query('SELECT 1 FROM login_attempts LIMIT 1');
            $ready = true;
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1146) {
                throw $exception;
            }
            // Deployments that have not run scripts/setup-database.php yet keep working, unthrottled.
            error_log('login_attempts table is missing; run scripts/setup-database.php to enable login throttling.');
            $ready = false;
        }
    }
    return $ready;
}

function login_throttle_ip(): string
{
    $address = $_SERVER['REMOTE_ADDR'] ?? '';
    return is_string($address) && filter_var($address, FILTER_VALIDATE_IP) !== false ? $address : 'unknown';
}

function login_throttle_scopes(string $hash, string $ip): array
{
    return [
        'pair' => ['email_hash = ? AND ip = ?', [$hash, $ip]],
        'identity' => ['email_hash = ?', [$hash]],
        'ip' => ['ip = ?', [$ip]],
    ];
}

/**
 * Registers one password attempt. Returns 0 when it may proceed (the attempt stays recorded until
 * login_throttle_succeeded() clears it), or the number of seconds to wait; a refused attempt is not
 * recorded, so hammering a locked account does not extend the lock.
 */
function login_throttle_begin(string $identity): int
{
    if (!login_throttle_ready()) {
        return 0;
    }
    $hash = hash('sha256', strtolower($identity));
    $ip = login_throttle_ip();
    $connection = db();
    $connection->prepare('DELETE FROM login_attempts WHERE created_at < NOW() - INTERVAL 1 DAY')->execute();
    $connection->prepare('INSERT INTO login_attempts (email_hash, ip) VALUES (?, ?)')->execute([$hash, $ip]);
    $attemptId = (int) $connection->lastInsertId();

    $window = LOGIN_WINDOW_SECONDS;
    $exceeded = [];
    foreach (login_throttle_scopes($hash, $ip) as $name => [$condition, $parameters]) {
        $statement = $connection->prepare('SELECT COUNT(*) FROM login_attempts WHERE ' . $condition
            . ' AND created_at > NOW() - INTERVAL ' . $window . ' SECOND');
        $statement->execute($parameters);
        if ((int) $statement->fetchColumn() > LOGIN_LIMITS[$name]) {
            $exceeded[$name] = [$condition, $parameters];
        }
    }
    if ($exceeded === []) {
        return 0;
    }

    $connection->prepare('DELETE FROM login_attempts WHERE id = ?')->execute([$attemptId]);
    $retryAfter = 1;
    foreach ($exceeded as $name => [$condition, $parameters]) {
        // The window frees a slot when the oldest attempt that still counts against the limit expires.
        $statement = $connection->prepare('SELECT GREATEST(1, ' . $window . ' - TIMESTAMPDIFF(SECOND, created_at, NOW()))'
            . ' FROM login_attempts WHERE ' . $condition . ' AND created_at > NOW() - INTERVAL ' . $window . ' SECOND'
            . ' ORDER BY created_at DESC, id DESC LIMIT 1 OFFSET ' . (LOGIN_LIMITS[$name] - 1));
        $statement->execute($parameters);
        $seconds = $statement->fetchColumn();
        $retryAfter = max($retryAfter, $seconds === false ? 1 : (int) $seconds);
    }
    return $retryAfter;
}

function login_throttle_succeeded(string $identity): void
{
    if (login_throttle_ready()) {
        db()->prepare('DELETE FROM login_attempts WHERE email_hash = ?')->execute([hash('sha256', strtolower($identity))]);
    }
}

function login_throttle_message(int $seconds): string
{
    $minutes = max(1, (int) ceil($seconds / 60));
    return 'Bạn đã thử quá nhiều lần. Vui lòng thử lại sau ' . $minutes . ' phút.';
}
