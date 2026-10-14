<?php
declare(strict_types=1);

function blog_post_select(int $userId): string
{
    return 'SELECT p.*, COALESCE(u.name, \'Ban biên tập\') AS author,'
        . ' COALESCE((SELECT SUM(v.value) FROM blog_votes v WHERE v.post_id = p.id), 0) AS score,'
        . ' (SELECT COUNT(*) FROM blog_comments c WHERE c.post_id = p.id) AS comment_count,'
        . ' COALESCE((SELECT v.value FROM blog_votes v WHERE v.post_id = p.id AND v.user_id = ' . $userId . '), 0) AS my_vote,'
        . ' EXISTS(SELECT 1 FROM blog_saves s WHERE s.post_id = p.id AND s.user_id = ' . $userId . ') AS saved'
        . ' FROM blog_posts p LEFT JOIN users u ON u.id = p.user_id';
}

function find_post(string $slug): ?array
{
    $statement = db()->prepare(blog_post_select((int) (current_user()['id'] ?? 0)) . ' WHERE p.slug = ?');
    $statement->execute([$slug]);
    return $statement->fetch() ?: null;
}

function count_blog_posts(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
}

function latest_blog_posts(): array
{
    return db()->query('SELECT slug, title, tag, image_path, created_at FROM blog_posts ORDER BY created_at DESC, id DESC LIMIT 5')->fetchAll();
}

function list_admin_posts(string $search, string $tag, int $page): array
{
    $from = ' FROM blog_posts p LEFT JOIN users u ON u.id = p.user_id';
    $where = ' WHERE 1 = 1';
    $parameters = [];
    if ($search !== '') {
        $where .= " AND (p.title LIKE ? ESCAPE '!' OR u.name LIKE ? ESCAPE '!')";
        $parameters = [search_pattern($search), search_pattern($search)];
    }
    if ($tag !== '') {
        $where .= ' AND p.tag = ?';
        $parameters[] = $tag;
    }
    $statement = db()->prepare('SELECT COUNT(*)' . $from . $where);
    $statement->execute($parameters);
    $total = (int) $statement->fetchColumn();
    $pages = max(1, (int) ceil($total / 20));
    $page = min(max(1, $page), $pages);
    $statement = db()->prepare("SELECT p.id, p.slug, p.title, p.tag, p.created_at, p.image_path, COALESCE(u.name, 'Ban biên tập') AS author,"
        . ' (SELECT COUNT(*) FROM blog_comments c WHERE c.post_id = p.id) AS comment_count'
        . $from . $where . ' ORDER BY p.created_at DESC, p.id DESC LIMIT 20 OFFSET ' . (($page - 1) * 20));
    $statement->execute($parameters);
    return ['posts' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
}

function list_blog_posts(string $search, string $sort, string $view, int $userId, int $page): array
{
    $where = ' WHERE 1 = 1';
    $parameters = [];
    if ($search !== '') {
        $where .= " AND (p.title LIKE ? ESCAPE '!' OR p.content LIKE ? ESCAPE '!' OR p.tag LIKE ? ESCAPE '!')";
        $parameters = array_fill(0, 3, search_pattern($search));
    }
    if ($view === 'saved') {
        $where .= ' AND EXISTS(SELECT 1 FROM blog_saves s WHERE s.post_id = p.id AND s.user_id = ?)';
        $parameters[] = $userId;
    } elseif ($view === 'mine') {
        $where .= ' AND p.user_id = ?';
        $parameters[] = $userId;
    }
    $statement = db()->prepare('SELECT COUNT(*) FROM blog_posts p' . $where);
    $statement->execute($parameters);
    $total = (int) $statement->fetchColumn();
    $pages = max(1, (int) ceil($total / 6));
    $page = min(max(1, $page), $pages);
    $order = $sort === 'popular' ? 'score DESC, p.created_at DESC, p.id DESC' : 'p.created_at DESC, p.id DESC';
    $statement = db()->prepare(blog_post_select($userId) . $where . ' ORDER BY ' . $order . ' LIMIT 6 OFFSET ' . (($page - 1) * 6));
    $statement->execute($parameters);
    return ['posts' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
}

function can_edit_post(array $post, ?array $user): bool
{
    return $user !== null && ($user['role'] === 'admin' || (int) $post['user_id'] === (int) $user['id']);
}

function blog_post_errors(string $title, string $content, string $tag): array
{
    $errors = [];
    if (mb_strlen($title, 'UTF-8') < 3 || mb_strlen($title, 'UTF-8') > 120) {
        $errors[] = 'Tiêu đề cần có từ 3 đến 120 ký tự.';
    }
    if (mb_strlen($content, 'UTF-8') < 10 || mb_strlen($content, 'UTF-8') > 10000) {
        $errors[] = 'Nội dung cần có từ 10 đến 10.000 ký tự.';
    }
    if (!in_array($tag, ['Chăm cây', 'Không gian', 'Cảm hứng', 'Mẹo hay', 'Sống xanh', 'Bài nổi bật'], true)) {
        $errors[] = 'Vui lòng chọn chủ đề hợp lệ.';
    }
    return $errors;
}

function list_blog_comments(int $postId, int $page): array
{
    $statement = db()->prepare('SELECT COUNT(*) FROM blog_comments WHERE post_id = ?');
    $statement->execute([$postId]);
    $pages = max(1, (int) ceil((int) $statement->fetchColumn() / 20));
    $page = min(max(1, $page), $pages);
    $statement = db()->prepare('SELECT c.*, u.name AS author FROM blog_comments c JOIN users u ON u.id = c.user_id'
        . ' WHERE c.post_id = ? ORDER BY c.id DESC LIMIT 20 OFFSET ' . (($page - 1) * 20));
    $statement->execute([$postId]);
    return ['comments' => $statement->fetchAll(), 'page' => $page, 'pages' => $pages];
}
