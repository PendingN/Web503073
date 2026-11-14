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
                '$latestPosts = latest_blog_posts(6);',
                '$latestPosts = [["slug" => "bat-dau-mot-goc-xanh-tu-dau", "title" => "Bắt đầu một góc xanh từ đâu?", "image_path" => "images/anh24.jpg", "created_at" => "2026-04-02"], ["slug" => "5-dau-hieu-cay-can-quan-tam", "title" => "5 dấu hiệu cây đang cần bạn quan tâm", "image_path" => "images/anh48.jpg", "created_at" => "2026-03-26"]];',
                $source,
                $postReplacements
            );
            if ($catalogReplacements !== 1 || $postReplacements !== 1) exit(1);
            eval(substr($source, 5));
        `], { cwd: root, encoding: 'utf8', env: { ...process.env, LANDING_SCRIPT_NAME: new URL('index.php', base).pathname } });
        assert.equal(rendered.status, 0, rendered.stderr);
        fixture = rendered.stdout;
        assert.ok(fixture.includes('data-feature-showcase'), 'The fixture must render the actual landing template.');
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
        assert.equal(await page.locator('.plant-pick').count(), 0);
        assert.equal(await page.locator('.feature-card').count(), 5);
        assert.ok(await page.locator('[data-feature-showcase]').isVisible());
        await page.locator('img').evaluateAll(images => Promise.all(images.map(image => {
            image.loading = 'eager';
            return image.decode();
        })));
        assert.ok(await page.evaluate(() => document.fonts.check('16px "Be Vietnam Pro"')));
        await page.evaluate(() => window.scrollTo(0, 350));
        await page.waitForFunction(() => parseFloat(document.querySelector('.room-hero').style.getPropertyValue('--hero-travel')) > 0);
        await page.evaluate(() => window.scrollTo(0, 0));

        // Keyboard disclosure, exclusive selection, Escape, and outside dismissal.
        const first = page.locator('.room-spot').first();
        await first.locator('summary').focus();
        await page.keyboard.press('Enter');
        assert.ok(await first.evaluate(spot => spot.open));
        await page.keyboard.press('Escape');
        assert.equal(await first.evaluate(spot => spot.open), false);
        assert.ok(await first.locator('summary').evaluate(summary => summary === document.activeElement));
        await first.locator('summary').click();
        await page.locator('.room-spot').nth(1).locator('summary').click();
        assert.equal(await page.locator('.room-spot[open]').count(), 1);
        await page.locator('#room-title').click();
        assert.equal(await page.locator('.room-spot[open]').count(), 0);

        // Verify horizontal web feature cards and room note links.
        const featureHrefs = await page.locator('.feature-card-link').evaluateAll(links => links.map(link => link.getAttribute('href')));
        assert.deepEqual(featureHrefs, ['plant-advisor.php', 'plant-advisor.php?mode=elements', 'collection.php', 'blog.php', 'favorites.php']);
        const trackInitial = await page.locator('[data-feature-track]').evaluate(el => el.scrollLeft);
        assert.equal(trackInitial, 0);
        await page.locator('[data-feature-next]').click();
        await page.waitForTimeout(450);
        const trackScrolled = await page.locator('[data-feature-track]').evaluate(el => el.scrollLeft);
        assert.ok(trackScrolled > 100, `Expected feature track to advance on next click (got ${trackScrolled})`);
        await page.locator('[data-feature-prev]').click();
        await page.waitForTimeout(450);
        for (const href of await page.locator('.room-plant-note a').evaluateAll(links => links.map(link => link.href))) {
            const url = new URL(href);
            assert.equal(url.searchParams.get('category'), 'Cây trong nhà');
            assert.ok(url.searchParams.get('search'));
        }

        // Verify Floema editorial sections, minimalist hero, and adaptive header theme.
        assert.equal(await page.locator('.room-hero .hero-tile').count(), 18);
        assert.equal(await page.locator('.room-copy a, .room-copy button').count(), 0);
        assert.ok(await page.locator('.manifesto-section').isVisible());
        await page.locator('.manifesto-section').scrollIntoViewIfNeeded();
        await page.waitForSelector('.manifesto-stage .manifesto-typed', { state: 'visible' });
        await page.waitForSelector('.manifesto-stage .manifesto-caret', { state: 'attached' });
        const getManifestoTypedLen = () => page.locator('.manifesto-stage .manifesto-typed').evaluateAll(els => els.reduce((n, el) => n + el.textContent.length, 0));
        const typedLenStart = await getManifestoTypedLen();
        await page.waitForTimeout(450);
        const typedLenNext = await getManifestoTypedLen();
        assert.ok(typedLenNext > typedLenStart, `Expected manifesto text to keep typing forward (${typedLenStart} -> ${typedLenNext})`);
        assert.equal(await page.locator('.collections-overview .collection-row').count(), 2);
        assert.deepEqual(
            await page.locator('.collections-overview .collection-name').allTextContents(),
            ['Trong nhà', 'Để bàn']
        );
        assert.equal(await page.locator('.collections-overview .collection-thumb img').count(), 10);
        const firstRow = page.locator('.collections-overview .collection-row').first();
        const firstThumb = firstRow.locator('.collection-thumb');
        await firstRow.scrollIntoViewIfNeeded();
        await page.mouse.move(0, 0);
        const thumbBeforeHover = await firstThumb.boundingBox();
        const restFramesA = await firstThumb.locator('img').evaluateAll(imgs => imgs.map(img => Number(getComputedStyle(img).opacity) > 0.5));
        await page.waitForTimeout(450);
        const restFramesB = await firstThumb.locator('img').evaluateAll(imgs => imgs.map(img => Number(getComputedStyle(img).opacity) > 0.5));
        assert.deepEqual(restFramesA, [true, false, false, false, false], 'Expected static cover image when not hovered');
        assert.deepEqual(restFramesB, restFramesA, 'Expected no GIF frame change when not hovered');
        await firstRow.hover();
        await page.waitForTimeout(260);
        const thumbAfterHover = await firstThumb.boundingBox();
        const hoverFramesA = await firstThumb.locator('img').evaluateAll(imgs => imgs.map(img => Number(getComputedStyle(img).opacity) > 0.5));
        await page.waitForTimeout(360);
        const hoverFramesB = await firstThumb.locator('img').evaluateAll(imgs => imgs.map(img => Number(getComputedStyle(img).opacity) > 0.5));
        assert.ok(thumbAfterHover.width > thumbBeforeHover.width * 1.8, `Expected expanded thumb width (${thumbAfterHover.width}) > 1.8x (${thumbBeforeHover.width})`);
        assert.notDeepEqual(hoverFramesB, hoverFramesA, 'Expected collection thumb GIF reel to cycle frames while hovered');
        await page.mouse.move(0, 0);
        assert.ok(await page.locator('.forest-highlight').isVisible());
        assert.equal(await page.locator('.care-pillar').count(), 3);
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
        assert.equal(navMetrics.ctaBg, 'rgb(52, 83, 60)');
        await page.evaluate(() => window.scrollTo({ top: 0, behavior: 'instant' }));

        // All three notes must stay inside the visible room on every layout.
        for (const width of [320, 390, 768, 1024, 1440, 1920]) {
            await page.setViewportSize({ width, height: 1000 });
            await page.evaluate(() => window.scrollTo(0, 0));
            await page.mouse.move(0, 0);
            await page.waitForTimeout(1700);
            assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), `Overflow at ${width}px`);
            for (const spot of await page.locator('.room-spot').all()) {
                await spot.locator('summary').click();
                await page.waitForTimeout(250);
                const bounds = await spot.locator('.room-plant-note').boundingBox();
                const room = await page.locator('.room-scene').boundingBox();
                assert.ok(bounds.x >= -1 && bounds.x + bounds.width <= width + 1, `Plant note clipped horizontally at ${width}px`);
                assert.ok(bounds.y >= room.y - 1 && bounds.y + bounds.height <= room.y + room.height + 1, `Plant note clipped vertically at ${width}px: ${JSON.stringify({ bounds, room })}`);
                if (width <= 700) {
                    const image = await page.locator('.room-image').boundingBox();
                    assert.ok(bounds.y >= image.y + image.height - 1, 'Mobile plant notes must expand below the room image.');
                }
                if ((width === 1440 || width === 390) && (await spot.getAttribute('class')).includes('--fig')) {
                    await page.evaluate(() => window.scrollTo(0, 0));
                    await page.screenshot({ path: path.join(output, width === 1440 ? 'desktop-note.png' : 'mobile-note.png') });
                }
                await spot.locator('summary').click();
            }
            await page.evaluate(() => window.scrollTo(0, 0));
            await page.mouse.move(0, 0);
            await page.waitForTimeout(750);
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
        await reduced.evaluate(() => window.scrollTo(0, 350));
        assert.equal(await reduced.locator('.room-hero').evaluate(hero => hero.style.getPropertyValue('--hero-travel')), '0px');
        assert.equal(await reduced.locator('.room-image').evaluate(image => getComputedStyle(image).animationName), 'none');
        await reduced.mouse.move(1300, 500);
        assert.equal(await reduced.locator('.room-scene').evaluate(scene => scene.style.getPropertyValue('--room-x')), '');
        assert.equal(await reduced.locator('.manifesto-stage').count(), 0);
        await reduced.close();
        const noScript = await newPage({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } });
        assert.ok(await noScript.locator('[data-feature-showcase]').isVisible(), 'Feature showcase must remain accessible without JavaScript.');
        assert.equal(await noScript.locator('.feature-card').count(), 5);
        assert.equal(await noScript.locator('.manifesto-phrase').count(), 5);
        await noScript.locator('.room-spot').first().locator('summary').click();
        assert.ok(await noScript.locator('.room-spot').first().evaluate(spot => spot.open));
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
