<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetWarrantyController extends Controller
{
    public function update(Request $request, Asset $asset)
    {
        $validated = $request->validate([
            'warranty_details'              => ['nullable', 'string'],
            'warranty_lapse_date'           => ['nullable', 'date'],
            'warranty_reminder_before_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'warranty_card'                 => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
            'warranty_activation_image'     => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $asset->update([
            'warranty_details'              => $validated['warranty_details'] ?? null,
            'warranty_lapse_date'           => $validated['warranty_lapse_date'] ?? null,
            'warranty_reminder_before_days' => $validated['warranty_reminder_before_days'] ?? null,
        ]);

        $this->storeDocuments($request, $asset);

        return redirect()->route('assets.show', [$asset, 'tab' => 'warranty'])
            ->with('success', 'Warranty details updated successfully.');
    }

    public function destroyDocument(Asset $asset, AssetDocument $document)
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'warranty'])
            ->with('success', 'Document deleted.');
    }

    private function storeDocuments(Request $request, Asset $asset): void
    {
        $uploads = [
            'warranty_card'             => 'warranty_card',
            'warranty_activation_image' => 'warranty_activation_image',
        ];

        foreach ($uploads as $field => $docType) {
            if (! $request->hasFile($field)) {
                continue;
            }

            $file = $request->file($field);
            $path = $file->store("assets/{$asset->id}/warranty", 'public');

            AssetDocument::create([
                'asset_id'           => $asset->id,
                'documentable_type'  => Asset::class,
                'documentable_id'    => $asset->id,
                'document_type'      => $docType,
                'document_title'     => $docType === 'warranty_card' ? 'Warranty Card' : 'Warranty Activation Image',
                'file_path'          => $path,
                'file_original_name' => $file->getClientOriginalName(),
                'file_mime_type'     => $file->getClientMimeType(),
                'file_size'          => $file->getSize(),
                'uploaded_by'        => auth()->id(),
            ]);
        }
    }
}
