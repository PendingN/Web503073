<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_post();
require_csrf();

$slug = trim(request_string($_POST, 'slug'));
$postUrl = app_url('blog-post.php') . '?slug=' . rawurlencode($slug);
$user = require_login($postUrl);
$operation = request_string($_POST, 'operation');
if (!in_array($operation, ['add', 'update', 'revoke'], true)) {
    abort_request(400, 'Thao tác không hợp lệ.');
}
$post = find_post($slug);
if ($post === null) {
    abort_request(404, 'Không tìm thấy bài viết.');
}
if (!is_post_owner($post, $user)) {
    abort_request(403, 'Chỉ tác giả mới được chia sẻ bài viết.');
}
if (!post_is_unlocked($post)) {
    set_flash('Bài viết đang được khóa. Hãy nhập mật khẩu của bài trước.');
    redirect_to($postUrl);
}

$back = static function (string $message) use ($postUrl): void {
    set_flash($message);
    $_SESSION['post_manage_open'] = 'share';
    redirect_to($postUrl . '#post-manage');
};
$permission = request_string($_POST, 'permission', 'read');
if (!in_array($permission, ['read', 'edit'], true)) {
    $back('Quyền chia sẻ không hợp lệ.');
}
$permissionLabel = $permission === 'edit' ? 'được sửa' : 'chỉ xem';

if ($operation === 'add') {
    $emails = array_values(array_unique(array_filter(array_map(
        static fn(string $email): string => strtolower(trim($email)),
        preg_split('/[\s,;]+/u', request_string($_POST, 'emails')) ?: []
    ))));
    if ($emails === []) {
        $back('Vui lòng nhập ít nhất một email.');
    }
    if (count($emails) > 20) {
        $back('Mỗi lần chia sẻ tối đa 20 email.');
    }
    $invalid = array_values(array_filter($emails, static fn(string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) === false));
    if ($invalid !== []) {
        $back('Email không hợp lệ: ' . implode(', ', $invalid) . '.');
    }
    $recipients = [];
    $unknown = [];
    foreach ($emails as $email) {
        $recipient = find_user_by_email($email);
        if ($recipient === null || $recipient['status'] !== 'active') {
            $unknown[] = $email;
        } elseif ((int) $recipient['id'] === (int) $user['id']) {
            $back('Bạn không cần chia sẻ bài viết cho chính mình.');
        } else {
            $recipients[] = $recipient;
        }
    }
    // Nothing is shared when any address is wrong, so the owner can fix the list and resend it as a whole.
    if ($unknown !== []) {
        $back('Chưa có tài khoản nào dùng email: ' . implode(', ', $unknown) . '. Người nhận cần đăng ký trước khi được chia sẻ.');
    }
    $statement = db()->prepare('INSERT INTO blog_shares (post_id, user_id, permission, shared_by) VALUES (?, ?, ?, ?)'
        . ' ON DUPLICATE KEY UPDATE permission = VALUES(permission)');
    foreach ($recipients as $recipient) {
        $statement->execute([$post['id'], $recipient['id'], $permission, $user['id']]);
        record_activity($user, 'blog.shared', 'Chia sẻ bài #' . $post['id'] . ' (' . $permissionLabel . ')', $recipient);
    }
    $back('Đã chia sẻ với ' . count($recipients) . ' người, quyền ' . $permissionLabel . '. Họ sẽ thấy thông báo khi đăng nhập.');
}

$recipientId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$statement = db()->prepare('SELECT u.id, u.name, u.email FROM blog_shares s JOIN users u ON u.id = s.user_id WHERE s.post_id = ? AND s.user_id = ?');
$statement->execute([$post['id'], $recipientId ?: 0]);
$recipient = $statement->fetch();
if (!$recipient) {
    $back('Người này không còn trong danh sách chia sẻ.');
}

if ($operation === 'update') {
    db()->prepare('UPDATE blog_shares SET permission = ? WHERE post_id = ? AND user_id = ?')->execute([$permission, $post['id'], $recipient['id']]);
    record_activity($user, 'blog.share_changed', 'Đổi quyền bài #' . $post['id'] . ' thành ' . $permissionLabel, $recipient);
    $back('Đã đổi quyền của ' . $recipient['email'] . ' thành ' . $permissionLabel . '.');
}

db()->prepare('DELETE FROM blog_shares WHERE post_id = ? AND user_id = ?')->execute([$post['id'], $recipient['id']]);
record_activity($user, 'blog.unshared', 'Thu hồi quyền xem bài #' . $post['id'], $recipient);
$back('Đã thu hồi quyền của ' . $recipient['email'] . '.');
