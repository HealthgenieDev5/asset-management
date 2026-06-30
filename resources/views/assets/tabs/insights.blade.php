@php
    $today = now()->startOfDay();
    $items = collect();

    $stateMeta = [
        'overdue' => [
            'label' => 'Overdue',
            'class' => 'bg-red-400/10 text-red-400 border-red-400/30',
            'rank' => 0,
        ],
        'due_soon' => [
            'label' => 'Due Soon',
            'class' => 'bg-yellow-400/10 text-yellow-500 border-yellow-400/30',
            'rank' => 1,
        ],
        'upcoming' => [
            'label' => 'Upcoming',
            'class' => 'bg-blue-400/10 text-blue-400 border-blue-400/30',
            'rank' => 2,
        ],
        'active' => [
            'label' => 'Active',
            'class' => 'bg-green-400/10 text-green-400 border-green-400/30',
            'rank' => 3,
        ],
        'unknown' => [
            'label' => 'No Reading',
            'class' => 'bg-zinc-400/10 text-zinc-500 border-zinc-400/30',
            'rank' => 4,
        ],
    ];

    $formatDays = function (int $days): string {
        if ($days === 0) {
            return 'Today';
        }

        if ($days < 0) {
            return abs($days) . 'd overdue';
        }

        return $days . 'd left';
    };

    $pushDateItem = function (
        string $type,
        string $name,
        $date,
        ?int $threshold,
        string $tab,
        string $icon,
        ?string $meta = null,
        ?string $actionLabel = null
    ) use (&$items, $today, $stateMeta, $formatDays): void {
        if (! $date) {
            return;
        }

        $target = $date->copy()->startOfDay();
        $days = (int) $today->diffInDays($target, false);
        $threshold = $threshold ?: 30;

        if ($days < 0) {
            $state = 'overdue';
        } elseif ($days <= $threshold) {
            $state = 'due_soon';
        } else {
            $state = 'upcoming';
        }

        $items->push([
            'type' => $type,
            'name' => $name,
            'meta' => $meta,
            'icon' => $icon,
            'tab' => $tab,
            'action_label' => $actionLabel ?: 'Open',
            'date' => $target,
            'date_label' => $target->format('d M Y'),
            'days' => $days,
            'value_label' => $formatDays($days),
            'state' => $state,
            'state_label' => $stateMeta[$state]['label'],
            'state_class' => $stateMeta[$state]['class'],
            'rank' => $stateMeta[$state]['rank'],
            'sort_key' => $target->timestamp,
        ]);
    };

    $pushUnitItem = function (
        string $type,
        string $name,
        ?int $remaining,
        string $unit,
        ?int $threshold,
        string $tab,
        string $icon,
        ?string $meta = null,
        ?string $actionLabel = null
    ) use (&$items, $stateMeta): void {
        $threshold = $threshold ?? 0;

        if ($remaining === null) {
            $state = 'unknown';
            $valueLabel = 'No reading logged';
            $sortKey = now()->addYears(10)->timestamp;
        } elseif ($remaining <= 0) {
            $state = 'overdue';
            $valueLabel = abs($remaining) . ' ' . $unit . ' overdue';
            $sortKey = now()->timestamp;
        } elseif ($remaining <= $threshold) {
            $state = 'due_soon';
            $valueLabel = number_format($remaining) . ' ' . $unit . ' left';
            $sortKey = now()->addDays(1)->timestamp;
        } else {
            $state = 'upcoming';
            $valueLabel = number_format($remaining) . ' ' . $unit . ' left';
            $sortKey = now()->addDays(90)->timestamp;
        }

        $items->push([
            'type' => $type,
            'name' => $name,
            'meta' => $meta,
            'icon' => $icon,
            'tab' => $tab,
            'action_label' => $actionLabel ?: 'Open',
            'date' => null,
            'date_label' => null,
            'days' => null,
            'value_label' => $valueLabel,
            'state' => $state,
            'state_label' => $stateMeta[$state]['label'],
            'state_class' => $stateMeta[$state]['class'],
            'rank' => $stateMeta[$state]['rank'],
            'sort_key' => $sortKey,
        ]);
    };

    if ($asset->warranty_lapse_date) {
        $pushDateItem(
            'Warranty',
            'Asset Warranty',
            $asset->warranty_lapse_date,
            $asset->warranty_reminder_before_days,
            'overview',
            'shield-check',
            $asset->warranty_details ?: 'Asset record',
            'View Overview'
        );
    }

    foreach ($asset->warranties as $warranty) {
        if ($warranty->isDisposed()) {
            continue;
        }

        $isPartScope = $warranty->scope === 'part';
        $type = $isPartScope ? 'Part Warranty' : $warranty->warrantyTypeLabel() . ' Warranty';
        $name = $isPartScope
            ? ($warranty->part_name ?: 'Unnamed Part')
            : ($warranty->vendor ?: ($warranty->details ?: $type));
        $meta = $isPartScope
            ? ($warranty->part_serial_number ?: $warranty->vendor)
            : ($warranty->bill_no ? 'Bill #' . $warranty->bill_no : null);

        if ($warranty->isTimeBased()) {
            $pushDateItem(
                $type,
                $name,
                $warranty->expiry_date,
                $warranty->reminder_before_days,
                'warranty',
                $isPartScope ? 'puzzle-piece' : 'shield-exclamation',
                $meta,
                'View Warranty'
            );
        } else {
            $pushUnitItem(
                $type,
                $name,
                $warranty->remainingUnits(),
                $warranty->unitLabel(),
                $warranty->reminder_before_units,
                'warranty',
                $isPartScope ? 'puzzle-piece' : 'shield-exclamation',
                $meta,
                'View Warranty'
            );
        }
    }

    foreach ($asset->amcContracts as $amc) {
        $pushDateItem(
            'AMC',
            $amc->vendor?->name ?? $amc->vendor_name ?: ($amc->contract_number ?: 'AMC Contract'),
            $amc->amc_date_to,
            $amc->reminder_before_days,
            'amc',
            'wrench-screwdriver',
            $amc->contract_number ? 'Contract #' . $amc->contract_number : null,
            'View AMC'
        );
    }

    foreach ($asset->insurancePolicies as $policy) {
        $pushDateItem(
            'Insurance',
            $policy->insurer_name ?: ($policy->policy_number ?: 'Insurance Policy'),
            $policy->policy_date_to,
            $policy->reminder_before_days,
            'insurance',
            'building-library',
            $policy->policy_number ? 'Policy #' . $policy->policy_number : null,
            'View Policy'
        );
    }

    if ($asset->isVehicle()) {
        $pushDateItem('PUC', 'PUC Certificate', $asset->puc_expiry_date, $asset->puc_reminder_before_days, 'overview', 'document-text', $asset->registration_number, 'View Vehicle');
        $pushDateItem('Fitness', 'Fitness Certificate', $asset->fitness_expiry_date, $asset->fitness_reminder_before_days, 'overview', 'shield-check', $asset->registration_number, 'View Vehicle');
        $pushDateItem('Road Tax', 'Road Tax', $asset->road_tax_expiry_date, $asset->road_tax_reminder_before_days, 'overview', 'building-library', $asset->registration_number, 'View Vehicle');
    }

    foreach ($asset->services as $service) {
        $serviceName = $service->service_type_label ?: 'Service Record';
        $serviceMeta = $service->service_date ? 'Serviced ' . $service->service_date->format('d M Y') : $service->service_agency;

        $pushDateItem(
            'Service Due',
            $serviceName,
            $service->next_service_date,
            $service->next_service_reminder_before_days,
            'services',
            'cog-6-tooth',
            $serviceMeta,
            'View Service'
        );

        $pushDateItem(
            'Certification',
            $serviceName . ' Certification',
            $service->certification_expiry,
            $service->certification_reminder_before_days,
            'services',
            'shield-check',
            $serviceMeta,
            'View Service'
        );
    }

    foreach ($asset->services->flatMap->parts as $part) {
        $partMeta = $part->purchased_from ?: ($part->part_serial_number ? 'Serial ' . $part->part_serial_number : null);

        if ($part->isWarrantyTimeBased()) {
            $pushDateItem(
                'Part Warranty',
                $part->part_name ?: 'Replacement Part',
                $part->warranty_till,
                $part->warranty_reminder_before_days,
                'parts',
                'puzzle-piece',
                $partMeta,
                'View Part'
            );
        } elseif ($part->warranty_unit && $part->warranty_counter_limit) {
            $currentReading = $asset->latestMeterReading($part->warranty_unit);
            $remaining = $currentReading !== null ? max(0, (int) $part->warranty_counter_limit - $currentReading) : null;

            $pushUnitItem(
                'Part Warranty',
                $part->part_name ?: 'Replacement Part',
                $remaining,
                $part->warranty_unit,
                $part->warranty_reminder_before_units,
                'parts',
                'puzzle-piece',
                $partMeta,
                'View Part'
            );
        }
    }

    foreach ($asset->maintenanceSchedules->where('is_active', true) as $schedule) {
        $scheduleMeta = $schedule->serviceTypeLabel();

        if ($schedule->schedule_type === 'date') {
            $pushDateItem(
                'Maintenance',
                $schedule->schedule_name,
                $schedule->next_due_date ?: $schedule->computeNextDueDate(),
                $schedule->reminder_thresholds ? max(array_column($schedule->reminder_thresholds, 'value')) : 30,
                'schedules',
                'calendar-days',
                $scheduleMeta,
                'View Schedule'
            );
        } elseif ($schedule->schedule_type === 'mileage') {
            $pushUnitItem(
                'Maintenance',
                $schedule->schedule_name,
                $schedule->remainingKm(),
                'km',
                $schedule->reminder_thresholds ? max(array_column($schedule->reminder_thresholds, 'value')) : 500,
                'schedules',
                'chart-bar',
                $scheduleMeta,
                'View Schedule'
            );
        } elseif ($schedule->schedule_type === 'operating_hours') {
            $pushUnitItem(
                'Maintenance',
                $schedule->schedule_name,
                $schedule->remainingHours(),
                'hrs',
                $schedule->reminder_thresholds ? max(array_column($schedule->reminder_thresholds, 'value')) : 50,
                'schedules',
                'clock',
                $scheduleMeta,
                'View Schedule'
            );
        }
    }

    $openComplaints = $asset->complaints
        ->whereNotIn('status', ['resolved', 'closed', 'rejected'])
        ->values();

    foreach ($openComplaints as $complaint) {
        $rank = match ($complaint->priority) {
            'critical' => 0,
            'high' => 1,
            default => 3,
        };
        $state = $rank === 0 ? 'overdue' : ($rank === 1 ? 'due_soon' : 'active');

        $items->push([
            'type' => 'Complaint',
            'name' => $complaint->title ?: 'Open Complaint',
            'meta' => trim($complaint->priority_label . ' priority - ' . $complaint->status_label),
            'icon' => 'exclamation-triangle',
            'tab' => 'complaints',
            'action_label' => 'View Complaint',
            'date' => null,
            'date_label' => null,
            'days' => null,
            'value_label' => $complaint->status_label,
            'state' => $state,
            'state_label' => $rank === 0 ? 'Critical' : ($rank === 1 ? 'High Priority' : 'Open'),
            'state_class' => $stateMeta[$state]['class'],
            'rank' => $rank,
            'sort_key' => $complaint->created_at?->timestamp ?? now()->timestamp,
        ]);
    }

    $sortedItems = $items->sortBy([['rank', 'asc'], ['sort_key', 'asc']])->values();
    $attentionItems = $sortedItems
        ->filter(fn($item) => in_array($item['state'], ['overdue', 'due_soon']))
        ->values();
    $timelineItems = $items
        ->filter(fn($item) => $item['date'] && $item['days'] !== null && $item['days'] >= 0)
        ->sortBy('sort_key')
        ->take(12)
        ->values();

    $overdueCount = $items->where('state', 'overdue')->count();
    $dueSoonCount = $items->where('state', 'due_soon')->count();
    $upcomingCount = $items->where('state', 'upcoming')->count();
    $activeReminderCount = $asset->smartReminders->where('is_active', true)->count();
    $lastService = $asset->services->sortByDesc('service_date')->first();
    $totalMaintenanceCost = $asset->services->sum(fn($service) => $service->grandTotalCost());
    $activeWarrantyCount = $asset->warranties->filter(fn($warranty) => $warranty->isActive())->count();
    $activeAmcCount = $asset->amcContracts->filter(fn($amc) => ! $amc->isExpired())->count();
    $activeInsuranceCount = $asset->insurancePolicies->filter(fn($policy) => ! $policy->isExpired())->count();
    $latestMeterLog = $asset->meterLogs->sortByDesc('logged_at')->first();
    $coverageCount = $activeWarrantyCount + $activeAmcCount + $activeInsuranceCount;

    $trackedNonComplaintCount = max(1, $items->filter(fn($item) => $item['type'] !== 'Complaint')->count());
    $criticalComplaintCount = $openComplaints->where('priority', 'critical')->count();
    $highComplaintCount = $openComplaints->where('priority', 'high')->count();
    $otherComplaintCount = max(0, $openComplaints->count() - $criticalComplaintCount - $highComplaintCount);


@endphp

<div class="space-y-5">

    {{-- ── Stat Strip ── --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6">

        {{-- Overdue --}}
        <div class="relative overflow-hidden rounded-2xl border border-red-500/20 bg-red-500/5 p-4 dark:border-red-500/20 dark:bg-red-500/5">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-red-400/80">Overdue</p>
                    <p class="mt-1.5 text-3xl font-bold text-red-400">{{ $overdueCount }}</p>
                </div>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-red-400/15">
                    <flux:icon.exclamation-triangle class="size-4 text-red-400" />
                </span>
            </div>
        </div>

        {{-- Due Soon --}}
        <div class="relative overflow-hidden rounded-2xl border border-yellow-500/20 bg-yellow-500/5 p-4 dark:border-yellow-500/20 dark:bg-yellow-500/5">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-yellow-500/80">Due Soon</p>
                    <p class="mt-1.5 text-3xl font-bold text-yellow-500">{{ $dueSoonCount }}</p>
                </div>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-yellow-400/15">
                    <flux:icon.bell-alert class="size-4 text-yellow-500" />
                </span>
            </div>
        </div>

        {{-- Upcoming --}}
        <div class="relative overflow-hidden rounded-2xl border border-blue-500/20 bg-blue-500/5 p-4 dark:border-blue-500/20 dark:bg-blue-500/5">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-blue-400/80">Upcoming</p>
                    <p class="mt-1.5 text-3xl font-bold text-blue-400">{{ $upcomingCount }}</p>
                </div>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-blue-400/15">
                    <flux:icon.calendar-days class="size-4 text-blue-400" />
                </span>
            </div>
        </div>

        {{-- Complaints --}}
        <div class="relative overflow-hidden rounded-2xl border border-orange-500/20 bg-orange-500/5 p-4 dark:border-orange-500/20 dark:bg-orange-500/5">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="text-[11px] font-medium uppercase tracking-wide text-orange-400/80">Complaints</p>
                    <p class="mt-1.5 text-3xl font-bold text-orange-400">{{ $openComplaints->count() }}</p>
                </div>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-orange-400/15">
                    <flux:icon.chat-bubble-oval-left-ellipsis class="size-4 text-orange-400" />
                </span>
            </div>
        </div>

        {{-- Last Service --}}
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-400">Last Service</p>
                    <p class="mt-1.5 text-sm font-bold text-zinc-800 dark:text-zinc-100">
                        {{ $lastService?->service_date?->format('d M Y') ?: '—' }}
                    </p>
                    @if ($lastService?->condition_rating)
                        <p class="mt-0.5 text-[11px] font-medium {{ $lastService->condition_rating_color }}">{{ $lastService->condition_rating_label }}</p>
                    @endif
                </div>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-cyan-400/10">
                    <flux:icon.cog-6-tooth class="size-4 text-cyan-400" />
                </span>
            </div>
        </div>

        {{-- Maintenance Cost --}}
        <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-[11px] font-medium uppercase tracking-wide text-zinc-400">Maint. Cost</p>
                    <p class="mt-1.5 text-sm font-bold text-zinc-800 dark:text-zinc-100">
                        ₹{{ number_format($totalMaintenanceCost, 0) }}
                    </p>
                    <p class="mt-0.5 text-[11px] text-zinc-400">lifetime total</p>
                </div>
                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-green-400/10">
                    <flux:icon.banknotes class="size-4 text-green-400" />
                </span>
            </div>
        </div>
    </div>

    {{-- ── Main two-column grid ── --}}
    <div class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">

        {{-- LEFT: Needs Attention + Timeline stacked --}}
        <div class="space-y-5">

            {{-- Needs Attention --}}
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                <div class="flex items-center justify-between gap-3 px-5 py-4">
                    <div class="flex items-center gap-2.5">
                        <span class="flex size-7 items-center justify-center rounded-lg {{ $attentionItems->isEmpty() ? 'bg-green-400/10' : 'bg-red-400/10' }}">
                            <flux:icon.exclamation-circle class="size-4 {{ $attentionItems->isEmpty() ? 'text-green-400' : 'text-red-400' }}" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Needs Attention</p>
                            <p class="text-[11px] text-zinc-500">Overdue, due-soon &amp; critical alerts</p>
                        </div>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $attentionItems->isEmpty() ? 'bg-green-400/10 text-green-400' : 'bg-red-400/10 text-red-400' }}">
                        {{ $attentionItems->count() }} item{{ $attentionItems->count() !== 1 ? 's' : '' }}
                    </span>
                </div>

                @if ($attentionItems->isEmpty())
                    <div class="border-t border-zinc-100 px-5 py-8 text-center dark:border-zinc-800">
                        <div class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-green-400/10">
                            <flux:icon.check-circle class="size-6 text-green-400" />
                        </div>
                        <p class="mt-3 text-sm font-semibold text-zinc-800 dark:text-zinc-200">All clear</p>
                        <p class="mt-1 text-xs text-zinc-500">No overdue or urgent items right now.</p>
                    </div>
                @else
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                        @foreach ($attentionItems->take(10) as $item)
                            @php
                                $leftBorder = $item['state'] === 'overdue' ? 'border-l-2 border-l-red-400' : 'border-l-2 border-l-yellow-400';
                                $iconBg = $item['state'] === 'overdue' ? 'bg-red-400/10 text-red-400' : 'bg-yellow-400/10 text-yellow-500';
                            @endphp
                            <div class="flex items-center gap-3 py-3 pl-4 pr-5 {{ $leftBorder }}">
                                <span class="flex size-8 shrink-0 items-center justify-center rounded-xl {{ $iconBg }}">
                                    <flux:icon :icon="$item['icon']" class="size-4" />
                                </span>
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $item['name'] }}</p>
                                        <span class="rounded-md bg-zinc-100 px-1.5 py-0.5 text-[10px] font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">{{ $item['type'] }}</span>
                                    </div>
                                    @if ($item['meta'])
                                        <p class="truncate text-[11px] text-zinc-400">{{ $item['meta'] }}</p>
                                    @endif
                                </div>
                                <div class="shrink-0 text-right">
                                    <p class="text-xs font-bold {{ $item['state'] === 'overdue' ? 'text-red-400' : 'text-yellow-500' }}">{{ $item['value_label'] }}</p>
                                    @if ($item['date_label'])
                                        <p class="text-[11px] text-zinc-400">{{ $item['date_label'] }}</p>
                                    @endif
                                </div>
                                <button type="button"
                                        x-on:click="$dispatch('set-tab', '{{ $item['tab'] }}')"
                                        class="ml-1 shrink-0 rounded-lg border border-zinc-200 px-2.5 py-1.5 text-[11px] font-medium text-zinc-500 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-400">
                                    {{ $item['action_label'] }} →
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- Coverage Summary --}}
            <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
                <div class="px-5 py-4">
                    <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Coverage Summary</p>
                    <p class="text-[11px] text-zinc-500">Active protections on this asset</p>
                </div>
                <div class="grid grid-cols-3 divide-x divide-zinc-100 border-t border-zinc-100 dark:divide-zinc-800 dark:border-zinc-800">
                    <div class="px-5 py-4 text-center">
                        <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">{{ $activeWarrantyCount }}</p>
                        <p class="mt-0.5 text-[11px] font-medium text-zinc-400">Warranties</p>
                    </div>
                    <div class="px-5 py-4 text-center">
                        <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">{{ $activeAmcCount }}</p>
                        <p class="mt-0.5 text-[11px] font-medium text-zinc-400">AMC</p>
                    </div>
                    <div class="px-5 py-4 text-center">
                        <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">{{ $activeInsuranceCount }}</p>
                        <p class="mt-0.5 text-[11px] font-medium text-zinc-400">Insurance</p>
                    </div>
                </div>
                @php
                    $coveragePct = min(100, (int) round(($coverageCount / 3) * 100));
                    $coverageColor = $coveragePct >= 80 ? 'bg-green-400' : ($coveragePct >= 40 ? 'bg-yellow-400' : 'bg-red-400');
                @endphp
                <div class="border-t border-zinc-100 px-5 py-3 dark:border-zinc-800">
                    <div class="flex items-center justify-between gap-3 text-[11px] text-zinc-500">
                        <span>Coverage level</span>
                        <span class="font-semibold">{{ $coveragePct }}%</span>
                    </div>
                    <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-full rounded-full {{ $coverageColor }} transition-all" style="width:{{ $coveragePct }}%"></div>
                    </div>
                </div>
            </div>
        </div>

        {{-- RIGHT: Upcoming Timeline --}}
        <div class="overflow-hidden rounded-2xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3 border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
                <div class="flex items-center gap-2.5">
                    <span class="flex size-7 items-center justify-center rounded-lg bg-blue-400/10">
                        <flux:icon.calendar-days class="size-4 text-blue-400" />
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-zinc-800 dark:text-zinc-200">Upcoming Timeline</p>
                        <p class="text-[11px] text-zinc-500">Renewals, services &amp; compliance</p>
                    </div>
                </div>
                @if ($timelineItems->isNotEmpty())
                    <span class="rounded-full bg-blue-400/10 px-2.5 py-1 text-[11px] font-semibold text-blue-400">
                        {{ $timelineItems->count() }}
                    </span>
                @endif
            </div>

            @if ($timelineItems->isEmpty())
                <div class="px-5 py-12 text-center">
                    <div class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800">
                        <flux:icon.calendar-days class="size-7 text-zinc-400" />
                    </div>
                    <p class="mt-4 text-sm font-semibold text-zinc-700 dark:text-zinc-300">No upcoming items</p>
                    <p class="mt-1 text-xs text-zinc-400">Add warranty, AMC, insurance, or service records with due dates.</p>
                </div>
            @else
                <div class="divide-y divide-zinc-100 dark:divide-zinc-800/60">
                    @foreach ($timelineItems->take(10) as $item)
                        @php
                            $urgency = $item['days'] <= 7 ? 'critical' : ($item['days'] <= 30 ? 'soon' : 'later');
                            $dotBg = match($urgency) {
                                'critical' => 'bg-red-400',
                                'soon'     => 'bg-yellow-400',
                                default    => 'bg-blue-400',
                            };
                            $daysBadge = match($urgency) {
                                'critical' => 'bg-red-400/10 text-red-400',
                                'soon'     => 'bg-yellow-400/10 text-yellow-500',
                                default    => 'bg-blue-400/10 text-blue-400',
                            };
                            $dateFmt = $item['date']->isCurrentYear()
                                ? $item['date']->format('d M')
                                : $item['date']->format('d M Y');
                        @endphp
                        <div class="flex items-center gap-3 px-5 py-3.5">
                            {{-- Colored dot --}}
                            <div class="flex shrink-0 flex-col items-center gap-1">
                                <span class="size-2.5 rounded-full {{ $dotBg }}"></span>
                            </div>

                            {{-- Icon --}}
                            <span class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                <flux:icon :icon="$item['icon']" class="size-4" />
                            </span>

                            {{-- Info --}}
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $item['name'] }}</p>
                                <div class="mt-0.5 flex items-center gap-1.5">
                                    <span class="text-[11px] text-zinc-400">{{ $item['type'] }}</span>
                                    <span class="text-zinc-300 dark:text-zinc-700">·</span>
                                    <span class="text-[11px] text-zinc-400">{{ $dateFmt }}</span>
                                </div>
                            </div>

                            {{-- Days badge + nav --}}
                            <div class="shrink-0 text-right">
                                <span class="rounded-lg px-2 py-0.5 text-[11px] font-bold {{ $daysBadge }}">{{ $item['value_label'] }}</span>
                                <button type="button"
                                        x-on:click="$dispatch('set-tab', '{{ $item['tab'] }}')"
                                        class="mt-1 block text-[10px] font-medium text-zinc-400 transition-colors hover:text-accent dark:text-zinc-500">
                                    {{ $item['action_label'] }} →
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
