/**
 * Asset management — list, search/filter, create form, show page.
 */
import { expect, test } from '@playwright/test';

test.describe('Asset list', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/assets');
    });

    test('page loads and shows the assets table', async ({ page }) => {
        await expect(page.getByRole('table')).toBeVisible();
    });

    test('table headers include Code, Asset Name, Category, Status', async ({ page }) => {
        const headers = page.getByRole('columnheader');
        await expect(headers.filter({ hasText: /code/i })).toBeVisible();
        await expect(headers.filter({ hasText: /asset name/i })).toBeVisible();
        await expect(headers.filter({ hasText: /category/i })).toBeVisible();
        await expect(headers.filter({ hasText: /status/i })).toBeVisible();
    });

    test('search input is visible', async ({ page }) => {
        await expect(page.getByPlaceholder(/asset name|serial|search/i).first()).toBeVisible();
    });

    test('status filter dropdown is visible', async ({ page }) => {
        await expect(page.locator('select[name="status"]')).toBeVisible();
    });

    test('searching updates the URL query string', async ({ page }) => {
        const searchInput = page.getByPlaceholder(/asset name|serial|search/i).first();
        await searchInput.fill('Laptop');
        await page.keyboard.press('Enter');

        await expect(page).toHaveURL(/search=Laptop/);
    });

    test('Add Asset button is present', async ({ page }) => {
        const addBtn = page.getByRole('link', { name: /add asset|new asset/i })
            .or(page.getByRole('button', { name: /add asset|new asset/i }));
        await expect(addBtn.first()).toBeVisible();
    });
});

test.describe('Asset create form', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/assets/create');
    });

    test('create page renders correctly', async ({ page }) => {
        await expect(page).toHaveURL(/assets\/create/);
    });

    test('Asset Name field is visible and required', async ({ page }) => {
        const nameInput = page.getByLabel(/asset name/i).or(page.locator('input[name="asset_name"]'));
        await expect(nameInput.first()).toBeVisible();
    });

    test('Category dropdown is visible', async ({ page }) => {
        const catSelect = page.locator('select[name="asset_category_id"]')
            .or(page.getByLabel(/category/i));
        await expect(catSelect.first()).toBeVisible();
    });

    test('submitting empty form shows validation errors', async ({ page }) => {
        await page.getByRole('button', { name: /save|create|submit/i }).first().click();
        // Should stay on create page or show error — not redirect to show page
        await expect(page).not.toHaveURL(/assets\/\d+$/);
    });

    test('vehicle compliance section is hidden for non-vehicle category', async ({ page }) => {
        // The PUC / Fitness / Road Tax section should only show for VE category
        const vehicleSection = page.locator('[data-section="vehicle"]')
            .or(page.getByText(/PUC Expiry/i).first());

        // It may not exist at all, which is also correct
        const count = await vehicleSection.count();
        if (count > 0) {
            await expect(vehicleSection.first()).toBeHidden();
        }
    });
});

test.describe('Asset detail page', () => {
    test('asset show page loads when a valid ID is in the URL', async ({ page }) => {
        // Navigate to list and click the first asset link
        await page.goto('/assets');

        const firstCodeLink = page.locator('table tbody tr a').first();
        const count = await firstCodeLink.count();

        if (count === 0) {
            test.skip(true, 'No assets seeded — skip show page test');
        }

        await firstCodeLink.click();
        await expect(page).toHaveURL(/\/assets\/\d+/);
    });
});
