<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetDocumentController extends Controller
{
    private const ALLOWED_TYPES = [
        'purchase_bill'             => 'Purchase Bill',
        'invoice'                   => 'Invoice',
        'warranty_card'             => 'Warranty Card',
        'warranty_activation_image' => 'Warranty Activation Image',
        'extended_warranty_bill'    => 'Extended Warranty Bill',
        'extended_warranty_image'   => 'Extended Warranty Image',
        'insurance_copy'            => 'Insurance Copy',
        'insurance_policy'          => 'Insurance Policy',
        'puc_copy'                  => 'PUC Copy',
        'rc_copy'                   => 'RC Copy',
        'service_bill'              => 'Service Bill',
        'amc_bill'                  => 'AMC Bill',
        'amc_image'                 => 'AMC Image',
        'inspection_certificate'    => 'Inspection Certificate',
        'compliance_certificate'    => 'Compliance Certificate',
        'vehicle_document'          => 'Vehicle Document',
        'asset_photo'               => 'Asset Photo',
        'other'                     => 'Other',
    ];

    public function store(Request $request, Asset $asset)
    {
        $request->validate([
            'document_type'  => ['required', 'in:' . implode(',', array_keys(self::ALLOWED_TYPES))],
            'document_title' => ['nullable', 'string', 'max:255'],
            'remarks'        => ['nullable', 'string', 'max:1000'],
            'file'           => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp,doc,docx,xls,xlsx', 'max:10240'],
        ]);

        $file  = $request->file('file');
        $path  = $file->store("assets/{$asset->id}/documents", 'public');
        $title = $request->input('document_title')
            ?: (self::ALLOWED_TYPES[$request->document_type] ?? 'Document');

        $doc = AssetDocument::create([
            'asset_id'           => $asset->id,
            'documentable_type'  => Asset::class,
            'documentable_id'    => $asset->id,
            'document_type'      => $request->document_type,
            'document_title'     => $title,
            'file_path'          => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime_type'     => $file->getClientMimeType(),
            'file_size'          => $file->getSize(),
            'remarks'            => $request->input('remarks'),
            'uploaded_by'        => auth()->id(),
        ]);

        if ($request->ajax()) {
            return response((string) $doc->id, 200)
                ->header('Content-Type', 'text/plain');
        }

        $tab = $request->input('_tab', 'documents');

        return redirect()->route('assets.show', [$asset, 'tab' => $tab])
            ->with('success', 'Document uploaded successfully.');
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

    public function destroy(Request $request, Asset $asset, AssetDocument $document)
    {
        abort_if($document->asset_id !== $asset->id, 403);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        $tab = $request->input('_tab', 'documents');

        return redirect()->route('assets.show', [$asset, 'tab' => $tab])
            ->with('success', 'Document deleted.');
    }
}
