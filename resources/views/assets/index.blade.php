<x-layouts::app :title="__('Asset Register')">
    @include('partials.flash')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-extrabold">
                Asset <span class="text-accent">Register</span>
            </flux:heading>
            <flux:text class="mt-1 text-zinc-400">All company assets tracked in one place.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button
                href="{{ route('assets.export', array_filter(request()->only(['status', 'category_id', 'department']))) }}"
                variant="ghost"
                icon="arrow-down-tray">
                Export Excel
            </flux:button>
            <flux:button href="{{ route('assets.create') }}" wire:navigate variant="primary" icon="plus">
                Add Asset
            </flux:button>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('assets.index') }}" class="mb-5 flex flex-wrap gap-3">
        <flux:input
            name="search"
            value="{{ request('search') }}"
            placeholder="Search code, name, serial, manufacturer…"
            class="w-72"
            icon="magnifying-glass"
        />

        <flux:select name="category_id" placeholder="All Categories" class="w-48" onchange="this.form.submit()">
            <option value="">All Categories</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
            @endforeach
        </flux:select>

        @if ($subcategories->isNotEmpty())
            <flux:select name="subcategory_id" placeholder="All Subcategories" class="w-48">
                <option value="">All Subcategories</option>
                @foreach ($subcategories as $sub)
                    <option value="{{ $sub->id }}" @selected(request('subcategory_id') == $sub->id)>{{ $sub->name }}</option>
                @endforeach
            </flux:select>
        @else
            <input type="hidden" name="subcategory_id" value="">
        @endif

        <flux:select name="status" placeholder="All Statuses" class="w-44">
            <option value="">All Statuses</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="under_repair" @selected(request('status') === 'under_repair')>Under Repair</option>
            <option value="disposed" @selected(request('status') === 'disposed')>Disposed</option>
            <option value="scrapped" @selected(request('status') === 'scrapped')>Scrapped</option>
            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
        </flux:select>

        <flux:button type="submit" variant="ghost" icon="funnel">Filter</flux:button>

        @if (request()->hasAny(['search', 'category_id', 'subcategory_id', 'status']))
            <flux:button href="{{ route('assets.index') }}" wire:navigate variant="ghost" icon="x-mark">Clear</flux:button>
        @endif
    </form>

    {{-- Table --}}
    <div class="rounded-xl border border-zinc-800 bg-zinc-900 overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-800 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500">
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Asset Name</th>
                    <th class="px-4 py-3">Category / Sub</th>
                    <th class="px-4 py-3">Location</th>
                    <th class="px-4 py-3">Custodian</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($assets as $asset)
                    <tr class="hover:bg-accent/5 transition-colors">
                        <td class="px-4 py-3 text-zinc-500">{{ $assets->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-semibold text-accent bg-accent/10 border border-accent/20 px-2 py-0.5 rounded">
                                {{ $asset->asset_code }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('assets.show', $asset) }}" wire:navigate class="font-semibold text-zinc-100 hover:text-accent transition-colors">
                                {{ $asset->asset_name }}
                            </a>
                            @if ($asset->manufacturer || $asset->model)
                                <div class="text-xs text-zinc-500 mt-0.5">{{ implode(' · ', array_filter([$asset->manufacturer, $asset->model])) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-300">
                            {{ $asset->category?->name }}
                            @if ($asset->subcategory)
                                <span class="text-zinc-500"> / {{ $asset->subcategory->name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-400">{{ $asset->location ?: '—' }}</td>
                        <td class="px-4 py-3 text-zinc-400">{{ $asset->custodian ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $asset->status_color }}">
                                {{ $asset->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button href="{{ route('assets.show', $asset) }}" wire:navigate size="sm" variant="ghost" icon="eye" />
                                <flux:button href="{{ route('assets.edit', $asset) }}" wire:navigate size="sm" variant="ghost" icon="pencil" />
                                <form method="POST" action="{{ route('assets.destroy', $asset) }}"
                                      onsubmit="return confirm('Delete asset {{ $asset->asset_code }}? This cannot be undone.')">
                                    @csrf @method('DELETE')
                                    <flux:button type="submit" size="sm" variant="ghost" icon="trash" class="text-red-400 hover:text-red-300" />
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-zinc-500">
                            <flux:icon.clipboard-document-list class="mx-auto mb-3 size-10 opacity-30" />
                            <p class="font-medium">No assets found</p>
                            <p class="mt-1 text-xs">Add your first asset to get started.</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($assets->hasPages())
        <div class="mt-4">
            {{ $assets->links() }}
        </div>
    @endif
</x-layouts::app>
