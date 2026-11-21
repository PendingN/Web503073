<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/mail.php';

/**
 * Minimal outgoing mail: one recipient, a text part and an HTML part, sent over SMTP
 * (STARTTLS, implicit TLS, or plain text for a local test server) or written to a log folder.
 * PHP's mail() is avoided on purpose: it depends on server-wide sendmail settings that XAMPP leaves unset.
 */

/** Sends one message. Never throws: a mail failure must not break registration. Returns whether it was delivered. */
function send_mail(string $to, string $subject, string $text, string $html): bool
{
    try {
        $config = load_mail_config();
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid recipient address.');
        }
        if ($config['driver'] === 'log') {
            mail_write_log($config, $to, $subject, $text);
            return true;
        }
        mail_smtp_deliver($config, $config['from_email'], $to, mail_build_message($config, $to, $subject, $text, $html));
        return true;
    } catch (Throwable $exception) {
        error_log('Cannot send email: ' . $exception->getMessage());
        return false;
    }
}

function mail_encode_header(string $value): string
{
    return '=?UTF-8?B?' . base64_encode($value) . '?=';
}

function mail_build_message(array $config, string $to, string $subject, string $text, string $html): string
{
    $boundary = 'b' . bin2hex(random_bytes(12));
    $domain = substr(strrchr($config['from_email'], '@'), 1);
    $part = static fn (string $type, string $body): string => '--' . $boundary . "\r\n"
        . 'Content-Type: ' . $type . "; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
        . chunk_split(base64_encode($body), 76, "\r\n");
    // Subject and display name are base64-encoded and addresses were validated, so no header can be injected.
    return implode("\r\n", [
        'Date: ' . date('r'),
        'From: ' . mail_encode_header($config['from_name']) . ' <' . $config['from_email'] . '>',
        'To: <' . $to . '>',
        'Subject: ' . mail_encode_header($subject),
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
        'MIME-Version: 1.0',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        '',
        $part('text/plain', $text) . $part('text/html', $html) . '--' . $boundary . '--',
        '',
    ]);
}

/** Development driver: keeps the readable message in .runtime/mail/ so the link can be opened by hand. */
function mail_write_log(array $config, string $to, string $subject, string $text): void
{
    $directory = $config['log_dir'];
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create mail log folder: ' . $directory);
    }
    // Microseconds keep file names in sending order, so the newest message sorts last.
    $file = $directory . DIRECTORY_SEPARATOR . sprintf('%s-%06d-%s.eml', date('Ymd-His'), (int) (fmod(microtime(true), 1) * 1000000), bin2hex(random_bytes(3)));
    $content = "To: $to\r\nSubject: $subject\r\n\r\n" . $text;
    if (file_put_contents($file, $content, LOCK_EX) === false) {
        throw new RuntimeException('Cannot write mail log file: ' . $file);
    }
}

function mail_host_is_loopback(string $host): bool
{
    return in_array(strtolower($host), ['localhost', '127.0.0.1', '::1', '[::1]'], true);
}

function mail_smtp_deliver(array $config, string $from, string $to, string $message): void
{
    if ($config['host'] === '') {
        throw new RuntimeException('MAIL_HOST is empty.');
    }
    if ($config['username'] !== '' && $config['encryption'] === 'none' && !mail_host_is_loopback($config['host'])) {
        throw new RuntimeException('Refusing to send the SMTP password without encryption.');
    }
    $context = stream_context_create(['ssl' => [
        'verify_peer' => $config['verify_peer'], 'verify_peer_name' => $config['verify_peer'],
        'peer_name' => $config['host'], 'SNI_enabled' => true,
    ]]);
    $socket = @stream_socket_client(($config['encryption'] === 'ssl' ? 'ssl://' : 'tcp://') . $config['host'] . ':' . $config['port'],
        $errorCode, $error, (float) $config['timeout'], STREAM_CLIENT_CONNECT, $context);
    if ($socket === false) {
        throw new RuntimeException('Cannot connect to SMTP server ' . $config['host'] . ':' . $config['port'] . ' (' . $error . ').');
    }
    stream_set_timeout($socket, $config['timeout']);
    try {
        $clientName = preg_replace('/[^A-Za-z0-9.-]/', '', (string) gethostname()) ?: 'localhost';
        mail_smtp_expect($socket, [220]);
        mail_smtp_command($socket, 'EHLO ' . $clientName, [250]);
        if ($config['encryption'] === 'tls') {
            mail_smtp_command($socket, 'STARTTLS', [220]);
            if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT) !== true) {
                throw new RuntimeException('SMTP server refused the TLS handshake.');
            }
            mail_smtp_command($socket, 'EHLO ' . $clientName, [250]);
        }
        if ($config['username'] !== '') {
            mail_smtp_command($socket, 'AUTH LOGIN', [334]);
            mail_smtp_command($socket, base64_encode($config['username']), [334], false);
            mail_smtp_command($socket, base64_encode($config['password']), [235], false);
        }
        mail_smtp_command($socket, 'MAIL FROM:<' . $from . '>', [250]);
        mail_smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        mail_smtp_command($socket, 'DATA', [354]);
        // Normalize line endings, then double any leading dot so a body line of "." cannot end the message.
        $data = preg_replace('/^\./m', '..', preg_replace("/\r\n|\r|\n/", "\r\n", $message));
        mail_smtp_command($socket, rtrim($data, "\r\n") . "\r\n.", [250]);
        @fwrite($socket, "QUIT\r\n");
    } finally {
        fclose($socket);
    }
}

/** @param resource $socket */
function mail_smtp_command($socket, string $line, array $accepted, bool $logLine = true): string
{
    if (@fwrite($socket, $line . "\r\n") === false) {
        throw new RuntimeException('SMTP connection closed while sending ' . ($logLine ? strtok($line, ' ') : 'credentials') . '.');
    }
    return mail_smtp_expect($socket, $accepted);
}

/** Reads one (possibly multi-line) reply and checks its status code. @param resource $socket */
function mail_smtp_expect($socket, array $accepted): string
{
    $reply = '';
    for ($lines = 0; $lines < 100; $lines++) {
        $line = fgets($socket, 1024);
        if ($line === false) {
            throw new RuntimeException('SMTP server stopped responding.');
        }
        $reply .= $line;
        if (preg_match('/^\d{3} /', $line)) {
            $code = (int) substr($line, 0, 3);
            if (!in_array($code, $accepted, true)) {
                throw new RuntimeException('Unexpected SMTP reply: ' . trim($reply));
            }
            return $reply;
        }
    }
    throw new RuntimeException('SMTP reply was too long.');
}

/** HTML body for a one-button transactional email (inline styles: mail clients ignore stylesheets). */
function mail_button_html(string $heading, string $name, string $lead, string $link, string $button, string $footnote): string
{
    return '<!doctype html><html lang="vi"><body style="margin:0;padding:24px;background:#f4f5e9;font-family:Arial,sans-serif;color:#183022">'
        . '<div style="max-width:520px;margin:auto;padding:28px;background:#ffffff;border-radius:12px">'
        . '<h1 style="margin:0 0 16px;font-size:22px">' . e($heading) . '</h1>'
        . '<p style="line-height:1.6">Xin chào ' . e($name) . ',</p>'
        . '<p style="line-height:1.6">' . e($lead) . '</p>'
        . '<p style="margin:24px 0"><a href="' . e($link) . '" style="display:inline-block;padding:12px 24px;border-radius:999px;background:#183022;color:#f4f5e9;text-decoration:none;font-weight:bold">' . e($button) . '</a></p>'
        . '<p style="font-size:13px;line-height:1.6;color:#526857">Nếu nút không hoạt động, hãy sao chép liên kết này vào trình duyệt:<br>' . e($link) . '</p>'
        . '<p style="font-size:13px;line-height:1.6;color:#526857">' . e($footnote) . '</p>'
        . '</div></body></html>';
}
