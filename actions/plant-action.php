<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/image-uploads.php';
require_post();
require_csrf();
require_admin();
$operation = request_string($_POST, 'operation');
if (!in_array($operation, ['create', 'update', 'delete'], true)) {
    abort_request(400, 'Thao tác cây không hợp lệ.');
}
$plantId = filter_var($_POST['plant_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($operation !== 'create' && $plantId === false) {
    abort_request(400, 'Thao tác cây không hợp lệ.');
}

$connection = db();
$connection->beginTransaction();
$oldImage = null;
$newImage = null;
// Remove a new upload if a database write fails before commit.
$imageCommitted = false;
register_shutdown_function(static function () use (&$newImage, &$imageCommitted): void {
    if (!$imageCommitted) {
        delete_image_upload($newImage, 'images/plants');
    }
});

if ($operation === 'delete') {
    $statement = $connection->prepare('SELECT image FROM plants WHERE id = ? FOR UPDATE');
    $statement->execute([$plantId]);
    $oldImage = $statement->fetchColumn();
    if ($oldImage === false) {
        $connection->rollBack();
        abort_request(404, 'Không tìm thấy cây này.');
    }
    // Favorites referencing this plant are removed by the foreign key cascade.
    $statement = $connection->prepare('DELETE FROM plants WHERE id = ?');
    $statement->execute([$plantId]);
    $connection->commit();
    $imageCommitted = true;
    delete_image_upload('images/' . $oldImage, 'images/plants');
    set_flash('Đã xóa cây khỏi bộ sưu tập.');
    redirect_to(app_url('admin-plants.php') . '#plants');
}

$name = trim(request_string($_POST, 'name'));
$category = trim(request_string($_POST, 'category'));
$description = trim(request_string($_POST, 'description'));
$care = trim(request_string($_POST, 'care'));
$errors = plant_errors($name, $category, $description, $care);
if ($operation === 'update') {
    $statement = $connection->prepare('SELECT image FROM plants WHERE id = ? FOR UPDATE');
    $statement->execute([$plantId]);
    $current = $statement->fetchColumn();
    if ($current === false) {
        $connection->rollBack();
        abort_request(404, 'Không tìm thấy cây này.');
    }
}
if ($errors === []) {
    try {
        $newImage = store_image_upload($_FILES['image'] ?? null, 'images/plants');
    } catch (InvalidArgumentException $exception) {
        $errors[] = $exception->getMessage();
    }
    if ($newImage === null && $operation === 'create') {
        $errors[] = 'Vui lòng chọn ảnh cho cây mới.';
    }
}
$formTarget = $operation === 'create' ? 'new' : (string) $plantId;
if ($errors !== []) {
    $connection->rollBack();
    $_SESSION['plant_errors'] = $errors;
    $_SESSION['plant_input'] = ['id' => $operation === 'create' ? 0 : (int) $plantId, 'name' => $name, 'category' => $category,
        'description' => $description, 'care' => $care];
    redirect_to(app_url('admin-plants.php') . '?plant=' . $formTarget . '#plant-form');
}

if ($operation === 'create') {
    $nextId = (int) $connection->query('SELECT COALESCE(MAX(id), 0) + 1 FROM plants FOR UPDATE')->fetchColumn();
    $statement = $connection->prepare('INSERT INTO plants (id, name, category, description, care, image) VALUES (?, ?, ?, ?, ?, ?)');
    // The plants.image column is relative to images/, matching the seed data and image_url().
    $statement->execute([$nextId, $name, $category, $description, $care, $newImage === null ? null : substr($newImage, strlen('images/'))]);
    $message = 'Đã thêm cây mới.';
} else {
    if ($newImage !== null) {
        $oldImage = $current;
        $statement = $connection->prepare('UPDATE plants SET name = ?, category = ?, description = ?, care = ?, image = ? WHERE id = ?');
        $statement->execute([$name, $category, $description, $care, substr($newImage, strlen('images/')), $plantId]);
    } else {
        $statement = $connection->prepare('UPDATE plants SET name = ?, category = ?, description = ?, care = ? WHERE id = ?');
        $statement->execute([$name, $category, $description, $care, $plantId]);
    }
    $message = 'Đã cập nhật thông tin cây.';
}
$connection->commit();
$imageCommitted = true;
delete_image_upload($oldImage === null ? null : 'images/' . $oldImage, 'images/plants');
set_flash($message);
redirect_to(app_url('admin-plants.php') . '#plants');
