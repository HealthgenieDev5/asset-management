{{--
  Shared report filter bar.
  Props (pass as @include('reports._filters', [...]) ):
    $showExpiry      bool   — show expiry_filter dropdown
    $showDates       bool   — show date_from / date_to
    $showStatus      bool   — show status filter
    $showServiceType bool   — show service_type filter
    $showSearch      bool   — show keyword search
    $expiryLabel     string — label for expiry filter (default 'Expiry')
    $expiryOptions   array  — [value => label] pairs (optional override)
    $exportUrl       string — if set, shows an Export Excel button before Print
--}}
@php
    $showExpiry     = $showExpiry     ?? false;
    $showDates      = $showDates      ?? false;
    $showStatus     = $showStatus     ?? false;
    $showServiceType= $showServiceType ?? false;
    $showSearch     = $showSearch     ?? false;
    $expiryLabel    = $expiryLabel    ?? 'Expiry';
    $exportUrl      = $exportUrl      ?? null;
    $expiryOptions  = $expiryOptions  ?? [
        'all'     => 'All',
        'expired' => 'Expired',
        'in30'    => 'Within 30 Days',
        'in90'    => 'Within 90 Days',
    ];
    $activeFilters = array_filter(request()->only(['category_id','subcategory_id','department','location','custodian','vendor','status','expiry_filter','date_from','date_to','service_type','search']));
    $hasFilters = count($activeFilters) > 0;
    $filterCount = count($activeFilters);
@endphp

<form method="GET" action="{{ url()->current() }}" class="mb-6 print:hidden">

    {{-- Filter Card --}}
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
                    <a href="{{ url()->current() }}"
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

                @if ($exportUrl)
                    <a href="{{ $exportUrl }}"
                       class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-medium text-zinc-500 transition hover:bg-emerald-50 hover:text-emerald-600 dark:hover:bg-emerald-900/20 dark:hover:text-emerald-400">
                        <svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                        </svg>
                        Excel
                    </a>
                @endif

                {{-- Print button hidden for now --}}
            </div>
        </div>

        {{-- Filter Fields --}}
        <div class="flex flex-wrap items-end gap-3 px-4 py-3">

            @if ($showSearch)
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Search</label>
                    <div class="relative">
                        <svg class="absolute left-2.5 top-1/2 size-3.5 -translate-y-1/2 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 15.803a7.5 7.5 0 0 0 10.607 0Z" />
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Keyword…"
                               class="h-8 w-44 rounded-lg border border-zinc-200 bg-zinc-50 pl-8 pr-3 text-xs text-zinc-800 placeholder-zinc-400 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-200 dark:placeholder-zinc-500 dark:focus:bg-zinc-800 dark:focus:text-zinc-100" />
                    </div>
                </div>
            @endif

            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Category</label>
                <select name="category_id" onchange="this.form.submit()"
                        class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:focus:bg-zinc-800 {{ request('category_id') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            @if (request('category_id'))
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Subcategory</label>
                    <select name="subcategory_id" onchange="this.form.submit()"
                            class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:focus:bg-zinc-800 {{ request('subcategory_id') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}">
                        <option value="">All Subcategories</option>
                        @foreach ($subcategories->where('asset_category_id', request('category_id')) as $sub)
                            <option value="{{ $sub->id }}" @selected(request('subcategory_id') == $sub->id)>{{ $sub->name }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" name="subcategory_id" value="">
            @endif

            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Location</label>
                <select name="location" onchange="this.form.submit()"
                        class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:focus:bg-zinc-800 {{ request('location') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}">
                    <option value="">All Locations</option>
                    @foreach ($locations as $loc)
                        <option value="{{ $loc }}" @selected(request('location') === $loc)>{{ $loc }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex flex-col gap-1">
                <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Department</label>
                <select name="department" onchange="this.form.submit()"
                        class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:focus:bg-zinc-800 {{ request('department') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}">
                    <option value="">All Departments</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept }}" @selected(request('department') === $dept)>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>

            @if ($showExpiry)
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">{{ $expiryLabel }}</label>
                    <select name="expiry_filter"
                            class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:focus:bg-zinc-800 dark:focus:text-zinc-100 {{ (request('expiry_filter') && request('expiry_filter') !== 'all') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}">
                        @foreach ($expiryOptions as $val => $label)
                            <option value="{{ $val }}" @selected(request('expiry_filter', 'all') === $val)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if ($showDates)
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">From Date</label>
                    <input type="text" inputmode="none" autocomplete="off" name="date_from" value="{{ request('date_from') }}"
                           placeholder="From date" data-datepicker
                           class="h-8 w-36 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:focus:bg-zinc-800 dark:focus:text-zinc-100 {{ request('date_from') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">To Date</label>
                    <input type="text" inputmode="none" autocomplete="off" name="date_to" value="{{ request('date_to') }}"
                           placeholder="To date" data-datepicker
                           class="h-8 w-36 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:focus:bg-zinc-800 dark:focus:text-zinc-100 {{ request('date_to') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}" />
                </div>
            @endif

            @if ($showStatus)
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
            @endif

            @if ($showServiceType)
                <div class="flex flex-col gap-1">
                    <label class="text-[10px] font-semibold uppercase tracking-wider text-zinc-400">Service Type</label>
                    <select name="service_type"
                            class="h-8 rounded-lg border border-zinc-200 bg-zinc-50 px-2.5 text-xs text-zinc-700 transition focus:border-accent focus:bg-white focus:text-zinc-900 focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:focus:bg-zinc-800 dark:focus:text-zinc-100 {{ request('service_type') ? 'border-accent/60 bg-accent/5 font-semibold text-accent dark:bg-accent/10' : '' }}">
                        <option value="">All Service Types</option>
                        <option value="preventive_maintenance" @selected(request('service_type') === 'preventive_maintenance')>Preventive Maintenance</option>
                        <option value="corrective_maintenance" @selected(request('service_type') === 'corrective_maintenance')>Corrective Maintenance</option>
                        <option value="inspection"             @selected(request('service_type') === 'inspection')>Inspection</option>
                        <option value="repair"                 @selected(request('service_type') === 'repair')>Repair</option>
                        <option value="calibration"            @selected(request('service_type') === 'calibration')>Calibration</option>
                        <option value="cleaning"               @selected(request('service_type') === 'cleaning')>Cleaning</option>
                        <option value="other"                  @selected(request('service_type') === 'other')>Other</option>
                    </select>
                </div>
            @endif

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
                @foreach ($subcategories->where('id', request('subcategory_id')) as $sub)
                    <span class="inline-flex items-center gap-1 rounded-full bg-accent/10 px-2.5 py-0.5 text-xs font-medium text-accent">
                        {{ $sub->name }}
                    </span>
                @endforeach
                @if (request('location'))
                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        Location: {{ request('location') }}
                    </span>
                @endif
                @if (request('department'))
                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        Dept: {{ request('department') }}
                    </span>
                @endif
                @if (request('status'))
                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        Status: {{ ucfirst(str_replace('_', ' ', request('status'))) }}
                    </span>
                @endif
                @if (request('expiry_filter') && request('expiry_filter') !== 'all')
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-600 dark:bg-amber-900/20 dark:text-amber-400">
                        {{ $expiryOptions[request('expiry_filter')] ?? request('expiry_filter') }}
                    </span>
                @endif
                @if (request('date_from') || request('date_to'))
                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <svg class="size-3 text-zinc-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
                        {{ request('date_from') ?: '…' }} → {{ request('date_to') ?: '…' }}
                    </span>
                @endif
                @if (request('service_type'))
                    <span class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ ucfirst(str_replace('_', ' ', request('service_type'))) }}
                    </span>
                @endif
            </div>
        @endif

    </div>

</form>
