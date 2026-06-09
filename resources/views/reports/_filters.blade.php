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
    $hasFilters = request()->hasAny(['category_id','subcategory_id','department','location','custodian','vendor','status','expiry_filter','date_from','date_to','service_type','search']);
@endphp

<form method="GET" action="{{ url()->current() }}" class="mb-5 flex flex-wrap gap-3 items-end">

    @if ($showSearch)
        <flux:input name="search" value="{{ request('search') }}"
                    placeholder="Search…" class="w-56" icon="magnifying-glass" />
    @endif

    {{-- Category --}}
    <flux:select name="category_id" placeholder="All Categories" class="w-44"
                 onchange="this.form.submit()">
        <option value="">All Categories</option>
        @foreach ($categories as $cat)
            <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
        @endforeach
    </flux:select>

    {{-- Subcategory (only visible when category selected) --}}
    @if (request('category_id'))
        <flux:select name="subcategory_id" placeholder="All Subcategories" class="w-44">
            <option value="">All Subcategories</option>
            @foreach ($subcategories->where('asset_category_id', request('category_id')) as $sub)
                <option value="{{ $sub->id }}" @selected(request('subcategory_id') == $sub->id)>{{ $sub->name }}</option>
            @endforeach
        </flux:select>
    @else
        <input type="hidden" name="subcategory_id" value="">
    @endif

    {{-- Department text filter --}}
    <flux:input name="department" value="{{ request('department') }}"
                placeholder="Department" class="w-40" />

    {{-- Expiry dropdown --}}
    @if ($showExpiry)
        <flux:select name="expiry_filter" class="w-44">
            @foreach ($expiryOptions as $val => $label)
                <option value="{{ $val }}" @selected(request('expiry_filter', 'all') === $val)>{{ $label }}</option>
            @endforeach
        </flux:select>
    @endif

    {{-- Date range --}}
    @if ($showDates)
        <flux:input type="date" name="date_from" value="{{ request('date_from') }}" class="w-38" />
        <flux:input type="date" name="date_to"   value="{{ request('date_to') }}"   class="w-38" />
    @endif

    {{-- Status --}}
    @if ($showStatus)
        <flux:select name="status" placeholder="All Statuses" class="w-40">
            <option value="">All Statuses</option>
            <option value="active"              @selected(request('status') === 'active')>Active</option>
            <option value="under_repair"        @selected(request('status') === 'under_repair')>Under Repair</option>
            <option value="disposed"            @selected(request('status') === 'disposed')>Disposed</option>
            <option value="scrapped"            @selected(request('status') === 'scrapped')>Scrapped</option>
            <option value="inactive"            @selected(request('status') === 'inactive')>Inactive</option>
        </flux:select>
    @endif

    {{-- Service Type --}}
    @if ($showServiceType)
        <flux:select name="service_type" placeholder="All Types" class="w-48">
            <option value="">All Service Types</option>
            <option value="preventive_maintenance" @selected(request('service_type') === 'preventive_maintenance')>Preventive Maintenance</option>
            <option value="corrective_maintenance" @selected(request('service_type') === 'corrective_maintenance')>Corrective Maintenance</option>
            <option value="inspection"             @selected(request('service_type') === 'inspection')>Inspection</option>
            <option value="repair"                 @selected(request('service_type') === 'repair')>Repair</option>
            <option value="calibration"            @selected(request('service_type') === 'calibration')>Calibration</option>
            <option value="cleaning"               @selected(request('service_type') === 'cleaning')>Cleaning</option>
            <option value="other"                  @selected(request('service_type') === 'other')>Other</option>
        </flux:select>
    @endif

    <flux:button type="submit" variant="ghost" icon="funnel">Filter</flux:button>

    @if ($hasFilters)
        <flux:button href="{{ url()->current() }}" wire:navigate variant="ghost" icon="x-mark">Clear</flux:button>
    @endif

    <div class="ml-auto flex items-center gap-2 print:hidden">
        @if ($exportUrl)
            <flux:button href="{{ $exportUrl }}" variant="ghost" icon="arrow-down-tray">
                Export Excel
            </flux:button>
        @endif
        <flux:button type="button" onclick="window.print()" variant="ghost" icon="printer">
            Print
        </flux:button>
    </div>
</form>
