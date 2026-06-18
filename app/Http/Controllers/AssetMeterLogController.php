<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMeterLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssetMeterLogController extends Controller
{
    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validate([
            'unit'          => ['required', 'string', 'max:30'],
            'reading_value' => ['required', 'integer', 'min:0'],
            'logged_at'     => ['required', 'date'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ]);

        $asset->meterLogs()->create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('assets.show', [$asset, 'tab' => 'meter-logs'])
            ->with('success', 'Meter reading logged.');
    }

    public function update(Request $request, Asset $asset, AssetMeterLog $log): RedirectResponse
    {
        abort_if($log->asset_id !== $asset->id, 404);

        $validated = $request->validate([
            'unit'          => ['required', 'string', 'max:30'],
            'reading_value' => ['required', 'integer', 'min:0'],
            'logged_at'     => ['required', 'date'],
            'notes'         => ['nullable', 'string', 'max:500'],
        ]);

        $log->update($validated);

        return redirect()->route('assets.show', [$asset, 'tab' => 'meter-logs'])
            ->with('success', 'Meter reading updated.');
    }

    public function destroy(Asset $asset, AssetMeterLog $log): RedirectResponse
    {
        abort_if($log->asset_id !== $asset->id, 404);

        $log->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'meter-logs'])
            ->with('success', 'Meter reading deleted.');
    }
}
