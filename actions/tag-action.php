<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_post();
require_csrf();
$admin = require_admin();
$operation = request_string($_POST, 'operation');
if (!in_array($operation, ['create', 'rename', 'delete'], true)) {
    abort_request(400, 'Thao tác chủ đề không hợp lệ.');
}
$tagId = filter_var($_POST['tag_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($operation !== 'create' && $tagId === false) {
    abort_request(400, 'Thao tác chủ đề không hợp lệ.');
}
$name = request_string($_POST, 'name');
$target = app_url('admin-tags.php');

$connection = db();
$connection->beginTransaction();
$errors = [];
if ($operation === 'create') {
    $result = create_tag($connection, $name);
    $errors = $result['errors'];
    $message = 'Đã thêm chủ đề “' . normalize_tag_name($name) . '”.';
    $description = 'Thêm chủ đề #' . (int) $result['id'] . ': ' . normalize_tag_name($name);
    $event = 'tag.created';
} else {
    // Lock the row so a concurrent rename or delete of the same tag waits for this one.
    $statement = $connection->prepare('SELECT name FROM tags WHERE id = ? FOR UPDATE');
    $statement->execute([$tagId]);
    $current = $statement->fetchColumn();
    if ($current === false) {
        $connection->rollBack();
        abort_request(404, 'Không tìm thấy chủ đề này.');
    }
    if ($operation === 'rename') {
        $errors = rename_tag($connection, (int) $tagId, $name);
        $message = 'Đã đổi tên chủ đề thành “' . normalize_tag_name($name) . '”. Mọi bài viết gắn chủ đề này tự hiện tên mới.';
        $description = 'Đổi tên chủ đề #' . (int) $tagId . ': ' . $current . ' → ' . normalize_tag_name($name);
        $event = 'tag.renamed';
    } else {
        $statement = $connection->prepare('SELECT COUNT(*) FROM post_tags WHERE tag_id = ?');
        $statement->execute([$tagId]);
        $linked = (int) $statement->fetchColumn();
        // Only the links go (foreign key cascade); the posts themselves stay.
        delete_tag($connection, (int) $tagId);
        $message = 'Đã xóa chủ đề “' . $current . '”.' . ($linked > 0 ? ' ' . $linked . ' bài viết vẫn được giữ nguyên.' : '');
        $description = 'Xóa chủ đề #' . (int) $tagId . ': ' . $current;
        $event = 'tag.deleted';
    }
}

if ($errors !== []) {
    $connection->rollBack();
    $_SESSION['tag_error'] = ['operation' => $operation, 'tag_id' => $tagId === false ? 0 : (int) $tagId,
        'name' => mb_substr($name, 0, 200, 'UTF-8'), 'messages' => $errors];
    redirect_to($target);
}
$connection->commit();
record_activity($admin, $event, $description);
set_flash($message);
redirect_to($target);
