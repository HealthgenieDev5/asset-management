<x-layouts::app :title="__('Asset Categories')">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <flux:heading size="xl" class="font-extrabold">
                    Asset <span class="text-accent">Categories</span>
                </flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Manage asset categories and their 2-character codes.</flux:text>
            </div>
            <flux:button href="{{ route('asset-categories.create') }}" wire:navigate variant="filled" class="bg-accent text-accent-foreground hover:opacity-90">
                <flux:icon.plus class="size-4" />
                Add Category
            </flux:button>
        </div>

        @include('partials.flash')

        {{-- Filters --}}
        @php
            $activeFilters = array_filter(request()->only(['search', 'status']));
            $hasFilters    = count($activeFilters) > 0;
            $filterCount   = count($activeFilters);
        @endphp

        <form method="GET" class="mb-6">
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
                            <a href="{{ route('asset-categories.index') }}"
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

                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Search</label>
                        <div class="relative">
                            <svg class="absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z" />
                            </svg>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Name or code…"
                                   class="h-8 w-52 rounded-lg border border-zinc-200 bg-zinc-50 pl-8 pr-3 text-xs text-zinc-800 placeholder-zinc-400 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:placeholder-zinc-500 dark:focus:bg-zinc-800 dark:focus:text-zinc-100 {{ request('search') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Status</label>
                        <select name="status"
                                class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:focus:bg-zinc-800 dark:focus:text-zinc-100 {{ request('status') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}">
                            <option value="">All Statuses</option>
                            <option value="active"   @selected(request('status') === 'active')>Active</option>
                            <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
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
                        @if (request('status'))
                            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                Status: {{ ucfirst(request('status')) }}
                            </span>
                        @endif
                    </div>
                @endif

            </div>
        </form>

        {{-- Table --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        <th class="px-4 py-3 font-semibold">#</th>
                        <th class="px-4 py-3 font-semibold">Code</th>
                        <th class="px-4 py-3 font-semibold">Name</th>
                        <th class="px-4 py-3 font-semibold">Subcategories</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Created</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($categories as $category)
                        <tr class="hover:bg-accent/5 transition-colors">
                            <td class="px-4 py-3 text-zinc-500">{{ $categories->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded bg-zinc-100 px-2 py-0.5 font-mono text-xs font-bold tracking-widest text-accent dark:bg-zinc-800">
                                    {{ $category->code }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $category->name }}</td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $category->subcategories_count ?? $category->subcategories()->count() }}</td>
                            <td class="px-4 py-3">
                                @if ($category->status === 'active')
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-300 dark:bg-green-900/40 dark:text-green-400 dark:ring-green-700">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-500 ring-1 ring-zinc-300 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700">Inactive</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-500">{{ $category->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <flux:button href="{{ route('asset-categories.edit', $category) }}" wire:navigate size="sm" variant="ghost">
                                        <flux:icon.pencil class="size-3.5" />
                                        Edit
                                    </flux:button>
                                    <form method="POST" action="{{ route('asset-categories.destroy', $category) }}" onsubmit="confirmDelete(this, 'Delete this category?'); return false;">
                                        @csrf
                                        @method('DELETE')
                                        <flux:button type="submit" size="sm" variant="ghost" class="text-red-400 hover:text-red-300">
                                            <flux:icon.trash class="size-3.5" />
                                            Delete
                                        </flux:button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-zinc-500">
                                No categories found.
                                <a href="{{ route('asset-categories.create') }}" class="ml-1 text-accent hover:underline">Add one</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $categories->links() }}
        </div>
</x-layouts::app>
