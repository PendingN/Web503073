<?php
// AI summary box on blog-post.php. Expects $post and $user; works without JavaScript (form POST + redirect).
$summary = find_blog_summary($post);
$summaryNotice = $_SESSION['summary_notice'] ?? null;
unset($_SESSION['summary_notice']);
?>
<section class="ai-summary<?= $summary !== null ? ' has-summary' : '' ?>" id="ai-summary" aria-labelledby="ai-summary-title" data-ai-summary>
    <div class="ai-summary-head">
        <h2 id="ai-summary-title">
            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9Z"/><path d="M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8Z"/></svg>
            Tóm tắt bằng AI
        </h2>
        <?php if ($user !== null): ?>
            <form method="post" action="actions/blog-summary-action.php" data-ai-summary-form>
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="slug" value="<?= e($post['slug']) ?>">
                <button class="ai-summary-button" type="submit" data-ai-summary-button><?= $summary !== null ? 'Tạo lại' : 'Tóm tắt bài này' ?></button>
            </form>
        <?php else: ?>
            <a class="ai-summary-button" href="login.php?<?= e(http_build_query(['return_to' => 'blog-post.php?slug=' . $post['slug']])) ?>">Đăng nhập để tóm tắt</a>
        <?php endif; ?>
    </div>

    <p class="ai-summary-status" role="status" aria-live="polite" data-ai-summary-status><?= $summaryNotice !== null ? e($summaryNotice) : '' ?></p>

    <div class="ai-summary-body" data-ai-summary-body<?= $summary === null ? ' hidden' : '' ?>>
        <p class="ai-summary-lead" data-ai-summary-lead><?= $summary !== null ? e($summary['summary']) : '' ?></p>
        <ul data-ai-summary-points>
            <?php foreach ($summary['points'] ?? [] as $point): ?><li><?= e($point) ?></li><?php endforeach; ?>
        </ul>
        <p class="ai-summary-meta">Tạo lúc <span data-ai-summary-time><?= $summary !== null ? e(date('d/m/Y H:i', strtotime($summary['created_at']))) : '' ?></span> · AI có thể sai, hãy đối chiếu với bài viết.</p>
    </div>
    <?php if ($summary === null): ?>
        <p class="ai-summary-empty" data-ai-summary-empty>Đọc nhanh ý chính của bài trong vài giây.</p>
    <?php endif; ?>
</section>
