<?php
declare(strict_types=1);

function database_config(): array
{
    return require __DIR__ . '/../config/database.php';
}

function database_connection(bool $withDatabase = true): PDO
{
    $config = database_config();
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
        $connection = database_connection();
    }
    return $connection;
}
