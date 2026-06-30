/**
 * Dashboard — stat cards, navigation, quick links.
 * All tests run with the shared authenticated session.
 */
import { expect, test } from '@playwright/test';

test.describe('Dashboard', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/dashboard');
    });

    test('page title contains "Dashboard"', async ({ page }) => {
        await expect(page).toHaveTitle(/Dashboard/i);
    });

    test('renders stat cards (total assets, active, etc.)', async ({ page }) => {
        // Cards are present — exact text depends on seeded data
        const cards = page.locator('[class*="rounded"]').filter({ hasText: /assets|active|expired|vendor/i });
        await expect(cards.first()).toBeVisible();
    });

    test('navigation sidebar contains Assets link', async ({ page }) => {
        await expect(page.getByRole('link', { name: /assets/i }).first()).toBeVisible();
    });

    test('navigation sidebar contains Reports link', async ({ page }) => {
        await expect(page.getByRole('link', { name: /reports/i }).first()).toBeVisible();
    });

    test('navigation sidebar contains Vendors link', async ({ page }) => {
        await expect(page.getByRole('link', { name: /vendors/i }).first()).toBeVisible();
    });
});
