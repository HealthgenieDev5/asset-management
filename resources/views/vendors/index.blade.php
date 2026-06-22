<x-layouts::app :title="__('Vendors')">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <flux:heading size="xl" class="font-extrabold">
                Vendor <span class="text-accent">Directory</span>
            </flux:heading>
            <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">Manage vendor contact details and linked service records.</flux:text>
        </div>
        <div class="flex items-center gap-2">
            <flux:button href="{{ route('vendors.export', request()->query()) }}" variant="ghost" size="sm">
                <flux:icon.arrow-down-tray class="size-4" />
                Export CSV
            </flux:button>
            <button type="button" x-data x-on:click="$dispatch('open-modal-add-vendor')"
                class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-2 text-sm font-semibold text-accent-foreground shadow-sm transition hover:opacity-90 active:scale-95">
                <flux:icon.plus class="size-4" />
                Add Vendor
            </button>
        </div>
    </div>

    @include('partials.flash')

    {{-- Add Vendor Modal --}}
    <x-modal name="add-vendor" title="Add Vendor" maxWidth="42rem" :dismissible="false"
        :auto-open="$errors->any() && old('_form') === 'add-vendor'">
        <form method="POST" action="{{ route('vendors.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="_form" value="add-vendor">
            @include('vendors._form')
            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">Save Vendor</flux:button>
                <button type="button" x-on:click="$dispatch('close-modal-add-vendor')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 transition hover:text-zinc-700 dark:hover:text-zinc-300">
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Edit Vendor Modal --}}
    <x-modal name="edit-vendor" title="Edit Vendor" maxWidth="42rem" :dismissible="false"
        :auto-open="$errors->any() && old('_form') === 'edit-vendor'">
        <form method="POST" id="vendor-edit-form" action="" class="space-y-4"
            x-data="{}"
            x-on:open-modal-edit-vendor.window="
                let d = $event.detail;
                $el.action = '/vendors/' + d.id;
                ['name','status','phone','alt_phone','email','alt_email','address']
                    .forEach(k => {
                        let el = document.getElementById('edit_' + k);
                        if (el) el.value = d[k] ?? '';
                    });
                $el.querySelectorAll('input[name=type]').forEach(r => {
                    r.checked = (r.value === d.type);
                    r.dispatchEvent(new Event('change'));
                });
            ">
            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit-vendor">
            @include('vendors._form', ['fieldPrefix' => 'edit_', 'vendor' => null])
            <div class="flex items-center gap-3 pt-1">
                <flux:button type="submit" variant="primary" size="sm" icon="check">Update Vendor</flux:button>
                <button type="button" x-on:click="$dispatch('close-modal-edit-vendor')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 transition hover:text-zinc-700 dark:hover:text-zinc-300">
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Filters --}}
    @php
        $activeFilters = array_filter(request()->only(['search', 'status']));
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
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Name, phone, email…"
                               class="h-8 w-56 rounded-lg border border-zinc-200 bg-white pl-8 pr-3 text-xs text-zinc-800 placeholder-zinc-400 transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:placeholder-zinc-500" />
                    </div>
                </div>

                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Status</label>
                    <select name="status"
                            class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300">
                        <option value="">All Statuses</option>
                        <option value="active"   @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
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
                </div>
            @endif

        </div>
    </form>

    {{-- Vendor Cards --}}
    @if ($vendors->isEmpty())
        <div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-200 bg-white py-16 dark:border-zinc-700 dark:bg-zinc-900">
            <svg class="mb-4 size-12 text-zinc-300 dark:text-zinc-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
            </svg>
            <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400">No vendors found.</p>
            <button type="button" x-data x-on:click="$dispatch('open-modal-add-vendor')"
                class="mt-3 text-sm font-semibold text-accent hover:underline">
                Add your first vendor
            </button>
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($vendors as $vendor)
                <div class="flex flex-col overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm transition hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">

                    {{-- Card Header --}}
                    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
                        @if ($vendor->status === 'active')
                            <span class="inline-flex items-center gap-1 rounded-full bg-green-100 px-2 py-0.5 text-[10px] font-semibold text-green-700 ring-1 ring-green-300 dark:bg-green-900/40 dark:text-green-400 dark:ring-green-700">
                                <span class="size-1.5 rounded-full bg-green-500"></span>
                                Active
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[10px] font-semibold text-zinc-500 ring-1 ring-zinc-300 dark:bg-zinc-800 dark:text-zinc-400 dark:ring-zinc-700">
                                <span class="size-1.5 rounded-full bg-zinc-400"></span>
                                Inactive
                            </span>
                        @endif
                    </div>

                    {{-- Card Body --}}
                    <div class="flex flex-1 flex-col gap-3 px-4 py-4">
                        {{-- Name + Type --}}
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <a href="{{ route('vendors.show', $vendor) }}" wire:navigate
                                   class="text-sm font-bold text-zinc-900 hover:text-accent dark:text-zinc-100">
                                    {{ $vendor->name }}
                                </a>
                                <a href="{{ route('vendors.show', $vendor) }}" wire:navigate
                                   class="shrink-0 inline-flex items-center gap-1 rounded-lg border border-zinc-200 px-2 py-0.5 text-[10px] font-semibold text-zinc-500 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-400 dark:hover:border-accent dark:hover:text-accent">
                                    Details
                                    <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </a>
                            </div>
                            <span class="mt-0.5 inline-flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                                @if ($vendor->type === 'company')
                                    <svg class="size-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />
                                    </svg>
                                @else
                                    <svg class="size-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                    </svg>
                                @endif
                                {{ $vendor->typeLabel() }}
                            </span>
                        </div>

                        {{-- Contact --}}
                        <div class="space-y-1.5">
                            @if ($vendor->phone)
                                <div class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                                    <svg class="size-3.5 shrink-0 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                                    </svg>
                                    <span>{{ $vendor->phone }}</span>
                                </div>
                                @if ($vendor->alt_phone)
                                    <div class="flex items-center gap-2 pl-5 text-xs text-zinc-400 dark:text-zinc-500">
                                        <span>{{ $vendor->alt_phone }} <span class="text-[10px]">(alt)</span></span>
                                    </div>
                                @endif
                            @endif

                            @if ($vendor->email)
                                <div class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-300">
                                    <svg class="size-3.5 shrink-0 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                    <span class="truncate">{{ $vendor->email }}</span>
                                </div>
                                @if ($vendor->alt_email)
                                    <div class="flex items-center gap-2 pl-5 text-xs text-zinc-400 dark:text-zinc-500">
                                        <span class="truncate">{{ $vendor->alt_email }} <span class="text-[10px]">(alt)</span></span>
                                    </div>
                                @endif
                            @endif

                            @if ($vendor->address)
                                <div class="flex items-start gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                                    <svg class="mt-0.5 size-3.5 shrink-0 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                    </svg>
                                    <span class="line-clamp-1">{{ $vendor->address }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Card Footer --}}
                    <div class="flex items-center justify-between border-t border-zinc-100 px-4 py-2.5 dark:border-zinc-800">
                        <div class="flex items-center gap-1.5 text-[10px] text-zinc-400 dark:text-zinc-500">
                            @if ($vendor->warranties_count)
                                <span>{{ $vendor->warranties_count }}W</span>
                            @endif
                            @if ($vendor->amc_contracts_count)
                                @if ($vendor->warranties_count) <span>·</span> @endif
                                <span>{{ $vendor->amc_contracts_count }}A</span>
                            @endif
                            @if ($vendor->services_count)
                                @if ($vendor->warranties_count || $vendor->amc_contracts_count) <span>·</span> @endif
                                <span>{{ $vendor->services_count }}S</span>
                            @endif
                            @if (! $vendor->warranties_count && ! $vendor->amc_contracts_count && ! $vendor->services_count)
                                <span>No linked records</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1">
                            <button type="button" x-data
                                x-on:click="$dispatch('open-modal-edit-vendor', {
                                    id: {{ $vendor->id }},
                                    name: @js($vendor->name),
                                    type: @js($vendor->type),
                                    status: @js($vendor->status),
                                    phone: @js($vendor->phone),
                                    alt_phone: @js($vendor->alt_phone),
                                    email: @js($vendor->email),
                                    alt_email: @js($vendor->alt_email),
                                    address: @js($vendor->address),
                                })"
                                class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-zinc-500 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-300">
                                <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                </svg>
                                Edit
                            </button>
                            <form method="POST" action="{{ route('vendors.destroy', $vendor) }}"
                                  onsubmit="return confirm('Delete {{ addslashes($vendor->name) }}? This cannot be undone.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-red-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40">
                                    <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $vendors->links() }}
        </div>
    @endif
</x-layouts::app>
