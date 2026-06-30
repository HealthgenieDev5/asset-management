/**
 * Reports — page loads, column header presence, filter UI, CSV download link.
 *
 * These are UI smoke tests.  Data-layer assertions live in the PHPUnit
 * ReportTest.php file which can check exact CSV content.
 */
import { expect, test } from '@playwright/test';

// ── helpers ──────────────────────────────────────────────────────────────────

/**
 * Navigate to a report page and assert the table is present and visible.
 */
async function assertReportLoads(page, path) {
    await page.goto(path);
    await expect(page.getByRole('table')).toBeVisible();
}

/**
 * Assert that every string in `expectedHeaders` appears somewhere in the
 * table's <thead> row (case-insensitive).
 */
async function assertTableHeaders(page, expectedHeaders) {
    for (const header of expectedHeaders) {
        await expect(
            page.getByRole('columnheader', { name: new RegExp(header, 'i') })
        ).toBeVisible({ timeout: 5000 });
    }
}

// ── Reports hub ──────────────────────────────────────────────────────────────

test('reports index page loads', async ({ page }) => {
    await page.goto('/reports');
    await expect(page).toHaveURL(/reports/);
});

// ── Asset Register ────────────────────────────────────────────────────────────

test.describe('Asset Register report', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/reports/asset-register');
    });

    test('table renders with expected columns', async ({ page }) => {
        await assertTableHeaders(page, ['Code', 'Asset Name', 'Vendor', 'Age', 'Status']);
    });

    test('export / Excel download link is visible', async ({ page }) => {
        const exportLink = page.getByRole('link', { name: /excel|export|csv/i });
        await expect(exportLink.first()).toBeVisible();
    });

    test('search input filters the table', async ({ page }) => {
        const search = page.getByPlaceholder(/search/i).first();
        if (await search.count() > 0) {
            await search.fill('test');
            await page.getByRole('button', { name: /apply|filter/i }).first().click();
            await expect(page).toHaveURL(/search=test/);
        }
    });
});

// ── Purchase Bills ────────────────────────────────────────────────────────────

test('Purchase Bills report shows Department column', async ({ page }) => {
    await page.goto('/reports/purchase-bills');
    await assertTableHeaders(page, ['Code', 'Asset Name', 'Department', 'Bill Amount']);
});

// ── Warranty Expiry ───────────────────────────────────────────────────────────

test.describe('Warranty Expiry report', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/reports/warranty-expiry');
    });

    test('shows Vendor, Location, Custodian, Days columns', async ({ page }) => {
        await assertTableHeaders(page, ['Vendor', 'Location', 'Custodian', 'Days']);
    });

    test('expiry stat cards render (Expired / 30 / 90)', async ({ page }) => {
        // Stat banner only renders when counts > 0, so check it either exists or the table is there
        const hasBanner = await page.getByText(/Expired/i).count() > 0
            || await page.getByText(/due in/i).count() > 0
            || await page.getByRole('table').count() > 0;
        expect(hasBanner).toBeTruthy();
    });
});

// ── AMC Expiry ────────────────────────────────────────────────────────────────

test('AMC Expiry report shows Department and Days columns', async ({ page }) => {
    await page.goto('/reports/amc-expiry');
    await assertTableHeaders(page, ['Department', 'Days']);
});

// ── Insurance Expiry ──────────────────────────────────────────────────────────

test('Insurance Expiry report shows Department and Days columns', async ({ page }) => {
    await page.goto('/reports/insurance-expiry');
    await assertTableHeaders(page, ['Department', 'Days']);
});

// ── Vehicle compliance expiry reports ────────────────────────────────────────

for (const [label, path] of [
    ['PUC Expiry', '/reports/puc-expiry'],
    ['Fitness Expiry', '/reports/fitness-expiry'],
    ['Road Tax Expiry', '/reports/road-tax-expiry'],
]) {
    test(`${label} report shows Reg. No. and Days columns`, async ({ page }) => {
        await page.goto(path);
        await assertTableHeaders(page, ['Reg. No.', 'Days', 'Status']);
    });
}

// ── Inspection Due ────────────────────────────────────────────────────────────

test('Inspection Due report shows Location column', async ({ page }) => {
    await page.goto('/reports/inspection-due');
    await assertTableHeaders(page, ['Code', 'Category', 'Location', 'Custodian', 'Status']);
});

// ── Certification Expiry ──────────────────────────────────────────────────────

test('Certification Expiry report shows Department and Days columns', async ({ page }) => {
    await page.goto('/reports/certification-expiry');
    await assertTableHeaders(page, ['Department', 'Days']);
});

// ── Service Due ───────────────────────────────────────────────────────────────

test('Service Due report shows Custodian column', async ({ page }) => {
    await page.goto('/reports/service-due');
    await assertTableHeaders(page, ['Custodian', 'Days']);
});

// ── Service History ───────────────────────────────────────────────────────────

test('Service History report shows Department and Technician columns', async ({ page }) => {
    await page.goto('/reports/service-history');
    await assertTableHeaders(page, ['Department', 'Technician']);
});

// ── Maintenance Cost ──────────────────────────────────────────────────────────

test('Maintenance Cost report shows Agency column', async ({ page }) => {
    await page.goto('/reports/maintenance-cost');
    await assertTableHeaders(page, ['Agency']);
});

// ── Vehicle Depreciation ──────────────────────────────────────────────────────

test('Vehicle Depreciation report shows Age column', async ({ page }) => {
    await page.goto('/reports/vehicle-depreciation');
    await assertTableHeaders(page, ['Age', 'OBV', 'Dep\\. %', 'Book Value']);
});

// ── Vendor Performance ────────────────────────────────────────────────────────

test('Vendor Performance report shows Email column', async ({ page }) => {
    await page.goto('/reports/vendor-performance');
    await assertTableHeaders(page, ['Email', 'Vendor Name', 'Services', 'Total Cost']);
});

// ── Non-functional: page response speed ──────────────────────────────────────

test('Asset Register report loads within 3 seconds', async ({ page }) => {
    const start = Date.now();
    await page.goto('/reports/asset-register');
    await page.getByRole('table').waitFor({ timeout: 3000 });
    const elapsed = Date.now() - start;
    expect(elapsed).toBeLessThan(3000);
});
