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
            <flux:text class="mt-0.5 text-xs text-zinc-500">
                {{ $allSchedules->count() }} {{ Str::plural('schedule', $allSchedules->count()) }} — track recurring maintenance by date, mileage, or operating hours.
            </flux:text>
        </div>
        <button type="button" x-on:click="$dispatch('open-modal-add-schedule')"
            class="inline-flex items-center gap-1.5 rounded-lg bg-accent px-3 py-1.5 text-xs font-semibold text-accent-foreground shadow-sm hover:opacity-90 transition-opacity">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
            Add Schedule
        </button>
    </div>

    {{-- Add Modal --}}
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

    {{-- Per-schedule modals + cards --}}
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
                $dueLabel = $sch->next_due_date
                    ? $sch->next_due_date->format('d M Y')
                    : ($lastDoneDate ? 'Not calculated' : 'Not started');
                $subLabel = $sch->interval_value && $sch->interval_unit
                    ? 'Every ' . $sch->interval_value . ' ' . $sch->interval_unit
                    : '';
            }
        @endphp

        {{-- Edit Modal --}}
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

        {{-- Complete Modal --}}
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

        {{-- View Modal (inline edit) --}}
        @php
            $schPatchUrl = route('assets.maintenance-schedules.patch-field', [$asset, $sch]);
            $schInp      = 'rounded border border-zinc-300 bg-white px-2 py-0.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100';
            $schBtnOk    = 'rounded p-0.5 text-green-500 hover:text-green-400 transition-colors';
            $schBtnX     = 'rounded p-0.5 text-zinc-400 hover:text-zinc-200 transition-colors';
            $schPencil   = '<svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125"/></svg>';
            $schCheck    = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>';
            $schX        = '<svg class="size-3.5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>';
            $schDt       = 'text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
            $schDd       = 'mt-0.5 text-sm text-zinc-800 dark:text-zinc-200';
        @endphp
        <x-modal name="view-schedule-{{ $sch->id }}" title="Schedule Details">
            <x-slot:footer>
                <div class="flex items-center gap-2">
                    <span class="rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $svcColor }}">{{ $svcLabel }}</span>
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $sch->is_active ? $statusColor : 'bg-zinc-200/60 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' }}">
                        {{ $sch->is_active ? $statusText : 'Inactive' }}
                    </span>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" x-on:click="$dispatch('close-modal-view-schedule-{{ $sch->id }}')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-600 transition-colors hover:border-zinc-400 hover:text-zinc-800 dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:text-zinc-100">
                        <flux:icon.x-mark class="size-3.5" />
                        Close
                    </button>
                    <form method="POST" action="{{ route('assets.maintenance-schedules.destroy', [$asset, $sch]) }}" onsubmit="confirmDelete(this, 'Delete this schedule?'); return false;">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-300/60 px-3 py-1.5 text-xs font-medium text-red-500 transition-colors hover:border-red-500/60 hover:bg-red-500/5 dark:border-red-700/50 dark:text-red-400 dark:hover:border-red-500/60">
                            <flux:icon.trash class="size-3.5" />
                            Delete
                        </button>
                    </form>
                </div>
            </x-slot:footer>

            <div x-data="{
                schedName:    {{ json_encode($sch->schedule_name) }},
                svcType:      '{{ $sch->service_type }}',
                schedType:    '{{ $sch->schedule_type }}',
                description:  {{ json_encode($sch->description ?? '') }},
                notes:        {{ json_encode($sch->notes ?? '') }},
                isActive:     {{ $sch->is_active ? 'true' : 'false' }},
                intervalVal:  '{{ $sch->interval_value ?? '' }}',
                intervalUnit: '{{ $sch->interval_unit ?? '' }}',
                lastDoneDate: '{{ $sch->last_done_date?->format('d M Y') ?? '' }}',
                intervalKm:   '{{ $sch->interval_km ?? '' }}',
                lastDoneKm:   '{{ $sch->last_done_km ?? '' }}',
                intervalHrs:  '{{ $sch->interval_hours ?? '' }}',
                lastDoneHrs:  '{{ $sch->last_done_hours ?? '' }}',
                async schp(field, value) {
                    const fd = new URLSearchParams({ _method: 'PATCH', field, value: value ?? '' });
                    const r = await fetch('{{ $schPatchUrl }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json' },
                        body: fd
                    });
                    if (!r.ok) { toastr.error('Save failed.'); return false; }
                    toastr.success('Updated.');
                    return true;
                }
            }" class="space-y-5 mt-1">

                {{-- ── Identity ── --}}
                <div>
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Schedule Info</p>
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">

                        {{-- Schedule Name --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $schDt }}">Schedule Name</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="text-sm font-semibold text-zinc-800 dark:text-zinc-100" x-text="schedName"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $schBtnX }}">{!! $schPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="text" x-ref="inpName" class="{{ $schInp }} w-48" :value="schedName" maxlength="255" />
                                        <button type="button" class="{{ $schBtnOk }}" @click="if(await schp('schedule_name',$refs.inpName.value)){schedName=$refs.inpName.value;editing=false}">{!! $schCheck !!}</button>
                                        <button type="button" class="{{ $schBtnX }}" @click="editing=false">{!! $schX !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Service Type --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $schDt }}">Service Type</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="rounded-full px-2 py-0.5 text-[10px] font-semibold {{ $svcColor }}" x-text="{ preventive_maintenance:'Preventive Maintenance', corrective_maintenance:'Corrective Maintenance', inspection:'Inspection', repair:'Repair', calibration:'Calibration', cleaning:'Cleaning', other:'Other' }[svcType]"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $schBtnX }}">{!! $schPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <select x-ref="selSvcType" class="{{ $schInp }}" :value="svcType">
                                            <option value="preventive_maintenance">Preventive Maintenance</option>
                                            <option value="corrective_maintenance">Corrective Maintenance</option>
                                            <option value="inspection">Inspection</option>
                                            <option value="repair">Repair</option>
                                            <option value="calibration">Calibration</option>
                                            <option value="cleaning">Cleaning</option>
                                            <option value="other">Other</option>
                                        </select>
                                        <button type="button" class="{{ $schBtnOk }}" @click="if(await schp('service_type',$refs.selSvcType.value)){svcType=$refs.selSvcType.value;editing=false}">{!! $schCheck !!}</button>
                                        <button type="button" class="{{ $schBtnX }}" @click="editing=false">{!! $schX !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Schedule Type --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $schDt }}">Schedule Type</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $schDd }}" x-text="{ date:'Date / Time', mileage:'Mileage (km)', operating_hours:'Operating Hours' }[schedType]"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $schBtnX }}">{!! $schPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <select x-ref="selSchedType" class="{{ $schInp }}" :value="schedType">
                                            <option value="date">Date / Time</option>
                                            <option value="mileage">Mileage (km)</option>
                                            <option value="operating_hours">Operating Hours</option>
                                        </select>
                                        <button type="button" class="{{ $schBtnOk }}" @click="if(await schp('schedule_type',$refs.selSchedType.value)){schedType=$refs.selSchedType.value;editing=false}">{!! $schCheck !!}</button>
                                        <button type="button" class="{{ $schBtnX }}" @click="editing=false">{!! $schX !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Active --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $schDt }}">Status</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="text-sm" :class="isActive ? 'text-green-400' : 'text-zinc-400'" x-text="isActive ? 'Active' : 'Inactive'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $schBtnX }}">{!! $schPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <select x-ref="selActive" class="{{ $schInp }}">
                                            <option value="1" :selected="isActive">Active</option>
                                            <option value="0" :selected="!isActive">Inactive</option>
                                        </select>
                                        <button type="button" class="{{ $schBtnOk }}" @click="if(await schp('is_active',$refs.selActive.value)){isActive=$refs.selActive.value==='1';editing=false}">{!! $schCheck !!}</button>
                                        <button type="button" class="{{ $schBtnX }}" @click="editing=false">{!! $schX !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                    </dl>
                </div>

                {{-- ── Interval & Last Done ── --}}
                <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Interval & Tracking</p>
                    <dl class="grid gap-x-6 gap-y-4 sm:grid-cols-2">

                        {{-- Date: interval value + unit (paired) --}}
                        <div x-show="schedType === 'date'" x-data="{ editing: false }">
                            <dt class="{{ $schDt }}">Interval</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $schDd }}" x-text="intervalVal && intervalUnit ? 'Every ' + intervalVal + ' ' + intervalUnit : '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $schBtnX }}">{!! $schPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex flex-wrap items-center gap-1">
                                        <input type="number" x-ref="inpIntVal" class="{{ $schInp }} w-16" :value="intervalVal" min="1" placeholder="N" />
                                        <select x-ref="selIntUnit" class="{{ $schInp }}" :value="intervalUnit">
                                            <option value="days">Days</option>
                                            <option value="weeks">Weeks</option>
                                            <option value="months">Months</option>
                                            <option value="years">Years</option>
                                        </select>
                                        <button type="button" class="{{ $schBtnOk }}" @click="if(await schp('interval_value',$refs.inpIntVal.value) && await schp('interval_unit',$refs.selIntUnit.value)){intervalVal=$refs.inpIntVal.value;intervalUnit=$refs.selIntUnit.value;editing=false}">{!! $schCheck !!}</button>
                                        <button type="button" class="{{ $schBtnX }}" @click="editing=false">{!! $schX !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Date: last done date --}}
                        <div x-show="schedType === 'date'" x-data="{ editing: false }" x-init="$watch('editing', v => { if(v) $nextTick(() => flatpickr($refs.fpLastDone, { dateFormat:'Y-m-d', altInput:true, altFormat:'d M Y', allowInput:true, disableMobile:true })) })">
                            <dt class="{{ $schDt }}">Last Done Date</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $schDd }}" x-text="lastDoneDate || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $schBtnX }}">{!! $schPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="text" x-ref="fpLastDone" class="{{ $schInp }} w-32" placeholder="Date" />
                                        <button type="button" class="{{ $schBtnOk }}" @click="if(await schp('last_done_date',$refs.fpLastDone._flatpickr?.input.value||$refs.fpLastDone.value)){lastDoneDate=$refs.fpLastDone._flatpickr?.altInput?.value||$refs.fpLastDone.value;editing=false}">{!! $schCheck !!}</button>
                                        <button type="button" class="{{ $schBtnX }}" @click="editing=false">{!! $schX !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Mileage: interval km --}}
                        <div x-show="schedType === 'mileage'" x-data="{ editing: false }">
                            <dt class="{{ $schDt }}">Interval (km)</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $schDd }}" x-text="intervalKm ? 'Every ' + Number(intervalKm).toLocaleString() + ' km' : '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $schBtnX }}">{!! $schPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="number" x-ref="inpIntKm" class="{{ $schInp }} w-28" :value="intervalKm" min="1" />
                                        <button type="button" class="{{ $schBtnOk }}" @click="if(await schp('interval_km',$refs.inpIntKm.value)){intervalKm=$refs.inpIntKm.value;editing=false}">{!! $schCheck !!}</button>
                                        <button type="button" class="{{ $schBtnX }}" @click="editing=false">{!! $schX !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Mileage: last done km --}}
                        <div x-show="schedType === 'mileage'" x-data="{ editing: false }">
                            <dt class="{{ $schDt }}">Last Done at (km)</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $schDd }}" x-text="lastDoneKm ? Number(lastDoneKm).toLocaleString() + ' km' : '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $schBtnX }}">{!! $schPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="number" x-ref="inpLastKm" class="{{ $schInp }} w-28" :value="lastDoneKm" min="0" />
                                        <button type="button" class="{{ $schBtnOk }}" @click="if(await schp('last_done_km',$refs.inpLastKm.value)){lastDoneKm=$refs.inpLastKm.value;editing=false}">{!! $schCheck !!}</button>
                                        <button type="button" class="{{ $schBtnX }}" @click="editing=false">{!! $schX !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Hours: interval hours --}}
                        <div x-show="schedType === 'operating_hours'" x-data="{ editing: false }">
                            <dt class="{{ $schDt }}">Interval (hours)</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $schDd }}" x-text="intervalHrs ? 'Every ' + Number(intervalHrs).toLocaleString() + ' hrs' : '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $schBtnX }}">{!! $schPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="number" x-ref="inpIntHrs" class="{{ $schInp }} w-24" :value="intervalHrs" min="1" />
                                        <button type="button" class="{{ $schBtnOk }}" @click="if(await schp('interval_hours',$refs.inpIntHrs.value)){intervalHrs=$refs.inpIntHrs.value;editing=false}">{!! $schCheck !!}</button>
                                        <button type="button" class="{{ $schBtnX }}" @click="editing=false">{!! $schX !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Hours: last done hours --}}
                        <div x-show="schedType === 'operating_hours'" x-data="{ editing: false }">
                            <dt class="{{ $schDt }}">Last Done at (hrs)</dt>
                            <dd class="mt-0.5 flex items-center gap-1.5">
                                <span x-show="!editing" class="{{ $schDd }}" x-text="lastDoneHrs ? Number(lastDoneHrs).toLocaleString() + ' hrs' : '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $schBtnX }}">{!! $schPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex items-center gap-1">
                                        <input type="number" x-ref="inpLastHrs" class="{{ $schInp }} w-24" :value="lastDoneHrs" min="0" />
                                        <button type="button" class="{{ $schBtnOk }}" @click="if(await schp('last_done_hours',$refs.inpLastHrs.value)){lastDoneHrs=$refs.inpLastHrs.value;editing=false}">{!! $schCheck !!}</button>
                                        <button type="button" class="{{ $schBtnX }}" @click="editing=false">{!! $schX !!}</button>
                                    </span>
                                </template>
                            </dd>
                        </div>

                    </dl>
                </div>

                {{-- ── Status summary (read-only) ── --}}
                <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Current Status</p>
                    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                        <div>
                            <dt class="{{ $schDt }}">
                                @if ($sch->schedule_type === 'mileage') Km Remaining
                                @elseif ($sch->schedule_type === 'operating_hours') Hours Remaining
                                @else Next Due
                                @endif
                            </dt>
                            <dd class="mt-0.5 text-sm font-semibold {{ $status === 'overdue' ? 'text-red-400' : ($status === 'due-soon' ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-100') }}">
                                {{ $dueLabel }}
                            </dd>
                        </div>
                        @if ($lastDoneDate || $lastDoneKm || $lastDoneHours)
                            <div>
                                <dt class="{{ $schDt }}">Last Done</dt>
                                <dd class="{{ $schDd }}">
                                    @if ($lastDoneDate) {{ $lastDoneDate->format('d M Y') }} @endif
                                    @if ($lastDoneKm) · {{ number_format($lastDoneKm) }} km @endif
                                    @if ($lastDoneHours) · {{ number_format($lastDoneHours) }} hrs @endif
                                    @if ($fromHistory) <span class="text-[10px] text-zinc-400">(from history)</span> @endif
                                </dd>
                            </div>
                        @endif
                        @if (! empty($sch->reminder_thresholds))
                            @php $rSuffix = match ($sch->reminder_unit ?? 'days') { 'km' => 'km remaining', 'hours' => 'hrs remaining', default => 'days before' }; @endphp
                            <div class="sm:col-span-2">
                                <dt class="{{ $schDt }}">Smart Reminders</dt>
                                <dd class="mt-1 flex flex-wrap gap-1">
                                    @foreach (array_reverse(array_values($sch->reminder_thresholds)) as $t)
                                        <span class="rounded-full bg-blue-400/10 px-2.5 py-0.5 text-[11px] font-semibold text-blue-400">{{ number_format(is_array($t) ? $t['value'] : $t) }} {{ $rSuffix }}</span>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>

                {{-- ── Notes ── --}}
                <div class="border-t border-zinc-100 pt-4 dark:border-zinc-800">
                    <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400">Notes</p>
                    <dl class="space-y-4">

                        {{-- Description --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $schDt }}">Description</dt>
                            <dd class="mt-0.5 flex items-start gap-1.5">
                                <span x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200" x-text="description || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $schBtnX }} mt-0.5">{!! $schPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex w-full flex-col gap-1">
                                        <textarea x-ref="taDesc" rows="2" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" x-text="description"></textarea>
                                        <span class="flex gap-1">
                                            <button type="button" class="{{ $schBtnOk }}" @click="if(await schp('description',$refs.taDesc.value)){description=$refs.taDesc.value;editing=false}">{!! $schCheck !!}</button>
                                            <button type="button" class="{{ $schBtnX }}" @click="editing=false">{!! $schX !!}</button>
                                        </span>
                                    </span>
                                </template>
                            </dd>
                        </div>

                        {{-- Notes --}}
                        <div x-data="{ editing: false }">
                            <dt class="{{ $schDt }}">Notes</dt>
                            <dd class="mt-0.5 flex items-start gap-1.5">
                                <span x-show="!editing" class="whitespace-pre-line text-sm text-zinc-800 dark:text-zinc-200" x-text="notes || '--'"></span>
                                <button x-show="!editing" type="button" @click="editing=true" class="{{ $schBtnX }} mt-0.5">{!! $schPencil !!}</button>
                                <template x-if="editing">
                                    <span class="flex w-full flex-col gap-1">
                                        <textarea x-ref="taNotes" rows="2" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-900 focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100" x-text="notes"></textarea>
                                        <span class="flex gap-1">
                                            <button type="button" class="{{ $schBtnOk }}" @click="if(await schp('notes',$refs.taNotes.value)){notes=$refs.taNotes.value;editing=false}">{!! $schCheck !!}</button>
                                            <button type="button" class="{{ $schBtnX }}" @click="editing=false">{!! $schX !!}</button>
                                        </span>
                                    </span>
                                </template>
                            </dd>
                        </div>

                    </dl>
                </div>

            </div>
        </x-modal>

    @endforeach

    {{-- Schedules Grid --}}
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
                    $dueLabel = $sch->next_due_date
                        ? $sch->next_due_date->format('d M Y')
                        : ($lastDoneDate ? 'Not calculated' : 'Not started');
                    $subLabel = $sch->interval_value && $sch->interval_unit
                        ? 'Every ' . $sch->interval_value . ' ' . $sch->interval_unit
                        : '';
                }
            @endphp

            <div class="flex flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">

                {{-- Card Header --}}
                <div class="flex items-center justify-between gap-2 border-b border-zinc-200 bg-zinc-50 px-4 py-3 dark:border-zinc-800 dark:bg-zinc-800/40">
                    <div class="flex min-w-0 items-center gap-2">
                        <span class="shrink-0 rounded-full px-1.5 py-0.5 text-[10px] font-semibold {{ $svcColor }}">{{ $svcLabel }}</span>
                        <p class="truncate text-sm font-semibold text-zinc-800 dark:text-zinc-100">{{ $sch->schedule_name }}</p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1.5">
                        <a href="{{ route('assets.show', [$asset, 'tab' => 'reminders', 'showform' => '1', 'scheduleid' => $sch->id]) }}"
                           title="{{ $sch->smartReminders->isNotEmpty() ? 'Manage Reminders' : 'Add Reminder' }}"
                           class="inline-flex size-6 items-center justify-center rounded-md border transition-colors {{ $sch->smartReminders->isNotEmpty() ? 'border-blue-500/40 text-blue-400 hover:bg-blue-500/10' : 'border-yellow-500/40 text-yellow-400 hover:bg-yellow-500/10' }}">
                            <flux:icon.bell-alert class="size-3.5" />
                        </a>
                        <button type="button" x-on:click="$dispatch('open-modal-view-schedule-{{ $sch->id }}')"
                                title="View Details"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.eye class="size-3.5" />
                        </button>
                        {{-- <button type="button" x-on:click="$dispatch('open-modal-edit-schedule-{{ $sch->id }}')"
                                title="Edit"
                                class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-600 transition-colors hover:border-accent hover:text-accent dark:border-zinc-700 dark:text-zinc-300">
                            <flux:icon.pencil class="size-3.5" />
                        </button>
                        <form method="POST" action="{{ route('assets.maintenance-schedules.destroy', [$asset, $sch]) }}"
                              onsubmit="confirmDelete(this, 'Delete this maintenance schedule?'); return false;">
                            @csrf @method('DELETE')
                            <button type="submit" title="Delete"
                                    class="inline-flex size-6 items-center justify-center rounded-md border border-zinc-300 text-zinc-500 transition-colors hover:border-red-500/60 hover:text-red-400 dark:border-zinc-700">
                                <flux:icon.trash class="size-3.5" />
                            </button>
                        </form> --}}
                    </div>
                </div>

                {{-- Card Body --}}
                <div class="flex-1 space-y-3 px-4 py-3">

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-zinc-500">Interval</p>
                            <p class="mt-0.5 text-xs text-zinc-700 dark:text-zinc-300">{{ $subLabel ?: '—' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-zinc-500">
                                @if ($sch->schedule_type === 'mileage') Km Remaining
                                @elseif ($sch->schedule_type === 'operating_hours') Hours Remaining
                                @else Next Due
                                @endif
                            </p>
                            <p class="mt-0.5 text-xs font-semibold {{ $status === 'overdue' ? 'text-red-400' : ($status === 'due-soon' ? 'text-yellow-400' : 'text-zinc-800 dark:text-zinc-100') }}">
                                {{ $dueLabel }}
                            </p>
                        </div>
                    </div>

                    @if ($lastDoneDate || $lastDoneKm || $lastDoneHours)
                        <div>
                            <p class="text-[10px] font-medium uppercase tracking-wide text-zinc-500">Last Done</p>
                            <p class="mt-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                                @if ($lastDoneDate) {{ $lastDoneDate->format('d M Y') }} @endif
                                @if ($lastDoneKm) · {{ number_format($lastDoneKm) }} km @endif
                                @if ($lastDoneHours) · {{ number_format($lastDoneHours) }} hrs @endif
                            </p>
                            @if ($fromHistory)
                                <p class="mt-0.5 text-[10px] text-zinc-400">Auto-read from service history</p>
                            @endif
                        </div>
                    @endif

                    @if (! empty($sch->reminder_thresholds))
                        @php
                            $rUnit   = $sch->reminder_unit ?? 'days';
                            $rSuffix = match ($rUnit) { 'km' => 'km remaining', 'hours' => 'hrs remaining', default => 'days before' };
                        @endphp
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 dark:border-zinc-700 dark:bg-zinc-800/50">
                            <div class="mb-1.5 flex items-center gap-1.5">
                                <flux:icon.bell-alert class="size-3 text-blue-400" />
                                <span class="text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Smart Reminder</span>
                            </div>
                            <div class="flex flex-wrap gap-1">
                                @foreach (array_reverse(array_values($sch->reminder_thresholds)) as $t)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-400/10 px-2.5 py-0.5 text-[11px] font-semibold text-blue-400">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-2.5 opacity-70"><path fill-rule="evenodd" d="M8 14a.75.75 0 0 1-.75-.75V4.56L4.03 7.78a.75.75 0 0 1-1.06-1.06l4.5-4.5a.75.75 0 0 1 1.06 0l4.5 4.5a.75.75 0 0 1-1.06 1.06L8.75 4.56v8.69A.75.75 0 0 1 8 14Z" clip-rule="evenodd"/></svg>
                                        {{ number_format(is_array($t) ? $t['value'] : $t) }} {{ $rSuffix }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($sch->description)
                        <p class="line-clamp-2 text-xs text-zinc-500">{{ $sch->description }}</p>
                    @endif

                </div>

                {{-- Card Footer --}}
                <div class="flex h-9 shrink-0 items-center justify-between border-t border-zinc-100 px-4 dark:border-zinc-800">
                    <span class="whitespace-nowrap rounded-full px-2 py-0.5 text-[11px] font-medium {{ $sch->is_active ? $statusColor : 'bg-zinc-200/60 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' }}">
                        {{ $sch->is_active ? $statusText : 'Inactive' }}
                    </span>
                    <button type="button" x-on:click="$dispatch('open-modal-complete-schedule-{{ $sch->id }}')"
                            class="inline-flex items-center gap-1 rounded-md bg-green-500/10 px-2.5 py-1 text-xs font-medium text-green-500 transition-colors hover:bg-green-500/20">
                        <flux:icon.check class="size-3" />
                        Done
                    </button>
                </div>

            </div>
        @endforeach

        {{-- Placeholder --}}
        <div class="rounded-xl border border-dashed border-zinc-300 bg-zinc-50 p-4 text-center transition-colors hover:border-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-accent">
            <flux:icon.calendar-days class="mx-auto size-10 text-zinc-600" />
            <flux:heading class="mt-4 text-zinc-400">
                {{ $allSchedules->isEmpty() ? 'No Schedules Yet' : 'Add Another Schedule' }}
            </flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600">Track recurring maintenance by date, mileage, or operating hours.</flux:text>
            <div class="mt-4">
                <button type="button" x-on:click="$dispatch('open-modal-add-schedule')"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-zinc-300 px-3 py-1.5 text-xs font-medium text-zinc-500 transition-colors hover:text-zinc-700 dark:border-zinc-700 dark:hover:text-zinc-300">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3.5"><path d="M8.75 3.75a.75.75 0 0 0-1.5 0v3.5h-3.5a.75.75 0 0 0 0 1.5h3.5v3.5a.75.75 0 0 0 1.5 0v-3.5h3.5a.75.75 0 0 0 0-1.5h-3.5v-3.5Z"/></svg>
                    {{ $allSchedules->isEmpty() ? 'Add First Schedule' : 'Add Schedule' }}
                </button>
            </div>
        </div>
    </div>

</div>
