# AGENTS.md

## Build / Lint / Test

```bash
# Development (server + queue + logs + Vite concurrently)
composer dev

# Frontend only
npm run dev
npm run build

# Lint (Laravel Pint — apply fixes)
composer lint
./vendor/bin/pint

# Check lint without modifying
composer lint:check

# Run full test suite
composer test
php artisan test

# Run a single test class or method
php artisan test --filter=DashboardTest
php artisan test --filter=test_guests_are_redirected_to_the_login_page
php artisan test tests/Feature/DashboardTest.php

# Database
php artisan migrate
php artisan migrate:fresh --seed
php artisan db:seed --class=AssetCategorySeeder

# Reminder emails (dry-run safe)
php artisan assets:send-reminders --dry-run
php artisan assets:send-reminders --days=7
```

## Code Style

Laravel Pint enforces PSR-12 with Laravel presets (`pint.json` -> `"preset": "laravel"`). Run `composer lint` before committing.

- **Indentation**: 4 spaces. No tabs.
- **Braces**: Allman for classes/methods (opening brace on new line). K&R for control structures (`if`, `foreach`, etc. — opening brace on same line).
- **Arrays**: Multi-line arrays use trailing commas.
- **Line length**: Soft ~120 char limit, no hard wrapping.
- **Named arguments**: Use `absolute: false` style when needed (e.g., `route('dashboard', absolute: false)`).

## Naming Conventions

| Element | Convention | Example |
|---|---|---|
| Classes | PascalCase | `AssetController`, `AssetAmcContract` |
| Methods / variables | camelCase | `isVehicle()`, `$validated` |
| Constants | UPPER_SNAKE | `ALLOWED_TYPES` |
| DB tables | snake_case plural | `assets`, `asset_amc_contracts` |
| DB columns / foreign keys | snake_case | `asset_category_id`, `created_by` |
| Route URIs | kebab-case | `/asset-categories`, `/complaint-escalation-rules` |
| Route names | dot.notation | `assets.show`, `reports.asset-register.export` |
| Blade views | kebab-case | `assets._form`, `asset-subcategories.index` |

## Imports

Group by source (no blank lines between groups): App models first, then Illuminate/Framework, then vendor packages. No inline fully-qualified class names if a `use` statement exists (exception: short closures in routes).

```php
use App\Models\Asset;
use App\Models\AssetCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
```

## Types

- **No `declare(strict_types=1)`** — this codebase does not use it.
- Add return types on all methods (`: void`, `: array`, `: int`, etc.).
- Add parameter type hints on all method parameters.
- Property types: NOT used on `$fillable`/`$casts`. Use traditional `protected $fillable = [...]` and `protected function casts(): array`.
- Nullable types: use `?` syntax (`?int`, `?string`).
- For model relationships: import and use `BelongsTo`, `HasMany`, `MorphTo` return types.

## Models / Eloquent

- Use `$fillable` (never `$guarded`). Small arrays single-line; large ones multi-line with trailing commas.
- Casts use the method style: `protected function casts(): array` (NOT the old `$casts` property).
- Soft deletes via `use SoftDeletes;` trait on the Asset model and related entities.
- Cascade soft-deletes in `booted()` hook using `static::deleting(function (Model $model) { ... })`.
- Scope pattern: `public function scopeActive($query)` — `$query` parameter typically untyped.
- Accessor pattern: `public function getStatusLabelAttribute(): string`.
- Relationship foreign key explicit when non-standard: `$this->belongsTo(AssetCategory::class, 'asset_category_id')`.
- Polymorphic `HasMany` with `where('documentable_type', self::class)` constraint.
- Methods like `isExpired()`, `daysUntilExpiry(): ?int` for domain logic on models.

## Controllers / Validation

- Thin controllers: accept request, validate inline (`$request->validate([...])`), call model methods, redirect.
- Use **array syntax** for validation rules: `['required', 'string', 'max:255']` (not pipe syntax).
- Authorization via `abort_if($condition, 403)`.
- Flash messages: `->with('success', '...')` / `->with('error', '...')`.
- No try/catch — let the framework handle exceptions.
- Optional filters in listing: `->when($request->search, fn($q, $s) => $q->where(...))`.
- Eager load relationships: `Asset::with(['category', 'subcategory'])`.
- Paginate with `->paginate(15)->withQueryString()`.
- Return `view('name', compact('var1', 'var2'))` or `view('name', ['key' => $val])`.

## Routes

- All authenticated routes require `['auth', 'verified']` middleware group.
- Use `Route::resource()` for CRUD resources (`'asset-categories'`, `'assets'`).
- Nest child resources explicitly (not `->shallow()`): `Route::post('assets/{asset}/amc', [...])`.
- Export/report routes paired: view at `reports/name` and export at `reports/name/export`.
- Always use explicit `->name()` for non-resourceful routes.
- API helpers (auth-only, no verified) for dependent dropdowns: `/api/subcategories?category_id=`.
- Console commands in `routes/console.php` via `Schedule::command(...)`.

## Tests (PHPUnit)

- Use `RefreshDatabase` trait on every test class.
- `use Tests\TestCase` as base (not `PHPUnit\Framework\TestCase` directly).
- Test method naming: snake_case with `test_` prefix, return type `: void`.
- Factory usage: `User::factory()->create()`, `User::factory()->withTwoFactor()->create()`.
- Assertion patterns: `$response->assertOk()`, `assertRedirect()`, `assertSessionHasNoErrors()`, `assertGuest()`.
- Livewire tests: `Livewire::test('pages::settings.profile')->set('name', '...')->call('updateProfileInfo')->assertHasNoErrors()`.
- Use `$this->skipUnlessFortifyHas(Features::twoFactorAuthentication())` for feature-gated tests.

## UI / Blade

- Use Flux components (`flux:button`, `flux:input`, `flux:select`, `flux:modal`) for most UI.
- Custom floating-label inputs: use Tailwind peer pattern with `placeholder=" "`.
- `@props(['name', 'value' => '', 'label' => '', ...])` at top of component files.
- View partials prefixed with underscore: `_form.blade.php`.
- Alpine.js component registered via `Alpine.data('name', () => ({...}))` in `alpine:init` event.
- flatpickr date pickers initialized in `alpine:initialized` event (not `DOMContentLoaded`).

## Documents

- Polymorphic: `AssetDocument` with `documentable_type` / `documentable_id`.
- Document types are plain strings (e.g. `'warranty_card'`, `'extended_warranty_bill'`).
- Files stored in `storage/app/` via `Storage::url()`.

## Reports

- `ReportController` has paired view + CSV export methods (16 pairs).
- CSV exports prepend UTF-8 BOM (`\xEF\xBB\xBF`) via `fwrite($handle, "\xEF\xBB\xBF")`.
- All report routes are GET-only, no POST/PUT/DELETE.

## Reminder Emails

- Sent via `php artisan assets:send-reminders`.
- Scheduled in `routes/console.php` at `03:30 Asia/Kolkata`, `->withoutOverlapping()->runInBackground()`.
- Compares expiry dates against `now()->addDays($reminderDays)`. Sends `AssetExpiryReminderMail`.
- Dry-run flag: `--dry-run` outputs what would be sent without sending.
