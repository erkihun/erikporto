import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

test.describe('command palette', () => {
    test.beforeEach(({ isMobile }) => {
        test.skip(isMobile, 'Keyboard shortcut and desktop trigger');
    });

    test('opens with Ctrl+K, filters, and opens a case study with Enter', async ({ page }) => {
        await page.goto('/', { waitUntil: 'networkidle' });

        await page.keyboard.press('Control+k');
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible();
        const input = page.getByRole('dialog').getByRole('combobox');
        await expect(input).toBeFocused();

        // Case studies load on first open.
        await expect(page.getByRole('dialog').getByRole('option', { name: /EUISIS/ })).toBeVisible();
        const { violations } = await new AxeBuilder({ page }).include('dialog').analyze();
        expect(violations.map((v) => v.id)).toEqual([]);

        await input.fill('euis');
        await expect(page.getByRole('dialog').getByRole('option')).toHaveCount(1);
        await expect(page.getByRole('dialog').getByRole('option', { name: /EUISIS/ })).toHaveAttribute('aria-selected', 'true');
        await expect(input).toHaveAttribute('aria-activedescendant', /.+/);

        await page.keyboard.press('Enter');
        await expect(page).toHaveURL(/\/work\/euisis$/);
        await expect(dialog).toBeHidden();
        await expect(page.getByRole('heading', { level: 1 })).toHaveText('EUISIS');
    });

    test('moves with the arrow keys and closes with Escape, returning focus', async ({ page }) => {
        await page.goto('/', { waitUntil: 'networkidle' });
        const trigger = page.locator('.palette-btn');
        await trigger.click();

        const options = page.getByRole('dialog').getByRole('option');
        // Wait for the case studies, so the list no longer grows under the arrow keys.
        await expect(page.getByRole('dialog').getByRole('option', { name: /EUISIS/ })).toBeVisible();
        await expect(options.first()).toHaveAttribute('aria-selected', 'true');
        await page.keyboard.press('ArrowDown');
        await expect(options.nth(1)).toHaveAttribute('aria-selected', 'true');
        await page.keyboard.press('ArrowUp');
        await page.keyboard.press('ArrowUp');
        await expect(options.last()).toHaveAttribute('aria-selected', 'true');

        await page.keyboard.press('Escape');
        await expect(page.getByRole('dialog')).toBeHidden();
        await expect(trigger).toBeFocused();
    });

    test('says when nothing matches', async ({ page }) => {
        await page.goto('/am', { waitUntil: 'networkidle' });
        await page.keyboard.press('Control+k');
        await page.getByRole('dialog').getByRole('combobox').fill('zzzz');
        await expect(page.getByRole('dialog').getByRole('option')).toHaveCount(0);
        await expect(page.locator('.palette-empty', { hasText: 'zzzz' })).toBeVisible();
    });
});

test('a case study opens from its card and the back button returns home', async ({ page }) => {
    await page.goto('/', { waitUntil: 'networkidle' });
    await page.locator('.work .card').first().click();
    await expect(page).toHaveURL(/\/work\//);
    await expect(page.getByRole('heading', { level: 1 })).toBeVisible();

    await page.goBack();
    await expect(page).toHaveURL(/\/$/);
    await expect(page.locator('.work .card').first()).toBeVisible();
    // Only one card holds the shared transition names.
    const named = await page.locator('.card .shot').evaluateAll((els) => els.filter((el) => getComputedStyle(el).viewTransitionName === 'project-cover').length);
    expect(named).toBeLessThanOrEqual(1);
});
