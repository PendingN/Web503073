<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Ho_Chi_Minh');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('web503073_session');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_set_cookie_params([
        'path' => app_url(),
        'httponly' => true,
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'samesite' => 'Lax',
    ]);
    session_start();
}
header('Cache-Control: no-store');

require_once __DIR__ . '/../data/content.php';
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/repositories.php';
require_once __DIR__ . '/auth.php';

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function request_string(array $source, string $key, string $default = ''): string
{
    return isset($source[$key]) && is_string($source[$key]) ? $source[$key] : $default;
}

function app_url(string $path = ''): string
{
    $directory = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    if (basename($directory) === 'actions') {
        $directory = str_replace('\\', '/', dirname($directory));
    }
    return rtrim($directory === '.' ? '' : $directory, '/') . '/' . ltrim($path, '/');
}

function redirect_to(string $location): void
{
    header('Location: ' . $location, true, 303);
    exit;
}

function abort_request(int $status, string $message): void
{
    http_response_code($status);
    echo '<!doctype html><html lang="vi"><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Vũ Điệu Rừng Xanh</title><link rel="stylesheet" href="' . e(app_url('css/style.css')) . '">';
    echo '<main class="not-found"><h1>' . e($message) . '</h1><a class="btn-brand" href="' . e(app_url('index.php')) . '">Về trang chủ ↗</a></main></html>';
    exit;
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        abort_request(405, 'Thao tác này cần được gửi từ biểu mẫu.');
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
    }
    return $_SESSION['csrf_token'];
}

function valid_csrf_token(mixed $token): bool
{
    return is_string($token) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function require_csrf(): void
{
    if (!valid_csrf_token($_POST['csrf_token'] ?? null)) {
        abort_request(403, 'Biểu mẫu đã hết hạn. Vui lòng tải lại trang và thử lại.');
    }
}

function favorite_ids(): array
{
    static $favorites;
    if ($favorites === null) {
        $user = current_user();
        $favorites = $user === null ? [] : user_favorite_ids((int) $user['id']);
    }
    return $favorites;
}

function is_favorite(int $plantId): bool
{
    return in_array($plantId, favorite_ids(), true);
}

function find_post(string $slug): ?array
{
    global $featuredPost, $posts;
    foreach ([$featuredPost, ...$posts] as $post) {
        if ($post['slug'] === $slug) {
            return $post;
        }
    }
    return null;
}

function set_flash(string $message): void
{
    $_SESSION['flash_message'] = $message;
}

function take_flash(): ?string
{
    $message = $_SESSION['flash_message'] ?? null;
    unset($_SESSION['flash_message']);
    return is_string($message) ? $message : null;
}

function safe_return_url(mixed $returnTo, string $fallback = 'collection.php'): string
{
    if (!is_string($returnTo) || preg_match('/[\\\\\x00-\x20\x7f]/', $returnTo)) {
        return app_url($fallback);
    }
    $parts = parse_url($returnTo);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || isset($parts['user'])
        || isset($parts['fragment']) || str_starts_with($returnTo, '//')) {
        return app_url($fallback);
    }
    $path = $parts['path'] ?? '';
    $base = app_url();
    if (str_starts_with($path, '/')) {
        if (!str_starts_with($path, $base)) {
            return app_url($fallback);
        }
        $path = substr($path, strlen($base));
    }
    $pages = ['index.php', 'dashboard.php', 'collection.php', 'favorites.php', 'profile.php', 'plant-detail.php', 'blog.php', 'blog-post.php', 'admin.php'];
    if (!in_array($path, $pages, true)) {
        return app_url($fallback);
    }
    return app_url($path) . (isset($parts['query']) ? '?' . $parts['query'] : '');
}

function current_request_url(): string
{
    return $_SERVER['REQUEST_URI'] ?? app_url('collection.php');
}

set_exception_handler(static function (Throwable $exception): void {
    error_log(get_class($exception) . ': ' . $exception->getMessage() . ' in ' . $exception->getFile() . ':' . $exception->getLine());
    if ($exception instanceof PDOException) {
        abort_request(503, 'Khu vườn tạm thời chưa sẵn sàng. Vui lòng thử lại sau.');
    }
    abort_request(500, 'Có lỗi xảy ra. Vui lòng thử lại sau.');
});

$plants = list_plants();
// Validate the session on every request, including public pages.
current_user();

