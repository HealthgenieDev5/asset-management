@php
    $typeOptions = [
        'warranty'          => 'Original Warranty',
        'extended_warranty' => 'Extended Warranty',
        'amc'               => 'AMC Contract',
        'insurance'         => 'Insurance Policy',
        'puc'               => 'PUC Expiry',
        'fitness'           => 'Fitness Certificate',
        'road_tax'          => 'Road Tax',
        'service_due'       => 'Service Due',
        'certification'     => 'Certification Expiry',
        'part_warranty'     => 'Part Warranty',
        'custom'            => 'Custom',
    ];
    $isEdit      = isset($reminder) && $reminder !== null;
    $old         = fn($field, $default = '') => old($field, $isEdit ? ($reminder?->$field ?? $default) : $default);
    $initMode    = old('reminder_mode', $isEdit ? ($reminder?->reminder_mode ?? 'time') : 'time');
    $initDays    = old('reminder_days_input', $isEdit ? implode(', ', $reminder?->reminder_days ?? []) : '');
    $initDaysArr = array_values(array_filter(array_map('intval', array_filter(explode(',', str_replace(' ', '', $initDays))))));
    $latestReading = $isEdit && $reminder?->threshold_unit
        ? $asset->latestMeterReading($reminder->threshold_unit)
        : null;
    $uid = $isEdit ? $reminder->id : 'new';
    $inp = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition placeholder:text-transparent focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
    $sel = 'peer w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 shadow-sm transition focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 dark:focus:border-accent';
    $lbl = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-zinc-500 dark:text-zinc-400';
    $lbs = 'pointer-events-none absolute left-3 top-2 text-[10px] font-medium text-zinc-500 dark:text-zinc-400';
    $err = 'mt-0.5 text-[11px] text-red-400';
    $cal = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="size-4"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" /></svg>';
@endphp

<div x-data="{
        mode: '{{ $initMode }}',
        unit: '{{ old('threshold_unit', $reminder?->threshold_unit ?? '') }}',
        get thresholdLabel() {
            if (this.mode === 'time') return 'days before expiry';
            return (this.unit || 'units') + ' remaining';
        }
    }" class="space-y-4">

    {{-- Hidden mode field --}}
    <input type="hidden" name="reminder_mode" :value="mode">

    {{-- ── Mode Toggle ── --}}
    <div>
        <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">Tracking Mode</p>
        <div class="flex gap-2">
            <button type="button" @click="mode = 'time'"
                :class="mode === 'time' ? 'bg-accent text-accent-foreground' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Date-based
            </button>
            <button type="button" @click="mode = 'meter'"
                :class="mode === 'meter' ? 'bg-accent text-accent-foreground' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Meter-based
            </button>
            <button type="button" @click="mode = 'count'"
                :class="mode === 'count' ? 'bg-accent text-accent-foreground' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700'"
                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition-colors">
                Count-based
            </button>
        </div>
        <p class="mt-1.5 text-[11px] text-zinc-400 dark:text-zinc-500"
           x-show="mode === 'time'" x-cloak>Remind N days before a fixed expiry date.</p>
        <p class="mt-1.5 text-[11px] text-zinc-400 dark:text-zinc-500"
           x-show="mode === 'meter'" x-cloak>Remind when N units remain based on meter readings (km, hours, etc.).</p>
        <p class="mt-1.5 text-[11px] text-zinc-400 dark:text-zinc-500"
           x-show="mode === 'count'" x-cloak>Remind when N uses remain (prints, cycles, etc.).</p>
    </div>

    {{-- ── Common Fields ── --}}
    <div class="relative">
        <input type="text" name="reminder_name" id="reminder_name_{{ $uid }}"
               placeholder=" " value="{{ $old('reminder_name') }}"
               class="{{ $inp }}" />
        <label for="reminder_name_{{ $uid }}" class="{{ $lbl }}">
            Reminder Name <span class="text-red-400">*</span>
        </label>
        @error('reminder_name') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    <div class="relative">
        <select name="reminder_type" id="reminder_type_{{ $uid }}" class="{{ $sel }}">
            @foreach ($typeOptions as $val => $label)
                <option value="{{ $val }}" @selected($old('reminder_type') === $val)>{{ $label }}</option>
            @endforeach
        </select>
        <label for="reminder_type_{{ $uid }}" class="{{ $lbs }}">
            Reminder Type <span class="text-red-400">*</span>
        </label>
        @error('reminder_type') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- ── Date Mode Fields ── --}}
    <div x-show="mode === 'time'" x-cloak>
        <div x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', altInput: true, altFormat: 'd M Y', allowInput: true, disableMobile: true })">
            <div class="relative w-full">
                <input type="text" inputmode="none" name="expiry_date" id="expiry_date_{{ $uid }}"
                       value="{{ $old('expiry_date') ? \Carbon\Carbon::parse($old('expiry_date'))->format('Y-m-d') : '' }}"
                       placeholder=" " autocomplete="off" class="{{ $inp }} pr-9" />
                <label for="expiry_date_{{ $uid }}" class="{{ $lbl }}">
                    Expiry Date <span class="text-red-400">*</span>
                </label>
                <span class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400">{!! $cal !!}</span>
            </div>
            @error('expiry_date') <p class="{{ $err }}">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- ── Meter / Count Mode Fields ── --}}
    <div x-show="mode !== 'time'" x-cloak class="space-y-4">
        <div class="grid grid-cols-2 gap-3">
            {{-- Unit --}}
            <div class="relative">
                <input type="text" name="threshold_unit" id="threshold_unit_{{ $uid }}"
                       placeholder=" " value="{{ $old('threshold_unit') }}"
                       list="threshold-unit-list-{{ $uid }}"
                       x-model="unit" autocomplete="off"
                       class="{{ $inp }}" />
                <label for="threshold_unit_{{ $uid }}" class="{{ $lbl }}">
                    Unit <span class="text-red-400">*</span>
                    <span class="font-normal text-zinc-400">(km, hours, prints…)</span>
                </label>
                <datalist id="threshold-unit-list-{{ $uid }}">
                    <option value="km">
                    <option value="hours">
                    <option value="prints">
                    <option value="cycles">
                    <option value="litres">
                </datalist>
                @error('threshold_unit') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>
            {{-- Counter Limit --}}
            <div class="relative">
                <input type="number" name="counter_limit" id="counter_limit_{{ $uid }}"
                       placeholder=" " min="1"
                       value="{{ $old('counter_limit') }}"
                       class="{{ $inp }}" />
                <label for="counter_limit_{{ $uid }}" class="{{ $lbl }}">
                    Total Limit <span class="text-red-400">*</span>
                    <span class="font-normal text-zinc-400">(e.g. 100000)</span>
                </label>
                @error('counter_limit') <p class="{{ $err }}">{{ $message }}</p> @enderror
            </div>
        </div>

        {{-- Latest reading hint --}}
        @if ($isEdit && $latestReading !== null)
            <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                Latest meter reading for <strong>{{ $reminder->threshold_unit }}</strong>:
                <span class="font-semibold text-zinc-700 dark:text-zinc-200">{{ number_format($latestReading) }} {{ $reminder->threshold_unit }}</span>
                @if ($reminder->counter_limit)
                    &nbsp;·&nbsp;
                    <span class="{{ max(0, $reminder->counter_limit - $latestReading) <= (count($reminder->reminder_days ?? []) ? max($reminder->reminder_days) : 0) ? 'text-yellow-500' : 'text-green-500' }} font-semibold">
                        {{ number_format(max(0, $reminder->counter_limit - $latestReading)) }} {{ $reminder->threshold_unit }} remaining
                    </span>
                @endif
            </p>
        @elseif ($isEdit)
            <p class="text-[11px] text-zinc-400">No meter readings logged yet for this unit. Add one in the <strong>Meter Logs</strong> tab.</p>
        @endif
    </div>

    {{-- ── Reminder Thresholds (tag repeater) ── --}}
    <div>
        <p class="mb-1.5 text-[10px] font-medium text-zinc-500">
            Remind when
            <span x-text="'N ' + thresholdLabel" class="font-semibold"></span>
            <span class="text-red-400">*</span>
        </p>
        <div x-data="reminderDaysPicker({{ json_encode($initDaysArr) }})"
             class="rounded-lg border border-zinc-300 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-900">
            {{-- Tags --}}
            <div class="flex flex-wrap gap-1.5 mb-2">
                <template x-for="(day, i) in days" :key="i">
                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-400/10 px-2.5 py-0.5 text-xs font-semibold text-blue-400">
                        <span x-text="day + ' ' + thresholdLabel"></span>
                        <button type="button" @click="remove(i)"
                                class="ml-0.5 rounded-full text-blue-400 hover:text-red-400 transition-colors leading-none">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3"><path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-3.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z"/></svg>
                        </button>
                    </span>
                </template>
            </div>
            {{-- Input row --}}
            <div class="flex items-center gap-2">
                <input type="number" min="1" x-model.number="inputVal"
                       @keydown.enter.prevent="add()"
                       placeholder="e.g. 30"
                       class="w-24 rounded-md border border-zinc-300 bg-zinc-50 px-2.5 py-1 text-sm text-zinc-900 focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                <span class="text-xs text-zinc-500" x-text="thresholdLabel"></span>
                <button type="button" @click="add()"
                        class="inline-flex items-center gap-1 rounded-md border border-zinc-300 px-2 py-1 text-xs font-medium text-zinc-600 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-300">
                    <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Add
                </button>
            </div>
            {{-- Quick add presets --}}
            <div x-show="mode === 'time'" class="mt-1.5 text-[11px] text-zinc-500">
                Quick add:
                <template x-for="preset in [2,7,15,30,60,90]" :key="preset">
                    <button type="button" @click="addPreset(preset)"
                            class="ml-1 rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] font-mono text-zinc-600 hover:bg-blue-100 hover:text-blue-600 transition-colors dark:bg-zinc-800 dark:text-zinc-400">
                        <span x-text="preset + 'd'"></span>
                    </button>
                </template>
            </div>
            <div x-show="mode !== 'time'" class="mt-1.5 text-[11px] text-zinc-500">
                Quick add:
                <template x-for="preset in [500, 1000, 1500, 2000, 5000]" :key="preset">
                    <button type="button" @click="addPreset(preset)"
                            class="ml-1 rounded bg-zinc-100 px-1.5 py-0.5 text-[11px] font-mono text-zinc-600 hover:bg-blue-100 hover:text-blue-600 transition-colors dark:bg-zinc-800 dark:text-zinc-400">
                        <span x-text="preset"></span>
                    </button>
                </template>
            </div>
            {{-- Hidden serialized value --}}
            <input type="hidden" name="reminder_days_input" :value="days.join(',')">
        </div>
        @error('reminder_days_input') <p class="{{ $err }}">{{ $message }}</p> @enderror
    </div>

    {{-- ── Notes ── --}}
    <div class="relative">
        <textarea name="notes" id="notes_{{ $uid }}" placeholder=" " rows="2"
                  class="{{ $inp }}">{{ $old('notes') }}</textarea>
        <label for="notes_{{ $uid }}" class="{{ $lbl }}">Notes</label>
    </div>

    {{-- ── Active toggle ── --}}
    <label class="flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="is_active" value="0" />
        <input type="checkbox" name="is_active" value="1"
               @checked($old('is_active', $isEdit ? $reminder?->is_active : true))
               class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-700" />
        <span class="text-sm text-zinc-700 dark:text-zinc-300">Active</span>
    </label>

</div>
