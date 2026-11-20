<?php
declare(strict_types=1);

// Six fills two rows of the three-column grid and divides evenly into the two-column one.
const BLOG_POSTS_PER_PAGE = 6;

function blog_post_select(int $userId): string
{
    return 'SELECT p.*, COALESCE(u.name, \'Ban biên tập\') AS author,'
        . ' COALESCE((SELECT SUM(v.value) FROM blog_votes v WHERE v.post_id = p.id), 0) AS score,'
        . ' (SELECT COUNT(*) FROM blog_votes v WHERE v.post_id = p.id AND v.value = 1) AS likes,'
        . ' (SELECT COUNT(*) FROM blog_votes v WHERE v.post_id = p.id AND v.value = -1) AS dislikes,'
        . ' (SELECT COUNT(*) FROM blog_comments c WHERE c.post_id = p.id) AS comment_count,'
        . ' COALESCE((SELECT v.value FROM blog_votes v WHERE v.post_id = p.id AND v.user_id = ' . $userId . '), 0) AS my_vote,'
        . ' EXISTS(SELECT 1 FROM blog_saves s WHERE s.post_id = p.id AND s.user_id = ' . $userId . ') AS saved,'
        . ' (SELECT s.permission FROM blog_shares s WHERE s.post_id = p.id AND s.user_id = ' . $userId . ') AS my_share,'
        . ' (SELECT COUNT(*) FROM blog_shares s WHERE s.post_id = p.id) AS share_count,'
        . ' (p.lock_hash IS NOT NULL) AS is_locked'
        . ' FROM blog_posts p LEFT JOIN users u ON u.id = p.user_id';
}

// Hidden posts are visible only to administrators; everyone else gets the same 404 as a missing post.
// Private posts are visible only to their author and the people they were shared with.
function visible_blog_condition(string $alias = 'p'): string
{
    $userId = (int) (current_user()['id'] ?? 0);
    $private = ' AND (' . $alias . '.is_private = 0 OR ' . $alias . '.user_id = ' . $userId
        . ' OR EXISTS(SELECT 1 FROM blog_shares vs WHERE vs.post_id = ' . $alias . '.id AND vs.user_id = ' . $userId . '))';
    return (is_admin() ? '' : ' AND ' . $alias . '.is_hidden = 0') . $private;
}

function find_post(string $slug): ?array
{
    $statement = db()->prepare(blog_post_select((int) (current_user()['id'] ?? 0)) . ' WHERE p.slug = ?' . visible_blog_condition());
    $statement->execute([$slug]);
    return $statement->fetch() ?: null;
}

function count_blog_posts(): int
{
    return (int) db()->query('SELECT COUNT(*) FROM blog_posts p WHERE 1 = 1' . visible_blog_condition())->fetchColumn();
}

function latest_blog_posts(): array
{
    return db()->query("SELECT p.slug, p.title, p.tag, p.excerpt, p.image_path, p.created_at, (p.lock_hash IS NOT NULL) AS is_locked, COALESCE(u.name, 'Là Lá La') AS author"
        . ' FROM blog_posts p LEFT JOIN users u ON u.id = p.user_id WHERE 1 = 1'
        . visible_blog_condition() . ' ORDER BY p.created_at DESC, p.id DESC LIMIT 5')->fetchAll();
}

/** Compact rows for the blog search box's as-you-type suggestions, newest first. */
function blog_search_suggestions(int $limit = 200): array
{
    return db()->query('SELECT p.slug, p.title, p.tag, p.excerpt, p.image_path FROM blog_posts p WHERE 1 = 1'
        . visible_blog_condition() . ' ORDER BY p.created_at DESC, p.id DESC LIMIT ' . max(1, $limit))->fetchAll();
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
    $statement = db()->prepare("SELECT p.id, p.slug, p.title, p.tag, p.is_hidden, p.created_at, p.image_path, COALESCE(u.name, 'Ban biên tập') AS author,"
        . ' (SELECT COUNT(*) FROM blog_comments c WHERE c.post_id = p.id) AS comment_count'
        . $from . $where . ' ORDER BY p.created_at DESC, p.id DESC LIMIT 20 OFFSET ' . (($page - 1) * 20));
    $statement->execute($parameters);
    return ['posts' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
}

function list_blog_posts(string $search, string $sort, string $view, int $userId, int $page): array
{
    $where = ' WHERE 1 = 1' . visible_blog_condition();
    $parameters = [];
    if ($search !== '') {
        // The collation already ignores most Vietnamese accents; "đ" is a separate letter to it,
        // so fold it on both sides to let "dau" find "đầu".
        $folded = static fn (string $column): string => "REPLACE(REPLACE($column, 'đ', 'd'), 'Đ', 'D')";
        $where .= ' AND (' . $folded('p.title') . " LIKE ? ESCAPE '!' OR " . $folded('p.content') . " LIKE ? ESCAPE '!' OR " . $folded('p.tag') . " LIKE ? ESCAPE '!')";
        $parameters = array_fill(0, 3, search_pattern(str_replace(['đ', 'Đ'], ['d', 'D'], $search)));
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
    $pages = max(1, (int) ceil($total / BLOG_POSTS_PER_PAGE));
    $page = min(max(1, $page), $pages);
    $order = $sort === 'popular' ? 'score DESC, p.created_at DESC, p.id DESC' : 'p.created_at DESC, p.id DESC';
    $statement = db()->prepare(blog_post_select($userId) . $where . ' ORDER BY ' . $order
        . ' LIMIT ' . BLOG_POSTS_PER_PAGE . ' OFFSET ' . (($page - 1) * BLOG_POSTS_PER_PAGE));
    $statement->execute($parameters);
    return ['posts' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
}

// Owners, administrators, and recipients with edit permission may change a post. Deleting is narrower: can_delete_post().
function can_edit_post(array $post, ?array $user): bool
{
    return $user !== null && ($user['role'] === 'admin' || in_array(post_role($post, $user), ['owner', 'edit'], true));
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

// Per-user counters for the profile hero.
function user_profile_statistics(int $userId): array
{
    $statement = db()->prepare('SELECT'
        . ' (SELECT COUNT(*) FROM favorites f WHERE f.user_id = :favorite_user) AS favorites,'
        . ' (SELECT COUNT(*) FROM blog_posts p WHERE p.user_id = :author) AS posts,'
        . ' (SELECT COUNT(*) FROM blog_saves s JOIN blog_posts p ON p.id = s.post_id WHERE s.user_id = :saved_user' . visible_blog_condition() . ') AS saved,'
        . ' (SELECT COUNT(*) FROM blog_comments c WHERE c.user_id = :commenter) AS comments');
    $statement->execute(['favorite_user' => $userId, 'author' => $userId, 'saved_user' => $userId, 'commenter' => $userId]);
    return array_map('intval', $statement->fetch() ?: ['favorites' => 0, 'posts' => 0, 'saved' => 0, 'comments' => 0]);
}

function user_post_sort_options(): array
{
    return [
        'newest' => ['label' => 'Mới nhất', 'order' => 'p.created_at DESC, p.id DESC'],
        'oldest' => ['label' => 'Cũ nhất', 'order' => 'p.created_at ASC, p.id ASC'],
        'liked' => ['label' => 'Nhiều lượt thích', 'order' => 'likes DESC, p.created_at DESC, p.id DESC'],
        'discussed' => ['label' => 'Nhiều bình luận', 'order' => 'comment_count DESC, p.created_at DESC, p.id DESC'],
    ];
}

// The author's own posts, hidden ones included so they can see what moderators hid.
function list_user_posts(int $userId, string $search = '', string $sort = 'newest'): array
{
    $options = user_post_sort_options();
    $order = ($options[$sort] ?? $options['newest'])['order'];
    $where = ' WHERE p.user_id = ?';
    $parameters = [$userId];
    if ($search !== '') {
        $where .= " AND (p.title LIKE ? ESCAPE '!' OR p.excerpt LIKE ? ESCAPE '!' OR p.tag LIKE ? ESCAPE '!')";
        array_push($parameters, search_pattern($search), search_pattern($search), search_pattern($search));
    }
    $statement = db()->prepare(blog_post_select($userId) . $where . ' ORDER BY ' . $order . ' LIMIT 60');
    $statement->execute($parameters);
    return $statement->fetchAll();
}
