<?php
declare(strict_types=1);

// Standalone checks for the AI post summary: no database, network, or API key required.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/blog-ai.php';

$checks = 0;
function check(bool $condition, string $label): void
{
    global $checks;
    if (!$condition) {
        throw new RuntimeException($label);
    }
    $checks++;
}

$post = ['id' => 1, 'title' => 'Tưới trầu bà', 'excerpt' => 'Khi nào nên tưới.',
    'content' => "Sờ đất trước khi tưới.\n\nĐất khô 2 đốt ngón tay thì tưới đẫm.", 'content_blocks' => null];

// A well-formed answer is kept, trimmed and capped at five points.
$good = validate_blog_summary(['summary' => '  Tưới khi đất khô.  ', 'points' => ['Sờ đất trước.', ' Tưới đẫm. ', '', 7, 'Ý 3', 'Ý 4', 'Ý 5', 'Ý 6']]);
check($good !== null && $good['summary'] === 'Tưới khi đất khô.', 'Summary text must be trimmed.');
check($good['points'] === ['Sờ đất trước.', 'Tưới đẫm.', 'Ý 3', 'Ý 4', 'Ý 5'], 'Points must drop blanks and non-strings and keep at most five.');

// Unusable answers are rejected instead of being shown.
foreach ([null, 'text', [], ['summary' => '', 'points' => ['a', 'b']], ['summary' => 'x', 'points' => ['only one']],
    ['summary' => 'x', 'points' => 'not a list'], ['points' => ['a', 'b']]] as $index => $bad) {
    check(validate_blog_summary($bad) === null, "Bad answer #$index must be rejected.");
}

// Prompts carry the post text and the stay-on-source rule.
$userPrompt = blog_summary_user_prompt($post);
check(str_contains($userPrompt, 'Sờ đất trước khi tưới.') && str_contains($userPrompt, 'Tưới trầu bà'), 'User prompt must include title and body.');
check(str_contains(blog_summary_system_prompt(), 'Chỉ dùng thông tin có trong bài'), 'System prompt must forbid outside facts.');

// The hash changes when the post changes, so a stale summary is not shown.
$edited = ['content' => $post['content'] . ' Thêm ý mới.'] + $post;
check(blog_summary_hash($post) === blog_summary_hash($post) && blog_summary_hash($post) !== blog_summary_hash($edited), 'Content hash must track edits.');

// Long posts are cut with a visible marker rather than silently.
$long = ['content' => str_repeat('Lá xanh. ', 5000)] + $post;
check(str_contains(blog_summary_user_prompt($long), 'phần sau đã được lược bớt'), 'Long posts must be marked as shortened.');

fwrite(STDOUT, "Blog summary checks passed: $checks" . PHP_EOL);
