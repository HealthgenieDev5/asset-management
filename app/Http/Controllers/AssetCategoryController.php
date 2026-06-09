<?php

namespace App\Http\Controllers;

use App\Models\AssetCategory;
use Illuminate\Http\Request;

class AssetCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = AssetCategory::query()
            ->when($request->search, fn ($q, $s) => $q->where('name', 'like', "%{$s}%")->orWhere('code', 'like', "%{$s}%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('asset-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('asset-categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'code'   => ['required', 'string', 'size:2', 'unique:asset_categories,code', 'regex:/^[A-Z]{2}$/'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'code.regex' => 'The code must be exactly 2 uppercase letters (e.g. VE, AC, IT).',
            'code.size'  => 'The code must be exactly 2 characters.',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        AssetCategory::create($validated);

        return redirect()->route('asset-categories.index')
            ->with('success', 'Category created successfully.');
    }

    public function show(AssetCategory $assetCategory)
    {
        return redirect()->route('asset-categories.edit', $assetCategory);
    }

    public function edit(AssetCategory $assetCategory)
    {
        return view('asset-categories.edit', ['category' => $assetCategory]);
    }

    public function update(Request $request, AssetCategory $assetCategory)
    {
        $validated = $request->validate([
            'name'   => ['required', 'string', 'max:255'],
            'code'   => ['required', 'string', 'size:2', 'unique:asset_categories,code,' . $assetCategory->id, 'regex:/^[A-Z]{2}$/'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'code.regex' => 'The code must be exactly 2 uppercase letters (e.g. VE, AC, IT).',
            'code.size'  => 'The code must be exactly 2 characters.',
        ]);

        $validated['code'] = strtoupper($validated['code']);

        $assetCategory->update($validated);

        return redirect()->route('asset-categories.index')
            ->with('success', 'Category updated successfully.');
    }

    public function destroy(AssetCategory $assetCategory)
    {
        if ($assetCategory->subcategories()->exists()) {
            return back()->with('error', 'Cannot delete category because it has subcategories. Remove subcategories first.');
        }

        if ($assetCategory->assets()->exists()) {
            return back()->with('error', 'Cannot delete category because it has assets assigned to it.');
        }

        $assetCategory->delete();

        return redirect()->route('asset-categories.index')
            ->with('success', 'Category deleted successfully.');
    }
}
