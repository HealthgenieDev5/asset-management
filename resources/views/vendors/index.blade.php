<x-layouts::app :title="__('Vendors')">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-extrabold">
                Vendor <span class="text-accent">Directory</span>
            </flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Manage vendor contact details, SLA terms, and linked service records.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('vendors.export', request()->query()) }}" variant="ghost" size="sm">
                <flux:icon.arrow-down-tray class="size-4" />
                Export CSV
            </flux:button>
            <flux:button href="{{ route('vendors.create') }}" wire:navigate variant="filled" class="bg-accent text-accent-foreground hover:opacity-90">
                <flux:icon.plus class="size-4" />
                Add Vendor
            </flux:button>
        </div>
    </div>

    @include('partials.flash')

    @php
        $activeFilters = array_filter(request()->only(['search', 'status', 'service_type']));
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
                        <a href="{{ route('vendors.index') }}"
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

            <div class="flex flex-wrap items-end gap-3 px-4 py-3">
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Search</label>
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z" />
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, code, contact…"
                               class="h-8 w-56 rounded-lg border border-zinc-200 bg-zinc-50 pl-8 pr-3 text-xs text-zinc-800 placeholder-zinc-400 transition focus:border-accent focus:bg-white focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:placeholder-zinc-500 {{ request('search') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}" />
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Status</label>
                    <select name="status"
                            class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 {{ request('status') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}">
                        <option value="">All Statuses</option>
                        <option value="active"   @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Service Type</label>
                    <select name="service_type"
                            class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 {{ request('service_type') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}">
                        <option value="">All Types</option>
                        <option value="warranty" @selected(request('service_type') === 'warranty')>Warranty</option>
                        <option value="amc"      @selected(request('service_type') === 'amc')>AMC</option>
                        <option value="service"  @selected(request('service_type') === 'service')>Service</option>
                        <option value="all"      @selected(request('service_type') === 'all')>All</option>
                    </select>
                </div>
            </div>

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
                    @if (request('service_type'))
                        <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                            Type: {{ ucfirst(request('service_type')) }}
                        </span>
                    @endif
                </div>
            @endif

        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-zinc-200 text-left text-zinc-500 dark:border-zinc-800 dark:text-zinc-400">
                    <th class="px-4 py-3 font-semibold">#</th>
                    <th class="px-4 py-3 font-semibold">Code</th>
                    <th class="px-4 py-3 font-semibold">Name</th>
                    <th class="px-4 py-3 font-semibold">Contact</th>
                    <th class="px-4 py-3 font-semibold">Service Types</th>
                    <th class="px-4 py-3 font-semibold">SLA</th>
                    <th class="px-4 py-3 font-semibold">Linked Records</th>
                    <th class="px-4 py-3 font-semibold">Status</th>
                    <th class="px-4 py-3 font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                @forelse ($vendors as $vendor)
                    <tr class="hover:bg-accent/5 transition-colors">
                        <td class="px-4 py-3 text-zinc-500">{{ $vendors->firstItem() + $loop->index }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded bg-zinc-100 px-2 py-0.5 font-mono text-xs font-bold tracking-widest text-accent dark:bg-zinc-800">
                                {{ $vendor->code }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('vendors.show', $vendor) }}" wire:navigate class="font-medium text-zinc-900 hover:text-accent dark:text-zinc-100 hover:underline">
                                {{ $vendor->name }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">
                            <div>{{ $vendor->contact_person ?? '—' }}</div>
                            @if ($vendor->phone)
                                <div class="text-xs text-zinc-400">{{ $vendor->phone }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 text-xs">{{ $vendor->serviceTypesLabel() }}</td>
                        <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 font-mono text-xs">{{ $vendor->slaLabel() }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('vendors.show', $vendor) }}" wire:navigate class="text-xs text-zinc-500 hover:text-accent dark:text-zinc-400">
                                @if ($vendor->warranties_count || $vendor->amc_contracts_count || $vendor->services_count)
                                    @if ($vendor->warranties_count)
                                        <span>{{ $vendor->warranties_count }} {{ Str::plural('warranty', $vendor->warranties_count) }}</span>
                                    @endif
                                    @if ($vendor->amc_contracts_count)
                                        <span class="{{ $vendor->warranties_count ? 'ml-1' : '' }}">· {{ $vendor->amc_contracts_count }} AMC</span>
                                    @endif
                                    @if ($vendor->services_count)
                                        <span class="{{ ($vendor->warranties_count || $vendor->amc_contracts_count) ? 'ml-1' : '' }}">· {{ $vendor->services_count }} {{ Str::plural('service', $vendor->services_count) }}</span>
                                    @endif
                                @else
                                    <span class="text-zinc-400">None</span>
                                @endif
                            </a>
                        </td>
                        <td class="px-4 py-3">
                            @if ($vendor->status === 'active')
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-300 dark:bg-green-900/40 dark:text-green-400 dark:ring-green-700">Active</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-500 ring-1 ring-zinc-300 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700">Inactive</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-2">
                                <flux:button href="{{ route('vendors.edit', $vendor) }}" wire:navigate size="sm" variant="ghost">
                                    <flux:icon.pencil class="size-3.5" />
                                    Edit
                                </flux:button>
                                <form method="POST" action="{{ route('vendors.destroy', $vendor) }}" onsubmit="return confirm('Delete this vendor?')">
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
                        <td colspan="9" class="px-4 py-12 text-center text-zinc-500">
                            No vendors found.
                            <a href="{{ route('vendors.create') }}" class="ml-1 text-accent hover:underline">Add one</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $vendors->links() }}
    </div>
</x-layouts::app>
