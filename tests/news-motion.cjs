// Run with Playwright available on NODE_PATH. No database or application data is changed.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const http = require('node:http');
const { execFileSync } = require('node:child_process');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const output = fs.mkdtempSync(path.join(os.tmpdir(), 'blog-hero-check-'));
const fixture = `function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
require 'data/content.php'; $latestPosts = array_slice($posts, 0, (int)$argv[1]);
foreach ($latestPosts as &$post) { $post['created_at'] = '2026-10-14 10:00:00'; } unset($post);
$s = file_get_contents('blog.php'); $a = strpos($s, '    <section class="feed-hero"');
eval('?>' . substr($s, $a, strpos($s, '</section>', $a) + 10 - $a));`;
const server = http.createServer((req, res) => {
    const url = new URL(req.url, 'http://localhost');
    if (url.pathname === '/') {
        const hero = execFileSync(process.env.PHP_BINARY || 'C:/xampp/php/php.exe', ['-r', fixture, String(Number(url.searchParams.get('count') ?? 5))], { cwd: root, encoding: 'utf8' });
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        return res.end(`<!doctype html><html lang="vi"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="/css/fonts.css"><link rel="stylesheet" href="/css/style.css"><body class="blog-page"><main class="blog-feed-main">${hero}</main><script src="/js/main.js"></script></body></html>`);
    }
    const file = path.resolve(root, '.' + url.pathname);
    if (!file.startsWith(root + path.sep) || !fs.existsSync(file) || !fs.statSync(file).isFile()) return res.writeHead(404).end();
    res.setHeader('Content-Type', ({ '.css': 'text/css', '.js': 'text/javascript', '.webp': 'image/webp', '.woff2': 'font/woff2' })[path.extname(file)] || 'application/octet-stream');
    fs.createReadStream(file).pipe(res);
});
(async () => {
    await new Promise(resolve => server.listen(0, '127.0.0.1', resolve));
    const browser = await chromium.launch({ channel: 'msedge', headless: true });
    try {
        const page = await browser.newPage();
        const errors = [];
        page.on('pageerror', e => errors.push(e.message));
        const base = `http://127.0.0.1:${server.address().port}/`;
        for (const width of [1440, 1024, 768, 390, 320]) {
            await page.setViewportSize({ width, height: 900 });
            await page.goto(base);
            await page.evaluate(() => document.fonts.ready);
            await page.waitForSelector('.has-marquee');
            const geometry = await page.evaluate(() => {
                const rect = s => document.querySelector(s).getBoundingClientRect();
                const hero = rect('.feed-hero'), copy = rect('.feed-hero-copy'), viewport = rect('.feed-ticker-viewport');
                return { right: copy.right - hero.left, width: hero.width, bottom: copy.bottom, top: viewport.top, group: rect('.feed-ticker-group').width, viewport: viewport.width, overflow: document.documentElement.scrollWidth > innerWidth };
            });
            assert.equal(geometry.overflow, false);
            assert.ok(geometry.group >= geometry.viewport);
            if (width > 760) assert.ok(geometry.right <= geometry.width * .6, `Text escapes gradient at ${width}: ${JSON.stringify(geometry)}`);
            else assert.ok(geometry.top >= geometry.bottom);
            const before = await page.locator('.feed-hero-copy').boundingBox();
            for (const fraction of [0, .25, .5, .75, .9999, 1]) {
                await page.evaluate(f => { const a = document.querySelector('.feed-ticker-track').getAnimations()[0]; a.pause(); a.currentTime = a.effect.getTiming().duration * f; }, fraction);
                assert.deepEqual(await page.locator('.feed-hero-copy').boundingBox(), before);
                if ([0, .5, .9999, 1].includes(fraction)) await page.locator('.feed-hero').screenshot({ path: path.join(output, `hero-${width}-${fraction}.png`) });
            }
        }
        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(base);
        const track = page.locator('.feed-ticker-track'), toggle = page.locator('.feed-motion-toggle');
        const transform = () => track.evaluate(el => getComputedStyle(el).transform);
        let previous = await transform();
        await page.waitForTimeout(150);
        assert.notEqual(await transform(), previous);
        await toggle.click();
        assert.equal(await toggle.getAttribute('aria-pressed'), 'true');
        previous = await transform();
        await page.waitForTimeout(150);
        assert.equal(await transform(), previous);
        await toggle.click();
        await page.waitForTimeout(150);
        assert.notEqual(await transform(), previous);
        await page.locator('.feed-ticker-card').first().focus();
        assert.equal(await track.evaluate(el => getComputedStyle(el).animationName), 'none');
        assert.equal(await page.locator('[aria-hidden="true"] a:not([tabindex="-1"]), a[aria-hidden="true"]:not([tabindex="-1"])').count(), 0);
        await toggle.focus();
        await page.emulateMedia({ reducedMotion: 'reduce' });
        assert.equal(await track.evaluate(el => getComputedStyle(el).animationName), 'none');
        assert.equal(await toggle.isVisible(), false);
        assert.equal(await page.locator('.feed-ticker-group').count(), 1);
        await page.emulateMedia({ reducedMotion: 'no-preference' });
        await page.waitForFunction(() => !document.querySelector('.feed-motion-toggle').hidden);
        assert.equal(await toggle.isVisible(), true);
        for (const count of [0, 1]) {
            await page.goto(base + '?count=' + count);
            if (!count) assert.equal(await toggle.count(), 0);
            else {
                assert.equal(await page.locator('.feed-ticker-group').count(), 2);
                assert.ok((await page.locator('.feed-ticker-group').first().boundingBox()).width >= (await page.locator('.feed-ticker-viewport').boundingBox()).width);
            }
        }
        assert.deepEqual(errors, []);
        console.log('PASS: five widths, fixed text inside 97% gradient, loop phases, pause/resume, keyboard access, reduced motion, empty/single article.');
        console.log('Screenshots: ' + output);
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; }).finally(() => server.close());
