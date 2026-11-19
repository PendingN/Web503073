// Run with Playwright available on NODE_PATH. No database or application data is changed.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const http = require('node:http');
const { execFileSync } = require('node:child_process');
const { chromium } = require('playwright');
const root = path.resolve(__dirname, '..');
const output = fs.mkdtempSync(path.join(os.tmpdir(), 'blog-masthead-check-'));
const fixture = `$s = file_get_contents('blog.php');
$a = strpos($s, '<section class="masthead-section">');
if ($a === false) exit(1);
$section = substr($s, $a, strpos($s, '</section>', $a) + 10 - $a);
$interval = max(200, (int) ($argv[1] ?? 4000));
echo str_replace('data-interval="4000"', 'data-interval="' . $interval . '"', $section);`;
const server = http.createServer((req, res) => {
    const url = new URL(req.url, 'http://localhost');
    if (url.pathname === '/') {
        const masthead = execFileSync(process.env.PHP_BINARY || 'C:/xampp/php/php.exe', ['-r', fixture, String(Number(url.searchParams.get('interval') ?? 4000))], { cwd: root, encoding: 'utf8' });
        res.setHeader('Content-Type', 'text/html; charset=utf-8');
        return res.end(`<!doctype html><html lang="vi"><meta name="viewport" content="width=device-width, initial-scale=1"><link rel="stylesheet" href="/css/fonts.css"><link rel="stylesheet" href="/css/style.css"><link rel="stylesheet" href="/css/blog.css"><body class="blog-page"><main class="blog-feed-main">${masthead}</main><script src="/js/main.js"></script></body></html>`);
    }
    const file = path.resolve(root, '.' + url.pathname);
    if (!file.startsWith(root + path.sep) || !fs.existsSync(file) || !fs.statSync(file).isFile()) return res.writeHead(404).end();
    res.setHeader('Content-Type', ({ '.css': 'text/css', '.js': 'text/javascript', '.webp': 'image/webp', '.woff2': 'font/woff2', '.svg': 'image/svg+xml' })[path.extname(file)] || 'application/octet-stream');
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

        const readMastheadState = () => page.evaluate(() => {
            const masthead = document.querySelector('.masthead');
            const brand = document.querySelector('.masthead-slide--brand');
            const tagline = document.querySelector('.masthead-slide--tagline');
            const mastheadRect = masthead.getBoundingClientRect();
            const brandRect = brand.getBoundingClientRect();
            const tagRect = tagline.getBoundingClientRect();
            return {
                overflow: document.documentElement.scrollWidth > innerWidth,
                mastheadHeight: mastheadRect.height,
                brandActive: brand.classList.contains('is-active'),
                brandAriaHidden: brand.getAttribute('aria-hidden'),
                taglineActive: tagline.classList.contains('is-active'),
                taglineAriaHidden: tagline.getAttribute('aria-hidden'),
                brandFits: brandRect.left >= mastheadRect.left - 0.5 && brandRect.right <= mastheadRect.right + 0.5 && brand.scrollWidth <= brand.clientWidth + 1,
                taglineFits: tagRect.left >= mastheadRect.left - 0.5 && tagRect.right <= mastheadRect.right + 0.5 && tagline.scrollWidth <= tagline.clientWidth + 1
            };
        });

        for (const width of [1440, 1024, 768, 390, 320]) {
            await page.setViewportSize({ width, height: 900 });
            await page.goto(base + '?interval=250');
            await page.evaluate(() => document.fonts.ready);
            const initial = await readMastheadState();
            assert.equal(initial.overflow, false, `No horizontal overflow at ${width}`);
            assert.equal(initial.brandActive, true, `Brand slide active initially at ${width}`);
            assert.equal(initial.brandAriaHidden, 'false');
            assert.equal(initial.taglineActive, false);
            assert.equal(initial.taglineAriaHidden, 'true');
            assert.equal(initial.brandFits, true, `Brand slide fits without clipping at ${width}`);
            assert.equal(initial.taglineFits, true, `Tagline slide fits without clipping at ${width}`);
            await page.locator('.masthead').screenshot({ path: path.join(output, `masthead-${width}-brand.png`) });

            await page.waitForFunction(() => document.querySelector('.masthead-slide--tagline')?.classList.contains('is-active') === true);
            const swapped = await readMastheadState();
            assert.equal(swapped.overflow, false, `No horizontal overflow after swap at ${width}`);
            assert.equal(swapped.taglineActive, true);
            assert.equal(swapped.taglineAriaHidden, 'false');
            assert.equal(swapped.brandActive, false);
            assert.equal(swapped.brandAriaHidden, 'true');
            assert.ok(Math.abs(swapped.mastheadHeight - initial.mastheadHeight) < 0.5, `Masthead height unchanged at ${width}: ${initial.mastheadHeight} vs ${swapped.mastheadHeight}`);
            await page.locator('.masthead').screenshot({ path: path.join(output, `masthead-${width}-tagline.png`) });
        }

        await page.setViewportSize({ width: 1440, height: 900 });
        await page.goto(base + '?interval=250');
        await page.evaluate(() => document.fonts.ready);
        const start = await readMastheadState();
        assert.equal(start.brandActive, true);
        assert.equal(start.brandAriaHidden, 'false');
        assert.equal(start.taglineAriaHidden, 'true');

        await page.waitForFunction(() => document.querySelector('.masthead-slide--tagline')?.classList.contains('is-active') === true);
        const mid = await readMastheadState();
        assert.equal(mid.taglineActive, true);
        assert.equal(mid.taglineAriaHidden, 'false');
        assert.equal(mid.brandActive, false);
        assert.equal(mid.brandAriaHidden, 'true');

        await page.waitForFunction(() => document.querySelector('.masthead-slide--brand')?.classList.contains('is-active') === true);
        const back = await readMastheadState();
        assert.equal(back.brandActive, true);
        assert.equal(back.brandAriaHidden, 'false');
        assert.equal(back.taglineActive, false);
        assert.equal(back.taglineAriaHidden, 'true');

        // Reduced motion stops rotation and keeps THE BLOG active.
        await page.waitForFunction(() => document.querySelector('.masthead-slide--tagline')?.classList.contains('is-active') === true);
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await page.waitForFunction(() => document.querySelector('.masthead-slide--brand')?.classList.contains('is-active') === true);
        const reduced = await readMastheadState();
        assert.equal(reduced.brandActive, true);
        assert.equal(reduced.brandAriaHidden, 'false');
        assert.equal(reduced.taglineActive, false);
        assert.equal(reduced.taglineAriaHidden, 'true');
        await page.waitForTimeout(450);
        const reducedAfterWait = await readMastheadState();
        assert.equal(reducedAfterWait.brandActive, true);
        assert.equal(reducedAfterWait.taglineActive, false);

        await page.emulateMedia({ reducedMotion: 'no-preference' });
        await page.waitForFunction(() => document.querySelector('.masthead-slide--tagline')?.classList.contains('is-active') === true);

        await page.emulateMedia({ reducedMotion: 'reduce' });
        await page.goto(base + '?interval=250');
        await page.waitForTimeout(450);
        const reducedLoad = await readMastheadState();
        assert.equal(reducedLoad.brandActive, true);
        assert.equal(reducedLoad.brandAriaHidden, 'false');
        assert.equal(reducedLoad.taglineActive, false);
        assert.equal(reducedLoad.taglineAriaHidden, 'true');

        assert.deepEqual(errors, []);
        console.log('PASS: five widths, no horizontal clipping, zero masthead height jump, timed alternation, and reduced-motion enforcement.');
        console.log('Screenshots: ' + output);
    } finally { await browser.close(); }
})().catch(error => { console.error(error); process.exitCode = 1; }).finally(() => server.close());
