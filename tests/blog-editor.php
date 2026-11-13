<?php
declare(strict_types=1);
// Runs inside the disposable database owned by tests/run.php.
$editorGate = $reader->request('blog-editor.php');
check($editorGate['status'] === 303 && str_contains($editorGate['headers']['location'], rawurlencode($prefix . '/blog-editor.php')), 'Editor must require login and preserve its return URL.');
check($aliceBrowser->request('blog.php?edit=legacy-url')['headers']['location'] === $prefix . '/blog-editor.php?edit=legacy-url', 'Legacy edit links must redirect.');
$blockFixture = tempnam(sys_get_temp_dir(), 'blog-block-image-');
$blockSlug = null;
$blockPaths = [];
try {
    file_put_contents($blockFixture, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a6foAAAAASUVORK5CYII='));
    $blockUpload = new CURLFile($blockFixture, 'image/png', 'leaf.png');
    $blockData = ['version' => 1, 'blocks' => [
        ['type' => 'h2', 'html' => 'Một góc <strong>xanh</strong>'],
        ['type' => 'paragraph', 'html' => 'Chăm cây <em>mỗi ngày</em><script>alert(1)</script>'],
        ['type' => 'image', 'src' => 'upload_ab', 'alt' => 'Lá cây', 'caption' => 'Ảnh thứ nhất'],
        ['type' => 'image', 'src' => 'upload_cd', 'alt' => 'Chậu cây', 'caption' => 'Ảnh thứ hai'],
    ]];
    $blockFields = ['csrf_token' => $aliceToken, 'operation' => 'create', 'title' => 'Bài viết theo khối', 'tag' => 'Chăm cây', 'content_blocks' => json_encode($blockData)];
    $response = $aliceBrowser->request('actions/blog-action.php', $blockFields + ['block_images[upload_ab]' => $blockUpload, 'block_images[upload_cd]' => $blockUpload], true);
    $savedBlockPost = db()->query('SELECT * FROM blog_posts ORDER BY id DESC LIMIT 1')->fetch();
    check($response['status'] === 303 && $savedBlockPost['title'] === $blockFields['title'], 'Multi-image block publishing failed.');
    $blockSlug = $savedBlockPost['slug'];
    $savedBlocks = json_decode($savedBlockPost['content_blocks'], true)['blocks'];
    $blockPaths = [$savedBlocks[2]['src'], $savedBlocks[3]['src']];
    check($blockPaths[0] !== $blockPaths[1] && is_file(dirname(__DIR__) . '/' . $blockPaths[0]) && is_file(dirname(__DIR__) . '/' . $blockPaths[1]), 'Images must persist under separate safe filenames.');
    $blockArticle = $reader->request('blog-post.php?slug=' . $blockSlug)['body'];
    check(str_contains($blockArticle, '<strong>xanh</strong>') && str_contains($blockArticle, 'Ảnh thứ hai') && !str_contains($blockArticle, '<script>alert(1)'), 'Published block rendering or sanitization failed.');
    check(str_contains($savedBlockPost['content'], 'mỗi ngày') && !str_contains($savedBlockPost['content'], '<em>'), 'Plain-text search content must stay synchronized.');
    check($blockedLogin->request('blog-editor.php?edit=' . $blockSlug)['status'] === 403, 'Foreign author accessed block editor.');
    $blockFields['operation'] = 'update'; $blockFields['slug'] = $blockSlug;
    $invalidBlocks = ['version' => 1, 'blocks' => [['type' => 'image', 'src' => 'images/blog/' . str_repeat('0', 32) . '.png']]];
    $aliceBrowser->request('actions/blog-action.php', array_merge($blockFields, ['content_blocks' => json_encode($invalidBlocks)]));
    check(is_file(dirname(__DIR__) . '/' . $blockPaths[0]) && db()->query('SELECT content_blocks FROM blog_posts WHERE slug = ' . db()->quote($blockSlug))->fetchColumn() === $savedBlockPost['content_blocks'], 'Invalid update must preserve previous content and files.');
    $savedBlocks = [$savedBlocks[3], $savedBlocks[0], $savedBlocks[1]];
    $aliceBrowser->request('actions/blog-action.php', array_merge($blockFields, ['content_blocks' => json_encode(['version' => 1, 'blocks' => $savedBlocks])]));
    clearstatcache();
    check(!is_file(dirname(__DIR__) . '/' . $blockPaths[0]) && is_file(dirname(__DIR__) . '/' . $blockPaths[1]), 'Removing a block must delete only its image after commit.');
    check(json_decode(db()->query('SELECT content_blocks FROM blog_posts WHERE slug = ' . db()->quote($blockSlug))->fetchColumn(), true)['blocks'][0]['src'] === $blockPaths[1], 'Reordering must persist.');
    $aliceBrowser->request('actions/blog-action.php', ['csrf_token' => $aliceToken, 'operation' => 'delete', 'slug' => $blockSlug]);
    clearstatcache();
    check(!is_file(dirname(__DIR__) . '/' . $blockPaths[1]), 'Deleting a block article must remove remaining images.');
} finally {
    unlink($blockFixture);
    foreach ($blockPaths as $path) { delete_blog_image($path); }
    if ($blockSlug !== null) {
        $statement = db()->prepare('DELETE FROM blog_posts WHERE slug = ?'); $statement->execute([$blockSlug]);
    }
}
