<?php
declare(strict_types=1);

// Runs inside tests/run.php against its disposable database and HTTP sessions.
$reader = new TestBrowser($base);
check((int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn() === 7, 'Seed must include all seven blog posts.');
$feedPage = $reader->request('blog.php')['body'];
check(str_contains($feedPage, 'class="mono-masthead"')
    && str_contains($feedPage, 'Sổ Tay Thực Vật')
    && str_contains($feedPage, 'mono-opening-spread')
    && !str_contains($feedPage, 'THE BLOG')
    && !str_contains($feedPage, 'feed-ticker-card')
    && !str_contains($feedPage, 'feed-post-actions')
    && !str_contains($feedPage, 'data-reaction'), 'Blog feed must render the Vietnamese Botanical Monograph masthead and clean cards without ticker or reaction bars.');
check(substr_count($feedPage, 'class="post-card feed-post') === 10
    && str_contains($feedPage, 'Bắt đầu một góc xanh từ đâu?')
    && !str_contains($feedPage, 'Tạo một nhịp sống chậm cùng cây'), 'Front page must show the four-post teaser plus the first six posts of the list.');
check(str_contains($feedPage, 'class="pagination"')
    && str_contains($feedPage, 'Trang 1 / 2')
    && str_contains($feedPage, 'href="blog.php?search=&amp;sort=new&amp;view=all&amp;page=2#blog-content"')
    && !str_contains($feedPage, 'mono-spotlight'), 'Seven posts at six per page must render numbered pagination.');
$secondPage = $reader->request('blog.php?page=2')['body'];
check(substr_count($secondPage, 'class="post-card feed-post') === 1
    && str_contains($secondPage, 'Tạo một nhịp sống chậm cùng cây')
    && str_contains($secondPage, 'Trang 2 / 2')
    && !str_contains($secondPage, 'mono-opening-spread'), 'Page two must list the remaining post without the teaser.');
check(substr_count($reader->request('blog.php?page=99')['body'], 'class="post-card feed-post') === 1, 'A page past the end must clamp to the last page.');
$listPage = $reader->request('blog.php?layout=list')['body'];
check(str_contains($listPage, 'all-grid--list')
    && !str_contains($feedPage, 'all-grid--list')
    && substr_count($listPage, 'class="post-card feed-post') === 10
    && str_contains($listPage, 'layout=list&amp;page=2#blog-content'), 'The list layout must page like the grid and keep its layout on page links.');
check(str_contains($feedPage, 'blog-layout-toggle')
    && str_contains($feedPage, 'aria-label="Lưới"') && str_contains($feedPage, 'aria-label="Danh sách"')
    && str_contains($listPage, 'href="blog.php?search=&amp;sort=new&amp;view=all&amp;layout=list#blog-content" aria-current="true"')
    && str_contains($feedPage, 'href="blog.php?search=&amp;sort=new&amp;view=all#blog-content" aria-current="true"'), 'The layout switch must mark the active layout.');
check(!str_contains($reader->request('blog.php?layout=bogus')['body'], 'all-grid--list'), 'An unknown layout must fall back to the grid.');
$listSearch = $reader->request('blog.php?layout=list&search=' . rawurlencode('Chăm cây'))['body'];
check(str_contains($listSearch, 'name="layout" value="list"')
    && str_contains($listSearch, 'search=' . urlencode('Không gian') . '&amp;sort=new&amp;view=all&amp;layout=list#blog-content')
    && str_contains($listPage, 'search=' . urlencode('Chăm cây') . '&amp;layout=list#blog-content'), 'Search, topic and card tag links must keep the list layout.');
$searchPage = $reader->request('blog.php?search=' . rawurlencode('Tạo một nhịp sống chậm cùng cây'))['body'];
check(substr_count($searchPage, 'class="post-card feed-post') === 1, 'Feed must follow the current search.');
check(str_contains($feedPage, 'id="blog-search-list"')
    && str_contains($feedPage, 'role="combobox"')
    && str_contains($feedPage, 'blog-post.php?slug=bat-dau-mot-goc-xanh-tu-dau&quot;'), 'The blog search box must carry as-you-type suggestions.');
$accentless = $reader->request('blog.php?search=' . rawurlencode('bat dau mot goc'))['body'];
check(substr_count($accentless, 'class="post-card feed-post') === 1
    && str_contains($accentless, 'Bắt đầu một góc xanh từ đâu?'), 'Searching without accents, including "đ" as "d", must find the post.');
$emptySearchPage = $reader->request('blog.php?search=no-matching-post-98347')['body'];
check(substr_count($emptySearchPage, 'class="post-card feed-post') === 0
    && !str_contains($emptySearchPage, 'class="recent-grid"'), 'Empty searches must not show unrelated articles.');
check(str_contains($secondPage, 'Tạo một nhịp sống chậm cùng cây'), 'Last seeded post is missing.');
check($reader->request('blog-post.php?slug[]=bad')['status'] === 404, 'Array slug must return 404.');
check($reader->request('blog-post.php?slug=missing')['status'] === 404, 'Missing post must return 404.');
check($reader->request('blog.php?view=saved')['status'] === 303, 'Saved posts must require login.');
$readerToken = $reader->token('login.php');
check($reader->request('actions/blog-action.php', ['csrf_token' => $readerToken, 'operation' => 'create'])['status'] === 303, 'Guests must not publish.');
$aliceToken = $aliceBrowser->token('blog-editor.php');
require __DIR__ . '/blog-images.php';
require __DIR__ . '/blog-editor.php';
$draft = ['csrf_token' => $aliceToken, 'operation' => 'create', 'title' => 'Góc cây <script>alert(1)</script>', 'content' => "Kinh nghiệm chăm cây xanh.\n<script>alert(2)</script>", 'tag' => 'Chăm cây', 'user_id' => $admin['id']];
check($aliceBrowser->request('actions/blog-action.php', array_merge($draft, ['title' => ' ']))['status'] === 303, 'Invalid post must return to the editor.');
check((int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn() === 7, 'Invalid post was inserted.');
check(str_contains($aliceBrowser->request('blog-editor.php')['body'], 'Kinh nghiệm chăm cây xanh.'), 'Invalid draft content was lost.');
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
$bobToken = $blockedLogin->token('blog-editor.php');
$bobAction = ['csrf_token' => $bobToken, 'slug' => $slug];
foreach (['update', 'delete'] as $operation) {
    check($blockedLogin->request('actions/blog-action.php', $bobAction + ['operation' => $operation])['status'] === 403, 'Another user changed the post.');
}
check($blockedLogin->request('blog-editor.php?edit=' . $slug)['status'] === 403, 'Another user accessed the edit form.');
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
foreach ([0, -1, 1] as $vote) {
    $response = $aliceBrowser->request('actions/blog-action.php', $aliceAction + ['operation' => 'vote', 'value' => $vote], false, true);
    $state = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
    check($response['status'] === 200 && !isset($response['headers']['location']) && $state['vote'] === $vote
        && $state['likes'] === max(0, $vote) && $state['dislikes'] === max(0, -$vote) && $state['saved'] === true,
        'Async vote must return separate non-negative like/dislike counts, user vote and save state.');
}
$blockedLogin->request('actions/blog-action.php', $bobAction + ['operation' => 'vote', 'value' => -1]);
$storedVotes = array_count_values(db()->query('SELECT value FROM blog_votes WHERE post_id = ' . (int) $blogPost['id'])->fetchAll(PDO::FETCH_COLUMN));
check(($storedVotes[1] ?? 0) === 1 && ($storedVotes[-1] ?? 0) === 1, 'One user disliking must not subtract another user like.');
$mixedState = $aliceBrowser->request('actions/blog-action.php', $aliceAction + ['operation' => 'vote', 'value' => 1], false, true);
$mixedCounts = json_decode($mixedState['body'], true, 512, JSON_THROW_ON_ERROR);
check($mixedCounts['likes'] === 1 && $mixedCounts['dislikes'] === 1, 'Async mixed reactions must keep separate counts.');
$blockedLogin->request('actions/blog-action.php', $bobAction + ['operation' => 'vote', 'value' => 0]);
foreach (['0', '1', '1'] as $saved) {
    $response = $aliceBrowser->request('actions/blog-action.php', $aliceAction + ['operation' => 'save', 'saved' => $saved], false, true);
    $state = json_decode($response['body'], true, 512, JSON_THROW_ON_ERROR);
    check($response['status'] === 200 && $state['saved'] === ($saved === '1') && $state['vote'] === 1, 'Async save must persist without resetting votes.');
}
check($aliceBrowser->request('actions/blog-action.php', ['csrf_token' => 'bad', 'slug' => $slug, 'operation' => 'vote', 'value' => 1], false, true)['status'] === 403,
    'Async vote must require CSRF.');
$savedFeed = $aliceBrowser->request('blog.php?view=saved')['body'];
check(str_contains($savedFeed, 'Bài đã sửa'), 'Saved-post filter failed.');
check(substr_count($savedFeed, 'class="post-card feed-post') === 1
    && !str_contains($savedFeed, 'data-reaction'), 'Saved-post feed must render clean cards matching the filter.');
check(str_contains($blockedLogin->request('blog.php?view=saved')['body'], '0 kết quả'), 'Saved posts leaked to another user.');
$relogin = new TestBrowser($base);
$relogin->login('alice-new@example.test', 'NewGarden123!');
check(str_contains($relogin->request('blog.php?view=saved')['body'], 'Bài đã sửa'), 'Saved list must survive a new login.');
$savedDetail = $relogin->request($detail)['body'];
check(str_contains($savedDetail, '>Đã lưu</span>') && str_contains($savedDetail, 'aria-pressed="true"'), 'Votes and saves must survive a new login on the post detail page.');
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
