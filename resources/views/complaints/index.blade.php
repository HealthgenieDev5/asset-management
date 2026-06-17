<x-layouts::app :title="__('Complaints')">
        <div class="mb-6 flex items-center justify-between">
            <div>
                <flux:heading size="xl" class="font-extrabold">
                    Asset <span class="text-accent">Complaints</span>
                </flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Log and track complaints reported against any asset.</flux:text>
            </div>
            <button type="button" x-data x-on:click="$dispatch('open-modal-add-complaint')"
                class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-2 text-sm font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                <flux:icon.plus class="size-4" />
                Log Complaint
            </button>
        </div>

        @include('partials.flash')

        {{-- Add Modal --}}
        <x-modal name="add-complaint" title="New Complaint" :dismissible="false"
            :auto-open="$errors->any() && old('_form') === 'complaint'">
            @php
                $assetContext = $assets->mapWithKeys(fn ($asset) => [
                    $asset->id => [
                        'location'    => $asset->location ?: '—',
                        'department'  => $asset->department ?: '—',
                        'category'    => $asset->category?->name ?: '—',
                        'subcategory' => $asset->subcategory?->name ?: '—',
                    ],
                ]);
            @endphp
            <form method="POST" action="{{ route('complaints.store') }}" enctype="multipart/form-data" class="space-y-4"
                  x-data="{ context: {{ $assetContext->toJson() }}, selectedAsset: '{{ old('asset_id') }}' }">
                @csrf
                <input type="hidden" name="_form" value="complaint">

                <div class="relative">
                    @php
                        $sel = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
                        $lbs = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
                    @endphp
                    <select name="asset_id" id="asset_id" class="{{ $sel }}" x-model="selectedAsset" required>
                        <option value="" disabled @selected(! old('asset_id'))>Select an asset…</option>
                        @foreach ($assets as $asset)
                            <option value="{{ $asset->id }}" @selected((int) old('asset_id') === $asset->id)>
                                {{ $asset->asset_code }} — {{ $asset->asset_name }}
                            </option>
                        @endforeach
                    </select>
                    <label for="asset_id" class="{{ $lbs }}">Asset <span class="text-red-400">*</span></label>
                    @error('asset_id')<p class="mt-0.5 text-[11px] text-red-400">{{ $message }}</p>@enderror
                </div>

                {{-- Asset Context (auto-captured, live) --}}
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-700 dark:bg-zinc-800/40"
                     x-show="selectedAsset" x-cloak>
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Asset Context (auto-captured)</p>
                    <dl class="grid grid-cols-2 gap-x-6 gap-y-2 sm:grid-cols-4">
                        <div>
                            <dt class="text-[10px] text-zinc-500">Location</dt>
                            <dd class="text-xs font-medium text-zinc-700 dark:text-zinc-300" x-text="context[selectedAsset]?.location ?? '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-[10px] text-zinc-500">Department</dt>
                            <dd class="text-xs font-medium text-zinc-700 dark:text-zinc-300" x-text="context[selectedAsset]?.department ?? '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-[10px] text-zinc-500">Category</dt>
                            <dd class="text-xs font-medium text-zinc-700 dark:text-zinc-300" x-text="context[selectedAsset]?.category ?? '—'"></dd>
                        </div>
                        <div>
                            <dt class="text-[10px] text-zinc-500">Subcategory</dt>
                            <dd class="text-xs font-medium text-zinc-700 dark:text-zinc-300" x-text="context[selectedAsset]?.subcategory ?? '—'"></dd>
                        </div>
                    </dl>
                </div>

                @include('assets.tabs._complaint-form-fields', ['complaint' => null])

                <div class="flex items-center gap-3 pt-1">
                    <flux:button type="submit" variant="primary" size="sm" icon="check">Submit Complaint</flux:button>
                    <button type="button" x-on:click="$dispatch('close-modal-add-complaint')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>

        {{-- Filters --}}
        @php
            $activeFilters = array_filter(request()->only(['search', 'status', 'priority', 'asset_category_id', 'asset_subcategory_id']));
            $hasFilters    = count($activeFilters) > 0;
            $filterCount   = count($activeFilters);
        @endphp

        <form method="GET" class="mb-6">
            <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
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
                            <a href="{{ route('complaints.index') }}"
                               class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                                Clear all
                            </a>
                        @endif
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-accent/90 active:scale-95">
                            Apply
                        </button>
                    </div>
                </div>

                <div class="flex flex-wrap items-end gap-3 px-4 py-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Complaint title…"
                               class="h-8 w-52 rounded-lg border border-zinc-200 bg-zinc-50 px-3 text-xs text-zinc-800 placeholder-zinc-400 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:placeholder-zinc-500" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Status</label>
                        <select name="status" class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            <option value="">All Statuses</option>
                            @foreach (['open' => 'Open', 'acknowledged' => 'Acknowledged', 'in_progress' => 'In Progress', 'resolved' => 'Resolved', 'closed' => 'Closed', 'rejected' => 'Rejected'] as $val => $sLabel)
                                <option value="{{ $val }}" @selected(request('status') === $val)>{{ $sLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Priority</label>
                        <select name="priority" class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            <option value="">All Priorities</option>
                            @foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High', 'critical' => 'Critical'] as $val => $pLabel)
                                <option value="{{ $val }}" @selected(request('priority') === $val)>{{ $pLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Category</label>
                        <select name="asset_category_id" onchange="this.form.asset_subcategory_id.value=''; this.form.submit()" class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            <option value="">All Categories</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" @selected((int) request('asset_category_id') === $category->id)>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Subcategory</label>
                        <select name="asset_subcategory_id" class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                            <option value="">All Subcategories</option>
                            @foreach ($subcategories as $subcategory)
                                <option value="{{ $subcategory->id }}" @selected((int) request('asset_subcategory_id') === $subcategory->id)>{{ $subcategory->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </form>

        {{-- Table --}}
        <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 text-left text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                        <th class="px-4 py-3 font-semibold">#</th>
                        <th class="px-4 py-3 font-semibold">Asset</th>
                        <th class="px-4 py-3 font-semibold">Category</th>
                        <th class="px-4 py-3 font-semibold">Title</th>
                        <th class="px-4 py-3 font-semibold">Priority</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Reported</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($complaints as $complaint)
                        <tr class="hover:bg-accent/5 transition-colors">
                            <td class="px-4 py-3 text-zinc-500">{{ $complaints->firstItem() + $loop->index }}</td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                @if ($complaint->asset)
                                    <span class="font-mono text-xs font-bold text-accent">{{ $complaint->asset->asset_code }}</span>
                                    <span class="block text-xs text-zinc-500">{{ $complaint->asset->asset_name }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                <span class="block">{{ $complaint->category?->name ?: '—' }}</span>
                                @if ($complaint->subcategory)
                                    <span class="block text-xs text-zinc-500">{{ $complaint->subcategory->name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">{{ $complaint->title }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-semibold {{ $complaint->priority_color }}">
                                    {{ $complaint->priority_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $complaint->status_color }}">
                                    {{ $complaint->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-500">{{ $complaint->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                @if ($complaint->asset)
                                    <flux:button href="{{ route('assets.show', [$complaint->asset, 'tab' => 'complaints']) }}" wire:navigate size="sm" variant="ghost">
                                        <flux:icon.eye class="size-3.5" />
                                        Manage
                                    </flux:button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-zinc-500">
                                No complaints found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $complaints->links() }}
        </div>
</x-layouts::app>
