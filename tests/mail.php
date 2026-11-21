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
    foreach (['DRIVER', 'HOST', 'PORT', 'ENCRYPTION', 'USERNAME', 'PASSWORD', 'FROM_EMAIL', 'FROM_NAME', 'LOG_DIR', 'VERIFY_PEER'] as $key) {
        putenv('MAIL_' . $key . (isset($values[$key]) ? '=' . $values[$key] : ''));
    }
    putenv('APP_URL' . (isset($values['APP_URL']) ? '=' . $values['APP_URL'] : ''));
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
    check($config['driver'] === 'log' && str_ends_with($config['log_dir'], '.runtime' . DIRECTORY_SEPARATOR . 'mail'), 'Without MAIL_HOST the log driver must be used.');
    set_mail_environment(['HOST' => 'smtp.example.test', 'USERNAME' => 'sender@example.test']);
    $config = load_mail_config();
    check($config['driver'] === 'smtp' && $config['port'] === 587 && $config['encryption'] === 'tls' && $config['from_email'] === 'sender@example.test' && $config['verify_peer'] === true, 'A host must select SMTP with STARTTLS on 587.');
    foreach ([['ENCRYPTION' => 'maybe'], ['PORT' => '0'], ['DRIVER' => 'carrier-pigeon'], ['FROM_EMAIL' => "x@example.test
Bcc: y@example.test"], ['APP_URL' => 'javascript:alert(1)']] as $bad) {
        set_mail_environment($bad);
        $rejected = false;
        try {
            load_mail_config();
        } catch (RuntimeException $exception) {
            $rejected = true;
        }
        check($rejected, 'Invalid mail configuration was accepted: ' . json_encode($bad));
    }

    // Log driver.
    set_mail_environment(['DRIVER' => 'log', 'LOG_DIR' => $logDir]);
    check(send_mail('to@example.test', 'Tiêu đề', "Dòng một\nhttps://example.test/verify-email.php?token=abc", '<p>x</p>'), 'The log driver must accept a message.');
    $written = glob($logDir . DIRECTORY_SEPARATOR . '*.eml') ?: [];
    check(count($written) === 1 && str_starts_with((string) file_get_contents($written[0]), "To: to@example.test\r\nSubject: Tiêu đề\r\n\r\nDòng một"), 'The log driver must keep a readable copy.');
    check(!send_mail("to@example.test\r\nBcc: attacker@example.test", 'Hi', 'x', 'x'), 'A recipient containing a line break must be refused.');

    // SMTP delivery with authentication.
    $subject = 'Kích hoạt tài khoản Là Lá La';
    $text = "Xin chào Ánh,\nmở https://example.test/verify-email.php?token=abc\n.một dòng bắt đầu bằng dấu chấm\n";
    $transcript = with_fake_smtp('', static function (int $port) use ($subject, $text): void {
        set_mail_environment(['DRIVER' => 'smtp', 'HOST' => '127.0.0.1', 'PORT' => (string) $port, 'ENCRYPTION' => 'none', 'USERNAME' => 'smtp-user',
            'PASSWORD' => 'smtp-secret', 'FROM_EMAIL' => 'no-reply@example.test', 'FROM_NAME' => 'Là Lá La']);
        check(send_mail('member@example.test', $subject, $text, '<p>Xin chào</p>'), 'SMTP delivery to the fake server failed.');
    });
    check(str_contains($transcript, "AUTH LOGIN\r\n" . base64_encode('smtp-user') . "\r\n" . base64_encode('smtp-secret') . "\r\n"), 'SMTP must authenticate with AUTH LOGIN.');
    check(str_contains($transcript, "MAIL FROM:<no-reply@example.test>\r\nRCPT TO:<member@example.test>\r\nDATA\r\n"), 'SMTP must send the envelope before DATA.');
    check(str_contains($transcript, 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=') && str_contains($transcript, 'To: <member@example.test>')
        && str_contains($transcript, 'Content-Type: multipart/alternative'), 'The message needs encoded headers and both parts.');
    $message = substr($transcript, strpos($transcript, "DATA\r\n") + 6);
    preg_match_all('/Content-Transfer-Encoding: base64\r\n\r\n([A-Za-z0-9+\/=\r\n]+?)\r\n--/', $message, $parts);
    $decodedParts = array_map(static fn (string $chunk): string|false => base64_decode(str_replace("\r\n", '', $chunk), true), $parts[1]);
    check($decodedParts === [$text, '<p>Xin chào</p>'], 'Both text and HTML bodies must survive encoding intact.');
    check(str_contains($transcript, "\r\n.\r\nQUIT\r\n"), 'The message must end with a lone dot, followed by QUIT.');

    // Dot-stuffing: a line that is just "." must not end the message early.
    $transcript = with_fake_smtp('', static function (int $port): void {
        set_mail_environment(['DRIVER' => 'smtp', 'HOST' => '127.0.0.1', 'PORT' => (string) $port, 'ENCRYPTION' => 'none', 'FROM_EMAIL' => 'no-reply@example.test']);
        mail_smtp_deliver(load_mail_config(), 'no-reply@example.test', 'member@example.test', "Subject: x\r\n\r\nfirst\n.\n.hidden\nlast");
    });
    check(str_contains($transcript, "first\r\n..\r\n..hidden\r\nlast\r\n.\r\n"), 'Lines starting with a dot must be doubled.');
    check(!str_contains($transcript, 'AUTH'), 'Without credentials the client must not authenticate.');

    // Refusals: a rejected recipient, no server, and a password that would cross the network unencrypted.
    with_fake_smtp('reject-rcpt', static function (int $port): void {
        set_mail_environment(['DRIVER' => 'smtp', 'HOST' => '127.0.0.1', 'PORT' => (string) $port, 'ENCRYPTION' => 'none', 'FROM_EMAIL' => 'no-reply@example.test']);
        check(!send_mail('gone@example.test', 'Hi', 'x', 'x'), 'A rejected recipient must be reported as a failure.');
    });
    set_mail_environment(['DRIVER' => 'smtp', 'HOST' => '127.0.0.1', 'PORT' => '1', 'ENCRYPTION' => 'none', 'FROM_EMAIL' => 'no-reply@example.test']);
    check(!send_mail('member@example.test', 'Hi', 'x', 'x'), 'An unreachable server must be reported as a failure, not thrown.');
    set_mail_environment(['DRIVER' => 'smtp', 'HOST' => 'smtp.example.test', 'ENCRYPTION' => 'none', 'USERNAME' => 'u', 'PASSWORD' => 'p', 'FROM_EMAIL' => 'no-reply@example.test']);
    check(!send_mail('member@example.test', 'Hi', 'x', 'x'), 'Credentials must not be sent over an unencrypted remote connection.');
    check(str_contains((string) file_get_contents($errorLog), 'without encryption') && !str_contains((string) file_get_contents($errorLog), 'smtp-secret'), 'Failures must be logged without secrets.');

    // Links in emails never follow a forged Host header.
    $_SERVER['HTTP_HOST'] = 'evil.example';
    set_mail_environment(['DRIVER' => 'log', 'LOG_DIR' => $logDir]);
    check(email_base_url() === null, 'A non-local Host header must not be used for activation links.');
    check(!send_verification_email(['name' => 'Ánh', 'email' => 'member@example.test'], str_repeat('a', 64)) && count(glob($logDir . DIRECTORY_SEPARATOR . '*.eml')) === 1, 'Without APP_URL, no activation email may be built from a forged Host.');
    $_SERVER['HTTP_HOST'] = 'localhost:8090';
    check(email_base_url() === 'http://localhost:8090/Web503073', 'A localhost request may build its own links.');
    $_SERVER['HTTP_HOST'] = 'evil.example';
    set_mail_environment(['DRIVER' => 'log', 'LOG_DIR' => $logDir, 'APP_URL' => 'https://garden.example/Web503073/']);
    check(email_base_url() === 'https://garden.example/Web503073', 'The configured APP_URL must win over the Host header.');
    $token = str_repeat('b', 64);
    check(send_verification_email(['name' => '<b>Ánh</b>', 'email' => 'member@example.test'], $token), 'The activation email must be sent when APP_URL is set.');
    $files = glob($logDir . DIRECTORY_SEPARATOR . '*.eml');
    sort($files);
    $body = (string) file_get_contents(end($files));
    check(str_contains($body, 'https://garden.example/Web503073/verify-email.php?token=' . $token) && str_contains($body, 'Xin chào <b>Ánh</b>,'), 'The activation email must carry the link.');
} catch (Throwable $exception) {
    $failure = $exception->getMessage();
} finally {
    if (is_dir($logDir)) {
        array_map('unlink', glob($logDir . DIRECTORY_SEPARATOR . '*.eml') ?: []);
        rmdir($logDir);
    }
    @unlink($errorLog);
}
if ($failure !== null) {
    fwrite(STDERR, 'FAIL: ' . $failure . PHP_EOL);
    exit(1);
}
fwrite(STDOUT, 'PASS: ' . $checks . ' mail checks.' . PHP_EOL);
