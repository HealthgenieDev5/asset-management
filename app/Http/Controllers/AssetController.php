<?php

namespace App\Http\Controllers;

use App\Exports\AssetFullExport;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDocument;
use App\Models\AssetExtendedWarranty;
use App\Models\AssetSubcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

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
            ->when($request->category_id, fn ($q, $id) => $q->where('asset_category_id', $id))
            ->when($request->subcategory_id, fn ($q, $id) => $q->where('asset_subcategory_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
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
        $filename = 'assets-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new AssetFullExport($filters), $filename);
    }

    public function create()
    {
        $categories = AssetCategory::active()->orderBy('name')->get();

        return view('assets.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $isVehicle = $this->isVehicleCategory($request->asset_category_id);

        $validated = $request->validate($this->rules($isVehicle));

        $validated['asset_code'] = Asset::generateAssetCode((int) $validated['asset_category_id']);
        $validated['created_by'] = auth()->id();
        $validated['inspection_required'] = $request->boolean('inspection_required');

        if (! $isVehicle) {
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

        $this->storeWarrantyDocuments($request, $asset);
        $this->saveExtendedWarranty($request, $asset);

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset created successfully.');
    }

    public function show(Asset $asset)
    {
        $asset->load([
            'category', 'subcategory',
            'documents.uploader',
            'extendedWarranties.documents.uploader',
            'amcContracts.documents',
            'insurancePolicies.documents',
            'services.documents',
            'services.parts',
        ]);
        $tab = request('tab', 'overview');

        return view('assets.show', compact('asset', 'tab'));
    }

    public function edit(Asset $asset)
    {
        $asset->load(['documents', 'extendedWarranties.documents']);
        $categories = AssetCategory::active()->orderBy('name')->get();
        $subcategories = AssetSubcategory::where('asset_category_id', $asset->asset_category_id)
            ->active()->orderBy('name')->get();

        return view('assets.edit', compact('asset', 'categories', 'subcategories'));
    }

    public function update(Request $request, Asset $asset)
    {
        $isVehicle = $this->isVehicleCategory($request->asset_category_id);

        $validated = $request->validate($this->rules($isVehicle, $asset->id));

        $validated['updated_by'] = auth()->id();
        $validated['inspection_required'] = $request->boolean('inspection_required');

        if (! $isVehicle) {
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

        $this->storeWarrantyDocuments($request, $asset);
        $this->saveExtendedWarranty($request, $asset);

        return redirect()->route('assets.show', $asset)
            ->with('success', 'Asset updated successfully.');
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
            'warranty_details'             => ['nullable', 'string'],
            'warranty_lapse_date'          => ['nullable', 'date'],
            'warranty_reminder_before_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'maintenance_schedule_type'    => ['required', 'in:date_based,hours_based,mileage_based,custom,none'],
            'maintenance_interval_value'   => ['nullable', 'integer', 'min:1'],
            'maintenance_interval_unit'    => ['nullable', 'in:days,weeks,months,years,operating_hours,miles,kilometers'],
            'inspection_required'          => ['nullable', 'boolean'],
            'inspection_frequency_value'   => ['nullable', 'integer', 'min:1'],
            'inspection_frequency_unit'    => ['nullable', 'in:days,weeks,months,years'],
            'status'                       => ['required', 'in:active,under_repair,disposed,scrapped,inactive'],
            'remarks'                      => ['nullable', 'string'],
        ];

        if ($isVehicle) {
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

        $rules['warranty_card']             = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'];
        $rules['warranty_activation_image'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'];

        // Extended warranty fields
        $rules['ew_vendor']        = ['nullable', 'string', 'max:255'];
        $rules['ew_date_from']     = ['nullable', 'date'];
        $rules['ew_date_to']       = ['nullable', 'date', 'after_or_equal:ew_date_from'];
        $rules['ew_bill_no']       = ['nullable', 'string', 'max:255'];
        $rules['ew_amount']        = ['nullable', 'numeric', 'min:0'];
        $rules['ew_terms']         = ['nullable', 'string'];
        $rules['ew_reminder_days'] = ['nullable', 'integer', 'min:1', 'max:365'];
        $rules['ew_remarks']       = ['nullable', 'string'];
        $rules['ew_bill_image']    = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'];
        $rules['ew_activation_image'] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'];

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

    private function saveExtendedWarranty(Request $request, Asset $asset): void
    {
        // Only proceed if any ew_ field has a value
        $hasData = $request->filled('ew_vendor')
            || $request->filled('ew_date_from')
            || $request->filled('ew_date_to')
            || $request->filled('ew_bill_no')
            || $request->filled('ew_amount')
            || $request->hasFile('ew_bill_image')
            || $request->hasFile('ew_activation_image');

        if (! $hasData) {
            return;
        }

        // On edit, update the first existing record; otherwise create a new one
        $ew = $asset->extendedWarranties()->first()
            ?? new AssetExtendedWarranty(['asset_id' => $asset->id]);

        $ew->fill([
            'asset_id'                    => $asset->id,
            'extended_warranty_vendor'    => $request->input('ew_vendor'),
            'extended_warranty_date_from' => $request->input('ew_date_from') ?: null,
            'extended_warranty_date_to'   => $request->input('ew_date_to') ?: null,
            'extended_warranty_bill_no'   => $request->input('ew_bill_no'),
            'extended_warranty_amount'    => $request->input('ew_amount') ?: null,
            'extended_warranty_terms'     => $request->input('ew_terms'),
            'reminder_before_days'        => $request->input('ew_reminder_days') ?: null,
            'remarks'                     => $request->input('ew_remarks'),
        ])->save();

        // Store uploaded files
        $ewFiles = [
            'ew_bill_image'       => 'extended_warranty_bill',
            'ew_activation_image' => 'extended_warranty_image',
        ];

        foreach ($ewFiles as $field => $docType) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            $path = $file->store("assets/{$asset->id}/extended-warranty", 'public');

            AssetDocument::create([
                'asset_id'           => $asset->id,
                'documentable_type'  => AssetExtendedWarranty::class,
                'documentable_id'    => $ew->id,
                'document_type'      => $docType,
                'document_title'     => $docType === 'extended_warranty_bill' ? 'Extended Warranty Bill' : 'Extended Warranty Activation Image',
                'file_path'          => $path,
                'file_original_name' => $file->getClientOriginalName(),
                'file_mime_type'     => $file->getClientMimeType(),
                'file_size'          => $file->getSize(),
                'uploaded_by'        => auth()->id(),
            ]);
        }
    }

    private function storeWarrantyDocuments(Request $request, Asset $asset): void
    {
        $uploads = [
            'warranty_card'             => 'warranty_card',
            'warranty_activation_image' => 'warranty_activation_image',
        ];

        foreach ($uploads as $field => $docType) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            $path = $file->store("assets/{$asset->id}/warranty", 'public');

            AssetDocument::create([
                'asset_id'            => $asset->id,
                'documentable_type'   => Asset::class,
                'documentable_id'     => $asset->id,
                'document_type'       => $docType,
                'document_title'      => $docType === 'warranty_card' ? 'Warranty Card' : 'Warranty Activation Image',
                'file_path'           => $path,
                'file_original_name'  => $file->getClientOriginalName(),
                'file_mime_type'      => $file->getClientMimeType(),
                'file_size'           => $file->getSize(),
                'uploaded_by'         => auth()->id(),
            ]);
        }
    }

    private function isVehicleCategory(?string $categoryId): bool
    {
        if (! $categoryId) {
            return false;
        }
        $cat = AssetCategory::find($categoryId);
        return $cat && $cat->code === 'VE';
    }
}
