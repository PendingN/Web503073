// Run against a disposable seeded database with a dedicated test account.
// Requires Playwright on NODE_PATH and REACTIONS_BASE_URL/EMAIL/PASSWORD.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const { chromium } = require('playwright');

(async () => {
    const base = process.env.REACTIONS_BASE_URL;
    assert.ok(base && process.env.REACTIONS_EMAIL && process.env.REACTIONS_PASSWORD, 'Provide a disposable test server and account.');
    const browser = await chromium.launch({ channel: 'msedge', headless: true });
    const output = fs.mkdtempSync(path.join(os.tmpdir(), 'reactions-ui-'));
    try {
        const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        let navigations = 0;
        page.on('framenavigated', frame => { if (frame === page.mainFrame()) navigations++; });
        await page.goto(base + '/login.php');
        await page.locator('input[name="email"]').fill(process.env.REACTIONS_EMAIL);
        await page.locator('input[name="password"]').fill(process.env.REACTIONS_PASSWORD);
        await Promise.all([page.waitForURL('**/profile.php'), page.locator('button[type="submit"]').click()]);

        async function react(button, expectedPressed) {
            const before = navigations;
            const responsePromise = page.waitForResponse(response => response.url().includes('/actions/') && response.request().method() === 'POST');
            await button.click();
            const response = await responsePromise;
            assert.equal(response.status(), 200);
            assert.match(response.headers()['content-type'], /application\/json/);
            await page.waitForFunction(() => !document.querySelector('[data-reacting="true"]'));
            if (expectedPressed !== undefined) assert.equal(await button.getAttribute('aria-pressed'), String(expectedPressed));
            assert.equal(navigations, before, 'Reaction must not reload or navigate.');
            return response.json();
        }

        await page.goto(base + '/collection.php');
        const favorite = page.locator('[data-reaction="favorite"] button').first();
        const firstId = await page.locator('[data-reaction="favorite"] input[name="plant_id"]').first().inputValue();
        const state = await react(favorite, true);
        assert.equal(state.count, 1);
        assert.equal(await page.locator('.favorite-count').innerText(), '1');
        await page.waitForFunction(() => getComputedStyle(document.querySelector('[data-reaction="favorite"] svg')).fill === 'rgb(255, 48, 64)');
        assert.equal(await favorite.locator('svg').evaluate(icon => getComputedStyle(icon).fill), 'rgb(255, 48, 64)');
        await page.locator('.plant-card').first().screenshot({ path: path.join(output, 'favorite-desktop.png') });
        await page.reload();
        assert.equal(await favorite.getAttribute('aria-pressed'), 'true', 'Favorite must persist after reload.');

        await page.goto(base + '/plant-detail.php?id=' + firstId);
        const detailHeart = page.locator('[data-reaction="favorite"] button');
        await react(detailHeart, false);
        assert.equal(await detailHeart.locator('[data-reaction-label]').innerText(), 'Thêm vào yêu thích');

        // Two synchronous submits send one request while the resource is busy.
        let requests = 0;
        const countRequest = request => { if (request.url().includes('/actions/favorite-action.php')) requests++; };
        page.on('request', countRequest);
        await page.route('**/actions/favorite-action.php', async route => {
            await new Promise(resolve => setTimeout(resolve, 180));
            await route.continue();
        });
        await detailHeart.evaluate(button => { button.form.requestSubmit(button); button.form.requestSubmit(button); });
        await page.waitForFunction(() => document.querySelector('[data-reaction="favorite"] button').getAttribute('aria-pressed') === 'true');
        assert.equal(requests, 1, 'Repeated clicks must not double-submit.');
        page.off('request', countRequest);
        await page.unroute('**/actions/favorite-action.php');

        await page.goto(base + '/favorites.php');
        await react(page.locator('.favorite-button'), undefined);
        assert.equal(await page.locator('.plant-card').count(), 0);
        assert.equal(await page.locator('[data-favorites-empty]').isVisible(), true);
        assert.equal(await page.locator('.favorite-count').innerText(), '0');

        await page.goto(base + '/blog.php');
        const voteForm = page.locator('[data-reaction="vote"]').first();
        const like = voteForm.locator('[data-vote="1"]');
        const dislike = voteForm.locator('[data-vote="-1"]');
        const save = page.locator('[data-reaction="save"] button').first();
        const liked = await react(like, true);
        assert.equal(liked.likes, 1);
        assert.equal(liked.dislikes, 0);
        const disliked = await react(dislike, true);
        assert.equal(disliked.likes, 0);
        assert.equal(disliked.dislikes, 1);
        assert.equal(await like.locator('[data-vote-count]').innerText(), '0');
        assert.equal(await dislike.locator('[data-vote-count]').innerText(), '1');
        assert.equal(await like.getAttribute('aria-pressed'), 'false');
        await page.reload();
        assert.equal(await dislike.getAttribute('aria-pressed'), 'true');
        assert.equal(await like.locator('[data-vote-count]').innerText(), '0');
        assert.equal(await dislike.locator('[data-vote-count]').innerText(), '1');
        const unvoted = await react(dislike, false);
        assert.equal(unvoted.likes, 0);
        assert.equal(unvoted.dislikes, 0);
        await react(like, true);
        await react(save, true);
        const slug = await voteForm.locator('input[name="slug"]').inputValue();
        await page.reload();
        assert.equal(await like.getAttribute('aria-pressed'), 'true');
        assert.equal(await save.getAttribute('aria-pressed'), 'true');
        await page.goto(base + '/blog-post.php?slug=' + encodeURIComponent(slug));
        await react(page.locator('[data-vote="1"]'), false);
        await react(page.locator('[data-reaction="save"] button'), false);

        // A failed request leaves state intact and makes the button usable again.
        await page.route('**/actions/blog-action.php', route => route.abort('failed'));
        await page.locator('[data-vote="1"]').click();
        await page.waitForSelector('.reaction-error');
        assert.equal(await page.locator('[data-vote="1"]').getAttribute('aria-pressed'), 'false');
        assert.equal(await page.locator('[data-vote="1"]').isEnabled(), true);
        await page.unroute('**/actions/blog-action.php');
        await react(page.locator('[data-vote="1"]'), true);
        assert.equal(await page.locator('.reaction-error').count(), 0);

        // Server-side validation failures must be shown without changing state.
        const token = await page.locator('[data-reaction="vote"] input[name="csrf_token"]').inputValue();
        await page.locator('[data-reaction="vote"] input[name="csrf_token"]').evaluate(input => { input.value = 'bad'; });
        await page.locator('[data-vote="1"]').click();
        await page.waitForSelector('.reaction-error');
        assert.equal(await page.locator('[data-vote="1"]').getAttribute('aria-pressed'), 'true');
        await page.locator('[data-reaction="vote"] input[name="csrf_token"]').evaluate((input, value) => { input.value = value; }, token);
        await react(page.locator('[data-vote="1"]'), false);

        await page.setViewportSize({ width: 390, height: 844 });
        await page.goto(base + '/collection.php');
        await page.emulateMedia({ reducedMotion: 'reduce' });
        await react(favorite, true);
        assert.equal(await favorite.evaluate(button => button.getAnimations({ subtree: true }).length), 0);
        assert.equal(await page.evaluate(() => document.documentElement.scrollWidth > innerWidth), false);
        await page.locator('.plant-card').first().screenshot({ path: path.join(output, 'favorite-mobile.png') });

        const guest = await browser.newPage();
        await guest.goto(base + '/collection.php');
        await guest.locator('.favorite-button').first().click();
        await guest.waitForURL('**/login.php?return_to=**');
        assert.ok(new URL(guest.url()).searchParams.get('return_to').endsWith('/collection.php'));
        await guest.close();
        assert.deepEqual(errors, []);
        console.log('PASS: browser reactions, persistence, no navigation, repeated clicks, errors, CSRF, guest login, reduced motion and mobile layout.');
        console.log('Screenshots: ' + output);
    } finally {
        await browser.close();
    }
})().catch(error => { console.error(error); process.exitCode = 1; });
