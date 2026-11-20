<?php
$showSiteFooter = $showSiteFooter ?? true;
$footerTagline = $footerTagline ?? 'Cùng thiên nhiên tạo nên những giá trị xanh bền vững.';
$pageScripts = $pageScripts ?? [];
?>
<?php if ($showSiteFooter): ?>
    <footer class="site-footer">
        <span>Là Lá La</span>
        <span><?= e($footerTagline) ?></span>
    </footer>
<?php endif; ?>
<?php
// The AI caterpillar sits in the corner of every public page for signed-in users.
$showAiWorm = current_user() !== null && ($bodyClass ?? '') !== 'admin-page' && ($activePage ?? '') !== 'ask';
if ($showAiWorm) {
    require __DIR__ . '/ai-worm.php';
    $pageScripts = array_values(array_unique([...$pageScripts, 'js/ask.js', 'js/ai-worm.js']));
}
?>
<?php foreach ($pageScripts as $pageScript): ?><script src="<?= e(asset_url($pageScript)) ?>" defer></script>
<?php endforeach; ?>
<script src="<?= e(asset_url('js/confirm-dialog.js')) ?>" defer></script>
<script src="<?= e(asset_url(($bodyClass ?? '') === 'admin-page' ? 'js/admin.js' : 'js/main.js')) ?>" defer></script>
<?php if (($bodyClass ?? '') === 'landing-page'): ?><script src="<?= e(asset_url('js/landing.js')) ?>" defer></script><?php endif; ?>
<?php if (in_array($bodyClass ?? '', ['blog-page', 'blog-post-page'], true)): ?><script src="<?= e(asset_url('js/blog.js')) ?>" defer></script><?php endif; ?>
</body>
</html>

