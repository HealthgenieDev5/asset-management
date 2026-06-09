<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use App\Models\AssetSubcategory;
use Illuminate\Http\Request;

class AssetSubcategoryController extends Controller
{
    public function index(Request $request)
    {
        $subcategories = AssetSubcategory::with('category')
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"))
            ->when($request->category_id, fn ($q, $id) => $q->where('asset_category_id', $id))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('asset_category_id')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = AssetCategory::orderBy('name')->get();

        return view('asset-subcategories.index', compact('subcategories', 'categories'));
    }

    public function create()
    {
        $categories = AssetCategory::active()->orderBy('name')->get();

        return view('asset-subcategories.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'name'              => ['required', 'string', 'max:255'],
            'code'              => ['nullable', 'string', 'max:50'],
            'status'            => ['required', 'in:active,inactive'],
        ]);

        AssetSubcategory::create($validated);

        return redirect()->route('asset-subcategories.index')
            ->with('success', 'Subcategory created successfully.');
    }

    public function show(AssetSubcategory $assetSubcategory)
    {
        return redirect()->route('asset-subcategories.edit', $assetSubcategory);
    }

    public function edit(AssetSubcategory $assetSubcategory)
    {
        $categories = AssetCategory::active()->orderBy('name')->get();

        return view('asset-subcategories.edit', [
            'subcategory' => $assetSubcategory,
            'categories'  => $categories,
        ]);
    }

    public function update(Request $request, AssetSubcategory $assetSubcategory)
    {
        $validated = $request->validate([
            'asset_category_id' => ['required', 'exists:asset_categories,id'],
            'name'              => ['required', 'string', 'max:255'],
            'code'              => ['nullable', 'string', 'max:50'],
            'status'            => ['required', 'in:active,inactive'],
        ]);

        $assetSubcategory->update($validated);

        return redirect()->route('asset-subcategories.index')
            ->with('success', 'Subcategory updated successfully.');
    }

    public function destroy(AssetSubcategory $assetSubcategory)
    {
        if ($assetSubcategory->assets()->exists()) {
            return back()->with('error', 'Cannot delete subcategory because it has assets assigned to it.');
        }

        $assetSubcategory->delete();

        return redirect()->route('asset-subcategories.index')
            ->with('success', 'Subcategory deleted successfully.');
    }
}
