<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDocument;
use App\Models\AssetService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetServiceController extends Controller
{
    public function store(Request $request, Asset $asset)
    {
        $validated = $request->validate($this->rules());

        $validated['asset_id']   = $asset->id;
        $validated['created_by'] = auth()->id();

        $service = AssetService::create($validated);

        $this->storeDocument($request, $asset, $service);

        return redirect()->route('assets.show', [$asset, 'tab' => 'services'])
            ->with('success', 'Service record added successfully.');
    }

    public function update(Request $request, Asset $asset, AssetService $service)
    {
        abort_if($service->asset_id !== $asset->id, 403);

        $validated = $request->validate($this->rules());
        $validated['updated_by'] = auth()->id();

        $service->update($validated);

        $this->storeDocument($request, $asset, $service);

        return redirect()->route('assets.show', [$asset, 'tab' => 'services'])
            ->with('success', 'Service record updated successfully.');
    }

    public function destroy(Asset $asset, AssetService $service)
    {
        abort_if($service->asset_id !== $asset->id, 403);

        foreach ($service->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }

        $service->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'services'])
            ->with('success', 'Service record deleted.');
    }

    public function destroyDocument(Asset $asset, AssetDocument $document)
    {
        abort_if($document->asset_id !== $asset->id, 403);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'services'])
            ->with('success', 'Document removed.');
    }

    private function rules(): array
    {
        return [
            'service_type'                      => ['required', 'in:preventive_maintenance,corrective_maintenance,inspection,repair,calibration,cleaning,other'],
            'service_date'                      => ['required', 'date'],
            'vendor_id'                         => ['nullable', 'integer', 'exists:vendors,id'],
            'technician_name'                   => ['nullable', 'string', 'max:255'],
            'work_done'                         => ['nullable', 'string'],
            'service_cost'                      => ['nullable', 'numeric', 'min:0'],
            'bill_no'                           => ['nullable', 'string', 'max:255'],
            'bill_date'                         => ['nullable', 'date'],
            'next_service_date'                 => ['nullable', 'date'],
            'service_interval_value'            => ['nullable', 'integer', 'min:1'],
            'service_interval_unit'             => ['nullable', 'in:days,weeks,months,years,operating_hours,kilometers'],
            'meter_reading'                     => ['nullable', 'integer', 'min:0'],
            'mileage_reading'                   => ['nullable', 'integer', 'min:0'],
            'downtime_hours'                    => ['nullable', 'numeric', 'min:0'],
            'condition_rating'                  => ['nullable', 'in:excellent,good,fair,poor,critical'],
            'certification_expiry'              => ['nullable', 'date'],
            'certification_reminder_before_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'next_service_reminder_before_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'safety_notes'                      => ['nullable', 'string'],
            'remarks'                           => ['nullable', 'string'],
            'service_bill'                      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    private function storeDocument(Request $request, Asset $asset, AssetService $service): void
    {
        if (! $request->hasFile('service_bill')) {
            return;
        }

        $file = $request->file('service_bill');
        $path = $file->store("assets/{$asset->id}/services", 'public');

        AssetDocument::create([
            'asset_id'           => $asset->id,
            'documentable_type'  => AssetService::class,
            'documentable_id'    => $service->id,
            'document_type'      => 'service_bill',
            'document_title'     => 'Service Bill',
            'file_path'          => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime_type'     => $file->getClientMimeType(),
            'file_size'          => $file->getSize(),
            'uploaded_by'        => auth()->id(),
        ]);
    }
}
