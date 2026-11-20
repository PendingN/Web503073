<?php
declare(strict_types=1);

// Tickets for the real-time co-editing server (realtime/server.js). Both sides share one secret:
// REALTIME_SECRET, or the file .runtime/realtime-secret created by whichever side starts first.

const REALTIME_TICKET_SECONDS = 7200;

function realtime_secret(): string
{
    $fromEnvironment = getenv('REALTIME_SECRET');
    if (is_string($fromEnvironment) && $fromEnvironment !== '') {
        return $fromEnvironment;
    }
    $file = dirname(__DIR__) . '/.runtime/realtime-secret';
    if (!is_file($file)) {
        if (!is_dir(dirname($file))) {
            @mkdir(dirname($file), 0755, true);
        }
        $handle = @fopen($file, 'x');
        if ($handle !== false) {
            fwrite($handle, bin2hex(random_bytes(32)));
            fclose($handle);
            @chmod($file, 0644);
        }
    }
    $secret = is_file($file) ? trim((string) file_get_contents($file)) : '';
    if ($secret === '') {
        throw new RuntimeException('Realtime secret is unavailable.');
    }
    return $secret;
}

function base64url(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

/** A signed ticket letting this user join the co-editing room of one post. */
function realtime_ticket(array $post, array $user): string
{
    $body = base64url(json_encode([
        'post' => (int) $post['id'],
        'user' => (int) $user['id'],
        'name' => mb_substr((string) $user['name'], 0, 60, 'UTF-8'),
        'exp' => time() + REALTIME_TICKET_SECONDS,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    return $body . '.' . base64url(hash_hmac('sha256', $body, realtime_secret(), true));
}

/**
 * Where browsers connect. REALTIME_URL (e.g. wss://example.com/collab behind a proxy) wins; otherwise
 * the browser builds ws(s)://<current host>:<REALTIME_PORT>/collab, so no host is written into the code.
 */
function realtime_client_config(): array
{
    $url = getenv('REALTIME_URL');
    $port = getenv('REALTIME_PORT');
    return ['url' => is_string($url) ? $url : '', 'port' => is_string($port) && $port !== '' ? $port : '8091'];
}
