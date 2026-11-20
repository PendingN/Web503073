<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/plant-advisor.php';
require_post();
require_csrf();

$slug = trim(request_string($_POST, 'slug'));
$postUrl = app_url('blog-post.php') . '?slug=' . rawurlencode($slug);
$user = require_login($postUrl);
$operation = request_string($_POST, 'operation');
if (!in_array($operation, ['unlock', 'enable', 'change', 'disable'], true)) {
    abort_request(400, 'Thao tác không hợp lệ.');
}
$post = find_post($slug);
if ($post === null) {
    abort_request(404, 'Không tìm thấy bài viết.');
}

$back = static function (string $message, string $anchor = '#post-manage') use ($postUrl): void {
    set_flash($message);
    $_SESSION['post_manage_open'] = 'lock';
    redirect_to($postUrl . $anchor);
};

if ($operation === 'unlock') {
    // Five tries per post every ten minutes per session, plus a wider per-address cap.
    $runtime = dirname(__DIR__) . '/.runtime/post-unlock-rate';
    if (!advisor_rate_allow($runtime, 'session:' . session_id() . ':' . $post['id'], 5, 600)
        || !advisor_rate_allow($runtime, 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 40, 3600)) {
        set_flash('Bạn đã nhập sai quá nhiều lần. Vui lòng thử lại sau 10 phút.');
        redirect_to($postUrl);
    }
    if (!verify_post_lock($post, request_string($_POST, 'password'))) {
        set_flash('Mật khẩu chưa đúng.');
        redirect_to($postUrl);
    }
    remember_post_unlock($post);
    redirect_to($postUrl);
}

// Managing the lock is for the author only, and the post must already be unlocked in this session.
if (!is_post_owner($post, $user)) {
    abort_request(403, 'Chỉ tác giả mới được quản lý khóa của bài viết.');
}
if (!post_is_unlocked($post)) {
    set_flash('Bài viết đang được khóa. Hãy nhập mật khẩu của bài trước.');
    redirect_to($postUrl);
}

$newPassword = request_string($_POST, 'new_password');
$confirmation = request_string($_POST, 'password_confirmation');
$currentPassword = request_string($_POST, 'current_password');

if ($operation === 'enable') {
    if (post_is_locked($post)) {
        $back('Bài viết đã được khóa sẵn.');
    }
    $errors = post_lock_password_errors($newPassword, $confirmation);
    if ($errors !== []) {
        $back(implode(' ', $errors));
    }
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    db()->prepare('UPDATE blog_posts SET lock_hash = ? WHERE id = ?')->execute([$hash, $post['id']]);
    remember_post_unlock(['lock_hash' => $hash] + $post);
    record_activity($user, 'blog.locked', 'Khóa bài #' . $post['id'] . ': ' . $post['title'], $user);
    $back('Đã khóa bài viết bằng mật khẩu.');
}

if (!post_is_locked($post)) {
    $back('Bài viết chưa được khóa.');
}
if (!verify_post_lock($post, $currentPassword)) {
    $back('Mật khẩu khóa hiện tại chưa đúng.');
}

if ($operation === 'change') {
    $errors = post_lock_password_errors($newPassword, $confirmation);
    if ($errors === [] && password_verify($newPassword, (string) $post['lock_hash'])) {
        $errors[] = 'Mật khẩu mới cần khác mật khẩu hiện tại.';
    }
    if ($errors !== []) {
        $back(implode(' ', $errors));
    }
    $hash = password_hash($newPassword, PASSWORD_DEFAULT);
    db()->prepare('UPDATE blog_posts SET lock_hash = ? WHERE id = ?')->execute([$hash, $post['id']]);
    remember_post_unlock(['lock_hash' => $hash] + $post);
    record_activity($user, 'blog.lock_changed', 'Đổi mật khẩu khóa bài #' . $post['id'] . ': ' . $post['title'], $user);
    $back('Đã đổi mật khẩu khóa. Người khác cần nhập mật khẩu mới.');
}

db()->prepare('UPDATE blog_posts SET lock_hash = NULL WHERE id = ?')->execute([$post['id']]);
forget_post_unlock((int) $post['id']);
record_activity($user, 'blog.unlocked', 'Tắt khóa bài #' . $post['id'] . ': ' . $post['title'], $user);
$back('Đã tắt khóa bài viết.');
