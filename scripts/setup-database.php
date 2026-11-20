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
    if (!$connection->query("SHOW COLUMNS FROM blog_posts LIKE 'is_hidden'")->fetch()) {
        $connection->exec('ALTER TABLE blog_posts ADD COLUMN is_hidden TINYINT(1) NOT NULL DEFAULT 0 AFTER image_path');
    }
    if (!$connection->query("SHOW COLUMNS FROM blog_posts LIKE 'content_blocks'")->fetch()) {
        $connection->exec('ALTER TABLE blog_posts ADD COLUMN content_blocks MEDIUMTEXT NULL AFTER content');
    }
    // One-off upgrade: blog_posts.tag (a single text column) becomes the tags + post_tags tables,
    // so a post can have none, one or many tags and renaming a tag updates every post at once.
    // Runs only while the old column still exists, so later runs never restore a removed tag.
    // DDL commits implicitly, so this stays outside the seed transaction.
    if ($connection->query("SHOW COLUMNS FROM blog_posts LIKE 'tag'")->fetch()) {
        $insertTag = $connection->prepare('INSERT IGNORE INTO tags (name) VALUES (?)');
        foreach (['Chăm cây', 'Không gian', 'Cảm hứng', 'Mẹo hay', 'Sống xanh', 'Bài nổi bật'] as $legacyTag) {
            $insertTag->execute([$legacyTag]);
        }
        $connection->exec("INSERT IGNORE INTO tags (name) SELECT DISTINCT TRIM(tag) FROM blog_posts WHERE TRIM(tag) <> ''");
        $connection->exec('INSERT IGNORE INTO post_tags (post_id, tag_id) SELECT p.id, t.id FROM blog_posts p JOIN tags t ON t.name = TRIM(p.tag)');
        $connection->exec('ALTER TABLE blog_posts DROP COLUMN tag');
    }
    if (!$connection->query("SHOW COLUMNS FROM blog_posts LIKE 'is_private'")->fetch()) {
        $connection->exec('ALTER TABLE blog_posts ADD COLUMN is_private TINYINT(1) NOT NULL DEFAULT 0 AFTER is_hidden');
    }
    if (!$connection->query("SHOW COLUMNS FROM blog_posts LIKE 'lock_hash'")->fetch()) {
        $connection->exec('ALTER TABLE blog_posts ADD COLUMN lock_hash VARCHAR(255) NULL AFTER is_private');
    }
    require __DIR__ . '/../data/plants.php';
    $connection->beginTransaction();
    $statement = $connection->prepare('INSERT INTO plants (id, name, category, description, care, image)'
        . ' VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id = VALUES(id)');
    foreach ($plants as $plant) {
        $statement->execute([$plant['id'], $plant['name'], $plant['category'], $plant['description'], $plant['care'], $plant['image']]);
    }
    require __DIR__ . '/../data/content.php';
    $statement = $connection->prepare('INSERT INTO blog_posts (slug, title, excerpt, content, illustration, tone, created_at)'
        . ' VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE slug = VALUES(slug)');
    $ensureTag = $connection->prepare('INSERT INTO tags (name) VALUES (?) ON DUPLICATE KEY UPDATE name = name');
    $linkTag = $connection->prepare('INSERT IGNORE INTO post_tags (post_id, tag_id) SELECT p.id, t.id FROM blog_posts p JOIN tags t ON t.name = ? WHERE p.slug = ?');
    foreach ([$featuredPost, ...$posts] as $post) {
        $date = DateTimeImmutable::createFromFormat('!d.m.Y', $post['date']);
        $statement->execute([$post['slug'], $post['title'], $post['excerpt'],
            implode("\n\n", $post['body']), $post['illustration'], $post['tone'], $date->format('Y-m-d H:i:s')]);
        // Tag only posts inserted now, so an administrator's later tag edits survive a re-run.
        if ($statement->rowCount() === 1) {
            $ensureTag->execute([$post['tag']]);
            $linkTag->execute([$post['tag'], $post['slug']]);
        }
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
