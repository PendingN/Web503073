<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/database.php';

try {
    $config = load_database_config(true);
    $connection = create_database_connection($config, false);
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
    if (!$connection->query("SHOW COLUMNS FROM blog_posts LIKE 'image_path'")->fetch()) {
        $connection->exec('ALTER TABLE blog_posts ADD COLUMN image_path VARCHAR(255) NULL AFTER content');
    }
    require __DIR__ . '/../data/plants.php';
    $connection->beginTransaction();
    $statement = $connection->prepare('INSERT INTO plants (id, name, category, description, care, image)'
        . ' VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id = VALUES(id)');
    foreach ($plants as $plant) {
        $statement->execute([$plant['id'], $plant['name'], $plant['category'], $plant['description'], $plant['care'], $plant['image']]);
    }
    require __DIR__ . '/../data/content.php';
    $statement = $connection->prepare('INSERT INTO blog_posts (slug, title, tag, excerpt, content, illustration, tone, created_at)'
        . ' VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE slug = VALUES(slug)');
    foreach ([$featuredPost, ...$posts] as $post) {
        $date = DateTimeImmutable::createFromFormat('!d.m.Y', $post['date']);
        $statement->execute([$post['slug'], $post['title'], $post['tag'], $post['excerpt'],
            implode("\n\n", $post['body']), $post['illustration'], $post['tone'], $date->format('Y-m-d H:i:s')]);
    }
    $connection->exec("INSERT INTO user_activity (actor_name, subject_id, subject_name, event, description, created_at)"
        . " SELECT 'Hệ thống', u.id, u.name, 'account.created', 'Tạo tài khoản (mốc từ dữ liệu có sẵn)', u.created_at FROM users u"
        . " LEFT JOIN user_activity a ON a.subject_id = u.id AND a.event = 'account.created' WHERE a.id IS NULL");
    $connection->commit();
    fwrite(STDOUT, 'Database ' . $config['name'] . ' is ready. Plants: ' . $connection->query('SELECT COUNT(*) FROM plants')->fetchColumn() . PHP_EOL);
    fwrite(STDOUT, 'Existing users, favorites, plants, and blog records were preserved.' . PHP_EOL);
} catch (Throwable $exception) {
    if (isset($connection) && $connection->inTransaction()) {
        $connection->rollBack();
    }
    fwrite(STDERR, 'Setup failed. Start MySQL and check config/database.local.php or DB_* variables.' . PHP_EOL);
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}
