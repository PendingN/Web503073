<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/blog-ai.php';
require_once __DIR__ . '/../includes/plant-advisor.php';
require_once __DIR__ . '/../config/ai.php';
require_post();
require_csrf();

$slug = trim(request_string($_POST, 'slug'));
$returnTo = app_url('blog-post.php') . '?slug=' . rawurlencode($slug) . '#ai-summary';
require_login($returnTo);
$post = find_post($slug);
if ($post === null) {
    abort_request(404, 'Không tìm thấy bài viết.');
}
if (!post_is_unlocked($post)) {
    abort_request(403, 'Bài viết đang được khóa. Hãy nhập mật khẩu của bài trước.');
}

$respond = static function (int $status, array $data) use ($returnTo): void {
    if (wants_json()) {
        json_response($data, $status);
    }
    if (isset($data['message'])) {
        $_SESSION['summary_notice'] = $data['message'];
    }
    redirect_to($returnTo);
};

$config = load_ai_config();
if (!$config['enabled']) {
    $respond(503, ['message' => 'Tính năng tóm tắt AI chưa được bật trên máy chủ này.']);
}

$runtime = dirname(__DIR__) . '/.runtime/blog-summary-rate';
if (!advisor_rate_allow($runtime, 'session:' . session_id(), 10, 600)
    || !advisor_rate_allow($runtime, 'ip:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 40, 3600)) {
    $respond(429, ['message' => 'Bạn đã tạo tóm tắt khá nhiều lần. Vui lòng thử lại sau ít phút.']);
}

try {
    $summary = generate_blog_summary($post, static fn(string $system, string $user): mixed => deepseek_chat_json($config, $system, $user));
} catch (Throwable $exception) {
    error_log('Blog summary AI failed: ' . $exception->getMessage());
    $summary = null;
}
if ($summary === null) {
    $respond(502, ['message' => 'Trợ lý AI chưa tóm tắt được bài này. Vui lòng thử lại.']);
}
$respond(200, ['summary' => $summary['summary'], 'points' => $summary['points'],
    'created_at' => date('d/m/Y H:i', strtotime($summary['created_at']))]);
