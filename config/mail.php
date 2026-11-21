<?php
declare(strict_types=1);

// Outgoing mail settings (account activation). config/mail.local.php is ignored by Git.
// MAIL_* environment variables take precedence; APP_URL is the public address of the site.
//
// With no MAIL_HOST the "log" driver writes each message to .runtime/mail/ instead of sending it,
// which is what you want on a development machine. Set MAIL_HOST to send through SMTP.
function load_mail_config(): array
{
    $localPath = __DIR__ . '/mail.local.php';
    $local = is_file($localPath) ? require $localPath : [];
    if (!is_array($local)) {
        throw new RuntimeException('config/mail.local.php must return an array.');
    }
    $defaults = [
        'driver' => '', 'host' => '', 'port' => '587', 'encryption' => 'tls', 'username' => '', 'password' => '',
        'from_email' => '', 'from_name' => 'Là Lá La', 'app_url' => '', 'log_dir' => '', 'timeout' => '15', 'verify_peer' => '1',
    ];
    $config = [];
    foreach ($defaults as $key => $value) {
        $environment = getenv($key === 'app_url' ? 'APP_URL' : 'MAIL_' . strtoupper($key));
        $config[$key] = trim($environment === false ? (string) ($local[$key] ?? $value) : $environment);
    }

    $config['driver'] = strtolower($config['driver']) !== '' ? strtolower($config['driver']) : ($config['host'] !== '' ? 'smtp' : 'log');
    if (!in_array($config['driver'], ['smtp', 'log'], true)) {
        throw new RuntimeException('MAIL_DRIVER must be smtp or log.');
    }
    $config['encryption'] = strtolower($config['encryption']);
    if (!in_array($config['encryption'], ['tls', 'ssl', 'none'], true)) {
        throw new RuntimeException('MAIL_ENCRYPTION must be tls (STARTTLS), ssl or none.');
    }
    $config['port'] = (int) $config['port'];
    if ($config['port'] < 1 || $config['port'] > 65535) {
        throw new RuntimeException('MAIL_PORT must be between 1 and 65535.');
    }
    $config['timeout'] = max(5, min(60, (int) $config['timeout']));
    $config['verify_peer'] = !in_array(strtolower($config['verify_peer']), ['0', 'false', 'no', 'off'], true);
    if ($config['from_email'] === '' && filter_var($config['username'], FILTER_VALIDATE_EMAIL)) {
        $config['from_email'] = $config['username'];
    }
    if ($config['from_email'] === '') {
        $config['from_email'] = 'no-reply@localhost.localdomain';
    }
    if (!filter_var($config['from_email'], FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('MAIL_FROM_EMAIL is not a valid email address.');
    }
    $config['app_url'] = rtrim($config['app_url'], '/');
    if ($config['app_url'] !== '' && !preg_match('#^https?://[^\s/?\#]+(?:/[^\s?\#]*)?$#i', $config['app_url'])) {
        throw new RuntimeException('APP_URL must look like https://example.com or https://example.com/Web503073.');
    }
    $config['log_dir'] = $config['log_dir'] !== '' ? $config['log_dir'] : dirname(__DIR__) . DIRECTORY_SEPARATOR . '.runtime' . DIRECTORY_SEPARATOR . 'mail';
    return $config;
}
