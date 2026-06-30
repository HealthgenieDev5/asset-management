<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDocument;
use App\Models\AssetSubcategory;
use App\Models\Department;
use App\Models\Location;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetController extends Controller
{
    public function index(Request $request)
    {
        $assets = Asset::with(['category', 'subcategory'])
            ->when($request->search, function ($q, $s) {
                $q->where(function ($q2) use ($s) {
                    $q2->where('asset_code', 'like', "%{$s}%")
                        ->orWhere('asset_name', 'like', "%{$s}%")
                        ->orWhere('serial_number', 'like', "%{$s}%")
                        ->orWhere('manufacturer', 'like', "%{$s}%")
                        ->orWhere('vendor_supplier', 'like', "%{$s}%")
                        ->orWhere('location', 'like', "%{$s}%")
                        ->orWhere('department', 'like', "%{$s}%")
                        ->orWhere('custodian', 'like', "%{$s}%");
                });
            })
            ->when($request->category_id, fn($q, $id) => $q->where('asset_category_id', $id))
            ->when($request->subcategory_id, fn($q, $id) => $q->where('asset_subcategory_id', $id))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $categories = AssetCategory::orderBy('name')->get();
        $subcategories = $request->category_id
            ? AssetSubcategory::where('asset_category_id', $request->category_id)->orderBy('name')->get()
            : collect();

        return view('assets.index', compact('assets', 'categories', 'subcategories'));
    }

    public function export(Request $request)
    {
        $filters = $request->only(['status', 'category_id', 'department']);

        $q = Asset::with(['category', 'subcategory'])->withoutTrashed();

        if (! empty($filters['status']))      { $q->where('status', $filters['status']); }
        if (! empty($filters['category_id'])) { $q->where('asset_category_id', $filters['category_id']); }
        if (! empty($filters['department']))  { $q->where('department', 'like', '%' . $filters['department'] . '%'); }

        $assets   = $q->orderBy('asset_code')->get();
        $filename = 'assets-' . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($assets) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Code', 'Asset Name', 'Category', 'Sub-Category', 'Serial No.', 'Reg. No.', 'Manufacturer', 'Model', 'Location', 'Department', 'Custodian', 'Purchase Date', 'Bill Amount (₹)', 'Status']);
            foreach ($assets as $a) {
                fputcsv($out, [
                    $a->asset_code,
                    $a->asset_name,
                    $a->category?->name,
                    $a->subcategory?->name,
                    $a->serial_number,
                    $a->registration_number,
                    $a->manufacturer,
                    $a->model,
                    $a->location,
                    $a->department,
                    $a->custodian,
                    $a->purchase_date?->format('d/m/Y'),
                    $a->bill_amount ? number_format($a->bill_amount, 2) : '',
                    ucfirst(str_replace('_', ' ', $a->status)),
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function create()
    {
        $categories  = AssetCategory::active()->orderBy('name')->get();
        $locations   = Location::active()->orderBy('name')->get(['id', 'name']);
        $departments = Department::active()->orderBy('name')->get(['id', 'name']);

        return view('assets.create', compact('categories', 'locations', 'departments'));
    }

    public function store(Request $request)
    {
        $isVehicle = $this->isVehicleCategory($request->asset_category_id);

        $validated = $request->validate($this->rules($isVehicle));

        $validated['asset_code'] = Asset::generateAssetCode((int) $validated['asset_category_id']);
        $validated['created_by'] = auth()->id();

        if (! $isVehicle) {
            $validated['registration_number']             = null;
            $validated['puc_expiry_date']                 = null;
            $validated['puc_reminder_before_days']        = null;
            $validated['fitness_expiry_date']             = null;
            $validated['fitness_reminder_before_days']    = null;
            $validated['road_tax_expiry_date']            = null;
            $validated['road_tax_reminder_before_days']   = null;
            $validated['vehicle_obv']                     = null;
            $validated['vehicle_depreciation_percent']    = null;
            $validated['vehicle_depreciation_book_value'] = null;
        }

        $asset = Asset::create($validated);

        if ($request->hasFile('purchase_bill_file')) {
            $file = $request->file('purchase_bill_file');
            $path = $file->store("assets/{$asset->id}/documents", 'public');
            AssetDocument::create([
                'asset_id'           => $asset->id,
                'documentable_type'  => Asset::class,
                'documentable_id'    => $asset->id,
                'document_type'      => 'purchase_bill',
                'document_title'     => 'Purchase Bill',
                'file_path'          => $path,
                'file_original_name' => $file->getClientOriginalName(),
                'file_mime_type'     => $file->getClientMimeType(),
                'file_size'          => $file->getSize(),
                'uploaded_by'        => auth()->id(),
            ]);
        }

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset created successfully.');
    }

    public function show(Asset $asset)
    {
        $tab = request('tab', 'insights');

        $asset->load([
            'category',
            'subcategory',
            'documents.uploader',
            'warranties.documents',
            'warranties.smartReminders',
            'warranties.vendorRecord',
            'amcContracts.documents',
            'amcContracts.smartReminders',
            'amcContracts.vendor',
            'insurancePolicies.documents',
            'insurancePolicies.smartReminders',
            'services.documents',
            'services.smartReminders',
            'services.vendor',
            'services.parts.documents',
            'services.parts.smartReminders',
            'complaints.comments.user',
            'complaints.documents',
            'complaints.service',
            'smartReminders.remindable',
            'maintenanceSchedules.smartReminders',
            'meterLogs',
        ]);

        $showReminderForm   = request('showform') === '1' && $tab === 'reminders';
        $prefillInsuranceId = request('insuranceid');
        $prefillWarrantyId  = request('warrantyid');
        $prefillScheduleId  = request('scheduleid');
        $prefillAmcId       = request('amcid');
        $prefillServiceId   = request('serviceid');
        $prefillPartId      = request('partid');

        $reminderPrefill = null;
        if ($showReminderForm && $prefillInsuranceId) {
            $policy = $asset->insurancePolicies->firstWhere('id', $prefillInsuranceId);
            if ($policy) {
                $name = trim(implode(' – ', array_filter([
                    $policy->insurer_name,
                    $policy->policy_number ? 'Policy #' . $policy->policy_number : null,
                ]))) ?: 'Insurance Reminder';
                $reminderPrefill = [
                    'reminder_name' => $name . ' Renewal Reminder',
                    'reminder_type' => 'insurance',
                    'expiry_date'   => $policy->policy_date_to?->format('Y-m-d'),
                ];
            }
        }

        if ($showReminderForm && $prefillWarrantyId && ! $reminderPrefill) {
            $warranty = $asset->warranties->firstWhere('id', (int) $prefillWarrantyId);
            if ($warranty) {
                $scopeLabel  = $warranty->scope === 'part' ? ($warranty->part_name . ' — ') : '';
                $reminderType = $warranty->scope === 'part' ? 'part_warranty'
                    : ($warranty->warranty_type === 'extended' ? 'extended_warranty' : 'warranty');
                $reminderPrefill = [
                    'reminder_name' => $scopeLabel . $warranty->warrantyTypeLabel() . ' Warranty Reminder',
                    'reminder_type' => $reminderType,
                    'expiry_date'   => $warranty->expiry_date?->format('Y-m-d'),
                ];
            }
        }

        if ($showReminderForm && $prefillAmcId && ! $reminderPrefill) {
            $amcContract = $asset->amcContracts->firstWhere('id', (int) $prefillAmcId);
            if ($amcContract) {
                $amcName = trim(implode(' – ', array_filter([
                    $amcContract->vendor?->name ?? $amcContract->vendor_name,
                    $amcContract->contract_number ? 'Contract #' . $amcContract->contract_number : null,
                ]))) ?: 'AMC Contract';
                $reminderPrefill = [
                    'reminder_name' => $amcName . ' Renewal Reminder',
                    'reminder_type' => 'amc',
                    'expiry_date'   => $amcContract->amc_date_to?->format('Y-m-d'),
                ];
            }
        }

        if ($showReminderForm && $prefillScheduleId && ! $reminderPrefill) {
            $schedule = $asset->maintenanceSchedules->firstWhere('id', (int) $prefillScheduleId);
            if ($schedule) {
                $reminderPrefill = [
                    'reminder_name' => $schedule->schedule_name . ' Reminder',
                    'reminder_type' => 'maintenance_schedule',
                    'expiry_date'   => $schedule->next_due_date?->format('Y-m-d'),
                ];
            }
        }

        if ($showReminderForm && $prefillServiceId && ! $reminderPrefill) {
            $service = $asset->services->firstWhere('id', (int) $prefillServiceId);
            if ($service) {
                $reminderPrefill = [
                    'reminder_name' => $service->service_type_label . ' — ' . $service->service_date->format('d M Y') . ' Reminder',
                    'reminder_type' => 'service_due',
                    'expiry_date'   => $service->next_service_date?->format('Y-m-d'),
                ];
            }
        }

        if ($showReminderForm && $prefillPartId && ! $reminderPrefill) {
            $part = $asset->services->flatMap->parts->firstWhere('id', (int) $prefillPartId);
            if ($part) {
                $reminderPrefill = [
                    'reminder_name' => $part->part_name . ' Part Warranty Reminder',
                    'reminder_type' => 'part_warranty',
                    'expiry_date'   => $part->warranty_till?->format('Y-m-d'),
                ];
            }
        }

        if ($showReminderForm && request('puc') === '1' && ! $reminderPrefill && $asset->isVehicle()) {
            $reminderPrefill = [
                'reminder_name' => ($asset->registration_number ? $asset->registration_number . ' – ' : '') . 'PUC Expiry Reminder',
                'reminder_type' => 'puc',
                'expiry_date'   => $asset->puc_expiry_date?->format('Y-m-d'),
            ];
        }

        if ($showReminderForm && request('fitness') === '1' && ! $reminderPrefill && $asset->isVehicle()) {
            $reminderPrefill = [
                'reminder_name' => ($asset->registration_number ? $asset->registration_number . ' – ' : '') . 'Fitness Certificate Renewal Reminder',
                'reminder_type' => 'fitness',
                'expiry_date'   => $asset->fitness_expiry_date?->format('Y-m-d'),
            ];
        }

        if ($showReminderForm && request('road_tax') === '1' && ! $reminderPrefill && $asset->isVehicle()) {
            $reminderPrefill = [
                'reminder_name' => ($asset->registration_number ? $asset->registration_number . ' – ' : '') . 'Road Tax Renewal Reminder',
                'reminder_type' => 'road_tax',
                'expiry_date'   => $asset->road_tax_expiry_date?->format('Y-m-d'),
            ];
        }

        $auditLogs = \App\Models\AssetAuditLog::where('asset_id', $asset->id)
            ->with('causer:id,name')
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        $vendors = in_array($tab, ['amc', 'insurance', 'services', 'reminders', 'warranty', 'parts']) || $showReminderForm
            ? \App\Models\Vendor::active()->orderBy('name')->get(['id', 'name', 'type', 'phone', 'email'])
            : collect();

        $categories = \App\Models\AssetCategory::active()->orderBy('name')->get(['id', 'name', 'code']);

        return view('assets.show', compact('asset', 'tab', 'auditLogs', 'vendors', 'showReminderForm', 'prefillInsuranceId', 'reminderPrefill', 'categories'));
    }

    public function edit(Asset $asset)
    {
        $asset->load(['category', 'documents']);
        $categories    = AssetCategory::active()->orderBy('name')->get();
        $subcategories = AssetSubcategory::where('asset_category_id', $asset->asset_category_id)
            ->active()->orderBy('name')->get();
        $locations     = Location::active()->orderBy('name')->get(['id', 'name']);
        $departments   = Department::active()->orderBy('name')->get(['id', 'name']);

        return view('assets.edit', compact('asset', 'categories', 'subcategories', 'locations', 'departments'));
    }

    public function update(Request $request, Asset $asset)
    {
        $isVehicle = $this->isVehicleCategory($request->asset_category_id);

        $validated = $request->validate($this->rules($isVehicle, $asset->id));

        $validated['updated_by'] = auth()->id();

        if (! $isVehicle) {
            $validated['registration_number']             = null;
            $validated['puc_expiry_date']                 = null;
            $validated['puc_reminder_before_days']        = null;
            $validated['fitness_expiry_date']             = null;
            $validated['fitness_reminder_before_days']    = null;
            $validated['road_tax_expiry_date']            = null;
            $validated['road_tax_reminder_before_days']   = null;
            $validated['vehicle_obv']                     = null;
            $validated['vehicle_depreciation_percent']    = null;
            $validated['vehicle_depreciation_book_value'] = null;
        }

        $asset->update($validated);

        if ($request->hasFile('purchase_bill_file')) {
            $file = $request->file('purchase_bill_file');
            $path = $file->store("assets/{$asset->id}/documents", 'public');
            AssetDocument::create([
                'asset_id'           => $asset->id,
                'documentable_type'  => Asset::class,
                'documentable_id'    => $asset->id,
                'document_type'      => 'purchase_bill',
                'document_title'     => 'Purchase Bill',
                'file_path'          => $path,
                'file_original_name' => $file->getClientOriginalName(),
                'file_mime_type'     => $file->getClientMimeType(),
                'file_size'          => $file->getSize(),
                'uploaded_by'        => auth()->id(),
            ]);
        }

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset updated successfully.');
    }

    public function patchField(Request $request, Asset $asset)
    {
        $allowed = [
            // Core
            'asset_name'                    => ['required', 'string', 'max:255'],
            'asset_category_id'             => ['required', 'exists:asset_categories,id'],
            'asset_subcategory_id'          => ['nullable', 'exists:asset_subcategories,id'],
            'manufacturer'                  => ['nullable', 'string', 'max:255'],
            'model'                         => ['nullable', 'string', 'max:255'],
            'model_year'                    => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'serial_number'                 => ['nullable', 'string', 'max:255'],
            'status'                        => ['required', 'in:active,under_repair,disposed,scrapped,inactive'],
            // Location & ownership
            'location'                      => ['nullable', 'string', 'max:255'],
            'department'                    => ['nullable', 'string', 'max:255'],
            'custodian'                     => ['nullable', 'string', 'max:255'],
            'vendor_supplier'               => ['nullable', 'string', 'max:255'],
            // Purchase
            'bill_no'                       => ['nullable', 'string', 'max:255'],
            'bill_amount'                   => ['nullable', 'numeric', 'min:0'],
            'bill_date'                     => ['nullable', 'date'],
            'purchase_date'                 => ['nullable', 'date'],
            // Maintenance schedule
            'maintenance_schedule_type'     => ['required', 'in:date_based,hours_based,mileage_based,custom,none'],
            'maintenance_interval_value'    => ['nullable', 'integer', 'min:1'],
            'maintenance_interval_unit'     => ['nullable', 'in:days,weeks,months,years,operating_hours,miles,kilometers'],
            'inspection_required'           => ['required', 'boolean'],
            'inspection_frequency_value'    => ['nullable', 'integer', 'min:1'],
            'inspection_frequency_unit'     => ['nullable', 'in:days,weeks,months,years'],
            // Vehicle compliance
            'registration_number'           => ['nullable', 'string', 'max:50'],
            'puc_expiry_date'               => ['nullable', 'date'],
            'puc_reminder_before_days'      => ['nullable', 'integer', 'min:1', 'max:365'],
            'fitness_expiry_date'           => ['nullable', 'date'],
            'fitness_reminder_before_days'  => ['nullable', 'integer', 'min:1', 'max:365'],
            'road_tax_expiry_date'          => ['nullable', 'date'],
            'road_tax_reminder_before_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'vehicle_obv'                   => ['nullable', 'numeric', 'min:0'],
            'vehicle_depreciation_percent'  => ['nullable', 'numeric', 'min:0', 'max:100'],
            'vehicle_depreciation_book_value' => ['nullable', 'numeric', 'min:0'],
            // Misc
            'remarks'                       => ['nullable', 'string'],
        ];

        $field = $request->input('field');
        abort_if(! array_key_exists($field, $allowed), 422);

        // Vehicle-only fields must not be patched on non-vehicle assets
        $vehicleFields = ['registration_number', 'puc_expiry_date', 'puc_reminder_before_days',
                          'fitness_expiry_date', 'fitness_reminder_before_days', 'road_tax_expiry_date',
                          'road_tax_reminder_before_days', 'vehicle_obv', 'vehicle_depreciation_percent',
                          'vehicle_depreciation_book_value'];
        abort_if(in_array($field, $vehicleFields, true) && ! $asset->isVehicle(), 403);

        $validated = $request->validate(['value' => $allowed[$field]]);

        $asset->update([
            $field       => $validated['value'],
            'updated_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return redirect()->route('assets.show', [$asset, 'tab' => 'overview'])
            ->with('success', ucwords(str_replace('_', ' ', $field)) . ' updated.');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();

        return redirect()->route('assets.index')
            ->with('success', 'Asset deleted successfully.');
    }

    private function rules(bool $isVehicle, ?int $ignoreId = null): array
    {
        $rules = [
            'asset_name'                   => ['required', 'string', 'max:255'],
            'asset_description'            => ['nullable', 'string'],
            'asset_category_id'            => ['required', 'exists:asset_categories,id'],
            'asset_subcategory_id'         => ['nullable', 'exists:asset_subcategories,id'],
            'serial_number'                => ['nullable', 'string', 'max:255'],
            'manufacturer'                 => ['nullable', 'string', 'max:255'],
            'model'                        => ['nullable', 'string', 'max:255'],
            'model_year'                   => ['nullable', 'integer', 'min:1900', 'max:' . (date('Y') + 1)],
            'location'                     => ['nullable', 'string', 'max:255'],
            'department'                   => ['nullable', 'string', 'max:255'],
            'custodian'                    => ['nullable', 'string', 'max:255'],
            'vendor_supplier'              => ['nullable', 'string', 'max:255'],
            'bill_no'                      => ['nullable', 'string', 'max:255'],
            'bill_amount'                  => ['nullable', 'numeric', 'min:0'],
            'bill_date'                    => ['nullable', 'date'],
            'purchase_date'                => ['nullable', 'date'],
            'status'                       => ['required', 'in:active,under_repair,disposed,scrapped,inactive'],
            'remarks'                      => ['nullable', 'string'],
            'purchase_bill_file'           => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];

        if ($isVehicle) {
            $rules['registration_number']             = ['nullable', 'string', 'max:50'];
            $rules['puc_expiry_date']                 = ['nullable', 'date'];
            $rules['puc_reminder_before_days']        = ['nullable', 'integer', 'min:1', 'max:365'];
            $rules['fitness_expiry_date']             = ['nullable', 'date'];
            $rules['fitness_reminder_before_days']    = ['nullable', 'integer', 'min:1', 'max:365'];
            $rules['road_tax_expiry_date']            = ['nullable', 'date'];
            $rules['road_tax_reminder_before_days']   = ['nullable', 'integer', 'min:1', 'max:365'];
            $rules['vehicle_obv']                     = ['nullable', 'numeric', 'min:0'];
            $rules['vehicle_depreciation_percent']    = ['nullable', 'numeric', 'min:0', 'max:100'];
            $rules['vehicle_depreciation_book_value'] = ['nullable', 'numeric', 'min:0'];
        }

        // Validate subcategory belongs to selected category
        if (request('asset_subcategory_id') && request('asset_category_id')) {
            $rules['asset_subcategory_id'][] = function ($attr, $value, $fail) {
                $sub = AssetSubcategory::find($value);
                if ($sub && (string) $sub->asset_category_id !== (string) request('asset_category_id')) {
                    $fail('The selected subcategory does not belong to the selected category.');
                }
            };
        }

        return $rules;
    }

    // private function isVehicleCategory(?string $categoryId): bool
    // {
    //     if (! $categoryId) {
    //         return false;
    //     }
    //     static $cache = [];
    //     if (! isset($cache[$categoryId])) {
    //         $cat = AssetCategory::find($categoryId);
    //         $cache[$categoryId] = $cat && $cat->code === 'VE';
    //     }
    //     return $cache[$categoryId];
    // }

    private function isVehicleCategory(?string $categoryId = '0'): bool
    {
        #find the vehicle category id from env variable. 
        $vehicle_category_id = config('app.vehicle_category_id');
        return $vehicle_category_id == $categoryId;
    }
}
