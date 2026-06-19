<x-layouts::app :title="__('Dashboard')">

{{-- ApexCharts --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.0/dist/apexcharts.min.js"></script>

@php
    $categoryNames  = $assetsByCategory->pluck('name')->toJson();
    $categoryCounts = $assetsByCategory->pluck('count')->toJson();
    $monthLabels    = $assetsByMonth->pluck('month')->toJson();
    $monthCounts    = $assetsByMonth->pluck('count')->toJson();
    $costLabels     = $serviceCostByMonth->pluck('month')->toJson();
    $costTotals     = $serviceCostByMonth->pluck('total')->toJson();
    $isDark         = false;
@endphp

{{-- ── Page Header ──────────────────────────────────────────────────────────── --}}
<div class="mb-6 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-extrabold text-zinc-900 dark:text-zinc-100 tracking-tight">
            Dashboard
        </h1>
        <p class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
            {{ now()->format('l, d F Y') }} &mdash; Fixed Asset Management Overview
        </p>
    </div>
    <div class="flex items-center gap-2 mt-2 sm:mt-0">
        <flux:button href="{{ route('assets.create') }}" wire:navigate variant="primary" size="sm">
            <flux:icon.plus class="size-4" />
            New Asset
        </flux:button>
        <flux:button href="{{ route('asset-reminders.index') }}" wire:navigate variant="ghost" size="sm">
            <flux:icon.bell-alert class="size-4" />
            Reminders
            @if($reminderStats['expired'] > 0)
                <span class="ml-1 inline-flex items-center justify-center rounded-full bg-red-500 px-1.5 py-0.5 text-xs font-bold text-white leading-none">
                    {{ $reminderStats['expired'] }}
                </span>
            @endif
        </flux:button>
    </div>
</div>

{{-- ── Row 1: KPI Cards ──────────────────────────────────────────────────────── --}}
<div class="grid gap-4 grid-cols-2 sm:grid-cols-3 lg:grid-cols-6">

    {{-- Total Assets --}}
    <a href="{{ route('assets.index') }}" wire:navigate
       class="col-span-2 sm:col-span-1 group relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
        <div class="flex items-start justify-between">
            <div class="rounded-xl bg-accent/10 p-2.5">
                <flux:icon.clipboard-document-list class="size-5 text-accent" />
            </div>
            <flux:icon.arrow-right class="size-4 text-zinc-400 opacity-0 transition group-hover:opacity-100" />
        </div>
        <div class="mt-4 text-3xl font-extrabold text-zinc-900 dark:text-zinc-100">
            {{ number_format($assetStats['total']) }}
        </div>
        <div class="mt-0.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">Total Assets</div>
        @if($totalAssetValue > 0)
            <div class="mt-2 text-xs text-zinc-400">
                ₹{{ number_format($totalAssetValue / 100000, 1) }}L total value
            </div>
        @endif
    </a>

    {{-- Active --}}
    <a href="{{ route('assets.index', ['status' => 'active']) }}" wire:navigate
       class="group relative overflow-hidden rounded-2xl border border-green-200 bg-green-50 p-5 transition hover:border-green-300 hover:shadow-sm dark:border-green-900/40 dark:bg-green-950/20 dark:hover:border-green-800/60">
        <div class="flex items-start justify-between">
            <div class="rounded-xl bg-green-100 p-2.5 dark:bg-green-900/40">
                <flux:icon.check-circle class="size-5 text-green-600 dark:text-green-400" />
            </div>
            <flux:icon.arrow-right class="size-4 text-green-400 opacity-0 transition group-hover:opacity-100" />
        </div>
        <div class="mt-4 text-3xl font-extrabold text-green-700 dark:text-green-400">
            {{ number_format($assetStats['active']) }}
        </div>
        <div class="mt-0.5 text-xs font-medium text-green-600 dark:text-green-500">Active</div>
        @if($assetStats['total'] > 0)
            <div class="mt-2 text-xs text-green-500/70">
                {{ round($assetStats['active'] / $assetStats['total'] * 100) }}% of fleet
            </div>
        @endif
    </a>

    {{-- Under Repair --}}
    <a href="{{ route('assets.index', ['status' => 'under_repair']) }}" wire:navigate
       class="group relative overflow-hidden rounded-2xl border border-yellow-200 bg-yellow-50 p-5 transition hover:border-yellow-300 hover:shadow-sm dark:border-yellow-900/40 dark:bg-yellow-950/20 dark:hover:border-yellow-800/60">
        <div class="flex items-start justify-between">
            <div class="rounded-xl bg-yellow-100 p-2.5 dark:bg-yellow-900/40">
                <flux:icon.wrench-screwdriver class="size-5 text-yellow-600 dark:text-yellow-400" />
            </div>
            <flux:icon.arrow-right class="size-4 text-yellow-400 opacity-0 transition group-hover:opacity-100" />
        </div>
        <div class="mt-4 text-3xl font-extrabold text-yellow-700 dark:text-yellow-400">
            {{ number_format($assetStats['under_repair']) }}
        </div>
        <div class="mt-0.5 text-xs font-medium text-yellow-600 dark:text-yellow-500">Under Repair</div>
    </a>

    {{-- Inactive --}}
    <a href="{{ route('assets.index', ['status' => 'inactive']) }}" wire:navigate
       class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
        <div class="flex items-start justify-between">
            <div class="rounded-xl bg-zinc-100 p-2.5 dark:bg-zinc-800">
                <flux:icon.pause-circle class="size-5 text-zinc-500" />
            </div>
            <flux:icon.arrow-right class="size-4 text-zinc-400 opacity-0 transition group-hover:opacity-100" />
        </div>
        <div class="mt-4 text-3xl font-extrabold text-zinc-600 dark:text-zinc-400">
            {{ number_format($assetStats['inactive']) }}
        </div>
        <div class="mt-0.5 text-xs font-medium text-zinc-500 dark:text-zinc-400">Inactive</div>
    </a>

    {{-- Disposed --}}
    <a href="{{ route('assets.index', ['status' => 'disposed']) }}" wire:navigate
       class="group relative overflow-hidden rounded-2xl border border-zinc-200 bg-zinc-50 p-5 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700">
        <div class="flex items-start justify-between">
            <div class="rounded-xl bg-zinc-100 p-2.5 dark:bg-zinc-800">
                <flux:icon.archive-box-x-mark class="size-5 text-zinc-400" />
            </div>
            <flux:icon.arrow-right class="size-4 text-zinc-400 opacity-0 transition group-hover:opacity-100" />
        </div>
        <div class="mt-4 text-3xl font-extrabold text-zinc-500 dark:text-zinc-500">
            {{ number_format($assetStats['disposed']) }}
        </div>
        <div class="mt-0.5 text-xs font-medium text-zinc-400">Disposed</div>
    </a>

    {{-- Complaints Open --}}
    <a href="{{ route('complaints.index') }}" wire:navigate
       class="group relative overflow-hidden rounded-2xl border border-rose-200 bg-rose-50 p-5 transition hover:border-rose-300 hover:shadow-sm dark:border-rose-900/40 dark:bg-rose-950/20 dark:hover:border-rose-800/60">
        <div class="flex items-start justify-between">
            <div class="rounded-xl bg-rose-100 p-2.5 dark:bg-rose-900/40">
                <flux:icon.chat-bubble-left-ellipsis class="size-5 text-rose-600 dark:text-rose-400" />
            </div>
            <flux:icon.arrow-right class="size-4 text-rose-400 opacity-0 transition group-hover:opacity-100" />
        </div>
        <div class="mt-4 text-3xl font-extrabold text-rose-700 dark:text-rose-400">
            {{ number_format($complaints['open']) }}
        </div>
        <div class="mt-0.5 text-xs font-medium text-rose-600 dark:text-rose-500">Open Complaints</div>
        @if($complaints['total'] > 0)
            <div class="mt-2 text-xs text-rose-500/70">{{ $complaints['resolved'] }} resolved</div>
        @endif
    </a>

</div>

{{-- ── Row 2: Alert Banner + Expiry Summary ────────────────────────────────── --}}
<div class="mt-5 grid gap-4 sm:grid-cols-3">

    <a href="{{ route('asset-reminders.index', ['filter' => 'expired']) }}"
       class="group flex items-center gap-4 rounded-2xl border border-red-200 bg-red-50 p-5 transition hover:border-red-300 hover:shadow-sm dark:border-red-900/40 dark:bg-red-950/20">
        <div class="shrink-0 rounded-2xl bg-red-100 p-3 dark:bg-red-900/40">
            <flux:icon.exclamation-triangle class="size-6 text-red-600 dark:text-red-400" />
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-2xl font-extrabold text-red-600 dark:text-red-400">{{ number_format($reminderStats['expired']) }}</div>
            <div class="text-sm font-semibold text-red-700 dark:text-red-300">Already Expired</div>
            <div class="text-xs text-red-500 mt-0.5">Immediate attention required</div>
        </div>
    </a>

    <a href="{{ route('asset-reminders.index', ['filter' => 'upcoming']) }}"
       class="group flex items-center gap-4 rounded-2xl border border-orange-200 bg-orange-50 p-5 transition hover:border-orange-300 hover:shadow-sm dark:border-orange-900/40 dark:bg-orange-950/20">
        <div class="shrink-0 rounded-2xl bg-orange-100 p-3 dark:bg-orange-900/40">
            <flux:icon.clock class="size-6 text-orange-600 dark:text-orange-400" />
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-2xl font-extrabold text-orange-600 dark:text-orange-400">{{ number_format($reminderStats['expiring_7']) }}</div>
            <div class="text-sm font-semibold text-orange-700 dark:text-orange-300">Expiring in 7 Days</div>
            <div class="text-xs text-orange-500 mt-0.5">Action needed this week</div>
        </div>
    </a>

    <a href="{{ route('asset-reminders.index', ['filter' => 'upcoming']) }}"
       class="group flex items-center gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 transition hover:border-amber-300 hover:shadow-sm dark:border-amber-900/40 dark:bg-amber-950/20">
        <div class="shrink-0 rounded-2xl bg-amber-100 p-3 dark:bg-amber-900/40">
            <flux:icon.calendar-days class="size-6 text-amber-600 dark:text-amber-400" />
        </div>
        <div class="min-w-0 flex-1">
            <div class="text-2xl font-extrabold text-amber-600 dark:text-amber-400">{{ number_format($reminderStats['expiring_30']) }}</div>
            <div class="text-sm font-semibold text-amber-700 dark:text-amber-300">Expiring in 30 Days</div>
            <div class="text-xs text-amber-500 mt-0.5">Plan renewals this month</div>
        </div>
    </a>

</div>

{{-- ── Row 3: Charts ─────────────────────────────────────────────────────────── --}}
<div class="mt-5 grid gap-5 lg:grid-cols-3">

    {{-- Asset by Category – Donut --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-zinc-800 dark:text-zinc-100">Assets by Category</h3>
                <p class="text-xs text-zinc-400 mt-0.5">Distribution across categories</p>
            </div>
            <flux:icon.chart-pie class="size-5 text-zinc-400" />
        </div>
        @if($assetsByCategory->isEmpty())
            <div class="flex h-48 items-center justify-center text-sm text-zinc-400">No data yet</div>
        @else
            <div id="chart-category" class="h-52"></div>
        @endif
    </div>

    {{-- Assets Added per Month – Bar --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-zinc-800 dark:text-zinc-100">Assets Added</h3>
                <p class="text-xs text-zinc-400 mt-0.5">Last 6 months</p>
            </div>
            <flux:icon.chart-bar class="size-5 text-zinc-400" />
        </div>
        @if($assetsByMonth->isEmpty())
            <div class="flex h-48 items-center justify-center text-sm text-zinc-400">No data yet</div>
        @else
            <div id="chart-monthly" class="h-52"></div>
        @endif
    </div>

    {{-- Service Cost – Area --}}
    <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="mb-4 flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-zinc-800 dark:text-zinc-100">Service Cost</h3>
                <p class="text-xs text-zinc-400 mt-0.5">Last 6 months (₹)</p>
            </div>
            <flux:icon.currency-rupee class="size-5 text-zinc-400" />
        </div>
        @if($serviceCostByMonth->isEmpty())
            <div class="flex h-48 items-center justify-center text-sm text-zinc-400">No service records yet</div>
        @else
            <div id="chart-cost" class="h-52"></div>
        @endif
    </div>

</div>

{{-- ── Row 4: Expiry Breakdown Cards ───────────────────────────────────────── --}}
<div class="mt-5">
    <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Coverage & Compliance Status</h2>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">

        @php
            $coverageCards = [
                ['label' => 'Warranty',          'icon' => 'shield-check',       'accent' => 'text-violet-500',  'bg' => 'bg-violet-50 dark:bg-violet-950/20',  'border' => 'border-violet-200 dark:border-violet-900/40', 'data' => $warranty,    'href' => route('reports.warranty-expiry')],
                ['label' => 'Extended Warranty',  'icon' => 'shield-exclamation', 'accent' => 'text-indigo-500',  'bg' => 'bg-indigo-50 dark:bg-indigo-950/20',  'border' => 'border-indigo-200 dark:border-indigo-900/40', 'data' => $extWarranty, 'href' => route('reports.extended-warranty-expiry')],
                ['label' => 'AMC Contract',       'icon' => 'document-text',      'accent' => 'text-blue-500',    'bg' => 'bg-blue-50 dark:bg-blue-950/20',      'border' => 'border-blue-200 dark:border-blue-900/40',     'data' => $amc,         'href' => route('reports.amc-expiry')],
                ['label' => 'Insurance',          'icon' => 'banknotes',          'accent' => 'text-teal-500',    'bg' => 'bg-teal-50 dark:bg-teal-950/20',      'border' => 'border-teal-200 dark:border-teal-900/40',     'data' => $insurance,   'href' => route('reports.insurance-expiry')],
            ];
        @endphp

        @foreach($coverageCards as $c)
            <a href="{{ $c['href'] }}" wire:navigate
               class="rounded-2xl border {{ $c['border'] }} {{ $c['bg'] }} p-5 transition hover:shadow-sm">
                <div class="flex items-center gap-2.5 mb-4">
                    <flux:icon :icon="$c['icon']" class="size-5 {{ $c['accent'] }}" />
                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $c['label'] }}</span>
                </div>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-1.5 text-red-500">
                            <span class="inline-block size-1.5 rounded-full bg-red-500"></span>Expired
                        </span>
                        <span class="font-bold tabular-nums text-red-500">{{ $c['data']['expired'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-1.5 text-orange-500">
                            <span class="inline-block size-1.5 rounded-full bg-orange-500"></span>Within 7 days
                        </span>
                        <span class="font-bold tabular-nums text-orange-500">{{ $c['data']['in7'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-1.5 text-amber-500">
                            <span class="inline-block size-1.5 rounded-full bg-amber-400"></span>Within 30 days
                        </span>
                        <span class="font-bold tabular-nums text-amber-500">{{ $c['data']['in30'] }}</span>
                    </div>
                </div>
            </a>
        @endforeach

    </div>
</div>

{{-- ── Row 4b: Vehicle Compliance (only when data exists) ─────────────────── --}}
@php $hasVehicle = $puc['expired'] + $puc['in7'] + $puc['in30'] + $fitness['expired'] + $fitness['in7'] + $fitness['in30'] + $roadTax['expired'] + $roadTax['in7'] + $roadTax['in30'] > 0; @endphp
@if($hasVehicle)
<div class="mt-5">
    <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Vehicle Compliance</h2>
    <div class="grid gap-4 sm:grid-cols-3">
        @php
            $vehicleCards = [
                ['label' => 'PUC Certificate',   'icon' => 'cloud',        'accent' => 'text-sky-500',   'bg' => 'bg-sky-50 dark:bg-sky-950/20',   'border' => 'border-sky-200 dark:border-sky-900/40',   'data' => $puc,     'href' => route('reports.puc-expiry')],
                ['label' => 'Fitness Certificate','icon' => 'check-badge',  'accent' => 'text-green-500', 'bg' => 'bg-green-50 dark:bg-green-950/20','border' => 'border-green-200 dark:border-green-900/40','data' => $fitness, 'href' => route('reports.fitness-expiry')],
                ['label' => 'Road Tax',           'icon' => 'currency-rupee','accent'=> 'text-amber-500', 'bg' => 'bg-amber-50 dark:bg-amber-950/20','border' => 'border-amber-200 dark:border-amber-900/40','data' => $roadTax, 'href' => route('reports.road-tax-expiry')],
            ];
        @endphp
        @foreach($vehicleCards as $c)
            <a href="{{ $c['href'] }}" wire:navigate
               class="rounded-2xl border {{ $c['border'] }} {{ $c['bg'] }} p-5 transition hover:shadow-sm">
                <div class="flex items-center gap-2.5 mb-4">
                    <flux:icon :icon="$c['icon']" class="size-5 {{ $c['accent'] }}" />
                    <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $c['label'] }}</span>
                </div>
                <div class="space-y-2.5">
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-1.5 text-red-500"><span class="inline-block size-1.5 rounded-full bg-red-500"></span>Expired</span>
                        <span class="font-bold tabular-nums text-red-500">{{ $c['data']['expired'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-1.5 text-orange-500"><span class="inline-block size-1.5 rounded-full bg-orange-500"></span>Within 7 days</span>
                        <span class="font-bold tabular-nums text-orange-500">{{ $c['data']['in7'] }}</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-1.5 text-amber-500"><span class="inline-block size-1.5 rounded-full bg-amber-400"></span>Within 30 days</span>
                        <span class="font-bold tabular-nums text-amber-500">{{ $c['data']['in30'] }}</span>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- ── Row 5: Tables ─────────────────────────────────────────────────────────── --}}
<div class="mt-5 grid gap-5 lg:grid-cols-5">

    {{-- Upcoming Expiries – wider --}}
    <div class="lg:col-span-3 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="font-semibold text-zinc-800 dark:text-zinc-100">Upcoming Expiries</h3>
                <p class="text-xs text-zinc-400 mt-0.5">Next 30 days across all coverage types</p>
            </div>
            <flux:button href="{{ route('asset-reminders.index') }}" wire:navigate variant="ghost" size="sm">
                View all
            </flux:button>
        </div>

        @if($upcomingExpiries->isEmpty())
            <div class="flex flex-col items-center justify-center py-10 gap-2 text-center">
                <flux:icon.check-circle class="size-10 text-green-500" />
                <p class="text-sm font-medium text-zinc-500">All clear for the next 30 days</p>
            </div>
        @else
            <div class="overflow-x-auto -mx-1">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-100 dark:border-zinc-800">
                            <th class="pb-2.5 pl-1 text-left text-xs font-medium text-zinc-400 uppercase tracking-wide">Asset</th>
                            <th class="pb-2.5 text-left text-xs font-medium text-zinc-400 uppercase tracking-wide">Type</th>
                            <th class="pb-2.5 pr-1 text-right text-xs font-medium text-zinc-400 uppercase tracking-wide">Expires</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        @foreach($upcomingExpiries as $item)
                            @php
                                $daysLeft = now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($item['expiry_date'])->startOfDay(), false);
                                $urgency  = $daysLeft <= 0 ? 'red' : ($daysLeft <= 7 ? 'orange' : 'amber');
                                $dotColor = ['red' => 'bg-red-500', 'orange' => 'bg-orange-500', 'amber' => 'bg-amber-400'][$urgency];
                                $textColor= ['red' => 'text-red-500', 'orange' => 'text-orange-500', 'amber' => 'text-amber-500'][$urgency];
                                $typeBadge = [
                                    'Warranty'     => 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400',
                                    'Ext. Warranty'=> 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
                                    'AMC'          => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                    'Insurance'    => 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400',
                                ][$item['type']] ?? 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400';
                            @endphp
                            <tr class="group">
                                <td class="py-2.5 pl-1 pr-3">
                                    @if($item['asset_id'])
                                        <a href="{{ route('assets.show', [$item['asset_id'], 'tab' => $item['tab']]) }}"
                                           class="font-semibold text-zinc-800 hover:text-accent transition dark:text-zinc-200">
                                            {{ $item['asset_code'] }}
                                        </a>
                                        <div class="text-xs text-zinc-400 truncate max-w-40">{{ $item['asset_name'] }}</div>
                                    @else
                                        <span class="text-zinc-400">—</span>
                                    @endif
                                </td>
                                <td class="py-2.5 pr-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $typeBadge }}">
                                        {{ $item['type'] }}
                                    </span>
                                </td>
                                <td class="py-2.5 pr-1 text-right">
                                    <span class="font-semibold {{ $textColor }} tabular-nums">
                                        {{ \Carbon\Carbon::parse($item['expiry_date'])->format('d M Y') }}
                                    </span>
                                    <div class="flex items-center justify-end gap-1 mt-0.5">
                                        <span class="inline-block size-1.5 rounded-full {{ $dotColor }}"></span>
                                        <span class="text-xs {{ $textColor }}">
                                            {{ $daysLeft <= 0 ? 'Expired' : $daysLeft.'d left' }}
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Right column: Recent Assets + Overdue Schedules --}}
    <div class="lg:col-span-2 flex flex-col gap-5">

        {{-- Recently Added Assets --}}
        <div class="rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-100">Recently Added</h3>
                    <p class="text-xs text-zinc-400 mt-0.5">Latest assets registered</p>
                </div>
                <flux:button href="{{ route('assets.create') }}" wire:navigate variant="ghost" size="sm">
                    <flux:icon.plus class="size-3.5" />
                    Add
                </flux:button>
            </div>

            @if($recentAssets->isEmpty())
                <div class="py-6 text-center text-sm text-zinc-400">No assets yet.</div>
            @else
                <div class="space-y-2.5">
                    @foreach($recentAssets as $asset)
                        @php
                            $statusDot = match($asset->status) {
                                'active'       => 'bg-green-500',
                                'under_repair' => 'bg-yellow-400',
                                'inactive'     => 'bg-zinc-400',
                                default        => 'bg-zinc-300',
                            };
                        @endphp
                        <a href="{{ route('assets.show', $asset) }}" wire:navigate
                           class="flex items-center gap-3 rounded-xl p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/50 transition">
                            <span class="mt-1 inline-block size-2 shrink-0 rounded-full {{ $statusDot }}"></span>
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-semibold text-zinc-800 dark:text-zinc-100 truncate">{{ $asset->asset_code }}</div>
                                <div class="text-xs text-zinc-400 truncate">{{ $asset->asset_name }}</div>
                            </div>
                            <div class="text-xs text-zinc-400 shrink-0">{{ $asset->category?->name }}</div>
                        </a>
                    @endforeach
                </div>
                @if($assetStats['total'] > 8)
                    <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-800 text-center">
                        <flux:button href="{{ route('assets.index') }}" wire:navigate variant="ghost" size="sm">
                            View all {{ number_format($assetStats['total']) }} assets →
                        </flux:button>
                    </div>
                @endif
            @endif
        </div>

        {{-- Overdue Maintenance Schedules --}}
        @if($overdueSchedules->count() > 0)
        <div class="rounded-2xl border border-red-200 bg-red-50 p-5 dark:border-red-900/40 dark:bg-red-950/20">
            <div class="flex items-center gap-2 mb-4">
                <flux:icon.exclamation-circle class="size-5 text-red-500" />
                <h3 class="font-semibold text-red-800 dark:text-red-300">Overdue Maintenance</h3>
            </div>
            <div class="space-y-2.5">
                @foreach($overdueSchedules as $s)
                    <a href="{{ route('assets.show', [$s->asset_id, 'tab' => 'schedules']) }}" wire:navigate
                       class="flex items-start gap-3 rounded-xl bg-white/60 p-2.5 hover:bg-white transition dark:bg-red-950/30 dark:hover:bg-red-950/50">
                        <flux:icon.wrench-screwdriver class="size-4 mt-0.5 shrink-0 text-red-500" />
                        <div class="min-w-0 flex-1">
                            <div class="text-sm font-semibold text-red-800 dark:text-red-300 truncate">{{ $s->asset_code }}</div>
                            <div class="text-xs text-red-500/80 truncate">{{ $s->schedule_name }}</div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="text-xs font-semibold text-red-600">
                                {{ \Carbon\Carbon::parse($s->next_due_date)->format('d M') }}
                            </div>
                            <div class="text-xs text-red-400">
                                {{ now()->diffInDays($s->next_due_date) }}d overdue
                            </div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
        @endif

    </div>
</div>

{{-- ── Row 6: Quick Actions ─────────────────────────────────────────────────── --}}
<div class="mt-5 rounded-2xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
    <h3 class="mb-3 text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Quick Actions</h3>
    <div class="flex flex-wrap gap-2">
        <flux:button href="{{ route('assets.create') }}" wire:navigate variant="ghost" size="sm">
            <flux:icon.plus class="size-4" /> New Asset
        </flux:button>
        <flux:button href="{{ route('assets.index') }}" wire:navigate variant="ghost" size="sm">
            <flux:icon.clipboard-document-list class="size-4" /> Asset Register
        </flux:button>
        <flux:button href="{{ route('asset-reminders.index') }}" wire:navigate variant="ghost" size="sm">
            <flux:icon.bell-alert class="size-4" /> All Reminders
        </flux:button>
        <flux:button href="{{ route('complaints.index') }}" wire:navigate variant="ghost" size="sm">
            <flux:icon.chat-bubble-left-ellipsis class="size-4" /> Complaints
        </flux:button>
        <flux:button href="{{ route('reports.asset-register') }}" wire:navigate variant="ghost" size="sm">
            <flux:icon.document-chart-bar class="size-4" /> Reports
        </flux:button>
        <flux:button href="{{ route('asset-categories.index') }}" wire:navigate variant="ghost" size="sm">
            <flux:icon.tag class="size-4" /> Categories
        </flux:button>
    </div>
</div>

{{-- ── ApexCharts Init ──────────────────────────────────────────────────────── --}}
<script>
(function () {
    const CHART_IDS = ['chart-category', 'chart-monthly', 'chart-cost'];

    function destroyExisting() {
        CHART_IDS.forEach(id => {
            const el = document.querySelector('#' + id);
            if (el && el._apexChart) {
                el._apexChart.destroy();
                el._apexChart = null;
            }
        });
    }

    function makeChart(el, options) {
        const chart = new ApexCharts(el, options);
        chart.render();
        el._apexChart = chart;
    }

    function initDashboardCharts() {
        if (!document.querySelector('#chart-category, #chart-monthly, #chart-cost')) return;

        destroyExisting();

        const isDark     = document.documentElement.classList.contains('dark');
        const textColor  = isDark ? '#a1a1aa' : '#71717a';
        const gridColor  = isDark ? '#27272a' : '#f4f4f5';

        const base = {
            chart:   { background: 'transparent', toolbar: { show: false }, fontFamily: 'inherit' },
            grid:    { borderColor: gridColor, strokeDashArray: 4, padding: { left: 0, right: 0, top: 0, bottom: 0 } },
            tooltip: { theme: isDark ? 'dark' : 'light' },
        };

        // Donut: assets by category
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

        // Bar: assets added per month
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

        // Area: service cost per month
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
    }

    document.addEventListener('DOMContentLoaded', initDashboardCharts);
    document.addEventListener('livewire:navigated', initDashboardCharts);
})();
</script>

</x-layouts::app>
