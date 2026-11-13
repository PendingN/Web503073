// Requires Playwright on NODE_PATH and a running site (BLOG_BASE_URL).
// Public flows use live HTTP. The authenticated composer is a template fixture; no posts are written.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { chromium } = require('playwright');

(async () => {
    const root = path.resolve(__dirname, '..');
    const base = (process.env.BLOG_BASE_URL || 'http://localhost/Web503073/').replace(/\/?$/, '/');
    const output = path.join(root, '.runtime', 'blog-ui');
    fs.mkdirSync(output, { recursive: true });
    const php = process.env.PHP_BINARY || 'C:/xampp/php/php.exe';
    const covers = spawnSync(php, ['-r', `
        require 'includes/blog-images.php';
        foreach (['Chăm cây', 'Không gian', 'Cảm hứng', 'Mẹo hay', 'Sống xanh', 'Bài nổi bật', 'Unknown'] as $tag) {
            foreach ([null, ''] as $image) {
                if (!is_file(blog_cover_url(['tag' => $tag, 'image_path' => $image]))) exit(1);
            }
        }
        if (blog_cover_url(['tag' => 'Chăm cây', 'image_path' => 'images/blog/upload.png']) !== 'images/blog/upload.png') exit(1);
    `], { cwd: root, encoding: 'utf8' });
    assert.equal(covers.status, 0, 'Every fallback must exist and uploaded covers must take priority.');
    const fixture = spawnSync(php, ['-r', `
        $_SERVER['SCRIPT_NAME'] = getenv('BLOG_SCRIPT_NAME');
        $source = file_get_contents('blog.php');
        $source = str_replace('$user = current_user();', '$user = ["id" => -1, "role" => "user"];', $source, $replacements);
        if ($replacements !== 1) exit(1);
        eval(substr($source, 5));
    `], { cwd: root, encoding: 'utf8', env: { ...process.env, BLOG_SCRIPT_NAME: new URL('blog.php', base).pathname } });
    assert.equal(fixture.status, 0, 'The composer fixture must render the actual PHP template.');
    const browser = await chromium.launch({ channel: 'msedge', headless: true });
    const errors = [];
    try {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
        page.on('pageerror', error => errors.push(error.message));
        for (const width of [320, 390, 768, 1024, 1440, 1920]) {
            await page.setViewportSize({ width, height: 1000 });
            assert.equal((await page.goto(base + 'blog.php')).status(), 200);
            await page.evaluate(() => document.fonts.ready);
            await page.locator('.feed-motion-toggle').click();
            await page.locator('.feed-post img').evaluateAll(images => images.forEach(image => { image.loading = 'eager'; }));
            await page.waitForFunction(() => [...document.querySelectorAll('.feed-post img')].every(image => image.complete && image.naturalWidth > 0));
            const layout = await page.evaluate(() => ({
                overflow: document.documentElement.scrollWidth > innerWidth,
                columns: getComputedStyle(document.querySelector('.feed-list')).gridTemplateColumns.split(' ').length,
                images: [...document.querySelectorAll('.feed-post img')].every(image => image.complete && image.naturalWidth > 0),
                font: document.fonts.check('16px "Be Vietnam Pro"'),
                sidebar: document.querySelector('.feed-sidebar').getBoundingClientRect().left >= document.querySelector('.feed-column').getBoundingClientRect().right,
                smallInputs: [...document.querySelectorAll('.blog-search input:not([type="hidden"]), .blog-search select')].some(input => parseFloat(getComputedStyle(input).fontSize) < 16)
            }));
            assert.equal(layout.overflow, false, 'No horizontal overflow at ' + width);
            assert.equal(layout.columns, 1, 'Posts remain a single community feed.');
            assert.equal(layout.sidebar, width > 1050, 'The sidebar sits beside the desktop feed.');
            assert.equal(layout.images, true);
            assert.equal(layout.font, true);
            assert.equal(layout.smallInputs, false);
            await page.screenshot({ path: path.join(output, 'blog-' + width + '.png'), fullPage: true });
        }
        await page.setViewportSize({ width: 1440, height: 1000 });
        await page.goto(base + 'blog.php');
        await page.keyboard.press('Tab');
        assert.equal(await page.locator(':focus').getAttribute('class'), 'blog-skip-link');
        await page.keyboard.press('Enter');
        assert.equal(await page.locator(':focus').getAttribute('id'), 'blog-content');

        const firstTitle = await page.locator('.feed-post h3 a').first().innerText();
        const firstUrl = await page.locator('.feed-post h3 a').first().getAttribute('href');
        await page.locator('.feed-topics a').first().click();
        assert.equal(await page.getByLabel('Tìm bài viết', { exact: true }).inputValue(), 'Chăm cây');
        await page.goto(base + 'blog.php');
        await page.getByLabel('Tìm bài viết', { exact: true }).fill(firstTitle);
        await page.getByLabel('Sắp xếp').selectOption('popular');
        await Promise.all([page.waitForURL('**/blog.php?**'), page.getByRole('button', { name: 'Tìm kiếm', exact: true }).click()]);
        assert.equal(await page.getByLabel('Sắp xếp').inputValue(), 'popular');
        assert.ok((await page.locator('.feed-post h3 a').allTextContents()).includes(firstTitle));
        assert.equal(new URL(page.url()).hash, '#blog-content');

        await page.goto(base + 'blog.php?search=__no_blog_matches_9c37a__');
        assert.equal(await page.locator('.feed-post').count(), 0);
        assert.equal(await page.locator('.feed-ticker-card').count(), 0);
        await page.screenshot({ path: path.join(output, 'empty-search.png'), fullPage: true });
        await page.getByRole('link', { name: 'Xem tất cả bài viết' }).click();
        assert.ok(await page.locator('.feed-post').count());
        const next = page.getByRole('link', { name: 'Sau', exact: true });
        if (await next.count()) {
            await next.click();
            assert.equal(new URL(page.url()).searchParams.get('page'), '2');
            assert.equal(await page.getByRole('link', { name: 'Trước', exact: true }).count(), 1);
        }
        await page.goto(base + 'blog.php?view=saved');
        assert.ok(new URL(page.url()).pathname.endsWith('/login.php'), 'Personal views retain the login gate.');

        for (const width of [320, 390, 768, 1440]) {
            await page.setViewportSize({ width, height: 1000 });
            assert.equal((await page.goto(new URL(firstUrl, base).href)).status(), 200);
            assert.equal(await page.locator('h1').innerText(), firstTitle);
            assert.equal(await page.locator('.blog-post-art img').evaluate(image => image.complete && image.naturalWidth > 0), true);
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
            assert.equal(await page.locator('.blog-post-art img').evaluate(image => getComputedStyle(image).objectFit), 'contain');
            assert.ok((await page.locator('.blog-post-body').boundingBox()).width <= 720);
        }
        await page.goto(base + 'blog-post.php?slug=bat-dau-mot-goc-xanh-tu-dau');
        await page.screenshot({ path: path.join(output, 'article-desktop.png'), fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        await page.screenshot({ path: path.join(output, 'article-mobile.png'), fullPage: true });
        assert.equal((await page.goto(base + 'blog-post.php?slug=__missing_blog_9c37a__')).status(), 404);
        await page.getByRole('link', { name: 'Về trang blog' }).click();
        await page.locator('[data-menu-toggle]').click();
        assert.equal(await page.locator('[data-menu-toggle]').getAttribute('aria-expanded'), 'true');
        await page.keyboard.press('Escape');
        assert.equal(await page.locator('[data-menu-toggle]').getAttribute('aria-expanded'), 'false');

        const composerPage = await browser.newPage();
        composerPage.on('pageerror', error => errors.push(error.message));
        await composerPage.route('**/blog.php', route => route.fulfill({ status: 200, contentType: 'text/html; charset=utf-8', body: fixture.stdout }));
        for (const width of [320, 390, 1440]) {
            await composerPage.setViewportSize({ width, height: 1000 });
            await composerPage.goto(base + 'blog.php');
            const writeLink = composerPage.getByRole('link', { name: /Viết bài mới/ });
            assert.equal(await writeLink.getAttribute('href'), 'blog-editor.php');
            assert.equal(await composerPage.locator('.blog-form').count(), 0);
            assert.equal(await composerPage.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
            await composerPage.locator('.feed-composer').screenshot({ path: path.join(output, 'composer-' + width + '.png') });
        }
        const editorFixture = spawnSync(php, ['-r', `
            $_SERVER['SCRIPT_NAME'] = getenv('EDITOR_SCRIPT_NAME');
            $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
            $source = file_get_contents('blog-editor.php');
            $source = str_replace('$user = require_login(current_request_url());', '$user = ["id" => -1, "role" => "user"];', $source, $replacements);
            if ($replacements !== 1) exit(1);
            eval(substr($source, 5));
        `], { cwd: root, encoding: 'utf8', env: { ...process.env, EDITOR_SCRIPT_NAME: new URL('blog-editor.php', base).pathname } });
        assert.equal(editorFixture.status, 0, 'Editor fixture renders actual PHP template.');
        const editorPage = await browser.newPage();
        editorPage.on('pageerror', error => errors.push(error.message));
        editorPage.on('dialog', dialog => dialog.accept());
        await editorPage.route('**/blog-editor.php', route => route.fulfill({ status: 200, contentType: 'text/html; charset=utf-8', body: editorFixture.stdout }));
        await editorPage.goto(base + 'blog-editor.php');
        await editorPage.getByLabel('Tiêu đề bài viết', { exact: true }).fill('Một góc xanh trong nhà');
        await editorPage.getByRole('textbox', { name: 'Đoạn văn 1', exact: true }).fill('Chia sẻ cách chăm cây trong góc phòng sáng và thoáng.');
        await editorPage.getByLabel('Thêm khối', { exact: true }).selectOption('h2');
        await editorPage.getByRole('button', { name: 'Thêm', exact: true }).click();
        await editorPage.getByRole('textbox', { name: 'Tiêu đề H2 2', exact: true }).fill('Ánh sáng và nước');
        const tinyPng = { name: 'leaf.png', mimeType: 'image/png', buffer: Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+a6foAAAAASUVORK5CYII=', 'base64') };
        await editorPage.locator('#block-files').setInputFiles([tinyPng, { ...tinyPng, name: 'leaf-two.png' }]);
        await editorPage.getByLabel('Chú thích ảnh', { exact: true }).first().fill('Góc cây đầu tiên');
        await editorPage.getByRole('button', { name: 'Xem trước', exact: true }).click();
        assert.equal(await editorPage.locator('#editor-preview h2').textContent(), 'Ánh sáng và nước');
        assert.equal(await editorPage.locator('#editor-preview figure').count(), 2);
        assert.equal(await editorPage.locator('#editor-preview figcaption').first().textContent(), 'Góc cây đầu tiên');
        await editorPage.getByRole('button', { name: 'Tiếp tục viết' }).click();
        await editorPage.getByRole('button', { name: 'Hoàn tác', exact: true }).click();
        await editorPage.getByRole('button', { name: 'Làm lại', exact: true }).click();
        assert.equal(await editorPage.locator('.editor-block img').count(), 2);
        for (const width of [320, 390, 768, 1440]) {
            await editorPage.setViewportSize({ width, height: 1000 });
            assert.equal(await editorPage.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
            await editorPage.screenshot({ path: path.join(output, 'editor-' + width + '.png'), fullPage: true });
        }
        await editorPage.reload();
        await editorPage.getByRole('button', { name: 'Khôi phục', exact: true }).click();
        assert.equal(await editorPage.getByLabel('Tiêu đề bài viết', { exact: true }).inputValue(), 'Một góc xanh trong nhà');
        assert.equal(await editorPage.locator('.editor-block').count(), 4);
        assert.equal(await editorPage.getByText('Ảnh chưa được tải lên. Chọn lại ảnh để tiếp tục.', { exact: true }).count(), 2);
        await editorPage.evaluate(() => localStorage.removeItem(document.querySelector('form').dataset.draftKey));
        await editorPage.close();
        const responses = [
            { vote: 1, likes: 1, dislikes: 0, saved: false },
            { vote: -1, likes: 0, dislikes: 1, saved: false },
            { vote: -1, likes: 0, dislikes: 1, saved: true }
        ];
        await composerPage.route('**/actions/blog-action.php', async route => {
            const response = responses.shift();
            await route.fulfill({ status: response ? 200 : 403, contentType: 'application/json', body: JSON.stringify(response ? { ...response, message: 'Đã cập nhật.' } : { message: 'Không thể cập nhật bài viết.' }) });
        });
        const currentUrl = composerPage.url();
        const up = composerPage.locator('[data-reaction="vote"] [data-vote="1"]').first();
        const down = composerPage.locator('[data-reaction="vote"] [data-vote="-1"]').first();
        await up.click();
        await composerPage.waitForFunction(() => document.querySelector('[data-reaction="vote"] [data-vote="1"]').getAttribute('aria-pressed') === 'true');
        assert.equal(await up.locator('[data-vote-count]').innerText(), '1');
        await down.click();
        await composerPage.waitForFunction(() => document.querySelector('[data-reaction="vote"] [data-vote="-1"]').getAttribute('aria-pressed') === 'true');
        assert.equal(await up.getAttribute('aria-pressed'), 'false');
        assert.equal(await down.locator('[data-vote-count]').innerText(), '1');
        await composerPage.locator('[data-reaction="save"] button').first().click();
        await composerPage.waitForFunction(() => document.querySelector('[data-reaction="save"] button').getAttribute('aria-pressed') === 'true');
        assert.equal(composerPage.url(), currentUrl, 'Voting and saving do not navigate.');
        await up.click();
        await composerPage.waitForSelector('.reaction-error');
        assert.equal(await up.isEnabled(), true);
        assert.equal(await down.getAttribute('aria-pressed'), 'true', 'A rejected reaction preserves the previous vote.');
        await composerPage.close();

        const noJs = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } });
        const noJsPage = await noJs.newPage();
        await noJsPage.bringToFront();
        await noJsPage.goto(base + 'blog.php');
        await noJsPage.evaluate(() => document.fonts.ready);
        assert.ok(await noJsPage.locator('.feed-post').count());
        assert.equal(await noJsPage.locator('.feed-motion-toggle').isVisible(), false);
        await noJsPage.getByLabel('Tìm bài viết', { exact: true }).fill(firstTitle);
        await Promise.all([
            noJsPage.waitForURL('**/blog.php?**'),
            noJsPage.getByLabel('Tìm bài viết', { exact: true }).press('Enter')
        ]);
        assert.ok((await noJsPage.locator('.feed-post h3 a').allTextContents()).includes(firstTitle));
        await noJs.close();
        assert.deepEqual(errors, []);
        console.log('PASS: live blog at six widths, images/fonts, keyboard, search/sort, empty recovery, pagination, login gates, article/404, mobile menu and no-JS search.');
        console.log('PASS: dedicated editor link at three widths and mocked vote/save/error states. No database writes.');
        console.log('Screenshots: ' + output);
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
