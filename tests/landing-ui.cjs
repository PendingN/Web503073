// Requires Playwright on NODE_PATH and a running PHP server (LANDING_BASE_URL).
// LANDING_FIXTURE=1 renders the real template with only catalog statistics stubbed.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const { chromium } = require('playwright');

(async () => {
    const root = path.resolve(__dirname, '..');
    const base = (process.env.LANDING_BASE_URL || 'http://127.0.0.1:8090/').replace(/\/?$/, '/');
    const output = path.join(root, '.runtime', 'landing-ui');
    fs.mkdirSync(output, { recursive: true });
    let fixture;
    if (process.env.LANDING_FIXTURE === '1') {
        const rendered = spawnSync(process.env.PHP_BINARY || 'C:/xampp/php/php.exe', ['-r', `
            $_SERVER['SCRIPT_NAME'] = getenv('LANDING_SCRIPT_NAME');
            $source = file_get_contents('index.php');
            $source = str_replace(
                '$catalog = plant_catalog_statistics();',
                '$catalog = ["total" => 29, "counts" => ["Cây trong nhà" => 10, "Cây để bàn" => 19]];',
                $source,
                $catalogReplacements
            );
            $source = str_replace(
                '$collectionPlants = list_plants();',
                'require __DIR__ . "/data/plants.php"; $collectionPlants = $plants ?? [];',
                $source,
                $collectionReplacements
            );
            $source = str_replace(
                '$latestPosts = latest_blog_posts(6);',
                '$latestPosts = [["slug" => "bat-dau-mot-goc-xanh-tu-dau", "title" => "Bắt đầu một góc xanh từ đâu?", "image_path" => "images/anh24.jpg", "created_at" => "2026-04-02"], ["slug" => "5-dau-hieu-cay-can-quan-tam", "title" => "5 dấu hiệu cây đang cần bạn quan tâm", "image_path" => "images/anh48.jpg", "created_at" => "2026-03-26"]];',
                $source,
                $postReplacements
            );
            if ($catalogReplacements !== 1 || $collectionReplacements !== 1 || $postReplacements !== 1) exit(1);
            eval(substr($source, 5));
        `], { cwd: root, encoding: 'utf8', env: { ...process.env, LANDING_SCRIPT_NAME: new URL('index.php', base).pathname } });
        assert.equal(rendered.status, 0, rendered.stderr);
        fixture = rendered.stdout;
        assert.ok(fixture.includes('lalala-offerings'), 'The fixture must render the actual landing template.');
        fs.writeFileSync(path.join(output, 'landing.html'), fixture);
    }
    const browser = await chromium.launch({ channel: 'msedge', headless: true });
    const errors = [];
    async function newPage(options = {}) {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1000 }, ...options });
        page.on('pageerror', error => errors.push(error.message));
        if (fixture) {
            await page.route('**/*', route => {
                const url = new URL(route.request().url());
                const isLanding = url.href === base || url.href === new URL('index.php', base).href;
                return isLanding && route.request().isNavigationRequest()
                    ? route.fulfill({ contentType: 'text/html; charset=utf-8', body: fixture })
                    : route.continue();
            });
        }
        const response = await page.goto(base, { waitUntil: 'networkidle' });
        assert.equal(response.status(), 200, 'Landing page must load; use fixture mode when MySQL is unavailable.');
        await page.evaluate(() => document.fonts.ready);
        await page.keyboard.press('Tab');
        assert.ok(await page.locator('.landing-skip-link').evaluate(link => link === document.activeElement), 'Skip link must be the first keyboard stop.');
        await page.locator('.landing-skip-link').evaluate(link => link.blur());
        return page;
    }
    let page;
    try {
        page = await newPage();
        assert.equal(await page.locator('h1').count(), 1);
        assert.ok((await page.locator('.lalala-hero h1').textContent()).includes('Là Lá La'));
        assert.equal(await page.locator('.lalala-hero .lalala-card').count(), 35);
        assert.equal(await page.locator('.lalala-hero .lalala-btn--primary').getAttribute('href'), 'collection.php');
        assert.equal(await page.locator('.lalala-hero .lalala-btn--secondary').getAttribute('href'), '#lalala-features');
        assert.equal(await page.locator('.room-hero').count(), 0);
        assert.equal(await page.locator('.room-scene').count(), 0);
        assert.equal(await page.locator('.feature-showcase').count(), 0);
        assert.equal(await page.locator('.plant-pick').count(), 0);
        assert.equal(await page.locator('.lalala-offering-card').count(), 4);
        assert.ok(await page.locator('.lalala-offerings').isVisible());
        assert.equal(await page.locator('.lalala-need-card').count(), 4);
        assert.ok(await page.locator('.lalala-needs').isVisible());
        assert.ok((await page.locator('.lalala-needs h2').textContent()).includes('Những vấn đề cây đang gặp phải'));
        assert.ok(await page.locator('.lalala-marquee-section').isVisible());
        assert.equal(await page.locator('[data-marquee-toggle]').count(), 0);
        assert.ok((await page.locator('.lalala-marquee-section .lalala-btn--primary').textContent()).includes('Khám phá Bộ sưu tập'));
        assert.equal(await page.locator('.lalala-marquee-body').count(), 0);
        assert.ok((await page.locator('.lalala-marquee-group').first().locator('.lalala-marquee-card').count()) >= 28);
        assert.ok(await page.locator('.lalala-blog-intro').isVisible());
        assert.equal(await page.locator('.lalala-blog-intro .lalala-btn--primary').getAttribute('href'), 'blog.php');
        assert.equal(await page.locator('.lalala-blog-intro .lalala-btn--secondary').getAttribute('href'), 'blog-editor.php');
        assert.ok((await page.locator('.lalala-blog-card').count()) >= 1);
        assert.ok((await page.locator('.lalala-blog-card-author').count()) >= 1);
        await page.locator('img').evaluateAll(images => Promise.all(images.map(image => {
            image.loading = 'eager';
            return image.decode();
        })));
        assert.ok(await page.evaluate(() => document.fonts.check('16px "Plus Jakarta Sans"')));
        assert.ok(await page.evaluate(() => document.fonts.check('700 16px "Fraunces"')));

        const offeringHrefs = await page.locator('.lalala-offering-card').evaluateAll(links => links.map(link => link.getAttribute('href')));
        assert.deepEqual(offeringHrefs, ['collection.php', 'plant-advisor.php', 'blog.php', 'favorites.php']);

        assert.equal(await page.locator('.manifesto-section').count(), 0);
        assert.equal(await page.locator('.collections-overview').count(), 0);
        assert.equal(await page.locator('.forest-highlight').count(), 0);
        assert.equal(await page.locator('.care-pillar').count(), 0);
        assert.ok(await page.locator('.floema-footer').isVisible());
        assert.equal(await page.locator('.site-header .nav-search-link').count(), 0);
        const navMetrics = await page.locator('.site-header').evaluate(el => {
            const headerStyle = getComputedStyle(el);
            const activeLink = el.querySelector('.nav-link.active');
            const cta = el.querySelector('.btn-contact');
            return {
                position: headerStyle.position,
                minHeight: headerStyle.minHeight,
                activeBg: activeLink ? getComputedStyle(activeLink).backgroundColor : '',
                ctaBg: cta ? getComputedStyle(cta).backgroundColor : '',
            };
        });
        assert.equal(navMetrics.position, 'relative');
        assert.equal(navMetrics.minHeight, '82px');
        assert.equal(navMetrics.activeBg, 'rgba(0, 0, 0, 0)');
        assert.equal(navMetrics.ctaBg, 'rgb(24, 48, 34)');
        await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));

        for (const width of [320, 390, 768, 1024, 1440, 1920]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.evaluate(() => window.scrollTo(0, 0));
            await page.mouse.move(0, 0);
            await page.waitForTimeout(750);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `Overflow at ${width}px`);
            if (width === 1440 || width === 390) await page.screenshot({ path: path.join(output, width === 1440 ? 'desktop.png' : 'mobile.png') });
            await page.screenshot({ path: path.join(output, `landing-${width}.png`), fullPage: true });
        }
        await page.setViewportSize({ width: 390, height: 844 });
        await page.evaluate(() => window.scrollTo(0, 0));
        await page.locator('[data-menu-toggle]').click();
        assert.equal(await page.locator('[data-menu-toggle]').getAttribute('aria-expanded'), 'true');
        await page.keyboard.press('Escape');
        assert.equal(await page.locator('[data-menu-toggle]').getAttribute('aria-expanded'), 'false');

        const reduced = await newPage({ reducedMotion: 'reduce' });
        assert.equal(await reduced.locator('.lalala-marquee-section').evaluate(el => getComputedStyle(el.querySelector('.lalala-marquee-track')).animationName), 'none');
        await reduced.close();
        const noScript = await newPage({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } });
        assert.ok(await noScript.locator('.lalala-offerings').isVisible(), 'Offerings section must remain accessible without JavaScript.');
        assert.equal(await noScript.locator('.lalala-offering-card').count(), 4);
        assert.equal(await noScript.locator('.lalala-need-card').count(), 4);
        await noScript.close();
        assert.deepEqual(errors, [], 'No JavaScript errors.');
        console.log(`PASS: landing interactions, filters, advisor link, keyboard, mobile menu, reduced motion, no-JS fallback, assets, and six widths. Mode: ${fixture ? 'template fixture; no live database' : 'live HTTP'}. Screenshots: ${output}`);
    } catch (error) {
        if (page) await page.screenshot({ path: path.join(output, 'failure.png'), fullPage: true });
        throw error;
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
