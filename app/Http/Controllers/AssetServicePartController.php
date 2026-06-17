<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetService;
use App\Models\AssetServicePart;
use Illuminate\Http\Request;

class AssetServicePartController extends Controller
{
    public function store(Request $request, Asset $asset, AssetService $service)
    {
        abort_if($service->asset_id !== $asset->id, 403);

        $validated = $this->nullOutTrackingFields($request->validate($this->rules()));

        $validated['asset_service_id'] = $service->id;
        $validated['asset_id']         = $asset->id;
        $validated['created_by']       = auth()->id();

        AssetServicePart::create($validated);

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

        return redirect()->route('assets.show', [$asset, 'tab' => 'parts'])
            ->with('success', 'Part record updated successfully.');
    }

    public function destroy(Asset $asset, AssetService $service, AssetServicePart $part)
    {
        abort_if($service->asset_id !== $asset->id, 403);
        abort_if($part->asset_service_id !== $service->id, 403);

        $part->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'parts'])
            ->with('success', 'Part record deleted.');
    }

    private function rules(): array
    {
        return [
            'part_name'                    => ['required', 'string', 'max:255'],
            'quantity'                     => ['required', 'integer', 'min:1'],
            'part_cost'                    => ['nullable', 'numeric', 'min:0'],
            'purchased_from'               => ['nullable', 'string', 'max:255'],
            'warranty_tracking_mode'       => ['nullable', 'in:time,meter,count'],
            'warranty_till'                => ['nullable', 'date'],
            'warranty_unit'                => ['nullable', 'string', 'max:20'],
            'warranty_meter_source'        => ['nullable', 'in:mileage,meter'],
            'warranty_counter_limit'       => ['nullable', 'integer', 'min:1'],
            'warranty_reminder_before_days'  => ['nullable', 'integer', 'min:1', 'max:365'],
            'warranty_reminder_before_units' => ['nullable', 'integer', 'min:1'],
            'remarks'                      => ['nullable', 'string'],
        ];
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
