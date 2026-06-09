# Asset Register & Reminder System - Laravel Implementation Plan

## 1. Project Goal

Build a simple Laravel-based Asset Register & Reminder System for managing company assets, asset documents/images, purchase bill details, warranty details, AMC details, insurance details, expiry reminders, inspection/compliance dates, service history, parts replacement, and maintenance cost reports.

This system is not an ERP, procurement system, inventory system, accounting system, or vendor management system.

The main purpose is to ensure that the organization has a reliable fixed asset master and never misses important expiry dates such as warranty, insurance, PUC, AMC, service due dates, inspection dates, certification dates, calibration dates, and other renewal reminders.

---

## 2. Technology Stack

- Backend: Laravel 13
- Frontend: Use the Laravel 13 starter stack selected during setup; Blade/application views are acceptable for simple CRUD screens
- Database: MySQL
- Authentication: Laravel 13 first-party authentication starter/scaffolding selected for the project, not restricted to Breeze
- File Upload: Laravel Storage
- Email: Laravel Mail
- Scheduler: Laravel Task Scheduler
- Queue: Laravel Queue, optional in MVP
- Reports: Blade HTML reports first, Excel/PDF later
- Timezone: `Asia/Kolkata` — set in `config/app.php` as the application timezone. All dates, timestamps, and the daily scheduler (`dailyAt('09:00')`) operate in IST.

---

## 2.1 UI Design Reference

### Color Scheme

- **Accent color:** Neon green — `#39FF14` (or nearest Tailwind equivalent `lime-400` / `#a3e635`)
- Use accent on: primary action buttons, active nav/tab indicators, highlighted badge text, focus rings, links on hover
- **Dark mode:** Full dark mode support required. Use Tailwind's `dark:` variant throughout.
  - Dark background: `gray-950` / `#030712`
  - Card/surface: `gray-900` / `#111827`
  - Border: `gray-800`
  - Body text: `gray-100` in dark, `gray-900` in light
  - Muted text: `gray-400` in dark, `gray-500` in light
- **Light mode:** White / `gray-50` background, standard gray text

### Typography

- **Font family:** Nunito (all weights: ExtraLight 200, Light 300, Regular 400, SemiBold 600, Bold 700, ExtraBold 800)
- Load via Google Fonts: `https://fonts.googleapis.com/css2?family=Nunito:wght@200;300;400;600;700;800&display=swap`
- Set as the default `font-sans` in `tailwind.config.js`
- Heading style: `font-extrabold` (`800`) with tight tracking; one key word or phrase in the accent color where appropriate (e.g., page title, dashboard section headers)
- Body text: `font-normal` (`400`) or `font-light` (`300`) for supporting text

### Component Conventions

- Buttons: rounded (`rounded-md`), accent background for primary, outlined accent for secondary, ghost for destructive-cancel
- Table rows: subtle hover highlight using accent at low opacity (`lime-400/10` in dark mode)
- Status badges: pill style, color-coded (green = active, yellow = under repair, red = disposed/expired, gray = inactive)
- Expiry warning badges: red = expired, orange = expiring within 7 days, yellow = within 30 days, gray = within 60 days
- Form inputs: dark surface in dark mode (`gray-800` bg), accent focus ring
- Sidebar/nav active item: accent left border + accent text

### Dark Mode Toggle

- Provide a dark/light mode toggle in the top navigation bar
- Persist the preference in `localStorage`
- **Default to dark mode** regardless of OS preference. Only switch to light mode if the user explicitly toggles it.

---

## 3. Database Tables

### Phase 1 Tables

- users
- asset_categories
- asset_subcategories
- assets

### Phase 2 Tables

- asset_documents
- asset_extended_warranties
- asset_amcs
- asset_insurances
- asset_reminders
- email_reminder_logs

### Phase 3 Tables

- asset_services
- asset_service_parts

---

## 4. Suggested Database Structure

## 4.1 users

Use the default Laravel users table from the selected authentication starter/scaffolding. No custom user fields are required in the current scope.

---

## 4.2 asset_categories

```php
id
name
code
status
created_at
updated_at
```

### Category Code Rule

The `code` field is a short 2-character prefix used for asset code generation. It is entered manually when creating a category and must be unique and uppercase (e.g. `VE`, `AC`, `MO`, `EQ`). The system uses this prefix to auto-generate asset codes.

Examples:

| Code | Name |
|------|------|
| VE | Vehicle |
| AC | Air Conditioner |
| MO | Mobile |
| CO | Cooler |
| IT | IT Equipment |
| OE | Office Equipment |
| OX | Other Office Equipment |
| HE | Heavy Equipment |
| PE | Power Equipment |
| MA | Machine |
| UE | Utility Equipment |
| GE | Generator |

---

## 4.3 asset_subcategories

```php
id
asset_category_id
name
code
status
created_at
updated_at
```

Examples:

- Vehicle > Car
- Vehicle > Bike
- Vehicle > Scooter
- Vehicle > Truck
- AC > Window AC
- AC > Split AC
- AC > HVAC
- IT Equipment > Laptop
- IT Equipment > Printer
- Other Office Equipment > RO
- Other Office Equipment > Geyser
- Other Office Equipment > Kitchen Hob
- Other Office Equipment > Exhaust
- Utility Equipment > Water Cooler

---

## 4.4 assets

```php
id
asset_code
asset_name
asset_description
asset_category_id
asset_subcategory_id
serial_number
manufacturer
model
model_year
location
department
custodian
vendor_supplier
bill_no
bill_amount
bill_date
purchase_date
warranty_details
warranty_lapse_date
warranty_reminder_before_days
maintenance_schedule_type
maintenance_interval_value
maintenance_interval_unit
inspection_required
inspection_frequency_value
inspection_frequency_unit
puc_expiry_date
fitness_expiry_date
road_tax_expiry_date
vehicle_obv
vehicle_depreciation_percent
vehicle_depreciation_book_value
status
remarks
created_by
updated_by
created_at
updated_at
deleted_at
```

### Status Values

```text
active
under_repair
disposed
scrapped
inactive
```

### Maintenance Schedule Types

```text
date_based
hours_based
mileage_based
custom
none
```

### Schedule Units

```text
days
weeks
months
years
operating_hours
miles
kilometers
```

### Asset Code Generation Rule

`asset_code` is auto-generated on asset creation and is unique. It must not be editable by the user after creation.

Format: `{category_code}-{sequence}` where sequence is a per-category auto-incrementing integer starting at 1.

Examples: `VE-1`, `VE-2`, `AC-1`, `IT-1`, `IT-2`

Generation logic: query the maximum sequence number ever used for the selected category (including deleted assets), increment by 1, and combine with the category code. Store the generated code in `asset_code` on insert. Add a unique index on `asset_code`.

Asset codes are never reused. If `VE-3` was assigned to a deleted asset, the next vehicle asset receives `VE-4`, not `VE-3`. To support this, use a soft delete (`deleted_at`) on the `assets` table so deleted rows are retained for sequence tracking. The MAX sequence query must include soft-deleted rows.

### Category/Subcategory Rule

The selected subcategory must belong to the selected category. `asset_subcategory_id` can be nullable only if a category does not require subcategories.

### Category and Subcategory Delete Rule

A category cannot be deleted if it has dependent assets or subcategories. A subcategory cannot be deleted if it has dependent assets. Show a validation error and prevent the delete. Do not cascade deletes to assets.

### Vehicle-Specific Fields Rule

The fields `puc_expiry_date`, `fitness_expiry_date`, `road_tax_expiry_date`, `vehicle_obv`, `vehicle_depreciation_percent`, and `vehicle_depreciation_book_value` are nullable columns. These fields are only applicable to Vehicle category assets. Asset create and edit forms must skip validation for these fields when the selected category is not Vehicle. Reports and reminders based on these fields must filter to assets where the relevant field is not null.

### Bill Amount Rule

`bill_amount` is the purchase amount for the asset. Do not store a separate `purchase_amount` field.

### Standard Warranty Rule

The original purchase warranty is stored on the asset master. Extended warranties are stored separately in `asset_extended_warranties`, and AMC plans are stored separately in `asset_amcs`.

### Date-Based Reminder Rule

The MVP records hours and mileage values, but automatic reminder calculation is date-based only.

### Vehicle Depreciation Rule

Vehicle OBV, depreciation percentage, and depreciation book value are manually entered in MVP. The system does not calculate depreciation automatically in the first version.

---

## 4.5 asset_documents

```php
id
asset_id
documentable_type
documentable_id
document_type
document_title
file_path
file_original_name
file_mime_type
file_size
remarks
uploaded_by
created_at
updated_at
```

### Document Types

```text
purchase_bill
bill_image
invoice
warranty_card
warranty_activation_image
extended_warranty_bill
extended_warranty_image
insurance_copy
insurance_policy
puc_copy
rc_copy
service_bill
service_part_bill
amc_bill
amc_image
inspection_certificate
compliance_certificate
vehicle_document
asset_photo
other
```

### Document Ownership Rule

All uploaded files/images are stored in `asset_documents`.

`asset_id` is always stored so the asset detail page can list every file related to that asset.

`documentable_type` and `documentable_id` identify where the file was uploaded from, such as:

- Asset
- AssetExtendedWarranty
- AssetAmc
- AssetInsurance
- AssetReminder
- AssetService
- AssetServicePart

### Document Upload Request Rule

The `AssetDocumentController::store` request must receive the following fields alongside the file:

- `asset_id` — always required; the owning asset
- `documentable_type` — the fully-qualified model class name of the parent record (e.g. `App\Models\AssetInsurance`)
- `documentable_id` — the integer ID of the parent record
- `document_type` — the document type from the allowed list
- `document_title` — optional label

These fields must be passed as hidden form inputs in the upload form on each detail page. The controller must validate that the `documentable_id` exists for the given `documentable_type` and that it belongs to the supplied `asset_id`.

---

## 4.6 asset_extended_warranties

```php
id
asset_id
extended_warranty_vendor
extended_warranty_date_from
extended_warranty_date_to
extended_warranty_bill_no
extended_warranty_amount
extended_warranty_terms
reminder_before_days
remarks
created_at
updated_at
```

The original purchase warranty is stored on the asset master. Extended warranty bills, images, and documents should be stored in `asset_documents`.

---

## 4.7 asset_amcs

```php
id
asset_id
needs_amc_after_warranty
amc_vendor
amc_date_from
amc_date_to
amc_bill_no
amc_amount
amc_terms
reminder_before_days
remarks
created_at
updated_at
```

AMC bills and AMC images should be stored in `asset_documents`.

---

## 4.8 asset_insurances

```php
id
asset_id
is_insured
insurance_vendor
insurance_amount
insurance_policy_number
insurance_date_from
insurance_date_to
reminder_before_days
remarks
created_at
updated_at
```

Insurance policy documents/images should be stored in `asset_documents`.

---

## 4.9 asset_reminders

```php
id
asset_id
source_type
source_id
source_date_field
is_system_generated
reminder_type
title
due_date
reminder_before_days
status
last_email_sent_at
remarks
created_by
updated_by
created_at
updated_at
```

### Reminder Types

```text
warranty_expiry
extended_warranty_expiry
insurance_expiry
puc_expiry
fitness_expiry
road_tax_expiry
amc_expiry
service_due
inspection_due
certification_expiry
calibration_due
other
```

### Status Values

```text
pending
completed
expired
cancelled
```

### Generated Reminder Rule

Generated reminders must track their source using `source_type`, `source_id`, and `source_date_field`.

If a source date changes, the existing generated reminder must be updated instead of creating a duplicate reminder. This update must happen immediately when the source record is saved, not in a background scheduler. Use an Eloquent observer or model event on each source model to trigger reminder creation or update on save.

If a source record is deleted, its generated reminder must also be deleted. Do not leave orphaned reminders pointing to deleted source records.

Examples:

- Asset warranty lapse date
- Asset PUC expiry date
- Asset fitness expiry date
- Asset road tax expiry date
- Extended warranty end date
- AMC end date
- Insurance end date
- Service next service date
- Inspection service next service date
- Inspection service certification expiry

---

## 4.10 email_reminder_logs

```php
id
asset_reminder_id
asset_id
email_to
email_subject
email_status
sent_at
error_message
created_at
updated_at
```

### Email Status Values

```text
sent
failed
```

### Email Deduplication Rule

Add a composite index on `(asset_reminder_id, email_to, sent_at)` to support efficient deduplication lookups. The deduplication check is: no `sent` log entry exists for the same `asset_reminder_id`, `email_to`, and calendar date of `sent_at`. A reminder that is marked `completed` and then reverted to `pending` within the same day must not trigger a second email on that day — the existing sent log entry for that day takes precedence regardless of status changes.

---

## 4.11 asset_services

```php
id
asset_id
service_date
service_type
service_agency_name
technician_name
work_done
service_cost
next_service_date
service_interval
meter_reading
operating_hours
mileage_reading
downtime_hours
condition_rating
certification_expiry
safety_notes
remarks
created_by
updated_by
created_at
updated_at
```

### Service Types

```text
regular_service
repair
inspection
cleaning
emergency_repair
other
```

### Inspection Service Rule

Inspection is handled as a service record with `service_type = inspection`.

For inspection services:

- `next_service_date` is treated as the next inspection due date.
- `certification_expiry` is used for certification expiry reminders.
- Inspection certificates and compliance documents are stored in `asset_documents`.

### Condition Rating Values

```text
good
fair
poor
critical
```

---

## 4.12 asset_service_parts

```php
id
asset_service_id
part_name
quantity
part_cost
purchased_from
warranty_till
remarks
created_at
updated_at
```

---

## 5. Laravel Models

Create these models:

```text
User
AssetCategory
AssetSubcategory
Asset
AssetDocument
AssetExtendedWarranty
AssetAmc
AssetInsurance
AssetReminder
EmailReminderLog
AssetService
AssetServicePart
```

### Important Relationships

```php
Asset belongsTo AssetCategory
Asset belongsTo AssetSubcategory
AssetCategory hasMany AssetSubcategory
AssetSubcategory belongsTo AssetCategory
AssetSubcategory hasMany Asset
Asset hasMany AssetDocument
Asset hasMany AssetExtendedWarranty
Asset hasMany AssetAmc
Asset hasMany AssetInsurance
Asset hasMany AssetReminder
Asset hasMany AssetService
AssetService hasMany AssetServicePart
AssetDocument belongsTo Asset
AssetDocument morphTo documentable
AssetExtendedWarranty belongsTo Asset
AssetAmc belongsTo Asset
AssetInsurance belongsTo Asset
AssetReminder belongsTo Asset
AssetService belongsTo Asset
AssetServicePart belongsTo AssetService
```

---

## 6. Controllers

### Application / Panel Controllers

```text
DashboardController
AssetCategoryController
AssetSubcategoryController
AssetController
AssetDocumentController
AssetExtendedWarrantyController
AssetAmcController
AssetInsuranceController
AssetReminderController
AssetServiceController
AssetServicePartController
ReportController
```

### Console / Command

```text
SendAssetReminderEmailsCommand
```

---

## 7. Routes

Use authenticated routes.

```php
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('asset-categories', AssetCategoryController::class);
    Route::resource('asset-subcategories', AssetSubcategoryController::class);
    Route::resource('assets', AssetController::class);

    Route::post('asset-documents', [AssetDocumentController::class, 'store'])->name('asset-documents.store');
    Route::delete('asset-documents/{document}', [AssetDocumentController::class, 'destroy'])->name('asset-documents.destroy');

    Route::resource('asset-extended-warranties', AssetExtendedWarrantyController::class);
    Route::resource('asset-amcs', AssetAmcController::class);
    Route::resource('asset-insurances', AssetInsuranceController::class);
    Route::resource('asset-reminders', AssetReminderController::class);
    Route::resource('asset-services', AssetServiceController::class);
    Route::delete('asset-service-parts/{part}', [AssetServicePartController::class, 'destroy'])->name('asset-service-parts.destroy');

    Route::get('reports/asset-register', [ReportController::class, 'assetRegister'])->name('reports.asset-register');
    Route::get('reports/purchase-bills', [ReportController::class, 'purchaseBills'])->name('reports.purchase-bills');
    Route::get('reports/expiry', [ReportController::class, 'expiry'])->name('reports.expiry');
    Route::get('reports/warranty-expiry', [ReportController::class, 'warrantyExpiry'])->name('reports.warranty-expiry');
    Route::get('reports/extended-warranty-expiry', [ReportController::class, 'extendedWarrantyExpiry'])->name('reports.extended-warranty-expiry');
    Route::get('reports/insurance-expiry', [ReportController::class, 'insuranceExpiry'])->name('reports.insurance-expiry');
    Route::get('reports/puc-expiry', [ReportController::class, 'pucExpiry'])->name('reports.puc-expiry');
    Route::get('reports/amc-expiry', [ReportController::class, 'amcExpiry'])->name('reports.amc-expiry');
    Route::get('reports/fitness-expiry', [ReportController::class, 'fitnessExpiry'])->name('reports.fitness-expiry');
    Route::get('reports/road-tax-expiry', [ReportController::class, 'roadTaxExpiry'])->name('reports.road-tax-expiry');
    Route::get('reports/inspection-due', [ReportController::class, 'inspectionDue'])->name('reports.inspection-due');
    Route::get('reports/certification-expiry', [ReportController::class, 'certificationExpiry'])->name('reports.certification-expiry');
    Route::get('reports/service-due', [ReportController::class, 'serviceDue'])->name('reports.service-due');
    Route::get('reports/service-history', [ReportController::class, 'serviceHistory'])->name('reports.service-history');
    Route::get('reports/maintenance-cost', [ReportController::class, 'maintenanceCost'])->name('reports.maintenance-cost');
    Route::get('reports/vehicle-depreciation', [ReportController::class, 'vehicleDepreciation'])->name('reports.vehicle-depreciation');
});
```

---

# 8. Phase-Wise Implementation Plan

---

# Phase 0: Requirement Freezing and Project Setup

## Goal

Finalize the simple scope and prepare the Laravel project.

## Tasks

1. Confirm final modules:
   - Asset Register
   - Asset Category and Subcategory
   - Document Upload
   - Warranty Tracking
   - Extended Warranty Tracking
   - AMC Tracking
   - Insurance Tracking
   - Reminder Tracking
   - Service, Inspection, and Compliance History
   - Parts Replacement
   - Dashboard
   - Reports

2. Create Laravel project.

```bash
composer create-project laravel/laravel asset-register
```

3. Configure `.env` database settings.

4. Install or select the Laravel 13 first-party authentication starter/scaffolding for the project.
5. Do not hardcode the implementation to Laravel Breeze; use the Laravel 13-supported auth stack chosen during project setup.
6. Install frontend dependencies and run migrations.

```bash
npm install
npm run dev
php artisan migrate
```

7. Create base layout for application panel.

8. Create navigation menu.

## Deliverable

- Laravel project running
- Login/logout working
- Basic dashboard page ready

---

# Phase 1: Application Shell and Common Requirements

## Goal

Define the common application structure and shared software requirements before building modules.

## Tasks

1. Define the application panel layout structure.
2. Define the navigation menu with the following sidebar structure:

   ```
   Dashboard
   ├── Assets
   │   ├── Asset Register
   │   ├── Categories
   │   └── Subcategories
   ├── Reminders
   └── Reports
       ├── Asset Register Report
       ├── Purchase / Bill Details
       ├── Expiry Reports (Warranty, Ext. Warranty, AMC, Insurance, PUC, Fitness, Road Tax, Certification)
       ├── Service Reports (Service Due, Service History, Maintenance Cost)
       └── Vehicle Depreciation
   ```
3. Define common list page behavior:
   - Search
   - Filters
   - Pagination
   - Sorting where useful
4. Define common form behavior:
   - Server-side validation
   - Clear success/error messages
   - Required field indicators
5. Define common date handling:
   - Bill dates
   - Purchase dates
   - Due dates
   - Service dates
   - Warranty dates
   - AMC dates
   - Insurance dates
   - Inspection dates
   - Certification expiry dates
6. Define common schedule handling:
   - Date-based schedules
   - Hours-based schedules for recording only in MVP
   - Mileage-based schedules for recording only in MVP
   - Custom intervals
7. Define common file handling:
   - Allowed file types
   - Maximum upload size
   - Storage path convention
8. Define dashboard reminder windows (implemented fully in Phase 7):
   - Expired (due_date < today)
   - Expiring in 7 days (due_date between today and today+7)
   - Expiring in 30 days (due_date between today and today+30)
   - Expiring in 60 days (due_date between today and today+60)
9. Define email reminder recipients and sender details.
   - Reminder emails are sent to all registered users in MVP.
   - Date-based reminders are automated in MVP.
   - Hours-based and mileage-based values are stored but do not trigger automatic reminders in MVP.

## Deliverable

- Common application requirements finalized
- Application panel structure ready for module development

---

# Phase 2: Asset Category and Subcategory Module

## Goal

Create simple category and subcategory masters for assets.

## Tasks

1. Create migrations, models, controllers, and views.
2. Category fields:
   - Name
   - Code (2-character uppercase prefix, unique, used for asset code generation — e.g. `VE`, `AC`, `IT`)
   - Status
3. Subcategory fields:
   - Category
   - Name
   - Code
   - Status
4. Build category screens:
   - Category list
   - Add category
   - Edit category
   - Delete category
5. Build subcategory screens:
   - Subcategory list
   - Add subcategory
   - Edit subcategory
   - Delete subcategory

## Deliverable

- Category and subcategory CRUD completed

---

# Phase 3: Asset Register Module

## Goal

Create the main asset register.

## Tasks

1. Create `assets` migration.
2. Create `Asset` model.
3. Create `AssetController`.
4. Build asset list page with filters.
5. Build asset create form.
6. Build asset edit form.
7. Build asset detail page.
8. Add fields:
   - Asset Code (auto-generated on create from category code + sequence; read-only, not shown on create form)
   - Asset Name
   - Asset Description
   - Category
   - Subcategory
   - Manufacturer
   - Model
   - Model / Year
   - Serial Number
   - Location
   - Department
   - Custodian
   - Vendor / Supplier
   - Bill Number
   - Bill / Purchase Amount
   - Bill Date
   - Purchase Date
   - Original Warranty Details
   - Original Warranty Lapse Date
   - Warranty Reminder Before Days
   - Maintenance Schedule Type
   - Maintenance Interval Value
   - Maintenance Interval Unit
   - Inspection Required
   - Inspection Frequency Value
   - Inspection Frequency Unit
   - PUC Expiry Date
   - Fitness Expiry Date
   - Road Tax Expiry Date
   - Vehicle OBV
   - Vehicle Depreciation Percentage
   - Vehicle Depreciation Book Value
   - Status
   - Remarks
9. Add search by:
   - Asset Code
   - Asset Name
   - Serial Number
   - Manufacturer
   - Vendor / Supplier
   - Category
   - Subcategory
   - Location
   - Department
   - Custodian
10. Add dependent subcategory loading based on selected category.
11. Validate that selected subcategory belongs to selected category.
12. Build the asset detail page using a vertical tab layout (see section 2.1 Asset Detail Page Layout).

## Asset Detail Page Layout

The asset detail/show page uses a two-column layout: a fixed vertical tab list on the left and a content panel on the right.

```
┌─────────────────────────────────────────────────────────────┐
│  Asset Header: Code · Name · Status badge · Edit button     │
├──────────────────┬──────────────────────────────────────────┤
│  [▶] Overview    │                                          │
│  [ ] Documents   │   Tab content panel                      │
│  [ ] Warranty    │   (renders the active tab's records,     │
│  [ ] Ext. War.   │    forms, and upload sections)           │
│  [ ] AMC         │                                          │
│  [ ] Insurance   │                                          │
│  [ ] Reminders   │                                          │
│  [ ] Services    │                                          │
│  [ ] Parts       │                                          │
└──────────────────┴──────────────────────────────────────────┘
```

### Vertical Tab Definitions

| Tab | Content |
|-----|---------|
| Overview | All core asset fields: category, purchase details, warranty summary, vehicle compliance dates, maintenance schedule, remarks |
| Documents | All `asset_documents` for this asset grouped by document type; upload form |
| Warranty | Original warranty details from asset master |
| Extended Warranty | List of `asset_extended_warranties` records; add/edit/delete inline |
| AMC | List of `asset_amcs` records; add/edit/delete inline |
| Insurance | List of `asset_insurances` records; add/edit/delete inline |
| Reminders | All `asset_reminders` for this asset (system-generated and manual); status badges |
| Services | List of `asset_services` records ordered by service date descending; add new service |
| Parts | All `asset_service_parts` grouped under their service record |

### Tab Behavior Rules

- The active tab is indicated by the accent left border and accent text color.
- Tab state is preserved in the URL using a `?tab=` query parameter so the page can be linked directly to a specific tab.
- Each tab loads its data on page load (no lazy AJAX in MVP). Use Blade `@include` partials for each tab panel.
- On mobile, the vertical tab list collapses to a horizontal scrollable tab bar at the top.

## Deliverable

- Full asset register working with category/subcategory classification

---

# Phase 4: Document Upload Module

## Goal

Allow files/images to be uploaded against each asset.

## Tasks

1. Create `asset_documents` table.
2. Use one common document table for files uploaded from asset, extended warranty, AMC, insurance, service, and service parts.
3. Add document upload sections on relevant detail pages.
4. Show all related files on the asset detail page using `asset_id`.
5. Allow multiple document types:
   - Purchase Bill
   - Bill Image
   - Invoice
   - Warranty Card
   - Warranty Activation Image
   - Extended Warranty Bill
   - Extended Warranty Image
   - Insurance Copy
   - Insurance Policy
   - PUC Copy
   - RC Copy
   - Service Bill
   - Service Part Bill
   - AMC Bill
   - AMC Image
   - Inspection Certificate
   - Compliance Certificate
   - Vehicle Document
   - Asset Photo
   - Other
6. Store files using Laravel Storage.
7. Save file metadata in database.
8. Add view/download/delete options.
9. Add file validation:

```php
pdf,jpg,jpeg,png,webp
max:5120
```

## Deliverable

- Asset-wise document upload working

---

# Phase 5: Warranty, Extended Warranty, AMC, Insurance, and Reminder Tracking Module

## Goal

Track original warranty, extended warranty, AMC, insurance, PUC, fitness, road tax, service due, inspection due, certification expiry, and other renewal dates.

## Tasks

1. Create `asset_extended_warranties`, `asset_amcs`, `asset_insurances`, and `asset_reminders` tables.
2. Show original warranty details from the asset master on the asset detail page.
3. Add extended warranty section on asset detail page.
4. Add extended warranty fields:
   - Extended Warranty Vendor
   - Extended Warranty Date From
   - Extended Warranty Date To
   - Extended Warranty Bill Number
   - Extended Warranty Amount
   - Extended Warranty Terms
   - Reminder Before Days
   - Remarks
5. Add AMC section on asset detail page.
6. Add AMC fields:
   - Needs AMC After Warranty
   - AMC Vendor
   - AMC Date From
   - AMC Date To
   - AMC Bill Number
   - AMC Amount
   - AMC Terms
   - Reminder Before Days
   - Remarks
7. Add insurance section on asset detail page.
8. Add insurance fields:
   - Insurance Yes/No
   - Insurance Vendor
   - Insurance Amount
   - Insurance Policy Number
   - Insurance From Date
   - Insurance To Date
   - Reminder Before Days
   - Remarks
9. Create reminder CRUD for manual reminders.
10. Attach reminders to assets.
11. Add reminder section on asset detail page.
12. Add reminder fields:
   - Reminder Type
   - Title
   - Due Date
   - Reminder Before Days
   - Source Type
   - Source ID
   - Source Date Field
   - System Generated Yes/No
   - Status
   - Remarks
13. Auto-detect overdue reminders.
14. Auto-create/update generated reminders from:
   - Warranty Lapse Date
   - Extended Warranty Date To
   - AMC Date To
   - Insurance To Date
   - PUC Expiry Date
   - Fitness Expiry Date
   - Road Tax Expiry Date
   - Next Service Due
   - Inspection Service Next Service Date
   - Inspection Service Certification Expiry
15. Track generated reminder source so source date changes update the same reminder instead of creating duplicates.

## Deliverable

- Warranty, extended warranty, AMC, insurance, and asset reminder tracking working

---

# Phase 6: Email Reminder Scheduler

## Goal

Send automatic email reminders before due dates.

## Tasks

1. Create `email_reminder_logs` table.
2. Create Artisan command:

```bash
php artisan make:command SendAssetReminderEmails
```

3. Query pending reminders where today's date is on or after `(due_date - reminder_before_days)`. Each reminder record carries its own `reminder_before_days` value. The scheduler does not use a global window — it reads `reminder_before_days` from each reminder row and fires when `CURDATE() >= DATE_SUB(due_date, INTERVAL reminder_before_days DAY)`.
4. Send email to all registered users.
5. Log success/failure in `email_reminder_logs`.
6. Prevent duplicate emails for the same reminder, same recipient, and same day.
7. Register command in scheduler:

```php
$schedule->command('asset-reminders:send')->dailyAt('09:00');
```

8. Configure server cron:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Deliverable

- Automatic email reminders working
- Email log maintained

---

# Phase 7: Dashboard Module

## Goal

Create a useful dashboard for management and users.

## Dashboard Widgets

1. Total Assets
2. Active Assets
3. Under Repair Assets
4. Disposed Assets
5. Expired Reminders
6. Expiring in 7 Days
7. Expiring in 30 Days
8. Service Due
9. Warranty Expiring
10. Extended Warranty Expiring
11. AMC Expiring
12. Insurance Expiring
13. PUC Expiring
14. Fitness Expiring
15. Road Tax Expiring
16. Inspection Due
17. Certification Expiring
18. Recent Service Records
19. High Maintenance Cost Assets

## Tasks

1. Create dashboard queries. Load reminder-type counts with a single `GROUP BY reminder_type` query on `asset_reminders` filtered by the active date window instead of one query per reminder type. Load asset status counts with a single `GROUP BY status` query on `assets`.
2. Add cards for summary numbers.
3. Add expiry reminder table.
4. Add recent service history table.
5. Add warranty/extended warranty/AMC/insurance/vehicle compliance/inspection due tables where useful.
6. Add filter by category/subcategory/location/department/custodian/status.

## Deliverable

- Management dashboard completed

---

# Phase 8: Service / Maintenance and Inspection History Module

## Goal

Record all service, repair, inspection, compliance, and maintenance activity.

## Tasks

1. Create `asset_services` table.
2. Create service CRUD.
3. Add service section to asset detail page.
4. Add service fields:
   - Service Date
   - Service Type
   - Service Agency / Person Name
   - Technician Name
   - Work Done
   - Service Cost
   - Next Service Date
   - Service Interval
   - Meter Reading / Operating Hours
   - Mileage Reading
   - Downtime Hours
   - Condition Rating
   - Certification Expiry
   - Safety Notes
   - Bill Upload through `asset_documents`
   - Remarks
5. Use `service_type = inspection` for inspection records.
6. For inspection service records:
   - Service Date means Inspection Date
   - Next Service Date means Next Inspection Due
   - Certification Expiry triggers certification reminders
   - Safety Notes stores inspection/compliance notes
7. If next service date or certification expiry is entered, auto-create/update the generated reminder.

## Deliverable

- Service, maintenance, inspection, and compliance history working

---

# Phase 9: Parts Replacement Module

## Goal

Track parts replaced during service.

## Tasks

1. Create `asset_service_parts` table.
2. Add parts section inside service create/edit form.
3. Allow multiple parts per service.
4. Fields:
   - Part Name
   - Quantity
   - Part Cost
   - Purchased From
   - Warranty Till
   - Remarks
5. Calculate total part cost.
6. Show service cost + parts cost.

## Deliverable

- Parts replacement history working

---

# Phase 10: Reports Module

## Goal

Provide simple reports for asset tracking and management decisions.

## Reports

1. Asset Register Report
2. Purchase / Bill Details Report
3. Warranty Expiry Report
4. Extended Warranty Expiry Report
5. Insurance Expiry Report
6. PUC Expiry Report
7. Fitness Expiry Report
8. Road Tax Expiry Report
9. AMC Expiry Report
10. Inspection Due Report
11. Certification Expiry Report
12. Service Due Report
13. Service History Report
14. Maintenance Cost Report
15. Category-wise Asset Report
16. Subcategory-wise Asset Report
17. Location-wise Asset Report
18. Department-wise Asset Report
19. Custodian-wise Asset Report
20. Vehicle Depreciation Report

## Filters

- Date range
- Category
- Subcategory
- Location
- Department
- Custodian
- Status
- Reminder type
- Maintenance schedule type
- Vendor / Supplier

## Tasks

1. Create report controller methods.
2. Create report views.
3. Add filter forms.
4. Add print-friendly layout.
5. Add Excel/PDF export later.

## Deliverable

- Basic reports working

---

# Phase 11: Testing and Validation

## Goal

Ensure system correctness before internal use.

## Test Areas

1. Login/logout
2. Asset CRUD
3. Category and subcategory CRUD
4. Document upload
5. Bill detail entry
6. Warranty detail entry
7. Extended warranty detail entry
8. AMC detail entry
9. Insurance detail entry
10. Vehicle compliance date entry
11. Reminder creation
12. Dashboard counts
13. Email reminder logic
14. Service entry
15. Inspection service entry
16. Parts entry
17. Reports
18. File permissions
19. Delete behavior

## Important Test Cases

- Expired reminder appears on dashboard
- Upcoming reminder appears in 7/30-day list
- Duplicate reminder email is not sent to the same user on the same day
- Uploaded document can be downloaded
- Asset total cost is calculated correctly
- Selected subcategory must belong to the selected category
- Warranty lapse date auto-creates or updates a warranty reminder
- Extended warranty end date auto-creates or updates an extended warranty reminder
- AMC end date auto-creates or updates an AMC reminder
- Insurance to date auto-creates or updates an insurance reminder
- PUC, fitness, and road tax dates auto-create or update reminders
- Next inspection due appears in reminder/dashboard lists
- Certification expiry appears in reminder/dashboard lists
- Date-based reminders work; hours-based and mileage-based values can be recorded without automatic reminder calculation in MVP
- Asset detail page lists documents uploaded from asset, extended warranty, AMC, insurance, service, and service parts

## Deliverable

- Tested MVP ready for users

---

# Phase 12: Deployment

## Goal

Deploy application on production server.

## Tasks

1. Configure production `.env`.
2. Set database credentials.
3. Set mail credentials.
4. Run migrations.
5. Link storage:

```bash
php artisan storage:link
```

6. Set folder permissions.
7. Configure scheduler cron.
8. Create first application user.
9. Test email reminders.
10. Take database backup.

## Deliverable

- Production system live

---

# 9. Recommended MVP Build Order

Build in this exact order:

1. Laravel setup
2. Authentication
3. Application shell and common requirements
4. Asset categories
5. Asset subcategories
6. Assets
7. Asset detail page
8. Document uploads
9. Original warranty fields on asset
10. Extended warranty details
11. AMC details
12. Insurance details
13. Vehicle compliance date fields
14. Asset reminders with source tracking
15. Dashboard expiry counters
16. Email reminder scheduler
17. Service and inspection history
18. Parts replacement
19. Reports

---

# 10. MVP Scope

## Included in MVP

- Login
- Asset category and subcategory CRUD
- Asset CRUD
- Asset document upload
- Purchase/bill details
- Warranty details
- Extended warranty details
- AMC details
- Insurance details
- PUC, fitness, and road tax expiry fields
- Reminder records
- Dashboard alerts
- Email reminders
- Service, inspection, and compliance history
- Parts replacement
- Basic reports

## Not Included in MVP

- Purchase orders
- Inventory management
- Full vendor management module
- Accounting
- Payment tracking
- Approval workflow
- Spare parts stock
- QR code tracking
- Mobile app

---

# 11. Final Implementation Summary

This project should be developed as a simple Laravel application panel focused on fixed asset tracking, asset documents/images, renewal reminders, inspection/compliance dates, service records, and reports.

The system should focus on:

- Asset records with category/subcategory classification
- Purchase/bill details
- Single document/image upload table shared by all asset-related modules
- Original warranty, extended warranty, AMC, and insurance tracking
- Auto-created/updated date-based renewal reminders with source tracking
- PUC, fitness, and road tax expiry tracking
- Inspection and compliance tracking inside service history
- Parts replacement
- Dashboard alerts
- Reports

The first version should avoid ERP-style complexity and should not include procurement, accounting, inventory, or full vendor management modules.
