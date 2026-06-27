<?php

namespace App\Http\Controllers;

use App\Concerns\FilePondDocumentHandler;
use App\Models\Asset;
use App\Models\AssetDocument;
use App\Models\AssetInsurancePolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AssetInsurancePolicyController extends Controller
{
    use FilePondDocumentHandler;

    public function store(Request $request, Asset $asset)
    {
        $validated = $request->validate($this->rules());

        $validated['asset_id']   = $asset->id;
        $validated['created_by'] = auth()->id();

        $policy = AssetInsurancePolicy::create($validated);

        $this->storeDocuments($request, $asset, $policy);

        return redirect()->route('assets.show', [$asset, 'tab' => 'insurance'])
            ->with('success', 'Insurance policy added successfully.');
    }

    public function update(Request $request, Asset $asset, AssetInsurancePolicy $insurance)
    {
        abort_if($insurance->asset_id !== $asset->id, 403);

        $validated = $request->validate($this->rules());
        $validated['updated_by'] = auth()->id();

        $insurance->update($validated);

        $this->storeDocuments($request, $asset, $insurance);

        return redirect()->route('assets.show', [$asset, 'tab' => 'insurance'])
            ->with('success', 'Insurance policy updated successfully.');
    }

    public function patchField(Request $request, Asset $asset, AssetInsurancePolicy $insurance)
    {
        abort_if($insurance->asset_id !== $asset->id, 403);

        $allowed = [
            'policy_number', 'insurer_name', 'insurer_contact_person', 'insurer_phone', 'insurer_email',
            'policy_type', 'policy_date_from', 'policy_date_to',
            'premium_amount', 'sum_insured', 'bill_no', 'bill_date',
            'coverage_details', 'reminder_before_days', 'remarks',
        ];
        $field = $request->input('field');
        abort_if(! in_array($field, $allowed, true), 422);

        $rules = [
            'policy_number'          => ['nullable', 'string', 'max:255'],
            'insurer_name'           => ['nullable', 'string', 'max:255'],
            'insurer_contact_person' => ['nullable', 'string', 'max:255'],
            'insurer_phone'          => ['nullable', 'string', 'max:30'],
            'insurer_email'          => ['nullable', 'email', 'max:255'],
            'policy_type'            => ['nullable', 'string', 'max:255'],
            'policy_date_from'       => ['nullable', 'date'],
            'policy_date_to'         => ['nullable', 'date'],
            'premium_amount'         => ['nullable', 'numeric', 'min:0'],
            'sum_insured'            => ['nullable', 'numeric', 'min:0'],
            'bill_no'                => ['nullable', 'string', 'max:255'],
            'bill_date'              => ['nullable', 'date'],
            'coverage_details'       => ['nullable', 'string'],
            'reminder_before_days'   => ['nullable', 'integer', 'min:1', 'max:365'],
            'remarks'                => ['nullable', 'string'],
        ];

        $validated = $request->validate(['value' => $rules[$field]]);
        $value = $validated['value'] ?: null;

        $insurance->update([$field => $value, 'updated_by' => auth()->id()]);

        return response()->json(['ok' => true]);
    }

    public function storeDocument(Request $request, Asset $asset, AssetInsurancePolicy $insurance)
    {
        return $this->performStoreDocument($request, $asset, $insurance, 'insurance', 'insurance_policy', 'Insurance Document');
    }

    public function destroy(Asset $asset, AssetInsurancePolicy $insurance)
    {
        abort_if($insurance->asset_id !== $asset->id, 403);

        foreach ($insurance->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->delete();
        }

        $insurance->delete();

        return redirect()->route('assets.show', [$asset, 'tab' => 'insurance'])
            ->with('success', 'Insurance policy deleted.');
    }

    private function rules(): array
    {
        return [
            'policy_number'          => ['nullable', 'string', 'max:255'],
            'insurer_name'           => ['nullable', 'string', 'max:255'],
            'insurer_contact_person' => ['nullable', 'string', 'max:255'],
            'insurer_phone'          => ['nullable', 'string', 'max:30'],
            'insurer_email'          => ['nullable', 'email', 'max:255'],
            'policy_type'            => ['nullable', 'string', 'max:255'],
            'policy_date_from'       => ['nullable', 'date'],
            'policy_date_to'         => ['nullable', 'date', 'after_or_equal:policy_date_from'],
            'premium_amount'         => ['nullable', 'numeric', 'min:0'],
            'sum_insured'            => ['nullable', 'numeric', 'min:0'],
            'bill_no'                => ['nullable', 'string', 'max:255'],
            'bill_date'              => ['nullable', 'date'],
            'coverage_details'       => ['nullable', 'string'],
            'reminder_before_days'   => ['nullable', 'integer', 'min:1', 'max:365'],
            'remarks'                => ['nullable', 'string'],
            'insurance_document'     => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    private function storeDocuments(Request $request, Asset $asset, AssetInsurancePolicy $policy): void
    {
        if (! $request->hasFile('insurance_document')) {
            return;
        }

        $file = $request->file('insurance_document');
        $path = $file->store("assets/{$asset->id}/insurance", 'public');

        AssetDocument::create([
            'asset_id'           => $asset->id,
            'documentable_type'  => AssetInsurancePolicy::class,
            'documentable_id'    => $policy->id,
            'document_type'      => 'insurance_policy',
            'document_title'     => 'Insurance Policy Document',
            'file_path'          => $path,
            'file_original_name' => $file->getClientOriginalName(),
            'file_mime_type'     => $file->getClientMimeType(),
            'file_size'          => $file->getSize(),
            'uploaded_by'        => auth()->id(),
        ]);
    }
}