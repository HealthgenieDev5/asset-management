<?php

namespace App\Http\Controllers;

use App\Exports\AssetFullExport;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetDocument;
use App\Models\AssetSubcategory;
use App\Models\Department;
use App\Models\Location;
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
        $filename = 'assets-' . now()->format('Y-m-d') . '.xlsx';
        return Excel::download(new AssetFullExport($filters), $filename);
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
        $asset->load([
            'category',
            'subcategory',
            'documents.uploader',
            'extendedWarranties.documents.uploader',
            'amcContracts.documents',
            'insurancePolicies.documents',
            'services.documents',
            'services.parts.documents',
            'complaints.comments.user',
            'complaints.documents',
            'complaints.service',
            'smartReminders',
            'maintenanceSchedules',
            'meterLogs',
        ]);
        $tab = request('tab', 'overview');

        return view('assets.show', compact('asset', 'tab'));
    }

    public function edit(Asset $asset)
    {
        $asset->load(['category', 'documents', 'extendedWarranties.documents']);
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

    private function isVehicleCategory(?string $categoryId): bool
    {
        if (! $categoryId) {
            return false;
        }
        $cat = AssetCategory::find($categoryId);
        return $cat && $cat->code === 'VE';
    }
}
