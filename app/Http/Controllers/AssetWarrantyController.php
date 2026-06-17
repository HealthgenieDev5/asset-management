<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDocument;
use App\Models\AssetWarranty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetWarrantyController extends Controller
{
    public function store(Request $request, Asset $asset)
    {
        $validated = $request->validate($this->rules());
        $this->applyModeNullOut($validated);

        $validated['asset_id']   = $asset->id;
        $validated['created_by'] = auth()->id();
        $validated['status']     = 'active';

        $warranty = AssetWarranty::create($validated);

        $this->storeDocuments($request, $asset, $warranty);

        return redirect()->route('assets.show', [$asset, 'tab' => 'warranty'])
            ->with('success', 'Warranty added successfully.');
    }

    public function update(Request $request, Asset $asset, AssetWarranty $warranty)
    {
        abort_if($warranty->asset_id !== $asset->id, 403);

        $validated = $request->validate($this->rules());
        $this->applyModeNullOut($validated);
        $validated['updated_by'] = auth()->id();

        $warranty->update($validated);

        $this->storeDocuments($request, $asset, $warranty);

        return redirect()->route('assets.show', [$asset, 'tab' => 'warranty'])
            ->with('success', 'Warranty updated successfully.');
    }

    public function destroy(Asset $asset, AssetWarranty $warranty)
    {
        abort_if($warranty->asset_id !== $asset->id, 403);

        foreach ($warranty->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }

        $warranty->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'warranty'])
            ->with('success', 'Warranty entry deleted.');
    }

    public function dispose(Request $request, Asset $asset, AssetWarranty $warranty)
    {
        abort_if($warranty->asset_id !== $asset->id, 403);

        $validated = $request->validate([
            'disposed_at'     => ['required', 'date'],
            'disposed_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $warranty->update([
            'status'          => 'disposed',
            'disposed_at'     => $validated['disposed_at'],
            'disposed_reason' => $validated['disposed_reason'] ?? null,
            'updated_by'      => auth()->id(),
        ]);

        return redirect()->route('assets.show', [$asset, 'tab' => 'warranty'])
            ->with('success', 'Warranty marked as disposed.');
    }

    public function destroyDocument(Asset $asset, AssetDocument $document)
    {
        abort_if($document->asset_id !== $asset->id, 403);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'warranty'])
            ->with('success', 'Document removed.');
    }

    private function rules(): array
    {
        return [
            'warranty_type'         => ['required', 'in:original,extended'],
            'scope'                 => ['required', 'in:overall,part'],
            'part_name'             => ['required_if:scope,part', 'nullable', 'string', 'max:100'],
            'part_serial_number'    => ['nullable', 'string', 'max:100'],
            'vendor'                => ['nullable', 'string', 'max:255'],
            'bill_no'               => ['nullable', 'string', 'max:255'],
            'bill_amount'           => ['nullable', 'numeric', 'min:0'],
            'details'               => ['nullable', 'string'],
            'terms'                 => ['nullable', 'string'],
            'tracking_mode'         => ['required', 'in:time,meter,count'],
            'unit'                  => ['nullable', 'string', 'max:20'],
            'meter_source'          => ['nullable', 'in:mileage,meter'],
            'date_from'             => ['nullable', 'date'],
            'expiry_date'           => ['nullable', 'date'],
            'reminder_before_days'  => ['nullable', 'integer', 'min:1', 'max:365'],
            'counter_limit'         => ['nullable', 'integer', 'min:1'],
            'reminder_before_units' => ['nullable', 'integer', 'min:1'],
            'remarks'               => ['nullable', 'string'],
            'warranty_doc'          => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    private function applyModeNullOut(array &$validated): void
    {
        $mode = $validated['tracking_mode'] ?? 'time';

        if ($mode === 'time') {
            $validated['counter_limit']         = null;
            $validated['reminder_before_units'] = null;
            $validated['unit']                  = null;
            $validated['meter_source']          = null;
        } else {
            $validated['expiry_date']           = null;
            $validated['reminder_before_days']  = null;
            if ($mode !== 'meter') {
                $validated['meter_source']      = null;
            }
        }
    }

    private function storeDocuments(Request $request, Asset $asset, AssetWarranty $warranty): void
    {
        if (! $request->hasFile('warranty_doc')) {
            return;
        }

        $file = $request->file('warranty_doc');
        $path = $file->store("assets/{$asset->id}/warranties", 'public');

        AssetDocument::create([
            'asset_id'           => $asset->id,
            'documentable_type'  => AssetWarranty::class,
            'documentable_id'    => $warranty->id,
            'document_type'      => 'warranty_doc',
            'document_title'     => 'Warranty Document',
            'file_path'          => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime_type'     => $file->getClientMimeType(),
            'file_size'          => $file->getSize(),
            'uploaded_by'        => auth()->id(),
        ]);
    }
}
