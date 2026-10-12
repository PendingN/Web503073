<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

function create_database_connection(array $config, bool $withDatabase): PDO
{
    $dsn = 'mysql:host=' . $config['host'] . ';port=' . $config['port'] . ';charset=utf8mb4';
    if ($withDatabase) {
        $dsn .= ';dbname=' . $config['name'];
    }
    return new PDO($dsn, $config['user'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
}

function db(): PDO
{
    static $connection;
    if ($connection === null) {
        $connection = create_database_connection(load_database_config(), true);
    }
    return $connection;
}
