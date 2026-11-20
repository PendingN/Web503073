<?php
declare(strict_types=1);

// Who may do what with a blog post: ownership, sharing permissions, and per-post password locks.
// A locked post asks for its own password before anyone (owner included) can view, edit, or delete it.

const POST_LOCK_MIN_LENGTH = 4;

function post_share_permission(int $postId, int $userId): ?string
{
    if ($userId <= 0) {
        return null;
    }
    $statement = db()->prepare('SELECT permission FROM blog_shares WHERE post_id = ? AND user_id = ?');
    $statement->execute([$postId, $userId]);
    $permission = $statement->fetchColumn();
    return is_string($permission) ? $permission : null;
}

/** 'owner', 'edit', 'read', or null. Administrators are handled by the callers that need them. */
function post_role(array $post, ?array $user): ?string
{
    if ($user === null) {
        return null;
    }
    if ((int) $post['user_id'] === (int) $user['id']) {
        return 'owner';
    }
    if (array_key_exists('my_share', $post)) {
        return $post['my_share'] !== null ? (string) $post['my_share'] : null;
    }
    return post_share_permission((int) $post['id'], (int) $user['id']);
}

function is_post_owner(array $post, ?array $user): bool
{
    return $user !== null && (int) $post['user_id'] === (int) $user['id'];
}

function can_delete_post(array $post, ?array $user): bool
{
    return $user !== null && ($user['role'] === 'admin' || is_post_owner($post, $user));
}

function post_is_locked(array $post): bool
{
    return !empty($post['lock_hash']);
}

// Changing the password changes this token, so earlier unlocks stop working.
function post_lock_token(array $post): string
{
    return substr(hash('sha256', (string) $post['lock_hash']), 0, 32);
}

function post_is_unlocked(array $post): bool
{
    if (!post_is_locked($post)) {
        return true;
    }
    $token = $_SESSION['post_unlocks'][(int) $post['id']] ?? null;
    return is_string($token) && hash_equals(post_lock_token($post), $token);
}

function remember_post_unlock(array $post): void
{
    $_SESSION['post_unlocks'][(int) $post['id']] = post_lock_token($post);
}

function forget_post_unlock(int $postId): void
{
    unset($_SESSION['post_unlocks'][$postId]);
}

/** Errors for a new lock password entered twice. */
function post_lock_password_errors(string $password, string $confirmation): array
{
    $errors = [];
    if (mb_strlen($password, 'UTF-8') < POST_LOCK_MIN_LENGTH || strlen($password) > 72) {
        $errors[] = 'Mật khẩu khóa cần từ ' . POST_LOCK_MIN_LENGTH . ' ký tự và tối đa 72 byte.';
    }
    if ($password !== $confirmation) {
        $errors[] = 'Hai lần nhập mật khẩu khóa chưa khớp.';
    }
    return $errors;
}

function verify_post_lock(array $post, string $password): bool
{
    return post_is_locked($post) && strlen($password) <= 72 && password_verify($password, (string) $post['lock_hash']);
}

/** Recipients of a post, for the owner's sharing panel. */
function list_post_shares(int $postId): array
{
    $statement = db()->prepare('SELECT s.user_id, s.permission, s.created_at, s.updated_at, u.email, u.name'
        . ' FROM blog_shares s JOIN users u ON u.id = s.user_id WHERE s.post_id = ? ORDER BY s.created_at, u.email');
    $statement->execute([$postId]);
    return $statement->fetchAll();
}

/** Posts shared with a user, newest share first. */
function list_shared_with(int $userId): array
{
    $statement = db()->prepare('SELECT p.id, p.slug, p.title, p.tag, p.excerpt, p.image_path, p.lock_hash, p.created_at AS post_created_at,'
        . ' s.permission, s.created_at AS shared_at, s.seen_at, COALESCE(o.name, \'Người dùng đã xóa\') AS owner_name, o.email AS owner_email,'
        . ' COALESCE(b.name, o.name) AS shared_by_name'
        . ' FROM blog_shares s JOIN blog_posts p ON p.id = s.post_id'
        . ' LEFT JOIN users o ON o.id = p.user_id LEFT JOIN users b ON b.id = s.shared_by'
        . ' WHERE s.user_id = ? AND p.is_hidden = 0 ORDER BY s.created_at DESC, p.id DESC');
    $statement->execute([$userId]);
    return $statement->fetchAll();
}

function count_unseen_shares(int $userId): int
{
    try {
        $statement = db()->prepare('SELECT COUNT(*) FROM blog_shares s JOIN blog_posts p ON p.id = s.post_id'
            . ' WHERE s.user_id = ? AND s.seen_at IS NULL AND p.is_hidden = 0');
        $statement->execute([$userId]);
        return (int) $statement->fetchColumn();
    } catch (PDOException $exception) {
        if ((int) ($exception->errorInfo[1] ?? 0) === 1146) {
            return 0;
        }
        throw $exception;
    }
}

function mark_shares_seen(int $userId): void
{
    db()->prepare('UPDATE blog_shares SET seen_at = CURRENT_TIMESTAMP WHERE user_id = ? AND seen_at IS NULL')->execute([$userId]);
}

/** Excerpt for lists: a locked post never shows its text. */
function post_list_excerpt(array $post): string
{
    return (int) ($post['is_locked'] ?? 0) === 1 ? 'Nội dung được bảo vệ bằng mật khẩu.' : (string) $post['excerpt'];
}

/** Small status icons (locked, shared, private) shown next to a post title in every list. */
function render_post_status_icons(array $post): void
{
    $icons = [];
    if ((int) ($post['is_locked'] ?? 0) === 1) {
        $icons[] = ['locked', 'Có mật khẩu', '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>'];
    }
    if ((int) ($post['share_count'] ?? 0) > 0 || !empty($post['my_share'])) {
        $icons[] = ['shared', !empty($post['my_share']) ? 'Được chia sẻ với bạn' : 'Đã chia sẻ', '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/>'];
    }
    if ((int) ($post['is_private'] ?? 0) === 1) {
        $icons[] = ['private', 'Riêng tư', '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19M1 1l22 22"/>'];
    }
    if ($icons === []) {
        return;
    }
    echo '<span class="post-status-icons">';
    foreach ($icons as [$kind, $label, $path]) {
        echo '<span class="post-status-icon is-' . e($kind) . '" title="' . e($label) . '">'
            . '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>'
            . '<span class="sr-only">' . e($label) . '</span></span>';
    }
    echo '</span>';
}
