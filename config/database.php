<?php
declare(strict_types=1);

// Local overrides are ignored by Git. Environment variables take precedence.
$localPath = __DIR__ . '/database.local.php';
$local = is_file($localPath) ? require $localPath : [];
if (!is_array($local)) {
    throw new RuntimeException('config/database.local.php must return an array.');
}
$defaults = ['host' => '127.0.0.1', 'port' => '3306', 'name' => 'web503073', 'user' => 'root', 'password' => ''];
$config = [];
foreach ($defaults as $key => $value) {
    $environment = getenv('DB_' . strtoupper($key));
    $config[$key] = $environment === false ? (string) ($local[$key] ?? $value) : $environment;
}
if (!preg_match('/^[a-zA-Z0-9_]+$/', $config['name']) || !ctype_digit($config['port'])
    || (int) $config['port'] < 1 || (int) $config['port'] > 65535
    || preg_match('/[;\r\n]/', $config['host'])) {
    throw new RuntimeException('Invalid database name, host, or port.');
}
return $config;
