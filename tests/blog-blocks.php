<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/blog-blocks.php';
function e(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function app_url(string $path = ''): string { return '/Web503073/' . $path; }
$checks = 0;
function check(bool $condition, string $label): void {
    global $checks;
    if (!$condition) { throw new RuntimeException($label); }
    $checks++;
}
function rejected(array $data, array $images = []): bool {
    try { normalize_blog_blocks(json_encode($data), $images); return false; }
    catch (InvalidArgumentException $exception) { return true; }
}
$unsafe = '<script>alert(1)</script><strong onclick="bad()">Cây xanh</strong><img src=x onerror=bad()><a href="javascript:bad()">x</a>';
$safe = blog_inline($unsafe);
check($safe === '<strong>Cây xanh</strong>x', 'Strip executable markup and unsafe links');
check(str_contains(blog_inline('<a href="https://example.com/?a=1&amp;b=2" onclick="x">Link</a>'), 'rel="nofollow noopener"'), 'Allow safe HTTPS links only');
check(blog_inline('<a href="data:text/html,x">x</a>') === 'x', 'Reject data links');
check(blog_inline('Chăm cây &amp; lá <em>mới</em>') === 'Chăm cây &amp; lá <em>mới</em>', 'Preserve Vietnamese and entities');
$legacy = blog_blocks(['content' => "Đoạn một\n\nĐoạn hai\nxuống dòng"]);
check(count($legacy) === 2 && str_contains($legacy[1]['html'], '<br>'), 'Legacy paragraphs and line breaks');
$image = 'images/blog/' . str_repeat('a', 32) . '.png';
$data = ['version' => 1, 'blocks' => [
    ['type' => 'h2', 'html' => '<b>Chăm cây</b>'],
    ['type' => 'ul', 'items' => ['Tưới nước', '<em>Đủ sáng</em>']],
    ['type' => 'image', 'src' => 'upload_ab', 'alt' => 'Lá xanh', 'caption' => 'Góc nhà'],
]];
$blocks = normalize_blog_blocks(json_encode($data), [], ['upload_ab' => $image]);
check($blocks[2]['src'] === $image, 'Resolve upload references');
check(blog_block_images($blocks) === [$image], 'Collect only managed image references');
check(str_contains(blog_blocks_text($blocks), 'Đủ sáng') && !str_contains(blog_blocks_text($blocks), '<em>'), 'Generate searchable plain text');
$rendered = render_blog_blocks($blocks);
check(str_contains($rendered, '<h2>') && str_contains($rendered, '<ul>') && str_contains($rendered, '<figcaption>Góc nhà</figcaption>'), 'Render structured article');
check(rejected($data), 'Reject foreign and unresolved images');
check(rejected(['version' => 2, 'blocks' => []]), 'Reject unknown schema version');
check(rejected(['version' => 1, 'blocks' => [['type' => 'iframe']]]), 'Reject unsupported blocks');
check(rejected(['version' => 1, 'blocks' => [['type' => 'paragraph', 'html' => []]]]), 'Reject malformed text');
check(rejected(['version' => 1, 'blocks' => array_fill(0, 201, ['type' => 'divider'])]), 'Bound block count');
check(rejected(['version' => 1, 'blocks' => [['type' => 'ul', 'items' => [[]]]]]), 'Reject malformed list items');
check(normalize_blog_blocks(json_encode(['version' => 1, 'blocks' => [['type' => 'image', 'src' => $image]]]), [$image])[0]['src'] === $image, 'Allow retained image owned by post');
echo "PASS: $checks block validation, rendering, legacy and XSS checks.\n";
