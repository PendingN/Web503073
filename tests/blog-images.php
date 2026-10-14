<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/blog-images.php';
$imageFixture = tempnam(sys_get_temp_dir(), 'blog-image-');
$imageSlug = null;
$imagePaths = [];
$png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a6foAAAAASUVORK5CYII=');
$imageDraft = ['csrf_token' => $aliceToken, 'operation' => 'create', 'title' => 'Bài kiểm tra ảnh', 'tag' => 'Chăm cây', 'content' => 'Nội dung bài kiểm tra tải ảnh.'];
try {
    file_put_contents($imageFixture, $png);
    $upload = new CURLFile($imageFixture, 'image/png', '../../picture.php.png');
    $before = glob(dirname(__DIR__) . '/images/blog/*');
    check($reader->request('actions/blog-action.php', $imageDraft + ['image' => $upload], true)['status'] === 403, 'Upload must require a valid session CSRF token.');
    $aliceBrowser->request('actions/blog-action.php', array_merge($imageDraft, ['title' => '', 'image' => $upload]), true);
    check(glob(dirname(__DIR__) . '/images/blog/*') === $before, 'Invalid content must not store uploaded files.');
    $created = $aliceBrowser->request('actions/blog-action.php', $imageDraft + ['image' => $upload], true);
    $imagePost = db()->query('SELECT * FROM blog_posts ORDER BY id DESC LIMIT 1')->fetch();
    $imageSlug = $imagePost['slug'];
    $imagePaths[] = $imagePost['image_path'];
    check($created['status'] === 303 && preg_match('#^images/blog/[a-f0-9]{32}\.png$#', $imagePost['image_path']) === 1, 'Upload must generate its own safe filename and extension.');
    $imageUrl = $imagePost['image_path'];
    $response = $reader->request($imageUrl);
    check($response['status'] === 200 && $response['body'] === $png && str_contains($response['headers']['content-type'], 'image/png'), 'Uploaded image must be publicly served as an image.');
    foreach (['blog.php', 'blog-post.php?slug=' . $imageSlug, 'blog.php?edit=' . $imageSlug] as $page) {
        check(str_contains($aliceBrowser->request($page)['body'], 'src="' . $imageUrl . '"'), 'Image missing from feed, detail, or editor: ' . $page);
    }
    $imageUpdate = array_merge($imageDraft, ['operation' => 'update', 'slug' => $imageSlug]);
    $otherUpdate = array_merge($imageUpdate, ['csrf_token' => $blockedLogin->token('blog.php'), 'image' => $upload]);
    check($blockedLogin->request('actions/blog-action.php', $otherUpdate, true)['status'] === 403, 'Another author must not replace an image.');
    $aliceBrowser->request('actions/blog-action.php', $imageUpdate);
    $savedImage = db()->prepare('SELECT image_path FROM blog_posts WHERE slug = ?');
    $savedImage->execute([$imageSlug]);
    check($savedImage->fetchColumn() === $imageUrl && is_file(dirname(__DIR__) . '/' . $imageUrl), 'Editing without an upload must preserve the image.');
    foreach (['<?php echo "bad"; ?>', '<svg xmlns="http://www.w3.org/2000/svg"></svg>', $png . str_repeat('x', 2097152)] as $invalid) {
        file_put_contents($imageFixture, $invalid);
        $aliceBrowser->request('actions/blog-action.php', $imageUpdate + ['image' => $upload], true);
        $savedImage->execute([$imageSlug]);
        check($savedImage->fetchColumn() === $imageUrl, 'Invalid or oversized upload replaced the existing image.');
        check(str_contains($aliceBrowser->request('blog.php?edit=' . $imageSlug)['body'], 'vui lòng chọn lại'), 'Invalid upload must show a retry message and retain the draft.');
    }
    file_put_contents($imageFixture, $png);
    $aliceBrowser->request('actions/blog-action.php', $imageUpdate + ['image' => $upload], true);
    $savedImage->execute([$imageSlug]);
    $replacement = $savedImage->fetchColumn();
    $imagePaths[] = $replacement;
    clearstatcache();
    check($replacement !== $imageUrl && !is_file(dirname(__DIR__) . '/' . $imageUrl) && is_file(dirname(__DIR__) . '/' . $replacement), 'Replacing an image must remove the old file after saving.');
    run_cli(['scripts/setup-database.php']);
    $savedImage->execute([$imageSlug]);
    check($savedImage->fetchColumn() === $replacement, 'Repeated setup must preserve uploaded images.');
    $aliceBrowser->request('actions/blog-action.php', ['csrf_token' => $aliceToken, 'operation' => 'delete', 'slug' => $imageSlug]);
    clearstatcache();
    check(!is_file(dirname(__DIR__) . '/' . $replacement) && $reader->request('blog-post.php?slug=' . $imageSlug)['status'] === 404, 'Deleting the post must remove its uploaded image.');
} finally {
    unlink($imageFixture);
    foreach ($imagePaths as $path) {
        delete_blog_image($path);
    }
    if ($imageSlug !== null) {
        $statement = db()->prepare('DELETE FROM blog_posts WHERE slug = ?');
        $statement->execute([$imageSlug]);
    }
}
