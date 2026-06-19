<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetMeterLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetMeterLogController extends Controller
{
    public function store(Request $request, Asset $asset): RedirectResponse
    {
        $validated = $request->validate([
            'unit'          => ['required', 'string', 'max:30'],
            'reading_value' => ['required', 'integer', 'min:0'],
            'logged_at'     => ['required', 'date'],
            'notes'         => ['nullable', 'string', 'max:500'],
            'evidence'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $data = [
            'unit'          => $validated['unit'],
            'reading_value' => $validated['reading_value'],
            'logged_at'     => $validated['logged_at'],
            'notes'         => $validated['notes'] ?? null,
            'created_by'    => auth()->id(),
        ];

        if ($request->hasFile('evidence')) {
            $file = $request->file('evidence');
            $data['evidence_path']          = $file->store('meter-log-evidence', 'public');
            $data['evidence_original_name'] = $file->getClientOriginalName();
        }

        $asset->meterLogs()->create($data);

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
            'evidence'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $data = [
            'unit'          => $validated['unit'],
            'reading_value' => $validated['reading_value'],
            'logged_at'     => $validated['logged_at'],
            'notes'         => $validated['notes'] ?? null,
        ];

        if ($request->hasFile('evidence')) {
            // New file uploaded — replace old one; ignore remove_evidence since a new file takes precedence
            if ($log->evidence_path) {
                Storage::disk('public')->delete($log->evidence_path);
            }
            $file = $request->file('evidence');
            $data['evidence_path']          = $file->store('meter-log-evidence', 'public');
            $data['evidence_original_name'] = $file->getClientOriginalName();
        } elseif ($request->boolean('remove_evidence') && $log->evidence_path) {
            // Explicitly removed with no replacement
            Storage::disk('public')->delete($log->evidence_path);
            $data['evidence_path']          = null;
            $data['evidence_original_name'] = null;
        }

        $log->update($data);

        return redirect()->route('assets.show', [$asset, 'tab' => 'meter-logs'])
            ->with('success', 'Meter reading updated.');
    }

    public function destroy(Asset $asset, AssetMeterLog $log): RedirectResponse
    {
        abort_if($log->asset_id !== $asset->id, 404);

        if ($log->evidence_path) {
            Storage::disk('public')->delete($log->evidence_path);
        }

        $log->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'meter-logs'])
            ->with('success', 'Meter reading deleted.');
    }
}
