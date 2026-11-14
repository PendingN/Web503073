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
            $source = str_replace('$catalog = plant_catalog_statistics();', '$catalog = ["total" => 40];', $source, $replacements);
            if ($replacements !== 1) exit(1);
            eval(substr($source, 5));
        `], { cwd: root, encoding: 'utf8', env: { ...process.env, LANDING_SCRIPT_NAME: new URL('index.php', base).pathname } });
        assert.equal(rendered.status, 0, rendered.stderr);
        fixture = rendered.stdout;
        assert.ok(fixture.includes('data-plant-finder'), 'The fixture must render the actual landing template.');
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
        assert.equal(await page.locator('.plant-pick:visible').count(), 4);
        assert.ok(await page.locator('[data-plant-finder]').isVisible());
        await page.locator('img').evaluateAll(images => Promise.all(images.map(image => {
            image.loading = 'eager';
            return image.decode();
        })));
        assert.ok(await page.evaluate(() => document.fonts.check('16px Lora')));

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

        // Changing room conditions previews matches in place; the button opens the advisor.
        await page.selectOption('#room-light', 'low');
        await page.selectOption('#room-position', 'shelf');
        assert.deepEqual(await page.locator('.plant-pick:visible h3').allTextContents(), ['Lưỡi Hổ', 'Kim Tiền']);
        assert.match(await page.locator('[data-finder-status]').innerText(), /^2 gợi ý/);
        await page.selectOption('#room-light', 'bright');
        assert.deepEqual(await page.locator('.plant-pick:visible h3').allTextContents(), ['Lưỡi Hổ']);
        await page.selectOption('#room-light', 'all');
        await page.selectOption('#room-position', 'all');
        assert.equal(await page.locator('.plant-pick:visible').count(), 4);
        assert.equal(page.url(), base);

        // Exercise the empty-state recovery for a future editorial data change.
        const lightValues = await page.locator('.plant-pick').evaluateAll(plants => plants.map(plant => plant.dataset.light));
        await page.locator('.plant-pick').evaluateAll(plants => plants.forEach(plant => { plant.dataset.light = ''; }));
        await page.selectOption('#room-light', 'low');
        assert.ok(await page.locator('[data-finder-empty]').isVisible());
        await page.locator('.plant-pick').evaluateAll((plants, values) => plants.forEach((plant, i) => { plant.dataset.light = values[i]; }), lightValues);
        await page.selectOption('#room-light', 'all');
        assert.equal(await page.locator('[data-finder-empty]').isVisible(), false);
        await page.selectOption('#room-light', 'low');
        await page.selectOption('#room-position', 'shelf');
        await Promise.all([page.waitForURL(/plant-advisor\.php/), page.locator('[data-plant-finder] button').click()]);
        const advisorUrl = new URL(page.url());
        assert.equal(advisorUrl.searchParams.get('light'), 'low');
        assert.equal(advisorUrl.searchParams.get('position'), 'shelf');
        await page.goBack();
        await page.selectOption('#room-light', 'all');
        await page.selectOption('#room-position', 'all');
        for (const href of await page.locator('.plant-pick > a, .room-plant-note a').evaluateAll(links => links.map(link => link.href))) {
            const url = new URL(href);
            assert.equal(url.searchParams.get('category'), 'Cây trong nhà');
            assert.ok(url.searchParams.get('search'));
        }

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
        assert.equal(await reduced.locator('.room-image').evaluate(image => getComputedStyle(image).animationName), 'none');
        await reduced.mouse.move(1300, 500);
        assert.equal(await reduced.locator('.room-scene').evaluate(scene => scene.style.getPropertyValue('--room-x')), '');
        await reduced.close();
        const noScript = await newPage({ javaScriptEnabled: false, viewport: { width: 390, height: 844 } });
        assert.ok(await noScript.locator('[data-plant-finder]').isVisible(), 'The finder must still open the advisor without JavaScript.');
        assert.equal(await noScript.locator('.plant-pick:visible').count(), 4);
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
