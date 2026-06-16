<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HandlesComplaintCreation;
use App\Models\Asset;
use App\Models\AssetCategory;
use App\Models\AssetComplaint;
use App\Models\AssetSubcategory;
use Illuminate\Http\Request;

class ComplaintController extends Controller
{
    use HandlesComplaintCreation;

    public function index(Request $request)
    {
        $complaints = AssetComplaint::query()
            ->with(['asset', 'category', 'subcategory'])
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->when($request->priority, fn ($q, $s) => $q->where('priority', $s))
            ->when($request->asset_category_id, fn ($q, $s) => $q->where('asset_category_id', $s))
            ->when($request->asset_subcategory_id, fn ($q, $s) => $q->where('asset_subcategory_id', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $assets = Asset::query()
            ->with(['category:id,name', 'subcategory:id,name'])
            ->orderBy('asset_name')
            ->get(['id', 'asset_code', 'asset_name', 'location', 'department', 'asset_category_id', 'asset_subcategory_id']);
        $categories = AssetCategory::query()->where('status', 'active')->orderBy('name')->get(['id', 'name']);
        $subcategories = AssetSubcategory::query()
            ->when($request->asset_category_id, fn ($q, $s) => $q->where('asset_category_id', $s))
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'asset_category_id']);

        return view('complaints.index', compact('complaints', 'assets', 'categories', 'subcategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate(array_merge(
            ['asset_id' => ['required', 'exists:assets,id']],
            $this->complaintRules()
        ));

        $asset = Asset::findOrFail($validated['asset_id']);

        $validated['location'] = $asset->location;
        $validated['department'] = $asset->department;
        $validated['asset_category_id'] = $asset->asset_category_id;
        $validated['asset_subcategory_id'] = $asset->asset_subcategory_id;
        $validated['created_by'] = auth()->id();

        $complaint = AssetComplaint::create($validated);

        $this->storeComplaintVideo($request, $asset, $complaint, 'video_before', 'complaint_video_before');
        $this->storeComplaintVideo($request, $asset, $complaint, 'video_after', 'complaint_video_after');

        $this->triggerComplaintEscalation($asset, $complaint);

        return redirect()->route('complaints.index')
            ->with('success', 'Complaint logged successfully.');
    }
}
