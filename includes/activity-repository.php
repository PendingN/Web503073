<?php
declare(strict_types=1);

function activity_ready(): bool
{
    static $ready = null;
    if ($ready === null) {
        try {
            db()->query('SELECT 1 FROM user_activity LIMIT 1');
            $ready = true;
        } catch (PDOException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) !== 1146) {
                throw $exception;
            }
            $ready = false;
        }
    }
    return $ready;
}

function record_activity(?array $actor, string $event, string $description, ?array $subject = null): void
{
    if (!activity_ready()) {
        return;
    }
    // Keep identity snapshots when an account is removed, without locking other user rows.
    $statement = db()->prepare('INSERT INTO user_activity (actor_id, actor_name, subject_id, subject_name, event, description) VALUES (?, ?, ?, ?, ?, ?)');
    $statement->execute([$actor['id'] ?? null, $actor['name'] ?? 'Hệ thống', $subject['id'] ?? null,
        $subject['name'] ?? null, $event, mb_substr($description, 0, 255, 'UTF-8')]);
}

function activity_labels(): array
{
    return [
        'account.created' => 'Tạo tài khoản', 'auth.login' => 'Đăng nhập', 'auth.logout' => 'Đăng xuất',
        'page.view' => 'Xem trang', 'profile.updated' => 'Sửa hồ sơ', 'user.updated' => 'Quản trị sửa tài khoản',
        'user.blocked' => 'Khóa tài khoản', 'user.unblocked' => 'Mở khóa tài khoản',
        'blog.created' => 'Đăng bài viết', 'blog.updated' => 'Sửa bài viết', 'blog.deleted' => 'Xóa bài viết',
        'blog.vote' => 'Bình chọn bài viết', 'blog.saved' => 'Lưu bài viết', 'blog.unsaved' => 'Bỏ lưu bài viết',
        'comment.created' => 'Gửi bình luận', 'comment.deleted' => 'Xóa bình luận',
        'plant.saved' => 'Yêu thích cây', 'plant.unsaved' => 'Bỏ yêu thích cây',
    ];
}

function list_user_activity(string $period, int $userId, string $search, string $event, int $page): array
{
    $where = ' WHERE created_at <= CURRENT_TIMESTAMP';
    $parameters = [];
    if ($period !== 'all') {
        $where .= ' AND created_at >= DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 3 DAY)';
    }
    if ($userId > 0) {
        $where .= ' AND (actor_id = ? OR subject_id = ?)';
        array_push($parameters, $userId, $userId);
    }
    if ($search !== '') {
        $where .= " AND (actor_name LIKE ? ESCAPE '!' OR subject_name LIKE ? ESCAPE '!' OR description LIKE ? ESCAPE '!')";
        array_push($parameters, search_pattern($search), search_pattern($search), search_pattern($search));
    }
    if ($event !== '') {
        $where .= ' AND event = ?';
        $parameters[] = $event;
    }
    $statement = db()->prepare('SELECT COUNT(*) FROM user_activity' . $where);
    $statement->execute($parameters);
    $total = (int) $statement->fetchColumn();
    $pages = max(1, (int) ceil($total / 25));
    $page = min(max(1, $page), $pages);
    $statement = db()->prepare('SELECT * FROM user_activity' . $where . ' ORDER BY created_at DESC, id DESC LIMIT 25 OFFSET ' . (($page - 1) * 25));
    $statement->execute($parameters);
    return ['activities' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages];
}
