@php
    // Build a unified list of all tracked expiry items for this asset
    $items = collect();

    // Original warranty (from asset record itself — legacy field on assets table)
    if ($asset->warranty_lapse_date) {
        $items->push([
            'category'       => 'Warranty',
            'category_color' => 'bg-blue-400/10 text-blue-400',
            'icon'           => 'shield-check',
            'name'           => $asset->warranty_details ?: 'Asset Warranty',
            'source'         => 'From asset record',
            'expiry'         => $asset->warranty_lapse_date,
            'edit_tab'       => 'overview',
        ]);
    }

    // Warranties (unified model — asset_warranties table)
    foreach ($asset->warranties ?? [] as $w) {
        if ($w->expiry_date) {
            $isPartScope = $w->scope === 'part';
            if ($isPartScope) {
                $name   = $w->part_name ?: 'Unnamed Part';
                $source = $w->vendor ?: null;
            } else {
                // asset-scope: vendor is the primary identifier; fallback to details or type label
                $name   = $w->vendor ?: ($w->details ?: ($w->warrantyTypeLabel() . ' Warranty'));
                $source = $w->vendor && $w->details ? $w->details : null;
            }
            $items->push([
                'category'       => $isPartScope ? 'Part Warranty' : ($w->warrantyTypeLabel() . ' Warranty'),
                'category_color' => $isPartScope ? 'bg-violet-400/10 text-violet-400' : 'bg-blue-400/10 text-blue-400',
                'icon'           => $isPartScope ? 'puzzle-piece' : 'shield-exclamation',
                'name'           => $name,
                'source'         => $source,
                'expiry'         => $w->expiry_date,
                'edit_tab'       => 'warranty',
            ]);
        }
    }

    // AMC contracts
    foreach ($asset->amcContracts as $amc) {
        if ($amc->amc_date_to) {
            $items->push([
                'category'       => 'AMC',
                'category_color' => 'bg-amber-400/10 text-amber-400',
                'icon'           => 'wrench-screwdriver',
                'name'           => $amc->vendor_name ?: ($amc->contract_number ?: 'No vendor'),
                'source'         => $amc->contract_number && $amc->vendor_name ? 'Contract #' . $amc->contract_number : null,
                'expiry'         => $amc->amc_date_to,
                'edit_tab'       => 'amc',
            ]);
        }
    }

    // Insurance policies
    foreach ($asset->insurancePolicies as $policy) {
        if ($policy->policy_date_to) {
            $items->push([
                'category'       => 'Insurance',
                'category_color' => 'bg-green-400/10 text-green-400',
                'icon'           => 'building-library',
                'name'           => $policy->insurer_name ?: ($policy->policy_number ?: 'No insurer'),
                'source'         => $policy->policy_number ? 'Policy #' . $policy->policy_number : null,
                'expiry'         => $policy->policy_date_to,
                'edit_tab'       => 'insurance',
            ]);
        }
    }

    // Part warranties (from service parts)
    foreach ($asset->services->flatMap->parts as $part) {
        if ($part->warranty_till) {
            $items->push([
                'category'       => 'Part Warranty',
                'category_color' => 'bg-violet-400/10 text-violet-400',
                'icon'           => 'puzzle-piece',
                'name'           => $part->part_name,
                'source'         => $part->purchased_from ?: null,
                'expiry'         => $part->warranty_till,
                'edit_tab'       => 'parts',
            ]);
        }
    }

    $items = $items->sortBy(fn($i) => $i['expiry']->timestamp)->values();

    $smartReminders = $asset->smartReminders->sortByDesc('created_at');

    $typeLabels = \App\Models\AssetSmartReminder::$typeLabels;
@endphp

<div class="space-y-8">

    {{-- ── Section 1: Tracked Expiry Dates ── --}}
    <div class="space-y-4">
        <div>
            <flux:heading class="font-semibold text-zinc-800 dark:text-zinc-200">Tracked Expiry Dates</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">All expiry dates tracked across warranty, AMC, insurance, and parts for this asset.</flux:text>
        </div>

        @if ($items->isEmpty())
            <div class="grid grid-cols-3 gap-4">
                <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
                    <flux:icon.bell-alert class="mx-auto size-10 text-zinc-600" />
                    <flux:heading class="mt-4 text-zinc-400">No Expiry Dates Found</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-600">Add warranty, AMC, or insurance records to see expiry dates here.</flux:text>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-800/40">
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Category</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Name / Vendor</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Expiry Date</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Days Left</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                            <th class="px-4 py-2.5"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                        @foreach ($items as $item)
                            @php
                                $expStart = $item['expiry']->copy()->startOfDay();
                                $days     = (int) now()->startOfDay()->diffInDays($expStart, false);
                                $expired  = $days < 0;
                                $soon     = ! $expired && $days <= 30;

                                if ($expired) {
                                    $statusLabel = 'Expired';
                                    $statusClass = 'bg-red-400/10 text-red-400';
                                    $daysLabel   = abs($days) . 'd ago';
                                    $daysClass   = 'text-red-400 font-semibold';
                                } elseif ($soon) {
                                    $statusLabel = 'Expiring Soon';
                                    $statusClass = 'bg-yellow-400/10 text-yellow-400';
                                    $daysLabel   = $days . 'd left';
                                    $daysClass   = 'text-yellow-400 font-semibold';
                                } else {
                                    $statusLabel = 'Active';
                                    $statusClass = 'bg-green-400/10 text-green-400';
                                    $daysLabel   = $days . 'd left';
                                    $daysClass   = 'text-zinc-800 dark:text-zinc-200';
                                }
                            @endphp
                            <tr class="hover:bg-zinc-50 transition-colors dark:hover:bg-zinc-800/30">
                                {{-- Category badge --}}
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <flux:icon :icon="$item['icon']" class="size-4 shrink-0 text-zinc-400" />
                                        <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $item['category_color'] }}">
                                            {{ $item['category'] }}
                                        </span>
                                    </div>
                                </td>
                                {{-- Name + optional source sub-line --}}
                                <td class="px-4 py-3 max-w-56">
                                    <p class="font-medium text-zinc-800 dark:text-zinc-200 truncate">{{ $item['name'] }}</p>
                                    @if ($item['source'])
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate mt-0.5">{{ $item['source'] }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-zinc-800 dark:text-zinc-200 whitespace-nowrap">{{ $item['expiry']->format('d M Y') }}</td>
                                <td class="px-4 py-3 {{ $daysClass }} whitespace-nowrap">{{ $daysLabel }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">{{ $statusLabel }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    @if ($item['edit_tab'])
                                        <button type="button"
                                                @click="$dispatch('set-tab', '{{ $item['edit_tab'] }}')"
                                                title="Go to {{ ucfirst($item['edit_tab']) }} tab to edit"
                                                class="inline-flex items-center gap-1 rounded-md border border-zinc-300 px-2 py-0.5 text-xs text-zinc-500 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700">
                                            <flux:icon.pencil class="size-3" />
                                            Edit
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- ── Section 2: Smart Reminders ── --}}
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading class="font-semibold text-zinc-800 dark:text-zinc-200">Smart Reminders</flux:heading>
                <flux:text class="text-xs text-zinc-500 mt-0.5">
                    Set multiple reminder dates before any expiry. Emails sent automatically on each threshold day.
                </flux:text>
            </div>
            <button type="button" x-on:click="$dispatch('open-modal-add-smart-reminder')"
                class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                Add Smart Reminder
            </button>
        </div>

        {{-- Add Modal --}}
        <x-modal name="add-smart-reminder" title="New Smart Reminder" :dismissible="false"
            :auto-open="($errors->any() && old('_form') === 'smart_reminder' && !old('_reminder_id')) || ($showReminderForm ?? false)">
            <form method="POST" action="{{ route('assets.smart-reminders.store', $asset) }}"
                  class="mt-4 space-y-4">
                @csrf
                <input type="hidden" name="_form" value="smart_reminder">
                @include('assets.tabs._smart-reminder-form', ['reminder' => null, 'reminderPrefill' => $reminderPrefill ?? null])
                <div class="flex items-center gap-3 pt-1">
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                        Save Reminder
                    </button>
                    <button type="button" x-on:click="$dispatch('close-modal-add-smart-reminder')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>

        {{-- Edit Modals --}}
        @foreach ($smartReminders as $sr)
            <x-modal name="edit-smart-reminder-{{ $sr->id }}" title="Edit Smart Reminder" :dismissible="false"
                :auto-open="$errors->any() && old('_form') === 'smart_reminder' && (int) old('_reminder_id') === $sr->id">
                <form method="POST" action="{{ route('assets.smart-reminders.update', [$asset, $sr]) }}"
                      class="mt-4 space-y-4">
                    @csrf @method('PUT')
                    <input type="hidden" name="_form" value="smart_reminder">
                    <input type="hidden" name="_reminder_id" value="{{ $sr->id }}">
                    @include('assets.tabs._smart-reminder-form', ['reminder' => $sr])
                    <div class="flex items-center gap-3 pt-1">
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                            Save Changes
                        </button>
                        <button type="button" x-on:click="$dispatch('close-modal-edit-smart-reminder-{{ $sr->id }}')"
                            class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                            Cancel
                        </button>
                    </div>
                </form>
            </x-modal>
        @endforeach

        {{-- Smart Reminders List --}}
        @if ($smartReminders->isEmpty())
            <div class="grid grid-cols-3 gap-4">
                <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
                    <flux:icon.bell-alert class="mx-auto size-10 text-zinc-600" />
                    <flux:heading class="mt-4 text-zinc-400">No Smart Reminders Yet</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-600">Create a smart reminder to get notified at multiple points before an expiry.</flux:text>
                    <div class="mt-4">
                        <button type="button" x-on:click="$dispatch('open-modal-add-smart-reminder')"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                            Add First Reminder
                        </button>
                    </div>
                </div>
            </div>
        @else
            <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-800/40">
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Name</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Type</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Expiry</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Remind Before Expiry</th>
                            <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                            <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200/60 dark:divide-zinc-800/60">
                        @foreach ($smartReminders as $sr)
                            @php
                                $badge    = $sr->statusBadge();
                                $expired  = $sr->isExpired();
                                $isTime   = $sr->isTimeBased();

                                $statusClass = match ($badge) {
                                    'expired' => 'bg-red-400/10 text-red-400',
                                    'soon'    => 'bg-yellow-400/10 text-yellow-400',
                                    default   => 'bg-green-400/10 text-green-400',
                                };
                                $statusText = match ($badge) {
                                    'expired' => 'Expired',
                                    'soon'    => $isTime ? 'Expiring Soon' : 'Running Low',
                                    default   => 'Active',
                                };

                                // Time-mode display
                                if ($isTime) {
                                    $daysLeft   = $sr->daysUntilExpiry();
                                    $expiryLine = $sr->expiry_date?->format('d M Y') ?? '—';
                                    $subLine    = $expired ? abs($daysLeft) . 'd ago' : $daysLeft . 'd left';
                                    $subClass   = $expired ? 'text-red-400' : 'text-zinc-500';
                                } elseif ($sr->reminder_type === 'maintenance_schedule' && $sr->remindable) {
                                    $sch        = $sr->remindable;
                                    $unit       = $sr->threshold_unit ?? '';
                                    $remaining  = $sr->remainingUnits();
                                    if ($sch->schedule_type === 'mileage') {
                                        $expiryLine = 'Every ' . number_format($sch->interval_km) . ' ' . $unit;
                                        $nextDue    = ($sch->effectiveLastDoneKm() !== null && $sch->interval_km)
                                            ? 'Next at ' . number_format($sch->effectiveLastDoneKm() + $sch->interval_km) . ' ' . $unit
                                            : 'No reading logged';
                                    } else {
                                        $expiryLine = 'Every ' . number_format($sch->interval_hours) . ' ' . $unit;
                                        $nextDue    = ($sch->effectiveLastDoneHours() !== null && $sch->interval_hours)
                                            ? 'Next at ' . number_format($sch->effectiveLastDoneHours() + $sch->interval_hours) . ' ' . $unit
                                            : 'No reading logged';
                                    }
                                    $subLine  = $nextDue;
                                    $subClass = 'text-zinc-500';
                                } else {
                                    $remaining  = $sr->remainingUnits();
                                    $unit       = $sr->threshold_unit ?? '';
                                    $expiryLine = $sr->counter_limit ? number_format($sr->counter_limit) . ' ' . $unit . ' limit' : '—';
                                    $subLine    = $remaining !== null
                                        ? number_format($remaining) . ' ' . $unit . ' remaining'
                                        : 'No reading logged';
                                    $subClass   = $remaining === null ? 'text-zinc-400' : ($remaining <= (count($sr->reminder_days ?? []) ? max($sr->reminder_days) : 0) ? 'text-yellow-400' : 'text-zinc-500');
                                }
                            @endphp
                            <tr class="hover:bg-zinc-50 transition-colors dark:hover:bg-zinc-800/30">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-1.5">
                                        <p class="font-medium text-zinc-800 dark:text-zinc-200">{{ $sr->reminder_name }}</p>
                                        @if (! $isTime)
                                            <span class="rounded-full bg-violet-400/10 px-1.5 py-0.5 text-[10px] font-medium text-violet-400">{{ ucfirst($sr->reminder_mode) }}</span>
                                        @endif
                                    </div>
                                    @if ($sr->notes)
                                        <p class="text-[11px] text-zinc-500 mt-0.5 truncate max-w-45">{{ $sr->notes }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400">
                                        {{ $typeLabels[$sr->reminder_type] ?? $sr->reminder_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-zinc-800 dark:text-zinc-200">{{ $expiryLine }}</p>
                                    <p class="text-[11px] {{ $subClass }}">{{ $subLine }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @foreach (array_reverse(array_values(array_unique($sr->reminder_days ?? []))) as $d)
                                            <span class="inline-flex items-center rounded-full bg-blue-400/10 px-2 py-0.5 text-[11px] font-medium text-blue-400">
                                                {{ $d }}{{ $isTime ? 'd' : ' ' . ($sr->threshold_unit ?? '') }}
                                            </span>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if (! $sr->is_active)
                                        <span class="rounded-full bg-zinc-200/60 px-2 py-0.5 text-xs font-medium text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">Inactive</span>
                                    @else
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $statusClass }}">{{ $statusText }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button"
                                                x-on:click="$dispatch('open-modal-edit-smart-reminder-{{ $sr->id }}')"
                                                title="Edit"
                                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                                            <flux:icon.pencil class="size-3.5" />
                                        </button>
                                        <form method="POST" action="{{ route('assets.smart-reminders.destroy', [$asset, $sr]) }}"
                                              onsubmit="return confirm('Delete this smart reminder?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Delete"
                                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                                <flux:icon.trash class="size-3.5" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
