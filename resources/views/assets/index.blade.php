<x-layouts::app :title="__('Asset Register')">
    @include('partials.flash')

    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-extrabold">
                Asset <span class="text-accent">Register</span>
            </flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">All company assets tracked in one place.</flux:text>
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
    @php
        $activeFilters = array_filter(request()->only(['search', 'category_id', 'subcategory_id', 'status']));
        $hasFilters    = count($activeFilters) > 0;
        $filterCount   = count($activeFilters);
    @endphp

    <form method="GET" action="{{ route('assets.index') }}" class="mb-6">
        <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">

            {{-- Header bar --}}
            <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-2.5 dark:border-zinc-800">
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 4h18M7 8h10M11 12h2M9 16h6" />
                        </svg>
                        Filters
                    </div>
                    @if ($hasFilters)
                        <span class="inline-flex items-center rounded-full bg-accent/10 px-2 py-0.5 text-xs font-semibold text-accent">
                            {{ $filterCount }} active
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-1.5">
                    @if ($hasFilters)
                        <a href="{{ route('assets.index') }}"
                           class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                            <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                            Clear all
                        </a>
                    @endif

                    <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-accent/90 active:scale-95">
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                        </svg>
                        Apply
                    </button>
                </div>
            </div>

            {{-- Filter fields --}}
            <div class="flex flex-wrap items-end gap-3 px-4 py-3">

                {{-- Search --}}
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Search</label>
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z" />
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Code, name, serial…"
                               class="h-8 w-52 rounded-lg border border-zinc-200 bg-zinc-50 pl-8 pr-3 text-xs text-zinc-800 placeholder-zinc-400 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:placeholder-zinc-500 dark:focus:bg-zinc-800 dark:focus:text-zinc-100 {{ request('search') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}" />
                    </div>
                </div>

                {{-- Category --}}
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Category</label>
                    <select name="category_id" onchange="this.form.submit()"
                            class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:focus:bg-zinc-800 dark:focus:text-zinc-100 {{ request('category_id') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}">
                        <option value="">All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Subcategory --}}
                @if ($subcategories->isNotEmpty())
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Subcategory</label>
                        <select name="subcategory_id"
                                class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:focus:bg-zinc-800 dark:focus:text-zinc-100 {{ request('subcategory_id') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}">
                            <option value="">All Subcategories</option>
                            @foreach ($subcategories as $sub)
                                <option value="{{ $sub->id }}" @selected(request('subcategory_id') == $sub->id)>{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <input type="hidden" name="subcategory_id" value="">
                @endif

                {{-- Status --}}
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Status</label>
                    <select name="status"
                            class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:focus:bg-zinc-800 dark:focus:text-zinc-100 {{ request('status') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}">
                        <option value="">All Statuses</option>
                        <option value="active"       @selected(request('status') === 'active')>Active</option>
                        <option value="under_repair" @selected(request('status') === 'under_repair')>Under Repair</option>
                        <option value="disposed"     @selected(request('status') === 'disposed')>Disposed</option>
                        <option value="scrapped"     @selected(request('status') === 'scrapped')>Scrapped</option>
                        <option value="inactive"     @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>

            </div>

            {{-- Active filter pills --}}
            @if ($hasFilters)
                <div class="flex flex-wrap items-center gap-1.5 border-t border-zinc-100 px-4 py-2 dark:border-zinc-800">
                    <span class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400 mr-1">Active:</span>
                    @if (request('search'))
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            <svg class="size-3 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z" /></svg>
                            "{{ request('search') }}"
                        </span>
                    @endif
                    @foreach ($categories->where('id', request('category_id')) as $c)
                        <span class="inline-flex items-center gap-1 rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-medium text-accent">
                            <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 0 1 4.5 9.75h15A2.25 2.25 0 0 1 21.75 12v.75m-8.69-6.44-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" /></svg>
                            {{ $c->name }}
                        </span>
                    @endforeach
                    @if (request('status'))
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            Status: {{ ucfirst(str_replace('_', ' ', request('status'))) }}
                        </span>
                    @endif
                </div>
            @endif

        </div>
    </form>

    {{-- Table --}}
    <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
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
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($assets as $asset)
                    <tr class="hover:bg-accent/5 transition-colors">
                        <td class="px-4 py-3 text-zinc-500">{{ $assets->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <span class="font-mono text-xs font-semibold text-accent bg-accent/10 border border-accent/20 px-2 py-0.5 rounded">
                                {{ $asset->asset_code }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('assets.show', $asset) }}" wire:navigate class="font-semibold text-zinc-900 hover:text-accent transition-colors dark:text-zinc-100">
                                {{ $asset->asset_name }}
                            </a>
                            @if ($asset->manufacturer || $asset->model)
                                <div class="text-xs text-zinc-500 mt-0.5">{{ implode(' · ', array_filter([$asset->manufacturer, $asset->model])) }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                            {{ $asset->category?->name }}
                            @if ($asset->subcategory)
                                <span class="text-zinc-500"> / {{ $asset->subcategory->name }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $asset->location ?: '—' }}</td>
                        <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">{{ $asset->custodian ?: '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $asset->status_color }}">
                                {{ $asset->status_label }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <flux:button href="{{ route('assets.show', $asset) }}" wire:navigate size="sm" variant="ghost" icon="eye" />
                                <form method="POST" action="{{ route('assets.destroy', $asset) }}"
                                      onsubmit="confirmDelete(this, 'Delete asset {{ $asset->asset_code }}? This cannot be undone.'); return false;">
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
