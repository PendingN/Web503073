<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/mail.php';

/**
 * Sends one plain-text UTF-8 email. Throws RuntimeException when the message cannot be delivered.
 * The "log" driver writes the message to the log directory instead, so local setups and tests
 * never need a real mailbox.
 */
function send_mail(string $to, string $subject, string $body): void
{
    if (strlen($to) > 254 || !filter_var($to, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $to . $subject)) {
        throw new RuntimeException('Invalid email recipient or subject.');
    }
    $config = load_mail_config();
    $body = str_replace(["\r\n", "\r"], "\n", $body);
    if ($config['driver'] === 'log') {
        mail_write_log($config, $to, $subject, $body);
        return;
    }
    smtp_send($config, $to, mail_build_message($config, $to, $subject, $body));
}

function mail_encode_header(string $value): string
{
    return preg_match('/[^\x20-\x7e]/', $value) ? '=?UTF-8?B?' . base64_encode($value) . '?=' : $value;
}

function mail_build_message(array $config, string $to, string $subject, string $body): string
{
    $domain = substr(strrchr($config['from_address'], '@') ?: '@localhost', 1);
    $headers = [
        'Date: ' . date(DATE_RFC2822),
        'From: ' . mail_encode_header($config['from_name']) . ' <' . $config['from_address'] . '>',
        'To: <' . $to . '>',
        'Subject: ' . mail_encode_header($subject),
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
    return implode("\r\n", $headers) . "\r\n\r\n" . rtrim(chunk_split(base64_encode($body), 76, "\r\n")) . "\r\n";
}

function mail_write_log(array $config, string $to, string $subject, string $body): void
{
    $directory = $config['log_dir'];
    if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
        throw new RuntimeException('Cannot create the mail log directory.');
    }
    // Readable copy for developers: headers plus the decoded text body.
    $content = 'Date: ' . date(DATE_RFC2822) . "\nTo: " . $to . "\nSubject: " . $subject . "\n\n" . $body . "\n";
    $file = $directory . DIRECTORY_SEPARATOR . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.txt';
    if (file_put_contents($file, $content, LOCK_EX) === false) {
        throw new RuntimeException('Cannot write the mail log file.');
    }
}

/** Reads one SMTP reply (possibly multi-line) and checks its status code. */
function smtp_expect($socket, array $codes): string
{
    $reply = '';
    while (($line = fgets($socket, 1024)) !== false) {
        $reply .= $line;
        if (strlen($line) < 4 || $line[3] !== '-') {
            break;
        }
    }
    $code = (int) substr($reply, 0, 3);
    if (!in_array($code, $codes, true)) {
        $meta = stream_get_meta_data($socket);
        throw new RuntimeException($meta['timed_out'] ? 'SMTP server timed out.' : 'SMTP error: ' . trim($reply));
    }
    return $reply;
}

function smtp_command($socket, string $command, array $codes): string
{
    if (fwrite($socket, $command . "\r\n") === false) {
        throw new RuntimeException('Cannot write to the SMTP server.');
    }
    return smtp_expect($socket, $codes);
}

function smtp_send(array $config, string $to, string $message): void
{
    $scheme = $config['encryption'] === 'ssl' ? 'ssl://' : 'tcp://';
    $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true, 'peer_name' => $config['host']]]);
    $socket = @stream_socket_client($scheme . $config['host'] . ':' . $config['port'], $errorNumber, $errorText,
        $config['timeout'], STREAM_CLIENT_CONNECT, $context);
    if ($socket === false) {
        throw new RuntimeException('Cannot connect to the SMTP server: ' . $errorText);
    }
    try {
        stream_set_timeout($socket, $config['timeout']);
        $helo = 'EHLO ' . (preg_replace('/[^A-Za-z0-9.-]/', '', (string) gethostname()) ?: 'localhost');
        smtp_expect($socket, [220]);
        smtp_command($socket, $helo, [250]);
        if ($config['encryption'] === 'tls') {
            smtp_command($socket, 'STARTTLS', [220]);
            if (stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT) !== true) {
                throw new RuntimeException('Cannot start TLS with the SMTP server.');
            }
            smtp_command($socket, $helo, [250]);
        }
        if ($config['username'] !== '') {
            smtp_command($socket, 'AUTH LOGIN', [334]);
            smtp_command($socket, base64_encode($config['username']), [334]);
            smtp_command($socket, base64_encode($config['password']), [235]);
        }
        smtp_command($socket, 'MAIL FROM:<' . $config['from_address'] . '>', [250]);
        smtp_command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
        smtp_command($socket, 'DATA', [354]);
        // The body is base64, so no line starts with "."; dot-stuffing is still applied for safety.
        smtp_command($socket, preg_replace('/^\./m', '..', $message) . '.', [250]);
        smtp_command($socket, 'QUIT', [221]);
    } finally {
        fclose($socket);
    }
}

/**
 * Absolute URL for links inside emails. Uses MAIL_APP_URL when configured (recommended on a public
 * server, because the Host header is client-controlled); otherwise builds it from the current request.
 */
function absolute_app_url(string $path): string
{
    $configured = load_mail_config()['app_url'];
    if ($configured !== '') {
        return rtrim($configured, '/') . '/' . ltrim($path, '/');
    }
    $host = (string) ($_SERVER['HTTP_HOST'] ?? '');
    if (!preg_match('/^(?:[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*|\[[0-9A-Fa-f:.]+\])(?::\d{1,5})?$/', $host)) {
        $host = 'localhost';
    }
    $scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http';
    return $scheme . '://' . $host . app_url($path);
}
