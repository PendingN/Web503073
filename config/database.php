<?php
declare(strict_types=1);

// Local overrides are ignored by Git. Environment variables take precedence.
function load_database_config(bool $forSetup = false): array
{
    $localPath = __DIR__ . '/database.local.php';
    $local = is_file($localPath) ? require $localPath : [];
    if (!is_array($local)) {
        throw new RuntimeException('config/database.local.php must return an array.');
    }

    $defaults = ['host' => '127.0.0.1', 'port' => '3306', 'name' => 'web503073', 'user' => '', 'password' => ''];
    $config = [];
    foreach ($defaults as $key => $value) {
        $environment = getenv('DB_' . strtoupper($key));
        $config[$key] = $environment === false ? (string) ($local[$key] ?? $value) : $environment;
    }

    if ($forSetup) {
        $setupUser = getenv('DB_SETUP_USER');
        $setupPassword = getenv('DB_SETUP_PASSWORD');
        if ($setupUser === false || $setupPassword === false) {
            throw new RuntimeException('Database setup requires DB_SETUP_USER and DB_SETUP_PASSWORD.');
        }
        $config['user'] = $setupUser;
        $config['password'] = $setupPassword;
    }

    if (!preg_match('/^[a-zA-Z0-9_]+$/', $config['name']) || !ctype_digit($config['port'])
        || (int) $config['port'] < 1 || (int) $config['port'] > 65535
        || preg_match('/[;\r\n]/', $config['host'])) {
        throw new RuntimeException('Invalid database name, host, or port.');
    }
    if ($config['user'] === '' || $config['password'] === '') {
        throw new RuntimeException('Configure a dedicated database user with a non-empty password.');
    }
    if (strcasecmp($config['user'], 'root') === 0) {
        throw new RuntimeException('The application database user must not be root.');
    }
    if (in_array($config['password'], ['CHANGE_ME', 'REPLACE_WITH_A_LONG_RANDOM_PASSWORD'], true)) {
        throw new RuntimeException('Replace the database password placeholder before starting the application.');
    }
    return $config;
}
