@php
    $isEdit         = isset($schedule) && $schedule !== null;
    $old            = fn($field, $default = '') => old($field, $isEdit ? ($schedule?->$field ?? $default) : $default);
    $oldServiceType = old('service_type', $isEdit
        ? ($schedule?->service_type ?? 'preventive_maintenance')
        : ($defaultServiceType ?? 'preventive_maintenance'));
    $oldType        = old('schedule_type', $isEdit ? ($schedule?->schedule_type ?? 'date') : 'date');
    $oldThresh      = old('reminder_thresholds_input', $isEdit ? implode(', ', $schedule?->reminder_thresholds ?? []) : '');
@endphp

<div x-data="{ serviceType: '{{ $oldServiceType }}', type: '{{ $oldType }}' }">

    {{-- Service Type --}}
    <div class="relative">
        <select name="service_type" x-model="serviceType"
                class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
            <option value="preventive_maintenance" @selected($oldServiceType === 'preventive_maintenance')>Preventive Maintenance</option>
            <option value="corrective_maintenance" @selected($oldServiceType === 'corrective_maintenance')>Corrective Maintenance</option>
            <option value="inspection"             @selected($oldServiceType === 'inspection')>Inspection</option>
            <option value="repair"                 @selected($oldServiceType === 'repair')>Repair</option>
            <option value="calibration"            @selected($oldServiceType === 'calibration')>Calibration</option>
            <option value="cleaning"               @selected($oldServiceType === 'cleaning')>Cleaning</option>
            <option value="other"                  @selected($oldServiceType === 'other')>Other</option>
        </select>
        <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500">Service Type <span class="text-red-400">*</span></label>
        @error('service_type') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Schedule Name --}}
    <div class="relative mt-4">
        <input type="text" name="schedule_name" placeholder=" " value="{{ $old('schedule_name') }}"
               class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 placeholder-transparent focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
        <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent">
            Schedule Name <span class="text-red-400">*</span>
        </label>
        @error('schedule_name') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Description --}}
    <div class="relative mt-4">
        <textarea name="description" placeholder=" " rows="2"
                  class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 placeholder-transparent focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">{{ $old('description') }}</textarea>
        <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent">
            Description
        </label>
    </div>

    {{-- Schedule Type — always visible for all service types --}}
    <div class="mt-4">
        <div class="relative">
            <select name="schedule_type" x-model="type"
                    class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                <option value="date"            @selected($oldType === 'date')>Date / Time Based</option>
                <option value="mileage"         @selected($oldType === 'mileage')>Mileage Based (km)</option>
                <option value="operating_hours" @selected($oldType === 'operating_hours')>Operating Hours Based</option>
            </select>
            <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500">Schedule Type <span class="text-red-400">*</span></label>
            @error('schedule_type') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>
    </div>

    {{-- Date-based fields --}}
    <div x-show="type === 'date'" x-cloak class="mt-4 space-y-4">
        <div class="grid grid-cols-2 gap-3">
            <div class="relative">
                <input type="number" name="interval_value" placeholder=" " min="1"
                       value="{{ $old('interval_value') }}"
                       class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 placeholder-transparent focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent">
                    Every (number) <span class="text-red-400">*</span>
                </label>
                @error('interval_value') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="relative">
                <select name="interval_unit"
                        class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">
                    <option value="days"   @selected($old('interval_unit') === 'days')>Days</option>
                    <option value="weeks"  @selected($old('interval_unit') === 'weeks')>Weeks</option>
                    <option value="months" @selected($old('interval_unit', 'months') === 'months')>Months</option>
                    <option value="years"  @selected($old('interval_unit') === 'years')>Years</option>
                </select>
                <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500">Unit <span class="text-red-400">*</span></label>
                @error('interval_unit') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="relative"
             x-data
             x-init="flatpickr($el.querySelector('input'), { dateFormat: 'Y-m-d', allowInput: true })">
            <input type="text" name="last_done_date" placeholder=" "
                   value="{{ $old('last_done_date') ? \Carbon\Carbon::parse($old('last_done_date'))->format('Y-m-d') : '' }}"
                   class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 placeholder-transparent focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
            <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent">
                Last Done Date (manual override)
            </label>
            <p class="mt-1 text-[10px] text-zinc-400">Leave blank to auto-read from service history.</p>
        </div>
    </div>

    {{-- Mileage-based fields --}}
    <div x-show="type === 'mileage'" x-cloak class="mt-4 space-y-4">
        <div class="grid grid-cols-2 gap-3">
            <div class="relative">
                <input type="number" name="interval_km" placeholder=" " min="1"
                       value="{{ $old('interval_km') }}"
                       class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 placeholder-transparent focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent">
                    Every (km) <span class="text-red-400">*</span>
                </label>
                @error('interval_km') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="relative">
                <input type="number" name="last_done_km" placeholder=" " min="0"
                       value="{{ $old('last_done_km') }}"
                       class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 placeholder-transparent focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent">
                    Last Done at (km, manual override)
                </label>
            </div>
        </div>
        <p class="text-[10px] text-zinc-400">Last Done km is auto-read from matching service records if left blank.</p>
    </div>

    {{-- Operating hours-based fields --}}
    <div x-show="type === 'operating_hours'" x-cloak class="mt-4 space-y-4">
        <div class="grid grid-cols-2 gap-3">
            <div class="relative">
                <input type="number" name="interval_hours" placeholder=" " min="1"
                       value="{{ $old('interval_hours') }}"
                       class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 placeholder-transparent focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent">
                    Every (hours) <span class="text-red-400">*</span>
                </label>
                @error('interval_hours') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
            </div>
            <div class="relative">
                <input type="number" name="last_done_hours" placeholder=" " min="0"
                       value="{{ $old('last_done_hours') }}"
                       class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 placeholder-transparent focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100" />
                <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent">
                    Last Done at (hours, manual override)
                </label>
            </div>
        </div>
        <p class="text-[10px] text-zinc-400">Last Done hours is auto-read from matching service records if left blank.</p>
    </div>

    {{-- Reminder Thresholds — tag repeater --}}
    <div class="mt-4">
        <p class="mb-1.5 text-[10px] font-medium text-zinc-500">
            Remind when
            <span x-show="type === 'mileage'" x-cloak>(km remaining)</span>
            <span x-show="type === 'operating_hours'" x-cloak>(hours remaining)</span>
            <span x-show="type === 'date'" x-cloak>(days before)</span>
            <span class="text-red-400">*</span>
        </p>
        <div x-data="reminderDaysPicker({{ json_encode(array_map('intval', array_filter(explode(',', $oldThresh)))) }})"
             class="rounded-lg border border-zinc-300 bg-white p-2.5 dark:border-zinc-700 dark:bg-zinc-900">
            {{-- Tags --}}
            <div class="flex flex-wrap gap-1.5 mb-2">
                <template x-for="(day, i) in days" :key="i">
                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-400/10 px-2.5 py-0.5 text-xs font-semibold text-blue-400">
                        <span x-text="day + (type === 'mileage' ? ' km' : type === 'operating_hours' ? ' hrs' : 'd')"></span>
                        <button type="button" @click="remove(i)"
                                class="ml-0.5 rounded-full text-blue-400 hover:text-red-400 transition-colors leading-none">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="size-3"><path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z"/></svg>
                        </button>
                    </span>
                </template>
            </div>
            {{-- Input row --}}
            <div class="flex items-center gap-2">
                <input type="number" min="1" x-model.number="inputVal"
                       @keydown.enter.prevent="add()"
                       :placeholder="type === 'mileage' ? 'e.g. 2000' : type === 'operating_hours' ? 'e.g. 50' : 'e.g. 30'"
                       class="w-28 rounded-md border border-zinc-300 bg-zinc-50 px-2.5 py-1 text-sm text-zinc-900 focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100" />
                <span class="text-xs text-zinc-500"
                      x-text="type === 'mileage' ? 'km remaining' : type === 'operating_hours' ? 'hours remaining' : 'days before'">
                </span>
                <button type="button" @click="add()"
                        class="inline-flex items-center gap-1 rounded-md border border-zinc-300 px-2 py-1 text-xs font-medium text-zinc-600 hover:border-accent hover:text-accent transition-colors dark:border-zinc-700 dark:text-zinc-300">
                    <svg class="size-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Add
                </button>
            </div>
            {{-- Hidden serialized value --}}
            <input type="hidden" name="reminder_thresholds_input" :value="days.join(',')">
        </div>
        @error('reminder_thresholds_input') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
    </div>

    {{-- Notes --}}
    <div class="mt-4 relative">
        <textarea name="notes" placeholder=" " rows="2"
                  class="peer block w-full rounded-lg border border-zinc-300 bg-white px-3 pb-2 pt-5 text-sm text-zinc-900 placeholder-transparent focus:border-accent focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100">{{ $old('notes') }}</textarea>
        <label class="absolute left-3 top-2 text-[10px] font-medium text-zinc-500 transition-all peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-sm peer-placeholder-shown:text-zinc-400 peer-focus:top-2 peer-focus:text-[10px] peer-focus:text-accent">
            Notes
        </label>
    </div>

    {{-- Active toggle --}}
    <label class="mt-4 flex items-center gap-2 cursor-pointer">
        <input type="hidden" name="is_active" value="0" />
        <input type="checkbox" name="is_active" value="1"
               @checked($old('is_active', $isEdit ? $schedule?->is_active : true))
               class="rounded border-zinc-300 text-accent focus:ring-accent dark:border-zinc-700" />
        <span class="text-sm text-zinc-700 dark:text-zinc-300">Active</span>
    </label>

</div>
