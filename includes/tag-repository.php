<?php
declare(strict_types=1);

// Blog tags ("chủ đề"): a shared list kept in the tags table and linked to posts through post_tags.
// Posts store tag ids, never tag text, so renaming a tag updates every post and deleting one only
// removes the links (the posts stay).

const TAG_NAME_MAX_LENGTH = 50;
const POST_TAG_LIMIT = 10;

/** Trims and collapses whitespace so "  Mẹo   hay " and "Mẹo hay" are the same name. */
function normalize_tag_name(string $name): string
{
    return trim((string) preg_replace('/\s+/u', ' ', $name));
}

/** @return string[] */
function tag_name_errors(string $name): array
{
    $length = mb_strlen($name, 'UTF-8');
    if ($length < 1 || $length > TAG_NAME_MAX_LENGTH) {
        return ['Tên chủ đề cần có từ 1 đến ' . TAG_NAME_MAX_LENGTH . ' ký tự.'];
    }
    if (preg_match('/[\x00-\x1F\x7F]/', $name) === 1 || !mb_check_encoding($name, 'UTF-8')) {
        return ['Tên chủ đề chứa ký tự không hợp lệ.'];
    }
    return [];
}

/**
 * Tags in creation order. post_count counts the posts the current viewer can see
 * (everything for administrators, visible posts for everyone else), or all posts with $allPosts.
 *
 * @return array<int, array{id: int, name: string, post_count: int}>
 */
function list_tags(bool $allPosts = false): array
{
    $visible = $allPosts ? '' : visible_blog_condition();
    $rows = db()->query('SELECT t.id, t.name, COUNT(p.id) AS post_count FROM tags t'
        . ' LEFT JOIN post_tags pt ON pt.tag_id = t.id'
        . ' LEFT JOIN blog_posts p ON p.id = pt.post_id' . $visible
        . ' GROUP BY t.id, t.name ORDER BY t.id')->fetchAll();
    return array_map(static fn (array $row): array => [
        'id' => (int) $row['id'], 'name' => (string) $row['name'], 'post_count' => (int) $row['post_count'],
    ], $rows);
}

function find_tag(int $id): ?array
{
    $statement = db()->prepare('SELECT id, name FROM tags WHERE id = ?');
    $statement->execute([$id]);
    $row = $statement->fetch();
    return $row ? ['id' => (int) $row['id'], 'name' => (string) $row['name']] : null;
}

/**
 * Another tag already uses this name. The collation ignores case and most Vietnamese accents;
 * "đ" is a separate letter to it, so fold it too and "Cây để bàn" clashes with "cay de ban".
 */
function tag_name_taken(PDO $connection, string $name, int $exceptId = 0): bool
{
    $statement = $connection->prepare("SELECT 1 FROM tags WHERE REPLACE(REPLACE(name, 'đ', 'd'), 'Đ', 'D') = ? AND id <> ? LIMIT 1");
    $statement->execute([str_replace(['đ', 'Đ'], ['d', 'D'], $name), $exceptId]);
    return (bool) $statement->fetchColumn();
}

function is_duplicate_key_error(PDOException $exception): bool
{
    return ($exception->errorInfo[1] ?? 0) === 1062;
}

/** @return array{id: ?int, errors: string[]} */
function create_tag(PDO $connection, string $name): array
{
    $name = normalize_tag_name($name);
    $errors = tag_name_errors($name);
    if ($errors === [] && tag_name_taken($connection, $name)) {
        $errors[] = 'Chủ đề “' . $name . '” đã tồn tại.';
    }
    if ($errors !== []) {
        return ['id' => null, 'errors' => $errors];
    }
    try {
        $connection->prepare('INSERT INTO tags (name) VALUES (?)')->execute([$name]);
    } catch (PDOException $exception) {
        if (!is_duplicate_key_error($exception)) {
            throw $exception;
        }
        return ['id' => null, 'errors' => ['Chủ đề “' . $name . '” đã tồn tại.']];
    }
    return ['id' => (int) $connection->lastInsertId(), 'errors' => []];
}

/** @return string[] validation errors, empty on success */
function rename_tag(PDO $connection, int $id, string $name): array
{
    $name = normalize_tag_name($name);
    $errors = tag_name_errors($name);
    if ($errors === [] && tag_name_taken($connection, $name, $id)) {
        $errors[] = 'Chủ đề “' . $name . '” đã tồn tại.';
    }
    if ($errors !== []) {
        return $errors;
    }
    try {
        $connection->prepare('UPDATE tags SET name = ? WHERE id = ?')->execute([$name, $id]);
    } catch (PDOException $exception) {
        if (!is_duplicate_key_error($exception)) {
            throw $exception;
        }
        return ['Chủ đề “' . $name . '” đã tồn tại.'];
    }
    return [];
}

/** Deleting a tag removes only its post links (foreign key cascade); posts are untouched. */
function delete_tag(PDO $connection, int $id): void
{
    $connection->prepare('DELETE FROM tags WHERE id = ?')->execute([$id]);
}

/**
 * Normalizes submitted tag ids: positive integers, unique, in submitted order.
 * Anything that is not a list of integers is reported as an error rather than silently dropped.
 *
 * @return array{ids: int[], errors: string[]}
 */
function parse_tag_ids(mixed $input): array
{
    if ($input === null || $input === '' || $input === []) {
        return ['ids' => [], 'errors' => []];
    }
    if (!is_array($input)) {
        return ['ids' => [], 'errors' => ['Danh sách chủ đề không hợp lệ.']];
    }
    $ids = [];
    foreach ($input as $value) {
        $id = is_string($value) || is_int($value) ? filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : false;
        if ($id === false) {
            return ['ids' => [], 'errors' => ['Danh sách chủ đề không hợp lệ.']];
        }
        $ids[$id] = $id;
    }
    $ids = array_values($ids);
    if (count($ids) > POST_TAG_LIMIT) {
        return ['ids' => $ids, 'errors' => ['Mỗi bài chỉ có thể có tối đa ' . POST_TAG_LIMIT . ' chủ đề.']];
    }
    return ['ids' => $ids, 'errors' => []];
}

/**
 * Checks that every id still exists and holds a shared lock on those rows for the rest of the
 * transaction, so a tag cannot be deleted between validation and linking.
 *
 * @param int[] $ids
 * @return string[] errors
 */
function lock_existing_tags(PDO $connection, array $ids): array
{
    if ($ids === []) {
        return [];
    }
    $marks = implode(',', array_fill(0, count($ids), '?'));
    $statement = $connection->prepare('SELECT id FROM tags WHERE id IN (' . $marks . ') LOCK IN SHARE MODE');
    $statement->execute($ids);
    return count($statement->fetchAll()) === count($ids) ? [] : ['Có chủ đề đã chọn không còn tồn tại. Vui lòng chọn lại.'];
}

/** Replaces all tags of a post; an empty list leaves it untagged. Call inside the post's transaction. */
function set_post_tags(PDO $connection, int $postId, array $tagIds): void
{
    $connection->prepare('DELETE FROM post_tags WHERE post_id = ?')->execute([$postId]);
    $insert = $connection->prepare('INSERT INTO post_tags (post_id, tag_id) VALUES (?, ?)');
    foreach ($tagIds as $tagId) {
        $insert->execute([$postId, (int) $tagId]);
    }
}

/**
 * Adds 'tags' (list of {id, name}) and 'tag' (first tag name, '' when untagged) to each post row.
 * Rows must carry their post id in 'id'.
 */
function attach_blog_tags(array $posts): array
{
    if ($posts === []) {
        return $posts;
    }
    $ids = array_values(array_unique(array_map(static fn (array $post): int => (int) $post['id'], $posts)));
    $statement = db()->prepare('SELECT pt.post_id, t.id, t.name FROM post_tags pt JOIN tags t ON t.id = pt.tag_id'
        . ' WHERE pt.post_id IN (' . implode(',', array_fill(0, count($ids), '?')) . ') ORDER BY t.id');
    $statement->execute($ids);
    $byPost = [];
    foreach ($statement->fetchAll() as $row) {
        $byPost[(int) $row['post_id']][] = ['id' => (int) $row['id'], 'name' => (string) $row['name']];
    }
    foreach ($posts as $key => $post) {
        $posts[$key]['tags'] = $byPost[(int) $post['id']] ?? [];
        $posts[$key]['tag'] = $posts[$key]['tags'][0]['name'] ?? '';
    }
    return $posts;
}

/** @return int[] */
function post_tag_ids(array $post): array
{
    return array_map(static fn (array $tag): int => (int) $tag['id'], $post['tags'] ?? []);
}

/** Link to the blog list filtered to one tag. */
function blog_tag_url(int $tagId, array $extra = []): string
{
    return 'blog.php?' . http_build_query(['tag' => [$tagId]] + $extra) . '#blog-content';
}
