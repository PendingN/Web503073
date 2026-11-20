<?php
declare(strict_types=1);

// Runs inside tests/run.php after tests/blog.php: managing tags (admin), giving posts none / one / many
// tags, filtering by tag, and the one-off upgrade from the old blog_posts.tag column.
$guestBrowser = new TestBrowser($base);
$tagCount = static fn (): int => (int) db()->query('SELECT COUNT(*) FROM tags')->fetchColumn();
$postCount = static fn (): int => (int) db()->query('SELECT COUNT(*) FROM blog_posts')->fetchColumn();
$linkNames = static function (int $postId): array {
    $statement = db()->prepare('SELECT t.name FROM post_tags pt JOIN tags t ON t.id = pt.tag_id WHERE pt.post_id = ? ORDER BY t.id');
    $statement->execute([$postId]);
    return $statement->fetchAll(PDO::FETCH_COLUMN);
};
// Titles also sit in the search-suggestion JSON on every page, so look for the card link itself.
$listed = static fn (string $body, string $slug): bool => str_contains($body, '<h3><a href="blog-post.php?slug=' . rawurlencode($slug) . '"');
$tagToken = $adminBrowser->token('admin-tags.php');
$tagAction = static fn (array $data, ?TestBrowser $browser = null, ?string $token = null): array => ($browser ?? $GLOBALS['adminBrowser'])
    ->request('actions/tag-action.php', ['csrf_token' => $token ?? $GLOBALS['tagToken']] + $data);

// Criterion 20: view, add, rename, delete — administrators only.
check($guestBrowser->request('admin-tags.php')['status'] === 303, 'Guests must log in before managing tags.');
check($aliceBrowser->request('admin-tags.php')['status'] === 403, 'Members must not open tag management.');
$memberToken = $aliceBrowser->token('blog-editor.php');
$before = $tagCount();
check($tagAction(['operation' => 'create', 'name' => 'Của thành viên'], $aliceBrowser, $memberToken)['status'] === 403 && $tagCount() === $before, 'Members must not create tags.');
check($tagAction(['operation' => 'create', 'name' => 'Thiếu CSRF'], $adminBrowser, 'bad-token')['status'] === 403 && $tagCount() === $before, 'Tag changes must require a CSRF token.');
$tagPage = $adminBrowser->request('admin-tags.php');
check($tagPage['status'] === 200 && str_contains($tagPage['body'], 'Chăm cây') && str_contains($tagPage['body'], 'Danh sách chủ đề') && str_contains($tagPage['body'], 'data-confirm-tone="danger"'), 'Admin tag page must list the tags with a confirmed delete.');

check($tagAction(['operation' => 'create', 'name' => '  Cây   để bàn '])['status'] === 303 && $tagCount() === $before + 1, 'Admin must be able to add a tag.');
check(db()->query("SELECT name FROM tags ORDER BY id DESC LIMIT 1")->fetchColumn() === 'Cây để bàn', 'Tag names must be trimmed and have inner spaces collapsed.');
check(str_contains($adminBrowser->request('admin-tags.php')['body'], 'Đã thêm chủ đề'), 'Adding a tag must confirm.');
foreach (['cây để bàn', 'CÂY ĐỂ BÀN', 'cay de ban', '', str_repeat('x', TAG_NAME_MAX_LENGTH + 1)] as $badName) {
    $tagAction(['operation' => 'create', 'name' => $badName]);
    check($tagCount() === $before + 1, 'Duplicate (any case or accent) or invalid tag names must be rejected: ' . $badName);
}
check(str_contains($adminBrowser->request('admin-tags.php')['body'], 'class="login-error"'), 'A rejected tag must explain why.');
$deskId = (int) db()->query("SELECT id FROM tags WHERE name = 'Cây để bàn'")->fetchColumn();
$tipsId = test_tag_id('Mẹo hay');
$careId = test_tag_id('Chăm cây');

// Criterion 21: a post can have no tag, one tag or several.
$makePost = static function (string $title, string $content, ?array $tags) use ($aliceBrowser, $memberToken): array {
    $data = ['csrf_token' => $memberToken, 'operation' => 'create', 'title' => $title, 'content' => $content];
    if ($tags !== null) {
        $data['tags'] = $tags;
    }
    check($aliceBrowser->request('actions/blog-action.php', $data)['status'] === 303, 'Post must be accepted: ' . $title);
    $post = db()->query('SELECT id, slug FROM blog_posts ORDER BY id DESC LIMIT 1')->fetch();
    return ['id' => (int) $post['id'], 'slug' => $post['slug']];
};
$untagged = $makePost('Bài không nhãn', 'Bài viết này chưa thuộc chủ đề nào cả.', null);
$tagged = $makePost('Bài hai nhãn', 'Bài viết này có hai chủ đề khác nhau.', [$deskId, $tipsId]);
$textOnly = $makePost('Bài chỉ nhắc chữ', 'Bài này nói nhiều về chăm cây nhưng chỉ thuộc mẹo hay.', [$tipsId]);
check($linkNames($untagged['id']) === [], 'A post may have no tag.');
check($linkNames($tagged['id']) === ['Mẹo hay', 'Cây để bàn'] || $linkNames($tagged['id']) === ['Cây để bàn', 'Mẹo hay'], 'A post may have several tags.');
check($linkNames($textOnly['id']) === ['Mẹo hay'], 'A post may have one tag.');
$detail = static fn (array $post): array => $GLOBALS['guestBrowser']->request('blog-post.php?slug=' . rawurlencode($post['slug']));
$untaggedPage = $detail($untagged);
$taggedPage = $detail($tagged);
check($untaggedPage['status'] === 200
    && preg_match('/<div class="mono-article-ledger post-meta">(.*?)<\/div>/s', $untaggedPage['body'], $ledger) === 1
    && !str_contains($ledger[1], 'class="tag"') && str_contains($ledger[1], '<time'), 'An untagged post must render without a tag pill.');
check(str_contains($taggedPage['body'], 'Cây để bàn') && str_contains($taggedPage['body'], 'Mẹo hay') && str_contains($taggedPage['body'], blog_tag_url($deskId)), 'A post page must show every tag, each linking to the filtered list.');
foreach ([['tags' => ['abc']], ['tags' => 'x'], ['tags' => [999999]], ['tags' => range(1, POST_TAG_LIMIT + 1)]] as $badTags) {
    $count = $postCount();
    $aliceBrowser->request('actions/blog-action.php', ['csrf_token' => $memberToken, 'operation' => 'create', 'title' => 'Nhãn sai', 'content' => 'Nội dung đủ dài để hợp lệ.'] + $badTags);
    check($postCount() === $count, 'Invalid tag input must not create a post.');
}
$edit = static fn (array $post, array $tags): int => $GLOBALS['aliceBrowser']->request('actions/blog-action.php', [
    'csrf_token' => $GLOBALS['memberToken'], 'operation' => 'update', 'slug' => $post['slug'], 'title' => 'Bài hai nhãn',
    'content' => 'Bài viết này có hai chủ đề khác nhau.'] + ($tags === [] ? [] : ['tags' => $tags]))['status'];
check($edit($tagged, []) === 303 && $linkNames($tagged['id']) === [] && $detail($tagged)['status'] === 200, 'Clearing every tag must keep the post.');
check($edit($tagged, [$deskId, $tipsId]) === 303 && count($linkNames($tagged['id'])) === 2, 'Tags can be added back to a post.');

// Criterion 22: filter by the selected tags (exact match, several at once).
$filterQuery = static fn (array $ids): string => 'blog.php?' . http_build_query(['tag' => $ids]);
$byDesk = $guestBrowser->request($filterQuery([$deskId]))['body'];
check($listed($byDesk, $tagged['slug']) && !$listed($byDesk, $untagged['slug']) && !$listed($byDesk, $textOnly['slug']), 'Filtering by a tag must list only posts that carry it.');
$byCare = $guestBrowser->request($filterQuery([$careId]))['body'];
check(!$listed($byCare, $textOnly['slug']), 'Tag filter must not match posts that only mention the words in their text.');
check($listed($guestBrowser->request('blog.php?search=' . rawurlencode('chăm cây'))['body'], $textOnly['slug']), 'Text search must still look through post text.');
$byEither = $guestBrowser->request($filterQuery([$deskId, $tipsId]))['body'];
check($listed($byEither, $tagged['slug']) && $listed($byEither, $textOnly['slug']) && !$listed($byEither, $untagged['slug']), 'Selecting several tags must list posts carrying any of them.');
// A selected topic is marked and its link drops just that tag, so a second click turns it off.
check(str_contains($byEither, 'tag%5B0%5D=' . $tipsId . '#blog-content" aria-current="true">Cây để bàn</a>')
    && str_contains($byEither, 'tag%5B0%5D=' . $deskId . '#blog-content" aria-current="true">Mẹo hay</a>'), 'Selected topics must be marked and toggle off on a second click.');
check($guestBrowser->request('blog.php?tag[]=abc&tag[]=999999')['status'] === 200, 'Unknown tag filters must be ignored, not break the page.');
$byDeskList = $guestBrowser->request($filterQuery([$deskId]) . '&layout=list')['body'];
check(str_contains($byDeskList, 'all-grid--list') && str_contains($byDeskList, 'layout=list&amp;tag%5B0%5D='), 'Tag filters must work with, and keep, the list layout.');
$adminByTag = $adminBrowser->request('admin-posts.php?tag=' . $deskId)['body'];
check(str_contains($adminByTag, 'Bài hai nhãn') && !str_contains($adminByTag, 'Bài không nhãn'), 'The admin post list must filter by tag id.');

// Renaming updates every post at once; the links stay the same rows.
$linksBefore = db()->query('SELECT COUNT(*) FROM post_tags')->fetchColumn();
check($tagAction(['operation' => 'rename', 'tag_id' => $deskId, 'name' => 'Cây văn phòng'])['status'] === 303, 'Admin must be able to rename a tag.');
check(db()->query('SELECT COUNT(*) FROM post_tags')->fetchColumn() === $linksBefore, 'Renaming must not touch the links.');
$taggedPage = $detail($tagged);
check(str_contains($taggedPage['body'], 'Cây văn phòng') && !str_contains($taggedPage['body'], 'Cây để bàn'), 'Every post must show the new tag name after a rename.');
check(str_contains($guestBrowser->request($filterQuery([$deskId]))['body'], 'Cây văn phòng'), 'The topic list must show the new name.');
$tagAction(['operation' => 'rename', 'tag_id' => $deskId, 'name' => 'mẹo HAY']);
check(db()->query('SELECT name FROM tags WHERE id = ' . $deskId)->fetchColumn() === 'Cây văn phòng', 'A tag cannot be renamed to an existing name.');
check($tagAction(['operation' => 'rename', 'tag_id' => 999999, 'name' => 'Không có'])['status'] === 404, 'Renaming a missing tag must return 404.');

// Deleting removes the tag and its links only; the posts stay.
$posts = $postCount();
check($tagAction(['operation' => 'delete', 'tag_id' => $deskId], $aliceBrowser, $memberToken)['status'] === 403 && find_tag($deskId) !== null, 'Members must not delete tags.');
check($tagAction(['operation' => 'delete', 'tag_id' => $deskId])['status'] === 303, 'Admin must be able to delete a tag.');
check(find_tag($deskId) === null && $postCount() === $posts, 'Deleting a tag must not delete posts.');
check($linkNames($tagged['id']) === ['Mẹo hay'] && $detail($tagged)['status'] === 200, 'A deleted tag must only disappear from the posts that had it.');
check((int) db()->query('SELECT COUNT(*) FROM post_tags WHERE tag_id = ' . $deskId)->fetchColumn() === 0, 'Deleting a tag must remove its links.');
check($tagAction(['operation' => 'delete', 'tag_id' => $deskId])['status'] === 404, 'Deleting a missing tag must return 404.');

// Setup is safe to repeat: it must neither bring back a deleted tag nor re-tag a post an admin untagged.
$seedSlug = 'bat-dau-mot-goc-xanh-tu-dau';
$seedId = (int) db()->query("SELECT id FROM blog_posts WHERE slug = '" . $seedSlug . "'")->fetchColumn();
$seedLinks = db()->query('SELECT tag_id FROM post_tags WHERE post_id = ' . $seedId)->fetchAll(PDO::FETCH_COLUMN);
db()->exec('DELETE FROM post_tags WHERE post_id = ' . $seedId);
run_cli(['scripts/setup-database.php']);
check(find_tag($deskId) === null && !in_array('Cây văn phòng', array_column(list_tags(true), 'name'), true), 'Setup must not recreate a deleted tag.');
check($linkNames($seedId) === [], 'Setup must not re-tag a post whose tags an administrator removed.');
foreach ($seedLinks as $seedTagId) {
    db()->exec('INSERT INTO post_tags (post_id, tag_id) VALUES (' . $seedId . ', ' . (int) $seedTagId . ')');
}

// Upgrade path: a database that still has blog_posts.tag keeps every post and its topic.
$legacyName = $testName . '_legacy';
$server->exec('CREATE DATABASE `' . $legacyName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    $server->exec('CREATE TABLE `' . $legacyName . '`.blog_posts (id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, user_id INT UNSIGNED NULL,'
        . ' slug VARCHAR(160) NOT NULL UNIQUE, title VARCHAR(120) NOT NULL, tag VARCHAR(50) NOT NULL, excerpt VARCHAR(300) NOT NULL,'
        . ' content TEXT NOT NULL, image_path VARCHAR(255) NULL, is_hidden TINYINT(1) NOT NULL DEFAULT 0, illustration VARCHAR(20) NOT NULL DEFAULT \'sprout\','
        . ' tone VARCHAR(20) NOT NULL DEFAULT \'mint\', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
        . ' updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    $legacyInsert = $server->prepare('INSERT INTO `' . $legacyName . '`.blog_posts (slug, title, tag, excerpt, content) VALUES (?, ?, ?, ?, ?)');
    foreach ([['legacy-a', 'Chăm cây'], ['legacy-b', 'Mẹo hay'], ['legacy-c', 'Chủ đề tự đặt']] as [$legacySlug, $legacyTag]) {
        $legacyInsert->execute([$legacySlug, 'Bài cũ ' . $legacySlug, $legacyTag, 'Tóm tắt bài cũ.', 'Nội dung bài cũ đủ dài.']);
    }
    putenv('DB_NAME=' . $legacyName);
    run_cli(['scripts/setup-database.php']);
    $legacyLinks = static function () use ($server, $legacyName): array {
        $rows = $server->query('SELECT p.slug, t.name FROM `' . $legacyName . '`.post_tags pt JOIN `' . $legacyName . '`.blog_posts p ON p.id = pt.post_id'
            . ' JOIN `' . $legacyName . '`.tags t ON t.id = pt.tag_id WHERE p.slug LIKE \'legacy-%\' ORDER BY p.slug')->fetchAll(PDO::FETCH_NUM);
        return array_map(static fn (array $row): string => $row[0] . '=' . $row[1], $rows);
    };
    check($legacyLinks() === ['legacy-a=Chăm cây', 'legacy-b=Mẹo hay', 'legacy-c=Chủ đề tự đặt'], 'The upgrade must turn each post\'s old tag into a linked tag.');
    check(!$server->query('SHOW COLUMNS FROM `' . $legacyName . '`.blog_posts LIKE \'tag\'')->fetch(), 'The upgrade must drop the old tag column.');
    $legacyTags = $server->query('SELECT name FROM `' . $legacyName . '`.tags ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
    check(array_slice($legacyTags, 0, 6) === ['Chăm cây', 'Không gian', 'Cảm hứng', 'Mẹo hay', 'Sống xanh', 'Bài nổi bật'] && in_array('Chủ đề tự đặt', $legacyTags, true), 'The upgrade must keep the original topics in order and add custom ones.');
    $server->exec('DELETE pt FROM `' . $legacyName . '`.post_tags pt JOIN `' . $legacyName . '`.blog_posts p ON p.id = pt.post_id WHERE p.slug = \'legacy-a\'');
    run_cli(['scripts/setup-database.php']);
    check($legacyLinks() === ['legacy-b=Mẹo hay', 'legacy-c=Chủ đề tự đặt'], 'Running setup again must not restore a removed tag link.');
} finally {
    putenv('DB_NAME=' . $testName);
    $server->exec('DROP DATABASE IF EXISTS `' . $legacyName . '`');
}

// Leave the shared fixtures as they were for the remaining tests.
db()->exec('DELETE FROM blog_posts WHERE id IN (' . implode(',', [$untagged['id'], $tagged['id'], $textOnly['id']]) . ')');
