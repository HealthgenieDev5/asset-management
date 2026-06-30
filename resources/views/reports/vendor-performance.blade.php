<x-layouts::app title="Vendor Performance Report">
    @include('partials.flash')

    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <flux:heading size="xl" class="font-extrabold">Vendor <span class="text-accent">Performance</span></flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Per-vendor contract counts, service incidents, and SLA summary.</flux:text>
        </div>
        <span class="text-xs text-zinc-500">{{ $vendors->total() }} {{ Str::plural('vendor', $vendors->total()) }}</span>
    </div>

    {{-- Filters --}}
    @php
        $activeFilters = array_filter(request()->only(['search', 'status']));
        $hasFilters    = count($activeFilters) > 0;
        $filterCount   = count($activeFilters);
    @endphp

    <form method="GET" class="mb-6 print:hidden">
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
                        <a href="{{ route('reports.vendor-performance') }}"
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

                    <a href="{{ route('reports.vendor-performance.export', request()->query()) }}"
                       class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-zinc-500 transition hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-900/20 dark:hover:text-emerald-400">
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Excel
                    </a>
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
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Vendor name…"
                               class="h-8 w-44 rounded-lg border border-zinc-200 bg-zinc-50 pl-8 pr-3 text-xs text-zinc-800 placeholder-zinc-400 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:placeholder-zinc-500 dark:focus:bg-zinc-800 dark:focus:text-zinc-100 {{ request('search') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}" />
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
                    <span class="mr-1 text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Active:</span>
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

    <div class="overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left text-xs font-semibold uppercase tracking-wider text-zinc-500 dark:border-zinc-800">
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Vendor Name</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3 text-center">Warranties</th>
                    <th class="px-4 py-3 text-center">AMC (Active/Total)</th>
                    <th class="px-4 py-3 text-center">Services</th>
                    <th class="px-4 py-3 text-right">Total Cost</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($vendors as $vendor)
                    <tr class="hover:bg-accent/5 transition-colors">
                        <td class="px-4 py-2.5 text-zinc-500">{{ $vendors->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-2.5">
                            <a href="{{ route('vendors.show', $vendor) }}" wire:navigate class="font-medium text-zinc-900 hover:text-accent hover:underline dark:text-zinc-100">
                                {{ $vendor->name }}
                            </a>
                        </td>
                        <td class="px-4 py-2.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $vendor->typeLabel() }}</td>
                        <td class="px-4 py-2.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $vendor->phone ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-xs text-zinc-500 dark:text-zinc-400">{{ $vendor->email ?: '—' }}</td>
                        <td class="px-4 py-2.5 text-center text-zinc-700 dark:text-zinc-300">{{ $vendor->warranties_count }}</td>
                        <td class="px-4 py-2.5 text-center text-zinc-700 dark:text-zinc-300">
                            <span class="{{ $vendor->active_amc_count > 0 ? 'text-green-500' : 'text-zinc-400' }}">{{ $vendor->active_amc_count }}</span>
                            <span class="text-zinc-400">/</span>{{ $vendor->amc_contracts_count }}
                        </td>
                        <td class="px-4 py-2.5 text-center text-zinc-700 dark:text-zinc-300">{{ $vendor->services_count }}</td>
                        <td class="px-4 py-2.5 text-right font-mono text-xs text-zinc-700 dark:text-zinc-300">
                            {{ $vendor->services_sum_service_cost ? '₹ ' . number_format($vendor->services_sum_service_cost, 2) : '—' }}
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-xs font-semibold {{ $vendor->status === 'active' ? 'border-green-300 bg-green-50 text-green-700 dark:border-green-700 dark:bg-green-900/30 dark:text-green-400' : 'border-zinc-300 bg-zinc-50 text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400' }}">
                                {{ ucfirst($vendor->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-12 text-center text-zinc-500">No vendors found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($vendors->hasPages())
        <div class="mt-4 print:hidden">{{ $vendors->links() }}</div>
    @endif
</x-layouts::app>
