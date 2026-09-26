<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/database.php';

try {
    $config = database_config();
    $connection = database_connection(false);
    $connection->exec('CREATE DATABASE IF NOT EXISTS `' . $config['name'] . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $connection->exec('USE `' . $config['name'] . '`');
    $schema = file_get_contents(__DIR__ . '/../database/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('Cannot read database/schema.sql.');
    }
    foreach (explode(';', $schema) as $sql) {
        if (trim($sql) !== '') {
            $connection->exec($sql);
        }
    }
    require __DIR__ . '/../data/plants.php';
    $connection->beginTransaction();
    $statement = $connection->prepare('INSERT INTO plants (id, name, category, description, care, image)'
        . ' VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id = VALUES(id)');
    foreach ($plants as $plant) {
        $statement->execute([$plant['id'], $plant['name'], $plant['category'], $plant['description'], $plant['care'], $plant['image']]);
    }
    $connection->commit();
    fwrite(STDOUT, 'Database ' . $config['name'] . ' is ready. Plants: ' . $connection->query('SELECT COUNT(*) FROM plants')->fetchColumn() . PHP_EOL);
    fwrite(STDOUT, 'Existing users, favorites, and plant records were preserved.' . PHP_EOL);
} catch (Throwable $exception) {
    if (isset($connection) && $connection->inTransaction()) {
        $connection->rollBack();
    }
    fwrite(STDERR, 'Setup failed. Start MySQL and check config/database.local.php or DB_* variables.' . PHP_EOL);
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
