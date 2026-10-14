<?php
declare(strict_types=1);

// Uses only the disposable database created by tests/run.php.
$managementGuest = new TestBrowser($base);
foreach (['admin-posts.php', 'admin-history.php'] as $path) {
    check($managementGuest->request($path)['status'] === 303, 'Guests must not access ' . $path);
    check($aliceBrowser->request($path)['status'] === 403, 'Ordinary users must not access ' . $path);
    check($adminBrowser->request($path)['status'] === 200, 'Admins must access ' . $path);
}
$adminToken = $adminBrowser->token('admin-posts.php?create=1');
$postDraft = ['csrf_token' => $adminToken, 'operation' => 'create', 'title' => 'Admin <script>alert(7)</script>',
    'content' => 'Nội dung bài viết thử nghiệm quản trị.', 'tag' => 'Chăm cây', 'return_to' => $prefix . '/admin-posts.php'];
$beforeCount = (int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
$response = $adminBrowser->request('actions/blog-action.php', array_merge($postDraft, ['title' => ' ']));
check($response['status'] === 303 && $response['headers']['location'] === $prefix . '/admin-posts.php?create=1', 'Invalid admin draft must return to the admin editor.');
check((int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn() === $beforeCount, 'Invalid admin draft must not create a post.');
check(str_contains($adminBrowser->request('admin-posts.php?create=1')['body'], $postDraft['content']), 'Invalid admin draft must preserve its content.');
$response = $adminBrowser->request('actions/blog-action.php', $postDraft);
$adminPost = db()->query('SELECT * FROM blog_posts ORDER BY id DESC LIMIT 1')->fetch();
check($response['status'] === 303 && $response['headers']['location'] === $prefix . '/admin-posts.php?edit=' . $adminPost['slug'] . '#post-editor', 'Admin create must stay in the admin editor.');
check(str_contains($adminBrowser->request('admin-posts.php')['body'], '&lt;script&gt;alert(7)&lt;/script&gt;'), 'Admin post listing must escape titles.');
check(str_contains($adminBrowser->request('admin-posts.php?search=%25')['body'], 'Không tìm thấy bài viết phù hợp.'), 'Admin search must treat SQL wildcards literally.');
check($adminBrowser->request('admin-posts.php?edit=missing-post')['status'] === 404, 'Unknown admin post must return 404.');
$memberDraft = array_merge($postDraft, ['csrf_token' => $aliceBrowser->token('blog.php'), 'title' => 'Bài của thành viên', 'return_to' => $prefix . '/blog.php']);
$aliceBrowser->request('actions/blog-action.php', $memberDraft);
$memberPost = db()->query('SELECT * FROM blog_posts ORDER BY id DESC LIMIT 1')->fetch();
$adminUpdate = array_merge($postDraft, ['operation' => 'update', 'slug' => $memberPost['slug'], 'title' => 'Quản trị đã sửa bài thành viên']);
check($adminBrowser->request('actions/blog-action.php', $adminUpdate)['status'] === 303, 'Admin must edit another user post.');
$statement = db()->prepare('SELECT title, user_id FROM blog_posts WHERE id = ?');
$statement->execute([$memberPost['id']]);
$changedPost = $statement->fetch();
check($changedPost['title'] === $adminUpdate['title'] && (int) $changedPost['user_id'] === (int) $alice['id'], 'Admin edit must preserve the original author.');
$beforeEvents = (int) db()->query("SELECT COUNT(*) FROM user_activity WHERE event = 'blog.deleted'")->fetchColumn();
$delete = ['csrf_token' => $adminToken, 'operation' => 'delete', 'slug' => $memberPost['slug'], 'return_to' => $prefix . '/admin-posts.php'];
check($adminBrowser->request('actions/blog-action.php', array_diff_key($delete, ['csrf_token' => true]))['status'] === 403, 'Admin post delete must enforce CSRF.');
check((int) db()->query("SELECT COUNT(*) FROM user_activity WHERE event = 'blog.deleted'")->fetchColumn() === $beforeEvents, 'Rejected mutations must not write successful history events.');
$response = $adminBrowser->request('actions/blog-action.php', $delete);
check($response['headers']['location'] === $prefix . '/admin-posts.php', 'Admin delete must return to post management.');
check((int) db()->query("SELECT COUNT(*) FROM user_activity WHERE event = 'blog.deleted'")->fetchColumn() === $beforeEvents + 1, 'Deleted posts must retain an activity record.');
$delete['slug'] = $adminPost['slug'];
$adminBrowser->request('actions/blog-action.php', $delete);

$marker = 'history-fixture-' . bin2hex(random_bytes(5));
$snapshot = ['id' => $admin['id'], 'name' => '<script>admin</script>'];
record_activity($snapshot, 'plant.saved', $marker . ' <script>now</script>', ['id' => $bob['id'], 'name' => 'Bob Edited']);
$historyId = (int) db()->lastInsertId();
record_activity($snapshot, 'plant.saved', $marker . ' old', ['id' => $bob['id'], 'name' => 'Bob Edited']);
$oldHistoryId = (int) db()->lastInsertId();
$statement = db()->prepare('UPDATE user_activity SET created_at = DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 4 DAY) WHERE id = ?');
$statement->execute([$oldHistoryId]);
check(list_user_activity('3days', (int) $bob['id'], $marker, 'plant.saved', 1)['total'] === 1, 'History must default to the last 72 hours and include affected accounts.');
check(list_user_activity('all', (int) $bob['id'], $marker, 'plant.saved', 1)['total'] === 2, 'All history must include older stored records.');
check(list_user_activity('all', (int) $alice['id'], $marker, 'plant.saved', 1)['total'] === 0, 'Account history must not include unrelated actors or subjects.');
$body = $adminBrowser->request('admin-history.php?search=' . rawurlencode($marker))['body'];
check(str_contains($body, '&lt;script&gt;admin&lt;/script&gt;') && !str_contains($body, '<script>admin</script>'), 'History must escape actor snapshots.');
check(str_contains($body, '&lt;script&gt;now&lt;/script&gt;') && !str_contains($body, $marker . ' old'), 'History must escape descriptions and filter older events.');
check(str_contains($adminBrowser->request('admin-history.php?period=all&search=' . rawurlencode($marker))['body'], $marker . ' old'), 'All-period UI must include older records.');
check(str_contains($adminBrowser->request('admin-history.php?search=%25')['body'], 'Chưa có hoạt động phù hợp'), 'History LIKE wildcards must be literal.');
check($adminBrowser->request('admin-history.php?user_id[]=1')['status'] === 400, 'Invalid history account IDs must be rejected.');
for ($index = 0; $index < 26; $index++) {
    record_activity($snapshot, 'plant.saved', $marker . ' paging ' . $index);
}
check(str_contains($adminBrowser->request('admin-history.php?search=' . rawurlencode($marker) . '&event=plant.saved&page=999')['body'], 'Trang 2 / 2'), 'History pagination must clamp out-of-range pages.');
$historyJson = json_encode(db()->query('SELECT event, description FROM user_activity')->fetchAll());
check(!str_contains($historyJson, 'TestAdmin123!') && !str_contains($historyJson, 'Garden123!'), 'Activity records must not contain passwords.');
foreach (['auth.login', 'auth.logout', 'profile.updated', 'user.updated', 'user.blocked', 'user.unblocked', 'blog.created', 'blog.updated', 'blog.deleted', 'plant.saved', 'page.view'] as $event) {
    $statement = db()->prepare('SELECT COUNT(*) FROM user_activity WHERE event = ?');
    $statement->execute([$event]);
    check((int) $statement->fetchColumn() > 0, 'Missing successful activity category: ' . $event);
}
