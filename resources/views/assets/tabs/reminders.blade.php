@php
    // Build a unified list of all tracked expiry items for this asset
    $items = collect();

    // Original warranty
    if ($asset->warranty_lapse_date) {
        $items->push([
            'type'        => 'Original Warranty',
            'icon'        => 'shield-check',
            'expiry'      => $asset->warranty_lapse_date,
            'reminder'    => $asset->warranty_reminder_before_days,
            'detail'      => $asset->warranty_details,
            'edit_tab'    => null, // edit on main form
        ]);
    }

    // Extended warranties
    foreach ($asset->extendedWarranties as $ew) {
        if ($ew->extended_warranty_date_to) {
            $items->push([
                'type'     => 'Extended Warranty',
                'icon'     => 'shield-exclamation',
                'expiry'   => $ew->extended_warranty_date_to,
                'reminder' => $ew->reminder_before_days,
                'detail'   => $ew->extended_warranty_vendor,
                'edit_tab' => 'ext-warranty',
            ]);
        }
    }

    // AMC contracts
    foreach ($asset->amcContracts as $amc) {
        if ($amc->amc_date_to) {
            $items->push([
                'type'     => 'AMC Contract',
                'icon'     => 'wrench-screwdriver',
                'expiry'   => $amc->amc_date_to,
                'reminder' => $amc->reminder_before_days,
                'detail'   => $amc->vendor_name ?: ($amc->contract_number ?: null),
                'edit_tab' => 'amc',
            ]);
        }
    }

    // Insurance policies
    foreach ($asset->insurancePolicies as $policy) {
        if ($policy->policy_date_to) {
            $items->push([
                'type'     => 'Insurance',
                'icon'     => 'building-library',
                'expiry'   => $policy->policy_date_to,
                'reminder' => $policy->reminder_before_days,
                'detail'   => $policy->insurer_name ?: ($policy->policy_number ?: null),
                'edit_tab' => 'insurance',
            ]);
        }
    }

    // Part warranties
    foreach ($asset->services->flatMap->parts as $part) {
        if ($part->warranty_till) {
            $items->push([
                'type'     => 'Part Warranty',
                'icon'     => 'puzzle-piece',
                'expiry'   => $part->warranty_till,
                'reminder' => null,
                'detail'   => $part->part_name . ($part->purchased_from ? ' — ' . $part->purchased_from : ''),
                'edit_tab' => 'parts',
            ]);
        }
    }

    // Sort: expired first by most recently expired, then upcoming soonest first
    $items = $items->sortBy(fn($i) => $i['expiry']->timestamp)->values();
@endphp

<div class="space-y-5">
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-800 dark:text-zinc-200">Expiry Reminders</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                All tracked expiry dates for this asset.
            </flux:text>
        </div>
    </div>

    @if ($items->isEmpty())
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 py-14 text-center dark:border-zinc-700 dark:bg-zinc-900">
            <flux:icon.bell-alert class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">No Expiry Dates Tracked</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">
                Add warranty, extended warranty, AMC, or insurance expiry dates to see reminders here.
            </flux:text>
        </div>
    @else
        <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Type</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Detail</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Expiry Date</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Days Left</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Reminder At</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                    @foreach ($items as $item)
                        @php
                            $expStart = $item['expiry']->copy()->startOfDay();
                            $days    = (int) ($expStart->diffInDays(now()->startOfDay()) * ($expStart->gte(now()->startOfDay()) ? 1 : -1));
                            $expired = $days < 0;
                            $soon    = ! $expired && $days <= ($item['reminder'] ?? 30);

                            if ($expired) {
                                $statusLabel = 'Expired';
                                $statusClass = 'bg-red-400/10 text-red-400';
                                $daysLabel   = abs($days) . 'd ago';
                                $daysClass   = 'text-red-400 font-semibold';
                            } elseif ($soon) {
                                $statusLabel = 'Expiring Soon';
                                $statusClass = 'bg-yellow-400/10 text-yellow-400';
                                $daysLabel   = $days . 'd';
                                $daysClass   = 'text-yellow-400 font-semibold';
                            } else {
                                $statusLabel = 'Active';
                                $statusClass = 'bg-green-400/10 text-green-400';
                                $daysLabel   = $days . 'd';
                                $daysClass   = 'text-zinc-800 dark:text-zinc-200';
                            }

                            $reminderDate = $item['reminder']
                                ? $item['expiry']->copy()->subDays($item['reminder'])->format('d M Y')
                                : '—';
                        @endphp
                        <tr class="hover:bg-zinc-50 transition-colors dark:hover:bg-zinc-800/30">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <flux:icon :icon="$item['icon']" class="size-4 shrink-0 text-zinc-400" />
                                    <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $item['type'] }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">
                                {{ $item['detail'] ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-800 dark:text-zinc-200">
                                {{ $item['expiry']->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 {{ $daysClass }}">
                                {{ $daysLabel }}
                            </td>
                            <td class="px-4 py-3 text-zinc-400 text-xs">
                                @if ($item['reminder'])
                                    {{ $reminderDate }}
                                    <span class="text-zinc-600">({{ $item['reminder'] }}d before)</span>
                                @else
                                    <span class="text-zinc-600">Not set</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">
                                    {{ $statusLabel }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-xs text-zinc-600">
            Reminder email notifications will be sent automatically based on the reminder days set above (Phase 6 — Email Scheduler).
        </p>
    @endif
</div>
