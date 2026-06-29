<x-layouts::app :title="__('Dashboard')">

<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.0/dist/apexcharts.min.js"></script>

@php
    $categoryNames  = $assetsByCategory->pluck('name')->toJson();
    $categoryCounts = $assetsByCategory->pluck('count')->toJson();
    $monthLabels    = $assetsByMonth->pluck('month')->toJson();
    $monthCounts    = $assetsByMonth->pluck('count')->toJson();
    $costLabels     = $serviceCostByMonth->pluck('month')->toJson();
    $costTotals     = $serviceCostByMonth->pluck('total')->toJson();

    // Status donut
    $statusSeries = json_encode([$assetStats['active'], $assetStats['under_repair'], $assetStats['inactive'], $assetStats['disposed']]);

    // Expiry grouped bar
    $expiryExpired = json_encode([$warranty['expired'], $extWarranty['expired'], $amc['expired'], $insurance['expired']]);
    $expiry7       = json_encode([$warranty['in7'],     $extWarranty['in7'],     $amc['in7'],     $insurance['in7']]);
    $expiry30      = json_encode([$warranty['in30'],    $extWarranty['in30'],    $amc['in30'],    $insurance['in30']]);

    // Complaints donut — only include non-zero slices to avoid ApexCharts blank-wedge issue
    $complaintOther  = max(0, $complaints['total'] - $complaints['open'] - $complaints['resolved']);
    $complaintRaw    = [['Open', $complaints['open']], ['Resolved', $complaints['resolved']], ['Other', $complaintOther]];
    $complaintActive = array_values(array_filter($complaintRaw, fn($x) => $x[1] > 0));
    $complaintSeries = json_encode(array_column($complaintActive, 1));
    $complaintLabels = json_encode(array_column($complaintActive, 0));
    $complaintColors = json_encode(array_values(array_map(fn($x) => match($x[0]) { 'Open' => '#fb923c', 'Resolved' => '#4ade80', default => '#a1a1aa' }, $complaintActive)));
@endphp

{{-- ── Page Header ── --}}
<div class="mb-6 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-extrabold tracking-tight text-zinc-900 dark:text-zinc-100">Dashboard</h1>
        <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
            {{ now()->format('l, d F Y') }} &mdash; Fixed Asset Management Overview
        </p>
    </div>
    <div class="mt-2 flex items-center gap-2 sm:mt-0">
        <flux:button href="{{ route('assets.create') }}" wire:navigate variant="primary" size="sm">
            <flux:icon.plus class="size-4" /> New Asset
        </flux:button>
        <flux:button href="{{ route('asset-reminders.index', ['filter' => 'expired']) }}" wire:navigate variant="ghost" size="sm">
            <flux:icon.bell-alert class="size-4" /> Expiry Tracker
            @if($reminderStats['expired'] > 0)
                <span class="ml-1 inline-flex items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-xs font-bold leading-none text-white">
                    {{ $reminderStats['expired'] }}
                </span>
            @endif
        </flux:button>
    </div>
</div>

{{-- ── Combined Stat Strip (single row, 9 cards) ── --}}
<div class="grid grid-cols-3 gap-2 sm:grid-cols-5 xl:grid-cols-9">

    @php
        $statCards = [
            ['href' => route('assets.index'),                                      'label' => 'Total Assets',    'value' => number_format($assetStats['total']),              'sub' => $totalAssetValue > 0 ? '₹'.number_format($totalAssetValue/100000,1).'L' : null, 'icon' => 'clipboard-document-list', 'color' => 'text-accent',     'bg' => 'bg-accent/10',        'border' => 'border-zinc-200 dark:border-zinc-800',           'card' => 'bg-white dark:bg-zinc-900'],
            ['href' => route('assets.index', ['status'=>'active']),                'label' => 'Active',          'value' => number_format($assetStats['active']),             'sub' => $assetStats['total'] > 0 ? round($assetStats['active']/$assetStats['total']*100).'%' : null, 'icon' => 'check-circle',            'color' => 'text-green-400',  'bg' => 'bg-green-400/10',     'border' => 'border-green-500/20',                            'card' => 'bg-green-500/5 dark:bg-green-500/5'],
            ['href' => route('assets.index', ['status'=>'under_repair']),          'label' => 'Under Repair',    'value' => number_format($assetStats['under_repair']),       'sub' => null,                                                                                         'icon' => 'wrench-screwdriver',      'color' => 'text-yellow-500', 'bg' => 'bg-yellow-400/10',    'border' => 'border-yellow-500/20',                           'card' => 'bg-yellow-500/5 dark:bg-yellow-500/5'],
            ['href' => route('assets.index', ['status'=>'inactive']),              'label' => 'Inactive',        'value' => number_format($assetStats['inactive']),           'sub' => null,                                                                                         'icon' => 'pause-circle',            'color' => 'text-zinc-400',   'bg' => 'bg-zinc-100 dark:bg-zinc-800', 'border' => 'border-zinc-200 dark:border-zinc-800',      'card' => 'bg-white dark:bg-zinc-900'],
            ['href' => route('assets.index', ['status'=>'disposed']),              'label' => 'Disposed',        'value' => number_format($assetStats['disposed']),           'sub' => null,                                                                                         'icon' => 'archive-box-x-mark',      'color' => 'text-zinc-400',   'bg' => 'bg-zinc-100 dark:bg-zinc-800', 'border' => 'border-zinc-200 dark:border-zinc-800',      'card' => 'bg-white dark:bg-zinc-900'],
            ['href' => route('complaints.index'),                                  'label' => 'Complaints',      'value' => number_format($complaints['open']),               'sub' => $complaints['total'] > 0 ? $complaints['resolved'].' resolved' : null,                       'icon' => 'chat-bubble-oval-left-ellipsis', 'color' => 'text-orange-400', 'bg' => 'bg-orange-400/10', 'border' => 'border-orange-500/20',                      'card' => 'bg-orange-500/5 dark:bg-orange-500/5'],
            ['href' => route('asset-reminders.index', ['filter'=>'expired']),      'label' => 'Coverage Expired', 'value' => number_format($reminderStats['expired']),         'sub' => 'Immediate action',                                                                           'icon' => 'exclamation-triangle',    'color' => 'text-red-400',    'bg' => 'bg-red-400/10',       'border' => 'border-red-500/20',                              'card' => 'bg-red-500/5 dark:bg-red-500/5'],
            ['href' => route('asset-reminders.index', ['filter'=>'upcoming']),     'label' => 'Expiring Soon',   'value' => number_format($reminderStats['expiring_7']),      'sub' => 'Within 7 days',                                                                              'icon' => 'clock',                   'color' => 'text-yellow-500', 'bg' => 'bg-yellow-400/10',    'border' => 'border-yellow-500/20',                           'card' => 'bg-yellow-500/5 dark:bg-yellow-500/5'],
            ['href' => route('asset-reminders.index', ['filter'=>'upcoming']),     'label' => 'Expiring This Month', 'value' => number_format($reminderStats['expiring_30']), 'sub' => 'Within 30 days',                                                                             'icon' => 'calendar-days',           'color' => 'text-blue-400',   'bg' => 'bg-blue-400/10',      'border' => 'border-blue-500/20',                             'card' => 'bg-blue-500/5 dark:bg-blue-500/5'],
        ];
    @endphp

    @foreach($statCards as $card)
        <a href="{{ $card['href'] }}" wire:navigate
           class="relative overflow-hidden rounded-xl border {{ $card['border'] }} {{ $card['card'] }} p-3 transition hover:shadow-sm">
            <div class="flex items-center justify-between gap-1.5">
                <p class="text-[10px] font-semibold uppercase tracking-wide {{ $card['color'] }} opacity-80 truncate">{{ $card['label'] }}</p>
                <span class="flex size-6 shrink-0 items-center justify-center rounded-lg {{ $card['bg'] }}">
                    <flux:icon :icon="$card['icon']" class="size-3.5 {{ $card['color'] }}" />
                </span>
            </div>
            <p class="mt-1.5 text-2xl font-bold {{ $card['color'] }}">{{ $card['value'] }}</p>
            @if($card['sub'])
                <p class="mt-0.5 text-[10px] {{ $card['color'] }} opacity-60">{{ $card['sub'] }}</p>
            @endif
        </a>
    @endforeach

</div>

{{-- ── Main Two-Column Grid ── --}}
<div class="mt-5 grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">

    {{-- LEFT column --}}
    <div class="space-y-5">

        {{-- Coverage Summary --}}
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="px-5 py-4">
                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Coverage &amp; Compliance Status</p>
                <p class="text-[11px] text-zinc-500">Warranty, AMC, Insurance, and vehicle compliance across the fleet</p>
            </div>
            @php
                $coverageCards = [
                    ['label' => 'Warranty',          'icon' => 'shield-check',       'color' => 'text-violet-400',  'bg' => 'bg-violet-400/10',  'data' => $warranty,    'href' => route('reports.warranty-expiry')],
                    ['label' => 'Ext. Warranty',     'icon' => 'shield-exclamation', 'color' => 'text-indigo-400',  'bg' => 'bg-indigo-400/10',  'data' => $extWarranty, 'href' => route('reports.warranty-expiry')],
                    ['label' => 'AMC Contract',      'icon' => 'document-text',      'color' => 'text-blue-400',    'bg' => 'bg-blue-400/10',    'data' => $amc,         'href' => route('reports.amc-expiry')],
                    ['label' => 'Insurance',         'icon' => 'banknotes',          'color' => 'text-teal-400',    'bg' => 'bg-teal-400/10',    'data' => $insurance,   'href' => route('reports.insurance-expiry')],
                ];
            @endphp
            <div class="grid grid-cols-2 gap-px border-t border-zinc-100 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-800 sm:grid-cols-4">
                @foreach($coverageCards as $c)
                    <a href="{{ $c['href'] }}" wire:navigate
                       class="bg-white p-4 transition hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800/60">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-lg {{ $c['bg'] }}">
                                <flux:icon :icon="$c['icon']" class="size-3.5 {{ $c['color'] }}" />
                            </span>
                            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ $c['label'] }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1 text-[11px] text-red-400">
                                    <span class="size-1.5 rounded-full bg-red-400"></span>Expired
                                </span>
                                <span class="text-xs font-bold text-red-400">{{ $c['data']['expired'] }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1 text-[11px] text-yellow-500">
                                    <span class="size-1.5 rounded-full bg-yellow-400"></span>7 days
                                </span>
                                <span class="text-xs font-bold text-yellow-500">{{ $c['data']['in7'] }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1 text-[11px] text-blue-400">
                                    <span class="size-1.5 rounded-full bg-blue-400"></span>30 days
                                </span>
                                <span class="text-xs font-bold text-blue-400">{{ $c['data']['in30'] }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Vehicle Compliance --}}
        @php $hasVehicle = $puc['expired'] + $puc['in7'] + $puc['in30'] + $fitness['expired'] + $fitness['in7'] + $fitness['in30'] + $roadTax['expired'] + $roadTax['in7'] + $roadTax['in30'] > 0; @endphp
        @if($hasVehicle)
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="px-5 py-4">
                <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Vehicle Compliance</p>
                <p class="text-[11px] text-zinc-500">PUC, Fitness &amp; Road Tax expiry status</p>
            </div>
            @php
                $vehicleCards = [
                    ['label' => 'PUC Certificate',    'icon' => 'cloud',         'color' => 'text-sky-400',   'bg' => 'bg-sky-400/10',   'data' => $puc,     'href' => route('reports.puc-expiry')],
                    ['label' => 'Fitness Certificate', 'icon' => 'check-badge',   'color' => 'text-green-400', 'bg' => 'bg-green-400/10', 'data' => $fitness, 'href' => route('reports.fitness-expiry')],
                    ['label' => 'Road Tax',            'icon' => 'currency-rupee','color' => 'text-amber-400', 'bg' => 'bg-amber-400/10', 'data' => $roadTax, 'href' => route('reports.road-tax-expiry')],
                ];
            @endphp
            <div class="grid grid-cols-3 gap-px border-t border-zinc-100 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-800">
                @foreach($vehicleCards as $c)
                    <a href="{{ $c['href'] }}" wire:navigate
                       class="bg-white p-4 transition hover:bg-zinc-50 dark:bg-zinc-900 dark:hover:bg-zinc-800/60">
                        <div class="flex items-center gap-2 mb-3">
                            <span class="flex size-7 shrink-0 items-center justify-center rounded-lg {{ $c['bg'] }}">
                                <flux:icon :icon="$c['icon']" class="size-3.5 {{ $c['color'] }}" />
                            </span>
                            <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">{{ $c['label'] }}</p>
                        </div>
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1 text-[11px] text-red-400"><span class="size-1.5 rounded-full bg-red-400"></span>Expired</span>
                                <span class="text-xs font-bold text-red-400">{{ $c['data']['expired'] }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1 text-[11px] text-yellow-500"><span class="size-1.5 rounded-full bg-yellow-400"></span>7 days</span>
                                <span class="text-xs font-bold text-yellow-500">{{ $c['data']['in7'] }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="flex items-center gap-1 text-[11px] text-blue-400"><span class="size-1.5 rounded-full bg-blue-400"></span>30 days</span>
                                <span class="text-xs font-bold text-blue-400">{{ $c['data']['in30'] }}</span>
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

        {{-- Charts Row 1 --}}
        <div class="grid gap-5 lg:grid-cols-3">
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">By Category</p>
                        <p class="text-[11px] text-zinc-400">Asset distribution</p>
                    </div>
                    <span class="flex size-7 items-center justify-center rounded-lg bg-violet-400/10">
                        <flux:icon.chart-pie class="size-4 text-violet-400" />
                    </span>
                </div>
                @if($assetsByCategory->isEmpty())
                    <div class="flex h-48 items-center justify-center text-sm text-zinc-400">No data yet</div>
                @else
                    <div id="chart-category" class="h-52"></div>
                @endif
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Assets Added</p>
                        <p class="text-[11px] text-zinc-400">Last 6 months</p>
                    </div>
                    <span class="flex size-7 items-center justify-center rounded-lg bg-indigo-400/10">
                        <flux:icon.chart-bar class="size-4 text-indigo-400" />
                    </span>
                </div>
                @if($assetsByMonth->isEmpty())
                    <div class="flex h-48 items-center justify-center text-sm text-zinc-400">No data yet</div>
                @else
                    <div id="chart-monthly" class="h-52"></div>
                @endif
            </div>

            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Service Cost</p>
                        <p class="text-[11px] text-zinc-400">Last 6 months (₹)</p>
                    </div>
                    <span class="flex size-7 items-center justify-center rounded-lg bg-teal-400/10">
                        <flux:icon.currency-rupee class="size-4 text-teal-400" />
                    </span>
                </div>
                @if($serviceCostByMonth->isEmpty())
                    <div class="flex h-48 items-center justify-center text-sm text-zinc-400">No service records yet</div>
                @else
                    <div id="chart-cost" class="h-52"></div>
                @endif
            </div>
        </div>

        {{-- Charts Row 2 --}}
        <div class="grid gap-5 lg:grid-cols-3">

            {{-- Asset Status Donut --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Asset Status</p>
                        <p class="text-[11px] text-zinc-400">Fleet condition breakdown</p>
                    </div>
                    <span class="flex size-7 items-center justify-center rounded-lg bg-green-400/10">
                        <flux:icon.check-circle class="size-4 text-green-400" />
                    </span>
                </div>
                <div id="chart-status" class="h-52"></div>
            </div>

            {{-- Expiry Overview Grouped Bar --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Expiry Overview</p>
                        <p class="text-[11px] text-zinc-400">Warranty, AMC &amp; Insurance status</p>
                    </div>
                    <span class="flex size-7 items-center justify-center rounded-lg bg-red-400/10">
                        <flux:icon.exclamation-triangle class="size-4 text-red-400" />
                    </span>
                </div>
                <div id="chart-expiry" class="h-52"></div>
            </div>

            {{-- Complaints Donut --}}
            <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-100">Complaints</p>
                        <p class="text-[11px] text-zinc-400">Open vs resolved breakdown</p>
                    </div>
                    <span class="flex size-7 items-center justify-center rounded-lg bg-orange-400/10">
                        <flux:icon.chat-bubble-oval-left-ellipsis class="size-4 text-orange-400" />
                    </span>
                </div>
                @if($complaints['total'] === 0)
                    <div class="flex h-48 items-center justify-center text-sm text-zinc-400">No complaints yet</div>
                @else
                    <div id="chart-complaints" class="h-52"></div>
                @endif
            </div>

        </div>

    </div>

    {{-- RIGHT column --}}
    <div class="space-y-5">

        {{-- Upcoming Expiries --}}
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-7 items-center justify-center rounded-lg bg-blue-400/10">
                        <flux:icon.calendar-days class="size-4 text-blue-400" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Upcoming Expiries</p>
                        <p class="text-[11px] text-zinc-500">Next 30 days across all coverage types</p>
                    </div>
                </div>
                <a href="{{ route('asset-reminders.index', ['filter' => 'upcoming']) }}" wire:navigate
                   class="rounded-lg border border-zinc-200 px-2.5 py-1.5 text-[11px] font-medium text-zinc-500 transition hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-400">
                    View all →
                </a>
            </div>

            @if($upcomingExpiries->isEmpty())
                <div class="px-5 py-8 text-center">
                    <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-green-400/10">
                        <flux:icon.check-circle class="size-6 text-green-400" />
                    </div>
                    <p class="mt-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">All clear</p>
                    <p class="mt-1 text-xs text-zinc-500">No expiries in the next 30 days.</p>
                </div>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                    @foreach($upcomingExpiries->take(10) as $item)
                        @php
                            $daysLeft  = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($item['expiry_date'])->startOfDay(), false);
                            $urgency   = $daysLeft <= 0 ? 'overdue' : ($daysLeft <= 7 ? 'soon' : 'later');
                            $dotBg     = match($urgency) { 'overdue' => 'bg-red-400', 'soon' => 'bg-yellow-400', default => 'bg-blue-400' };
                            $badge     = match($urgency) { 'overdue' => 'bg-red-400/10 text-red-400', 'soon' => 'bg-yellow-400/10 text-yellow-500', default => 'bg-blue-400/10 text-blue-400' };
                            $leftBorder= match($urgency) { 'overdue' => 'border-l-2 border-l-red-400', 'soon' => 'border-l-2 border-l-yellow-400', default => 'border-l-2 border-l-blue-400' };
                            $typeBadge = ['Warranty' => 'bg-violet-400/10 text-violet-400', 'Ext. Warranty' => 'bg-indigo-400/10 text-indigo-400', 'AMC' => 'bg-blue-400/10 text-blue-400', 'Insurance' => 'bg-teal-400/10 text-teal-400'][$item['type']] ?? 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400';
                        @endphp
                        <div class="flex items-center gap-3 py-3 pl-4 pr-5 {{ $leftBorder }}">
                            <span class="size-2.5 shrink-0 rounded-full {{ $dotBg }}"></span>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @if($item['asset_id'])
                                        <a href="{{ route('assets.show', [$item['asset_id'], 'tab' => $item['tab']]) }}" wire:navigate
                                           class="text-sm font-semibold text-zinc-900 transition hover:text-accent dark:text-zinc-100">
                                            {{ $item['asset_code'] }}
                                        </a>
                                    @else
                                        <span class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">—</span>
                                    @endif
                                    <span class="rounded-md px-1.5 py-0.5 text-[10px] font-medium {{ $typeBadge }}">{{ $item['type'] }}</span>
                                </div>
                                @if(!empty($item['asset_name']))
                                    <p class="truncate text-[11px] text-zinc-400">{{ $item['asset_name'] }}</p>
                                @endif
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="rounded-lg px-2 py-0.5 text-[11px] font-bold {{ $badge }}">
                                    {{ $daysLeft <= 0 ? 'Expired' : $daysLeft.'d left' }}
                                </span>
                                <p class="mt-0.5 text-[11px] text-zinc-400">{{ \Carbon\Carbon::parse($item['expiry_date'])->format('d M Y') }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Overdue Maintenance --}}
        @if($overdueSchedules->count() > 0)
        <div class="overflow-hidden rounded-2xl border border-red-500/20 bg-red-500/5 dark:border-red-500/20 dark:bg-red-500/5">
            <div class="flex items-center gap-2.5 border-b border-red-500/10 px-5 py-4">
                <span class="flex size-7 items-center justify-center rounded-lg bg-red-400/15">
                    <flux:icon.exclamation-circle class="size-4 text-red-400" />
                </span>
                <div>
                    <p class="text-sm font-semibold text-red-400">Overdue Maintenance</p>
                    <p class="text-[11px] text-red-400/70">Schedules past their due date</p>
                </div>
            </div>
            <div class="divide-y divide-red-500/10">
                @foreach($overdueSchedules as $s)
                    <a href="{{ route('assets.show', [$s->asset_id, 'tab' => 'schedules']) }}" wire:navigate
                       class="flex items-center gap-3 px-5 py-3 transition hover:bg-red-500/5">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-red-400/10">
                            <flux:icon.wrench-screwdriver class="size-4 text-red-400" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-red-400">{{ $s->asset_code }}</p>
                            <p class="truncate text-[11px] text-red-400/70">{{ $s->schedule_name }}</p>
                        </div>
                        <div class="shrink-0 text-right">
                            <p class="text-xs font-bold text-red-400">{{ \Carbon\Carbon::parse($s->next_due_date)->format('d M') }}</p>
                            <p class="text-[11px] text-red-400/70">{{ now()->diffInDays($s->next_due_date) }}d overdue</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>

{{-- ── ApexCharts Init ── --}}
<script>
(function () {
    const CHART_IDS = ['chart-category', 'chart-monthly', 'chart-cost', 'chart-status', 'chart-expiry', 'chart-complaints'];

    function destroyExisting() {
        CHART_IDS.forEach(id => {
            const el = document.querySelector('#' + id);
            if (el && el._apexChart) { el._apexChart.destroy(); el._apexChart = null; }
        });
    }

    function makeChart(el, options) {
        const chart = new ApexCharts(el, options);
        chart.render();
        el._apexChart = chart;
    }

    function initDashboardCharts() {
        if (!document.querySelector('#chart-category, #chart-monthly, #chart-cost, #chart-status, #chart-expiry, #chart-complaints')) return;
        destroyExisting();

        const isDark    = document.documentElement.classList.contains('dark');
        const textColor = isDark ? '#a1a1aa' : '#71717a';
        const gridColor = isDark ? '#27272a' : '#f4f4f5';

        const base = {
            chart:   { background: 'transparent', toolbar: { show: false }, fontFamily: 'inherit' },
            grid:    { borderColor: gridColor, strokeDashArray: 4, padding: { left: 0, right: 0, top: 0, bottom: 0 } },
            tooltip: { theme: isDark ? 'dark' : 'light' },
        };

        const catEl = document.querySelector('#chart-category');
        if (catEl) {
            makeChart(catEl, {
                ...base,
                chart:       { ...base.chart, type: 'donut', height: 208 },
                series:      {!! $categoryCounts !!},
                labels:      {!! $categoryNames !!},
                colors:      ['#8b5cf6','#6366f1','#3b82f6','#0ea5e9','#14b8a6','#10b981','#f59e0b','#f97316'],
                plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', color: textColor, fontSize: '12px', fontWeight: 600 } } } } },
                dataLabels:  { enabled: false },
                legend:      { show: true, position: 'bottom', fontSize: '11px', labels: { colors: textColor }, itemMargin: { horizontal: 6, vertical: 2 }, markers: { size: 6, shape: 'circle' } },
            });
        }

        const monthEl = document.querySelector('#chart-monthly');
        if (monthEl) {
            makeChart(monthEl, {
                ...base,
                chart:        { ...base.chart, type: 'bar', height: 208 },
                series:       [{ name: 'Assets Added', data: {!! $monthCounts !!} }],
                xaxis:        { categories: {!! $monthLabels !!}, labels: { style: { colors: textColor, fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis:        { labels: { style: { colors: textColor, fontSize: '11px' } }, min: 0, forceNiceScale: true, tickAmount: 4 },
                colors:       ['#6366f1'],
                plotOptions:  { bar: { borderRadius: 5, columnWidth: '50%' } },
                dataLabels:   { enabled: false },
            });
        }

        const costEl = document.querySelector('#chart-cost');
        if (costEl) {
            makeChart(costEl, {
                ...base,
                chart:      { ...base.chart, type: 'area', height: 208 },
                series:     [{ name: 'Service Cost (₹)', data: {!! $costTotals !!} }],
                xaxis:      { categories: {!! $costLabels !!}, labels: { style: { colors: textColor, fontSize: '11px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis:      { labels: { style: { colors: textColor, fontSize: '11px' }, formatter: v => '₹' + (v >= 1000 ? (v/1000).toFixed(1)+'k' : v) } },
                colors:     ['#14b8a6'],
                fill:       { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 95, 100] } },
                stroke:     { curve: 'smooth', width: 2 },
                dataLabels: { enabled: false },
            });
        }

        // Donut: asset status
        const statusEl = document.querySelector('#chart-status');
        if (statusEl) {
            makeChart(statusEl, {
                ...base,
                chart:       { ...base.chart, type: 'donut', height: 208 },
                series:      {!! $statusSeries !!},
                labels:      ['Active', 'Under Repair', 'Inactive', 'Disposed'],
                colors:      ['#4ade80', '#facc15', '#a1a1aa', '#71717a'],
                plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', color: textColor, fontSize: '12px', fontWeight: 600 } } } } },
                dataLabels:  { enabled: false },
                legend:      { show: true, position: 'bottom', fontSize: '11px', labels: { colors: textColor }, itemMargin: { horizontal: 6, vertical: 2 }, markers: { size: 6, shape: 'circle' } },
            });
        }

        // Grouped bar: expiry overview
        const expiryEl = document.querySelector('#chart-expiry');
        if (expiryEl) {
            makeChart(expiryEl, {
                ...base,
                chart:        { ...base.chart, type: 'bar', height: 208 },
                series:       [
                    { name: 'Expired',      data: {!! $expiryExpired !!} },
                    { name: 'Within 7d',    data: {!! $expiry7 !!} },
                    { name: 'Within 30d',   data: {!! $expiry30 !!} },
                ],
                xaxis:        { categories: ['Warranty', 'Ext. Warranty', 'AMC', 'Insurance'], labels: { style: { colors: textColor, fontSize: '10px' } }, axisBorder: { show: false }, axisTicks: { show: false } },
                yaxis:        { labels: { style: { colors: textColor, fontSize: '11px' } }, min: 0, forceNiceScale: true, tickAmount: 4 },
                colors:       ['#f87171', '#facc15', '#60a5fa'],
                plotOptions:  { bar: { borderRadius: 3, columnWidth: '65%', borderRadiusApplication: 'end' } },
                dataLabels:   { enabled: false },
                legend:       { show: true, position: 'bottom', fontSize: '11px', labels: { colors: textColor }, itemMargin: { horizontal: 6, vertical: 2 }, markers: { size: 6, shape: 'circle' } },
            });
        }

        // Donut: complaints
        const complaintsEl = document.querySelector('#chart-complaints');
        if (complaintsEl) {
            makeChart(complaintsEl, {
                ...base,
                chart:       { ...base.chart, type: 'donut', height: 208 },
                series:      {!! $complaintSeries !!},
                labels:      {!! $complaintLabels !!},
                colors:      {!! $complaintColors !!},
                plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, label: 'Total', color: textColor, fontSize: '12px', fontWeight: 600 } } } } },
                dataLabels:  { enabled: false },
                legend:      { show: true, position: 'bottom', fontSize: '11px', labels: { colors: textColor }, itemMargin: { horizontal: 6, vertical: 2 }, markers: { size: 6, shape: 'circle' } },
            });
        }
    }

    document.addEventListener('DOMContentLoaded', initDashboardCharts);
    document.addEventListener('livewire:navigated', initDashboardCharts);
})();
</script>

</x-layouts::app>
