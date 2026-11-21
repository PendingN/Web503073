<?php
declare(strict_types=1);

/**
 * Checks outgoing mail without a database, a real mail account or a network: the SMTP client talks to a
 * throwaway server on 127.0.0.1 (this same file started with --serve), and the log driver writes to a temp folder.
 * TLS (STARTTLS / ssl) needs a real server and certificate, so those paths are not covered here.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

// ---- Fake SMTP server (child process) -------------------------------------------------------------------------
if (($argv[1] ?? '') === '--serve') {
    [, , $transcriptFile, $portFile, $mode] = $argv + [4 => ''];
    $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    file_put_contents($portFile, (string) parse_url('tcp://' . stream_socket_get_name($server, false), PHP_URL_PORT));
    $connection = stream_socket_accept($server, 15);
    $transcript = fopen($transcriptFile, 'wb');
    $say = static function (string $line) use ($connection): void {
        fwrite($connection, $line . "\r\n");
    };
    $say('220 fake.test ready');
    $authStep = 0;
    $inData = false;
    while (($line = fgets($connection)) !== false) {
        fwrite($transcript, $line);
        if ($inData) {
            if ($line === ".\r\n") {
                $inData = false;
                $say('250 queued');
            }
            continue;
        }
        $command = strtoupper(substr($line, 0, 4));
        if ($authStep === 1) {
            $authStep = 2;
            $say('334 UGFzc3dvcmQ6');
        } elseif ($authStep === 2) {
            $authStep = 0;
            $say('235 authenticated');
        } elseif ($command === 'EHLO') {
            $say('250-fake.test');
            $say('250 AUTH LOGIN');
        } elseif ($command === 'AUTH') {
            $authStep = 1;
            $say('334 VXNlcm5hbWU6');
        } elseif ($command === 'MAIL') {
            $say('250 sender ok');
        } elseif ($command === 'RCPT') {
            $say($mode === 'reject-rcpt' ? '550 no such user' : '250 recipient ok');
        } elseif ($command === 'DATA') {
            $inData = true;
            $say('354 go ahead');
        } elseif ($command === 'QUIT') {
            $say('221 bye');
            break;
        } else {
            $say('500 unknown');
        }
    }
    fclose($transcript);
    exit(0);
}

// ---- Tests ----------------------------------------------------------------------------------------------------
$checks = 0;
function check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}

// Stand-ins for helpers that init.php normally provides.
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function app_url(string $path = ''): string
{
    return '/Web503073/' . ltrim($path, '/');
}

require_once __DIR__ . '/../includes/email-verification.php';

function set_mail_environment(array $values): void
{
    // Every key is set explicitly so a developer's config/mail.local.php never leaks into these checks.
    $defaults = ['DRIVER' => 'log', 'HOST' => '', 'PORT' => '587', 'ENCRYPTION' => 'tls', 'USERNAME' => '', 'PASSWORD' => '',
        'FROM_ADDRESS' => '', 'FROM_NAME' => 'Là Lá La', 'LOG_DIR' => '', 'APP_URL' => ''];
    foreach ($values + $defaults as $key => $value) {
        putenv('MAIL_' . $key . '=' . $value);
    }
}

/** True when $send throws a RuntimeException (includes/mailer.php reports every failure that way). */
function mail_fails(callable $send): bool
{
    try {
        $send();
    } catch (RuntimeException $exception) {
        return true;
    }
    return false;
}

/** Newest message the log driver wrote. */
function latest_logged_mail(string $logDir): string
{
    $files = glob($logDir . DIRECTORY_SEPARATOR . '*.txt') ?: [];
    sort($files);
    return $files === [] ? '' : (string) file_get_contents(end($files));
}

/** Starts the fake server, runs $client against its port, and returns the raw text the server received. */
function with_fake_smtp(string $mode, callable $client): string
{
    $transcript = tempnam(sys_get_temp_dir(), 'smtp-transcript-');
    $portFile = tempnam(sys_get_temp_dir(), 'smtp-port-');
    unlink($portFile);
    $process = proc_open([PHP_BINARY, __FILE__, '--serve', $transcript, $portFile, $mode], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    try {
        for ($wait = 0; $wait < 100 && !is_file($portFile); $wait++) {
            usleep(50000);
        }
        $port = is_file($portFile) ? (int) file_get_contents($portFile) : 0;
        check($port > 0, 'The fake SMTP server did not start.');
        $client($port);
        proc_close($process);
        $process = null;
        return (string) file_get_contents($transcript);
    } finally {
        if (is_resource($process)) {
            proc_terminate($process);
            proc_close($process);
        }
        @unlink($transcript);
        @unlink($portFile);
    }
}

$errorLog = tempnam(sys_get_temp_dir(), 'mail-errors-');
ini_set('error_log', $errorLog);
$logDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'web503073-mailtest-' . bin2hex(random_bytes(6));
$failure = null;
try {
    // Configuration.
    set_mail_environment([]);
    $config = load_mail_config();
    check($config['driver'] === 'log' && str_ends_with($config['log_dir'], '.runtime' . DIRECTORY_SEPARATOR . 'mail'), 'The log driver must be the default.');
    set_mail_environment(['DRIVER' => 'smtp', 'HOST' => 'smtp.example.test', 'USERNAME' => 'sender@example.test']);
    $config = load_mail_config();
    check($config['driver'] === 'smtp' && $config['port'] === 587 && $config['encryption'] === 'tls' && $config['from_address'] === 'sender@example.test', 'SMTP must default to STARTTLS on 587.');
    foreach ([['ENCRYPTION' => 'maybe'], ['PORT' => '0'], ['DRIVER' => 'carrier-pigeon'], ['FROM_ADDRESS' => "x@example.test\nBcc: y@example.test"],
        ['APP_URL' => 'javascript:alert(1)'], ['DRIVER' => 'smtp']] as $bad) {
        set_mail_environment($bad);
        check(mail_fails(static fn () => load_mail_config()), 'Invalid mail configuration was accepted: ' . json_encode($bad));
    }

    // Log driver.
    set_mail_environment(['LOG_DIR' => $logDir]);
    send_mail('to@example.test', 'Tiêu đề', "Dòng một\nhttps://example.test/verify-email.php?token=abc");
    check(count(glob($logDir . DIRECTORY_SEPARATOR . '*.txt') ?: []) === 1 && str_contains(latest_logged_mail($logDir), "\nTo: to@example.test\nSubject: Tiêu đề\n\nDòng một"), 'The log driver must keep a readable copy.');
    check(mail_fails(static fn () => send_mail("to@example.test\r\nBcc: attacker@example.test", 'Hi', 'x')), 'A recipient containing a line break must be refused.');
    check(mail_fails(static fn () => send_mail('to@example.test', "Hi\r\nBcc: attacker@example.test", 'x')), 'A subject containing a line break must be refused.');

    // SMTP delivery with authentication.
    $subject = 'Kích hoạt tài khoản Là Lá La';
    $text = "Xin chào Ánh,\nmở https://example.test/verify-email.php?token=abc\n.một dòng bắt đầu bằng dấu chấm\n";
    $transcript = with_fake_smtp('', static function (int $port) use ($subject, $text): void {
        set_mail_environment(['DRIVER' => 'smtp', 'HOST' => '127.0.0.1', 'PORT' => (string) $port, 'ENCRYPTION' => 'none', 'USERNAME' => 'smtp-user',
            'PASSWORD' => 'smtp-secret', 'FROM_ADDRESS' => 'no-reply@example.test', 'FROM_NAME' => 'Là Lá La']);
        send_mail('member@example.test', $subject, $text);
    });
    check(str_contains($transcript, "AUTH LOGIN\r\n" . base64_encode('smtp-user') . "\r\n" . base64_encode('smtp-secret') . "\r\n"), 'SMTP must authenticate with AUTH LOGIN.');
    check(str_contains($transcript, "MAIL FROM:<no-reply@example.test>\r\nRCPT TO:<member@example.test>\r\nDATA\r\n"), 'SMTP must send the envelope before DATA.');
    check(str_contains($transcript, 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=') && str_contains($transcript, 'To: <member@example.test>')
        && str_contains($transcript, 'Content-Type: text/plain; charset=UTF-8'), 'The message needs encoded headers.');
    $message = substr($transcript, strpos($transcript, "DATA\r\n") + 6);
    $body = substr($message, strpos($message, "\r\n\r\n") + 4);
    $body = substr($body, 0, strrpos($body, "\r\n.\r\n"));
    check(base64_decode(str_replace("\r\n", '', $body), true) === $text, 'The text body must survive encoding intact.');
    check(str_contains($transcript, "\r\n.\r\nQUIT\r\n"), 'The message must end with a lone dot, followed by QUIT.');

    // Without credentials the client must not authenticate.
    $transcript = with_fake_smtp('', static function (int $port): void {
        set_mail_environment(['DRIVER' => 'smtp', 'HOST' => '127.0.0.1', 'PORT' => (string) $port, 'ENCRYPTION' => 'none', 'FROM_ADDRESS' => 'no-reply@example.test']);
        send_mail('member@example.test', 'Hi', 'x');
    });
    check(!str_contains($transcript, 'AUTH'), 'Without credentials the client must not authenticate.');

    // Refusals: a rejected recipient, no server, and a password that would cross the network unencrypted.
    with_fake_smtp('reject-rcpt', static function (int $port): void {
        set_mail_environment(['DRIVER' => 'smtp', 'HOST' => '127.0.0.1', 'PORT' => (string) $port, 'ENCRYPTION' => 'none', 'FROM_ADDRESS' => 'no-reply@example.test']);
        check(mail_fails(static fn () => send_mail('gone@example.test', 'Hi', 'x')), 'A rejected recipient must be reported as a failure.');
    });
    set_mail_environment(['DRIVER' => 'smtp', 'HOST' => '127.0.0.1', 'PORT' => '1', 'ENCRYPTION' => 'none', 'FROM_ADDRESS' => 'no-reply@example.test', 'PASSWORD' => 'x']);
    check(mail_fails(static fn () => send_mail('member@example.test', 'Hi', 'x')), 'An unreachable server must be reported as a failure.');
    set_mail_environment(['DRIVER' => 'smtp', 'HOST' => 'smtp.example.test', 'ENCRYPTION' => 'none', 'USERNAME' => 'u@example.test', 'PASSWORD' => 'smtp-secret']);
    check(mail_fails(static fn () => send_mail('member@example.test', 'Hi', 'x')), 'Credentials must not be sent over an unencrypted remote connection.');

    // Activation links never follow a forged Host header; a failed send is reported, not thrown.
    $_SERVER['HTTP_HOST'] = 'evil.example';
    set_mail_environment(['LOG_DIR' => $logDir]);
    $before = count(glob($logDir . DIRECTORY_SEPARATOR . '*.txt') ?: []);
    check(email_base_url() === null, 'A non-local Host header must not be used for activation links.');
    check(!send_verification_email(['name' => 'Ánh', 'email' => 'member@example.test'], str_repeat('a', 64))
        && count(glob($logDir . DIRECTORY_SEPARATOR . '*.txt') ?: []) === $before, 'Without an app URL, no activation email may be built from a forged Host.');
    check(!str_contains((string) file_get_contents($errorLog), 'smtp-secret'), 'Failures must be logged without secrets.');
    $_SERVER['HTTP_HOST'] = 'localhost:8090';
    check(email_base_url() === 'http://localhost:8090/Web503073', 'A localhost request may build its own links.');
    $_SERVER['HTTP_HOST'] = 'evil.example';
    set_mail_environment(['LOG_DIR' => $logDir, 'APP_URL' => 'https://garden.example/Web503073/']);
    check(email_base_url() === 'https://garden.example/Web503073', 'The configured app URL must win over the Host header.');
    $token = str_repeat('b', 64);
    check(send_verification_email(['name' => '<b>Ánh</b>', 'email' => 'member@example.test'], $token), 'The activation email must be sent when the app URL is set.');
    $body = latest_logged_mail($logDir);
    check(str_contains($body, 'https://garden.example/Web503073/verify-email.php?token=' . $token) && str_contains($body, 'Xin chào <b>Ánh</b>,'), 'The activation email must carry the link.');
    set_mail_environment(['DRIVER' => 'smtp', 'HOST' => '127.0.0.1', 'PORT' => '1', 'ENCRYPTION' => 'none', 'FROM_ADDRESS' => 'no-reply@example.test', 'PASSWORD' => 'x', 'APP_URL' => 'https://garden.example/']);
    check(!send_verification_email(['name' => 'Ánh', 'email' => 'member@example.test'], $token), 'A failed activation email must return false instead of throwing.');
} catch (Throwable $exception) {
    $failure = $exception->getMessage();
} finally {
    if (is_dir($logDir)) {
        array_map('unlink', glob($logDir . DIRECTORY_SEPARATOR . '*.txt') ?: []);
        rmdir($logDir);
    }
    @unlink($errorLog);
}
if ($failure !== null) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
    exit(1);
}
fwrite(STDOUT, 'PASS: ' . $checks . ' mail checks.' . PHP_EOL);
