import AxeBuilder from '@axe-core/playwright';
import { expect, test, type Page } from '@playwright/test';

/** Every kind of public page, in both languages where they exist (data from LighthouseSeeder). */
const PAGES = [
    '/',
    '/am',
    '/work/euisis',
    '/am/work/euisis',
    '/notes',
    '/notes/storing-ethiopian-dates',
    '/now',
    '/colophon',
    '/am/colophon',
    '/no-such-page',
];

const WCAG = ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'];

async function violations(page: Page, include?: string): Promise<string[]> {
    const builder = new AxeBuilder({ page }).withTags(WCAG);
    if (include) builder.include(include);
    const { violations: found } = await builder.analyze();
    return found.map((v) => `${v.id} (${v.impact}): ${v.nodes.map((n) => n.target.join(' ')).join(' | ')}`);
}

async function open(page: Page, path: string): Promise<void> {
    await page.goto(path, { waitUntil: 'networkidle' });
    // Fonts swapped in, so contrast is checked on the final glyphs.
    await page.evaluate(() => document.fonts.ready);
}

for (const scheme of ['light', 'dark'] as const) {
    test.describe(`${scheme} theme`, () => {
        test.use({ colorScheme: scheme, reducedMotion: 'reduce' });

        for (const path of PAGES) {
            test(`${path} passes axe`, async ({ page }) => {
                await open(page, path);
                expect(await violations(page)).toEqual([]);
            });
        }
    });
}

test('pages log no errors to the console', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(error.message));
    page.on('console', (message) => {
        if (message.type() === 'error') errors.push(message.text());
    });

    for (const path of PAGES.filter((p) => p !== '/no-such-page')) {
        await open(page, path);
    }

    expect(errors).toEqual([]);
});

test('the skip link is the first stop and jumps to the content', async ({ page, isMobile }) => {
    test.skip(isMobile, 'Keyboard navigation');
    await open(page, '/');
    await page.keyboard.press('Tab');
    const skip = page.locator('a.skip');
    await expect(skip).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/#main$/);
});

test('printing leaves out the interface', async ({ page }) => {
    await open(page, '/work/euisis');
    await page.emulateMedia({ media: 'print' });
    await expect(page.locator('header.nav')).toBeHidden();
    await expect(page.locator('h1')).toBeVisible();
    await expect(page.locator('.cs-block').first()).toBeVisible();
});

test('the Content Security Policy blocks nothing the site itself does', async ({ page, isMobile }) => {
    await page.addInitScript(() => {
        const found: string[] = [];
        (window as unknown as { __csp: string[] }).__csp = found;
        document.addEventListener('securitypolicyviolation', (event) => found.push(`${event.violatedDirective} ${event.blockedURI}`));
    });
    const violationsSoFar = () => page.evaluate(() => (window as unknown as { __csp: string[] }).__csp);

    await open(page, '/');
    expect(await page.evaluate(() => document.querySelector('meta[http-equiv]'))).toBeNull();
    if (!isMobile) {
        await page.keyboard.press('Control+k');
        await expect(page.getByRole('dialog').getByRole('option').first()).toBeVisible();
        await page.keyboard.press('Escape');
    }
    await page.locator('.work .card').first().click();
    await expect(page).toHaveURL(/\/work\//);
    expect(await violationsSoFar()).toEqual([]);
    await page.goBack();
    await expect(page).toHaveURL(/\/$/);
    expect(await violationsSoFar()).toEqual([]);

    for (const path of ['/am', '/notes/storing-ethiopian-dates', '/now', '/colophon', '/no-such-page']) {
        await open(page, path);
        expect(await violationsSoFar(), path).toEqual([]);
    }
});
