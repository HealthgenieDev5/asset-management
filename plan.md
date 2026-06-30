# Plan: Vehicle Compliance Tab

## Goal
Add a `vehicle-compliance` tab visible only on vehicle assets (`$asset->isVehicle()`).
Three cards — PUC, Fitness Certificate, Road Tax — each with inline-editable fields and a dedicated document upload.

---

## Files to Change

### 1. `app/Models/AssetDocument.php`
Add two missing document type labels to the `getDocumentTypeLabelAttribute()` match block:
```php
'fitness_copy'  => 'Fitness Certificate',
'road_tax_copy' => 'Road Tax Copy',
```

### 2. `app/Http/Controllers/AssetDocumentController.php`
Add the same two types to `ALLOWED_TYPES` constant so the upload endpoint accepts them:
```php
'fitness_copy'  => 'Fitness Certificate',
'road_tax_copy' => 'Road Tax Copy',
```

### 3. `resources/views/assets/show.blade.php`
Conditionally insert the tab into the `$tabs` array after `insurance`:
```php
if ($asset->isVehicle()) {
    $tabs['vehicle-compliance'] = ['label' => 'Compliance', 'icon' => 'clipboard-document-check'];
}
```
No other change needed — the foreach loop handles rendering automatically.

### 4. `resources/views/assets/tabs/vehicle-compliance.blade.php` ← NEW FILE
Three-column card layout. Each card is self-contained.

---

## Card Structure (repeated × 3)

Each card has two zones:

**Left zone — inline editable fields (same pencil pattern as services/complaints view modal):**
- Expiry Date (flatpickr, patches `puc_expiry_date` / `fitness_expiry_date` / `road_tax_expiry_date`)
- Reminder Before Days (number input, patches `puc_reminder_before_days` / `fitness_reminder_before_days` / `road_tax_reminder_before_days`)
- Status badge: computed from expiry date — Expired (red) / Expiring Soon ≤30 days (amber) / Valid (green) / Not Set (zinc)

**Right zone — document upload (same FilePond pattern as complaints view modal aside):**
- Single-file FilePond uploader
- Uses existing route `assets.documents.store` with `document_type = 'puc_copy'` / `'fitness_copy'` / `'road_tax_copy'`
- Delete uses existing route `assets.documents.destroy`
- Revert uses existing route `assets.documents.revert`
- View/download button if doc exists

**Patch endpoint:** Reuses existing `AssetController::patchField` — all six vehicle compliance fields are already in its `$allowed` map and vehicle-only guard.

**No new routes, no new migrations, no new controllers needed.**

---

## Detailed Card Layout

```
┌─────────────────────────────────────────────────────────────┐
│  🔵 PUC Certificate          [STATUS BADGE]                 │
│─────────────────────────────────────────────────────────────│
│  Left (flex-1)                  │  Right (w-52, border-l)   │
│                                 │                           │
│  Expiry Date       [✎]         │  [FilePond upload zone]   │
│  dd MMM YYYY                   │                           │
│                                 │  ── or if doc exists ──  │
│  Reminder          [✎]         │  📄 filename.pdf  👁 ⬇ 🗑 │
│  30 days before expiry         │                           │
└─────────────────────────────────────────────────────────────┘
```

Three cards stacked vertically (not grid) so each card has enough horizontal room for both zones at all screen widths.

---

## Status Badge Logic (PHP)

```php
// per card, computed in @php block
$expiry = $asset->puc_expiry_date; // Carbon or null
if (!$expiry) {
    $badge = ['label' => 'Not Set',       'cls' => 'bg-zinc-400/10 text-zinc-400 border-zinc-400/20'];
} elseif ($expiry->isPast()) {
    $badge = ['label' => 'Expired',       'cls' => 'bg-red-400/10 text-red-400 border-red-400/20'];
} elseif ($expiry->diffInDays(now()) <= 30) {
    $badge = ['label' => 'Expiring Soon', 'cls' => 'bg-amber-400/10 text-amber-400 border-amber-400/20'];
} else {
    $badge = ['label' => 'Valid',         'cls' => 'bg-green-400/10 text-green-400 border-green-400/20'];
}
```

---

## Alpine Data (per card, x-data on each card div)

```js
{
    expiry:   '<formatted date or empty>',
    reminder: '<days or empty>',
    async patch(field, value) {
        const fd = new URLSearchParams({ _method: 'PATCH', field, value: value ?? '' });
        const r = await fetch('<patchFieldRoute>', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': '...', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
            body: fd,
        });
        if (!r.ok) { toastr.error('Save failed.'); return false; }
        toastr.success('Updated.');
        return true;
    }
}
```

---

## Implementation Order

1. `AssetDocument.php` — add labels (2 lines)
2. `AssetDocumentController.php` — add to ALLOWED_TYPES (2 lines)
3. `show.blade.php` — conditional tab entry (3 lines)
4. `vehicle-compliance.blade.php` — new blade file with three cards
