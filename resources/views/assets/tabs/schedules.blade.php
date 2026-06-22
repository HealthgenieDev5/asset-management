@php
$allSchedules = $asset->maintenanceSchedules->sortBy('schedule_name')->values();

$serviceTypeLabels = [
    'preventive_maintenance' => 'Preventive Maintenance',
    'corrective_maintenance' => 'Corrective Maintenance',
    'inspection'             => 'Inspection',
    'repair'                 => 'Repair',
    'calibration'            => 'Calibration',
    'cleaning'               => 'Cleaning',
    'other'                  => 'Other',
];
$serviceTypeIcons = [
    'preventive_maintenance' => 'shield-check',
    'corrective_maintenance' => 'wrench-screwdriver',
    'inspection'             => 'magnifying-glass',
    'repair'                 => 'wrench',
    'calibration'            => 'adjustments-horizontal',
    'cleaning'               => 'sparkles',
    'other'                  => 'ellipsis-horizontal',
];
$serviceTypeColors = [
    'preventive_maintenance' => 'bg-blue-400/10 text-blue-400',
    'corrective_maintenance' => 'bg-orange-400/10 text-orange-400',
    'inspection'             => 'bg-purple-400/10 text-purple-400',
    'repair'                 => 'bg-red-400/10 text-red-400',
    'calibration'            => 'bg-cyan-400/10 text-cyan-400',
    'cleaning'               => 'bg-green-400/10 text-green-400',
    'other'                  => 'bg-zinc-400/10 text-zinc-400',
];
@endphp

<div class="space-y-4">

    {{-- Section Header --}}
    <div class="flex items-center justify-between">
        <div>
            <flux:heading class="font-semibold text-zinc-800 dark:text-zinc-200">Maintenance Schedules</flux:heading>
            <flux:text class="text-xs text-zinc-500 mt-0.5">
                {{ $allSchedules->count() }} {{ Str::plural('schedule', $allSchedules->count()) }} — track recurring maintenance by date, mileage, or operating hours.
            </flux:text>
        </div>
        <button type="button" x-on:click="$dispatch('open-modal-add-schedule')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
            Add Schedule
        </button>
    </div>

    {{-- Single Add Modal --}}
    <x-modal name="add-schedule" title="New Maintenance Schedule" :dismissible="false"
        :auto-open="$errors->any() && old('_form') === 'schedule_add' && !old('_schedule_id')">
        <form method="POST" action="{{ route('assets.maintenance-schedules.store', $asset) }}"
              class="mt-4 space-y-4">
            @csrf
            <input type="hidden" name="_form" value="schedule_add">
            @include('assets.tabs._schedule-form', ['schedule' => null])
            <div class="flex items-center gap-3 pt-1">
                <button type="submit"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                    Save Schedule
                </button>
                <button type="button" x-on:click="$dispatch('close-modal-add-schedule')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                    Cancel
                </button>
            </div>
        </form>
    </x-modal>

    {{-- Edit + Complete Modals per schedule --}}
    @foreach ($allSchedules as $sch)
        <x-modal name="edit-schedule-{{ $sch->id }}" title="Edit Schedule" :dismissible="false"
            :auto-open="$errors->any() && old('_form') === 'schedule_edit' && (int) old('_schedule_id') === $sch->id">
            <form method="POST" action="{{ route('assets.maintenance-schedules.update', [$asset, $sch]) }}"
                  class="mt-4 space-y-4">
                @csrf @method('PUT')
                <input type="hidden" name="_form" value="schedule_edit">
                <input type="hidden" name="_schedule_id" value="{{ $sch->id }}">
                @include('assets.tabs._schedule-form', ['schedule' => $sch])
                <div class="flex items-center gap-3 pt-1">
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                        Save Changes
                    </button>
                    <button type="button" x-on:click="$dispatch('close-modal-edit-schedule-{{ $sch->id }}')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>

        <x-modal name="complete-schedule-{{ $sch->id }}" title="Log Completion — {{ $sch->schedule_name }}" :dismissible="false">
            <form method="POST" action="{{ route('assets.maintenance-schedules.complete', [$asset, $sch]) }}"
                  class="mt-4 space-y-4">
                @csrf @method('PATCH')
                @include('assets.tabs._schedule-complete-form', ['schedule' => $sch])
                <div class="flex items-center gap-3 pt-1">
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
                        Mark Complete
                    </button>
                    <button type="button" x-on:click="$dispatch('close-modal-complete-schedule-{{ $sch->id }}')"
                        class="rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                        Cancel
                    </button>
                </div>
            </form>
        </x-modal>
    @endforeach

    {{-- Empty State --}}
    {{-- Schedules Grid (always shown, Add card always last) --}}
    <div class="grid grid-cols-3 gap-4">
        @foreach ($allSchedules as $sch)
                @php
                    $status      = $sch->statusLabel();
                    $statusColor = $sch->statusColor();
                    $statusText  = $sch->statusText();

                    $lastDoneDate  = $sch->effectiveLastDoneDate();
                    $lastDoneKm    = $sch->effectiveLastDoneKm();
                    $lastDoneHours = $sch->effectiveLastDoneHours();
                    $fromHistory   = $sch->latestServiceRecord() !== null;

                    $svcLabel = $serviceTypeLabels[$sch->service_type] ?? 'Unclassified';
                    $svcIcon  = $serviceTypeIcons[$sch->service_type]  ?? 'calendar-days';
                    $svcColor = $serviceTypeColors[$sch->service_type] ?? 'bg-zinc-400/10 text-zinc-400';

                    if ($sch->schedule_type === 'mileage') {
                        $remaining = $sch->remainingKm();
                        $dueLabel  = $remaining !== null
                            ? ($remaining <= 0 ? 'Overdue by ' . abs($remaining) . ' km' : number_format($remaining) . ' km remaining')
                            : 'No mileage data';
                        $subLabel  = 'Every ' . number_format($sch->interval_km) . ' km';
                    } elseif ($sch->schedule_type === 'operating_hours') {
                        $remaining = $sch->remainingHours();
                        $dueLabel  = $remaining !== null
                            ? ($remaining <= 0 ? 'Overdue by ' . abs($remaining) . ' hrs' : number_format($remaining) . ' hrs remaining')
                            : 'No hour data';
                        $subLabel  = 'Every ' . number_format($sch->interval_hours) . ' hrs';
                    } else {
                        $dueLabel  = $sch->next_due_date
                            ? $sch->next_due_date->format('d M Y')
                            : ($lastDoneDate ? 'Not calculated' : 'Not started');
                        $subLabel  = $sch->interval_value && $sch->interval_unit
                            ? 'Every ' . $sch->interval_value . ' ' . $sch->interval_unit
                            : '';
                    }
                @endphp
                <div class="rounded-xl border border-zinc-200 bg-white overflow-hidden dark:border-zinc-800 dark:bg-zinc-900">

                    {{-- Card Header --}}
                    <div class="flex items-start justify-between gap-2 border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                        <div class="min-w-0">
                            <p class="font-semibold text-sm text-zinc-800 dark:text-zinc-100 truncate">{{ $sch->schedule_name }}</p>
                            <div class="flex items-center gap-1.5 mt-1">
                                <flux:icon :icon="$svcIcon" class="size-3.5 shrink-0 text-zinc-400" />
                                <span class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold {{ $svcColor }}">{{ $svcLabel }}</span>
                            </div>
                        </div>
                        <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium {{ $statusColor }}">{{ $statusText }}</span>
                    </div>

                    {{-- Card Body --}}
                    <div class="px-4 py-3 space-y-3">

                        {{-- Interval + Due/Remaining --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <p class="text-[10px] font-medium text-zinc-500 uppercase tracking-wide">Interval</p>
                                <p class="text-xs text-zinc-700 dark:text-zinc-300 mt-0.5">{{ $subLabel ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-medium text-zinc-500 uppercase tracking-wide">
                                    @if ($sch->schedule_type === 'mileage') Km Remaining
                                    @elseif ($sch->schedule_type === 'operating_hours') Hours Remaining
                                    @else Next Due
                                    @endif
                                </p>
                                <p class="text-xs font-semibold mt-0.5 {{ $status === 'overdue' ? 'text-red-400' : ($status === 'due-soon' ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-100') }}">
                                    {{ $dueLabel }}
                                </p>
                            </div>
                        </div>

                        {{-- Last Done --}}
                        @if ($lastDoneDate || $lastDoneKm || $lastDoneHours)
                            <div>
                                <p class="text-[10px] font-medium text-zinc-500 uppercase tracking-wide">Last Done</p>
                                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-0.5">
                                    @if ($lastDoneDate) {{ $lastDoneDate->format('d M Y') }} @endif
                                    @if ($lastDoneKm) · {{ number_format($lastDoneKm) }} km @endif
                                    @if ($lastDoneHours) · {{ number_format($lastDoneHours) }} hrs @endif
                                </p>
                                @if ($fromHistory)
                                    <p class="text-[10px] text-zinc-400 mt-0.5">Auto-read from service history</p>
                                @endif
                            </div>
                        @endif

                        {{-- Reminder thresholds --}}
                        @if (! empty($sch->reminder_thresholds))
                            @php
                                $rUnit = $sch->reminder_unit ?? 'days';
                                $rSuffix = match ($rUnit) {
                                    'km'    => 'km remaining',
                                    'hours' => 'hrs remaining',
                                    default => 'days before',
                                };
                            @endphp
                            <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/50">
                                <div class="mb-1.5 flex items-center gap-1.5">
                                    <flux:icon.bell-alert class="size-3 text-blue-400" />
                                    <span class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Smart Reminder</span>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    @foreach (array_reverse(array_values(array_unique($sch->reminder_thresholds))) as $t)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-400/10 px-2.5 py-0.5 text-[11px] font-semibold text-blue-400">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 opacity-70"><path fill-rule="evenodd" d="M8 14a.75.75 0 0 1-.75-.75V4.56L4.03 7.78a.75.75 0 0 1-1.06-1.06l4.5-4.5a.75.75 0 0 1 1.06 0l4.5 4.5a.75.75 0 0 1-1.06 1.06L8.75 4.56v8.69A.75.75 0 0 1 8 14Z" clip-rule="evenodd"/></svg>
                                            {{ number_format($t) }} {{ $rSuffix }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if ($sch->description)
                            <p class="text-xs text-zinc-500 line-clamp-2">{{ $sch->description }}</p>
                        @endif

                        @if (! $sch->is_active)
                            <span class="inline-block rounded-full bg-zinc-200/60 px-2 py-0.5 text-[11px] font-medium text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">Inactive</span>
                        @endif
                    </div>

                    {{-- Card Footer --}}
                    <div class="flex items-center justify-between border-t border-zinc-200 px-4 py-2.5 dark:border-zinc-800">
                        <button type="button" x-on:click="$dispatch('open-modal-complete-schedule-{{ $sch->id }}')"
                                class="inline-flex items-center gap-1 rounded-md bg-green-500/10 px-2.5 py-1 text-xs font-medium text-green-500 hover:bg-green-500/20 transition-colors">
                            <flux:icon.check class="size-3" />
                            Done
                        </button>
                        <div class="flex items-center gap-1.5">
                            <a href="{{ route('assets.show', [$asset, 'tab' => 'reminders', 'showform' => '1', 'scheduleid' => $sch->id]) }}"
                               title="{{ $sch->smartReminders->isNotEmpty() ? 'Manage Reminders' : 'Add Reminder' }}"
                               class="inline-flex size-6 items-center justify-center rounded-md border border-accent text-accent hover:bg-accent/10 transition-colors">
                                <flux:icon.bell-alert class="size-3.5" />
                            </a>
                            <button type="button" x-on:click="$dispatch('open-modal-edit-schedule-{{ $sch->id }}')"
                                    title="Edit"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                                <flux:icon.pencil class="size-3.5" />
                            </button>
                            <form method="POST" action="{{ route('assets.maintenance-schedules.destroy', [$asset, $sch]) }}"
                                  onsubmit="return confirm('Delete this maintenance schedule?')">
                                @csrf @method('DELETE')
                                <button type="submit" title="Delete"
                                        class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                    <flux:icon.trash class="size-3.5" />
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Add Schedule card --}}
            <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors duration-200 hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
                <flux:icon.calendar-days class="mx-auto size-10 text-zinc-600" />
                <flux:heading class="mt-4 text-zinc-400">
                    {{ $allSchedules->isEmpty() ? 'No Schedules Yet' : 'Add Another Schedule' }}
                </flux:heading>
                <flux:text class="mt-1 text-sm text-zinc-600">Track recurring maintenance by date, mileage, or operating hours.</flux:text>
                <div class="mt-4">
                    <button type="button" x-on:click="$dispatch('open-modal-add-schedule')"
                        class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors border border-zinc-300 dark:border-zinc-700">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                        {{ $allSchedules->isEmpty() ? 'Add First Schedule' : 'Add Schedule' }}
                    </button>
                </div>
            </div>
        </div>

</div>
