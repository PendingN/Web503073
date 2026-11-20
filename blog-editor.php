<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/blog-blocks.php';
require_once __DIR__ . '/includes/realtime.php';
$user = require_login(current_request_url());
$slug = request_string($_GET, 'edit');
$post = $slug !== '' ? find_post($slug) : null;
if ($slug !== '' && $post === null) { abort_request(404, 'Không tìm thấy bài viết.'); }
if ($post !== null && !can_edit_post($post, $user)) { abort_request(403, 'Bạn không có quyền sửa bài viết này.'); }
// A locked post is unlocked on its own page first; the editor never shows locked content.
if ($post !== null && !post_is_unlocked($post)) {
    set_flash('Bài viết đang được khóa. Nhập mật khẩu để tiếp tục.');
    redirect_to(app_url('blog-post.php?slug=' . rawurlencode($slug)));
}
$canChangeVisibility = $post === null || is_post_owner($post, $user);
// Co-editing is for the author and recipients with edit permission, on posts that already exist.
$collab = null;
if ($post !== null && in_array(post_role($post, $user), ['owner', 'edit'], true)) {
    try {
        $collab = ['ticket' => realtime_ticket($post, $user)] + realtime_client_config();
    } catch (RuntimeException $exception) {
        error_log('Co-editing disabled: ' . $exception->getMessage());
    }
}
$draft = $_SESSION['blog_draft'] ?? null;
$form = is_array($draft) && ($draft['slug'] ?? '') === $slug ? $draft : ($post ?? ['title' => '', 'content' => '', 'tag' => 'Chăm cây']);
unset($_SESSION['blog_draft']);
$initialBlocks = blog_blocks(['content' => $form['content'] ?? '']);
if (!empty($form['content_blocks'])) {
    $decoded = json_decode($form['content_blocks'], true);
    $pendingImages = [];
    foreach (is_array($decoded['blocks'] ?? null) ? $decoded['blocks'] : [] as $block) {
        if (is_array($block) && is_string($block['src'] ?? null) && preg_match('/^upload_[a-f0-9]+$/D', $block['src'])) {
            $pendingImages[$block['src']] = $block['src'];
        }
    }
    try {
        $initialBlocks = normalize_blog_blocks($form['content_blocks'], $post ? blog_block_images(blog_blocks($post)) : [], $pendingImages);
    } catch (InvalidArgumentException $exception) {
        // Keep the plain-text draft if submitted block structure is invalid.
    }
}
$existingSizes = [];
foreach ($post ? array_filter([...blog_block_images(blog_blocks($post)), $post['image_path']]) : [] as $path) {
    $absolute = __DIR__ . '/' . $path;
    $existingSizes[$path] = is_file($absolute) ? (int) filesize($absolute) : 0;
}
$pageTitle = $post ? 'Sửa bài viết' : 'Viết bài mới';
$bodyClass = 'blog-editor-page';
$pageStyles = ['css/blog-editor.css'];
$pageScripts = $collab !== null ? ['js/blog-editor.js', 'js/blog-collab.js'] : ['js/blog-editor.js'];
$showSiteFooter = false;
$fromAdmin = request_string($_GET, 'from') === 'admin' && $user['role'] === 'admin';
$back = $fromAdmin ? 'admin-posts.php' : 'blog.php';
require __DIR__ . '/includes/header.php';
?>
<a class="blog-skip-link" href="#main-content">Đến nội dung chính</a>
<main class="editor-shell" id="main-content" tabindex="-1">
    <form id="blog-editor" action="actions/blog-action.php" method="post" enctype="multipart/form-data" data-draft-key="blog-draft-<?= (int) $user['id'] ?>-<?= e($slug ?: 'new') ?>" data-cover="<?= e($post['image_path'] ?? '') ?>" data-base="<?= e(app_url()) ?>" data-existing-sizes="<?= e(json_encode($existingSizes)) ?>"<?php if ($collab !== null): ?> data-collab-ticket="<?= e($collab['ticket']) ?>" data-collab-url="<?= e($collab['url']) ?>" data-collab-port="<?= e($collab['port']) ?>"<?php endif; ?>>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="operation" value="<?= $post ? 'update' : 'create' ?>">
        <input type="hidden" name="slug" value="<?= e($slug) ?>">
        <input type="hidden" name="return_to" value="<?= e($back) ?>">
        <input type="hidden" name="content_blocks" id="blocks-data">
        <header class="editor-header">
            <a href="<?= e($back) ?>">← <?= $fromAdmin ? 'Quay lại Quản trị bài viết' : 'Quay lại Blog' ?></a>
            <span id="draft-status" role="status">Nháp trên thiết bị này</span>
            <button type="button" id="preview-toggle" aria-pressed="false">Xem trước</button>
            <button class="editor-primary" type="submit"><?= $post ? 'Lưu thay đổi' : 'Đăng bài' ?></button>
        </header>
        <?php if ($collab !== null): ?>
            <div class="collab-bar" data-collab data-state="connecting">
                <span class="collab-dot" aria-hidden="true"></span>
                <span class="collab-status" role="status" aria-live="polite" data-collab-status>Đang kết nối sửa cùng…</span>
                <ul class="collab-people" aria-label="Người đang sửa cùng" data-collab-people></ul>
                <span class="collab-hint">Thay đổi hiện ngay cho mọi người. Bấm “Lưu thay đổi” để lưu vào bài.</span>
            </div>
        <?php endif; ?>
        <?php if ($flash = take_flash()): ?><p class="editor-notice" role="alert"><?= e($flash) ?></p><?php endif; ?>
        <p class="editor-notice" id="editor-error" role="alert" hidden></p>
        <div class="editor-notice" id="draft-recovery" hidden>Bạn có bản nháp chưa đăng. <button type="button" id="restore-draft">Khôi phục</button> <button type="button" id="discard-draft">Bỏ nháp</button></div>
        <div class="editor-layout">
            <section class="editor-paper" aria-label="Soạn bài viết">
                <label class="editor-label" for="editor-title">Tiêu đề bài viết</label>
                <input id="editor-title" name="title" value="<?= e($form['title']) ?>" placeholder="Câu chuyện xanh của bạn…" minlength="3" maxlength="120" required>
                <div class="editor-toolbar" role="toolbar" aria-label="Định dạng văn bản">
                    <button type="button" data-format="strong" aria-label="In đậm"><strong>B</strong></button>
                    <button type="button" data-format="em" aria-label="In nghiêng"><em>I</em></button>
                    <button type="button" data-format="u" aria-label="Gạch chân"><u>U</u></button>
                    <button type="button" data-format="a">Liên kết</button>
                    <button type="button" data-format="clear">Xóa định dạng</button>
                    <button type="button" id="undo">Hoàn tác</button>
                    <button type="button" id="redo">Làm lại</button>
                </div>
                <div id="editor-blocks"></div>
                <div class="editor-add">
                    <label for="block-type">Thêm khối</label>
                    <select id="block-type"><option value="paragraph">Đoạn văn</option><option value="h2">Tiêu đề H2</option><option value="h3">Tiêu đề H3</option><option value="ul">Danh sách</option><option value="ol">Danh sách đánh số</option><option value="quote">Trích dẫn</option><option value="divider">Đường phân cách</option></select>
                    <button type="button" id="add-block">Thêm</button>
                    <button type="button" id="add-images">Chèn ảnh</button>
                    <input type="file" id="block-files" accept="image/jpeg,image/png,image/webp" multiple hidden>
                </div>
                <p class="editor-hint" id="word-count">Nội dung: 10–10.000 ký tự</p>
            </section>
            <aside class="editor-settings" aria-label="Thiết lập bài viết">
                <details open><summary>Thiết lập bài viết</summary>
                    <label for="editor-tag">Chủ đề</label>
                    <select id="editor-tag" name="tag"><?php foreach (['Chăm cây', 'Không gian', 'Cảm hứng', 'Mẹo hay', 'Sống xanh', 'Bài nổi bật'] as $tag): ?><option<?= $form['tag'] === $tag ? ' selected' : '' ?>><?= e($tag) ?></option><?php endforeach; ?></select>
                    <?php if ($canChangeVisibility): ?>
                        <label class="editor-check" for="editor-private">
                            <input type="checkbox" id="editor-private" name="is_private" value="1"<?= !empty($form['is_private']) ? ' checked' : '' ?>>
                            <span><strong>Riêng tư</strong><small>Chỉ bạn và người được chia sẻ mới xem được.</small></span>
                        </label>
                    <?php else: ?>
                        <p class="editor-hint">Bạn đang sửa bài được chia sẻ. Chỉ tác giả mới đổi được chế độ hiển thị.</p>
                    <?php endif; ?>
                    <label for="editor-cover">Ảnh bìa</label>
                    <input type="file" id="editor-cover" name="image" accept="image/jpeg,image/png,image/webp">
                    <?php if (!empty($post['image_path'])): ?><img id="cover-preview" src="<?= e($post['image_path']) ?>" alt="Xem trước ảnh bìa" decoding="async"><?php endif; ?>
                    <p class="editor-hint">JPG, PNG hoặc WebP. Tối đa 10 ảnh kể cả ảnh bìa, 2 MB/ảnh và 6 MB tổng dung lượng ảnh.</p>
                    <p class="editor-hint">Nháp lưu chữ và bố cục trên máy này. Ảnh mới chưa đăng cần chọn lại sau khi tải lại trang.</p>
                </details>
            </aside>
        </div>
    </form>
    <section id="editor-preview" class="editor-paper block-article" aria-label="Xem trước bài viết" hidden></section>
    <noscript><p class="editor-notice">Vui lòng bật JavaScript để sử dụng trình viết theo khối.</p></noscript>
</main>
<script type="application/json" id="initial-blocks"><?= json_encode($initialBlocks, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
