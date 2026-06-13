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

    private function rules(): array
    {
        return [
            'extended_warranty_vendor'    => ['nullable', 'string', 'max:255'],
            'extended_warranty_date_from' => ['nullable', 'date'],
            'extended_warranty_date_to'   => ['nullable', 'date', 'after_or_equal:extended_warranty_date_from'],
            'extended_warranty_bill_no'   => ['nullable', 'string', 'max:255'],
            'extended_warranty_amount'    => ['nullable', 'numeric', 'min:0'],
            'extended_warranty_terms'     => ['nullable', 'string'],
            'reminder_before_days'        => ['nullable', 'integer', 'min:1', 'max:365'],
            'remarks'                     => ['nullable', 'string'],
            'extended_warranty_bill'      => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'extended_warranty_image'     => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
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
