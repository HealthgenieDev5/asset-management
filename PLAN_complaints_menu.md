# Plan: Add "Complaints" Menu Item (Global)

## Goal
Add a **Complaints** sidebar menu item positioned between **Asset Register** and **Categories**, so any authenticated user can log/view complaints about any asset without drilling into that asset's detail page first.

## Current State
- Complaints (`AssetComplaint`) only exist nested under an asset (`assets/{asset}/complaints/...`), surfaced via the "Complaints" tab on `assets/{asset}` (`resources/views/assets/tabs/complaints.blade.php`).
- No standalone list/index page or route exists for complaints across all assets.
- Sidebar items are defined in [sidebar.blade.php](resources/views/layouts/app/sidebar.blade.php#L55-L59):
  ```php
  $assetItems = [
      $item('clipboard-document-list', 'Asset Register',  'assets.index',               'assets.*'),
      $item('tag',                     'Categories',       'asset-categories.index',     'asset-categories.*'),
      $item('queue-list',              'Subcategories',    'asset-subcategories.index',  'asset-subcategories.*'),
  ];
  ```
- No `@can`/role gating exists on any menu item today — all items are visible to all authenticated users, so "anyone can add complaints" requires no new permission logic, just a new route + view.

## Approach
Build a minimal **global Complaints index** page (list all complaints across assets, with filters) plus a **create form** that lets the user pick which asset the complaint is about. Reuse the existing `AssetComplaint` model and as much of the existing `_complaint-form.blade.php` partial / styling as practical, but the global form needs an asset picker (the nested form currently assumes `$asset` is already known).

### 1. Routes (`routes/web.php`)
Add a new top-level resource-ish set, **not** nested under `assets/{asset}`, e.g.:
```php
Route::get('complaints', [ComplaintController::class, 'index'])->name('complaints.index');
Route::post('complaints', [ComplaintController::class, 'store'])->name('complaints.store');
```
Place near existing complaint routes (after line 80, before "Complaint Escalation Rules"). Keep existing `assets.complaints.*` nested routes untouched (still used by the per-asset tab) — the new controller can delegate to/share logic with `AssetComplaintController` or simply reuse the `AssetComplaint` model directly.

### 2. Controller
New `App\Http\Controllers\ComplaintController`:
- `index()` — eager-load `asset`, `category`, `subcategory`; support optional filters (status, priority, asset search) via query params; paginate.
- `store(Request $request)` — validate `asset_id` (`required|exists:assets,id`) plus the same fields as `AssetComplaintController::rules()`; copy `location`/`department`/`asset_category_id`/`asset_subcategory_id` from the selected asset (mirror lines 20-25 of `AssetComplaintController`); set `created_by`; redirect to `complaints.index`.
- Reuse `triggerEscalation()` logic — either extract it to a shared trait/static helper, or call into `AssetComplaintController` — avoid duplicating the escalation email logic.

### 3. View
New `resources/views/complaints/index.blade.php`:
- Header + "Log Complaint" button opening a modal (same `x-modal` pattern as `complaints.blade.php`).
- Modal form: asset picker (`<select>` or searchable dropdown populated from `Asset::query()->orderBy('asset_code')`) + same fields as `_complaint-form.blade.php` (title, description, reporter name/email/phone, priority, remarks, optional videos).
- Table/grid of complaints across all assets — columns: Asset (code/name, link to `assets.show` with `complaints` tab), Title, Priority, Status, Reported date, Reporter. Link each row's "View" to `assets.show` route with `tab=complaints` (existing per-asset modals already handle full detail/edit/delete — no need to rebuild that here) OR build lightweight read-only view modal if cross-asset deep view is wanted.
- Consider extracting the shared fields markup from `_complaint-form.blade.php` into a reusable partial parameterized by whether an asset selector is shown, to avoid duplicating ~15 form fields.

### 4. Sidebar menu entry
Edit [sidebar.blade.php:55-59](resources/views/layouts/app/sidebar.blade.php#L55-L59):
```php
$assetItems = [
    $item('clipboard-document-list', 'Asset Register',  'assets.index',               'assets.*'),
    $item('exclamation-triangle',    'Complaints',       'complaints.index',           'complaints.*'),
    $item('tag',                     'Categories',       'asset-categories.index',     'asset-categories.*'),
    $item('queue-list',              'Subcategories',    'asset-subcategories.index',  'asset-subcategories.*'),
];
```
No permission/role check needed — matches existing ungated behavior of all other menu items.

### 5. Open questions to confirm before building
- Should the global "Log Complaint" form let the user pick *any* asset, or only assets in their department/location?
- Should the global index support edit/delete/comment actions inline, or just link out to the asset's Complaints tab for management (simpler, less duplication)?
- Icon choice for the menu item (`exclamation-triangle` suggested, matches the existing "No Complaints" empty-state icon already used in `complaints.blade.php`).

## Files to touch
- `routes/web.php` — add `complaints.index` / `complaints.store` routes
- `app/Http/Controllers/ComplaintController.php` — new
- `resources/views/complaints/index.blade.php` — new
- `resources/views/layouts/app/sidebar.blade.php` — insert menu item (lines 55-59)
- Possibly extract `resources/views/assets/tabs/_complaint-form.blade.php` fields into a shared partial reused by both the per-asset and global forms
