<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/blog-images.php';
require_post();
require_csrf();
$returnTo = safe_return_url($_POST['return_to'] ?? null, 'blog.php');
$user = require_login($returnTo);
$adminEditor = $user['role'] === 'admin' && parse_url($returnTo, PHP_URL_PATH) === app_url('admin-posts.php');
$operation = request_string($_POST, 'operation');
if (!in_array($operation, ['create', 'update', 'delete', 'vote', 'save', 'comment', 'delete-comment', 'hide', 'unhide'], true)) {
    abort_request(400, 'Thao tác không hợp lệ.');
}

$connection = db();
$connection->beginTransaction();
// Serialize writes with account blocking and other actions from the same user.
$statement = $connection->prepare('SELECT status, session_version FROM users WHERE id = ? FOR UPDATE');
$statement->execute([$user['id']]);
$account = $statement->fetch();
if (!$account || $account['status'] !== 'active' || (int) $account['session_version'] !== $_SESSION['auth_version']) {
    $connection->rollBack();
    abort_request(403, 'Phiên đăng nhập đã kết thúc. Vui lòng đăng nhập lại.');
}
$slug = request_string($_POST, 'slug');
if ($operation !== 'create') {
    $statement = $connection->prepare('SELECT * FROM blog_posts WHERE slug = ? FOR UPDATE');
    $statement->execute([$slug]);
    $post = $statement->fetch();
    if (!$post) {
        $connection->rollBack();
        abort_request(404, 'Không tìm thấy bài viết.');
    }
    if (in_array($operation, ['update', 'delete'], true) && !can_edit_post($post, $user)) {
        $connection->rollBack();
        abort_request(403, 'Bạn không có quyền sửa hoặc xóa bài viết này.');
    }
    if (in_array($operation, ['hide', 'unhide'], true) && $user['role'] !== 'admin') {
        $connection->rollBack();
        abort_request(403, 'Chỉ quản trị viên mới được ẩn hoặc hiện bài viết.');
    }
    // Hidden posts behave like missing posts for everyone except administrators.
    if ((int) $post['is_hidden'] === 1 && $user['role'] !== 'admin') {
        $connection->rollBack();
        abort_request(404, 'Không tìm thấy bài viết.');
    }
}

$newImage = null;
$oldImage = null;
// Remove a new upload if a database write fails before commit.
$imageCommitted = false;
register_shutdown_function(static function () use (&$newImage, &$imageCommitted): void {
    if (!$imageCommitted) {
        delete_blog_image($newImage);
    }
});

if ($operation === 'create' || $operation === 'update') {
    $title = trim(request_string($_POST, 'title'));
    $content = trim(request_string($_POST, 'content'));
    $tag = request_string($_POST, 'tag');
    $errors = blog_post_errors($title, $content, $tag);
    if ($errors === []) {
        try {
            $newImage = store_blog_image($_FILES['image'] ?? null);
        } catch (InvalidArgumentException $exception) {
            $errors[] = $exception->getMessage();
        }
    }
    if ($errors !== []) {
        $connection->rollBack();
        $_SESSION['blog_draft'] = ['title' => mb_substr($title, 0, 120), 'content' => mb_substr($content, 0, 10000), 'tag' => $tag, 'slug' => $operation === 'update' ? $slug : ''];
        set_flash(implode(' ', $errors) . ' Nếu đã chọn ảnh, vui lòng chọn lại trước khi gửi.');
        redirect_to(app_url($adminEditor ? 'admin-posts.php' : 'blog.php') . ($operation === 'update' ? '?edit=' . rawurlencode($slug) : ($adminEditor ? '?create=1' : '')));
    }
    $excerpt = mb_substr(preg_replace('/\s+/u', ' ', $content), 0, 220, 'UTF-8');
    if ($operation === 'create') {
        $slug = 'bai-viet-' . bin2hex(random_bytes(12));
        $statement = $connection->prepare('INSERT INTO blog_posts (user_id, slug, title, tag, excerpt, content, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $statement->execute([$user['id'], $slug, $title, $tag, $excerpt, $content, $newImage]);
        $createdPostId = (int) $connection->lastInsertId();
    } else {
        $oldImage = $newImage !== null ? $post['image_path'] : null;
        $statement = $connection->prepare('UPDATE blog_posts SET title = ?, tag = ?, excerpt = ?, content = ?, image_path = ? WHERE id = ?');
        $statement->execute([$title, $tag, $excerpt, $content, $newImage ?? $post['image_path'], $post['id']]);
    }
    unset($_SESSION['blog_draft']);
    $returnTo = $adminEditor ? app_url('admin-posts.php?edit=' . rawurlencode($slug)) . '#post-editor' : app_url('blog-post.php?slug=' . rawurlencode($slug));
    $message = $operation === 'create' ? 'Đã đăng bài viết.' : 'Đã cập nhật bài viết.';
} elseif ($operation === 'delete') {
    $oldImage = $post['image_path'];
    $statement = $connection->prepare('DELETE FROM blog_posts WHERE id = ?');
    $statement->execute([$post['id']]);
    $returnTo = app_url($adminEditor ? 'admin-posts.php' : 'blog.php');
    $message = 'Đã xóa bài viết.';
} elseif ($operation === 'hide' || $operation === 'unhide') {
    $statement = $connection->prepare('UPDATE blog_posts SET is_hidden = ? WHERE id = ?');
    $statement->execute([$operation === 'hide' ? 1 : 0, $post['id']]);
    $returnTo = app_url($adminEditor ? 'admin-posts.php' : 'blog.php');
    $message = $operation === 'hide' ? 'Đã ẩn bài viết khỏi trang công khai.' : 'Đã hiện lại bài viết.';
} elseif ($operation === 'vote') {
    $value = filter_var($_POST['value'] ?? null, FILTER_VALIDATE_INT);
    if (!in_array($value, [-1, 0, 1], true)) {
        $connection->rollBack();
        abort_request(400, 'Lượt bình chọn không hợp lệ.');
    }
    $statement = $connection->prepare('INSERT INTO blog_votes (user_id, post_id, value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE value = VALUES(value)');
    $statement->execute([$user['id'], $post['id'], $value]);
    $message = 'Đã cập nhật bình chọn.';
} elseif ($operation === 'save') {
    $saved = request_string($_POST, 'saved');
    if (!in_array($saved, ['0', '1'], true)) {
        $connection->rollBack();
        abort_request(400, 'Trạng thái lưu không hợp lệ.');
    }
    $sql = $saved === '1'
        ? 'INSERT INTO blog_saves (user_id, post_id) VALUES (?, ?) ON DUPLICATE KEY UPDATE post_id = VALUES(post_id)'
        : 'DELETE FROM blog_saves WHERE user_id = ? AND post_id = ?';
    $statement = $connection->prepare($sql);
    $statement->execute([$user['id'], $post['id']]);
    $message = $saved === '1' ? 'Đã lưu bài viết.' : 'Đã bỏ lưu bài viết.';
} elseif ($operation === 'comment') {
    $content = trim(request_string($_POST, 'content'));
    $returnTo = app_url('blog-post.php?slug=' . rawurlencode($slug)) . '#comments';
    if ($content === '' || mb_strlen($content, 'UTF-8') > 2000) {
        $connection->rollBack();
        $_SESSION['comment_draft'] = ['slug' => $slug, 'content' => mb_substr($content, 0, 2000)];
        set_flash('Bình luận cần có từ 1 đến 2.000 ký tự.');
        redirect_to($returnTo);
    }
    $statement = $connection->prepare('INSERT INTO blog_comments (user_id, post_id, content) VALUES (?, ?, ?)');
    $statement->execute([$user['id'], $post['id'], $content]);
    $createdCommentId = (int) $connection->lastInsertId();
    unset($_SESSION['comment_draft']);
    $message = 'Đã gửi bình luận.';
} else {
    $commentId = filter_var($_POST['comment_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $statement = $connection->prepare('SELECT user_id FROM blog_comments WHERE id = ? AND post_id = ?');
    $statement->execute([$commentId ?: 0, $post['id']]);
    $authorId = $statement->fetchColumn();
    if ($authorId === false || ($user['role'] !== 'admin' && (int) $authorId !== (int) $user['id'])) {
        $connection->rollBack();
        abort_request($authorId === false ? 404 : 403, 'Không thể xóa bình luận này.');
    }
    $statement = $connection->prepare('DELETE FROM blog_comments WHERE id = ?');
    $statement->execute([$commentId]);
    $returnTo = app_url('blog-post.php?slug=' . rawurlencode($slug)) . '#comments';
    $message = 'Đã xóa bình luận.';
}
$activityEvents = ['create' => 'blog.created', 'update' => 'blog.updated', 'delete' => 'blog.deleted', 'vote' => 'blog.vote',
    'save' => ($saved ?? '0') === '1' ? 'blog.saved' : 'blog.unsaved', 'comment' => 'comment.created', 'delete-comment' => 'comment.deleted', 'hide' => 'blog.hidden', 'unhide' => 'blog.unhidden'];
$subjectId = $operation === 'delete-comment' ? (int) $authorId : (int) ($post['user_id'] ?? $user['id']);
$activitySubject = $subjectId === (int) $user['id'] ? $user : ($subjectId > 0 ? find_user($subjectId) : null);
$description = 'Bài #' . ($createdPostId ?? $post['id']) . ': ' . ($title ?? $post['title']);
if ($operation === 'vote') {
    $description = 'Bình chọn ' . $value . ' · ' . $description;
} elseif ($operation === 'comment' || $operation === 'delete-comment') {
    $description = 'Bình luận #' . ($createdCommentId ?? $commentId) . ' · ' . $description;
}
record_activity($user, $activityEvents[$operation], $description, $activitySubject);
$reaction = null;
if (wants_json() && in_array($operation, ['vote', 'save'], true)) {
    $statement = $connection->prepare('SELECT COUNT(CASE WHEN value = 1 THEN 1 END) AS likes,'
        . ' COUNT(CASE WHEN value = -1 THEN 1 END) AS dislikes FROM blog_votes WHERE post_id = ?');
    $statement->execute([$post['id']]);
    $counts = $statement->fetch();
    $statement = $connection->prepare('SELECT value FROM blog_votes WHERE post_id = ? AND user_id = ?');
    $statement->execute([$post['id'], $user['id']]);
    $myVote = (int) $statement->fetchColumn();
    $statement = $connection->prepare('SELECT 1 FROM blog_saves WHERE post_id = ? AND user_id = ?');
    $statement->execute([$post['id'], $user['id']]);
    $reaction = ['likes' => (int) $counts['likes'], 'dislikes' => (int) $counts['dislikes'],
        'vote' => $myVote, 'saved' => (bool) $statement->fetchColumn(), 'message' => $message];
}
$connection->commit();
$imageCommitted = true;
delete_blog_image($oldImage);
if ($reaction !== null) {
    json_response($reaction);
}
set_flash($message);
redirect_to($returnTo);
