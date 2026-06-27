# Functional Test Report

Date: 2026-06-27
Project: `d:\LOCALHOST\asset-management`

## Scope

I performed a functional smoke assessment of the Laravel asset-management app using the local workspace. PHP is provided by Laravel Herd, so backend checks were run with Herd PHP explicitly:

`C:\Users\Developer-2\.config\herd\bin\php84\php.exe`

## Commands Run

| Check | Result | Notes |
|---|---:|---|
| Herd PHP | Pass | PHP 8.4.22 runs from Herd's `php84` binary. |
| `php artisan route:list` | Pass | Route registration completed and showed 200 routes. |
| `php artisan test` | Fail | 33 tests discovered; 1 passed, 32 errored during migration setup. |
| `vendor/bin/pint --parallel --test` | Fail | 51 files need Pint formatting fixes. |
| `npm run build` | Pass | Vite production build completed successfully. Assets were written under `public/build`. |
| Direct shell `php` lookup | Needs PATH setup | `php` is not on the default shell PATH, but Herd PHP works by explicit path. |
| Static route/view scan | Completed | Checked route helper references, controller view targets, report routes, tab links, and missing imported app classes. |

## What Is Working

- Frontend build pipeline works. `vite build` completed with 32 modules transformed and produced CSS/JS/font assets.
- Herd PHP 8.4.22 can run the app when invoked by explicit path.
- Laravel route registration works. `php artisan route:list` completed and showed 200 routes.
- Core route declarations for the main app areas are present in `routes/web.php`: dashboard, assets, categories, subcategories, vendors, complaints, reminders, and reports.
- Existing automated tests cover starter-kit flows: home page, dashboard auth redirect, login/logout, password reset/confirmation, email verification, two-factor challenge, and settings/profile Livewire flows.
- Static view target checks did not find missing normal Blade views for current controllers. Namespaced `pages::...` and dynamic tab includes were excluded as expected framework/dynamic cases.
- Current report export routes exist for asset register, purchase bills, warranty, AMC, insurance, PUC, fitness, road tax, inspection, certification, service due/history, maintenance cost, vehicle depreciation, and vendor performance.

## What Is Going Wrong

### 1. PHPUnit is failing during migration setup

`php artisan test` ran with Herd PHP and failed before most tests could execute.

Result:
- 33 tests discovered
- 1 passed
- 32 errors

Root error:

`database/migrations/2026_06_19_174000_add_maintenance_schedule_to_smart_reminders_type_enum.php` uses a MySQL-specific statement:

`ALTER TABLE asset_smart_reminders MODIFY COLUMN reminder_type ENUM(...) NOT NULL`

The test suite uses SQLite in-memory from `phpunit.xml`, and SQLite does not support `MODIFY COLUMN` or MySQL `ENUM`, so every test using `RefreshDatabase` fails during migration.

Impact: the current automated test suite is not usable until this migration is made test-database compatible or tests run against MySQL.

### 2. Pint style check is failing

`vendor/bin/pint --parallel --test` ran with Herd PHP and reported 51 files needing formatting changes.

Impact: `composer test` / CI-style checks will fail even after the migration issue is fixed, unless Pint is applied or the style violations are intentionally handled.

### 3. Legacy extended-warranty code is broken/orphaned

Evidence:
- `app/Http/Controllers/AssetExtendedWarrantyController.php:8` imports missing `App\Models\AssetExtendedWarranty`.
- `app/Http/Controllers/AssetExtendedWarrantyController.php:24` calls `AssetExtendedWarranty::create(...)`.
- `database/migrations/2026_06_19_210000_drop_asset_extended_warranties_table.php` intentionally drops `asset_extended_warranties`.
- `resources/views/assets/tabs/ext-warranty.blade.php:3` expects `$asset->extendedWarranties`, but `App\Models\Asset` has no such relationship.

Impact: any legacy `/assets/{asset}/ext-warranty...` flow is expected to fail if reached.

### 4. Extended-warranty tab redirects to a non-existent visible tab

Evidence:
- `AssetExtendedWarrantyController` redirects to `tab=ext-warranty` at lines 28, 44, and 59.
- `resources/views/assets/show.blade.php:33` defines visible tabs, but only includes current warranty-related tab key `warranty`; it does not include `ext-warranty`.

Impact: old extended-warranty redirects land on the asset detail page with no matching tab content.

### 5. Extended-warranty report is incomplete

Evidence:
- `resources/views/reports/extended-warranty-expiry.blade.php:16` references `reports.extended-warranty-expiry.export`.
- No matching route exists in `routes/web.php`.
- `resources/views/reports/index.blade.php:22` labels "Extended Warranty" but links to `reports.warranty-expiry`.

Impact: the separate extended-warranty report/export is not actually wired. This also conflicts with the documented expectation that reports have paired view/export routes.

### 6. `asset-reminders` exposes routes that the controller cannot handle

Evidence:
- `routes/web.php:168` uses `Route::resource('asset-reminders', AssetReminderController::class)`.
- `app/Http/Controllers/AssetReminderController.php:12` only implements `index`.
- `php artisan route:list` confirms `asset-reminders.create`, `store`, `show`, `edit`, `update`, and `destroy` are registered.

Impact: generated `create`, `store`, `show`, `edit`, `update`, and `destroy` URLs/actions would fail if requested. The route should likely be `Route::get(...)` or `Route::resource(...)->only(['index'])`.

### 7. Expiry tracker part-warranty links use the wrong asset tab key

Evidence:
- `app/Http/Controllers/AssetReminderController.php:129` emits tab key `servicing`.
- `resources/views/assets/show.blade.php:38` defines the actual tab key as `services`.

Impact: clicking "View" for part-warranty reminder rows opens an asset URL with `tab=servicing`, which has no matching tab panel.

### 8. Automated tests do not cover the core asset-management features

Existing tests are mostly Laravel starter-kit/auth/settings tests. I did not find feature tests for:

- Asset CRUD and inline patching
- Category/subcategory CRUD
- Vendor CRUD/export
- AMC, warranty, insurance, service, parts, schedules, meter logs, complaints
- Document upload/delete/revert
- Reminder tracker behavior
- Report pages and CSV/XLSX exports
- Scheduled reminder email command

Impact: regressions in the main business workflows can pass the current test suite.

## Recommended Fix Order

1. Fix `2026_06_19_174000_add_maintenance_schedule_to_smart_reminders_type_enum.php` so migrations can run under SQLite tests, or configure tests to use MySQL.
2. Run Pint or address the 51 style failures, then rerun `vendor/bin/pint --parallel --test`.
3. Remove or migrate the legacy `AssetExtendedWarrantyController`, `assets/{asset}/ext-warranty` routes, and `assets.tabs.ext-warranty` view, or rebuild them on top of `AssetWarranty` if the feature is still required.
4. Add or remove the missing extended-warranty report route/export so the report index and report views are consistent.
5. Change `asset-reminders` routing to index-only unless CRUD is actually planned.
6. Change reminder part-warranty tab target from `servicing` to `services`.
7. Add functional tests for the core asset workflows and report exports before making more UI changes.
