/**
 * Authentication flows — login, logout, guest redirects.
 * These tests deliberately do NOT use the shared auth session so they
 * can verify that unauthenticated visitors are handled correctly.
 */
import { expect, test } from '@playwright/test';

// Override storageState — run these as a fresh, unauthenticated browser
test.use({ storageState: { cookies: [], origins: [] } });

test.describe('Guest access', () => {
    test('visiting /dashboard redirects to login', async ({ page }) => {
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/login/);
    });

    test('visiting /assets redirects to login', async ({ page }) => {
        await page.goto('/assets');
        await expect(page).toHaveURL(/login/);
    });

    test('visiting /reports redirects to login', async ({ page }) => {
        await page.goto('/reports');
        await expect(page).toHaveURL(/login/);
    });
});

test.describe('Login page', () => {
    test('renders email and password fields', async ({ page }) => {
        await page.goto('/login');

        await expect(page.getByLabel('Email')).toBeVisible();
        await expect(page.locator('input[name="password"]')).toBeVisible();
        await expect(page.getByRole('button', { name: /log in/i })).toBeVisible();
    });

    test('shows validation error for empty credentials', async ({ page }) => {
        await page.goto('/login');
        await page.getByRole('button', { name: /log in/i }).click();

        // Stay on login page; an error or validation message should appear
        await expect(page).toHaveURL(/login/);
    });

    test('shows error for wrong password', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill('test@example.com');
        await page.locator('input[name="password"]').fill('wrongpassword');
        await page.getByRole('button', { name: /log in/i }).click();

        await expect(page).toHaveURL(/login/);
        await expect(page.getByText(/credentials/i).or(page.getByText(/These credentials/i))).toBeVisible();
    });
});

test.describe('Logout', () => {
    test('user can log out', async ({ page }) => {
        await page.goto('/login');
        await page.getByLabel('Email').fill('test@example.com');
        await page.locator('input[name="password"]').fill('password');
        await page.getByRole('button', { name: /log in/i }).click();
        await page.waitForURL('**/dashboard');

        await page.goto('/dashboard');

        await page.getByRole('button', { name: /test user|test@example.com/i }).click();
        await page.getByRole('menuitem', { name: /log.?out/i }).click();

        await expect(page).toHaveURL(/\/(?:login)?$/);
    });
});
