<?php
declare(strict_types=1);

require_once __DIR__ . '/blog-blocks.php';

// AI summary of one blog post. The model may only restate what the post says; its JSON answer
// is validated before it is stored, and the stored copy is reused until the post changes.

const BLOG_SUMMARY_MAX_INPUT = 20000;

function blog_summary_source(array $post): string
{
    $body = blog_blocks_text(blog_blocks($post));
    return trim((string) $post['title']) . "\n\n" . trim((string) $post['excerpt']) . "\n\n" . $body;
}

function blog_summary_hash(array $post): string
{
    return hash('sha256', blog_summary_source($post));
}

function blog_summary_system_prompt(): string
{
    return <<<PROMPT
Bạn tóm tắt bài viết về cây cảnh cho website "Vũ Điệu Rừng Xanh".
Chỉ dùng thông tin có trong bài. Không thêm lời khuyên, số liệu hay tên cây mà bài không nhắc tới.
Viết tiếng Việt, ngắn gọn, giữ đúng ý của tác giả.
Trả về JSON đúng dạng: {"summary": "1–2 câu nêu ý chính", "points": ["3 đến 5 ý quan trọng, mỗi ý một câu"]}
PROMPT;
}

function blog_summary_user_prompt(array $post): string
{
    $source = blog_summary_source($post);
    if (mb_strlen($source, 'UTF-8') > BLOG_SUMMARY_MAX_INPUT) {
        // Long posts are cut; the prompt says so, so the model does not invent an ending.
        $source = mb_substr($source, 0, BLOG_SUMMARY_MAX_INPUT, 'UTF-8') . "\n\n[Bài viết dài hơn, phần sau đã được lược bớt.]";
    }
    return "Tóm tắt bài viết sau:\n\n" . $source;
}

/** Returns ['summary' => string, 'points' => string[]] or null when the AI answer is unusable. */
function validate_blog_summary(mixed $data): ?array
{
    if (!is_array($data) || !is_string($data['summary'] ?? null) || !is_array($data['points'] ?? null)) {
        return null;
    }
    $summary = trim($data['summary']);
    $points = [];
    foreach ($data['points'] as $point) {
        if (is_string($point) && trim($point) !== '') {
            $points[] = mb_substr(trim($point), 0, 300, 'UTF-8');
        }
    }
    if ($summary === '' || count($points) < 2) {
        return null;
    }
    return ['summary' => mb_substr($summary, 0, 600, 'UTF-8'), 'points' => array_slice($points, 0, 5)];
}

/** The stored summary when it still matches the post's current content. */
function find_blog_summary(array $post): ?array
{
    try {
        $statement = db()->prepare('SELECT content_hash, summary_json, created_at FROM blog_summaries WHERE post_id = ?');
        $statement->execute([(int) $post['id']]);
        $row = $statement->fetch();
    } catch (PDOException $exception) {
        // Before scripts/setup-database.php adds the table, the page still renders without a summary.
        if ((int) ($exception->errorInfo[1] ?? 0) !== 1146) {
            throw $exception;
        }
        return null;
    }
    if (!$row || !hash_equals($row['content_hash'], blog_summary_hash($post))) {
        return null;
    }
    $summary = validate_blog_summary(json_decode($row['summary_json'], true));
    return $summary === null ? null : $summary + ['created_at' => $row['created_at']];
}

/**
 * Generates a fresh summary and stores it.
 *
 * @param callable(string, string): mixed $aiCaller Receives system and user prompts, returns decoded JSON.
 */
function generate_blog_summary(array $post, callable $aiCaller): ?array
{
    $summary = validate_blog_summary($aiCaller(blog_summary_system_prompt(), blog_summary_user_prompt($post)));
    if ($summary === null) {
        return null;
    }
    $statement = db()->prepare('INSERT INTO blog_summaries (post_id, content_hash, summary_json) VALUES (?, ?, ?)'
        . ' ON DUPLICATE KEY UPDATE content_hash = VALUES(content_hash), summary_json = VALUES(summary_json), created_at = CURRENT_TIMESTAMP');
    $statement->execute([(int) $post['id'], blog_summary_hash($post), json_encode($summary, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)]);
    return $summary + ['created_at' => date('Y-m-d H:i:s')];
}
