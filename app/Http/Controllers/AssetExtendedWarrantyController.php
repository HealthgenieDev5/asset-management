<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDocument;
use App\Models\AssetExtendedWarranty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetExtendedWarrantyController extends Controller
{
    public function store(Request $request, Asset $asset)
    {
        $validated = $request->validate($this->rules());
        $this->applyModeNullOut($validated);

        $validated['asset_id']   = $asset->id;
        $validated['created_by'] = auth()->id();

        $ew = AssetExtendedWarranty::create($validated);

        $this->storeDocuments($request, $asset, $ew);

        return redirect()->route('assets.show', [$asset, 'tab' => 'ext-warranty'])
            ->with('success', 'Extended warranty added successfully.');
    }

    public function update(Request $request, Asset $asset, AssetExtendedWarranty $ew)
    {
        abort_if($ew->asset_id !== $asset->id, 403);

        $validated = $request->validate($this->rules());
        $this->applyModeNullOut($validated);
        $validated['updated_by'] = auth()->id();

        $ew->update($validated);

        $this->storeDocuments($request, $asset, $ew);

        return redirect()->route('assets.show', [$asset, 'tab' => 'ext-warranty'])
            ->with('success', 'Extended warranty updated successfully.');
    }

    public function destroy(Asset $asset, AssetExtendedWarranty $ew)
    {
        abort_if($ew->asset_id !== $asset->id, 403);

        foreach ($ew->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }

        $ew->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'ext-warranty'])
            ->with('success', 'Extended warranty deleted.');
    }

    public function storeDocument(Request $request, Asset $asset, AssetExtendedWarranty $ew)
    {
        abort_if($ew->asset_id !== $asset->id, 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $file->store("assets/{$asset->id}/ext-warranty", 'public');

        $doc = AssetDocument::create([
            'asset_id'           => $asset->id,
            'documentable_type'  => AssetExtendedWarranty::class,
            'documentable_id'    => $ew->id,
            'document_type'      => 'extended_warranty_bill',
            'document_title'     => 'Extended Warranty Document',
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

    private function rules(): array
    {
        return [
            'extended_warranty_vendor'                => ['nullable', 'string', 'max:255'],
            'extended_warranty_date_from'             => ['nullable', 'date'],
            'extended_warranty_date_to'               => ['nullable', 'date', 'after_or_equal:extended_warranty_date_from'],
            'extended_warranty_bill_no'               => ['nullable', 'string', 'max:255'],
            'extended_warranty_amount'                => ['nullable', 'numeric', 'min:0'],
            'extended_warranty_terms'                 => ['nullable', 'string'],
            'reminder_before_days'                    => ['nullable', 'integer', 'min:1', 'max:365'],
            'extended_warranty_counter_limit'         => ['nullable', 'integer', 'min:1'],
            'extended_warranty_reminder_before_units' => ['nullable', 'integer', 'min:1'],
            'ew_tracking_mode'                        => ['required', 'in:time,meter,count'],
            'ew_unit'                                 => ['nullable', 'string', 'max:20'],
            'ew_meter_source'                         => ['nullable', 'in:mileage,meter'],
            'remarks'                                 => ['nullable', 'string'],
            'extended_warranty_bill'                  => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'extended_warranty_image'                 => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    private function applyModeNullOut(array &$validated): void
    {
        $mode = $validated['ew_tracking_mode'] ?? 'time';

        if ($mode === 'time') {
            $validated['extended_warranty_counter_limit']         = null;
            $validated['extended_warranty_reminder_before_units'] = null;
            $validated['ew_unit']                                 = null;
        } else {
            $validated['extended_warranty_date_from'] = null;
            $validated['extended_warranty_date_to']   = null;
            $validated['reminder_before_days']        = null;
            if ($mode !== 'meter') {
                $validated['ew_meter_source'] = null;
            }
        }
    }

    private function storeDocuments(Request $request, Asset $asset, AssetExtendedWarranty $ew): void
    {
        $uploads = [
            'extended_warranty_bill'  => 'extended_warranty_bill',
            'extended_warranty_image' => 'extended_warranty_image',
        ];

        foreach ($uploads as $inputName => $docType) {
            if (! $request->hasFile($inputName)) {
                continue;
            }

            $file = $request->file($inputName);
            $path = $file->store("assets/{$asset->id}/ext-warranty", 'public');

            AssetDocument::create([
                'asset_id'           => $asset->id,
                'documentable_type'  => AssetExtendedWarranty::class,
                'documentable_id'    => $ew->id,
                'document_type'      => $docType,
                'document_title'     => $docType === 'extended_warranty_bill' ? 'Extended Warranty Bill' : 'Warranty Activation Image',
                'file_path'          => $path,
                'file_original_name' => $file->getClientOriginalName(),
                'file_mime_type'     => $file->getClientMimeType(),
                'file_size'          => $file->getSize(),
                'uploaded_by'        => auth()->id(),
            ]);
        }
    }
}
