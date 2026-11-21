<?php
declare(strict_types=1);

// Outgoing email settings. config/mail.local.php is ignored by Git; MAIL_* environment variables win.
// driver "log" writes each message to .runtime/mail/ instead of sending it (local development and tests);
// driver "smtp" sends through an SMTP server such as Gmail (smtp.gmail.com, port 587, tls, app password).
function load_mail_config(): array
{
    $localPath = __DIR__ . '/mail.local.php';
    $local = is_file($localPath) ? require $localPath : [];
    if (!is_array($local)) {
        throw new RuntimeException('config/mail.local.php must return an array.');
    }
    $defaults = [
        'driver' => 'log',
        'host' => '',
        'port' => '587',
        'encryption' => 'tls',
        'username' => '',
        'password' => '',
        'from_address' => '',
        'from_name' => 'Là Lá La',
        'app_url' => '',
        'log_dir' => '',
        'timeout' => '15',
    ];
    // Older mail.local.php files (written for the first activation mailer) used 'from_email' and had no
    // 'driver': a filled-in host meant SMTP. Read them the same way so a deployed config keeps working.
    if (!isset($local['from_address']) && isset($local['from_email'])) {
        $local['from_address'] = $local['from_email'];
    }
    if (!isset($local['driver']) && trim((string) ($local['host'] ?? '')) !== '') {
        $local['driver'] = 'smtp';
    }
    $config = [];
    foreach ($defaults as $key => $value) {
        $environment = getenv('MAIL_' . strtoupper($key));
        $config[$key] = trim($environment === false ? (string) ($local[$key] ?? $value) : $environment);
    }

    $config['driver'] = strtolower($config['driver']);
    $config['encryption'] = strtolower($config['encryption']);
    if (!in_array($config['driver'], ['log', 'smtp'], true)) {
        throw new RuntimeException('MAIL_DRIVER must be "log" or "smtp".');
    }
    if (!in_array($config['encryption'], ['tls', 'ssl', 'none'], true)) {
        throw new RuntimeException('MAIL_ENCRYPTION must be "tls", "ssl" or "none".');
    }
    if (!ctype_digit($config['port']) || (int) $config['port'] < 1 || (int) $config['port'] > 65535) {
        throw new RuntimeException('MAIL_PORT must be a valid port number.');
    }
    $config['port'] = (int) $config['port'];
    $config['timeout'] = max(5, min(60, (int) $config['timeout']));
    if ($config['from_address'] === '') {
        $config['from_address'] = $config['username'] !== '' ? $config['username'] : 'no-reply@localhost';
    }
    if (preg_match('/[\r\n]/', $config['from_name'] . $config['from_address'] . $config['host'])
        || !filter_var($config['from_address'], FILTER_VALIDATE_EMAIL) && $config['from_address'] !== 'no-reply@localhost') {
        throw new RuntimeException('Invalid mail sender or host.');
    }
    if ($config['driver'] === 'smtp' && ($config['host'] === '' || $config['password'] === 'REPLACE_WITH_APP_PASSWORD')) {
        throw new RuntimeException('Configure the SMTP host and password in config/mail.local.php or MAIL_* variables.');
    }
    if ($config['app_url'] !== '' && !preg_match('#^https?://[^/\s?\#]+(?:/[^\s?\#]*)?$#i', $config['app_url'])) {
        throw new RuntimeException('MAIL_APP_URL must be an absolute http(s) URL such as https://example.com/.');
    }
    if ($config['log_dir'] === '') {
        $config['log_dir'] = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.runtime' . DIRECTORY_SEPARATOR . 'mail';
    }
    return $config;
}
