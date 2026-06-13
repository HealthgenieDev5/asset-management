# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

```bash
# Development (runs server + queue + logs + Vite concurrently)
composer dev

# Frontend only
npm run dev
npm run build

# Code style (Laravel Pint)
composer lint
./vendor/bin/pint

# Tests
composer test
php artisan test
php artisan test --filter=TestName

# Database
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed --class=AssetCategorySeeder

# Reminder emails (dry-run safe)
php artisan assets:send-reminders --dry-run
php artisan assets:send-reminders --days=7
```

## Architecture

**Stack:** Laravel 13 / PHP 8.3 · Livewire 4 + Flux 2 UI · Tailwind CSS 4 · Vite · MySQL · Auth via Laravel Fortify (passkeys + 2FA)

### Request flow

All authenticated routes require `auth` + `verified` middleware (routes/web.php). Controllers are thin — they handle form data, call model methods, and redirect. No service layer or repositories; logic lives in models and Eloquent scopes.

### Asset model is the core entity

`app/Models/Asset.php` owns ~40 fillable fields. Several related entities hang off it:

| Relationship | Model | Notes |
|---|---|---|
| `documents()` | `AssetDocument` | Polymorphic — also used by services, AMC, insurance |
| `extendedWarranties()` | `AssetExtendedWarranty` | One-to-many, typically one active |
| `amcContracts()` | `AssetAmcContract` | AMC periods |
| `insurancePolicies()` | `AssetInsurancePolicy` | Insurance coverage |
| `services()` | `AssetService` → `AssetServicePart` | Service history with parts |

Asset uses soft deletes; the `booted()` hook cascades deletion to all relations. `asset_code` is auto-generated as `{CATEGORY_CODE}-{sequence}` (includes trashed rows to prevent reuse).

Vehicle-specific fields (PUC, fitness, road tax, depreciation) are columns on the `assets` table, gated by `$asset->isVehicle()` which checks `category->code === 'VE'`.

### Forms: `_form.blade.php` pattern

Asset create/edit share `resources/views/assets/_form.blade.php`. The form uses Alpine.js (`assetForm` component) for:
- Category → subcategory dependent dropdown (fetches `/api/subcategories?category_id=`)
- `isVehicle` flag to show/hide vehicle compliance section
- flatpickr date pickers initialized in `alpine:initialized` (not `DOMContentLoaded`) — Flux rebuilds DOM during Alpine boot

Floating-label inputs use the Tailwind peer pattern: `placeholder=" "` (single space) + `peer-placeholder-shown` to animate label from centered to `top-2`.

### Document uploads

`AssetDocument` is polymorphic (`documentable_type` / `documentable_id`). Document types are plain strings (e.g. `warranty_card`, `extended_warranty_bill`). Files stored in `storage/app/` via `Storage::url()`.

### Reports

`ReportController` has 16 view methods and 16 matching CSV export methods. CSV exports prepend UTF-8 BOM (`\xEF\xBB\xBF`) for Excel compatibility. All reports are read-only GET routes.

### Reminder emails

`app/Console/Commands/SendAssetReminderEmails.php` queries all reminder-eligible records by comparing expiry dates against `now()->addDays($reminderDays)`. Sends `AssetExpiryReminderMail`. Runs via scheduler or manually.

### UI components

- `resources/views/components/date-picker.blade.php` — wraps flatpickr, accepts `name`, `value`, `label`, `minDate`, `maxDate`
- Flux components (`flux:button`, `flux:input`, `flux:select`, etc.) are used throughout except where custom floating-label HTML is needed
- `$labelSelCls` / `$labelCls` PHP variables defined at top of `_form.blade.php` hold shared Tailwind class strings for the two floating-label variants (inputs vs selects)

### Seeded data

`AssetCategorySeeder` seeds categories including `VE` (Vehicles) which triggers vehicle-specific form fields. `TestDataSeeder` creates sample assets for development.
