<?php

namespace App\Concerns;

use App\Models\Asset;
use App\Models\AssetDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

trait FilePondDocumentHandler
{
    protected function performStoreDocument(
        Request $request,
        Asset $asset,
        Model $documentable,
        string $folder,
        string $documentType,
        string $documentTitle
    ) {
        abort_if($documentable->asset_id !== $asset->id, 403);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $file = $request->file('file');
        $path = $file->store("assets/{$asset->id}/{$folder}", 'public');

        $doc = AssetDocument::create([
            'asset_id'           => $asset->id,
            'documentable_type'  => $documentable::class,
            'documentable_id'    => $documentable->id,
            'document_type'      => $documentType,
            'document_title'     => $documentTitle,
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
        $doc = AssetDocument::where('id', $id)
            ->where('asset_id', $asset->id)
            ->firstOrFail();

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
}
