<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDocument;
use App\Models\AssetService;
use App\Models\AssetServicePart;
use App\Models\AssetSmartReminder;
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
        $this->storeDocument($request, $asset, $part);
        $this->syncSmartReminder($request, $asset, $part);

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
        $this->storeDocument($request, $asset, $part);
        $this->syncSmartReminder($request, $asset, $part);

        return redirect()->route('assets.show', [$asset, 'tab' => 'parts'])
            ->with('success', 'Part record updated successfully.');
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

    public function destroyDocument(Asset $asset, AssetDocument $document)
    {
        abort_if($document->asset_id !== $asset->id, 403);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'parts'])
            ->with('success', 'Document removed.');
    }

    private function storeDocument(Request $request, Asset $asset, AssetServicePart $part): void
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

    private function syncSmartReminder(Request $request, Asset $asset, AssetServicePart $part): void
    {
        $raw = $request->input('sr_reminder_days', '');
        $days = array_values(array_unique(array_filter(
            array_map('intval', preg_split('/[\s,]+/', trim($raw))),
            fn($d) => $d > 0
        )));

        $existing = AssetSmartReminder::where('remindable_type', AssetServicePart::class)
            ->where('remindable_id', $part->id)
            ->first();

        if (empty($days)) {
            $existing?->delete();
            return;
        }

        $data = [
            'reminder_name'   => $part->part_name . ' Part Warranty Reminder',
            'reminder_type'   => 'part_warranty',
            'reminder_mode'   => $part->warranty_tracking_mode === 'time' ? 'time' : ($part->warranty_tracking_mode ?? 'meter'),
            'counter_limit'   => $part->warranty_counter_limit,
            'threshold_unit'  => $part->warranty_unit,
            'reminder_days'   => $days,
            'is_active'       => true,
            'remindable_type' => AssetServicePart::class,
            'remindable_id'   => $part->id,
        ];

        if ($existing) {
            $existing->update(array_merge($data, ['updated_by' => auth()->id()]));
        } else {
            $asset->smartReminders()->create(array_merge($data, [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ]));
        }
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
