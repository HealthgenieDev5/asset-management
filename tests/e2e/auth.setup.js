/**
 * Auth setup — logs in once and saves the session cookie so all
 * tests can reuse it without hitting the login form each time.
 *
 * Credentials come from environment variables or fall back to the
 * default seeded user.  Set APP_TEST_EMAIL / APP_TEST_PASSWORD in
 * your .env (or .env.testing) before running Playwright.
 */
import { expect, test as setup } from '@playwright/test';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const AUTH_FILE = path.join(__dirname, '.auth/user.json');

setup('authenticate', async ({ page }) => {
    const email    = process.env.APP_TEST_EMAIL    || 'test@example.com';
    const password = process.env.APP_TEST_PASSWORD || 'password';

    await page.goto('/login');

    await page.getByLabel('Email').fill(email);
    await page.locator('input[name="password"]').fill(password);
    await page.getByRole('button', { name: /log in/i }).click();

    // Wait until we land on the dashboard (not still on /login)
    await page.waitForURL('**/dashboard');
    await expect(page).toHaveURL(/dashboard/);

    await page.context().storageState({ path: AUTH_FILE });
});
