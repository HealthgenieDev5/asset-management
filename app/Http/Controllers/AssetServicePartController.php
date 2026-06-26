<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDocument;
use App\Models\AssetService;
use App\Models\AssetServicePart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetServicePartController extends Controller
{
    public function store(Request $request, Asset $asset, AssetService $service)
    {
        abort_if($service->asset_id !== $asset->id, 403);

        $validated = $this->nullOutTrackingFields($request->validate($this->rules()));

        $validated['asset_service_id'] = $service->id;
        $validated['asset_id']         = $asset->id;
        $validated['created_by']       = auth()->id();

        $part = AssetServicePart::create($validated);
        $this->saveFormDocument($request, $asset, $part);

        return redirect()->route('assets.show', [$asset, 'tab' => 'parts'])
            ->with('success', 'Part record added successfully.');
    }

    public function update(Request $request, Asset $asset, AssetService $service, AssetServicePart $part)
    {
        abort_if($service->asset_id !== $asset->id, 403);
        abort_if($part->asset_service_id !== $service->id, 403);

        $validated = $this->nullOutTrackingFields($request->validate($this->rules()));
        $validated['updated_by'] = auth()->id();

        $part->update($validated);
        $this->saveFormDocument($request, $asset, $part);

        return redirect()->route('assets.show', [$asset, 'tab' => 'parts'])
            ->with('success', 'Part record updated successfully.');
    }

    public function patchField(Request $request, Asset $asset, AssetService $service, AssetServicePart $part)
    {
        abort_if($service->asset_id !== $asset->id, 403);
        abort_if($part->asset_service_id !== $service->id, 403);

        $allowed = [
            'part_name', 'part_serial_number', 'part_cost', 'purchased_from', 'vendor_id', 'bill_no',
            'warranty_tracking_mode', 'warranty_till', 'warranty_unit',
            'warranty_meter_source', 'warranty_counter_limit',
            'warranty_reminder_before_days', 'warranty_reminder_before_units',
            'remarks',
        ];

        $field = $request->input('field');
        abort_if(! in_array($field, $allowed, true), 422);

        $rules = [
            'part_name'                      => ['required', 'string', 'max:255'],
            'part_serial_number'             => ['nullable', 'string', 'max:255'],
            'part_cost'                      => ['nullable', 'numeric', 'min:0'],
            'purchased_from'                 => ['nullable', 'string', 'max:255'],
            'vendor_id'                      => ['nullable', 'integer', 'exists:vendors,id'],
            'bill_no'                        => ['nullable', 'string', 'max:255'],
            'warranty_tracking_mode'         => ['nullable', 'in:time,meter,count'],
            'warranty_till'                  => ['nullable', 'date'],
            'warranty_unit'                  => ['nullable', 'string', 'max:20'],
            'warranty_meter_source'          => ['nullable', 'in:mileage,meter'],
            'warranty_counter_limit'         => ['nullable', 'integer', 'min:1'],
            'warranty_reminder_before_days'  => ['nullable', 'integer', 'min:1', 'max:365'],
            'warranty_reminder_before_units' => ['nullable', 'integer', 'min:1'],
            'remarks'                        => ['nullable', 'string'],
        ];

        $validated = $request->validate(['value' => $rules[$field]]);
        $value     = $validated['value'] ?: null;

        $part->update([$field => $value, 'updated_by' => auth()->id()]);

        $label = null;
        if ($field === 'vendor_id' && $value) {
            $label = \App\Models\Vendor::where('id', $value)->value('name');
        }

        return response()->json(['ok' => true, 'label' => $label]);
    }

    public function destroy(Asset $asset, AssetService $service, AssetServicePart $part)
    {
        abort_if($service->asset_id !== $asset->id, 403);
        abort_if($part->asset_service_id !== $service->id, 403);

        foreach ($part->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }
        $part->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'parts'])
            ->with('success', 'Part record deleted.');
    }

    private function rules(): array
    {
        return [
            'part_name'                    => ['required', 'string', 'max:255'],
            'part_serial_number'           => ['nullable', 'string', 'max:255'],
            'part_cost'                    => ['nullable', 'numeric', 'min:0'],
            'purchased_from'               => ['nullable', 'string', 'max:255'],
            'vendor_id'                    => ['nullable', 'integer', 'exists:vendors,id'],
            'bill_no'                      => ['nullable', 'string', 'max:255'],
            'warranty_tracking_mode'       => ['nullable', 'in:time,meter,count'],
            'warranty_till'                => ['nullable', 'date'],
            'warranty_unit'                => ['nullable', 'string', 'max:20'],
            'warranty_meter_source'        => ['nullable', 'in:mileage,meter'],
            'warranty_counter_limit'       => ['nullable', 'integer', 'min:1'],
            'warranty_reminder_before_days'  => ['nullable', 'integer', 'min:1', 'max:365'],
            'warranty_reminder_before_units' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'remarks'                      => ['nullable', 'string'],
        ];
    }

    public function storeDocument(Request $request, Asset $asset, AssetServicePart $part)
    {
        abort_if($part->asset_id !== $asset->id, 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $file->store("assets/{$asset->id}/service-parts", 'public');

        $doc = AssetDocument::create([
            'asset_id'           => $asset->id,
            'documentable_type'  => AssetServicePart::class,
            'documentable_id'    => $part->id,
            'document_type'      => 'service_part_bill',
            'document_title'     => 'Part Document',
            'file_path'          => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime_type'     => $file->getClientMimeType(),
            'file_size'          => $file->getSize(),
            'uploaded_by'        => auth()->id(),
        ]);

        return response((string) $doc->id, 200)->header('Content-Type', 'text/plain');
    }

    public function revertDocument(Request $request, Asset $asset)
    {
        $id  = (int) $request->getContent();
        $doc = AssetDocument::where('id', $id)->where('asset_id', $asset->id)->firstOrFail();

        Storage::disk('public')->delete($doc->file_path);
        $doc->delete();

        return response('', 200);
    }

    public function destroyDocument(Asset $asset, AssetDocument $document)
    {
        abort_if($document->asset_id !== $asset->id, 403);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return response('', 200);
    }

    private function saveFormDocument(Request $request, Asset $asset, AssetServicePart $part): void
    {
        if (! $request->hasFile('part_doc') || ! $request->file('part_doc')->isValid()) {
            return;
        }

        $request->validate([
            'part_doc' => ['file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('part_doc');
        $path = $file->store("assets/{$asset->id}/service-parts", 'public');

        AssetDocument::create([
            'asset_id'           => $asset->id,
            'documentable_type'  => AssetServicePart::class,
            'documentable_id'    => $part->id,
            'document_type'      => 'service_part_bill',
            'document_title'     => 'Part Document',
            'file_path'          => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime_type'     => $file->getClientMimeType(),
            'file_size'          => $file->getSize(),
            'uploaded_by'        => auth()->id(),
        ]);
    }

    private function nullOutTrackingFields(array $data): array
    {
        $mode = $data['warranty_tracking_mode'] ?? 'time';
        if ($mode === 'time') {
            $data['warranty_counter_limit']       = null;
            $data['warranty_reminder_before_units'] = null;
            $data['warranty_unit']                = null;
            $data['warranty_meter_source']        = null;
        } else {
            $data['warranty_till']                  = null;
            $data['warranty_reminder_before_days']  = null;
            if ($mode !== 'meter') {
                $data['warranty_meter_source'] = null;
            }
        }
        return $data;
    }
}
