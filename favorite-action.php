<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_to('collection.php');
}

$returnTo = safe_return_url($_POST['return_to'] ?? null);
$plantId = filter_var($_POST['plant_id'] ?? null, FILTER_VALIDATE_INT);

if (!valid_csrf_token($_POST['csrf_token'] ?? null) || $plantId === false || find_plant((int) $plantId) === null) {
    set_flash('Yêu cầu không hợp lệ. Vui lòng thử lại.');
    redirect_to($returnTo);
}

$favorites = favorite_ids();
$plantId = (int) $plantId;
$plant = find_plant($plantId);

if (in_array($plantId, $favorites, true)) {
    $_SESSION['favorites'] = array_values(array_filter($favorites, static fn (int $id): bool => $id !== $plantId));
    set_flash('Đã xóa ' . $plant['name'] . ' khỏi mục yêu thích.');
} else {
    $favorites[] = $plantId;
    $_SESSION['favorites'] = array_values(array_unique($favorites));
    set_flash('Đã thêm ' . $plant['name'] . ' vào mục yêu thích.');
}

redirect_to($returnTo);
