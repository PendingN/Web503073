<?php
declare(strict_types=1);

// Runs inside tests/run.php against its disposable database and HTTP sessions.
$reader = new TestBrowser($base);
check((int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn() === 7, 'Seed must include all seven blog posts.');
$tickerPage = $reader->request('blog.php')['body'];
check(substr_count($tickerPage, 'class="feed-ticker-card"') === 10
    && str_contains($tickerPage, 'Bắt đầu một góc xanh từ đâu?'), 'Latest-post ticker must render the newest titles twice for a continuous loop.');
check(str_contains($reader->request('blog.php?page=2')['body'], 'Tạo một nhịp sống chậm cùng cây'), 'Last seeded post is missing.');
check($reader->request('blog-post.php?slug[]=bad')['status'] === 404, 'Array slug must return 404.');
check($reader->request('blog-post.php?slug=missing')['status'] === 404, 'Missing post must return 404.');
check($reader->request('blog.php?view=saved')['status'] === 303, 'Saved posts must require login.');
$readerToken = $reader->token('login.php');
check($reader->request('actions/blog-action.php', ['csrf_token' => $readerToken, 'operation' => 'create'])['status'] === 303, 'Guests must not publish.');
$aliceToken = $aliceBrowser->token('blog.php');
$draft = ['csrf_token' => $aliceToken, 'operation' => 'create', 'title' => 'Góc cây <script>alert(1)</script>', 'content' => "Kinh nghiệm chăm cây xanh.\n<script>alert(2)</script>", 'tag' => 'Chăm cây', 'user_id' => $admin['id']];
check($aliceBrowser->request('actions/blog-action.php', array_merge($draft, ['title' => ' ']))['status'] === 303, 'Invalid post must return to the editor.');
check((int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn() === 7, 'Invalid post was inserted.');
check(str_contains($aliceBrowser->request('blog.php')['body'], 'Kinh nghiệm chăm cây xanh.'), 'Invalid draft content was lost.');
foreach ([['tag' => 'invalid'], ['title' => ['bad']], ['content' => str_repeat('x', 10001)], ['title' => str_repeat('x', 121)]] as $invalidFields) {
    $aliceBrowser->request('actions/blog-action.php', array_merge($draft, $invalidFields));
    check((int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn() === 7, 'Invalid blog input was accepted.');
}
$created = $aliceBrowser->request('actions/blog-action.php', $draft);
$blogPost = db()->query('SELECT * FROM blog_posts ORDER BY id DESC LIMIT 1')->fetch();
$slug = $blogPost['slug'];
$detail = 'blog-post.php?slug=' . rawurlencode($slug);
check($created['status'] === 303 && $created['headers']['location'] === $prefix . '/' . $detail, 'Create must redirect to the saved post.');
check((int) $blogPost['user_id'] === (int) $alice['id'], 'Client must not choose the post author.');
$rendered = $reader->request($detail)['body'];
check(str_contains($rendered, '&lt;script&gt;alert(1)&lt;/script&gt;') && str_contains($rendered, '&lt;script&gt;alert(2)&lt;/script&gt;') && !str_contains($rendered, '<script>alert'), 'Blog content must be escaped.');
check(str_contains($aliceBrowser->request('blog.php?view=mine')['body'], htmlspecialchars($draft['title'], ENT_QUOTES, 'UTF-8')), 'Own-post filter failed.');
check(str_contains($reader->request('blog.php?search=' . rawurlencode('Kinh nghiệm'))['body'], '1 kết quả'), 'Blog text search failed.');
foreach (['%', '_', "' OR 1=1 --"] as $query) {
    check(str_contains($reader->request('blog.php?search=' . rawurlencode($query))['body'], '0 kết quả'), 'Blog search must treat input literally.');
}
check(str_contains($reader->request('dashboard.php')['body'], '<strong>8</strong>'), 'Dashboard blog count must use the database.');
$bobToken = $blockedLogin->token('blog.php');
$bobAction = ['csrf_token' => $bobToken, 'slug' => $slug];
foreach (['update', 'delete'] as $operation) {
    check($blockedLogin->request('actions/blog-action.php', $bobAction + ['operation' => $operation])['status'] === 403, 'Another user changed the post.');
}
check($blockedLogin->request('blog.php?edit=' . $slug)['status'] === 403, 'Another user accessed the edit form.');
$aliceAction = ['csrf_token' => $aliceToken, 'slug' => $slug];
$aliceBrowser->request('actions/blog-action.php', $aliceAction + ['operation' => 'update', 'title' => 'Bài đã sửa', 'content' => 'Nội dung mới được lưu lâu dài.', 'tag' => 'Sống xanh']);
check(str_contains($reader->request($detail)['body'], 'Nội dung mới được lưu lâu dài.'), 'Edited post did not persist.');
foreach ([1, 1, -1, 0, 1] as $vote) {
    $response = $aliceBrowser->request('actions/blog-action.php', $aliceAction + ['operation' => 'vote', 'value' => $vote, 'return_to' => '//example.invalid']);
    check(($response['headers']['location'] ?? '') === $prefix . '/blog.php', 'Blog allowed an external redirect.');
    check((int) db()->query('SELECT SUM(value) FROM blog_votes WHERE post_id = ' . (int) $blogPost['id'])->fetchColumn() === $vote, 'Vote switch/removal or duplicate submission failed.');
}
check($aliceBrowser->request('actions/blog-action.php', $aliceAction + ['operation' => 'vote', 'value' => 2])['status'] === 400, 'Invalid vote accepted.');
check($aliceBrowser->request('actions/blog-action.php', $aliceAction + ['operation' => 'save', 'saved' => 'bad'])['status'] === 400, 'Invalid save state accepted.');
foreach (['1', '1', '0', '1'] as $saved) {
    $aliceBrowser->request('actions/blog-action.php', $aliceAction + ['operation' => 'save', 'saved' => $saved]);
    check((int) db()->query('SELECT COUNT(*) FROM blog_saves WHERE post_id = ' . (int) $blogPost['id'])->fetchColumn() === (int) $saved, 'Save/remove or duplicate submission failed.');
}
check(str_contains($aliceBrowser->request('blog.php?view=saved')['body'], 'Bài đã sửa'), 'Saved-post filter failed.');
check(str_contains($blockedLogin->request('blog.php?view=saved')['body'], '0 kết quả'), 'Saved posts leaked to another user.');
$relogin = new TestBrowser($base);
$relogin->login('alice-new@example.test', 'NewGarden123!');
$savedPage = $relogin->request('blog.php?view=saved')['body'];
check(str_contains($savedPage, '♥ Đã lưu') && str_contains($savedPage, 'aria-pressed="true"'), 'Votes and saves must survive a new login.');
$popularPage = $reader->request('blog.php?sort=popular')['body'];
check(strpos($popularPage, 'Bài đã sửa') < strpos($popularPage, 'Bắt đầu một góc xanh'), 'Popular ordering failed.');
$comment = '<script>comment</script> Chăm cây vui quá!';
$aliceBrowser->request('actions/blog-action.php', $aliceAction + ['operation' => 'comment', 'content' => $comment]);
$commentId = (int) db()->query('SELECT MAX(id) FROM blog_comments')->fetchColumn();
check(str_contains($reader->request($detail)['body'], '&lt;script&gt;comment&lt;/script&gt;'), 'Comment rendering is unsafe or missing.');
check($blockedLogin->request('actions/blog-action.php', $bobAction + ['operation' => 'delete-comment', 'comment_id' => $commentId])['status'] === 403, 'Another user deleted the comment.');
foreach ([' ', str_repeat('a', 2001)] as $content) {
    $aliceBrowser->request('actions/blog-action.php', $aliceAction + ['operation' => 'comment', 'content' => $content]);
    check((int) db()->query('SELECT COUNT(*) FROM blog_comments')->fetchColumn() === 1, 'Invalid comment was stored.');
}
$aliceBrowser->request('actions/blog-action.php', $aliceAction + ['operation' => 'delete-comment', 'comment_id' => $commentId]);
check((int) db()->query('SELECT COUNT(*) FROM blog_comments')->fetchColumn() === 0, 'Author could not delete own comment.');
$fixture = db()->prepare('INSERT INTO blog_comments (user_id, post_id, content) VALUES (?, ?, ?)');
for ($i = 0; $i < 21; $i++) {
    $fixture->execute([$alice['id'], $blogPost['id'], 'Comment fixture ' . $i]);
}
check(str_contains($reader->request($detail . '&page=9999')['body'], 'Trang 2 / 2'), 'Comment pagination failed.');
$adminAction = ['csrf_token' => $adminToken, 'slug' => $slug];
$commentId = (int) db()->query('SELECT MAX(id) FROM blog_comments')->fetchColumn();
$adminBrowser->request('actions/blog-action.php', $adminAction + ['operation' => 'delete-comment', 'comment_id' => $commentId]);
check((int) db()->query('SELECT COUNT(*) FROM blog_comments')->fetchColumn() === 20, 'Admin could not moderate comments.');
$adminBrowser->request('actions/blog-action.php', $adminAction + ['operation' => 'update', 'title' => 'Bài quản trị sửa', 'content' => 'Nội dung được quản trị viên sửa.', 'tag' => 'Chăm cây']);
check(str_contains($reader->request($detail)['body'], 'Bài quản trị sửa'), 'Admin could not edit posts.');
run_cli(['scripts/setup-database.php']);
check((int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn() === 8 && str_contains($reader->request($detail)['body'], 'Bài quản trị sửa'), 'Setup overwrote blog data.');
$adminBrowser->request('actions/user-action.php', ['csrf_token' => $adminToken, 'user_id' => $bob['id'], 'operation' => 'block']);
check($blockedLogin->request('actions/blog-action.php', $bobAction + ['operation' => 'comment', 'content' => 'Blocked comment'])['status'] === 303, 'Blocked session could write to blog.');
$adminBrowser->request('actions/user-action.php', ['csrf_token' => $adminToken, 'user_id' => $bob['id'], 'operation' => 'unblock']);
$adminBrowser->request('actions/blog-action.php', $adminAction + ['operation' => 'delete']);
check($reader->request($detail)['status'] === 404, 'Deleted post still appears.');
foreach (['blog_comments', 'blog_votes', 'blog_saves'] as $table) {
    check((int) db()->query('SELECT COUNT(*) FROM ' . $table)->fetchColumn() === 0, 'Post deletion left orphan rows in ' . $table);
}
check($aliceBrowser->request('actions/blog-action.php', $aliceAction + ['operation' => 'vote', 'value' => 1])['status'] === 404, 'Deleted post accepts interactions.');
$aliceBrowser->request('actions/blog-action.php', $draft);
$ownSlug = db()->query('SELECT slug FROM blog_posts ORDER BY id DESC LIMIT 1')->fetchColumn();
$deleted = $aliceBrowser->request('actions/blog-action.php', ['csrf_token' => $aliceToken, 'slug' => $ownSlug, 'operation' => 'delete']);
check($deleted['status'] === 303 && $reader->request('blog-post.php?slug=' . $ownSlug)['status'] === 404, 'Author cannot delete own post.');
