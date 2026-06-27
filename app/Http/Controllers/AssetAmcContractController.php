<?php

namespace App\Http\Controllers;

use App\Concerns\FilePondDocumentHandler;
use App\Models\Asset;
use App\Models\AssetAmcContract;
use App\Models\AssetDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetAmcContractController extends Controller
{
    use FilePondDocumentHandler;

    public function store(Request $request, Asset $asset)
    {
        $validated = $request->validate($this->rules());

        $validated['asset_id']    = $asset->id;
        $validated['created_by']  = auth()->id();

        $amc = AssetAmcContract::create($validated);

        $this->storeDocuments($request, $asset, $amc);

        return redirect()->route('assets.show', [$asset, 'tab' => 'amc'])
            ->with('success', 'AMC contract added successfully.');
    }

    public function update(Request $request, Asset $asset, AssetAmcContract $amc)
    {
        abort_if($amc->asset_id !== $asset->id, 403);

        $validated = $request->validate($this->rules());
        $validated['updated_by'] = auth()->id();

        $amc->update($validated);

        $this->storeDocuments($request, $asset, $amc);

        return redirect()->route('assets.show', [$asset, 'tab' => 'amc'])
            ->with('success', 'AMC contract updated successfully.');
    }

    public function patchField(Request $request, Asset $asset, AssetAmcContract $amc)
    {
        abort_if($amc->asset_id !== $asset->id, 403);

        $allowed = [
            'contract_number', 'vendor_id', 'coverage_type',
            'amc_date_from', 'amc_date_to',
            'amc_amount', 'amc_bill_no', 'amc_bill_date',
            'reminder_before_days', 'coverage_details', 'amc_terms', 'remarks',
        ];
        $field = $request->input('field');
        abort_if(! in_array($field, $allowed, true), 422);

        $rules = [
            'contract_number'      => ['nullable', 'string', 'max:255'],
            'vendor_id'            => ['nullable', 'integer', 'exists:vendors,id'],
            'coverage_type'        => ['required', 'in:comprehensive,non_comprehensive,parts_only,labour_only'],
            'amc_date_from'        => ['nullable', 'date'],
            'amc_date_to'          => ['nullable', 'date'],
            'amc_amount'           => ['nullable', 'numeric', 'min:0'],
            'amc_bill_no'          => ['nullable', 'string', 'max:255'],
            'amc_bill_date'        => ['nullable', 'date'],
            'reminder_before_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'coverage_details'     => ['nullable', 'string'],
            'amc_terms'            => ['nullable', 'string'],
            'remarks'              => ['nullable', 'string'],
        ];

        $validated = $request->validate(['value' => $rules[$field]]);
        $value = $validated['value'] ?: null;

        $amc->update([$field => $value, 'updated_by' => auth()->id()]);

        $label = null;
        if ($field === 'vendor_id' && $value) {
            $label = \App\Models\Vendor::where('id', $value)->value('name');
        }

        return response()->json(['ok' => true, 'label' => $label]);
    }

    public function storeDocument(Request $request, Asset $asset, AssetAmcContract $amc)
    {
        return $this->performStoreDocument($request, $asset, $amc, 'amc', 'amc_bill', 'AMC Document');
    }

    public function destroy(Asset $asset, AssetAmcContract $amc)
    {
        abort_if($amc->asset_id !== $asset->id, 403);

        foreach ($amc->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }

        $amc->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'amc'])
            ->with('success', 'AMC contract deleted.');
    }

    private function rules(): array
    {
        return [
            'contract_number'       => ['nullable', 'string', 'max:255'],
            'vendor_id'             => ['nullable', 'integer', 'exists:vendors,id'],
            'amc_date_from'         => ['nullable', 'date'],
            'amc_date_to'           => ['nullable', 'date', 'after_or_equal:amc_date_from'],
            'amc_amount'            => ['nullable', 'numeric', 'min:0'],
            'amc_bill_no'           => ['nullable', 'string', 'max:255'],
            'amc_bill_date'         => ['nullable', 'date'],
            'coverage_type'         => ['required', 'in:comprehensive,non_comprehensive,parts_only,labour_only'],
            'coverage_details'      => ['nullable', 'string'],
            'amc_terms'             => ['nullable', 'string'],
            'reminder_before_days'  => ['nullable', 'integer', 'min:1', 'max:365'],
            'remarks'               => ['nullable', 'string'],
            'amc_bill_image'        => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    private function storeDocuments(Request $request, Asset $asset, AssetAmcContract $amc): void
    {
        if (! $request->hasFile('amc_bill_image')) {
            return;
        }

        $file = $request->file('amc_bill_image');
        $path = $file->store("assets/{$asset->id}/amc", 'public');

        AssetDocument::create([
            'asset_id'           => $asset->id,
            'documentable_type'  => AssetAmcContract::class,
            'documentable_id'    => $amc->id,
            'document_type'      => 'amc_bill',
            'document_title'     => 'AMC Bill',
            'file_path'          => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime_type'     => $file->getClientMimeType(),
            'file_size'          => $file->getSize(),
            'uploaded_by'        => auth()->id(),
        ]);
    }
}